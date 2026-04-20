<?php

/**
 * Multilanguage functions for EVO Installer
 *
 * @author davaeron
 * @package EVO
 * @version 1.0
 *
 * Filename:       /install/lang.php
 */

$_lang = [];

#default fallback language file - english
$install_language = 'en';

$_langISO6391 = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2);
if (ctype_alpha($_langISO6391) && file_exists(__DIR__ . '/lang/' . $_langISO6391 . '.inc.php')) {
    $install_language = $_langISO6391;
}

if (isset($_POST['language']) && ctype_alpha($_POST['language'])) {
    $install_language = $_POST['language'];
} else {
    if (isset($_GET['language']) && ctype_alpha($_GET['language'])) {
        $install_language = $_GET['language'];
    }
}
# load language file
require_once 'lang/en.inc.php'; // As fallback
require_once 'lang/' . $install_language . '.inc.php';

$manager_language = $install_language;

if (isset($_POST['managerlanguage']) && ctype_alpha($_POST['managerlanguage'])) {
    $manager_language = $_POST['managerlanguage'];
} else {
    if (isset($_GET['managerlanguage']) && ctype_alpha($_GET['managerlanguage'])) {
        $manager_language = $_GET['managerlanguage'];
    }
}

foreach ($_lang as $k => $v) {
    if (strpos($v, '[+MGR_DIR+]') !== false) {
        $_lang[$k] = str_replace('[+MGR_DIR+]', MGR_DIR, $v);
    }
}
