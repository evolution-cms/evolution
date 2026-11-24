<?php

namespace EvolutionCMS\Support;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

/**
 * The one migration log of a site, `migrations`, for the installer and artisan alike.
 *
 * Up to 3.5.9 the installer logged in `migrations_install` and artisan in `migrations`, and both ran
 * the migrations of every package, so a migration logged on one side ran again on the other (the
 * sGallery table created twice). The first use of this log copies the rows only
 * `migrations_install` holds, and is never written again: 3.5 keeps that table for a site that
 * goes back to 3.5.9, 3.6 drops it (2026_11_23_000000_drop_migrations_install_table).
 *
 * Batch 0 is the installer's baseline: the copied rows and whatever the installer runs. Rollback
 * and reset never undo it, as artisan never undid what `migrations_install` recorded: the install
 * chain's down() drops the CMS tables.
 *
 * @since 3.5.10
 */
class MigrationRepository extends DatabaseMigrationRepository
{
    /** The installer's log up to 3.5.9. */
    public const INSTALLER_TABLE = 'migrations_install';

    public const BASELINE_BATCH = 0;

    private bool $installerLogImported = false;

    /**
     * @param string $table
     * @param bool $baseline Log every run in the baseline batch (the installer).
     */
    public function __construct(ConnectionResolverInterface $resolver, $table, private bool $baseline = false)
    {
        parent::__construct($resolver, $table);
    }

    /**
     * Copy the migrations only `migrations_install` recorded, into the baseline batch.
     *
     * Idempotent: a migration already in this log is not copied again.
     *
     * @return int The number of migrations copied.
     */
    public function importInstallerLog(): int
    {
        $connection = $this->getConnection();
        $schema = $connection->getSchemaBuilder();
        if ($this->table === self::INSTALLER_TABLE || !$schema->hasTable(self::INSTALLER_TABLE)) {
            $this->installerLogImported = true;

            return 0;
        }
        if (!$schema->hasTable($this->table)) {
            parent::createRepository();
        }

        $own = array_flip($connection->table($this->table)->useWritePdo()->pluck('migration')->all());
        $missing = [];
        $legacy = $connection->table(self::INSTALLER_TABLE)->useWritePdo()
            ->orderBy('batch')->orderBy('migration')->pluck('migration');
        foreach ($legacy as $migration) {
            $migration = (string) $migration;
            if (!isset($own[$migration])) {
                $own[$migration] = true;
                $missing[] = ['migration' => $migration, 'batch' => self::BASELINE_BATCH];
            }
        }

        foreach (array_chunk($missing, 100) as $rows) {
            $connection->table($this->table)->insert($rows);
        }
        // Only now: a copy that failed halfway is completed by the next use.
        $this->installerLogImported = true;

        return count($missing);
    }

    public function getTable(): string
    {
        return (string) $this->table;
    }

    /**
     * @return array<int, object>
     */
    public function getMigrationsByBatch($batch)
    {
        return (int) $batch === self::BASELINE_BATCH ? [] : parent::getMigrationsByBatch($batch);
    }

    /**
     * @return array<int, object>
     */
    public function getLast()
    {
        return (int) $this->getLastBatchNumber() === self::BASELINE_BATCH ? [] : parent::getLast();
    }

    /**
     * The migrations reset may undo: all but the baseline, latest first.
     *
     * @return array<int, string>
     */
    public function getRanSinceBaseline(): array
    {
        return $this->table()
            ->where('batch', '>', self::BASELINE_BATCH)
            ->orderBy('batch')
            ->orderBy('migration')
            ->pluck('migration')->all();
    }

    /**
     * @return int
     */
    public function getNextBatchNumber()
    {
        return $this->baseline ? self::BASELINE_BATCH : parent::getNextBatchNumber();
    }

    /**
     * @return void
     */
    public function createRepository()
    {
        parent::createRepository();
        $this->ensureInstallerLogImported();
    }

    /**
     * @return bool
     */
    public function repositoryExists()
    {
        $this->ensureInstallerLogImported();

        return parent::repositoryExists();
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        $this->ensureInstallerLogImported();

        return parent::table();
    }

    private function ensureInstallerLogImported(): void
    {
        if (!$this->installerLogImported) {
            $this->importInstallerLog();
        }
    }
}
