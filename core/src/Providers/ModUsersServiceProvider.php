<?php namespace EvolutionCMS\Providers;

use EvolutionCMS\ServiceProvider;
use modUsers;

/**
 * @deprecated
 * @TODO: remove in 3.7
 */
class ModUsersServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('modUsers', function ($modx) {
            /* @phpstan-ignore-next-line class.notFound */
            return new modUsers($modx);
        });

        $this->app->setEvolutionProperty('modUsers', 'user');
    }
}
