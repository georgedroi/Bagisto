#!/usr/bin/env bash
set -euo pipefail
umask 077

project=$(cd "$(dirname "$0")/../.." && pwd)
archive=${1:?Usage: package-release.sh /secure/path/release.tar.gz}
archive=$(realpath -m "$archive")
case "$archive" in "$project"/*) printf '%s\n' 'Store the release archive outside the working copy.' >&2; exit 1;; esac
test ! -e "$archive" || { printf '%s\n' 'Refusing to overwrite an existing archive.' >&2; exit 1; }
test -f "$project/packages/Webkul/Shop/package-lock.json"
test -f "$project/public/themes/shop/default/build/manifest.json"
test -f "$project/public/themes/admin/default/build/manifest.json"
extra=()
if test -f "$project/release.json"; then extra+=(release.json); fi
mkdir -p "$(dirname "$archive")"
tar --create --gzip --file "$archive" --directory "$project" \
    --exclude='*/node_modules' --exclude='*/vendor' --exclude='*/tests' \
    --exclude='*.hot' --exclude='*.pem' --exclude='*.key' \
    --exclude='.env*' --exclude='bootstrap/cache/*.php' \
    --exclude='public/storage' --exclude='database/seeders/ManPleasureQaProductSeeder.php' \
    --exclude='*/.git' --exclude='*/.local' \
    app bootstrap config database lang packages public resources routes \
    artisan composer.json composer.lock package.json vite.config.js scripts/deploy \
    docker/production/mansecretpleasure "${extra[@]}"
chmod 600 "$archive"
sha256sum "$archive"
