<?php

namespace EvolutionCMS\Support;

/**
 * Keeps the TV bindings that run code out of the hands of managers who may edit TVs but not PHP.
 *
 * @EVAL passes its argument to eval() and @SELECT puts it into a query, in a TV's input options,
 * default value and output options; @INCLUDE in a custom input or a custom_widget output includes a
 * file as PHP. save_template alone is enough to store them, so they need the
 * permission that already grants arbitrary PHP (save_snippet).
 */
final class TvBindingGuard
{
    /** Definition fields (POST name => column) a binding can sit in. */
    public const FIELDS = ['elements' => 'elements', 'default_text' => 'default_text', 'display_params' => 'display_params'];

    /** Bindings that run code: @EVAL passes its argument to eval(), @SELECT to the database. */
    public const CODE_BINDINGS = ['EVAL', 'SELECT'];

    /**
     * Bindings a TV definition runs as code on top of CODE_BINDINGS: a custom_tv input and the
     * custom_widget output include the file @INCLUDE names, and include runs any file as PHP -
     * an uploaded image as well as a script.
     */
    public const DEFINITION_CODE_BINDINGS = ['EVAL', 'SELECT', 'INCLUDE'];

    /**
     * Bindings that hand back content stored somewhere else - a chunk, a file, a page, a directory
     * listing. Whoever may write that content need not be allowed to write PHP.
     */
    public const CONTENT_BINDINGS = ['FILE', 'CHUNK', 'DOCUMENT', 'DIRECTORY'];

    /** @var array<string, true> refusals already written to the event log in this request */
    private static array $loggedRefusals = [];

    public static function runsCode(?string $command): bool
    {
        return in_array(strtoupper(trim((string) $command)), self::CODE_BINDINGS, true);
    }

    /**
     * Whether the binding found in the output of $command may still run code. ProcessTVCommand()
     * hands a binding's output back to itself, so a chunk, file or page that starts with @EVAL
     * would otherwise run as PHP although only its author's chunk, file or page permission was
     * checked. Once the chain has passed through content, it stays content.
     */
    public static function nestedMayRunCode(?string $command, bool $mayRunCode): bool
    {
        return $mayRunCode && !in_array(strtoupper(trim((string) $command)), self::CONTENT_BINDINGS, true);
    }

    /**
     * Refuse a code binding that a content binding produced: log it once per request for the
     * administrators and render nothing.
     */
    public static function refuseNestedCode(?string $tvName, ?string $command): string
    {
        $key = (string) $tvName . '|' . strtoupper((string) $command);
        if (!isset(self::$loggedRefusals[$key]) && function_exists('evo')) {
            self::$loggedRefusals[$key] = true;
            try {
                evo()->logEvent(0, 2, sprintf(
                    'Template variable "%s": an @%s binding inside a chunk, file or document was not run. '
                    . 'Put code bindings into the template variable itself.',
                    htmlspecialchars((string) $tvName, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars(strtoupper((string) $command), ENT_QUOTES, 'UTF-8')
                ), 'TV binding');
            } catch (\Throwable $e) {
                // the refusal stands even when the event log cannot be written
            }
        }

        return '';
    }

    public static function hasCodeBinding(?string $value): bool
    {
        $value = (string) $value;
        // the parser trims with trim(), which also drops NUL and vertical tab, and matches a command
        // by prefix ("@EVALreturn 1;" runs), so no boundary may be required after the name.
        // @INHERIT hands its argument back to the parser, so it is looked through.
        for ($depth = 0; $depth < 20; $depth++) {
            $value = ltrim($value, " \t\n\r\0\x0B");
            if (preg_match('/^@{1,2}(?:EVAL|SELECT)/i', $value)) {
                return true;
            }
            if (!preg_match('/^@INHERIT/i', $value)) {
                return false;
            }
            $value = substr($value, 8);
        }

        return true; // deeper than any real value nests: do not guess
    }

    /**
     * Whether a TV definition field holds a binding that runs code. Display parameters are a
     * "&name=value" list whose values are bindings of their own (custom_widget runs
     * "&output=@EVAL ..."), so each value is checked, not only the start of the field.
     */
    public static function definitionHasCodeBinding(?string $value, string $column = ''): bool
    {
        $value = (string) $value;
        $candidates = [$value];
        if ($column === 'display_params') {
            foreach (explode('&', $value) as $pair) {
                $pos = strpos($pair, '=');
                if ($pos !== false) {
                    // decoded the way getTVDisplayFormat() decodes it
                    $candidates[] = str_replace(['%3D', '%26'], ['=', '&'], substr($pair, $pos + 1));
                }
            }
        }
        foreach ($candidates as $candidate) {
            if (self::hasCodeBinding($candidate) || preg_match('/^@{1,2}INCLUDE/i', ltrim($candidate, " \t\n\r\0\x0B"))) {
                return true;
            }
        }

        return false;
    }

    /**
     * A TV value or resource field that can reach a code binding. Bindings nest (@INHERIT hands
     * its argument back to the binding parser), so one is also caught behind another binding.
     */
    public static function reachesCodeBinding(?string $value): bool
    {
        return self::hasCodeBinding($value);
    }

    /**
     * Whether a manager may store these TV values and the resource content. Values that did not
     * change are kept, so an existing binding stays editable around by whoever may edit the page.
     *
     * @param array<int|string, string|null> $desired TV id => submitted value
     * @param array<int|string, string|null> $stored  TV id => current value
     */
    public static function allowsValues(bool $mayWritePhp, array $desired, array $stored, string $content = '', string $storedContent = '', array $fields = [], array $storedFields = []): bool
    {
        if ($mayWritePhp) {
            return true;
        }
        // content only runs as a binding when a TV pulls it in with @DOCUMENT, and then from its start
        if ($content !== $storedContent && self::hasCodeBinding($content)) {
            return false;
        }
        // [*field*] in a binding's argument pulls in any other field of the page, so each is checked
        foreach ($fields as $name => $value) {
            $value = (string) $value;
            if (self::hasCodeBinding($value) && $value !== (string) ($storedFields[$name] ?? '')) {
                return false;
            }
        }
        foreach ($desired as $id => $value) {
            $value = (string) $value;
            if (self::reachesCodeBinding($value) && $value !== (string) ($stored[$id] ?? '')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $submitted values keyed by column
     * @param array<string, mixed> $stored    current values keyed by column, empty for a new TV
     */
    public static function allows(bool $mayWritePhp, array $submitted, array $stored = []): bool
    {
        if ($mayWritePhp) {
            return true;
        }
        foreach (self::FIELDS as $column) {
            $value = (string) ($submitted[$column] ?? '');
            if (self::definitionHasCodeBinding($value, $column) && $value !== (string) ($stored[$column] ?? '')) {
                return false;
            }
        }

        return true;
    }
}
