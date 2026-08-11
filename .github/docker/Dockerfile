# Runs the pre-installed sqlite build produced by .github/workflows/build.yml.
#
# The base image is built from evolution-cms/salo2 runtimes/8.4 (Salo is the
# Evolution CMS flavour of Laravel Sail): php:8.4-apache with gd, zip, pdo and
# mod_rewrite. sqlite3/pdo_sqlite come enabled in the official php images.
ARG BASE_IMAGE=evo-salo-runtime:8.4
FROM ${BASE_IMAGE}

COPY build/ /var/www/html/

# Friendly URLs: the repository ships the rules as ht.access.
RUN if [ -f /var/www/html/ht.access ] && [ ! -f /var/www/html/.htaccess ]; then \
        cp /var/www/html/ht.access /var/www/html/.htaccess; \
    fi \
    && chown -R www-data:www-data /var/www/html \
    && a2enmod rewrite

EXPOSE 80
