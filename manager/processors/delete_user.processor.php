<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('delete_user')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? intval($_GET['id']) : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// delete the user, but first check if we are deleting our own record
if($id==$modx->getLoginUserID()) {
	$modx->webAlertAndQuit("You can't delete yourself!");
}

// Set the item name for logger
$username = $modx->db->getValue($modx->db->select('username', $modx->getFullTableName('manager_users'), "id='{$id}'"));
$_SESSION['itemname'] = $username;

// invoke OnBeforeUserFormDelete event
$modx->invokeEvent("OnBeforeUserFormDelete",
	array(
		"id"	=> $id
	));

// delete the user.
$modx->db->delete($modx->getFullTableName('manager_users'), "id='{$id}'");

$modx->db->delete($modx->getFullTableName('member_groups'), "member='{$id}'");

// delete user settings
$modx->db->delete($modx->getFullTableName('user_settings'), "user='{$id}'");

// delete the attributes
$modx->db->delete($modx->getFullTableName('user_attributes'), "internalKey='{$id}'");

// invoke OnManagerDeleteUser event
$modx->invokeEvent("OnManagerDeleteUser",
	array(
		"userid"		=> $id,
		"username"		=> $username
	));

// invoke OnUserFormDelete event
$modx->invokeEvent("OnUserFormDelete",
	array(
		"id"	=> $id
	));

$header="Location: index.php?a=75";
header($header);
