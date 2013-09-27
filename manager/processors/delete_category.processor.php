<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
$hasPermission = 0;
if($modx->hasPermission('save_plugin') ||
   $modx->hasPermission('save_snippet') ||
   $modx->hasPermission('save_template') ||
   $modx->hasPermission('save_module')) {
    $hasPermission = 1;
}

if ($hasPermission) {
    $catId = intval($_GET['catId']);
    include_once "categories.inc.php";
    deleteCategory($catId);
}
$header="Location: index.php?a=76";
header($header);
?>