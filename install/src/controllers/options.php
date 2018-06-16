<?php
$installMode = isset($_POST['installmode']) ? (int)$_POST['installmode'] : 0;

switch($installMode){
    case 0:
    case 2:
        $database_collation = isset($_POST['database_collation']) ? $_POST['database_collation'] : 'utf8_general_ci';
        $database_charset = substr($database_collation, 0, strpos($database_collation, '_'));
        $_POST['database_connection_charset'] = $database_charset;
        if(empty($_SESSION['databaseloginpassword']))
            $_SESSION['databaseloginpassword'] = $_POST['databaseloginpassword'];
        if(empty($_SESSION['databaseloginname']))
            $_SESSION['databaseloginname'] = $_POST['databaseloginname'];
        break;
    case 1:
        include $base_path . MGR_DIR . '/includes/config.inc.php';
        if (@ $conn = mysqli_connect($database_server, $database_user, $database_password)) {
            if (@ mysqli_query($conn, "USE {$dbase}")) {
                if (!$rs = mysqli_query($conn, "show session variables like 'collation_database'")) {
                    $rs = mysqli_query($conn, "show session variables like 'collation_server'");
                }
                if ($rs && $collation = mysqli_fetch_row($rs)) {
                    $database_collation = trim($collation[1]);
                }
            }
        }
        if (empty ($database_collation)) $database_collation = 'utf8_general_ci';

        $database_charset = substr($database_collation, 0, strpos($database_collation, '_'));
        if (!isset ($database_connection_charset) || empty ($database_connection_charset)) {
            $database_connection_charset = $database_charset;
        }

        if (!isset ($database_connection_method) || empty ($database_connection_method)) {
            $database_connection_method = 'SET CHARACTER SET';
            if (function_exists('mysqli_set_charset')) mysqli_set_charset($conn, $database_connection_charset);
        }
        if ($database_connection_method != 'SET NAMES' && $database_connection_charset != $database_charset) {
            $database_connection_method = 'SET NAMES';
        }

        $_POST['database_name'] = $dbase;
        $_POST['tableprefix'] = $table_prefix;
        $_POST['database_connection_charset'] = $database_connection_charset;
        $_POST['database_connection_method'] = $database_connection_method;
        $_POST['databasehost'] = $database_server;
        $_SESSION['databaseloginname'] = $database_user;
        $_SESSION['databaseloginpassword'] = $database_password;
        break;
    default:
        throw new Exception('installmode is undefined');
}

$ph['install_language'] = $install_language;
$ph['manager_language'] = $manager_language;
$ph['installMode'] = $installMode;
$ph['database_name'] = trim($_POST['database_name'], '`');
$ph['tableprefix'] = $_POST['tableprefix'];
$ph['database_collation'] = $_POST['database_collation'];
$ph['database_connection_charset'] = $_POST['database_connection_charset'];
$ph['database_connection_method'] = $_POST['database_connection_method'];
$ph['databasehost'] = $_POST['databasehost'];
$ph['cmsadmin'] = trim($_POST['cmsadmin']);
$ph['cmsadminemail'] = trim($_POST['cmsadminemail']);
$ph['cmspassword'] = trim($_POST['cmspassword']);
$ph['cmspasswordconfirm'] = trim($_POST['cmspasswordconfirm']);

$ph['checked'] = isset ($_POST['installdata']) && $_POST['installdata'] == "1" ? 'checked' : '';

# load setup information file
include_once dirname(__DIR__) . '/processor/result.php';
$ph['templates'] = getTemplates($moduleTemplates);
$ph['tvs']       = getTVs($moduleTVs);
$ph['chunks']    = getChunks($moduleChunks);
$ph['modules']   = getModules($moduleModules);
$ph['plugins']   = getPlugins($modulePlugins);
$ph['snippets']  = getSnippets($moduleSnippets);

$ph['action'] = ($installMode == 1) ? 'mode' : 'connection';

$tpl = file_get_contents(dirname(__DIR__) . '/template/actions/options.tpl');
$content = parse($tpl, $ph);
echo parse($content, $_lang, '[%', '%]');
