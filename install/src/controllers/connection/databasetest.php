<?php
$driver = strip_tags($_POST['database_type']);
$host = strip_tags($_POST['host']);
$uid = strip_tags($_POST['uid']);
$pwd = strip_tags($_POST['pwd']);
$tableprefix = strip_tags($_POST['tableprefix']);
$database_name = strip_tags($_POST['database_name']);
$installMode = $_POST['installMode'];

$output = $_lang['status_checking_database'];
$h = explode(':', $host, 2);
$database_collation = $_POST['database_collation'];
$database_connection_method = $_POST['database_connection_method'];

$database_charset = getDatabaseCharset($database_collation, $driver);
try {
    if ($driver === 'sqlite') {
        $dbh = new PDO('sqlite:' . EVO_CORE_PATH . "database/$database_name.sqlite");
    } else {
        $dbh = new PDO($driver . ':host=' . $host . ';dbname=' . $database_name, $uid, $pwd);
    }
    switch ($driver) {
        case 'pgsql':
            $result = $dbh->query("SELECT * FROM pg_settings WHERE name='client_encoding'");
            if ($result->errorCode() == 0) {
                $data = $result->fetch();
                if ($data['setting'] != $database_charset) {
                    echo $output . '<span id="database_fail">' . sprintf($_lang['status_failed_database_collation_does_not_match'], $data['setting']) . '</span>';
                    exit();
                }
                try {
                    $result = $dbh->query("SELECT COUNT(*) FROM {$tableprefix}site_content");
                } catch (PDOException $e) {
                    // no table is expected
                }

                if ($dbh->errorCode() == 0) {
                    echo $output . '<span id="database_fail">' . $_lang['status_failed_table_prefix_already_in_use'] . '</span>';
                    exit();
                }
            } else {
                echo $output . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . print_r($result->errorInfo(), true) . '</span>';
                exit();
            }
            break;
        case 'mysql':
            $result = $dbh->query("SHOW VARIABLES LIKE 'collation_database'");
            if ($result->errorCode() == 0) {
                $data = $result->fetch();
                $database_actual_collation = $data['Value'];

                $collation_check = $dbh->query("SHOW COLLATION WHERE Collation = " . $dbh->quote($database_collation));
                $collation_available = false;
                if ($collation_check && $collation_check->rowCount() > 0) {
                    $collation_available = true;
                }

                if ($database_actual_collation != $database_collation) {
                    if (!$collation_available && !empty($database_actual_collation)) {
                        $database_collation = $database_actual_collation;
                    } else {
                        echo $output . '<span id="database_fail">' . sprintf($_lang['status_failed_database_collation_does_not_match'], $database_actual_collation) . '</span>';
                        exit();
                    }
                }

                $result = $dbh->query("SELECT COUNT(*) FROM {$tableprefix}site_content");

                if ($dbh->errorCode() == 0) {
                    echo $output . '<span id="database_fail">' . $_lang['status_failed_table_prefix_already_in_use'] . '</span>';
                    exit();
                }
                $result = $dbh->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . $pwd . "'");
                if ($dbh->errorCode() == 0) {
                    $data = $result->fetch();
                    if (isset($data['SCHEMA_NAME']) && $data['SCHEMA_NAME'] == $pwd) {
                        echo $output . '<span id="database_pass"> ' . $_lang['status_passed'] . '</span>';
                        exit();
                    }
                }
            } else {
                echo $output . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . print_r($result->errorInfo(), true) . '</span>';
                exit();
            }
            break;
        case 'sqlite':
            try {
                $result = $dbh->query("SELECT COUNT(*) FROM {$tableprefix}site_content");
            } catch (PDOException $e) {
                // no table is expected
            }

            if ($dbh->errorCode() == 0) {
                echo $output . '<span id="database_fail">' . $_lang['status_failed_table_prefix_already_in_use'] . '</span>';
                exit();
            }
            break;
    }

} catch (PDOException $e) {
    if (!stristr($e->getMessage(), 'database "' . $database_name . '" does not exist') && !stristr($e->getMessage(), 'Unknown database \'' . $database_name . '\'') && !stristr($e->getMessage(), 'Base table or view not found')) {
        echo $output . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . $e->getMessage() . '</span>';
        exit();
    }
}

try {
    if ($driver === 'sqlite') {
        $dbh = new PDO('sqlite:' . EVO_CORE_PATH . "database/$database_name.sqlite");
    } else {
        $dbh = new PDO($driver . ':host=' . $host . ($driver === 'pgsql' ? ';dbname=postgres' : ''), $uid, $pwd);
    }
    switch ($driver) {
        case 'pgsql':
            try {
                $dbh->query(sprintf(
                    "CREATE DATABASE %s WITH ENCODING '%s' LC_COLLATE '%s' LC_CTYPE '%s' TEMPLATE template0",
                    $database_name, $database_charset, $database_collation, $database_collation
                ));
            } catch (PDOException $e) {
                // there is no "create database if not exists" in PostgreSQL
            }
            if ($dbh->errorCode() > 0) {
                if (stristr($dbh->errorInfo()[2], 'already exists') === false) {
                    $output .= '<span id="database_fail">' . $_lang['status_failed_could_not_create_database'] . ' ' . print_r($dbh->errorInfo(), true) . '</span>';
                }
            }
            break;
        case 'mysql':
            $query = 'CREATE DATABASE IF NOT EXISTS `' . $database_name . '` CHARACTER SET ' . $database_charset . ' COLLATE ' . $database_collation . ";";
            if (!$dbh->query($query)) {
                $output .= '<span id="database_fail">' . $_lang['status_failed_could_not_create_database'] . '</span>';
                echo $output;
                exit();
            } else {
                $output .= '<span id="database_pass">' . $_lang['status_passed_database_created'] . '</span>';
                echo $output;
                exit();
            }
            break;
        case 'sqlite':
            break;
    }

    echo $output . '<span id="database_pass"> ' . $_lang['status_passed'] . '</span>';
    exit();
} catch (PDOException $e) {
    echo $output . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . $e->getMessage() . '</span>';
}

echo $output;
