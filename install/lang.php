<?php

/**
 * Multilanguage functions for MODx Installer
 *
 * @author davaeron
 * @package MODx
 * @version 1.0
 * 
 * Filename:       /install/lang.php
 */

$_lang = array ();

#default fallback language file - english
require_once("lang/english.inc.php");

$install_language = "english";

if (isset($_POST['language']) && !stristr($_POST['language'],"..")) {
	$install_language = $_POST['language'];
} else {
	if (isset($_GET['language']) && !stristr($_GET['language'],"..")) 
		$install_language = $_GET['language'];
}

$manager_language = "english";

if (isset($_POST['managerlanguage']) && !stristr($_POST['managerlanguage'],"..")) {
	$manager_language = $_POST['managerlanguage'];
} else {
	if (isset($_GET['managerlanguage']) && !stristr($_GET['managerlanguage'],"..")) 
		$manager_language = $_GET['managerlanguage'];
}

# load language file
if($install_language!="english" && file_exists("lang/".$install_language.".inc.php")) {
    include_once "lang/".$install_language.".inc.php";
}
foreach($_lang as $k=>$v)
{
	if(strpos($v,'[+MGR_DIR+]')!==false)
		$_lang[$k] = str_replace('[+MGR_DIR+]', MGR_DIR, $v);
}
?>