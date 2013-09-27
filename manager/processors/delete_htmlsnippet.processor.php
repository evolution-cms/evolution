<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('delete_snippet')) {
	$e->setError(3);
	$e->dumpError();
}
?>
<?php

$id=intval($_GET['id']);

// invoke OnBeforeChunkFormDelete event
$modx->invokeEvent("OnBeforeChunkFormDelete",
						array(
							"id"	=> $id
						));

//ok, delete the chunk.
$sql = "DELETE FROM $dbase.`".$table_prefix."site_htmlsnippets` WHERE $dbase.`".$table_prefix."site_htmlsnippets`.id=".$id.";";
$rs = $modx->db->query($sql);
if(!$rs) {
	echo "Something went wrong while trying to delete the htmlsnippet...";
	exit;
} else {
	// invoke OnChunkFormDelete event
	$modx->invokeEvent("OnChunkFormDelete",
							array(
								"id"	=> $id
							));

	// empty cache
	include_once "cache_sync.class.processor.php";
	$sync = new synccache();
	$sync->setCachepath("../assets/cache/");
	$sync->setReport(false);
	$sync->emptyCache(); // first empty the cache
	// finished emptying cache - redirect
	$header="Location: index.php?a=76&r=2";
	header($header);
}
?>