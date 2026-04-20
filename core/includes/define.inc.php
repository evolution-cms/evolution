<?php

if (!defined('HTTPS_PORT')) {
    define('HTTPS_PORT', env('HTTPS_PORT', '443')); //$https_port
}

if (!defined('SESSION_STORAGE')) {
    define('SESSION_STORAGE', env('SESSION_STORAGE', 'default')); // $session_cookie_path
}

if (!defined('REDIS_HOST')) {
    define('REDIS_HOST', env('REDIS_HOST', '127.0.0.1')); // $session_cookie_path
}

if (!defined('REDIS_PORT')) {
    define('REDIS_PORT', env('REDIS_PORT', '6379')); // $session_cookie_path
}

if (!defined('SESSION_COOKIE_PATH')) {
    define('SESSION_COOKIE_PATH', env('SESSION_COOKIE_PATH', '')); // $session_cookie_path
}

if (!defined('SESSION_COOKIE_DOMAIN')) {
    define('SESSION_COOKIE_DOMAIN', env('SESSION_COOKIE_DOMAIN', '')); //$session_cookie_domain
}

if (!defined('SESSION_COOKIE_NAME')) {
    // For legacy extras not using startCMSSession
    define('SESSION_COOKIE_NAME', env('SESSION_COOKIE_NAME', genEvoSessionName())); // $site_sessionname
}

define('EVO_CLASS', '\DocumentParser');

define('EVO_SITE_HOSTNAMES', '');

if (!defined('MGR_DIR')) {
    define('MGR_DIR', env('MGR_DIR', 'manager'));
}

if (!defined('EVO_CORE_PATH')) {
    define('EVO_CORE_PATH', env('EVO_CORE_PATH', dirname(__DIR__) . '/'));
}

if (!defined('EVO_STORAGE_PATH')) {
    define('EVO_STORAGE_PATH', env('EVO_STORAGE_PATH', EVO_CORE_PATH . 'storage/'));
}

if (!defined('EVO_BASE_PATH') || !defined('EVO_BASE_URL')) {
    // automatically assign base_path and base_url
    $script_name = str_replace(
        '\\',
        '/',
        dirname(
            get_by_key(
                $_SERVER,
                ($_SERVER['PHP_SELF'] !== $_SERVER['SCRIPT_NAME'] && ('undefined' === php_sapi_name() || is_cli())) ?
                    'PHP_SELF' : 'SCRIPT_NAME'
            )
        )
    );

    if (substr($script_name, -1 - strlen(MGR_DIR)) === '/' . MGR_DIR ||
        strpos($script_name, '/' . MGR_DIR . '/') !== false
    ) {
        $separator = MGR_DIR;
    } elseif (strpos($script_name, '/assets/') !== false) {
        $separator = 'assets';
    } else {
        $separator = '';
    }

    if ($separator !== '') {
        $items = explode('/' . $separator, $script_name);
    } else {
        $items = [$script_name];
    }
    unset($script_name);

    if (count($items) > 1) {
        array_pop($items);
    }

    $url = implode($separator, $items);

    $base_url = rtrim(implode($separator, $items), '/') . '/';
    unset($separator);

    reset($items);
    $items = explode(MGR_DIR, str_replace('\\', '/', dirname(__DIR__, 2)));
    if (count($items) > 1) {
        array_pop($items);
    }

    $base_path = rtrim(
        str_replace('\\', '/', implode(MGR_DIR, $items))
        , '/'
    ) . '/';
}

if (!defined('EVO_CORE_PATH')) { define('EVO_CORE_PATH', $config['core'] . '/'); }
if (!defined('EVO_BASE_PATH')) { define('EVO_BASE_PATH', $base_path ?? null); }
if (!defined('EVO_BASE_URL')) { define('EVO_BASE_URL', $base_url ?? null); }

unset($base_path, $base_url);

if (!preg_match('/\/$/', EVO_BASE_PATH)) {
    throw new RuntimeException('Please, use trailing slash at the end of EVO_BASE_PATH');
}

if (!preg_match('/\/$/', EVO_BASE_URL)) {
    throw new RuntimeException('Please, use trailing slash at the end of EVO_BASE_URL');
}

if (!defined('EVO_MANAGER_PATH')) {
    define('EVO_MANAGER_PATH', EVO_BASE_PATH . MGR_DIR . '/');
}

if (!defined('EVO_SITE_URL')) {
    // check for valid hostnames
    $site_hostname = 'localhost';
    if (!is_cli()) {
        $site_hostname = str_replace(
            ':' . $_SERVER['SERVER_PORT'],
            '',
            get_by_key($_SERVER, 'HTTP_HOST', $site_hostname)
        );
    }
    $site_hostnames = explode(',', EVO_SITE_HOSTNAMES);
    if (!empty($site_hostnames[0]) && !in_array($site_hostname, $site_hostnames)) {
        $site_hostname = $site_hostnames[0];
    }
    unset($site_hostnames);

    if (!isset($_SERVER['SERVER_PORT'])) {
        $_SERVER['SERVER_PORT'] = 80;
    }

    // assign site_url
    if ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') ||
        $_SERVER['SERVER_PORT'] == HTTPS_PORT ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ) {
        $site_url = 'https://' . $site_hostname;
    } else {
        $site_url = 'http://' . $site_hostname;
    }
    unset($site_hostname);

    if ($_SERVER['SERVER_PORT'] !== 80) { // remove port from HTTP_HOST
        $site_url = str_replace(':' . $_SERVER['SERVER_PORT'], '', $site_url);
    }

    if (!in_array((int)$_SERVER['SERVER_PORT'], [80, (int)HTTPS_PORT], true) &&
        strtolower(get_by_key($_SERVER, 'HTTPS', 'off'))
    ) {
        $site_url .= ':' . $_SERVER['SERVER_PORT'];
    }

    $site_url .= EVO_BASE_URL;
}
if (!defined('EVO_SITE_URL')) { define('EVO_SITE_URL', $site_url ?? null); }
unset($site_url);

if (!preg_match('/\/$/', EVO_SITE_URL)) {
    throw new RuntimeException('Please, use trailing slash at the end of EVO_SITE_URL');
}

define('EVO_MANAGER_URL', EVO_SITE_URL . MGR_DIR . '/');
define('EVO_SANITIZE_SEED', 'sanitize_seed_' . base_convert(md5(__FILE__), 16, 36));

if (is_cli()) {
    if (!defined('EVO_CLI')) { define('EVO_CLI', true); }

    if (!(defined('EVO_BASE_PATH') || defined('EVO_BASE_URL'))) {
        throw new RuntimeException('Please, define EVO_BASE_PATH and EVO_BASE_URL on cli mode');
    }

    if (!defined('EVO_SITE_URL')) {
        throw new RuntimeException('Please, define EVO_SITE_URL on cli mode');
    }
}

/**
 * @deprecated
 * @since 3.5.5
 *
 * This block defines constants that will be permanently deleted. Please replace them in your code with appropriate options.
 *
 * @todo [remove@3.7] Remove in Evolution CMS 3.7
 */
if (!defined('MODX_CLASS')) {
    define('MODX_CLASS', EVO_CLASS);
}
if (!defined('MODX_SITE_HOSTNAMES')) {
    define('MODX_SITE_HOSTNAMES', EVO_SITE_HOSTNAMES);
}
if (!defined('MODX_BASE_PATH')) {
    define('MODX_BASE_PATH', EVO_BASE_PATH);
}
if (!defined('MODX_BASE_URL')) {
    define('MODX_BASE_URL', EVO_BASE_URL);
}
if (!defined('MODX_MANAGER_PATH')) {
    define('MODX_MANAGER_PATH', EVO_MANAGER_PATH);
}
if (!defined('MODX_SITE_URL')) {
    define('MODX_SITE_URL', EVO_SITE_URL);
}
if (!defined('MODX_MANAGER_URL')) {
    define('MODX_MANAGER_URL', EVO_MANAGER_URL);
}
if (!defined('MODX_SANITIZE_SEED')) {
    define('MODX_SANITIZE_SEED', EVO_SANITIZE_SEED);
}
