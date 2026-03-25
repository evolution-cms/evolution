<?php

test('js_json keeps apostrophes as JavaScript string content', function () {
    $payload = [
        'confirm_delete_resource' => "Delete the resource 'Home'?",
        'confirm_publish' => "Опублікувати 'Головна'?",
    ];

    $json = js_json($payload);

    expect($json)->not->toContain('&#039;')
        ->and($json)->toContain("Delete the resource 'Home'?")
        ->and($json)->toContain("Опублікувати 'Головна'?");

    expect(json_decode($json, true))->toBe($payload);
});

test('manager frame lang payload is rendered through js_json helper', function () {
    $template = file_get_contents(dirname(__DIR__, 3) . '/manager/views/frame/1.blade.php');

    expect($template)->toContain('lang: {!! js_json([')
        ->and($template)->toContain("'confirm_remove_locks' => ManagerTheme::getLexicon('confirm_remove_locks')")
        ->and($template)->not->toContain('confirm_delete_resource: "{{ManagerTheme::getLexicon(\'confirm_delete_resource\')}}"')
        ->and($template)->not->toContain('confirm_remove_locks: "{{ManagerTheme::getLexicon(\'confirm_remove_locks\')}}"');
});
