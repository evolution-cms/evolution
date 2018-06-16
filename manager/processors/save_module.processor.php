<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('save_module')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = (int)$_POST['id'];
$name = $modx->getDatabase()->escape(trim($_POST['name']));
$description = $modx->getDatabase()->escape($_POST['description']);
$resourcefile = $modx->getDatabase()->escape($_POST['resourcefile']);
$enable_resource = $_POST['enable_resource'] == 'on' ? 1 : 0;
$icon = $modx->getDatabase()->escape($_POST['icon']);
//$category = (int)$_POST['category'];
$disabled = $_POST['disabled'] == 'on' ? 1 : 0;
$wrap = $_POST['wrap'] == 'on' ? 1 : 0;
$locked = $_POST['locked'] == 'on' ? 1 : 0;
$modulecode = $modx->getDatabase()->escape($_POST['post']);
$properties = $modx->getDatabase()->escape($_POST['properties']);
$enable_sharedparams = $_POST['enable_sharedparams'] == 'on' ? 1 : 0;
$guid = $modx->getDatabase()->escape($_POST['guid']);
$parse_docblock = $_POST['parse_docblock'] == "1" ? '1' : '0';
$currentdate = time() + $modx->config['server_offset_time'];

//Kyle Jaebker - added category support
if (empty($_POST['newcategory']) && $_POST['categoryid'] > 0) {
    $categoryid = (int)$_POST['categoryid'];
} elseif (empty($_POST['newcategory']) && $_POST['categoryid'] <= 0) {
    $categoryid = 0;
} else {
    include_once(MODX_MANAGER_PATH . 'includes/categories.inc.php');
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
        include_once(MODX_MANAGER_PATH . 'includes/categories.inc.php');
        $categoryid = getCategory($parsed['modx_category']);
    }
}

switch ($_POST['mode']) {
    case '107':
        // invoke OnBeforeModFormSave event
        $modx->invokeEvent("OnBeforeModFormSave", array(
                "mode" => "new",
                "id" => $id
            ));

        // disallow duplicate names for new modules
        $rs = $modx->getDatabase()->select('count(id)', $modx->getDatabase()->getFullTableName('site_modules'), "name='{$name}'");
        $count = $modx->getDatabase()->getValue($rs);
        if ($count > 0) {
            $modx->getManagerApi()->saveFormValues(107);
            $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_module'], $name), "index.php?a=107");
        }

        // save the new module
        $newid = $modx->getDatabase()->insert(array(
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
        ), $modx->getDatabase()->getFullTableName('site_modules'));

        // save user group access permissions
        saveUserGroupAccessPermissons();

        // invoke OnModFormSave event
        $modx->invokeEvent("OnModFormSave", array(
                "mode" => "new",
                "id" => $newid
            ));

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
            $header = "Location: index.php?a=106&r=2";
            header($header);
        }
        break;
    case '108':
        // invoke OnBeforeModFormSave event
        $modx->invokeEvent("OnBeforeModFormSave", array(
                "mode" => "upd",
                "id" => $id
            ));

        // disallow duplicate names for new modules
        $rs = $modx->getDatabase()->select('count(id)', $modx->getDatabase()->getFullTableName('site_modules'), "name='{$name}' AND id!='{$id}'");
        if ($modx->getDatabase()->getValue($rs) > 0) {
            $modx->getManagerApi()->saveFormValues(108);
            $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_module'], $name), "index.php?a=108&id={$id}");
        }

        // save the edited module
        $modx->getDatabase()->update(array(
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
        ), $modx->getDatabase()->getFullTableName('site_modules'), "id='{$id}'");

        // save user group access permissions
        saveUserGroupAccessPermissons();

        // invoke OnModFormSave event
        $modx->invokeEvent("OnModFormSave", array(
                "mode" => "upd",
                "id" => $id
            ));

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
            $header = "Location: index.php?a=106&r=2";
            header($header);
        }
        break;
    default:
        $modx->webAlertAndQuit("No operation set in request.");
}
