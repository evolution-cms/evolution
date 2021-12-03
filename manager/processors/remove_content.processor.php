<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('delete_document')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$ids = \EvolutionCMS\Models\SiteContent::query()->withTrashed()->where('deleted', 1)->pluck('id')->toArray();

// invoke OnBeforeEmptyTrash event
$modx->invokeEvent(
    "OnBeforeEmptyTrash",
    array(
        "ids" => $ids
    )
);

// remove the document groups link.
\EvolutionCMS\Models\DocumentGroup::query()->whereIn('document', $ids)->delete();

// remove the TV content values.
\EvolutionCMS\Models\SiteTmplvarContentvalue::query()->whereIn('contentid', $ids)->delete();

//'undelete' the document.
\EvolutionCMS\Models\SiteContent::query()->where('deleted', 1)->forceDelete();

// invoke OnEmptyTrash event
$modx->invokeEvent(
    "OnEmptyTrash",
    array(
        "ids" => $ids
    )
);

// empty cache
$modx->clearCache('full');

// finished emptying cache - redirect
$header = "Location: index.php?a=2&r=1";
header($header);
