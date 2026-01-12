<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

require_once __DIR__ . '/vendor/autoload.php';

if (!defined('EVO_INSTALL_TIME')) {
    $tmp = __DIR__ . '.install';
    define('EVO_INSTALL_TIME', is_readable($tmp) ? (int)file_get_contents($tmp) : 0);
    unset($tmp);
}

try {
    \EvolutionCMS\Bootstrap\EnvCacheLoader::load(dirname(__DIR__));
} catch (\Throwable) {
    $projectRoot = dirname(__DIR__);
    $envFile = $projectRoot . '/core/custom/.env';
    if (!is_readable($envFile)) {
        $envFile = $projectRoot . '/.env';
    }

    if (is_readable($envFile) && class_exists(\Dotenv\Dotenv::class)) {
        \Dotenv\Dotenv::createImmutable(dirname($envFile), basename($envFile))->load();
    }

    unset($projectRoot, $envFile);
}

if (file_exists(__DIR__ . '/custom/define.php')) {
    require_once __DIR__ . '/custom/define.php';
}
require_once __DIR__ . '/includes/define.inc.php';

if (!defined('EVO_SESSION')) {
    define('EVO_SESSION', (bool)env('EVO_SESSION', true));
}

require_once __DIR__ . '/functions/session_proxy.php';

require_once __DIR__ . '/includes/legacy.inc.php';

require_once __DIR__ . '/includes/protect.inc.php'; // harden it

if ((! is_cli() && session_status() === PHP_SESSION_NONE) &&
    (defined('IN_MANAGER_MODE') && IN_MANAGER_MODE || !defined('NO_SESSION'))) {
    startCMSSession(); // start session
}
