<?php

use EvolutionCMS\Support\ChunkFileStore;

/**
 * The chunk directory is guarded by an .htaccess the store writes itself, and the
 * store is also built by processes that never ran the bootstrap - the cache refresh
 * worker among them - so neither may depend on Apache 2.2 nor on EVO_BASE_PATH.
 */

it('writes the directory guard in both the 2.2 and the 2.4 dialect', function () {
    $dir = sys_get_temp_dir() . '/evo_chunk_guard_' . uniqid();
    $store = new ChunkFileStore(['html' => 'HTML'], $dir);

    expect($store->ensureDirectory())->toBeTrue();

    $htaccess = (string) file_get_contents($dir . '/.htaccess');
    expect($htaccess)
        ->toContain('<IfModule mod_authz_core.c>')
        ->toContain('Require all denied')
        ->toContain('<IfModule !mod_authz_core.c>')
        ->and(preg_match('/^\s*(Order|Deny from)\b/mi', preg_replace('/<IfModule !mod_authz_core\.c>.*?<\/IfModule>/s', '', $htaccess)))
        ->toBe(0)
        ->and($htaccess)->toBe((string) file_get_contents(dirname(__DIR__, 4) . '/views/chunks/.htaccess'));

    unlink($dir . '/.htaccess');
    unlink($dir . '/index.html');
    rmdir($dir);
});

it('resolves the installation root without the bootstrap constants', function () {
    // A separate PHP process: constants defined by other tests must not leak in.
    $probe = tempnam(sys_get_temp_dir(), 'evo_chunk_probe_');
    file_put_contents($probe, '<?php require ' . var_export(dirname(__DIR__, 3) . '/vendor/autoload.php', true) . ';'
        . 'echo defined("EVO_BASE_PATH") ? "defined" : \EvolutionCMS\Support\ChunkFileStore::make()->displayDirectory();');
    $out = trim((string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($probe) . ' 2>&1'));
    unlink($probe);

    expect($out)->toBe('views/chunks/');
});
