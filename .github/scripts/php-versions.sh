#!/bin/sh
# The PHP versions CI runs a tree on, decided by the CMS version of that tree:
# 3.6 requires PHP 8.4 and runs on 8.6, the 3.5 line still supports 8.3. So one
# set of workflows keeps testing 8.3 on 3.5.x and moves to 8.6 on 3.6.x,
# whichever branch it was merged to.
#
# Usage: php-versions.sh [tree]   (default: the current directory)
# Writes to $GITHUB_OUTPUT when set, to stdout otherwise:
#   versions  JSON list for a job matrix, lowest first
#   lowest    the lowest of them, for jobs that run on one version only
#   runtimes  JSON list of those with a Salo runtime (evolution-cms/salo2
#             runtimes/<php>*), which the Docker images are built on
set -eu

tree=${1:-.}
file="$tree/core/factory/version.php"
version=$(sed -n "s/^[[:space:]]*'version'[[:space:]]*=>[[:space:]]*'\([0-9][0-9.]*\)'.*/\1/p" "$file" | head -n 1)
if [ -z "$version" ]; then
    echo "::error file=$file::no 'version' => 'x.y.z' entry" >&2
    exit 1
fi

major=${version%%.*}
rest=${version#*.}
minor=${rest%%.*}
if [ "$major" -gt 3 ] || { [ "$major" -eq 3 ] && [ "$minor" -ge 6 ]; }; then
    set -- 8.4 8.5 8.6
    # salo2 has no 8.6 runtime yet; add it here once it does.
    runtimes='["8.4","8.5"]'
else
    set -- 8.3 8.4 8.5
    runtimes=
fi

lowest=$1
versions=$(printf '"%s",' "$@")
versions="[${versions%,}]"
runtimes=${runtimes:-$versions}

result="versions=$versions
lowest=$lowest
runtimes=$runtimes"
if [ -n "${GITHUB_OUTPUT:-}" ]; then
    echo "$result" >> "$GITHUB_OUTPUT"
else
    echo "$result"
fi
echo "CMS $version: PHP $*, images on $runtimes" >&2
