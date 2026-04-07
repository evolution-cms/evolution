<!-- Interface & editor settings -->
<div class="tab-page" id="tabPage5">
    <h2 class="tab">{{ __('global.settings_ui') }}</h2>
    <script type="text/javascript">
        tpSettings.addTabPage(document.getElementById('tabPage5'));
    </script>
    <div class="container container-body">

        @include('manager::form.select', [
            'name' => 'manager_language',
            'label' => __('global.language_title'),
            'small' => '[(manager_language)]',
            'value' => $settings['manager_language'],
            'options' => $langKeys,
            'as' => 'values',
            'ucwords' => false,
            'str_to_upper' => true,
            'attributes' => 'onChange="documentDirty=true;" size="1"',
            'comment' => __('global.language_message'),
        ])

        <div class="split my-1"></div>

        @include('manager::form.select', [
            'name' => 'modx_charset',
            'label' => __('global.charset_title'),
            'small' => '[(modx_charset)]',
            'value' => $settings['modx_charset'],
            'attributes' => 'onChange="documentDirty=true;" size="1"',
            'options' => include EVO_CORE_PATH . '/factory/charsets.php',
            'comment' =>
                (isset($disabledSettings['modx_charset']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.charset_message'),
            'disabled' => $disabledSettings['modx_charset'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.select', [
            'name' => 'manager_theme',
            'label' => __('global.manager_theme'),
            'small' => '[(manager_theme)]',
            'value' => $settings['manager_theme'],
            'attributes' =>
                'onChange="documentDirty=true; document.forms[\'settings\'].theme_refresher.value = Date.parse(new Date());" size="1"',
            'options' => $themes,
            'ucwords' => true,
            'comment' =>
                (isset($disabledSettings['manager_theme']) ? __('global.setting_from_file') . '<br>' : '') .
                '<input type="hidden" name="theme_refresher" value="" />',
            'disabled' => $disabledSettings['manager_theme'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'manager_theme_mode',
            'label' => __('global.manager_theme_mode'),
            'small' => '[(manager_theme_mode)]',
            'value' => $settings['manager_theme_mode'],
            'options' => [
                1 => __('global.manager_theme_mode1'),
                2 => __('global.manager_theme_mode2'),
                3 => __('global.manager_theme_mode3'),
                4 => __('global.manager_theme_mode4'),
            ],
            'comment' =>
                (isset($disabledSettings['manager_theme_mode']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.manager_theme_mode_message'),
            'disabled' => $disabledSettings['manager_theme_mode'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.row', [
            'label' => __('global.login_logo_title'),
            'small' => '[(login_logo)]',
            'for' => 'login_logo',
            'element' =>
                '
                                        <div class="col-md-8">
                                            <div class="input-group">' .
                view('manager::form.inputElement', [
                    'name' => 'login_logo',
                    'value' => $settings['login_logo'],
                    'attributes' => 'onChange="documentDirty=true;"',
                    'disabled' => $disabledSettings['login_logo'] ?? null,
                ]) .
                '<div class="input-group-btn">' .
                view('manager::form.inputElement', [
                    'type' => 'button',
                    'value' => __('global.insert'),
                    'attributes' => 'onclick="BrowseServer(\'login_logo\')"',
                    'disabled' => $disabledSettings['login_logo'] ?? null,
                ]) .
                '</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <img name="login_logo" style="max-height: 48px" src="' .
                ($settings['login_logo'] ? EVO_SITE_URL . $settings['login_logo'] : '') .
                '" />
                                        </div>',
            'comment' =>
                (isset($disabledSettings['login_logo']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.login_logo_message'),
        ])

        <div class="split my-1"></div>

        @include('manager::form.row', [
            'label' => __('global.login_bg_title'),
            'small' => '[(login_bg)]',
            'for' => 'login_bg',
            'element' =>
                '
                                        <div class="col-md-8">
                                            <div class="input-group">' .
                view('manager::form.inputElement', [
                    'name' => 'login_bg',
                    'value' => $settings['login_bg'],
                    'attributes' => 'onChange="documentDirty=true;"',
                    'disabled' => $disabledSettings['login_bg'] ?? null,
                ]) .
                '<div class="input-group-btn">' .
                view('manager::form.inputElement', [
                    'type' => 'button',
                    'value' => __('global.insert'),
                    'attributes' => 'onclick="BrowseServer(\'login_bg\')"',
                    'disabled' => $disabledSettings['login_bg'] ?? null,
                ]) .
                '</div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-right">
                                            <img name="login_bg" style="max-height: 48px" src="' .
                ($settings['login_bg'] ? EVO_SITE_URL . $settings['login_bg'] : '') .
                '" />
                                        </div>',
            'comment' =>
                (isset($disabledSettings['login_bg']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.login_bg_message'),
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'login_form_position',
            'label' => __('global.login_form_position_title'),
            'small' => '[(login_form_position)]',
            'value' => $settings['login_form_position'],
            'options' => [
                'left' => __('global.login_form_position_left'),
                'center' => __('global.login_form_position_center'),
                'right' => __('global.login_form_position_right'),
            ],
            'comment' => isset($disabledSettings['login_form_position'])
                ? __('global.setting_from_file') . '<br>'
                : '',
            'disabled' => $disabledSettings['login_form_position'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'login_form_style',
            'label' => __('global.login_form_style'),
            'small' => '[(login_form_style)]',
            'value' => $settings['login_form_style'],
            'options' => [
                'dark' => __('global.login_form_style_dark'),
                'light' => __('global.login_form_style_light'),
            ],
            'comment' => isset($disabledSettings['login_form_style'])
                ? __('global.setting_from_file') . '<br>'
                : '',
            'disabled' => $disabledSettings['login_form_style'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'manager_menu_position',
            'label' => __('global.manager_menu_position_title'),
            'small' => '[(manager_menu_position)]',
            'value' => $settings['manager_menu_position'],
            'options' => [
                'top' => __('global.manager_menu_position_top'),
                'left' => __('global.manager_menu_position_left'),
            ],
            'comment' => isset($disabledSettings['manager_menu_position'])
                ? __('global.setting_from_file') . '<br>'
                : '',
            'disabled' => $disabledSettings['manager_menu_position'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'show_picker',
            'label' => __('global.show_picker'),
            'small' => '[(show_picker)]',
            'value' => $settings['show_picker'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no'),
            ],
            'comment' =>
                (isset($disabledSettings['show_picker']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.settings_show_picker_message'),
            'disabled' => $disabledSettings['show_picker'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'warning_visibility',
            'label' => __('global.warning_visibility'),
            'small' => '[(warning_visibility)]',
            'value' => $settings['warning_visibility'],
            'options' => [
                0 => __('global.administrators'),
                1 => __('global.everybody'),
            ],
            'comment' =>
                (isset($disabledSettings['warning_visibility']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.warning_visibility_message'),
            'disabled' => $disabledSettings['warning_visibility'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'tree_page_click',
            'label' => __('global.tree_page_click'),
            'small' => '[(tree_page_click)]',
            'value' => $settings['tree_page_click'],
            'options' => [
                27 => __('global.edit_resource'),
                3 => __('global.doc_data_title'),
            ],
            'comment' =>
                (isset($disabledSettings['tree_page_click']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.tree_page_click_message'),
            'disabled' => $disabledSettings['tree_page_click'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'use_breadcrumbs',
            'label' => __('global.use_breadcrumbs'),
            'small' => '[(use_breadcrumbs)]',
            'value' => $settings['use_breadcrumbs'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no'),
            ],
            'comment' =>
                (isset($disabledSettings['use_breadcrumbs']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.use_breadcrumbs_message'),
            'disabled' => $disabledSettings['use_breadcrumbs'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'remember_last_tab',
            'label' => __('global.remember_last_tab'),
            'small' => '[(remember_last_tab)]',
            'value' => $settings['remember_last_tab'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no'),
            ],
            'comment' =>
                (isset($disabledSettings['remember_last_tab']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.remember_last_tab_message'),
            'disabled' => $disabledSettings['remember_last_tab'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'global_tabs',
            'label' => __('global.use_global_tabs'),
            'small' => '[(global_tabs)]',
            'value' => $settings['global_tabs'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no'),
            ],
            'comment' => isset($disabledSettings['global_tabs']) ? __('global.setting_from_file') . '<br>' : '',
            'disabled' => $disabledSettings['global_tabs'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.select', [
            'name' => 'group_tvs',
            'label' => __('global.group_tvs'),
            'small' => '[(group_tvs)]',
            'value' => $settings['group_tvs'],
            'options' => explode(',', __('global.settings_group_tv_options')),
            'attributes' => 'onChange="documentDirty=true;" size="1"',
            'comment' =>
                (isset($disabledSettings['group_tvs']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.settings_group_tv_message'),
            'disabled' => $disabledSettings['group_tvs'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'show_newresource_btn',
            'label' => __('global.show_newresource_btn'),
            'small' => '[(show_newresource_btn)]',
            'value' => $settings['show_newresource_btn'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no'),
            ],
            'comment' =>
                (isset($disabledSettings['show_newresource_btn'])
                    ? __('global.setting_from_file') . '<br>'
                    : '') . __('global.show_newresource_btn_message'),
            'disabled' => $disabledSettings['show_newresource_btn'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'show_fullscreen_btn',
            'label' => __('global.show_fullscreen_btn'),
            'small' => '[(show_fullscreen_btn)]',
            'value' => $settings['show_fullscreen_btn'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no'),
            ],
            'comment' =>
                (isset($disabledSettings['show_fullscreen_btn']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.show_fullscreen_btn_message'),
            'disabled' => $disabledSettings['show_fullscreen_btn'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.select', [
            'name' => 'resource_tree_node_name',
            'label' => __('global.setting_resource_tree_node_name'),
            'small' => '[(resource_tree_node_name)]',
            'value' => $settings['resource_tree_node_name'],
            'options' => [
                'pagetitle' => '[*pagetitle*]',
                'longtitle' => '[*longtitle*]',
                'menutitle' => '[*menutitle*]',
                'alias' => '[*alias*]',
                'createdon' => '[*createdon*]',
                'editedon' => '[*editedon*]',
                'publishedon' => '[*publishedon*]',
            ],
            'attributes' => 'onChange="documentDirty=true;" size="1"',
            'comment' =>
                (isset($disabledSettings['resource_tree_node_name'])
                    ? __('global.setting_from_file') . '<br>'
                    : '') .
                __('global.setting_resource_tree_node_name_desc') .
                '<br /><b>' .
                __('global.setting_resource_tree_node_name_desc_add') .
                '</b>',
            'disabled' => $disabledSettings['resource_tree_node_name'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'session_timeout',
            'label' => __('global.session_timeout'),
            'small' => '[(session_timeout)]',
            'value' => $settings['session_timeout'],
            'comment' =>
                (isset($disabledSettings['session_timeout']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.session_timeout_msg'),
            'disabled' => $disabledSettings['session_timeout'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'tree_show_protected',
            'label' => __('global.tree_show_protected'),
            'small' => '[(tree_show_protected)]',
            'value' => $settings['tree_show_protected'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no'),
            ],
            'comment' =>
                (isset($disabledSettings['tree_show_protected']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.tree_show_protected_message'),
            'disabled' => $disabledSettings['tree_show_protected'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'datepicker_offset',
            'label' => __('global.datepicker_offset'),
            'small' => '[(datepicker_offset)]',
            'value' => $settings['datepicker_offset'],
            'attributes' => 'onChange="documentDirty=true;" maxlength="50"',
            'comment' =>
                (isset($disabledSettings['datepicker_offset']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.datepicker_offset_message'),
            'disabled' => $disabledSettings['datepicker_offset'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.select', [
            'name' => 'datetime_format',
            'label' => __('global.datetime_format'),
            'small' => '[(datetime_format)]',
            'value' => $settings['datetime_format'],
            'attributes' => 'onChange="documentDirty=true;" size="1"',
            'options' => ['dd-mm-YYYY', 'mm/dd/YYYY', 'YYYY/mm/dd'],
            'as' => 'values',
            'comment' =>
                (isset($disabledSettings['datetime_format']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.datetime_format_message'),
            'disabled' => $disabledSettings['datetime_format'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'number_of_logs',
            'label' => __('global.nologentries_title'),
            'small' => '[(number_of_logs)]',
            'value' => $settings['number_of_logs'],
            'attributes' => 'onChange="documentDirty=true;" maxlength="50"',
            'comment' =>
                (isset($disabledSettings['number_of_logs']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.nologentries_message'),
            'disabled' => $disabledSettings['number_of_logs'] ?? null,
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'number_of_results',
            'label' => __('global.noresults_title'),
            'small' => '[(number_of_results)]',
            'value' => $settings['number_of_results'],
            'attributes' => 'onChange="documentDirty=true;" maxlength="50"',
            'comment' =>
                (isset($disabledSettings['number_of_results']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.noresults_message'),
            'disabled' => $disabledSettings['number_of_results'] ?? null,
        ])

        <div class="split my-1"></div>

        <?php
        // invoke OnRichTextEditorRegister event
        $evtOut = app()->invokeEvent('OnRichTextEditorRegister');
        if (!is_array($evtOut)) {
            $evtOut = [];
            $use_editor = 0;
        }
        ?>

        <div @if (empty($evtOut)) style="display: none;" @endif>

            @include('manager::form.radio', [
                'name' => 'use_editor',
                'label' => __('global.use_editor_title'),
                'small' => '[(use_editor)]',
                'value' => $settings['use_editor'],
                'options' => [
                    1 => [
                        'text' => __('global.yes'),
                        'attributes' => 'id="editorRowOn"',
                    ],
                    0 => [
                        'text' => __('global.no'),
                        'attributes' => 'id="editorRowOff"',
                    ],
                ],
                'comment' =>
                    (isset($disabledSettings['use_editor']) ? __('global.setting_from_file') . '<br>' : '') .
                    __('global.use_editor_message'),
                'disabled' => $disabledSettings['use_editor'] ?? null,
            ])

            <div class="split my-1"></div>

            <div class="editorRow" @if (empty($settings['use_editor'])) style="display: none;" @endif>

                @include('manager::form.select', [
                    'name' => 'which_editor',
                    'label' => __('global.which_editor_title'),
                    'small' => '[(which_editor)]',
                    'value' => $settings['which_editor'],
                    'attributes' => 'onChange="documentDirty=true;" size="1"',
                    'first' => [
                        'value' => 'none',
                        'text' => __('global.none'),
                    ],
                    'options' => $evtOut,
                    'as' => 'values',
                    'comment' =>
                        (isset($disabledSettings['which_editor'])
                            ? __('global.setting_from_file') . '<br>'
                            : '') . __('global.which_editor_message'),
                    'disabled' => $disabledSettings['which_editor'] ?? null,
                ])

                <div class="split my-1"></div>

                @include('manager::form.select', [
                    'name' => 'fe_editor_lang',
                    'label' => __('global.fe_editor_lang_title'),
                    'small' => '[(fe_editor_lang)]',
                    'value' => $settings['fe_editor_lang'],
                    'attributes' => 'onChange="documentDirty=true;" size="1"',
                    'first' => [
                        'text' => __('global.language_title'),
                    ],
                    'options' => $langKeys,
                    'as' => 'values',
                    'ucwords' => true,
                    'comment' =>
                        (isset($disabledSettings['fe_editor_lang'])
                            ? __('global.setting_from_file') . '<br>'
                            : '') . __('global.fe_editor_lang_message'),
                    'disabled' => $disabledSettings['fe_editor_lang'] ?? null,
                ])

                <div class="split my-1"></div>

                @include('manager::form.input', [
                    'name' => 'editor_css_path',
                    'label' => __('global.editor_css_path_title'),
                    'small' => '[(editor_css_path)]',
                    'value' => $settings['editor_css_path'],
                    'attributes' => 'onChange="documentDirty=true;" maxlength="255"',
                    'comment' =>
                        (isset($disabledSettings['editor_css_path'])
                            ? __('global.setting_from_file') . '<br>'
                            : '') . __('global.editor_css_path_message'),
                    'disabled' => $disabledSettings['editor_css_path'] ?? null,
                ])

                <div class="split my-1"></div>
            </div>
        </div>

        {!! get_by_key($tabEvents, 'OnInterfaceSettingsRender') !!}
    </div>
</div>
