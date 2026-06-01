<?php
try {
    $method = validateDbType($_POST['database_type']);
    $host = validateDbHost($_POST['host'], $method);
    $uid = validateDbUser($_POST['uid'], $method);
    $pwd = validateDbPassword($_POST['pwd'], $method);
} catch (Throwable $e) {
    exit('<span id="server_fail"> ' . $_lang['status_failed'] . ' ' . $e->getMessage() . '</span>');
}

$output = $_lang['status_connecting'];
try {
    if ($method === 'sqlite') {
        $dbh = new PDO('sqlite::memory:');
    } else {
        $dbh = new PDO(databaseDsn($method, $host, $method === 'pgsql' ? 'postgres' : null), $uid, $pwd);
    }
    $output .= '<span id="server_pass"> ' . $_lang['status_passed_server'] . '</span>';
} catch (Throwable $e) {
    $output .= '<span id="server_fail"> ' . $_lang['status_failed'] . ' ' . $e->getMessage() . '</span>';
}
echo $output;
