<?php namespace EvolutionCMS\Providers;

use EvolutionCMS\ServiceProvider;
use modResource;

/**
 * @deprecated
 * @TODO: remove in 3.7
 */
class ModResourceServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('modResource', function ($modx) {
            /* @phpstan-ignore-next-line class.notFound */
            return new modResource($modx);
        });

        $this->app->setEvolutionProperty('modResource', 'doc');
    }
}
