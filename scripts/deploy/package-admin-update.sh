#!/usr/bin/env bash
set -euo pipefail
umask 077

project=$(cd "$(dirname "$0")/../.." && pwd)
tag=${1:?Usage: package-admin-update.sh msp-YYYY.M.D.N /secure/output/directory}
output=${2:?Provide an output directory outside the project.}
[[ "$tag" =~ ^msp-[0-9]{4}\.[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,4}$ ]]
output=$(realpath -m "$output")
case "$output/" in "$project/"*) printf '%s\n' 'Choose an output directory outside the project.' >&2; exit 1;; esac
test -z "$(git -C "$project" status --porcelain --untracked-files=normal)" || { printf '%s\n' 'Commit the reviewed source before creating a published update release.' >&2; exit 1; }
test -f "$project/app/Http/Controllers/Admin/SystemUpdateController.php"
test -f "$project/routes/manpleasure-updates.php"
mkdir -p "$output"
test ! -e "$output/manpleasure-release.json"
test ! -e "$output/$tag.tar.gz"
commit=$(php -r '
$document = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
if (($document["schema"] ?? null) !== 1 || ($document["updater_protocol"] ?? null) !== 1 || ($document["project"] ?? null) !== "mansecretpleasure" || ($document["release_id"] ?? null) !== $argv[2] || ($document["version"] ?? null) !== substr($argv[2], 4) || ! preg_match("/^[a-f0-9]{40}$/", $document["commit"] ?? "")) {
    fwrite(STDERR, "Commit the matching reviewed release.json before packaging an update.\n");
    exit(1);
}
echo $document["commit"];
' "$project/release.json" "$tag")
git -C "$project" merge-base --is-ancestor "$commit" HEAD
bash "$project/scripts/deploy/package-release.sh" "$output/$tag.tar.gz"
php -r '
$document = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$document["archive"] = ["name" => basename($argv[2]), "sha256" => hash_file("sha256", $argv[2])];
$document["database_policy"] = "unchanged";
$stream = fopen($argv[3], "x");
fwrite($stream, json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);
fclose($stream);
' "$project/release.json" "$output/$tag.tar.gz" "$output/manpleasure-release.json"
printf '\n%s\n' 'Update package created. Publish a stable GitHub release with the matching tag and both generated files as assets.'
printf 'Tag: %s\nArchive: %s/%s.tar.gz\nManifest: %s/manpleasure-release.json\n' "$tag" "$output" "$tag" "$output"
printf '%s\n' 'The server worker independently checks the archive hash and migration compatibility before activation.'
