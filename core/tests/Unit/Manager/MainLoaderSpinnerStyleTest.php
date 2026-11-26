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

    expect($css)->toContain('#mainloader .evo__logo::before {animation-play-state: paused;}')
        ->and($css)->toContain('#mainloader.show .evo__logo::before {animation-play-state: running;}');
});

test('the ring of the preloader keeps its animation outside the main loader', function () {
    // only the main loader pauses it; preloader.css itself still spins the ring
    $preloader = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/default/css/preloader.css');

    expect($preloader)->toMatch('/\.evo__logo::before \{[^}]*animation: evo-preloader-rotate 2s linear infinite;/');
});
