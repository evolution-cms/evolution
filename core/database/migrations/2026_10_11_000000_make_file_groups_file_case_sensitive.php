<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * File group keys are paths, and on a case-sensitive file system report.txt and REPORT.txt are two
 * files. A case-insensitive column collation (the database default for utf8mb4_unicode_ci) treats
 * their rows as one, and the unique index then refuses the second one: the copy stays unrestricted.
 */
class MakeFileGroupsFileCaseSensitive extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('file_groups') || DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $table = DB::getTablePrefix() . 'file_groups';
        $current = DB::selectOne(
            'SELECT COLLATION_NAME AS collation FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, 'file']
        );
        if (!$current || str_ends_with((string) $current->collation, '_bin')) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `file` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT ''");
    }

    public function down()
    {
        // the case-sensitive column is what the paths need; nothing to restore
    }
}
