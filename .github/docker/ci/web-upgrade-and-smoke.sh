#!/bin/bash
# Updates an older Evolution CMS to this tree the way the manager's updater does, and proves the
# web installer gets the site back on its feet.
#
# The updater lays a source archive over the site - this tree's tracked files, no vendor/ - and
# keeps the site's core/vendor, with the packages and the classmap-authoritative autoloader of the
# version it ran. The new core cannot boot on that: classes it added are missing from the classmap,
# and packages its lock moved are still at their old versions. The web installer has to bring
# core/vendor to the new lock before any step boots the new core (install/index.php,
# installerDependenciesStale()), then finish the update through its own forms.
#
#   EVO_FROM_DIR=/path/to/older/tree EVO_NEW_DIR=/path/to/this/tree ./web-upgrade-and-smoke.sh
#
#   EVO_CUSTOM_PACKAGE=1   also give the older site a package of core/custom/composer.json
#                          (a path repository, so no network is needed for it)
#   COMPOSER=...           how to run Composer (default: composer)
#
# The older tree is installed into in place and is left dirty, so hand it a checkout that can be
# thrown away, never a working tree. sqlite only: the subject is Composer, not the database.

set -euo pipefail

NEW_DIR=${EVO_NEW_DIR:-$(cd "$(dirname "$0")/../../.." && pwd)}
APP_DIR=${EVO_FROM_DIR:?EVO_FROM_DIR must point at the older tree to update}
COMPOSER=${COMPOSER:-composer}
CUSTOM_PACKAGE=${EVO_CUSTOM_PACKAGE:-0}
PORT=${EVO_HTTP_PORT:-8898}
BASE="http://127.0.0.1:${PORT}"

export EVO_DB_TYPE=sqlite
export EVO_DB_NAME=${EVO_DB_NAME:-$APP_DIR/core/database/evo-web-upgrade.sqlite}
export EVO_DB_PREFIX=${EVO_DB_PREFIX:-evo_}
export EVO_ADMIN=${EVO_ADMIN:-admin}
export EVO_ADMIN_EMAIL=${EVO_ADMIN_EMAIL:-admin@evo.local}
export EVO_ADMIN_PASSWORD=${EVO_ADMIN_PASSWORD:-Passw0rd123}
export EVO_LANGUAGE=${EVO_LANGUAGE:-en}

# shellcheck source=lib.sh
. "$(dirname "$0")/lib.sh"

DRIVER="$NEW_DIR/.github/docker/ci/web-update.php"
SMOKE="$NEW_DIR/.github/docker/ci/smoke.php"
export EVO_SMOKE_BASELINE=/tmp/evo-web-upgrade-baseline.json
rm -f "$EVO_SMOKE_BASELINE"
JAR=$(mktemp)
BIN=$(mktemp -d)

# The web installer finds Composer on the PATH of the server process, as on a real host.
printf '#!/bin/sh\nexec %s "$@"\n' "$COMPOSER" > "$BIN/composer"
chmod +x "$BIN/composer"

# What installerDependenciesStale() says about a core directory: the new installer's own verdict.
stale_reason() {
    php -r 'require $argv[1] . "/core/vendor/autoload.php"; require $argv[2] . "/install/src/functions.php";
        echo installerDependenciesStale($argv[1] . "/core/") ?? "";' "$APP_DIR" "$APP_DIR"
}

from_version=$(php -r 'echo (include $argv[1])["version"] ?? "unknown";' "$APP_DIR/core/factory/version.php")
to_version=$(php -r 'echo (include $argv[1])["version"] ?? "unknown";' "$NEW_DIR/core/factory/version.php")

say "Building the ${from_version} vendor, classmap-authoritative"
(cd "$APP_DIR/core" && "$COMPOSER" install --no-dev --classmap-authoritative --no-interaction --no-progress --no-scripts)
grep -q 'setClassMapAuthoritative(true)' "$APP_DIR/core/vendor/composer/autoload_real.php" \
    || fail "the ${from_version} vendor is not classmap-authoritative"

say "Installing ${from_version} (sqlite)"
mapfile -t db_args < <(installer_db_args)
(cd "$APP_DIR/install" && php cli-install.php --typeInstall=1 "${db_args[@]}" \
    --cmsAdmin="$EVO_ADMIN" --cmsAdminEmail="$EVO_ADMIN_EMAIL" --cmsPassword="$EVO_ADMIN_PASSWORD" \
    --language="$EVO_LANGUAGE" --removeInstall=n --skipComposer=y < /dev/null > /tmp/web-upgrade-source.log 2>&1) \
    || { tail -30 /tmp/web-upgrade-source.log; fail "the ${from_version} installer failed"; }

if [ "$CUSTOM_PACKAGE" = 1 ]; then
    say "Adding a custom package to ${from_version}"
    probe="$APP_DIR/core/custom/packages/ci-probe"
    mkdir -p "$probe/src"
    printf '{"name": "evo-ci/probe", "version": "1.0.0", "autoload": {"psr-4": {"EvoCi\\\\Probe\\\\": "src/"}}}\n' > "$probe/composer.json"
    printf '<?php\n\nnamespace EvoCi\\Probe;\n\nfinal class Probe\n{\n}\n' > "$probe/src/Probe.php"
    # The merge plugin resolves path repositories of core/custom/composer.json against core/custom.
    printf '{"repositories": [{"type": "path", "url": "packages/ci-probe", "options": {"symlink": false}}], "require": {}}\n' \
        > "$APP_DIR/core/custom/composer.json"
    (cd "$APP_DIR/core" && PATH="$BIN:$PATH" php artisan package:installrequire evo-ci/probe '*') \
        || fail "the custom package could not be installed on ${from_version}"
    [ -d "$APP_DIR/core/vendor/evo-ci/probe" ] || fail "the custom package is not in the ${from_version} vendor"
fi

say "Recording what ${from_version} left in the database"
php "$SMOKE" --mode=baseline "$APP_DIR" || fail "the ${from_version} install is not usable as an update source"

say "Serving the site; the administrator takes the installer grant in the ${from_version} Manager"
PATH="$BIN:$PATH" php -S "127.0.0.1:${PORT}" -t "$APP_DIR" > /tmp/web-upgrade-server.log 2>&1 &
server=$!
trap 'kill "$server" 2>/dev/null || true' EXIT
for _ in $(seq 1 30); do curl -s -o /dev/null "$BASE/" && break; sleep 1; done
php "$DRIVER" grant "$BASE" "$EVO_ADMIN" "$EVO_ADMIN_PASSWORD" "$JAR" || fail "no installer grant from the ${from_version} Manager"

say "Laying the ${to_version} source over ${from_version}, keeping its core/vendor"
# A source archive: the files of the tree (tracked, or new and not ignored), no vendor. The site keeps its connection and its custom files.
if git -C "$NEW_DIR" rev-parse --git-dir > /dev/null 2>&1; then
    git -C "$NEW_DIR" ls-files -co --exclude-standard -z | grep -zv '^core/vendor/' | tar -C "$NEW_DIR" --null -T - -cf - | tar -C "$APP_DIR" -xf -
else
    tar -C "$NEW_DIR" --exclude=./.git --exclude=./core/vendor --exclude=./core/custom \
        --exclude=./core/config/database/connections/default.php --exclude='./core/database/*.sqlite' -cf - . \
        | tar -C "$APP_DIR" -xf -
fi
reason=$(stale_reason)
[ -n "$reason" ] || fail "the ${from_version} vendor already fits ${to_version}: nothing under test"
echo "before the update: ${reason}"

say "Running the web update"
php "$DRIVER" update "$BASE" "$JAR" || { tail -40 /tmp/web-upgrade-server.log >&2; fail "the web update did not complete"; }

say "Checking core/vendor"
reason=$(stale_reason)
[ -z "$reason" ] || fail "core/vendor still does not fit ${to_version}: ${reason}"
grep -q 'setClassMapAuthoritative(true)' "$APP_DIR/core/vendor/composer/autoload_real.php" \
    || fail "the rebuilt autoloader is not classmap-authoritative"
if [ "$CUSTOM_PACKAGE" = 1 ]; then
    grep -q "EvoCi\\\\\\\\Probe\\\\\\\\Probe" "$APP_DIR/core/vendor/composer/autoload_classmap.php" \
        || fail "the custom package did not survive the update"
fi

kill "$server" 2>/dev/null || true
trap - EXIT

say "Checking the updated site"
php "$SMOKE" --mode=upgrade "$APP_DIR" || fail "the web update from ${from_version} did not produce a sound site"
http_check "$APP_DIR" ""

say "OK: ${from_version} -> ${to_version} updated through the web installer$([ "$CUSTOM_PACKAGE" = 1 ] && echo ', custom package kept')"
