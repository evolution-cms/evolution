<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('delete_module')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? intval($_GET['id']) : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// Set the item name for logger
$name = $modx->db->getValue($modx->db->select('name', $modx->getFullTableName('site_modules'), "id='{$id}'"));
$_SESSION['itemname'] = $name;

// invoke OnBeforeModFormDelete event
$modx->invokeEvent("OnBeforeModFormDelete",
	array(
		"id"	=> $id
	));

// delete the module.
$modx->db->delete($modx->getFullTableName('site_modules'), "id='{$id}'");

// delete the module dependencies.
$modx->db->delete($modx->getFullTableName('site_module_depobj'), "module='{$id}'");

// delete the module user group access.
$modx->db->delete($modx->getFullTableName('site_module_access'), "module='{$id}'");

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
