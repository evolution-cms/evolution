<?php

use EvolutionCMS\Support\DocumentSave\TemplateVariableInput;

$text = ['id' => 3, 'type' => 'text', 'default_text' => 'dflt'];

test('a plain value is stored as posted', function () use ($text) {
    expect(TemplateVariableInput::value($text, ['tv3' => 'hello']))->toBe('hello');
});

test('empty values, "0" and the default text mean "remove the row"', function () use ($text) {
    expect(TemplateVariableInput::value($text, []))->toBeNull()
        ->and(TemplateVariableInput::value($text, ['tv3' => '']))->toBeNull()
        // the legacy processor treated "0" as empty; the row is dropped and the default shows
        ->and(TemplateVariableInput::value($text, ['tv3' => '0']))->toBeNull()
        ->and(TemplateVariableInput::value($text, ['tv3' => 'dflt']))->toBeNull();
});

test('checkboxes and multiple selects are joined with the || delimiter', function () use ($text) {
    expect(TemplateVariableInput::value($text, ['tv3' => ['a' => 'one', 'b' => 'two']]))->toBe('one||two');
});

test('url fields get the chosen prefix after stripping any scheme the user typed', function () {
    $url = ['id' => 5, 'type' => 'url', 'default_text' => ''];

    expect(TemplateVariableInput::value($url, ['tv5' => 'http://example.org', 'tv5_prefix' => 'https://']))->toBe('https://example.org')
        ->and(TemplateVariableInput::value($url, ['tv5' => 'mailto:me@example.org', 'tv5_prefix' => '--']))->toBe('mailto:me@example.org')
        ->and(TemplateVariableInput::value($url, ['tv5' => 'example.org']))->toBe('example.org');
});

test('file fields ignore array input', function () {
    $file = ['id' => 6, 'type' => 'file', 'default_text' => ''];

    expect(TemplateVariableInput::value($file, ['tv6' => 'assets/a.pdf']))->toBe('assets/a.pdf')
        ->and(TemplateVariableInput::value($file, ['tv6' => ['x']]))->toBeNull();
});

test('values() keys the desired value by tv id for every tv of the template', function () use ($text) {
    $tvs = [$text, ['id' => 4, 'type' => 'text', 'default_text' => '']];

    expect(TemplateVariableInput::values($tvs, ['tv3' => 'x']))->toBe([3 => 'x', 4 => null]);
});
