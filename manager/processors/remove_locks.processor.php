<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

if(!isset($_GET['id'])) {
	if(!$modx->hasPermission('remove_locks')) $modx->webAlertAndQuit($_lang["error_no_privileges"]);

	// Remove all locks
	$modx->getDatabase()->truncate($modx->getDatabase()->getFullTableName('active_user_locks'));

	$header = "Location: index.php?a=2";
	header($header);
} else {
	// Remove single locks via AJAX / window.onbeforeunload
	$type = (int)$_GET['type'];
	$id = (int)$_GET['id'];
	$includeAllUsers = $modx->hasPermission('remove_locks'); // Enables usage of "unlock"-ajax-button
	if($type && $id) {
		$modx->unlockElement($type, $id, $includeAllUsers);
		echo '1';
		exit;
	} else {
		echo 'No type or id sent with request.';
	}
}
