@extends('manager::template.page')
@section('content')

            <form name="userform" method="post" action="index.php" enctype="multipart/form-data">
                <input type="hidden" name="a" value="136">
                <input type="hidden" name="mode" value="<?= EvolutionCMS()->getManagerApi()->action ?>">
                <input type="hidden" name="id" value="<?= isset($_GET['id']) ? (int)$_GET['id'] : '' ?>">

                <h1>
                    <i class="fa fa-user-tag"></i>@if(isset($groups->name)){{$groups->name}} <small>({{$groups->id}})</small> @else {{ ManagerTheme::getLexicon('groups_permission_title') }}@endif
                </h1>

                {{ ManagerTheme::styleHtml('actionbuttons.dynamic.savedelete') }}

                <div class="tab-page">
                    <div class="container container-body">
                        <div class="form-group">
                            <div class="row form-row">
                                <div class="col-md-3 col-lg-2">{{ ManagerTheme::getLexicon('cm_category_name') }}:</div>
                                <div class="col-md-9 col-lg-10"><input class="form-control form-control-lg" name="name" type="text"
                                                                       maxlength="50" value="{{$groups->name}}"/></div>
                            </div>
                            <div class="row form-row">
                                <div class="col-md-3 col-lg-2">{{ ManagerTheme::getLexicon('lang_key_desc') }}:</div>
                                <div class="col-md-9 col-lg-10"><input name="lang_key" type="text" maxlength="255"
                                                                       value="{{$groups->lang_key}}" size="60"/></div>
                            </div>
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
                if (confirm("{{ ManagerTheme::getLexicon('confirm_delete_category') }}") === true) {
                    document.location.href = "index.php?id=" + document.userform.id.value + "&a=136&action=delete";
                }
            },
            cancel: function () {
                documentDirty = false;
                document.location.href = 'index.php?a=86&tab=1';
            }
        }

    </script>
@endpush