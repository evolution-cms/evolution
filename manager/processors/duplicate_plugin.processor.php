<?php 
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

if(!$modx->hasPermission('new_plugin')) {	
	$e->setError(3);
	$e->dumpError();	
}

$id=$_GET['id'];

// duplicate Plugin
if (version_compare($modx->db->getVersion(),"4.0.14")>=0) {
	$sql = "INSERT INTO $dbase.`".$table_prefix."site_plugins` (name, description, disabled, moduleguid, plugincode, properties, category) 
			SELECT CONCAT('Duplicate of ',name) AS 'name', description, disabled, moduleguid, plugincode, properties, category 
			FROM $dbase.`".$table_prefix."site_plugins` WHERE id=$id;";
	$rs = $modx->db->query($sql);
}
else {
	$sql = "SELECT CONCAT('Duplicate of ',name) AS 'name', description, disabled, moduleguid, plugincode, properties, category
			FROM $dbase.`".$table_prefix."site_plugins` WHERE id=$id;";
	$rs = $modx->db->query($sql);
	if($rs) {
		$row = $modx->db->getRow($rs);
		$sql ="INSERT INTO $dbase.`".$table_prefix."site_plugins` 
				(name, description, disabled, moduleguid, plugincode, properties, category) VALUES 
				('".$modx->db->escape($row['name'])."', '".$modx->db->escape($row['description'])."', '".$row['disabled']."', '".$modx->db->escape($row['moduleguid'])."', '".$modx->db->escape($row['plugincode'])."', '".$modx->db->escape($row['properties'])."', ".$modx->db->escape($row['category']).");";
		$rs = $modx->db->query($sql);
	}	
}
if($rs) $newid = $modx->db->getInsertId(); // get new id
else {
	echo "A database error occured while trying to duplicate plugin: <br /><br />".$modx->db->getLastError();
	exit;
}

// duplicate Plugin Event Listeners
if (version_compare($modx->db->getVersion(),"4.0.14")>=0) {
	$sql = "INSERT INTO $dbase.`".$table_prefix."site_plugin_events` (pluginid,evtid,priority)
			SELECT $newid, evtid, priority
			FROM $dbase.`".$table_prefix."site_plugin_events` WHERE pluginid=$id;";
	$rs = $modx->db->query($sql);
}
else {
	$sql = "SELECT $newid as 'newid', evtid, priority
			FROM $dbase.`".$table_prefix."site_plugin_events` WHERE pluginid=$id;";
	$ds = $modx->db->query($sql);
	while($row = $modx->db->getRow($ds)) {
		$sql = "INSERT INTO $dbase.`".$table_prefix."site_plugin_events` 
				(pluginid, evtid, priority) VALUES
				('".$row['newid']."', '".$row['evtid']."', '".$row['priority']."');";
		$rs = $modx->db->query($sql);
	}
}
if (!$rs) {
	echo "A database error occured while trying to duplicate plugin events: <br /><br />".$modx->db->getLastError();
	exit;
}

// finish duplicating - redirect to new plugin
$header="Location: index.php?r=2&a=102&id=$newid";
header($header);
?>