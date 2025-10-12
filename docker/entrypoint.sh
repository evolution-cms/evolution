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

# Git Sync Project Files
if [ -n "$GIT_REPO" ] && [ -n "$GIT_TOKEN" ]; then
  echo "🔄 Syncing project files from Git..."
  
  # Set default branch
  GIT_BRANCH="${GIT_BRANCH:-main}"
  
  # Prepare Git URL with token
  GIT_URL_WITH_TOKEN=$(echo "$GIT_REPO" | sed "s|https://|https://${GIT_TOKEN}@|")
  
  # Configure Git
  git config --global user.email "${GIT_USER_EMAIL:-evo@localhost}"
  git config --global user.name "${GIT_USER_NAME:-Evolution CMS}"
  git config --global --add safe.directory /var/www/html
  
  cd /var/www/html
  
  # Check if already initialized
  if [ ! -d ".git" ]; then
    echo "📥 Initializing Git repository..."
    
    # Initialize Git and add remote
    git init
    git remote add origin "$GIT_URL_WITH_TOKEN"
    
    # Fetch from remote
    git fetch origin "$GIT_BRANCH"
    
    # Create tracking branch directly from remote
    git checkout -b "$GIT_BRANCH" origin/"$GIT_BRANCH" 2>/dev/null || {
      # If checkout fails (empty repo), create branch and merge
      git checkout -b "$GIT_BRANCH"
      echo "🔀 Merging with remote repository..."
      git merge origin/"$GIT_BRANCH" --allow-unrelated-histories --no-edit || {
        echo "⚠️  Merge conflicts detected, keeping local files"
        git merge --abort 2>/dev/null || true
      }
    }
    
    echo "✅ Git repository initialized"
  else
    echo "🔄 Pulling latest changes from Git..."
    
    # Make sure we're on the right branch
    git checkout "$GIT_BRANCH" 2>/dev/null || git checkout -b "$GIT_BRANCH"
    
    # Stash any local changes
    git stash push -m "Auto-stash before pull" 2>/dev/null || true
    
    # Pull latest changes
    git pull origin "$GIT_BRANCH" --no-edit || {
      echo "⚠️  Pull failed, trying reset..."
      git fetch origin "$GIT_BRANCH"
      git reset --hard origin/"$GIT_BRANCH"
    }
    
    # Restore stashed changes if any
    git stash pop 2>/dev/null || true
    
    echo "✅ Git sync completed"
  fi
  
  # Set proper permissions
  chown -R www-data:www-data /var/www/html 2>/dev/null || true
  
  echo "✅ Project files synced successfully!"
fi

# Function to create database config file from ENV variables
create_db_config() {
  echo "📝 Creating database configuration file..."
  
  # Map database type
  case "$DB_CONNECTION" in
    "pgsql"|"postgresql")
      DB_TYPE="pgsql"
      DB_PORT_DEFAULT="5432"
      DB_CHARSET="utf8"
      DB_COLLATION="utf8"
      DB_ENGINE=""
      ;;
    "mysql"|"mariadb")
      DB_TYPE="mysql"
      DB_PORT_DEFAULT="3306"
      DB_CHARSET="utf8mb4"
      DB_COLLATION="utf8mb4_unicode_520_ci"
      DB_ENGINE=", 'innodb'"
      ;;
    *)
      DB_TYPE="mysql"
      DB_PORT_DEFAULT="3306"
      DB_CHARSET="utf8mb4"
      DB_COLLATION="utf8mb4_unicode_520_ci"
      DB_ENGINE=", 'innodb'"
      echo "⚠️  Unknown DB_CONNECTION '$DB_CONNECTION', defaulting to mysql"
      ;;
  esac
  
  # Use default port if not set
  DB_PORT="${DB_PORT:-$DB_PORT_DEFAULT}"
  
  # Create config directory if it doesn't exist
  mkdir -p /var/www/html/core/config/database/connections
  
  # Create database config file
  cat > /var/www/html/core/config/database/connections/default.php <<EOF
<?php
return [
    'driver' => env('DB_TYPE', '${DB_TYPE}'),
    'host' => env('DB_HOST', '${DB_HOST}'),
    'port' => env('DB_PORT', '${DB_PORT}'),
    'database' => env('DB_DATABASE', '${DB_DATABASE}'),
    'username' => env('DB_USERNAME', '${DB_USERNAME}'),
    'password' => env('DB_PASSWORD', '${DB_PASSWORD}'),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => env('DB_CHARSET', '${DB_CHARSET}'),
    'collation' => env('DB_COLLATION', '${DB_COLLATION}'),
    'prefix' => env('DB_PREFIX', '${EVO_TABLE_PREFIX:-evo_}'),
    'method' => env('DB_METHOD', 'SET CHARACTER SET'),
    'strict' => env('DB_STRICT', false),
    'engine' => env('DB_ENGINE'${DB_ENGINE}),
    'options' => [
        PDO::ATTR_STRINGIFY_FETCHES => true,
    ]
];
EOF
  
  chmod 0644 /var/www/html/core/config/database/connections/default.php
  echo "✅ Database configuration file created"
}

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
    
    # For update mode (typeInstall=2), create database config file first
    if [ "${EVO_INSTALL_TYPE}" = "2" ]; then
      create_db_config
    fi
    
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
      if [ "${EVO_INSTALL_TYPE}" = "2" ]; then
        echo "✅ Evolution CMS updated successfully!"
        
        # Create .install file for update mode
        echo $(date +%s) > /var/www/html/core/.install
        chmod 644 /var/www/html/core/.install
        
        # Update composer packages for update mode
        cd /var/www/html/core/
        if [ -f "custom/composer.json" ]; then
          echo "🔄 Updating composer autoload for custom packages..."
          composer dump-autoload -o
          php artisan package:discover
        fi
      else
        echo "✅ Evolution CMS installed successfully!"
        
        # Post-installation setup (only for fresh install, not for update)
        cd /var/www/html/core/
        
        # Create main package if MAIN_PACKAGE_NAME is set
        if [ -n "$EVO_MAIN_PACKAGE_NAME" ]; then
          echo "📦 Creating main package: $EVO_MAIN_PACKAGE_NAME"
          php artisan package:create "$EVO_MAIN_PACKAGE_NAME"
          # Normalize package name to StudlyCase (first letter uppercase)
          EVO_MAIN_PACKAGE_STUDLY=$(printf "%s" "$EVO_MAIN_PACKAGE_NAME" | awk '{print toupper(substr($0,1,1)) substr($0,2)}')
          # Write exact PHP string with double quotes and escaped backslashes
          cat > custom/config/cms/settings/ControllerNamespace.php <<PHP
<?php return "EvolutionCMS\\\\$EVO_MAIN_PACKAGE_STUDLY\\\\Controllers\\\\";
PHP
          
          echo "🔄 Updating composer autoload..."
          composer dump-autoload -o
          
          echo "🔍 Discovering packages..."
          php artisan package:discover
          
          echo "✅ Package setup completed"
        fi
        
        # Install TinyMCE5 if enabled
        if [ "$EVO_INSTALL_TINYMCE" = "true" ]; then
          echo "📝 Installing TinyMCE5..."
          php artisan extras extras TinyMCE5 master || echo "⚠️  TinyMCE5 installation failed"
          echo '<?php return "TinyMCE5"; ?>' > custom/config/cms/settings/which_editor.php || true
        fi
        
        echo "🎉 Evolution CMS setup completed!"
      fi
      
      # Set final permissions (for both install and update)
      cd /var/www/html/
      chown -R www-data:www-data . || true
    else
      echo "❌ Evolution CMS installation/update failed!"
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


