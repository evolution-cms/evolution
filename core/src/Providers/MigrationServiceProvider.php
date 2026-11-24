<?php namespace EvolutionCMS\Providers;

use EvolutionCMS\Support\MigrationRepository;
use EvolutionCMS\Support\Migrator as EvolutionMigrator;
use Illuminate\Database\MigrationServiceProvider as IlluminateMigrationServiceProvider;
use Illuminate\Database\Migrations\Migrator;

/**
 * Laravel's migration services on the site's one log, which keeps the installer's baseline.
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

            return new MigrationRepository($app['db'], $table, defined('IN_INSTALL_MODE') && IN_INSTALL_MODE === true);
        });
    }

    /**
     * @return void
     */
    protected function registerMigrator()
    {
        $this->app->singleton('migrator', function ($app) {
            return new EvolutionMigrator($app['migration.repository'], $app['db'], $app['files'], $app['events']);
        });

        $this->app->bind(Migrator::class, fn ($app) => $app['migrator']);
    }
}
