<?php

class EvoSessionProxy
{
    /**
     * @var bool
     */
    private static bool $initialized = false;

    /**
     * @var bool
     */
    private static bool $synced = false;

    /**
     * @var bool
     */
    private static bool $shutdownRegistered = false;

    /**
     * @var bool  Tracks whether saveAndEmitCookie() already ran, so the shutdown handler does not attempt a second save/emit.
     */
    private static bool $cookieEmitted = false;


    /**
     * Early init - before Laravel middleware.
     * Ensure $_SESSION is an array (do NOT overwrite if already initialized).
     */
    public static function earlyInit(): void
    {
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        $createdKey = 'evo.session.created.time';
        if (!isset($_SESSION[$createdKey])) {
            $_SESSION[$createdKey] = $_SERVER['REQUEST_TIME'] ?? time();
        }
    }

    /**
     * Init - after Laravel StartSession middleware.
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        $store = self::getLaravelSessionStore();
        if ($store === null) {
            return;
        }

        self::ensureLaravelSessionStarted($store);
        self::migrateLegacySessionIfNeeded($store);

        // Start PHP session with cookies disabled (Laravel owns the cookie).
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_cookies', '0');
            if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 80400) {
                ini_set('session.use_only_cookies', '0');
            }
            $sessionId = $store->getId();
            if (is_string($sessionId) && $sessionId !== '') {
                session_id($sessionId);
            }
            @session_start();
        }

        $earlyData = (isset($_SESSION) && is_array($_SESSION)) ? $_SESSION : [];
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        // Laravel → $_SESSION (Laravel wins on conflicts).
        $laravelData = $store->all();
        foreach ($laravelData as $key => $value) {
            $_SESSION[$key] = $value;
        }

        // Merge back early data for keys not present in Laravel.
        foreach ($earlyData as $key => $value) {
            if (!array_key_exists($key, $laravelData)) {
                $_SESSION[$key] = $value;
                $store->put($key, $value);
            }
        }

        self::$initialized = true;

        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function([self::class, 'syncBack']);
        }
    }

    /**
     * Sync back - before response.
     */
    public static function syncBack(): void
    {
        if (!self::$initialized || self::$synced) {
            return;
        }

        self::$synced = true;

        $store = self::getLaravelSessionStore();
        if ($store === null) {
            return;
        }

        $laravelData = $store->all();

        foreach ($_SESSION as $key => $value) {
            if (self::isInternalKey($key)) {
                continue;
            }
            if (!array_key_exists($key, $laravelData) || $laravelData[$key] !== $value) {
                $store->put($key, $value);
            }
        }

        foreach ($laravelData as $key => $value) {
            if (self::isInternalKey($key)) {
                continue;
            }
            if (!array_key_exists($key, $_SESSION)) {
                $store->forget($key);
            }
        }

        $store->save();
    }

    /**
     * @return object|null
     */
    private static function getLaravelSessionStore()
    {
        if (!function_exists('app')) {
            return null;
        }

        $app = app();
        if (!is_object($app) || !method_exists($app, 'has') || !$app->has('session')) {
            return null;
        }

        try {
            $manager = app('session');
        } catch (\Throwable $exception) {
            return null;
        }

        if (is_object($manager) && method_exists($manager, 'driver')) {
            $store = $manager->driver();
        } else {
            $store = $manager;
        }

        if (!is_object($store) || !method_exists($store, 'all') || !method_exists($store, 'getId')) {
            return null;
        }

        return $store;
    }

    /**
     * @param object $store
     * @return void
     */
    private static function ensureLaravelSessionStarted($store): void
    {
        if (method_exists($store, 'isStarted') && $store->isStarted()) {
            return;
        }

        $cookieName = self::getLaravelSessionCookieName();
        $cookieId = self::getCookieValue($cookieName);
        if (is_string($cookieId) && $cookieId !== '') {
            if (method_exists($store, 'setId')) {
                $store->setId($cookieId);
            }
        }

        if (method_exists($store, 'start')) {
            $store->start();
        }
    }

    /**
     * @param object $store
     * @return void
     */
    private static function migrateLegacySessionIfNeeded($store): void
    {
        $laravelCookie = self::getLaravelSessionCookieName();
        if (!empty($_COOKIE[$laravelCookie])) {
            return;
        }

        $legacyCookie = defined('SESSION_COOKIE_NAME') ? SESSION_COOKIE_NAME : 'EVOSESSID';
        $legacyId = self::getCookieValue($legacyCookie);
        if (!is_string($legacyId) || $legacyId === '') {
            return;
        }

        $payload = self::readLegacySessionPayload($legacyId);
        if ($payload === null || $payload === '') {
            return;
        }

        $legacyData = self::decodeSessionPayload($payload);
        if (!is_array($legacyData) || $legacyData === []) {
            return;
        }

        $existing = $store->all();
        foreach ($legacyData as $key => $value) {
            if (!array_key_exists($key, $existing)) {
                $store->put($key, $value);
            }
        }
        $store->save();

        // Expire legacy cookie after successful migration.
        setcookie($legacyCookie, '', time() - 3600, '/');
        unset($_COOKIE[$legacyCookie]);
    }

    /**
     * @param string $sessionId
     * @return string|null
     */
    private static function readLegacySessionPayload(string $sessionId): ?string
    {
        $savePath = session_save_path();
        if (!is_string($savePath) || $savePath === '') {
            $savePath = sys_get_temp_dir();
        }

        $parts = explode(';', $savePath);
        $path = end($parts);
        if (!is_string($path) || $path === '') {
            return null;
        }

        $file = rtrim($path, "/\\") . DIRECTORY_SEPARATOR . 'sess_' . $sessionId;
        if (!is_readable($file)) {
            return null;
        }

        $payload = file_get_contents($file);
        return ($payload === false) ? null : $payload;
    }

    /**
     * @param string $payload
     * @return array
     */
    private static function decodeSessionPayload(string $payload): array
    {
        $backup = $_SESSION ?? null;
        $_SESSION = [];
        $ok = @session_decode($payload);
        $decoded = ($ok === false) ? [] : $_SESSION;

        if ($backup === null) {
            unset($_SESSION);
        } else {
            $_SESSION = $backup;
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Sync $_SESSION to the Laravel session store and emit the session cookie header.
     *
     * Must be called BEFORE any header() / exit() that bypasses the middleware stack
     * (e.g. sendRedirect on a fresh session where the browser has no evo_session cookie yet).
     */
    public static function saveAndEmitCookie(): void {
        if (!self::$initialized) {
            return;
        }

        // Sync data first (marks self::$synced = true so shutdown won't double-save).
        if (!self::$cookieEmitted) {
            self::syncBack();
        }

        $store = self::getLaravelSessionStore();
        if ($store === null) {
            return;
        }

        $cookieName = self::getLaravelSessionCookieName();
        $sessionId  = $store->getId();

        if (!$cookieName || !$sessionId || headers_sent()) {
            return;
        }

        // Only emit when the browser does not already carry this exact session id,
        // or when it carries a different one (session was regenerated).
        if (isset($_COOKIE[$cookieName]) && $_COOKIE[$cookieName] === $sessionId) {
            return;
        }

        $config   = function_exists('config') ? (array) config('session', []) : [];
        $lifetime = isset($config['lifetime']) ? (int) $config['lifetime'] * 60 : 0;

        $secure = (bool) ($config['secure'] ?? (
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        ));

        setcookie(
            $cookieName,
            $sessionId,
            [
                'expires'  => $lifetime ? time() + $lifetime : 0,
                'path'     => $config['path']      ?? '/',
                'domain'   => $config['domain']    ?? '',
                'secure'   => $secure,
                'httponly' => (bool) ($config['http_only'] ?? true),
                'samesite' => $config['same_site'] ?? 'Lax',
            ]
        );

        self::$cookieEmitted = true;
    }

    /**
     * @return string
     */
    private static function getLaravelSessionCookieName(): string
    {
        if (function_exists('config')) {
            return (string)config('session.cookie', 'evo_session');
        }
        return 'evo_session';
    }

    /**
     * @param string $name
     * @return string|null
     */
    private static function getCookieValue(string $name): ?string
    {
        if (!isset($_COOKIE[$name])) {
            return null;
        }
        $value = $_COOKIE[$name];
        return is_string($value) ? $value : null;
    }

    /**
     * @param string $key
     * @return bool
     */
    private static function isInternalKey(string $key): bool
    {
        return str_starts_with($key, '_');
    }
}
