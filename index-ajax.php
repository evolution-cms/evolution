<?php
// Add items to this array corresponding to which directories within assets/snippets/ can be used by this file.
// Do not add entries unneccesarily.
// Any PHP files in these directories can be executed by any user.
$allowed_dirs = array('assets/snippets/ajaxSearch/');

include_once(dirname(__FILE__)."/assets/cache/siteManager.php");

// harden it
require_once('./'.MGR_DIR.'/includes/protect.inc.php');

// initialize the variables prior to grabbing the config file
$database_type = "";
$database_server = "";
$database_user = "";
$database_password = "";
$dbase = "";
$table_prefix = "";
$base_url = "";
$base_path = "";

// get the required includes
if($database_user=='') {
        if (!$rt = @include_once MGR_DIR."/includes/config.inc.php") {
           exit('Could not load MODx configuration file!');
        }
}

if($axhandler = (strtoupper($_SERVER['REQUEST_METHOD'])=='GET') ? $_GET['q'] : $_POST['q']) {
    $axhandler = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '', $axhandler);
    // Get realpath
    $axhandler = realpath(MODX_BASE_PATH.$axhandler) or die(); // full
    $axhandler = str_replace('\\','/',$axhandler);
    $axhandler_rel = substr($axhandler, strlen(MODX_BASE_PATH)); // relative
    //$axhandler = realpath($directory.str_replace($directory, '', $axhandler));

    if ($axhandler_rel && strtolower(substr($axhandler_rel, -4)) == '.php') {
    // permission check
		$allowed = false;
		foreach($allowed_dirs as $allowed_dir) {
            if (substr($axhandler_rel, 0, strlen($allowed_dir)) == $allowed_dir) {
                $allowed = true;
                break;
            }
        }

		if ($allowed) {
            include_once($axhandler);
        }
	}
}
?>