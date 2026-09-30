<?php

use EvolutionCMS\Services\Store\StoreContextService;

$storeModule = dirname(__DIR__, 4) . '/assets/modules/store';

function storeLanguageCodes(string $dir): array
{
    $codes = array_map(static fn ($file) => basename($file, '.php'), glob($dir . '/*.php'));
    sort($codes);

    return $codes;
}

test('the store has a language file for every manager language', function () use ($storeModule) {
    $managerLanguages = array_map('basename', glob(dirname(__DIR__, 3) . '/lang/*', GLOB_ONLYDIR));
    sort($managerLanguages);

    expect(storeLanguageCodes($storeModule . '/lang'))->toBe($managerLanguages);
});

function storeLanguageStrings(string $file): array
{
    $_Lang = [];
    include $file;

    return $_Lang;
}

test('every store language translates the composer archive messages', function (string $key) use ($storeModule) {
    $english = storeLanguageStrings($storeModule . '/lang/en.php')[$key];

    foreach (storeLanguageCodes($storeModule . '/lang') as $code) {
        $strings = storeLanguageStrings($storeModule . '/lang/' . $code . '.php');

        expect(isset($strings[$key]))->toBeTrue($code . ' is missing ' . $key);
        expect(substr_count($strings[$key], '%'))
            ->toBe(substr_count($english, '%'), $code . ' ' . $key . ' has other placeholders than en');
    }
})->with([
    'install_file_artifact_queued',
    'install_file_artifact_no_version',
    'install_file_artifact_failed',
    'install_file_artifact_invalid',
]);

test('a key missing from a translation falls back to english', function () use ($storeModule) {
    $english = (new StoreContextService())->loadLanguage($storeModule, 'en');
    $slovak = (new StoreContextService())->loadLanguage($storeModule, 'sk');
    $german = (new StoreContextService())->loadLanguage($storeModule, 'de');

    expect(array_diff_key($english, $slovak))->toBe([])
        ->and(array_diff_key($english, $german))->toBe([])
        ->and($slovak['install_file'])->toBe('Inštalácia z archívu')
        ->and($german['install_file'])->toBe($english['install_file'])
        ->and($german['install_file_artifact_failed'])->toStartWith('Das Composer-Paket');
});

test('an unknown manager language loads english', function () use ($storeModule) {
    expect((new StoreContextService())->loadLanguage($storeModule, 'xx'))
        ->toBe((new StoreContextService())->loadLanguage($storeModule, 'en'));
});
