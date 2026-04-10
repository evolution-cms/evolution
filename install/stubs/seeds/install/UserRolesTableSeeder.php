<?php namespace EvolutionCMS\Installer\Install;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRolesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('user_roles')->delete();
        DB::table('user_roles')->insert([
            0 =>
                [
                    'name'        => 'Administrator',
                    'description' => 'Site administrators have full access to all functions',
                ],
            1 =>
                [
                    'name'        => 'Editor',
                    'description' => 'Limited to managing content',
                ],
            2 =>
                [
                    'name'        => 'Publisher',
                    'description' => 'Editor with expanded permissions including manage users, update Elements and site settings',
                ]
        ]);
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
        DB::table('role_permissions')->insert($insertArray);
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
        DB::table('role_permissions')->insert($insertArray);
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
            ['permission' => 'manage_document_permissions', 'role_id' => 3]
        ];
        DB::table('role_permissions')->insert($insertArray);
    }
}
