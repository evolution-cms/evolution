<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!EvolutionCMS()->hasPermission('delete_snippet')) {
	EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	EvolutionCMS()->webAlertAndQuit($_lang["error_no_id"]);
}

// Set the item name for logger
$name = EvolutionCMS\Models\SiteSnippet::findOrFail($id)->name;
$_SESSION['itemname'] = $name;

// invoke OnBeforeSnipFormDelete event
EvolutionCMS()->invokeEvent("OnBeforeSnipFormDelete",
	[
		"id"	=> $id
	]);

// delete the snippet.
EvolutionCMS\Models\SiteSnippet::destroy($id);

// invoke OnSnipFormDelete event
EvolutionCMS()->invokeEvent("OnSnipFormDelete",
	[
		"id"	=> $id
	]);

// empty cache
EvolutionCMS()->clearCache('full');

// finished emptying cache - redirect
$header="Location: index.php?a=76&r=2&tab=3";
header($header);
