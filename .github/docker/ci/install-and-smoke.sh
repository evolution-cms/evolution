#!/bin/bash
# Installs Evolution CMS onto the database named by the EVO_DB_* environment,
# then proves the installation is sound: no diagnostics from the migrations, the
# seeded data present, the updater able to replay the chain over it without
# changing anything, and the site answering over HTTP.
#
# Runs as the container entrypoint (see Dockerfile) and, in CI, directly on the
# runner; every knob has a default so `docker run` with only EVO_DB_HOST set
# does something sensible. EVO_DB_TYPE=sqlite needs no server at all.
#
# For the upgrade of an older release to this tree, see upgrade-and-smoke.sh.
# pipefail: the installer output goes through tee, and a pipeline that hides
# the exit code of its first command would report a failed install as a pass.
set -euo pipefail

APP_DIR=${EVO_APP_DIR:-/var/www/html}

export EVO_DB_TYPE=${EVO_DB_TYPE:-mysql}
export EVO_DB_HOST=${EVO_DB_HOST:-127.0.0.1}
export EVO_DB_USER=${EVO_DB_USER:-root}
export EVO_DB_PASSWORD=${EVO_DB_PASSWORD:-secret}
export EVO_DB_NAME=${EVO_DB_NAME:-evolution}
export EVO_DB_PREFIX=${EVO_DB_PREFIX:-evo_}

# Exported: smoke.php reads these to check what the installer stored.
export EVO_ADMIN=${EVO_ADMIN:-admin}
export EVO_ADMIN_EMAIL=${EVO_ADMIN_EMAIL:-admin@evo.local}
export EVO_ADMIN_PASSWORD=${EVO_ADMIN_PASSWORD:-Passw0rd123}
export EVO_LANGUAGE=${EVO_LANGUAGE:-en}

# shellcheck source=lib.sh
. "$(dirname "$0")/lib.sh"

LOG=/tmp/install.log
UPDATE_LOG=/tmp/update.log

wait_for_db

say "Installing Evolution CMS (${EVO_DB_TYPE}, database '${EVO_DB_NAME}', prefix '${EVO_DB_PREFIX}')"
# --skipComposer=y: core/vendor is committed, so there is nothing to install and
# a composer update here would test the network rather than the CMS.
# --removeInstall=n: keeping install/ lets the run report what it used, and the
# sqlite leg of the build workflow already covers removal.
# Interactive prompts read from stdin; </dev/null makes an unanswered prompt an
# immediate failure instead of a hung job.
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
    < /dev/null 2>&1 | tee "$LOG"
status=$?
set -e
[ "$status" -eq 0 ] || fail "the installer exited with ${status}"
cd "$APP_DIR"

say "Checking the installer output for diagnostics"
check_log "$LOG" "installer"
# The banner is the last thing install() prints, and the only progress message
# that survives: everything the installer says while migrating and seeding goes
# into the output buffer index.php opens, which checkRemoveInstall() discards.
# What actually reached the database is checked below instead.
grep -q 'Now you use' "$LOG" || fail "the installer did not run to completion"

say "Checking the seeded data"
php "$APP_DIR/.github/docker/ci/smoke.php" "$APP_DIR" || fail "the seeded data is not what the installer promises"

say "Running the updater over the site just installed"
# The update path replays the same migration chain against a populated database,
# which is the only way to find a migration that is not idempotent - and it is
# what every existing site runs when it moves to a new release.
cd "$APP_DIR/install"
set +e
php cli-install.php --typeInstall=2 --removeInstall=n < /dev/null 2>&1 | tee "$UPDATE_LOG"
status=$?
set -e
[ "$status" -eq 0 ] || fail "the updater exited with ${status}"
cd "$APP_DIR"

# update() prints its own "Evolution CMS updated!" before checkRemoveInstall()
# discards the buffer, so a successful update says nothing at all; its exit code
# above and the checks below are the evidence. Warnings still arrive, because
# php.ini routes them to stderr as well.
check_log "$UPDATE_LOG" "updater"

say "Checking the data survived the update unchanged"
# Same checks, plus the row counts recorded by the run above: a seeder that
# inserts instead of updating doubles its table here.
php "$APP_DIR/.github/docker/ci/smoke.php" "$APP_DIR" || fail "the update changed the installed data"

say "Checking the installed site answers over HTTP"
http_check "$APP_DIR"

say "OK: ${EVO_DB_TYPE} installation is clean, migrated and seeded"
