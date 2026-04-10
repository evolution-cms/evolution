<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!EvolutionCMS()->hasPermission('save_plugin') && !EvolutionCMS()->hasPermission('save_snippet') && !EvolutionCMS()->hasPermission('save_template') && !EvolutionCMS()->hasPermission('save_module')) {
    EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['catId'])? (int)$_GET['catId'] : 0;
if ($id==0) {
    EvolutionCMS()->webAlertAndQuit($_lang["error_no_id"]);
}

// Set the item name for logger
$name = \EvolutionCMS\Models\Category::find($id)->category;
$_SESSION['itemname'] = $name;

include_once(EVO_MANAGER_PATH.'includes/categories.inc.php');
deleteCategory($id);

// finished emptying cache - redirect
$header="Location: index.php?a=76";
header($header);
