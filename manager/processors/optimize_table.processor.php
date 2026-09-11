<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!(EvolutionCMS()->hasPermission('settings') && (EvolutionCMS()->hasPermission('logs')||EvolutionCMS()->hasPermission('bk_manager')))) {
	EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

$db = EvolutionCMS()->getDatabase();

if (isset($_REQUEST['t'])) {

	// The name goes into raw SQL, so it has to be a bare prefixed identifier.
	if (!$db->isValidTableName($_REQUEST['t'])) {
		EvolutionCMS()->webAlertAndQuit($_lang["error_no_optimise_tablename"]);
	}

	// Set the item name for logger
	$_SESSION['itemname'] = $_REQUEST['t'];

	if ($db->getConfig('driver') != 'pgsql') {
		$db->optimize($_REQUEST['t']);
	}

} elseif (isset($_REQUEST['u'])) {

	if (!$db->isValidTableName($_REQUEST['u'])) {
		EvolutionCMS()->webAlertAndQuit($_lang["error_no_truncate_tablename"]);
	}

	// Set the item name for logger
	$_SESSION['itemname'] = $_REQUEST['u'];
	// Raw so the builder does not prefix an already prefixed name; safe now that it is a validated identifier.
	\DB::table(\DB::raw($_REQUEST['u']))->truncate();

} else {
	EvolutionCMS()->webAlertAndQuit($_lang["error_no_optimise_tablename"]);
}

$mode = (int)get_by_key($_REQUEST, 'mode', 93, 'is_scalar');
$header="Location: index.php?a={$mode}&s=4";
header($header);
