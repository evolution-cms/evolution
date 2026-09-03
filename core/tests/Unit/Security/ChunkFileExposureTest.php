<?php

use EvolutionCMS\Support\ChunkFileStore;

/**
 * Chunk files must not be reachable over HTTP.
 *
 * A chunk is not always markup. A good deal of older extras keep configuration
 * in one - API keys, SMTP credentials, connection details - because the
 * database was the only place to put it. Moving every chunk into a file is only
 * safe if the files land somewhere the web server will not serve, and the root
 * .htaccess passes ^assets/ straight through to the filesystem.
 *
 * Verified against a live Apache during development: a file under assets/chunks
 * came back 200 with its contents, and the same file under views/ came back
 * 403.
 */

beforeAll(function () {
    if (!defined('EVO_BASE_PATH')) {
        define('EVO_BASE_PATH', str_replace(chr(92), '/', dirname(__DIR__, 3)) . '/');
    }
});

$root = static fn (string $file): string => str_replace(
    chr(13),
    '',
    (string) file_get_contents(dirname(__DIR__, 4) . '/' . $file)
);

test('the default chunk directory is not under a served path', function () use ($root) {
    $config = $root('core/config/view.php');

    expect($config)->toContain("'chunk_path' => EVO_BASE_PATH . 'views/chunks/',")
        ->and($config)->not->toContain("'chunk_path' => EVO_BASE_PATH . 'assets/");
});

test('the directory it lives under ships denied', function () use ($root) {
    // views/ carries the same guard core/ does, and has since long before
    // this - which is exactly why the chunks went there.
    $guard = strtolower($root('views/.htaccess'));

    expect($guard)->toContain('deny from all');
});

test('the root rewrite serves assets straight from disk', function () use ($root) {
    // The reason assets/ is not an option: this line hands the request to the
    // filesystem before the CMS ever sees it. Read from ht.access - the
    // shipped template - because the live .htaccess is not in the repository.
    expect($root('ht.access'))->toContain('RewriteRule ^(manager|assets|js|css|images|img)/.*$ - [L]');
});

test('the store writes its own guards, wherever it is pointed', function () {
    // The path is configurable, and a plugin or a hoster may point it
    // somewhere with no inherited protection at all.
    $directory = sys_get_temp_dir() . '/evo_guard_' . bin2hex(random_bytes(6));
    $store = new ChunkFileStore(['html' => 'HTML'], $directory);

    try {
        expect($store->ensureDirectory())->toBeTrue()
            ->and(is_file($directory . '/.htaccess'))->toBeTrue()
            ->and(strtolower((string) file_get_contents($directory . '/.htaccess')))
            ->toContain('deny from all')
            ->and(is_file($directory . '/index.html'))->toBeTrue();
    } finally {
        @unlink($directory . '/.htaccess');
        @unlink($directory . '/index.html');
        @rmdir($directory);
    }
});

test('an existing guard is never overwritten', function () {
    // Somebody may have tightened it, or replaced it with something their
    // server actually reads.
    $directory = sys_get_temp_dir() . '/evo_guard_' . bin2hex(random_bytes(6));
    mkdir($directory, 0777, true);
    file_put_contents($directory . '/.htaccess', 'Require all denied');

    $store = new ChunkFileStore(['html' => 'HTML'], $directory);

    try {
        expect($store->ensureDirectory())->toBeTrue()
            ->and(file_get_contents($directory . '/.htaccess'))->toBe('Require all denied');
    } finally {
        @unlink($directory . '/.htaccess');
        @unlink($directory . '/index.html');
        @rmdir($directory);
    }
});

test('the shipped chunk directory carries its guards', function () {
    // So a site gets them from the archive, not from the first chunk anybody
    // happens to save. (EVO_BASE_PATH is whatever an earlier test defined it
    // as, so the repository root is worked out from this file instead.)
    $chunks = dirname(__DIR__, 4) . '/views/chunks/';

    expect(is_file($chunks . '.htaccess'))->toBeTrue()
        ->and(strtolower((string) file_get_contents($chunks . '.htaccess')))
        ->toContain('deny from all')
        ->and(is_file($chunks . 'index.html'))->toBeTrue();
});
