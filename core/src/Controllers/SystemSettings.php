<?php namespace EvolutionCMS\Controllers;

use EvolutionCMS\Interfaces\ManagerTheme;
use EvolutionCMS\Models;
use function extension_loaded;
use function is_array;

class SystemSettings extends AbstractController implements ManagerTheme\PageControllerInterface
{
    /**
     * @var string
     */
    protected $view = 'page.system_settings';

    /**
     * @var array
     */
    protected array $tabEvents = [
        'OnMiscSettingsRender',
        'OnFriendlyURLSettingsRender',
        'OnSiteSettingsRender',
        'OnInterfaceSettingsRender',
        'OnUserSettingsRender',
        'OnSecuritySettingsRender',
        'OnFileManagerSettingsRender',
    ];

    /**
     * @var array
     */
    protected array $disabledSettings = [];

    /**
     * {@inheritdoc}
     */
    public function canView(): bool
    {
        return $this->managerTheme->getCore()
            ->hasPermission('settings');
    }

    /**
     * {@inheritdoc}
     */
    public function checkLocked(): ?string
    {
        $out = Models\ActiveUser::locked(17)
            ->first();
        if ($out !== null) {
            return sprintf($this->managerTheme->getLexicon('lock_settings_msg'), $out->username);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(array $params = []): array
    {
        return [
            'passwordsHash' => $this->parameterPasswordHash(),
            'gdAvailable' => $this->parameterCheckGD(),
            'settings' => $this->parameterSettings(),
            'disabledSettings' => $this->disabledSettings(),
            'displayStyle' => ($_SESSION['browser'] === 'modern') ? 'table-row' : 'block',
            'fileBrowsers' => $this->parameterFileBrowsers(),
            'themes' => $this->parameterThemes(),
            'serverTimes' => $this->parameterServerTimes(),
            'phxEnabled' => Models\SitePlugin::activePhx()
                ->count(),
            'langKeys' => $this->parameterLang(),
            'templates' => $this->parameterTemplates(),
            'tabEvents' => $this->parameterTabEvents(),
            'actionButtons' => $this->parameterActionButtons(),
        ];
    }

    /**
     * @return array
     */
    protected function parameterTemplates(): array
    {
        $templatesFromDb = Models\SiteTemplate::query()
            ->select('site_templates.templatename', 'site_templates.id', 'categories.category')
            ->leftJoin('categories', 'site_templates.category', '=', 'categories.id')
            ->orderBy('categories.category', 'ASC')
            ->orderBy('site_templates.templatename', 'ASC')
            ->get();
        $templates = [];
        $currentCategory = '';
        $templates['oldTmpId'] = 0;
        $templates['oldTmpName'] = '';
        $i = 0;
        foreach ($templatesFromDb->toArray() as $row) {
            $thisCategory = $row['category'];
            if ($row['category'] == null) {
                $thisCategory = $this->managerTheme->getLexicon('no_category');
            }
            if ($thisCategory != $currentCategory) {
                $i++;
                $templates['items'][$i] = [
                    'optgroup' => [
                        'name' => $thisCategory,
                        'options' => []
                    ]
                ];
            }
            if ($row['id'] == get_by_key($this->managerTheme->getCore()->config, 'default_template')) {
                $templates['oldTmpId'] = $row['id'];
                $templates['oldTmpName'] = $row['templatename'];
            }
            $templates['items'][$i]['optgroup']['options'][] = [
                'text' => $row['templatename'],
                'value' => $row['id']
            ];
            $currentCategory = $thisCategory;
        }

        return $templates;
    }

    /**
     * load languages and keys
     *
     * @return array
     */
    protected function parameterLang(): array
    {
        $lang_keys_select = [];
        $dir = dir(EVO_CORE_PATH . 'lang');
        while ($file = $dir->read()) {
            if (is_dir(EVO_CORE_PATH . 'lang/' . $file) && ($file != '.' && $file != '..')) {
                $lang_keys_select[$file] = $file;
            }
        }
        $dir->close();

        return $lang_keys_select;
    }

    /**
     * @return array
     */
    protected function parameterServerTimes(): array
    {
        $serverTimes = [];
        for ($i = -24; $i < 25; $i++) {
            $seconds = $i * 60 * 60;
            $serverTimes[$seconds] = [
                'value' => $seconds,
                'text' => $i
            ];
        }

        return $serverTimes;
    }

    /**
     * @return array
     */
    protected function parameterThemes(): array
    {
        $themeNames = [];
        $dir = dir(EVO_MANAGER_PATH . 'media/style/');
        while ($file = $dir->read()) {
            if (strpos($file, '.') === 0 || $file === 'common') {
                continue;
            }
            if (!is_dir(EVO_MANAGER_PATH . 'media/style/' . $file)) {
                continue;
            }

            $themeNames[] = $file;
        }
        $dir->close();

        natcasesort($themeNames);
        $themes = [];
        if (in_array('default', $themeNames, true)) {
            $themes['default'] = 'default';
        }
        foreach ($themeNames as $name) {
            if ($name === 'default') {
                continue;
            }
            $themes[$name] = $name;
        }

        return $themes;
    }

    /**
     * @return array
     */
    protected function parameterFileBrowsers(): array
    {
        $out = [];
        foreach (glob(EVO_MANAGER_PATH . 'media/browser/*', GLOB_ONLYDIR) as $dir) {
            $dir = str_replace('\\', '/', $dir);
            $out[] = substr($dir, strrpos($dir, '/') + 1);
        }

        return $out;
    }

    /**
     * @return array
     */
    protected function parameterSettings(): array
    {
        // reload system settings from the database.
        // this will prevent user-defined settings from being saved as system setting
        $out = array_merge(
            $this->managerTheme->getCore()->config,
            $this->managerTheme->getCore()
                ->getFactorySettings(),
            Models\SystemSetting::all()
                ->pluck('setting_value', 'setting_name')
                ->toArray()
        );

        foreach (config('cms.settings', []) as $key => $value) {
            if (isset($out[$key])) {
                $out[$key] = $value;
                $this->disabledSettings[$key] = true;
            }
        }

        $out['filemanager_path'] = str_replace(
            EVO_BASE_PATH,
            '[(base_path)]',
            get_by_key($out, 'filemanager_path')
        );

        $out['rb_base_dir'] = str_replace(
            EVO_BASE_PATH,
            '[(base_path)]',
            get_by_key($out, 'rb_base_dir')
        );

        if (!$this->parameterCheckGD()) {
            $out['use_captcha'] = 0;
        }

        return $out;
    }

    /**
     * @return array
     */
    protected function disabledSettings(): array
    {
        return $this->disabledSettings;
    }

    /**
     * @return bool
     */
    protected function parameterCheckGD(): bool
    {
        return extension_loaded('gd');
    }

    /**
     * @return array[]
     */
    protected function parameterPasswordHash(): array
    {
        $managerApi = $this->managerTheme->getCore()
            ->getManagerApi();

        return [
            'BLOWFISH_Y' => [
                'value' => 'BLOWFISH_Y',
                'text' => 'CRYPT_BLOWFISH_Y (salt &amp; stretch)',
                'disabled' => $managerApi->checkHashAlgorithm('BLOWFISH_Y') ? 0 : 1
            ],
            'BLOWFISH_A' => [
                'value' => 'BLOWFISH_A',
                'text' => 'CRYPT_BLOWFISH_A (salt &amp; stretch)',
                'disabled' => $managerApi->checkHashAlgorithm('BLOWFISH_A') ? 0 : 1
            ],
            'SHA512' => [
                'value' => 'SHA512',
                'text' => 'CRYPT_SHA512 (salt &amp; stretch)',
                'disabled' => $managerApi->checkHashAlgorithm('SHA512') ? 0 : 1
            ],
            'SHA256' => [
                'value' => 'SHA256',
                'text' => 'CRYPT_SHA256 (salt &amp; stretch)',
                'disabled' => $managerApi->checkHashAlgorithm('SHA256') ? 0 : 1
            ],
            'MD5' => [
                'value' => 'MD5',
                'text' => 'CRYPT_MD5 (salt &amp; stretch)',
                'disabled' => $managerApi->checkHashAlgorithm('MD5') ? 0 : 1
            ],
            'UNCRYPT' => [
                'value' => 'UNCRYPT',
                'text' => 'UNCRYPT(32 chars salt + SHA-1 hash)',
                'disabled' => $managerApi->checkHashAlgorithm('UNCRYPT') ? 0 : 1
            ],
        ];
    }

    /**
     * @return array
     */
    protected function parameterTabEvents(): array
    {
        $out = [];

        foreach ($this->tabEvents as $event) {
            $out[$event] = $this->callEvent($event);
        }

        return $out;
    }

    /**
     * @param string $name
     * @return array|bool|string
     */
    private function callEvent(string $name)
    {
        $out = $this->managerTheme->getCore()
            ->invokeEvent($name);
        if (is_array($out)) {
            $out = implode('', $out);
        }

        return $out;
    }

    /**
     * @return int[]
     */
    protected function parameterActionButtons(): array
    {
        return [
            'select' => 1,
            'save' => 1,
            'cancel' => 1
        ];
    }
}
