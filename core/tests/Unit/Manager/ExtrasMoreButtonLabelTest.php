<?php

test('every Extras locale labels the package card button in its own language', function () {
    $missing = [];
    $english = [];
    foreach (glob(dirname(__DIR__, 4) . '/assets/modules/store/lang/*.php') as $file) {
        $locale = basename($file, '.php');
        $_Lang = [];
        include $file;
        if (!isset($_Lang['more']) || trim((string) $_Lang['more']) === '') {
            $missing[] = $locale;
        } elseif ($locale !== 'en' && strtolower(trim((string) $_Lang['more'])) === 'more') {
            // a missing key falls back to English, so an untranslated one looked the same
            $english[] = $locale;
        }
    }

    expect($missing)->toBe([])
        ->and($english)->toBe([]);
});
