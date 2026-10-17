#!/usr/bin/env bash
set -euo pipefail
umask 077

test "$(id -u)" -eq 0 || { printf '%s\n' 'Run this script as root on the VPS.' >&2; exit 1; }
release_id=${1:?Usage: migrate-reviewed.sh RELEASE_ID --pending-migrations-reviewed}
test "${2:-}" = --pending-migrations-reviewed || { printf '%s\n' 'Inspect migrate:status and every pending migration before running this script.' >&2; exit 1; }
[[ "$release_id" =~ ^[A-Za-z0-9][A-Za-z0-9._-]*$ ]]
base=/var/www/mansecretpleasure
release="$base/releases/$release_id"
test -f "$release/artisan"
test -f "$base/shared/.mysql.cnf"
test -f "$base/shared/.env"
runuser -u mansecret-deploy -- sh -c 'cd "$1" && php8.3 artisan package:discover --ansi' sh "$release"
backup="$base/backups/before-migrations-$release_id-$(date -u +%Y%m%dT%H%M%SZ)"
install -d -m 700 "$backup"
systemctl stop mansecretpleasure-scheduler.timer mansecretpleasure-scheduler.service mansecretpleasure-queue.service
if test -f "$base/current/artisan"; then
    runuser -u mansecret-deploy -- php8.3 "$base/current/artisan" down --retry=60
fi
runuser -u mansecret-deploy -- mysqldump --defaults-extra-file="$base/shared/.mysql.cnf" \
    --single-transaction --no-tablespaces --set-gtid-purged=OFF mansecretpleasure > "$backup/database.sql"
tar -czf "$backup/media.tar.gz" -C "$base/shared/storage" app/public app/private
cp -a "$base/shared/.env" "$backup/production.env"
runuser -u mansecret-deploy -- php8.3 "$release/artisan" migrate --force --no-interaction
printf 'Reviewed migrations completed. Backup: %s. Application remains in maintenance pending activation.\n' "$backup"
