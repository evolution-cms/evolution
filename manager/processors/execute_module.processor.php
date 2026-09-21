<?php

use EvolutionCMS\Models\SiteModule;
use EvolutionCMS\Support\ModuleAccess;

if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('exec_module')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}
if (isset($_GET['id'])) {
    if (is_numeric($_GET['id'])) {
        $id = (int)$_GET['id'];
    } elseif (is_string($_GET['id'])) {
        // the key of a module registered from a file
        $id = $_GET['id'];
    } else {
        // ?id[]=... and friends: not an id at all
        $modx->webAlertAndQuit($_lang["error_no_id"]);
    }
} else {
    $modx->webAlertAndQuit($_lang["error_no_id"]);
}
$mgrRole = (int)get_by_key($_SESSION, 'mgrRole', 0);

if (is_numeric($id)) {
    // check if user has access permission, except admins
    if ($mgrRole !== ModuleAccess::ADMIN_ROLE) {
        $moduleAccess = SiteModule::query()
            ->select('site_modules.id')
            ->withoutProtected()
            ->where('site_modules.id', $id)
            ->first();

        if (empty($moduleAccess)) {
            $modx->webAlertAndQuit($_lang["module_exec_no_privileges"], "index.php?a=76&tab=5");
        }
    }

    // get module data
    $content = \EvolutionCMS\Models\SiteModule::find($id);
    if (is_null($content)) {
        $modx->webAlertAndQuit(sprintf($_lang["module_exec_not_found"], e($id)), "index.php?a=76&tab=5");
    }
    $content = $content->toArray();
    if ($content['disabled']) {
        $modx->webAlertAndQuit($_lang["module_exec_disabled"], "index.php?a=76&tab=5");
    }
} else {
    // file based modules: the id is a registry key, never a path - reject
    // anything that is not registered instead of reading an arbitrary file
    if (!is_string($id) || !isset($modx->modulesFromFile[$id])) {
        $modx->webAlertAndQuit(sprintf($_lang["module_exec_not_found"], e($id)), "index.php?a=76&tab=5");
    }

    $content = $modx->modulesFromFile[$id];

    // file based modules carry their role restriction in the registration
    // params; the group ACL cannot apply because they have no database row
    if (!ModuleAccess::canRunFileModule($mgrRole, $content)) {
        $modx->webAlertAndQuit($_lang["module_exec_no_privileges"], "index.php?a=76&tab=5");
    }

    if (!is_file($content['file']) || !is_readable($content['file'])) {
        $modx->webAlertAndQuit(sprintf($_lang["module_exec_not_found"], e($id)), "index.php?a=76&tab=5");
    }

    $content['modulecode'] = file_get_contents($content['file']);
    $content["guid"] = '';
}
// Set the item name for logger
$_SESSION['itemname'] = $content['name'];

// load module configuration
$parameter = $modx->parseProperties($content["properties"], $content["guid"], 'module');

// Set the item name for logger
$_SESSION['itemname'] = $content['name'];

if (substr($content["modulecode"], 0, 5) === '<?php') {
    $content["modulecode"] = substr($content["modulecode"], 5);
}
echo evalModule($content["modulecode"], $parameter);
include EVO_MANAGER_PATH . "includes/sysalert.display.inc.php";
