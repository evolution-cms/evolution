<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('save_plugin') && !$modx->hasPermission('save_snippet') && !$modx->hasPermission('save_template') && !$modx->hasPermission('save_module')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['catId'])? intval($_GET['catId']) : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// Set the item name for logger
$name = $modx->db->getValue($modx->db->select('category', $modx->getFullTableName('categories'), "id='{$id}'"));
$_SESSION['itemname'] = $name;

include_once(MODX_MANAGER_PATH.'includes/categories.inc.php');
deleteCategory($id);

// finished emptying cache - redirect
$header="Location: index.php?a=76";
header($header);
