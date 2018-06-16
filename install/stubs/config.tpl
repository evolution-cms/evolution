<?php
/**
 * MODX Configuration file
 */
$database_type = 'mysqli';
$database_server = '[+database_server+]';
$database_user = '[+user_name+]';
$database_password = '[+password+]';
$database_connection_charset = '[+connection_charset+]';
$database_connection_method = '[+connection_method+]';
$dbase = '`[+dbase+]`';
$table_prefix = '[+table_prefix+]';

$lastInstallTime = '[+lastInstallTime+]';

$https_port = '443';
$coreClass = '\DocumentParser';
$session_cookie_path = '';
$session_cookie_domain = '';

/**
 * Preventing the overwrite of the config when updating
 * Here you can
 *  - define manual constants, dedicated specifically for you project
 *  - inject composer
 *  - change predefined variables by the environment variables
 *  - ...
 *  - etc.
 *  - PROFIT!
 */
if (file_exists(__DIR__ . '/config_mutator.php')) {
    require_once __DIR__ . '/config_mutator.php';
}

if (! defined('MODX_CLASS')) {
    define('MODX_CLASS', $coreClass);
}

if (defined('MODX_CLI')) {
    throw new RuntimeException("MODX_CLI");
} else {
    define('MODX_CLI', (php_sapi_name() === 'cli' && (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0)));
}

if (!defined('MGR_DIR')) {
    if (is_file(dirname(dirname(__DIR__)) . '/assets/cache/siteManager.php')) {
        require_once dirname(dirname(__DIR__)) . '/assets/cache/siteManager.php';
    }
}
if (!defined('MGR_DIR')) {
    if (is_dir(dirname(dirname(__DIR__)) . '/manager')) {
        define('MGR_DIR', 'manager');
    } else {
        throw new RuntimeException('MGR_DIR is not defined');
    }
}

if (MODX_CLI) {
    if (!(defined('MODX_BASE_PATH') || defined('MODX_BASE_URL'))) {
        throw new RuntimeException('Please, define MODX_BASE_PATH and MODX_BASE_URL on cli mode');
    }
    $base_path = MODX_BASE_PATH;
    $base_url = MODX_BASE_URL;

    if (!defined('MODX_SITE_URL')) {
        throw new RuntimeException('Please, define MODX_SITE_URL on cli mode');
    }
}

// automatically assign base_path and base_url
if (empty($base_path) || empty($base_url)) {
    $sapi = 'undefined';
    if ($_SERVER['PHP_SELF'] !== $_SERVER['SCRIPT_NAME'] && ($sapi === php_sapi_name() || MODX_CLI)) {
        $script_name = $_SERVER['PHP_SELF'];
    } else {
        $script_name = $_SERVER['SCRIPT_NAME'];
    }
    $script_name = str_replace('\\', '/', dirname($script_name));
    if (substr($script_name, -1 - strlen(MGR_DIR)) === '/' . MGR_DIR || strpos($script_name,
            '/' . MGR_DIR . '/') !== false) {
        $separator = MGR_DIR;
    } elseif (strpos($script_name, '/assets/') !== false) {
        $separator = 'assets';
    } else {
        $separator = '';
    }

    if ($separator !== '') {
        $a = explode('/' . $separator, $script_name);
    } else {
        $a = array($script_name);
    }

    if (count($a) > 1) {
        array_pop($a);
    }

    $url = implode($separator, $a);
    reset($a);
    $a = explode(MGR_DIR, str_replace('\\', '/', dirname(__FILE__)));
    if (count($a) > 1) {
        array_pop($a);
    }
    $pth = implode(MGR_DIR, $a);
    unset ($a);
    $base_url = $url . (substr($url, -1) != '/' ? '/' : '');
    $base_path = $pth . (substr($pth, -1) != '/' && substr($pth, -1) != '\\' ? '/' : '');
}

if (!defined('MODX_BASE_PATH')) {
    define('MODX_BASE_PATH', $base_path);
}

if (!preg_match('/\/$/', MODX_BASE_PATH)) {
    throw new RuntimeException('Please, use trailing slash at the end of MODX_BASE_PATH');
}

if (!defined('MODX_BASE_URL')) {
    define('MODX_BASE_URL', $base_url);
}

if (!preg_match('/\/$/', MODX_BASE_URL)) {
    throw new RuntimeException('Please, use trailing slash at the end of MODX_BASE_URL');
}

if (!defined('MODX_MANAGER_PATH')) {
    define('MODX_MANAGER_PATH', MODX_BASE_PATH . MGR_DIR . '/');
}

if (!defined('MODX_SITE_HOSTNAMES') && is_file(MODX_BASE_PATH . '/assets/cache/siteHostnames.php')) {
    require_once MODX_BASE_PATH . '/assets/cache/siteHostnames.php';
}
if (!defined('MODX_SITE_HOSTNAMES')) {
    define('MODX_SITE_HOSTNAMES', '');
}


// check for valid hostnames
$site_hostname = MODX_CLI ? 'localhost' : str_replace(':' . $_SERVER['SERVER_PORT'], '', $_SERVER['HTTP_HOST']);
$site_hostnames = explode(',', MODX_SITE_HOSTNAMES);
if (!empty($site_hostnames[0]) && !in_array($site_hostname, $site_hostnames)) {
    $site_hostname = $site_hostnames[0];
}

// assign site_url
if (!defined('MODX_SITE_URL')) {
    $secured = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https');
    $site_url = ((isset ($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || $_SERVER['SERVER_PORT'] == $parameters['https_port'] || $secured) ? 'https://' : 'http://';
    $site_url .= $site_hostname;
    if ($_SERVER['SERVER_PORT'] != 80) {
        $site_url = str_replace(':' . $_SERVER['SERVER_PORT'], '', $site_url);
    } // remove port from HTTP_HOST

    $site_url .= ($_SERVER['SERVER_PORT'] == 80 || (isset ($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || $_SERVER['SERVER_PORT'] == $parameters['https_port']) ? '' : ':' . $_SERVER['SERVER_PORT'];
    $site_url .= $base_url;

    define('MODX_SITE_URL', $site_url);
}

if (!preg_match('/\/$/', MODX_SITE_URL)) {
    throw new RuntimeException('Please, use trailing slash at the end of MODX_SITE_URL');
}

if (!defined('MODX_MANAGER_URL')) {
    define('MODX_MANAGER_URL', MODX_SITE_URL . MGR_DIR . '/');
}

if (!defined('EVO_BOOTSTRAP_FILE')) {
    define('EVO_BOOTSTRAP_FILE', __DIR__ . '/bootstrap.php');
    require_once EVO_BOOTSTRAP_FILE;
}

if (!defined('EVO_SERVICES_FILE')) {
    define('EVO_SERVICES_FILE', __DIR__ . '/services.php');
}
