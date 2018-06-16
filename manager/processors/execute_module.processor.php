<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('exec_module')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// check if user has access permission, except admins
if($_SESSION['mgrRole']!=1){
	$rs = $modx->getDatabase()->select(
		'sma.usergroup,mg.member',
		$modx->getDatabase()->getFullTableName("site_module_access")." sma
			LEFT JOIN ".$modx->getDatabase()->getFullTableName("member_groups")." mg ON mg.user_group = sma.usergroup AND member='".$modx->getLoginUserID()."'",
		"sma.module = '{$id}'"
		);
	//initialize permission to -1, if it stays -1 no permissions
	//attached so permission granted
	$permissionAccessInt = -1;

	while ($row = $modx->getDatabase()->getRow($rs)) {
		if($row["usergroup"] && $row["member"]) {
			//if there are permissions and this member has permission, ofcourse
			//this is granted
			$permissionAccessInt = 1;
		} elseif ($permissionAccessInt==-1) {
			//if there are permissions but this member has no permission and the
			//variable was still in init state we set permission to 0; no permissions
			$permissionAccessInt = 0;
		}
	}

	if($permissionAccessInt==0) {
		$modx->webAlertAndQuit("You do not sufficient privileges to execute this module.", "index.php?a=106");
	}
}

// get module data
$rs = $modx->getDatabase()->select('*', $modx->getDatabase()->getFullTableName("site_modules"), "id='{$id}'");
$content = $modx->getDatabase()->getRow($rs);
if(!$content) {
	$modx->webAlertAndQuit("No record found for id {$id}.", "index.php?a=106");
}
if($content['disabled']) {
	$modx->webAlertAndQuit("This module is disabled and cannot be executed.", "index.php?a=106");
}

// Set the item name for logger
$_SESSION['itemname'] = $content['name'];

// load module configuration
$parameter = $modx->parseProperties($content["properties"], $content["guid"], 'module');

// Set the item name for logger
$_SESSION['itemname'] = $content['name'];


$output = evalModule($content["modulecode"],$parameter);
if (strpos(trim($output),'<')===0 && strpos(trim($output),'<?xml')!==0) {
	echo "<style>@supports (-webkit-overflow-scrolling: touch) {body,html {-webkit-overflow-scrolling: touch;overflow:auto!important;height:100%!important}}</style>"; // for iframe scroller
}
echo $output;
include MODX_MANAGER_PATH."includes/sysalert.display.inc.php";
