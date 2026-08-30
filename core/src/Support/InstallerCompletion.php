<?php

namespace EvolutionCMS\Support;

final class InstallerCompletion
{
    public static function writeLock(
        string $lockFile,
        string $sessionId,
        string $ip,
        int $timestamp,
        string $token
    ): bool {
        if (preg_match('/\A[0-9a-f]{64}\z/D', $token) !== 1) {
            return false;
        }

        $content = "<?php\n\$install_session = " . var_export($sessionId, true) . ";\n"
            . "\$install_ip = " . var_export($ip, true) . ";\n"
            . "\$install_timestamp = $timestamp;\n"
            . "\$install_token = '$token';\n";

        return file_put_contents($lockFile, $content, LOCK_EX) !== false;
    }

    /**
     * @return array{ip: string, timestamp: int, token: string}|null
     */
    public static function readLock(string $lockFile): ?array
    {
        if (!is_file($lockFile)) {
            return null;
        }

        $install_ip = $install_token = null;
        $install_timestamp = 0;

        include $lockFile;

        if (
            !is_string($install_ip)
            || !is_string($install_token)
            || preg_match('/\A[0-9a-f]{64}\z/D', $install_token) !== 1
        ) {
            return null;
        }

        return [
            'ip' => $install_ip,
            'timestamp' => (int) $install_timestamp,
            'token' => $install_token,
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

        if ($maxLifetime <= 0) {
            $maxLifetime = 1440;
        }

        return $lock['timestamp'] > 0
            && $now <= $lock['timestamp'] + $maxLifetime
            && $ip === $lock['ip']
            && hash_equals($lock['token'], $token);
    }
}
