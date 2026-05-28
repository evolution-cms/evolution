<!-- Interface & editor settings -->
<div class="tab-page" id="tabPageSecurity">
    <h2 class="tab">{{ __('global.settings_security') }}</h2>
    <script type="text/javascript">tpSettings.addTabPage(document.getElementById('tabPageSecurity'));</script>
    <div class="container container-body">

        @include('manager::form.radio', [
            'name' => 'use_udperms',
            'label' => __('global.udperms_title'),
            'small' => '[(use_udperms)]',
            'value' => $settings['use_udperms'],
            'options' => [
                1 => [
                    'text' => __('global.yes'),
                    'attributes' => 'id="udPermsOn"'
                ],
                0 => [
                    'text' => __('global.no'),
                    'attributes' => 'id="udPermsOff"'
                ]
            ],
            'comment' => (isset($disabledSettings['use_udperms']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.udperms_message'),
            'disabled' => $disabledSettings['use_udperms'] ?? null
        ])

        <div class="split my-1"></div>

        <div class="udPerms" @if(!$settings['use_udperms']) style="display: none;" @endif>
            @include('manager::form.radio', [
                'name' => 'udperms_allowroot',
                'label' => __('global.udperms_allowroot_title'),
                'small' => '[(udperms_allowroot)]',
                'value' => $settings['udperms_allowroot'],
                'options' => [
                    1 => __('global.yes'),
                    0 => __('global.no')
                ],
                'comment' => (isset($disabledSettings['udperms_allowroot']) ? __('global.setting_from_file') . '<br>' : '') .
                    __('global.udperms_allowroot_message'),
                'disabled' => $disabledSettings['udperms_allowroot'] ?? null
            ])

            <div class="split my-1"></div>
        </div>

        @include('manager::form.radio', [
            'name' => 'allow_eval',
            'label' => __('global.allow_eval_title'),
            'small' => '[(allow_eval)]',
            'value' => $settings['allow_eval'],
            'options' => [
                'with_scan' =>  __('global.allow_eval_with_scan'),
                'with_scan_at_post' => __('global.allow_eval_with_scan_at_post'),
                'everytime_eval' => __('global.allow_eval_everytime_eval'),
                'dont_eval' => __('global.allow_eval_dont_eval'),
            ],
            'comment' => (isset($disabledSettings['allow_eval']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.allow_eval_msg'),
            'disabled' => $disabledSettings['allow_eval'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'safe_functions_at_eval',
            'label' => __('global.safe_functions_at_eval_title'),
            'small' => '[(safe_functions_at_eval)]',
            'value' => $settings['safe_functions_at_eval'],
            'attributes' => 'onchange="documentDirty=true;"',
            'comment' => (isset($disabledSettings['safe_functions_at_eval']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.safe_functions_at_eval_msg'),
            'disabled' => $disabledSettings['safe_functions_at_eval'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.textarea', [
            'name' => 'check_files_onlogin',
            'label' => __('global.check_files_onlogin_title'),
            'small' => '[(check_files_onlogin)]',
            'value' => $settings['check_files_onlogin'],
            'comment' => (isset($disabledSettings['check_files_onlogin']) ? __('global.setting_from_file') . '<br>' : ''),
            'disabled' => $disabledSettings['check_files_onlogin'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'validate_referer',
            'label' => __('global.validate_referer_title'),
            'small' => '[(validate_referer)]',
            'value' => $settings['validate_referer'],
            'options' => [
                1 =>  __('global.yes'),
                0 => __('global.no')
            ],
            'comment' => (isset($disabledSettings['validate_referer']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.validate_referer_message'),
            'disabled' => $disabledSettings['validate_referer'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'error_reporting',
            'label' => __('global.a17_error_reporting_title'),
            'small' => '[(error_reporting)]',
            'value' => $settings['error_reporting'],
            'options' => [
                0 =>  __('global.a17_error_reporting_opt0'),
                1 => __('global.a17_error_reporting_opt1'),
                2 => __('global.a17_error_reporting_opt2'),
                99 => __('global.a17_error_reporting_opt99'),
                199 => __('global.a17_error_reporting_opt199'),
            ],
            'comment' => (isset($disabledSettings['error_reporting']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.a17_error_reporting_msg'),
            'disabled' => $disabledSettings['error_reporting'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'send_errormail',
            'label' => __('global.mutate_settings.dynamic.php6'),
            'small' => '[(send_errormail)]',
            'value' => $settings['send_errormail'],
            'options' => [
                0 =>  __('global.mutate_settings.dynamic.php7'),
                3 => 'error',
                2 => 'error + warning',
                1 => 'error + warning + information',
            ],
            'comment' => (isset($disabledSettings['send_errormail']) ? __('global.setting_from_file') . '<br>' : '') .
                str_replace('[+emailsender+]', $settings['emailsender'], __('global.mutate_settings.dynamic.php8')),
            'disabled' => $disabledSettings['send_errormail'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'enable_bindings',
            'label' => __('global.enable_bindings_title'),
            'small' => '[(enable_bindings)]',
            'value' => $settings['enable_bindings'],
            'options' => [
                1 =>  __('global.yes'),
                0 =>  __('global.no'),
            ],
            'comment' => (isset($disabledSettings['enable_bindings']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.enable_bindings_message') . '<br><br>' . __('global.check_files_onlogin_message'),
            'disabled' => $disabledSettings['enable_bindings'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'failed_login_attempts',
            'label' => __('global.failed_login_title'),
            'small' => '[(failed_login_attempts)]',
            'value' => $settings['failed_login_attempts'],
            'attributes' => 'onchange="documentDirty=true;"',
            'comment' => (isset($disabledSettings['failed_login_attempts']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.failed_login_message'),
            'disabled' => $disabledSettings['failed_login_attempts'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'blocked_minutes',
            'label' => __('global.blocked_minutes_title'),
            'small' => '[(blocked_minutes)]',
            'value' => $settings['blocked_minutes'],
            'attributes' => 'onchange="documentDirty=true;"',
            'comment' => (isset($disabledSettings['blocked_minutes']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.blocked_minutes_message'),
            'disabled' => $disabledSettings['blocked_minutes'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'pwd_hash_algo',
            'label' => __('global.pwd_hash_algo_title'),
            'small' => '[(pwd_hash_algo)]',
            'value' => $settings['pwd_hash_algo'],
            'options' => $passwordsHash,
            'comment' => (isset($disabledSettings['pwd_hash_algo']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.pwd_hash_algo_message'),
            'disabled' => $disabledSettings['pwd_hash_algo'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'use_captcha',
            'label' => __('global.captcha_title'),
            'small' => '[(use_captcha)]',
            'value' => $settings['use_captcha'],
            'disabled' => !$gdAvailable,
            'options' => [
                1 =>  [
                    'text' => __('global.yes'),
                    'attributes' => 'id="captchaOn"',
                ],
                0 =>  [
                    'text' => __('global.no'),
                    'attributes' => 'id="captchaOff"'
                ]
            ],
            'comment' => (isset($disabledSettings['use_captcha']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.captcha_message'),
            'disabled' => $disabledSettings['use_captcha'] ?? null
        ])

        <div class="split my-1"></div>

        <div class="captchaRow" @if(!$settings['use_captcha']) style="display: none;" @endif>
            @include('manager::form.textarea', [
                'name' => 'captcha_words',
                'label' => __('global.captcha_words_title') . '<br>' .
                    __('global.update_settings_from_language') .
                    view('manager::form.selectElement', [
                        'name' => 'reload_captcha_words',
                        'id' => 'reload_captcha_words_select',
                        'class' => 'form-control-sm',
                        'attributes' => 'onchange="confirmLangChange(this, \'captcha_words_default\', \'captcha_words_input\');"',
                        'first' => [
                            'text' => __('global.language_title')
                        ],
                        'options' => $langKeys,
                        'as' => 'values',
                        'ucwords' => true,
                        'disabled' => $disabledSettings['captcha_words'] ?? null
                    ]),
                'small' => '[(captcha_words)]',
                'value' => $settings['captcha_words'],
                'comment' => (isset($disabledSettings['captcha_words']) ? __('global.setting_from_file') . '<br>' : '') .
                    __('global.captcha_words_message') .
                    view('manager::form.inputElement', [
                        'type' => 'hidden',
                        'name' => 'captcha_words_default',
                        'id' => 'captcha_words_default_hidden',
                        'value' => addslashes(__('global.captcha_words_default'))
                    ]),
                'disabled' => $disabledSettings['captcha_words'] ?? null
            ])

            <div class="split my-1"></div>
        </div>

        {!! get_by_key($tabEvents, 'OnSecuritySettingsRender') !!}
    </div>
</div>
