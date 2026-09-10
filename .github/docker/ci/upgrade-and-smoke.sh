#!/bin/bash
# Upgrades an older Evolution CMS to this tree and proves the result is sound.
#
# Installs the older version, copies this tree over it the way unpacking a
# release archive would, runs `cli-install.php --typeInstall=2`, and checks what
# the updater left behind. That exercises what install-and-smoke.sh cannot: the
# migration chain replayed over a schema it did not create, including
# bootstrapInstallMigrationHistory() back-filling the history of a database that
# predates the 2025_12_25 baseline.
#
#   EVO_FROM_DIR=/path/to/3.5.7 EVO_NEW_DIR=/path/to/this/tree \
#   EVO_DB_TYPE=mysql EVO_DB_HOST=127.0.0.1 ./upgrade-and-smoke.sh
#
# The older tree is installed into in place and is left dirty, so hand it a
# checkout that can be thrown away, never a working tree.
set -euo pipefail

NEW_DIR=${EVO_NEW_DIR:-$(cd "$(dirname "$0")/../../.." && pwd)}
APP_DIR=${EVO_FROM_DIR:?EVO_FROM_DIR must point at the older tree to upgrade}

export EVO_DB_TYPE=${EVO_DB_TYPE:-mysql}
export EVO_DB_HOST=${EVO_DB_HOST:-127.0.0.1}
export EVO_DB_USER=${EVO_DB_USER:-root}
export EVO_DB_PASSWORD=${EVO_DB_PASSWORD:-secret}
export EVO_DB_NAME=${EVO_DB_NAME:-evolution}
export EVO_DB_PREFIX=${EVO_DB_PREFIX:-evo_}

export EVO_ADMIN=${EVO_ADMIN:-admin}
export EVO_ADMIN_EMAIL=${EVO_ADMIN_EMAIL:-admin@evo.local}
export EVO_ADMIN_PASSWORD=${EVO_ADMIN_PASSWORD:-Passw0rd123}
export EVO_LANGUAGE=${EVO_LANGUAGE:-en}

# shellcheck source=lib.sh
. "$(dirname "$0")/lib.sh"

# Always this tree's smoke test: the older one may not have it, and an old copy
# would check the old expectations.
SMOKE="$NEW_DIR/.github/docker/ci/smoke.php"
export EVO_SMOKE_BASELINE=/tmp/evo-upgrade-baseline.json
rm -f "$EVO_SMOKE_BASELINE"

SOURCE_LOG=/tmp/source-install.log
UPDATE_LOG=/tmp/upgrade.log

from_version=$(php -r 'echo (include $argv[1])["version"] ?? "unknown";' "$APP_DIR/core/factory/version.php" 2>/dev/null || echo unknown)
to_version=$(php -r 'echo (include $argv[1])["version"] ?? "unknown";' "$NEW_DIR/core/factory/version.php")

wait_for_db

say "Installing the source version ${from_version} (${EVO_DB_TYPE})"
# No --skipComposer before 3.5.8: composerUpdate() runs whatever it finds at
# core/vendor/bin/composer and only warns when that is missing. Moving the shim
# aside is how an older installer is told to leave the committed vendor tree
# alone - a composer update here would test the network rather than the CMS.
composer_shim="$APP_DIR/core/vendor/bin/composer"
[ -f "$composer_shim" ] && mv "$composer_shim" "${composer_shim}.ci-disabled"

mapfile -t db_args < <(installer_db_args)
cd "$APP_DIR/install"
set +e
php cli-install.php \
    --typeInstall=1 \
    "${db_args[@]}" \
    --cmsAdmin="$EVO_ADMIN" \
    --cmsAdminEmail="$EVO_ADMIN_EMAIL" \
    --cmsPassword="$EVO_ADMIN_PASSWORD" \
    --language="$EVO_LANGUAGE" \
    --removeInstall=n \
    --skipComposer=y \
    < /dev/null 2>&1 | tee "$SOURCE_LOG"
status=$?
set -e
[ "$status" -eq 0 ] || fail "the ${from_version} installer exited with ${status}"
grep -q 'Now you use' "$SOURCE_LOG" || fail "the ${from_version} installer did not run to completion"
cd "$APP_DIR"

# Deliberately not check_log: an older release run on a newer PHP may emit
# deprecations that are not this branch's to fix, and it is not what is under
# test. Anything that actually broke the install shows up in the exit code
# above, in the baseline below, or in the strict check of the updater's own log.
if grep -nE 'SQLSTATE\[|Fatal error:' "$SOURCE_LOG"; then
    fail "the ${from_version} installer failed to build a database to upgrade"
fi

say "Recording what ${from_version} left in the database"
php "$SMOKE" --mode=baseline "$APP_DIR" || fail "the ${from_version} install is not usable as an upgrade source"

say "Copying ${to_version} over ${from_version}"
# What unpacking a release archive over a site does. The connection config is
# git ignored, so it is not in this tree to copy and the installed site keeps
# the one it wrote - which is exactly why a real upgrade keeps working. It is
# excluded anyway, so a local run in a tree that has been installed into does
# not carry its own connection across.
tar -C "$NEW_DIR" \
    --exclude=./.git \
    --exclude=./core/config/database/connections/default.php \
    --exclude='./core/database/*.sqlite' \
    -cf - . | tar -C "$APP_DIR" -xf -
installed_version=$(php -r 'echo (include $argv[1])["version"] ?? "unknown";' "$APP_DIR/core/factory/version.php")
[ "$installed_version" = "$to_version" ] || fail "the copy left version ${installed_version}, expected ${to_version}"

say "Running the ${to_version} updater over the ${from_version} site"
cd "$APP_DIR/install"
set +e
php cli-install.php --typeInstall=2 --removeInstall=n < /dev/null 2>&1 | tee "$UPDATE_LOG"
status=$?
set -e
[ "$status" -eq 0 ] || fail "the updater exited with ${status}"
cd "$APP_DIR"

say "Checking the updater output for diagnostics"
# Strict here: the updater is this branch's code, running the chain this branch
# ships. A warning raised inside a migration reaches this log because php.ini
# routes error_log to stderr, outside the buffer the installer discards.
check_log "$UPDATE_LOG" "updater"

say "Checking the upgraded site"
php "$SMOKE" --mode=upgrade "$APP_DIR" || fail "the upgrade from ${from_version} did not produce a sound site"

say "Checking the upgraded site answers over HTTP"
# No expected text: the document is whichever one the older version seeded, and
# what matters is that the upgraded site still renders it.
http_check "$APP_DIR" ""

say "OK: ${from_version} -> ${to_version} on ${EVO_DB_TYPE} upgraded cleanly"
