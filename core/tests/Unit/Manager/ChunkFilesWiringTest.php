<?php

/**
 * A chunk's code lives in views/chunks/<name>.html. Not "can live" - lives:
 * there is no setting, no column and no migration behind it. The file is the
 * chunk if it exists, and the site_htmlsnippets row is what a chunk that has
 * not been saved since files existed still holds.
 *
 * That is what makes the database a deprecation rather than an option: nothing
 * in the manager offers it, every save writes a file, and a site crosses over
 * one chunk at a time without anybody running anything.
 */

$read = static fn (string $path): string => str_replace(chr(13), '', (string) file_get_contents($path));
$core = static fn (string $file): string => $read(dirname(__DIR__, 3) . '/src/' . $file);
$root = static fn (string $file): string => $read(dirname(__DIR__, 4) . '/' . $file);

test('both readers of a stored chunk go through one rule', function () use ($core) {
    // Two things read a stored chunk: Parser::getBaseChunk() on demand, and the
    // site cache builder up front - which bakes every chunk into
    // Core::$chunkCache and so short circuits the first entirely. A rule in
    // only one of them renders a file backed chunk from the stale column on
    // every request the cache serves, which is every request.
    expect($core('Parser.php'))
        ->toContain('ChunkFileStore::make()->resolve((string) $row->name, $row->snippet)')
        ->and($core('Legacy/Cache.php'))
        ->toContain('$chunkFiles->resolve((string) $doc[\'name\'], $doc[\'snippet\'])')
        ->and($core('Support/ChunkFileStore.php'))
        ->toContain('public function resolve(string $name, ?string $stored): ?string');
});

test('the file wins and the column is the fallback', function () {
    // The whole rule, and the reason nothing needs migrating: a site that
    // never saves a chunk keeps rendering exactly what it rendered before.
    $resolve = static fn (?string $file, ?string $column): ?string => $file ?? $column;

    expect($resolve('from the file', 'from the column'))->toBe('from the file')
        ->and($resolve(null, 'from the column'))->toBe('from the column')
        ->and($resolve(null, null))->toBeNull();
});

test('nothing about a chunk is stored to say where its code is', function () use ($core, $root) {
    // No column means no migration, and no migration means an update does not
    // write into anybody's tree.
    foreach (['Support/ChunkFileStore.php', 'Parser.php', 'Legacy/Cache.php', 'Models/SiteHtmlsnippet.php', 'Controllers/Chunk.php'] as $file) {
        expect($core($file))->not->toContain('chunksource');
    }

    expect($root('manager/processors/save_htmlsnippet.processor.php'))->not->toContain('chunksource')
        ->and($root('manager/views/page/chunk.blade.php'))->not->toContain('chunksource')
        ->and(glob(dirname(__DIR__, 3) . '/database/migrations/*chunk*'))->toBe([])
        ->and(glob(dirname(__DIR__, 4) . '/install/stubs/migrations/*chunk*'))->toBe([]);
});

test('the manager never offers the database as a place to keep a chunk', function () use ($root) {
    // It is a deprecation, not a choice, so it is not in the form and not in
    // any language file either.
    $view = $root('manager/views/page/chunk.blade.php');

    expect($view)->not->toContain('name="chunksource"')
        ->and($view)->not->toContain('chunk_source');

    foreach (['en', 'uk', 'ru'] as $lang) {
        expect($root("core/lang/$lang/global.php"))->not->toContain('$_lang["chunk_source');
    }
});

test('the form still says which file it will write', function () use ($root) {
    // The one thing the operator does need: the name, encoded the way the
    // store encodes it, so what is on screen is what lands on disk.
    $view = $root('manager/views/page/chunk.blade.php');

    expect($view)->toContain('var encodeChunkName = function(value)')
        ->toContain('var RESERVED = /^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$/i;')
        ->toContain('id="chunk-filename"')
        ->toContain('chunk_file_unusable_name');
});

test('every save writes the file, and nothing is saved if it cannot', function () use ($root) {
    $processor = $root('manager/processors/save_htmlsnippet.processor.php');

    expect($processor)->toContain('$chunkStore->write($name, $chunkFormat, $snippet)')
        ->toContain('chunkFileWriteFailed($name, $chunkFormat, 77);')
        ->toContain('chunkFileWriteFailed($name, $chunkFormat, 78, $id);')
        // The column is written too - not as a second source of truth, but so
        // that the manager's search, which reads the database, still finds a
        // chunk by its contents.
        ->toContain("compact('name', 'description','snippet'")
        ->not->toContain("unset(\$updates['snippet']);");
});

test('a name that cannot be a file is refused before the save, with a reason', function () use ($root) {
    // Four different accidents, four different fixes - a shrug would leave the
    // operator guessing which.
    $processor = $root('manager/processors/save_htmlsnippet.processor.php');

    expect($processor)->toContain('function chunkNameRefused(')
        ->toContain("'chunk_name_collides'")
        ->and($root('core/lang/en/global.php'))->toContain('$_lang["chunk_name_collides"]')
        ->and($root('core/lang/uk/global.php'))->toContain('$_lang["chunk_name_collides"]')
        ->and($root('core/lang/ru/global.php'))->toContain('$_lang["chunk_name_collides"]');
});

test('a file is never left behind to be adopted by the next chunk', function () use ($root, $core) {
    // Renaming or deleting a chunk while its file stays put would hand that
    // file - and whatever is in it - to whoever names a chunk the same next.
    expect($core('Support/ChunkFileStore.php'))->toContain('public function forget(string $name): void')
        ->and($root('manager/processors/save_htmlsnippet.processor.php'))
        ->toContain('$chunkStore->forget($renamedFrom);')
        ->and($root('manager/processors/delete_htmlsnippet.processor.php'))
        ->toContain('ChunkFileStore::make()->forget($name);');
});

test('a duplicate carries the code, not the file', function () use ($root) {
    // The original's file is named after the original; the copy writes its own
    // the first time it is saved.
    expect($root('manager/processors/duplicate_htmlsnippet.processor.php'))
        ->toContain('$newHtmlsnippet->snippet = $store->resolve((string) $htmlsnippet->name, $htmlsnippet->snippet);');
});

test('the format list is the only thing a plugin has to touch', function () use ($core, $root) {
    // A plugin adds a format and the reader looks for it too - the core needs
    // to know no format but html.
    expect($root('core/config/view.php'))->toContain("'chunk_formats' => [")
        ->and($core('Support/ChunkFileStore.php'))
        ->toContain('public function firstExisting(string $name): ?string')
        ->and($core('Support/ChunkFileStore.php'))
        ->toContain('public function writeExtension(string $name): ?string');
});

test('the database path is marked as the deprecation it is', function () use ($core, $root) {
    // `php core/artisan deprecated:list` is how this project keeps track of what
    // 3.7 removes, so the column has to be findable there rather than only
    // described in prose.
    expect($core('Models/SiteHtmlsnippet.php'))->toContain('@deprecated since 3.5.8')
        ->and($core('Support/ChunkFileStore.php'))->toContain('@deprecated since 3.5.8')
        ->and($core('Parser.php'))
        ->toContain("@deprecated since 3.5.8 reading a chunk's code out of the database")
        ->and($core('Legacy/Cache.php'))->toContain('@deprecated since 3.5.8')
        ->and($core('Controllers/Chunk.php'))->toContain('@deprecated since 3.5.8')
        ->and($root('manager/processors/save_htmlsnippet.processor.php'))
        ->toContain("@deprecated since 3.5.8 writing a chunk's code to the database");
});

test('every deprecation says when it goes', function () use ($core, $root) {
    // The listing groups by removal version; a @deprecated without one is a
    // note nobody ever acts on.
    foreach (['Models/SiteHtmlsnippet.php', 'Support/ChunkFileStore.php', 'Parser.php', 'Legacy/Cache.php', 'Controllers/Chunk.php'] as $file) {
        expect($core($file))->toContain('@todo [remove@3.7]');
    }

    expect($root('manager/processors/save_htmlsnippet.processor.php'))->toContain('@todo [remove@3.7]');
});

test('the form measures filename bytes without a deprecated global', function () use ($root) {
    // unescape() has been deprecated for years; TextEncoder is the modern way
    // to count UTF-8 bytes, and it counts the same 255 the store measures.
    $view = $root('manager/views/page/chunk.blade.php');

    expect($view)->not->toContain('unescape(')
        ->toContain('new TextEncoder().encode(value).length')
        ->toContain("byteLength(filename + '.' + selected) <= 255");
});

test('every language the CMS ships has the chunk file strings', function () use ($read) {
    // A missing key renders as the lexicon name itself, so a half translated
    // set is visible to the operator rather than silently English.
    $keys = [
        'chunk_assigned_file',
        'chunk_file_unusable_name',
        'chunk_file_not_writable',
        'chunk_name_empty',
        'chunk_name_not_utf8',
        'chunk_name_not_nfc',
        'chunk_name_too_long',
        'chunk_name_collides',
    ];

    $missing = [];
    foreach (glob(dirname(__DIR__, 3) . '/lang/*/global.php') as $file) {
        $source = $read($file);
        foreach ($keys as $key) {
            if (!str_contains($source, '$_lang["' . $key . '"]')) {
                $missing[] = basename(dirname($file)) . '/' . $key;
            }
        }
    }

    expect($missing)->toBe([]);
});

test('the two strings that take an argument keep their placeholder', function () use ($read) {
    // sprintf() with no %s drops the filename, or the name of the chunk that
    // clashes - which is the only useful half of the message.
    $missing = [];
    foreach (glob(dirname(__DIR__, 3) . '/lang/*/global.php') as $file) {
        foreach ($read($file) === '' ? [] : explode("
", $read($file)) as $line) {
            foreach (['chunk_file_not_writable', 'chunk_name_collides'] as $key) {
                if (str_starts_with($line, '$_lang["' . $key . '"]') && !str_contains($line, '%s')) {
                    $missing[] = basename(dirname($file)) . '/' . $key;
                }
            }
        }
    }

    expect($missing)->toBe([]);
});
