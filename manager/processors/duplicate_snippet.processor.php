<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('new_snippet')) {
	$e->setError(3);
	$e->dumpError();
}
?>
<?php

$id=$_GET['id'];

// duplicate Snippet
if (version_compare($modx->db->getVersion(),"4.0.14")>=0) {
	$sql = "INSERT INTO $dbase.`".$table_prefix."site_snippets` (name, description, snippet, properties, category)
			SELECT CONCAT('Duplicate of ',name) AS 'name', description, snippet, properties, category
			FROM $dbase.`".$table_prefix."site_snippets` WHERE id=$id;";
	$rs = $modx->db->query($sql);
}
else {
	$sql = "SELECT CONCAT('Duplicate of ',name) AS 'name', description, snippet, properties, category
			FROM $dbase.`".$table_prefix."site_snippets` WHERE id=$id;";
	$rs = $modx->db->query($sql);
	if($rs) {
		$row = $modx->db->getRow($rs);
		$sql ="INSERT INTO $dbase.`".$table_prefix."site_snippets`
				(name, description, snippet, properties, category) VALUES
				('".$modx->db->escape($row['name'])."', '".$modx->db->escape($row['description'])."', '".$modx->db->escape($row['snippet'])."', '".$modx->db->escape($row['properties'])."', ".$modx->db->escape($row['category']).");";
		$rs = $modx->db->query($sql);
	}
}
if($rs) $newid = $modx->db->getInsertId(); // get new id
else {
	echo "A database error occured while trying to duplicate snippet: <br /><br />".$modx->db->getLastError();
	exit;
}


// finish duplicating - redirect to new snippet
$header="Location: index.php?r=2&a=22&id=$newid";
header($header);
?>