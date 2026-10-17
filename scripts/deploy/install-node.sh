#!/usr/bin/env bash
set -euo pipefail
umask 022

test "$(id -u)" -eq 0 || { printf '%s\n' 'Run this script as root on the VPS.' >&2; exit 1; }
case "$(uname -m)" in x86_64) node_arch=x64;; aarch64) node_arch=arm64;; *) exit 1;; esac
node_temp=$(mktemp -d)
trap 'rm -rf "$node_temp"' EXIT
curl --fail --silent --show-error --location https://nodejs.org/dist/latest-v22.x/SHASUMS256.txt -o "$node_temp/SHASUMS256.txt"
node_archive=$(awk -v suffix="-linux-$node_arch.tar.xz" '$2 ~ suffix "$" { print $2 }' "$node_temp/SHASUMS256.txt")
[[ "$node_archive" =~ ^node-v22\.[0-9]+\.[0-9]+-linux-(x64|arm64)\.tar\.xz$ ]]
curl --fail --silent --show-error --location "https://nodejs.org/dist/latest-v22.x/$node_archive" -o "$node_temp/$node_archive"
(cd "$node_temp" && awk -v archive="$node_archive" '$2 == archive' SHASUMS256.txt | sha256sum --check --status)
node_directory="/opt/${node_archive%.tar.xz}"
test ! -e "$node_directory" || { printf '%s\n' 'Pinned Node directory already exists; verify it before reusing.' >&2; exit 1; }
tar -xJf "$node_temp/$node_archive" -C /opt
ln -sfn "$node_directory/bin/node" /usr/local/bin/node
ln -sfn "$node_directory/bin/npm" /usr/local/bin/npm
ln -sfn "$node_directory/bin/npx" /usr/local/bin/npx
node --version
npm --version
