<?php

use EvolutionCMS\Support\FileBrowserAccess;

/**
 * Run real browser guards in a clean process with release/shared roots and SQLite groups.
 *
 * @since 3.6.0
 */
function sharedBrowserResult(): array
{
    static $result;
    if ($result === null) {
        $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/shared-browser-access.php', [], $exitCode);
        $result = json_decode($output, true);
        expect($exitCode)->toBe(0, $output);
        expect($result)->toBeArray($output);
    }
    if (isset($result['skip'])) {
        test()->markTestSkipped($result['skip']);
    }

    return $result;
}

it('preserves old group keys for shared roots and newly created files', function () {
    $result = sharedBrowserResult();
    expect($result['key'])->toBe('assets/images/private/secret.txt')
        ->and($result['dottedKey'])->toBe($result['key'])
        ->and($result['nextReleaseKey'])->toBe($result['key'])
        ->and($result['personalKey'])->toBe($result['key'])
        ->and($result['standardKey'])->toBe('assets/local/new.txt')
        ->and($result['newKey'])->toBe('assets/images/private/new.txt');
});

it('enforces groups in listings direct access writes and recursive reads', function () {
    $result = sharedBrowserResult();
    expect($result['deniedRead'])->toBeFalse()
        ->and($result['deniedWrite'])->toBeFalse()
        ->and($result['publicRead'])->toBeTrue()
        ->and($result['publicWrite'])->toBeTrue()
        ->and($result['listing'])->toBe(['open.txt'])
        ->and($result['subtreeSecret'])->toBeFalse()
        ->and($result['protectedDescendants'])->toBeTrue()
        ->and($result['memberRead'])->toBeTrue()
        ->and($result['memberWrite'])->toBeTrue()
        ->and($result['memberSubtree'])->toBeTrue()
        ->and($result['memberDescendants'])->toBeFalse();
});

it('refuses and logs an unmapped ACL root instead of treating it as public', function () {
    $result = sharedBrowserResult();
    expect($result['unmappedKey'])->toBeNull()
        ->and($result['unmappedRead'])->toBeFalse()
        ->and($result['unmappedWrite'])->toBeFalse()
        ->and($result['unmappedListing'])->toBe([])
        ->and($result['unmappedSubtree'])->toBeFalse()
        ->and($result['unmappedDescendants'])->toBeTrue()
        ->and($result['unmappedLink'])->toBeFalse()
        ->and(implode('\n', $result['log']))->toContain('file groups root cannot be resolved');
});

it('keeps symlinks and traversal refused including for administrators', function () {
    $result = sharedBrowserResult();
    foreach (['escape', 'internal', 'dangling', 'traversal', 'adminEscape'] as $key) {
        expect($result[$key])->toBeFalse($key);
    }
    expect($result['adminRead'])->toBeTrue();
    expect($result['visibleLink'])->toBeTrue(); // inert link entry; opening it is still refused
    expect($result['safeThumbs'])->toBeTrue()
        ->and($result['linkedThumbs'])->toBeFalse();
});

it('accepts a proven shared root but reports missing and unmapped roots', function () {
    $result = sharedBrowserResult();
    expect($result['diagnostic'])->toBeTrue()
        ->and($result['missingDiagnostic'])->toBeFalse()
        ->and($result['unmappedDiagnostic'])->toBeFalse();
    expect($result['standardDiagnostic'])->toBeTrue()
        ->and($result['wrongTargetKey'])->toBeNull()
        // a spelling outside the ACL root names nothing a row could restrict (the browser derives
        // it from the configured URL below the site root, so this needs a narrowed filemanager_path)
        ->and($result['outsideSpellingKey'])->toBe('');
});

it('does not derive local roots from unsafe or remote URLs', function (string $url) {
    expect(FileBrowserAccess::localUploadPath($url, sys_get_temp_dir(), '/cms/', 'https://example.test/cms/'))->toBeNull();
})->with(['https://other.test/cms/assets/images', '/other/assets/images', '../assets/images',
    '/cms/assets/../images', '/cms/assets/%2e%2e/images', 'assets/images?x=1', 'assets/images#x',
    'https://user@example.test/cms/assets/images', 'file:///cms/assets/images', 'assets/CON/images']);

it('refuses device names and NUL bytes before probing configured paths', function () {
    expect(FileBrowserAccess::resolveAclPath('/NUL', '/tmp', '/tmp/test'))->toBeNull()
        ->and(FileBrowserAccess::resolveAclPath('/tmp', '/tmp', "/tmp/bad\0name"))->toBeNull()
        ->and(FileBrowserAccess::isUsableRoot('/CON', 'assets/', '/tmp', '/tmp', '/', 'https://example.test/'))->toBeFalse();
});

it('renders dashboard path warnings independently and only accepts proven browser roots', function () {
    $dashboard = sharedBrowserResult()['dashboard'];
    foreach (['shared', 'portable'] as $case) {
        expect($dashboard[$case])->toBe(['browserWarning' => false, 'managerWarning' => false]);
    }
    foreach (['missing', 'unmapped'] as $case) {
        expect($dashboard[$case])->toBe(['browserWarning' => true, 'managerWarning' => false]);
    }
    expect($dashboard['missingManager']['managerWarning'])->toBeTrue()
        ->and($dashboard['traversalManager']['managerWarning'])->toBeTrue()
        ->and($dashboard['outsideManager']['managerWarning'])->toBeTrue();
});

it('resolves site relative URLs for a subdirectory installation', function () {
    $root = rtrim(str_replace('\\', '/', realpath(sys_get_temp_dir())), '/');
    foreach (['assets/images', '/cms/assets/images', 'https://example.test/cms/assets/images'] as $url) {
        expect(FileBrowserAccess::localUploadPath($url, sys_get_temp_dir(), '/cms/', 'https://example.test/cms/'))
            ->toBe($root . '/assets/images');
    }
});

it('maps the shared root from the configured upload URL, not from KCFinder typeURL', function () {
    // the fixture gives typeURL the value KCFinder computes for rb_base_url "assets/"
    expect(sharedBrowserResult()['key'])->toBe('assets/images/private/secret.txt');
});

it('leaves type roots outside a narrowed filemanager_path open as before, but not unproven ones', function () {
    $result = sharedBrowserResult();
    expect($result['narrowedPlainKey'])->toBe('')
        ->and($result['narrowedPlainRead'])->toBeTrue()
        ->and($result['narrowedPlainWrite'])->toBeTrue()
        ->and($result['narrowedPlainListing'])->toBe(['cat.jpg'])
        ->and($result['narrowedSharedKey'])->toBe('')
        ->and($result['narrowedSharedRead'])->toBeTrue()
        ->and($result['narrowedUnprovenKey'])->toBeNull()
        ->and($result['narrowedDiagnostic'])->toBeTrue();
});
