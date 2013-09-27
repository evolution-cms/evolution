<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('edit_template')) {
	$e->setError(3);
	$e->dumpError();
}
?>
<?php

$id=$_GET['id'];

// duplicate TV
if (version_compare($modx->db->getVersion(),"4.0.14")>=0) {
	$sql = "INSERT INTO $dbase.`".$table_prefix."site_tmplvars` (type, name, caption, description, default_text, elements, rank, display, display_params, category)
			SELECT type, name, CONCAT('Duplicate of ',caption) AS 'caption', description, default_text, elements, rank, display, display_params, category
			FROM $dbase.`".$table_prefix."site_tmplvars` WHERE id=$id;";
	$rs = $modx->db->query($sql);
}
else {
	$sql = "SELECT type, name, CONCAT('Duplicate of ',caption) AS 'caption', description, default_text, elements, rank, display, display_params, category
			FROM $dbase.`".$table_prefix."site_tmplvars` WHERE id=$id;";
	$rs = $modx->db->query($sql);
	if($rs) {
		$row = $modx->db->getRow($rs);
		$sql = "INSERT INTO $dbase.`".$table_prefix."site_tmplvars`
				(type, name, caption, description, default_text, elements, rank, display, display_params, category) VALUES
				('".$row['type']."', '".$modx->db->escape($row['name'])."', '".$modx->db->escape($row['caption'])."', '".$modx->db->escape($row['description'])."', '".$modx->db->escape($row['default_text'])."', '".$modx->db->escape($row['elements'])."', '".$row['rank']."', '".$row['display']."', '".$modx->db->escape($row['display_params'])."', ".$modx->db->escape($row['category']).");";
		$rs = $modx->db->query($sql);
	}
}
if($rs) $newid = $modx->db->getInsertId(); // get new id
else {
	echo "A database error occured while trying to duplicate TV: <br /><br />".$modx->db->getLastError();
	exit;
}


// duplicate TV Template Access Permissions
if (version_compare($modx->db->getVersion(),"4.0.14")>=0) {
	$sql = "INSERT INTO $dbase.`".$table_prefix."site_tmplvar_templates` (tmplvarid, templateid)
			SELECT $newid, templateid
			FROM $dbase.`".$table_prefix."site_tmplvar_templates` WHERE tmplvarid=$id;";
	$rs = $modx->db->query($sql);
}
else {
	$sql = "SELECT $newid as 'newid', templateid
			FROM $dbase.`".$table_prefix."site_tmplvar_templates` WHERE tmplvarid=$id;";
	$ds = $modx->db->query($sql);
	if($ds) while($row = $modx->db->getRow($ds)) {
		$sql = "INSERT INTO $dbase.`".$table_prefix."site_tmplvar_templates`
				(tmplvarid, templateid) VALUES
				('".$row['newid']."', '".$row['templateid']."');";
		$rs = $modx->db->query($sql);
	}
}
if (!$rs) {
	echo "A database error occured while trying to duplicate TV template access: <br /><br />".$modx->db->getLastError();
	exit;
}


// duplicate TV Access Permissions
if (version_compare($modx->db->getVersion(),"4.0.14")>=0) {
	$sql = "INSERT INTO $dbase.`".$table_prefix."site_tmplvar_access` (tmplvarid, documentgroup)
			SELECT $newid, documentgroup
			FROM $dbase.`".$table_prefix."site_tmplvar_access` WHERE tmplvarid=$id;";
	$rs = $modx->db->query($sql);
}
else {
	$sql = "SELECT $newid as 'newid', documentgroup
			FROM $dbase.`".$table_prefix."site_tmplvar_access` WHERE tmplvarid=$id;";
	$ds = $modx->db->query($sql);
	while($row = $modx->db->getRow($ds)) {
		$sql = "INSERT INTO $dbase.`".$table_prefix."site_tmplvar_access`
				(tmplvarid, documentgroup) VALUES
				('".$row['newid']."', '".$row['documentgroup']."');";
		$rs = $modx->db->query($sql);
	}
}
if (!$rs) {
	echo "A database error occured while trying to duplicate TV Acess Permissions: <br /><br />".$modx->db->getLastError();
	exit;
}

// finish duplicating - redirect to new variable
$header="Location: index.php?r=2&a=301&id=$newid";
header($header);
?>