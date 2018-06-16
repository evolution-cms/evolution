<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('delete_web_user')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// Set the item name for logger
$username = $modx->getDatabase()->getValue($modx->getDatabase()->select('username', $modx->getDatabase()->getFullTableName('web_users'), "id='{$id}'"));
$_SESSION['itemname'] = $username;

// invoke OnBeforeWUsrFormDelete event
$modx->invokeEvent("OnBeforeWUsrFormDelete",
	array(
		"id"	=> $id
	));

// delete the user.
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('web_users'), "id='{$id}'");

// delete user groups
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('web_groups'), "webuser='{$id}'");

// delete the attributes
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('web_user_attributes'), "internalKey='{$id}'");

// invoke OnWebDeleteUser event
$modx->invokeEvent("OnWebDeleteUser",
	array(
		"userid"		=> $id,
		"username"		=> $username
	));

// invoke OnWUsrFormDelete event
$modx->invokeEvent("OnWUsrFormDelete",
	array(
		"id"	=> $id
	));

$header="Location: index.php?a=99";
header($header);
