<?php

use EvolutionCMS\Support\ArithmeticExpression;

/*
|--------------------------------------------------------------------------
| ArithmeticExpression
|--------------------------------------------------------------------------
|
| Replaces the eval() that backed the `math`/`calc` modifiers. Two things have to hold: every
| expression a template could produce before still produces the same value, and nothing that
| survived the callers' letter strip can reach PHP any more.
|
*/

describe('compatibility with the eval() it replaces', function () {

    // Expressions in the shape `math` receives after its caller has stripped letters and
    // whitespace and substituted the tag value for `?`. Each is evaluated both ways and the
    // results are compared, so this is a differential test rather than a table of expected values.
    test('evaluates exactly as eval() did', function (string $expression) {
        $expected = eval("return {$expression};");

        expect(ArithmeticExpression::evaluate($expression))->toBe($expected);
    })->with([
        '1+1',
        '2*3',
        '10-4',
        '7/2',
        '6/3',
        '10%3',
        '2+3*4',
        '(2+3)*4',
        '((1+2)*(3+4))',
        '-5+3',
        '+5-3',
        '2*-3',
        '1.5+2.25',
        '0.1*3',
        '100/8',
        '1<2',
        '2<=2',
        '3>4',
        '4>=4',
        '1==1',
        '1!=2',
        '1&&0',
        '1||0',
        '!0',
        '!1',
        '2+3>4',
        '1&&1||0',
        '10-2-3',
        '100/10/2',
        '2*3%4',
        '0',
        '42',
        '-0',
    ]);
});

describe('operator handling', function () {

    test('left associativity is preserved for subtraction and division', function () {
        expect(ArithmeticExpression::evaluate('10-2-3'))->toBe(5)
            ->and(ArithmeticExpression::evaluate('100/10/2'))->toBe(5);
    });

    test('unary minus binds tighter than multiplication but not than parentheses', function () {
        expect(ArithmeticExpression::evaluate('-2*3'))->toBe(-6)
            ->and(ArithmeticExpression::evaluate('-(2*3)'))->toBe(-6)
            ->and(ArithmeticExpression::evaluate('2--3'))->toBe(5);
    });

    test('integer arithmetic stays integer', function () {
        expect(ArithmeticExpression::evaluate('2*3'))->toBeInt()
            ->and(ArithmeticExpression::evaluate('6/3'))->toBeInt()
            ->and(ArithmeticExpression::evaluate('7/2'))->toBeFloat();
    });

    test('division and modulo by zero fall back instead of raising', function () {
        // eval() raised DivisionByZeroError here, which took the whole request down.
        expect(ArithmeticExpression::evaluate('1/0'))->toBe(0)
            ->and(ArithmeticExpression::evaluate('1%0'))->toBe(0)
            ->and(ArithmeticExpression::evaluate('1/0', 'n/a'))->toBe('n/a');
    });
});

describe('rejects everything that is not arithmetic', function () {

    // Every payload below survives `preg_replace('@([a-zA-Z\n\r\t\s])@', '', $filter)` - the filter
    // the callers apply before handing the string over - because it contains no letters at all.
    $payloads = [
        'octal escaped system() call' => '"\163\171\163\164\145\155"("\151\144")',
        'octal escaped phpinfo' => '"\160\150\160\151\156\146\157"()',
        'backtick shell operator' => '1 . `\151\144`',
        'variable variable' => '${"\137\107\105\124"}',
        'statement separator' => '1;print_r($_SERVER)',
        'superglobal read' => '$_SERVER',
        'string concatenation' => '"1"."2"',
        'array literal' => '[1,2][0]',
        'xor built string' => '("\1"^"\1")',
        'heredoc-ish quoting' => '"1"',
        'bare quote' => "'",
        'backslash' => '\\',
        'dollar' => '$',
        'braces' => '{1}',
        'closing paren only' => ')',
        'opening paren only' => '(',
        'unbalanced parens' => '(1+2',
        'trailing operator' => '1+',
        'leading binary operator' => '*2',
        'empty' => '',
        'two numbers' => '1 2',
        'implicit multiplication' => '2(3)',
    ];

    test('refuses the payload', function (string $payload) {
        expect(ArithmeticExpression::tryEvaluate($payload))->toBeNull()
            ->and(ArithmeticExpression::evaluate($payload))->toBe(0);
    })->with($payloads);

    test('no payload reaches PHP even when it would be valid PHP', function () {
        // If any of these were still evaluated the marker file would exist afterwards.
        $marker = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'evo_arith_' . bin2hex(random_bytes(6));

        // file_put_contents("<marker>", "x") spelled without a single letter.
        $call = '"\146\151\154\145\137\160\165\164\137\143\157\156\164\145\156\164\163"("'
            . addcslashes($marker, "\\\"")
            . '","\170")';

        ArithmeticExpression::evaluate($call);

        expect(file_exists($marker))->toBeFalse();
    });
});

describe('bounds', function () {

    test('an over-long expression is refused rather than parsed', function () {
        $long = str_repeat('1+', 400) . '1';

        expect(ArithmeticExpression::tryEvaluate($long))->toBeNull();
    });

    test('deeply nested parentheses are refused rather than recursed', function () {
        $nested = str_repeat('(', 100) . '1' . str_repeat(')', 100);

        expect(ArithmeticExpression::tryEvaluate($nested))->toBeNull();
    });

    test('a nesting depth a template might really use still works', function () {
        expect(ArithmeticExpression::evaluate('((((1+2))))'))->toBe(3);
    });

    test('non-scalar input is refused', function () {
        expect(ArithmeticExpression::tryEvaluate([1, 2]))->toBeNull()
            ->and(ArithmeticExpression::tryEvaluate(null))->toBeNull();
    });
});
