<?php

test('vendor publish records written assets in a storage manifest', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/VendorPublishCommand.php');

    expect($source)
        ->toContain('vendor-publish/manifest.json')
        ->toContain('recordPublishedItem($from, $to, \'file\'')
        ->toContain('recordPublishedItem($source,')
        ->toContain('package_version')
        ->toContain('composer.lock')
        ->toContain('writePublishManifest()');
});
