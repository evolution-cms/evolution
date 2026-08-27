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

if (!defined('EVO_CLASS')) {
    define('EVO_CLASS', '\DocumentParser');
}

if (!defined('EVO_SITE_HOSTNAMES')) {
    define('EVO_SITE_HOSTNAMES', '');
}

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
    if (!isset($_SERVER['SERVER_PORT'])) {
        $_SERVER['SERVER_PORT'] = 80;
    }

    // Host is what the browser actually asked for, and behind a proxy or a
    // published container port it is the only view of the site that can be
    // reached again - SERVER_PORT is the port this process listens on, which
    // may be a different number entirely. So the host header decides both the
    // hostname and the port, and SERVER_PORT is consulted only when there is
    // no host header at all.
    $site_hostname = 'localhost';
    $site_port = null;
    $has_http_host = false;
    if (!is_cli() && !empty($_SERVER['HTTP_HOST'])) {
        // Anchored on purpose: str_replace(':' . SERVER_PORT, ...) turns
        // "localhost:8080" into "localhost80" whenever the server itself
        // listens on 80. The character sets are spelled out rather than
        // written as "everything up to the colon", because whatever lands here
        // is pasted into every URL the site emits: a header of
        // "localhost@evil.example" would otherwise become the userinfo of
        // http://localhost@evil.example/ and send the visitor elsewhere.
        // The two branches share no first character and neither repetition can
        // match the delimiter that follows it, so the match stays linear.
        $host_pattern = '/^(?:([A-Za-z0-9._-]+)|(\[[0-9A-Fa-f:.]+\]))(?::(\d{1,5}))?$/';
        if (preg_match($host_pattern, $_SERVER['HTTP_HOST'], $matches)) {
            $port = isset($matches[3]) && $matches[3] !== '' ? (int) $matches[3] : null;
            if ($port === null || ($port > 0 && $port <= 65535)) {
                $has_http_host = true;
                $site_hostname = $matches[1] !== '' ? $matches[1] : $matches[2];
                $site_port = $port;
            }
        }
        unset($host_pattern, $matches, $port);
    }

    // check for valid hostnames
    $site_hostnames = explode(',', EVO_SITE_HOSTNAMES);
    if (!empty($site_hostnames[0]) && !in_array($site_hostname, $site_hostnames)) {
        $site_hostname = $site_hostnames[0];
    }
    unset($site_hostnames);

    // assign site_url
    if ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') ||
        $_SERVER['SERVER_PORT'] == HTTPS_PORT ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ) {
        $scheme = 'https';
        $default_port = (int) HTTPS_PORT;
    } else {
        $scheme = 'http';
        $default_port = 80;
    }

    // A host header omits the port when it is the default one for the scheme,
    // so "no port here" is an answer rather than a gap to fill from SERVER_PORT.
    if (!$has_http_host) {
        $site_port = (int) $_SERVER['SERVER_PORT'];
    }

    $site_url = $scheme . '://' . $site_hostname;
    if ($site_port !== null && $site_port !== $default_port) {
        $site_url .= ':' . $site_port;
    }
    unset($site_hostname, $site_port, $has_http_host, $scheme, $default_port);

    $site_url .= EVO_BASE_URL;
}
if (!defined('EVO_SITE_URL')) { define('EVO_SITE_URL', $site_url ?? null); }
unset($site_url);

if (!preg_match('/\/$/', EVO_SITE_URL)) {
    throw new RuntimeException('Please, use trailing slash at the end of EVO_SITE_URL');
}

if (!defined('EVO_MANAGER_URL')) {
    define('EVO_MANAGER_URL', EVO_SITE_URL . MGR_DIR . '/');
}

// Must keep its first value: the sanitize helpers in core/functions/preload.php strip
// this seed back out, so a second, different seed would leave the marker in the output.
if (!defined('EVO_SANITIZE_SEED')) {
    define('EVO_SANITIZE_SEED', 'sanitize_seed_' . base_convert(md5(__FILE__), 16, 36));
}

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
