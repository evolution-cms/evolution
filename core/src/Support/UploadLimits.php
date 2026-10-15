<?php declare(strict_types=1);

namespace EvolutionCMS\Support;

/**
 * Upload size limits, and the request PHP threw away for exceeding them.
 *
 * When a POST body is larger than `post_max_size`, PHP drops all of it (`$_POST` and `$_FILES`
 * arrive empty) and raises a startup warning. If that warning is displayed, output has begun
 * before the manager runs: every later header() and session call warns with a server path, and
 * the empty body then fails the CSRF check with a message about the session token. The manager
 * front controller asks this class first, before the session starts, and answers such a request
 * with a plain 413 page that says what happened.
 *
 * This file is loaded before the autoloader (manager/index.php), so it depends on nothing.
 *
 * @since 3.5.8
 */
final class UploadLimits
{
    /**
     * Bytes of a php.ini size value ("8M", "512K", "1G", "1048576"). Empty, zero or negative is 0.
     */
    public static function toBytes($value): int
    {
        if (!is_scalar($value)) {
            return 0;
        }
        $value = trim((string) $value);
        if (!preg_match('/^(\d+)\s*([kmg]?)$/i', $value, $match)) {
            return 0;
        }
        $bytes = (int) $match[1];
        $power = ['' => 0, 'k' => 1, 'm' => 2, 'g' => 3][strtolower($match[2])];

        return $bytes * (1024 ** $power);
    }

    /** `post_max_size` in bytes; 0 means PHP sets no limit. */
    public static function postMaxSize(): int
    {
        return self::toBytes(ini_get('post_max_size'));
    }

    /** `upload_max_filesize` in bytes; 0 means PHP sets no limit. */
    public static function uploadMaxFilesize(): int
    {
        return self::toBytes(ini_get('upload_max_filesize'));
    }

    /**
     * Largest single file an upload form can take: the smaller of the CMS setting (bytes, e.g.
     * `upload_maxsize`) and PHP's own limits. 0 means no limit is known.
     */
    public static function fileLimit($configured = 0, ?int $uploadMax = null, ?int $postMax = null): int
    {
        $limits = array_filter([
            is_numeric($configured) ? (int) $configured : 0,
            $uploadMax ?? self::uploadMaxFilesize(),
            $postMax ?? self::postMaxSize(),
        ], static fn (int $limit) => $limit > 0);

        return $limits === [] ? 0 : min($limits);
    }

    /**
     * Whether PHP discarded this POST body for exceeding `post_max_size`.
     *
     * @param array<string, mixed> $server A `$_SERVER`-shaped array.
     */
    public static function bodyWasDiscarded(array $server, array $post, array $files, ?int $postMax = null): bool
    {
        $postMax ??= self::postMaxSize();
        $length = $server['CONTENT_LENGTH'] ?? 0;

        return strtoupper((string) ($server['REQUEST_METHOD'] ?? '')) === 'POST'
            && $postMax > 0
            && is_numeric($length)
            && (int) $length > $postMax
            && $post === []
            && $files === [];
    }

    /** "8 MB", "512 KB", "300 bytes" - binary units, as php.ini counts them. */
    public static function humanSize(int $bytes): string
    {
        foreach (['GB' => 3, 'MB' => 2, 'KB' => 1] as $unit => $power) {
            if ($bytes >= 1024 ** $power) {
                return rtrim(rtrim(number_format($bytes / (1024 ** $power), 1, '.', ''), '0'), '.') . ' ' . $unit;
            }
        }

        return $bytes . ' bytes';
    }

    /**
     * Manager language to report in before the session (and the user's own setting) is known:
     * the first language of Accept-Language that the manager ships, else English.
     */
    public static function language(string $acceptLanguage, string $langDir): string
    {
        foreach (explode(',', $acceptLanguage) as $part) {
            $code = strtolower(substr(trim(explode(';', $part)[0]), 0, 2));
            if (preg_match('/^[a-z]{2}$/', $code) && is_file($langDir . '/' . $code . '/global.php')) {
                return $code;
            }
        }

        return 'en';
    }

    /**
     * The 413 page. Only sizes and lexicon text, never a path.
     *
     * @param array<string, string> $lang Lexicon entries; English fallbacks are built in.
     */
    public static function discardedBodyPage(array $lang, int $length, int $postMax, string $langCode = 'en'): string
    {
        $message = sprintf(
            $lang['upload_request_too_large']
                ?? 'The request was not accepted: it is %1$s, but this server takes at most %2$s per request (PHP post_max_size). Nothing was saved.',
            self::humanSize($length),
            self::humanSize($postMax)
        );
        $hint = $lang['upload_request_too_large_hint']
            ?? 'Upload fewer or smaller files, or ask the administrator to raise post_max_size and upload_max_filesize.';
        $back = $lang['upload_request_back'] ?? 'Back';
        $e = static fn (string $text) => htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<!DOCTYPE html><html lang="' . $e($langCode) . '"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1"><title>413</title>'
            . '<style>body{font:15px/1.5 sans-serif;margin:2rem;color:#222;background:#fff}'
            . '.alert{max-width:44rem;padding:1rem 1.25rem;border:1px solid #e0b4b4;border-radius:4px;background:#fff6f6}'
            . 'a{color:#0b5cad}</style></head><body><div class="alert" role="alert"><p><b>' . $e($message) . '</b></p>'
            . '<p>' . $e($hint) . '</p><p><a href="javascript:history.back()">' . $e($back) . '</a></p></div></body></html>';
    }

    /**
     * Answer a discarded POST and stop. Runs before the session starts and sends no cookie.
     *
     * @param array<string, mixed> $server
     */
    public static function rejectDiscardedBody(array $server, string $langDir): never
    {
        $postMax = self::postMaxSize();
        $code = self::language((string) ($server['HTTP_ACCEPT_LANGUAGE'] ?? ''), $langDir);
        $_lang = [];
        include $langDir . '/' . $code . '/global.php';

        if (!headers_sent()) {
            http_response_code(413);
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store');
        }
        echo self::discardedBodyPage($_lang, (int) ($server['CONTENT_LENGTH'] ?? 0), $postMax, $code);
        exit;
    }
}
