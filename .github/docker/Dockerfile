# Runs the pre-installed sqlite build produced by .github/workflows/build.yml.
#
# The base image is built from evolution-cms/salo2 runtimes/<php version> (Salo
# is the Evolution CMS flavour of Laravel Sail): php:<version>-apache with gd,
# zip, pdo and mod_rewrite. sqlite3/pdo_sqlite come enabled in the official php
# images. The workflow builds 8.3, 8.4 and 8.5 and overrides BASE_IMAGE per leg.
ARG BASE_IMAGE=evo-salo-runtime:8.4
FROM ${BASE_IMAGE}

COPY build/ /var/www/html/

# Friendly URLs: the repository ships the Apache rules as ht.access. The nginx
# and FrankenPHP runtimes carry their own rules inside the base image, and
# a2enmod exists only on the apache ones — hence the probe rather than an ARG.
RUN if [ -f /var/www/html/ht.access ] && [ ! -f /var/www/html/.htaccess ]; then \
        cp /var/www/html/ht.access /var/www/html/.htaccess; \
    fi \
    && if command -v a2enmod > /dev/null 2>&1; then a2enmod rewrite; fi \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
