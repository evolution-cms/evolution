<?php namespace EvolutionCMS\Bootstrap;

use Dotenv\Dotenv;
use Throwable;

/**
 * `.env` loader with a PHP array cache.
 *
 * Cache file:
 * - `core/storage/cache/env.php` (relative to project root)
 *
 * Invalidation:
 * - Cache is considered valid when `filemtime(cache) >= filemtime(.env)`.
 * - Cache should be removed by the Manager “Refresh site / Clear cache” action (a=26) so it rebuilds automatically.
 */
final class EnvCacheLoader
{
    /**
     * Loads environment variables with caching.
     *
     * Behavior:
     * - Detects an `.env` file (project-specific search order is implemented in {@see detectEnvPathAndMtime()}).
     * - If `core/storage/cache/env.php` exists and is fresh (`mtime(cache) >= mtime(.env)`), loads it.
     * - Otherwise parses `.env`, applies variables, and writes an atomic cache file.
     *
     * Compatibility:
     * - Applies variables "immutably": does not overwrite keys already present in `$_ENV` or `$_SERVER`.
     * - Optionally calls `putenv("$name=$value")` only when `getenv($name) === false`, to avoid overwriting
     *   existing OS-level env values while still supporting legacy code that reads only via `getenv()`.
     *
     * Safety:
     * - Best-effort only; never throws (all internal failures are swallowed).
     * - If the cache directory is not writable, falls back to the project’s existing Dotenv loading.
     */
    public static function load(string $projectRoot): void
    {
        $projectRoot = rtrim($projectRoot, '/');
        if ($projectRoot === '') {
            return;
        }

        [$envPath, $envMtime] = self::detectEnvPathAndMtime($projectRoot);
        if ($envPath === null || $envMtime === null) {
            return;
        }

        $cachePath = $projectRoot . '/core/storage/cache/env.php';

        $cacheMtime = false;
        if (is_file($cachePath)) {
            $cacheMtime = @filemtime($cachePath);
        }
        if ($cacheMtime !== false && $cacheMtime >= $envMtime) {
            $cached = self::loadCacheArray($cachePath);
            if (is_array($cached)) {
                self::applyImmutable(self::normalizeVarsForCache($cached));
                return;
            }
        }

        self::rebuildAndLoad($envPath, $cachePath);
    }

    /**
     * Detects the `.env` file path and its mtime.
     *
     * Search order (project-specific):
     * 1) `{projectRoot}/core/custom/.env`
     * 2) `{projectRoot}/.env`
     *
     * @return array{0: string|null, 1: int|null}
     */
    private static function detectEnvPathAndMtime(string $projectRoot): array
    {
        $coreCustomEnv = $projectRoot . '/core/custom/.env';
        if (is_file($coreCustomEnv)) {
            $mtime = @filemtime($coreCustomEnv);
            if ($mtime !== false) {
                return [$coreCustomEnv, $mtime];
            }
        }

        $rootEnv = $projectRoot . '/.env';
        if (is_file($rootEnv)) {
            $mtime = @filemtime($rootEnv);
            if ($mtime !== false) {
                return [$rootEnv, $mtime];
            }
        }

        return [null, null];
    }

    /**
     * Loads the cached env array from disk.
     *
     * Cache format:
     * - A PHP file returning an array: `<?php return ['KEY' => 'value', ...];`
     *
     * @return array<string, string|null>|null
     */
    private static function loadCacheArray(string $cachePath): ?array
    {
        try {
            $data = require $cachePath;
            return is_array($data) ? $data : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Rebuilds the cache from `.env` (if possible) and applies the resulting variables.
     *
     * Flow:
     * - Ensures the cache directory exists and is writable; otherwise falls back to Dotenv load (no caching).
     * - Parses `.env` content (without mutating env) using `Dotenv::parse(...)`.
     * - Normalizes and applies the final variables.
     * - Writes cache atomically.
     *
     * @param string $envPath Absolute path to `.env`.
     * @param string $cachePath Absolute path to `core/storage/cache/env.php`.
     */
    private static function rebuildAndLoad(string $envPath, string $cachePath): void
    {
        if (!class_exists(Dotenv::class)) {
            return;
        }

        $cacheDir = dirname($cachePath);
        if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0777, true) && !is_dir($cacheDir)) {
            self::fallbackDotenvLoad($envPath);
            return;
        }

        if (!is_writable($cacheDir)) {
            self::fallbackDotenvLoad($envPath);
            return;
        }

        $content = @file_get_contents($envPath);
        if (!is_string($content)) {
            return;
        }

        try {
            $parsed = Dotenv::parse($content);
        } catch (Throwable) {
            return;
        }

        $normalized = self::normalizeVarsForCache($parsed);
        self::applyImmutable($normalized);

        self::writeCacheAtomic($cachePath, $normalized);
    }

    /**
     * Falls back to the original Dotenv behavior (no caching).
     *
     * This is used when cache directory creation/writability checks fail. It is intentionally best-effort and
     * must not break the request.
     */
    private static function fallbackDotenvLoad(string $envPath): void
    {
        try {
            Dotenv::createImmutable(dirname($envPath), basename($envPath))->load();
        } catch (Throwable) {
            // Ignore
        }
    }

    /**
     * Apply values like Dotenv::createImmutable(...)->load() in this project:
     * - do not overwrite already-present variables
     * - do not write null values (treated as "not set")
     *
     * Also attempts to make values visible to legacy `getenv()` calls by calling `putenv()` only when the
     * variable is not already present at the OS/env level (`getenv($name) === false`).
     *
     * @param array<string, string> $vars
     */
    private static function applyImmutable(array $vars): void
    {
        foreach ($vars as $name => $value) {
            if (!is_string($name) || $name === '' || !is_string($value)) {
                continue;
            }

            if (array_key_exists($name, $_ENV) || array_key_exists($name, $_SERVER)) {
                continue;
            }

            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;

            if (function_exists('putenv') && getenv($name) === false) {
                @putenv($name . '=' . $value);
            }
        }
    }

    /**
     * Writes the env cache atomically.
     *
     * Implementation details:
     * - Writes to `{cachePath}.tmp` using `LOCK_EX` to avoid concurrent partial writes.
     * - Renames the temp file into place using `rename()` (atomic on most filesystems when on the same volume).
     *
     * Safety:
     * - Best-effort only; failures are ignored.
     *
     * @param array<string, string> $vars
     */
    private static function writeCacheAtomic(string $cachePath, array $vars): void
    {
        try {
            ksort($vars, SORT_STRING);
            $php = "<?php return " . self::exportShortArray($vars) . ";\n";

            $tmpPath = $cachePath . '.tmp';
            if (@file_put_contents($tmpPath, $php, LOCK_EX) === false) {
                return;
            }

            @rename($tmpPath, $cachePath);
        } catch (Throwable) {
            // Ignore
        }
    }

    /**
     * Converts an array into a readable, stable PHP array literal using short array syntax.
     *
     * @param array<string, string> $vars
     * @return string PHP code fragment for the array only (no `<?php` wrapper).
     */
    private static function exportShortArray(array $vars): string
    {
        $lines = [];
        $lines[] = '[';
        foreach ($vars as $k => $v) {
            $lines[] = '    ' . var_export((string)$k, true) . ' => ' . var_export($v, true) . ',';
        }
        $lines[] = ']';
        return implode("\n", $lines);
    }

    /**
     * Cache only the final key/value pairs that are actually applied.
     *
     * - Drops `null` values (Dotenv treats them as "not set")
     * - Keeps empty strings (they are real values in Dotenv)
     *
     * @param array<string, mixed> $vars
     * @return array<string, string>
     */
    private static function normalizeVarsForCache(array $vars): array
    {
        $out = [];
        foreach ($vars as $name => $value) {
            if (!is_string($name) || $name === '' || $value === null) {
                continue;
            }
            if (is_string($value)) {
                $out[$name] = $value;
            }
        }
        return $out;
    }
}
