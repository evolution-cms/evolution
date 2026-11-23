<?php

/*
|--------------------------------------------------------------------------
| The default rich-text editor is the one the core ships
|--------------------------------------------------------------------------
|
| 3.6 bundles dTui.editor (dmi3yy/dtui-editor, registered in app.providers), but the factory default
| of which_editor named TinyMCE4, which 3.6 does not include, and a fresh install stored no
| which_editor at all: every rich-text field opened as a plain textarea until an administrator
| picked an editor. A fresh install now stores dTuiEditor, and the factory default agrees with it.
| Sites being updated are covered in Feature/SiteUpdateE2ETest.
|
*/

it('stores dTui.editor as the editor of a fresh installation', function () {
    $seeder = (string) file_get_contents(dirname(__DIR__, 4) . '/install/stubs/seeds/install/SystemSettingsTableSeeder.php');

    expect($seeder)->toMatch("/'setting_name' => 'which_editor',\\s*'setting_value' => 'dTuiEditor',/");
});

it('defaults which_editor to the bundled dTui.editor', function () {
    $root = dirname(__DIR__, 4);
    $factory = (string) file_get_contents($root . '/core/factory/settings.php');
    $app = (string) file_get_contents($root . '/core/config/app.php');

    expect($factory)->toContain("'which_editor' => 'dTuiEditor',")
        ->and($factory)->not->toContain("'which_editor' => 'TinyMCE4',")
        ->and($app)->toContain('EvolutionCMS\dTuiEditor\DTuiEditorServiceProvider::class');
});
