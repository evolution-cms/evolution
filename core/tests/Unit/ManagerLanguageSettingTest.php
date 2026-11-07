<?php

use EvolutionCMS\Core;
use EvolutionCMS\Models\UserSetting;
use Illuminate\Database\Capsule\Manager;

/**
 * manager_language names a file to include, in the core and in packages that read it as it
 * stands. The user editor stores any value, so a path never gets out of the settings.
 */
function managerLanguageCore(array $config, string $userValue, callable $check): void
{
    defined('EVO_BASE_PATH') || define('EVO_BASE_PATH', '/fixture/');
    $previous = UserSetting::getConnectionResolver();
    $session = $_SESSION ?? [];
    $database = new Manager;
    $database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
    $database->bootEloquent();
    $connection = $database->getConnection();
    $connection->getSchemaBuilder()->create('user_settings', function ($table) {
        $table->integer('user'); $table->string('setting_name'); $table->string('setting_value');
    });
    $connection->table('user_settings')->insert(['user' => 1, 'setting_name' => 'manager_language', 'setting_value' => $userValue]);
    $core = Mockery::mock(Core::class)->makePartial();
    $core->instance('config', new \Illuminate\Config\Repository([]));
    $core->config = $config + ['filemanager_path' => '[(base_path)]assets/', 'rb_base_dir' => '[(base_path)]assets/'];
    $core->shouldReceive('getLoginUserID')->withNoArgs()->andReturn(1);
    $core->shouldReceive('getLoginUserID')->with('mgr')->andReturn(1);
    $core->shouldReceive('getLoginUserType')->andReturn('manager');
    $core->shouldReceive('isFrontend')->andReturn(true);
    $core->shouldReceive('isBackend')->andReturn(false);
    try {
        $check($core);
    } finally {
        if ($previous === null) UserSetting::unsetConnectionResolver();
        else UserSetting::setConnectionResolver($previous);
        $_SESSION = $session;
        Mockery::close();
    }
}

test('a per-user manager_language that is a path leaves the system language in place', function (string $value) {
    managerLanguageCore(['manager_language' => 'de'], $value, function ($core) {
        $settings = $core->getUserSettings();

        expect($settings)->not->toHaveKey('manager_language')
            ->and($core->config['manager_language'])->toBe('de');
    });
})->with(['../../../assets/files/x', '..\\..\\x', 'de/../uk', '..']);

test('a per-user manager_language that names a locale still applies, installed or not', function () {
    managerLanguageCore(['manager_language' => 'de'], 'uk', function ($core) {
        expect($core->getUserSettings()['manager_language'])->toBe('uk')
            ->and($core->config['manager_language'])->toBe('uk');
    });
});

test('a system manager_language that is a path reads as English', function (string $value, string $expected) {
    $core = Mockery::mock(Core::class)->makePartial();
    $core->config = ['manager_language' => $value];

    (new ReflectionMethod(Core::class, 'dropManagerLanguagePath'))->invoke($core);
    expect($core->config['manager_language'])->toBe($expected);
    Mockery::close();
})->with([
    'a path' => ['../../x', 'en'],
    'a deleted locale, kept for when it returns' => ['xx', 'xx'],
    'not set yet' => ['', ''],
]);

test('the system language is checked before anything reads the settings', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 2) . '/src/Core.php');
    $load = strpos($source, '$this->loadConfig();', (int) strpos($source, 'public function getSettings()'));
    $check = strpos($source, '$this->dropManagerLanguagePath();', $load);

    expect($check)->toBeGreaterThan($load)
        ->and($check)->toBeLessThan((int) strpos($source, '$this->getUserSettings();', $load));
});

test('every include named after manager_language asks for a locale name first', function (string $file, string $variable) {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/' . $file);

    expect($source)->toContain('ManagerLanguage::isName(' . $variable . ')');
})->with([
    ['core/src/Legacy/ErrorHandler.php', '$manager_language'],
    ['core/src/Services/Store/StoreContextService.php', '$managerLanguage'],
    ['assets/plugins/updater/plugin.updater.php', '$language'],
    ['assets/plugins/extrascheck/CheckOutdated.class.php', '$lang'],
    ['assets/modules/store/installer/index.php', '$lang'],
    ['manager/actions/mutate_web_user.dynamic.php', '$manager_language'],
]);
