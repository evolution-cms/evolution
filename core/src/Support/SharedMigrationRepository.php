<?php

namespace EvolutionCMS\Support;

use Illuminate\Database\Migrations\DatabaseMigrationRepository;

/**
 * Migration log that counts what the other side of the site already ran.
 *
 * The installer records migrations in `migrations_install`, artisan in `migrations`
 * (core/config/database/migrations.php), and both run the migrations of every package. A package
 * migration recorded on one side ran again on the other: `make:site` after a web install, or the
 * installer's upgrade mode after `make:site`, created the sGallery table a second time and stopped
 * with "table already exists". A migration is applied once, wherever it was recorded. Each side
 * still writes, rolls back and reports batches in its own table.
 *
 * @since 3.5.10
 */
class SharedMigrationRepository extends DatabaseMigrationRepository
{
    /** The two tables, each the other's counterpart. */
    public const TABLES = ['migrations', 'migrations_install'];

    /**
     * Migrations recorded in this side's table, then those only the other side recorded.
     *
     * @return array<int, string>
     */
    public function getRan()
    {
        $ran = parent::getRan();
        $other = $this->counterpartTable();
        if ($other === null || !$this->getConnection()->getSchemaBuilder()->hasTable($other)) {
            return $ran;
        }

        $elsewhere = $this->getConnection()->table($other)
            ->orderBy('batch')
            ->orderBy('migration')
            ->pluck('migration')
            ->all();

        return array_values(array_unique(array_merge($ran, $elsewhere)));
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
