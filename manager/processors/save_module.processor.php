<?php
global $id, $newid;
global $use_udperms;
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('save_module')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}else {
    $use_udperms = 1;
}

if (isset($_GET['disabled'])) {
    $disabled = $_GET['disabled'] == 1 ? 1 : 0;
    $id = (int)($_REQUEST['id'] ?? 0);
    // Set the item name for logger
    try {
        $module = EvolutionCMS\Models\SiteModule::findOrFail($id);
        // invoke OnBeforeChunkFormSave event
        $modx->invokeEvent("OnBeforeModFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);
        $_SESSION['itemname'] = $module->name;
        $module->update(['disabled' => $disabled]);
        // invoke OnChunkFormSave event
        $modx->invokeEvent("OnModFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        $modx->webAlertAndQuit($_lang["error_no_id"]);
    }
    // empty cache
    $modx->clearCache('full');

    // finished emptying cache - redirect
    $header="Location: index.php?a=76&tab=5&r=2";
    header($header);
    exit;
}

$id = (int)$_POST['id'];
$name = trim($_POST['name']);
$description = $_POST['description'];
$resourcefile = $_POST['resourcefile'];
$enable_resource = (isset($_POST['enable_resource']) && $_POST['enable_resource'] == 'on') ? 1 : 0;
$icon = $_POST['icon'];
//$category = (int)$_POST['category'];
$disabled = (isset($_POST['disabled']) && $_POST['disabled'] == 'on') == 'on' ? 1 : 0;
$wrap = (isset($_POST['wrap']) && $_POST['wrap'] == 'on') == 'on' ? 1 : 0;
$locked = (isset($_POST['locked']) && $_POST['locked'] == 'on') == 'on' ? 1 : 0;
$modulecode = $_POST['post'] ?? '';
$properties = $_POST['properties'] ?? '';
$enable_sharedparams = (isset($_POST['enable_sharedparams']) && $_POST['enable_sharedparams'] == 'on') == 'on' ? 1 : 0;
$guid = $_POST['guid'];
$parse_docblock = (isset($_POST['parse_docblock']) && $_POST['parse_docblock'] == 'on') == "1" ? '1' : '0';
$currentdate = time() + $modx->config['server_offset_time'];

//Kyle Jaebker - added category support
if (empty($_POST['newcategory']) && $_POST['categoryid'] > 0) {
    $categoryid = (int)$_POST['categoryid'];
} elseif (empty($_POST['newcategory']) && $_POST['categoryid'] <= 0) {
    $categoryid = 0;
} else {
    include_once(EVO_MANAGER_PATH . 'includes/categories.inc.php');
    $categoryid = checkCategory($_POST['newcategory']);
    if (!$categoryid) {
        $categoryid = newCategory($_POST['newcategory']);
    }
}

if ($name == "") {
    $name = "Untitled module";
}

if ($parse_docblock) {
    $parsed = $modx->parseDocBlockFromString($modulecode, true);
    $name = isset($parsed['name']) ? $parsed['name'] : $name;
    $properties = isset($parsed['properties']) ? $parsed['properties'] : $properties;
    $guid = isset($parsed['guid']) ? $parsed['guid'] : $guid;
    $enable_sharedparams = isset($parsed['shareparams']) ? (int)$parsed['shareparams'] : $enable_sharedparams;

    $description = isset($parsed['description']) ? $parsed['description'] : $description;
    $version = isset($parsed['version']) ? '<b>' . $parsed['version'] . '</b> ' : '';
    if ($version) {
        $description = $version . trim(preg_replace('/(<b>.+?)+(<\/b>)/i', '', $description));
    }
    if (isset($parsed['modx_category'])) {
        include_once(EVO_MANAGER_PATH . 'includes/categories.inc.php');
        $categoryid = getCategory($parsed['modx_category']);
    }
}

switch ($_POST['mode']) {
    case '107':
        // invoke OnBeforeModFormSave event
        $modx->invokeEvent("OnBeforeModFormSave", [
                "mode" => "new",
                "id" => $id
            ]);

        // disallow duplicate names for new modules
        $count = \EvolutionCMS\Models\SiteModule::query()->where('name', $name)->count();
        if ($count > 0) {
            $modx->getManagerApi()->saveFormValues(107);
            $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_module'], $name), "index.php?a=107");
        }

        // save the new module
        $newid = \EvolutionCMS\Models\SiteModule::query()->insertGetId([
            'name' => $name,
            'description' => $description,
            'disabled' => $disabled,
            'wrap' => $wrap,
            'locked' => $locked,
            'icon' => $icon,
            'resourcefile' => $resourcefile,
            'enable_resource' => $enable_resource,
            'category' => $categoryid,
            'enable_sharedparams' => $enable_sharedparams,
            'guid' => $guid,
            'modulecode' => $modulecode,
            'properties' => $properties,
            'createdon' => $currentdate,
            'editedon' => $currentdate
        ]);

        // save user group access permissions
        saveUserGroupAccessPermissons();

        // invoke OnModFormSave event
        $modx->invokeEvent("OnModFormSave", [
                "mode" => "new",
                "id" => $newid
            ]);

        // Set the item name for logger
        $_SESSION['itemname'] = $name;

        // empty cache
        $modx->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "108&id=$newid" : "107";
            $header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
            header($header);
        } else {
            $header = "Location: index.php?a=76&tab=5&r=2";
            header($header);
        }
        break;
    case '108':
        // invoke OnBeforeModFormSave event
        $modx->invokeEvent("OnBeforeModFormSave", [
                "mode" => "upd",
                "id" => $id
            ]);

        // disallow duplicate names for new modules
        $count = \EvolutionCMS\Models\SiteModule::query()->where('name', $name)->where('id', '!=', $id)->count();

        if ($count > 0) {
            $modx->getManagerApi()->saveFormValues(108);
            $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_module'], $name), "index.php?a=108&id={$id}");
        }

        // save the edited module
        \EvolutionCMS\Models\SiteModule::find($id)->update([
            'name' => $name,
            'description' => $description,
            'icon' => $icon,
            'enable_resource' => $enable_resource,
            'resourcefile' => $resourcefile,
            'disabled' => $disabled,
            'wrap' => $wrap,
            'locked' => $locked,
            'category' => $categoryid,
            'enable_sharedparams' => $enable_sharedparams,
            'guid' => $guid,
            'modulecode' => $modulecode,
            'properties' => $properties,
            'editedon' => $currentdate
        ]);

        // save user group access permissions
        saveUserGroupAccessPermissons();

        // invoke OnModFormSave event
        $modx->invokeEvent("OnModFormSave", [
                "mode" => "upd",
                "id" => $id
            ]);

        // Set the item name for logger
        $_SESSION['itemname'] = $name;

        // empty cache
        $modx->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "108&id=$id" : "107";
            $header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
            header($header);
        } else {
            $modx->unlockElement(6, $id);
            $header = "Location: index.php?a=76&tab=5&r=2";
            header($header);
        }
        break;
    default:
        $modx->webAlertAndQuit("No operation set in request.");
}
