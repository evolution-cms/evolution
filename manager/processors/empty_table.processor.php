<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('settings')) {
	$e->setError(3);
	$e->dumpError();
}

$sql = "TRUNCATE TABLE $dbase.`".$table_prefix."manager_log`";
$rs = $modx->db->query($sql);

$header="Location: index.php?a=13";
header($header);
?>