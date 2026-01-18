<?php namespace EvolutionCMS\Providers;

use EvolutionCMS\Services\ConfigService;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('ConfigService', function ($app) {
            return new ConfigService();
        });
    }
}
