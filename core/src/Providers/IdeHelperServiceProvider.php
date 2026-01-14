<?php namespace EvolutionCMS\Providers;

use Illuminate\Support\ServiceProvider;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider as BaseIdeHelperServiceProvider;

class IdeHelperServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (!$this->app->environment('local', 'development')) {
            return;
        }
        $this->app->register(BaseIdeHelperServiceProvider::class);
    }
}
