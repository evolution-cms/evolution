<?php namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds permission groups and permissions for a fresh installation.
 *
 * Safety: this seeder is idempotent and only repairs or upserts missing
 * baseline permission groups and permission definitions.
 */
class UserPermissionsTableSeeder extends Seeder
{
    protected function upsertPermissionGroups(array $groups): void
    {
        foreach ($groups as $group) {
            DB::table('permissions_groups')->updateOrInsert(
                ['id' => (int) $group['id']],
                [
                    'name' => $group['name'],
                    'lang_key' => $group['lang_key'],
                ]
            );
        }
    }

    protected function upsertPermissions(array $permissions): void
    {
        foreach ($permissions as $permission) {
            $exists = DB::table('permissions')->where('key', $permission['key'])->exists();

            if ($exists) {
                DB::table('permissions')
                    ->where('key', $permission['key'])
                    ->update([
                        'name' => $permission['name'],
                        'lang_key' => $permission['lang_key'],
                        'disabled' => $permission['disabled'],
                        'group_id' => $permission['group_id'],
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('permissions')->insert([
                'name' => $permission['name'],
                'lang_key' => $permission['lang_key'],
                'key' => $permission['key'],
                'disabled' => $permission['disabled'],
                'group_id' => $permission['group_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Execute the database seeder.
     *
     * Seeds permission groups and permissions and repairs missing baseline rows.
     */
    public function run(): void
    {
        if (!Schema::hasTable('permissions_groups') || !Schema::hasTable('permissions')) {
            return;
        }

        // Insert permission groups
        $insertArray = [
            ['id' => 1, 'name' => 'General', 'lang_key' => 'page_data_general'],
            ['id' => 2, 'name' => 'Content Management', 'lang_key' => 'role_content_management'],
            ['id' => 3, 'name' => 'File Management', 'lang_key' => 'role_file_management'],
            ['id' => 4, 'name' => 'Category Management', 'lang_key' => 'category_management'],
            ['id' => 5, 'name' => 'Module Management', 'lang_key' => 'role_module_management'],
            ['id' => 6, 'name' => 'Template Management', 'lang_key' => 'role_template_management'],
            ['id' => 7, 'name' => 'Snippet Management', 'lang_key' => 'role_snippet_management'],
            ['id' => 8, 'name' => 'Chunk Management', 'lang_key' => 'role_chunk_management'],
            ['id' => 9, 'name' => 'Plugin Management', 'lang_key' => 'role_plugin_management'],
            ['id' => 10, 'name' => 'User Management', 'lang_key' => 'role_user_management'],
            ['id' => 11, 'name' => 'Permissions', 'lang_key' => 'role_udperms'],
            ['id' => 12, 'name' => 'Role Management', 'lang_key' => 'role_role_management'],
            ['id' => 13, 'name' => 'Events Log Management', 'lang_key' => 'role_eventlog_management'],
            ['id' => 14, 'name' => 'Config Management', 'lang_key' => 'role_config_management'],
        ];
        $this->upsertPermissionGroups($insertArray);

        // Insert permissions - General
        $insertArray = [
            [
                'name'     => 'Request manager frames',
                'lang_key' => 'role_frames',
                'key'      => 'frames',
                'disabled' => 1,
                'group_id' => 1
            ],
            [
                'name'     => 'Request manager intro page',
                'lang_key' => 'role_home',
                'key'      => 'home',
                'disabled' => 1,
                'group_id' => 1
            ],
            [
                'name'     => 'View widget Recently edited/created Resources',
                'lang_key' => 'role_widget_recent_info',
                'key'      => 'widget_recent_info',
                'disabled' => 0,
                'group_id' => 1
            ],
            [
                'name'     => 'View help pages',
                'lang_key' => 'role_help',
                'key'      => 'help',
                'disabled' => 0,
                'group_id' => 1
            ],
            [
                'name'     => 'View action completed screen',
                'lang_key' => 'role_actionok',
                'key'      => 'action_ok',
                'disabled' => 1,
                'group_id' => 1
            ],
            [
                'name'     => 'View error dialog',
                'lang_key' => 'role_errors',
                'key'      => 'error_dialog',
                'disabled' => 1,
                'group_id' => 1
            ],
            [
                'name'     => 'View the about page',
                'lang_key' => 'role_about',
                'key'      => 'about',
                'disabled' => 1,
                'group_id' => 1
            ],
            [
                'name'     => 'View widget Online users',
                'lang_key' => 'role_widget_online_info',
                'key'      => 'widget_online_info',
                'disabled' => 0,
                'group_id' => 1
            ],
            [
                'name'     => 'Change password',
                'lang_key' => 'role_change_password',
                'key'      => 'change_password',
                'disabled' => 0,
                'group_id' => 1
            ],
            [
                'name'     => 'Save password',
                'lang_key' => 'role_save_password',
                'key'      => 'save_password',
                'disabled' => 0,
                'group_id' => 1
            ],
        ];
        $this->upsertPermissions($insertArray);

        // Insert permissions - Content Management
        $insertArray = [
            [
                'name'     => 'View a Resource\'s data',
                'key'      => 'view_document',
                'lang_key' => 'role_view_docdata',
                'disabled' => 1,
                'group_id' => 2
            ],
            [
                'name'     => 'Create new Resources',
                'key'      => 'new_document',
                'lang_key' => 'role_create_doc',
                'disabled' => 0,
                'group_id' => 2
            ],
            [
                'name'     => 'Edit a Resource',
                'key'      => 'edit_document',
                'lang_key' => 'role_edit_doc',
                'disabled' => 0,
                'group_id' => 2
            ],
            [
                'name'     => 'Change Resource-Type',
                'key'      => 'change_resourcetype',
                'lang_key' => 'role_change_resourcetype',
                'disabled' => 0,
                'group_id' => 2
            ],
            [
                'name'     => 'Save Resources',
                'key'      => 'save_document',
                'lang_key' => 'role_save_doc',
                'disabled' => 0,
                'group_id' => 2
            ],
            [
                'name'     => 'Publish Resources',
                'key'      => 'publish_document',
                'lang_key' => 'role_publish_doc',
                'disabled' => 0,
                'group_id' => 2
            ],
            [
                'name'     => 'Delete Resources',
                'key'      => 'delete_document',
                'lang_key' => 'role_delete_doc',
                'disabled' => 0,
                'group_id' => 2
            ],
            [
                'name'     => 'Permanently purge deleted Resources',
                'key'      => 'empty_trash',
                'lang_key' => 'role_empty_trash',
                'disabled' => 0,
                'group_id' => 2
            ],
            [
                'name'     => 'Empty the site\'s cache',
                'key'      => 'empty_cache',
                'lang_key' => 'role_cache_refresh',
                'disabled' => 0,
                'group_id' => 2
            ],
            [
                'name'     => 'View Unpublished Resources',
                'key'      => 'view_unpublished',
                'lang_key' => 'role_view_unpublished',
                'disabled' => 0,
                'group_id' => 2
            ],
        ];
        $this->upsertPermissions($insertArray);

        // Insert permissions - File Management, Category, Module, Template
        $insertArray = [
            [
                'name'     => 'Use the file manager (full root access)',
                'key'      => 'file_manager',
                'lang_key' => 'role_file_manager',
                'disabled' => 0,
                'group_id' => 3
            ],
            [
                'name'     => 'Manage assets/files',
                'key'      => 'assets_files',
                'lang_key' => 'role_assets_files',
                'disabled' => 0,
                'group_id' => 3
            ],
            [
                'name'     => 'Manage assets/images',
                'key'      => 'assets_images',
                'lang_key' => 'role_assets_images',
                'disabled' => 0,
                'group_id' => 3
            ],
            [
                'name'     => 'Use the Category Manager',
                'key'      => 'category_manager',
                'lang_key' => 'role_category_manager',
                'disabled' => 0,
                'group_id' => 4
            ],
            [
                'name'     => 'Create new Module',
                'key'      => 'new_module',
                'lang_key' => 'role_new_module',
                'disabled' => 0,
                'group_id' => 5
            ],
            [
                'name'     => 'Edit Module',
                'key'      => 'edit_module',
                'lang_key' => 'role_edit_module',
                'disabled' => 0,
                'group_id' => 5
            ],
            [
                'name'     => 'Save Module',
                'key'      => 'save_module',
                'lang_key' => 'role_save_module',
                'disabled' => 0,
                'group_id' => 5
            ],
            [
                'name'     => 'Delete Module',
                'key'      => 'delete_module',
                'lang_key' => 'role_delete_module',
                'disabled' => 0,
                'group_id' => 5
            ],
            [
                'name'     => 'Run Module',
                'key'      => 'exec_module',
                'lang_key' => 'role_run_module',
                'disabled' => 0,
                'group_id' => 5
            ],
            [
                'name'     => 'List Module',
                'key'      => 'list_module',
                'lang_key' => 'role_list_module',
                'disabled' => 0,
                'group_id' => 5
            ],
            [
                'name'     => 'Create new site Templates',
                'key'      => 'new_template',
                'lang_key' => 'role_create_template',
                'disabled' => 0,
                'group_id' => 6
            ],
            [
                'name'     => 'Edit site Templates',
                'key'      => 'edit_template',
                'lang_key' => 'role_edit_template',
                'disabled' => 0,
                'group_id' => 6
            ],
            [
                'name'     => 'Save Templates',
                'key'      => 'save_template',
                'lang_key' => 'role_save_template',
                'disabled' => 0,
                'group_id' => 6
            ],
            [
                'name'     => 'Delete Templates',
                'key'      => 'delete_template',
                'lang_key' => 'role_delete_template',
                'disabled' => 0,
                'group_id' => 6
            ],
        ];
        $this->upsertPermissions($insertArray);

        // Insert permissions - Snippet, Chunk, Plugin, User, Permissions
        $insertArray = [
            [
                'name'     => 'Create new Snippets',
                'key'      => 'new_snippet',
                'lang_key' => 'role_create_snippet',
                'disabled' => 0,
                'group_id' => 7
            ],
            [
                'name'     => 'Edit Snippets',
                'key'      => 'edit_snippet',
                'lang_key' => 'role_edit_snippet',
                'disabled' => 0,
                'group_id' => 7
            ],
            [
                'name'     => 'Save Snippets',
                'key'      => 'save_snippet',
                'lang_key' => 'role_save_snippet',
                'disabled' => 0,
                'group_id' => 7
            ],
            [
                'name'     => 'Delete Snippets',
                'key'      => 'delete_snippet',
                'lang_key' => 'role_delete_snippet',
                'disabled' => 0,
                'group_id' => 7
            ],
            [
                'name'     => 'Create new Chunks',
                'key'      => 'new_chunk',
                'lang_key' => 'role_create_chunk',
                'disabled' => 0,
                'group_id' => 8
            ],
            [
                'name'     => 'Edit Chunks',
                'key'      => 'edit_chunk',
                'lang_key' => 'role_edit_chunk',
                'disabled' => 0,
                'group_id' => 8
            ],
            [
                'name'     => 'Save Chunks',
                'key'      => 'save_chunk',
                'lang_key' => 'role_save_chunk',
                'disabled' => 0,
                'group_id' => 8
            ],
            [
                'name'     => 'Delete Chunks',
                'key'      => 'delete_chunk',
                'lang_key' => 'role_delete_chunk',
                'disabled' => 0,
                'group_id' => 8
            ],
            [
                'name'     => 'Create new Plugins',
                'key'      => 'new_plugin',
                'lang_key' => 'role_create_plugin',
                'disabled' => 0,
                'group_id' => 9
            ],
            [
                'name'     => 'Edit Plugins',
                'key'      => 'edit_plugin',
                'lang_key' => 'role_edit_plugin',
                'disabled' => 0,
                'group_id' => 9
            ],
            [
                'name'     => 'Save Plugins',
                'key'      => 'save_plugin',
                'lang_key' => 'role_save_plugin',
                'disabled' => 0,
                'group_id' => 9
            ],
            [
                'name'     => 'Delete Plugins',
                'key'      => 'delete_plugin',
                'lang_key' => 'role_delete_plugin',
                'disabled' => 0,
                'group_id' => 9
            ],
            [
                'name'     => 'Create new users',
                'key'      => 'new_user',
                'lang_key' => 'role_new_user',
                'disabled' => 0,
                'group_id' => 10
            ],
            [
                'name'     => 'Edit users',
                'key'      => 'edit_user',
                'lang_key' => 'role_edit_user',
                'disabled' => 0,
                'group_id' => 10
            ],
            [
                'name'     => 'Save users',
                'key'      => 'save_user',
                'lang_key' => 'role_save_user',
                'disabled' => 0,
                'group_id' => 10
            ],
            [
                'name'     => 'Delete users',
                'key'      => 'delete_user',
                'lang_key' => 'role_delete_user',
                'disabled' => 0,
                'group_id' => 10
            ],
            [
                'name'     => 'Manager access permissions',
                'key'      => 'access_permissions',
                'lang_key' => 'manager_access_permissions',
                'disabled' => 0,
                'group_id' => 11
            ],
            [
                'name'     => 'Manage document and user groups',
                'key'      => 'manage_groups',
                'lang_key' => 'manage_groups',
                'disabled' => 0,
                'group_id' => 11
            ],
            [
                'name'     => 'Manage document permissions',
                'key'      => 'manage_document_permissions',
                'lang_key' => 'manage_document_permissions',
                'disabled' => 0,
                'group_id' => 11
            ],
            [
                'name'     => 'Manage module permissions',
                'key'      => 'manage_module_permissions',
                'lang_key' => 'manage_module_permissions',
                'disabled' => 0,
                'group_id' => 11
            ],
            [
                'name'     => 'Manage TV permissions',
                'key'      => 'manage_tv_permissions',
                'lang_key' => 'manage_tv_permissions',
                'disabled' => 0,
                'group_id' => 11
            ],
        ];
        $this->upsertPermissions($insertArray);

        // Insert permissions - Role, Events Log, Config
        $insertArray = [
            [
                'name'     => 'Create new roles',
                'key'      => 'new_role',
                'lang_key' => 'role_new_role',
                'disabled' => 0,
                'group_id' => 12
            ],
            [
                'name'     => 'Edit roles',
                'key'      => 'edit_role',
                'lang_key' => 'role_edit_role',
                'disabled' => 0,
                'group_id' => 12
            ],
            [
                'name'     => 'Save roles',
                'key'      => 'save_role',
                'lang_key' => 'role_save_role',
                'disabled' => 0,
                'group_id' => 12
            ],
            [
                'name'     => 'Delete roles',
                'key'      => 'delete_role',
                'lang_key' => 'role_delete_role',
                'disabled' => 0,
                'group_id' => 12
            ],
            [
                'name'     => 'View event log',
                'key'      => 'view_eventlog',
                'lang_key' => 'role_view_eventlog',
                'disabled' => 0,
                'group_id' => 13
            ],
            [
                'name'     => 'Delete event log',
                'key'      => 'delete_eventlog',
                'lang_key' => 'role_delete_eventlog',
                'disabled' => 0,
                'group_id' => 13
            ],
            [
                'name'     => 'View system logs',
                'key'      => 'logs',
                'lang_key' => 'role_view_logs',
                'disabled' => 0,
                'group_id' => 14
            ],
            [
                'name'     => 'Change site settings',
                'key'      => 'settings',
                'lang_key' => 'role_edit_settings',
                'disabled' => 0,
                'group_id' => 14
            ],
            [
                'name'     => 'Use the Backup Manager',
                'key'      => 'bk_manager',
                'lang_key' => 'role_bk_manager',
                'disabled' => 0,
                'group_id' => 14
            ],
            [
                'name'     => 'Remove Locks',
                'key'      => 'remove_locks',
                'lang_key' => 'role_remove_locks',
                'disabled' => 0,
                'group_id' => 14
            ],
            [
                'name'     => 'Display Locks',
                'key'      => 'display_locks',
                'lang_key' => 'role_display_locks',
                'disabled' => 0,
                'group_id' => 14
            ],
        ];
        $this->upsertPermissions($insertArray);
    }
}
