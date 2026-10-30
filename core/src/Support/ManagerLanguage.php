<?php

namespace EvolutionCMS\Support;

/**
 * The manager languages installed under `core/lang`.
 *
 * Every locale but English may be deleted from a site. A manager or system setting that
 * still names a deleted locale is kept as it is (the preference returns with the locale),
 * but reads as English wherever the language is used or offered.
 *
 * A deleted locale is the rare case: per request the lexicon is included without asking
 * the filesystem first (`isName()`), and only a failed include falls back to English.
 * `exists()`, `resolve()` and `available()` touch the disk and belong to the pages and
 * cache builds that offer or store a language.
 */
final class ManagerLanguage
{
    public const DEFAULT = 'en';

    /**
     * The installed locales, sorted.
     *
     * @return list<string>
     * @since 3.5.9
     */
    public static function available(?string $langPath = null): array
    {
        $dir = self::path($langPath);
        $locales = [];
        foreach (is_dir($dir) ? (scandir($dir) ?: []) : [] as $entry) {
            if (self::exists($entry, $langPath)) {
                $locales[] = $entry;
            }
        }
        sort($locales, SORT_STRING);

        return $locales;
    }

    /**
     * Whether the value names an installed locale.
     *
     * @since 3.5.9
     */
    public static function exists(mixed $language, ?string $langPath = null): bool
    {
        return self::isName($language) && is_file(self::path($langPath) . $language . '/global.php');
    }

    /**
     * Whether the value may name a locale folder, without touching the filesystem.
     *
     * @since 3.5.9
     */
    public static function isName(mixed $language): bool
    {
        // a locale name only: the value names a file to include
        return is_string($language) && preg_match('/^[A-Za-z0-9_-]+$/', $language) === 1;
    }

    /**
     * The given locale when it is installed, English otherwise.
     *
     * @since 3.5.9
     */
    public static function resolve(mixed $language, ?string $langPath = null): string
    {
        return self::exists($language, $langPath) ? $language : self::DEFAULT;
    }

    private static function path(?string $langPath): string
    {
        $langPath ??= defined('EVO_CORE_PATH') ? EVO_CORE_PATH . 'lang' : dirname(__DIR__, 2) . '/lang';

        return rtrim($langPath, '/\\') . '/';
    }
}
