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

$_lang = array();

#default fallback language file - english
$install_language = 'english';

$_langFiles = array(
    'en' => 'english',
    'bg' => 'bulgarian',
    'cs' => 'czech',
    'da' => 'danish',
    'fi' => 'finnish-utf8',
    'fr' => 'francais-utf8',
    'de' => 'german',
    'he' => 'hebrew',
    'it' => 'italian',
    'ja' => 'japanese-utf8',
    'nl' => 'nederlands-utf8',
    'no' => 'norwegian',
    'fa' => 'persian',
    'pl' => 'polish-utf8',
    'pt' => 'portuguese-br-utf8',
// 'pt' => 'portuguese',
    'ru' => 'russian-UTF8',
    'es' => 'spanish-utf8',
    'sv' => 'svenska'
);
$_langISO6391 = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
if (! empty($_langFiles[$_langISO6391])) {
    $install_language = $_langFiles[$_langISO6391];
}


if (isset($_POST['language']) && false === strpos($_POST['language'], '..')) {
    $install_language = $_POST['language'];
} else {
    if (isset($_GET['language']) && false === strpos($_GET['language'], '..')) {
        $install_language = $_GET['language'];
    }
}
# load language file
require_once 'lang/english.inc.php'; // As fallback
require_once 'lang/' . $install_language . '.inc.php';

$manager_language = $install_language;

if (isset($_POST['managerlanguage']) && false === strpos($_POST['managerlanguage'], '..')) {
    $manager_language = $_POST['managerlanguage'];
} else {
    if (isset($_GET['managerlanguage']) && false === strpos($_GET['managerlanguage'], '..')) {
        $manager_language = $_GET['managerlanguage'];
    }
}

foreach ($_lang as $k => $v) {
    if (strpos($v, '[+MGR_DIR+]') !== false) {
        $_lang[$k] = str_replace('[+MGR_DIR+]', MGR_DIR, $v);
    }
}
