<?php

namespace Tests\Unit\Install;

use Tests\TestCase;

final class DashboardWidgetRolePermissionUpgradeTest extends TestCase
{
    public function testUpdateSeederRemapsLegacyPermissionKeysForExistingInstalls(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 4) . '/install/stubs/seeds/update/UserPermissionsTableSeeder.php');

        self::assertStringContainsString("replacePermission(\n            'logout',", $source);
        self::assertStringContainsString("'widget_recent_info'", $source);
        self::assertStringContainsString("'role_widget_recent_info'", $source);
        self::assertStringContainsString("replacePermission(\n            'credits',", $source);
        self::assertStringContainsString("'widget_online_info'", $source);
        self::assertStringContainsString("'role_widget_online_info'", $source);
        self::assertStringContainsString("DB::table('role_permissions')->where('permission', \$oldKey)->update([", $source);
    }

    public function testFreshInstallRoleSeedersUseNewWidgetPermissionKeys(): void
    {
        $coreSource = (string) file_get_contents(dirname(__DIR__, 4) . '/core/database/seeders/UserRolesTableSeeder.php');
        $installSource = (string) file_get_contents(dirname(__DIR__, 4) . '/install/stubs/seeds/install/UserRolesTableSeeder.php');

        foreach ([$coreSource, $installSource] as $source) {
            self::assertStringNotContainsString("['permission' => 'logout'", $source);
            self::assertStringNotContainsString("['permission' => 'credits'", $source);
            self::assertStringContainsString("['permission' => 'widget_recent_info', 'role_id' => 1]", $source);
            self::assertStringContainsString("['permission' => 'widget_online_info', 'role_id' => 1]", $source);
            self::assertStringContainsString("['permission' => 'widget_recent_info', 'role_id' => 2]", $source);
            self::assertStringContainsString("['permission' => 'widget_online_info', 'role_id' => 2]", $source);
            self::assertStringContainsString("['permission' => 'widget_recent_info', 'role_id' => 3]", $source);
            self::assertStringContainsString("['permission' => 'widget_online_info', 'role_id' => 3]", $source);
        }
    }
}
