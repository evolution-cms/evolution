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
})->with(function () {
    $root = dirname(__DIR__, 4);
    $out = [];
    foreach (explode("\n", trim((string)shell_exec('git -C ' . escapeshellarg($root) . ' ls-files'))) as $path) {
        if (preg_match('~(^|/)\.htaccess$~', $path)) {
            $out[] = $path;
        }
    }

    return $out;
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
