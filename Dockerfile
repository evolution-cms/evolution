FROM php:8.3-apache

# Install system dependencies first
RUN apt-get update && apt-get install -y \
        ca-certificates \
        curl \
        gnupg \
        lsb-release \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install required system packages and development libraries
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        libpq-dev \
        libxml2-dev \
        libicu-dev \
        libonig-dev \
        libcurl4-openssl-dev \
        pkg-config \
        libssl-dev \
        unzip \
        git \
        netcat-openbsd \
        wget \
        cron \
        postgresql-client \
        default-mysql-client \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
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
        curl \
        fileinfo \
        exif \
    && a2enmod rewrite \
    && a2enmod headers

# Composer (use official image for better security)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files (excluding lock files via .dockerignore)
COPY . /var/www/html

# Install composer dependencies (update to get latest compatible versions)
RUN if [ -f composer.json ]; then \
        composer update --no-dev --prefer-dist --no-interaction --optimize-autoloader || true; \
    fi \
    && if [ -f core/composer.json ]; then \
        cd core && composer update --no-dev --prefer-dist --no-interaction --optimize-autoloader && cd ..; \
    fi

# PHP configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/40-custom.ini

# Create necessary directories and set permissions
RUN mkdir -p storage core/storage assets/cache assets/export assets/files assets/images \
    && chown -R www-data:www-data storage core/storage assets \
    && chmod -R 755 storage core/storage assets \
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
ENV EVO_ADMIN_LOGIN=admin \
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


