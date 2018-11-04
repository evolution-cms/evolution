<?php
if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

$ph = array();

// use icons
$ph['tabPadding'] = (isset($useIcons) && $useIcons === 'yes') ? '10px' : '9px';

// tree buttons in tab
if (isset($treeButtonsInTab) && $treeButtonsInTab === 'yes') {
    $ph['treeButtonsInTab_js']  = file_get_contents($eitBaseBath.'assets/js_treeButtonsInTab.tpl');
    $ph['treeButtonsInTab_css'] = file_get_contents($eitBaseBath.'assets/css_treeButtonsInTab.tpl');
} else {
    $ph['treeButtonsInTab_js'] = '';
    $ph['treeButtonsInTab_css'] = '';
}

$ph['tabTreeTitle'] = '<i class="fa fa-sitemap"></i>';

// Prepare lang-strings
$unlockTranslations = array();
$unlockTranslations['msg']   = $_lang["unlock_element_id_warning"];
$unlockTranslations['type1'] = $_lang["lock_element_type_1"];
$unlockTranslations['type2'] = $_lang["lock_element_type_2"];
$unlockTranslations['type3'] = $_lang["lock_element_type_3"];
$unlockTranslations['type4'] = $_lang["lock_element_type_4"];
$unlockTranslations['type5'] = $_lang["lock_element_type_5"];
$unlockTranslations['type6'] = $_lang["lock_element_type_6"];
$unlockTranslations['type7'] = $_lang["lock_element_type_7"];
$unlockTranslations['type8'] = $_lang["lock_element_type_8"];

// start main output
$modx->event->addOutput(
    $modx->parseText(file_get_contents($eitBaseBath.'assets/txt_content.tpl'), $ph)
);
