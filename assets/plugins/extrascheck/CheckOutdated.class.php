<?php

class CheckOutdated
{
    /**
     * @var string
     */
    public $pluginName = '';

    /** @var DocumentParser */
    protected $modx;

    /** @var int */
    protected $extrasId = 0;

    /** @var int */
    protected $pluginId = 0;

    /** @var Helpers\Lexicon */
    public $lang;

    /** @var DLTemplate */
    public $tpl;

    /**
     * @param DocumentParser $modx
     * @param string $pluginName
     * @param array $lang
     */
    public function __construct($modx, $pluginName, $lang = [])
    {
        $this->modx = $modx;
        $this->pluginName = $pluginName;
        $this->pluginId = $this->findPluginId();
        $this->extrasId = $this->findExtrasId();

        $this->tpl = DLTemplate::getInstance($this->modx);

        $this->setLang($lang);
    }

    /**
     * @param string $source
     * @return array
     */
    public function load($source)
    {
        if (0 === strpos($source, 'http')) {
            $data = Cache::remember('users', 24 * 60, function () use($source) {
                return json_decode(file_get_contents($source), true);
            });
        } else {
            $data = file_get_contents($source);
            $data = json_decode($data, true);
        }

        return \is_array($data) ? $data : [];
    }

    /**
     * @param string $name
     * @param array $options
     * @return string
     */
    public function snippet($name, $options)
    {
        $out = '';
        //search the snippet by name

        $snippet = \EvolutionCMS\Models\SiteSnippet::select('id', 'name', 'description')
            ->where('name', $name)->where('disabled', 0)->first();

        if (!is_null($snippet)) {
            $tpl = isset($options['template']) ? $options['template'] :
                '@CODE:<div class="widget-wrapper alert alert-warning">' .
                    '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' .
                    '[+snippet+] <b>[+name+]</b> (version [+currentVersion+]) ' .
                    ' [+isSecurity:empty:then=`[+isoutdated+] <b>Evolution [+evo_cms_version+]</b>`:else=`<u><b>[+security_reason+]</b></u>.`+]' .
                    '<br />[+please_update+] <b>[+name+]</b> [+to_latest+]' .
                    ' ([+min_required+] [+minVersion+]) [+from+]' .
                    ' <a target="main" href="[+extrasURL+]">[+extras_module+]</a> ' .
                    ' [+replaced:isnotempty=`[+or_move_to+]: <b>[+replaced+]</b>`+]' .
                '</div>';

            $minVersion = isset($options['version']) ? $options['version'] : 0;
            $replaced = isset($options['replaced']) ? implode('</b>, </b>', $options['replaced']) : '';
            $isSecurity = !empty($options['security']);

            //extract snippet version from description <strong></strong> tags
            $currentVersion = $this->getver($snippet->description, 'strong');
            //check snippet version and return an alert if outdated
            if (version_compare($currentVersion, $minVersion, 'lt')) {
                $out .= $this->parseTemplate(
                    $tpl,
                    [
                        'name' => $snippet->name,
                        'replaced' => $replaced,
                        'isSecurity' => $isSecurity,
                        'minVersion' => $minVersion,
                        'currentVersion' => $currentVersion,
                        'extrasID' => $this->getExtrasId(),
                        'extrasURL' => $this->getExtrasUrl($snippet->name)
                    ]
                );
            }

        }

        return $out;
    }

    /**
     * @param string $name
     * @param array $options
     * @return string
     */
    public function plugin($name, $options)
    {
        $out = '';
        //search the plugin by name

        $plugin = \EvolutionCMS\Models\SitePlugin::select('id', 'name', 'description')
            ->where('name', $name)->where('disabled', 0)->first();
        if (!is_null($plugin)) {
            $tpl = isset($options['template']) ? $options['template'] :
                '@CODE:<div class="widget-wrapper alert alert-warning">' .
                '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' .
                '[+plugin+] <b>[+name+]</b> (version [+currentVersion+]) ' .
                ' [+isSecurity:empty:then=`[+isoutdated+] <b>Evolution [+evo_cms_version+]</b>`:else=`<u><b>[+security_reason+]</b></u>.`+]' .
                '<br />[+please_update+] <b>[+name+]</b> [+to_latest+]' .
                ' ([+min_required+] [+minVersion+]) [+from+]' .
                ' <a target="main" href="[+extrasURL+]">[+extras_module+]</a> ' .
                ' [+replaced:isnotempty=`[+or_move_to+]: <b>[+replaced+]</b>`+]' .
                '</div>';

            $minVersion = isset($options['version']) ? $options['version'] : 0;
            $replaced = isset($options['replaced']) ? implode('</b>, </b>', $options['replaced']) : '';
            $isSecurity = !empty($options['security']);


            //extract snippet version from description <strong></strong> tags
            $currentVersion = $this->getver($plugin->description, 'strong');
            //check snippet version and return an alert if outdated
            if (version_compare($currentVersion, $minVersion, 'lt')) {
                $out .= $this->parseTemplate(
                    $tpl,
                    [
                        'name' => $plugin->name,
                        'replaced' => $replaced,
                        'isSecurity' => $isSecurity,
                        'minVersion' => $minVersion,
                        'currentVersion' => $currentVersion,
                        'extrasID' => $this->getExtrasId(),
                        'extrasURL' => $this->getExtrasUrl($plugin->name)
                    ]
                );
            }
        }

        return $out;
    }

    /**
     * @param string $name
     * @param array $options
     * @return string
     */
    public function module($name, $options)
    {
        $out = '';
        //search the module by name
        $module = \EvolutionCMS\Models\SiteModule::select('id', 'name', 'description')
            ->where('name', $name)->where('disabled', 0)->first();
        if (!is_null($module)) {
            $tpl = isset($options['template']) ? $options['template'] :
                '@CODE:<div class="widget-wrapper alert alert-warning">' .
                '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' .
                '[+lock_element_type_6+] <b>[+name+]</b> (version [+currentVersion+]) ' .
                ' [+isSecurity:empty:then=`[+isoutdated+] <b>Evolution [+evo_cms_version+]</b>`:else=`<u><b>[+security_reason+]</b></u>.`+]' .
                '<br />[+please_update+] <b>[+name+]</b> [+to_latest+]' .
                ' ([+min_required+] [+minVersion+]) [+from+]' .
                ' <a target="main" href="[+extrasURL+]">[+extras_module+]</a> ' .
                ' [+replaced:isnotempty=`[+or_move_to+]: <b>[+replaced+]</b>`+]' .
                '</div>';

            $minVersion = isset($options['version']) ? $options['version'] : 0;
            $replaced = isset($options['replaced']) ? implode('</b>, </b>', $options['replaced']) : '';
            $isSecurity = !empty($options['security']);

            while ($row = $this->modx->db->getRow($query)) {
                //extract snippet version from description <strong></strong> tags
                $currentVersion = $this->getver($module->description, 'strong');
                //check snippet version and return an alert if outdated
                if (version_compare($currentVersion, $minVersion, 'lt')) {
                    $out .= $this->parseTemplate(
                        $tpl,
                        [
                            'name' => $module->name,
                            'replaced' => $replaced,
                            'isSecurity' => $isSecurity,
                            'minVersion' => $minVersion,
                            'currentVersion' => $currentVersion,
                            'extrasID' => $this->getExtrasId(),
                            'extrasURL' => $this->getExtrasUrl($module->name)
                        ]
                    );
                }
            }
        }

        return $out;
    }

    /**
     * @param string $path
     * @param array $options
     * @return string
     */
    public function file($path, $options = [])
    {
        $out = '';
        if (file_exists(EVO_BASE_PATH . $path)) {
            $isSecurity = !empty($options['security']);

            $tpl = isset($options['template']) ? $options['template'] :
                '@CODE:<div class="widget-wrapper alert alert-danger">' .
                    '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' .
                    '<b>[+path+]</b> ' .
                    ' [+isSecurity:empty:then=`[+isoutdated+] <b>Evolution [+evo_cms_version+]</b>`:else=`<u><b>[+security_reason+]</b></u>.`+]' .
                    '<br />[+please_delete+]. [+if_dont_use+]' .
                '</div>';

            $out = $this->parseTemplate($tpl, compact('path', 'isSecurity'));
        }

        return $out;
    }

    /**
     * @param string $theme
     * @param array $options
     * @return string
     */
    public function theme($theme, $options = [])
    {
        $out = '';
        if (file_exists(EVO_MANAGER_PATH . 'media/style/' . $theme)) {
            $isSecurity = !empty($options['security']);

            $tpl = isset($options['template']) ? $options['template'] :
                '@CODE: <div class="widget-wrapper alert alert-danger">' .
                    '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' .
                    '[+manager_theme+] <b>[+theme+]</b> ' .
                    '[+isSecurity:empty:then=`[+isoutdated+] <b>Evolution [+evo_cms_version+]</b>`:else=`- <u><b>[+security_reason+]</b></u>.`+]' .
                    '<br />[+please_delete+] [+from_folder+] [+path+]. [+if_dont_use+]' .
                '</div>';

            $out = $this->parseTemplate(
                $tpl,
                [
                    'theme' => $theme,
                    'isSecurity' => $isSecurity,
                    'path' => EVO_MANAGER_PATH . 'media/style/'
                ]
            );
        }

        return $out;
    }

    /**
     * @param string $key
     * @param array $options
     * @return string
     */
    public function rss($key, $options)
    {
        $out = '';
        if (isset($options['old']) && $this->modx->getConfig($key) === $options['old']) {
            $tpl = isset($options['template']) ? $options['template'] :
                '@CODE:<div class="widget-wrapper alert alert-warning">' .
                    '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' .
                    '<b>[+settings_config+] > [+' . $key . '_title+]</b> ' .
                    '([+oldUrl+]) [+outdated+]. ' .
                    '[+please_download_and_install+] <b>UpdateEvosRss</b>  [+from+]' .
                    ' <a target="main" href="index.php?a=112&id=[+extrasID+]">[+extras_module+]</a>' .
                '</div>';

            $out = $this->parseTemplate(
                $tpl,
                [
                    'extrasID' => $this->getExtrasId(),
                    'oldUrl' => $options['old']
                ]
            );
        }

        return $out;
    }

    /**
     * @return int
     */
    public function getExtrasId()
    {
        return $this->extrasId;
    }

    /**
     * @param null $package
     * @return string
     */
    public function getExtrasUrl($package = null)
    {
        if ($this->getExtrasId() === 0) {
            $url = 'https://extras.evo.im/find/' . $package;
        } else {
            $url = 'index.php?a=112&id=' . $this->getExtrasId();
        }

        return $url;
    }

    /**
     * @return int
     */
    public function findExtrasId()
    {
        $moduleId = \EvolutionCMS\Models\SiteModule::select('id')->where('name', 'Extras')->where('disabled', 0)->first();
        if (!is_null($moduleId)) {
            return (int)$moduleId->id;
        }
        return 0;
    }

    /**
     * @return int
     */
    public function getPluginId()
    {
        return $this->pluginId;
    }

    /**
     * @return int
     */
    public function findPluginId()
    {
        $pluginId = \EvolutionCMS\Models\SitePlugin::select('id')->where('name', $this->pluginName)->where('disabled', 0)->first();
        if (!is_null($pluginId)) {
            return (int)$pluginId->id;
        }
        return 0;
    }

    /**
     * @param string $title
     * @return string
     */
    public function makeConfigButton($title)
    {
        $button = '';
        if ($this->modx->hasPermission('edit_plugin')) {
            $popup = [
                'url' => EVO_MANAGER_URL . '?a=102&id=' . $this->getPluginId() . '&tab=1',
                'title1' => $title,
                'icon' => 'fa-cog',
                'iframe' => 'iframe',
                'selector2' => '#tabConfig',
                'position' => 'center',
                'width' => '80%',
                'height' => '80%',
                'hide' => 0,
                'hover' => 0,
                'overlay' => 1,
                'overlayclose' => 1
            ];

            $button = '<a ' .
                    'data-toggle="tooltip" ' .
                    'href="javascript:;" '.
                    'title="' . $title . '" ' .
                    'class="text-muted pull-right" ' .
                    'onclick="parent.modx.popup(' .
                        str_replace('"', "'", stripslashes(json_encode($popup))) .
                    ')" ' .
                '>' .
                    '<i class="fa fa-cog fa-spin-hover"></i> ' .
                '</a>';
        }

        return $button;
    }

    /**
     * @param array $lexicon
     */
    public function setLang($lexicon = [])
    {
        $lang = $this->modx->getConfig('manager_language');
        if (file_exists( __DIR__ .  '/lang/'.$lang.'/core.inc.php')){
            include_once(__DIR__ .  '/lang/'.$lang.'/core.inc.php');
        } else {
            include_once(__DIR__ .  '/lang/english/core.inc.php');
        }
        $this->lang = $_lang;
        /*
        $this->lang = new Helpers\Lexicon($this->modx, array(
            'lang' => $lang,
            'langDir' => 'assets/plugins/extrascheck/lang/'
        ));

        if (!empty($lang)) {
            $this->lang->fromArray(array($lang => $lexicon));
        }
        $this->lang->loadLang();*/
    }

    /**
     * @param string $type
     * @param string $item
     * @param array $options
     * @return mixed
     */
    public function process($type, $item, $options)
    {
        $available = ['rss', 'file', 'theme', 'snippet', 'plugin', 'module'];

        if (\in_array($type, $available) && method_exists($this, $type)) {
            $out = $this->{$type}($item, $options);
        }
        return $out;
    }

    /**
     * @param string $tpl
     * @param array $data
     * @return string
     */
    public function parseTemplate($tpl, $data = [])
    {
        return $this->tpl->parseChunk(
            ($tpl),
            array_merge(
                ['evo_cms_version' => $this->modx->getVersionData('version')],
                $data,
                $this->lang
            ),
            true,
            false
        );
    }

    /**
     * function to extract snippet version from description <strong></strong> tags
     * @param $string
     * @param $tag
     * @return mixed
     */
    protected function getver($string, $tag)
    {
        $content = "/<$tag>(.*?)<\/$tag>/";
        preg_match($content, $string, $text);
        if(isset($text[1]))
        return $text[1];
        else return false;
    }
}
