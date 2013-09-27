<?php
    $filename = dirname(__FILE__) . '/svenska-utf8_country.inc.php';
    $contents = file_get_contents($filename);
    $contents = utf8_decode($contents);
    eval('?>' . $contents);
?>