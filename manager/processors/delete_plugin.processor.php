<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('delete_plugin')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// Set the item name for logger
$name = $modx->getDatabase()->getValue($modx->getDatabase()->select('name', $modx->getDatabase()->getFullTableName('site_plugins'), "id='{$id}'"));
$_SESSION['itemname'] = $name;

// invoke OnBeforePluginFormDelete event
$modx->invokeEvent("OnBeforePluginFormDelete",
	array(
		"id"	=> $id
	));

// delete the plugin.
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('site_plugins'), "id='{$id}'");

// delete the plugin events.
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('site_plugin_events'), "pluginid='{$id}'");

// invoke OnPluginFormDelete event
$modx->invokeEvent("OnPluginFormDelete",
	array(
		"id"	=> $id
	));

// empty cache
$modx->clearCache('full');

// finished emptying cache - redirect
$header="Location: index.php?a=76&r=2";
header($header);
