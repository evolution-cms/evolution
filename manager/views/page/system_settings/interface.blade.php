<!-- Interface & editor settings -->
<div class="tab-page" id="tabPage5">
    <h2 class="tab">{{ ManagerTheme::getLexicon('settings_ui') }}</h2>
    <script type="text/javascript">tpSettings.addTabPage(document.getElementById('tabPage5'));</script>
    <div class="container container-body">

        @include('manager::form.select', [
            'name' => 'manager_language',
            'label' => ManagerTheme::getLexicon('language_title'),
            'small' => '[(manager_language)]',
            'value' => $settings['manager_language'],
            'options' => $langKeys,
            'as' => 'values',
            'ucwords' => true,
            'attributes' => 'onChange="documentDirty=true;" size="1"',
            'comment' => ManagerTheme::getLexicon('language_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.select', [
            'name' => 'modx_charset',
            'label' => ManagerTheme::getLexicon('charset_title'),
            'small' => '[(modx_charset)]',
            'value' => $settings['modx_charset'],
            'attributes' => 'onChange="documentDirty=true;" size="1"',
            'options' => include EVO_CORE_PATH . '/factory/charsets.php',
            'comment' => ManagerTheme::getLexicon('charset_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.select', [
            'name' => 'manager_theme',
            'label' => ManagerTheme::getLexicon('manager_theme'),
            'small' => '[(manager_theme)]',
            'value' => $settings['manager_theme'],
            'attributes' => 'onChange="documentDirty=true; document.forms[\'settings\'].theme_refresher.value = Date.parse(new Date());" size="1"',
            'options' => $themes,
            'ucwords' => true,
            'comment' => '<input type="hidden" name="theme_refresher" value="" />'
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'manager_theme_mode',
            'label' => ManagerTheme::getLexicon('manager_theme_mode'),
            'small' => '[(manager_theme_mode)]',
            'value' => $settings['manager_theme_mode'],
            'options' => [
                1 => ManagerTheme::getLexicon('manager_theme_mode1'),
                2 => ManagerTheme::getLexicon('manager_theme_mode2'),
                3 => ManagerTheme::getLexicon('manager_theme_mode3'),
                4 => ManagerTheme::getLexicon('manager_theme_mode4')
            ],
            'comment' => ManagerTheme::getLexicon('manager_theme_mode_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.row', [
            'label' => ManagerTheme::getLexicon('login_logo_title'),
            'small' => '[(login_logo)]',
            'for' => 'login_logo',
            'element' => '
                <div class="col-md-9">
                    <div class="input-group">' .
                        ManagerTheme::view('form.inputElement', [
                            'name' => 'login_logo',
                            'value' => $settings['login_logo'],
                            'attributes' => 'onChange="documentDirty=true;"'
                        ]) .
                        '<div class="input-group-btn">' .
                            ManagerTheme::view('form.inputElement', [
                                'type' => 'button',
                                'value' => ManagerTheme::getLexicon('insert'),
                                'attributes' => 'onclick="BrowseServer(\'login_logo\')"'
                            ]) .
                        '</div>
                    </div>
                </div>
                <div class="col-md-3 text-right">
                    <img name="login_logo" style="max-height: 48px" src="' .
                    ($settings['login_logo'] ? MODX_SITE_URL . $settings['login_logo'] : '') . '" />
                </div>',
            'comment' => ManagerTheme::getLexicon('login_logo_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.row', [
            'label' => ManagerTheme::getLexicon('login_bg_title'),
            'small' => '[(login_bg)]',
            'for' => 'login_bg',
            'element' => '
                <div class="col-md-9">
                    <div class="input-group">' .
                        ManagerTheme::view('form.inputElement', [
                            'name' => 'login_bg',
                            'value' => $settings['login_bg'],
                            'attributes' => 'onChange="documentDirty=true;"'
                        ]) .
                        '<div class="input-group-btn">' .
                            ManagerTheme::view('form.inputElement', [
                                'type' => 'button',
                                'value' => ManagerTheme::getLexicon('insert'),
                                'attributes' => 'onclick="BrowseServer(\'login_bg\')"'
                            ]) .
                        '</div>
                    </div>
                </div>
                <div class="col-md-3 text-right">
                    <img name="login_bg" style="max-height: 48px" src="' .
                    ($settings['login_bg'] ? MODX_SITE_URL . $settings['login_bg'] : '') . '" />
                </div>',
            'comment' => ManagerTheme::getLexicon('login_bg_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'login_form_position',
            'label' => ManagerTheme::getLexicon('login_form_position_title'),
            'small' => '[(login_form_position)]',
            'value' => $settings['login_form_position'],
            'options' => [
                'left' => ManagerTheme::getLexicon('login_form_position_left'),
                'center' => ManagerTheme::getLexicon('login_form_position_center'),
                'right' => ManagerTheme::getLexicon('login_form_position_right')
            ]
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'login_form_style',
            'label' => ManagerTheme::getLexicon('login_form_style'),
            'small' => '[(login_form_style)]',
            'value' => $settings['login_form_style'],
            'options' => [
                'dark' => ManagerTheme::getLexicon('login_form_style_dark'),
                'light' => ManagerTheme::getLexicon('login_form_style_light')
            ]
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'manager_menu_position',
            'label' => ManagerTheme::getLexicon('manager_menu_position_title'),
            'small' => '[(manager_menu_position)]',
            'value' => $settings['manager_menu_position'],
            'options' => [
                'top' => ManagerTheme::getLexicon('manager_menu_position_top'),
                'left' => ManagerTheme::getLexicon('manager_menu_position_left')
            ]
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'show_picker',
            'label' => ManagerTheme::getLexicon('show_picker'),
            'small' => '[(show_picker)]',
            'value' => $settings['show_picker'],
            'options' => [
                1 => ManagerTheme::getLexicon('yes'),
                0 => ManagerTheme::getLexicon('no')
            ],
            'comment' => ManagerTheme::getLexicon('settings_show_picker_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'warning_visibility',
            'label' => ManagerTheme::getLexicon('warning_visibility'),
            'small' => '[(warning_visibility)]',
            'value' => $settings['warning_visibility'],
            'options' => [
                0 => ManagerTheme::getLexicon('administrators'),
                1 => ManagerTheme::getLexicon('everybody')
            ],
            'comment' => ManagerTheme::getLexicon('warning_visibility_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'tree_page_click',
            'label' => ManagerTheme::getLexicon('tree_page_click'),
            'small' => '[(tree_page_click)]',
            'value' => $settings['tree_page_click'],
            'options' => [
                27 => ManagerTheme::getLexicon('edit_resource'),
                3 => ManagerTheme::getLexicon('doc_data_title')
            ],
            'comment' => ManagerTheme::getLexicon('tree_page_click_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'use_breadcrumbs',
            'label' => ManagerTheme::getLexicon('use_breadcrumbs'),
            'small' => '[(use_breadcrumbs)]',
            'value' => $settings['use_breadcrumbs'],
            'options' => [
                1 => ManagerTheme::getLexicon('yes'),
                0 => ManagerTheme::getLexicon('no')
            ],
            'comment' => ManagerTheme::getLexicon('use_breadcrumbs_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'remember_last_tab',
            'label' => ManagerTheme::getLexicon('remember_last_tab'),
            'small' => '[(remember_last_tab)]',
            'value' => $settings['remember_last_tab'],
            'options' => [
                1 => ManagerTheme::getLexicon('yes'),
                0 => ManagerTheme::getLexicon('no')
            ],
            'comment' => ManagerTheme::getLexicon('remember_last_tab_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'global_tabs',
            'label' => ManagerTheme::getLexicon('use_global_tabs'),
            'small' => '[(global_tabs)]',
            'value' => $settings['global_tabs'],
            'options' => [
                1 => ManagerTheme::getLexicon('yes'),
                0 => ManagerTheme::getLexicon('no')
            ]
        ])

        <div class="split my-1"></div>

        @include('manager::form.select', [
            'name' => 'group_tvs',
            'label' => ManagerTheme::getLexicon('group_tvs'),
            'small' => '[(group_tvs)]',
            'value' => $settings['group_tvs'],
            'options' => explode(',', ManagerTheme::getLexicon('settings_group_tv_options')),
            'attributes' => 'onChange="documentDirty=true;" size="1"',
            'comment' => ManagerTheme::getLexicon('settings_group_tv_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'show_newresource_btn',
            'label' => ManagerTheme::getLexicon('show_newresource_btn'),
            'small' => '[(show_newresource_btn)]',
            'value' => $settings['show_newresource_btn'],
            'options' => [
                1 => ManagerTheme::getLexicon('yes'),
                0 => ManagerTheme::getLexicon('no')
            ],
            'comment' => ManagerTheme::getLexicon('show_newresource_btn_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'show_fullscreen_btn',
            'label' => ManagerTheme::getLexicon('show_fullscreen_btn'),
            'small' => '[(show_fullscreen_btn)]',
            'value' => $settings['show_fullscreen_btn'],
            'options' => [
                1 => ManagerTheme::getLexicon('yes'),
                0 => ManagerTheme::getLexicon('no')
            ],
            'comment' => ManagerTheme::getLexicon('show_fullscreen_btn_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.select', [
            'name' => 'resource_tree_node_name',
            'label' => ManagerTheme::getLexicon('setting_resource_tree_node_name'),
            'small' => '[(resource_tree_node_name)]',
            'value' => $settings['resource_tree_node_name'],
            'options' => [
                'pagetitle' => '[*pagetitle*]',
                'longtitle' => '[*longtitle*]',
                'menutitle' => '[*menutitle*]',
                'alias' => '[*alias*]',
                'createdon' => '[*createdon*]',
                'editedon' => '[*editedon*]',
                'publishedon' => '[*publishedon*]'
            ],
            'attributes' => 'onChange="documentDirty=true;" size="1"',
            'comment' => ManagerTheme::getLexicon('setting_resource_tree_node_name_desc') . '<br /><b>' . ManagerTheme::getLexicon('setting_resource_tree_node_name_desc_add') . '</b>'
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'session_timeout',
            'label' => ManagerTheme::getLexicon('session_timeout'),
            'small' => '[(session_timeout)]',
            'value' => $settings['session_timeout'],
            'comment' => ManagerTheme::getLexicon('session_timeout_msg')
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'tree_show_protected',
            'label' => ManagerTheme::getLexicon('tree_show_protected'),
            'small' => '[(tree_show_protected)]',
            'value' => $settings['tree_show_protected'],
            'options' => [
                1 => ManagerTheme::getLexicon('yes'),
                0 => ManagerTheme::getLexicon('no')
            ],
            'comment' => ManagerTheme::getLexicon('tree_show_protected_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'datepicker_offset',
            'label' => ManagerTheme::getLexicon('datepicker_offset'),
            'small' => '[(datepicker_offset)]',
            'value' => $settings['datepicker_offset'],
            'attributes' => 'onChange="documentDirty=true;" maxlength="50"',
            'comment' => ManagerTheme::getLexicon('datepicker_offset_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.select', [
            'name' => 'datetime_format',
            'label' => ManagerTheme::getLexicon('datetime_format'),
            'small' => '[(datetime_format)]',
            'value' => $settings['datetime_format'],
            'attributes' => 'onChange="documentDirty=true;" size="1"',
            'options' =>  ['dd-mm-YYYY', 'mm/dd/YYYY', 'YYYY/mm/dd'],
            'as' => 'values',
            'comment' => ManagerTheme::getLexicon('datetime_format_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'number_of_logs',
            'label' => ManagerTheme::getLexicon('nologentries_title'),
            'small' => '[(number_of_logs)]',
            'value' => $settings['number_of_logs'],
            'attributes' => 'onChange="documentDirty=true;" maxlength="50"',
            'comment' => ManagerTheme::getLexicon('nologentries_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'mail_check_timeperiod',
            'label' => ManagerTheme::getLexicon('mail_check_timeperiod_title'),
            'small' => '[(mail_check_timeperiod)]',
            'value' => $settings['mail_check_timeperiod'],
            'attributes' => 'onChange="documentDirty=true;" maxlength="50"',
            'comment' => ManagerTheme::getLexicon('mail_check_timeperiod_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'number_of_messages',
            'label' => ManagerTheme::getLexicon('nomessages_title'),
            'small' => '[(number_of_messages)]',
            'value' => $settings['number_of_messages'],
            'attributes' => 'onChange="documentDirty=true;" maxlength="50"',
            'comment' => ManagerTheme::getLexicon('nomessages_message')
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'number_of_results',
            'label' => ManagerTheme::getLexicon('noresults_title'),
            'small' => '[(number_of_results)]',
            'value' => $settings['number_of_results'],
            'attributes' => 'onChange="documentDirty=true;" maxlength="50"',
            'comment' => ManagerTheme::getLexicon('noresults_message')
        ])

        <div class="split my-1"></div>

        <?php
        // invoke OnRichTextEditorRegister event
        $evtOut = $modx->invokeEvent('OnRichTextEditorRegister');
        if (!is_array($evtOut)) {
            $evtOut = array();
            $use_editor = 0;
        }
        ?>

        <div @if(empty($evtOut)) style="display: none;" @endif>

            @include('manager::form.radio', [
                'name' => 'use_editor',
                'label' => ManagerTheme::getLexicon('use_editor_title'),
                'small' => '[(use_editor)]',
                'value' => $settings['use_editor'],
                'options' => [
                    1 => [
                        'text' => ManagerTheme::getLexicon('yes'),
                        'attributes' => 'id="editorRowOn"'
                    ],
                    0 => [
                        'text' => ManagerTheme::getLexicon('no'),
                        'attributes' => 'id="editorRowOff"'
                    ]
                ],
                'comment' => ManagerTheme::getLexicon('use_editor_message')
            ])

            <div class="split my-1"></div>

            <div class="editorRow" @if(empty($settings['use_editor'])) style="display: none;" @endif>

                @include('manager::form.select', [
                    'name' => 'which_editor',
                    'label' => ManagerTheme::getLexicon('which_editor_title'),
                    'small' => '[(which_editor)]',
                    'value' => $settings['which_editor'],
                    'attributes' => 'onChange="documentDirty=true;" size="1"',
                    'first' => [
                        'value' => 'none',
                        'text' => ManagerTheme::getLexicon('none')
                    ],
                    'options' => $evtOut,
                    'as' => 'values',
                    'comment' => ManagerTheme::getLexicon('which_editor_message')
                ])

                <div class="split my-1"></div>

                @include('manager::form.select', [
                    'name' => 'fe_editor_lang',
                    'label' => ManagerTheme::getLexicon('fe_editor_lang_title'),
                    'small' => '[(fe_editor_lang)]',
                    'value' => $settings['fe_editor_lang'],
                    'attributes' => 'onChange="documentDirty=true;" size="1"',
                    'first' => [
                        'text' => ManagerTheme::getLexicon('language_title')
                    ],
                    'options' => $langKeys,
                    'as' => 'values',
                    'ucwords' => true,
                    'comment' => ManagerTheme::getLexicon('fe_editor_lang_message')
                ])

                <div class="split my-1"></div>

                @include('manager::form.input', [
                    'name' => 'editor_css_path',
                    'label' => ManagerTheme::getLexicon('editor_css_path_title'),
                    'small' => '[(editor_css_path)]',
                    'value' => $settings['editor_css_path'],
                    'attributes' => 'onChange="documentDirty=true;" maxlength="255"',
                    'comment' => ManagerTheme::getLexicon('editor_css_path_message')
                ])

                <div class="split my-1"></div>
            </div>
        </div>

        {!! get_by_key($tabEvents, 'OnInterfaceSettingsRender') !!}
    </div>
</div>
