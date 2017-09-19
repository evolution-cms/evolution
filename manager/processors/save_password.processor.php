<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('save_password')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = $_POST['id'];
$pass1 = $_POST['pass1'];
$pass2 = $_POST['pass2'];

if($pass1!=$pass2){
	$modx->webAlertAndQuit("Passwords don't match!");
}

if(strlen($pass1)<6){
	$modx->webAlertAndQuit("Password is too short. Please specify a password of at least 6 characters.");
}

    $pass1 = $modx->htmlspecialchars($pass1, ENT_NOQUOTES);
	$tbl_manager_users = $modx->getFullTableName('manager_users');
	$uid = $modx->getLoginUserID();
	$modx->loadExtension('phpass');
	$f['password'] = $modx->phpass->HashPassword($pass1);
	$modx->db->update($f,$tbl_manager_users,"id='{$uid}'");

	// invoke OnManagerChangePassword event
	$modx->invokeEvent('OnManagerChangePassword', array (
		'userid' => $uid,
		'username' => $_SESSION['mgrShortname'],
		'userpassword' => $pass1
	));

$header="Location: index.php?a=2";
header($header);
