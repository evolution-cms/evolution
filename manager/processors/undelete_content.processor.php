<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('delete_document')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_REQUEST['id'])? (int)$_REQUEST['id'] : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

/************ webber ********/
$content=$modx->getDatabase()->getRow($modx->getDatabase()->select('parent, pagetitle', $modx->getDatabase()->getFullTableName('site_content'), "id='{$id}'"));
$pid=($content['parent']==0?$id:$content['parent']);

/************** webber *************/
$sd=isset($_REQUEST['dir'])?'&dir='.$_REQUEST['dir']:'&dir=DESC';
$sb=isset($_REQUEST['sort'])?'&sort='.$_REQUEST['sort']:'&sort=createdon';
$pg=isset($_REQUEST['page'])?'&page='.(int)$_REQUEST['page']:'';
$add_path=$sd.$sb.$pg;

/***********************************/


// check permissions on the document
$udperms = new EvolutionCMS\Legacy\Permissions();
$udperms->user = $modx->getLoginUserID();
$udperms->document = $id;
$udperms->role = $_SESSION['mgrRole'];

if(!$udperms->checkPermissions()) {
	$modx->webAlertAndQuit($_lang["access_permission_denied"]);
}

// get the timestamp on which the document was deleted.
$rs = $modx->getDatabase()->select('deletedon', $modx->getDatabase()->getFullTableName('site_content'), "id='{$id}' AND deleted=1");
$deltime = $modx->getDatabase()->getValue($rs);
if(!$deltime) {
	$modx->webAlertAndQuit("Couldn't find document to determine it's date of deletion!");
}

$children = array();

getChildrenForUnDelete($id);

if(count($children)>0) {
	$modx->getDatabase()->update(
		array(
			'deleted'   => 0,
			'deletedby' => 0,
			'deletedon' => 0,
		), $modx->getDatabase()->getFullTableName('site_content'), "id IN(".implode(", ", $children).")");
}
//'undelete' the document.
$modx->getDatabase()->update(
	array(
		'deleted'   => 0,
		'deletedby' => 0,
		'deletedon' => 0,
	), $modx->getDatabase()->getFullTableName('site_content'), "id='{$id}'");

$modx->invokeEvent("OnDocFormUnDelete",
    array(
        "id"=>$id,
        "children"=>$children
    ));

// Set the item name for logger
$_SESSION['itemname'] = $content['pagetitle'];

// empty cache
$modx->clearCache('full');

// finished emptying cache - redirect
$header="Location: index.php?a=3&id=$pid&r=1".$add_path;
header($header);
