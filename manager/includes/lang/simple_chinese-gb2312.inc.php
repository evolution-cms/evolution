<?php
    $filename = dirname(__FILE__) . '/simple_chinese-gb2312-utf8.inc.php';
    $contents = file_get_contents($filename);
    $contents = mb_convert_encoding($contents, 'EUC-CN', 'UTF-8');
//    $contents = str_replace('UTF-8', 'gb2312', $contents);
    eval('?>' . $contents);
    $modx_lang_attribute = 'zh'; // Manager HTML and XML Language Attribute
    $modx_manager_charset = 'gb2312';
?>