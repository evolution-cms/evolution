<?php
$driver = validateDbType($_POST['database_type']);
$host = validateDbHost($_POST['host'], $driver);
$uid = validateDbUser($_POST['uid'], $driver);
$pwd = validateDbPassword($_POST['pwd'], $driver);
$tableprefix = validateTablePrefix($_POST['tableprefix']);
$database_name = validateDbName($_POST['database_name']);
$installMode = (int)$_POST['installMode'];

$output = $_lang['status_checking_database'];
$database_collation = $_POST['database_collation'];

$database_charset = getDatabaseCharset($database_collation, $driver);
try {
    if ($driver === 'sqlite') {
        $dbh = new PDO('sqlite:' . sqliteDbNameToPath($database_name));
    } else {
        $dbh = new PDO(databaseDsn($driver, $host, $database_name), $uid, $pwd);
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

                if ($installMode === 0 && $dbh->errorCode() == 0) {
                    echo $output . '<span id="database_fail">' . $_lang['status_failed_table_prefix_already_in_use'] . '</span>';
                    exit();
                }
            } else {
                echo $output . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . htmlspecialchars(print_r($result->errorInfo(), true), ENT_QUOTES, 'UTF-8') . '</span>';
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
                        echo $output . '<span id="database_fail">' . sprintf($_lang['status_failed_database_collation_does_not_match'], htmlspecialchars((string)$database_actual_collation, ENT_QUOTES, 'UTF-8')) . '</span>';
                        exit();
                    }
                }

                try {
                    $dbh->query("SELECT COUNT(*) FROM {$tableprefix}site_content");
                } catch (PDOException $e) {
                    // no table is expected for new installation
                    if ($e->getCode() !== '42S02') {
                        throw $e;
                    }
                }
                if ($installMode === 0 && $dbh->errorCode() == 0) {
                    echo $output . '<span id="database_fail">' . $_lang['status_failed_table_prefix_already_in_use'] . '</span>';
                    exit();
                }
                $result = $dbh->query('SELECT SCHEMA_NAME, DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME '
                  . 'FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ' . $dbh->quote($database_name));
                if ($dbh->errorCode() == 0) {
                    $data = $result->fetch();
                    if (isset($data['SCHEMA_NAME']) && $data['SCHEMA_NAME'] === $database_name) {
                        if ($data['DEFAULT_CHARACTER_SET_NAME'] === $database_charset
                        && $data['DEFAULT_COLLATION_NAME'] === $database_collation) {
                            echo $output . '<span id="database_pass"> ' . $_lang['status_passed'] . '</span>';
                        } else {
                            echo $output . '<span id="database_fail">' . sprintf($_lang['status_failed_database_collation_does_not_match'],
                                $data['DEFAULT_COLLATION_NAME']) . '</span>';
                        }
                        exit();
                    }
                }
            } else {
                echo $output . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . htmlspecialchars(print_r($result->errorInfo(), true), ENT_QUOTES, 'UTF-8') . '</span>';
                exit();
            }
            break;
        case 'sqlite':
            try {
                $result = $dbh->query("SELECT COUNT(*) FROM {$tableprefix}site_content");
            } catch (PDOException $e) {
                // no table is expected
            }

            if ($installMode === 0 && $dbh->errorCode() == 0) {
                echo $output . '<span id="database_fail">' . $_lang['status_failed_table_prefix_already_in_use'] . '</span>';
                exit();
            }
            break;
    }

} catch (PDOException $e) {
    if (!stristr($e->getMessage(), 'database "' . $database_name . '" does not exist') && !stristr($e->getMessage(), 'Unknown database \'' . $database_name . '\'') && !stristr($e->getMessage(), 'Base table or view not found')) {
        echo $output . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</span>';
        exit();
    }
} catch (Throwable $e) {
    echo $output . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</span>';
    exit();
}

try {
    if ($driver === 'sqlite') {
        $dbh = new PDO('sqlite:' . sqliteDbNameToPath($database_name));
    } else {
        $dbh = new PDO(databaseDsn($driver, $host, $driver === 'pgsql' ? 'postgres' : null), $uid, $pwd);
    }
    switch ($driver) {
        case 'pgsql':
            try {
                $dbh->query(sprintf(
                    "CREATE DATABASE \"%s\" WITH ENCODING '%s' LC_COLLATE '%s' LC_CTYPE '%s' TEMPLATE template0",
                    str_replace('"', '""', $database_name), $database_charset, $database_collation, $database_collation
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
            $query = 'CREATE DATABASE IF NOT EXISTS `' . $database_name . '` CHARACTER SET ' . $database_charset .
                ' COLLATE ' . $database_collation . ";";
            try {
                $dbh->query($query);
                die('<span id="database_pass">' . $_lang['status_passed_database_created'] . '</span>');
            } catch (PDOException $e) {
                die('<span id="database_fail">' . $_lang['status_failed_could_not_create_database'] . '</span>');
            }
        case 'sqlite':
            break;
    }

    echo $output . '<span id="database_pass"> ' . $_lang['status_passed'] . '</span>';
    exit();
} catch (Throwable $e) {
    echo $output . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</span>';
    exit();
}
