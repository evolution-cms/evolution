<?php namespace EvolutionCMS\Providers;

use EvolutionCMS\ServiceProvider;
use EvolutionCMS\ManagerTheme;
use EvolutionCMS\Interfaces\ManagerThemeInterface;

class ManagerThemeServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    protected $namespace = 'manager';

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('ManagerTheme', function ($app) {
            $theme = $this->app->getConfig('manager_theme', 'default');
            $this->loadSnippetsFrom(
                EVO_MANAGER_PATH . 'media/style/' . $theme . '/snippets/',
                $this->namespace
            );
            $this->loadChunksFrom(
                EVO_MANAGER_PATH . 'media/style/' . $theme . '/chunks/',
                $this->namespace
            );
            return new ManagerTheme($app, $theme);
        });

        $this->app->alias('ManagerTheme', ManagerThemeInterface::class);
        $this->app->alias('ManagerTheme', ManagerTheme::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['ManagerTheme'];
    }
}
