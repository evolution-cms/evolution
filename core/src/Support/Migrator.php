<?php

namespace EvolutionCMS\Support;

use Illuminate\Console\View\Components\Info;
use Illuminate\Database\Migrations\Migrator as BaseMigrator;
use Illuminate\Support\Arr;

/**
 * Migrator whose reset leaves the installer's baseline (MigrationRepository::BASELINE_BATCH) alone.
 *
 * @since 3.5.10
 */
class Migrator extends BaseMigrator
{
    /**
     * @param array<int, string>|string $paths
     * @param bool $pretend
     * @return array<int, string>
     */
    public function reset($paths = [], $pretend = false)
    {
        if (!$this->repository instanceof MigrationRepository) {
            return parent::reset($paths, $pretend);
        }

        $migrations = array_reverse($this->repository->getRanSinceBaseline());
        if (count($migrations) === 0) {
            $this->write(Info::class, 'Nothing to rollback.');

            return [];
        }

        return tap($this->resetMigrations($migrations, Arr::wrap($paths), $pretend), function () {
            if ($this->output) {
                $this->output->writeln('');
            }
        });
    }
}
