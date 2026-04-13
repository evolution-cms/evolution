<?php namespace EvolutionCMS\Services\Store;

class ModuleViewService
{
    public function render($store, $version)
    {
        $modx = EvolutionCMS();
        $legacyInstalled = $store->getLegacyInstalledState();
        $installedState = [
            'legacy_by_type' => $legacyInstalled['by_type'],
            'legacy_items' => $legacyInstalled['items'],
            'console_by_composer' => $store->getConsoleInstalledState(),
        ];

        $store->lang['user_email'] = $_SESSION['mgrEmail'] ?? '';
        $store->lang['hash'] = isset($_SESSION['STORE_USER']) ? stripslashes($_SESSION['STORE_USER']) : '';
        $store->lang['lang'] = $store->language;
        $store->lang['_type'] = json_encode($legacyInstalled['by_type'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $store->lang['installed_state'] = json_encode($installedState, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $store->lang['v'] = (string) $version;
        $store->lang['project_path'] = rtrim(EVO_BASE_PATH, '/\\');
        $store->lang['core_path'] = defined('EVO_CORE_PATH')
            ? rtrim(EVO_CORE_PATH, '/\\')
            : rtrim(EVO_BASE_PATH, '/\\') . '/core';
        $store->lang['system_task_ui_flags'] = json_encode($store->getSystemTaskUiFlags(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ((int) ($modx->config['manager_theme_mode'] ?? 0) === 4) {
            $store->lang['body_class_name'] = 'darkness';
        }

        $tpl = $store->parseTemplate($store->loadTemplate($store->getModulePath() . '/template/main.html'), $modx->config);
        return $store->parseTemplate($tpl, $store->lang);
    }
}
