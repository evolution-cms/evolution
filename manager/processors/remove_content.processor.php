<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('delete_document')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$rs = $modx->db->select('id', $modx->getFullTableName('site_content'), "deleted=1");
$ids = $modx->db->getColumn('id', $rs); 

// invoke OnBeforeEmptyTrash event
$modx->invokeEvent("OnBeforeEmptyTrash",
						array(
							"ids"=>$ids
						));

// remove the document groups link.
$sql = "DELETE document_groups
		FROM ".$modx->getFullTableName('document_groups')." AS document_groups
		INNER JOIN ".$modx->getFullTableName('site_content')." AS site_content ON site_content.id = document_groups.document
		WHERE site_content.deleted=1";
$modx->db->query($sql);

// remove the TV content values.
$sql = "DELETE site_tmplvar_contentvalues
		FROM ".$modx->getFullTableName('site_tmplvar_contentvalues')." AS site_tmplvar_contentvalues
		INNER JOIN ".$modx->getFullTableName('site_content')." AS site_content ON site_content.id = site_tmplvar_contentvalues.contentid
		WHERE site_content.deleted=1";
$modx->db->query($sql);

//'undelete' the document.
$modx->db->delete($modx->getFullTableName('site_content'), "deleted=1");

	// invoke OnEmptyTrash event
	$modx->invokeEvent("OnEmptyTrash",
						array(
							"ids"=>$ids
						));

	// empty cache
	$modx->clearCache('full');

	// finished emptying cache - redirect
	$header="Location: index.php?a=2&r=1";
	header($header);
