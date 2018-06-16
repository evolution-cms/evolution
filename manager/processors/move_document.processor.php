<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('edit_document')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$newParentID = isset($_REQUEST['new_parent']) ? (int)$_REQUEST['new_parent'] : 0;
$documentID = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

// ok, two things to check.
// first, document cannot be moved to itself
// second, new parent must be a folder. If not, set it to folder.
if($documentID==$newParentID) $modx->webAlertAndQuit($_lang["error_movedocument1"]);
if($documentID <= 0) $modx->webAlertAndQuit($_lang["error_movedocument2"]);
if($newParentID < 0) $modx->webAlertAndQuit($_lang["error_movedocument2"]);

$parents = $modx->getParentIds($newParentID);
if (in_array($documentID, $parents))  $modx->webAlertAndQuit($_lang["error_movedocument2"]);

$rs = $modx->getDatabase()->select('parent', $modx->getDatabase()->getFullTableName('site_content'), "id='{$documentID}'");
$oldparent = $modx->getDatabase()->getValue($rs);

// check user has permission to move document to chosen location

if ($use_udperms == 1) {
	if ($oldparent != $newParentID) {
		$udperms = new EvolutionCMS\Legacy\Permissions();
		$udperms->user = $modx->getLoginUserID();
		$udperms->document = $newParentID;
		$udperms->role = $_SESSION['mgrRole'];

		 if (!$udperms->checkPermissions()) {
			$modx->webAlertAndQuit($_lang["access_permission_parent_denied"]);
		 }
	}
}

$evtOut = $modx->invokeEvent("onBeforeMoveDocument", array (
	"id_document" => $documentID,
	"old_parent" => $oldparent,
	"new_parent" => $newParentID
));
if (is_array($evtOut) && count($evtOut) > 0){
	$newParent = array_pop($evtOut);
	if($newParent == $oldparent) {
		$modx->webAlertAndQuit($_lang["error_movedocument2"]);
	}else{
		$newParentID = $newParent;
	}
}

$children = allChildren($documentID);
if (!array_search($newParentID, $children)) {
	$modx->getDatabase()->update(array(
		'isfolder' => 1,
	), $modx->getDatabase()->getFullTableName('site_content'), "id='{$newParentID}'");

	$modx->getDatabase()->update(array(
		'parent'   => $newParentID,
		'editedby' => $modx->getLoginUserID(),
		'editedon' => time(),
	), $modx->getDatabase()->getFullTableName('site_content'), "id='{$documentID}'");

	// finished moving the document, now check to see if the old_parent should no longer be a folder.
	$rs = $modx->getDatabase()->select('COUNT(*)', $modx->getDatabase()->getFullTableName('site_content'), "parent='{$oldparent}'");
	$limit = $modx->getDatabase()->getValue($rs);

	if(!$limit>0) {
		$modx->getDatabase()->update(array(
			'isfolder' => 0,
		), $modx->getDatabase()->getFullTableName('site_content'), "id='{$oldparent}'");
	}
	// Set the item name for logger
	$pagetitle = $modx->getDatabase()->getValue($modx->getDatabase()->select('pagetitle', $modx->getDatabase()->getFullTableName('site_content'), "id='{$documentID}'"));
	$_SESSION['itemname'] = $pagetitle;

	$modx->invokeEvent("onAfterMoveDocument", array (
		"id_document" => $documentID,
		"old_parent" => $oldparent,
		"new_parent" => $newParentID
	));

	// empty cache & sync site
	$modx->clearCache('full');

	$header="Location: index.php?a=3&id={$documentID}&r=9";
	header($header);
} else {
	$modx->webAlertAndQuit("You cannot move a document to a child document!");
}
