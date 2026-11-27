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

function loginLoaderCss(): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/manager/media/style/default/login.tpl');
}

function loaderRingRules(string $css): array
{
    preg_match_all('/(?:#mainloader\.show )?\.evo__logo::before \{[^}]*\}/', $css, $matches);

    return $matches[0];
}

test('the login screen shows the same loader ring as the manager', function () {
    $manager = loaderRingRules(mainLoaderCss());

    expect($manager)->toHaveCount(2)
        ->and(loaderRingRules(loginLoaderCss()))->toBe($manager);
});
