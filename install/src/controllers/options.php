<?php
$installMode = isset($_POST['installmode']) ? (int)$_POST['installmode'] : 0;

switch($installMode){
    case 0:
    case 2:
        $database_collation = isset($_POST['database_collation']) ? $_POST['database_collation'] : 'utf8mb4_general_ci';
        $database_charset = substr($database_collation, 0, strpos($database_collation, '_'));
        $_POST['database_connection_charset'] = $database_charset;
        $_SESSION['databaseloginpassword'] = $_POST['databaseloginpassword'];
        $_SESSION['databaseloginname'] = $_POST['databaseloginname'];
        break;
    case 1:
        $db_config = include_once EVO_CORE_PATH . 'config/database/connections/default.php';
        $database_collation = $db_config['collation'];
        $database_connection_charset = $db_config['charset'];

        if ($db_config['driver'] === 'mysql') {
            if (@$conn = mysqli_connect($db_config['host'], $db_config['username'], $db_config['password'], '', isset($db_config['port']) ? $db_config['port'] : null)) {
                if (@mysqli_query($conn, 'USE `' . mysqli_real_escape_string($conn, $db_config['database']) . '`')) {
                    if (!$rs = mysqli_query($conn, "show session variables like 'collation_database'")) {
                        $rs = mysqli_query($conn, "show session variables like 'collation_server'");
                    }
                    if ($rs && $collation = mysqli_fetch_row($rs)) {
                        $database_collation = trim($collation[1]);
                    }
                }
            }
            if (empty($database_collation)) {
                $database_collation = 'utf8mb4_general_ci';
            }
            $database_charset = substr($database_collation, 0, strpos($database_collation, '_'));
            if (!isset($database_connection_charset) || empty($database_connection_charset)) {
                $database_connection_charset = $database_charset;
            }
            if (!isset($database_connection_method) || empty($database_connection_method)) {
                $database_connection_method = 'SET CHARACTER SET';
                if (function_exists('mysqli_set_charset')) {
                    mysqli_set_charset($conn, $database_connection_charset);
                }
            }
            if ($database_connection_method != 'SET NAMES' && $database_connection_charset != $database_charset) {
                $database_connection_method = 'SET NAMES';
            }
        } elseif ($db_config['driver'] === 'pgsql') {
            if (isset($db_config['unix_socket']) && !empty($db_config['unix_socket'])) {
                $dsn = "pgsql:unix_socket={$db_config['unix_socket']}";
            } else {
                $dsn = "pgsql:host={$db_config['host']};port={$db_config['port']}";
            }
            $database_collation_fetched = false;
            try {
                $conn = new PDO($dsn . ";dbname=postgres", $db_config['username'], $db_config['password']);
                $escaped_dbname = $conn->quote($db_config['database']);
                $sql = "SELECT datcollate FROM pg_database WHERE datname = $escaped_dbname";
                $stmt = $conn->query($sql);
                if ($stmt && $row = $stmt->fetch(PDO::FETCH_NUM)) {
                    $database_collation = trim($row[0]);
                    $database_collation_fetched = true;
                }
            } catch (PDOException $e) {
                // Ignore errors and use config value or fallback
            }
            if (!$database_collation_fetched || empty($database_collation)) {
                $database_collation = 'en_US.utf8';
            }
            $pos = strpos($database_collation, '.');
            $database_charset = ($pos !== false) ? substr($database_collation, $pos + 1) : 'utf8';
            if (!isset($database_connection_charset) || empty($database_connection_charset)) {
                $database_connection_charset = $database_charset;
            }
            if (!isset($database_connection_method) || empty($database_connection_method)) {
                $database_connection_method = 'SET client_encoding';
            }
        }

        $_POST['database_name'] = $db_config['database'];
        $_POST['tableprefix'] = $db_config['prefix'];
        $_POST['database_connection_charset'] = $database_connection_charset;
        $_POST['database_connection_method'] = $database_connection_method;
        $_POST['databasehost'] = $db_config['host'];
        $_POST['database_type'] = $db_config['driver'];
        $_POST['database_collation'] = $database_collation;
        $_SESSION['databaseloginname'] = $db_config['username'];
        $_SESSION['databaseloginpassword'] = $db_config['password'];
        break;
    default:
        throw new Exception('installmode is undefined');
}

$ph['install_language'] = $install_language;
$ph['manager_language'] = $manager_language;
$ph['installMode'] = $installMode;
$ph['database_name'] = strip_tags(trim($_POST['database_name'], '`'));
$ph['tableprefix'] = strip_tags($_POST['tableprefix']);
$ph['database_type'] = $_POST['database_type'];
$ph['database_collation'] = $_POST['database_collation'];
$ph['database_connection_charset'] = $_POST['database_connection_charset'];
$ph['database_connection_method'] = $_POST['database_connection_method'];
$ph['databasehost'] = strip_tags($_POST['databasehost']);
$ph['cmsadmin'] = strip_tags(trim($_POST['cmsadmin'] ?? ''));
$ph['cmsadminemail'] = strip_tags(trim($_POST['cmsadminemail'] ?? ''));
$ph['cmspassword'] = strip_tags(trim($_POST['cmspassword'] ?? ''));
$ph['cmspasswordconfirm'] = strip_tags(trim($_POST['cmspasswordconfirm'] ?? ''));
$ph['checked'] = isset ($_POST['installdata']) && $_POST['installdata'] == '1' ? 'checked' : '';

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
