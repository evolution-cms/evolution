<?php

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;

function niceEtaWithLocale(float $seconds, string $locale): string
{
    $previousContainer = Container::getInstance();
    $container = new Container();
    $loader = new FileLoader(new Filesystem(), dirname(__DIR__, 2) . '/lang');
    $translator = new Translator($loader, $locale);
    $translator->setFallback('en');
    $container->instance('translator', $translator);
    Container::setInstance($container);

    try {
        return niceEta($seconds);
    } finally {
        Container::setInstance($previousContainer);
    }
}

test('niceEta keeps compact second minute and hour formats', function () {
    expect(niceEtaWithLocale(45.5, 'en'))->toBe('46s')
        ->and(niceEtaWithLocale(150, 'en'))->toBe('2m 30s')
        ->and(niceEtaWithLocale(3600, 'en'))->toBe('1h 0m')
        ->and(niceEtaWithLocale(8100, 'en'))->toBe('2h 15m');
});

test('niceEta decomposes long durations into days hours and minutes', function () {
    expect(niceEtaWithLocale(86400, 'en'))->toBe('1d 0h 0m')
        ->and(niceEtaWithLocale(813360, 'en'))->toBe('9d 9h 56m');
});

test('niceEta translates unit abbreviations using the current locale', function () {
    expect(niceEtaWithLocale(813360, 'uk'))->toBe('9д 9г 56хв')
        ->and(niceEtaWithLocale(813360, 'ru'))->toBe('9д 9ч 56мин');
});

test('niceEta unit translations are synchronized across manager locales', function () {
    $langRoot = dirname(__DIR__, 2) . '/lang';

    foreach (glob($langRoot . '/*/global.php') as $file) {
        $_lang = [];
        include $file;

        expect($_lang)->toHaveKeys([
            'time_unit_day_short',
            'time_unit_hour_short',
            'time_unit_minute_short',
            'time_unit_second_short',
        ]);
    }
});
