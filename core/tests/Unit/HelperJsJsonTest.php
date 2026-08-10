<?php

use Illuminate\Support\HtmlString;

test('js_json keeps apostrophes as JavaScript string content', function () {
    $payload = [
        'confirm_delete_resource' => "Delete the resource 'Home'?",
        'confirm_publish' => "Опублікувати 'Головна'?",
    ];

    $json = (string) js_json($payload);

    expect($json)->not->toContain('&#039;')
        ->and($json)->toContain("Delete the resource 'Home'?")
        ->and($json)->toContain("Опублікувати 'Головна'?");

    expect(json_decode($json, true))->toBe($payload);
});

test('js_json escapes markup delimiters so the payload cannot leave the script element', function () {
    $payload = ['msg' => 'closing </script><script>alert(1)</script>'];

    $json = (string) js_json($payload);

    expect($json)->not->toContain('</script>')
        ->and($json)->not->toContain('<script>')
        ->and($json)->toContain('\u003C/script\u003E');

    // Only the encoding changed - a browser still reads back exactly the original text.
    expect(json_decode($json, true))->toBe($payload);
});

test('js_json is Htmlable so templates can print it with the escaping echo', function () {
    $json = js_json(['a' => 1]);

    expect($json)->toBeInstanceOf(HtmlString::class)
        ->and(e($json))->toBe('{"a":1}');
});

test('js_json degrades to a valid JavaScript literal for unencodable input', function () {
    expect((string) js_json(fopen('php://memory', 'r')))->toBe('null');
});
