<?php
/**
 * Evolution CMS Installer
 */

use EvolutionCMS\Support\InstallerCompletion;

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
$base_path = dirname(__DIR__) . '/';

// Resolve MGR_DIR the way the site does: core/custom/define.php and the .env file first (the
// same loader core/bootstrap.php uses), then the legacy siteManager.php cache, then the default.
if (is_file($base_path . 'core/vendor/autoload.php')) {
    require_once $base_path . 'core/functions/laravel.php';
    require_once $base_path . 'core/vendor/autoload.php';
    require_once $base_path . 'core/includes/custom-define.inc.php';
    if (!defined('MGR_DIR') && is_string(env('MGR_DIR')) && env('MGR_DIR') !== '') {
        define('MGR_DIR', env('MGR_DIR'));
    }
}
if (!defined('MGR_DIR') && is_file($base_path . 'assets/cache/siteManager.php')) {
    include_once $base_path . 'assets/cache/siteManager.php';
}
if (!defined('MGR_DIR') && is_dir($base_path . 'manager')) {
    define('MGR_DIR', 'manager');
}
if (!defined('MGR_DIR') || !is_file($base_path . MGR_DIR . '/includes/version.inc.php')) {
    die('The manager folder was not found. If it was renamed, set MGR_DIR in core/custom/define.php.');
}
if (!defined('EVO_MANAGER_PATH')) {
    define('EVO_MANAGER_PATH', $base_path . MGR_DIR . '/');
}
if (! defined('EVO_CORE_PATH')) {
    if (is_dir($base_path . 'core')) {
        define('EVO_CORE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR);
    } else {
        die('EVO_CORE_PATH is not defined');
    }
}
require_once EVO_CORE_PATH . 'src/Support/InstallerCompletion.php';
require_once 'src/lang.php';
require_once 'src/functions.php';

// Start session
session_start();

// Get current IP, session ID, and current time
$ip = $_SERVER['REMOTE_ADDR'];
$sid = session_id();
$current_time = time();

// Get session GC max lifetime
$maxlifetime = (int) ini_get('session.gc_maxlifetime');
if ($maxlifetime <= 0) {
    $maxlifetime = 1440; // Default to 24 minutes if not set
}

// Define lock file path
$lockfile = $base_path . 'install.session.php';

if (file_exists("{$base_path}manager/includes/config.inc.php")) { ?>
    Backup and delete the file `manager/includes/config.inc.php` then <a href="<?= htmlspecialchars($_SERVER['REQUEST_URI'],
        ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" onclick="location.reload(true)">reload the page</a>
    <?php exit();
}

// Ensure cache directory exists
$cache_dir = EVO_CORE_PATH . 'storage/cache/';
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

// Once the site is installed, the installer opens only for the browser an administrator's Manager
// session granted it to (the grant cookie), or for the session that installed the site while its
// lock lives. A leftover install folder, or one uploaded for a manual upgrade, stays shut to
// everyone else and the site runs as usual. Deciding and recording happen in one locked step.
$grantCookie = $_COOKIE[InstallerCompletion::GRANT_COOKIE] ?? null;
$decision = InstallerCompletion::admit(
    $lockfile,
    InstallerCompletion::isInstalled(EVO_CORE_PATH),
    $sid,
    $ip,
    is_string($grantCookie) ? $grantCookie : null,
    $current_time,
    $maxlifetime
);

if ($decision === null) {
    header('HTTP/1.1 503 Service Unavailable');
    header('Content-Type: text/plain; charset=utf-8');
    echo "The installer could not write install.session.php in the site root."
        . "\nMake the site root writable by the web server, then reload this page.";
    exit;
}

if ($decision === InstallerCompletion::DENY) {
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found.\n\nThis site is already installed. To run the installer, log into the Manager"
        . "\nas an administrator in this browser, then reload this page. If another installer"
        . "\nsession is running, it has to end first.";
    exit;
}

$nonce = csrfNonce();
header("content-security-policy: default-src 'self' 'nonce-$nonce';"
    . " script-src 'self' 'nonce-$nonce'; style-src 'self' 'nonce-$nonce';"
    . " frame-ancestors 'none';");

if (empty($_GET['s'])) {
    require_once $base_path . MGR_DIR . '/includes/version.inc.php';

    $_SESSION['test'] = 1;
    install_sessionCheck();

    $moduleName = 'Evolution CMS';
    $moduleVersion = $evo_branch . ' ' . $evo_version;
    $moduleRelease = $evo_release_date;
    $moduleSQLBaseFile = 'stubs/sql/setup.sql';
    $moduleSQLDataFile = 'stubs/sql/setup.data.sql';
    $moduleSQLResetFile = 'stubs/sql/setup.data.reset.sql';

    // chunks - array : name, description, type - 0:file or 1:content, file or content
    $moduleChunks = [];

    // templates - array : name, description, type - 0:file or 1:content, file or content
    $moduleTemplates = [];

    // snippets - array : name, description, type - 0:file or 1:content, file or content,properties
    $moduleSnippets = [];

    // plugins - array : name, description, type - 0:file or 1:content, file or content,properties, events,guid
    $modulePlugins = [];

    // modules - array : name, description, type - 0:file or 1:content, file or content,properties, guid
    $moduleModules = [];

    // templates - array : name, description, type - 0:file or 1:content, file or content,properties
    $moduleTemplates = [];

    // template variables - array : name, description, type - 0:file or 1:content, file or content,properties
    $moduleTVs = [];
    $moduleDependencies = []; // module depedencies - array : module, table, column, type, name

    $errors = 0;

    // get post back status
    $isPostBack = count($_POST);

    $ph = ph($_lang, $moduleVersion, $evo_textdir ?? false, $evo_release_date);
    $ph = array_merge($ph, $_lang);
    $ph['install_language'] = $install_language;

    ob_start();
    $action = isset($_GET['action']) ? preg_replace('/[^a-z\/]/', '', $_GET['action']) : 'language';
    $controller = __DIR__ . '/src/controllers/' . $action . '.php';
    if (! file_exists($controller)) {
        die("Invalid install action attempted. [action={$action}]");
    }
    try {
        require $controller;
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    $ph['content'] = ob_get_contents();
    ob_end_clean();
    $tpl = file_get_contents(__DIR__ . '/src/template/install.tpl');
    echo parse($tpl, $ph);
} else {
    $action = isset($_GET['action']) ? preg_replace('/[^a-z\/]/', '', $_GET['action']) : 'language';
    $controller = __DIR__ . '/src/controllers/' . $action . '.php';
    if (! file_exists($controller)) {
        die("Invalid install action attempted. [action={$action}]");
    }
    require $controller;
}
