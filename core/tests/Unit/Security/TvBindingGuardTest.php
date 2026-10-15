<?php

use EvolutionCMS\Support\TvBindingGuard;

it('recognises the bindings that run code', function (string $value) {
    expect(TvBindingGuard::hasCodeBinding($value))->toBeTrue();
})->with(['@EVAL return 1;', '  @eval return 1;', '@@EVAL return time();', "@SELECT id FROM t"]);

it('lets harmless values through', function (?string $value) {
    expect(TvBindingGuard::hasCodeBinding($value))->toBeFalse();
})->with([null, '', 'Red==red||Blue==blue', '@CHUNK header', '@FILE assets/x.html', 'mail@EVALUATE.com', 'text @EVAL later']);

it('refuses a new code binding without the PHP permission', function (string $field) {
    expect(TvBindingGuard::allows(false, [$field => '@EVAL return 1;']))->toBeFalse();
})->with(['elements', 'default_text', 'display_params']);

it('allows a code binding for a manager who may write PHP', function () {
    expect(TvBindingGuard::allows(true, ['elements' => '@EVAL return 1;']))->toBeTrue();
});

it('keeps an existing binding editable around it, but not changeable, without the permission', function () {
    $stored = ['elements' => '@EVAL return 1;'];

    expect(TvBindingGuard::allows(false, ['elements' => '@EVAL return 1;', 'default_text' => 'x'], $stored))->toBeTrue()
        ->and(TvBindingGuard::allows(false, ['elements' => '@EVAL system("id");'], $stored))->toBeFalse();
});

it('catches a code binding in a TV value even behind another binding', function (string $value) {
    expect(TvBindingGuard::reachesCodeBinding($value))->toBeTrue();
})->with(['@EVAL return 1;', '@INHERIT @EVAL return 1;', "@INHERIT\n@eval x();", '@@SELECT 1']);

it('leaves ordinary TV text alone', function (string $value) {
    expect(TvBindingGuard::reachesCodeBinding($value))->toBeFalse();
})->with(['', 'write to me@select.com', '@CHUNK header', 'plain']);

it('refuses new code in TV values or resource content without the PHP permission', function () {
    expect(TvBindingGuard::allowsValues(false, [3 => '@INHERIT @EVAL 1;'], []))->toBeFalse()
        ->and(TvBindingGuard::allowsValues(false, [], [], '@EVAL return 1;', 'old'))->toBeFalse()
        ->and(TvBindingGuard::allowsValues(true, [3 => '@EVAL 1;'], [], '@EVAL 1;'))->toBeTrue()
        ->and(TvBindingGuard::allowsValues(false, [3 => '@EVAL 1;'], [3 => '@EVAL 1;']))->toBeTrue()
        ->and(TvBindingGuard::allowsValues(false, [3 => 'text'], [3 => '@EVAL 1;']))->toBeTrue();
});

it('catches the forms the parser accepts but a word-boundary check misses', function (string $value) {
    expect(TvBindingGuard::hasCodeBinding($value))->toBeTrue();
})->with([
    'no boundary' => '@EVALreturn 1;',
    'select no boundary' => '@SELECTid FROM x',
    'inherit chain' => '@INHERIT@EVAL x',
    'nested inherit' => "@INHERIT @INHERIT\n@evalx",
    'nul prefix' => "\0@EVAL x",
    'vertical tab prefix' => "\x0B@EVAL x",
]);

it('checks the page text fields a binding can pull in with a placeholder', function () {
    expect(TvBindingGuard::allowsValues(false, [], [], '', '', ['pagetitle' => '@EVALreturn 1;'], ['pagetitle' => 'old']))->toBeFalse()
        ->and(TvBindingGuard::allowsValues(false, [], [], '', '', ['pagetitle' => 'Hello'], ['pagetitle' => 'old']))->toBeTrue()
        ->and(TvBindingGuard::allowsValues(true, [], [], '', '', ['pagetitle' => '@EVAL 1;'], []))->toBeTrue();
});

it('refuses a code binding in a display parameter, not only at the start of the field', function (string $params) {
    expect(TvBindingGuard::allows(false, ['display_params' => $params]))->toBeFalse();
})->with([
    'custom_widget output' => '&output=@EVAL return 1;',
    'after other params' => '&format=x&output=@EVALreturn 1;',
    'encoded separators' => '&output=@EVAL $a %3D 1; return $a;',
    'leading space' => '&output= @eval return 1;',
    'include output' => '&output=@INCLUDE assets/images/x.jpg',
    'select output' => '&output=@SELECT 1',
]);

it('refuses @INCLUDE, which includes any file as PHP, in a TV definition', function (string $field) {
    expect(TvBindingGuard::allows(false, [$field => '@INCLUDE assets/images/shell.jpg']))->toBeFalse()
        ->and(TvBindingGuard::allows(true, [$field => '@INCLUDE assets/tvs/x/x.php']))->toBeTrue();
})->with(['elements', 'display_params']);

it('keeps harmless display parameters and a stored code parameter editable around', function () {
    $stored = ['display_params' => '&output=@EVAL return 1;'];

    expect(TvBindingGuard::allows(false, ['display_params' => '&output=@CHUNK widget&format=Y-m-d']))->toBeTrue()
        ->and(TvBindingGuard::allows(false, ['display_params' => '&text=mail@EVALUATE.com']))->toBeTrue()
        ->and(TvBindingGuard::allows(false, ['display_params' => '&text=write me@eval']))->toBeTrue()
        ->and(TvBindingGuard::allows(false, ['elements' => 'Yes==@EVAL'], []))->toBeTrue()
        ->and(TvBindingGuard::allows(false, $stored + ['elements' => 'a'], $stored))->toBeTrue()
        ->and(TvBindingGuard::allows(false, ['display_params' => '&output=@EVAL system("id");'], $stored))->toBeFalse();
});
