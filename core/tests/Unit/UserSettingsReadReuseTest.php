<?php

use EvolutionCMS\Core;
use EvolutionCMS\Models\UserSetting;
use Illuminate\Database\Capsule\Manager;

test('identical current and manager IDs reuse only the raw read within one call', function () {
    if (!defined('EVO_BASE_PATH')) define('EVO_BASE_PATH', '/fixture/');
    $previous = UserSetting::getConnectionResolver();
    $session = $_SESSION ?? [];
    $database = new Manager;
    $database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
    $database->bootEloquent();
    $connection = $database->getConnection();
    $connection->getSchemaBuilder()->create('user_settings', function ($table) {
        $table->integer('user'); $table->string('setting_name'); $table->string('setting_value');
    });
    $connection->table('user_settings')->insert([
        ['user' => 1, 'setting_name' => 'which_browser', 'setting_value' => 'default'],
        ['user' => 1, 'setting_name' => 'custom', 'setting_value' => 'first'],
    ]);
    $core = Mockery::mock(Core::class)->makePartial();
    $core->instance('config', new \Illuminate\Config\Repository([]));
    $core->config = ['which_browser' => 'configured-browser', 'filemanager_path' => '[(base_path)]assets/', 'rb_base_dir' => '[(base_path)]assets/'];
    $core->shouldReceive('getLoginUserID')->withNoArgs()->andReturn(1);
    $core->shouldReceive('getLoginUserID')->with('mgr')->andReturn(1);
    $core->shouldReceive('getLoginUserType')->andReturn('manager');
    $core->shouldReceive('isFrontend')->andReturn(true);
    $core->shouldReceive('isBackend')->andReturn(false);
    try {
        $connection->enableQueryLog();
        $settings = $core->getUserSettings();
        expect($settings['which_browser'])->toBe('configured-browser')
            ->and($_SESSION['mgrUsrConfigSet']['which_browser'])->toBe('default')
            ->and($settings['custom'])->toBe('first')
            ->and($connection->getQueryLog())->toHaveCount(1);
        $connection->table('user_settings')->where('setting_name', 'custom')->update(['setting_value' => 'second']);
        $connection->flushQueryLog();
        expect($core->getUserSettings()['custom'])->toBe('second')
            ->and($connection->getQueryLog())->toHaveCount(1);
    } finally {
        if ($previous === null) UserSetting::unsetConnectionResolver();
        else UserSetting::setConnectionResolver($previous);
        $_SESSION = $session;
        Mockery::close();
    }
});

test('different web and manager IDs retain separate reads and web-user precedence', function () {
    if (!defined('EVO_BASE_PATH')) define('EVO_BASE_PATH', '/fixture/');
    $previous = UserSetting::getConnectionResolver();
    $session = $_SESSION ?? [];
    $database = new Manager;
    $database->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
    $database->bootEloquent();
    $connection = $database->getConnection();
    $connection->getSchemaBuilder()->create('user_settings', function ($table) {
        $table->integer('user'); $table->string('setting_name'); $table->string('setting_value');
    });
    $connection->table('user_settings')->insert([
        ['user' => 1, 'setting_name' => 'shared', 'setting_value' => 'manager'],
        ['user' => 2, 'setting_name' => 'shared', 'setting_value' => 'web'],
        ['user' => 1, 'setting_name' => 'manager-only', 'setting_value' => 'retained'],
    ]);
    $core = Mockery::mock(Core::class)->makePartial();
    $core->instance('config', new \Illuminate\Config\Repository([]));
    $core->config = ['filemanager_path' => '[(base_path)]assets/', 'rb_base_dir' => '[(base_path)]assets/'];
    $core->shouldReceive('getLoginUserID')->withNoArgs()->andReturn(2);
    $core->shouldReceive('getLoginUserID')->with('mgr')->andReturn(1);
    $core->shouldReceive('getLoginUserType')->andReturn('web');
    $core->shouldReceive('isFrontend')->andReturn(true);
    try {
        $connection->enableQueryLog();
        $settings = $core->getUserSettings();
        expect($settings['shared'])->toBe('web')
            ->and($settings['manager-only'])->toBe('retained')
            ->and($_SESSION['webUsrConfigSet']['shared'])->toBe('web')
            ->and($_SESSION['mgrUsrConfigSet']['shared'])->toBe('manager')
            ->and($connection->getQueryLog())->toHaveCount(2);
    } finally {
        if ($previous === null) UserSetting::unsetConnectionResolver();
        else UserSetting::setConnectionResolver($previous);
        $_SESSION = $session;
        Mockery::close();
    }
});
