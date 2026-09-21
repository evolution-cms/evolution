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
