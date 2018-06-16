<?php
// Determine upgradeability
$upgradeable = 0;
if (is_file($base_path . MGR_DIR . '/includes/config.inc.php')) { // Include the file so we can test its validity
    include_once $base_path . MGR_DIR . '/includes/config.inc.php';
    // We need to have all connection settings - tho prefix may be empty so we have to ignore it
    if (isset($dbase)) {
        if (!$conn = @mysqli_connect($database_server, $database_user, $database_password))
            $upgradeable = isset($_POST['installmode']) && $_POST['installmode'] == 'new' ? 0 : 2;
        elseif (!@mysqli_select_db($conn, trim($dbase, '`')))
            $upgradeable = isset($_POST['installmode']) && $_POST['installmode'] == 'new' ? 0 : 2;
        else
            $upgradeable = 1;
    }
    else
        $upgradeable = 2;
}

$ph['moduleName']       = $moduleName;
$ph['displayNew']       = ($upgradeable!=0) ? 'display:none;' : '';
$ph['displayUpg']       = ($upgradeable==0) ? 'display:none;' : '';
$ph['displayAdvUpg']    = $ph['displayUpg'];
$ph['checkedNew']       = !$upgradeable     ? 'checked' : '';
$ph['checkedUpg']       = ($_POST['installmode']==1 || $upgradeable==1) ? 'checked' : '';
$ph['checkedAdvUpg']    = ($_POST['installmode']==2 || $upgradeable==2) ? 'checked' : '';
$ph['install_language'] = $install_language;
$ph['disabledUpg']      = ($upgradeable!=1) ? 'disabled' : '';
$ph['disabledAdvUpg']   = ($upgradeable==0) ? 'disabled' : '';

$tpl = file_get_contents(dirname(__DIR__) . '/template/actions/mode.tpl');
$content = parse($tpl, $ph);
echo parse($content, $_lang,'[%','%]');
