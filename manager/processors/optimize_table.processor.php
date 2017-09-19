<?php 
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!($modx->hasPermission('settings') && ($modx->hasPermission('logs')||$modx->hasPermission('bk_manager')))) {	
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

if (isset($_REQUEST['t'])) {

	if (empty($_REQUEST['t'])) {
		$modx->webAlertAndQuit($_lang["error_no_optimise_tablename"]);
	}

	// Set the item name for logger
	$_SESSION['itemname'] = $_REQUEST['t'];

	$modx->db->optimize($_REQUEST['t']);

} elseif (isset($_REQUEST['u'])) {

	if (empty($_REQUEST['u'])) {
		$modx->webAlertAndQuit($_lang["error_no_truncate_tablename"]);
	}

	// Set the item name for logger
	$_SESSION['itemname'] = $_REQUEST['u'];

	$modx->db->truncate($_REQUEST['u']);

} else {
	$modx->webAlertAndQuit($_lang["error_no_optimise_tablename"]);
}

$mode = intval($_REQUEST['mode']);
$header="Location: index.php?a={$mode}&s=4";
header($header);
