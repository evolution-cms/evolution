<?php namespace EvolutionCMS\Providers;

use EvolutionCMS\Auth\LoginPipeline;
use EvolutionCMS\Auth\PipelineUserManager;
use EvolutionCMS\Interfaces\UserManagerInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the user manager, wrapped in a configurable login pipeline.
 *
 * This is the default provider for the `Evolution_UserManager` key. It binds the same
 * container key as the package's own provider, so the `\UserManager` facade and every
 * existing call site keep working unchanged — with an empty `cms.auth.pipeline` the
 * wrapper is a pass-through and behaviour is identical to the package's.
 *
 * A site that wants the plain package implementation back, or its own, replaces this
 * one entry — the custom configuration directory is loaded after the core one and
 * overwrites just that key, leaving every other provider untouched:
 *
 *     // core/custom/config/app/providers/Evolution_UserManager.php
 *     <?php return EvolutionCMS\UserManager\Providers\UserManagerServiceProvider::class;
 */
class PipelineUserManagerServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register()
    {
        $this->app->singleton(LoginPipeline::class, function ($app) {
            return new LoginPipeline($app);
        });

        $this->app->singleton('UserManager', function ($app) {
            return new PipelineUserManager($app->make(LoginPipeline::class));
        });

        // Resolving the contract yields the same singleton, so code can type-hint
        // UserManagerInterface instead of reaching for the facade — without a second
        // instance and without breaking anything that still uses the string key.
        $this->app->alias('UserManager', UserManagerInterface::class);
    }
}
