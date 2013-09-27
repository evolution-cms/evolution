<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('delete_eventlog')) {
	$e->setError(3);
	$e->dumpError();
}
?>
<?php

$id=intval($_GET['id']);
$clearlog = ($_GET['cls']==1 ? true:false);

// delete event log
$sql = "DELETE FROM ".$modx->getFullTableName("event_log").(!$clearlog ? " WHERE id=".$id.";":"");
$rs = $modx->db->query($sql);
if(!$rs) {
	echo "Something went wrong while trying to delete the event log...";
	exit;
} else {
	$header="Location: index.php?a=114";
	header($header);
}

?>