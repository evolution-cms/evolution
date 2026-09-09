# Runs the CLI installer against a real database server, so the MySQL and
# PostgreSQL paths of install/cli-install.php get the same coverage the sqlite
# build (.github/workflows/build.yml) already gives the sqlite one.
#
# php:<version>-cli, not one of the Salo runtimes: the checks here are the
# installer, the migrations and the seeded data, and the built in web server is
# enough to prove the installed site answers. Which web server serves it is what
# the Salo images in .github/docker/Dockerfile are for.
ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-cli

# libpq/libzip/libicu are the build inputs of the extensions below; the -dev
# packages stay out of the final image only in multi stage builds, and a CI
# image that lives for one workflow run does not earn the extra stage.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq-dev libzip-dev libicu-dev libxml2-dev libonig-dev unzip \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_pgsql zip intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Every diagnostic the installer or a migration emits has to reach the log the
# entrypoint greps. display_errors alone is not enough: the installer runs the
# migrations inside an output buffer it later discards, which would take the
# warnings printed during them with it. Logging to stderr as well puts each one
# outside the buffer, where nothing can swallow it.
RUN { \
        echo 'error_reporting = E_ALL'; \
        echo 'display_errors = On'; \
        echo 'display_startup_errors = On'; \
        echo 'log_errors = On'; \
        echo 'error_log = /dev/stderr'; \
    } > /usr/local/etc/php/conf.d/evo-ci.ini

WORKDIR /var/www/html

# The repository ships core/vendor, so there is no composer step here and the
# installer is called with --skipComposer=y. A copy rather than a bind mount:
# the installer rewrites core/config and deletes install/, which would leave a
# developer running this locally with a dirty working tree.
COPY . /var/www/html

RUN chmod +x .github/docker/ci/install-and-smoke.sh

ENTRYPOINT ["/var/www/html/.github/docker/ci/install-and-smoke.sh"]
