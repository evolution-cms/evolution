<?php

/*
|--------------------------------------------------------------------------
| The manager loader must not outlive a download
|--------------------------------------------------------------------------
|
| The loader is switched on from beforeunload and switched off by the next page's load handler.
| A download never produces that load event - the browser sees Content-Disposition, cancels the
| navigation and leaves the current document in place - so the loader used to spin forever after
| "Download ZIP" in the file manager.
|
| main.js is a plain script rather than a module, so these assertions are made against its
| source, in the same style as the other manager script tests.
|
*/

/**
 * Reads a manager script.
 */
function managerScriptSource(string $relative): string
{
    return (string)file_get_contents(dirname(__DIR__, 4) . '/manager/' . $relative);
}

it('does not register an unload listener', function () {
    // Chrome refuses 'unload' under the Permissions Policy: the listener never ran and logged a
    // violation on every manager page.
    $source = managerScriptSource('media/script/main.js');

    expect($source)
        ->not->toContain("addEventListener('unload'")
        ->and($source)->toContain("addEventListener('pagehide'");
});

it('still clears the pending loader timer when the page really goes away', function () {
    $source = managerScriptSource('media/script/main.js');

    expect($source)->toMatch("/addEventListener\('pagehide', function \(\) \{\s*clearTimeout\(timerForUnload\);/");
});

it('bounds the loader when a navigation never commits', function () {
    // Without the timer a cancelled navigation - a download, a failed load, a dismissed
    // unsaved-changes prompt - leaves the loader visible with nothing left to switch it off.
    $source = managerScriptSource('media/script/main.js');

    expect($source)
        ->toContain('var UNLOAD_LOADER_TIMEOUT =')
        ->toContain('timerForUnload = setTimeout(stopWorker, UNLOAD_LOADER_TIMEOUT);');
});

it('consumes the worker suppression flag so it cannot silence later navigation', function () {
    // A download leaves the document in place, so a flag that is never reset would keep the
    // loader hidden for every later navigation from that page.
    $source = managerScriptSource('media/script/main.js');

    expect($source)->toMatch('/if \(dontShowWorker\) \{\s*dontShowWorker = false;/');
});

it('suppresses the loader on the file manager zip download link', function () {
    $source = managerScriptSource('actions/files.dynamic.php');

    expect($source)->toMatch('/<a[^>]*onclick="dontShowWorker = true;"[^>]*mode=downloadzip/');
});

it('keeps the zip download reachable in the main frame so errors stay visible', function () {
    // Retargeting the link at a hidden iframe would also hide the "invalid token" and
    // "zip unavailable" responses, which are rendered as an ordinary page.
    $source = managerScriptSource('actions/files.dynamic.php');

    expect($source)
        ->toContain('mode=downloadzip')
        ->and($source)->not->toMatch('/mode=downloadzip[^>]*target="fileDownloader"/');
});
