<?php namespace EvolutionCMS\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

class BladeServiceProvider extends ServiceProvider
{
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
