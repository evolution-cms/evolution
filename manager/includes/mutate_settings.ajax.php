<?php
/**
 * mutate_settings.ajax.php
 * 
 */
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
 
require_once(dirname(__FILE__) . '/protect.inc.php');

$action = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '', $_POST['action']);
$lang = preg_replace('/[^A-Za-z0-9_\s\+\-\.\/]/', '', $_POST['lang']);
$key = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '', $_POST['key']);
$value = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '', $_POST['value']);

$action = $modx->db->escape($action);
$lang = $modx->db->escape($lang);
$key = $modx->db->escape($key);
$value = $modx->db->escape($value);

$str = '';
$emptyCache = false;

switch(true){
    case ($action == 'get' && preg_match('/^[A-z0-9_-]+$/',$lang) && file_exists(dirname(__FILE__) . '/lang/'.$lang.'.inc.php')):{
        include(dirname(__FILE__) . '/lang/'.$lang.'.inc.php');
        $str = isset($key,$_lang,$_lang[$key]) ? $_lang[$key] : "" ;
        break;
    }
    case ($action == 'setsetting' && !empty($key) && !empty($value)):{
        $sql = "REPLACE INTO ".$modx->getFullTableName("system_settings")." (setting_name, setting_value) VALUES('{$key}', '{$value}');";
        $str = "true";
        if(!@$rs = $modx->db->query($sql)) {
            $str = "false";
        } else {
            $emptyCache = true;
        }
        break;
    }
    case ($action == 'updateplugin' && ($key == '_delete_' && !empty($lang))):{
        $sql = "DELETE FROM " . $modx->getFullTableName("site_plugins") . " WHERE name='{$lang}'";
        $str = "true";
        if(!@$rs = $modx->db->query($sql)) {
            $str = "false";
        } else {
            $emptyCache = true;
        }
        break;
    }
    case ($action == 'updateplugin' && (!empty($key) && !empty($lang) && !empty($value))):{
        $sql = "UPDATE ".$modx->getFullTableName("site_plugins")." SET {$key}='{$value}' WHERE name = '{$lang}';";
        $str = "true";
        if(!@$rs = $modx->db->query($sql)) {
            $str = "false";
        } else {
            $emptyCache = true;
        }
        break;
    }
    default: {
    break;
    }
}

if($emptyCache) {
    include_once dirname(dirname(__FILE__)) . "/processors/cache_sync.class.processor.php";
    $sync = new synccache();
    $sync->setCachepath("../assets/cache/");
    $sync->setReport(false);
    $sync->emptyCache();
}

echo $str;