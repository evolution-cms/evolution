#!/bin/sh
set -e

# Set Apache ServerName to suppress warning
echo "ServerName localhost" >> /etc/apache2/apache2.conf

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

# Function to detect if database has Evolution CMS tables
# Returns: 1 for fresh install, 2 for update
detect_install_type() {
  echo "🔍 Detecting installation type..." >&2
  
  # Map database type
  case "$DB_CONNECTION" in
    "pgsql"|"postgresql")
      DB_TYPE="pgsql"
      DB_PORT_DEFAULT="5432"
      ;;
    "mysql"|"mariadb")
      DB_TYPE="mysql"
      DB_PORT_DEFAULT="3306"
      ;;
    *)
      DB_TYPE="mysql"
      DB_PORT_DEFAULT="3306"
      ;;
  esac
  
  # Use default port if not set
  DB_PORT="${DB_PORT:-$DB_PORT_DEFAULT}"
  TABLE_PREFIX="${EVO_TABLE_PREFIX:-evo_}"
  
  # Check if database has tables with our prefix
  echo "   🔎 Checking for tables with prefix: '${TABLE_PREFIX}'" >&2
  echo "   🔎 Database: ${DB_DATABASE} on ${DB_HOST}:${DB_PORT}" >&2
  
  if [ "$DB_TYPE" = "pgsql" ]; then
    # PostgreSQL check
    TABLE_COUNT=$(PGPASSWORD="$DB_PASSWORD" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -t -c "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name LIKE '${TABLE_PREFIX}%';" 2>&1 | xargs)
  else
    # MySQL/MariaDB check
    TABLE_COUNT=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -sN -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$DB_DATABASE' AND table_name LIKE '${TABLE_PREFIX}%';" 2>&1)
  fi
  
  echo "   🔎 Query result: TABLE_COUNT='${TABLE_COUNT}'" >&2
  
  # Ensure TABLE_COUNT is a number
  if [ -z "$TABLE_COUNT" ]; then
    echo "   ⚠️  TABLE_COUNT is empty, defaulting to 0" >&2
    TABLE_COUNT=0
  fi
  
  # If we have tables, it's an update, otherwise it's a fresh install
  if [ "$TABLE_COUNT" -gt 0 ]; then
    echo "📊 Found $TABLE_COUNT tables with prefix '${TABLE_PREFIX}' - this is an UPDATE" >&2
    echo "2"
  else
    echo "📦 No existing tables found - this is a FRESH INSTALL" >&2
    echo "1"
  fi
}

# Auto-install Evolution CMS if not already installed
echo "🔍 Checking installation status..."
echo "   EVO_AUTO_INSTALL: ${EVO_AUTO_INSTALL}"
echo "   config.php exists: $([ -f /var/www/html/config.php ] && echo 'yes' || echo 'no')"

if ([ "$EVO_AUTO_INSTALL" = "true" ] || [ "$EVO_AUTO_INSTALL" = "1" ]) && [ ! -f "/var/www/html/config.php" ]; then
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
    
    # Automatically detect install type based on database state
    # Function outputs diagnostics to stderr and returns 1 or 2 to stdout
    INSTALL_TYPE=$(detect_install_type | tr -d '\n\r ')
    echo "   📋 INSTALL_TYPE detected: '${INSTALL_TYPE}' (expecting 1 or 2)"
    
    # For update mode (typeInstall=2), create database config file first
    if [ "${INSTALL_TYPE}" = "2" ]; then
      create_db_config
    fi
    
    # Run CLI installer
    php cli-install.php \
      --typeInstall="${INSTALL_TYPE}" \
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
      if [ "${INSTALL_TYPE}" = "2" ]; then
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
      fi
      
      # Common post-installation tasks (for both install and update)
      cd /var/www/html/core/
      
      # Install extras packages if specified
      if [ -n "$EVO_EXTRAS" ]; then
        echo "📦 Installing extras packages..."
        echo "$EVO_EXTRAS" | tr ',' '\n' | while IFS= read -r package_spec; do
          # Trim whitespace
          package_spec=$(echo "$package_spec" | xargs)
          if [ -n "$package_spec" ]; then
            # Parse package name and version (format: package:version or just package)
            package_name=$(echo "$package_spec" | cut -d':' -f1)
            package_version=$(echo "$package_spec" | cut -d':' -f2 -s)
            
            if [ -n "$package_version" ]; then
              # Convert Composer branch format (dev-main -> main, dev-dev -> dev)
              # Keep version tags as-is (v1.0.4, 1.0.4)
              extras_version="$package_version"
              if echo "$package_version" | grep -q "^dev-"; then
                # Remove 'dev-' prefix for extras command
                extras_version=$(echo "$package_version" | sed 's/^dev-//')
              fi
              
              echo "📝 Installing $package_name version $extras_version..."
              php artisan extras extras "$package_name" "$extras_version" "$package_name" || echo "⚠️  $package_name:$extras_version installation failed"
            else
              echo "📝 Installing $package_name (latest version)..."
              php artisan extras extras "$package_name" "Current and updated" "$package_name" || echo "⚠️  $package_name installation failed"
            fi
          fi
        done
        
        # Update composer autoload after installing packages
        echo "🔄 Updating composer autoload..."
        composer dump-autoload -o
        php artisan package:discover
        
        # Set TinyMCE5 as default editor if it was installed
        if echo "$EVO_EXTRAS" | grep -qi "TinyMCE5"; then
          echo '<?php return "TinyMCE5"; ?>' > custom/config/cms/settings/which_editor.php || true
        fi
        
        # Publish sTask assets if it was installed
        if echo "$EVO_EXTRAS" | grep -qi "sTask"; then
          echo "📤 Publishing sTask assets..."
          php artisan vendor:publish --tag=stask --force 2>&1 || echo "⚠️  sTask publish failed or not needed"
          
          # Setup cron job for sTask scheduled tasks
          echo "⏰ Setting up cron job for sTask..."
          echo "* * * * * cd /var/www/html/core && /usr/local/bin/php artisan schedule:run >> /var/log/cron.log 2>&1" > /etc/cron.d/stask-scheduler
          chmod 0644 /etc/cron.d/stask-scheduler
          crontab /etc/cron.d/stask-scheduler
          touch /var/log/cron.log
          echo "✅ Cron job configured"
        fi
      fi
      
      # Run migrations after all packages are installed
      cd /var/www/html/core/
      echo "🔄 Running database migrations..."
      
      # Clear caches to ensure fresh state
      php artisan cache:clear 2>&1 || true
      
      # Discover packages again to ensure all ServiceProviders are loaded
      php artisan package:discover --ansi 2>&1
      
      # Run all migrations with verbose output
      php artisan migrate --force 2>&1
      
      # If sTask is installed, run its migrations explicitly
      if echo "$EVO_EXTRAS" | grep -qi "sTask"; then
        echo "🔄 Running sTask migrations..."
        if [ -d "vendor/seiger/stask/database/migrations" ]; then
          php artisan migrate --path=vendor/seiger/stask/database/migrations --force 2>&1 || echo "⚠️  sTask migrations skipped or already ran"
        fi
      fi
      
      # Note: Package seeders run automatically via ServiceProvider after migrations
      # through MigrationsEnded event
      
      echo "🎉 Evolution CMS setup completed!"
      
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
  else
    echo "⏭️  Auto-install is disabled (EVO_AUTO_INSTALL=${EVO_AUTO_INSTALL})"
  fi
fi

# Start cron daemon if sTask is installed
if [ -f "/etc/cron.d/stask-scheduler" ]; then
  echo "🕐 Starting cron daemon for sTask..."
  service cron start
fi

echo "🎬 Starting Apache server..."
exec "$@"


