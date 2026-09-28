<?php

/*
|--------------------------------------------------------------------------
| Apache templates and the session cookie
|--------------------------------------------------------------------------
|
| ng.inx refuses /core, /views, dumps, composer.json and PHP under assets; the Apache template
| refused none of them and left that to per-directory .htaccess files written in the Apache 2.2
| dialect, which a 2.4 server without mod_access_compat rejects. The session cookie's Secure flag
| came from SESSION_SECURE_COOKIE alone, so the session proxy (the default) never set it, even
| on an HTTPS site.
|
*/

function evoFile(string $relative): string
{
    return (string)file_get_contents(dirname(__DIR__, 4) . '/' . $relative);
}

it('refuses the same paths in the apache template as in the nginx one', function () {
    $htaccess = evoFile('ht.access');

    expect($htaccess)
        ->toContain('RewriteRule ^(core|views|vendor|tmp)(/|$) - [F,L]')
        ->toContain('RewriteRule ^assets/(cache|backup|export|import)(/|$) - [F,L]')
        ->toContain('RewriteRule ^assets/.*\.php$ - [F,L,NC]')
        ->toContain('RewriteRule \.(sql|sqlite|db|log|bak|old|orig|save|swp|swo|tpl|inc|ini|env|dist|example|yml|yaml|lock|tar)(\.(gz|bz2|xz|zip|tgz))?$ - [F,L,NC]')
        ->toContain('RewriteRule ^(composer\.(json|lock)|phpstan\.neon|publiccode\.yml|AGENTS\.md|README\.md|ht\.access|ng\.inx|config\.php(\.example)?)$ - [F,L]');

    // the deny rules must run before the assets/manager passthrough that ends rewriting
    expect(strpos($htaccess, 'RewriteRule ^(core|views|vendor|tmp)'))
        ->toBeLessThan(strpos($htaccess, 'RewriteRule ^(manager|assets|js|css|images|img)/.*$ - [L]'));
});

it('writes every shipped per-directory .htaccess in both the 2.2 and the 2.4 dialect', function (string $file) {
    $source = evoFile($file);

    if (preg_match('/^\s*(Order|Deny|Allow|Require)\b/mi', $source) !== 1) {
        expect(true)->toBeTrue(); // rewrite-only file, nothing to check
        return;
    }

    expect($source)
        ->toContain('<IfModule mod_authz_core.c>')
        ->toContain('<IfModule !mod_authz_core.c>')
        ->and(preg_match_all('/Require all (denied|granted)/', $source))
        ->toBe(preg_match_all('/(Deny|Allow) from all/i', $source));

    // a bare 2.2 directive outside its guard is a 500 on a 2.4 server without mod_access_compat
    expect(preg_match('/^\s*(Order|Deny from|Allow from)\b/mi', preg_replace('/<IfModule !mod_authz_core\.c>.*?<\/IfModule>/s', '', $source)))
        ->toBe(0);
})->with(fn () => evoShippedHtaccessFiles(dirname(__DIR__, 4)));

/**
 * The .htaccess files the project ships, relative to $root: git's list when this is a checkout,
 * otherwise (a release archive, a copy, a container where git refuses the directory) the ones
 * on disk, minus folders that hold dependencies, runtime data or scratch work.
 *
 * @return string[]
 */
function evoShippedHtaccessFiles(string $root): array
{
    return evoTrackedHtaccessFiles($root) ?: evoScannedHtaccessFiles($root);
}

/**
 * @return string[] empty when $root is no git checkout or git is unusable here
 */
function evoTrackedHtaccessFiles(string $root): array
{
    $null = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
    try {
        $tracked = evoRunCommand('git -C ' . escapeshellarg($root) . ' ls-files 2>' . $null, $code);
    } catch (\Exception $e) {
        return []; // no way to run a process here
    }
    if ($code !== 0) {
        return []; // no checkout, no git, or git refuses the directory (dubious ownership)
    }
    $out = [];
    foreach (explode("\n", $tracked) as $path) {
        if (preg_match('~(^|/)\.htaccess$~', $path)) {
            $out[] = $path;
        }
    }
    sort($out);

    return $out;
}

/**
 * @return string[]
 */
function evoScannedHtaccessFiles(string $root): array
{
    $root = rtrim(str_replace('\\', '/', $root), '/');
    // skipped wherever they are / only at these paths from the root
    $skipNames = ['.git', 'node_modules', 'vendor'];
    $skipPaths = ['tmp', 'core/storage'];
    $relative = static fn (SplFileInfo $item) => ltrim(substr(str_replace('\\', '/', $item->getPathname()), strlen($root)), '/');

    $iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $item) use ($relative, $skipNames, $skipPaths) {
            if ($item->isDir() && !$item->isLink()) {
                return !in_array($item->getFilename(), $skipNames, true) && !in_array($relative($item), $skipPaths, true);
            }

            return $item->getFilename() === '.htaccess';
        }
    ));
    $out = [];
    foreach ($iterator as $item) {
        $out[] = $relative($item);
    }
    sort($out);

    return $out;
}

it('finds the shipped .htaccess files without git as well', function () {
    $root = str_replace('\\', '/', sys_get_temp_dir()) . '/evo-htaccess-' . bin2hex(random_bytes(6));
    $dirs = ['core', 'core/vendor/pkg', 'core/storage/cache', 'node_modules/x', 'tmp/evo/core', 'assets/cache', 'assets/tmp'];
    foreach ($dirs as $dir) {
        mkdir($root . '/' . $dir, 0777, true);
        file_put_contents($root . '/' . $dir . '/.htaccess', 'Require all denied');
    }
    file_put_contents($root . '/core/htaccess.txt', 'not one');
    try {
        expect(evoScannedHtaccessFiles($root))->toBe(['assets/cache/.htaccess', 'assets/tmp/.htaccess', 'core/.htaccess']);
    } finally {
        @unlink($root . '/core/htaccess.txt');
        foreach ($dirs as $dir) {
            @unlink($root . '/' . $dir . '/.htaccess');
        }
        foreach (['core/vendor/pkg', 'core/vendor', 'core/storage/cache', 'core/storage', 'core', 'node_modules/x', 'node_modules',
                     'tmp/evo/core', 'tmp/evo', 'tmp', 'assets/cache', 'assets/tmp', 'assets', ''] as $dir) {
            @rmdir($root . '/' . $dir);
        }
    }
});

it('finds the same .htaccess files through git and on disk in this checkout', function () {
    $root = dirname(__DIR__, 4);
    $tracked = evoTrackedHtaccessFiles($root);
    if ($tracked === []) {
        test()->markTestSkipped('no usable git checkout here');
    }

    // git's list is the reference; the scan may add untracked files, but must not miss any
    expect(array_values(array_diff($tracked, evoScannedHtaccessFiles($root))))->toBe([]);
});

it('does not ship an installer .htaccess that strips the CSP the installer sets', function () {
    expect(file_exists(dirname(__DIR__, 4) . '/install/.htaccess'))->toBeFalse();
});

it('no longer switches mod_security off for the manager', function () {
    expect(evoFile('manager/.htaccess'))->not->toContain('SecFilterEngine');
});

it('marks the session cookie Secure on https unless told otherwise', function () {
    $source = evoFile('core/config/session.php');

    expect($source)
        ->toContain("'secure' => env('SESSION_SECURE_COOKIE') ?? (")
        ->toContain("\$_SERVER['HTTPS'] !== 'off'")
        ->and($source)->not->toContain("env('SESSION_SECURE_COOKIE', false)");
});
