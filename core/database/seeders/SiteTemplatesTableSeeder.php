<?php namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiteTemplatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('site_templates')->delete();
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
