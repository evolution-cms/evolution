<?php 
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('save_document')||!$modx->hasPermission('publish_document')) {
	$e->setError(3);
	$e->dumpError();	
}

$id = $_REQUEST['id'];


/************webber ********/
$pid=$modx->db->getValue($modx->db->query("SELECT parent FROM ".$modx->getFullTableName('site_content')." WHERE id=".$id." LIMIT 0,1"));
$pid=($pid==0?$id:$pid);

/************** webber *************/
$sd=isset($_REQUEST['dir'])?'&dir='.$_REQUEST['dir']:'&dir=DESC';
$sb=isset($_REQUEST['sort'])?'&sort='.$_REQUEST['sort']:'&sort=createdon';
$pg=isset($_REQUEST['page'])?'&page='.(int)$_REQUEST['page']:'';
$add_path=$sd.$sb.$pg;

/***********************************/



// check permissions on the document
include_once "./processors/user_documents_permissions.class.php";
$udperms = new udperms();
$udperms->user = $modx->getLoginUserID();
$udperms->document = $id;
$udperms->role = $_SESSION['mgrRole'];

if(!$udperms->checkPermissions()) {
	include "header.inc.php";
	?><div class="sectionHeader"><?php echo $_lang['access_permissions']; ?></div>
	<div class="sectionBody">
	<p><?php echo $_lang['access_permission_denied']; ?></p>
	<?php
	include("footer.inc.php");
	exit;	
}

// update the document
$sql = "UPDATE $dbase.`".$table_prefix."site_content` SET published=1, pub_date=0, unpub_date=0, editedby=".$modx->getLoginUserID().", editedon=".time().", publishedby=".$modx->getLoginUserID().", publishedon=".time()." WHERE id=$id;";

$rs = $modx->db->query($sql);
if(!$rs){
	echo "An error occured while attempting to publish the document.";
}

// invoke OnDocPublished  event
$modx->invokeEvent("OnDocPublished",array("docid"=>$id));	

include_once "cache_sync.class.processor.php";
$sync = new synccache();
$sync->setCachepath("../assets/cache/");
$sync->setReport(false);
$sync->emptyCache(); // first empty the cache		

//$header="Location: index.php?r=1&id=$id&a=7";

// webber
$header="Location: index.php?r=1&id=$pid&a=7&dv=1".$add_path;

header($header);
?>