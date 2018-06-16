<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('settings')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$modx->getDatabase()->truncate($modx->getDatabase()->getFullTableName('manager_log'));

$header="Location: index.php?a=13";
header($header);
