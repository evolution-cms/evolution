<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('save_template')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = (int)$_POST['id'];
$name = $modx->getDatabase()->escape(trim($_POST['name']));
$description = $modx->getDatabase()->escape($_POST['description']);
$caption = $modx->getDatabase()->escape($_POST['caption']);
$type = $modx->getDatabase()->escape($_POST['type']);
$elements = $modx->getDatabase()->escape($_POST['elements']);
$default_text = $modx->getDatabase()->escape($_POST['default_text']);
$rank = isset ($_POST['rank']) ? $modx->getDatabase()->escape($_POST['rank']) : 0;
$display = $modx->getDatabase()->escape($_POST['display']);
$params = $modx->getDatabase()->escape($_POST['params']);
$locked = $_POST['locked'] == 'on' ? 1 : 0;
$origin = isset($_REQUEST['or']) ? (int)$_REQUEST['or'] : 76;
$originId = isset($_REQUEST['oid']) ? (int)$_REQUEST['oid'] : null;
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

$name = $name != '' ? $name : "Untitled variable";
$caption = $caption != '' ? $caption : $name;

// get table names
$tbl_site_tmplvars = $modx->getDatabase()->getFullTableName('site_tmplvars');

switch ($_POST['mode']) {
    case '300':

        // invoke OnBeforeTVFormSave event
        $modx->invokeEvent("OnBeforeTVFormSave", array(
            "mode" => "new",
            "id" => $id
        ));

        // disallow duplicate names for new tvs
        $rs = $modx->getDatabase()->select('COUNT(*)', $tbl_site_tmplvars, "name='{$name}'");
        $count = $modx->getDatabase()->getValue($rs);
        if ($count > 0) {
            $modx->getManagerApi()->saveFormValues(300);
            $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['tv'], $name), "index.php?a=300");
        }
        // disallow reserved names
        if (in_array($name, array('id', 'type', 'contentType', 'pagetitle', 'longtitle', 'description', 'alias', 'link_attributes', 'published', 'pub_date', 'unpub_date', 'parent', 'isfolder', 'introtext', 'content', 'richtext', 'template', 'menuindex', 'searchable', 'cacheable', 'createdby', 'createdon', 'editedby', 'editedon', 'deleted', 'deletedon', 'deletedby', 'publishedon', 'publishedby', 'menutitle', 'donthit', 'privateweb', 'privatemgr', 'content_dispo', 'hidemenu', 'alias_visible'))) {
            $_POST['name'] = '';
            $modx->getManagerApi()->saveFormValues(300);
            $modx->webAlertAndQuit(sprintf($_lang['reserved_name_warning'], $_lang['tv'], $name), "index.php?a=300");
        }

        // Add new TV
        $newid = $modx->getDatabase()->insert(array(
            'name' => $name,
            'description' => $description,
            'caption' => $caption,
            'type' => $type,
            'elements' => $elements,
            'default_text' => $default_text,
            'display' => $display,
            'display_params' => $params,
            'rank' => $rank,
            'locked' => $locked,
            'category' => $categoryid,
            'createdon' => $currentdate,
            'editedon' => $currentdate
        ), $tbl_site_tmplvars);

        // save access permissions
        saveTemplateVarAccess();
        saveDocumentAccessPermissons();

        // invoke OnTVFormSave event
        $modx->invokeEvent("OnTVFormSave", array(
            "mode" => "new",
            "id" => $newid
        ));

        // Set the item name for logger
        $_SESSION['itemname'] = $caption;

        // empty cache
        $modx->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "301&id=$newid" : "300";
            $header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
            header($header);
        } else {
            $header = "Location: index.php?a=76&r=2";
            header($header);
        }
        break;
    case '301':
        // invoke OnBeforeTVFormSave event
        $modx->invokeEvent("OnBeforeTVFormSave", array(
            "mode" => "upd",
            "id" => $id
        ));

        // disallow duplicate names for tvs
        $rs = $modx->getDatabase()->select('COUNT(*)', $tbl_site_tmplvars, "name='{$name}' AND id!='{$id}'");
        if ($modx->getDatabase()->getValue($rs) > 0) {
            $modx->getManagerApi()->saveFormValues(300);
            $modx->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['tv'], $name), "index.php?a=301&id={$id}");
        }
        // disallow reserved names
        if (in_array($name, array('id', 'type', 'contentType', 'pagetitle', 'longtitle', 'description', 'alias', 'link_attributes', 'published', 'pub_date', 'unpub_date', 'parent', 'isfolder', 'introtext', 'content', 'richtext', 'template', 'menuindex', 'searchable', 'cacheable', 'createdby', 'createdon', 'editedby', 'editedon', 'deleted', 'deletedon', 'deletedby', 'publishedon', 'publishedby', 'menutitle', 'donthit', 'privateweb', 'privatemgr', 'content_dispo', 'hidemenu', 'alias_visible'))) {
            $modx->getManagerApi()->saveFormValues(300);
            $modx->webAlertAndQuit(sprintf($_lang['reserved_name_warning'], $_lang['tv'], $name), "index.php?a=301&id={$id}");
        }

        // update TV
        $modx->getDatabase()->update(array(
            'name' => $name,
            'description' => $description,
            'caption' => $caption,
            'type' => $type,
            'elements' => $elements,
            'default_text' => $default_text,
            'display' => $display,
            'display_params' => $params,
            'rank' => $rank,
            'locked' => $locked,
            'category' => $categoryid,
            'editedon' => $currentdate
        ), $tbl_site_tmplvars, "id='{$id}'");

        // save access permissions
        saveTemplateVarAccess();
        saveDocumentAccessPermissons();

        // invoke OnTVFormSave event
        $modx->invokeEvent("OnTVFormSave", array(
            "mode" => "upd",
            "id" => $id
        ));

        // Set the item name for logger
        $_SESSION['itemname'] = $caption;

        // empty cache
        $modx->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "301&id=$id" : "300";
            $header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'] . "&or=" . $origin . "&oid=" . $originId;
            header($header);
        } else {
            $modx->unlockElement(2, $id);
            $header = "Location: index.php?a=" . $origin . "&r=2" . (empty($originId) ? '' : '&id=' . $originId);
            header($header);
        }

        break;
    default:
        $modx->webAlertAndQuit("No operation set in request.");
}
