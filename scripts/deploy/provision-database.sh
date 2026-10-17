#!/usr/bin/env bash
set -euo pipefail
umask 077

test "$(id -u)" -eq 0 || { printf '%s\n' 'Run this script as root on the VPS.' >&2; exit 1; }
environment=${1:?Usage: provision-database.sh /secure/path/reviewed-production.env}
test -f "$environment"
python3 - "$environment" <<'PY'
import base64, pathlib, re, sys
match = re.search(r'(?m)^APP_KEY=(.+)$', pathlib.Path(sys.argv[1]).read_text())
key = match.group(1).strip().strip('\"\'') if match else ''
try:
    key_bytes = base64.b64decode(key[7:], validate=True) if key.startswith('base64:') else key.encode()
except ValueError:
    raise SystemExit('A valid preserved APP_KEY is required; provisioning stopped.')
if len(key_bytes) != 32:
    raise SystemExit('A valid preserved APP_KEY is required; provisioning stopped.')
PY
base=/var/www/mansecretpleasure
test -d "$base/shared"
test ! -e "$base/shared/.env" || { printf '%s\n' 'Production environment already exists; preserve it and provision manually.' >&2; exit 1; }
existing=$(mysql --batch --skip-column-names --execute="SELECT (SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = 'mansecretpleasure') + (SELECT COUNT(*) FROM mysql.user WHERE User = 'mansecretpleasure')")
test "$existing" = 0 || { printf '%s\n' 'Database or application user exists; inspect existing data before proceeding.' >&2; exit 1; }
secret=$(openssl rand -hex 32)
private_temp=$(mktemp -d)
trap 'unset secret; rm -rf "$private_temp"' EXIT
printf "CREATE DATABASE mansecretpleasure CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nCREATE USER 'mansecretpleasure'@'127.0.0.1' IDENTIFIED BY '%s';\nGRANT ALL PRIVILEGES ON mansecretpleasure.* TO 'mansecretpleasure'@'127.0.0.1';\n" "$secret" > "$private_temp/provision.sql"
mysql < "$private_temp/provision.sql" 2> "$private_temp/error" || { printf '%s\n' 'Database provisioning failed; inspect server state privately before retrying.' >&2; exit 1; }
install -m 640 -o mansecret-deploy -g www-data "$environment" "$base/shared/.env"
printf '%s' "$secret" > "$private_temp/password"
python3 - "$base/shared/.env" "$private_temp/password" "$base/shared/.mysql.cnf" <<'PY'
import pathlib, re, sys
environment, password_file, options_file = map(pathlib.Path, sys.argv[1:])
password = password_file.read_text()
text = environment.read_text()
settings = {'APP_ENV': 'production', 'APP_DEBUG': 'false', 'APP_DEBUG_ALLOWED_IPS': '', 'APP_URL': 'https://mansecretpleasure.com', 'DB_CONNECTION': 'mysql', 'DB_HOST': '127.0.0.1', 'DB_PORT': '3306', 'DB_DATABASE': 'mansecretpleasure', 'DB_USERNAME': 'mansecretpleasure', 'DB_PASSWORD': password, 'CACHE_STORE': 'file', 'SESSION_DRIVER': 'database', 'SESSION_SECURE_COOKIE': 'true', 'SESSION_DOMAIN': 'null', 'QUEUE_CONNECTION': 'database', 'DB_QUEUE_RETRY_AFTER': '120', 'TRUSTED_PROXIES': ''}
for name, value in settings.items():
    line = name + '=' + value
    pattern = r'(?m)^' + re.escape(name) + r'=.*$'
    text = re.sub(pattern, lambda _: line, text) if re.search(pattern, text) else text.rstrip() + '\n' + line + '\n'
environment.write_text(text)
options_file.write_text('[client]\nhost=127.0.0.1\nuser=mansecretpleasure\npassword=' + password + '\ndatabase=mansecretpleasure\n')
options_file.chmod(0o600)
PY
chown mansecret-deploy:mansecret-deploy "$base/shared/.mysql.cnf"
printf '%s\n' 'Empty dedicated database and protected environment created. APP_KEY preserved. No data imported or seeded.'
