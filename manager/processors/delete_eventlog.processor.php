<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('delete_eventlog')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

if (isset($_GET['cls']) && $_GET['cls']==1) {
	$where = '';
} else {
	$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
	if($id==0) {
		$modx->webAlertAndQuit($_lang["error_no_id"]);
	}
	$where = "id='{$id}'";
}

// delete event log
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('event_log'), $where);

$header="Location: index.php?a=114";
header($header);
