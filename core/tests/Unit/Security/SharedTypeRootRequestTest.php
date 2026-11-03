<?php

/**
 * Links an operator made, in both file managers, with the media browser built as a web request
 * builds it (see the fixture). Each case runs in its own process: the decision which links pass
 * needs the site settings, which a test process shares between tests.
 */
function sharedTypeRootRequest(string $base, string $upload): array
{
    static $results = [];
    $key = "$base/$upload";
    if (!isset($results[$key])) {
        $output = evoRunPhp(dirname(__DIR__, 2) . '/Fixtures/shared-type-root-request.php', [
            base64_encode(json_encode(['base' => $base, 'upload' => $upload])),
        ], $exitCode);
        $results[$key] = json_decode($output, true);
        expect($exitCode)->toBe(0, $output)
            ->and($results[$key])->toBeArray($output);
    }
    if (isset($results[$key]['skip'])) {
        test()->markTestSkipped($results[$key]['skip']);
    }

    return $results[$key];
}

dataset('site layouts', [
    'site root by its real path, upload folder in the site' => ['canonical', 'site'],
    'site root through public_html -> evolution -> release' => ['linked', 'site'],
    'upload folder configured in shared storage' => ['linked', 'shared'],
]);

it('keys a shared image folder the same way in both managers, from the configured URL', function (string $base, string $upload) {
    $result = sharedTypeRootRequest($base, $upload);

    // what KCFinder makes of rb_base_url "assets/" here: not the folder, so nothing may rely on it
    expect($result['typeURL'])->not->toBe('/assets/images')
        ->and($result['sharedRoots'])->toBe(['assets/images'])
        ->and($result['browserKey'])->toBe('assets/images/private/secret.png')
        ->and($result['classicKey'])->toBe('assets/images/private/secret.png')
        ->and($result['browserOutsiderReadsSecret'])->toBeFalse()
        ->and($result['classicOutsiderReadsSecret'])->toBeFalse()
        ->and($result['classicMemberReadsSecret'])->toBeTrue();
})->with('site layouts');

it('passes links to somewhere new as folders named by their own path', function (string $base, string $upload) {
    $result = sharedTypeRootRequest($base, $upload);

    expect($result['browserListing'])->toBe(['public', 'plugin-images', 'outside'])
        ->and($result['browserPluginImage'])->toBeTrue()
        ->and($result['browserPluginImageKey'])->toBe('assets/images/plugin-images/logo.png')
        ->and($result['browserPluginUpload'])->toBeTrue()
        ->and($result['classicPluginImage'])->toBe('assets/images/plugin-images/logo.png')
        ->and($result['classicPluginFolder'])->toBe('assets/plugins/myplugin/images/logo.png')
        ->and($result['classicOutside'])->toBe('assets/images/outside/loot.txt')
        ->and($result['writeInside'])->toBeTrue()
        ->and($result['writeIntoPlugin'])->toBeTrue();
})->with('site layouts');

it('refuses second names, loops, dangling links and climbing out of a link', function (string $base, string $upload) {
    $result = sharedTypeRootRequest($base, $upload);

    foreach (['browserSecondName', 'browserSiteName', 'browserLoop', 'browserDangling', 'browserClimb'] as $key) {
        expect($result[$key])->toBeFalse($key);
    }
    foreach (['classicSecondName', 'classicSiteName', 'classicLoop', 'classicDangling', 'classicClimb', 'classicClimbOutOfPlugin'] as $key) {
        expect($result[$key])->toBeNull($key);
    }
    expect($result['writeOverLink'])->toBeFalse()
        ->and($result['writeThroughSecondName'])->toBeFalse()
        ->and($result['browserInertLinks'])->toBe(['dangling', 'internal', 'team'])
        ->and($result['inertListedAsLinks'])->toBe(['internal', 'team', 'dangling']);
})->with('site layouts');

it('applies the rules of the place a link sits in, code included', function (string $base, string $upload) {
    $result = sharedTypeRootRequest($base, $upload);

    // a plugin checked out elsewhere stays behind save_plugin, and its code counts as code
    expect($result['pluginProtected'])->toBeTrue()
        ->and($result['pluginCode'])->toBe('plugin.php')
        ->and($result['pluginCodeFromAssets'])->toBe('myplugin/plugin.php');
})->with('site layouts');

it('never writes through a linked file, and edits through a linked folder', function (string $base, string $upload) {
    $result = sharedTypeRootRequest($base, $upload);

    // note.txt is a text name for a PHP file elsewhere: the name passes the checks, the write would not
    expect($result['saveThroughFileLink'])->toBe('files_access_denied')
        ->and($result['fileLinkTarget'])->toBe('original')
        ->and($result['savePlain'])->toBe('file_saved')
        ->and($result['plain'])->toBe('edited')
        ->and($result['saveThroughFolderLink'])->toBe('file_saved')
        ->and($result['loot'])->toBe('edited');
})->with('site layouts');

it('protects a view path outside the site wherever a link reaches it from', function (string $base, string $upload) {
    $result = sharedTypeRootRequest($base, $upload);

    // a template file is executed by its engine whatever the extension
    expect($result['saveInViewAlias'])->toBe('files_access_denied')
        ->and($result['deleteInViewAlias'])->toBe('files_access_denied')
        ->and($result['view'])->toBe('view')
        ->and($result['createInViewAlias'])->toBeTrue()
        ->and($result['createDeepInViewAlias'])->toBeTrue()
        ->and($result['renameInViewAlias'])->toBeTrue()
        ->and($result['moveIntoViewAlias'])->toBeTrue()
        ->and($result['browserViewAlias'])->toBeFalse()
        ->and($result['browserViewAliasNew'])->toBeFalse()
        ->and($result['aliasLog'])->toBeGreaterThanOrEqual(3);
    // shared media is not a protected folder
    expect($result['sharedMediaOpen'])->toBeTrue()
        ->and($result['browserSharedMedia'])->toBeTrue();
})->with('site layouts');

it('lists the shared image folder as a folder that is entered but never removed or renamed', function (string $base, string $upload) {
    $result = sharedTypeRootRequest($base, $upload);

    expect($result['isSharedRoot'])->toBeTrue()
        ->and($result['isSharedRootPlugin'])->toBeFalse()
        ->and($result['imagesListedAsFolder'])->toBeTrue()
        ->and($result['imagesOffersDelete'])->toBeFalse()
        ->and($result['imagesOffersRename'])->toBeFalse()
        ->and($result['pluginImagesListedAsFolder'])->toBeTrue()
        ->and($result['pluginImagesOffersDelete'])->toBeTrue();
})->with('site layouts');

it('removes a linked folder as a link, never what it leads to', function (string $base, string $upload) {
    $result = sharedTypeRootRequest($base, $upload);

    expect($result['removedLink'])->toBeTrue()
        ->and($result['linkGone'])->toBeTrue()
        ->and($result['checkoutKept'])->toBeTrue()
        ->and($result['log'])->toBe([]);
})->with('site layouts');

it('refuses deleting, renaming or unlinking the shared type folder in the classic manager', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/actions/files.dynamic.php');

    expect(substr_count($source, 'fileManagerSharedTypeRootAt($folder) !== null'))->toBe(1)
        ->and(substr_count($source, 'fileManagerSharedTypeRootAt($dirname) !== null'))->toBe(1)
        ->and(substr_count($source, 'fileManagerSharedTypeRootAt($linkPath) !== null'))->toBe(1);
});
