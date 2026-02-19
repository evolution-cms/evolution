<?php namespace EvolutionCMS\Providers;

use EvolutionCMS\ServiceProvider;
use Helpers\FS;

/**
 * @deprecated
 * @TODO: remove in 3.7
 */
class FsServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('FS', function ($modx) {
            /* @phpstan-ignore-next-line class.notFound */
            return FS::getInstance();
        });

        $this->app->setEvolutionProperty('FS', 'fs');
    }
}
