<?php

namespace EvolutionCMS\Services;

use RuntimeException;

/**
 * Reapply the PHP 8.6 backports to the Illuminate sources shipped by CMS 3.5.
 *
 * Composer runs this before generating its autoloader. The release also commits
 * the patched vendor files, so serving requests never needs to write dependencies.
 * Only whitespace splitting, object identities and session contracts are changed;
 * unfamiliar source fails before any file is replaced.
 *
 * @since 3.6.0
 */
final class IlluminateCompatibility
{
    /**
     * Validate the complete plan, then replace each changed dependency file atomically.
     *
     * @param string $core Directory containing the CMS vendor tree
     * @return list<string> Vendor-relative files changed by this invocation
     * @since 3.6.0
     */
    public function apply(string $core): array
    {
        $vendor = realpath($core . '/vendor');
        if ($vendor === false) {
            return [];
        }

        $validators = [
            'ArraySessionHandler' => 'isset($this->storage[$id])',
            'CacheBasedSessionHandler' => '$this->cache->has($id)',
            'CookieSessionHandler' => '$this->request->cookies->has($id)',
            'DatabaseSessionHandler' => '! is_null($this->getQuery()->find($id))',
            'FileSessionHandler' => '$this->files->isFile($this->path.\'/\'.$id)',
            'NullSessionHandler' => 'true',
        ];
        $paths = ['illuminate/support/Str.php'];
        $identities = [
            'illuminate/container/Container.php' => ['spl_object_hash($concrete)', "'closure:'.spl_object_id(\$concrete)"],
            'illuminate/database/Eloquent/Concerns/HasGlobalScopes.php' => ['spl_object_hash($scope)', "'closure:'.spl_object_id(\$scope)"],
            'illuminate/support/Onceable.php' => ['spl_object_hash($argument)', "'object:'.spl_object_id(\$argument)"],
        ];
        $paths = array_merge($paths, array_keys($identities));
        foreach ($validators as $class => $expression) {
            $paths[] = 'illuminate/session/' . $class . '.php';
        }

        $pending = [];
        foreach ($paths as $relative) {
            $path = $vendor . '/' . $relative;
            if (!is_file($path)) {
                continue;
            }
            $resolved = realpath($path);
            if ($resolved === false || !str_starts_with(str_replace('\\', '/', $resolved), str_replace('\\', '/', $vendor) . '/')) {
                throw new RuntimeException('Refusing to patch a dependency outside vendor: ' . $relative);
            }
            $source = file_get_contents($path);
            if ($source === false) {
                throw new RuntimeException('Cannot read dependency: ' . $relative);
            }
            if ($relative === 'illuminate/support/Str.php') {
                $updated = $this->strings($source);
            } elseif (isset($identities[$relative])) {
                $updated = $this->identity($source, ...$identities[$relative]);
            } else {
                $class = pathinfo($relative, PATHINFO_FILENAME);
                $updated = $this->session($source, $class, $validators[$class]);
            }
            if ($updated !== $source) {
                token_get_all($updated, TOKEN_PARSE);
                $pending[$relative] = [$path, $updated];
            }
        }

        foreach ($pending as [$path, $updated]) {
            $temporary = tempnam(dirname($path), '.evo-compat-');
            if ($temporary === false) {
                throw new RuntimeException('Cannot stage dependency backport: ' . $path);
            }
            try {
                if (file_put_contents($temporary, $updated) !== strlen($updated)
                    || !chmod($temporary, fileperms($path) & 0777)
                    || !rename($temporary, $path)) {
                    throw new RuntimeException('Cannot publish dependency backport: ' . $path);
                }
            } finally {
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
        }

        return array_keys($pending);
    }

    /**
     * Replace Illuminate's fixed whitespace expressions with Unicode PCRE splits.
     *
     * Keeping empty parts retains Laravel 12's string results. This removes calls
     * to the mbregex API deprecated in PHP 8.6, rather than filtering diagnostics.
     *
     * @param string $source Contents of Illuminate Support's Str class
     * @return string Compatible source, or the original already-compatible source
     * @since 3.6.0
     */
    private function strings(string $source): string
    {
        if (!preg_match('/\bclass\s+Str\b/', $source)) {
            throw new RuntimeException('Unknown Illuminate Str class; review the PHP 8.6 backport.');
        }
        $updated = str_replace(["mb_split('\\s+', ", 'mb_split("\\s+", '], "preg_split('/\\s+/u', ", $source);
        if (str_contains($updated, 'mb_split(')) {
            throw new RuntimeException('Unknown Illuminate Str mb_split call; review the PHP 8.6 backport.');
        }

        return $updated;
    }

    /**
     * Replace obsolete object hashing while keeping Laravel 12's opaque string keys.
     *
     * Laravel 13 uses object IDs directly; prefixing IDs here preserves the string
     * keys required by Laravel 12's scope removal and separates captured objects
     * from scalar numeric arguments in once() memoization. IDs are process-local.
     *
     * @param string $source Dependency source
     * @param string $from Known object-hash call
     * @param string $to Compatible object-ID expression
     * @return string Compatible source
     * @since 3.6.0
     */
    private function identity(string $source, string $from, string $to): string
    {
        $updated = str_replace($from, $to, $source);
        if (str_contains($updated, 'spl_object_hash(')) {
            throw new RuntimeException('Unknown Illuminate object identity call; review the PHP 8.6 backport.');
        }

        return $updated;
    }

    /**
     * Backport Laravel 13's ID generation and validation to each Laravel 12 store.
     *
     * PHP 8.6 diagnoses missing methods when a SessionHandlerInterface class loads.
     * The existing read/write/expiry implementation and each store's ID lookup are
     * retained. Newer upstream handlers already providing these methods are kept.
     *
     * @param string $source Session-handler source
     * @param string $class Expected Illuminate handler class
     * @param string $validation Upstream expression checking whether an ID exists
     * @return string Source implementing both session ID methods
     * @since 3.6.0
     */
    private function session(string $source, string $class, string $validation): string
    {
        if (!preg_match('/class\s+' . preg_quote($class, '/') . '\s+implements\s+[^\{]*SessionHandlerInterface\b/', $source)) {
            throw new RuntimeException('Unknown Illuminate session handler: ' . $class);
        }
        $methods = '';
        if (!preg_match('/function\s+create_sid\s*\(/', $source)) {
            $methods .= <<<'PHP'

    /**
     * Generate a session ID (Laravel 13 backport for PHP 8.6).
     *
     * @return string
     * @since 13.0
     */
    public function create_sid(): string
    {
        return session_create_id() ?: throw new \RuntimeException('Unable to create a session ID.');
    }

PHP;
        }
        if (!preg_match('/function\s+validateId\s*\(/', $source)) {
            $methods .= "\n    /**\n     * Check an existing session ID (Laravel 13 backport for PHP 8.6).\n     *\n     * @param string \$id\n     * @return bool\n     * @since 13.0\n     */\n"
                . "    public function validateId(\$id): bool\n    {\n        return {$validation};\n    }\n";
        }
        $end = strrpos($source, "\n}");
        if ($end === false) {
            throw new RuntimeException('Cannot locate session handler end: ' . $class);
        }

        return $methods === '' ? $source : substr_replace($source, "\n" . $methods, $end, 0);
    }
}
