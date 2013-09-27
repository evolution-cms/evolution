<?php
/**
 * MODx Installer
 */
// do a little bit of environment cleanup if possible
if (version_compare(phpversion(), "5.3") < 0) {
    @ ini_set('magic_quotes_runtime', 0);
    @ ini_set('magic_quotes_sybase', 0);
}

$self = 'install/index.php';
$base_path = str_replace($self,'',str_replace('\\','/', __FILE__));
require_once("{$base_path}install/functions.php");

// set error reporting
error_reporting(E_ALL & ~E_NOTICE);

if (is_file("{$base_path}assets/cache/siteManager.php")) {
	include_once("{$base_path}assets/cache/siteManager.php");
}
if(!defined('MGR_DIR') && is_dir("{$base_path}manager")) {
	define('MGR_DIR', 'manager');
}


require_once("lang.php");
require_once('../'.MGR_DIR.'/includes/version.inc.php');

// start session
session_start();
$_SESSION['test'] = 1;
install_sessionCheck();

$moduleName = "MODX";
$moduleVersion = $modx_branch.' '.$modx_version;
$moduleRelease = $modx_release_date;
$moduleSQLBaseFile = "setup.sql";
$moduleSQLDataFile = "setup.data.sql";

$moduleChunks = array (); // chunks - array : name, description, type - 0:file or 1:content, file or content
$moduleTemplates = array (); // templates - array : name, description, type - 0:file or 1:content, file or content
$moduleSnippets = array (); // snippets - array : name, description, type - 0:file or 1:content, file or content,properties
$modulePlugins = array (); // plugins - array : name, description, type - 0:file or 1:content, file or content,properties, events,guid
$moduleModules = array (); // modules - array : name, description, type - 0:file or 1:content, file or content,properties, guid
$moduleTemplates = array (); // templates - array : name, description, type - 0:file or 1:content, file or content,properties
$moduleTVs = array (); // template variables - array : name, description, type - 0:file or 1:content, file or content,properties

$errors= 0;

// get post back status
$isPostBack = (count($_POST));

$action= isset ($_GET['action']) ? trim(strip_tags($_GET['action'])) : 'language';

// make sure they agree to the license
#if (!in_array($action, array ('language', 'welcome', 'connection', 'options', 'license', 'mode', 'summary'))) {
#    if (!isset ($_POST['chkagree'])) $action= 'license';
#}

ob_start();
include ('header.php');

if (!@include ('action.' . $action . '.php')) {
    die ('Invalid install action attempted. [action=' . $action . ']');
}

include ('footer.php');
ob_end_flush();
?>