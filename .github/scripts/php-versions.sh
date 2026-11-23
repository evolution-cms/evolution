#!/bin/sh
# The PHP versions CI runs a tree on, decided by the CMS version of that tree:
# 3.6 requires PHP 8.4 and the 3.5 line still supports 8.3. Stable PHP 8.6 is
# checked by the normal required matrix on both lines.
#
# Usage: php-versions.sh [tree]   (default: the current directory)
# Writes to $GITHUB_OUTPUT when set, to stdout otherwise:
#   versions  JSON list for a job matrix, lowest first
#   lowest    the lowest of them, for jobs that run on one version only
#   runtimes  JSON list of versions with Salo2 Docker recipes
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
else
    set -- 8.3 8.4 8.5 8.6
fi
runtimes='["8.4","8.5"]'
if [ "$1" = 8.3 ]; then
    runtimes='["8.3","8.4","8.5"]'
fi

lowest=$1
versions=$(printf '"%s",' "$@")
versions="[${versions%,}]"

result="versions=$versions
lowest=$lowest
runtimes=$runtimes"
if [ -n "${GITHUB_OUTPUT:-}" ]; then
    echo "$result" >> "$GITHUB_OUTPUT"
else
    echo "$result"
fi
echo "CMS $version: PHP $*" >&2
