<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!EvolutionCMS()->hasPermission('delete_module')) {
	EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	EvolutionCMS()->webAlertAndQuit($_lang["error_no_id"]);
}

// Set the item name for logger
$name = EvolutionCMS\Models\SiteModule::select("name")->firstOrFail($id)->name;
$_SESSION['itemname'] = $name;

// invoke OnBeforeModFormDelete event
EvolutionCMS()->invokeEvent("OnBeforeModFormDelete",
	[
		"id"	=> $id
	]);

// delete the module.
EvolutionCMS\Models\SiteModule::destroy($id);
// delete the module dependencies.
EvolutionCMS\Models\SiteModuleDepobj::where('module',$id)->delete();
// delete the module user group access.
EvolutionCMS\Models\SiteModuleAccess::where('module',$id)->delete();

// invoke OnModFormDelete event
EvolutionCMS()->invokeEvent("OnModFormDelete",
	[
		"id"	=> $id
	]);

// empty cache
EvolutionCMS()->clearCache('full');

// finished emptying cache - redirect
$header="Location: index.php?a=76&r=2&tab=5";
header($header);
