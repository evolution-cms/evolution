<?php
// Determine upgradeability
$upgradeable = 0;
if (is_file(EVO_CORE_PATH . 'config/database/connections/default.php')) { // Include the file so we can test its validity
    $db_config = include_once EVO_CORE_PATH . 'config/database/connections/default.php';
    // We need to have all connection settings - tho prefix may be empty, so we have to ignore it
    if (isset($db_config['database'])) {
        try {
            $dbh = new PDO($db_config['method'] . ':host=' . $db_config['host'] . ';dbname=' . $db_config['database'], $db_config['username'], $db_config['password']);
            $upgradeable = isset($_POST['installmode']) && $_POST['installmode'] == 'new' ? 0 : 2;
        } catch (PDOException $e) {
            $upgradeable = 1;
        }
    } else {
        $upgradeable = 2;
    }
}

$ph['moduleName'] = $moduleName;
$ph['displayNew'] = ($upgradeable !== 0) ? 'hidden' : '';
$ph['displayUpg'] = ($upgradeable === 0) ? 'hidden' : '';
$ph['displayAdvUpg'] = $ph['displayUpg'];
$ph['checkedNew'] = !$upgradeable ? 'checked' : '';
$ph['checkedUpg'] = (isset($_POST['installmode']) && $_POST['installmode'] == 1 || $upgradeable == 1) ? 'checked' : '';
$ph['checkedAdvUpg'] = (isset($_POST['installmode']) && $_POST['installmode'] == 2 || $upgradeable == 2) ? 'checked' : '';
$ph['install_language'] = $install_language;
$ph['disabledUpg'] = ($upgradeable != 1) ? 'disabled' : '';
$ph['disabledAdvUpg'] = ($upgradeable == 0) ? 'disabled' : '';
$ph['csrf_nonce'] = csrfNonce();

$tpl = file_get_contents(dirname(__DIR__) . '/template/actions/mode.tpl');
$content = parse($tpl, $ph);
echo parse($content, $_lang, '[%', '%]');
