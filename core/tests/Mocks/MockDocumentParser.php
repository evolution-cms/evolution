<?php

namespace Tests\Mocks;

use EvolutionCMS\Legacy\Modifiers;

/**
 * Mock DocumentParser for testing
 * Provides minimal functionality needed for modifier testing
 */
class MockDocumentParser
{
    private static $instance = null;
    private $modifiers = null;
    public $config = [
        'modx_charset' => 'UTF-8',
        'enable_filter' => 1,
    ];

    public static function create()
    {
        return new self();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Reset singleton instance (useful for testing)
     */
    public static function resetInstance()
    {
        self::$instance = null;
    }

    /**
     * Get Modifiers instance
     */
    public function getModifiers()
    {
        if ($this->modifiers === null) {
            $this->modifiers = new Modifiers();
        }
        return $this->modifiers;
    }

    /**
     * Get configuration value
     */
    public function getConfig($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set configuration value (for testing)
     */
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * Clear cache (stub for testing)
     */
    public function clearCache(string $type = 'full'): void
    {
        // no-op in mock
    }

    /**
     * Minimal IoC make() — resolves known path abstracts directly from
     * constants so that app('path.storage') etc. work correctly when
     * evo() is returning this mock during Core bootstrap.
     * This avoids infinite recursion that would occur if we called
     * Core::getInstance() while Core is still being constructed.
     */
    public function make(string $abstract, array $parameters = [])
    {
        $paths = [
            'path' => defined('EVO_CORE_PATH') ? EVO_CORE_PATH : null,
            'path.base' => defined('EVO_BASE_PATH') ? EVO_BASE_PATH : null,
            'path.storage' => defined('EVO_STORAGE_PATH') ? EVO_STORAGE_PATH : null,
            'path.config' => defined('EVO_CORE_PATH') ? EVO_CORE_PATH . 'config' . DIRECTORY_SEPARATOR : null,
            'path.public' => defined('EVO_BASE_PATH') ? EVO_BASE_PATH : null,
            'path.database' => defined('EVO_BASE_PATH') ? EVO_BASE_PATH . 'database' . DIRECTORY_SEPARATOR : null,
            'path.resources' => defined('EVO_BASE_PATH') ? EVO_BASE_PATH . 'resources' . DIRECTORY_SEPARATOR : null,
            'path.bootstrap' => defined('EVO_CORE_PATH') ? EVO_CORE_PATH . 'bootstrap' . DIRECTORY_SEPARATOR : null,
            'path.lang' => defined('EVO_CORE_PATH') ? EVO_CORE_PATH . 'lang' . DIRECTORY_SEPARATOR : null,
        ];
        return $paths[$abstract] ?? null;
    }

    /**
     * Return false (not logged in) so Core::checkAuth() is a no-op.
     */
    public function getLoginUserID(string $context = '')
    {
        return false;
    }

    /**
     * Path helpers — delegate to constants so service providers don't blow up
     * when evo() returns this mock during Core bootstrap.
     */
    public function publicPath(string $path = ''): string
    {
        $base = defined('EVO_BASE_PATH') ? EVO_BASE_PATH : '';
        return rtrim($base, DIRECTORY_SEPARATOR) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }

    public function basePath(string $path = ''): string
    {
        $base = defined('EVO_BASE_PATH') ? EVO_BASE_PATH : '';
        return rtrim($base, DIRECTORY_SEPARATOR) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }

    public function storagePath(string $path = ''): string
    {
        $base = defined('EVO_STORAGE_PATH') ? EVO_STORAGE_PATH : '';
        return rtrim($base, DIRECTORY_SEPARATOR) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }

    public function configPath(string $path = ''): string
    {
        $base = defined('EVO_CORE_PATH') ? EVO_CORE_PATH . 'config' : '';
        return rtrim($base, DIRECTORY_SEPARATOR) . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }

    /**
     * Set locale (stub for testing)
     */
    public function setLocale(string $locale): void
    {
        // no-op in mock
    }
}
