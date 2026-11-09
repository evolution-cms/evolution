<?php

/**
 * Runs manager/processors/save_settings.processor.php in a clean process for a manager with the
 * given permissions, against a throwaway site and a system_settings table that matches names
 * case-insensitively: SQLite with NOCASE, or MySQL/MariaDB with the given collation.
 *
 * argv: site root, base64 JSON {permissions: string[], post: array, mysql?: {host, port, database,
 *       username, password, collation}}
 * Prints JSON: {settings: stored name => value, notices: PHP warnings/notices raised,
 *               folds: [spelling => whether a WHERE on it finds the use_udperms row]}
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
$mysql = $options['mysql'] ?? null;
if (is_array($mysql)) {
    $collation = (string) $mysql['collation'];
    if (!preg_match('/^utf8mb4_[a-z0-9_]+$/', $collation)) {
        exit('bad collation');
    }
    $capsule->addConnection([
        'driver' => 'mysql', 'host' => $mysql['host'], 'port' => $mysql['port'], 'database' => $mysql['database'],
        'username' => $mysql['username'], 'password' => $mysql['password'],
        'charset' => 'utf8mb4', 'collation' => $collation, 'prefix' => '',
    ]);
} else {
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
}
$capsule->setAsGlobal();
$capsule->bootEloquent();
$db = $capsule->getConnection();
if (is_array($mysql)) {
    // the column as the schema migration creates it
    $db->statement('DROP TABLE IF EXISTS system_settings');
    $db->statement("CREATE TABLE system_settings (setting_name VARCHAR(50) NOT NULL DEFAULT '' PRIMARY KEY, setting_value TEXT) DEFAULT CHARSET=utf8mb4 COLLATE={$collation}");
} else {
    $db->statement('CREATE TABLE system_settings (setting_name TEXT COLLATE NOCASE PRIMARY KEY, setting_value TEXT)');
}
$db->table('system_settings')->insert([
    ['setting_name' => 'site_name', 'setting_value' => 'Old name'],
    ['setting_name' => 'use_udperms', 'setting_value' => '1'],
    ['setting_name' => 'allow_eval', 'setting_value' => 'with_scan'],
    ['setting_name' => 'check_files_onlogin', 'setting_value' => "index.php"],
    ['setting_name' => 'sys_files_checksum', 'setting_value' => 'stored-checksum'],
    ['setting_name' => 'jpegQuality', 'setting_value' => '85'],
]);
$folds = [];
foreach (['USE_UDPERMS', 'use_udpérms', 'use_udperms '] as $spelling) {
    $folds[$spelling] = $db->table('system_settings')->where('setting_name', $spelling)->exists();
}

final class SaveSettingsStubEvo
{
    public array $config;

    public function __construct(private array $permissions)
    {
        $this->config = Illuminate\Database\Capsule\Manager::table('system_settings')
            ->pluck('setting_value', 'setting_name')->all()
            // factory defaults the Core merges in under the stored values
            + ['manager_theme' => 'default', 'settings_version' => '0'];
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
    'settings' => $db->table('system_settings')->pluck('setting_value', 'setting_name')->all(),
    'notices' => $notices,
    'folds' => $folds,
]);
