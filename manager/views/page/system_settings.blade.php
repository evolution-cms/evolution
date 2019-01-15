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
        <input type="hidden" name="site_id" value="{{ get_by_key($modx->config, 'site_id') }}" />
        <input type="hidden" name="settings_version" value="{{ $modx->getVersionData('version') }}" />
        <h1>
            <i class="fa fa-sliders fw"></i>{{ ManagerTheme::getLexicon('settings_title') }}
        </h1>

        @include('manager::partials.actionButtons', $actionButtons)

        @if(!get_by_key($modx->config, 'settings_version') || get_by_key($modx->config, 'settings_version') !== $modx->getVersionData('version'))
            <div class="container">
                <p class="alert alert-warning">{!! ManagerTheme::getLexicon('settings_after_install') !!}</p>
            </div>
        @endif
        <div class="tab-pane" id="settingsPane">
            <script>
              tpSettings = new WebFXTabPane(document.getElementById('settingsPane'), {{ get_by_key($modx->config, 'remember_last_tab') ? 1 : 0 }});
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
          (function($) {
            $('input:radio').change(function() {
              documentDirty = true;
            });
            $('#furlRowOn').change(function() {
              $('.furlRow').fadeIn();
            });
            $('#furlRowOff').change(function() {
              $('.furlRow').fadeOut();
            });
            $('#udPermsOn').change(function() {
              $('.udPerms').slideDown();
            });
            $('#udPermsOff').change(function() {
              $('.udPerms').slideUp();
            });
            $('#editorRowOn').change(function() {
              $('.editorRow').slideDown();
            });
            $('#editorRowOff').change(function() {
              $('.editorRow').slideUp();
            });
            $('#rbRowOn').change(function() {
              $('.rbRow').fadeIn();
            });
            $('#rbRowOff').change(function() {
              $('.rbRow').fadeOut();
            });
            $('#useSmtp').change(function() {
              $('.smtpRow').fadeIn();
            });
            $('#useMail').change(function() {
              $('.smtpRow').fadeOut();
            });
            $('#captchaOn').change(function() {
              $('.captchaRow').fadeIn();
            });
            $('#captchaOff').change(function() {
              $('.captchaRow').fadeOut();
            });
          })(jQuery);
        </script>

        <script>
          var lastImageCtrl;
          var lastFileCtrl;

          function OpenServerBrowser(url, width, height)
          {
            var iLeft = (screen.width - width) / 2;
            var iTop = (screen.height - height) / 2;

            var sOptions = 'toolbar=no,status=no,resizable=yes,dependent=yes';
            sOptions += ',width=' + width;
            sOptions += ',height=' + height;
            sOptions += ',left=' + iLeft;
            sOptions += ',top=' + iTop;

            var oWindow = window.open(url, 'FCKBrowseWindow', sOptions);
          }

          function BrowseServer(ctrl)
          {
            lastImageCtrl = ctrl;
            var w = screen.width * 0.7;
            var h = screen.height * 0.7;
            OpenServerBrowser('{{ MODX_MANAGER_URL }}media/browser/{{ $modx->getConfig('which_browser') }}/browser.php?Type=images', w, h);
          }

          function BrowseFileServer(ctrl)
          {
            lastFileCtrl = ctrl;
            var w = screen.width * 0.7;
            var h = screen.height * 0.7;
            OpenServerBrowser('{{ MODX_MANAGER_URL }}media/browser/{{ $modx->getConfig('which_browser') }}/browser.php?Type=files', w, h);
          }

          function SetUrlChange(el)
          {
            if ('createEvent' in document) {
              var evt = document.createEvent('HTMLEvents');
              evt.initEvent('change', false, true);
              el.dispatchEvent(evt);
            } else {
              el.fireEvent('onchange');
            }
          }

          function SetUrl(url, width, height, alt)
          {
            if (lastFileCtrl) {
              var c = document.getElementById(lastFileCtrl);
              if (c && c.value != url) {
                c.value = url;
                SetUrlChange(c);
              }
              lastFileCtrl = '';
            } else if (lastImageCtrl) {
              var c = document.getElementById(lastImageCtrl);
              if (c && c.value != url) {
                c.value = url;
                SetUrlChange(c);
              }
              lastImageCtrl = '';
            } else {
              return;
            }
          }

          function setChangesChunkProcessor(item)
          {
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
