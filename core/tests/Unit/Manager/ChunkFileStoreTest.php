<?php

use EvolutionCMS\Support\ChunkFileStore;

/**
 * A chunk may keep its code in a file instead of the site_htmlsnippets row.
 * The name it is stored under becomes half of a path, so the store decides what
 * a name is allowed to be - and refuses rather than sanitising into something
 * the operator did not type.
 */

beforeAll(function () {
    if (!defined('EVO_BASE_PATH')) {
        define('EVO_BASE_PATH', str_replace(chr(92), '/', dirname(__DIR__, 3)) . '/');
    }
});

function chunkFileStore(?string $directory = null): ChunkFileStore
{
    return new ChunkFileStore(
        ['html' => 'HTML', 'tpl' => 'Template', 'txt' => 'Text'],
        $directory ?? (EVO_BASE_PATH . 'views/chunks/')
    );
}

it('offers every declared format, and nothing else', function () {
    $store = chunkFileStore();

    expect(array_keys($store->all()))->toBe(['html', 'tpl', 'txt'])
        ->and($store->defaultExtension())->toBe('html')
        ->and($store->isRegistered('html'))->toBeTrue()
        ->and($store->isRegistered('php'))->toBeFalse()
        ->and($store->isRegistered('blade.php'))->toBeFalse()
        ->and($store->isRegistered(null))->toBeFalse();
});

it('keeps the dots that chunk names actually contain', function () {
    // Unlike a template alias, "nav.main" is an ordinary chunk name, so a
    // filename rule borrowed from templates would have refused most of them.
    expect(chunkFileStore()->filename('nav.main', 'html'))->toBe('nav.main.html')
        ->and(chunkFileStore()->filename('Header_2-col', 'tpl'))->toBe('Header_2-col.tpl');
});

it('encodes a name rather than changing it', function () {
    $store = chunkFileStore();

    // Writing "myChunk.html" for a chunk named "my Chunk" would leave the two
    // disagreeing about which file is read - so the space is escaped, not
    // dropped, and nameFor() gives the name back exactly.
    expect($store->filename('my Chunk', 'html'))->toBe('my Chunk.html')
        ->and($store->filename('nav/main', 'html'))->toBe('nav%2Fmain.html')
        ->and($store->nameFor('nav%2Fmain.html', 'html'))->toBe('nav/main')
        ->and($store->filename('', 'html'))->toBeNull();
});

it('cannot be talked out of the chunk directory', function () {
    $store = chunkFileStore();

    // Every separator becomes an escape and the leading dot with it, so a
    // traversal is one filename with percent signs in it and nothing more.
    expect($store->filename('../../index', 'html'))->toBe('%2E.%2F..%2Findex.html')
        ->and($store->filename('..', 'html'))->toBe('%2E%2E.html')
        ->and($store->filename('.htaccess', 'html'))->toBe('%2Ehtaccess.html')
        // ... and it still names the chunk it came from, not a path.
        ->and($store->nameFor('%2E.%2F..%2Findex.html', 'html'))->toBe('../../index')
        ->and($store->pathFor('../../index', 'html'))->toBeNull();
});

it('refuses a format nobody declared', function () {
    // The extension is the other half of a filename under the web root.
    $store = chunkFileStore();

    expect($store->filename('nav', 'php'))->toBeNull()
        ->and($store->pathFor('nav', 'php'))->toBeNull()
        ->and($store->read('nav', 'php'))->toBeNull()
        ->and($store->write('nav', 'php', 'x'))->toBeFalse();
});

it('writes, reads back and lists the file it made', function () {
    $directory = sys_get_temp_dir() . '/evo_chunks_' . bin2hex(random_bytes(6));
    $store = chunkFileStore($directory);
    $name = 'nav.main';

    try {
        // The directory does not exist yet: creating it is part of the job.
        expect($store->write($name, 'html', '<nav>[+items+]</nav>'))->toBeTrue()
            ->and($store->read($name, 'html'))->toBe('<nav>[+items+]</nav>')
            ->and(array_keys($store->existing($name)))->toBe(['html'])
            ->and($store->read($name, 'tpl'))->toBeNull();
    } finally {
        @unlink($directory . '/' . $name . '.html');
        @unlink($directory . '/.htaccess');
        @unlink($directory . '/index.html');
        @rmdir($directory);
    }
});

it('names the file relative to the installation, never absolutely', function () {
    // The manager shows this string; an absolute server path is not the
    // operator's business and not theirs to paste anywhere.
    $store = chunkFileStore();

    expect($store->displayDirectory())->toBe('views/chunks/')
        ->and($store->displayPath('nav.main', 'html'))->toBe('views/chunks/nav.main.html')
        ->and($store->displayPath('nav/main', 'html'))->toBe('views/chunks/nav%2Fmain.html')
        ->and($store->displayPath('', 'html'))->toBeNull();
});

it('reads the file when there is one, and the column when there is not', function () {
    $directory = sys_get_temp_dir() . '/evo_chunks_' . bin2hex(random_bytes(6));
    $store = chunkFileStore($directory);

    try {
        // No file yet: a chunk that has not been saved since files existed
        // renders exactly what it always rendered.
        expect($store->resolve('nav', 'from the column'))->toBe('from the column')
            ->and($store->resolve('nav', null))->toBeNull();

        $store->write('nav', 'html', 'from the file');

        expect($store->resolve('nav', 'from the column'))->toBe('from the file')
            ->and($store->firstExisting('nav'))->toBe('html')
            // Re-saving must go back to the same file, not put an .html
            // beside a .tpl and change which one wins.
            ->and($store->writeExtension('nav'))->toBe('html')
            ->and($store->writeExtension('never.seen'))->toBe('html');
    } finally {
        $store->forget('nav');
        @unlink($directory . '/.htaccess');
        @unlink($directory . '/index.html');
        @rmdir($directory);
    }
});

it('forgets every file a chunk had, not just the winning one', function () {
    // A file left behind after a rename or a delete would be adopted by the
    // next chunk named the same.
    $directory = sys_get_temp_dir() . '/evo_chunks_' . bin2hex(random_bytes(6));
    $store = chunkFileStore($directory);

    try {
        $store->write('nav', 'html', 'a');
        $store->write('nav', 'tpl', 'b');

        expect(array_keys($store->existing('nav')))->toBe(['html', 'tpl']);

        $store->forget('nav');

        expect($store->existing('nav'))->toBe([])
            ->and($store->resolve('nav', 'column'))->toBe('column');
    } finally {
        $store->forget('nav');
        @unlink($directory . '/.htaccess');
        @unlink($directory . '/index.html');
        @rmdir($directory);
    }
});
