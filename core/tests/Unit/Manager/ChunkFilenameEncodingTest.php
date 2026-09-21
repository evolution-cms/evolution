<?php

use EvolutionCMS\Support\ChunkFileStore;

/**
 * A chunk name is prose, and a filename is not. Rather than refuse every name
 * it cannot keep verbatim - which was most of them - the store encodes the
 * hazards and decodes them back, so moving a chunk into a file never renames
 * the chunk.
 *
 * Reversibility and uniqueness are different properties, and only the first is
 * the encoding's job. The second one has its own tests at the bottom.
 */

beforeAll(function () {
    if (!defined('EVO_BASE_PATH')) {
        define('EVO_BASE_PATH', str_replace(chr(92), '/', dirname(__DIR__, 3)) . '/');
    }
});

function encodingStore(): ChunkFileStore
{
    return new ChunkFileStore(['html' => 'HTML'], EVO_BASE_PATH . 'views/chunks/');
}

dataset('chunk names', [
    'a plain name'            => ['nav.main'],
    'mixed case and dashes'   => ['Header_2-col'],
    'a space'                 => ['my Chunk'],
    'a slash'                 => ['nav/main'],
    'a backslash'             => ['back' . chr(92) . 'slash'],
    'a colon'                 => ['a:b'],
    'a quote'                 => ['quote"name'],
    'a star'                  => ['star*name'],
    'a pipe'                  => ['pipe|name'],
    'a question mark'         => ['question?'],
    'angle brackets'          => ['less<greater>'],
    'a tab'                   => ["tab\tname"],
    'dot dot'                 => ['..'],
    'a single dot'            => ['.'],
    'a leading dot'           => ['.hidden'],
    'a trailing dot'          => ['trailing.'],
    'a trailing space'        => ['trailing '],
    'a reserved device name'  => ['CON'],
    'a lowercase device name' => ['nul'],
    'a numbered device name'  => ['COM1'],
    'a percent sign'          => ['100%'],
    'something that looks encoded' => ['%2E'],
    'cyrillic'                => ['Кнопка'],
    'an em dash'              => ['nav—dash'],
]);

it('round trips every name a chunk has ever had', function (string $name) {
    $store = encodingStore();
    $filename = $store->filename($name, 'html');

    expect($filename)->not->toBeNull()
        ->and($store->nameFor($filename, 'html'))->toBe($name);
})->with('chunk names');

it('produces a filename every platform accepts', function (string $name) {
    $encoded = substr((string) encodingStore()->filename($name, 'html'), 0, -strlen('.html'));

    expect($encoded)
        // Windows' forbidden set, and the control range with it.
        ->not->toMatch('/[\x00-\x1F\x7F<>:"\/\\\\|?*]/')
        // Windows drops these silently, so the file would stop matching the name.
        ->not->toEndWith('.')
        ->not->toEndWith(' ')
        // Hidden on unix, and '.'/'..' are not names at all.
        ->not->toStartWith('.')
        ->and(in_array(strtoupper($encoded), ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'LPT1'], true))
        ->toBeFalse();
})->with('chunk names');

it('leaves a name that was already safe exactly as it is', function () {
    // The common case, and the whole reason for encoding rather than hashing:
    // a chunk's file is findable by its name in a directory listing.
    $store = encodingStore();

    expect($store->filename('nav.main', 'html'))->toBe('nav.main.html')
        ->and($store->filename('Header_2-col', 'html'))->toBe('Header_2-col.html')
        ->and($store->filename('Кнопка', 'html'))->toBe('Кнопка.html');
});

it('encodes the escape character first of all', function () {
    // Otherwise "100%" and "100%25" would be the same file, and neither would
    // decode back to itself.
    $store = encodingStore();

    expect($store->filename('100%', 'html'))->toBe('100%25.html')
        ->and($store->filename('%2E', 'html'))->toBe('%252E.html')
        ->and($store->nameFor('100%25.html', 'html'))->toBe('100%')
        ->and($store->nameFor('%252E.html', 'html'))->toBe('%2E');
});

it('refuses to read a filename it would not have written', function () {
    $store = encodingStore();

    expect($store->nameFor('nav%2.html', 'html'))->toBeNull()
        ->and($store->nameFor('nav%zz.html', 'html'))->toBeNull()
        // Lower case hex decodes to the same byte, but is not what encode()
        // emits - accepting it would give one chunk two files.
        ->and($store->nameFor('nav%2fmain.html', 'html'))->toBeNull()
        ->and($store->nameFor('nav%2Fmain.html', 'html'))->toBe('nav/main')
        ->and($store->nameFor('nav.main.txt', 'html'))->toBeNull()
        ->and($store->nameFor('.html', 'html'))->toBeNull();
});

it('refuses a name no encoding can rescue, and says why', function () {
    $store = encodingStore();

    expect($store->refuseReason('nav.main', 'html'))->toBeNull()
        ->and($store->refuseReason('', 'html'))->toBe('empty')
        ->and($store->refuseReason('   ', 'html'))->toBe('empty')
        ->and($store->refuseReason("\xC3\x28", 'html'))->toBe('not_utf8')
        // 100 characters is what the column holds, and each escape costs
        // three bytes - so a name of stars alone overruns any filesystem.
        ->and($store->refuseReason(str_repeat('*', 90), 'html'))->toBe('too_long')
        ->and($store->filename(str_repeat('*', 90), 'html'))->toBeNull();
});

it('refuses a name macOS would fold into another one', function () {
    // APFS compares NFC and NFD as the same file, so two chunks differing only
    // in normal form would share it. Needs ext-intl to detect.
    if (!class_exists(Normalizer::class)) {
        expect(true)->toBeTrue();

        return;
    }

    $nfd = 'e' . "\xCC\x81" . 'clair';
    $nfc = "\xC3\xA9" . 'clair';

    expect($nfc)->not->toBe($nfd)
        ->and(encodingStore()->refuseReason($nfd, 'html'))->toBe('not_nfc')
        ->and(encodingStore()->refuseReason($nfc, 'html'))->toBeNull();
});

it('gives two names that share a file the same collision key', function () {
    // Nothing in the encoding can see this: both names are safe, both encode
    // to themselves, and both are one file on Windows and on macOS. Whoever
    // writes has to compare keys.
    $store = encodingStore();

    expect($store->caseKey('MyChunk', 'html'))->toBe($store->caseKey('mychunk', 'html'))
        ->and($store->caseKey('Кнопка', 'html'))->toBe($store->caseKey('КНОПКА', 'html'))
        ->and($store->caseKey('nav.main', 'html'))->not->toBe($store->caseKey('nav.other', 'html'))
        ->and($store->caseKey("\xC3\x28", 'html'))->toBeNull();
});
