<?php
/**
 * Site configuration that comes before the core defines: the .env file and custom/define.php.
 *
 * Shared by core/bootstrap.php and the standalone installer, so both resolve MGR_DIR and the other
 * constants the same way. Expects functions/laravel.php and the Composer autoloader to be loaded.
 */

try {
    \EvolutionCMS\Bootstrap\EnvCacheLoader::load(dirname(__DIR__, 2));
} catch (\Throwable) {
    $projectRoot = dirname(__DIR__, 2);
    $envFile = $projectRoot . '/core/custom/.env';
    if (!is_readable($envFile)) {
        $envFile = $projectRoot . '/.env';
    }

    if (is_readable($envFile) && class_exists(\Dotenv\Dotenv::class)) {
        \Dotenv\Dotenv::createImmutable(dirname($envFile), basename($envFile))->load();
    }

    unset($projectRoot, $envFile);
}

if (file_exists(dirname(__DIR__) . '/custom/define.php')) {
    require_once dirname(__DIR__) . '/custom/define.php';
}
