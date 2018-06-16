<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('delete_module')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// Set the item name for logger
$name = $modx->getDatabase()->getValue($modx->getDatabase()->select('name', $modx->getDatabase()->getFullTableName('site_modules'), "id='{$id}'"));
$_SESSION['itemname'] = $name;

// invoke OnBeforeModFormDelete event
$modx->invokeEvent("OnBeforeModFormDelete",
	array(
		"id"	=> $id
	));

// delete the module.
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('site_modules'), "id='{$id}'");

// delete the module dependencies.
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('site_module_depobj'), "module='{$id}'");

// delete the module user group access.
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('site_module_access'), "module='{$id}'");

// invoke OnModFormDelete event
$modx->invokeEvent("OnModFormDelete",
	array(
		"id"	=> $id
	));

// empty cache
$modx->clearCache('full');

// finished emptying cache - redirect
$header="Location: index.php?a=106&r=2";
header($header);
