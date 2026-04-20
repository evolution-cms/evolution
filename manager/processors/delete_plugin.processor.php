<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!EvolutionCMS()->hasPermission('delete_plugin')) {
	EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	EvolutionCMS()->webAlertAndQuit($_lang["error_no_id"]);
}

// Set the item name for logger
$name = EvolutionCMS\Models\SitePlugin::select("name")->firstOrFail($id)->name;
$_SESSION['itemname'] = $name;

// invoke OnBeforePluginFormDelete event
EvolutionCMS()->invokeEvent("OnBeforePluginFormDelete",
	[
		"id"	=> $id
	]);

// delete the plugin.
EvolutionCMS\Models\SitePlugin::destroy($id);
// delete the plugin events.
EvolutionCMS\Models\SitePluginEvent::where('pluginid',$id)->delete();
// invoke OnPluginFormDelete event
EvolutionCMS()->invokeEvent("OnPluginFormDelete",
	[
		"id"	=> $id
	]);

// empty cache
EvolutionCMS()->clearCache('full');

// finished emptying cache - redirect
$header="Location: index.php?a=76&r=2&tab=4";
header($header);
