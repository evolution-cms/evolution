#!/bin/bash
# Shared by install-and-smoke.sh and upgrade-and-smoke.sh: the parts that are
# about proving an installation is sound, rather than about how it got there.
#
# Sourced, never executed. The caller sets EVO_DB_* and APP_DIR first.

say() {
    printf '\n\033[1;36m== %s\033[0m\n' "$1"
}

fail() {
    printf '\033[1;31m!! %s\033[0m\n' "$1" >&2
    exit 1
}

# A file based database has no server to reach, no user and no password, so
# every step that talks to a server is skipped for it.
is_sqlite() {
    [ "${EVO_DB_TYPE}" = "sqlite" ]
}

# Rejects a run that printed anything PHP or PDO would call a problem.
#
# php.ini sends diagnostics to stdout and to stderr, and both are captured here,
# so a warning raised inside the output buffer the installer later discards
# still reaches this log.
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

# Compose already gates on a healthcheck, but the scripts are also meant to be
# runnable on their own against any reachable server.
wait_for_db() {
    if is_sqlite; then
        echo "sqlite: no server to wait for"
        return
    fi

    say "Waiting for ${EVO_DB_TYPE} at ${EVO_DB_HOST}"
    local attempt=0
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
}

# The connection arguments of cli-install.php --typeInstall=1, echoed one per
# line for the caller to read into an array. sqlite is a path, not a server.
installer_db_args() {
    printf '%s\n' "--databaseType=${EVO_DB_TYPE}" "--database=${EVO_DB_NAME}"
    if ! is_sqlite; then
        printf '%s\n' \
            "--databaseServer=${EVO_DB_HOST}" \
            "--databaseUser=${EVO_DB_USER}" \
            "--databasePassword=${EVO_DB_PASSWORD}"
    fi
    printf '%s\n' "--tablePrefix=${EVO_DB_PREFIX}"
}

# Serves the installed site and asks it for the two entry points every site has.
http_check() {
    local root=$1
    local port=${EVO_HTTP_PORT:-8899}

    php -S "127.0.0.1:${port}" -t "$root" > /tmp/server.log 2>&1 &
    local server=$!
    trap 'kill "$server" 2>/dev/null || true' EXIT

    php -r '
        // The front end and the manager are the two entry points an installed
        // site has to serve. The manager answers 404 without an Accept-Language
        // header by design, hence the header on the second request.
        $port = $argv[1];
        $expect = $argv[2];
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
        // CMS read its own data rather than merely booting. An upgraded site
        // carries whatever document it already had, so the caller passes "" to
        // ask only that something rendered.
        if ($expect !== "") {
            str_contains($body, $expect) or $failures[] = "the front page did not render the seeded document";
        } elseif (trim($body) === "") {
            $failures[] = "the front page rendered nothing";
        }

        [$status, $body] = $get("/manager/index.php", ["Accept-Language: en-US,en;q=0.9"]);
        echo "manager: {$status}\n";
        $status === 200 or $failures[] = "the manager answered {$status}";
        stripos($body, "password") !== false or $failures[] = "the manager did not render its login form";

        if ($failures !== []) {
            fwrite(STDERR, "  - " . implode("\n  - ", $failures) . "\n");
            exit(1);
        }
        echo "both entry points served the installed site\n";
    ' "$port" "${2-Install Successful!}" || {
        tail -40 /tmp/server.log >&2
        fail "the installed site did not answer correctly"
    }

    kill "$server" 2>/dev/null || true
    trap - EXIT
}
