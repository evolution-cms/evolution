<?php

it('uses the site label for lockout home buttons instead of the dashboard label', function () {
    $files = [
        dirname(__DIR__, 4) . '/manager/media/style/common/manager.lockout.tpl',
        dirname(__DIR__, 4) . '/manager/media/style/default/manager.lockout.tpl',
    ];

    foreach ($files as $file) {
        $template = file_get_contents($file);

        expect(str_contains($template, 'value="[%site%]"'))->toBeTrue();
        expect(str_contains($template, 'value="[%home%]"'))->toBeFalse();
        expect(str_contains($template, "[+homeurl+]"))->toBeTrue();
    }
});
