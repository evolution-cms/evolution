<!-- Site Settings -->
<div class="tab-page" id="tabPage2">
    <h2 class="tab">{{ __('global.settings_site') }}</h2>
    <script type="text/javascript">tpSettings.addTabPage(document.getElementById('tabPage2'));</script>
    <div class="container container-body">
        @include('manager::form.radio', [
            'name' => 'site_status',
            'label' => __('global.sitestatus_title'),
            'small' => '[(site_status)]',
            'value' => $settings['site_status'],
            'options' => [
                1 =>  __('global.online'),
                0 => __('global.offline'),
            ],
            'comment' => (isset($disabledSettings['site_status']) ? __('global.setting_from_file') . '<br>' : ''),
            'disabled' => $disabledSettings['site_status'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'site_name',
            'label' => __('global.sitename_title'),
            'small' => '[(site_name)]',
            'value' => $settings['site_name'],
            'attributes' => 'onchange="documentDirty=true;" maxlength="255"',
            'comment' => (isset($disabledSettings['site_name']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.sitename_message'),
            'disabled' => $disabledSettings['site_name'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'site_start',
            'label' => __('global.sitestart_title'),
            'small' => '[(site_start)]',
            'value' => $settings['site_start'],
            'attributes' => 'onchange="documentDirty=true;" maxlength="255"',
            'comment' => (isset($disabledSettings['site_start']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.sitestart_message'),
            'disabled' => $disabledSettings['site_start'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'error_page',
            'label' => __('global.errorpage_title'),
            'small' => '[(error_page)]',
            'value' => $settings['error_page'],
            'attributes' => 'onchange="documentDirty=true;" maxlength="10"',
            'comment' => (isset($disabledSettings['error_page']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.errorpage_message'),
            'disabled' => $disabledSettings['error_page'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'unauthorized_page',
            'label' => __('global.unauthorizedpage_title'),
            'small' => '[(unauthorized_page)]',
            'value' => $settings['unauthorized_page'],
            'attributes' => 'onchange="documentDirty=true;" maxlength="10"',
            'comment' => (isset($disabledSettings['unauthorized_page']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.unauthorizedpage_message'),
            'disabled' => $disabledSettings['unauthorized_page'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'ControllerNamespace',
            'label' => __('global.controller_namespace'),
            'small' => '[(ControllerNamespace)]',
            'value' => (isset($settings['ControllerNamespace']))? $settings['ControllerNamespace'] : '',
            'attributes' => 'onchange="documentDirty=true;" maxlength="255"',
            'comment' => (isset($disabledSettings['ControllerNamespace']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.controller_namespace_message'),
            'disabled' => $disabledSettings['ControllerNamespace'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'UpgradeRepository',
            'label' => __('global.update_repository'),
            'small' => '[(UpgradeRepository)]',
            'value' => (isset($settings['UpgradeRepository']))? $settings['UpgradeRepository'] : '',
            'attributes' => 'onchange="documentDirty=true;" maxlength="255"',
            'comment' => (isset($disabledSettings['UpgradeRepository']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.update_repository_message'),
            'disabled' => $disabledSettings['UpgradeRepository'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'site_unavailable_page',
            'label' => __('global.siteunavailable_page_title'),
            'small' => '[(site_unavailable_page)]',
            'value' => $settings['site_unavailable_page'],
            'attributes' => 'onchange="documentDirty=true;" maxlength="10"',
            'comment' => (isset($disabledSettings['site_unavailable_page']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.siteunavailable_page_message'),
            'disabled' => $disabledSettings['site_unavailable_page'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.textarea', [
            'name' => 'site_unavailable_message',
            'id' => 'site_unavailable_message_textarea',
            'for' => 'site_unavailable_message_textarea',
            'label' => __('global.siteunavailable_title') . '<br>' .
                __('global.update_settings_from_language') .
                view('manager::form.selectElement', [
                    'name' => 'reload_site_unavailable',
                    'id' => 'reload_site_unavailable_select',
                    'class' => 'form-control-sm',
                    'attributes' => 'onchange="confirmLangChange(this, \'siteunavailable_message_default\', \'site_unavailable_message_textarea\');"',
                    'first' => [
                        'text' => __('global.language_title')
                    ],
                    'options' => $langKeys,
                    'as' => 'values',
                    'ucwords' => true,
                    'disabled' => $disabledSettings['site_unavailable_message'] ?? null
                ]) .
                view('manager::form.inputElement', [
                    'type' => 'hidden',
                    'name' => 'siteunavailable_message_default',
                    'id' => 'siteunavailable_message_default_hidden',
                    'value' => $settings['manager_language']
                ])
            ,
            'small' => '[(site_unavailable_message)]',
            'value' => ($settings['site_unavailable_message'] ? $settings['site_unavailable_message'] : __('global.siteunavailable_message_default')),
            'attributes' => 'onchange="documentDirty=true;"',
            'rows' => 4,
            'comment' => (isset($disabledSettings['site_unavailable_message']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.siteunavailable_message'),
            'disabled' => $disabledSettings['site_unavailable_message'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.row', [
            'name' => 'default_template',
            'label' => __('global.defaulttemplate_title'),
            'small' => '[(default_template)]',
            'element' => view('manager::form.selectElement', [
                'name' => 'default_template',
                'value' => $settings['default_template'],
                'options' => $templates['items'],
                'attributes' => 'onchange="documentDirty=true;wrap=document.getElementById(\'template_reset_options_wrapper\');if(this.options[this.selectedIndex].value!=' . $settings['default_template'] . '){wrap.style.display=\'block\';}else{wrap.style.display=\'none\';}" size="1"',
                'comment' => (isset($disabledSettings['default_template']) ? __('global.setting_from_file') . '<br>' : ''),
                'disabled' => $disabledSettings['default_template'] ?? null
                ]) .
                '<div id="template_reset_options_wrapper" style="display:none;">' .
                    view('manager::form.radio', [
                        'name' => 'reset_template',
                        'options' => [
                            1 => __('global.template_reset_all'),
                            2 => sprintf(__('global.template_reset_specific'), $templates['oldTmpName'])
                        ]
                    ]) .
                '</div>' .
                view('manager::form.inputElement', [
                    'type' => 'hidden',
                    'name' => 'old_template',
                    'value' => $templates['oldTmpId']
                ]),
            'comment' => __('global.defaulttemplate_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'auto_template_logic',
            'label' => __('global.defaulttemplate_logic_title'),
            'small' => '[(auto_template_logic)]',
            'value' => $settings['auto_template_logic'] ?? 'sibling',
            'options' => [
                'system' => __('global.defaulttemplate_logic_system_message'),
                'parent' => __('global.defaulttemplate_logic_parent_message'),
                'sibling' => __('global.defaulttemplate_logic_sibling_message')
            ],
            'comment' => (isset($disabledSettings['auto_template_logic']) ? __('global.setting_from_file') . '<br>' : ''),
            'disabled' => $disabledSettings['auto_template_logic'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'chunk_processor',
            'label' => __('global.chunk_processor'),
            'small' => '[(chunk_processor)]',
            'value' => $settings['chunk_processor'],
            'options' => [
                '' => 'DocumentParser',
                'DLTemplate' => 'DLTemplate'
            ],
            'comment' => (isset($disabledSettings['chunk_processor']) ? __('global.setting_from_file') . '<br>' : ''),
            'disabled' => $disabledSettings['chunk_processor'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'enable_filter',
            'label' => __('global.enable_filter_title'),
            'small' => '[(enable_filter)]',
            'value' => $settings['enable_filter'],
            'options' => [
                1 => [
                    'text' => __('global.yes'),
                    'disabled' => $phxEnabled
                ],
                0 => [
                    'text' => __('global.no'),
                    'disabled' => $phxEnabled
                ]
            ],
            'comment' => (isset($disabledSettings['enable_filter']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.enable_filter_message'),
            'disabled' => $disabledSettings['enable_filter'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'enable_at_syntax',
            'label' => __('global.enable_at_syntax_title'),
            'small' => '[(enable_at_syntax)]',
            'value' => $settings['enable_at_syntax'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no')
            ],
            'comment' => (isset($disabledSettings['enable_at_syntax']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.enable_at_syntax_message') .
                '<ul>
                    <li><a href="https://github.com/modxcms/evolution/wiki/@@IF-@@ELSEIF-@@ELSE-@@ENDIF" target="_blank">@@IF @@ELSEIF @@ELSE @@ENDIF</a></li>
                    <li>&lt;@LITERAL&gt; @{{string}} [*string*] [[string]] &lt;@ENDLITERAL&gt;</li>
                    <li><!--@- Do not output -@--></li>
                </ul>',
            'disabled' => $disabledSettings['enable_at_syntax'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'publish_default',
            'label' => __('global.defaultpublish_title'),
            'small' => '[(publish_default)]',
            'value' => $settings['publish_default'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no')
            ],
            'comment' => (isset($disabledSettings['publish_default']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.defaultpublish_message'),
            'disabled' => $disabledSettings['publish_default'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'cache_default',
            'label' => __('global.defaultcache_title'),
            'small' => '[(cache_default)]',
            'value' => $settings['cache_default'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no')
            ],
            'comment' => (isset($disabledSettings['cache_default']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.defaultcache_message'),
            'disabled' => $disabledSettings['cache_default'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'search_default',
            'label' => __('global.defaultsearch_title'),
            'small' => '[(search_default)]',
            'value' => $settings['search_default'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no')
            ],
            'comment' => (isset($disabledSettings['search_default']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.defaultsearch_message'),
            'disabled' => $disabledSettings['search_default'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'auto_menuindex',
            'label' => __('global.defaultmenuindex_title'),
            'small' => '[(auto_menuindex)]',
            'value' => $settings['auto_menuindex'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no')
            ],
            'comment' => (isset($disabledSettings['auto_menuindex']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.defaultmenuindex_message'),
            'disabled' => $disabledSettings['auto_menuindex'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.row', [
            'label' => __('global.custom_contenttype_title'),
            'for' => 'txt_custom_contenttype',
            'element' => '
                <div class="input-group">' .
                    view('manager::form.inputElement', [
                        'name' => 'txt_custom_contenttype',
                        'attributes' => 'onChange="documentDirty=true;" maxlength="100"'
                    ]) .
                    '<div class="input-group-btn">' .
                        view('manager::form.inputElement', [
                            'type' => 'button',
                            'value' => __('global.add'),
                            'attributes' => 'onclick="addContentType();"'
                        ]) .
                    '</div>
                </div>
                <div class="col-auto col-sm-4 mt-1">' .
                    view('manager::form.selectElement', [
                        'name' => 'lst_custom_contenttype',
                        'attributes' => 'size="5"',
                        'options' => explode(',', $settings['custom_contenttype']),
                        'as' => 'values'
                    ]) .
                '</div>
                <div class="col-auto col-sm-2 mt-1">' .
                    view('manager::form.inputElement', [
                        'type' => 'button',
                        'name' => 'removecontenttype',
                        'value' => __('global.remove'),
                        'attributes' => 'onclick="removeContentType()"'
                    ]) .
                '</div>' .
                view('manager::form.inputElement', [
                    'type' => 'hidden',
                    'name' => 'custom_contenttype',
                    'value' => $settings['custom_contenttype']
                ]),
            'comment' => __('global.custom_contenttype_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'docid_incrmnt_method',
            'label' => __('global.docid_incrmnt_method_title'),
            'small' => '[(docid_incrmnt_method)]',
            'value' => $settings['docid_incrmnt_method'],
            'options' => [
                0 => __('global.docid_incrmnt_method_0'),
                1 => __('global.docid_incrmnt_method_1'),
                2 => __('global.docid_incrmnt_method_2')
            ],
            'comment' => (isset($disabledSettings['docid_incrmnt_method']) ? __('global.setting_from_file') . '<br>' : ''),
            'disabled' => $disabledSettings['docid_incrmnt_method'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'enable_cache',
            'label' => __('global.enable_cache_title'),
            'small' => '[(enable_cache)]',
            'value' => $settings['enable_cache'],
            'options' => [
                1 => __('global.enabled'),
                0 => __('global.disabled'),
                2 => __('global.disabled_at_login')
            ],
            'comment' => (isset($disabledSettings['enable_cache']) ? __('global.setting_from_file') . '<br>' : ''),
            'disabled' => $disabledSettings['enable_cache'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'disable_chunk_cache',
            'label' => __('global.disable_chunk_cache_title'),
            'small' => '[(disable_chunk_cache)]',
            'value' => $settings['disable_chunk_cache'] ?? 0,
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no'),
            ],
            'comment' => (isset($disabledSettings['disable_chunk_cache']) ? __('global.setting_from_file') . '<br>' : ''),
            'disabled' => $disabledSettings['disable_chunk_cache'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'disable_snippet_cache',
            'label' => __('global.disable_snippet_cache_title'),
            'small' => '[(disable_snippet_cache)]',
            'value' => $settings['disable_snippet_cache'] ?? 0,
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no'),
            ],
            'comment' => (isset($disabledSettings['disable_snippet_cache']) ? __('global.setting_from_file') . '<br>' : ''),
            'disabled' => $disabledSettings['disable_snippet_cache'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'disable_plugins_cache',
            'label' => __('global.disable_plugins_cache_title'),
            'small' => '[(disable_plugins_cache)]',
            'value' => $settings['disable_plugins_cache'] ?? 0,
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no'),
            ],
            'comment' => (isset($disabledSettings['disable_plugins_cache']) ? __('global.setting_from_file') . '<br>' : ''),
            'disabled' => $disabledSettings['disable_plugins_cache'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'cache_type',
            'label' => __('global.cache_type_title'),
            'small' => '[(cache_type)]',
            'value' => $settings['cache_type'],
            'options' => [
                1 => __('global.cache_type_1'),
                2 => __('global.cache_type_2')
            ],
            'comment' => (isset($disabledSettings['cache_type']) ? __('global.setting_from_file') . '<br>' : ''),
            'disabled' => $disabledSettings['cache_type'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'html_comment',
            'label' => __('global.html_comment_title'),
            'small' => '[(html_comment)]',
            'value' => $settings['html_comment'] ?? '',
            'attributes' => 'onchange="documentDirty=true;" maxlength="255" placeholder="snippets,chunks,tvs,snippetParams"',
            'comment' => (isset($disabledSettings['html_comment']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.html_comment_message'),
            'disabled' => $disabledSettings['html_comment'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'minifyphp_incache',
            'label' => __('global.minifyphp_incache_title'),
            'small' => '[(minifyphp_incache)]',
            'value' => $settings['minifyphp_incache'],
            'options' => [
                1 => __('global.enabled'),
                0 => __('global.disabled')
            ],
            'comment' => (isset($disabledSettings['minifyphp_incache']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.minifyphp_incache_message'),
            'disabled' => $disabledSettings['minifyphp_incache'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.select', [
            'name' => 'server_offset_time',
            'label' => __('global.serveroffset_title'),
            'small' => '[(server_offset_time)]',
            'value' => $settings['server_offset_time'],
            'options' => $serverTimes,
            'attributes' => 'onChange="documentDirty=true;" size="1"',
            'comment' => (isset($disabledSettings['server_offset_time']) ? __('global.setting_from_file') . '<br>' : '') .
                sprintf(__('global.serveroffset_message'), evolutionCMS()->toDateFormat(time(), 'timeOnly'), evolutionCMS()->toDateFormat(time() + $settings['server_offset_time'], 'timeOnly')),
            'disabled' => $disabledSettings['server_offset_time'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'server_protocol',
            'label' => __('global.server_protocol_title'),
            'small' => '[(server_protocol)]',
            'value' => $settings['server_protocol'],
            'options' => [
                'http' => __('global.server_protocol_http'),
                'https' => __('global.server_protocol_https')
            ],
            'comment' => (isset($disabledSettings['server_protocol']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.server_protocol_message'),
            'disabled' => $disabledSettings['server_protocol'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'track_visitors',
            'label' => __('global.track_visitors_title'),
            'small' => '[(track_visitors)]',
            'value' => $settings['track_visitors'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no')
            ],
            'comment' => (isset($disabledSettings['track_visitors']) ? __('global.setting_from_file') . '<br>' : '') . __('global.track_visitors_message'),
            'disabled' => $disabledSettings['track_visitors'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'rss_url_releases',
            'label' => __('global.rss_url_releases_title'),
            'small' => '[(rss_url_releases)]',
            'value' => $settings['rss_url_releases'],
            'attributes' => 'onchange="documentDirty=true;" maxlength="350"',
            'comment' => (isset($disabledSettings['rss_url_releases']) ? __('global.setting_from_file') . '<br>' : '') . __('global.rss_url_releases_message'),
            'disabled' => $disabledSettings['rss_url_releases'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'rss_url_extras',
            'label' => __('global.rss_url_extras_title'),
            'small' => '[(rss_url_extras)]',
            'value' => $settings['rss_url_extras'],
            'attributes' => 'onchange="documentDirty=true;" maxlength="350"',
            'comment' => (isset($disabledSettings['rss_url_extras']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.rss_url_extras_message'),
            'disabled' => $disabledSettings['rss_url_extras'] ?? null
        ])

        <div class="split my-1"></div>

        {!! get_by_key($tabEvents, 'OnSiteSettingsRender') !!}
    </div>
</div>
