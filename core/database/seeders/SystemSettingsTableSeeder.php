<?php namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds baseline system settings for a fresh installation.
 *
 * Safety:
 * - Does not delete existing settings.
 * - Uses insert-or-ignore semantics (primary key: `setting_name`) so existing values are preserved.
 */
class SystemSettingsTableSeeder extends Seeder
{
    /**
     * Execute the database seeder.
     *
     * Inserts baseline settings using insert-or-ignore semantics so existing values are preserved.
     */
    public function run(): void
    {
        if (!Schema::hasTable('system_settings')) {
            return;
        }

        DB::table('system_settings')->insertOrIgnore([
            [
                'setting_name' => 'settings_version',
                'setting_value' => '',
            ],
            [
                'setting_name' => 'server_offset_time',
                'setting_value' => '0',
            ],
            [
                'setting_name' => 'modx_charset',
                'setting_value' => 'UTF-8',
            ],
            [
                'setting_name' => 'site_name',
                'setting_value' => 'My Evolution Site',
            ],
            [
                'setting_name' => 'site_start',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'error_page',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'unauthorized_page',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'site_status',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'default_template',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'old_template',
                'setting_value' => '',
            ],
            [
                'setting_name' => 'publish_default',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'friendly_urls',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'friendly_alias_urls',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'use_alias_path',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'cache_type',
                'setting_value' => '2',
            ],
            [
                'setting_name' => 'html_comment',
                'setting_value' => '',
            ],
            [
                'setting_name' => 'failed_login_attempts',
                'setting_value' => '3',
            ],
            [
                'setting_name' => 'blocked_minutes',
                'setting_value' => '60',
            ],
            [
                'setting_name' => 'use_captcha',
                'setting_value' => '0',
            ],
            [
                'setting_name' => 'use_editor',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'use_browser',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'fck_editor_toolbar',
                'setting_value' => 'standard',
            ],
            [
                'setting_name' => 'fck_editor_autolang',
                'setting_value' => '0',
            ],
            [
                'setting_name' => 'editor_css_path',
                'setting_value' => '',
            ],
            [
                'setting_name' => 'editor_css_selectors',
                'setting_value' => '',
            ],
            [
                'setting_name' => 'upload_maxsize',
                'setting_value' => '10485760',
            ],
            [
                'setting_name' => 'manager_layout',
                'setting_value' => '4',
            ],
            [
                'setting_name' => 'auto_menuindex',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'session.cookie.lifetime',
                'setting_value' => '604800',
            ],
            [
                'setting_name' => 'manager_direction',
                'setting_value' => 'ltr',
            ],
            [
                'setting_name' => 'xhtml_urls',
                'setting_value' => '0',
            ],
            [
                'setting_name' => 'automatic_alias',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'datetime_format',
                'setting_value' => 'dd-mm-YYYY',
            ],
            [
                'setting_name' => 'warning_visibility',
                'setting_value' => '0',
            ],
            [
                'setting_name' => 'remember_last_tab',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'enable_bindings',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'seostrict',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'number_of_results',
                'setting_value' => '30',
            ],
            [
                'setting_name' => 'theme_refresher',
                'setting_value' => '',
            ],
            [
                'setting_name' => 'show_picker',
                'setting_value' => '0',
            ],
            [
                'setting_name' => 'show_newresource_btn',
                'setting_value' => '0',
            ],
            [
                'setting_name' => 'show_fullscreen_btn',
                'setting_value' => '0',
            ],
            [
                'setting_name' => 'email_sender_method',
                'setting_value' => '1',
            ],
            [
                'setting_name' => 'smtp_autotls',
                'setting_value' => '0',
            ],
        ]);
    }
}
