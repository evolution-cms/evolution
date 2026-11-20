<?php namespace EvolutionCMS\Console;

use EvolutionCMS\Support\SharedMigrationRepository;
use Illuminate\Database\Console\Migrations\StatusCommand;

/**
 * migrate:status that shows a migration the other log recorded as run, with that log's name.
 *
 * The migrator does not run such a migration again (SharedMigrator), so "Pending" would be wrong.
 *
 * @since 3.5.10
 */
class MigrateStatusCommand extends StatusCommand
{
    /**
     * @param array<int, string> $ran
     * @param array<string, int|string> $batches
     */
    protected function getStatusFor(array $ran, array $batches)
    {
        $repository = $this->migrator->getRepository();
        if ($repository instanceof SharedMigrationRepository) {
            foreach ($repository->ranElsewhere() as $migration => $batch) {
                $ran[] = $migration;
                $batches[$migration] = $repository->counterpartTable() . ' ' . $batch;
            }
        }

        return parent::getStatusFor($ran, $batches);
    }
}
