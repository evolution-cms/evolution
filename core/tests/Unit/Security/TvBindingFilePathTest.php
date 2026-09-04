<?php

/*
|--------------------------------------------------------------------------
| @FILE / @INCLUDE bindings in template variables
|--------------------------------------------------------------------------
|
| The custom_widget output format (front end, via getTVDisplayFormat) and the
| custom_tv form element (manager, via renderFormElement) each built a path by
| concatenating EVO_BASE_PATH with the binding as typed, and used it with no
| containment check at all - so a `..` segment read, or with @INCLUDE ran, any
| file the web user could reach. Both functions are defined twice, in
| functions/tv.php and again in functions/actions/mutate_content.php, so both
| copies have to be checked.
|
| These are source level assertions: the functions need a booted manager
| request to call, while the rule they must follow - Core::atBindFilePath() -
| is exercised directly in ParserEvalHardeningTest.
|
*/

$read = static fn (string $file): string => str_replace(
    "\r",
    '',
    (string) file_get_contents(dirname(__DIR__, 4) . '/' . $file)
);

$copies = [
    'core/functions/tv.php',
    'core/functions/actions/mutate_content.php',
];

test('no TV binding builds a path by concatenation any more', function () use ($read, $copies) {
    foreach ($copies as $file) {
        expect($read($file))->not->toContain('EVO_BASE_PATH . trim(substr(');
    }
});

test('both copies resolve @FILE and @INCLUDE through the shared rule', function () use ($read, $copies) {
    foreach ($copies as $file) {
        $source = $read($file);

        // custom_widget (front end) and custom_tv (manager form), four call
        // sites across the two files.
        // custom_widget x2, custom_tv x2, and the custom_tv:<widget> file.
        expect(substr_count($source, '$modx->atBindFilePath('))->toBe(5)
            ->and($source)->toContain('atBindFilePath(substr($output, 6))')
            ->and($source)->toContain('atBindFilePath(substr($output, 9))')
            ->and($source)->toContain('atBindFilePath(substr($field_elements, 6))')
            ->and($source)->toContain('atBindFilePath(substr($field_elements, 9))');
    }
});

test('a refused binding no longer echoes the path it tried', function () use ($read, $copies) {
    // The message used to be the absolute server path plus " does not exist",
    // which told a visitor where the installation lives on disk.
    foreach ($copies as $file) {
        expect($read($file))->not->toContain("\$file_name . ' does not exist'");
    }
});

test('the custom TV widget name cannot walk out of assets/tvs', function () use ($read, $copies) {
    // $field_type is "custom_tv:<widget>", and the file it names is included.
    foreach ($copies as $file) {
        expect($read($file))
            ->toContain("atBindFilePath('assets/tvs/' . \$widget . '/' . \$widget . '.customtv.php')")
            ->and($read($file))->not->toContain("EVO_BASE_PATH.'assets/tvs/'.\$custom['1']");
    }
});

test('a TV with no output option does not raise a notice', function () use ($read, $copies) {
    // $params['output'] was read unconditionally, and display_params is empty
    // for most TVs.
    foreach ($copies as $file) {
        $source = $read($file);

        expect($source)->toContain("\$output = (string) get_by_key(\$params, 'output', '');")
            ->and($source)->not->toContain("\$params['output']");
    }
});

test('Core exposes one containment rule for every binding', function () use ($read) {
    $core = $read('core/src/Core.php');

    expect($core)->toContain('public function atBindFilePath($relative, array $searchPaths')
        ->and($core)->toContain('public function resolveAtBindFilePath($candidate)')
        // atBindInclude() decided containment on the string as typed, which a
        // `..` segment walked past - the bug fixed for @FILE in 3.5.8.
        ->and($core)->not->toContain('if (strpos($str, EVO_MANAGER_PATH) === 0)')
        ->and($core)->toContain("\$this->atBindFilePath(\$str, ['', 'assets/templates/']);");
});
