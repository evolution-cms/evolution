<?php 
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('delete_document')) {	
	$e->setError(3);
	$e->dumpError();	
}

$id=$_REQUEST['id'];

/************ webber ********/
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

// get the timestamp on which the document was deleted.
$sql = "SELECT deletedon FROM $dbase.`".$table_prefix."site_content` WHERE $dbase.`".$table_prefix."site_content`.id=".$id." AND deleted=1;";
$rs = $modx->db->query($sql);
$limit = $modx->db->getRecordCount($rs);
if($limit!=1) {
	echo "Couldn't find document to determine it's date of deletion!";
	exit;
} else {
	$row=$modx->db->getRow($rs);
	$deltime = $row['deletedon'];
}

$children = array();

function getChildren($parent) {
	
	global $modx,$dbase;
	global $table_prefix;
	global $children;
	global $deltime;
	
	$db->debug = true;
	
	$sql = "SELECT id FROM $dbase.`".$table_prefix."site_content` WHERE $dbase.`".$table_prefix."site_content`.parent=".$parent." AND deleted=1 AND deletedon=$deltime;";
	$rs = $modx->db->query($sql);
	$limit = $modx->db->getRecordCount($rs);
	if($limit>0) {
		// the document has children documents, we'll need to delete those too
		for($i=0;$i<$limit;$i++) {
		$row=$modx->db->getRow($rs);
			$children[] = $row['id'];
			getChildren($row['id']);
			//echo "Found childNode of parentNode $parent: ".$row['id']."<br />";
		}
	}
}

getChildren($id);

if(count($children)>0) {
	$docs_to_undelete = implode(" ,", $children);
	$sql = "UPDATE $dbase.`".$table_prefix."site_content` SET deleted=0, deletedby=0, deletedon=0 WHERE id IN($docs_to_undelete);";
	$rs = @$modx->db->query($sql);
	if(!$rs) {
		echo "Something went wrong while trying to set the document's children to undeleted status...";
		exit;
	}
}
//'undelete' the document.
$sql = "UPDATE $dbase.`".$table_prefix."site_content` SET deleted=0, deletedby=0, deletedon=0 WHERE id=$id;";
$rs = $modx->db->query($sql);
if(!$rs) {
	echo "Something went wrong while trying to set the document to undeleted status...";
	exit;
} else {
	// empty cache
	include_once "cache_sync.class.processor.php";
	$sync = new synccache();
	$sync->setCachepath("../assets/cache/");
	$sync->setReport(false);
	$sync->emptyCache(); // first empty the cache		
	// finished emptying cache - redirect
	//$header="Location: index.php?r=1&a=7&id=$id&dv=1";

// webber
	$header="Location: index.php?r=1&a=7&id=$pid&dv=1".$add_path;
	header($header);
}
?>