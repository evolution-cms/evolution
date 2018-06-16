<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('save_snippet')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = (int)$_POST['id'];
$snippet = trim($_POST['post']);
$name = $modx->getDatabase()->escape(trim($_POST['name']));
$description = $modx->getDatabase()->escape($_POST['description']);
$locked = $_POST['locked'] == 'on' ? 1 : 0;
$disabled = $_POST['disabled'] == "on" ? '1' : '0';
$currentdate = time() + $modx->config['server_offset_time'];

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

$snippet = $modx->getDatabase()->escape($snippet);
$properties = $modx->getDatabase()->escape($_POST['properties']);
$moduleguid = $modx->getDatabase()->escape($_POST['moduleguid']);
$parse_docblock = $_POST['parse_docblock'] == "1" ? '1' : '0';

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
    $name = "Untitled snippet";
}

if ($parse_docblock) {
    $parsed = $modx->parseDocBlockFromString($snippet, true);
    $name = isset($parsed['name']) ? $parsed['name'] : $name;
    $properties = isset($parsed['properties']) ? $parsed['properties'] : $properties;
    $moduleguid = isset($parsed['guid']) ? $parsed['guid'] : $moduleguid;

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
    case '23': // Save new snippet

        // invoke OnBeforeSnipFormSave event
        $modx->invokeEvent("OnBeforeSnipFormSave", array(
            "mode" => "new",
            "id" => $id
        ));

        // disallow duplicate names for new snippets
        $rs = $modx->getDatabase()->select('COUNT(id)', $modx->getDatabase()->getFullTableName('site_snippets'), "name='{$name}'");
        $count = $modx->getDatabase()->getValue($rs);
        if ($count > 0) {
            $modx->getManagerApi()->saveFormValues(23);
            $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['snippet'], $name), "index.php?a=23");
        }

        //do stuff to save the new doc
        $newid = $modx->getDatabase()->insert(array(
            'name' => $name,
            'description' => $description,
            'snippet' => $snippet,
            'moduleguid' => $moduleguid,
            'locked' => $locked,
            'properties' => $properties,
            'category' => $categoryid,
            'disabled' => $disabled,
            'createdon' => $currentdate,
            'editedon' => $currentdate
        ), $modx->getDatabase()->getFullTableName('site_snippets'));

        // invoke OnSnipFormSave event
        $modx->invokeEvent("OnSnipFormSave", array(
            "mode" => "new",
            "id" => $newid
        ));

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
            $header = "Location: index.php?a=76&r=2";
            header($header);
        }
        break;
    case '22': // Save existing snippet
        // invoke OnBeforeSnipFormSave event
        $modx->invokeEvent("OnBeforeSnipFormSave", array(
            "mode" => "upd",
            "id" => $id
        ));

        // disallow duplicate names for snippets
        $rs = $modx->getDatabase()->select('COUNT(*)', $modx->getDatabase()->getFullTableName('site_snippets'), "name='{$name}' AND id!='{$id}'");
        if ($modx->getDatabase()->getValue($rs) > 0) {
            $modx->getManagerApi()->saveFormValues(22);
            $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['snippet'], $name), "index.php?a=22&id={$id}");
        }

        //do stuff to save the edited doc
        $modx->getDatabase()->update(array(
            'name' => $name,
            'description' => $description,
            'snippet' => $snippet,
            'moduleguid' => $moduleguid,
            'locked' => $locked,
            'properties' => $properties,
            'category' => $categoryid,
            'disabled' => $disabled,
            'editedon' => $currentdate
        ), $modx->getDatabase()->getFullTableName('site_snippets'), "id='{$id}'");

        // invoke OnSnipFormSave event
        $modx->invokeEvent("OnSnipFormSave", array(
            "mode" => "upd",
            "id" => $id
        ));

        // Set the item name for logger
        $_SESSION['itemname'] = $name;

        // empty cache
        $modx->clearCache('full');

        if ($_POST['runsnippet']) {
            run_snippet($snippet);
        }
        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "22&id=$id" : "23";
            $header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
            header($header);
        } else {
            $modx->unlockElement(4, $id);
            $header = "Location: index.php?a=76&r=2";
            header($header);
        }
        break;
    default:
        $modx->webAlertAndQuit("No operation set in request.");
}
