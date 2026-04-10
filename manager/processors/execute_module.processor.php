<?php

use EvolutionCMS\Models\SiteModule;

if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('exec_module')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}
if (isset($_GET['id'])) {
    if (is_numeric($_GET['id'])) {
        $id = (int)$_GET['id'];
    } else {
        $id = $_GET['id'];
    }
} else {
    $modx->webAlertAndQuit($_lang["error_no_id"]);
}
// check if user has access permission, except admins
if ($_SESSION['mgrRole'] != 1 && is_numeric($id)) {
    $moduleAccess = SiteModule::query()
        ->withoutProtected()
        ->where('site_modules.id', $id)
        ->first();

    if (empty($moduleAccess)) {
        $modx->webAlertAndQuit("You do not sufficient privileges to execute this module.", "index.php?a=76&tab=5");
    }
}
if (is_numeric($id)) {
    // get module data
    $content = \EvolutionCMS\Models\SiteModule::find($id);
    if (is_null($content)) {
        $modx->webAlertAndQuit("No record found for id {$id}.", "index.php?a=76&tab=5");
    }
    $content = $content->toArray();
    if ($content['disabled']) {
        $modx->webAlertAndQuit("This module is disabled and cannot be executed.", "index.php?a=76&tab=5");
    }
} else {
    $content = $modx->modulesFromFile[$id];
    $content['modulecode'] = file_get_contents($content['file']);
    $content["guid"] = '';
}
// Set the item name for logger
$_SESSION['itemname'] = $content['name'];

// load module configuration
$parameter = $modx->parseProperties($content["properties"], $content["guid"], 'module');

// Set the item name for logger
$_SESSION['itemname'] = $content['name'];

if (substr($content["modulecode"], 0, 5) === '<?php') {
    $content["modulecode"] = substr($content["modulecode"], 5);
}
echo evalModule($content["modulecode"], $parameter);
include EVO_MANAGER_PATH . "includes/sysalert.display.inc.php";
