<?php
if(!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
$modx = EvolutionCMS();

// the editor saves in place over XHR and expects JSON instead of the redirect
$ajax = is_ajax();
$respondJson = static function (int $status, array $payload): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
};

if (!evo()->hasPermission('save_document')) {
    if ($ajax) {
        $respondJson(403, ['success' => false, 'message' => __("global.error_no_privileges")]);
    }
    evo()->webAlertAndQuit(__("global.error_no_privileges"));
}

$id = is_numeric($_POST['id'] ?? null) ? (int)$_POST['id'] : 0;
$type = $_POST['type'] ?? 'document';
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

// a plugin may echo from OnDocFormSave; that must not end up inside the JSON
if ($ajax) {
    ob_start();
}
try {
    $saved = \EvolutionCMS\Services\DocumentSaveService::forManager()->save($_POST);
} catch (\EvolutionCMS\Services\DocumentSave\DocumentSaveDenied $denied) {
    if ($ajax) {
        ob_end_clean();
        $respondJson(422, ['success' => false, 'message' => $denied->getMessage()]);
    }
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

$refreshPreview = ($_POST['refresh_preview'] ?? '') == '1';
if (!$saved->isNew() && !$refreshPreview && $stay != '2') {
    $modx->unlockElement(7, $id);
}

$redirectUrl = \EvolutionCMS\Support\DocumentSave\SaveResponse::redirectUrl($saved, $stay, $refreshPreview, $add_path, EVO_SITE_URL);

if ($ajax) {
    ob_end_clean();
    $respondJson(200, \EvolutionCMS\Support\DocumentSave\SaveResponse::payload($saved, $redirectUrl, csrf_token()));
}
evo()->sendRedirect($redirectUrl, 0, headers_sent() ? 'REDIRECT_SCRIPT' : '');
