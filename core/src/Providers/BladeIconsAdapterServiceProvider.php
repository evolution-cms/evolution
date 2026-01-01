<?php namespace EvolutionCMS\Providers;

use BladeUI\Icons\Factory;
use BladeUI\Icons\IconsManifest;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

/**
 * Adapter for Blade Icons to work with Evolution CMS
 * This provider avoids the Application type-hint issues in the original provider
 */
class BladeIconsAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
        $this->registerManifest();
        $this->registerFactory();
    }

    public function boot(): void
    {
        $this->bootDirectives();
        $this->bootIconComponent();
        $this->bootPublishing();
    }

    private function registerConfig(): void
    {
        $this->mergeConfigFrom(EVO_CORE_PATH . 'config/blade-icons.php', 'blade-icons');
    }

    private function registerFactory(): void
    {
        $this->app->singleton(Factory::class, function ($app) {
            $config = $app['config']->get('blade-icons', []);

            return new Factory(
                new Filesystem,
                $app->make(IconsManifest::class),
                $app->make(FilesystemFactory::class),
                $config
            );
        });
    }

    private function registerManifest(): void
    {
        $this->app->singleton(IconsManifest::class, function ($app) {
            return new IconsManifest(
                new Filesystem,
                $this->manifestPath(),
                $app->make(FilesystemFactory::class),
            );
        });
    }

    private function bootDirectives(): void
    {
        // Register Blade directives without type-hint issues
        $this->callAfterResolving(ViewFactory::class, function ($view) {
            // Register @svg directive
            $view->getEngineResolver()
                ->resolve('blade')
                ->getCompiler()
                ->directive('svg', function ($expression) {
                    return "<?php echo e(svg($expression)); ?>";
                });
        });
    }

    private function bootIconComponent(): void
    {
        // Register icon component without Application type-hint
        $this->callAfterResolving(ViewFactory::class, function ($view) {
            if (!is_file($this->manifestPath())) {
                return;
            }

            $this->app->make(Factory::class)->registerComponents();
        });
    }

    private function bootPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                EVO_CORE_PATH . 'config/blade-icons.php' => $this->app->configPath('blade-icons.php'),
            ], 'blade-icons-config');
        }
    }

    private function manifestPath(): string
    {
        return $this->app->bootstrapPath('cache/blade-icons.php');
    }
}
