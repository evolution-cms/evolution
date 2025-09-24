FROM php:8.4-apache

# System deps and PHP extensions
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        libpq-dev \
        libxml2-dev \
        libicu-dev \
        unzip \
        git \
        netcat-openbsd \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        gd \
        mysqli \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        zip \
        dom \
        xml \
        simplexml \
        mbstring \
        iconv \
        intl \
        opcache \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer (use official image for better security)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# App files
WORKDIR /var/www/html
COPY . /var/www/html

# PHP configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/40-custom.ini

# Build-time Composer optimisation (root if needed, then core)
RUN if [ -f composer.json ]; then composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader; fi \
    && if [ -f core/composer.json ]; then cd core && composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader && cd - >/dev/null; fi

# Permissions (safe defaults; override in runtime if needed)
RUN chown -R www-data:www-data storage core/storage assets \
    || true

ENV APACHE_DOCUMENT_ROOT=/var/www/html

# Database environment variables
ENV DB_CONNECTION=pgsql \
    DB_HOST=localhost \
    DB_PORT=5432 \
    DB_DATABASE=evolution \
    DB_USERNAME=evo \
    DB_PASSWORD=secret

# Evolution CMS installation variables
ENV EVO_INSTALL_TYPE=1 \
    EVO_ADMIN_LOGIN=admin \
    EVO_ADMIN_EMAIL=admin@example.com \
    EVO_ADMIN_PASSWORD=admin123 \
    EVO_LANGUAGE=en \
    EVO_REMOVE_INSTALL=y \
    EVO_AUTO_INSTALL=true \
    EVO_TABLE_PREFIX=evo_ \
    EVO_MAIN_PACKAGE_NAME=main \
    EVO_INSTALL_TINYMCE=true

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]


