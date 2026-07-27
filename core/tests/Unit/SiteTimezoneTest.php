<?php

use EvolutionCMS\Support\SiteTimezone;

test('it lists selectable php timezone identifiers', function () {
    expect(SiteTimezone::identifiers())
        ->toContain('UTC')
        ->toContain('Europe/Kyiv')
        ->not->toContain('Europe/Kiev');
});

test('it resolves invalid site timezone to the server timezone', function () {
    expect(SiteTimezone::resolve('', 'Europe/Kyiv'))->toBe('Europe/Kyiv');
    expect(SiteTimezone::resolve('Europe/Kiev', 'Europe/Kyiv'))->toBe('Europe/Kyiv');
    expect(SiteTimezone::resolve('Invalid/Timezone', 'UTC'))->toBe('UTC');
});

test('it applies the resolved timezone without breaking bootstrap', function () {
    $previousTimezone = date_default_timezone_get();

    try {
        expect(SiteTimezone::apply('Europe/Kyiv'))->toBe('Europe/Kyiv');
        expect(date_default_timezone_get())->toBe('Europe/Kyiv');

        expect(SiteTimezone::apply('Invalid/Timezone', 'UTC'))->toBe('UTC');
        expect(date_default_timezone_get())->toBe('UTC');
    } finally {
        date_default_timezone_set($previousTimezone);
    }
});
