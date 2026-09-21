<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!EvolutionCMS()->hasPermission('delete_role')) {
	EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id'])? (int)$_GET['id'] : 0;
if($id==0) {
	EvolutionCMS()->webAlertAndQuit($_lang["error_no_id"]);
}

if($id==1){
	EvolutionCMS()->webAlertAndQuit("The role you are trying to delete is the admin role. This role cannot be deleted!");
}

$count = EvolutionCMS\Models\UserAttribute::where('role',$id)->count();
if($count>0){
	EvolutionCMS()->webAlertAndQuit("There are users with this role. It can't be deleted.");
}

// Set the item name for logger
$name = EvolutionCMS\Models\UserRole::select('name')->where('id',$id)->first()->name;
$_SESSION['itemname'] = $name;

// delete the module access rows granted to this role
EvolutionCMS\Models\SiteModuleRole::where('role',$id)->delete();

// delete the attributes
EvolutionCMS\Models\UserRole::select('name')->where('id',$id)->delete();

$header="Location: index.php?a=86";
header($header);
