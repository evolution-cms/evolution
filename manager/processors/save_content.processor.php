<?php
if(!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
$modx = EvolutionCMS();
if (!evo()->hasPermission('save_document')) {
    evo()->webAlertAndQuit(__("global.error_no_privileges"));
}

$id = is_numeric($_POST['id'] ?? null) ? (int)$_POST['id'] : 0;
$type = $_POST['type'] ?? 'document';
$parentId = (int)get_by_key($_POST, 'parent', 0, 'is_scalar');
$syncsite = (int)($_POST['syncsite'] ?? 0);
$stay = (string)($_POST['stay'] ?? '');

/************* webber ********/
$sd=isset($_POST['dir']) && strtolower($_POST['dir']) === 'asc' ? '&dir=ASC' : '&dir=DESC';
$sb=isset($_POST['sort'])?'&sort='.entities($_POST['sort'], $modx->getConfig('modx_charset')):'&sort=pub_date';
$pg=isset($_POST['page'])?'&page='.(int)$_POST['page']:'';
$add_path=$sd.$sb.$pg;

$actionToTake = ($id > 0 || ($_POST['mode'] ?? '') == '73' || ($_POST['mode'] ?? '') == '27') ? "edit" : "new";
$newResourceAction = ($type == "reference") ? "72" : "4";
$editResourceAction = "27";
$newResourceRedirect = "index.php?a={$newResourceAction}";
$editResourceRedirect = "index.php?a={$editResourceAction}&id={$id}";

try {
    $saved = \EvolutionCMS\Services\DocumentSaveService::forManager()->save($_POST);
} catch (\EvolutionCMS\Services\DocumentSave\DocumentSaveDenied $denied) {
    if (!$denied->restoreForm) {
        $modx->webAlertAndQuit($denied->getMessage());
    }
    if ($actionToTake == 'edit') {
        $modx->getManagerApi()->saveFormValues($editResourceAction);
        $modx->webAlertAndQuit($denied->getMessage(), $editResourceRedirect);
    }
    $modx->getManagerApi()->saveFormValues($newResourceAction);
    $modx->webAlertAndQuit($denied->getMessage(), $newResourceRedirect);
}

$id = $saved->id;

// Set the item name for logger
$_SESSION['itemname'] = $saved->pagetitle;

if ($syncsite == 1) {
    // empty cache
    $modx->clearCache('document');
}

if (!$saved->isNew() && ($_POST['refresh_preview'] ?? '') == '1') {
    $redirectUrl = EVO_SITE_URL . "index.php?id=$id&z=manprev";
} else {
    if (!$saved->isNew() && $stay != '2') {
        $modx->unlockElement(7, $id);
    }
    if ($stay != '') {
        if ($type == "reference") {
            // weblink
            $a = ($stay == '2') ? "27&id=$id" : "72&pid=$parentId";
        } else {
            // document
            $a = ($stay == '2') ? "27&id=$id" : "4&pid=$parentId";
        }
        $redirectUrl = "index.php?a=" . $a . "&r=1&stay=" . (int)$stay;
    } else {
        $redirectUrl = "index.php?a=3&id=$id&r=1";
    }
    if (!$saved->isNew()) {
        $redirectUrl .= $add_path;
    }
}
evo()->sendRedirect($redirectUrl, 0, headers_sent() ? 'REDIRECT_SCRIPT' : '');
