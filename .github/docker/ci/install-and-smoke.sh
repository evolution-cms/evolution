#!/bin/bash
# Installs Evolution CMS onto the database named by the EVO_DB_* environment,
# then proves the installation is sound: no diagnostics from the migrations, the
# seeded data present, the updater able to replay the chain over it without
# changing anything, and the site answering over HTTP.
#
# Runs as the container entrypoint (see Dockerfile); every knob has a default
# so `docker run` with only EVO_DB_HOST set does something sensible.
# pipefail: the installer output goes through tee, and a pipeline that hides
# the exit code of its first command would report a failed install as a pass.
set -euo pipefail

APP_DIR=${EVO_APP_DIR:-/var/www/html}

EVO_DB_TYPE=${EVO_DB_TYPE:-mysql}
EVO_DB_HOST=${EVO_DB_HOST:-127.0.0.1}
EVO_DB_USER=${EVO_DB_USER:-root}
EVO_DB_PASSWORD=${EVO_DB_PASSWORD:-secret}
EVO_DB_NAME=${EVO_DB_NAME:-evolution}
EVO_DB_PREFIX=${EVO_DB_PREFIX:-evo_}

# Exported: smoke.php reads these to check what the installer stored.
export EVO_ADMIN=${EVO_ADMIN:-admin}
export EVO_ADMIN_EMAIL=${EVO_ADMIN_EMAIL:-admin@evo.local}
export EVO_ADMIN_PASSWORD=${EVO_ADMIN_PASSWORD:-Passw0rd123}
export EVO_LANGUAGE=${EVO_LANGUAGE:-en}

LOG=/tmp/install.log
UPDATE_LOG=/tmp/update.log
PORT=${EVO_HTTP_PORT:-8899}

say() {
    printf '\n\033[1;36m== %s\033[0m\n' "$1"
}

fail() {
    printf '\033[1;31m!! %s\033[0m\n' "$1" >&2
    exit 1
}

# Rejects a run that printed anything PHP or PDO would call a problem.
#
# php.ini in the image sends diagnostics to stdout and to stderr (see
# Dockerfile), and both are captured here, so a warning raised inside the output
# buffer the installer later discards still reaches this log.
#
# The patterns are anchored on how PHP formats a diagnostic ("Warning: text in
# /file on line N") rather than on the bare words, which turn up in plenty of
# legitimate output - the settings seeder alone writes rows whose names contain
# "error".
check_log() {
    if grep -nE '(^|PHP )(Warning|Notice|Deprecated|Fatal error|Parse error|Recoverable fatal error|Strict Standards):' "$1"; then
        fail "the $2 emitted PHP diagnostics"
    fi
    # SQLSTATE is how both PDO drivers label a failed statement, and the
    # installer swallows some of those into plain output instead of exiting.
    if grep -nE 'SQLSTATE\[|Uncaught .*Exception|Migration not found|✖' "$1"; then
        fail "the $2 reported a database or migration error"
    fi
    echo "no diagnostics in $(wc -l < "$1") lines of $2 output"
}

say "Waiting for ${EVO_DB_TYPE} at ${EVO_DB_HOST}"
# Compose already gates on a healthcheck, but this script is also meant to be
# runnable on its own against any reachable server.
attempt=0
until php -r '
    $dsn = $argv[1] === "pgsql"
        ? "pgsql:host=" . $argv[2] . ";dbname=" . $argv[5]
        : "mysql:host=" . $argv[2];
    new PDO($dsn, $argv[3], $argv[4]);
' "$EVO_DB_TYPE" "$EVO_DB_HOST" "$EVO_DB_USER" "$EVO_DB_PASSWORD" "${EVO_DB_BOOTSTRAP_NAME:-$EVO_DB_USER}" 2>/dev/null; do
    attempt=$((attempt + 1))
    [ "$attempt" -lt 60 ] || fail "database never became reachable"
    sleep 2
done
echo "reachable after ${attempt} attempt(s)"

say "Installing Evolution CMS (${EVO_DB_TYPE}, database '${EVO_DB_NAME}', prefix '${EVO_DB_PREFIX}')"
# --skipComposer=y: core/vendor is committed, so there is nothing to install and
# a composer update here would test the network rather than the CMS.
# --removeInstall=n: keeping install/ lets the run report what it used, and the
# sqlite leg of the build workflow already covers removal.
# Interactive prompts read from stdin; </dev/null makes an unanswered prompt an
# immediate failure instead of a hung job.
cd "$APP_DIR/install"
set +e
php cli-install.php \
    --typeInstall=1 \
    --databaseType="$EVO_DB_TYPE" \
    --databaseServer="$EVO_DB_HOST" \
    --database="$EVO_DB_NAME" \
    --databaseUser="$EVO_DB_USER" \
    --databasePassword="$EVO_DB_PASSWORD" \
    --tablePrefix="$EVO_DB_PREFIX" \
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
php -S "127.0.0.1:${PORT}" -t "$APP_DIR" > /tmp/server.log 2>&1 &
server=$!
trap 'kill "$server" 2>/dev/null || true' EXIT

php -r '
    // The front end and the manager are the two entry points an installed site
    // has to serve. The manager answers 404 without an Accept-Language header
    // by design, hence the header on the second request.
    $port = $argv[1];
    $get = function (string $path, array $headers = []) use ($port): array {
        $context = stream_context_create(["http" => [
            "ignore_errors" => true,
            "timeout" => 20,
            "header" => $headers,
        ]]);
        $body = @file_get_contents("http://127.0.0.1:{$port}{$path}", false, $context);
        $status = isset($http_response_header[0]) ? (int) substr($http_response_header[0], 9, 3) : 0;
        return [$status, (string) $body];
    };

    for ($i = 0; $i < 30; $i++) {
        [$status] = $get("/");
        if ($status !== 0) {
            break;
        }
        sleep(1);
    }

    $failures = [];
    [$status, $body] = $get("/");
    echo "front page: {$status}\n";
    $status === 200 or $failures[] = "the front page answered {$status}";
    // The seeded document, rendered through the seeded template - proof the
    // CMS read its own data rather than merely booting.
    str_contains($body, "Install Successful!") or $failures[] = "the front page did not render the seeded document";

    [$status, $body] = $get("/manager/index.php", ["Accept-Language: en-US,en;q=0.9"]);
    echo "manager: {$status}\n";
    $status === 200 or $failures[] = "the manager answered {$status}";
    stripos($body, "password") !== false or $failures[] = "the manager did not render its login form";

    if ($failures !== []) {
        fwrite(STDERR, "  - " . implode("\n  - ", $failures) . "\n");
        exit(1);
    }
    echo "both entry points served the installed site\n";
' "$PORT" || {
    tail -40 /tmp/server.log >&2
    fail "the installed site did not answer correctly"
}

say "OK: ${EVO_DB_TYPE} installation is clean, migrated and seeded"
