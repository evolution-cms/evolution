<?php namespace EvolutionCMS\Controllers;

use EvolutionCMS\Interfaces\ManagerTheme;
use EvolutionCMS\Legacy;
use EvolutionCMS\Models;
use EvolutionCMS\Support\SiteTimezone;
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
            'siteTimezones' => $this->parameterSiteTimezones(),
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
     * Build the selectable IANA timezone identifiers.
     *
     * @since 3.5.8
     */
    protected function parameterSiteTimezones(): array
    {
        $timezones = [];
        foreach (SiteTimezone::identifiers() as $timezone) {
            $timezones[$timezone] = $timezone;
        }

        return $timezones;
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

        // Show which algorithm is actually in force. A site carrying a pre-3.5 value
        // (BLOWFISH_Y, UNCRYPT, ...) hashes with bcrypt, so bcrypt is what the radio
        // group must show as selected rather than nothing at all.
        if (!array_key_exists((string) get_by_key($out, 'pwd_hash_algo'), $this->parameterPasswordHash())) {
            $out['pwd_hash_algo'] = Legacy\PasswordHash::ALGO_BCRYPT;
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

        $out['site_timezone'] = SiteTimezone::resolve($out['site_timezone'] ?? null);

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

        // Only algorithms that password_hash() can actually produce are offered. The
        // pre-3.5 names (BLOWFISH_*, SHA*, MD5, UNCRYPT) described the legacy "v1"
        // scheme, which is verify-only now: a stored v1 hash carries its own algorithm
        // name, so nothing needs to select it here. Sites still holding one of those
        // values keep working — PasswordHash::resolveAlgorithm() maps them to bcrypt.
        return [
            'BCRYPT' => [
                'value' => 'BCRYPT',
                'text' => 'bcrypt (cost ' . \EvolutionCMS\Legacy\PasswordHash::BCRYPT_COST . ')',
                'disabled' => $managerApi->checkHashAlgorithm('BCRYPT') ? 0 : 1
            ],
            'ARGON2ID' => [
                'value' => 'ARGON2ID',
                'text' => 'Argon2id (memory-hard, recommended)',
                'disabled' => $managerApi->checkHashAlgorithm('ARGON2ID') ? 0 : 1
            ],
            'ARGON2I' => [
                'value' => 'ARGON2I',
                'text' => 'Argon2i',
                'disabled' => $managerApi->checkHashAlgorithm('ARGON2I') ? 0 : 1
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
