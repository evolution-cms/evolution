<?php

/*
|--------------------------------------------------------------------------
| File manager tokens, media browser CSRF, alert escaping
|--------------------------------------------------------------------------
|
| The file manager guards its destructive GET actions with a one-shot session token, which was
| built from uniqid() - a clock value, not a random one. The media browser (mcpuk) had no CSRF
| check at all and relied on SameSite alone. webAlertAndQuit() wrote its message into the page
| verbatim, so any caller interpolating request data into an alert was a reflected XSS. The
| ?stay= and ?tab= parameters were concatenated into Location headers and a script block; they
| are only ever small integers.
|
*/

function repoSource(string $relative): string
{
    return (string)file_get_contents(dirname(__DIR__, 4) . '/' . $relative);
}

it('builds the file manager token from random bytes and compares it in constant time', function () {
    $source = repoSource('core/functions/actions/files.php');

    expect($source)
        ->toContain('$newToken = bin2hex(random_bytes(16));')
        ->toContain("hash_equals(\$_SESSION['token'], \$token)")
        ->and($source)->not->toContain("uniqid('', true)");
});

it('requires the session CSRF token for every media browser act except the page and its thumbnails', function () {
    $entry = repoSource('manager/media/browser/mcpuk/browse.php');

    expect($entry)
        ->toContain("in_array(\$act, ['browser', 'thumb'], true)")
        ->toContain('hash_equals(csrf_token(), $token)');

    // the check must run before the browser is instantiated
    expect(strpos($entry, 'hash_equals(csrf_token()'))->toBeLessThan(strpos($entry, 'new browser('));

    // ... and the scripts have to send it: every request URL is built by baseGetData()
    expect(repoSource('manager/media/browser/mcpuk/tpl/tpl_javascript.php'))
        ->toContain('browser.csrfToken = "<?php echo text::jsValue(csrf_token()) ?>";');
    expect(repoSource('manager/media/browser/mcpuk/js/browser/misc.js'))
        ->toContain('data += "&_token=" + encodeURIComponent(this.csrfToken);');
});

it('escapes the message webAlertAndQuit writes into the alert page', function () {
    $source = repoSource('core/src/Core.php');

    expect($source)
        ->toContain("<p>\" . htmlspecialchars((string)\$msg, ENT_QUOTES, \$manager_charset) . '</p>")
        ->and($source)->not->toContain("<p>\" . \$msg . '</p>");
});

it('casts the stay parameter before echoing it into a redirect', function (string $file) {
    $source = repoSource($file);

    expect($source)->not->toMatch('/stay=[\'"] \. \$_POST\[\'stay\'\]/');
})->with(array_map(
    static fn (string $path) => 'manager/processors/' . basename($path),
    glob(dirname(__DIR__, 4) . '/manager/processors/save_*.processor.php')
));

it('casts the backup manager tab index before writing it into a script block', function () {
    expect(repoSource('manager/actions/bkmanager.static.php'))
        ->toContain("tpDBM.setSelectedIndex( ' . (int)\$_GET['tab'] . ' );");
});

it('ships upload defaults without scriptable or server-config extensions', function () {
    // the factory file needs a booted manager, so read the two lines instead of requiring it
    $source = repoSource('core/factory/settings.php');

    foreach (['upload_files', 'upload_images'] as $key) {
        expect(preg_match("/'$key' => '([^']*)'/", $source, $m))->toBe(1);
        $list = explode(',', $m[1]);
        expect(array_intersect($list, ['svg', 'htaccess', 'swf', 'fla', 'flv', 'php', 'phtml', 'phar']))
            ->toBe([], "$key allows a dangerous extension");
    }
});
