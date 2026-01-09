<?php
$method = strip_tags($_POST['method']);
$host = strip_tags($_POST['host']);
$uid = strip_tags($_POST['uid']);
$pwd = strip_tags($_POST['pwd']);
$database_name = isset($_POST['database_name']) ? strip_tags($_POST['database_name']) : '';

try {
    $dsn = $method . ':host=' . $host;
    $output = '<select id="database_collation" name="database_collation">';

    switch ($method) {
        case 'pgsql':
            $dbh = new PDO($dsn . ";dbname=postgres", $uid, $pwd);
            $sql = "SELECT collname FROM pg_collation ORDER BY collname";
            $_ = [];

            try {
                foreach ($dbh->query($sql) as $row) {
                    $_[$row['collname']] = '';
                }

                // Add options
                foreach (array_keys($_) as $collation) {
                    $selected = ($collation === 'en_US.utf8') ? ' selected' : '';
                    $output .= '<option value="' . htmlspecialchars($collation) . '"' . $selected . '>' . htmlspecialchars($collation) . '</option>';
                }
            } catch (PDOException $e) {
                // Fallback to common collations if query fails
                $output .= '<option value="en_US.utf8" selected>en_US.utf8</option>';
                $output .= '<option value="C.UTF-8">C.UTF-8</option>';
            }
            $output .= '</select>';
            break;
        case 'mysql':
            $dbh = new PDO($dsn, $uid, $pwd);
            $sql = 'SHOW COLLATION';
            $_ = [];
            foreach ($dbh->query($sql) as $row) {
                $_[$row[0]] = '';
            }

            $database_actual_collation = '';
            if (!empty($database_name)) {
                try {
                    $dbh_database = new PDO($dsn, $uid, $pwd);
                    $result = $dbh_database->query("SHOW VARIABLES LIKE 'collation_database'");
                    if ($result && $result->errorCode() == 0) {
                        $data = $result->fetch();
                        if ($data && isset($data['Value'])) {
                            $database_actual_collation = $data['Value'];
                            if (!isset($_[$database_actual_collation])) {
                                $_[$database_actual_collation] = '';
                            }
                        }
                    }
                } catch (PDOException $e) {
                    //
                }
            }

            $database_collation = isset($_POST['database_collation']) ? htmlentities($_POST['database_collation']) : '';
            $recommend_collation = $_lang['recommend_collation'];

            if (isset($_[$recommend_collation])) {
                $_[$recommend_collation] = ' selected';
            } elseif (!empty($database_actual_collation) && isset($_[$database_actual_collation])) {
                $_[$database_actual_collation] = ' selected';
            } elseif (isset($_['utf8mb4_general_ci'])) {
                $_['utf8mb4_general_ci'] = ' selected';
            } elseif (!empty($database_collation) && isset($_[$database_collation])) {
                $_[$database_collation] = ' selected';
            }

            $_ = sortItem($_, $_lang['recommend_collations_order']);

            foreach ($_ as $collation => $selected) {
                $collation = htmlentities($collation);
                if (strpos($collation, 'sjis') === 0) {
                    continue;
                }
                if ($collation == 'recommend') {
                    $output .= '<optgroup label="recommend">';
                } elseif ($collation == 'unrecommend') {
                    $output .= '</optgroup><optgroup label="unrecommend">';
                } else {
                    $output .= '<option value="' . $collation . '" ' . $selected . '>' . $collation . '</option>';
                }
            }
            $output .= '</optgroup></select>';
            break;
    }
    echo $output;
} catch (Exception $e) {
    echo $output . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . $e->getMessage() . '</span>';
}
