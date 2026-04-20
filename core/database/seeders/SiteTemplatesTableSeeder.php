<?php namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the minimal default site template for a fresh installation.
 *
 * Safety: this seeder is idempotent and will not overwrite existing templates.
 * If the `site_templates` table already contains any rows, it does nothing.
 */
class SiteTemplatesTableSeeder extends Seeder
{
    /**
     * Execute the database seeder.
     *
     * Inserts the default "Minimal Template" only when the `site_templates` table is empty.
     */
    public function run(): void
    {
        if (!Schema::hasTable('site_templates')) {
            return;
        }

        if (DB::table('site_templates')->count() > 0) {
            return;
        }

        DB::table('site_templates')->insert([
            [
                'templatename'  => 'Minimal Template',
                'templatealias' => '',
                'description'   => 'Default minimal empty template (content returned only)',
                'editor_type'   => 0,
                'category'      => 0,
                'icon'          => '',
                'template_type' => 0,
                'content'       => '[*content*]',
                'locked'        => 0,
                'selectable'    => 1,
                'createdon'     => 0,
                'editedon'      => 0,
            ],
        ]);
    }
}
