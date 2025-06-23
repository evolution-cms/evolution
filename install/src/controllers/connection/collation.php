<?php
$method = strip_tags($_POST['method']);
$host = strip_tags($_POST['host']);
$uid = strip_tags($_POST['uid']);
$pwd = strip_tags($_POST['pwd']);

try {
    $dbh = new PDO($method . ':host=' . $host, $uid, $pwd);
    $output = '<select id="database_collation" name="database_collation">';

    switch ($method) {
        case 'pgsql':
            $output = '<select id="database_collation" name="database_collation">';
            $output .= '<option value="utf8mb4_general_ci" selected>utf8mb4_general_ci</option>';
            $output .= '</optgroup></select>';
            break;
        case 'mysql':
            $output = '<select id="database_collation" name="database_collation">';
            $sql = 'SHOW COLLATION';
            $_ = array();
            foreach ($dbh->query($sql) as $row) {
                $_[$row[0]] = '';
            }
            $database_collation = isset($_POST['database_collation']) ? htmlentities($_POST['database_collation']) : '';
            $recommend_collation = $_lang['recommend_collation'];

            if (isset($_[$recommend_collation])) {
                $_[$recommend_collation] = ' selected';
            } elseif (isset($_['utf8mb4_general_ci'])) {
                $_['utf8mb4_general_ci'] = ' selected';
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
    exit();
} catch (Exception $e) {
    echo $output . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . $e->getMessage() . '</span>';
    exit();
}
echo $output;
exit;
