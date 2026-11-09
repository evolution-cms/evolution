<?php

/**
 * Runs manager/processors/save_settings.processor.php in a clean process for a manager with the
 * given permissions, against a throwaway site and a SQLite system_settings table.
 *
 * argv: site root, base64 JSON {permissions: string[], post: array}
 * Prints JSON: {settings: stored name => value, notices: PHP warnings/notices raised}
 */

[, $site, $payload] = $argv;
$options = json_decode(base64_decode($payload), true);

define('EVO_BASE_PATH', $site . '/');
define('EVO_BASE_URL', '/');
define('EVO_MANAGER_PATH', $site . '/manager/');
define('EVO_CORE_PATH', $site . '/core/');
define('IN_MANAGER_MODE', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', false);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$capsule = new Illuminate\Database\Capsule\Manager();
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$capsule->getConnection()->statement('CREATE TABLE system_settings (setting_name TEXT PRIMARY KEY, setting_value TEXT)');
$capsule->getConnection()->table('system_settings')->insert([
    ['setting_name' => 'site_name', 'setting_value' => 'Old name'],
    ['setting_name' => 'use_udperms', 'setting_value' => '1'],
    ['setting_name' => 'allow_eval', 'setting_value' => 'with_scan'],
    ['setting_name' => 'check_files_onlogin', 'setting_value' => "index.php"],
    ['setting_name' => 'sys_files_checksum', 'setting_value' => 'stored-checksum'],
]);

final class SaveSettingsStubEvo
{
    public array $config;

    public function __construct(private array $permissions)
    {
        $this->config = Illuminate\Database\Capsule\Manager::table('system_settings')
            ->pluck('setting_value', 'setting_name')->all() + ['manager_theme' => 'default'];
    }

    public function hasPermission($key)
    {
        return in_array($key, $this->permissions, true) ? 1 : 0;
    }

    public function getConfig($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function getManagerApi()
    {
        // the real checksum, without the manager API's bootstrap
        return (new ReflectionClass(EvolutionCMS\Legacy\ManagerApi::class))->newInstanceWithoutConstructor();
    }

    public function getVersionData($key = null)
    {
        return '3.5.9';
    }

    public function logEvent(...$args)
    {
    }

    public function clearCache(...$args)
    {
    }

    public function webAlertAndQuit($message, $url = '')
    {
        echo json_encode(['quit' => $message]);
        exit;
    }
}

function config($key = null, $default = null)
{
    return $key === 'cms.settings' ? [] : $default;
}

$notices = [];
set_error_handler(static function ($severity, $message, $file, $line) use (&$notices) {
    $notices[] = $message . ' @ ' . basename($file) . ':' . $line;

    return true;
});

$modx = new SaveSettingsStubEvo($options['permissions']);
$_lang = ['error_no_privileges' => 'error_no_privileges'];
$_SERVER['SERVER_SOFTWARE'] = 'Apache';
$_POST = $options['post'] + [
    'friendly_urls' => '0',
    'filemanager_path' => '[(base_path)]',
    'rb_base_dir' => '[(base_path)]assets/',
    'stay' => '',
];

include dirname(__DIR__, 3) . '/manager/processors/save_settings.processor.php';

echo json_encode([
    'settings' => Illuminate\Database\Capsule\Manager::table('system_settings')->pluck('setting_value', 'setting_name')->all(),
    'notices' => $notices,
]);
