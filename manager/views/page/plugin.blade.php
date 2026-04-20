@extends('manager::template.page')
@section('content')
    @push('scripts.top')
        <script src="media/script/element-properties.js"></script>
        <script>
            var elementProperties = new ElementProperties({
                name: 'elementProperties',
                lang: {
                    parameter: '{{ ManagerTheme::getLexicon('parameter') }}',
                    value: '{{ ManagerTheme::getLexicon('value') }}',
                    set_default: '{{ ManagerTheme::getLexicon('set_default') }}',
                },
                icon_refresh: '{{ ManagerTheme::getStyle('icon_refresh') }}',
                table:'displayparams',
                tr: 'displayparamrow',
                td: 'displayparams',
            });
          var actions = {
            save: function() {
              documentDirty = false;
              form_save = true;
              document.mutate.save.click();
              saveWait('mutate');
            }, duplicate: function() {
              if (confirm('{{ ManagerTheme::getLexicon('confirm_duplicate_record') }}') === true) {
                documentDirty = false;
                document.location.href = "index.php?id={{ $data->getKey() }}&a=105";
              }
            }, delete: function() {
              if (confirm('{{ ManagerTheme::getLexicon('confirm_delete_plugin') }}') === true) {
                documentDirty = false;
                document.location.href = 'index.php?id=' + document.mutate.id.value + '&a=104';
              }
            }, cancel: function() {
              documentDirty = false;
              document.location.href = 'index.php?a=76&tab=4';
            }
          };

          function setTextWrap(ctrl, b)
          {
            if (!ctrl) {
              return;
            }
            ctrl.wrap = (b) ? 'soft' : 'off';
          }

          function getEventsList()
          {
            var cboxes = document.getElementsByName('sysevents[]');
            var len = cboxes.length;
            var s = [];
            for (var i = 0; i < len; i++) {
              if (cboxes[i].checked) {
                s.push(cboxes[i].id);
              }
            }
            return s.join();
          }

          function createAssignEventsButton()
          {
            if (document.getElementById('assignEvents') === null) {
              var button = document.createElement('div');
              button.setAttribute('id', 'assignEvents');
              button.className = 'container container-body';
              button.innerHTML = '<a class="btn btn-primary" href="javascript:;" onclick="assignEvents();return false;">{{ ManagerTheme::getLexicon('set_automatic') }}</a>';
              var tab = document.getElementById('tabEvents');
              tab.insertBefore(button, tab.firstChild);
            }
          }

          function assignEvents()
          {
            // remove all events first
            var sysevents = document.getElementsByName('sysevents[]');
            for (var i = 0; i < sysevents.length; i++) {
              sysevents[i].checked = false;
            }
            // set events
            var events = internal[0]['events'];
            events = events.split(',');
            for (var i = 0; i < events.length; i++) {
              document.getElementById(events[i]).checked = true;
            }
          }

          var internal = {!! $internal !!};

          document.addEventListener('DOMContentLoaded', function() {
            var h1help = document.querySelector('h1 > .help');
            h1help.onclick = function() {
              document.querySelector('.element-edit-message').classList.toggle('show');
            };
          });

        </script>
    @endpush

    <form name="mutate" method="post" action="index.php" enctype="multipart/form-data">
        {!! get_by_key($events, 'OnPluginFormPrerender') !!}

        <input type="hidden" name="a" value="103">
        <input type="hidden" name="id" value="{{ $data->getKey() }}">
        <input type="hidden" name="mode" value="{{ $action }}">

        <h1>
            <i class="{{ $_style['icon_plugin'] }}"></i>
            @if($data->name)
                {{ $data->name }}
                <small>({{ $data->getKey() }})</small>
            @else
                {{ ManagerTheme::getLexicon('new_plugin') }}
            @endif
            <i class="{{ $_style['icon_question_circle'] }} help"></i>
        </h1>

        @include('manager::partials.actionButtons', $actionButtons)

        <div class="container element-edit-message">
            <div class="alert alert-info">{{ ManagerTheme::getLexicon('plugin_msg') }}</div>
        </div>

        <div class="tab-pane" id="pluginPane">
            <script>
              var tpSnippet = new WebFXTabPane(document.getElementById('pluginPane'), {{ get_by_key($modx->config, 'remember_last_tab') ? 1 : 0 }});
            </script>

            <!-- General -->
            <div class="tab-page" id="tabPlugin">
                <h2 class="tab">{{ ManagerTheme::getLexicon('settings_general') }}</h2>
                <script>tpSnippet.addTabPage(document.getElementById('tabPlugin'));</script>

                <div class="container container-body">
                    @include('manager::form.row', [
                        'for' => 'name',
                        'label' => ManagerTheme::getLexicon('plugin_name'),
                        'element' => '<div class="form-control-name clearfix">' .
                            ManagerTheme::view('form.inputElement', [
                                'name' => 'name',
                                'value' => $data->name,
                                'class' => 'form-control-lg',
                                'attributes' => 'onchange="documentDirty=true;" maxlength="100"'
                            ]) .
                            ($modx->hasPermission('save_role')
                            ? '<label class="custom-control" data-tooltip="' . ManagerTheme::getLexicon('lock_plugin') . "\n" . ManagerTheme::getLexicon('lock_plugin_msg') .'">' .
                             ManagerTheme::view('form.inputElement', [
                                'type' => 'checkbox',
                                'name' => 'locked',
                                'checked' => ($data->locked == 1)
                             ]) .
                             '<i class="' . $_style['icon_lock'] . '"></i>
                             </label>
                             <small class="form-text text-danger hide" id="savingMessage"></small>
                             <script>if (!document.getElementsByName(\'name\')[0].value) document.getElementsByName(\'name\')[0].focus();</script>'
                            : '') .
                            '</div>'
                    ])

                    @include('manager::form.input', [
                        'name' => 'description',
                        'id' => 'description',
                        'label' => ManagerTheme::getLexicon('plugin_desc'),
                        'value' => $data->description,
                        'attributes' => 'onchange="documentDirty=true;" maxlength="255"'
                    ])

                    @include('manager::form.select', [
                        'name' => 'categoryid',
                        'id' => 'categoryid',
                        'label' => ManagerTheme::getLexicon('existing_category'),
                        'value' => $data->category,
                        'first' => [
                            'text' => ''
                        ],
                        'options' => $categories->pluck('category', 'id'),
                        'attributes' => 'onchange="documentDirty=true;"'
                    ])

                    @include('manager::form.input', [
                        'name' => 'newcategory',
                        'id' => 'newcategory',
                        'label' => ManagerTheme::getLexicon('new_category'),
                        'value' => (isset($data->newcategory) ? $data->newcategory : ''),
                        'attributes' => 'onchange="documentDirty=true;" maxlength="45"'
                    ])

                    <div class="form-row">
                        <label for="disabled">
                            @include('manager::form.inputElement', [
                                'type' => 'checkbox',
                                'name' => 'disabled',
                                'value' => 'on',
                                'checked' => ($data->disabled === 1)
                            ])
                            @if($data->disabled == 1)
                                <span class="text-danger">{{ ManagerTheme::getLexicon('plugin_disabled') }}</span>
                            @else
                                {{ ManagerTheme::getLexicon('plugin_disabled') }}
                            @endif
                        </label>
                    </div>

                    <div class="form-row">
                        <label>
                            @include('manager::form.inputElement', [
                                'type' => 'checkbox',
                                'name' => 'parse_docblock',
                                'value' => 1,
                                'checked' => ($action == 101)
                            ])
                            {{ ManagerTheme::getLexicon('parse_docblock') }}
                        </label>
                        <small class="form-text text-muted">{!! ManagerTheme::getLexicon('parse_docblock_msg') !!}</small>
                    </div>
                </div>

                <!-- PHP text editor start -->
                <div class="navbar navbar-editor">
                    <span>{{ ManagerTheme::getLexicon('plugin_code') }}</span>
                </div>
                <div class="section-editor clearfix">
                    @include('manager::form.textareaElement', [
                        'name' => 'post',
                        'value' => $data->plugincode,
                        'class' => 'phptextarea',
                        'rows' => 20,
                        'attributes' => 'onChange="documentDirty=true;" wrap="soft"'
                    ])
                </div>
                <!-- PHP text editor end -->
            </div>

            <!-- Config -->
            <div class="tab-page" id="tabConfig">
                <h2 class="tab">{{ ManagerTheme::getLexicon('settings_config') }}</h2>
                <script>tpSnippet.addTabPage(document.getElementById('tabConfig'));</script>

                <div class="container container-body">
                    <div class="form-group">
                        <a href="javascript:;" class="btn btn-primary" onclick="elementProperties.setDefaults(this);return false;">{{ ManagerTheme::getLexicon('set_default_all') }}</a>
                    </div>
                    <div id="displayparamrow">
                        <div id="displayparams"></div>
                    </div>
                </div>
            </div>

            <!-- Properties -->
            <div class="tab-page" id="tabProps">
                <h2 class="tab">{{ ManagerTheme::getLexicon('settings_properties') }}</h2>
                <script>tpSnippet.addTabPage(document.getElementById('tabProps'));</script>

                <div class="container container-body">
                    <div class="form-group">
                        @include('manager::form.select', [
                            'name' => 'moduleguid',
                            'label' => ManagerTheme::getLexicon('import_params'),
                            'value' => $data->moduleguid,
                            'first' => [
                                'text' => ''
                            ],
                            'options' => $importParams,
                            'attributes' => 'onchange="documentDirty=true;"',
                            'comment' => ManagerTheme::getLexicon('import_params_msg')
                        ])
                    </div>
                    <div class="form-group">
                        <a href="javascript:;" class="btn btn-primary" onclick="tpSnippet.pages[1].select();elementProperties.showParameters(this);return false;">{{ ManagerTheme::getLexicon('update_params') }}</a>
                    </div>
                </div>

                <!-- HTML text editor start -->
                <div class="section-editor clearfix">
                    @include('manager::form.textareaElement', [
                        'name' => 'properties',
                        'value' => $data->properties,
                        'class' => 'phptextarea',
                        'rows' => 20,
                        'attributes' => 'onChange="documentDirty=true;elementProperties.showParameters(this);"'
                    ])
                </div>
                <!-- HTML text editor end -->
            </div>

            <!-- System Events -->

            <div class="tab-page" id="tabEvents">
                <h2 class="tab">{{ ManagerTheme::getLexicon('settings_events') }}</h2>
                <script>tpSnippet.addTabPage(document.getElementById('tabEvents'));</script>

                <div class="container container-body">
                    <p>{{ ManagerTheme::getLexicon('plugin_event_msg') }}</p>
                    <?php
                    // get selected events
                    if ($data->getKey() > 0) {
                        $evts=\EvolutionCMS\Models\SitePluginEvent::select('evtid')->where('pluginid',$data->getKey())->pluck('evtid')->toArray();
                    } else {
                        if (isset($data->sysevents) && is_array($data->sysevents)) {
                            $evts = $data->sysevents;
                        } else {
                            $evts = [];
                        }
                    }

                    // display system events
                    $evtnames = [];
                    $services = [
                        "Parser Service Events",
                        "Manager Access Events",
                        "Web Access Service Events",
                        "Cache Service Events",
                        "Template Service Events",
                        "User Defined Events"
                    ];
                    $eventNames = \EvolutionCMS\Models\SystemEventname::query()
                        ->orderBy('service', 'DESC')->orderBy('groupname', 'ASC')->orderBy('name', 'ASC');

                    if ($eventNames->count() == 0) {

                        echo "";
                    } else {
                        $srv = null;
                        $grp = null;

                        foreach($eventNames->get()->toArray() as $row) {

                            // display records
                            if ($srv != $row['service']) {
                                $srv = $row['service'];
                                if (count($evtnames) > 0) {
                                    echoEventRows($evtnames);
                                }
                                echo '<hr class="clear">';
                                echo '<div class="form-group"><b>' . $services[$srv - 1] . '</b></div>';
                            }
                            // display group name
                            if ($grp != $row['groupname']) {
                                $grp = $row['groupname'];
                                if (count($evtnames) > 0) {
                                    echoEventRows($evtnames);
                                }
                                echo '<hr class="clear">';
                                echo '<div class="form-group"><b>' . $row['groupname'] . '</b></div>';
                            }
                            $evtnames[] = '<input name="sysevents[]" id="' . $row['name'] . '" type="checkbox" ' . (in_array($row['id'], $evts) ? ' checked="checked" ' : '') . 'class="inputBox" value="' . $row['id'] . '" /> <label for="' . $row['name'] . '" ' . bold(in_array($row['id'], $evts)) . '> ' . $row['name'] . '</label>' . "\n";
                            if (count($evtnames) == 2) {
                                echoEventRows($evtnames);
                            }
                        }
                    }
                    if (count($evtnames) > 0) {
                        echoEventRows($evtnames);
                    }

                    ?>
                </div>
            </div>

            <!-- docBlock Info -->
            <div class="tab-page" id="tabDocBlock">
                <h2 class="tab">{{ ManagerTheme::getLexicon('information') }}</h2>
                <script>tpSnippet.addTabPage(document.getElementById('tabDocBlock'));</script>

                <div class="container container-body">
                    {!! $docBlockList  !!}
                </div>
            </div>

            <input type="submit" name="save" style="display:none">

            {!! get_by_key($events, 'OnPluginFormRender') !!}
        </div>
    </form>
    <script>setTimeout(function(){
        elementProperties.showParameters();
        createAssignEventsButton();
    }, 10);
    </script>
@endsection
