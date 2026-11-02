<?php

function actionButtonsCss(): string
{
    $dir = dirname(__DIR__, 4) . '/manager/media/style/default/css/';

    return file_get_contents($dir . 'custom.css') . "\n" . file_get_contents($dir . 'forms.css');
}

test('the save button has no darker band along its bottom edge', function () {
    expect(actionButtonsCss())->not->toMatch('/#actions[^{]*\.btn-success[^{]*\{[^}]*box-shadow/');
});

test('action button text sits 1px higher than the forms.css padding puts it', function () {
    $css = actionButtonsCss();

    expect($css)->toContain('#actions .btn-group .btn { padding-top: calc(.46153846em - 1px) }')
        // the chevron toggle keeps the full padding, so it does not move with the text
        ->and($css)->toMatch('/#actions \.btn-group \.plus \{[^}]*padding: \.46153846em 0;/');
});
