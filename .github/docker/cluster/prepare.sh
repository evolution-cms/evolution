#!/bin/bash
# Copies the tracked tree into build/, the folder .github/docker/Dockerfile
# bakes into the node image, and creates the shared folders the tree does not
# carry. A volume mounted over a missing folder is created by Docker as root,
# where www-data cannot write; one mounted over an existing folder is seeded
# with its content and ownership.
set -euo pipefail

here=$(cd "$(dirname "$0")" && pwd)
root=$(git -C "$here" rev-parse --show-toplevel)
cd "$root"

rm -rf build
mkdir build
git ls-files -z | tar --null -T - -cf - | tar -xf - -C build

# The shared paths are listed once, in the compose file.
grep -oE '^ +- [a-z-]+:/var/www/html/[^ ]+' "$here/docker-compose.yml" \
    | sed 's#.*:/var/www/html/##' \
    | while read -r path; do
        mkdir -p "build/$path"
        echo "shared: $path"
    done
