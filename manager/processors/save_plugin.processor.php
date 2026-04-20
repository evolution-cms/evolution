<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.');
}
if (!$modx->hasPermission('save_plugin')) {
    $modx->webAlertAndQuit($_lang['error_no_privileges']);
}

if (isset($_GET['disabled'])) {
    $disabled = $_GET['disabled'] == 1 ? 1 : 0;
    $id = (int)($_REQUEST['id'] ?? 0);
    // Set the item name for logger
    try {
        $plugin = EvolutionCMS\Models\SitePlugin::findOrFail($id);
        // invoke OnBeforeChunkFormSave event
        $modx->invokeEvent("OnBeforePluginFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);
        $_SESSION['itemname'] = $plugin->name;
        $plugin->update(['disabled' => $disabled]);
        // invoke OnChunkFormSave event
        $modx->invokeEvent("OnPluginFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        $modx->webAlertAndQuit($_lang["error_no_id"]);
    }
    // empty cache
    $modx->clearCache('full');

    // finished emptying cache - redirect
    $header="Location: index.php?a=76&tab=4&r=2";
    header($header);
    exit;
}

$id = (int)$_POST['id'];
$name = trim($_POST['name']);
$description = $_POST['description'];
$locked = isset($_POST['locked']) && $_POST['locked'] == 'on' ? '1' : '0';
$plugincode = $_POST['post'];
$properties = $_POST['properties'];
$disabled = isset($_POST['disabled']) && $_POST['disabled'] == 'on' ? '1' : '0';
$moduleguid = $_POST['moduleguid'];
$sysevents = !empty($_POST['sysevents']) ? $_POST['sysevents'] : [];
$parse_docblock = isset($_POST['parse_docblock']) && $_POST['parse_docblock'] == '1' ? '1' : '0';
$currentdate = time() + $modx->config['server_offset_time'];

//Kyle Jaebker - added category support
if (empty($_POST['newcategory']) && $_POST['categoryid'] > 0) {
    $categoryid = (int)$_POST['categoryid'];
} elseif (empty($_POST['newcategory']) && $_POST['categoryid'] <= 0) {
    $categoryid = 0;
} else {
    include_once(EVO_MANAGER_PATH . 'includes/categories.inc.php');
    $categoryid = getCategory($_POST['newcategory']);
}

if ($name == '') {
    $name = 'Untitled plugin';
}

if ($parse_docblock) {
    $parsed = $modx->parseDocBlockFromString($plugincode, true);
    $name = isset($parsed['name']) ? $parsed['name'] : $name;
    $sysevents = isset($parsed['events']) ? explode(',', $parsed['events']) : $sysevents;
    $properties = isset($parsed['properties']) ? $parsed['properties'] : $properties;
    $moduleguid = isset($parsed['guid']) ? $parsed['guid'] : $moduleguid;

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

$eventIds = [];
switch ($_POST['mode']) {
    case '101':

        // invoke OnBeforePluginFormSave event
        $modx->invokeEvent('OnBeforePluginFormSave', [
            'mode' => 'new',
            'id' => $id
        ]);

        // disallow duplicate names for active plugins
        if ($disabled == '0') {
            $count = \EvolutionCMS\Models\SitePlugin::query()->where('name', $name)->where('disabled', 0)->count();
            if ($count > 0) {
                $modx->getManagerApi()->saveFormValues(101);
                $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['plugin'], $name), 'index.php?a=101');
            }
        }

        //do stuff to save the new plugin
        $newid = \EvolutionCMS\Models\SitePlugin::query()->insertGetId([
            'name' => $name,
            'description' => $description,
            'plugincode' => $plugincode,
            'disabled' => $disabled,
            'moduleguid' => $moduleguid,
            'locked' => $locked,
            'properties' => $properties,
            'category' => $categoryid,
            'createdon' => $currentdate,
            'editedon' => $currentdate
        ]);

        // save event listeners
        saveEventListeners($newid, $sysevents, $_POST['mode']);

        // invoke OnPluginFormSave event
        $modx->invokeEvent('OnPluginFormSave', [
            'mode' => 'new',
            'id' => $newid
        ]);

        // Set the item name for logger
        $_SESSION['itemname'] = $name;

        // empty cache
        $modx->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "102&id=$newid" : '101';
            $header = 'Location: index.php?a=' . $a . '&r=2&stay=' . $_POST['stay'];
            header($header);
        } else {
            $header = 'Location: index.php?a=76&tab=4&r=2';
            header($header);
        }
        break;
    case '102':

        // invoke OnBeforePluginFormSave event
        $modx->invokeEvent('OnBeforePluginFormSave', [
            'mode' => 'upd',
            'id' => $id
        ]);

        // disallow duplicate names for active plugins
        if ($disabled == '0') {
            $count = \EvolutionCMS\Models\SitePlugin::query()->where('name', $name)->where('disabled', 0)->where('id', '!=', $id)->count();
            if ($count > 0) {
                $modx->getManagerApi()->saveFormValues(102);
                $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['plugin'], $name), "index.php?a=102&id={$id}");
            }
        }

        //do stuff to save the edited plugin
        $newid = \EvolutionCMS\Models\SitePlugin::query()->find($id)->update([
            'name' => $name,
            'description' => $description,
            'plugincode' => $plugincode,
            'disabled' => $disabled,
            'moduleguid' => $moduleguid,
            'locked' => $locked,
            'properties' => $properties,
            'category' => $categoryid,
            'editedon' => $currentdate
        ]);

        // save event listeners
        saveEventListeners($id, $sysevents, $_POST['mode']);

        // invoke OnPluginFormSave event
        $modx->invokeEvent('OnPluginFormSave', [
            'mode' => 'upd',
            'id' => $id
        ]);

        // Set the item name for logger
        $_SESSION['itemname'] = $name;

        // empty cache
        $modx->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "102&id=$id" : '101';
            $header = 'Location: index.php?a=' . $a . '&r=2&stay=' . $_POST['stay'];
            header($header);
        } else {
            $modx->unlockElement(5, $id);
            $header = 'Location: index.php?a=76&tab=4&r=2';
            header($header);
        }
        break;
    default:
        $modx->webAlertAndQuit('No operation set in request.');
}
