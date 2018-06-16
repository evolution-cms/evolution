<?php

$host = $_POST['host'];
$uid = $_POST['uid'];
$pwd = $_POST['pwd'];

if (function_exists('mysqli_connect')) {
    $conn = mysqli_connect($host, $uid, $pwd);
    if (!$conn) {
        exit('can not connect');
    }
} else {
    exit('undefined function mysqli_connect()');
}

// get collation
$rs = mysqli_query($conn, "SHOW COLLATION");
if (mysqli_num_rows($rs) > 0) {
    $output = '<select id="database_collation" name="database_collation">';
    $_ = array();
    while ($row = mysqli_fetch_row($rs)) {
        $_[$row[0]] = '';
    }

    $database_collation = isset($_POST['database_collation']) ? htmlentities($_POST['database_collation']) : '';
    $recommend_collation = $_lang['recommend_collation'];

    if (isset($_[$recommend_collation])) {
        $_[$recommend_collation] = ' selected';
    } elseif (isset($_['utf8mb4_general_ci'])) {
        $_['utf8mb4_general_ci'] = ' selected';
    } elseif (isset($_['utf8_general_ci'])) {
        $_['utf8_general_ci'] = ' selected';
    } elseif (!empty($database_collation) && isset($_[$database_collation])) {
        $_[$database_collation] = ' selected';
    }

    $_ = sortItem($_, $_lang['recommend_collations_order']);

    foreach ($_ as $collation => $selected) {
        $collation = htmlentities($collation);
        // if(substr($collation,0,4)!=='utf8') continue;
        if (strpos($collation, 'sjis') === 0) {
            continue;
        }
        if ($collation == 'recommend') {
            $output .= '<optgroup label="recommend">';
        } elseif ($collation == 'unrecommend') {
            $output .= '</optgroup><optgroup label="unrecommend">';
        } else {
            $output .= sprintf('<option value="%s" %s>%s</option>', $collation, $selected, $collation);
        }
    }
    $output .= '</optgroup></select>';
}

echo $output;
exit;
