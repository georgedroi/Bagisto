#!/usr/bin/env bash
set -euo pipefail
umask 027

test "$(id -u)" -eq 0 || { printf '%s\n' 'Run this script as root on the VPS.' >&2; exit 1; }
public_key=${1:?Usage: prepare-origin.sh /secure/path/deployment-public-key.pub}
test -f "$public_key"
ssh-keygen -lf "$public_key" >/dev/null
. /etc/os-release
test "$ID" = ubuntu && test "$VERSION_ID" = 24.04
apt-get update
apt-get install -y acl rsync curl ca-certificates unzip xz-utils \
    php8.3-cli php8.3-fpm php8.3-mysql php8.3-bcmath php8.3-curl \
    php8.3-gd php8.3-intl php8.3-mbstring php8.3-xml php8.3-zip \
    nginx mysql-server certbot python3-certbot-nginx
id mansecret-deploy >/dev/null 2>&1 || useradd --create-home --shell /bin/bash mansecret-deploy
usermod -aG www-data mansecret-deploy
install -d -m 700 -o mansecret-deploy -g mansecret-deploy /home/mansecret-deploy/.ssh
key_file=/home/mansecret-deploy/.ssh/authorized_keys
touch "$key_file"
if ! grep -qF -- "$(cat "$public_key")" "$key_file"; then cat "$public_key" >> "$key_file"; fi
chown mansecret-deploy:mansecret-deploy "$key_file"
chmod 600 "$key_file"
base=/var/www/mansecretpleasure
install -d -m 2750 -o mansecret-deploy -g www-data "$base" "$base/releases" "$base/shared"
install -d -m 2700 -o mansecret-deploy -g mansecret-deploy "$base/backups"
for directory in app/public app/private framework/cache/data framework/sessions framework/views logs; do
    install -d -m 2770 -o mansecret-deploy -g www-data "$base/shared/storage/$directory"
done
setfacl -R -m u:mansecret-deploy:rwX,u:www-data:rwX,m::rwX "$base/shared/storage"
setfacl -R -d -m u:mansecret-deploy:rwx,u:www-data:rwx,m::rwx "$base/shared/storage"
install -d -m 755 /var/www/letsencrypt/.well-known/acme-challenge
systemctl enable --now nginx mysql php8.3-fpm
printf '%s\n' 'Infrastructure prepared. Test SSH as mansecret-deploy before changing firewall or root SSH access.'
