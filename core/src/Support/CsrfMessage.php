<?php declare(strict_types=1);

namespace EvolutionCMS\Support;

/**
 * What the manager says when its CSRF protection refuses a request.
 *
 * Every check uses it - the session token of the manager middleware, the file managers and the
 * updater, and the referer check of evo() - so the refusal reads the same, in the manager language,
 * whether it ends up on a page, in the alert of an XHR caller or in a JSON field.
 *
 * The text names no URL or path: the manager folder can be renamed to keep it from bots, and a
 * refusal must not tell a stranger where it is.
 *
 * The lexicon is read from the language files, not from ManagerTheme: the referer check runs inside
 * the first evo() call, before the theme exists, and resolving it there would call evo() again.
 *
 * @since 3.5.9
 */
final class CsrfMessage
{
    /** The session token is missing, expired or does not match. */
    public const TOKEN = 'csrf_token_mismatch';

    /** The referer is missing or belongs to another site (`validate_referer`). */
    public const REFERER = 'csrf_referer_mismatch';

    private const FALLBACK = [
        self::TOKEN => 'Security token expired. Reload the page and try again.',
        self::REFERER => 'Request blocked: the browser did not confirm it came from this site.',
    ];

    private const ERRORS = [
        self::TOKEN => 'CSRF token mismatch',
        self::REFERER => 'CSRF referer mismatch',
    ];

    /** @var array<string, array<string, mixed>> */
    private static array $lexicons = [];

    /**
     * The localized message. `$language` is a manager language code (`en`, `de`...); null asks the
     * current configuration, which already holds the user's own manager_language.
     */
    public static function text(string $key = self::TOKEN, ?string $language = null): string
    {
        $key = isset(self::FALLBACK[$key]) ? $key : self::TOKEN;
        $text = self::lexicon($language ?? self::managerLanguage())[$key] ?? null;
        if (!is_string($text) || trim($text) === '') {
            $text = self::FALLBACK[$key];
        }

        return $text;
    }

    /**
     * The JSON body of a refusal: `error` and `code` stay machine-readable for existing callers,
     * `message` is what to show.
     *
     * @return array{error: string, code: string, message: string}
     */
    public static function payload(string $key = self::TOKEN, ?string $language = null): array
    {
        $key = isset(self::FALLBACK[$key]) ? $key : self::TOKEN;

        return [
            'error' => self::ERRORS[$key],
            'code' => $key,
            'message' => self::text($key, $language),
        ];
    }

    /**
     * Whether the caller is a script that shows the response body itself: an XHR, a JSON client,
     * or a legacy form that posts `ajax=1` (the login form) and alerts whatever comes back.
     *
     * @param array<string, mixed> $server A `$_SERVER`-shaped array.
     * @param array<string, mixed> $post
     */
    public static function wantsScriptResponse(array $server, array $post = []): bool
    {
        return strcasecmp((string)($server['HTTP_X_REQUESTED_WITH'] ?? ''), 'XMLHttpRequest') === 0
            || stripos((string)($server['HTTP_ACCEPT'] ?? ''), 'json') !== false
            || (is_scalar($post['ajax'] ?? null) && (string)$post['ajax'] === '1');
    }

    /** @return array<string, mixed> */
    private static function lexicon(string $language): array
    {
        // a language code only: the value names a file to include
        if (!preg_match('/^[a-z]{2,3}(?:[-_][a-z0-9]{2,8})?$/i', $language)) {
            $language = 'en';
        }
        if (isset(self::$lexicons[$language])) {
            return self::$lexicons[$language];
        }

        $dir = dirname(__DIR__, 2) . '/lang/';
        $lexicon = self::read($dir . 'en/global.php');
        if ($language !== 'en') {
            $lexicon = array_merge($lexicon, self::read($dir . $language . '/global.php'));
        }

        return self::$lexicons[$language] = $lexicon;
    }

    /** @return array<string, mixed> */
    private static function read(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }

        return (static function (string $file): array {
            $_lang = [];
            include $file;

            return $_lang;
        })($file);
    }

    private static function managerLanguage(): string
    {
        // the instance evo() keeps, if it was built: calling evo() here could build it, or re-enter its referer check
        $evo = $GLOBALS['evo'] ?? null;

        return is_object($evo) && method_exists($evo, 'getConfig') ? (string)$evo->getConfig('manager_language', 'en') : 'en';
    }
}
