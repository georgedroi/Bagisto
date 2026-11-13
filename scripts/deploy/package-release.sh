#!/usr/bin/env bash
set -euo pipefail
umask 077

project=$(cd "$(dirname "$0")/../.." && pwd -P)
archive=${1:?Usage: package-release.sh /secure/path/release.tar.gz}
archive=$(realpath -m "$archive")
case "$archive" in "$project"/*) printf '%s\n' 'Store the release archive outside the working copy.' >&2; exit 1;; esac
test ! -e "$archive" && test ! -L "$archive" || { printf '%s\n' 'Refusing to overwrite an existing archive.' >&2; exit 1; }
test -z "$(git -C "$project" rev-parse --show-prefix)" || { printf '%s\n' 'Package from the repository root.' >&2; exit 1; }
test "$(git -C "$project" symbolic-ref --quiet --short HEAD)" = main || { printf '%s\n' 'Package only the reviewed main branch.' >&2; exit 1; }
test -z "$(git -C "$project" status --porcelain --untracked-files=normal)" || { printf '%s\n' 'Commit reviewed changes and use a clean checkout before packaging.' >&2; exit 1; }
test ! -e "$project/public/admin" && test ! -L "$project/public/admin" || { printf '%s\n' 'Refusing public/admin because it blocks the admin routes.' >&2; exit 1; }
if git -C "$project" ls-tree -r HEAD -- app bootstrap config database lang packages public resources routes scripts/deploy docker/production/mansecretpleasure | grep -E '^(120000|160000) ' >/dev/null; then
    printf '%s\n' 'Release source must not contain symbolic links or submodules.' >&2
    exit 1
fi
test -f "$project/packages/Webkul/Shop/package-lock.json"
test -f "$project/public/build/manifest.json"
test -f "$project/public/themes/shop/default/build/manifest.json"
test -f "$project/public/themes/admin/default/build/manifest.json"
git -C "$project" ls-files --error-unmatch -- release.json packages/Webkul/Shop/package-lock.json public/build/manifest.json public/themes/shop/default/build/manifest.json public/themes/admin/default/build/manifest.json >/dev/null
mkdir -p "$(dirname "$archive")"
attributes=$(mktemp)
archive_complete=0
cleanup_package() {
    rm -f "$attributes"
    if test "$archive_complete" != 1; then rm -f "$archive"; fi
}
trap cleanup_package EXIT
cat > "$attributes" <<'ATTRIBUTES'
**/node_modules export-ignore
**/vendor export-ignore
**/tests export-ignore
**/.git export-ignore
**/.local export-ignore
*.hot export-ignore
*.pem export-ignore
*.key export-ignore
*.bak export-ignore
.env* export-ignore
route-list.json export-ignore
deploy.bat export-ignore
/bootstrap/cache/*.php export-ignore
/public/storage export-ignore
/public/admin export-ignore
/database/seeders/ManPleasureQaProductSeeder.php export-ignore
ATTRIBUTES
git -C "$project" -c core.attributesFile="$attributes" archive --format=tar.gz --output="$archive" HEAD \
    app bootstrap config database lang packages public resources routes \
    artisan composer.json composer.lock package.json vite.config.js scripts/deploy \
    docker/production/mansecretpleasure release.json
chmod 600 "$archive"
archive_complete=1
sha256sum "$archive"
