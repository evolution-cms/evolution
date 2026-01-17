<?php
// Determine upgradeability
$isConnectable = false;
$installMode = isset($_POST['installmode']) ? (int)$_POST['installmode'] : 0;

if (!is_file(EVO_CORE_PATH . 'config/database/connections/default.php')) {
    $isNew = true;
} else {
    $isNew = false;
    $db_config = include_once EVO_CORE_PATH . 'config/database/connections/default.php';
    if (isset($db_config['database'])) {
        try {
            $pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            $dbh = new PDO($db_config['driver'] . ':host=' . $db_config['host'] . ';dbname='
                . $db_config['database'], $db_config['username'], $db_config['password'], $pdoOptions);
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
$ph['checkedUpg'] = ((!$isNew && $isConnectable) || ($installMode === 1)) ? 'checked' : '';
$ph['checkedAdvUpg'] = ((!$isNew && !$isConnectable) || ($installMode === 2)) ? 'checked' : '';
$ph['install_language'] = $install_language;
$ph['disabledUpg'] = !$isConnectable ? 'disabled' : '';
$ph['disabledAdvUpg'] = '';
$ph['csrf_nonce'] = csrfNonce();

$tpl = file_get_contents(dirname(__DIR__) . '/template/actions/mode.tpl');
$content = parse($tpl, $ph);
echo parse($content, $_lang, '[%', '%]');
