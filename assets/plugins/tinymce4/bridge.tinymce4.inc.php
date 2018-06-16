<?php
/* TinyMCE4 for Modx Evolution
   Base: v4.6.3
*/

// @todo: check all needed themes
// @todo: add https://www.tinymce.com/docs/configure/content-filtering/#invalid_styles
// @todo: add https://www.tinymce.com/docs/configure/content-filtering/#remove_trailing_brs
// @todo: youtube-plugin is commercial? -> http://www.cfcms.nl/tinymce-youtube/index.html

class tinymce4bridge extends modxRTEbridge
{
    public function __construct($tvOptions=array())
    {
        $bridgeConfig = array(
            // Editor-Settings
            'editorLabel'   => 'TinyMCE4',           // Name displayed in Modx-Dropdowns - No HTML!
            'skinsDirectory'=> 'tinymce/skins',      // Relative to plugin-dir
            'skinthemeDirectory'=> 'tinymce/themes', // Relative to plugin-dir
            'editorVersion' => '4.7.4',              // Version of TinyMCE4-Library
            'editorLogo'    => 'tinymce/logo.png',   // Optional Image displayed in Modx-settings

            'bridgeParams'=>array('url_setup','style_formats','advanced_resizing','forced_root_block','contentsLangDirection','disabledButtons','selectorPrefix','selector','block_formats','theme'),

            // Custom settings to show below Modx- / user-configuration
            'gSettingsCustom' => array(
                'css_selectors' => NULL,         // Hides "CSS Selectors" from settings

                // 'blockFormats' will be available as $this->modxParams['blockFormats']
                // will be handled by $this->bridgeParams[blockFormats]()
                'blockFormats' => array(
                    'title' => 'blockFormats_title',
                    'configTpl' => '<textarea class="inputBox mce" name="[+name+]">[+[+editorKey+]_blockFormats+]</textarea>',
                    'message' => 'blockFormats_message',
                    'defaultCheckbox'=>true
                )
            ),

            // For Modx- and user-configuration
            'gSettingsDefaultValues' => array(
                'entermode' => 'p',
                'element_format' => 'xhtml',
                'schema' => 'html5',
                'blockFormats' => 'Paragraph=p;Header 1=h1;Header 2=h2;Header 3=h3',
                'custom_plugins' => 'advlist autolink lists link image charmap print preview hr anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen spellchecker insertdatetime media nonbreaking save table contextmenu directionality emoticons template paste textcolor codesample colorpicker textpattern imagetools paste modxlink youtube',
                'custom_buttons1' => 'undo redo | cut copy paste | searchreplace | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent blockquote | styleselect',
                'custom_buttons2' => 'link unlink anchor image media codesample table | hr removeformat | subscript superscript charmap | nonbreaking | visualchars visualblocks print preview fullscreen code formatselect',
                // Provide empty values for parseText() #989
                'template_docs' => '',
                'template_chunks' => '',
                'custom_buttons3' => '',
                'custom_buttons4' => '',
                'skin' => ''
            )
        );

        // Init bridge first before altering Lang()
        parent::__construct('tinymce4', $bridgeConfig, $tvOptions, __FILE__);

        // Add translation for monolingual custom-messages with $this->setLang( key, string, overwriteExisting=false )
        $this->setLang('editor_custom_buttons1_msg', '<div style="width:70vw;word-wrap:break-word;overflow-wrap:break-word;">[+default+]<i>' . $bridgeConfig['gSettingsDefaultValues']['custom_buttons1'] . '</i></div>');
        $this->setLang('editor_custom_buttons2_msg', '<div style="width:70vw;word-wrap:break-word;overflow-wrap:break-word;">[+default+]<i>' . $bridgeConfig['gSettingsDefaultValues']['custom_buttons2'] . '</i></div>');
        $this->setLang('editor_css_selectors_schema', 'Title==Tag==CSS-Class');
        $this->setLang('editor_css_selectors_example', 'Mono==pre==mono||Small Text==span==small');
        $this->setLang('editor_css_selectors_separator', '||');

    }

    // Functions for dynamic translation of Modx-settings to editor-settings

    // https://www.tinymce.com/docs/demo/url-conversion/
    public function bridge_url_setup($selector) {
        global $modx;

        // $pathSetup = array( relative_urls, remove_script_host, convert_urls )

        switch ($this->pluginParams['pathOptions']) {
            case 'Site config':
            case 'siteconfig':
                if ($modx->config['strip_image_paths'] == 1) {
                    $pathSetup = array(true, true, true);
                } else {
                    $pathSetup = array(false, false, true);
                }
                break;
            case 'Root relative':
            case 'docrelative':
                $pathSetup = array(true, true, true);
                break;
            case 'Absolute path':
            case 'rootrelative':
                $pathSetup = array(false, true, true);
                break;
            case 'URL':
            case 'fullpathurl':
                $pathSetup = array(false, false, true);
                break;
            case 'No convert':
            default:
                $pathSetup = array(true, true, false);
        }
        $this->set('relative_urls', $pathSetup[0], 'bool');
        $this->set('remove_script_host', $pathSetup[1], 'bool');
        $this->set('convert_urls', $pathSetup[2], 'bool');
    }

    // Set in plugin-configuration
    // https://www.tinymce.com/docs/configure/content-formatting/#style_formats
    public function bridge_style_formats($selector) {
        $inline = array();
        $block = array();
        if (isset($this->pluginParams['styleFormats']) && !empty($this->pluginParams['styleFormats'])) {
            if($this->isSimpleFormat($this->pluginParams['styleFormats'])) {
                $styles_formats = explode('|', $this->pluginParams['styleFormats']);
                foreach ($styles_formats as $val) {
                    $style = explode(',', $val);
                    // create inline / block
                    $inline[] = array('title' => $style['0'], 'inline'   => 'span', 'classes' => $style['1']);
                    $block[]  = array('title' => $style['0'], 'selector' => '*',    'classes' => $style['1']);
                }
                return array(
                    0=>array('title'=>'Inline','items'=>$inline),
                    1=>array('title'=>'Block','items'=>$block)
                );
            } else {
                // Allow full-format as seen in https://www.tinymce.com/docs/demo/format-custom/
                $this->set('style_formats', $this->pluginParams['styleFormats'], 'object');
            }
        } else {
            if (isset($this->pluginParams['styleFormats_inline']) && !empty($this->pluginParams['styleFormats_inline'])) {
                if($this->isSimpleFormat($this->pluginParams['styleFormats_inline'])) {
                    $styles_formats = explode('|', $this->pluginParams['styleFormats_inline']);
                    foreach ($styles_formats as $val) {
                        $style = explode(',', $val);
                        $inline[] = array('title' => $style['0'], 'inline'   => 'span', 'classes' => $style['1']);
                    }
                }
            }
            if (isset($this->pluginParams['styleFormats_block']) && !empty($this->pluginParams['styleFormats_block'])) {
                if($this->isSimpleFormat($this->pluginParams['styleFormats_block'])) {
                    $styles_formats = explode('|', $this->pluginParams['styleFormats_block']);
                    foreach ($styles_formats as $val) {
                        $style = explode(',', $val);
                        $block[]  = array('title' => $style['0'], 'selector' => '*',    'classes' => $style['1']);
                    }
                }
            }

            $styleSetup = array();
            if(!empty($inline)) $styleSetup[] = array('title'=>'Inline','items'=>$inline);
            if(!empty($block))  $styleSetup[] = array('title'=>'Block', 'items'=>$block);

            if(!empty($styleSetup)) return $styleSetup;
        }
        return NULL;
    }

    // Check for simple format: Title,cssClass|Title2,cssClass
    public function isSimpleFormat($string) {
        return preg_match('/^[a-zA-Z0-9,а-я,А-Я,_,]+/', $string);
    }

    // https://www.tinymce.com/docs/configure/editor-appearance/#resize
    public function bridge_advanced_resizing($selector) {
        switch ($this->pluginParams['resizing']) {
            case 'true':
                $this->set('resize', 'both', 'string');
                break;
            default:
                $this->set('resize', true, 'bool'); // Only up/down
        };
    }

    // https://www.tinymce.com/docs/configure/content-filtering/#forced_root_block
    public function bridge_forced_root_block($selector) {
        switch ($this->modxParams['entermode']) {
            case 'br':
                $this->set('forced_root_block', false, 'bool');
                break;
            default:
                $this->set('forced_root_block', 'p', 'string');
        };
        return NULL;    // Important
    }

    // @todo: Remove? RTL will be set by language-pack -> http://www.tinymce.com/forum/viewtopic.php?id=32748
    public function bridge_contentsLangDirection($selector) {
        if ($this->pluginParams['webAlign'] == 'rtl') {
            $this->set('directionality', 'rtl', 'string');
            $this->appendInitOnce('<style>.mce-toolbar .mce-last { float: right; }</style>');   // Force editor by CSS ?
        };
    }

    // disabled_buttons-param is deprecated - bridge replaces old TinyMCE v3-param
    public function bridge_disabledButtons($selector) {
        if (!empty($this->pluginParams['disabledButtons'])) {
            $buttons = explode(' ', $this->pluginParams['disabledButtons']);
            if (isset($this->pluginParams['toolbar1'])) $this->pluginParams['toolbar1'] = str_replace($buttons, '', $this->pluginParams['toolbar1']);
            if (isset($this->pluginParams['toolbar2'])) $this->pluginParams['toolbar2'] = str_replace($buttons, '', $this->pluginParams['toolbar2']);
            if (isset($this->pluginParams['toolbar3'])) $this->pluginParams['toolbar3'] = str_replace($buttons, '', $this->pluginParams['toolbar3']);
            if (isset($this->pluginParams['toolbar4'])) $this->pluginParams['toolbar4'] = str_replace($buttons, '', $this->pluginParams['toolbar4']);
        };
    }

    // Sets selectorPrefix and handles InlineMode for Manager or Frontendediting
    public function bridge_selectorPrefix($selector) {
        global $modx;

        $inlineMode = $this->determineValue('inline') == true && $this->pluginParams['inlineMode'] == 'enabled' ? true : false;

        // Manager Mode
        if (!$inlineMode) {
            $this->setPlaceholder('selectorPrefix', '#');   // Selectors = #ta, #tv9

            // Prepare Inline-Magic
        } else {
            $this->setPlaceholder('selectorPrefix', '.');   // Single selector = .editable

            $this->force('setup', NULL);               // Remove from parameters for Frontend
            $this->force('save_onsavecallback', 'function () {
            triggerSave();
        }', 'object');

            // Prepare save-button
            $this->appendInitOnce('
            <style>
              #action-save { position:fixed;top:0px;left:0px; color: #000000; background-color: #F0F0F0; text-shadow: -1px 1px #aaaaaa; display: inline-block; padding: 15px 30px !important; font-size: 24px; font-family: sans-serif; line-height: 1.8; appearance: none; box-shadow: none; border-radius: 0; border: none; cursor:pointer; }
              #action-save:hover { color: #222222; outline: none; background-color: #E3E3E3; }
            </style>
            <button id="action-save" class="button" title="Save Ressource">SAVE</button>
            <script>
            // Remove every attribute starting with data-mce-
            var rtebElem;
            function tinymce_clean_html_before_save( _container ) {
             $(_container).find("*").each(function(){
              rtebElem = $(this);
              var attributes = rtebElem.get(0).attributes;
              $(attributes).each(function(index){
               if(typeof attributes[index] != "undefined") {
                var attribute = attributes[index].name;
                if( attribute.substring(0, 9) == "data-mce-" ){
                 rtebElem.removeAttr(attribute);
                }
               }
              });
             });
             return _container;
            }
    
            $("#action-save").on("click", function() { triggerSave(); });
            function triggerSave() {
    
            [+dataObject+]
    
            var saving = $.post( "' . $this->pluginParams['base_url'] . 'connector.tinymce4.saveProcessor.php", data );
    
            saving.done(function( data ) {
                if( data == ' . $modx->documentIdentifier . ' ) {
                    $("#action-save").css("color","#00ff00");
                    setTimeout(function(){ $("#action-save").css("color","#000000") }, 3000);
                    // Force all instances to not dirty state
                    for (var key in window.tinymce.editors) {
                        tinymce.get(key).setDirty(false);
                    }
                } else {
                    $("#action-save").css("color","#ff0000");
                    alert( data );  // Show (PHP-)Errors for debug
                }
            });
            }
        </script>
    ');
            // Prepare dataObject for submitting changes
            if (isset($modx->modxRTEbridge['editableIds'])) {
                $dataEls = array();
                $phs = '';
                foreach ($modx->modxRTEbridge['editableIds'] as $cssId=>$x) {
                    $dataEls[] = "'{$cssId}': tinymce_clean_html_before_save( $('#modx_{$cssId}').html() )";
                    $phs .= (!empty($phs) ? ',' : '') . $cssId;
                }
                $dataEls = implode(",\n                    ", $dataEls);

                $this->setPlaceholder('dataObject', "
                var data = {
                    'pluginName':'{$this->pluginParams['pluginName']}',
                    'rid':{$modx->documentIdentifier},
                    'secHash':'{$this->prepareAjaxSecHash($modx->documentIdentifier)}',
                    'phs':'{$phs}',
                    {$dataEls}
                };");
            }
        }
        return NULL;
    }

    // https://www.tinymce.com/docs/configure/integration-and-setup/#selector
    // Requires comma-separated IDs as selector instead of
    public function bridge_selector($selector) {
        global $modx;

        if ($selector === 'initBridge' && !defined('INITBRIDGE_TINYMCE4')) {   // called only once right before looping through $this->pluginParams['elements']
            define('INITBRIDGE_TINYMCE4', 1); // don´t call it at every getEditorScript() / TV-init!
            $prefix = $this->getPlaceholder('selectorPrefix');
            $elements = $this->pluginParams['elements'];

            // Sort elements by themes
            $sortElArr = array();
            foreach ($elements as $sel) {
                if (isset($this->tvOptions[$sel]['theme']) && !empty($this->tvOptions[$sel]['theme'])) {
                    $theme = $this->tvOptions[$sel]['theme'];
                } else {
                    $theme = $this->modxParams['theme'];
                };
                $sortElArr[$theme][] = $sel;
            }
            $sortOptArr = array();
            foreach ($sortElArr as $theme => $elArr) {
                $sortElArr[$theme] = '';
                // return selectors comma-separated per theme as single element for TinyMCE4 & translate options to new selectors
                $key = '';
                foreach ($elArr as $el) {
                    $key .= (!empty($key) ? ',' : '') . $prefix . $el;
                }
                $sortElArr[$theme] = $key;
                $sortOptArr[$key] = $this->tvOptions[$el];
            }
            $this->pluginParams['elements'] = $sortElArr;
            $this->tvOptions = $sortOptArr;
        }
        return NULL;
    }

    // Handles customSetting "blockFormats" -
    // https://www.tinymce.com/docs/configure/content-formatting/#block_formats
    public function bridge_block_formats($selector) {
        // Format: Paragraph=p;Header 1=h1;Header 2=h2;Header 3=h3
        // params-string could be bridged/modified here from Modx-config to Editor-config
        // Right now its enough to return the string
        return $this->modxParams['blockFormats'];
    }

    // https://github.com/evolution-cms/evolution/issues/442
    public function bridge_theme($selector) {
        global $modx;

        // inlite-theme is only compatible with inline-mode / frontend
        if($this->modxParams['skintheme'] == 'inlite' && $modx->event->name != 'OnWebPagePrerender') {
            $this->force('theme', 'modern');     // Fallback to modern-theme in backend
        }

        return NULL;
    }
}
