<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

// get the settings from the database.
$settings = array();
if ($modx && count($modx->config) > 0) {
    $settings = $modx->config;
} else {
    $rs = $modx->getDatabase()->select('setting_name, setting_value', $modx->getDatabase()->getFullTableName('system_settings'));
    while ($row = $modx->getDatabase()->getRow($rs)) {
        $settings[$row['setting_name']] = $row['setting_value'];
    }
}

extract($settings, EXTR_OVERWRITE);

// setup default site id - new installation should generate a unique id for the site.
if (!isset($site_id)) {
    $site_id = "MzGeQ2faT4Dw06+U49x3";
}
