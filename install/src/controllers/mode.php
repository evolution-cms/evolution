<?php
// step 2 part 1
// Determine upgradeability
$isConnectable = false;
$installMode = isset($_POST['installmode']) ? (int)$_POST['installmode'] : 0;
$databaseConfigFile = EVO_CORE_PATH . 'config/database/connections/default.php';
$databaseConfigUnreadable = is_file($databaseConfigFile) && !is_readable($databaseConfigFile);

if (!is_file($databaseConfigFile)) {
    $isNew = true;
} elseif ($databaseConfigUnreadable) {
    $isNew = false;
} else {
    $isNew = false;
    $db_config = include_once $databaseConfigFile;
    if (isset($db_config['database'])) {
        try {
            $pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            if ($db_config['driver'] === 'sqlite') {
                $dbh = new PDO('sqlite:' . $db_config['database']);
            } else {
                $dbh = new PDO($db_config['driver'] . ':host=' . $db_config['host'] . ';dbname='
                    . $db_config['database'], $db_config['username'], $db_config['password'], $pdoOptions);
            }
            $isConnectable = true;
        } catch (PDOException $e) {
            $isConnectable = false;
        }
    } else {
        $isConnectable = false;
    }
}

$ph['moduleName'] = $moduleName;
$ph['displayNew'] = !$isNew ? 'hidden' : '';
$ph['displayUpg'] = $isNew ? 'hidden' : '';
$ph['displayAdvUpg'] = $ph['displayUpg'];
$ph['checkedNew'] = $isNew ? 'checked' : '';
$ph['checkedUpg'] = (!$databaseConfigUnreadable && ((!$isNew && $isConnectable) || ($installMode === 1))) ? 'checked' : '';
$ph['checkedAdvUpg'] = (!$databaseConfigUnreadable && ((!$isNew && !$isConnectable) || ($installMode === 2))) ? 'checked' : '';
$ph['install_language'] = $install_language;
$ph['disabledUpg'] = !$isConnectable ? 'disabled' : '';
$ph['disabledAdvUpg'] = $databaseConfigUnreadable ? 'disabled' : '';
$ph['configPermissionError'] = $databaseConfigUnreadable ? $_lang['cant_write_config_file_retry'] : '';
$ph['configPermissionErrorHidden'] = $databaseConfigUnreadable ? '' : 'hidden';
$ph['disabledNext'] = $databaseConfigUnreadable ? 'disabled' : '';
$ph['csrf_nonce'] = csrfNonce();

$tpl = file_get_contents(dirname(__DIR__) . '/template/actions/mode.tpl');
$content = parse($tpl, $ph);
echo parse($content, $_lang, '[%', '%]');
