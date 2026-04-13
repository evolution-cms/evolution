<?php
define('MGR', EVO_BASE_PATH . MGR_DIR);

$modx = evo();
$GLOBALS['modx'] = $modx;

evo()->invokeEvent('OnManagerPageInit');
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true || !evo()->hasPermission('exec_module')) {
	die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.');
}

$moduleurl = EVO_SITE_URL . 'assets/modules/store/installer/index.php';
$modulePath = EVO_BASE_PATH . 'assets/modules/store/installer/';
$self = $modulePath . '/index.php';
require_once($modulePath . "/functions.php");

$_lang = array();
$_params = array();
$lang = evo()->config['manager_language'];
if (file_exists($modulePath . '/lang/' . $lang . '.inc.php')){
	include_once($modulePath . '/lang/' . $lang . '.inc.php');
} else {
	include_once($modulePath . '/lang/en.inc.php');
}
include_once(EVO_BASE_PATH . "assets/cache/siteManager.php");
require_once(MGR . '/includes/version.inc.php');

$_SESSION['test'] = 1;
install_sessionCheck();

$moduleName = "Evolution CMS";
$moduleVersion = $evo_branch . ' ' . $evo_version;
$moduleRelease = $evo_release_date;
$moduleSQLBaseFile = "setup.sql";
$moduleSQLDataFile = "setup.data.sql";

$moduleChunks = array (); // chunks - array : name, description, type - 0:file or 1:content, file or content
$moduleTemplates = array (); // templates - array : name, description, type - 0:file or 1:content, file or content
$moduleSnippets = array (); // snippets - array : name, description, type - 0:file or 1:content, file or content,properties
$modulePlugins = array (); // plugins - array : name, description, type - 0:file or 1:content, file or content,properties, events,guid
$moduleModules = array (); // modules - array : name, description, type - 0:file or 1:content, file or content, properties, guid, icon
$moduleTemplates = array (); // templates - array : name, description, type - 0:file or 1:content, file or content,properties
$moduleTVs = array (); // template variables - array : name, description, type - 0:file or 1:content, file or content,properties

$errors= 0;

// get post back status
$isPostBack = (count($_POST));
$action = isset($_GET['action']) && in_array($_GET['action'], ['load', 'options', 'install']) ? $_GET['action'] : 'load';

ob_start();
echo '<!DOCTYPE html>
<html><head><title>Install</title>
<meta http-equiv="Content-Type" content="text/html; charset="utf-8" />
<link rel="stylesheet" href="' . EVO_SITE_URL . 'assets/modules/store/installer/style.css" type="text/css" media="screen" /></head>
<body><div id="contentarea"><div class="container_12"><br>';

include($modulePath . '/action.' . $action . '.php');

echo "</div><!-- // content --></div><!-- // contentarea --><br /></body></html>";
ob_end_flush();
?>
