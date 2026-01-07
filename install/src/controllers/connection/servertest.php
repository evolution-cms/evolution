<?php
$method = strip_tags($_POST['method']);
$host = strip_tags($_POST['host']);
$uid = strip_tags($_POST['uid']);
$pwd = strip_tags($_POST['pwd']);

$output = $_lang['status_connecting'];
try {
    $dbh = new PDO($method . ':host=' . $host . ($method === 'pgsql' ? ';dbname=postgres' : ''), $uid, $pwd);
    $output .= '<span id="server_pass"> ' . $_lang['status_passed_server'] . '</span>';
} catch (PDOException $e) {
    $output .= '<span id="server_fail"> ' . $_lang['status_failed'] . ' ' . $e->getMessage() . '</span>';
}
echo $output;
