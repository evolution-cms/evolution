<?php
$method = strip_tags($_POST['method']);
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
$database_charset = substr($database_collation, 0, strpos($database_collation, '_'));

if ($method == 'pgsql') {
    if ($database_charset == 'utf8mb4') $database_charset = 'utf8';
    $database_charset = mb_strtoupper($database_charset);
}
try {
    $dbh = new PDO($method . ':host=' . $host . ';dbname=' . $database_name, $uid, $pwd);
    switch ($method) {
        case 'pgsql':
            $result = $dbh->query("SELECT * FROM pg_settings WHERE name='client_encoding'");
            if ($result->errorCode() == 0) {
                $data = $result->fetch();
                if ($data['setting'] != $database_charset) {
                    echo $output . '<span id="database_fail">' . sprintf($_lang['status_failed_database_collation_does_not_match'], $data['setting']) . '</span>';
                    exit();
                }
                $result = $dbh->query("SELECT COUNT(*) FROM {$tableprefix}site_content");

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
    }

} catch (PDOException $e) {
    if (!stristr($e->getMessage(), 'database "' . $pwd . '" does not exist') && !stristr($e->getMessage(), 'Unknown database \'' . $database_name . '\'') && !stristr($e->getMessage(), 'Base table or view not found')) {
        echo $output . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . $e->getMessage() . '</span>';
        exit();
    }
}

try {
    $dbh = new PDO($method . ':host=' . $host . ';', $uid, $pwd);
    switch ($method) {
        case 'pgsql':
            try {
                $dbh->query('CREATE DATABASE "' . $database_name . '" ENCODING \'' . $database_charset . '\';');
                if ($dbh->errorCode() > 0) {
                    if (stristr($dbh->errorInfo()[2], 'already exists') === false) {
                        $output .= '<span id="database_fail">' . $_lang['status_failed_could_not_create_database'] . ' ' . print_r($dbh->errorInfo(), true) . '</span>';
                    }
                }
            } catch (Exception $exception) {
                echo $exception->getMessage();
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
    }

    echo $output . '<span id="database_pass"> ' . $_lang['status_passed'] . '</span>';
    exit();
} catch (PDOException $e) {
    echo $output . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . $e->getMessage() . '</span>';
}

echo $output;
