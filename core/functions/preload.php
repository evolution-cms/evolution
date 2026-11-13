<?php

if (!function_exists('evo')) {
    /**
     * @return DocumentParser
     */
    function evo()
    {
        if (!defined('EVO_CLASS')) {
            if (!class_exists('\DocumentParser')) {
                throw new RuntimeException('EVO_CLASS not defined and EvolutionCMS\Core class not exists');
            }
            define('EVO_CLASS', '\DocumentParser');
        }

        global $evo;
        if ($evo === null) {
            try {
                $obj = new ReflectionClass(EVO_CLASS);
                $evo = $obj->newInstanceWithoutConstructor()->getInstance();
            } catch (ReflectionException $exception) {
                echo $exception->getMessage();
                exit($exception->getCode());
            }
        }

        if (IN_MANAGER_MODE == true && IN_INSTALL_MODE == false  && EVO_API_MODE != true) {
            // attempt to foil some simple types of CSRF attacks
            if ((int)$evo->getConfig('validate_referer') !== 0
                && !\EvolutionCMS\Support\ManagerReferer::allows(
                    $_SERVER,
                    (string)EVO_SITE_URL,
                    (string)$evo->getConfig('server_protocol', 'http')
                )) {
                rejectManagerReferer($evo);
            }
        }

        return $evo;
    }
}

if (!function_exists('rejectManagerReferer')) {
    /**
     * Refuse a manager request whose referer is missing or foreign (`validate_referer`).
     * A script caller shows the body itself, so it gets the localized message as JSON, not the alert page.
     *
     * @param \EvolutionCMS\Interfaces\CoreInterface $evo
     */
    function rejectManagerReferer($evo): never
    {
        // every evo() call repeats the check, and shutdown callbacks call evo(): answer once
        static $refused = false;
        if ($refused) {
            exit;
        }
        $refused = true;

        // evo() is still being built: the language comes from its configuration, never from evo()
        $language = (string)$evo->getConfig('manager_language', 'en');

        if (\EvolutionCMS\Support\CsrfMessage::wantsScriptResponse($_SERVER, $_POST)) {
            if (!headers_sent()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=UTF-8');
                header('Cache-Control: no-store');
            }
            echo json_encode(
                \EvolutionCMS\Support\CsrfMessage::payload(\EvolutionCMS\Support\CsrfMessage::REFERER, $language),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            exit;
        }

        $evo->webAlertAndQuit(
            \EvolutionCMS\Support\CsrfMessage::text(\EvolutionCMS\Support\CsrfMessage::REFERER, $language),
            '/' . MGR_DIR . '/index.php'
        );
        exit;
    }
}

if (!function_exists('evolutionCMS')) {
    /**
     * @return DocumentParser
     */
    function evolutionCMS()
    {
        return evo();
    }
}

if (!function_exists('genEvoSessionName')) {
    /**
     * @return string
     */
    function genEvoSessionName()
    {
        $_ = crc32(__FILE__);
        $_ = sprintf('%u', $_);

        return 'evo' . base_convert($_, 10, 36);
    }
}

if (!function_exists('startCMSSession')) {
    /**
     * @return void
     */
    function startCMSSession()
    {
        global $session_cookie_path, $session_cookie_domain;
        if (is_cli()) {
            return;
        }

        if (defined('EVO_SESSION') && EVO_SESSION) {
            EvoSessionProxy::earlyInit();
            return;
        }

        session_name(SESSION_COOKIE_NAME);
        removeInvalidCmsSessionIds(SESSION_COOKIE_NAME);
        session_cache_limiter('');
        if (isset ($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') {
            $secure = true;
        } else {
            $secure = ($_SERVER['SERVER_PORT'] == HTTPS_PORT);
        }
        session_set_cookie_params(
            0
            , $session_cookie_path ? $session_cookie_path : EVO_BASE_URL
            , $session_cookie_domain ? $session_cookie_domain : ''
            , $secure
            , true
        );

        $previousStorage = SESSION_STORAGE == 'redis' ? useRedisSessionStorage() : null;

        $sessionname = session_name();
        // safe session - see https://stackoverflow.com/a/33024310/1066234
        if (isset($_COOKIE[$sessionname]) && !preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $_COOKIE[$sessionname])) {
            unset($_COOKIE[$sessionname]);
        }

        if ($previousStorage === null) {
            session_start();
        } else {
            try {
                $started = @session_start();
            } catch (\Throwable $exception) {
                $started = false;
            }
            if (!$started) {
                // Redis is unreachable or the lock wait ran out: keep the site up on the storage configured before
                error_log('Evolution CMS: Redis session storage is unavailable or the session stayed locked, sessions use "'
                    . $previousStorage['session.save_handler'] . '" for this request');
                foreach ($previousStorage as $option => $value) {
                    ini_set($option, $value);
                }
                session_start();
            }
        }

        // no session started, exit
        if (session_status() === PHP_SESSION_NONE) {
            exit();
        }
        $key = "modx.mgr.session.cookie.lifetime";

        if (isset($_SESSION[$key]) && is_numeric($_SESSION[$key])) {
            setcookie(
                session_name()
                , session_id()
                , (int)$_SESSION[$key] ? $_SERVER['REQUEST_TIME'] + (int)$_SESSION[$key] : 0
                , $session_cookie_path ? $session_cookie_path : EVO_BASE_URL
                , $session_cookie_domain ? $session_cookie_domain : ''
                , $secure
                , true
            );
        }
        $createdKey = 'evo.session.created.time';
        if (!isset($_SESSION[$createdKey])) {
            $_SESSION[$createdKey] = $_SERVER['REQUEST_TIME'] ?? time();
        }
    }
}

if (!function_exists('useRedisSessionStorage')) {
    /**
     * Points the legacy PHP session (EVO_SESSION off, SESSION_STORAGE=redis) at the ext-redis session handler.
     * It keeps the `PHPREDIS_SESSION:<id>` keys and the locking of the userland handler it replaces.
     *
     * @return array|null the previous handler settings to fall back to, null when Redis is not used
     */
    function useRedisSessionStorage(): ?array
    {
        if (!extension_loaded('redis')) {
            return null;
        }

        $previous = [
            'session.save_handler' => (string)ini_get('session.save_handler'),
            'session.save_path' => (string)ini_get('session.save_path'),
        ];
        $savePath = redisSessionSavePath([
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'timeout' => env('REDIS_TIMEOUT', 60),
            'database' => env('REDIS_SESSION_DATABASE', 0),
            'user' => env('REDIS_USER', null),
            'password' => env('REDIS_PASS', null),
        ]);
        if (ini_set('session.save_handler', 'redis') === false || ini_set('session.save_path', $savePath) === false) {
            foreach ($previous as $option => $value) {
                ini_set($option, $value);
            }

            return null;
        }

        // An operator who enabled locking configured it; otherwise lock as the replaced handler did, but bounded
        if (ini_get('redis.session.locking_enabled') !== '1') {
            $lockSettings = redisSessionLockSettings((int)ini_get('max_execution_time'), [
                'redis.session.lock_expire' => ini_get('redis.session.lock_expire'),
                'redis.session.lock_retries' => ini_get('redis.session.lock_retries'),
                'redis.session.lock_wait_time' => ini_get('redis.session.lock_wait_time'),
            ]);
            foreach ($lockSettings + ['redis.session.locking_enabled' => '1'] as $option => $value) {
                ini_set($option, $value);
            }
        }

        return $previous;
    }
}

if (!function_exists('redisSessionLockSettings')) {
    /**
     * The ext-redis lock settings to apply when Evolution enables session locking itself.
     * Values an operator changed from the phpredis defaults (expire 0, retries 100, wait 20000 us) are kept.
     *
     * - Expiry: max_execution_time, or 30 s without one. phpredis otherwise takes max_execution_time,
     *   and with 0 the lock never expires: one killed request would lock its session for good.
     * - Acquisition: retries for 0.7 x max_execution_time, or 20 s without one (the replaced handler's
     *   budget), never longer than the expiry. Then session_start() fails and the caller falls back.
     *
     * @param int $maxExecutionTime
     * @param array<string, string|false> $current the current ini values, keyed by option
     * @return array<string, string> the options to set
     */
    function redisSessionLockSettings(int $maxExecutionTime, array $current): array
    {
        $limit = max(0, $maxExecutionTime);
        $settings = [];

        $expire = (int)($current['redis.session.lock_expire'] ?? 0);
        if ($expire <= 0) {
            $expire = $limit > 0 ? $limit : 30;
            $settings['redis.session.lock_expire'] = (string)$expire;
        }

        $wait = (int)($current['redis.session.lock_wait_time'] ?? 0);
        if ($wait <= 0) {
            $wait = 20000;
            $settings['redis.session.lock_wait_time'] = (string)$wait;
        }

        $retries = $current['redis.session.lock_retries'] ?? false;
        if ($retries === false || $retries === '' || (int)$retries === 100) {
            $budget = min($limit > 0 ? 0.7 * $limit : 20, $expire);
            $settings['redis.session.lock_retries'] = (string)max(1, (int)ceil($budget * 1000000 / $wait));
        }

        return $settings;
    }
}

if (!function_exists('redisSessionSavePath')) {
    /**
     * Builds the ext-redis `session.save_path` from REDIS_* settings.
     *
     * @param array{host?: mixed, port?: mixed, timeout?: mixed, database?: mixed, user?: mixed, password?: mixed} $settings
     * @return string
     */
    function redisSessionSavePath(array $settings): string
    {
        $host = trim((string)($settings['host'] ?? ''));
        if ($host === '') {
            $host = '127.0.0.1';
        }

        if (str_starts_with($host, '/') || str_starts_with($host, 'unix://')) {
            $target = str_starts_with($host, '/') ? 'unix://' . $host : $host;
        } else {
            if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
                $host = '[' . $host . ']';
            }
            $port = (int)($settings['port'] ?? 0) ?: 6379;
            $target = (str_contains($host, '://') ? $host : 'tcp://' . $host) . ':' . $port;
        }

        $query = [
            'timeout' => (float)($settings['timeout'] ?? 0) ?: 60,
            'database' => (int)($settings['database'] ?? 0),
        ];
        $user = (string)($settings['user'] ?? '');
        $password = (string)($settings['password'] ?? '');
        if ($password !== '') {
            $query['auth'] = $user !== '' ? [$user, $password] : $password;
        }

        return $target . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}

if (!function_exists('removeInvalidCmsSessionFromStorage')) {
    /**
     * @param $storage
     * @param $session_name
     * @return void
     */
    function removeInvalidCmsSessionFromStorage(&$storage, $session_name)
    {
        if (isset($storage[$session_name]) && ($storage[$session_name] === '' || $storage[$session_name] === 'deleted')) {
            unset($storage[$session_name]);
        }
    }
}

if (!function_exists('removeInvalidCmsSessionIds')) {
    /**
     * @param $session_name
     * @return void
     */
    function removeInvalidCmsSessionIds($session_name)
    {
        if (is_cli()) {
            return;
        }
        // session ids is invalid iff it is empty string
        // storage priorioty can see in PHP source ext/session/session.c
        removeInvalidCmsSessionFromStorage($_COOKIE, $session_name);
        removeInvalidCmsSessionFromStorage($_GET, $session_name);
        removeInvalidCmsSessionFromStorage($_POST, $session_name);
    }
}

if (!function_exists('modx_sanitize_gpc')) {
    /**
     * @param array|string $values
     * @param int $depth
     * @return array|string
     */
    function modx_sanitize_gpc(&$values, $depth = 0)
    {
        if (200 < $depth) {
            exit('GPC Array nested too deep!');
        }

        if (!is_array($values)) {
            return getSanitizedValue($values);
        }

        $depth++;
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                // Recurse into the element itself: $value is a copy, so `?a[b]=[[x]]` used to keep its tags
                modx_sanitize_gpc($values[$key], $depth);
            } else {
                $values[$key] = getSanitizedValue($value);
            }
        }

        return $values;
    }
}

if (!function_exists('getSanitizedValue')) {
    /**
     * @param string $value
     * @return string
     */
    function getSanitizedValue($value = '')
    {
        if (empty($value)) {
            return $value;
        }

        $brackets = explode(' ', '[[ ]] [! !] [* *] [( )] {{ }} [+ +] [~ ~] [^ ^]');
        foreach ($brackets as $bracket) {
            if (strpos($value, $bracket) === false) {
                continue;
            }
            $sanitizedBracket = str_replace(
                '#',
                EVO_SANITIZE_SEED,
                sprintf('#%s#%s#', substr($bracket, 0, 1), substr($bracket, 1, 1))
            );
            $value = str_replace($bracket, $sanitizedBracket, $value);
        }
        $value = str_ireplace('<script', 'sanitized_by_modx<s cript', $value);
        $value = preg_replace('/&#(\d+);/', 'sanitized_by_modx& #$1', $value);

        return $value;
    }
}
if (!function_exists('removeSanitizeSeed')) {
    /**
     * @param string $string
     * @return string
     */
    function removeSanitizeSeed($string = '')
    {
        if (!$string || strpos($string, EVO_SANITIZE_SEED) === false) {
            return $string;
        }

        return str_replace(EVO_SANITIZE_SEED, '', $string);
    }
}
