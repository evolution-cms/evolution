<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!EvolutionCMS()->hasPermission('new_module')) {
    EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    EvolutionCMS()->webAlertAndQuit($_lang["error_no_id"]);
}
// count duplicates
$name = EvolutionCMS\Models\SiteModule::select('name')->findOrFail($id)->name;
$count = EvolutionCMS\Models\SiteModule::where('name', 'LIKE', "{$name} {$_lang['duplicated_el_suffix']}%'")->count();
if ($count >= 1) $count = ' ' . ($count + 1);
else $count = '';

// duplicate module
$module = EvolutionCMS\Models\SiteModule::select("name",
    "description",  "category", "wrap", "icon", "enable_resource", "resourcefile", "createdon",
    "editedon", "guid", "enable_sharedparams", "properties", "modulecode")
    ->findOrFail($id);

$moduleNew = $module->replicate();
$moduleNew->name .= " {$_lang['duplicated_el_suffix']}{$count}";
$moduleNew->guid = createGUID();
$moduleNew->disabled = 1;
$moduleNew->save();
$newid = $moduleNew->id;

// duplicate module dependencies
EvolutionCMS\Models\SiteModuleDepobj::select("module", "resource", "type")
    ->where('module', $id)->get()
    ->each(function ($item, $key) use ($newid) {
        $item->module = $newid;
        $item->replicate()->save();
    });


// duplicate module user group access
EvolutionCMS\Models\SiteModuleAccess::select("module", "usergroup")
    ->where('module', $id)->get()
    ->each(function ($item, $key) use ($newid) {
        $item->module = $newid;
        $item->replicate()->save();
    });

// duplicate module role access
EvolutionCMS\Models\SiteModuleRole::select("module", "role")
    ->where('module', $id)->get()
    ->each(function ($item, $key) use ($newid) {
        $item->module = $newid;
        $item->replicate()->save();
    });

// Set the item name for logger
$name = EvolutionCMS\Models\SiteModule::select('name')->findOrFail($newid)->name;
$_SESSION['itemname'] = $name;

// finish duplicating - redirect to new module
$header="Location: index.php?r=2&a=108&id=$newid";
header($header);
