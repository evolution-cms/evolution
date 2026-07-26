<!-- User settings -->
<div class="tab-page" id="tabPage4">
    <h2 class="tab">{{ __('global.settings_email_templates') }}</h2>
    <script type="text/javascript">tpSettings.addTabPage(document.getElementById('tabPage4'));</script>
    <div class="container container-body">

        @include('manager::form.input', [
            'name' => 'emailsender',
            'label' => __('global.emailsender_title'),
            'small' => '[(emailsender)]',
            'value' => $settings['emailsender'],
            'attributes' => 'onchange="documentDirty=true;" maxlength="255"',
            'comment' => (isset($disabledSettings['emailsender']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.emailsender_message'),
            'disabled' => $disabledSettings['emailsender'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'email_sender_method',
            'label' => __('global.email_sender_method'),
            'small' => '[(email_sender_method)]',
            'value' => $settings['email_sender_method'],
            'options' => [
                1 => __('global.auto'),
                0 => __('global.use_emailsender')
            ],
            'comment' => (isset($disabledSettings['email_sender_method']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.email_sender_method_message'),
            'disabled' => $disabledSettings['email_sender_method'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'email_method',
            'label' => __('global.email_method_title'),
            'small' => '[(email_method)]',
            'value' => $settings['email_method'],
            'options' => [
                'mail' => [
                    'text' => __('global.email_method_mail'),
                    'attributes' => 'id="useMail"'
                ],
                'smtp' => [
                    'text' => __('global.email_method_smtp'),
                    'attributes' => 'id="useSmtp"'
                ]
            ],
            'comment' => (isset($disabledSettings['email_method']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.email_sender_method_message'),
            'disabled' => $disabledSettings['email_method'] ?? null
        ])

        <div class="split my-1"></div>

        <div class="smtpRow" @if($settings['email_method'] == 'mail') style="display: none;" @endif>
            @include('manager::form.radio', [
                'name' => 'smtp_auth',
                'label' => __('global.smtp_auth_title'),
                'small' => '[(smtp_auth)]',
                'value' => $settings['smtp_auth'],
                'options' => [
                    1 => __('global.yes'),
                    0 => __('global.no')
                ],
                'comment' => (isset($disabledSettings['smtp_auth']) ? __('global.setting_from_file') . '<br>' : ''),
                'disabled' => $disabledSettings['smtp_auth'] ?? null
            ])

            <div class="split my-1"></div>

            @include('manager::form.radio', [
                'name' => 'smtp_autotls',
                'label' => __('global.smtp_autotls_title'),
                'small' => '[(smtp_autotls)]',
                'value' => $settings['smtp_autotls'] ?? 0,
                'options' => [
                    1 => __('global.yes'),
                    0 => __('global.no')
                ],
                'comment' => (isset($disabledSettings['smtp_autotls']) ? __('global.setting_from_file') . '<br>' : ''),
                'disabled' => $disabledSettings['smtp_autotls'] ?? null
            ])

            <div class="split my-1"></div>

            @include('manager::form.select', [
                'name' => 'smtp_secure',
                'label' => __('global.smtp_secure_title'),
                'small' => '[(smtp_secure)]',
                'value' => $settings['smtp_secure'],
                'attributes' => 'onChange="documentDirty=true;" size="1"',
                'options' => [
                    'none' => __('global.no'),
                    'ssl' => 'SSL',
                    'tls' => 'TLS',
                ],
                'comment' => (isset($disabledSettings['smtp_secure']) ? __('global.setting_from_file') . '<br>' : ''),
                'disabled' => $disabledSettings['smtp_secure'] ?? null
            ])

            <div class="split my-1"></div>

            @include('manager::form.input', [
                'name' => 'smtp_host',
                'label' => __('global.smtp_host_title'),
                'small' => '[(smtp_host)]',
                'value' => $settings['smtp_host'],
                'attributes' => 'onchange="documentDirty=true;" maxlength="255"',
                'comment' => (isset($disabledSettings['smtp_host']) ? __('global.setting_from_file') . '<br>' : ''),
                'disabled' => $disabledSettings['smtp_host'] ?? null
            ])

            <div class="split my-1"></div>

            @include('manager::form.input', [
                'name' => 'smtp_port',
                'label' => __('global.smtp_port_title'),
                'small' => '[(smtp_port)]',
                'value' => $settings['smtp_port'],
                'attributes' => 'onchange="documentDirty=true;" maxlength="255"',
                'comment' => (isset($disabledSettings['smtp_port']) ? __('global.setting_from_file') . '<br>' : ''),
                'disabled' => $disabledSettings['smtp_port'] ?? null
            ])

            <div class="split my-1"></div>

            @include('manager::form.input', [
                'name' => 'smtp_username',
                'label' => __('global.smtp_username_title'),
                'small' => '[(smtp_username)]',
                'value' => $settings['smtp_username'],
                'attributes' => 'onchange="documentDirty=true;" maxlength="255"',
                'comment' => (isset($disabledSettings['smtp_username']) ? __('global.setting_from_file') . '<br>' : ''),
                'disabled' => $disabledSettings['smtp_username'] ?? null
            ])

            <div class="split my-1"></div>

            @include('manager::form.input', [
                'name' => 'smtppw',
                'label' => __('global.smtp_password_title'),
                'small' => '[(smtppw)]',
                'value' => '********************',
                'attributes' => 'onchange="documentDirty=true;" maxlength="255" autocomplete="off"',
                'comment' => (isset($disabledSettings['smtppw']) ? __('global.setting_from_file') . '<br>' : ''),
                'disabled' => $disabledSettings['smtppw'] ?? null
            ])

            <div class="split my-1"></div>
        </div>

        <div class="row form-row form-element-input" id="mailTestPanel" data-endpoint="index.php" data-action="{{ \EvolutionCMS\Controllers\MailTest::ACTION_ID }}" data-token="{{ csrf_token() }}">
            <label class="control-label col-5 col-md-3 col-lg-2" for="mailTestDestination">
                {{ __('global.mail_test_title') }}
            </label>
            <div class="col-7 col-md-9 col-lg-10">
                <div class="input-group">
                    <input
                        type="email"
                        class="form-control"
                        id="mailTestDestination"
                        form="mailTestForm"
                        maxlength="254"
                        autocomplete="email"
                        required
                        placeholder="{{ __('global.mail_test_destination') }}"
                    >
                    <div class="input-group-append">
                        <button class="btn btn-secondary" type="button" id="mailTestSend" form="mailTestForm">
                            {{ __('global.mail_test_send') }}
                        </button>
                    </div>
                </div>
                <small class="form-text text-muted">{{ __('global.mail_test_help') }}</small>
                <div class="alert mt-2 mb-0" id="mailTestFeedback" role="status" aria-live="polite" hidden></div>
            </div>
        </div>

        <div class="split my-1"></div>

        @include('manager::form.input', [
            'name' => 'emailsubject',
            'id' => 'emailsubject_field',
            'for' => 'emailsubject_field',
            'label' => __('global.emailsubject_title') . '<br>' .
                __('global.update_settings_from_language') .
                view('manager::form.selectElement', [
                    'name' => 'reload_emailsubject',
                    'id' => 'reload_emailsubject_select',
                    'class' => 'form-control-sm',
                    'attributes' => 'onchange="confirmLangChange(this, \'emailsubject_default\', \'emailsubject_field\');"',
                    'first' => [
                        'text' => __('global.language_title')
                    ],
                    'options' => $langKeys,
                    'as' => 'values',
                    'ucwords' => true,
                    'disabled' => $disabledSettings['emailsubject'] ?? null
                ]) .
                view('manager::form.inputElement', [
                    'type' => 'hidden',
                    'name' => 'emailsubject_default',
                    'id' => 'emailsubject_default_hidden',
                    'value' => addslashes(__('global.emailsubject_default'))
                ])
            ,
            'small' => '[(emailsubject)]',
            'value' => $settings['emailsubject'],
            'attributes' => 'onchange="documentDirty=true;" maxlength="255"',
            'comment' => (isset($disabledSettings['emailsubject']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.emailsubject_message'),
            'disabled' => $disabledSettings['emailsubject'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.textarea', [
            'name' => 'websignupemail_message',
            'id' => 'websignupemail_message_textarea',
            'for' => 'websignupemail_message_textarea',
            'label' => __('global.websignupemail_title') . '<br>' .
                __('global.update_settings_from_language') .
                view('manager::form.selectElement', [
                    'name' => 'reload_websignupemail_message',
                    'id' => 'reload_websignupemail_message_select',
                    'class' => 'form-control-sm',
                    'attributes' => 'onchange="confirmLangChange(this, \'system_email_websignup\', \'websignupemail_message_textarea\');"',
                    'first' => [
                        'text' => __('global.language_title')
                    ],
                    'options' => $langKeys,
                    'as' => 'values',
                    'ucwords' => true,
                    'disabled' => $disabledSettings['websignupemail_message'] ?? null
                ]) .
                view('manager::form.inputElement', [
                    'type' => 'hidden',
                    'name' => 'system_email_websignup_default',
                    'id' => 'system_email_websignup_hidden',
                    'value' => addslashes(__('global.system_email_websignup'))
                ])
            ,
            'small' => '[(websignupemail_message)]',
            'value' => $settings['websignupemail_message'],
            'rows' => 5,
            'attributes' => 'onchange="documentDirty=true;"',
            'comment' => (isset($disabledSettings['websignupemail_message']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.websignupemail_message'),
            'disabled' => $disabledSettings['websignupemail_message'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.textarea', [
            'name' => 'webpwdreminder_message',
            'id' => 'system_email_webreminder_textarea',
            'for' => 'system_email_webreminder_textarea',
            'label' => __('global.webpwdreminder_title') . '<br>' .
                __('global.update_settings_from_language') .
                view('manager::form.selectElement', [
                    'name' => 'reload_system_email_webreminder_message',
                    'id' => 'reload_system_email_webreminder_select',
                    'class' => 'form-control-sm',
                    'attributes' => 'onchange="confirmLangChange(this, \'system_email_webreminder\', \'system_email_webreminder_textarea\');"',
                    'first' => [
                        'text' => __('global.language_title')
                    ],
                    'options' => $langKeys,
                    'as' => 'values',
                    'ucwords' => true,
                    'disabled' => $disabledSettings['webpwdreminder_message'] ?? null
                ]) .
                view('manager::form.inputElement', [
                    'type' => 'hidden',
                    'name' => 'system_email_webreminder_default',
                    'id' => 'system_email_webreminder_hidden',
                    'value' => addslashes(__('global.system_email_webreminder'))
                ])
            ,
            'small' => '[(webpwdreminder_message)]',
            'value' => $settings['webpwdreminder_message'],
            'rows' => 5,
            'attributes' => 'onchange="documentDirty=true;"',
            'comment' => (isset($disabledSettings['webpwdreminder_message']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.webpwdreminder_message'),
            'disabled' => $disabledSettings['webpwdreminder_message'] ?? null
        ])

        <div class="split my-1"></div>

        @include('manager::form.radio', [
            'name' => 'allow_multiple_emails',
            'label' => __('global.allow_multiple_emails_title'),
            'small' => '[(allow_multiple_emails)]',
            'value' => $settings['allow_multiple_emails'],
            'options' => [
                1 => __('global.yes'),
                0 => __('global.no')
            ],
            'comment' => (isset($disabledSettings['allow_multiple_emails']) ? __('global.setting_from_file') . '<br>' : '') .
                __('global.allow_multiple_emails_message'),
            'disabled' => $disabledSettings['allow_multiple_emails'] ?? null
        ])

        <div class="split my-1"></div>

        {!! get_by_key($tabEvents, 'OnUserSettingsRender') !!}
    </div>
</div>

@push('scripts.bot')
    <script>
        (function() {
            var panel = document.getElementById('mailTestPanel');
            var destination = document.getElementById('mailTestDestination');
            var sendButton = document.getElementById('mailTestSend');
            var feedback = document.getElementById('mailTestFeedback');

            if (!panel || !destination || !sendButton || !feedback) {
                return;
            }

            function showFeedback(message, success) {
                feedback.textContent = message;
                feedback.className = 'alert mt-2 mb-0 ' + (success ? 'alert-success' : 'alert-danger');
                feedback.hidden = false;
            }

            sendButton.addEventListener('click', function() {
                if (!destination.checkValidity()) {
                    destination.reportValidity();
                    return;
                }

                sendButton.disabled = true;
                feedback.hidden = true;

                var body = new URLSearchParams();
                body.set('a', panel.dataset.action);
                body.set('_token', panel.dataset.token);
                body.set('destination', destination.value.trim());

                fetch(panel.dataset.endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: body.toString()
                })
                    .then(function(response) {
                        return response.json()
                            .catch(function() {
                                return {};
                            })
                            .then(function(payload) {
                                if (!response.ok || payload.success !== true) {
                                    throw new Error(payload.message || '{{ __('global.mail_test_error') }}');
                                }

                                return payload;
                            });
                    })
                    .then(function(payload) {
                        showFeedback(payload.message, true);
                    })
                    .catch(function(error) {
                        showFeedback(error.message || '{{ __('global.mail_test_error') }}', false);
                    })
                    .finally(function() {
                        sendButton.disabled = false;
                    });
            });
        })();
    </script>
@endpush
