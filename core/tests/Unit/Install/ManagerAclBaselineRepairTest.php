<?php

namespace Tests\Unit\Install;

use Tests\TestCase;

final class ManagerAclBaselineRepairTest extends TestCase
{
    public function testSystemTasksMigrationIsConsolidatedIntoSingleSlice(): void
    {
        $files = glob(dirname(__DIR__, 4) . '/core/database/migrations/2026_04_12_*.php') ?: [];

        self::assertCount(1, $files);
        self::assertStringEndsWith('2026_04_12_000000_create_system_cli_tasks_tables.php', $files[0]);
    }

    public function testUserRoleSeederRepairsMissingBaselineRowsInsteadOfSkippingNonEmptyTables(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/core/database/seeders/UserRolesTableSeeder.php');

        self::assertStringNotContainsString("DB::table('user_roles')->count() > 0 || DB::table('role_permissions')->count() > 0", $source);
        self::assertStringContainsString("updateOrInsert(", $source);
        self::assertStringContainsString("'id' => 1", $source);
        self::assertStringContainsString("where('role_id', (int) \$permission['role_id'])", $source);
        self::assertStringContainsString("where('permission', \$permission['permission'])", $source);
    }

    public function testUserPermissionSeederRepairsMissingBaselineRowsInsteadOfSkippingNonEmptyTables(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/core/database/seeders/UserPermissionsTableSeeder.php');

        self::assertStringNotContainsString("DB::table('permissions_groups')->count() > 0 || DB::table('permissions')->count() > 0", $source);
        self::assertStringContainsString("updateOrInsert(", $source);
        self::assertStringContainsString("where('key', \$permission['key'])", $source);
        self::assertStringContainsString("'key' => \$permission['key']", $source);
    }

    public function testRepairMigrationBootstrapsMissingManagerAclBaseline(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/core/database/migrations/2026_04_12_000000_create_system_cli_tasks_tables.php');

        self::assertStringContainsString("new UserPermissionsTableSeeder()", $source);
        self::assertStringContainsString("new UserRolesTableSeeder()", $source);
        self::assertStringContainsString("where('id', 1)", $source);
        self::assertStringContainsString("where('key', 'access_permissions')", $source);
        self::assertStringContainsString("where('role_id', 1)->where('permission', 'access_permissions')", $source);
        self::assertStringContainsString("repairAclBaselineIfNeeded()", $source);
    }
}
