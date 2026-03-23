<?php namespace EvolutionCMS\Traits;

trait Settings
{
    /**
     * @var array
     */
    public $config = [];

    /**
     * Needs only for the configCompatibility hack
     * @var array
     */
    protected $saveConfig = [];

    protected $casts = [
        'error_reporting' => 'int',
        'site_start' => 'int',
        'error_page' => 'int',
        'cache_type' => 'int',
        'unauthorized_page' => 'int',
        'server_offset_time' => 'int',
        'site_unavailable_page' => 'int',
        'enable_robot_indexing' => 'int',
        'site_status' => 'bool',
        'use_alias_path' => 'bool',
        'seostrict' => 'bool',
        'make_folders' => 'bool',
        'friendly_urls' => 'bool',
        'xhtml_urls' => 'bool',
        'aliaslistingfolder' => 'bool',
        'friendly_alias_urls' => 'bool',
        'enable_cache' => 'float',
        'enable_at_syntax' => 'bool',
        'enable_filter' => 'bool',
        'use_udperms' => 'bool',
        'manager_menu_height' => 'float',
        'manager_tree_width' => 'float',
        'show_picker' => 'bool',
        'show_fullscreen_btn' => 'bool',
        'export_includenoncache' => 'bool',
        'number_of_results' => 'int',
        'manager_theme_mode' => 'int',
        'manager_login_startup' => 'int',
        'global_tabs' => 'bool',
        'remember_last_tab' => 'bool',
        'show_newresource_btn' => 'bool',
        'use_browser' => 'bool',
        'warning_visibility' => 'bool',
        'use_captcha' => 'bool',
        'tree_page_click' => 'int',
        'allow_multiple_emails' => 'bool',
        'use_editor' => 'bool',
        'group_tvs' => 'int',
        'jpegQuality' => 'int',
        'thumbHeight' => 'int',
        'maxImageHeight' => 'int',
        'maxImageWidth' => 'int',
        'denyExtensionRename' => 'bool',
        'denyZipDownload' => 'bool',
        'showHiddenFiles' => 'bool',
        'new_folder_permissions' => 'string', //Don't set int
        'new_file_permissions' => 'string', //Don't set int
        'upload_maxsize' => 'int',
        'noThumbnailsRecreation' => 'bool',
        'clientResize' => 'bool',
        'publish_default' => 'bool',
        'search_default' => 'bool',
        'cache_default' => 'bool',
        'auto_menuindex' => 'bool',
        'track_visitors' => 'bool'
    ];

    /**
     * @param $name
     * @param $value
     */
    public function setConfig($name, $value)
    {
        $this->config[$name] = $value;
    }

    /**
     * @return array
     */
    public function allConfig()
    {
        return array_merge(
            $this->config,
            $this['config']->get('cms.settings', [])
        );
    }
    /**
     * {@inheritdoc}
     */
    public function getConfig($name = '', $default = null)
    {
        $config = get_by_key(
            $this->config,
            $name,
            ''
        );

        if ($config === '') {
            $config = $default;
        }

        $value = $this['config']->get(
            'cms.settings.' . $name,
            $config
        );

        return $this->castAttribute($name, $value);
    }

    /**
     * Get Evolution CMS settings including, but not limited to, the system_settings table
     */
    public function getSettings()
    {
        $this->config = array_merge($this->getFactorySettings(), $this->config);

        // setup default site id - new installation should generate a unique id for the site.
        if ($this->getConfig('site_id', '') === '') {
            $this->setConfig('site_id', 'MzGeQ2faT4Dw06+U49x3');
        }

        // store base_url and base_path inside config array
        $this->setConfig('base_url', EVO_BASE_URL);
        $this->setConfig('base_path', EVO_BASE_PATH);
        $this->setConfig('site_url', EVO_SITE_URL);
        $this->setConfig('site_manager_path', EVO_MANAGER_PATH);
        $this->error_reporting = $this->getConfig('error_reporting');
        $this->setConfig(
            'filemanager_path',
            str_replace(
                '[(base_path)]',
                EVO_BASE_PATH,
                $this->getConfig('filemanager_path')
            )
        );
        $this->setConfig(
            'snapshot_path',
            str_replace(
                '[(base_path)]',
                EVO_BASE_PATH,
                $this->getConfig('snapshot_path')
            )
        );
        $this->setConfig(
            'rb_base_dir',
            str_replace(
                '[(base_path)]',
                EVO_BASE_PATH,
                $this->getConfig('rb_base_dir')
            )
        );
    }

    public function getFactorySettings() : array
    {
        $out = include EVO_CORE_PATH . 'factory/settings.php';
        return \is_array($out) ? $out : [];
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * @see \Illuminate\Database\Eloquent\Concerns\HasAttributes::castAttribute
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if ($value === null) {
            return $value;
        }

        switch ($this->getCastType($key)) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            default:
                return $value;
        }
    }

    protected function getCastType($key)
    {
        return isset($this->casts[$key]) ? trim(strtolower($this->casts[$key])) : null;
    }

    /**
     * Hack for compatibility Laravel with EvolutionCMS
     * @TODO: This is dirty code. Any ideas?
     *
     * @return array
     */
    protected function configCompatibility() : array
    {
        return array_merge($this->config, ['view' => $this['config']->get('view')]);
    }
}
