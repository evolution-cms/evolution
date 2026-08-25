#!/bin/sh
# Starts the Evolution CMS scheduler alongside the web server, then hands the
# container over to whatever the base runtime would have started on its own.
#
# The manager queues package installs, site updates and backups as system tasks
# and waits for `php artisan schedule:work` to pick them up; without it those
# tasks sit queued forever and the manager reports the scheduler as stale. A
# container has no cron, so the scheduler is supervised here instead.
set -e

APP_DIR=${EVO_APP_DIR:-/var/www/html}
SCHEDULER_USER=${EVO_SCHEDULER_USER:-www-data}
# Seconds to wait before restarting the scheduler after it exits. A crash loop
# (bad database credentials, say) then costs one line of log every 5s rather
# than filling the log as fast as PHP can boot.
SCHEDULER_RESTART_DELAY=${EVO_SCHEDULER_RESTART_DELAY:-5}

# EVO_SCHEDULER=0 turns the scheduler off for anyone running several replicas
# off one database, where a single scheduler elsewhere owns the queue.
if [ "${EVO_SCHEDULER:-1}" != "0" ] && [ -f "$APP_DIR/core/artisan" ]; then
    (
        while true; do
            echo "[evo-entrypoint] starting php artisan schedule:work"
            # The web server serves as www-data, so the scheduler writes cache,
            # log and backup files as the same user — running it as root would
            # leave files the server cannot rewrite.
            su "$SCHEDULER_USER" -s /bin/sh -c "cd '$APP_DIR/core' && exec php artisan schedule:work" || true
            echo "[evo-entrypoint] schedule:work exited, restarting in ${SCHEDULER_RESTART_DELAY}s"
            sleep "$SCHEDULER_RESTART_DELAY"
        done
    ) &
fi

# Overriding ENTRYPOINT clears the CMD inherited from the base image, so the
# default command each runtime ships has to be restated here. All three bases
# keep php's own docker-php-entrypoint, which is what finally execs these.
if [ "$#" -eq 0 ]; then
    if command -v apache2-foreground > /dev/null 2>&1; then
        set -- apache2-foreground
    elif command -v salo-entrypoint > /dev/null 2>&1; then
        set -- salo-entrypoint
    elif command -v frankenphp > /dev/null 2>&1; then
        # FrankenPHP's docker-php-entrypoint turns a leading "-" into
        # "frankenphp run ...", so the flags are the whole command there.
        set -- --config /etc/frankenphp/Caddyfile --adapter caddyfile
    else
        echo "[evo-entrypoint] no known server command found in this image" >&2
        exit 1
    fi
fi

exec docker-php-entrypoint "$@"
