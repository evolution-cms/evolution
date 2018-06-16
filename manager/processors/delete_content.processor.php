<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('delete_document')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if ($id==0) {
    $modx->webAlertAndQuit($_lang["error_no_id"]);
}

/*******ищем родителя чтобы к нему вернуться********/
$content=$modx->getDatabase()->getRow($modx->getDatabase()->select('parent, pagetitle', $modx->getDatabase()->getFullTableName('site_content'), "id='{$id}'"));
$pid=($content['parent']==0?$id:$content['parent']);

/************ а заодно и путь возврата (сам путь внизу файла) **********/
$sd=isset($_REQUEST['dir'])?'&dir='.$_REQUEST['dir']:'&dir=DESC';
$sb=isset($_REQUEST['sort'])?'&sort='.$_REQUEST['sort']:'&sort=createdon';
$pg=isset($_REQUEST['page'])?'&page='.(int)$_REQUEST['page']:'';
$add_path=$sd.$sb.$pg;

/*****************************/

$deltime = time();
$children = array();

// check permissions on the document
$udperms = new EvolutionCMS\Legacy\Permissions();
$udperms->user = $modx->getLoginUserID();
$udperms->document = $id;
$udperms->role = $_SESSION['mgrRole'];

if (!$udperms->checkPermissions()) {
    $modx->webAlertAndQuit($_lang["access_permission_denied"]);
}

getChildrenForDelete($id);

// invoke OnBeforeDocFormDelete event
$modx->invokeEvent("OnBeforeDocFormDelete",
                        array(
                            "id"=>$id,
                            "children"=>$children
                        ));

if (count($children)>0) {
    $modx->getDatabase()->update(
        array(
            'deleted'   => 1,
            'deletedby' => $modx->getLoginUserID(),
            'deletedon' => $deltime,
        ), $modx->getDatabase()->getFullTableName('site_content'), "id IN (".implode(", ", $children).")");
}

if ($site_start==$id) {
    $modx->webAlertAndQuit("Document is 'Site start' and cannot be deleted!");
}

if ($site_unavailable_page==$id) {
    $modx->webAlertAndQuit("Document is used as the 'Site unavailable page' and cannot be deleted!");
}

if ($error_page==$id) {
    $modx->webAlertAndQuit("Document is used as the 'Site error page' and cannot be deleted!");
}

if ($unauthorized_page==$id) {
    $modx->webAlertAndQuit("Document is used as the 'Site unauthorized page' and cannot be deleted!");
}

// delete the document.
$modx->getDatabase()->update(
    array(
        'deleted'   => 1,
        'deletedby' => $modx->getLoginUserID(),
        'deletedon' => $deltime,
    ), $modx->getDatabase()->getFullTableName('site_content'), "id='{$id}'");

// invoke OnDocFormDelete event
$modx->invokeEvent("OnDocFormDelete",
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
