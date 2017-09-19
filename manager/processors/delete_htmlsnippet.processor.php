<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('delete_snippet')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? intval($_GET['id']) : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// Set the item name for logger
$name = $modx->db->getValue($modx->db->select('name', $modx->getFullTableName('site_htmlsnippets'), "id='{$id}'"));
$_SESSION['itemname'] = $name;

// invoke OnBeforeChunkFormDelete event
$modx->invokeEvent("OnBeforeChunkFormDelete",
	array(
		"id"	=> $id
	));

// delete the chunk.
$modx->db->delete($modx->getFullTableName('site_htmlsnippets'), "id='{$id}'");

// invoke OnChunkFormDelete event
$modx->invokeEvent("OnChunkFormDelete",
	array(
		"id"	=> $id
	));

// empty cache
$modx->clearCache('full');

// finished emptying cache - redirect
$header="Location: index.php?a=76&r=2";
header($header);
