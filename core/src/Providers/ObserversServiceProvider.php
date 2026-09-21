<?php namespace EvolutionCMS\Providers;

use Illuminate\Support\ServiceProvider;

class ObserversServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $observers = $this->app['config']->get('cms.observers');

        foreach ($observers as $model => $observer) {
            $model::observe($observer);
        }
    }
}
