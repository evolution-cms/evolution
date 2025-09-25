#!/bin/sh
set -e

# Runtime PHP configuration overrides
if [ -n "$PHP_DISPLAY_ERRORS" ]; then
  if [ "$PHP_DISPLAY_ERRORS" = "1" ] || [ "$PHP_DISPLAY_ERRORS" = "On" ]; then
    echo "display_errors = On" > /usr/local/etc/php/conf.d/41-runtime.ini
  else
    echo "display_errors = Off" > /usr/local/etc/php/conf.d/41-runtime.ini
  fi
fi

if [ -n "$PHP_MEMORY_LIMIT" ]; then
  echo "memory_limit = $PHP_MEMORY_LIMIT" >> /usr/local/etc/php/conf.d/41-runtime.ini
fi

if [ -n "$PHP_MAX_EXECUTION_TIME" ]; then
  echo "max_execution_time = $PHP_MAX_EXECUTION_TIME" >> /usr/local/etc/php/conf.d/41-runtime.ini
fi

# Ensure writable directories exist with correct permissions
mkdir -p /var/www/html/core/storage/logs \
         /var/www/html/core/storage/cache \
         /var/www/html/core/storage/sessions \
         /var/www/html/storage \
         /var/www/html/assets/cache \
         /var/www/html/assets/export \
         /var/www/html/assets/files \
         /var/www/html/assets/images

# Set permissions for Evolution CMS directories
chown -R www-data:www-data /var/www/html/core/storage || true
chown -R www-data:www-data /var/www/html/storage || true
chown -R www-data:www-data /var/www/html/assets || true

# Set proper file permissions (directories 755, files 644)
find /var/www/html/core/storage -type d -exec chmod 755 {} \; || true
find /var/www/html/core/storage -type f -exec chmod 644 {} \; || true
find /var/www/html/assets -type d -exec chmod 755 {} \; || true
find /var/www/html/assets -type f -exec chmod 644 {} \; || true

# Wait for database if DB_HOST is set
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "localhost" ]; then
  echo "Waiting for database at $DB_HOST:${DB_PORT:-5432}..."
  timeout 30 sh -c 'until nc -z $0 $1; do sleep 1; done' "$DB_HOST" "${DB_PORT:-5432}" || echo "Database wait timeout"
fi

# Auto-install Evolution CMS if not already installed
if [ "$EVO_AUTO_INSTALL" = "true" ] && [ ! -f "/var/www/html/config.php" ]; then
  echo "🚀 Evolution CMS not found, starting auto-installation..."
  
  # Check if install directory exists
  if [ -d "/var/www/html/install" ] && [ -f "/var/www/html/install/cli-install.php" ]; then
    echo "📦 Running Evolution CMS installation..."
    
    cd /var/www/html/install/
    
    # Map database type
    case "$DB_CONNECTION" in
      "pgsql"|"postgresql")
        DB_TYPE="pgsql"
        ;;
      "mysql"|"mariadb")
        DB_TYPE="mysql"
        ;;
      *)
        DB_TYPE="mysql"
        echo "⚠️  Unknown DB_CONNECTION '$DB_CONNECTION', defaulting to mysql"
        ;;
    esac
    
    # Run CLI installer
    php cli-install.php \
      --typeInstall="${EVO_INSTALL_TYPE}" \
      --databaseType="${DB_TYPE}" \
      --databaseServer="${DB_HOST}" \
      --databasePort="${DB_PORT}" \
      --database="${DB_DATABASE}" \
      --databaseUser="${DB_USERNAME}" \
      --databasePassword="${DB_PASSWORD}" \
      --tablePrefix="${EVO_TABLE_PREFIX:-evo_}" \
      --cmsAdmin="${EVO_ADMIN_LOGIN}" \
      --cmsAdminEmail="${EVO_ADMIN_EMAIL}" \
      --cmsPassword="${EVO_ADMIN_PASSWORD}" \
      --language="${EVO_LANGUAGE}" \
      --removeInstall="${EVO_REMOVE_INSTALL}"
    
    if [ $? -eq 0 ]; then
      echo "✅ Evolution CMS installed successfully!"
      
      # Post-installation setup
      cd /var/www/html/core/
      
      # Create main package if MAIN_PACKAGE_NAME is set
      if [ -n "$EVO_MAIN_PACKAGE_NAME" ]; then
        echo "📦 Creating main package: $EVO_MAIN_PACKAGE_NAME"
        php artisan package:create "$EVO_MAIN_PACKAGE_NAME"
        echo "<?php return \"EvolutionCMS\\\\$EVO_MAIN_PACKAGE_NAME\\\\Controllers\\\\\"; ?>" > custom/config/cms/settings/ControllerNamespace.php
      fi
      
      # Install TinyMCE5 if enabled
      if [ "$EVO_INSTALL_TINYMCE" = "true" ]; then
        echo "📝 Installing TinyMCE5..."
        php artisan extras extras TinyMCE5 master || echo "⚠️  TinyMCE5 installation failed"
        echo '<?php return "TinyMCE5"; ?>' > custom/config/cms/settings/which_editor.php || true
      fi
      
      # Set final permissions
      cd /var/www/html/
      chown -R www-data:www-data . || true
      
      echo "🎉 Evolution CMS setup completed!"
    else
      echo "❌ Evolution CMS installation failed!"
      exit 1
    fi
  else
    echo "❌ Install directory not found! Cannot auto-install."
  fi
else
  if [ -f "/var/www/html/config.php" ]; then
    echo "✅ Evolution CMS already installed, skipping auto-install"
  fi
fi

exec "$@"


