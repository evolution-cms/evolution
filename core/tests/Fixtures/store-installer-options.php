<?php

require dirname(__DIR__, 2) . '/vendor/autoload.php';

define('IN_MANAGER_MODE', true);
define('EVO_BASE_PATH', dirname(__DIR__, 3) . '/');
define('EVO_SITE_URL', 'https://example.test/site/');
define('EVO_MANAGER_URL', $argv[1]);

$_SESSION = ['mgrValidated' => 1, '_token' => str_repeat('a', 40)];
$_GET = ['id' => $argv[2]];
$_POST = [];

$modx = new class {
    /**
     * Allow rendering the installer without a database-backed Manager user.
     *
     * @since 3.5.10
     */
    public function hasPermission(string $permission): bool
    {
        return $permission === 'exec_module';
    }
};

$moduleTemplates = $moduleTVs = $moduleChunks = $moduleModules = $modulePlugins = $moduleSnippets = [];
$_lang = array_fill_keys(['optional_items', 'optional_items_note', 'checkbox_select_options', 'all', 'none', 'toggle', 'install'], 'Install');
$modulePath = sys_get_temp_dir() . '/evo-store-options-' . bin2hex(random_bytes(8));
mkdir($modulePath);
file_put_contents($modulePath . '/setup.info.php', '<?php');

try {
    require EVO_BASE_PATH . 'assets/modules/store/installer/action.options.php';
} finally {
    unlink($modulePath . '/setup.info.php');
    rmdir($modulePath);
}
