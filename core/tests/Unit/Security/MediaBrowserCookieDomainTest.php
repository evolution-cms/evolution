<?php

it('strips the port before deciding the media browser cookie domain', function () {
    $source = file_get_contents(dirname(__DIR__, 4) . '/manager/media/browser/mcpuk/core/uploader.php');

    expect($source)
        ->toContain("preg_replace('/:\d+\$/', '', \$_SERVER['HTTP_HOST'])")
        ->not->toContain("\$this->config['cookieDomain'] = \$_SERVER['HTTP_HOST']");
});
