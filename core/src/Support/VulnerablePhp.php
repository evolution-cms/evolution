<?php namespace EvolutionCMS\Support;

/**
 * Composer refusing to work because roave/security-advisories conflicts with the PHP version Composer uses.
 *
 * The core requires roave/security-advisories, which conflicts with every PHP release that has a
 * known critical vulnerability. On such a PHP every Composer run that resolves the lock fails, and
 * that is intended. The reported version may come from Composer's platform configuration
 * instead of the PHP executable. This turns the refusal into an actionable diagnostic.
 *
 * @since 3.5.10
 */
final class VulnerablePhp
{
    /** error_code of a system task that failed for this reason. */
    public const ERROR_CODE = 'PHP_VULNERABLE';

    private const PACKAGE = 'roave/security-advisories';

    /**
     * Read the refusal from Composer output.
     *
     * @param string $output Composer output, or output of a command that printed it.
     * @return array{version: string, fixed: string}|null null when the output holds no such refusal;
     *         version is the effective PHP from the diagnostic (or the local PHP as a fallback), fixed the first release of its line without the conflict ('' when unknown).
     */
    public static function fromComposerOutput(string $output): ?array
    {
        // "roave/security-advisories dev-master conflicts with php <8.2.36|>=8.4,<8.4.28."
        // or, when Composer names the version instead, "... conflicts with php[8.4.27]."
        if (!preg_match('~' . preg_quote(self::PACKAGE, '~') . '\S*\s+\S+\s+conflicts with php(?:\[([^\]\s]+)\]|\s+([^\s.]\S*?))\.?\s*$~mi', $output, $conflict)) {
            return null;
        }

        $constraint = $conflict[2] ?? '';
        $version = $conflict[1] ?? '';
        if (preg_match('~^\s*-?\s*php is present at version (\S+)~mi', $output, $present)) {
            $version = $present[1];
        } elseif ($version === '' && preg_match('~^\d+\.\d+\.\d+$~', $constraint)) {
            $version = $constraint;
        }
        if ($version === '') {
            $version = PHP_VERSION;
        }

        return [
            'version' => $version,
            'fixed' => self::fixedVersion($constraint, $version),
        ];
    }

    /**
     * The upper bound of the conflict range that holds $version: its first release without the conflict.
     *
     * @param string $constraint The conflict, such as "<8.2.36|>=8.3,<8.3.37|>=8.4,<8.4.28".
     * @param string $version The PHP version Composer evaluated.
     * @return string '' when no range with an upper bound holds it.
     */
    public static function fixedVersion(string $constraint, string $version): string
    {
        $version = preg_replace('~[^0-9.].*$~', '', $version);
        if ($version === '') {
            return '';
        }

        foreach (preg_split('~\s*\|\|?\s*~', trim($constraint)) ?: [] as $range) {
            $lower = null;
            $upper = null;
            foreach (preg_split('~\s*,\s*|\s+~', trim($range)) ?: [] as $bound) {
                if (preg_match('~^>=\s*(\d[\d.]*)$~', $bound, $match)) {
                    $lower = $match[1];
                } elseif (preg_match('~^<\s*(\d[\d.]*)$~', $bound, $match)) {
                    $upper = $match[1];
                }
            }
            if ($upper !== null && version_compare($version, $upper, '<')
                && ($lower === null || version_compare($version, $lower, '>='))) {
                return $upper;
            }
        }

        return '';
    }

    /**
     * What to tell the operator.
     *
     * @param array{version?: string, fixed?: string} $details From fromComposerOutput().
     * @param array<string, string>|null $lang A lexicon to take the texts from (the installer's), English for a key it lacks;
     *        the manager lexicon of the current locale when null.
     * @return string
     */
    public static function message(array $details, ?array $lang = null): string
    {
        $fixed = (string) ($details['fixed'] ?? '');
        $key = $fixed !== '' ? 'php_vulnerable_composer' : 'php_vulnerable_composer_no_fix';
        $replace = [
            ':version' => (string) ($details['version'] ?? PHP_VERSION),
            ':fixed' => $fixed,
        ];

        $text = $lang[$key] ?? null;
        if ($lang === null && function_exists('__')) {
            try {
                $translated = __('global.' . $key);
                $text = is_string($translated) && $translated !== 'global.' . $key ? $translated : null;
            } catch (\Throwable $exception) {
                $text = null;
            }
        }
        $text ??= $fixed !== ''
            ? 'Composer refused to change packages: roave/security-advisories blocks its effective PHP version (:version). Check the PHP executable Composer uses and any config.platform.php override. Use PHP :fixed or newer, then retry.'
            : 'Composer refused to change packages: roave/security-advisories blocks its effective PHP version (:version). Check the PHP executable Composer uses and any config.platform.php override. Use a supported PHP release without this conflict, then retry.';

        return strtr($text, $replace);
    }
}
