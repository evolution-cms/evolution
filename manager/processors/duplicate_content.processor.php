<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('new_document') || !$modx->hasPermission('save_document')) {
	$e->setError(3);
	$e->dumpError();
}

// check the document doesn't have any children
$id=$_GET['id'];
$children = array();

// check permissions on the document
include_once "./processors/user_documents_permissions.class.php";
$udperms = new udperms();
$udperms->user = $modx->getLoginUserID();
$udperms->document = $id;
$udperms->role = $_SESSION['mgrRole'];
$udperms->duplicateDoc = true;

if(!$udperms->checkPermissions()) {
	include "header.inc.php";
	?><div class="sectionHeader"><?php echo $_lang['access_permissions']; ?></div>
	<div class="sectionBody">
	<p><?php echo $_lang['access_permission_denied']; ?></p>
	<?php
	include("footer.inc.php");
	exit;
}

// check for MySQL 4.0.14
$mysqlVerOk = (version_compare($modx->db->getVersion(),"4.0.14")>=0);

// Run the duplicator
$id = duplicateDocument($id);

// finish cloning - redirect
$header="Location: index.php?r=1&a=3&id=$id";
header($header);

function duplicateDocument($docid, $parent=null, $_toplevel=0) {
	global $modx;

	// invoke OnBeforeDocDuplicate event
	$evtOut = $modx->invokeEvent('OnBeforeDocDuplicate', array(
		'id' => $docid
	));

	// if( !in_array( 'false', array_values( $evtOut ) ) ){}
	// TODO: Determine necessary handling for duplicateDocument "return $newparent" if OnBeforeDocDuplicate were able to conditially control duplication 
	// [DISABLED]: Proceed with duplicateDocument if OnBeforeDocDuplicate did not return false via: $event->output('false');

	$myChildren = array();
	$userID = $modx->getLoginUserID();

	$tblsc = $modx->getFullTableName('site_content');

	// Grab the original document
	$rs = $modx->db->select('*', $tblsc, 'id='.$docid);
	$content = $modx->db->getRow($rs);

	unset($content['id']); // remove the current id.

	// Once we've grabbed the document object, start doing some modifications
	if ($_toplevel == 0) {
		$content['pagetitle'] = 'Duplicate of '.$content['pagetitle'];
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
	$content['pagetitle'] = $modx->db->escape($content['pagetitle']);
	$content['longtitle'] = $modx->db->escape($content['longtitle']);
	$content['description'] = $modx->db->escape($content['description']);
	$content['introtext'] = $modx->db->escape($content['introtext']);
	$content['content'] = $modx->db->escape($content['content']);
	$content['menutitle'] = $modx->db->escape($content['menutitle']);

	// Duplicate the Document
	$newparent = $modx->db->insert($content, $tblsc);

	// duplicate document's TVs & Keywords
	duplicateKeywords($docid, $newparent);
	duplicateTVs($docid, $newparent);
	duplicateAccess($docid, $newparent);
	
	// invoke OnDocDuplicate event
	$evtOut = $modx->invokeEvent('OnDocDuplicate', array(
		'id' => $docid,
		'new_id' => $newparent
	));

	// Start duplicating all the child documents that aren't deleted.
	$_toplevel++;
	$rs = $modx->db->select('id', $tblsc, 'parent='.$docid.' AND deleted=0', 'id ASC');
	if ($modx->db->getRecordCount($rs)) {
		while ($row = $modx->db->getRow($rs))
			duplicateDocument($row['id'], $newparent, $_toplevel);
	}

	// return the new doc id
	return $newparent;
}

// Duplicate Keywords
function duplicateKeywords($oldid,$newid){
	global $modx, $mysqlVerOk;
	// global $dbase, $table_prefix;

	$tblkw = $modx->getFullTableName('keyword_xref');

	if($mysqlVerOk) {
		$modx->db->insert(
			array('content_id'=>'', 'keyword_id'=>''), $tblkw, // Insert into
			$newid.', keyword_id', $tblkw, 'content_id='.$oldid // Copy from
		);
	} else {
		$ds = $modx->db->select('keyword_id', $tblkw, 'content_id='.$oldid);
		while ($row = $modx->db->getRow($ds))
			$modx->db->insert(array('content_id'=>$newid, 'keyword_id'=>$row['keyword_id']), $tblkw);
	}
}

// Duplicate Document TVs
function duplicateTVs($oldid,$newid){
	global $modx, $mysqlVerOk;
	// global $dbase, $table_prefix;

	$tbltvc = $modx->getFullTableName('site_tmplvar_contentvalues');

	if($mysqlVerOk) {
		$modx->db->insert(
			array('contentid'=>'', 'tmplvarid'=>'', 'value'=>''), $tbltvc, // Insert into
			$newid.', tmplvarid, value', $tbltvc, 'contentid='.$oldid // Copy from
		);
	} else {
		$ds = $modx->db->select('tmplvarid, value', $tbltvc, 'contentid='.$oldid);
		while ($row = $modx->db->getRow($ds))
			$modx->db->insert(array('contentid'=>$newid, 'tmplvarid'=>$row['tmplvarid'], 'value'=>$modx->db->escape($row['value'])), $tbltvc);
	}
}

// Duplicate Document Access Permissions
function duplicateAccess($oldid,$newid){
	global $modx, $mysqlVerOk;
	// global $dbase, $table_prefix;

	$tbldg = $modx->getFullTableName('document_groups');

	if($mysqlVerOk) {
		$modx->db->insert(
			array('document'=>'', 'document_group'=>''), $tbldg, // Insert into
			$newid.', document_group', $tbldg, 'document='.$oldid // Copy from
		);
	} else {
		$ds = $modx->db->select('document_group', $tbldg, 'document='.$oldid);
		while ($row = $modx->db->getRow($ds))
			$modx->db->insert(array('document'=>$newid, 'document_group'=>$row['document_group']), $tbldg);
	}
}

?>