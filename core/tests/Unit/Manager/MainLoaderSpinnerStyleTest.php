<?php

function mainLoaderCss(): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/default/css/index.css');
}

test('the main loader contains its own painting', function () {
    // Chrome on Windows drew pieces of the spinning ring over the tree resizer while a page loaded
    expect(mainLoaderCss())->toMatch('/#mainloader \{[^}]*contain: paint/');
});

test('the loader ring spins only while the loader is shown', function () {
    $css = mainLoaderCss();

    expect($css)->toMatch('/\.evo__logo::before \{[^}]*animation: rotateLogo [^;}]*\bpaused\b/')
        ->and($css)->toContain('#mainloader.show .evo__logo::before {animation-play-state: running;}');
});
