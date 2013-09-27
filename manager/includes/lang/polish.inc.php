<?php
    $filename = dirname(__FILE__) . '/polish-utf8.inc.php';
    $contents = file_get_contents($filename);
    $contents = mb_convert_encoding($contents, 'iso-8859-2', 'UTF-8');
//    $contents = str_replace('UTF-8', $modx_charset, $contents);
    eval('?>' . $contents);
    $modx_lang_attribute = 'pl'; // Manager HTML/XML Language Attribute see http://en.wikipedia.org/wiki/ISO_639-1
    $modx_manager_charset = 'iso-8859-2';
