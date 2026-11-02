<?php

if (!function_exists('evo_exit')) {
    /** End the current request; a worker catches the signal instead of exiting PHP. */
    function evo_exit(string|int $status = 0): never
    {
        if (class_exists(\EvolutionCMS\Runtime\WorkerRuntime::class, false)
            && \EvolutionCMS\Runtime\WorkerRuntime::active()) {
            if (is_string($status)) {
                echo $status;
            }
            throw new \EvolutionCMS\Runtime\RequestTerminated();
        }

        exit($status);
    }
}

if (!function_exists('evo_register_shutdown')) {
    /** Schedule request cleanup in either the classic or persistent PHP runtime. */
    function evo_register_shutdown(callable $callback): void
    {
        if (class_exists(\EvolutionCMS\Runtime\WorkerRuntime::class, false)
            && \EvolutionCMS\Runtime\WorkerRuntime::active()) {
            \EvolutionCMS\Runtime\WorkerRuntime::defer($callback);
        } else {
            register_shutdown_function($callback);
        }
    }
}

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
                evo_exit($exception->getCode());
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
            evo_exit();
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
            evo_exit();
        }

        $evo->webAlertAndQuit(
            \EvolutionCMS\Support\CsrfMessage::text(\EvolutionCMS\Support\CsrfMessage::REFERER, $language),
            '/' . MGR_DIR . '/index.php'
        );
        evo_exit();
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

        if (SESSION_STORAGE == 'redis' && class_exists('Redis')) {
            $redis = new Redis();
            if ($redis->connect(env('REDIS_HOST', '127.0.0.1'),
                    env('REDIS_PORT', 6379),
                    env('REDIS_TIMEOUT', 60),
                    NULL,
                    0,
                    0,
                    ['auth' => [env('REDIS_USER', null), env('REDIS_PASS', null)]])
                && $redis->select(env('REDIS_SESSION_DATABASE', 0))) {
                try {
                    $handler = new \suffi\RedisSessionHandler\RedisSessionHandler($redis);
                    session_set_save_handler($handler);
                } catch (RedisException $exception) {

                } catch (\Exception $exception) {

                }
            }
        }

        $sessionname = session_name();
        // safe session - see https://stackoverflow.com/a/33024310/1066234
        if (isset($_COOKIE[$sessionname]) && preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $_COOKIE[$sessionname])) {
            session_start();
        } elseif (isset($_COOKIE[$sessionname])) {
            unset($_COOKIE[$sessionname]);
            session_start();
        } else {
            session_start();
        }

        // no session started, exit
        if (session_status() === PHP_SESSION_NONE) {
            evo_exit();
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
            evo_exit('GPC Array nested too deep!');
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
