<?php namespace EvolutionCMS\Providers;

use EvolutionCMS\Support\SharedMigrationRepository;
use EvolutionCMS\Support\SharedMigrator;
use Illuminate\Database\MigrationServiceProvider as IlluminateMigrationServiceProvider;
use Illuminate\Database\Migrations\Migrator;

/**
 * Laravel's migration services, with a migrator that knows the installer's and artisan's logs.
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

    /**
     * @return void
     */
    protected function registerMigrator()
    {
        $this->app->singleton('migrator', function ($app) {
            return new SharedMigrator($app['migration.repository'], $app['db'], $app['files'], $app['events']);
        });

        $this->app->bind(Migrator::class, fn ($app) => $app['migrator']);
    }
}
