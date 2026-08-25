<?php namespace EvolutionCMS\Support;

/**
 * Evaluate the arithmetic/boolean expressions that the template modifiers used to hand to eval().
 *
 * The `math`/`calc` modifiers accept a small expression built from the filtered modifier argument
 * with the tag value substituted for `?`. Historically that string went straight into
 * `eval("return {$expr};")`. The callers strip letters and whitespace first, which stops a template
 * from naming a function - but it leaves `$`, quotes, backslashes and `;` in place, and those are
 * enough to build a working payload out of octal string escapes alone.
 *
 * This evaluator accepts only the tokens arithmetic actually needs and rejects everything else, so
 * there is no path from a template into PHP any more. Operators are applied with PHP's own
 * semantics (int/float promotion, `/` returning int on an exact division, `%` casting to int), so a
 * template that produced a value before produces the same value now.
 *
 * @since 3.5.8
 */
final class ArithmeticExpression
{
    /**
     * Longest match first: `<=` must win over `<`, `===` over `==` over `=`.
     */
    private const OPERATORS = [
        '===', '!==', '<=>',
        '==', '!=', '<>', '<=', '>=', '&&', '||',
        '+', '-', '*', '/', '%', '<', '>', '!',
    ];

    /**
     * Binary operator precedence, mirroring PHP's own table. Higher binds tighter.
     */
    private const PRECEDENCE = [
        '*' => 60, '/' => 60, '%' => 60,
        '+' => 50, '-' => 50,
        '<' => 40, '<=' => 40, '>' => 40, '>=' => 40, '<=>' => 40,
        '==' => 30, '!=' => 30, '<>' => 30, '===' => 30, '!==' => 30,
        '&&' => 20,
        '||' => 10,
    ];

    /**
     * Unary operators, all right-associative and binding tighter than any binary operator.
     */
    private const UNARY = ['u+' => 70, 'u-' => 70, '!' => 70];

    /**
     * Bounds that keep a hostile expression from costing more than it is worth. Real modifier
     * arguments are a handful of characters; these are orders of magnitude above anything genuine.
     */
    private const MAX_LENGTH = 512;
    private const MAX_TOKENS = 256;
    private const MAX_DEPTH = 32;

    /**
     * Evaluate an expression, falling back to $default when it is not one we accept.
     *
     * @param mixed $expression
     * @param mixed $default
     * @return mixed
     */
    public static function evaluate($expression, $default = 0)
    {
        $result = self::tryEvaluate($expression);

        return $result === null ? $default : $result;
    }

    /**
     * Evaluate an expression, returning null when it is not one we accept.
     *
     * @param mixed $expression
     * @return int|float|bool|null
     */
    public static function tryEvaluate($expression)
    {
        if (!is_scalar($expression)) {
            return null;
        }

        $expression = trim((string) $expression);
        if ($expression === '' || strlen($expression) > self::MAX_LENGTH) {
            return null;
        }

        $tokens = self::tokenize($expression);
        if ($tokens === null) {
            return null;
        }

        $rpn = self::toReversePolish($tokens);
        if ($rpn === null) {
            return null;
        }

        return self::evaluateReversePolish($rpn);
    }

    /**
     * Split the expression into number literals, operators and parentheses.
     *
     * Unary `+`/`-`/`!` are distinguished from their binary forms here, while we still know whether
     * the previous token closed an operand.
     *
     * @param string $expression
     * @return array<int, array{0: string, 1: mixed}>|null
     */
    private static function tokenize($expression)
    {
        $tokens = [];
        $length = strlen($expression);
        $offset = 0;
        // False directly after an operand (a number or a closing paren), which is the only position
        // where `+`/`-` are binary.
        $expectOperand = true;

        while ($offset < $length) {
            if (count($tokens) > self::MAX_TOKENS) {
                return null;
            }

            $char = $expression[$offset];

            if ($char === ' ' || $char === "\t" || $char === "\n" || $char === "\r") {
                $offset++;
                continue;
            }

            if ($char === '(') {
                if (!$expectOperand) {
                    // `2(3)` was never valid PHP either.
                    return null;
                }
                $tokens[] = ['(', null];
                $offset++;
                continue;
            }

            if ($char === ')') {
                if ($expectOperand) {
                    return null;
                }
                $tokens[] = [')', null];
                $offset++;
                $expectOperand = false;
                continue;
            }

            if (self::isDigit($char) || ($char === '.' && isset($expression[$offset + 1]) && self::isDigit($expression[$offset + 1]))) {
                if (!$expectOperand) {
                    return null;
                }
                $number = self::readNumber($expression, $offset);
                if ($number === null) {
                    return null;
                }
                $tokens[] = ['num', $number];
                $expectOperand = false;
                continue;
            }

            $operator = self::readOperator($expression, $offset);
            if ($operator === null) {
                return null;
            }

            if ($expectOperand) {
                // Only `+`, `-` and `!` have a unary form; anything else here is a syntax error.
                if ($operator === '+' || $operator === '-') {
                    $tokens[] = ['op', 'u' . $operator];
                    continue;
                }
                if ($operator === '!') {
                    $tokens[] = ['op', '!'];
                    continue;
                }

                return null;
            }

            if (!isset(self::PRECEDENCE[$operator])) {
                // `!` cannot be binary.
                return null;
            }

            $tokens[] = ['op', $operator];
            $expectOperand = true;
        }

        if ($expectOperand || $tokens === []) {
            // A trailing operator, or nothing at all.
            return null;
        }

        return $tokens;
    }

    /**
     * @param string $char
     * @return bool
     */
    private static function isDigit($char)
    {
        return $char >= '0' && $char <= '9';
    }

    /**
     * Read one decimal literal, advancing $offset past it.
     *
     * Exponents are deliberately unsupported: the callers strip `e` before we ever see the string,
     * so accepting them here would only invent a syntax that never worked.
     *
     * @param string $expression
     * @param int $offset
     * @return int|float|null
     */
    private static function readNumber($expression, &$offset)
    {
        $start = $offset;
        $length = strlen($expression);
        $seenDot = false;

        while ($offset < $length) {
            $char = $expression[$offset];
            if (self::isDigit($char)) {
                $offset++;
                continue;
            }
            if ($char === '.' && !$seenDot) {
                $seenDot = true;
                $offset++;
                continue;
            }
            break;
        }

        $literal = substr($expression, $start, $offset - $start);
        if ($literal === '' || $literal === '.') {
            return null;
        }

        if (!$seenDot && ctype_digit($literal)) {
            // Stay on int while the value fits, so `2*3` keeps returning int(6) as eval() did.
            $asInt = (int) $literal;
            if ((string) $asInt === ltrim($literal, '0') || $literal === '0' || ltrim($literal, '0') === '') {
                return $asInt;
            }

            return (float) $literal;
        }

        return (float) $literal;
    }

    /**
     * Read one operator, advancing $offset past it.
     *
     * @param string $expression
     * @param int $offset
     * @return string|null
     */
    private static function readOperator($expression, &$offset)
    {
        foreach (self::OPERATORS as $operator) {
            if (substr($expression, $offset, strlen($operator)) === $operator) {
                $offset += strlen($operator);

                return $operator;
            }
        }

        return null;
    }

    /**
     * Shunting-yard: infix tokens to reverse polish notation.
     *
     * @param array<int, array{0: string, 1: mixed}> $tokens
     * @return array<int, array{0: string, 1: mixed}>|null
     */
    private static function toReversePolish(array $tokens)
    {
        $output = [];
        $stack = [];

        foreach ($tokens as $token) {
            [$type, $value] = $token;

            if ($type === 'num') {
                $output[] = $token;
                continue;
            }

            if ($type === '(') {
                $stack[] = $token;
                if (count($stack) > self::MAX_DEPTH) {
                    return null;
                }
                continue;
            }

            if ($type === ')') {
                $matched = false;
                while ($stack !== []) {
                    $top = array_pop($stack);
                    if ($top[0] === '(') {
                        $matched = true;
                        break;
                    }
                    $output[] = $top;
                }
                if (!$matched) {
                    return null;
                }
                continue;
            }

            $isUnary = isset(self::UNARY[$value]);
            $precedence = $isUnary ? self::UNARY[$value] : self::PRECEDENCE[$value];

            while ($stack !== []) {
                $top = end($stack);
                if ($top[0] !== 'op') {
                    break;
                }
                $topIsUnary = isset(self::UNARY[$top[1]]);
                $topPrecedence = $topIsUnary ? self::UNARY[$top[1]] : self::PRECEDENCE[$top[1]];

                // Unary operators are right-associative, so an equal precedence does not pop.
                if ($topPrecedence > $precedence || ($topPrecedence === $precedence && !$isUnary)) {
                    $output[] = array_pop($stack);
                    continue;
                }
                break;
            }

            $stack[] = $token;
            if (count($stack) > self::MAX_DEPTH) {
                return null;
            }
        }

        while ($stack !== []) {
            $top = array_pop($stack);
            if ($top[0] === '(') {
                return null;
            }
            $output[] = $top;
        }

        return $output;
    }

    /**
     * @param array<int, array{0: string, 1: mixed}> $rpn
     * @return int|float|bool|null
     */
    private static function evaluateReversePolish(array $rpn)
    {
        $stack = [];

        foreach ($rpn as $token) {
            [$type, $value] = $token;

            if ($type === 'num') {
                $stack[] = $value;
                continue;
            }

            if (isset(self::UNARY[$value])) {
                if ($stack === []) {
                    return null;
                }
                $operand = array_pop($stack);
                switch ($value) {
                    case 'u-':
                        $stack[] = -$operand;
                        break;
                    case 'u+':
                        $stack[] = +$operand;
                        break;
                    default:
                        $stack[] = !$operand;
                }
                continue;
            }

            if (count($stack) < 2) {
                return null;
            }
            $right = array_pop($stack);
            $left = array_pop($stack);

            $result = self::apply($value, $left, $right);
            if ($result === null) {
                return null;
            }
            $stack[] = $result;
        }

        if (count($stack) !== 1) {
            return null;
        }

        return $stack[0];
    }

    /**
     * Apply one binary operator using PHP's own semantics.
     *
     * @param string $operator
     * @param int|float|bool $left
     * @param int|float|bool $right
     * @return int|float|bool|null
     */
    private static function apply($operator, $left, $right)
    {
        switch ($operator) {
            case '+':
                return $left + $right;
            case '-':
                return $left - $right;
            case '*':
                return $left * $right;
            case '/':
                // eval() raised DivisionByZeroError here; reporting "not evaluable" lets the caller
                // fall back to its default instead of taking the request down.
                if ((float) $right === 0.0) {
                    return null;
                }

                return $left / $right;
            case '%':
                if ((int) $right === 0) {
                    return null;
                }

                return (int) $left % (int) $right;
            case '<':
                return $left < $right;
            case '<=':
                return $left <= $right;
            case '>':
                return $left > $right;
            case '>=':
                return $left >= $right;
            case '<=>':
                return $left <=> $right;
            case '==':
                return $left == $right;
            case '===':
                return $left === $right;
            case '!=':
            case '<>':
                return $left != $right;
            case '!==':
                return $left !== $right;
            case '&&':
                return (bool) $left && (bool) $right;
            case '||':
                return (bool) $left || (bool) $right;
        }

        return null;
    }
}
