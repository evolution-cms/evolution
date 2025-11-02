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
        echo "📦 Installing extras packages via Composer..."
        
        # Initialize custom/composer.json if it doesn't exist
        if [ ! -f "custom/composer.json" ]; then
          echo '{"name":"evolutioncms/custom","require":{},"autoload":{"psr-4":{}}}' > custom/composer.json
          echo "📝 Created custom/composer.json"
        fi

        # Convert package list to composer require format
        COMPOSER_PACKAGES=""
        echo "$EVO_EXTRAS" | tr ',' '\n' | while IFS= read -r package_spec; do
          package_spec=$(echo "$package_spec" | xargs)
          if [ -n "$package_spec" ]; then
            # Parse package name and version
            package_name=$(echo "$package_spec" | cut -d':' -f1)
            package_version=$(echo "$package_spec" | cut -d':' -f2 -s)

            # Map package names to composer package names
            case "$package_name" in
              "TinyMCE5"|"tinymce5")
                composer_package="evolution-cms-extras/tinymce5"
                ;;
              "sTask"|"stask")
                composer_package="seiger/stask"
                ;;
              *)
                # Try to find package by name (could be full composer name)
                composer_package="$package_name"
                ;;
            esac

            # Build version constraint
            if [ -n "$package_version" ]; then
              echo "📝 Installing $composer_package:$package_version..."
              composer require "$composer_package:$package_version" --no-interaction --prefer-dist && \
              php -r "
                \$file = 'custom/composer.json';
                \$data = json_decode(file_get_contents(\$file), true);
                \$data['require']['$composer_package'] = '$package_version';
                file_put_contents(\$file, json_encode(\$data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
              " || echo "⚠️  Failed to install $composer_package:$package_version"
            else
              echo "📝 Installing $composer_package (latest)..."
              composer require "$composer_package" --no-interaction --prefer-dist && \
              php -r "
                \$file = 'custom/composer.json';
                \$lock = json_decode(file_get_contents('composer.lock'), true);
                \$version = '*';
                foreach (\$lock['packages'] as \$pkg) {
                  if (\$pkg['name'] === '$composer_package') {
                    \$version = \$pkg['version'];
                    break;
                  }
                }
                \$data = json_decode(file_get_contents(\$file), true);
                \$data['require']['$composer_package'] = \$version;
                file_put_contents(\$file, json_encode(\$data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
              " || echo "⚠️  Failed to install $composer_package"
            fi
          fi
        done

        # Standard Laravel package setup
        echo "🔄 Running package discovery..."
        composer dump-autoload -o
        php artisan package:discover --ansi

        # Verify providers were created
        if [ -d "custom/config/app/providers" ]; then
          provider_count=$(find custom/config/app/providers -name "*.php" 2>/dev/null | wc -l | xargs)
          echo "✅ Discovered $provider_count service providers"
          if [ "$provider_count" -gt 0 ]; then
            echo "   📋 Providers:"
            find custom/config/app/providers -name "*.php" 2>/dev/null | while read -r f; do
              echo "      - $(basename "$f" .php)"
            done
          fi
        else
          echo "⚠️  No providers directory found - package:discover may have failed"
        fi
        
        # Set TinyMCE5 as default editor if installed
        if echo "$EVO_EXTRAS" | grep -qi "TinyMCE5"; then
          echo "⚙️  Setting TinyMCE5 as default editor..."
          mkdir -p custom/config/cms/settings/
          echo '<?php return "TinyMCE5"; ?>' > custom/config/cms/settings/which_editor.php || true
        fi
      fi
      
      # Standard Laravel package post-installation (for ALL packages, not just EVO_EXTRAS)
      cd /var/www/html/core/
      
      # Execute post-autoload-dump scripts from ALL vendor packages
      echo "🔄 Running package post-autoload scripts..."
      find vendor -name "composer.json" -type f 2>/dev/null | while read -r composer_file; do
        # Extract post-autoload-dump commands from composer.json
        post_scripts=$(php -r "
          \$json = json_decode(file_get_contents('$composer_file'), true);
          if (isset(\$json['scripts']['post-autoload-dump'])) {
            \$scripts = \$json['scripts']['post-autoload-dump'];
            if (is_array(\$scripts)) {
              foreach (\$scripts as \$script) {
                if (strpos(\$script, '@php artisan') === 0) {
                  echo substr(\$script, 5) . PHP_EOL;
                }
              }
            } elseif (strpos(\$scripts, '@php artisan') === 0) {
              echo substr(\$scripts, 5) . PHP_EOL;
            }
          }
        " 2>/dev/null)
        
        # Execute each artisan command found
        if [ -n "$post_scripts" ]; then
          package_name=$(dirname "$composer_file")
          echo "   📦 Package: $package_name"
          echo "$post_scripts" | while read -r cmd; do
            if [ -n "$cmd" ]; then
              echo "   ▶️  Running: $cmd"
              php $cmd --ansi 2>&1 | sed 's/^/      /' || true
            fi
          done
        fi
      done
      echo "✅ Package scripts completed"
      
      # Publish vendor assets (standard Laravel way)
      echo "📤 Publishing package assets..."
      php artisan vendor:publish --all --force --ansi 2>&1 || echo "⚠️  Some assets may not have published"
      
      # Run core migrations first
      echo "🔄 Running core migrations..."
      php artisan migrate --force --ansi 2>&1 || echo "⚠️  Some migrations may have failed"
      
      # ==========================================
      # TEMPORARY: Run package migrations manually
      # TODO: Remove when Evolution CMS auto-discovery is fully implemented
      # ==========================================
      echo "🔄 Running package migrations..."
      
      # Find and copy migrations from installed packages (support standard structures)
      # Structure 1: /migrations (Evolution CMS example-package style)
      find vendor -type f -path "*/migrations/*.php" ! -path "*/core/database/migrations/*" 2>/dev/null | sort | while read -r migration_file; do
        if [ -f "$migration_file" ]; then
          migration_name=$(basename "$migration_file")
          echo "   📝 Found migration: $migration_name"
          cp "$migration_file" "/var/www/html/core/database/migrations/$migration_name" 2>/dev/null || true
        fi
      done
      
      # Structure 2: /src/Database/Migrations (Laravel package standard)
      find vendor -type f -path "*/src/Database/Migrations/*.php" 2>/dev/null | sort | while read -r migration_file; do
        if [ -f "$migration_file" ]; then
          migration_name=$(basename "$migration_file")
          echo "   📝 Found migration: $migration_name"
          cp "$migration_file" "/var/www/html/core/database/migrations/$migration_name" 2>/dev/null || true
        fi
      done
      
      # Structure 3: /database/migrations (Laravel app standard)
      find vendor -type f -path "*/database/migrations/*.php" ! -path "*/core/database/migrations/*" 2>/dev/null | sort | while read -r migration_file; do
        if [ -f "$migration_file" ]; then
          migration_name=$(basename "$migration_file")
          echo "   📝 Found migration: $migration_name"
          cp "$migration_file" "/var/www/html/core/database/migrations/$migration_name" 2>/dev/null || true
        fi
      done
      
      # Run migrations again to catch package migrations
      php artisan migrate --force --ansi 2>&1 || echo "⚠️  Some package migrations may have failed"
      
      echo "✅ Migrations completed"
      # ==========================================
      
      # ==========================================
      # TEMPORARY: Run package seeders manually
      # TODO: Remove when Evolution CMS auto-discovery is fully implemented
      # ==========================================
      echo "🌱 Running package seeders..."
      
      # Find and run all seeders from installed packages (support standard structures)
      # Combine all possible seeder locations
      (
        # Structure 1: /seeders (Evolution CMS example-package style)
        find vendor -type f -path "*/seeders/*Seeder.php" 2>/dev/null
        # Structure 2: /src/Database/Seeders (Laravel package standard)
        find vendor -type f -path "*/src/Database/Seeders/*Seeder.php" 2>/dev/null
        # Structure 3: /database/seeders (Laravel app standard)
        find vendor -type f -path "*/database/seeders/*Seeder.php" 2>/dev/null
      ) | while read -r seeder_file; do
        if [ -f "$seeder_file" ]; then
          # Skip base Laravel Seeder class
          if echo "$seeder_file" | grep -q "illuminate/database/Seeder.php"; then
            continue
          fi
          
          # Extract namespace and class name from the file
          namespace=$(grep -E "^namespace " "$seeder_file" | head -1 | awk '{print $2}' | tr -d ';')
          classname=$(grep -E "^class " "$seeder_file" | head -1 | awk '{print $2}')
          
          if [ -n "$namespace" ] && [ -n "$classname" ]; then
            full_class="${namespace}\\${classname}"
            echo "   🌱 Seeding: $full_class"
            php artisan db:seed --class="$full_class" --force --ansi 2>&1 | grep -v "Nothing to seed" || true
          fi
        fi
      done
      
      echo "✅ Seeders completed"
      # ==========================================
      
      # ==========================================
      # Setup Laravel Scheduler cron job
      # This is universal for ALL packages that use Laravel scheduler
      # ==========================================
      echo "🕐 Setting up Laravel Scheduler..."
      
      # Create cron job for Laravel scheduler (runs every minute)
      cat > /etc/cron.d/laravel-scheduler <<EOF
# Laravel Scheduler - runs all scheduled tasks from packages
* * * * * www-data cd /var/www/html/core && php artisan schedule:run >> /dev/null 2>&1
EOF
      
      # Set proper permissions for cron file
      chmod 0644 /etc/cron.d/laravel-scheduler
      
      echo "✅ Laravel Scheduler configured"
      # ==========================================
      
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

# Start cron daemon
echo "🕐 Starting cron daemon..."
service cron start || true

echo "🎬 Starting Apache server..."
exec "$@"


