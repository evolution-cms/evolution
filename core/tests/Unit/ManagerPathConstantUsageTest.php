<?php

namespace Tests\Unit;

use EvolutionCMS\ManagerTheme;
use EvolutionCMS\Traits\Models\ManagerActions;
use EvolutionCMS\Traits\Path;
use EvolutionCMS\Traits\Settings;
use PHPUnit\Framework\TestCase;

final class ManagerPathConstantUsageTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $rootDir = dirname(__DIR__, 3);

        if (!defined('IN_INSTALL_MODE')) {
            define('IN_INSTALL_MODE', false);
        }
        if (!defined('EVO_API_MODE')) {
            define('EVO_API_MODE', true);
        }
        if (!defined('IN_MANAGER_MODE')) {
            define('IN_MANAGER_MODE', false);
        }
        if (!defined('EVO_BASE_PATH')) {
            define('EVO_BASE_PATH', rtrim($rootDir, '/\\') . '/');
        }
        if (!defined('EVO_BASE_URL')) {
            define('EVO_BASE_URL', '/');
        }
        if (!defined('EVO_CORE_PATH')) {
            define('EVO_CORE_PATH', EVO_BASE_PATH . 'core/');
        }
        if (!defined('EVO_STORAGE_PATH')) {
            define('EVO_STORAGE_PATH', EVO_CORE_PATH . 'storage/');
        }
        if (!defined('EVO_MANAGER_PATH')) {
            define('EVO_MANAGER_PATH', EVO_BASE_PATH . 'manager/');
        }
        if (!defined('EVO_MANAGER_URL')) {
            define('EVO_MANAGER_URL', 'https://example.test/manager/');
        }
        if (!defined('EVO_SITE_URL')) {
            define('EVO_SITE_URL', 'https://example.test/');
        }

        require_once EVO_BASE_PATH . 'core/vendor/autoload.php';
    }

    public function testPathTraitExposesEvoPublicAndManagerPaths(): void
    {
        $carrier = new class {
            use Path;
        };

        $this->assertSame(EVO_BASE_PATH, $carrier->publicPath());
        $this->assertSame(EVO_MANAGER_URL, $carrier->getManagerUrl());
    }

    public function testManagerActionsFullUrlsUseEvoManagerUrl(): void
    {
        $resource = new class {
            use ManagerActions;

            public $exists = false;
            protected $managerActionsMap = [
                'view' => 27,
            ];
        };

        $this->assertSame(EVO_MANAGER_URL . '?a=27', $resource->makeUrl('view', true));
    }

    public function testManagerThemePathHelpersUseEvoManagerConstants(): void
    {
        $theme = (new \ReflectionClass(ManagerTheme::class))->newInstanceWithoutConstructor();
        $setter = \Closure::bind(function (string $themeName): void {
            $this->theme = $themeName;
        }, $theme, ManagerTheme::class);
        $setter('default');

        $this->assertSame(EVO_MANAGER_PATH . 'media/style/default/', $theme->getThemeDir());
        $this->assertSame('media/style/default/', $theme->getThemeDir(false));
        $this->assertSame(EVO_MANAGER_URL . 'media/style/default/', $theme->getThemeUrl());
    }

    public function testSettingsTraitStoresEvoPathConstantsInConfig(): void
    {
        $settings = new class {
            use Settings;

            public $error_reporting;

            public function getFactorySettings(): array
            {
                return [];
            }

            public function getConfig($name = '', $default = null)
            {
                return $this->config[$name] ?? $default;
            }

            public function setConfig($name, $value = '')
            {
                $this->config[$name] = $value;
            }
        };

        $settings->config = [
            'filemanager_path' => '[(base_path)]assets/files',
            'snapshot_path' => '[(base_path)]assets/backup',
            'rb_base_dir' => '[(base_path)]assets',
        ];

        $settings->getSettings();

        $this->assertSame(EVO_BASE_URL, $settings->config['base_url']);
        $this->assertSame(EVO_BASE_PATH, $settings->config['base_path']);
        $this->assertSame(EVO_SITE_URL, $settings->config['site_url']);
        $this->assertSame(EVO_MANAGER_PATH, $settings->config['site_manager_path']);
        $this->assertStringContainsString(EVO_BASE_PATH, $settings->config['filemanager_path']);
        $this->assertStringContainsString(EVO_BASE_PATH, $settings->config['snapshot_path']);
        $this->assertStringContainsString(EVO_BASE_PATH, $settings->config['rb_base_dir']);
    }
}
