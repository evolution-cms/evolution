<?php namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds default user roles and their role-permission mappings for a fresh installation.
 *
 * Safety: this seeder is idempotent and only upserts missing baseline
 * role and role-permission rows needed by the manager ACL model.
 */
class UserRolesTableSeeder extends Seeder
{
    protected function upsertRoles(array $roles): void
    {
        foreach ($roles as $role) {
            DB::table('user_roles')->updateOrInsert(
                ['id' => (int) $role['id']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                ]
            );
        }
    }

    protected function upsertRolePermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            $exists = DB::table('role_permissions')
                ->where('role_id', (int) $permission['role_id'])
                ->where('permission', $permission['permission'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('role_permissions')->insert([
                'permission' => $permission['permission'],
                'role_id' => (int) $permission['role_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Execute the database seeder.
     *
     * Seeds default roles and role-permission mappings and repairs missing baseline rows.
     */
    public function run(): void
    {
        if (!Schema::hasTable('user_roles') || !Schema::hasTable('role_permissions')) {
            return;
        }

        $this->upsertRoles([
            [
                'id' => 1,
                'name'        => 'Administrator',
                'description' => 'Site administrators have full access to all functions',
            ],
            [
                'id' => 2,
                'name'        => 'Editor',
                'description' => 'Limited to managing content',
            ],
            [
                'id' => 3,
                'name'        => 'Publisher',
                'description' => 'Editor with expanded permissions including manage users, update Elements and site settings',
            ]
        ]);

        // Administrator role permissions
        $insertArray = [
            ['permission' => 'frames', 'role_id' => 1],
            ['permission' => 'home', 'role_id' => 1],
            ['permission' => 'widget_recent_info', 'role_id' => 1],
            ['permission' => 'help', 'role_id' => 1],
            ['permission' => 'role_actionok', 'role_id' => 1],
            ['permission' => 'error_dialog', 'role_id' => 1],
            ['permission' => 'about', 'role_id' => 1],
            ['permission' => 'widget_online_info', 'role_id' => 1],
            ['permission' => 'change_password', 'role_id' => 1],
            ['permission' => 'save_password', 'role_id' => 1],
            ['permission' => 'view_document', 'role_id' => 1],
            ['permission' => 'new_document', 'role_id' => 1],
            ['permission' => 'edit_document', 'role_id' => 1],
            ['permission' => 'change_resourcetype', 'role_id' => 1],
            ['permission' => 'save_document', 'role_id' => 1],
            ['permission' => 'publish_document', 'role_id' => 1],
            ['permission' => 'delete_document', 'role_id' => 1],
            ['permission' => 'empty_trash', 'role_id' => 1],
            ['permission' => 'empty_cache', 'role_id' => 1],
            ['permission' => 'view_unpublished', 'role_id' => 1],
            ['permission' => 'file_manager', 'role_id' => 1],
            ['permission' => 'assets_files', 'role_id' => 1],
            ['permission' => 'assets_images', 'role_id' => 1],
            ['permission' => 'category_manager', 'role_id' => 1],
            ['permission' => 'new_module', 'role_id' => 1],
            ['permission' => 'edit_module', 'role_id' => 1],
            ['permission' => 'save_module', 'role_id' => 1],
            ['permission' => 'delete_module', 'role_id' => 1],
            ['permission' => 'exec_module', 'role_id' => 1],
            ['permission' => 'list_module', 'role_id' => 1],
            ['permission' => 'new_template', 'role_id' => 1],
            ['permission' => 'edit_template', 'role_id' => 1],
            ['permission' => 'save_template', 'role_id' => 1],
            ['permission' => 'delete_template', 'role_id' => 1],
            ['permission' => 'new_snippet', 'role_id' => 1],
            ['permission' => 'edit_snippet', 'role_id' => 1],
            ['permission' => 'save_snippet', 'role_id' => 1],
            ['permission' => 'delete_snippet', 'role_id' => 1],
            ['permission' => 'new_chunk', 'role_id' => 1],
            ['permission' => 'edit_chunk', 'role_id' => 1],
            ['permission' => 'save_chunk', 'role_id' => 1],
            ['permission' => 'delete_chunk', 'role_id' => 1],
            ['permission' => 'new_plugin', 'role_id' => 1],
            ['permission' => 'edit_plugin', 'role_id' => 1],
            ['permission' => 'save_plugin', 'role_id' => 1],
            ['permission' => 'delete_plugin', 'role_id' => 1],
            ['permission' => 'new_user', 'role_id' => 1],
            ['permission' => 'edit_user', 'role_id' => 1],
            ['permission' => 'save_user', 'role_id' => 1],
            ['permission' => 'delete_user', 'role_id' => 1],
            ['permission' => 'access_permissions', 'role_id' => 1],
            ['permission' => 'manage_groups', 'role_id' => 1],
            ['permission' => 'manage_document_permissions', 'role_id' => 1],
            ['permission' => 'manage_module_permissions', 'role_id' => 1],
            ['permission' => 'manage_tv_permissions', 'role_id' => 1],
            ['permission' => 'new_role', 'role_id' => 1],
            ['permission' => 'edit_role', 'role_id' => 1],
            ['permission' => 'save_role', 'role_id' => 1],
            ['permission' => 'delete_role', 'role_id' => 1],
            ['permission' => 'view_eventlog', 'role_id' => 1],
            ['permission' => 'delete_eventlog', 'role_id' => 1],
            ['permission' => 'logs', 'role_id' => 1],
            ['permission' => 'settings', 'role_id' => 1],
            ['permission' => 'bk_manager', 'role_id' => 1],
            ['permission' => 'remove_locks', 'role_id' => 1],
            ['permission' => 'display_locks', 'role_id' => 1],
        ];
        $this->upsertRolePermissions($insertArray);

        // Editor role permissions
        $insertArray = [
            ['permission' => 'frames', 'role_id' => 2],
            ['permission' => 'home', 'role_id' => 2],
            ['permission' => 'widget_recent_info', 'role_id' => 2],
            ['permission' => 'help', 'role_id' => 2],
            ['permission' => 'role_actionok', 'role_id' => 2],
            ['permission' => 'error_dialog', 'role_id' => 2],
            ['permission' => 'about', 'role_id' => 2],
            ['permission' => 'widget_online_info', 'role_id' => 2],
            ['permission' => 'change_password', 'role_id' => 2],
            ['permission' => 'save_password', 'role_id' => 2],
            ['permission' => 'view_document', 'role_id' => 2],
            ['permission' => 'new_document', 'role_id' => 2],
            ['permission' => 'edit_document', 'role_id' => 2],
            ['permission' => 'change_resourcetype', 'role_id' => 2],
            ['permission' => 'save_document', 'role_id' => 2],
            ['permission' => 'publish_document', 'role_id' => 2],
            ['permission' => 'delete_document', 'role_id' => 2],
            ['permission' => 'empty_cache', 'role_id' => 2],
            ['permission' => 'view_unpublished', 'role_id' => 2],
            ['permission' => 'file_manager', 'role_id' => 2],
            ['permission' => 'assets_files', 'role_id' => 2],
            ['permission' => 'assets_images', 'role_id' => 2],
            ['permission' => 'exec_module', 'role_id' => 2],
            ['permission' => 'list_module', 'role_id' => 2],
            ['permission' => 'edit_chunk', 'role_id' => 2],
            ['permission' => 'save_chunk', 'role_id' => 2],
            ['permission' => 'remove_locks', 'role_id' => 2],
            ['permission' => 'display_locks', 'role_id' => 2],
            ['permission' => 'access_permissions', 'role_id' => 2],
            ['permission' => 'manage_document_permissions', 'role_id' => 2],
        ];
        $this->upsertRolePermissions($insertArray);

        // Publisher role permissions
        $insertArray = [
            ['permission' => 'frames', 'role_id' => 3],
            ['permission' => 'home', 'role_id' => 3],
            ['permission' => 'widget_recent_info', 'role_id' => 3],
            ['permission' => 'help', 'role_id' => 3],
            ['permission' => 'role_actionok', 'role_id' => 3],
            ['permission' => 'error_dialog', 'role_id' => 3],
            ['permission' => 'about', 'role_id' => 3],
            ['permission' => 'widget_online_info', 'role_id' => 3],
            ['permission' => 'change_password', 'role_id' => 3],
            ['permission' => 'save_password', 'role_id' => 3],
            ['permission' => 'view_document', 'role_id' => 3],
            ['permission' => 'new_document', 'role_id' => 3],
            ['permission' => 'edit_document', 'role_id' => 3],
            ['permission' => 'change_resourcetype', 'role_id' => 3],
            ['permission' => 'save_document', 'role_id' => 3],
            ['permission' => 'publish_document', 'role_id' => 3],
            ['permission' => 'delete_document', 'role_id' => 3],
            ['permission' => 'empty_trash', 'role_id' => 3],
            ['permission' => 'empty_cache', 'role_id' => 3],
            ['permission' => 'view_unpublished', 'role_id' => 3],
            ['permission' => 'file_manager', 'role_id' => 3],
            ['permission' => 'assets_files', 'role_id' => 3],
            ['permission' => 'assets_images', 'role_id' => 3],
            ['permission' => 'exec_module', 'role_id' => 3],
            ['permission' => 'list_module', 'role_id' => 3],
            ['permission' => 'new_template', 'role_id' => 3],
            ['permission' => 'edit_template', 'role_id' => 3],
            ['permission' => 'save_template', 'role_id' => 3],
            ['permission' => 'delete_template', 'role_id' => 3],
            ['permission' => 'new_chunk', 'role_id' => 3],
            ['permission' => 'edit_chunk', 'role_id' => 3],
            ['permission' => 'save_chunk', 'role_id' => 3],
            ['permission' => 'delete_chunk', 'role_id' => 3],
            ['permission' => 'new_user', 'role_id' => 3],
            ['permission' => 'edit_user', 'role_id' => 3],
            ['permission' => 'save_user', 'role_id' => 3],
            ['permission' => 'delete_user', 'role_id' => 3],
            ['permission' => 'logs', 'role_id' => 3],
            ['permission' => 'settings', 'role_id' => 3],
            ['permission' => 'bk_manager', 'role_id' => 3],
            ['permission' => 'remove_locks', 'role_id' => 3],
            ['permission' => 'display_locks', 'role_id' => 3],
            ['permission' => 'access_permissions', 'role_id' => 3],
            ['permission' => 'manage_document_permissions', 'role_id' => 3],
        ];
        $this->upsertRolePermissions($insertArray);
    }
}
