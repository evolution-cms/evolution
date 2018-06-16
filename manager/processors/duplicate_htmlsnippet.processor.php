<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('new_chunk')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// count duplicates
$name = $modx->getDatabase()->getValue($modx->getDatabase()->select('name', $modx->getDatabase()->getFullTableName('site_htmlsnippets'), "id='{$id}'"));
$count = $modx->getDatabase()->getRecordCount($modx->getDatabase()->select('name', $modx->getDatabase()->getFullTableName('site_htmlsnippets'), "name LIKE '{$name} {$_lang['duplicated_el_suffix']}%'"));
if($count>=1) $count = ' '.($count+1);
else $count = '';

// duplicate htmlsnippet
$newid = $modx->getDatabase()->insert(
	array(
		'name'=>'',
		'description'=>'',
		'snippet'=>'',
		'category'=>'',
		), $modx->getDatabase()->getFullTableName('site_htmlsnippets'), // Insert into
	"CONCAT(name, ' {$_lang['duplicated_el_suffix']}{$count}') AS name, description, snippet, category", $modx->getDatabase()->getFullTableName('site_htmlsnippets'), "id='{$id}'"); // Copy from

// Set the item name for logger
$name = $modx->getDatabase()->getValue($modx->getDatabase()->select('name', $modx->getDatabase()->getFullTableName('site_htmlsnippets'), "id='{$newid}'"));
$_SESSION['itemname'] = $name;

// finish duplicating - redirect to new chunk
$header="Location: index.php?r=2&a=78&id=$newid";
header($header);
