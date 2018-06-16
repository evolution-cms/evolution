<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

// START HACK
if (isset ($modx)) {
    $user_id = $modx->getLoginUserID();
} else {
    $user_id = $_SESSION['mgrInternalKey'];
}
// END HACK

if (!empty($user_id)) {
    // Raymond: grab the user settings from the database.
    $rs = $modx->getDatabase()->select('setting_name, setting_value', $modx->getDatabase()->getFullTableName('user_settings'),
        "user=" . $modx->getLoginUserID());

    $which_browser_default = $which_browser;
    while ($row = $modx->getDatabase()->getRow($rs)) {
        if ($row['setting_name'] == 'which_browser' && $row['setting_value'] == 'default') {
            $row['setting_value'] = $which_browser_default;
        }
        $settings[$row['setting_name']] = $row['setting_value'];
        if (isset($modx->config)) {
            $modx->config[$row['setting_name']] = $row['setting_value'];
        }
    }
    extract($settings, EXTR_OVERWRITE);
}
