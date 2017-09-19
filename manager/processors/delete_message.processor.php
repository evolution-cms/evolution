<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('messages')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? intval($_GET['id']) : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// check the user is allowed to delete this message
$rs = $modx->db->select('recipient', $modx->getFullTableName('user_messages'), "id='{$id}'");
$message = $modx->db->getRow($rs);
if(!$message) {
	$modx->webAlertAndQuit("Wrong number of messages returned!");
}

if($message['recipient']!=$modx->getLoginUserID()) {
	$modx->webAlertAndQuit("You are not allowed to delete this message!");
}

// delete message
$modx->db->delete($modx->getFullTableName('user_messages'), "id='{$id}'");

$header="Location: index.php?a=10";
header($header);
