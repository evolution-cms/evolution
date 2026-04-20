<?php
// step 3
const DB_CONNECTION_CONFIG_FILE = EVO_CORE_PATH . 'config/database/connections/default.php';
$installMode = isset($_POST['installmode']) ? (int)$_POST['installmode'] : (is_readable(DB_CONNECTION_CONFIG_FILE) ? 1 : 0);

// Early validation for new or advanced install modes
if ($installMode === 0 || $installMode === 2) {
    $database_type = validateDbType($_POST['database_type']);
    $database_name = validateDbName($_POST['database_name']);
    $databasehost = validateDbHost($_POST['databasehost'], $database_type);
    $databaseloginname = isset($_POST['databaseloginname']) ? validateDbUser($_POST['databaseloginname'], $database_type) : $_SESSION['databaseloginname'];
    $databaseloginpassword = isset($_POST['databaseloginpassword']) ? validateDbPassword($_POST['databaseloginpassword'], $database_type) : $_SESSION['databaseloginpassword'];
    $tableprefix = validateTablePrefix($_POST['tableprefix']);
    $cmsadmin = validateAdminUsername($_POST['cmsadmin']);
    $cmsadminemail = validateAdminEmail($_POST['cmsadminemail']);
    $cmspassword = validateAdminPassword($_POST['cmspassword']);
    $cmspasswordconfirm = validateAdminPassword($_POST['cmspasswordconfirm']);
}

switch($installMode){
    case 0: case 2:
    $database_collation = validateDbCollation($_POST['database_collation']);
    $database_type = validateDbType($_POST['database_type']);
    $_POST['database_connection_charset'] = getDatabaseCharset($database_collation, $database_type);
    $_SESSION['databasetype'] = $database_type;
    if (isset($_POST['databaseloginpassword'])) {
        $_SESSION['databaseloginpassword'] = validateDbPassword($_POST['databaseloginpassword'], $database_type);
    }
    if (isset($_POST['databaseloginname'])) {
        $_SESSION['databaseloginname'] = validateDbUser($_POST['databaseloginname'], $database_type);
    }
    break;
    case 1:
        $db_config = include_once EVO_CORE_PATH . 'config/database/connections/default.php';
        $database_collation = $db_config['collation'];
        $database_connection_charset = $db_config['charset'];
        $database_name = $db_config['driver'] === 'sqlite' ? sqliteDbPathToName($db_config['database']) : $db_config['database'];
        if ($db_config['driver'] === 'mysql') {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s%s;charset=%s',
                    $db_config['host'],
                    $db_config['database'],
                    isset($db_config['port']) ? ';port=' . $db_config['port'] : '',
                    $database_connection_charset ?? 'utf8mb4'
                );
                $conn = new PDO(
                    $dsn,
                    $db_config['username'],
                    $db_config['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
                // Try to get database collation
                $database_collation = null;
                try {
                    $stmt = $conn->query("SHOW SESSION VARIABLES LIKE 'collation_database'");
                    $result = $stmt->fetch();
                    if (!$result) {
                        $stmt = $conn->query("SHOW SESSION VARIABLES LIKE 'collation_server'");
                        $result = $stmt->fetch();
                    }
                    if ($result) {
                        $database_collation = trim($result['Value']);
                    }
                } catch (PDOException $e) {
                    // Silently fail if collation query doesn't work
                }
                if (empty($database_collation)) {
                    $database_collation = 'utf8mb4_general_ci';
                }
                $database_charset = substr($database_collation, 0, strpos($database_collation, '_'));
                if (!isset($database_connection_charset) || empty($database_connection_charset)) {
                    $database_connection_charset = $database_charset;
                }
            } catch (PDOException $e) {
                // Connection failed - handle silently like the original @ operator
                $conn = null;
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
        } elseif ($db_config['driver'] === 'sqlite') {
            try {
                $conn = new PDO('sqlite:' . $db_config['database']);
                $database_collation = 'utf8';
                $database_charset = 'utf8';
                $database_connection_charset = 'utf8';
            } catch (PDOException $e) {
                //
            }
        }
        $_POST['tableprefix'] = $db_config['prefix'];
        $_POST['database_connection_charset'] = $database_connection_charset;
        $_POST['databasehost'] = $db_config['host'];
        $_POST['database_type'] = $db_config['driver'];
        $_POST['database_collation'] = $database_collation;
        $_SESSION['databaseloginname'] = $db_config['username'];
        $_SESSION['databaseloginpassword'] = $db_config['password'];
        break;
    default:
        throw new Exception('installmode is undefined');
}
$ph['install_language'] = escapeHtmlAttribute($install_language);
$ph['manager_language'] = escapeHtmlAttribute($manager_language);
$ph['installMode'] = escapeHtmlAttribute($installMode);
$ph['database_name'] = escapeHtmlAttribute($database_name);
$ph['tableprefix'] = escapeHtmlAttribute($_POST['tableprefix']);
$ph['database_type'] = escapeHtmlAttribute($_POST['database_type']);
$ph['database_collation'] = escapeHtmlAttribute($_POST['database_collation']);
$ph['database_connection_charset'] = escapeHtmlAttribute($_POST['database_connection_charset']);
$ph['databasehost'] = escapeHtmlAttribute($_POST['databasehost']);
$ph['cmsadmin'] = escapeHtmlAttribute($_POST['cmsadmin'] ?? '');
$ph['cmsadminemail'] = escapeHtmlAttribute($_POST['cmsadminemail'] ?? '');
$ph['cmspassword'] = escapeHtmlAttribute($_POST['cmspassword'] ?? '');
$ph['cmspasswordconfirm'] = escapeHtmlAttribute($_POST['cmspasswordconfirm'] ?? '');
$ph['checked'] = isset($_POST['installdata']) && $_POST['installdata'] == '1' ? 'checked' : '';
# load setup information file
include_once dirname(__DIR__) . '/processor/result.php';
$ph['templates'] = getTemplates($moduleTemplates);
$ph['tvs'] = getTVs($moduleTVs);
$ph['chunks'] = getChunks($moduleChunks);
$ph['modules'] = getModules($moduleModules);
$ph['plugins'] = getPlugins($modulePlugins);
$ph['snippets'] = getSnippets($moduleSnippets);
$ph['action'] = ($installMode == 1) ? 'mode' : 'connection';
$tpl = file_get_contents(dirname(__DIR__) . '/template/actions/options.tpl');
$content = parse($tpl, $ph);
echo parse($content, $_lang, '[%', '%]');
