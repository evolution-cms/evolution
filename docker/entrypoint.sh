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
  GIT_BRANCH="${GIT_BRANCH:-main}"
  GIT_URL_WITH_TOKEN=$(echo "$GIT_REPO" | sed "s|https://|https://${GIT_TOKEN}@|")
  
  # Configure Git
  git config --global user.email "${GIT_USER_EMAIL:-evo@localhost}"
  git config --global user.name "${GIT_USER_NAME:-Evolution CMS}"
  git config --global --add safe.directory /var/www/html
  
  cd /var/www/html
  
  # Check if already initialized
  if [ ! -d ".git" ]; then
    git init
    git remote add origin "$GIT_URL_WITH_TOKEN"
    git fetch origin "$GIT_BRANCH"
    git checkout -b "$GIT_BRANCH" origin/"$GIT_BRANCH" 2>/dev/null || {
      git checkout -b "$GIT_BRANCH"
      git merge origin/"$GIT_BRANCH" --allow-unrelated-histories --no-edit || {
        echo "⚠️  Merge conflicts detected"
        git merge --abort 2>/dev/null || true
      }
    }
  else
    git checkout "$GIT_BRANCH" 2>/dev/null || git checkout -b "$GIT_BRANCH"
    git stash push -m "Auto-stash before pull" 2>/dev/null || true
    git pull origin "$GIT_BRANCH" --no-edit || {
      echo "⚠️  Pull failed, resetting..."
      git fetch origin "$GIT_BRANCH"
      git reset --hard origin/"$GIT_BRANCH"
    }
    git stash pop 2>/dev/null || true
  fi
  
  # Set proper permissions
  chown -R www-data:www-data /var/www/html 2>/dev/null || true
  
  echo "✅ Git sync completed"
fi

# Function to create database config file from ENV variables
create_db_config() {
  
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
}

# Function to detect if database has Evolution CMS tables
# Returns: 1 for fresh install, 2 for update
detect_install_type() {
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
    echo "2"
  else
    echo "1"
  fi
}

# Auto-install Evolution CMS if not already installed
if ([ "$EVO_AUTO_INSTALL" = "true" ] || [ "$EVO_AUTO_INSTALL" = "1" ]) && [ ! -f "/var/www/html/config.php" ]; then
  echo "🚀 Installing Evolution CMS..."
  
  # Check if install directory exists
  if [ -d "/var/www/html/install" ] && [ -f "/var/www/html/install/cli-install.php" ]; then
    
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
    INSTALL_TYPE=$(detect_install_type 2>/dev/null | tr -d '\n\r ')
    
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
      # Get Evolution CMS version
      EVO_VERSION=$(php -r "print_r(include('/var/www/html/core/factory/version.php'));" | grep -o "Evolution CMS [0-9.]* ([^)]*)" || echo "Evolution CMS")
      
      if [ "${INSTALL_TYPE}" = "2" ]; then
        echo "✅ Updated successfully to $EVO_VERSION"
        
        # Create .install file for update mode
        echo $(date +%s) > /var/www/html/core/.install
        chmod 644 /var/www/html/core/.install
        
        # Update composer packages for update mode
        cd /var/www/html/core/
        if [ -f "custom/composer.json" ]; then
          composer dump-autoload -o > /dev/null 2>&1
          php artisan package:discover > /dev/null 2>&1
        fi
      else
        echo "✅ Installed $EVO_VERSION successfully"
        
        # Post-installation setup (only for fresh install, not for update)
        cd /var/www/html/core/
        
        # Create main package if MAIN_PACKAGE_NAME is set
        if [ -n "$EVO_MAIN_PACKAGE_NAME" ]; then
          php artisan package:create "$EVO_MAIN_PACKAGE_NAME" > /dev/null 2>&1
          # Normalize package name to StudlyCase (first letter uppercase)
          EVO_MAIN_PACKAGE_STUDLY=$(printf "%s" "$EVO_MAIN_PACKAGE_NAME" | awk '{print toupper(substr($0,1,1)) substr($0,2)}')
          # Write exact PHP string with double quotes and escaped backslashes
          cat > custom/config/cms/settings/ControllerNamespace.php <<PHP
<?php return "EvolutionCMS\\\\$EVO_MAIN_PACKAGE_STUDLY\\\\Controllers\\\\";
PHP
          
          composer dump-autoload -o > /dev/null 2>&1
          php artisan package:discover > /dev/null 2>&1
        fi
      fi
      
      # Common post-installation tasks (for both install and update)
      cd /var/www/html/core/
      
      # Install extras packages if specified
      if [ -n "$EVO_EXTRAS" ]; then
        echo "📦 Installing extra packages..."
        
        # Initialize custom/composer.json if it doesn't exist
        if [ ! -f "custom/composer.json" ]; then
          echo '{"name":"evolutioncms/custom","require":{},"autoload":{"psr-4":{}}}' > custom/composer.json
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
              echo "   📦 Installing $composer_package:$package_version..."
              composer require "$composer_package:$package_version" --no-interaction --prefer-dist 2>&1 | grep -v "^$" | head -5 && \
              php -r "
                \$file = 'custom/composer.json';
                \$data = json_decode(file_get_contents(\$file), true);
                \$data['require']['$composer_package'] = '$package_version';
                file_put_contents(\$file, json_encode(\$data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
              " || echo "⚠️  Failed: $composer_package:$package_version"
            else
              echo "   📦 Installing $composer_package (latest)..."
              composer require "$composer_package" --no-interaction --prefer-dist 2>&1 | grep -v "^$" | head -5 && \
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
              " || echo "⚠️  Failed: $composer_package"
            fi
          fi
        done

        # Standard Laravel package setup
        echo "🔄 Configuring packages..."
        composer dump-autoload -o 2>&1 | grep -E "(Generated|Generating)" || true
        php artisan package:discover --ansi 2>&1 | grep -v "🔥" | grep -E "Discovered|packages" || true

        # Verify providers were created and show package versions
        if [ -d "custom/config/app/providers" ]; then
          provider_count=$(find custom/config/app/providers -name "*.php" 2>/dev/null | wc -l | xargs)
          if [ "$provider_count" -gt 0 ]; then
            echo "✅ Configured $provider_count packages:"
            find custom/config/app/providers -name "*.php" 2>/dev/null | while read -r f; do
              provider_name=$(basename "$f" .php)
              # Try to find package version from composer.lock
              package_info=$(php -r "
                \$lock = json_decode(file_get_contents('composer.lock'), true);
                foreach (\$lock['packages'] as \$pkg) {
                  if (stripos(\$pkg['name'], '$provider_name') !== false || stripos('$provider_name', str_replace('/', '', \$pkg['name'])) !== false) {
                    echo \$pkg['name'] . ' ' . \$pkg['version'];
                    break;
                  }
                }
              " 2>/dev/null)
              if [ -n "$package_info" ]; then
                echo "   - $provider_name ($package_info)"
              else
                echo "   - $provider_name"
              fi
            done
          fi
        fi
        
        # Set TinyMCE5 as default editor if installed
        if echo "$EVO_EXTRAS" | grep -qi "TinyMCE5"; then
          mkdir -p custom/config/cms/settings/
          echo '<?php return "TinyMCE5"; ?>' > custom/config/cms/settings/which_editor.php || true
        fi
      fi
      
      # Standard Laravel package post-installation (for ALL packages, not just EVO_EXTRAS)
      cd /var/www/html/core/
      
      # Execute post-autoload-dump scripts from ALL vendor packages
      script_count=0
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
        
        # Execute each artisan command found (silently)
        if [ -n "$post_scripts" ]; then
          echo "$post_scripts" | while read -r cmd; do
            if [ -n "$cmd" ]; then
              php $cmd > /dev/null 2>&1 || true
              script_count=$((script_count + 1))
            fi
          done
        fi
      done
      
      # Publish vendor assets (standard Laravel way)
      echo "📤 Publishing assets..."
      php artisan vendor:publish --all --force --ansi 2>&1 | grep -E "(Copied|Publishing)" || true
      
      # Run core migrations first
      echo "🔄 Running migrations..."
      php artisan migrate --force --ansi 2>&1 | grep -v "^$" | grep -v "🔥" || echo "⚠️  Some migrations may have failed"
      
      # Find and copy migrations from installed packages (support standard structures)
      migration_count=0
      find vendor -type f -path "*/migrations/*.php" ! -path "*/core/database/migrations/*" 2>/dev/null | sort | while read -r migration_file; do
        if [ -f "$migration_file" ]; then
          migration_name=$(basename "$migration_file")
          echo "   📝 Found migration: $migration_name"
          cp "$migration_file" "/var/www/html/core/database/migrations/$migration_name" 2>/dev/null || true
          migration_count=$((migration_count + 1))
        fi
      done
      
      find vendor -type f -path "*/src/Database/Migrations/*.php" 2>/dev/null | sort | while read -r migration_file; do
        if [ -f "$migration_file" ]; then
          migration_name=$(basename "$migration_file")
          echo "   📝 Found migration: $migration_name"
          cp "$migration_file" "/var/www/html/core/database/migrations/$migration_name" 2>/dev/null || true
        fi
      done
      
      find vendor -type f -path "*/database/migrations/*.php" ! -path "*/core/database/migrations/*" 2>/dev/null | sort | while read -r migration_file; do
        if [ -f "$migration_file" ]; then
          migration_name=$(basename "$migration_file")
          echo "   📝 Found migration: $migration_name"
          cp "$migration_file" "/var/www/html/core/database/migrations/$migration_name" 2>/dev/null || true
        fi
      done
      
      # Run migrations again to catch package migrations
      php artisan migrate --force --ansi 2>&1 | grep -v "^$" | grep -v "🔥" || echo "⚠️  Some package migrations may have failed"
      
      # Run package seeders
      echo "🌱 Running seeders..."
      (
        find vendor -type f -path "*/seeders/*Seeder.php" 2>/dev/null
        find vendor -type f -path "*/src/Database/Seeders/*Seeder.php" 2>/dev/null
        find vendor -type f -path "*/database/seeders/*Seeder.php" 2>/dev/null
      ) | while read -r seeder_file; do
        if [ -f "$seeder_file" ]; then
          if echo "$seeder_file" | grep -q "illuminate/database/Seeder.php"; then
            continue
          fi
          
          namespace=$(grep -E "^namespace " "$seeder_file" | head -1 | awk '{print $2}' | tr -d ';')
          classname=$(grep -E "^class " "$seeder_file" | head -1 | awk '{print $2}')
          
          if [ -n "$namespace" ] && [ -n "$classname" ]; then
            full_class="${namespace}\\${classname}"
            echo "   🌱 Seeding: $classname"
            php artisan db:seed --class="$full_class" --force --ansi 2>&1 | grep -v "^$" | grep -v "Nothing to seed" | grep -v "🔥" || true
          fi
        fi
      done
      
      # Setup Laravel Scheduler cron job
      cat > /etc/cron.d/laravel-scheduler <<EOF
# Laravel Scheduler - runs all scheduled tasks from packages
* * * * * www-data cd /var/www/html/core && php artisan schedule:run >> /dev/null 2>&1
EOF
      chmod 0644 /etc/cron.d/laravel-scheduler
      
      echo "✅ Setup completed"
      
      # Set final permissions (for both install and update)
      cd /var/www/html/
      chown -R www-data:www-data . || true
    else
      echo "❌ Installation failed"
      exit 1
    fi
  else
    echo "❌ Install directory not found"
  fi
fi

# Start cron daemon
service cron start || true

# Start Apache server
exec "$@"


