<?php namespace EvolutionCMS\Providers;

use BladeUI\Icons\Factory;
use BladeUI\Icons\IconsManifest;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

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
        // Register Blade directives without type-hint issues. On the compiler
        // itself (the one the Blade engine uses): a page that only resolves the
        // view factory, e.g. to look for a template file, then does not build it.
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade) {
            // Register @svg directive
            $blade->directive('svg', function ($expression) {
                return "<?php echo e(svg($expression)); ?>";
            });
        });
    }

    private function bootIconComponent(): void
    {
        // Register icon component without Application type-hint; components are
        // compiler state as well, so they wait for the compiler like the directive.
        $this->callAfterResolving('blade.compiler', function () {
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
