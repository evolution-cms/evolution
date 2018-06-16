<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('new_document') || !$modx->hasPermission('save_document')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

$children = array();

// check permissions on the document
$udperms = new EvolutionCMS\Legacy\Permissions();
$udperms->user = $modx->getLoginUserID();
$udperms->document = $id;
$udperms->role = $_SESSION['mgrRole'];
$udperms->duplicateDoc = true;

if(!$udperms->checkPermissions()) {
	$modx->webAlertAndQuit($_lang["access_permission_denied"]);
}

// Run the duplicator
$id = duplicateDocument($id);

// Set the item name for logger
$name = $modx->getDatabase()->getValue($modx->getDatabase()->select('pagetitle', $modx->getDatabase()->getFullTableName('site_content'), "id='{$id}'"));
$_SESSION['itemname'] = $name;

// finish cloning - redirect
$header="Location: index.php?r=1&a=3&id=$id";
header($header);
