<?php

test('vendor publish records written assets in a storage manifest', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/VendorPublishCommand.php');

    expect($source)
        ->toContain('vendor-publish/manifest.json')
        ->toContain('protected function recordPublishedItem($from, $to, $type, $action, $existedBefore)')
        ->toMatch('/recordPublishedItem\(\s*\$from,\s*\$to,\s*\'file\',/')
        ->toMatch('/recordPublishedItem\(\s*\$source,/')
        ->toContain('package_version')
        ->toContain('composer.lock')
        ->toContain('writePublishManifest()');
});
