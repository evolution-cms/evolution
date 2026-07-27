<?php

namespace EvolutionCMS\Support;

use DateTimeZone;
use Throwable;

/**
 * Resolves and applies the site's IANA timezone without making bootstrap fragile.
 */
final class SiteTimezone
{
    /**
     * Return the IANA timezone identifiers accepted by the system setting.
     *
     * Backward-compatible aliases are intentionally excluded so the manager
     * offers only current PHP timezone identifiers.
     *
     * @since 3.5.8
     */
    public static function identifiers(): array
    {
        return DateTimeZone::listIdentifiers(DateTimeZone::ALL);
    }

    /**
     * Determine whether a value is a supported IANA timezone identifier.
     *
     * @since 3.5.8
     */
    public static function isValid(mixed $timezone): bool
    {
        return is_string($timezone)
            && $timezone !== ''
            && in_array($timezone, self::identifiers(), true);
    }

    /**
     * Resolve a saved timezone, falling back to PHP's configured server timezone.
     *
     * UTC is the final safety fallback for an invalid server configuration.
     *
     * @since 3.5.8
     */
    public static function resolve(mixed $timezone, ?string $serverTimezone = null): string
    {
        $serverTimezone ??= date_default_timezone_get();

        if (self::isValid($timezone)) {
            return $timezone;
        }

        return self::isValid($serverTimezone) ? $serverTimezone : 'UTC';
    }

    /**
     * Apply the resolved timezone to PHP and return the applied identifier.
     *
     * @since 3.5.8
     */
    public static function apply(mixed $timezone, ?string $serverTimezone = null): string
    {
        $resolved = self::resolve($timezone, $serverTimezone);

        try {
            if (date_default_timezone_set($resolved)) {
                return $resolved;
            }
        } catch (Throwable) {
            // Keep bootstrap operational even when PHP rejects a saved value.
        }

        date_default_timezone_set('UTC');

        return 'UTC';
    }
}
