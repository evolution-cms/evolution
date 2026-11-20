<?php

namespace EvolutionCMS\Support;

use Illuminate\Database\Migrations\DatabaseMigrationRepository;

/**
 * Migration log that can tell what the other side of the site already ran.
 *
 * The installer records migrations in `migrations_install`, artisan in `migrations`
 * (core/config/database/migrations.php), and both run the migrations of every package. A package
 * migration recorded on one side ran again on the other: `make:site` after a web install, or the
 * installer's upgrade mode after `make:site`, created the sGallery table a second time and stopped
 * with "table already exists".
 *
 * getRan() and everything else keep to this side's own table, so reset, rollback and batches stay
 * what Laravel expects. ranElsewhere() lists the other side's migrations: SharedMigrator leaves
 * them out of the pending ones, and migrate:status shows them as run, with their table.
 *
 * @since 3.5.10
 */
class SharedMigrationRepository extends DatabaseMigrationRepository
{
    /** The two tables, each the other's counterpart. */
    public const TABLES = ['migrations', 'migrations_install'];

    /**
     * Migrations only the other side recorded, with their batch there.
     *
     * @return array<string, int>
     */
    public function ranElsewhere(): array
    {
        $other = $this->counterpartTable();
        if ($other === null || !$this->getConnection()->getSchemaBuilder()->hasTable($other)) {
            return [];
        }

        $own = array_flip($this->getRan());
        $elsewhere = [];
        $rows = $this->getConnection()->table($other)->orderBy('batch')->orderBy('migration')->get(['migration', 'batch']);
        foreach ($rows as $row) {
            if (!isset($own[$row->migration])) {
                $elsewhere[(string) $row->migration] = (int) $row->batch;
            }
        }

        return $elsewhere;
    }

    /**
     * The table the other side records in, or null for a table of another name.
     */
    public function counterpartTable(): ?string
    {
        $index = array_search($this->table, self::TABLES, true);

        return $index === false ? null : self::TABLES[1 - $index];
    }
}
