<?php

namespace EvolutionCMS\Providers;

/**
 * Retain Laravel cache bindings and deferred declarations with the core file store.
 *
 * @since 3.7.0
 */
class CacheServiceProvider extends \Illuminate\Cache\CacheServiceProvider
{
    /**
     * Register upstream services and substitute only the cache manager binding.
     *
     * @return void
     * @since 3.7.0
     */
    public function register()
    {
        parent::register();
        $this->app->singleton('cache', fn ($app) => new \EvolutionCMS\Cache\CacheManager($app));
    }
}
