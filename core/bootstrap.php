<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

require_once __DIR__ . '/functions/laravel.php';
require_once __DIR__ . '/vendor/autoload.php';

if (!defined('EVO_INSTALL_TIME')) {
    $tmp = __DIR__ . '.install';
    define('EVO_INSTALL_TIME', is_readable($tmp) ? (int)file_get_contents($tmp) : 0);
    unset($tmp);
}

require_once __DIR__ . '/includes/custom-define.inc.php';
require_once __DIR__ . '/includes/define.inc.php';

if (!defined('EVO_SESSION')) {
    define('EVO_SESSION', (bool)env('EVO_SESSION', true));
}

require_once __DIR__ . '/functions/session_proxy.php';

require_once __DIR__ . '/includes/legacy.inc.php';

require_once __DIR__ . '/includes/protect.inc.php'; // harden it

if (! is_cli() && session_status() === PHP_SESSION_NONE && !EvoSessionProxy::disabled()) {
    startCMSSession(); // start session
}
