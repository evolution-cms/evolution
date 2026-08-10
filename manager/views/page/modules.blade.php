@extends('manager::template.page')

@section('content')
    <h1>
        <i class="<?= ManagerTheme::getStyle('icon_modules') ?>"></i>{{ __('global.module_management') }}<i
                class="<?= ManagerTheme::getStyle('icon_question_circle') ?> help"></i>
    </h1>

    {{ ManagerTheme::styleHtml('actionbuttons.dynamic.newmodule') }}

    <div class="container element-edit-message">
        <div class="alert alert-info">{{ ManagerTheme::lexiconHtml('module_management_msg') }}</div>
    </div>

    <div class="tab-page">
        <div class="table-responsive">
            <table class="table data">
                <thead>
                <tr>
                    <td class="tableHeader" style="width: 34px;">{{ __('global.icon') }}</td>
                    <td class="tableHeader">{{ __('global.name') }}</td>
                    <td class="tableHeader">{{ __('global.description') }}</td>
                    <td class="tableHeader" style="width: 60px;">{{ __('global.locked') }}</td>
                    <td class="tableHeader" style="width: 60px;">{{ __('global.disabled') }}</td>
                </tr>
                </thead>
                <tbody>
                    @foreach($modules as $module)
                        <tr>
                            <td class="tableItem text-center" style="width: 34px;">
                                @if(evo()->hasAnyPermissions(['edit_module', 'exec_module']))
                                    <a class="tableRowIcon" href="javascript:;" onclick="return showContentMenu({{ $module->getKey() }}, event);" title="{{ __('global.click_to_context') }}">
                                        <i class="{{ !empty($module->icon) ? $module->icon : 'fa fa-cube' }}"></i>
                                    </a>
                                @else
                                    <i class="{{ !empty($module->icon) ? $module->icon : 'fa fa-cube' }}"></i>
                                @endif
                            </td>
                            <td class="tableItem">
                                @if(evo()->hasAnyPermissions(['edit_module']))
                                    <a href="index.php?a=108&id={{ $module->getKey() }}" title="{{ __('global.module_edit_click_title') }}">{{ $module->name }}</a>
                                @else
                                    {{ $module->name }}
                                @endif
                            </td>
                            <td class="tableItem">{{ safe_html($module->description) }}</td>
                            <td class="tableItem text-center" style="width: 60px;">
                                @if($module->locked)
                                    {{ __('global.yes') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="tableItem text-center" style="width: 60px;">
                                @if($module->disabled)
                                    {{ __('global.yes') }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts.bot')
    {!! $contextMenu['menu'] !!}

    <script>
      var selectedItem;
      var contextm = {!! $contextMenu['script'] !!};

      function showContentMenu(id, e) {
        selectedItem = id;
        contextm.style.left = (e.pageX || (e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft))) {{ ManagerTheme::getTextDir('+10') }} + 'px'; //offset menu if RTL is selected
        contextm.style.top = (e.pageY || (e.clientY + (document.documentElement.scrollTop || document.body.scrollTop))) + 'px';
        contextm.style.visibility = 'visible';
        e.cancelBubble = true;
        return false;
      };

      function menuAction(a) {
        var id = selectedItem;
        switch (a) {
          case 1:		// run module
            dontShowWorker = true; // prevent worker from being displayed
            window.location.href = 'index.php?a=112&id=' + id;
            break;
          case 2:		// edit
            window.location.href = 'index.php?a=108&id=' + id;
            break;
          case 3:		// duplicate
            if (confirm('{{ __('global.confirm_duplicate_record') }}') === true) {
              window.location.href = 'index.php?a=111&id=' + id;
            }
            break;
          case 4:		// delete
            if (confirm('{{ __('global.confirm_delete_module') }}') === true) {
              window.location.href = 'index.php?a=110&id=' + id;
            }
            break;
        }
      }

      document.addEventListener('click', function() {
        contextm.style.visibility = 'hidden';
      });

      var actions = {
        new: function() {
          document.location.href = 'index.php?a=107';
        },
      };

      document.querySelector('h1 > .help').onclick = function() {
        document.querySelector('.element-edit-message').classList.toggle('show');
      };

    </script>
@endpush
