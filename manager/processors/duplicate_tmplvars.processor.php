<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('edit_template')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id == 0) {
    $modx->webAlertAndQuit($_lang["error_no_id"]);
}

// count duplicates
$tmplvar = EvolutionCMS\Models\SiteTmplvar::with(['tmplvarAccess', 'tmplvarTemplate'])->findOrFail($id);
$name = $tmplvar->name;
$count = EvolutionCMS\Models\SiteTmplvar::where('name', 'like', $name . ' ' . $_lang['duplicated_el_suffix'] . '%')->count();
if ($count >= 1) $count = ' ' . ($count + 1);
else $count = '';


$newTmplvar = $tmplvar->replicate();
$newTmplvar->name = $tmplvar->name . ' ' . $_lang['duplicated_el_suffix'] . $count;
$newTmplvar->caption = $tmplvar->caption . ' Duplicate ' . $count . '';
$newTmplvar->push();

foreach ($tmplvar->tmplvarTemplate as $tmplvarTemplate) {
    $field = $tmplvarTemplate->attributesToArray();
    Illuminate\Support\Arr::except($field, ['tmplvarid']);
    $newTmplvar->tmplvarTemplate()->create($field);
}
foreach ($tmplvar->tmplvarAccess as $tmplvarAccess) {
    $field = $tmplvarAccess->attributesToArray();
    Illuminate\Support\Arr::except($field, ['tmplvarid']);
    $newTmplvar->tmplvarAccess()->create($field);
}

$_SESSION['itemname'] = $newTmplvar->name;

// finish duplicating - redirect to new variable
$header = "Location: index.php?r=2&a=301&id=" . $newTmplvar->getKey();
header($header);
