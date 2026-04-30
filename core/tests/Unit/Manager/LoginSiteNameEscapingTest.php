<?php

it('defines escaped site name placeholders for manager auth templates', function () {
    $managerThemePath = dirname(__DIR__, 3) . '/src/ManagerTheme.php';
    $managerTheme = file_get_contents($managerThemePath);

    expect(str_contains($managerTheme, "'site_name_text' => e((string)"))->toBeTrue();
    expect(str_contains($managerTheme, "'site_name_attr' => htmlspecialchars((string)"))->toBeTrue();
    expect(str_contains($managerTheme, "getConfig('site_name')"))->toBeTrue();
});

it('uses escaped site name placeholders in login and lockout templates', function () {
    $files = [
        dirname(__DIR__, 4) . '/manager/media/style/common/login.tpl',
        dirname(__DIR__, 4) . '/manager/media/style/default/login.tpl',
        dirname(__DIR__, 4) . '/manager/media/style/common/manager.lockout.tpl',
        dirname(__DIR__, 4) . '/manager/media/style/default/manager.lockout.tpl',
    ];

    foreach ($files as $file) {
        $template = file_get_contents($file);

        expect(str_contains($template, '[+site_name_text+]'))->toBeTrue();
        expect(str_contains($template, 'title="[(site_name)]"'))->toBeFalse();
        expect(str_contains($template, 'alt="[(site_name)]"'))->toBeFalse();
    }
});
