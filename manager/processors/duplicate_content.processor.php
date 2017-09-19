<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('new_document') || !$modx->hasPermission('save_document')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? intval($_GET['id']) : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

$children = array();

// check permissions on the document
include_once MODX_MANAGER_PATH . "processors/user_documents_permissions.class.php";
$udperms = new udperms();
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
$name = $modx->db->getValue($modx->db->select('pagetitle', $modx->getFullTableName('site_content'), "id='{$id}'"));
$_SESSION['itemname'] = $name;

// finish cloning - redirect
$header="Location: index.php?r=1&a=3&id=$id";
header($header);

function duplicateDocument($docid, $parent=null, $_toplevel=0) {
	global $modx, $_lang;

	// invoke OnBeforeDocDuplicate event
	$evtOut = $modx->invokeEvent('OnBeforeDocDuplicate', array(
		'id' => $docid
	));

	// if( !in_array( 'false', array_values( $evtOut ) ) ){}
	// TODO: Determine necessary handling for duplicateDocument "return $newparent" if OnBeforeDocDuplicate were able to conditially control duplication 
	// [DISABLED]: Proceed with duplicateDocument if OnBeforeDocDuplicate did not return false via: $event->output('false');

	$userID = $modx->getLoginUserID();

	$tblsc = $modx->getFullTableName('site_content');

	// Grab the original document
	$rs = $modx->db->select('*', $tblsc, "id='{$docid}'");
	$content = $modx->db->getRow($rs);

	// Handle incremental ID
	switch($modx->config['docid_incrmnt_method'])
	{
		case '1':
			$from = "{$tblsc} AS T0 LEFT JOIN {$tblsc} AS T1 ON T0.id + 1 = T1.id";
			$rs = $modx->db->select('MIN(T0.id)+1', $from, "T1.id IS NULL");
			$content['id'] = $modx->db->getValue($rs);
			break;
		case '2':
			$rs = $modx->db->select('MAX(id)+1',$tblsc);
			$content['id'] = $modx->db->getValue($rs);
			break;

		default:
			unset($content['id']); // remove the current id.
	}

	// Once we've grabbed the document object, start doing some modifications
	if ($_toplevel == 0) {
		// count duplicates
		$pagetitle = $modx->db->getValue($modx->db->select('pagetitle', $modx->getFullTableName('site_content'), "id='{$docid}'"));
		$pagetitle = $modx->db->escape($pagetitle);
		$count = $modx->db->getRecordCount($modx->db->select('pagetitle', $modx->getFullTableName('site_content'), "pagetitle LIKE '{$pagetitle} Duplicate%'"));
		if($count>=1) $count = ' '.($count+1);
		else $count = '';
		
		$content['pagetitle'] = $_lang['duplicated_el_suffix'].$count.' '.$content['pagetitle'];
		$content['alias'] = null;
	} elseif($modx->config['friendly_urls'] == 0 || $modx->config['allow_duplicate_alias'] == 0) {
		$content['alias'] = null;
	}

	// change the parent accordingly
	if ($parent !== null) $content['parent'] = $parent;

	// Change the author
	$content['createdby'] = $userID;
	$content['createdon'] = time();
	// Remove other modification times
	$content['editedby'] = $content['editedon'] = $content['deleted'] = $content['deletedby'] = $content['deletedon'] = 0;

	// [FS#922] Should the published status be honored? - sirlancelot
//	if ($modx->hasPermission('publish_document')) {
//		if ($modx->config['publish_default'])
//			$content['pub_date'] = $content['pub_date']; // should this be changed to 1?
//		else	$content['pub_date'] = 0;
//	} else {
		// User can't publish documents
//		$content['published'] = $content['pub_date'] = 0;
//	}

    // Set the published status to unpublished by default (see above ... commit #3388)
    $content['published'] = $content['pub_date'] = 0;

	// Escape the proper strings
	$content = $modx->db->escape($content);

	// Duplicate the Document
	$newparent = $modx->db->insert($content, $tblsc);

	// duplicate document's TVs
	duplicateTVs($docid, $newparent);
	duplicateAccess($docid, $newparent);
	
	// invoke OnDocDuplicate event
	$evtOut = $modx->invokeEvent('OnDocDuplicate', array(
		'id' => $docid,
		'new_id' => $newparent
	));

	// Start duplicating all the child documents that aren't deleted.
	$_toplevel++;
	$rs = $modx->db->select('id', $tblsc, "parent='{$docid}' AND deleted=0", 'id ASC');
		while ($row = $modx->db->getRow($rs))
			duplicateDocument($row['id'], $newparent, $_toplevel);

	// return the new doc id
	return $newparent;
}

// Duplicate Document TVs
function duplicateTVs($oldid,$newid){
	global $modx;

	$tbltvc = $modx->getFullTableName('site_tmplvar_contentvalues');

	$modx->db->insert(
		array('contentid'=>'', 'tmplvarid'=>'', 'value'=>''), $tbltvc, // Insert into
		"{$newid}, tmplvarid, value", $tbltvc, "contentid='{$oldid}'" // Copy from
	);
}

// Duplicate Document Access Permissions
function duplicateAccess($oldid,$newid){
	global $modx;

	$tbldg = $modx->getFullTableName('document_groups');

	$modx->db->insert(
		array('document'=>'', 'document_group'=>''), $tbldg, // Insert into
		"{$newid}, document_group", $tbldg, "document='{$oldid}'" // Copy from
	);
}
