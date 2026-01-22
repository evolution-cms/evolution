<?php namespace EvolutionCMS\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class BladeServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Blade::directive('evoConfig', function ($expression) {
            $expression = $expression ?: "''";
            return "<?php echo e(evo()->getConfig($expression)); ?>";
        });

        Blade::directive('makeUrl', function ($expression) {
            $expression = $expression ?: "''";
            return "<?php echo e(app('UrlProcessor')->makeUrlWithString($expression)); ?>";
        });

        Blade::directive('evoParser', function ($expression) {
            $expression = $expression ?: "''";
            return "<?php echo evo_parser($expression); ?>";
        });

        Blade::directive('evoRole', function ($expression) {
            $expression = $expression ?: "''";
            return "<?php if (evo_role($expression)): ?>";
        });

        Blade::directive('evoElseRole', function ($expression) {
            $expression = $expression ?: "''";
            return "<?php elseif (evo_role($expression)): ?>";
        });

        Blade::directive('evoEndRole', function () {
            return "<?php endif; ?>";
        });

        Blade::if('auth', fn () => evo()->getLoginUserID() !== false);
        Blade::if('guest', fn () => evo()->getLoginUserID() === false);

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
                $this->app->get('blade.compiler')->directive($name, $callback);
            }
        }
    }
}
