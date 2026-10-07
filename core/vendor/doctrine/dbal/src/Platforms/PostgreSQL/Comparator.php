<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Platforms\PostgreSQL;

use Doctrine\DBAL\Schema\Comparator as BaseComparator;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;

/**
 * Compares schemas in the context of PostgreSQL platform.
 *
 * PostgreSQL treats "default" collation as implicit, so an explicit "default" value should be ignored when comparing
 * schemas.
 */
class Comparator extends BaseComparator
{
    public function compareTables(Table $oldTable, Table $newTable): TableDiff
    {
        return parent::compareTables(
            $this->normalizeColumns($oldTable),
            $this->normalizeColumns($newTable),
        );
    }

    private function normalizeColumns(Table $table): Table
    {
        $table = clone $table;

        foreach ($table->getColumns() as $column) {
            $options = $column->getPlatformOptions();

            if (! isset($options['collation']) || $options['collation'] !== 'default') {
                continue;
            }

            unset($options['collation']);
            $column->setPlatformOptions($options);
        }

        return $table;
    }
}
