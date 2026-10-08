<?php

namespace EvolutionCMS\Support;

/**
 * Keeps the TV bindings that run code out of the hands of managers who may edit TVs but not PHP.
 *
 * @EVAL passes its argument to eval() and @SELECT puts it into a query, in a TV's input options,
 * default value and output options. save_template alone is enough to store them, so they need the
 * permission that already grants arbitrary PHP (save_snippet).
 */
final class TvBindingGuard
{
    /** Definition fields (POST name => column) a binding can sit in. */
    public const FIELDS = ['elements' => 'elements', 'default_text' => 'default_text', 'display_params' => 'display_params'];

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
            if (self::hasCodeBinding($value) && $value !== (string) ($stored[$column] ?? '')) {
                return false;
            }
        }

        return true;
    }
}
