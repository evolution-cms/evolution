<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('save_password')) {
	$e->setError(3);
	$e->dumpError();
}
?>
<?php

$id = $_POST['id'];
$pass1 = $_POST['pass1'];
$pass2 = $_POST['pass2'];

if($pass1!=$pass2){
	echo "passwords don't match!";
	exit;
}

if(strlen($pass1)<6){
	echo "Password is too short. Please specify a password of at least 6 characters.";
	exit;
}

	$tbl_manager_users = $modx->getFullTableName('manager_users');
	$uid = $modx->getLoginUserID();
	$f['password'] = $modx->manager->genHash($pass1, $uid);
	$rs = $modx->db->update($f,$tbl_manager_users,"id='{$uid}'");
	if(!$rs){
	echo "An error occured while attempting to save the new password.";
	exit;
}
    
	// invoke OnManagerChangePassword event
	$modx->invokeEvent('OnManagerChangePassword', array (
		'userid' => $uid,
		'username' => $_SESSION['mgrShortname'],
		'userpassword' => $pass1
	));

$header="Location: index.php?a=7";
header($header);
?>