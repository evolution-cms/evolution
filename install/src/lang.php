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

// Language codes arrive from the request and are interpolated into include paths, so a code is
// only accepted when it is a plain alphabetic stem that names a shipped locale file/directory.
$installLanguageExists = static function ($code): bool {
    return is_string($code) && ctype_alpha($code) && is_file(__DIR__ . '/lang/' . $code . '.inc.php');
};
$managerLanguageExists = static function ($code): bool {
    return is_string($code) && ctype_alpha($code) && is_dir(dirname(__DIR__, 2) . '/core/lang/' . $code);
};

$_langISO6391 = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2);
if ($installLanguageExists($_langISO6391)) {
    $install_language = $_langISO6391;
}

if ($installLanguageExists($_POST['language'] ?? null)) {
    $install_language = $_POST['language'];
} elseif ($installLanguageExists($_GET['language'] ?? null)) {
    $install_language = $_GET['language'];
}
# load language file
require __DIR__ . '/lang/en.inc.php'; // As fallback
$fallbackLang = $_lang;
if ($install_language !== 'en') {
    require __DIR__ . '/lang/' . $install_language . '.inc.php';
}
$_lang += $fallbackLang;

$manager_language = $managerLanguageExists($install_language) ? $install_language : 'en';

if ($managerLanguageExists($_POST['managerlanguage'] ?? null)) {
    $manager_language = $_POST['managerlanguage'];
} elseif ($managerLanguageExists($_GET['managerlanguage'] ?? null)) {
    $manager_language = $_GET['managerlanguage'];
}

foreach ($_lang as $k => $v) {
    if (strpos($v, '[+MGR_DIR+]') !== false) {
        $_lang[$k] = str_replace('[+MGR_DIR+]', MGR_DIR, $v);
    }
}
