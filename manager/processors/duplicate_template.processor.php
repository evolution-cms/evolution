<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('new_template')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// count duplicates
$name = $modx->db->getValue($modx->db->select('templatename', $modx->getFullTableName('site_templates'), "id='{$id}'"));
$count = $modx->db->getRecordCount($modx->db->select('templatename', $modx->getFullTableName('site_templates'), "templatename LIKE '{$name} {$_lang['duplicated_el_suffix']}%'"));
if($count>=1) $count = ' '.($count+1);
else $count = '';

// duplicate template
$newid = $modx->db->insert(
	array(
		'templatename'=>'',
		'description'=>'',
		'content'=>'',
		'category'=>'',
		), $modx->getFullTableName('site_templates'), // Insert into
	"CONCAT(templatename, ' {$_lang['duplicated_el_suffix']}{$count}') AS templatename, description, content, category", $modx->getFullTableName('site_templates'), "id='{$id}'"); // Copy from

// duplicate TV values
$modx->db->insert(
	array(
		'tmplvarid'=>'',
		'templateid'=>'',
		'`rank`'=>'',
		), $modx->getFullTableName('site_tmplvar_templates'), // Insert into
	"tmplvarid, '{$newid}', `rank`", $modx->getFullTableName('site_tmplvar_templates'), "templateid='{$id}'"); // Copy from

// Set the item name for logger
$name = $modx->db->getValue($modx->db->select('templatename', $modx->getFullTableName('site_templates'), "id='{$newid}'"));
$_SESSION['itemname'] = $name;

// finish duplicating - redirect to new template
$header="Location: index.php?r=2&a=16&id=$newid";
header($header);
