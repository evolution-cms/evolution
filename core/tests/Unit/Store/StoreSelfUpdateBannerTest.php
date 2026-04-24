<?php

test('store module does not expose legacy self update banner', function () {
    $template = file_get_contents(dirname(__DIR__, 4) . '/assets/modules/store/template/main.html');
    $script = file_get_contents(dirname(__DIR__, 4) . '/assets/modules/store/js/store.js');
    $languageFiles = glob(dirname(__DIR__, 4) . '/assets/modules/store/lang/*.php');

    expect($template)->not->toContain('id="actions"')
        ->and($template)->not->toContain('version_evailble')
        ->and($template)->not->toContain('new_version')
        ->and($template)->not->toContain('javascript:store.update()')
        ->and($script)->not->toContain('/assets/modules/store/update.php')
        ->and($script)->not->toContain('new_version')
        ->and($script)->not->toContain('#actions')
        ->and($script)->not->toMatch('/\bupdate\s*:\s*function\b/');

    foreach ($languageFiles as $languageFile) {
        expect(file_get_contents($languageFile))->not->toContain('version_evailble');
    }
});
