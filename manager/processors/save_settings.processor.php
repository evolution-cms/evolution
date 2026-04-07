<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

if (!$modx->hasPermission('settings')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}
$defaultSettings = config('cms.settings', []);
$data = $_POST + $defaultSettings;

// lose the POST now, gets rid of quirky issue with Safari 3 - see FS#972
unset($_POST);

if ($data['friendly_urls'] === '1' && strpos($_SERVER['SERVER_SOFTWARE'], 'IIS') === false) {
    $htaccess = EVO_BASE_PATH . '.htaccess';
    $sample_htaccess = EVO_BASE_PATH . 'ht.access';
    $dir = '/' . trim(EVO_BASE_URL, '/');
    if (is_file($htaccess)) {
        $_ = file_get_contents($htaccess);
        if (strpos($_, 'RewriteBase') === false) {
            $warnings[] = $_lang["settings_friendlyurls_alert2"];
        } elseif (is_writable($htaccess)) {
            $_ = preg_replace('@RewriteBase.+@', "RewriteBase {$dir}", $_);
            if (!@file_put_contents($htaccess, $_)) {
                $warnings[] = $_lang["settings_friendlyurls_alert2"];
            }
        }
    } elseif (is_file($sample_htaccess)) {
        if (!@rename($sample_htaccess, $htaccess)) {
            $warnings[] = $_lang["settings_friendlyurls_alert"];
        } elseif (EVO_BASE_URL !== '/') {
            $_ = file_get_contents($htaccess);
            $_ = preg_replace('@RewriteBase.+@', "RewriteBase {$dir}", $_);
            if (!@file_put_contents($htaccess, $_)) {
                $warnings[] = $_lang["settings_friendlyurls_alert2"];
            }
        }
    }
}

if (file_exists(EVO_MANAGER_PATH . 'media/style/' . $modx->getConfig('manager_theme') . '/css/styles.min.css')) {
    unlink(EVO_MANAGER_PATH . 'media/style/' . $modx->getConfig('manager_theme') . '/css/styles.min.css');
}

$data['filemanager_path'] = str_replace('[(base_path)]', EVO_BASE_PATH, $data['filemanager_path']);
$data['rb_base_dir'] = str_replace('[(base_path)]', EVO_BASE_PATH, $data['rb_base_dir']);

if (isset($data) && count($data) > 0) {
    if (isset($data['manager_language'])) {
        $lang_path = EVO_CORE_PATH . 'lang/' . $data['manager_language'] . '/global.php';
        if (is_file($lang_path)) {
            include $lang_path;
            $data['lang_code'] = $data['manager_language'];
        }
    }
    $data['sys_files_checksum'] = $modx->getManagerApi()->getSystemChecksum($data['check_files_onlogin']);
    foreach ($data as $k => $v) {
        if (isset($defaultSettings[$k])) {
            continue;
        }

        switch ($k) {
            case 'settings_version':
                if ($modx->getVersionData('version') != $data['settings_version']) {
                    $modx->logEvent(17, 2, '<pre>' . var_export($data['settings_version'], true) . '</pre>', 'fake settings_version');
                    $v = $modx->getVersionData('version');
                }
                break;
            case 'error_page':
            case 'unauthorized_page':
                if (trim($v) == '' || !is_numeric($v)) {
                    $v = $data['site_start'];
                }
                break;
            case 'lst_custom_contenttype':
            case 'txt_custom_contenttype':
                // Skip these
                $k = '';
                break;
            case 'rb_base_dir':
            case 'filemanager_path':
                $v = \EvolutionCMS\Support\SystemSettingPathNormalizer::normalizeStorageValue($k, $v, EVO_BASE_PATH);
                break;
            case 'rb_base_url':
                $v = trim($v);
                $v = rtrim($v, '/') . '/';
                break;
            case 'manager_language':
                $langDir = realpath(EVO_CORE_PATH . 'lang/' . $v);
                $langFile = realpath(EVO_CORE_PATH . 'lang/' . $v . '/global.php');
                $langFileDir = dirname($langFile);
                if ($langDir !== $langFileDir || !file_exists($langFile)) {
                    $v = 'en';
                }
                break;
            case 'smtppw':
                if ($v !== '********************' && $v !== '') {
                    $v = trim($v);
                    $v = base64_encode($v) . substr(str_shuffle('abcdefghjkmnpqrstuvxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 7);
                    $v = str_replace('=', '%', $v);
                } elseif ($v === '********************') {
                    $k = '';
                }
                break;
            default:
                break;
        }
        $v = is_array($v) ? implode(",", $v) : $v;

        $modx->config[$k] = $v;

        if (!empty($k)) {
            \EvolutionCMS\Models\SystemSetting::query()->updateOrCreate(['setting_name' => $k], ['setting_value' => $v]);
        }
    }

    // Reset Template Pages
    if (isset($data['reset_template'])) {
        $newtemplate = (int) $data['default_template'];
        $oldtemplate = (int) $data['old_template'];
        $reset = $data['reset_template'];
        if ($reset == 1) {
            \EvolutionCMS\Models\SiteContent::where('type', 'document')->update(['template' => $newtemplate]);
        } else if ($reset == 2) {
            \EvolutionCMS\Models\SiteContent::where('template', $oldtemplate)->update(['template' => $newtemplate]);
        }
    }

    // empty cache
    $modx->clearCache('full');
}

// redirect/stay options
if ($data['stay'] != '') {
    $header = "Location: index.php?a=17&stay=" . $data['stay'];
} else {
    $header = "Location: index.php?a=7&r=10";
}
header($header);
