<?php

namespace EvolutionCMS\Support;

/**
 * The installer lock (`install.session.php` in the site root) and who may run the installer.
 *
 * Every change to the lock is a read-decide-write under an exclusive flock() of the lock file
 * itself, so concurrent requests are serialized and cannot both take it.
 */
final class InstallerCompletion
{
    /** The cookie that carries an administrator's installer grant; the lock stores its hash only. */
    public const GRANT_COOKIE = 'evo_installer_grant';

    /** The installer runs on with the lock it holds. */
    public const PROCEED = 'proceed';
    /** The installer takes the lock over for its own session. */
    public const CLAIM = 'claim';
    /** The installer refuses the request. */
    public const DENY = 'deny';

    /**
     * Record a successful installation: the one-time token that authorizes removing the installer.
     * An administrator's grant held by the lock is kept.
     */
    public static function writeLock(
        string $lockFile,
        string $sessionId,
        string $ip,
        int $timestamp,
        string $token
    ): bool {
        if (!self::isHex64($token)) {
            return false;
        }

        return self::update($lockFile, static function (?array $lock) use ($sessionId, $ip, $timestamp, $token) {
            return [true, [
                'session' => $sessionId,
                'ip' => $ip,
                'timestamp' => $timestamp,
                'token' => $token,
                'grant' => $lock['grant'] ?? null,
            ]];
        }) === true;
    }

    /**
     * @return array{ip: string, timestamp: int, token: string}|null
     */
    public static function readLock(string $lockFile): ?array
    {
        $lock = self::readSessionLock($lockFile);
        if ($lock === null || $lock['token'] === null) {
            return null;
        }

        return [
            'ip' => $lock['ip'],
            'timestamp' => $lock['timestamp'],
            'token' => $lock['token'],
        ];
    }

    /**
     * @param array{ip: string, timestamp: int, token: string}|null $lock
     */
    public static function matches(
        ?array $lock,
        string $token,
        string $ip,
        int $now,
        int $maxLifetime
    ): bool {
        if ($lock === null || $token === '') {
            return false;
        }

        return self::isLive($lock, $now, $maxLifetime)
            && $ip === $lock['ip']
            && hash_equals($lock['token'], $token);
    }

    /**
     * Whether the site has been installed: the marker the front controller checks too.
     */
    public static function isInstalled(string $corePath): bool
    {
        return is_file(rtrim($corePath, '/\\') . '/.install');
    }

    /**
     * Read the installer lock, whatever stage wrote it.
     *
     * @return array{session: string, ip: string, timestamp: int, token: ?string, grant: ?string}|null
     */
    public static function readSessionLock(string $lockFile): ?array
    {
        if (!is_file($lockFile)) {
            return null;
        }

        $handle = @fopen($lockFile, 'r');
        if ($handle === false) {
            return null;
        }

        try {
            if (!flock($handle, LOCK_SH)) {
                return null;
            }
            $content = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        return self::parse(is_string($content) ? $content : '');
    }

    /**
     * Decide whether an installer request may run.
     *
     * A live lock that carries an administrator's grant admits only the browser holding the grant
     * cookie (see grantToAdministrator()). A live lock without one admits the session (from the
     * same address) that took it. Before installation the first visitor takes the installer, as it
     * always has; once the site is installed nobody else gets in, so a leftover install folder, or
     * one uploaded for a manual upgrade, neither opens the site up nor gets in the way of serving it.
     *
     * @param array{session: string, ip: string, timestamp: int, token: ?string, grant: ?string}|null $lock
     */
    public static function decide(
        ?array $lock,
        bool $installed,
        string $sessionId,
        string $ip,
        ?string $grantSecret,
        int $now,
        int $maxLifetime
    ): string {
        $live = $lock !== null && self::isLive($lock, $now, $maxLifetime);

        if ($live && $lock['grant'] !== null) {
            return self::grantMatches($lock['grant'], $grantSecret) ? self::PROCEED : self::DENY;
        }

        if (
            $live
            && $sessionId !== ''
            && $lock['session'] !== ''
            && $ip === $lock['ip']
            && hash_equals($lock['session'], $sessionId)
        ) {
            return self::PROCEED;
        }

        if ($installed) {
            return self::DENY;
        }

        if (!$live) {
            return self::CLAIM;
        }

        // Local development desyncs installer cookies easily: on loopback the current browser
        // may take over a loopback lock instead of failing with an opaque 404.
        $loopback = ['127.0.0.1', '::1'];

        return in_array($ip, $loopback, true) && in_array($lock['ip'], $loopback, true)
            ? self::CLAIM
            : self::DENY;
    }

    /**
     * Admit an installer request: decide and record it in one locked step.
     *
     * Returns PROCEED or CLAIM when the request may run (the lock now belongs to its session),
     * DENY when it may not, and null when the lock could not be written - the request must not
     * run then either, since nothing would keep another browser out.
     */
    public static function admit(
        string $lockFile,
        bool $installed,
        string $sessionId,
        string $ip,
        ?string $grantSecret,
        int $now,
        int $maxLifetime
    ): ?string {
        // nothing to read, and no reason to leave an empty lock file behind a refused guest
        if ($installed && !is_file($lockFile)) {
            return self::DENY;
        }

        $decision = self::update(
            $lockFile,
            static function (?array $lock) use ($installed, $sessionId, $ip, $grantSecret, $now, $maxLifetime) {
                $decision = self::decide($lock, $installed, $sessionId, $ip, $grantSecret, $now, $maxLifetime);
                if ($decision === self::DENY) {
                    return [$decision, null];
                }

                $kept = $decision === self::PROCEED;

                return [$decision, [
                    'session' => $sessionId,
                    'ip' => $ip,
                    'timestamp' => $now,
                    // a completion token stays with the session that completed the installation
                    'token' => $kept && $lock['session'] === $sessionId ? $lock['token'] : null,
                    'grant' => $kept ? $lock['grant'] : null,
                ]];
            }
        );

        return is_string($decision) ? $decision : null;
    }

    /**
     * Grant the installer to a logged-in administrator's browser, or keep that grant alive.
     *
     * Returns the grant secret the browser must hold as the GRANT_COOKIE cookie, or null when
     * nothing was granted. A live grant whose secret the browser already holds is renewed; any
     * other live lock - another installer session at work - is left alone; an expired or missing
     * lock is replaced by a fresh grant, so an abandoned session is never revived. Renewals are
     * throttled, since this runs on every administrator request.
     */
    public static function grantToAdministrator(
        string $lockFile,
        string $ip,
        ?string $grantSecret,
        int $now,
        int $maxLifetime,
        int $throttle = 60
    ): ?string {
        $secret = self::update(
            $lockFile,
            static function (?array $lock) use ($ip, $grantSecret, $now, $maxLifetime, $throttle) {
                $live = $lock !== null && self::isLive($lock, $now, $maxLifetime);

                if ($live && $lock['grant'] !== null && self::grantMatches($lock['grant'], $grantSecret)) {
                    if ($now - $lock['timestamp'] < $throttle) {
                        return [$grantSecret, null];
                    }

                    return [$grantSecret, ['timestamp' => $now] + $lock];
                }

                if ($live) {
                    return [null, null];
                }

                $secret = bin2hex(random_bytes(32));

                return [$secret, [
                    'session' => '',
                    'ip' => $ip,
                    'timestamp' => $now,
                    'token' => null,
                    'grant' => hash('sha256', $secret),
                ]];
            }
        );

        return is_string($secret) ? $secret : null;
    }

    /**
     * The paths the grant cookie is set on: the installer, which checks it, and the Manager,
     * which renews it - a cookie scoped to one is not sent to the other.
     *
     * @return list<string>
     */
    public static function grantCookiePaths(string $baseUrl, string $managerDir): array
    {
        $baseUrl = '/' . trim($baseUrl, '/');
        $baseUrl = $baseUrl === '/' ? '/' : $baseUrl . '/';

        return [$baseUrl . 'install/', $baseUrl . trim($managerDir, '/') . '/'];
    }

    /**
     * Run a read-decide-write of the lock under an exclusive lock of the file.
     *
     * The callback receives the current lock (or null) and returns [result, new lock or null to
     * leave it as it is]. Returns the result, or false when the file could not be locked or written.
     *
     * @param callable(?array): array{0: mixed, 1: ?array} $change
     */
    private static function update(string $lockFile, callable $change): mixed
    {
        $handle = is_dir(dirname($lockFile)) ? @fopen($lockFile, 'c+') : false;
        if ($handle === false) {
            return false;
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                return false;
            }

            $content = stream_get_contents($handle);
            [$result, $lock] = $change(self::parse(is_string($content) ? $content : ''));

            if ($lock !== null) {
                $data = self::format($lock);
                if (
                    !ftruncate($handle, 0)
                    || !rewind($handle)
                    || fwrite($handle, $data) !== strlen($data)
                    || !fflush($handle)
                ) {
                    return false;
                }
            }

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @param array{session: string, ip: string, timestamp: int, token: ?string, grant: ?string} $lock
     */
    private static function format(array $lock): string
    {
        $content = "<?php\n\$install_session = " . var_export($lock['session'], true) . ";\n"
            . "\$install_ip = " . var_export($lock['ip'], true) . ";\n"
            . "\$install_timestamp = " . (int) $lock['timestamp'] . ";\n";

        foreach (['token', 'grant'] as $key) {
            if (self::isHex64($lock[$key] ?? null)) {
                $content .= "\$install_$key = '{$lock[$key]}';\n";
            }
        }

        return $content;
    }

    /**
     * Read the `$install_*` assignments of a lock file without executing it.
     *
     * The lock is rewritten within the same second by competing requests, and an included
     * file is served from OPcache until `opcache.revalidate_freq` passes: an include would let
     * a second browser see a lock another one has already taken.
     *
     * @return array{session: string, ip: string, timestamp: int, token: ?string, grant: ?string}|null
     */
    private static function parse(string $content): ?array
    {
        // var_export() and the addslashes() of older lock files escape quotes and backslashes alike
        preg_match_all(
            "/^\\\$install_([a-z]+) = (?:'((?:[^'\\\\]|\\\\.)*)'|(\\d+));\$/m",
            $content,
            $matches,
            PREG_SET_ORDER
        );

        $values = [];
        foreach ($matches as $match) {
            $values[$match[1]] = isset($match[3]) && $match[3] !== ''
                ? (int) $match[3]
                : preg_replace('/\\\\(.)/s', '$1', $match[2]);
        }

        if (!is_string($values['session'] ?? null) || !is_string($values['ip'] ?? null)) {
            return null;
        }

        return [
            'session' => $values['session'],
            'ip' => $values['ip'],
            'timestamp' => (int) ($values['timestamp'] ?? 0),
            'token' => self::isHex64($values['token'] ?? null) ? $values['token'] : null,
            'grant' => self::isHex64($values['grant'] ?? null) ? $values['grant'] : null,
        ];
    }

    private static function isLive(array $lock, int $now, int $maxLifetime): bool
    {
        if ($maxLifetime <= 0) {
            $maxLifetime = 1440;
        }

        return $lock['timestamp'] > 0 && $now <= $lock['timestamp'] + $maxLifetime;
    }

    private static function grantMatches(string $grant, ?string $secret): bool
    {
        return self::isHex64($secret) && hash_equals($grant, hash('sha256', $secret));
    }

    private static function isHex64(mixed $value): bool
    {
        return is_string($value) && preg_match('/\A[0-9a-f]{64}\z/D', $value) === 1;
    }
}
