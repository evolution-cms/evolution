<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('new_module')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}
// count duplicates
$name = $modx->getDatabase()->getValue($modx->getDatabase()->select('name', $modx->getDatabase()->getFullTableName('site_modules'), "id='{$id}'"));
$count = $modx->getDatabase()->getRecordCount($modx->getDatabase()->select('name', $modx->getDatabase()->getFullTableName('site_modules'), "name LIKE '{$name} {$_lang['duplicated_el_suffix']}%'"));
if($count>=1) $count = ' '.($count+1);
else $count = '';

// duplicate module
$newid = $modx->getDatabase()->insert(
	array(
		'name'=>'',
		'description'=>'',
		'disabled'=>'',
		'category'=>'',
		'wrap'=>'',
		'icon'=>'',
		'enable_resource'=>'',
		'resourcefile'=>'',
		'createdon'=>'',
		'editedon'=>'',
		'guid'=>'',
		'enable_sharedparams'=>'',
		'properties'=>'',
		'modulecode'=>'',
		), $modx->getDatabase()->getFullTableName('site_modules'), // Insert into
	"CONCAT(name, ' {$_lang['duplicated_el_suffix']}{$count}') AS name, description, '1' AS disabled, category, wrap, icon, enable_resource, resourcefile, createdon, editedon, '".createGUID()."' AS guid, enable_sharedparams, properties, modulecode", $modx->getDatabase()->getFullTableName('site_modules'), "id='{$id}'"); // Copy from

// duplicate module dependencies
$modx->getDatabase()->insert(
	array(
		'module'=>'',
		'resource'=>'',
		'type'=>'',
		), $modx->getDatabase()->getFullTableName('site_module_depobj'), // Insert into
	"'{$newid}', resource, type", $modx->getDatabase()->getFullTableName('site_module_depobj'), "module='{$id}'"); // Copy from

// duplicate module user group access
$modx->getDatabase()->insert(
	array(
		'module'=>'',
		'usergroup'=>'',
		), $modx->getDatabase()->getFullTableName('site_module_access'), // Insert into
	"'{$newid}', usergroup", $modx->getDatabase()->getFullTableName('site_module_access'), "module='{$id}'"); // Copy from

// Set the item name for logger
$name = $modx->getDatabase()->getValue($modx->getDatabase()->select('name', $modx->getDatabase()->getFullTableName('site_modules'), "id='{$newid}'"));
$_SESSION['itemname'] = $name;

// finish duplicating - redirect to new module
$header="Location: index.php?r=2&a=108&id=$newid";
header($header);
