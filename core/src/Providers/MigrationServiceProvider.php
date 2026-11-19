<?php namespace EvolutionCMS\Providers;

use EvolutionCMS\Support\SharedMigrationRepository;
use Illuminate\Database\MigrationServiceProvider as IlluminateMigrationServiceProvider;

/**
 * Laravel's migration services, with a log shared by the installer and artisan.
 *
 * @since 3.5.10
 */
class MigrationServiceProvider extends IlluminateMigrationServiceProvider
{
    /**
     * @return void
     */
    protected function registerRepository()
    {
        $this->app->singleton('migration.repository', function ($app) {
            $migrations = $app['config']['database.migrations'];
            $table = is_array($migrations) ? ($migrations['table'] ?? null) : $migrations;

            return new SharedMigrationRepository($app['db'], $table);
        });
    }
}
