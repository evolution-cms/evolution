<?php

namespace Tests\Unit;

use EvolutionCMS\Traits\Settings;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

final class FrontendManagerViewNamespaceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!defined('EVO_MANAGER_PATH')) {
            define('EVO_MANAGER_PATH', '/example/manager/');
        }
    }

    public function testNamespaceWaitsUntilTheViewFactoryIsResolved(): void
    {
        $app = $this->app();
        $view = $this->view();
        $app->singleton('view', static fn () => $view);

        $app->registerManagerViews('custom');

        self::assertFalse($app->resolved('view'));
        self::assertSame($view, $app->make('view'));
        self::assertSame($this->paths('custom'), $view->namespaces['manager']);
    }

    public function testNamespaceIsRegisteredWhenTheViewFactoryAlreadyExists(): void
    {
        $app = $this->app();
        $view = $this->view();
        $app->singleton('view', static fn () => $view);
        $app->make('view');

        $app->registerManagerViews('default');

        self::assertSame($this->paths('default'), $view->namespaces['manager']);
    }

    private function app(): Container
    {
        return new class extends Container {
            use Settings;

            public function registerManagerViews(string $theme): void
            {
                $this->registerFrontendManagerViewNamespace($theme);
            }
        };
    }

    private function view(): object
    {
        return new class {
            public array $namespaces = [];

            public function addNamespace(string $namespace, array $paths): void
            {
                $this->namespaces[$namespace] = $paths;
            }
        };
    }

    private function paths(string $theme): array
    {
        return [
            EVO_MANAGER_PATH . '/media/style/' . $theme . '/views/',
            EVO_MANAGER_PATH . '/views/',
        ];
    }
}
