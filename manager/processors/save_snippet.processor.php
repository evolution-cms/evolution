<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('save_snippet')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

if (isset($_GET['disabled'])) {
    $disabled = $_GET['disabled'] == 1 ? 1 : 0;
    $id = (int)($_REQUEST['id'] ?? 0);
    // Set the item name for logger
    try {
        $snippet = EvolutionCMS\Models\SiteSnippet::findOrFail($id);
        // invoke OnBeforeChunkFormSave event
        $modx->invokeEvent("OnBeforeSnipFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);
        $_SESSION['itemname'] = $snippet->name;
        $snippet->update(['disabled' => $disabled]);
        // invoke OnChunkFormSave event
        $modx->invokeEvent("OnSnipFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        $modx->webAlertAndQuit($_lang["error_no_id"]);
    }
    // empty cache
    $modx->clearCache('full');

    // finished emptying cache - redirect
    $header="Location: index.php?a=76&tab=3&r=2";
    header($header);
    exit;
}

$id = (int)$_POST['id'];
$snippet = trim($_POST['post']);
$name = trim($_POST['name']);
$description = $_POST['description'];
$locked = isset($_POST['locked']) && $_POST['locked'] == 'on' ? 1 : 0;
$disabled = isset($_POST['disabled']) && $_POST['disabled'] == "on" ? '1' : '0';
$createdon = $editedon = time() + $modx->config['server_offset_time'];

// strip out PHP tags from snippets
if (strncmp($snippet, "<?", 2) == 0) {
    $snippet = substr($snippet, 2);
    if (strncmp($snippet, "php", 3) == 0) {
        $snippet = substr($snippet, 3);
    }
}

if (substr($snippet, -2) == '?>') {
    $snippet = substr($snippet, 0, -2);
}

$properties = $_POST['properties'];
$moduleguid = $_POST['moduleguid'];
$parse_docblock = isset($_POST['parse_docblock']) && $_POST['parse_docblock'] == "1" ? '1' : '0';

//Kyle Jaebker - added category support
if (empty($_POST['newcategory']) && $_POST['categoryid'] > 0) {
    $category = (int)$_POST['categoryid'];
} elseif (empty($_POST['newcategory']) && $_POST['categoryid'] <= 0) {
    $category = 0;
} else {
    include_once(EVO_MANAGER_PATH . 'includes/categories.inc.php');
    $category = checkCategory($_POST['newcategory']);
    if (!$category) {
        $category = newCategory($_POST['newcategory']);
    }
}

if ($name == "") {
    $name = "Untitled snippet";
}

if ($parse_docblock) {
    $parsed = DocBlock::parseFromString($snippet);
    $name = isset($parsed['name']) ? $parsed['name'] : $name;
    $properties = isset($parsed['properties']) ? $parsed['properties'] : $properties;
    $moduleguid = isset($parsed['guid']) ? $parsed['guid'] : $moduleguid;

    $description = isset($parsed['description']) ? $parsed['description'] : $description;
    $version = isset($parsed['version']) ? '<b>' . $parsed['version'] . '</b> ' : '';
    if ($version) {
        $description = $version . trim(preg_replace('/(<b>.+?)+(<\/b>)/i', '', $description));
    }
    if (isset($parsed['modx_category'])) {
        include_once(EVO_MANAGER_PATH . 'includes/categories.inc.php');
        $category = getCategory($parsed['modx_category']);
    }
}

switch ($_POST['mode']) {
    case '23': // Save new snippet

        // invoke OnBeforeSnipFormSave event
        $modx->invokeEvent("OnBeforeSnipFormSave", [
            "mode" => "new",
            "id" => $id
        ]);

        // disallow duplicate names for new snippets
        if (EvolutionCMS\Models\SiteSnippet::where('name','=',$name)->first()) {
            $modx->getManagerApi()->saveFormValues(23);
            $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['snippet'], $name), "index.php?a=23");
        }

        //do stuff to save the new doc
        $newid = EvolutionCMS\Models\SiteSnippet::create(compact('name', 'description','snippet','moduleguid','locked','properties','category','disabled','createdon','editedon'))->getKey();

        // invoke OnSnipFormSave event
        $modx->invokeEvent("OnSnipFormSave", [
            "mode" => "new",
            "id" => $newid
        ]);

        // Set the item name for logger
        $_SESSION['itemname'] = $name;

        // empty cache
        $modx->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "22&id=$newid" : "23";
            $header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
            header($header);
        } else {
            $header = "Location: index.php?a=76&tab=3&r=2";
            header($header);
        }
        break;
    case '22': // Save existing snippet
        // invoke OnBeforeSnipFormSave event
        $modx->invokeEvent("OnBeforeSnipFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);

        // disallow duplicate names for snippets
        if (EvolutionCMS\Models\SiteSnippet::where('id','!=',$id)->where('name','=',$name)->first()) {
            $modx->getManagerApi()->saveFormValues(22);
            $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['snippet'], $name), "index.php?a=22&id={$id}");
        }

        //do stuff to save the edited doc
        $siteSnippet= EvolutionCMS\Models\SiteSnippet::find($id);

        $siteSnippet->update(compact('name', 'description','snippet','moduleguid','locked','properties','category','disabled','editedon'));

        // invoke OnSnipFormSave event
        $modx->invokeEvent("OnSnipFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);

        // Set the item name for logger
        $_SESSION['itemname'] = $name;

        // empty cache
        $modx->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "22&id=$id" : "23";
            $header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
            header($header);
        } else {
            $modx->unlockElement(4, $id);
            $header = "Location: index.php?a=76&tab=3&r=2";
            header($header);
        }
        break;
    default:
        $modx->webAlertAndQuit("No operation set in request.");
}
