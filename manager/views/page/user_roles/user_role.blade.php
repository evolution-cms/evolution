@extends('manager::template.page')
@section('content')

    <form name="userform" method="post" action="index.php" enctype="multipart/form-data">
        <input type="hidden" name="a" value="36">
        <input type="hidden" name="mode" value="<?= EvolutionCMS()->getManagerApi()->action ?>">
        <input type="hidden" name="id" value="<?= isset($_GET['id']) ? (int)$_GET['id'] : '' ?>">

        <h1>
            <i class="{{ $_style['icon_role'] }}"></i>@if(isset($role->name)){{$role->name}} <small>({{$role->id}})</small> @else {{ ManagerTheme::getLexicon('role_title') }}@endif
        </h1>

        {{ ManagerTheme::styleHtml('actionbuttons.dynamic.savedelete') }}

        <div class="tab-pane" id="rolePane">
            <script>
                var tp = new WebFXTabPane(document.getElementById('rolePane'), {{ get_by_key(EvolutionCMS()->config, 'remember_last_tab') ? 1 : 0 }});
            </script>

            <div class="tab-page" id="roleMain">
                <h2 class="tab">{{ ManagerTheme::getLexicon('role') }}</h2>

                <script>
                    tp.addTabPage(document.getElementById('roleMain'));
                </script>

                <div class="container container-body">
                    <div class="form-group">
                        <div class="row form-row">
                            <div class="col-md-3 col-lg-2">{{ ManagerTheme::getLexicon('role_name') }}:</div>
                            <div class="col-md-9 col-lg-10"><input class="form-control form-control-lg" name="name" type="text" maxlength="50" @if(isset($_POST['name'])) value="{{$_POST['name']}}"  @else value="{{$role->name}}" @endif/></div>
                        </div>
                        <div class="row form-row">
                            <div class="col-md-3 col-lg-2">{{ ManagerTheme::getLexicon('resource_description') }}:</div>
                            <div class="col-md-9 col-lg-10"><input name="description" type="text" maxlength="255" @if(isset($_POST['description'])) value="{{$_POST['description']}}"  @else value="{{$role->description}}" @endif size="60"/></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-page" id="rolePermissions">
                <h2 class="tab">{{ ManagerTheme::getLexicon('manage_permission') }}</h2>

                <script>
                    tp.addTabPage(document.getElementById('rolePermissions'));
                </script>

                <div class="container container-body">
                    <div class="row">
                        @foreach($groups as $group)
                            <div class="col-sm-6 col-lg-3">
                                <div class="form-group">

                                    <h3> {{ ManagerTheme::getLexicon($group->lang_key, $group->name) }}</h3>
                                    @foreach($group->permissions as $permission)
                                    <label class="d-block" for="{{$permission->key}}_check">
                                        @include('manager::form.inputElementRole', [
                                            'type' => 'checkbox',
                                            'name' => 'permissions['.$permission->key.']',
                                            'id' => $permission->key.'_check',
                                            'value' => 1,
                                            'checked' => (isset($permissionsRole[$permission->key]) || $permission->disabled) ? 1 : 0,
                                            'disabled' => $permission->disabled,
                                            'class' => 'click'
                                        ])
                                        {{ ManagerTheme::getLexicon($permission->lang_key, $permission->name) }}
                                    </label>

                                    @endforeach

                                </div>
                            </div>
                            @if($loop->iteration/4 == 0)
                                </div>
                                <hr>
                            @endif
                        @endforeach

                    </div>

                </div>
            </div>

            <div class="tab-page" id="tabAssignedTVs">
                <h2 class="tab">{{ ManagerTheme::getLexicon('template_assignedtv_tab') }}</h2>
                <script>tp.addTabPage(document.getElementById('tabAssignedTVs'));</script>
                <input type="hidden" name="tvsDirty" id="tvsDirty" value="0">

                <div class="container container-body">
                    @if($role->tvs->count() > 0)
                        <p>{{ ManagerTheme::getLexicon('role_tv_msg') }}</p>
                    @endif

                    @if($role->tvs->count() > 0)
                        <ul>
                            @foreach($role->tvs as $item)
                                @include('manager::page.template.tv', [
                                    'item' => $item,
                                    'tvSelected' => [$item->getKey()]
                                ])
                            @endforeach
                        </ul>
                    @else
                        {{ ManagerTheme::getLexicon('role_no_tv') }}
                    @endif

                    @if($tvOutCategory->count() || $categoriesWithTv->count())
                        <hr>
                        <p>{{ ManagerTheme::getLexicon('role_notassigned_tv') }}</p>
                    @endif

                    @if($tvOutCategory->count() > 0)
                        @component('manager::partials.panelCollapse', ['name' => 'tv_in_template', 'id' => 0, 'title' => ManagerTheme::getLexicon('tmplvars')])
                            <ul>
                                @foreach($tvOutCategory as $item)
                                    @include('manager::page.template.tv', compact('item', 'tvSelected'))
                                @endforeach
                            </ul>
                        @endcomponent
                    @endif

                    @foreach($categoriesWithTv as $cat)
                        @component('manager::partials.panelCollapse', ['name' => 'tv_in_template', 'id' => $cat->id, 'title' => $cat->name])
                            <ul>
                                @foreach($cat->tvs as $item)
                                    @if(! $role->tvs->contains('id', $item->getKey()))
                                        @include('manager::page.template.tv', compact('item', 'tvSelected'))
                                    @endif
                                @endforeach
                            </ul>
                        @endcomponent
                    @endforeach
                </div>
            </div>
        </div>

        <input type="submit" name="save" style="display:none">
    </form>


@endsection


@push('scripts.bot')

    <script type="text/javascript">
        function changestate(element) {
            documentDirty = true;
            if (parseInt(element.value) === 1) {
                element.value = 0;
            } else {
                element.value = 1;
            }
        }

        var actions = {
            save: function () {
                documentDirty = false;
                form_save = true;
                document.userform.save.click();
            },
            delete: function () {
                if (confirm("{{ ManagerTheme::getLexicon('confirm_delete_role') }}") === true) {
                    document.location.href = "index.php?id=" + document.userform.id.value + "&a=35&action=delete";
                }
            },
            cancel: function () {
                documentDirty = false;
                document.location.href = 'index.php?a=86';
            }
        }

    </script>
@endpush