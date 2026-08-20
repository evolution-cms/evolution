<?php

it('renders the IANA timezone setting immediately before the legacy offset', function () {
    $view = file_get_contents(dirname(__DIR__, 4) . '/manager/views/page/system_settings/general.blade.php');

    $timezonePosition = strpos($view, "'name' => 'site_timezone'");
    $offsetPosition = strpos($view, "'name' => 'server_offset_time'");

    expect($timezonePosition)
        ->not->toBeFalse()
        ->and($offsetPosition)
        ->not->toBeFalse()
        ->and($timezonePosition)
        ->toBeLessThan($offsetPosition)
        ->and($view)
        ->toContain("__('global.serveroffset_deprecated_message')");
});

it('keeps timezone localization keys synchronized across manager locales', function () {
    $langRoot = dirname(__DIR__, 3) . '/lang';

    foreach (glob($langRoot . '/*/global.php') as $file) {
        $_lang = [];
        include $file;

        expect($_lang)
            ->toHaveKeys([
                'site_timezone_title',
                'site_timezone_message',
                'serveroffset_deprecated_message',
            ]);
    }
});

it('persists the PHP server timezone only on fresh installation paths', function () {
    $coreSeeder = file_get_contents(dirname(__DIR__, 3) . '/database/seeders/SystemSettingsTableSeeder.php');
    $installSeeder = file_get_contents(dirname(__DIR__, 4) . '/install/stubs/seeds/install/SystemSettingsTableSeeder.php');

    expect($coreSeeder)
        ->toContain("\$isFreshInstallation = DB::table('system_settings')->doesntExist();")
        ->toContain("if (\$isFreshInstallation)")
        ->toContain("'setting_name' => 'site_timezone'")
        ->toContain("'setting_value' => date_default_timezone_get()")
        ->and($installSeeder)
        ->toContain("'setting_name' => 'site_timezone'")
        ->toContain("'setting_value' => date_default_timezone_get()");
});

it('applies the timezone before OnLoadSettings and synchronizes Laravel config', function () {
    $core = file_get_contents(dirname(__DIR__, 3) . '/src/Core.php');
    $appConfig = file_get_contents(dirname(__DIR__, 3) . '/config/app.php');

    $applyPosition = strpos($core, 'Support\\SiteTimezone::apply(');
    $hookPosition = strpos($core, "\$this->invokeEvent('OnLoadSettings'");

    expect($applyPosition)
        ->not->toBeFalse()
        ->and($hookPosition)
        ->not->toBeFalse()
        ->and($applyPosition)
        ->toBeLessThan($hookPosition)
        ->and($core)
        ->toContain("\$this['config']->set('app.timezone', \$siteTimezone);")
        ->and($appConfig)
        ->toContain("'timezone' => date_default_timezone_get()");
});

it('validates posted timezone values before persistence', function () {
    $processor = file_get_contents(dirname(__DIR__, 4) . '/manager/processors/save_settings.processor.php');

    expect($processor)
        ->toContain("case 'site_timezone':")
        ->toContain('SiteTimezone::resolve($v)');
});
