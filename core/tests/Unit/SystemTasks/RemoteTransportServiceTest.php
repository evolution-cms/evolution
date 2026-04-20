<?php

use EvolutionCMS\Services\Store\CatalogService;
use EvolutionCMS\Services\Store\RemoteTransportService;

test('remote transport only allows https urls from allowlisted hosts', function () {
    $service = new RemoteTransportService();

    expect($service->normalizeAndValidateUrl('https://evo.im/extras.json'))->toBe('https://evo.im/extras.json')
        ->and($service->normalizeAndValidateUrl('https://extras.evo.im/get.php?get=file&cid=1'))->toBe('https://extras.evo.im/get.php?get=file&cid=1')
        ->and($service->normalizeAndValidateUrl('https://github.com/evolution-cms/evolution'))->toBe('https://github.com/evolution-cms/evolution')
        ->and($service->normalizeAndValidateUrl('https://codeload.github.com/evolution-cms/evolution/zip/refs/heads/3.5.x'))->toBe('https://codeload.github.com/evolution-cms/evolution/zip/refs/heads/3.5.x')
        ->and($service->normalizeAndValidateUrl('https://raw.githubusercontent.com/evolution-cms/evolution/main/README.md'))->toBe('https://raw.githubusercontent.com/evolution-cms/evolution/main/README.md')
        ->and($service->normalizeAndValidateUrl('http://evo.im/extras.json'))->toBe('')
        ->and($service->normalizeAndValidateUrl('https://example.com/file.zip'))->toBe('')
        ->and($service->normalizeAndValidateUrl('https://github.com.evil.example/evolution-cms/evolution'))->toBe('')
        ->and($service->normalizeAndValidateUrl('https://github.com@evil.example/evolution-cms/evolution'))->toBe('')
        ->and($service->normalizeAndValidateUrl(''))->toBe('')
        ->and($service->normalizeAndValidateUrl('file:///tmp/test.zip'))->toBe('');
});

test('store transport services keep remote access delegated through secure transport', function () {
    $catalogSource = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Services/Store/CatalogService.php');
    $installSource = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Services/Store/PackageInstallFlowService.php');
    $deleteSource = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Services/Store/LegacyDeleteService.php');

    expect($catalogSource)->toContain('remoteTransportService->fetchBody($url)')
        ->and($catalogSource)->not->toContain('CURLOPT_SSL_VERIFYPEER, false')
        ->and($installSource)->toContain('remoteTransportService->downloadFile($url, $path)')
        ->and($installSource)->not->toContain('CURLOPT_SSL_VERIFYPEER, false')
        ->and($deleteSource)->toContain('remoteTransportService->downloadFile($url, $path)')
        ->and($deleteSource)->not->toContain('CURLOPT_SSL_VERIFYPEER, false');
});

test('remote transport source keeps ssl verification enabled for stream and curl paths', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Services/Store/RemoteTransportService.php');

    expect($source)->toContain("verify_peer' => true")
        ->and($source)->toContain("verify_peer_name' => true")
        ->and($source)->toContain("allow_self_signed' => false")
        ->and($source)->toContain("createStreamContext(60)")
        ->and($source)->toContain('CURLOPT_SSL_VERIFYPEER, true')
        ->and($source)->toContain('CURLOPT_SSL_VERIFYHOST, 2')
        ->and($source)->not->toContain('CURLOPT_SSL_VERIFYPEER, false');
});
