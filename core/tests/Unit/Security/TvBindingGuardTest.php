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
