#!/usr/bin/env bash
set -euo pipefail
umask 077

project=$(cd "$(dirname "$0")/../.." && pwd)
tag=${1:?Usage: package-admin-update.sh msp-YYYY.M.D.N /secure/output/directory}
output=${2:?Provide an output directory outside the project.}
[[ "$tag" =~ ^msp-[0-9]{4}\.[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,4}$ ]]
output=$(realpath -m "$output")
case "$output/" in "$project/"*) printf '%s\n' 'Choose an output directory outside the project.' >&2; exit 1;; esac
test ! -e "$project/release.json" || { printf '%s\n' 'Remove or preserve the existing generated release.json before packaging.' >&2; exit 1; }
test -z "$(git -C "$project" status --porcelain --untracked-files=normal)" || { printf '%s\n' 'Commit the reviewed source before creating a published update release.' >&2; exit 1; }
test -f "$project/app/Http/Controllers/Admin/SystemUpdateController.php"
test -f "$project/routes/manpleasure-updates.php"
mkdir -p "$output"
test ! -e "$output/manpleasure-release.json"
test ! -e "$output/$tag.tar.gz"
commit=$(git -C "$project" rev-parse HEAD)
metadata_created=0
cleanup_release_metadata() {
    if test "$metadata_created" = 1; then rm -f "$project/release.json"; fi
}
trap cleanup_release_metadata EXIT
python3 - "$project/release.json" "$tag" "$commit" <<'PY'
import json, pathlib, sys
document = {"schema": 1, "updater_protocol": 1, "project": "mansecretpleasure", "release_id": sys.argv[2], "version": sys.argv[2][4:], "commit": sys.argv[3]}
with open(sys.argv[1], 'x') as stream:
    json.dump(document, stream, indent=2)
PY
metadata_created=1
bash "$project/scripts/deploy/package-release.sh" "$output/$tag.tar.gz"
python3 - "$project/release.json" "$output/$tag.tar.gz" "$output/manpleasure-release.json" <<'PY'
import hashlib, json, pathlib, sys
document = json.loads(pathlib.Path(sys.argv[1]).read_text())
archive = pathlib.Path(sys.argv[2])
with archive.open('rb') as stream:
    document['archive'] = {'name': archive.name, 'sha256': hashlib.file_digest(stream, 'sha256').hexdigest()}
document['database_policy'] = 'unchanged'
with open(sys.argv[3], 'x') as stream:
    json.dump(document, stream, indent=2)
PY
printf '\n%s\n' 'Update package created. Publish a stable GitHub release with the matching tag and both generated files as assets.'
printf 'Tag: %s\nArchive: %s/%s.tar.gz\nManifest: %s/manpleasure-release.json\n' "$tag" "$output" "$tag" "$output"
printf '%s\n' 'The server worker independently checks the archive hash and migration compatibility before activation.'
