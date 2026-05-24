@extends('manager::template.page')
@section('content')
    <?php /** @var EvolutionCMS\Models\SiteTmplvar $data */ ?>
    @push('scripts.top')
        <script src="media/script/element-properties.js"></script>
        <script src="media/script/element-name-helper.js"></script>
        <script>
            window.evoElementNameHelperConfig = window.evoElementNameHelperConfig || {};
            window.evoElementNameHelperConfig.endpoint = 'media/style/{{ ManagerTheme::getTheme() }}/ajax.php';

            var defaultProperties = {!! $defaultProperties !!};
            var elementProperties = new ElementProperties({
                name: 'elementProperties',
                lang: {
                    parameter: '{{ ManagerTheme::getLexicon('parameter') }}',
                    value: '{{ ManagerTheme::getLexicon('value') }}',
                    set_default: '{{ ManagerTheme::getLexicon('set_default') }}',
                },
                icon_refresh: '{{ ManagerTheme::getStyle('icon_refresh') }}',
                table:'displayprops',
                tr: 'displaypropsrow',
                td: 'displayprops',
            });

            function changeDefaultProperties(ctrl) {
                var f;
                if (ctrl && ctrl.form) {
                    f = ctrl.form;
                } else {
                    f = document.forms['mutate'];
                    if (!f) {
                        return;
                    }
                }
                // check if codemirror is used
                var currentProps = typeof myCodeMirrors != 'undefined' && typeof myCodeMirrors['properties'] != 'undefined' ? myCodeMirrors['properties'].getValue() : f.properties.value;
                try {
                    currentProps = JSON.parse(currentProps);
                } catch (e) {
                    currentProps = {};
                }
                if (typeof defaultProperties[ctrl.value] !== 'undefined' && (JSON.stringify(savedProperties) === '{}' || JSON.stringify(currentProps) === '{}')) {
                    var stringified = JSON.stringify(defaultProperties[ctrl.value], null, 2);
                    if (typeof myCodeMirrors != 'undefined') {
                        myCodeMirrors['properties'].setValue(stringified);
                    } else {
                        f.properties.value = stringified;
                    }
                    elementProperties.showParameters();
                    elementProperties.setDefaults();
                };
            }

          function check_toggle(target)
          {
            var el = document.getElementsByName(target + '[]');
            var count = el.length;
            for (var i = 0; i < count; i++) {
              el[i].checked = !el[i].checked;
            }
          };

          function check_none(target)
          {
            var el = document.getElementsByName(target + '[]');
            var count = el.length;
            for (var i = 0; i < count; i++) {
              el[i].checked = false;
            }
          };

          function check_all(target)
          {
            var el = document.getElementsByName(target + '[]');
            var count = el.length;
            for (var i = 0; i < count; i++) {
              el[i].checked = true;
            }
          };

          var actions = {
            save: function() {
              documentDirty = false;
              form_save = true;
              document.mutate.save.click();
              saveWait('mutate');
            },
            duplicate: function() {
              if (confirm("{{ ManagerTheme::getLexicon('confirm_duplicate_record') }}") === true) {
                documentDirty = false;
                document.location.href = "index.php?id={{ $data->getKey() }}&a=304";
              }
            },
            delete: function() {
              if (confirm("{{ ManagerTheme::getLexicon('confirm_delete_tmplvars') }}") === true) {
                documentDirty = false;
                document.location.href = 'index.php?id=' + document.mutate.id.value + '&a=303';
              }
            },
            cancel: function() {
              documentDirty = false;
              document.location.href = 'index.php?a={{ $origin }}@if(!empty($originId))&id={{ $originId}}@endif&tab=1';
            }
          };

          // Widget Parameters
          var widgetParams = {};          // name = description;datatype;default or list values - datatype: int, string, list : separated by comma (,)
          widgetParams['date'] = '&format=Date Format;string;%A %d, %B %Y &default=If no value, use current date;list;Yes,No;No';
          widgetParams['string'] = '&format=String Format;list;Upper Case,Lower Case,Sentence Case,Capitalize';
          widgetParams['delim'] = '&format=Delimiter;string;,';
          widgetParams['hyperlink'] = '&text=Display Text;string; &title=Title;string; &class=Class;string &style=Style;string &target=Target;string &attrib=Attributes;string';
          widgetParams['htmltag'] = '&tagname=Tag Name;string;div &tagid=Tag ID;string &class=Class;string &style=Style;string &attrib=Attributes;string';
          widgetParams['viewport'] = '&vpid=ID/Name;string &width=Width;string;100 &height=Height;string;100 &borsize=Border Size;int;1 &sbar=Scrollbars;list;,Auto,Yes,No &asize=Auto Size;list;,Yes,No &aheight=Auto Height;list;,Yes,No &awidth=Auto Width;list;,Yes,No &stretch=Stretch To Fit;list;,Yes,No &class=Class;string &style=Style;string &attrib=Attributes;string';
          widgetParams['datagrid'] = '&cols=Column Names;string &flds=Field Names;string &cwidth=Column Widths;string &calign=Column Alignments;string &ccolor=Column Colors;string &ctype=Column Types;string &cpad=Cell Padding;int;1 &cspace=Cell Spacing;int;1 &rowid=Row ID Field;string &rgf=Row Group Field;string &rgstyle = Row Group Style;string &rgclass = Row Group Class;string &rowsel=Row Select;string &rhigh=Row Hightlight;string; &psize=Page Size;int;100 &ploc=Pager Location;list;top-right,top-left,bottom-left,bottom-right,both-right,both-left; &pclass=Pager Class;string &pstyle=Pager Style;string &head=Header Text;string &foot=Footer Text;string &tblc=Grid Class;string &tbls=Grid Style;string &itmc=Item Class;string &itms=Item Style;string &aitmc=Alt Item Class;string &aitms=Alt Item Style;string &chdrc=Column Header Class;string &chdrs=Column Header Style;string;&egmsg=Empty message;string;No records found;';
          widgetParams['richtext'] = '&w=Width;string;100% &h=Height;string;300px &edt=Editor;list;{!! get_by_key($events, 'OnRichTextEditorRegister') !!}';
          widgetParams['image'] = '&alttext=Alternate Text;string &hspace=H Space;int &vspace=V Space;int &borsize=Border Size;int &align=Align;list;none,baseline,top,middle,bottom,texttop,absmiddle,absbottom,left,right &name=Name;string &class=Class;string &id=ID;string &style=Style;string &attrib=Attributes;string';
          widgetParams['custom_widget'] = '&output=Output;textarea;[+value+]';

          // Current Params
          var currentParams = {};
          var lastdf, lastmod = {};

          function showParameters(ctrl)
          {
            var c, p, df, cp;
            var ar, desc, value, key, dt;

            currentParams = {}; // reset;

            if (ctrl && ctrl.form) {
              f = ctrl.form;
            } else {
              f = document.forms['mutate'];
              if (!f) return;
              ctrl = f.display;
            }
            cp = f.params.value.split('&'); // load current setting once

            // get display format
            df = lastdf = ctrl.options[ctrl.selectedIndex].value;

            // load last modified param values
            if (lastmod[df]) cp = lastmod[df].split('&');
            for (p = 0; p < cp.length; p++) {
              cp[p] = (cp[p] + '').replace(/^\s|\s$/, ''); // trim
              ar = cp[p].split('=');
              currentParams[ar[0]] = ar[1];
            }

            // setup parameters
            var tr = document.getElementById('displayparamrow'), t, td,
                dp = (widgetParams[df]) ? widgetParams[df].split('&') : '';
            if (!dp) {
              tr.style.display = 'none';
            } else {
              t = '<table class="displayparams"><thead><tr><td width="50%">{{ ManagerTheme::getLexicon('parameter') }}</td><td width="50%">{{ ManagerTheme::getLexicon('value') }}</td></tr></thead>';
              for (p = 0; p < dp.length; p++) {
                dp[p] = (dp[p] + '').replace(/^\s|\s$/, ''); // trim
                ar = dp[p].split('=');
                key = ar[0];     // param
                ar = (ar[1] + '').split(';');
                desc = ar[0];   // description
                dt = ar[1];     // data type
                value = decode((currentParams[key]) ? currentParams[key] : (dt === 'list') ? ar[3] : (ar[2]) ? ar[2] : '');
                if (value !== currentParams[key]) currentParams[key] = value;
                value = (value + '').replace(/^\s|\s$/, ''); // trim
                value = value.replace(/\"/g, '&quot;'); // replace double quotes with &quot;
                if (dt) {
                  switch (dt) {
                    case 'int':
                    case 'float':
                      c = '<input type="text" name="prop_' + key + '" value="' + value + '" size="30" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)" />';
                      break;
                    case 'list':
                      c = '<select name="prop_' + key + '" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)">';
                      var ls = (ar[2] + '').split(',');
                      if (!currentParams[key] || currentParams[key] === 'undefined') {
                        currentParams[key] = ls[0]; // use first list item as default
                      }
                      for (var i = 0; i < ls.length; i++) {
                        c += '<option value="' + ls[i] + '"' + ((ls[i] === value) ? ' selected="selected"' : '') + '>' + ls[i] + '</option>';
                      }
                      c += '</select>';
                      break;
                    case 'textarea':
                      c = '<textarea class="inputBox phptextarea" name="prop_' + key + '" cols="25" style="width:220px;" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)" >' + value + '</textarea>';
                      break;
                    default:  // string
                      c = '<input type="text" name="prop_' + key + '" value="' + value + '" size="30" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)" />';
                      break;
                  }
                  t += '<tr><td bgcolor="#FFFFFF" width="50%">' + desc + '</td><td bgcolor="#FFFFFF" width="50%">' + c + '</td></tr>';
                }
                ;
              }
              t += '</table>';
              td = (document.getElementById) ? document.getElementById('displayparams') : document.all['displayparams'];
              td.innerHTML = t;
              tr.style.display = '';
            }
            implodeParameters();
          }

          function setParameter(key, dt, ctrl)
          {
            var v;
            if (!ctrl) return null;
            switch (dt) {
              case 'int':
                ctrl.value = parseInt(ctrl.value);
                if (isNaN(ctrl.value)) ctrl.value = 0;
                v = ctrl.value;
                break;
              case 'float':
                ctrl.value = parseFloat(ctrl.value);
                if (isNaN(ctrl.value)) ctrl.value = 0;
                v = ctrl.value;
                break;
              case 'list':
                v = ctrl.options[ctrl.selectedIndex].value;
                break;
              case 'textarea':
                v = ctrl.value + '';
                break;
              default:
                v = ctrl.value + '';
                break;
            }
            currentParams[key] = v;
            implodeParameters();
          }

          function resetParameters()
          {
            document.mutate.params.value = '';
            lastmod[lastdf] = '';
            showParameters();
          }

          // implode parameters
          function implodeParameters()
          {
            var v, p, s = '';
            for (p in currentParams) {
              v = currentParams[p];
              if (v) s += '&' + p + '=' + encode(v);
            }
            document.forms['mutate'].params.value = s;
            if (lastdf) lastmod[lastdf] = s;
          }

          function encode(s)
          {
            s = s + '';
            s = s.replace(/\=/g, '%3D'); // =
            s = s.replace(/\&/g, '%26'); // &
            return s;
          }

          function decode(s)
          {
            s = s + '';
            s = s.replace(/\%3D/g, '='); // =
            s = s.replace(/\%26/g, '&'); // &
            return s;
          }

          document.addEventListener('DOMContentLoaded', function() {
            var h1help = document.querySelector('h1 > .help');
            h1help.onclick = function() {
              document.querySelector('.element-edit-message').classList.toggle('show');
            };

            if (window.evoElementNameHelper) {
              window.evoElementNameHelper.installTvBindingPickers('#elements, #default_text');
            }
          });

        </script>
    @endpush

    <form name="mutate" method="post" action="index.php" enctype="multipart/form-data">
        {!! get_by_key($events, 'OnTVFormPrerender') !!}

        <input type="hidden" name="id" value="{{ $data->getKey() }}">
        <input type="hidden" name="a" value="302">
        <input type="hidden" name="or" value="{{ $origin }}">
        <input type="hidden" name="oid" value="{{ $originId }}">
        <input type="hidden" name="mode" value="{{ $action }}">
        <input type="hidden" name="params" value="{{ $data->display_params }}">

        <h1>
            <i class="{{ $_style['icon_tv'] }}"></i>
            @if($data->name)
                {{ $data->name }}
                <small>({{ $data->getKey() }})</small>
            @else
                {{ ManagerTheme::getLexicon('new_tmplvars') }}
            @endif
            <i class="{{ $_style['icon_question_circle'] }} help"></i>
        </h1>

        @include('manager::partials.actionButtons', $actionButtons)

        <div class="container element-edit-message">
            <div class="alert alert-info">{!! ManagerTheme::getLexicon('tmplvars_msg') !!}</div>
        </div>

        <div class="tab-pane" id="tmplvarsPane">
            <script>
              var tpTmplvars = new WebFXTabPane(document.getElementById('tmplvarsPane'), false);
            </script>

            <div class="tab-page" id="tabGeneral">
                <h2 class="tab">{{ ManagerTheme::getLexicon('settings_general') }}</h2>
                <script>tpTmplvars.addTabPage(document.getElementById('tabGeneral'));</script>

                <div class="container container-body">
                    @include('manager::form.row', [
                        'for' => 'name',
                        'label' => ManagerTheme::getLexicon('tmplvars_name'),
                        'element' => '<div class="form-control-name clearfix">' .
                            ManagerTheme::view('form.inputElement', [
                                'name' => 'name',
                                'value' => $data->name,
                                'class' => 'form-control-lg',
                                'attributes' => 'onchange="documentDirty=true;" maxlength="50"'
                            ]) .
                            ($modx->hasPermission('save_role')
                            ? '<label class="custom-control" data-tooltip="' . ManagerTheme::getLexicon('lock_tmplvars') . "\n" . ManagerTheme::getLexicon('lock_tmplvars_msg') .'">' .
                             ManagerTheme::view('form.inputElement', [
                                'type' => 'checkbox',
                                'name' => 'locked',
                                'checked' => ($data->locked == 1)
                             ]) .
                             '<i class="'. $_style['icon_lock'] .'"></i>
                             </label>
                             <small class="form-text text-danger hide" id="savingMessage"></small>
                             <script>if (!document.getElementsByName(\'name\')[0].value) document.getElementsByName(\'name\')[0].focus();</script>'
                            : '') .
                            '</div>'
                    ])

                    @include('manager::form.input', [
                        'name' => 'caption',
                        'id' => 'caption',
                        'label' => ManagerTheme::getLexicon('tmplvars_caption'),
                        'value' => $data->caption,
                        'attributes' => 'onchange="documentDirty=true;" maxlength="80"'
                    ])

                    @include('manager::form.input', [
                        'name' => 'description',
                        'id' => 'description',
                        'label' => ManagerTheme::getLexicon('tmplvars_description'),
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

                    @include('manager::form.select', [
                        'name' => 'type',
                        'id' => 'type',
                        'label' => ManagerTheme::getLexicon('tmplvars_type'),
                        'value' => $data->type,
                        'options' => $types,
                        'attributes' => 'onchange="changeDefaultProperties(this);documentDirty=true;"'
                    ])

                    @include('manager::form.textarea', [
                        'name' => 'elements',
                        'id' => 'elements',
                        'label' => ManagerTheme::getLexicon('tmplvars_elements'),
                        'small' => ManagerTheme::getLexicon('tmplvars_binding_msg'),
                        'value' => $data->elements,
                        'attributes' => 'onchange="documentDirty=true;"'
                    ])

                    @include('manager::form.textarea', [
                        'name' => 'default_text',
                        'id' => 'default_text',
                        'label' => ManagerTheme::getLexicon('tmplvars_default'),
                        'small' => ManagerTheme::getLexicon('tmplvars_binding_msg'),
                        'value' => $data->default_text,
                        'attributes' => 'onchange="documentDirty=true;"'
                    ])

                    @include('manager::form.select', [
                        'name' => 'display',
                        'id' => 'display',
                        'label' => ManagerTheme::getLexicon('tmplvars_widget'),
                        'value' => $data->display,
                        'first' => [
                            'text' => ''
                        ],
                        'options' => $display,
                        'attributes' => 'onchange="documentDirty=true;showParameters(this);"'
                    ])

                    <div class="row form-row" id="displayparamrow">
                        <label class="col-md-3 col-lg-2">{{ ManagerTheme::getLexicon('tmplvars_widget_prop') }}<br />
                            <a href="javascript:;" onclick="resetParameters(); return false">
                                <i class="<?= $_style['icon_refresh'] ?>"
                                    data-tooltip="{{ ManagerTheme::getLexicon('tmplvars_reset_params') }}"></i>
                            </a>
                        </label>
                        <div id="displayparams" class="col-md-9 col-lg-10"></div>
                    </div>

                @include('manager::form.input', [
                    'name' => 'rank',
                    'id' => 'rank',
                    'label' => ManagerTheme::getLexicon('tmplvars_rank'),
                    'value' => (isset($data->rank) ? $data->rank : 0),
                    'attributes' => 'onchange="documentDirty=true;" maxlength="4" size="1"'
                ])

                <!-- Access Permissions -->

                </div>
            </div>

           <!-- Config -->
            <div class="tab-page" id="tabConfig">
                <h2 class="tab">{{ ManagerTheme::getLexicon('settings_config') }}</h2>
                <script>tpTmplvars.addTabPage(document.getElementById('tabConfig'));</script>

                <div class="container container-body">
                    <div class="form-group">
                        <a href="javascript:;" class="btn btn-primary" onclick="elementProperties.setDefaults(this);return false;">{{ ManagerTheme::getLexicon('set_default_all') }}</a>
                    </div>
                    <div id="displaypropsrow">
                        <div id="displayprops"></div>
                    </div>
                </div>
            </div>

            <!-- Properties -->
            <div class="tab-page" id="tabProps">
                <h2 class="tab">{{ ManagerTheme::getLexicon('settings_properties') }}</h2>
                <script>tpTmplvars.addTabPage(document.getElementById('tabProps'));</script>
                <div class="container container-body">
                    <div class="form-group">
                        <a href="javascript:;" class="btn btn-primary" onclick="tpTmplvars.pages[1].select();elementProperties.showParameters(this);return false;">{{ ManagerTheme::getLexicon('update_params') }}</a>
                    </div>
                </div>

                <!-- HTML text editor start -->
                <div class="section-editor clearfix">
                    @include('manager::form.textareaElement', [
                        'name' => 'properties',
                        'value' => json_encode($data->properties),
                        'class' => 'phptextarea',
                        'rows' => 20,
                        'attributes' => 'onChange="documentDirty=true;elementProperties.showParameters(this);"'
                    ])
                </div>
                <!-- HTML text editor end -->
            </div>

            <div class="tab-page" id="tabTemplates">
                <h2 class="tab">{{ ManagerTheme::getLexicon('manage_templates') }}</h2>
                <script>tpTmplvars.addTabPage(document.getElementById('tabTemplates'));</script>

                <div class="container container-body">
                    <p>{{ ManagerTheme::getLexicon('tmplvar_tmpl_access_msg') }}</p>
                    <div class="form-group">
                        <a class="btn btn-secondary btn-sm" href="javascript:;"
                            onclick="check_all('template');return false;">{{ ManagerTheme::getLexicon('check_all') }}</a>
                        <a class="btn btn-secondary btn-sm" href="javascript:;"
                            onclick="check_none('template');return false;">{{ ManagerTheme::getLexicon('check_none') }}</a>
                        <a class="btn btn-secondary btn-sm" href="javascript:;"
                            onclick="check_toggle('template'); return false;">{{ ManagerTheme::getLexicon('check_toggle') }}</a>
                    </div>

                    @if(isset($tplOutCategory) && $tplOutCategory->count() > 0)
                        @component('manager::partials.panelCollapse', ['name' => 'tv_in_template', 'id' => 0, 'title' => ManagerTheme::getLexicon('no_category')])
                            <ul>
                                <?php /** @var EvolutionCMS\Models\SiteTemplate $item */ ?>
                                @foreach($tplOutCategory as $item)
                                    @include('manager::page.tmplvar.template', ['item' => $item, 'selected' => $controller->isSelectedTemplate($item)])
                                @endforeach
                            </ul>
                        @endcomponent
                    @endif

                    @if(isset($categoriesWithTpl))
                        @foreach($categoriesWithTpl as $cat)
                            @component('manager::partials.panelCollapse', ['name' => 'tv_in_template', 'id' => $cat->id, 'title' => $cat->name])
                                <ul>
                                    @foreach($cat->templates as $item)
                                        @include('manager::page.tmplvar.template', ['item' => $item, 'selected' => $controller->isSelectedTemplate($item)])
                                    @endforeach
                                </ul>
                            @endcomponent
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="tab-page" id="tabRoles">
                <h2 class="tab">{{ ManagerTheme::getLexicon('role_management_title') }}</h2>
                <script>tpTmplvars.addTabPage(document.getElementById('tabRoles'));</script>

                <div class="container container-body">
                    <p>{{ ManagerTheme::getLexicon('tmplvar_roles_access_msg') }}</p>
                    <div class="form-group">
                        <a class="btn btn-secondary btn-sm" href="javascript:;"
                            onclick="check_all('role');return false;">{{ ManagerTheme::getLexicon('check_all') }}</a>
                        <a class="btn btn-secondary btn-sm" href="javascript:;"
                            onclick="check_none('role');return false;">{{ ManagerTheme::getLexicon('check_none') }}</a>
                        <a class="btn btn-secondary btn-sm" href="javascript:;"
                            onclick="check_toggle('role'); return false;">{{ ManagerTheme::getLexicon('check_toggle') }}</a>
                    </div>

                    @if(isset($roles) && $roles->count() > 0)
                        @component('manager::partials.panelCollapse', ['name' => 'tv_in_roles', 'id' => 0, 'title' => ManagerTheme::getLexicon('role_management_title')])
                            <ul>
                                <?php /** @var EvolutionCMS\Models\SiteTemplate $item */ ?>
                                @foreach($roles as $item)
                                    @include('manager::page.tmplvar.role', ['item' => $item, 'selected' => $controller->isSelectedRole($item)])
                                @endforeach
                            </ul>
                        @endcomponent
                    @endif
                </div>
            </div>

            @if($modx->getConfig('use_udperms') && $modx->hasAnyPermissions(['manage_groups', 'manage_tv_permissions']))
                <div class="tab-page" id="tabAccess">
                    <h2 class="tab">{{ ManagerTheme::getLexicon('access_permissions') }}</h2>
                    <script>tpTmplvars.addTabPage(document.getElementById('tabAccess'));</script>

                    <div class="container container-body">

                        <script>
                          function makePublic(b)
                          {
                            var notPublic = false;
                            var f = document.forms['mutate'];
                            var chkpub = f['chkalldocs'];
                            var chks = f['docgroups[]'];
                            if (!chks && chkpub) {
                              chkpub.checked = true;
                              return false;
                            }
                            else if (!b && chkpub) {
                              if (!chks.length) {
                                notPublic = chks.checked;
                              } else {
                                for (var i = 0; i < chks.length; i++) {
                                  if (chks[i].checked) notPublic = true;
                                }
                              }
                              chkpub.checked = !notPublic;
                            }
                            else {
                              if (!chks.length) {
                                chks.checked = (b) ? false : chks.checked;
                              } else {
                                for (var i = 0; i < chks.length; i++) {
                                  if (b) chks[i].checked = false;
                                }
                              }
                              chkpub.checked = true;
                            }
                          }
                        </script>

                        <p>{{ ManagerTheme::getLexicon('tmplvar_access_msg') }}</p>

                        <?php

                        if (empty($groupsArray) && isset($_POST['docgroups']) && is_array($_POST['docgroups']) && empty($_POST['id'])) {
                            $groupsArray = $_POST['docgroups'];
                        }
                        $documentGroupNames = \EvolutionCMS\Models\DocumentgroupName::all()->toArray();
                        $chks = '';
                        foreach ($documentGroupNames as $row) {
                            $checked = in_array($row['id'], $groupsArray);
                            if ($checked) {
                                $notPublic = true;
                            }
                            $chks .= "<li><label><input type='checkbox' name='docgroups[]' value='" . $row['id'] . "' " . ($checked ? "checked='checked'" : '') . " onclick=\"makePublic(false)\" /> " . $row['name'] . "</label></li>";
                        }

                        $chks = "<li><label><input type='checkbox' name='chkalldocs' " . (empty($notPublic) ? "checked='checked'" : '') . " onclick=\"makePublic(true)\" /> <span class='warning'>" . ManagerTheme::getLexicon('all_doc_groups') . "</span></label></li>" . $chks;

                        echo '<ul>' . $chks . '</ul>';

                        ?>
                    </div>
                </div>
            @endif
            <input type="submit" name="save" style="display:none">

            {!! get_by_key($events, 'OnTVFormRender') !!}
        </div>
    </form>
    <script>
        var savedProperties = {!! json_encode($data->properties) !!};
        setTimeout(function () {
            showParameters();
            elementProperties.showParameters();
        }, 10);
    </script>
@endsection
