<?php namespace EvolutionCMS\Providers;

use EvolutionCMS\Extensions\BladeCompiler as FileTimeBladeCompiler;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\DynamicComponent;

class BladeServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Compiled views are keyed on the file time of their template, so a version
        // switch never runs the views of the version it replaced. A compiler that a
        // site registered itself is left alone.
        $this->app->extend('blade.compiler', function (BladeCompiler $blade, $app) {
            if (get_class($blade) !== BladeCompiler::class) {
                return $blade;
            }

            return tap(new FileTimeBladeCompiler(
                $app['files'],
                $app['config']['view.compiled'],
                $app['config']->get('view.relative_hash', false) ? $app->basePath() : '',
                $app['config']->get('view.cache', true),
                $app['config']->get('view.compiled_extension', 'php'),
                $app['config']->get('view.check_cache_timestamps', true),
            ), function (BladeCompiler $blade) {
                $blade->component('dynamic-component', DynamicComponent::class);
            });
        });
    }

    public function boot()
    {
        // Registered when the compiler is first built: a page that renders no
        // Blade view never needs it, and Blade:: here built it on every request.
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade) {
            $this->registerDirectives($blade);
        });
    }

    protected function registerDirectives(BladeCompiler $blade): void
    {
        $blade->directive('evoConfig', function ($expression) {
            $expression = $expression ?: "''";
            return "<?php echo e(evo()->getConfig($expression)); ?>";
        });

        $blade->directive('makeUrl', function ($expression) {
            $expression = $expression ?: "''";
            return "<?php echo e(app('UrlProcessor')->makeUrlWithString($expression)); ?>";
        });

        /**
         * Render a public file URL with an mtime-based cache revision.
         *
         * @since 3.5.8
         */
        $blade->directive('revision', function ($expression) {
            $expression = $expression ?: "''";
            return "<?php echo e(revision($expression)); ?>";
        });

        $blade->directive('evoParser', function ($expression) {
            $expression = $expression ?: "''";
            return "<?php echo evo_parser($expression); ?>";
        });

        $blade->directive('evoRole', function ($expression) {
            $expression = $expression ?: "''";
            return "<?php if (evo_role($expression)): ?>";
        });

        $blade->directive('evoElseRole', function ($expression) {
            $expression = $expression ?: "''";
            return "<?php elseif (evo_role($expression)): ?>";
        });

        $blade->directive('evoEndRole', function () {
            return "<?php endif; ?>";
        });

        $blade->if('auth', fn () => evo()->getLoginUserID() !== false);
        $blade->if('guest', fn () => evo()->getLoginUserID() === false);

        /**
         * @deprecated
         * @since 3.5.3
         *
         * It's not using anywhere.
         *
         * @todo [remove@3.7] Remove in Evolution CMS 3.7
         */
        $directives = $this->app['config']->get('view.directive');
        if (\is_array($directives)) {
            foreach ($directives as $name => $callback) {
                $blade->directive($name, $callback);
            }
        }
    }
}
