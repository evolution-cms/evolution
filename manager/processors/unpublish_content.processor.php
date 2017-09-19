<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('save_document')||!$modx->hasPermission('publish_document')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_REQUEST['id'])? intval($_REQUEST['id']) : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

/************webber ********/
$content=$modx->db->getRow($modx->db->select('parent, pagetitle', $modx->getFullTableName('site_content'), "id='{$id}'"));
$pid=($content['parent']==0?$id:$content['parent']);

/************** webber *************/
$sd=isset($_REQUEST['dir'])?'&dir='.$_REQUEST['dir']:'&dir=DESC';
$sb=isset($_REQUEST['sort'])?'&sort='.$_REQUEST['sort']:'&sort=createdon';
$pg=isset($_REQUEST['page'])?'&page='.(int)$_REQUEST['page']:'';
$add_path=$sd.$sb.$pg;

/***********************************/



// check permissions on the document
include_once MODX_MANAGER_PATH . "processors/user_documents_permissions.class.php";
$udperms = new udperms();
$udperms->user = $modx->getLoginUserID();
$udperms->document = $id;
$udperms->role = $_SESSION['mgrRole'];

if(!$udperms->checkPermissions()) {
	$modx->webAlertAndQuit($_lang["access_permission_denied"]);
}

// update the document
$modx->db->update(
	array(
		'published'   => 0,
		'pub_date'    => 0,
		'unpub_date'  => 0,
		'editedby'    => $modx->getLoginUserID(),
		'editedon'    => time(),
		'publishedby' => 0,
		'publishedon' => 0,
	), $modx->getFullTableName('site_content'), "id='{$id}'");

// invoke OnDocUnPublished  event
$modx->invokeEvent("OnDocUnPublished",array("docid"=>$id));

// Set the item name for logger
$_SESSION['itemname'] = $content['pagetitle'];

// empty cache
$modx->clearCache('full');

$header="Location: index.php?a=3&id=$pid&r=1".$add_path;

header($header);
