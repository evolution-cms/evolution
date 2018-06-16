<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('access_permissions')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

// access group processor.
// figure out what the user wants to do...

// Get table names (alphabetical)
$tbl_document_groups     = $modx->getDatabase()->getFullTableName('document_groups');
$tbl_documentgroup_names = $modx->getDatabase()->getFullTableName('documentgroup_names');
$tbl_member_groups       = $modx->getDatabase()->getFullTableName('member_groups');
$tbl_membergroup_access  = $modx->getDatabase()->getFullTableName('membergroup_access');
$tbl_membergroup_names   = $modx->getDatabase()->getFullTableName('membergroup_names');

$updategroupaccess = false;
$operation = $_REQUEST['operation'];

switch ($operation) {
	case "add_user_group" :
		$newgroup = $_REQUEST['newusergroup'];
		if(empty($newgroup)) {
			$modx->webAlertAndQuit("No group name specified.");
		} else {
			$id = $modx->getDatabase()->insert(array('name' => $modx->getDatabase()->escape($newgroup)), $tbl_membergroup_names);

			// invoke OnManagerCreateGroup event
			$modx->invokeEvent('OnManagerCreateGroup', array(
				'groupid'   => $id,
				'groupname' => $newgroup,
			));
		}
	break;
	case "add_document_group" :
		$newgroup = $_REQUEST['newdocgroup'];
		if(empty($newgroup)) {
			$modx->webAlertAndQuit("No group name specified.");
		} else {
			$id = $modx->getDatabase()->insert(array('name' => $modx->getDatabase()->escape($newgroup)), $tbl_documentgroup_names);

			// invoke OnCreateDocGroup event
			$modx->invokeEvent('OnCreateDocGroup', array(
				'groupid'   => $id,
				'groupname' => $newgroup,
			));
		}
	break;
	case "delete_user_group" :
		$updategroupaccess = true;
		$usergroup = (int)$_REQUEST['usergroup'];
		if(empty($usergroup)) {
			$modx->webAlertAndQuit("No user group id specified for deletion.");
		} else {
			$modx->getDatabase()->delete($tbl_membergroup_names, "id='{$usergroup}'");

			$modx->getDatabase()->delete($tbl_membergroup_access, "membergroup='{$usergroup}'");

			$modx->getDatabase()->delete($tbl_member_groups, "user_group='{$usergroup}'");
		}
	break;
	case "delete_document_group" :
		$group = (int)$_REQUEST['documentgroup'];
		if(empty($group)) {
			$modx->webAlertAndQuit("No document group id specified for deletion.");
		} else {
			$modx->getDatabase()->delete($tbl_documentgroup_names, "id='{$group}'");

			$modx->getDatabase()->delete($tbl_membergroup_access, "documentgroup='{$group}'");

			$modx->getDatabase()->delete($tbl_document_groups, "document_group='{$group}'");
		}
	break;
	case "rename_user_group" :
		$newgroupname = $_REQUEST['newgroupname'];
		if(empty($newgroupname)) {
			$modx->webAlertAndQuit("No group name specified.");
		}
		$groupid = (int)$_REQUEST['groupid'];
		if(empty($groupid)) {
			$modx->webAlertAndQuit("No group id specified for rename.");
		}

		$modx->getDatabase()->update(array('name' => $modx->getDatabase()->escape($newgroupname)), $tbl_membergroup_names, "id='{$groupid}'");
	break;
	case "rename_document_group" :
		$newgroupname = $_REQUEST['newgroupname'];
		if(empty($newgroupname)) {
			$modx->webAlertAndQuit("No group name specified.");
		}
		$groupid = (int)$_REQUEST['groupid'];
		if(empty($groupid)) {
			$modx->webAlertAndQuit("No group id specified for rename.");
		}

		$modx->getDatabase()->update(array('name' => $modx->getDatabase()->escape($newgroupname)), $tbl_documentgroup_names, "id='{$groupid}'");
	break;
	case "add_document_group_to_user_group" :
		$updategroupaccess = true;
		$usergroup = (int)$_REQUEST['usergroup'];
		$docgroup = (int)$_REQUEST['docgroup'];
		$rs = $modx->getDatabase()->select('COUNT(*)', $tbl_membergroup_access, "membergroup='{$usergroup}' AND documentgroup='{$docgroup}'");
		$limit = $modx->getDatabase()->getValue($rs);
		if($limit<=0) {
			$modx->getDatabase()->insert(array('membergroup' => $usergroup, 'documentgroup' => $docgroup), $tbl_membergroup_access);
		} else {
			//alert user that coupling already exists?
		}
	break;
	case "remove_document_group_from_user_group" :
		$updategroupaccess = true;
		$coupling = (int)$_REQUEST['coupling'];
		$modx->getDatabase()->delete($tbl_membergroup_access, "id='{$coupling}'");
	break;
	default :
		$modx->webAlertAndQuit("No operation set in request.");
}

// secure manager documents - flag as private
if($updategroupaccess==true){
	include MODX_MANAGER_PATH."includes/secure_mgr_documents.inc.php";
	secureMgrDocument();

	// Update the private group column
	$modx->getDatabase()->update(
		'dgn.private_memgroup = (mga.membergroup IS NOT NULL)',
		"{$tbl_documentgroup_names} AS dgn LEFT JOIN {$tbl_membergroup_access} AS mga ON mga.documentgroup = dgn.id");
}

$header = "Location: index.php?a=40";
header($header);
