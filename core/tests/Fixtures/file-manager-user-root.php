<?php

/**
 * From settings to the file manager root, in a clean process: the real Core::getUserSettings()
 * merges a manager's own filemanager_path (a user_settings row) over the site-wide one, then
 * fileManagerRoot() gives the folder the classic file manager and the media browser work in.
 *
 * argv: site root, base64 JSON {system: site-wide filemanager_path, own: the manager's (optional)}
 * Prints JSON: {root, config}
 */

[, $site, $payload] = $argv;
$options = json_decode(base64_decode($payload), true);

define('EVO_BASE_PATH', $site . '/');
define('EVO_MANAGER_PATH', $site . '/manager/');
define('EVO_CORE_PATH', $site . '/core/');
define('EVO_SITE_URL', 'http://example.test/');
define('IN_MANAGER_MODE', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', false);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

/** The real getUserSettings() of a logged-in manager, without the CMS bootstrap. */
final class UserRootCore extends EvolutionCMS\Core
{
    public function __construct()
    {
    }

    public function getLoginUserID($context = '')
    {
        return 1;
    }

    public function getLoginUserType($context = '')
    {
        return 'manager';
    }

    public function isFrontend()
    {
        return false;
    }

    public function isBackend()
    {
        return false;
    }
}

$GLOBALS['userRootCore'] = new UserRootCore();
function evo()
{
    return $GLOBALS['userRootCore'];
}
function evolutionCMS()
{
    return evo();
}

$capsule = new Illuminate\Database\Capsule\Manager();
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$capsule->getConnection()->statement('CREATE TABLE user_settings (user INTEGER, setting_name TEXT, setting_value TEXT)');
if (isset($options['own'])) {
    $capsule->getConnection()->table('user_settings')->insert([
        'user' => 1, 'setting_name' => 'filemanager_path', 'setting_value' => $options['own'],
    ]);
}

$core = evo();
// getConfig() lets config/cms/settings override a setting; there are none here
$core->instance('config', new Illuminate\Config\Repository([]));
$core->config =['filemanager_path' => $options['system'], 'rb_base_dir' => '[(base_path)]assets/'];
$_SESSION = [];
$core->getUserSettings();

echo json_encode(['root' => fileManagerRoot(), 'config' => $core->getConfig('filemanager_path')]);
