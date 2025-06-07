<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

require_once __DIR__ . '/vendor/autoload.php';

$tmp = __DIR__ . '.install';
define('EVO_INSTALL_TIME', is_readable($tmp) ? (int)file_get_contents($tmp) : 0);
unset($tmp);

$envFile = __DIR__ . '/custom/.env';
if (is_readable($envFile) && class_exists(Dotenv\Dotenv::class)) {
    /**
     * @see: https://github.com/vlucas/phpdotenv
     */
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/custom');
    $dotenv->load();
}
unset($envFile, $dotenv);

if (file_exists(__DIR__ . '/custom/define.php')) {
    require_once __DIR__ . '/custom/define.php';
}
require_once __DIR__ . '/includes/define.inc.php';

require_once __DIR__ . '/includes/legacy.inc.php';

require_once __DIR__ . '/includes/protect.inc.php'; // harden it

if ((! is_cli() && session_status() === PHP_SESSION_NONE) &&
    (defined('IN_MANAGER_MODE') && IN_MANAGER_MODE || !defined('NO_SESSION'))) {
    startCMSSession(); // start session
}
