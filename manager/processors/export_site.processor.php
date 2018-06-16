<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('export_static')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$maxtime = (is_numeric($_POST['maxtime'])) ? $_POST['maxtime'] : 30;
@set_time_limit($maxtime);


if(is_dir(MODX_BASE_PATH . 'temp'))       $export_dir = MODX_BASE_PATH . 'temp/export';
elseif(is_dir(MODX_BASE_PATH . 'assets')) $export_dir = MODX_BASE_PATH . 'assets/export';
$modx->getExportSite()->targetDir = $export_dir;

if(strpos($modx->config['base_path'],"{$export_dir}/")===0 && 0 <= strlen(str_replace("{$export_dir}/",'',$modx->config['base_path'])))
	return $_lang['export_site.static.php6'];
elseif($modx->config['rb_base_dir'] === $export_dir . '/')
	return $modx->parsePlaceholder($_lang['export_site.static.php7'],'rb_base_url=' . $modx->config['base_url'] . $modx->config['rb_base_url']);
elseif(!is_writable($export_dir))
	return $_lang['export_site_target_unwritable'];

$modx->getExportSite()->generate_mode = $_POST['generate_mode'];

$modx->getExportSite()->setExportDir($export_dir);
$modx->getExportSite()->removeDirectoryAll($export_dir);

$ignore_ids      = $_POST['ignore_ids'];
$repl_before     = $_POST['repl_before'];
$repl_after      = $_POST['repl_after'];
$includenoncache = $_POST['includenoncache'];

if($ignore_ids!==$_POST['ignore_ids']
 ||$includenoncache!==$_POST['includenoncache']
 ||$repl_before!==$_POST['repl_before']
 ||$repl_after !==$_POST['repl_after']) {
	$modx->clearCache('full');
}

$total = $modx->getExportSite()->getTotal($_POST['ignore_ids'], $modx->config['export_includenoncache']);

$output = sprintf($_lang['export_site_numberdocs'], $total);
$modx->getExportSite()->total = $total;

$modx->getExportSite()->repl_before = $_POST['repl_before'];
$modx->getExportSite()->repl_after  = $_POST['repl_after'];

$output .= $modx->getExportSite()->run();

$exportend = $modx->getExportSite()->get_mtime();
$totaltime = ($exportend - $modx->getExportSite()->exportstart);
$output .= sprintf ('<p>'.$_lang["export_site_time"].'</p>', round($totaltime, 3));
return $output;
