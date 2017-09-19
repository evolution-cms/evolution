<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('new_snippet')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? intval($_GET['id']) : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// count duplicates
$name = $modx->db->getValue($modx->db->select('name', $modx->getFullTableName('site_snippets'), "id='{$id}'"));
$count = $modx->db->getRecordCount($modx->db->select('name', $modx->getFullTableName('site_snippets'), "name LIKE '{$name} {$_lang['duplicated_el_suffix']}%'"));
if($count>=1) $count = ' '.($count+1);
else $count = '';

// duplicate Snippet
$newid = $modx->db->insert(
	array(
		'name'=>'',
		'description'=>'',
		'snippet'=>'',
		'properties'=>'',
		'category'=>'',
		), $modx->getFullTableName('site_snippets'), // Insert into
	"CONCAT(name, ' {$_lang['duplicated_el_suffix']}{$count}') AS name, description, snippet, properties, category", $modx->getFullTableName('site_snippets'), "id='{$id}'"); // Copy from

// Set the item name for logger
$name = $modx->db->getValue($modx->db->select('name', $modx->getFullTableName('site_snippets'), "id='{$newid}'"));
$_SESSION['itemname'] = $name;

// finish duplicating - redirect to new snippet
$header="Location: index.php?r=2&a=22&id=$newid";
header($header);
