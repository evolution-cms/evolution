<?php
if(IN_MANAGER_MODE!=true && IN_PARSER_MODE!="true") die("<b>INCLUDE ACCESS ERROR</b><br /><br />Direct access to this file prohibited.");
$tmpArray = array();
$tmpArray['ip'] = $_SERVER['REMOTE_ADDR'];
$tmpArray['ua'] = $_SERVER['HTTP_USER_AGENT'];
return $tmpArray;
