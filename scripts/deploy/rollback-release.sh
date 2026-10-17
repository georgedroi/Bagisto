#!/usr/bin/env bash
set -euo pipefail
umask 077

test "$(id -u)" -eq 0 || { printf '%s\n' 'Run this script as root on the VPS.' >&2; exit 1; }
release_id=${1:?Usage: rollback-release.sh PREVIOUS_RELEASE_ID --schema-compatible}
test "${2:-}" = --schema-compatible || { printf '%s\n' 'Confirm the previous source is compatible with the current database. Database restore is a separate reviewed action.' >&2; exit 1; }
[[ "$release_id" =~ ^[A-Za-z0-9][A-Za-z0-9._-]*$ ]]
base=/var/www/mansecretpleasure
release="$base/releases/$release_id"
test -f "$release/artisan"
systemctl stop mansecretpleasure-scheduler.timer mansecretpleasure-scheduler.service mansecretpleasure-queue.service
runuser -u mansecret-deploy -- php8.3 "$base/current/artisan" down --retry=60
test ! -e "$base/current.next"
ln -s "$release" "$base/current.next"
mv -Tf "$base/current.next" "$base/current"
runuser -u mansecret-deploy -- php8.3 "$base/current/artisan" optimize:clear
runuser -u mansecret-deploy -- php8.3 "$base/current/artisan" config:cache
runuser -u mansecret-deploy -- php8.3 "$base/current/artisan" route:cache
runuser -u mansecret-deploy -- php8.3 "$base/current/artisan" view:cache
systemctl reload php8.3-fpm
runuser -u mansecret-deploy -- php8.3 "$base/current/artisan" up
systemctl start mansecretpleasure-queue.service mansecretpleasure-scheduler.timer
printf 'Code rollback complete: %s. Database and media preserved. Verify HTTPS and commerce.\n' "$release_id"
