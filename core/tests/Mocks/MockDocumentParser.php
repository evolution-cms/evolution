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
    private $config = [
        'modx_charset' => 'UTF-8',
        'enable_filter' => 1,
    ];

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
}
