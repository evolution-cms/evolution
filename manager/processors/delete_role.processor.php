<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('delete_role')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

if($id==1){
	$modx->webAlertAndQuit("The role you are trying to delete is the admin role. This role cannot be deleted!");
}

$rs = $modx->getDatabase()->select('COUNT(*)', $modx->getDatabase()->getFullTableName('user_attributes'), "role='{$id}'");
$count=$modx->getDatabase()->getValue($rs);
if($count>0){
	$modx->webAlertAndQuit("There are users with this role. It can't be deleted.");
}

// Set the item name for logger
$name = $modx->getDatabase()->getValue($modx->getDatabase()->select('name', $modx->getDatabase()->getFullTableName('user_roles'), "id='{$id}'"));
$_SESSION['itemname'] = $name;

// delete the attributes
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('user_roles'), "id='{$id}'");

$header="Location: index.php?a=86";
header($header);
