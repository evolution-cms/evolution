<?php

use EvolutionCMS\Support\MigrationRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the installer log of releases before 3.5.10, once `migrations` holds all of it.
 *
 * 3.5.10 copied it into `migrations` and left it for a way back to 3.5.9; 3.6 does not go back
 * that far. A site that skipped 3.5.10 has it copied by the first use of the log, before this runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        $installerTable = MigrationRepository::INSTALLER_TABLE;
        if (!Schema::hasTable($installerTable)) {
            return;
        }

        $repository = app('migration.repository');
        if (!$repository instanceof MigrationRepository || $repository->getTable() === $installerTable) {
            return;
        }

        $repository->importInstallerLog();
        $log = $repository->getTable();
        $missing = DB::table($installerTable)
            ->whereNotIn('migration', DB::table($log)->select('migration'))
            ->count();
        if ($missing > 0) {
            throw new RuntimeException($missing . ' migrations of ' . $installerTable . ' are not in ' . $log . '; it was kept.');
        }

        Schema::drop($installerTable);
    }

    /**
     * Nothing to bring back: its rows stay in `migrations`, which every release from 3.5.10 reads.
     */
    public function down(): void
    {
    }
};
