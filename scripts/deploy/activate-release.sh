#!/usr/bin/env bash
set -euo pipefail
umask 077

test "$(id -u)" -eq 0 || { printf '%s\n' 'Run this script as root on the VPS.' >&2; exit 1; }
release_id=${1:?Usage: activate-release.sh RELEASE_ID --data-reviewed}
test "${2:-}" = --data-reviewed || { printf '%s\n' 'Review the production database, media and migrations before activation.' >&2; exit 1; }
[[ "$release_id" =~ ^[A-Za-z0-9][A-Za-z0-9._-]*$ ]]
base=/var/www/mansecretpleasure
release="$base/releases/$release_id"
test -f "$release/artisan"
test ! -e "$base/current.next" && test ! -L "$base/current.next"
test ! -e "$base/current.recover" && test ! -L "$base/current.recover"
runuser -u mansecret-deploy -- sh -c 'cd "$1" && php8.3 artisan package:discover --ansi && php8.3 artisan config:cache && php8.3 artisan route:cache && php8.3 artisan view:cache' sh "$release"
chown -R mansecret-deploy:www-data "$release/bootstrap/cache"
find "$release/bootstrap/cache" "$base/shared/storage/framework" -type f -exec chmod 660 {} +
runuser -u mansecret-deploy -- php8.3 "$release/scripts/deploy/check-production.php"
runuser -u www-data -- php8.3 "$release/scripts/deploy/check-production.php"
backup="$base/backups/before-$release_id-$(date -u +%Y%m%dT%H%M%SZ)"
install -d -m 700 -o mansecret-deploy -g mansecret-deploy "$backup"
previous=''
if test -L "$base/current"; then
    previous=$(readlink -f "$base/current")
    test -f "$previous/artisan"
elif test -e "$base/current"; then
    printf '%s\n' 'Current deployment is not a release symlink; inspect and preserve it before activation.' >&2
    exit 1
fi
recover_previous() {
    if test -n "$previous"; then
        ln -s "$previous" "$base/current.recover"
        mv -Tf "$base/current.recover" "$base/current"
        systemctl reload php8.3-fpm
        runuser -u mansecret-deploy -- php8.3 "$previous/artisan" up
        systemctl start mansecretpleasure-queue.service mansecretpleasure-scheduler.timer
    fi
}
trap recover_previous ERR
systemctl stop mansecretpleasure-scheduler.timer mansecretpleasure-scheduler.service mansecretpleasure-queue.service
if test -n "$previous"; then
    runuser -u mansecret-deploy -- php8.3 "$previous/artisan" down --retry=60
    printf '%s\n' "$previous" > "$backup/previous-release"
fi
runuser -u mansecret-deploy -- mysqldump --defaults-extra-file="$base/shared/.mysql.cnf" \
    --single-transaction --no-tablespaces --set-gtid-purged=OFF mansecretpleasure > "$backup/database.sql"
tar -czf "$backup/media.tar.gz" -C "$base/shared/storage" app/public app/private
cp -a "$base/shared/.env" "$backup/production.env"
test ! -e "$base/current.next"
ln -s "$release" "$base/current.next"
mv -Tf "$base/current.next" "$base/current"
systemctl reload php8.3-fpm
runuser -u mansecret-deploy -- php8.3 "$base/current/artisan" up
systemctl enable --now mansecretpleasure-queue.service mansecretpleasure-scheduler.timer
trap - ERR
printf 'Release activated; perform live HTTPS/commerce checks. Backup: %s\n' "$backup"
