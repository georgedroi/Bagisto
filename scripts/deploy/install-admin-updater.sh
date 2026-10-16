#!/usr/bin/env bash
set -euo pipefail
umask 077

test "$(id -u)" -eq 0 || { printf '%s\n' 'Run this one-time server setup as root.' >&2; exit 1; }
project=$(cd "$(dirname "$0")/../.." && pwd)
base=/var/www/mansecretpleasure
test -L "$base/current"
test -f "$base/current/app/Http/Controllers/Admin/SystemUpdateController.php" || { printf '%s\n' 'Deploy the reviewed updater application files before installing its server worker.' >&2; exit 1; }
test -f "$base/shared/.env"
for binary in python3 php8.3 composer npm mysqldump tar setfacl curl runuser systemctl; do command -v "$binary" >/dev/null; done
id mansecret-deploy >/dev/null
id www-data >/dev/null
owner_ids=${1:-}
if test -n "$owner_ids"; then [[ "$owner_ids" =~ ^[1-9][0-9]*(,[1-9][0-9]*)*$ ]]; fi
updater_token=''
read -r -s -p 'GitHub read-only token (blank keeps the existing token or uses public access): ' updater_token
printf '\n'
[[ "$updater_token" =~ ^[A-Za-z0-9_]{0,255}$ ]]
install -d -m 0711 -o root -g root /var/lib/manpleasure-updater
install -d -m 0700 -o root -g root /var/lib/manpleasure-updater/logs /var/lib/manpleasure-updater/private
install -d -m 0750 -o root -g mansecret-deploy /var/lib/manpleasure-updater/artifacts
install -d -m 0750 -o root -g www-data /var/lib/manpleasure-updater/public
install -d -m 0755 -o root -g root /var/spool/manpleasure-updater
install -d -m 1770 -o root -g www-data /var/spool/manpleasure-updater/requests
install -d -m 0755 -o root -g root /usr/local/lib/manpleasure-updater
export MANPLEASURE_SETUP_TOKEN="$updater_token"
export MANPLEASURE_SETUP_OWNERS="$owner_ids"
python3 - <<'PY'
import json, os, pathlib, re, shutil, tempfile, time
config_path = pathlib.Path('/etc/manpleasure-updater.json')
if config_path.is_symlink():
    raise SystemExit('Inspect the worker configuration path before setup.')
existing = json.loads(config_path.read_text()) if config_path.exists() else {}
owners = [int(value) for value in os.environ['MANPLEASURE_SETUP_OWNERS'].split(',')] if os.environ['MANPLEASURE_SETUP_OWNERS'] else existing.get('owner_ids', [1])
if not owners or any(type(value) is not int or value < 1 for value in owners):
    raise SystemExit('The owner ID configuration is invalid.')
config = {'repository': 'georgedroi/Bagisto', 'owner_ids': owners, 'token': os.environ['MANPLEASURE_SETUP_TOKEN'] or existing.get('token', '')}
stamp = str(time.time_ns())
environment = pathlib.Path('/var/www/mansecretpleasure/shared/.env')
if environment.is_symlink() or not environment.is_file():
    raise SystemExit('Inspect the production environment path before setup.')
info = environment.stat()
backup = pathlib.Path('/var/lib/manpleasure-updater/private') / ('production-env-before-' + stamp)
shutil.copyfile(environment, backup)
os.chmod(backup, 0o600)
updates = {
    'MANPLEASURE_UPDATE_REPOSITORY': config['repository'],
    'MANPLEASURE_UPDATE_OWNER_IDS': ','.join(map(str, owners)),
    'MANPLEASURE_UPDATE_DEPLOY_ENABLED': 'true',
    'MANPLEASURE_UPDATE_TOKEN': config['token'],
}
lines = environment.read_text().splitlines()
for key, value in updates.items():
    lines = [line for line in lines if not re.match(r'^\s*' + re.escape(key) + r'\s*=', line)]
    lines.append(key + '=' + value)
fd, temporary = tempfile.mkstemp(prefix='.updater-env-', dir=environment.parent)
with os.fdopen(fd, 'w') as stream:
    stream.write('\n'.join(lines) + '\n')
os.chown(temporary, info.st_uid, info.st_gid)
os.chmod(temporary, info.st_mode & 0o777)
os.replace(temporary, environment)
if config_path.exists():
    shutil.copyfile(config_path, '/var/lib/manpleasure-updater/private/worker-config-before-' + stamp)
with open(config_path, 'w') as stream:
    json.dump(config, stream)
os.chown(config_path, 0, 0)
os.chmod(config_path, 0o600)
print('Configured permitted owner IDs: ' + ','.join(map(str, owners)))
print('Production environment backup: ' + str(backup))
PY
unset MANPLEASURE_SETUP_TOKEN MANPLEASURE_SETUP_OWNERS updater_token
systemctl stop manpleasure-updater.service 2>/dev/null || true
install -m 0644 -o root -g root "$project/scripts/deploy/admin-updater/worker.py" /usr/local/lib/manpleasure-updater/worker.py
for script in stage-release.sh activate-release.sh rollback-release.sh; do
    install -m 0644 -o root -g root "$project/scripts/deploy/$script" "/usr/local/lib/manpleasure-updater/$script"
done
install -m 0644 -o root -g root "$project/scripts/deploy/admin-updater/manpleasure-updater.service" /etc/systemd/system/manpleasure-updater.service
python3 -m py_compile /usr/local/lib/manpleasure-updater/worker.py
runuser -u mansecret-deploy -- php8.3 "$base/current/artisan" config:cache
systemctl daemon-reload
systemctl enable --now manpleasure-updater.service
systemctl reload php8.3-fpm
printf '%s\n' 'Server updater installed. Check its service status, then open Settings > System updates in the live admin.'
printf '%s\n' 'The worker performs code releases only. Migration changes stop for review; database restoration is never automatic.'
