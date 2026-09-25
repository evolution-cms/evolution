<?php namespace EvolutionCMS\Bootstrap;

use Dotenv\Dotenv;
use Throwable;

/**
 * Environment loader and bootstrap configuration cache.
 *
 * Cache file:
 * - `core/storage/cache/env.php` (relative to project root)
 *
 * Invalidation:
 * - Cache is valid while its mtime is at least that of the selected `.env` file.
 * - Compiled configuration is rebuilt when the project moves to another directory.
 * - Compiled configuration is used only after `core/.install` exists.
 * - A full CMS cache clear removes it, so configuration changes take effect on the next request.
 * - The legacy environment-only array remains readable and is upgraded on the next bootstrap.
 */
final class EnvCacheLoader
{
    private const BOOTSTRAP_CACHE_VERSION = 3;

    /** @var array<string, mixed>|null */
    private static ?array $loadedCache = null;

    /** @var array{items: array<string, mixed>, dynamic_files: list<array{key: string, path: string}>}|null */
    private static ?array $runtimeConfiguration = null;

    private static ?string $loadedRoot = null;

    private static ?string $cachePath = null;

    private static ?string $envPath = null;

    private static ?int $envMtimeAtLoad = null;

    /** @var array<string, string> */
    private static array $environment = [];

    /**
     * Loads environment variables and the optional compiled configuration payload.
     *
     * Behavior:
     * - Detects an `.env` file (project-specific search order is implemented in {@see detectEnvPathAndMtime()}).
     * - If `core/storage/cache/env.php` exists and is fresh (`mtime(cache) >= mtime(.env)`), loads it.
     * - Otherwise parses `.env`, applies variables, and writes an environment-only cache file.
     * - AbstractLaravel adds configuration to the same file after loading it once.
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

        if (self::$loadedRoot === $projectRoot
            && (self::$loadedCache !== null || self::$runtimeConfiguration !== null)) {
            self::applyImmutable(self::$environment);
            return;
        }

        self::$loadedRoot = $projectRoot;
        self::$loadedCache = null;
        self::$runtimeConfiguration = null;
        self::$environment = [];
        self::$cachePath = $projectRoot . '/core/storage/cache/env.php';

        [$envPath, $envMtime] = self::detectEnvPathAndMtime($projectRoot);
        self::$envPath = $envPath;
        self::$envMtimeAtLoad = $envMtime;
        $cachePath = self::$cachePath;

        $cacheMtime = false;
        if (is_file($cachePath)) {
            $cacheMtime = @filemtime($cachePath);
        }
        if ($cacheMtime !== false && ($envMtime === null || $cacheMtime >= $envMtime)) {
            $cached = self::loadCacheArray($cachePath);
            if (is_array($cached)) {
                if (($cached['_evolution_bootstrap_cache'] ?? null) === self::BOOTSTRAP_CACHE_VERSION
                    && ($cached['project_root'] ?? null) === $projectRoot
                    && ($cached['env_path'] ?? null) === $envPath
                    && is_array($cached['environment'] ?? null)) {
                    self::$loadedCache = $cached;
                    self::$environment = self::normalizeVarsForCache($cached['environment']);
                    self::applyImmutable(self::$environment);
                    return;
                }
                if ($envPath !== null && !isset($cached['_evolution_bootstrap_cache'])) {
                    self::$loadedCache = $cached;
                    self::$environment = self::normalizeVarsForCache($cached);
                    self::applyImmutable(self::$environment);
                    return;
                }
            }
        }

        if ($envPath !== null) {
            self::rebuildAndLoad($envPath, $cachePath);
        }
    }

    /**
     * Return the parsed configuration from the same cache file loaded for the environment.
     * Request/process-dependent configuration groups are evaluated separately each time.
     * A project-root mismatch makes a packaged build rescan its configuration once.
     *
     * @return array{items: array<string, mixed>, dynamic_files: list<array{key: string, path: string}>}|null
     * @since 3.5.9
     */
    public static function configuration(): ?array
    {
        // Composer commands may boot the app before the installer writes its database
        // connection. Do not reuse a configuration snapshot from that incomplete state.
        if (self::$loadedRoot === null || !is_file(self::$loadedRoot . '/core/.install')) {
            return null;
        }

        if (self::$runtimeConfiguration !== null) {
            return self::$runtimeConfiguration;
        }

        $cached = self::$loadedCache;
        if (($cached['_evolution_bootstrap_cache'] ?? null) !== self::BOOTSTRAP_CACHE_VERSION
            || !is_array($cached['configuration'] ?? null)
            || !is_array($cached['dynamic_files'] ?? null)) {
            return null;
        }

        foreach ($cached['dynamic_files'] as $file) {
            if (!is_array($file) || !is_string($file['key'] ?? null) || !is_string($file['path'] ?? null)) {
                return null;
            }
        }

        return self::$runtimeConfiguration = [
            'items' => $cached['configuration'],
            'dynamic_files' => $cached['dynamic_files'],
        ];
    }

    /**
     * Remove the shared bootstrap cache after a full CMS cache clear.
     *
     * @since 3.5.9
     */
    public static function invalidate(string $projectRoot): void
    {
        $cachePath = rtrim($projectRoot, '/') . '/core/storage/cache/env.php';
        if (is_file($cachePath)) {
            @unlink($cachePath);
        }
        if (self::$cachePath === $cachePath) {
            self::$loadedCache = null;
            self::$runtimeConfiguration = null;
        }
    }

    /**
     * Persist only arrays and scalar values; objects and resources may not survive PHP export.
     * The write is best-effort, and a failure simply keeps the normal config loading path.
     *
     * @param array<string, mixed> $items
     * @param list<array{key: string, path: string}> $dynamicFiles
     * @since 3.5.9
     */
    public static function cacheConfiguration(array $items, array $dynamicFiles): void
    {
        self::$runtimeConfiguration = ['items' => $items, 'dynamic_files' => $dynamicFiles];
        if (self::$cachePath === null || self::$loadedRoot === null
            || !is_file(self::$loadedRoot . '/core/.install') || !self::isExportable($items)) {
            return;
        }

        if (self::$envPath !== null) {
            clearstatcache(true, self::$envPath);
        }
        [$currentEnvPath, $currentEnvMtime] = self::detectEnvPathAndMtime(self::$loadedRoot);
        if ($currentEnvPath !== self::$envPath || $currentEnvMtime !== self::$envMtimeAtLoad) {
            return;
        }

        $payload = [
            '_evolution_bootstrap_cache' => self::BOOTSTRAP_CACHE_VERSION,
            'project_root' => self::$loadedRoot,
            'env_path' => self::$envPath,
            'environment' => self::$environment,
            'configuration' => $items,
            'dynamic_files' => $dynamicFiles,
        ];
        $cacheDir = dirname(self::$cachePath);
        if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0777, true) && !is_dir($cacheDir)) {
            return;
        }
        if (!is_writable($cacheDir)) {
            return;
        }

        $temporary = @tempnam($cacheDir, '.env-');
        if ($temporary === false) {
            return;
        }
        try {
            $source = '<?php return ' . self::exportShortArray($payload) . ';' . PHP_EOL;
            $permissions = @fileperms(self::$cachePath);
            @chmod($temporary, $permissions === false ? (0666 & ~umask()) : ($permissions & 0777));
            if (@file_put_contents($temporary, $source, LOCK_EX) !== false && @rename($temporary, self::$cachePath)) {
                if (function_exists('opcache_invalidate')) {
                    @opcache_invalidate(self::$cachePath, true);
                }
                self::$loadedCache = $payload;
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    /**
     * Check that every cached value can be exported as a PHP literal.
     *
     * @since 3.5.9
     */
    private static function isExportable(mixed $value, int $depth = 0): bool
    {
        if ($depth > 64) {
            return false;
        }
        if (!is_array($value)) {
            return is_scalar($value) || $value === null;
        }

        foreach ($value as $item) {
            if (!self::isExportable($item, $depth + 1)) {
                return false;
            }
        }

        return true;
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
     * - Legacy environment array, or the versioned environment + configuration payload.
     *
     * @return array<string, mixed>|null
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
        self::$environment = $normalized;
        self::$loadedCache = $normalized;
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
     * - Uses a unique temporary file so concurrent requests do not overwrite each other's draft.
     * - Renames the temp file into place using `rename()` (atomic on most filesystems when on the same volume).
     *
     * Safety:
     * - Best-effort only; failures are ignored.
     *
     * @param array<string, string> $vars
     */
    private static function writeCacheAtomic(string $cachePath, array $vars): void
    {
        $tmpPath = @tempnam(dirname($cachePath), '.env-');
        if ($tmpPath === false) {
            return;
        }
        try {
            ksort($vars, SORT_STRING);
            $php = "<?php return " . self::exportShortArray($vars) . ";\n";
            $permissions = @fileperms($cachePath);
            @chmod($tmpPath, $permissions === false ? (0666 & ~umask()) : ($permissions & 0777));
            if (@file_put_contents($tmpPath, $php, LOCK_EX) === false) {
                return;
            }

            @rename($tmpPath, $cachePath);
        } catch (Throwable) {
            // Ignore
        } finally {
            if (is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    /**
     * Converts an array into a readable, stable PHP array literal using short array syntax.
     *
     * @param array<array-key, mixed> $vars
     * @return string PHP code fragment for the array only (no `<?php` wrapper).
     */
    private static function exportShortArray(array $vars, int $depth = 0): string
    {
        if ($vars === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth);
        $childIndent = $indent . '    ';
        $lines = ['['];
        foreach ($vars as $k => $v) {
            $value = is_array($v) ? self::exportShortArray($v, $depth + 1) : var_export($v, true);
            $lines[] = $childIndent . var_export($k, true) . ' => ' . $value . ',';
        }
        $lines[] = $indent . ']';
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
