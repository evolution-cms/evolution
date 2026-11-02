<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

require_once __DIR__ . '/functions/laravel.php';
// Composer uses package/path identifiers for files autoloading. The CMS helpers
// must be loaded first, but need not be required again by Composer.
$GLOBALS['__composer_autoload_files'][md5('evolution-cms/evolution:functions/laravel.php')] = true;
if (PHP_VERSION_ID >= 80400) {
    // Symfony's PHP 8.4 bootstrap returns immediately on supported runtimes.
    $GLOBALS['__composer_autoload_files'][md5('symfony/polyfill-php84:bootstrap.php')] = true;
}
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

if (defined('EVO_WORKER_MODE') && EVO_WORKER_MODE) {
    require __DIR__ . '/includes/protect.inc.php';
} else {
    require_once __DIR__ . '/includes/protect.inc.php';
}

if (! is_cli() && session_status() === PHP_SESSION_NONE && !EvoSessionProxy::disabled()
    && !EvoSessionProxy::startsAfterBoot()) {
    startCMSSession(); // start session
}
