@extends('manager::template.page')
@section('content')
    @push('scripts.top')
        <script>
            var displayStyle = '{{ $displayStyle }}';
            var lang_chg = '{{ ManagerTheme::getLexicon('confirm_setting_language_change') }}';
            var actions = {
                save: function() {
                    documentDirty = false;
                    document.settings.submit();
                },
                cancel: function() {
                    documentDirty = false;
                    document.location.href = 'index.php?a=2';
                }
            };
        </script>
        <script src="media/script/mutate_settings.js"></script>
    @endpush
    <form name="settings" method="post" action="index.php">
        <input type="hidden" name="a" value="30">
        <!-- this field is used to check site settings have been entered/ updated after install or upgrade -->
        <input type="hidden" name="site_id" value="{{ get_by_key(EvolutionCMS()->config, 'site_id') }}" />
        <input type="hidden" name="settings_version" value="{{ EvolutionCMS()->getVersionData('version') }}" />
        <h1><i class="{{ $_style['icon_sliders'] }}"></i>{{ ManagerTheme::getLexicon('settings_title') }}</h1>
        @include('manager::partials.actionButtons', $actionButtons)
        @if(!get_by_key(EvolutionCMS()->config, 'settings_version') || get_by_key(EvolutionCMS()->config, 'settings_version') !== EvolutionCMS()->getVersionData('version'))
            <div class="container">
                <p class="alert alert-warning">{!! ManagerTheme::getLexicon('settings_after_install') !!}</p>
            </div>
        @endif
        <div class="tab-pane" id="settingsPane">
            <script>
                tpSettings = new WebFXTabPane(document.getElementById('settingsPane'), {{ get_by_key(EvolutionCMS()->config, 'remember_last_tab') ? 1 : 0 }});
            </script>
            @include('manager::page.system_settings.general')
            @include('manager::page.system_settings.friendly_urls')
            @include('manager::page.system_settings.interface')
            @include('manager::page.system_settings.security')
            @include('manager::page.system_settings.file_manager')
            @include('manager::page.system_settings.file_browser')
            @include('manager::page.system_settings.mail_templates')
        </div>
    </form>
    @push('scripts.bot')
        <script>
            function toggleRows(selector, show) {
                document.querySelectorAll(selector).forEach(function(el) {
                    el.style.display = show ? '' : 'none';
                });
            }

            document.querySelectorAll('input[type="radio"]').forEach(function(el) {
                el.addEventListener('change', function() {
                    documentDirty = true;
                });
            });

            var furlRowOn = document.getElementById('furlRowOn');
            var furlRowOff = document.getElementById('furlRowOff');
            if (furlRowOn) furlRowOn.addEventListener('change', function() { toggleRows('.furlRow', true); });
            if (furlRowOff) furlRowOff.addEventListener('change', function() { toggleRows('.furlRow', false); });

            var udPermsOn = document.getElementById('udPermsOn');
            var udPermsOff = document.getElementById('udPermsOff');
            if (udPermsOn) udPermsOn.addEventListener('change', function() { toggleRows('.udPerms', true); });
            if (udPermsOff) udPermsOff.addEventListener('change', function() { toggleRows('.udPerms', false); });

            var editorRowOn = document.getElementById('editorRowOn');
            var editorRowOff = document.getElementById('editorRowOff');
            if (editorRowOn) editorRowOn.addEventListener('change', function() { toggleRows('.editorRow', true); });
            if (editorRowOff) editorRowOff.addEventListener('change', function() { toggleRows('.editorRow', false); });

            var rbRowOn = document.getElementById('rbRowOn');
            var rbRowOff = document.getElementById('rbRowOff');
            if (rbRowOn) rbRowOn.addEventListener('change', function() { toggleRows('.rbRow', true); });
            if (rbRowOff) rbRowOff.addEventListener('change', function() { toggleRows('.rbRow', false); });

            var useSmtp = document.getElementById('useSmtp');
            var useMail = document.getElementById('useMail');
            if (useSmtp) useSmtp.addEventListener('change', function() { toggleRows('.smtpRow', true); });
            if (useMail) useMail.addEventListener('change', function() { toggleRows('.smtpRow', false); });

            var captchaOn = document.getElementById('captchaOn');
            var captchaOff = document.getElementById('captchaOff');
            if (captchaOn) captchaOn.addEventListener('change', function() { toggleRows('.captchaRow', true); });
            if (captchaOff) captchaOff.addEventListener('change', function() { toggleRows('.captchaRow', false); });

            function setChangesChunkProcessor(item) {
                item = item || document.querySelector('[name="chunk_processor"]:checked');
                document.querySelectorAll('[name="enable_at_syntax"], [name="enable_filter"]').forEach(function(el) {
                    if (item.checked && item.value === 'DLTemplate') {
                        el.checked = !!el.value;
                        el.disabled = true;
                    } else {
                        el.disabled = false;
                    }
                });
            }

            setChangesChunkProcessor();

            document.querySelectorAll('[name="chunk_processor"]').forEach(function(item) {
                item.addEventListener('change', function() {
                    setChangesChunkProcessor(item);
                }, false);
            });
        </script>
        @if(is_numeric(get_by_key($_GET, 'tab')))
            <script>tpSettings.setSelectedIndex({{ $_GET['tab'] }});</script>
        @endif
    @endpush
@endsection
