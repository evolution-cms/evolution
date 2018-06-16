<?php
/**
 * EVO Installer
 */
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
$base_path = dirname(__DIR__) . '/';

if (is_file($base_path . 'assets/cache/siteManager.php')) {
    include_once $base_path . 'assets/cache/siteManager.php';
}
if (! defined('MGR_DIR')) {
    if (is_dir($base_path . 'manager')) {
        define('MGR_DIR', 'manager');
    } else {
        die('MGR_DIR is not defined');
    }
}
require_once 'src/lang.php';
require_once 'src/functions.php';

if (empty($_GET['self'])) {
    require_once '../' . MGR_DIR . '/includes/version.inc.php';

    // start session
    session_start();
    $_SESSION['test'] = 1;
    install_sessionCheck();

    $moduleName = 'EVO';
    $moduleVersion = $modx_branch . ' ' . $modx_version;
    $moduleRelease = $modx_release_date;
    $moduleSQLBaseFile = 'stubs/sql/setup.sql';
    $moduleSQLDataFile = 'stubs/sql/setup.data.sql';
    $moduleSQLResetFile = 'stubs/sql/setup.data.reset.sql';

    // chunks - array : name, description, type - 0:file or 1:content, file or content
    $moduleChunks = array();

    // templates - array : name, description, type - 0:file or 1:content, file or content
    $moduleTemplates = array();

    // snippets - array : name, description, type - 0:file or 1:content, file or content,properties
    $moduleSnippets = array();

    // plugins - array : name, description, type - 0:file or 1:content, file or content,properties, events,guid
    $modulePlugins = array();

    // modules - array : name, description, type - 0:file or 1:content, file or content,properties, guid
    $moduleModules = array();

    // templates - array : name, description, type - 0:file or 1:content, file or content,properties
    $moduleTemplates = array();

    // template variables - array : name, description, type - 0:file or 1:content, file or content,properties
    $moduleTVs = array();
    $moduleDependencies = array(); // module depedencies - array : module, table, column, type, name

    $errors = 0;

    // get post back status
    $isPostBack = count($_POST);

    $ph = ph();
    $ph = array_merge($ph, $_lang);
    $ph['install_language'] = $install_language;

    ob_start();
    $action = isset($_GET['action']) ? trim(strip_tags($_GET['action'])) : 'language';
    str_replace('.', '', $action);

    $controller = 'src/controllers/' . $action . '.php';
    if (! file_exists($controller)) {
        die("Invalid install action attempted. [action={$action}]");
    }
    require $controller;

    $ph['content'] = ob_get_contents();
    ob_end_clean();
    $tpl = file_get_contents('src/template/install.tpl');
    echo parse($tpl, $ph);
} else {
    $action = isset($_GET['action']) && is_scalar($_GET['action']) ? trim($_GET['action']) : 'language';
    str_replace('.', '', $action);
    $controller = 'src/controllers/' . $action . '.php';
    if (! file_exists($controller)) {
        die("Invalid install action attempted. [action={$action}]");
    }
    require $controller;
}
