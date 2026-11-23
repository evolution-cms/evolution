<?php namespace EvolutionCMS\Installer\Update;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSettingsTableSeeder extends Seeder
{
    /**
     * Backfill settings omitted by older CLI installations without replacing
     * values already chosen by the site operator.
     */
    public function run(): void
    {
        $this->setWhenEmpty('site_id', uniqid(''));
        $this->setWhenEmpty('manager_theme', 'default');
        $this->setWhenEmpty('which_editor', $this->defaultEditor());
    }

    /**
     * The rich-text editor for a site that never chose one. Its factory default used to be TinyMCE4,
     * which 3.6 does not ship, so a site that still has the TinyMCE4 plugin keeps it; any other site
     * gets dTui.editor, which the core bundles.
     */
    private function defaultEditor(): string
    {
        $tinyMce = DB::table('site_plugins')
            ->where('name', 'TinyMCE4')
            ->where('disabled', 0)
            ->exists();

        return $tinyMce ? 'TinyMCE4' : 'dTuiEditor';
    }

    private function setWhenEmpty(string $name, string $value): void
    {
        $current = DB::table('system_settings')
            ->where('setting_name', $name)
            ->value('setting_value');

        if (is_string($current) && $current !== '') {
            return;
        }

        DB::table('system_settings')->updateOrInsert(
            ['setting_name' => $name],
            ['setting_value' => $value]
        );
    }
}
