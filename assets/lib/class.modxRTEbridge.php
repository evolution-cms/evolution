<?php
/**
 * @author Deesen, yama / updated: 27.01.2018
 */
if (!defined('MODX_BASE_PATH')) { die('What are you doing? Get out of here!'); }

class modxRTEbridge
{
    public $pluginName = '';                    // Name of plugin - nessecary to retrieve plugin-configuration by connectors
    public $editorKey = '';                     // Key for config/tpl/settings-files (ckeditor4, tinymce4, ...)
    public $theme = '';                         // Theme-key (default, simple, mini ... )
    public $pluginParams = array();             // Params from Modx plugin-configuration
    public $modxParams = array();               // Holds actual settings merged from Modx- and user-configuration
    public $bridgeParams = array();             // Holds translation of Modx Configuration-Keys to Editor Configuration-Keys
    public $themeConfig = array();              // Valid params and defaults for Editor
    public $tvOptions = array();                // Options set via TV-Option like {"theme":"mini"}
    public $initOnceArr = array();              // Holds custom HTML-Code to inject into tpl.xxxxxxxxx.init_once.html
    public $gSettingsCustom = array();          // Holds custom settings to enable setting via Modx- / user-configuration
    public $gSettingsDefaultValues = array();   // Holds default values for settings
    public $customPlaceholders = array();       // Holds placeholders to make available in all tpl.xxx.xxx.html
    public $langArr = array();                  // Holds lang strings
    public $debug = false;                      // Enable/disable debug messages via HTML-comment
    public $debugMessages = array();            // Holds all messages - added by    $this->debugMessages[] = 'Message';
    public $ajaxSecHash = array();              // Holds security-hashes
    private $mgrAction = null;

    public function __construct($editorKey = NULL, $bridgeConfig=array(), $tvOptions=array(), $basePath='')
    {
        global $settings, $usersettings;

        $modx = evolutionCMS();

        if ($editorKey == NULL) {
            exit('modxRTEbridge: No editorKey set in plugin-initialization.');
        };

        // Check right path
        $file = !empty($basePath) ? $basePath : __FILE__;
        $current_path = str_replace('\\', '/', dirname($file) . '/');
        if (strpos($current_path, MODX_BASE_PATH) !== false) {
            $path = substr($current_path, strlen(MODX_BASE_PATH));
            $basePath = MODX_BASE_PATH . $path;
            $baseUrl = MODX_BASE_URL . $path;
        } else exit('modxRTEbridge: Path-Error');

        // Object to pass vars between multiple plugin-events
        if(!isset($modx->modxRTEbridge)) $modx->modxRTEbridge = array();

        // Init language before bridge so bridge can alter translations via $this->setLang()
        $this->initLang($basePath);

        // Get modxRTEbridge-config from child-class
        $this->bridgeParams           = isset($bridgeConfig['bridgeParams']) ? $bridgeConfig['bridgeParams'] : array();
        $this->gSettingsCustom        = isset($bridgeConfig['gSettingsCustom']) ? $bridgeConfig['gSettingsCustom'] : array();
        $this->gSettingsDefaultValues = isset($bridgeConfig['gSettingsDefaultValues']) ? $bridgeConfig['gSettingsDefaultValues'] : array();

        $this->mgrAction = $modx->getService(EvolutionCMS\Interfaces\ManagerApiInterface::class)->action;

        // Determine settings from Modx
        $this->mgrAction = $this->mgrAction ? $this->mgrAction : 11;
        switch ($this->mgrAction) {
            // Create empty array()
            case 11:    // Create new user
                $editorConfig = array();
                break;
            // Get user-config
            case 12:    // Edit user
            case 119:   // Purge plugin processor
                $editorConfig = $usersettings;
                break;
            // Get Modx-config
            case 17:    // Modx-configuration
            default:
                $editorConfig = $settings;
                break;
        };

        // Modx default WYSIWYG-params
        $modxParamsArr = array(
            'theme', 'skin', 'skintheme', 'entermode', 'element_format', 'schema', 'css_selectors',
            'custom_plugins', 'custom_buttons1', 'custom_buttons2', 'custom_buttons3', 'custom_buttons4',
            'template_docs', 'template_chunks'
        );

        // Add defaultCheckbox-Values for user-settings
        $settingsRows = array();
        include($basePath . 'gsettings/gsettings.rows.inc.php');
        $this->gSettingsRows = $settingsRows;
        foreach($this->gSettingsRows as $param=>$row) {
            if(isset($row['defaultCheckbox']) && $row['defaultCheckbox']) {
                $useGlobalName = $editorKey . '_' . $param . '_useglobal';
                $this->modxParams[$param . '_useglobal'] = !isset($editorConfig[$useGlobalName]) || !empty($editorConfig[$useGlobalName]) || (isset($editorConfig[$useGlobalName]) && is_null($editorConfig[$useGlobalName])) ? '1' : '0';
            }
        }

        // Add custom settings from bridge
        foreach ($this->gSettingsCustom as $param => $row) {
            if (!in_array($param, $modxParamsArr)) $modxParamsArr[] = $param;
            // Handle defaultCheckbox
            if(isset($row['defaultCheckbox']) && $row['defaultCheckbox']) {
                $useGlobalName = $editorKey . '_' . $param . '_useglobal';
                $this->modxParams[$param . '_useglobal'] = !isset($editorConfig[$useGlobalName]) || !empty($editorConfig[$useGlobalName]) || (isset($editorConfig[$useGlobalName]) && is_null($editorConfig[$useGlobalName])) ? '1' : '0';
            }
        };

        // Take over editor-configuration from Modx
        foreach ($modxParamsArr as $p) {
            $useGlobalName = $p . '_useglobal';
            if (!in_array($this->mgrAction,array(11,12)) && isset($this->modxParams[$useGlobalName]) && $this->modxParams[$useGlobalName] == '1' && isset($modx->configGlobal[$editorKey . '_' . $p])) {
                $value = $modx->configGlobal[$editorKey . '_' . $p];
            }
            else {
                $value = isset($editorConfig[$editorKey . '_' . $p]) ? $editorConfig[$editorKey . '_' . $p] : null;
                $value = $value === null && isset($this->gSettingsDefaultValues[$p]) ? $this->gSettingsDefaultValues[$p] : $value;
            }
            $this->modxParams[$p] = $value;
        }

        // Set TV-options
        $this->tvOptions = $tvOptions;

        // Set pluginParams
        $this->editorKey                      = $editorKey;
        $this->theme                          = isset($this->modxParams['theme']) ? $this->modxParams['theme'] : 'base';
        $this->pluginParams                   = isset($modx->event->params) ? $modx->event->params : array();
        $this->pluginParams['pluginName']     = $modx->event->activePlugin;
        $this->pluginParams['editorLabel']    = isset($bridgeConfig['editorLabel']) ? $bridgeConfig['editorLabel'] : 'No editorLabel set for "' . $editorKey . '"';
        $this->pluginParams['editorVersion']  = isset($bridgeConfig['editorVersion']) ? $bridgeConfig['editorVersion'] : 'No editorVersion set';
        $this->pluginParams['editorLogo']     = isset($bridgeConfig['editorLogo']) ? $bridgeConfig['editorLogo'] : '';
        $this->pluginParams['skinsDirectory'] = isset($bridgeConfig['skinsDirectory']) && !empty($bridgeConfig['skinsDirectory']) ? trim($bridgeConfig['skinsDirectory'], "/") . "/" : '';
        $this->pluginParams['skinthemeDirectory'] = isset($bridgeConfig['skinthemeDirectory']) && !empty($bridgeConfig['skinthemeDirectory']) ? trim($bridgeConfig['skinthemeDirectory'], "/") . "/" : '';
        $this->pluginParams['base_path']      = $basePath;
        $this->pluginParams['base_url']       = $baseUrl;
    }

    // Function to set editor-parameters
    // $value = NULL deletes key completely from editor-config
    public function set($key, $value, $type=false, $emptyAllowed=false)
    {
        if ($value === NULL) {
            $this->themeConfig[$key] = NULL;    // Delete Parameter completely from JS-initialization
        } else {
            if(!isset($this->themeConfig[$key])) $this->themeConfig[$key] = array();
            $this->themeConfig[$key]['value']   = $value;
            $this->themeConfig[$key]['default'] = !isset($this->themeConfig[$key]['default']) ? $value : $this->themeConfig[$key]['default'];
            $this->themeConfig[$key]['type']    = $type == false ? 'string' : $type;
            $this->themeConfig[$key]['empty']   = $emptyAllowed;
        }
    }

    // Function to append string to existing parameters
    public function appendSet($key, $value, $separator = ',')
    {
        if ($value === '') { return; };

        if (isset($this->themeConfig[$key])) {
            $this->themeConfig[$key]['value'] .= $this->themeConfig[$key]['value'] != '' ? $separator.$value : $value;
        };
    }

    // Function to force editor-setting via plugin-code
    // $value = NULL deletes key completely from editor-config
    public function force($key, $value)
    {
        if ($value === NULL) {
            $this->themeConfig[$key] = NULL;  // Delete Parameter completely from JS-initialization
        } else {
            if(!isset($this->themeConfig[$key])) $this->themeConfig[$key] = array();
            $this->themeConfig[$key]['force'] = $value;
        }
    }

    // Function to append custom HTML-Code to tpl.editor.init_once.html
    public function appendInitOnce($str)
    {
        if (!in_array($str, $this->initOnceArr)) {  // Avoid doubling..
            $this->initOnceArr[] = $str;
        };
    }

    // Function to force pluginParams like "elements" via plugin-code
    // $value = NULL deletes key completely from editor-config
    public function setPluginParam($param, $value)
    {
        if ($value === NULL) {
            unset($this->pluginParams[$param]);  // Delete Parameter completely
        } else {
            $this->pluginParams[$param] = $value;
        }
    }

    // Function to set custom-placeholders like renders javascript-objects, arrays etc
    // $value = NULL deletes key completely from custom-placeholders
    public function setPlaceholder($ph, $value)
    {
        if ($value === NULL) {
            unset($this->customPlaceholders[$ph]);  // Delete placeholder completely
        } else {
            $this->customPlaceholders[$ph] = $value;
        }
    }

    // Function to get custom-placeholders
    public function getPlaceholder($ph)
    {
        return isset($this->customPlaceholders[$ph]) ? $this->customPlaceholders[$ph] : NULL;
    }

    // Set new/overwrite translations manually (via bridge)
    public function setLang($key, $string, $overwriteExisting = false)
    {
        if (is_array($string)) {
            $this->langArr = $overwriteExisting == false ? array_merge($this->langArr, $string) : array_merge($string, $this->langArr);
        } else {
            $this->langArr[$key] = isset($this->langArr[$key]) && $overwriteExisting == false ? $this->langArr[$key] : $string;
        };
    }

    // Get translation
    public function lang($key = '', $returnNull = false)
    {
        if (!$key) return $returnNull ? NULL : '';
        if (isset($this->langArr[$key])) return $this->langArr[$key];
        return $returnNull ? NULL : 'lang_' . $key;    // Show missing key as fallback
    }

    // Renders complete JS-Script
    public function getEditorScript()
    {
        $modx = evolutionCMS();
        $ph = array();
        $output = "<!-- modxRTEbridge {$this->editorKey} -->\n";

        // Init via elements
        if (isset($this->pluginParams['elements'])) {

            $this->pluginParams['elements'] = !is_array($this->pluginParams['elements']) ? explode(',', $this->pluginParams['elements']) : $this->pluginParams['elements']; // Allow setting via plugin-configuration

            // Allows bridging elements+TV-options etc before looping
            $this->renderBridgeParams('initBridge');

            // Now loop through tvs
            foreach ($this->pluginParams['elements'] as $selector) {

                $this->initTheme($selector);
                $this->renderBridgeParams($selector);

                // Prepare config output
                $ph = $this->prepareDefaultPlaceholders($selector);
                $ph = array_merge($ph, $this->customPlaceholders, $this->mergeParamArrays());   // Big list..

                // Init only once at all - Load Editors-Library, CSS etc
                if (!defined($this->editorKey . '_INIT_ONCE')) {
                    define($this->editorKey . '_INIT_ONCE', 1);
                    $output .= file_get_contents("{$this->pluginParams['base_path']}tpl/tpl.{$this->editorKey}.init_once.html") ."\n";
                    if (!empty($this->initOnceArr)) {
                        $output .= implode("\n", $this->initOnceArr);
                    }
                    // Provide JS-object with parameters for external scripts like MultiTV
                    $jsParams = array(
                        'default'=>'config_'.$this->editorKey.'_'.$this->modxParams['theme']
                    );
                    $output .= "<script>var modxRTEbridge_{$this->editorKey} = ". json_encode($jsParams) .";</script>";
                }

                // Init only once per config (enables multiple config-objects i.e. for richtext / richtextmini via [+configJs+])
                if (!defined($this->editorKey . '_INIT_CONFIG_' . $this->theme)) {
                    define($this->editorKey . '_INIT_CONFIG_' . $this->theme, 1);
                    $output .= file_get_contents("{$this->pluginParams['base_path']}tpl/tpl.{$this->editorKey}.config.html") ."\n";
                }

                // Loop through tvs
                $output .= file_get_contents("{$this->pluginParams['base_path']}tpl/tpl.{$this->editorKey}.init.html") ."\n";
                $output  = $modx->parseText($output, $ph);
                $output = str_replace('\\', '/', $output);
            }

        } else {
            // No elements given - create Config-Object only
            $this->theme = $this->tvOptions['theme'];
            $this->initTheme('noselector');
            $this->renderBridgeParams('noselector');

            // Prepare config output
            $ph = $this->prepareDefaultPlaceholders();
            $ph = array_merge($ph, $this->customPlaceholders, $this->mergeParamArrays());   // Big list..

            if (!defined($this->editorKey . '_INIT_CONFIG_' . $this->theme)) {
                define($this->editorKey . '_INIT_CONFIG_' . $this->theme, 1);
                $output .= file_get_contents("{$this->pluginParams['base_path']}tpl/tpl.{$this->editorKey}.config.html") ."\n";
                $output  = $modx->parseText($output, $ph);
            }
        }

        // Remove empty placeholders !
        $placeholderArr = $modx->getTagsFromContent($output, '[+', '+]');
        if (!empty($placeholderArr)) {
            foreach ($placeholderArr[1] as $key => $val) {
                $output = str_replace($placeholderArr[0][$key], '', $output);
                $this->debugMessages[] = 'Removed empty placeholder: '.$placeholderArr[1];
            }
        }

        $output .= $this->renderDebugMessages($ph);
        $output .= "<!-- / modxRTEbridge {$this->editorKey} -->\n";

        return $output;
    }

    /**
     * @return array
     */
    public function prepareDefaultPlaceholders($selector='', $render=true)
    {
        $modx = evolutionCMS();

        if($render) {
            $ph['configString']    = $this->renderConfigString();
            $ph['configRawString'] = $this->renderConfigRawString();
        }
        $ph['editorKey'] = $this->editorKey;
        $ph['themeKey'] = $this->theme;
        $ph['selector'] = $selector;
        $ph['documentIdentifier'] = $modx->documentIdentifier;
        $ph['base_path'] = MODX_BASE_PATH;
        $ph['base_url'] = MODX_BASE_URL;
        $ph['manager_path'] = MGR_DIR;
        $ph['site_manager_url'] = MODX_MANAGER_URL;
        $ph['which_browser'] = !empty($modx->config['which_browser']) ? $modx->config['which_browser'] : 'mcpuk';

        return $ph;
    }

    // Init/load theme
    public function initTheme($selector)
    {
        $modx = evolutionCMS();

        $this->theme = isset($this->tvOptions[$selector]['theme']) ? $this->tvOptions[$selector]['theme'] : $this->theme;

        // Load theme for user or webuser
        if ($modx->isBackend() || ((int)$_GET['quickmanagertv'] == 1 || isset($_SESSION['mgrValidated']))) {
            // User is logged into Manager
            // Load base first to assure Modx settings like entermode, editor_css_path are given set, can be overwritten in custom theme
            include("{$this->pluginParams['base_path']}theme/theme.{$this->editorKey}.base.inc.php");
            include("{$this->pluginParams['base_path']}theme/theme.{$this->editorKey}.{$this->theme}.inc.php");
            $this->pluginParams['language'] = !isset($this->pluginParams['language']) ? $this->lang('lang_code') : $this->pluginParams['language'];
        } else {
            // User is a webuser
            $webuserTheme = !empty($this->pluginParams['webTheme']) ? $this->pluginParams['webTheme'] : 'webuser';
            // Load base first or set EVERYTHING for webuser only in webuser-theme?
            // include("{$this->pluginParams['base_path']}theme/theme.{$this->editorKey}.base.inc.php");
            include("{$this->pluginParams['base_path']}theme/theme.{$this->editorKey}.{$webuserTheme}.inc.php");
            // @todo: determine user-language?
            $this->pluginParams['language'] = !isset($this->pluginParams['language']) ? $this->lang('lang_code') : $this->pluginParams['language'];
        }
    }

    // Call bridge-functions and receive optional bridged-values
    // $selector = "initBridge" allows executing bridging function without modifying $this->themeConfig
    public function renderBridgeParams($selector)
    {
        // Call functions - for optional translation of params/values via bridge.xxxxxxxxxx.inc.php
        foreach ($this->bridgeParams as $editorParam) {
            $bridgeFunction = 'bridge_'.$editorParam;
            if (method_exists($this, $bridgeFunction)) {     // Call function, get return
                $return = $this->$bridgeFunction($selector);
                if ($return !== NULL && isset($this->themeConfig[$editorParam]) && $selector !== 'initBridge') {
                    $this->themeConfig[$editorParam]['bridged'] = $return;
                }
            }
        }
        // Load Tv-Options as bridged-params
        if($selector !== 'initBridge') {
            foreach ($this->themeConfig as $key => $conf) {
                if (isset($this->tvOptions[$selector][$key]) && $key != 'theme') { // Issue #577
                    $this->themeConfig[$key]['bridged'] = $this->tvOptions[$selector][$key];
                }
            }
        }
    }

    // Renders String for initialization via JS
    public function renderConfigString()
    {
        $modx = evolutionCMS();

        $config = array();
        $defaultPhs = $this->prepareDefaultPlaceholders('',false);

        // Build config-string as per themeConfig
        $raw = '';
        foreach ($this->themeConfig as $key => $conf) {

            if ($conf === NULL) { continue; }; // Skip nulled parameters
            $value = $this->determineValue($key, $conf);
            if ($value === NULL) { continue; }; // Skip none-allowed empty settings

            $value = is_string($value) ? $modx->parseText($value, $defaultPhs) : $value; // Allow default-placeholders like [+which_browser+] in theme-param-values

            // Escape quotes
            if (!is_array($value) && strpos($value, "'") !== false && !in_array($conf['type'], array('raw','object','obj')) )
                $value = str_replace("'", "\\'", $value);

            // Determine output-type
            switch (strtolower($conf['type'])) {
                case 'string': case 'str':
                $config[$key] = "        {$key}:'{$value}'";
                break;
                case 'array': case 'arr':
                if (is_array($value)) { $value = "['" . implode("','", $value) . "']"; };
                $config[$key] = "        {$key}:{$value}";
                break;
                case 'boolean': case 'bool':
                $value = $value == true ? 'true' : 'false';
                $config[$key] = "        {$key}:{$value}";
                break;
                case 'json':
                    if (is_array($value)) $value = json_encode($value);
                    $config[$key] = "        {$key}:{$value}";
                    break;
                case 'int':
                case 'constant': case 'const':
                case 'number':   case 'num':
                case 'object':   case 'obj':
                $config[$key] = "        {$key}:{$value}";
                break;
                case 'raw':
                    $raw .= "{$value}\n";
                    break;
            };
        }

        return implode(",\n", $config) . $raw;
    }

    // Renders String for initialization via JS
    public function renderConfigRawString()
    {
        // Build config-string as per themeConfig
        $raw = '';
        foreach ($this->themeConfig as $key => $conf) {

            if ($conf === NULL) { continue; };  // Skip nulled parameters
            $value = $this->determineValue($key, $conf);
            if ($value === NULL) { continue; }; // Skip none-allowed empty settings

            if ($conf['type'] == 'raw') {
                $raw .= "{$value}\n";
                break;
            };
        };

        return $raw;
    }

    // Get final value of editor-config
    public function determineValue($key, $conf=NULL)
    {
        if($conf == NULL) { $conf = $this->themeConfig[$key]; };

        $value = isset($this->themeConfig[$key]['bridged']) ? $this->themeConfig[$key]['bridged'] : NULL;
        $value = $value === NULL && isset($this->themeConfig[$key]['force']) ? $this->themeConfig[$key]['force'] : $value;
        $value = $value === NULL ? $this->themeConfig[$key]['value'] : $value;

        if(!in_array($conf['type'], array('boolean','bool'))) {
            if ($value === '' && $conf['empty'] === false) {  // Empty values not allowed
                if ($conf['default'] === '') return NULL;    // Skip none-allowed empty setting
                $value = $conf['default'];
            };
        };

        return $value;
    }

    // Adds initilization before </body> for frontend-editors
    public function addEditorScriptToBody()
    {
        $modx = evolutionCMS();

        if (isset($_SESSION['usertype']) && $_SESSION['usertype'] == 'manager') {   // Show only when logged in manager
            // Add only once
            if (!defined($this->editorKey . '_ADDED_TO_BODY')) {
                define($this->editorKey . '_ADDED_TO_BODY', 1);
                $initJs = $this->getEditorScript();

                // @todo: How to avoid caching of plugins on event "OnParseDocument"?
                if (strpos($modx->documentOutput, "<!-- modxRTEbridge {$this->editorKey} -->") === false) { // Avoid double init if already cached..
                    if (strpos($modx->documentOutput, '</body>') !== false) {
                        // Append to <body>
                        $modx->documentOutput = str_replace('</body>', $initJs . "</body>", $modx->documentOutput);
                    } else {
                        // No <body> - append to source
                        $modx->documentOutput .= $initJs;
                    }
                };

            };
        };
    }

    /***************************************************************
     * SETTINGS PARTS
     * @todo: make options dynamic to add for example additional options to setting "schema" like html5-strict, html5-bla, or just to "html4 and html5"..
     ***************************************************************/

    // Outputs Modx- / user-configuration settings
    public function getModxSettings()
    {
        $modx = evolutionCMS();
        $params = &$this->pluginParams;

        if (defined('INTERFACE_RENDERED_' . $this->editorKey)) {
            return '';
        }
        define('INTERFACE_RENDERED_' . $this->editorKey, 1);

        // Avoid conflicts with older TinyMCE base configs, prepend editorKey to configKey like [+ckeditor4_custom_plugins+]
        $prependModxParams = array();
        foreach ($this->modxParams as $key => $val) {
            $prependModxParams[$this->editorKey . '_' . $key] = $val;
        }

        $ph = array_merge($prependModxParams, $params);

        // Prepare [+display+]
        $ph['display'] = ($_SESSION['browser'] === 'modern') ? 'table-row' : 'block';
        $ph['display'] = $modx->config['use_editor'] == 1 ? $ph['display'] : 'none';

        // Prepare setting "theme"
        $ph['theme_options'] = $this->getThemeNames();

        // Prepare setting "skin"
        $ph['skin_options'] = $this->getSkinNames();

        // Prepare setting "skin-theme"
        $ph['skintheme_options'] = $this->getSkinThemeNames();

        // Prepare setting "entermode_options"
        $entermode = !empty($ph[$this->editorKey . '_entermode']) ? $ph[$this->editorKey . '_entermode'] : 'p';
        $ph['entermode_options'] = '<label><input name="[+name+]" type="radio" value="p" ' . $this->checked($entermode == 'p') . '/>' . $this->lang('entermode_opt1') . '</label><br />';
        $ph['entermode_options'] .= '<label><input name="[+name+]" type="radio" value="br" ' . $this->checked($entermode == 'br') . '/>' . $this->lang('entermode_opt2') . '</label>';
        switch ($modx->getService(EvolutionCMS\Interfaces\ManagerApiInterface::class)->action) {
            case '11':
            case '12':
            case '119':
                $ph['entermode_options'] .= '<br />';
                $ph['entermode_options'] .= '<label><input name="[+name+]" type="radio" value="" ' . $this->checked(empty($params[$this->editorKey . '_entermode'])) . '/>' . $this->lang('theme_global_settings') . '</label><br />';
                break;
        }

        // Prepare setting "element_format_options"
        $element_format = !empty($ph[$this->editorKey . '_element_format']) ? $ph[$this->editorKey . '_element_format'] : 'xhtml';
        $ph['element_format_options'] = '<label><input name="[+name+]" type="radio" value="xhtml" ' . $this->checked($element_format == 'xhtml') . '/>XHTML</label><br />';
        $ph['element_format_options'] .= '<label><input name="[+name+]" type="radio" value="html" ' . $this->checked($element_format == 'html') . '/>HTML</label>';
        switch ($this->mgrAction) {
            case '11':
            case '12':
            case '119':
                $ph['element_format_options'] .= '<br />';
                $ph['element_format_options'] .= '<label><input name="[+name+]" type="radio" value="" ' . $this->checked(empty($params[$this->editorKey . '_element_format'])) . '/>' . $this->lang('theme_global_settings') . '</label><br />';
                break;
        }

        // Prepare setting "schema_options"
        $schema = !empty($ph[$this->editorKey . '_schema']) ? $ph[$this->editorKey . '_schema'] : 'html5';
        $ph['schema_options'] = '<label><input name="[+name+]" type="radio" value="html4" ' . $this->checked($schema == 'html4') . '/>HTML4(XHTML)</label><br />';
        $ph['schema_options'] .= '<label><input name="[+name+]" type="radio" value="html5" ' . $this->checked($schema == 'html5') . '/>HTML5</label><br />';
        $ph['schema_options'] .= '<label><input name="[+name+]" type="radio" value="html5-strict" ' . $this->checked($schema == 'html5-strict') . '/>HTML5-strict</label>';
        switch ($this->mgrAction) {
            case '11':
            case '12':
            case '119':
                $ph['schema_options'] .= '<br />';
                $ph['schema_options'] .= '<label><input name="[+name+]" type="radio" value="" ' . $this->checked(empty($params[$this->editorKey . '_schema'])) . '/>' . $this->lang('theme_global_settings') . '</label><br />';
                break;
        };

        // Prepare settings rows output
        include($params['base_path'] . 'gsettings/gsettings.rows.inc.php');
        $settingsRowTpl = file_get_contents("{$params['base_path']}gsettings/gsettings.row.inc.html");
        $settingsRows = isset($settingsRows) ? array_merge($settingsRows, $this->gSettingsCustom) : $this->gSettingsCustom;

        $ph['rows'] = '';
        foreach ($settingsRows as $name => $row) {

            if ($row == NULL) {
                continue;
            };     // Skip disabled config-settings

            $row = array_merge($this->langArr, $row);

            $row['name'] = $this->editorKey . '_' . $name;
            $row['editorKey'] = $this->editorKey;
            $row['title'] = $this->lang($row['title']);
            $row['message'] = $this->lang($row['message']);
            $row['messageVal'] = !empty($row['messageVal']) ? $row['messageVal'] : '';

            // Prepare displaying of default values
            $row['default'] = isset($this->gSettingsDefaultValues[$name]) ? '<span class="default-val" style="margin:0.5em 0;display:block">' . $this->lang('default') . '<i>' . $this->gSettingsDefaultValues[$name] . '</i></span>' : '';

            // Prepare Default-Checkboxes for user-settings
            if(in_array($this->mgrAction, array(11,12)) && isset($row['defaultCheckbox']) && $row['defaultCheckbox']) {
                $useGlobalName          = $name . '_useglobal';
                $useGlobal              = is_null($this->modxParams[$useGlobalName]) || !empty($this->modxParams[$useGlobalName]) ? '1' : '0';
                $useGlobalBool          = $useGlobal ? true : false;
                $row['defaultCheckbox'] = '<label><input class="defaultCheckbox" type="checkbox" id="' . $useGlobalName . '" ' . $this->checked($useGlobalBool) . '>' . $this->lang('theme_global_settings') . '</label><input id="' . $useGlobalName . '_hidden" name="' . $this->editorKey .'_'. $useGlobalName . '" value="' . $useGlobal . '" type="hidden" />';
            } else {
                $row['defaultCheckbox'] = '';
            }

            // Nested parsing
            $output = $settingsRowTpl;
            $bt=md5('');
            while($bt !== md5($output)) {
                $bt = md5($output);
                $output = $this->parsePlaceholders($output, $row); // Replace general translations
                $output = $this->parsePlaceholders($output, $ph);  // Replace values / settings
            }

            $ph['rows'] .= $output . "\n";
        };

        $settingsBody = file_get_contents("{$params['base_path']}gsettings/gsettings.body.inc.html");

        $ph['editorLogo'] = !empty($this->pluginParams['editorLogo']) ? '<img src="' . $this->pluginParams['base_url'] . $this->pluginParams['editorLogo'] . '" style="max-height:50px;width:auto;margin-right:50px;" />' : '';

        $settingsBody = $this->parsePlaceholders($settingsBody, $ph);
        $settingsBody = $this->replaceTranslations($settingsBody);

        return $settingsBody;
    }

    public function parsePlaceholders($content, $ph) {
        foreach($ph as $key=>$value) $content = str_replace('[+'.$key.'+]', $value, $content);
        return $content;
    }

    // Replace all translation-placeholders
    public function replaceTranslations($output)
    {
        $modx = evolutionCMS();

        $placeholderArr = $modx->getTagsFromContent($output, '[+', '+]');
        if (!empty($placeholderArr)) {
            foreach ($placeholderArr[1] as $key => $val) {
                $trans = $this->lang($val, true);

                if ($trans !== NULL)
                    $output = str_replace($placeholderArr[0][$key], $trans, $output);
            };
        };
        return $output;
    }

    // helpers for getModxSettings()
    public function getThemeNames()
    {
        $modx = evolutionCMS();
        $params = $this->pluginParams;

        $themeDir = "{$params['base_path']}theme/";

        switch ($this->mgrAction) {
            case '11':
            case '12':
            case '119':
                $selected = $this->selected(empty($params[$this->editorKey . '_skin']));
                $option[] = '<option value=""' . $selected . '>' . $this->lang('theme_global_settings') . '</option>';
                break;
        }

        foreach (glob("{$themeDir}*") as $file) {
            //$file = str_replace('\\', '/', $file);
            $file = str_replace($themeDir, '', $file);
            $file = str_replace('theme.' . $this->editorKey . '.', '', $file);

            if(in_array($file,array('index.html'))) continue;

            $theme = trim(str_replace('.inc.php', '', $file));
            if ($theme == 'base') continue; // Why should user select base-theme?
            $label = $this->lang("theme_{$theme}", true) ? $this->lang("theme_{$theme}") : $theme; // Get optional translation or show raw themeKey
            $selected = $this->selected($theme == $this->modxParams['theme']);

            $label = $modx->parseText($label, $this->pluginParams);   // Enable [+editorLabel+] in options-label

            $option[] = '<option value="' . $theme . '"' . $selected . '>' . "{$label}</option>";
        }

        return isset($option) && is_array($option) ? implode("\n", $option) : '<!-- ' . $this->editorKey . ': No themes found -->';
    }

    public function getSkinNames()
    {
        $params = $this->pluginParams;

        if (empty($params['skinsDirectory'])) {
            return '<option value="">No skinsDirectory set</option>';
        };

        $skinDir = "{$params['base_path']}{$params['skinsDirectory']}";

        switch ($this->mgrAction) {
            case '11':
            case '12':
            case '119':
                $selected = $this->selected(empty($params[$this->editorKey . '_skin']));
                $option[] = '<option value=""' . $selected . '>' . $this->lang('theme_global_settings') . '</option>';
                break;
        }
        foreach (glob("{$skinDir}*", GLOB_ONLYDIR) as $dir) {
            //$dir = str_replace('\\', '/', $dir);
            $skin_name = substr($dir, strrpos($dir, '/') + 1);
            $skins[$skin_name][] = 'default';
            $styles = glob("{$dir}/ui_*.css");
            if (is_array($styles) && 0 < count($styles)) {
                foreach ($styles as $css) {
                    $skin_variant = substr($css, strrpos($css, '_') + 1);
                    $skin_variant = substr($skin_variant, 0, strrpos($skin_variant, '.'));
                    $skins[$skin_name][] = $skin_variant;
                }
            }
            foreach ($skins as $k => $o) ;
            {
                foreach ($o as $v) {
                    if ($v === 'default') $value = $k;
                    else               $value = "{$k}:{$v}";
                    $selected = $this->selected($value == $this->modxParams['skin']);
                    $option[] = '<option value="' . $value . '"' . $selected . '>' . "{$value}</option>";
                }
            }
        }

        return is_array($option) ? implode("\n", $option) : '<!-- ' . $this->editorKey . ': No skins found -->';
    }

    public function getSkinThemeNames()
    {
        $params = $this->pluginParams;

        $themeDir = "{$params['base_path']}{$params['skinthemeDirectory']}";

        switch ($this->mgrAction) {
            case '11':
            case '12':
            case '119':
                $selected = $this->selected(empty($params[$this->editorKey . '_skintheme']));
                $option[] = '<option value=""' . $selected . '>' . $this->lang('theme_global_settings') . '</option>';
                break;
        }

        foreach (glob("{$themeDir}*") as $theme) {
            //$file = str_replace('\\', '/', $file);
            $theme = str_replace($themeDir, '', $theme);

            if(in_array($theme,array('index.html'))) continue;

            $selected = $this->selected($theme == $this->modxParams['skintheme']);

            $option[] = '<option value="' . $theme . '"' . $selected . '>' . "{$theme}</option>";
        }

        return isset($option) && is_array($option) ? implode("\n", $option) : '<!-- ' . $this->editorKey . ': No themes found -->';
    }

    public function selected($cond = false)
    {
        if ($cond !== false) return ' selected="selected"';
        else                return '';
    }
    public function checked($cond = false)
    {
        if ($cond !== false) return ' checked="checked"';
        else                return '';
    }



    // Init translations
    public function initLang($basePath)
    {
        global $_lang;
        $modx = evolutionCMS();

        // Init langArray once
        if (empty($this->langArr)) {
            $lang_name = !empty($_SESSION['mgrUsrConfigSet']['manager_language']) ? $_SESSION['mgrUsrConfigSet']['manager_language'] : $modx->config['manager_language'];
            $gsettings_path = $basePath . "lang/gsettings/";     // Holds general translations
            $custom_path = $basePath . "lang/custom/";        // Holds custom translations
            $lang_file = $lang_name . '.inc.php';
            $fallback_file = 'english.inc.php';
            $lang_code = '';

            // Load gsettings fallback language (show at least english translations instead of empty)
            if (is_file($gsettings_path . $fallback_file)) include($gsettings_path . $fallback_file);
            if (isset($_lang['lang_code'])) $lang_code = $_lang['lang_code'];    // Set langcode for RTE

            // Load gsettings user language
            if (is_file($custom_path . $fallback_file)) include($custom_path . $fallback_file);
            if (isset($_lang['lang_code'])) $lang_code = $_lang['lang_code'];    // Set langcode for RTE

            // Load custom settings fallback language
            if (is_file($gsettings_path . $lang_file)) include($gsettings_path . $lang_file);
            if (isset($_lang['lang_code'])) $lang_code = $_lang['lang_code'];    // Set langcode for RTE

            // Load custom settings user language
            if (is_file($custom_path . $lang_file)) include($custom_path . $lang_file);
            if (isset($_lang['lang_code'])) $lang_code = $_lang['lang_code'];    // Set langcode for RTE

            $this->langArr = $_lang;
            $this->langArr['lang_code'] = $lang_code;
        };
    }

    // Merges all available config-params with prefixes into single array
    public function mergeParamArrays()
    {
        $p = array();
        foreach($this->pluginParams as $param=>$value) { $p['pp.'.$param] = is_array($value) ? implode(',',$value) : $value; };
        foreach($this->modxParams   as $param=>$value) { $p['mp.'.$param] = is_array($value) ? implode(',',$value) : $value; };
        foreach($this->themeConfig  as $param=>$arr) {
            if (isset($arr['force'])) $p['tc.' . $param] = $arr['force'];
            elseif (isset($arr['bridged'])) $p['tc.' . $param] = $arr['bridged'];
            else $p['tc.' . $param] = $arr['value'];
        };
        foreach($this->gSettingsDefaultValues as $param=>$value) { $p['gd.'.$param] = is_array($value) ? implode(',',$value) : $value; };
        foreach($this->langArr as $param=>$value)      { $p['l.'.$param] = $value; };
        return $p;
    }

    // Get PluginConfiguration by Connectors
    public function getModxPluginConfiguration($pluginName)
    {
        $modx = evolutionCMS();

        if( $pluginName != NULL ) {
            if (empty ($modx->config)) { $modx->getSettings(); };
            $modx->db->connect();

            $plugin = $modx->getPluginCode($pluginName);
            $parameter = $modx->parseProperties($plugin['props'], $pluginName, 'plugin');

            if (is_array($parameter)) {
                $this->pluginParams = array_merge($parameter, $this->pluginParams);
            };
        };
        return $this->pluginParams;
    }

    // Remove all but numbers
    public function onlyNumbers($string)
    {
        return preg_replace("/[^0-9]/", "", $string); // Remove px, % etc
    }

    // Helper to translate "bold,strike,underline,italic" to "bold","strike","underline","italic"
    // Translates Modx Plugin-configuration strings to JSON-compatible string
    public function addQuotesToCommaList($str, $quote = '"')
    {
        if (empty($str)) { return ''; }

        $elements = explode(',', $str);
        foreach ($elements as $key => $val) {
            $elements[$key] = $quote . trim($val) . $quote;
        };
        return implode(',', $elements);
    }

    public function parseEditableIds($source, $attrContentEditable=false)
    {
        if(!isset($_SESSION['mgrValidated'])) return $source;
        $attrContentEditable = $attrContentEditable == true ? ' contenteditable="true"' : '';

        $matchPhs = '~\[\*#(.*?)\*\]~'; // match [*#content*] / content
        preg_match_all($matchPhs, $source, $editableIds);

        $this->setEditableIds($editableIds);

        $source = preg_replace($matchPhs, '<div class="editable" id="modx_$1"'.$attrContentEditable.'>[*$1*]</div>', $source);

        return $source;
    }

    public function setEditableIds($editableIds)
    {
        $modx = evolutionCMS();

        if(!empty($editableIds) && isset($editableIds[1])) {
            foreach ($editableIds[1] as $i=>$id)
                $modx->modxRTEbridge['editableIds'][$id] = '';
        }
    }

    // Helper to avoid Placeholder-/Snippet-Execution for Frontend-Editors
    public function protectModxPhs()
    {
        $modx = evolutionCMS();

        if(isset($modx->modxRTEbridge['editableIds']) && isset($_SESSION['mgrValidated'])) {
            foreach ($modx->modxRTEbridge['editableIds'] as $modxPh=>$x) {
                if (isset($modx->documentObject[$modxPh]))
                    $modx->documentObject[$modxPh] = $this->protectModxPlaceholders($modx->documentObject[$modxPh]);
            }
        }
    }

    public function protectModxPlaceholders($output)
    {
        return str_replace(
            array('[*',     '*]',     '[(',     ')]',     '{{',           '}}',           '[[',         ']]',         '[!',     '!]',     '[+',     '+]',     '[~',     '~]'),
            array('&#91;*', '*&#93;', '&#91;(', ')&#93;', '&#123;&#123;', '&#125;&#125;', '&#91;&#91;', '&#93;&#93;', '&#91;!', '!&#93;', '&#91;+', '+&#93;', '&#91;~', '~&#93;'),
            $output
        );
    }
    public function unprotectModxPlaceholders($output)
    {
        return str_replace(
            array('&#91;*', '*&#93;', '&#91;(', ')&#93;', '&#123;&#123;', '&#125;&#125;', '&#91;&#91;', '&#93;&#93;', '&#91;!', '!&#93;', '&#91;+', '+&#93;', '&#91;~', '~&#93;'),
            array('[*',     '*]',     '[(',     ')]',     '{{',           '}}',           '[[',         ']]',         '[!',     '!]',     '[+',     '+]',     '[~',     '~]'),
            $output
        );
    }

    public function prepareAjaxSecHash($docId)
    {
        if(isset($this->ajaxSecHash[$docId])) return $this->ajaxSecHash[$docId];

        $secHash = md5(rand(0, 999999999) + rand(0, 999999999));
        $_SESSION['modxRTEbridge']['secHash'][$docId] = $secHash;
        $this->ajaxSecHash[$docId] = $secHash;

        return $secHash;
    }

    // Handle debug-modes
    public function setDebug($state)
    {
        if($state == 'full') $this->debug = 'full';
        else if($state != false) $this->debug = true;
        else $this->debug = false;
    }

    public function renderDebugMessages($placeholderArr) {
        $output = '';
        if($this->debug)
        {
            $output .= "<!-- ##### modxRTEbridge Debug Infos #########\n";
            $output .= " - ". implode("\n - ", $this->debugMessages);

            if($this->debug == 'full') {
                $output .= "this->modxParams = ".print_r($this->modxParams, true)."\n";
                $output .= "this->pluginParams = ".print_r($this->pluginParams, true)."\n";
                $output .= "this->themeConfig = ".print_r($this->themeConfig, true)."\n";
                $output .= "this->gSettingsCustom = ".print_r($this->gSettingsCustom, true)."\n";
                $output .= "this->gSettingsDefaultValues = ".print_r($this->gSettingsDefaultValues, true)."\n";
                $output .= "ph = ".print_r($placeholderArr, true)."\n";
            };

            $output .= "\n     ##### modxRTEbridge Debug Infos End ##### -->\n";
        }
        return $output;
    }

    /***************************************************************
     * Connectors
     **************************************************************/
    public function getTemplateChunkList()
    {
        $modx = evolutionCMS();

        $templatesArr = array();

        if ($modx->getLoginUserType() === 'manager') {

            $modx->getSettings();
            $ids    = $modx->config[$this->editorKey.'_template_docs'];
            $chunks = $modx->config[$this->editorKey.'_template_chunks'];
            $templatesArr = array();

            if (!empty($ids)) {
                $docs = $modx->getDocuments($modx->db->escape($ids), 1, 0, $fields = 'id,pagetitle,menutitle,description,content');
                foreach ($docs as $i => $a) {
                    $newTemplate = array(
                        'title'=>($docs[$i]['menutitle'] !== '') ? $docs[$i]['menutitle'] : $docs[$i]['pagetitle'],
                        'description'=>$docs[$i]['description'],
                        'content'=>$docs[$i]['content']
                    );
                    $templatesArr[] = $newTemplate;
                }
            }

            if (!empty($chunks)) {
                $tbl_site_htmlsnippets = $modx->getFullTableName('site_htmlsnippets');
                if (strpos($chunks, ',') !== false) {
                    $chunks  = array_filter(array_map('trim', explode(',', $chunks)));
                    $chunks  = $modx->db->escape($chunks);
                    $chunks  = implode("','", $chunks);
                    $where   = "`name` IN ('{$chunks}')";
                    $orderby = "FIELD(name, '{$chunks}')";
                } else {
                    $where   = "`name`='{$chunks}'";
                    $orderby = '';
                }

                $rs = $modx->db->select('id,name,description,snippet', $tbl_site_htmlsnippets, $where, $orderby);

                while ($row = $modx->db->getRow($rs)) {
                    $newTemplate = array(
                        'title'=>$row['name'],
                        'description'=>$row['description'],
                        'content'=>$row['snippet']
                    );
                    $templatesArr[] = $newTemplate;
                }
            }
        }
        return $templatesArr;
    }

    public function saveContentProcessor($rid, $ppPluginName, $ppEditableIds='editableIds')
    {
        $modx = evolutionCMS();

        if ($rid > 0 && $modx->getLoginUserType() === 'manager')
        {
            if(!isset($_POST['secHash']) ||
                !isset($_SESSION['modxRTEbridge']['secHash'][$rid]) ||
                $_POST['secHash'] != $_SESSION['modxRTEbridge']['secHash'][$rid]) return 'secHash invalid';

            $editableIds = explode(',', $_POST['phs']);

            if($editableIds) {
                include_once(MODX_BASE_PATH . "assets/lib/MODxAPI/modResource.php");

                $modx->doc = new modResource($modx);
                $modx->doc->edit($rid);

                foreach ($editableIds as $modxPh) {
                    if (isset($_POST[$modxPh]) && $_POST[$modxPh] != 'undefined') // Prevent if Javascript returned "undefined"
                        $modx->doc->set($modxPh, $this->unprotectModxPlaceholders($_POST[$modxPh]));
                };
                return $modx->doc->save(true, true);    // Returns ressource-ID
            }

            return 'editableIds not given in plugin-configuration with config-key "'. $ppEditableIds .'"';

        } else {
            return 'Not logged into manager!';
        }
    }
}
