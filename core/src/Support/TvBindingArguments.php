<?php

namespace EvolutionCMS\Support;

/**
 * Hands [*field*] values to @SELECT and @EVAL bindings as data.
 *
 * parseTvValues() replaces [*field*] by the field's text before the binding runs. For @SELECT and
 * @EVAL that text became part of the SQL statement or the PHP code, so whoever may edit the page
 * field (pagetitle, a TV value ...) could change a query or code an administrator wrote. Here a
 * placeholder becomes a bound parameter (SQL) or a variable read (PHP) instead:
 *
 *   WHERE alias = '[*alias*]'            ->  WHERE alias = ?          bound: the alias
 *   LIKE '%[*pagetitle*]%'               ->  LIKE ?                   bound: '%' . title . '%'
 *   WHERE parent = [*id*]                ->  WHERE parent = 5         only when the value is an integer
 *   return 'Hi [*pagetitle*]!';          ->  return ('Hi ' . (string) $tvFields['pagetitle'] . '!');
 *
 * A placeholder of a known field anywhere else - an SQL identifier, plain SQL with a non-integer
 * value, plain PHP code, a nowdoc - is refused, because there its value would be code. Placeholders
 * of unknown fields are left as they are, as parseTvValues() does.
 */
final class TvBindingArguments
{
    /** Name of the variable an @EVAL binding reads the field values from. */
    public const PHP_VARIABLE = 'tvFields';

    private const TAG = '/\[\*(.+?)\*\]/s';

    /** @var array<string, true> refusals already written to the event log in this request */
    private static array $loggedRefusals = [];

    /**
     * The field values a placeholder may name: the given TV rows or values over the current
     * document's fields, resolved the way parseTvValues() resolves them.
     *
     * @param array<string, mixed> $tvsArray
     * @param mixed $documentObject
     * @return array<string, string>
     */
    public static function fields(array $tvsArray, $documentObject = null): array
    {
        if (is_array($documentObject)) {
            $tvsArray = array_merge($tvsArray, $documentObject);
        }

        $fields = [];
        foreach ($tvsArray as $name => $value) {
            if (is_array($value)) {
                $value = (string) ($value['value'] ?? '') === '' ? ($value['default_text'] ?? '') : $value['value'];
            }
            if (is_scalar($value) || $value === null) {
                $fields[(string) $name] = (string) $value;
            }
        }

        return $fields;
    }

    /**
     * Rewrite the argument of an @SELECT binding into a statement with bound parameters.
     *
     * @param array<string, string> $fields
     * @return array{0: string, 1: array<int, string>}|null null when a placeholder would be code
     */
    public static function sql(string $sql, array $fields, string $driver = 'mysql'): ?array
    {
        $mysql = !in_array($driver, ['pgsql', 'sqlite', 'sqlite3', 'sqlsrv'], true);
        $out = '';
        $bindings = [];
        $length = strlen($sql);
        $code = '';

        // plain SQL takes a value only when it is an integer, written out as one
        $flushCode = static function () use (&$code, &$out, $fields): bool {
            if ($code === '') {
                return true;
            }
            $ok = true;
            $code = preg_replace_callback(self::TAG, static function ($m) use ($fields, &$ok) {
                if (!array_key_exists($m[1], $fields)) {
                    return $m[0];
                }
                if (!preg_match('/^-?\d{1,18}$/D', $fields[$m[1]])) {
                    $ok = false;

                    return $m[0];
                }

                return (string) (int) $fields[$m[1]];
            }, $code);
            $out .= $code;
            $code = '';

            return $ok;
        };

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            // comments are copied as they are, placeholders in them included
            $commentEnd = null;
            if (($char === '-' && $next === '-') || ($char === '#' && $mysql)) {
                $commentEnd = strpos($sql, "\n", $i);
                $commentEnd = $commentEnd === false ? $length : $commentEnd;
            } elseif ($char === '/' && $next === '*') {
                $commentEnd = strpos($sql, '*/', $i + 2);
                $commentEnd = $commentEnd === false ? $length : $commentEnd + 2;
            }
            if ($commentEnd !== null) {
                if (!$flushCode()) {
                    return null;
                }
                $out .= substr($sql, $i, $commentEnd - $i);
                $i = $commentEnd - 1;
                continue;
            }

            $isString = $char === "'" || ($char === '"' && $mysql);
            $isIdentifier = $char === '`' || ($char === '"' && !$mysql)
                || ($char === '[' && $driver === 'sqlsrv' && $next !== '*');
            if (!$isString && !$isIdentifier) {
                $code .= $char;
                continue;
            }

            $close = $char === '[' ? ']' : $char;
            $j = $i + 1;
            $body = '';
            while ($j < $length) {
                $c = $sql[$j];
                if ($isString && $mysql && $c === '\\' && $j + 1 < $length) {
                    $body .= $c . $sql[$j + 1];
                    $j += 2;
                    continue;
                }
                if ($c === $close) {
                    if ($j + 1 < $length && $sql[$j + 1] === $close) {
                        $body .= $c . $c;
                        $j += 2;
                        continue;
                    }
                    break;
                }
                $body .= $c;
                $j++;
            }
            $literal = substr($sql, $i, $j - $i + 1);
            $i = $j;

            if (!self::containsKnownTag($body, $fields)) {
                $code .= $literal;
                continue;
            }
            if ($isIdentifier || $j >= $length) {
                // a name, or a string that never closes
                return null;
            }

            if (!$flushCode()) {
                return null;
            }
            $value = self::unescapeSqlString($body, $char, $mysql);
            $bindings[] = preg_replace_callback(self::TAG, static function ($m) use ($fields) {
                return array_key_exists($m[1], $fields) ? $fields[$m[1]] : $m[0];
            }, $value);
            $out .= '?';
        }

        if (!$flushCode()) {
            return null;
        }

        return [$out, $bindings];
    }

    /**
     * Prepare the argument of an @SELECT binding as ProcessTVCommand() runs it: bind the
     * placeholders first, then merge [+PREFIX+] and the other parser placeholders.
     *
     * The merge cannot come first: Core::mergePlaceholderContent() resolves [*field*] tags as well
     * (through mergeDocumentContent()) and would paste the values back into the SQL. For the same
     * reason a [*...*] tag left after binding - a modifier form such as [*pagetitle:upper*] - is
     * refused rather than merged.
     *
     * @param array<string, string> $fields
     * @param callable(string): string $mergePlaceholders
     * @return array{0: string, 1: array<int, string>}|null
     */
    public static function prepareSelect(string $sql, array $fields, string $driver, callable $mergePlaceholders): ?array
    {
        $prepared = self::sql($sql, $fields, $driver);
        if ($prepared === null || strpos($prepared[0], '[*') !== false) {
            return null;
        }
        $prepared[0] = (string) $mergePlaceholders($prepared[0]);

        return $prepared;
    }

    /**
     * Rewrite the argument of an @EVAL binding so that placeholders inside string literals read
     * the field values from $tvFields.
     *
     * @param array<string, string> $fields
     * @return string|null null when a placeholder would be code
     */
    public static function php(string $code, array $fields): ?string
    {
        if (!self::containsKnownTag($code, $fields)) {
            return $code;
        }

        $tokens = token_get_all('<?php ' . $code);
        array_shift($tokens);

        $out = '';
        $codeOnly = '';
        $inNowdoc = false;

        foreach ($tokens as $token) {
            if (!is_array($token)) {
                $out .= $token;
                $codeOnly .= $token;
                continue;
            }
            [$id, $text] = $token;

            switch ($id) {
                case T_START_HEREDOC:
                    $inNowdoc = strpos($text, "'") !== false;
                    $out .= $text;
                    break;
                case T_END_HEREDOC:
                    $inNowdoc = false;
                    $out .= $text;
                    break;
                case T_COMMENT:
                case T_DOC_COMMENT:
                    $out .= $text;
                    break;
                case T_CONSTANT_ENCAPSED_STRING:
                    $rewritten = self::phpLiteral($text, $fields);
                    if ($rewritten === null) {
                        return null;
                    }
                    $out .= $rewritten;
                    break;
                case T_ENCAPSED_AND_WHITESPACE:
                    if (!self::containsKnownTag($text, $fields)) {
                        $out .= $text;
                        break;
                    }
                    if ($inNowdoc) {
                        return null;
                    }
                    $rewritten = self::phpInterpolated($text, $fields);
                    if ($rewritten === null) {
                        return null;
                    }
                    $out .= $rewritten;
                    break;
                default:
                    $out .= $text;
                    $codeOnly .= $text;
            }
        }

        // a placeholder outside every string literal would have its value run as PHP
        if (self::containsKnownTag($codeOnly, $fields)) {
            return null;
        }

        return $out;
    }

    /**
     * Log a refused binding once per request for the administrators, and render nothing.
     */
    public static function refuse(?string $tvName, string $command): string
    {
        $key = (string) $tvName . '|' . $command;
        if (!isset(self::$loggedRefusals[$key]) && function_exists('evo')) {
            self::$loggedRefusals[$key] = true;
            try {
                evo()->logEvent(0, 2, sprintf(
                    'Template variable "%s": the @%s binding was not run, because a [*field*] placeholder '
                    . 'in it stands where its value would become %s. Put the placeholder inside a quoted string.',
                    htmlspecialchars((string) $tvName, ENT_QUOTES, 'UTF-8'),
                    $command,
                    $command === 'EVAL' ? 'PHP code' : 'part of the SQL statement'
                ), 'TV binding');
            } catch (\Throwable $e) {
                // the refusal stands even when the event log cannot be written
            }
        }

        return '';
    }

    /**
     * @param array<string, string> $fields
     */
    private static function containsKnownTag(string $text, array $fields): bool
    {
        if (strpos($text, '[*') === false || !preg_match_all(self::TAG, $text, $matches)) {
            return false;
        }
        foreach ($matches[1] as $name) {
            if (array_key_exists($name, $fields)) {
                return true;
            }
        }

        return false;
    }

    private static function unescapeSqlString(string $body, string $quote, bool $mysql): string
    {
        $body = str_replace($quote . $quote, $quote, $body);
        if (!$mysql) {
            return $body;
        }

        // MySQL keeps the backslash of \% and \_ for LIKE, and drops it before any other character
        return preg_replace_callback('/\\\\(.)/s', static function ($m) {
            $map = ['0' => "\0", 'n' => "\n", 'r' => "\r", 't' => "\t", 'Z' => "\x1A", 'b' => "\x08"];
            if ($m[1] === '%' || $m[1] === '_') {
                return $m[0];
            }

            return $map[$m[1]] ?? $m[1];
        }, $body);
    }

    /**
     * A plain '...' or "..." literal: split it around the placeholders and concatenate the pieces
     * with the field values.
     *
     * @param array<string, string> $fields
     */
    private static function phpLiteral(string $literal, array $fields): ?string
    {
        if (!self::containsKnownTag($literal, $fields)) {
            return $literal;
        }
        $quote = $literal[0];
        $body = substr($literal, 1, -1);

        $parts = [];
        $offset = 0;
        preg_match_all(self::TAG, $body, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($matches as $match) {
            [$tag, $position] = $match[0];
            $name = $match[1][0];
            if (!array_key_exists($name, $fields)) {
                continue;
            }
            $before = substr($body, $offset, $position - $offset);
            if (!self::endsOutsideEscape($before)) {
                return null;
            }
            if ($before !== '') {
                $parts[] = $quote . $before . $quote;
            }
            $parts[] = '(string) $' . self::PHP_VARIABLE . '[' . var_export($name, true) . ']';
            $offset = $position + strlen($tag);
        }
        $rest = substr($body, $offset);
        if ($rest !== '') {
            $parts[] = $quote . $rest . $quote;
        }

        return '(' . implode(' . ', $parts) . ')';
    }

    /**
     * Text inside an interpolated "..." string or a heredoc: read the value with {$tvFields[...]}.
     *
     * @param array<string, string> $fields
     */
    private static function phpInterpolated(string $text, array $fields): ?string
    {
        $out = '';
        $offset = 0;
        preg_match_all(self::TAG, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($matches as $match) {
            [$tag, $position] = $match[0];
            $name = $match[1][0];
            if (!array_key_exists($name, $fields)) {
                continue;
            }
            $before = substr($text, $offset, $position - $offset);
            // "$[*x*]" would turn into ${...}, a variable variable
            if (!self::endsOutsideEscape($before) || substr($before, -1) === '$') {
                return null;
            }
            $out .= $before . '{$' . self::PHP_VARIABLE . '[' . var_export($name, true) . ']}';
            $offset = $position + strlen($tag);
        }

        return $out . substr($text, $offset);
    }

    /** False when $text ends in an odd run of backslashes, which would escape what follows. */
    private static function endsOutsideEscape(string $text): bool
    {
        $run = strlen($text) - strlen(rtrim($text, '\\'));

        return $run % 2 === 0;
    }
}
