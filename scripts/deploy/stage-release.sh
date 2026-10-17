#!/usr/bin/env bash
set -euo pipefail
umask 027

test "$(id -un)" = mansecret-deploy || { printf '%s\n' 'Run this script as mansecret-deploy on the VPS.' >&2; exit 1; }
archive=${1:?Usage: stage-release.sh /secure/path/release.tar.gz RELEASE_ID SHA256}
release_id=${2:?A unique release ID is required.}
expected_sha256=${3:?The reviewed archive SHA256 is required.}
[[ "$expected_sha256" =~ ^[a-fA-F0-9]{64}$ ]]
[[ "$release_id" =~ ^[A-Za-z0-9][A-Za-z0-9._-]*$ ]]
base=/var/www/mansecretpleasure
release="$base/releases/$release_id"
test -f "$base/shared/.env"
test ! -e "$release"
python3 - "$archive" "$expected_sha256" <<'PY'
import hashlib, pathlib, sys, tarfile
with open(sys.argv[1], 'rb') as source:
    if hashlib.file_digest(source, 'sha256').hexdigest() != sys.argv[2].lower():
        raise SystemExit('Archive SHA256 does not match the reviewed source.')
with tarfile.open(sys.argv[1], 'r:gz') as archive:
    for member in archive.getmembers():
        path = pathlib.PurePosixPath(member.name)
        if path.is_absolute() or '..' in path.parts or member.isdev() or member.issym() or member.islnk():
            raise SystemExit('Unsafe archive member; staging stopped.')
        if any(part.startswith('.env') or part in {'.git', '.local', 'node_modules', 'vendor'} for part in path.parts) or member.name.endswith(('.hot', '.pem', '.key')):
            raise SystemExit('Private or runtime archive member; staging stopped.')
PY
mkdir "$release"
tar -xzf "$archive" -C "$release" --no-same-owner --no-same-permissions
find "$release" -type d -exec chmod 2750 {} +
find "$release" -type f -exec chmod 640 {} +
ln -s "$base/shared/.env" "$release/.env"
ln -s "$base/shared/storage" "$release/storage"
chmod 2770 "$release/bootstrap/cache"
setfacl -m u:www-data:rwx,m::rwx "$release/bootstrap/cache"
setfacl -d -m u:www-data:rwx,m::rwx "$release/bootstrap/cache"
cd "$release"
composer validate --no-check-publish --strict
composer install --no-dev --prefer-dist --no-interaction --no-scripts --optimize-autoloader
composer check-platform-reqs --no-dev
ln -s "$base/shared/storage/app/public" "$release/public/storage"
(npm ci --no-audit --no-fund && npm run build)
(cd packages/Webkul/Shop && npm ci --no-audit --no-fund && npm run build)
printf 'Source and assets staged: %s. Import/review data before package discovery, cache commands, migrations and activation.\n' "$release_id"
