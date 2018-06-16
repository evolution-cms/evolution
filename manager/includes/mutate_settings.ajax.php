<?php
/**
 * mutate_settings.ajax.php
 */
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

require_once(dirname(__FILE__) . '/protect.inc.php');

$action = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '', $_POST['action']);
$lang = preg_replace('/[^A-Za-z0-9_\s\+\-\.\/]/', '', $_POST['lang']);
$key = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '', $_POST['key']);
$value = preg_replace('/[^A-Za-z0-9_\-\.\/]/', '', $_POST['value']);

$action = $modx->getDatabase()->escape($action);
$lang = $modx->getDatabase()->escape($lang);
$key = $modx->getDatabase()->escape($key);
$value = $modx->getDatabase()->escape($value);

$str = '';
$emptyCache = false;

switch (true) {
    case ($action == 'get' && preg_match('/^[A-z0-9_-]+$/',
            $lang) && file_exists(dirname(__FILE__) . '/lang/' . $lang . '.inc.php')): {
        include(dirname(__FILE__) . '/lang/' . $lang . '.inc.php');
        $str = isset($key, $_lang, $_lang[$key]) ? $_lang[$key] : "";
        break;
    }
    case ($action == 'setsetting' && !empty($key) && !empty($value)): {
        $sql = "REPLACE INTO " . $modx->getDatabase()->getFullTableName("system_settings") . " (setting_name, setting_value) VALUES('{$key}', '{$value}');";
        $str = "true";
        $modx->getDatabase()->query($sql);
        $emptyCache = true;
        break;
    }
    case ($action == 'updateplugin' && ($key == '_delete_' && !empty($lang))): {
        $modx->getDatabase()->delete($modx->getDatabase()->getFullTableName("site_plugins"), "name='{$lang}'");
        $str = "true";
        $emptyCache = true;
        break;
    }
    case ($action == 'updateplugin' && (!empty($key) && !empty($lang) && !empty($value))): {
        $modx->getDatabase()->update(array($key => $value), $modx->getDatabase()->getFullTableName("site_plugins"), "name = '{$lang}'");
        $str = "true";
        $emptyCache = true;
        break;
    }
    default: {
        break;
    }
}

if ($emptyCache) {
    $modx->clearCache('full');
}

echo $str;
