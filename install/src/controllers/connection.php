<?php
// step 2 part 2
$installMode = isset($_POST['installmode']) ? (int)$_POST['installmode'] : 0;
$dbTypes = ['mysql' => 'MySQL', 'pgsql' => 'PostgreSQL', 'sqlite' => 'SQLite'];

// Early validation for new installs
if ($installMode === 0) {
    $database_type = isset($_POST['database_type']) ? validateDbType($_POST['database_type']) :
        (isset($_SESSION['databasetype']) ? $_SESSION['databasetype'] : array_key_first($dbTypes));
    $database_name = isset($_POST['database_name']) ? validateDbName($_POST['database_name']) : '';
    $database_host = isset($_POST['databasehost']) ? validateDbHost($_POST['databasehost'], $database_type) : 'localhost';
    $databaseloginname = isset($_SESSION['databaseloginname']) ? $_SESSION['databaseloginname'] : '';
    $databaseloginpassword = isset($_SESSION['databaseloginpassword']) ? $_SESSION['databaseloginpassword'] : '';
    $table_prefix = isset($_POST['tableprefix']) ? validateTablePrefix($_POST['tableprefix']) :
        base_convert(mt_rand(10, 20), 10, 36) . substr(str_shuffle(
            '0123456789abcdefghijklmnopqrstuvwxyz'), mt_rand(0, 33), 3) . '_';
}

// Determine upgradeability
$upgradeable = 0;
if ($installMode !== 0) {
    $database_name = '';

    if (!is_file(EVO_CORE_PATH . 'config/database/connections/default.php')) {
        $upgradeable = 0;
    } else {
        // Include the file so we can test its validity
        $db_config = include_once EVO_CORE_PATH . 'config/database/connections/default.php';
        $database_type = $db_config['driver'];
        $database_host = $db_config['host'];
        $database_collation = $db_config['collation'];
        $database_connection_charset = $db_config['charset'];
        $table_prefix = $db_config['prefix'];

        // We need to have all connection settings - but prefix may be empty so we have to ignore it
        if (isset($db_config['database'])) {
            $database_name = $database_type === 'sqlite' ? sqliteDbPathToName($db_config['database']) :
                trim($db_config['database'], '` ');
            $conn = false;
            $result = false;
            if ($database_type === 'mysql') {
                try {
                    $port = isset($db_config['port']) ? ';port=' . $db_config['port'] : '';
                    $dsn = "mysql:host={$db_config['host']}{$port}";
                    $conn = new PDO($dsn, $db_config['username'], $db_config['password']);
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $conn->exec("USE `$database_name`");
                    $result = true;
                } catch (PDOException $e) {
                    $conn = false;
                    $result = false;
                }
            } elseif ($database_type === 'pgsql') {
                if (isset($db_config['unix_socket']) && !empty($db_config['unix_socket'])) {
                    $dsn = "pgsql:unix_socket={$db_config['unix_socket']};dbname={$database_name}";
                } else {
                    $dsn = "pgsql:host={$db_config['host']};port={$db_config['port']};dbname={$database_name}";
                }
                try {
                    $conn = new PDO($dsn, $db_config['username'], $db_config['password']);
                    $result = true;
                } catch (PDOException $e) {
                    $conn = false;
                    $result = false;
                }
            } elseif ($database_type === 'sqlite') {
                try {
                    $conn = new PDO('sqlite:' . sqliteDbNameToPath($database_name));
                    $result = true;
                } catch (PDOException $e) {
                    $conn = false;
                    $result = false;
                }
            }
            if (!$conn || !$result) {
                $upgradeable = ($installMode === 0) ? 0 : 2;
            } else {
                $upgradeable = 1;
            }
        } else {
            $upgradeable = 2;
        }
    }
}

// check the database collation if not specified in the configuration
if ($upgradeable && (! isset($database_connection_charset) || empty($database_connection_charset))) {
    if ($database_type === 'mysql') {

        try {
            $collation = $conn->query("SHOW SESSION VARIABLES LIKE 'collation_database'")->fetch(PDO::FETCH_NUM);
            if (!$collation) {
                $collation = $conn->query("SHOW SESSION VARIABLES LIKE 'collation_server'")->fetch(PDO::FETCH_NUM);
            }
            if ($collation) {
                $database_collation = $collation[1];
            }
        } catch (PDOException $e) {
            // Use default if query fails
        }

        if (empty($database_collation)) {
            $database_collation = 'utf8mb4_general_ci';
        }
        $database_charset = substr($database_collation, 0, strpos($database_collation, '_'));
        $database_connection_charset = $database_charset;
    } elseif ($database_type === 'pgsql') {
        try {
            $sql = "SELECT datcollate FROM pg_database WHERE datname = current_database()";
            $stmt = $conn->query($sql);
            if ($stmt && $row = $stmt->fetch(PDO::FETCH_NUM)) {
                $database_collation = trim($row[0]);
            }
        } catch (PDOException $e) {
            // Ignore errors and use config value or fallback
        }
        if (empty($database_collation)) {
            $database_collation = 'en_US.utf8';
        }
        $pos = strpos($database_collation, '.');
        $database_charset = ($pos !== false) ? substr($database_collation, $pos + 1) : 'utf8';
        $database_connection_charset = $database_charset;
    } elseif ($database_type === 'sqlite') {
        $database_collation = 'utf8';
        $database_charset = 'utf8';
        $database_connection_charset = 'utf8';
    }
} else {
    if ($database_type === 'mysql') {
        $database_collation = 'utf8mb4_general_ci';
    } elseif ($database_type === 'pgsql') {
        $database_collation = 'en_US.utf8';
    } elseif ($database_type === 'sqlite') {
        $database_collation = 'utf8';
    }
}

$ph['databaseTypeOptions'] = '';
foreach ($dbTypes as $dbType => $dbTypeName) {
    $selectedOptionDbType = $dbType === $database_type ? ' selected="selected"' : '';
    $ph['databaseTypeOptions'] .= "<option value=\"{$dbType}\" $selectedOptionDbType>{$dbTypeName}</option>\n";
}
$ph['database_type'] = escapeHtmlAttribute($dbTypes[$database_type]);
$ph['database_name'] = escapeHtmlAttribute(isset($_POST['database_name']) ? $_POST['database_name'] : $database_name);
$ph['tableprefix'] = escapeHtmlAttribute(isset($_POST['tableprefix']) ? $_POST['tableprefix'] : $table_prefix);
$ph['database_collation'] = escapeHtmlAttribute(isset($_POST['database_collation']) ? $_POST['database_collation'] : $database_collation);
$ph['show#AUH'] = ($installMode === 0) ? '' : 'hidden';
$ph['cmsadmin'] = escapeHtmlAttribute(isset($_POST['cmsadmin']) ? $_POST['cmsadmin'] : 'admin');
$ph['cmsadminemail'] = escapeHtmlAttribute(isset($_POST['cmsadminemail']) ? $_POST['cmsadminemail'] : '');
$ph['cmspassword'] = escapeHtmlAttribute(isset($_POST['cmspassword']) ? $_POST['cmspassword'] : '');
$ph['cmspasswordconfirm'] = escapeHtmlAttribute(isset($_POST['cmspasswordconfirm']) ? $_POST['cmspasswordconfirm'] : '');
$ph['managerLangs'] = getLangs($install_language);
$ph['install_language'] = escapeHtmlAttribute($install_language);
$ph['installMode'] = escapeHtmlAttribute($installMode);
$ph['checkedChkagree'] = isset($_POST['chkagree']) ? 'checked' : '';
$ph['databasehost'] = escapeHtmlAttribute(isset($_POST['databasehost']) ? $_POST['databasehost'] : $database_host);
$ph['databaseloginname'] = escapeHtmlAttribute(isset($_SESSION['databaseloginname']) ? $_SESSION['databaseloginname'] : '');
$ph['databaseloginpassword'] = escapeHtmlAttribute(isset($_SESSION['databaseloginpassword']) ? $_SESSION['databaseloginpassword'] : '');
$ph['MGR_DIR'] = escapeHtmlAttribute(MGR_DIR);

$content = file_get_contents(dirname(__DIR__) . '/template/actions/connection.tpl');
$content = parse($content, $_lang, '[%', '%]');
$content = parse($content, $ph);

echo $content;