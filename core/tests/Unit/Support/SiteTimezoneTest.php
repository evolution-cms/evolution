<?php

use EvolutionCMS\Support\SiteTimezone;

it('accepts IANA timezone identifiers and rejects arbitrary values', function () {
    expect(SiteTimezone::isValid('Europe/Kyiv'))
        ->toBeTrue()
        ->and(SiteTimezone::isValid('UTC'))
        ->toBeTrue()
        ->and(SiteTimezone::isValid('+03:00'))
        ->toBeFalse()
        ->and(SiteTimezone::isValid('not/a-timezone'))
        ->toBeFalse();
});

it('falls back to the configured PHP server timezone for missing or invalid values', function () {
    expect(SiteTimezone::resolve(null, 'Europe/Kyiv'))
        ->toBe('Europe/Kyiv')
        ->and(SiteTimezone::resolve('not/a-timezone', 'Europe/Kyiv'))
        ->toBe('Europe/Kyiv')
        ->and(SiteTimezone::resolve('America/Toronto', 'Europe/Kyiv'))
        ->toBe('America/Toronto')
        ->and(SiteTimezone::resolve(null, 'not/a-timezone'))
        ->toBe('UTC');
});

it('applies the resolved timezone without leaving global test state changed', function () {
    $original = date_default_timezone_get();

    try {
        expect(SiteTimezone::apply('Europe/Kyiv', 'UTC'))
            ->toBe('Europe/Kyiv')
            ->and(date_default_timezone_get())
            ->toBe('Europe/Kyiv');
    } finally {
        date_default_timezone_set($original);
    }
});
