<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('save_chunk')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = (int)$_POST['id'];
$snippet = $modx->getDatabase()->escape($_POST['post']);
$name = $modx->getDatabase()->escape(trim($_POST['name']));
$description = $modx->getDatabase()->escape($_POST['description']);
$locked = $_POST['locked'] == 'on' ? 1 : 0;
$disabled = $_POST['disabled'] == "on" ? '1' : '0';
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

if ($name == "" || $name == 'null') {
    $name = "Untitled chunk";
}

$editor_type = $_POST['which_editor'] != 'none' ? 1 : 2;
$editor_name = $_POST['which_editor'] != 'none' ? $_POST['which_editor'] : 'none';

switch ($_POST['mode']) {
    case '77':

        // invoke OnBeforeChunkFormSave event
        $modx->invokeEvent("OnBeforeChunkFormSave", array(
            "mode" => "new",
            "id" => $id
        ));

        // disallow duplicate names for new chunks
        $rs = $modx->getDatabase()->select('COUNT(*)', $modx->getDatabase()->getFullTableName('site_htmlsnippets'), "name='{$name}'");
        $count = $modx->getDatabase()->getValue($rs);
        if ($count > 0) {
            $modx->getManagerApi()->saveFormValues(77);
            $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['chunk'], $name), "index.php?a=77");
        }

        //do stuff to save the new doc
        $newid = $modx->getDatabase()->insert(array(
            'name' => $name,
            'description' => $description,
            'snippet' => $snippet,
            'locked' => $locked,
            'category' => $categoryid,
            'editor_type' => $editor_type,
            'editor_name' => $editor_name,
            'disabled' => $disabled,
            'createdon' => $currentdate,
            'editedon' => $currentdate
        ), $modx->getDatabase()->getFullTableName('site_htmlsnippets'));

        // invoke OnChunkFormSave event
        $modx->invokeEvent("OnChunkFormSave", array(
            "mode" => "new",
            "id" => $newid
        ));

        // Set the item name for logger
        $_SESSION['itemname'] = $name;

        // empty cache
        $modx->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "78&id=$newid" : "77";
            $header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
            header($header);
        } else {
            $header = "Location: index.php?a=76&r=2";
            header($header);
        }
        break;
    case '78':
        // invoke OnBeforeChunkFormSave event
        $modx->invokeEvent("OnBeforeChunkFormSave", array(
            "mode" => "upd",
            "id" => $id
        ));

        // disallow duplicate names for chunks
        $rs = $modx->getDatabase()->select('COUNT(*)', $modx->getDatabase()->getFullTableName('site_htmlsnippets'), "name='{$name}' AND id!='{$id}'");
        if ($modx->getDatabase()->getValue($rs) > 0) {
            $modx->getManagerApi()->saveFormValues(78);
            $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['chunk'], $name), "index.php?a=78&id={$id}");
        }

        //do stuff to save the edited doc
        $modx->getDatabase()->update(array(
            'name' => $name,
            'description' => $description,
            'snippet' => $snippet,
            'locked' => $locked,
            'category' => $categoryid,
            'editor_type' => $editor_type,
            'editor_name' => $editor_name,
            'disabled' => $disabled,
            'editedon' => $currentdate
        ), $modx->getDatabase()->getFullTableName('site_htmlsnippets'), "id='{$id}'");

        // invoke OnChunkFormSave event
        $modx->invokeEvent("OnChunkFormSave", array(
            "mode" => "upd",
            "id" => $id
        ));

        // Set the item name for logger
        $_SESSION['itemname'] = $name;

        // empty cache
        $modx->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "78&id=$id" : "77";
            $header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
            header($header);
        } else {
            $modx->unlockElement(3, $id);
            $header = "Location: index.php?a=76&r=2";
            header($header);
        }
        break;
    default:
        $modx->webAlertAndQuit("No operation set in request.");
}
