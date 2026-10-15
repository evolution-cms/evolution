<?php declare(strict_types=1);

namespace EvolutionCMS\Support;

/**
 * Picks the client address recorded for a manager session or log entry.
 *
 * `Client-IP` and `X-Forwarded-For` are request headers: anyone can send them with any content.
 * They are still consulted so sites behind a proxy keep showing the visitor's address, but a value
 * is only taken when it is a well-formed IP address. Anything else falls through to the next
 * source, so what is stored (and later printed on the dashboard and in the manager log) can only
 * ever be digits, hex letters, dots and colons.
 *
 * @since 3.5.8
 */
final class ClientIp
{
    public const UNKNOWN = 'UNKNOWN';

    /**
     * @param array<string, mixed> $server A `$_SERVER`-shaped array.
     * @param string[] $headers Forwarding headers to consult, in order of preference, before REMOTE_ADDR.
     */
    public static function fromServer(
        array $server,
        array $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR']
    ): string {
        foreach ($headers as $header) {
            $value = $server[$header] ?? null;
            if (!is_string($value) || $value === '') {
                continue;
            }
            // X-Forwarded-For is "client, proxy1, proxy2": the first entry is the client
            $candidate = trim(explode(',', $value, 2)[0]);
            if (self::isValid($candidate)) {
                return $candidate;
            }
        }

        $remote = $server['REMOTE_ADDR'] ?? null;
        if (is_string($remote) && self::isValid($remote)) {
            return $remote;
        }

        return self::UNKNOWN;
    }

    public static function isValid(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
}
