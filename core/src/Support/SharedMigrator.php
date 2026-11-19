<?php

namespace EvolutionCMS\Support;

use Illuminate\Database\Migrations\Migrator;

/**
 * Migrator that does not run again what the other migration log of the site recorded.
 *
 * Only the choice of pending migrations looks at both logs (SharedMigrationRepository): reset and
 * rollback undo this side's own migrations, so a migration the installer ran is never undone by
 * artisan while the installer's record of it stays.
 *
 * @since 3.5.10
 */
class SharedMigrator extends Migrator
{
    /**
     * @param array<string, string> $files
     * @param array<int, string> $ran
     * @return array<string, string>
     */
    protected function pendingMigrations($files, $ran)
    {
        if ($this->repository instanceof SharedMigrationRepository) {
            $ran = array_merge($ran, array_keys($this->repository->ranElsewhere()));
        }

        return parent::pendingMigrations($files, $ran);
    }
}
