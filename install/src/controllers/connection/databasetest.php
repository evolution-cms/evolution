<?php

$host = $_POST['host'];
$uid = $_POST['uid'];
$pwd = $_POST['pwd'];
$installMode = $_POST['installMode'];

$output = $_lang["status_checking_database"];
if (!$conn = mysqli_connect($host, $uid, $pwd)) {
    $output .= '<span id="database_fail" style="color:#FF0000;">' . $_lang['status_failed'] . '</span>';
} else {
    $database_name = mysqli_real_escape_string($conn, $_POST['database_name']);
    $database_name = str_replace("`", "", $database_name);
    $tableprefix = mysqli_real_escape_string($conn, $_POST['tableprefix']);
    $database_collation = mysqli_real_escape_string($conn, $_POST['database_collation']);
    $database_connection_method = mysqli_real_escape_string($conn, $_POST['database_connection_method']);

    if (!@ mysqli_select_db($conn, $database_name)) {
        // create database
        $database_charset = substr($database_collation, 0, strpos($database_collation, '_'));
        $query = "CREATE DATABASE `" . $database_name . "` CHARACTER SET " . $database_charset . " COLLATE " . $database_collation . ";";

        if (!mysqli_query($conn, $query)) {
            $output .= '<span id="database_fail" style="color:#FF0000;">' . $_lang['status_failed_could_not_create_database'] . '</span>';
        } else {
            $output .= '<span id="database_pass" style="color:#80c000;">' . $_lang['status_passed_database_created'] . '</span>';
        }
    } elseif (($installMode == 0) && (mysqli_query($conn,
            "SELECT COUNT(*) FROM {$database_name}.`{$tableprefix}site_content`"))) {
        $output .= '<span id="database_fail" style="color:#FF0000;">' . $_lang['status_failed_table_prefix_already_in_use'] . '</span>';
    } elseif (($database_connection_method != 'SET NAMES') && ($rs = mysqli_query($conn,
            "show variables like 'collation_database'")) && ($row = mysqli_fetch_row($rs)) && ($row[1] != $database_collation)) {
        $output .= '<span id="database_fail" style="color:#FF0000;">' . sprintf($_lang['status_failed_database_collation_does_not_match'],
                $row[1]) . '</span>';
    } else {
        $output .= '<span id="database_pass" style="color:#80c000;">' . $_lang['status_passed'] . '</span>';
    }
}

echo $output;
