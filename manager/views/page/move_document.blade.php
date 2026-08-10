@extends('manager::template.page')
@section('content')
    <?php /** @var EvolutionCMS\Models\SiteContent $document */ ?>
    @push('scripts.top')
        <script language="javascript">

            parent.tree.ca = 'move';

            var actions = {
                save: function() {
                    documentDirty = false;
                    document.newdocumentparent.submit();
                },
                cancel: function() {
                    documentDirty = false;
                    document.location.href = "index.php?a=3&id={{ $document->getKey() }}";
                }
            };

            function setMoveValue(pId, pName)
            {
                if (pId === 0 || checkParentChildRelation(pId, pName)) {
                    documentDirty = true;
                    document.newdocumentparent.new_parent.value = pId;
                    document.getElementById('parentName').innerHTML = '{{ ManagerTheme::getLexicon('new_parent') }}: <span class="text-primary"><b>' + pId + '</b> (' + pName + ')</span>';
                }
            }

            // check if the selected parent is a child of this document
            function checkParentChildRelation(pId, pName)
            {
                var sp;
                var id = document.newdocumentparent.id.value;
                var tdoc = parent.tree.document;
                var pn = (tdoc.getElementById) ? tdoc.getElementById('node' + pId) : tdoc.all['node' + pId];
                if (!pn) {
                    return;
                }
                if (pn.id.substr(4) === id) {
                    alert('{{ ManagerTheme::getLexicon('illegal_parent_self') }}');
                    return;
                } else {
                    while (pn.p > 0) {
                        pn = (tdoc.getElementById) ? tdoc.getElementById('node' + pn.p) : tdoc.all['node' + pn.p];
                        if (pn.id.substr(4) === id) {
                            alert('{{ ManagerTheme::getLexicon('illegal_parent_child') }}');
                            return;
                        }
                    }
                }
                return true;
            }

        </script>
    @endpush
    <h1>
        <i class="{{ $_style['icon_move'] }}"></i>{{ $document->pagetitle }} <small>({{ $document->getKey() }})</small>
    </h1>

    {{ ManagerTheme::styleHtml('actionbuttons.dynamic.save') }}

    <div class="tab-page">
        <div class="container container-body">
            <p class="alert alert-info">{{ ManagerTheme::getLexicon('move_resource_message') }}</p>
            <form method="post" action="index.php" name="newdocumentparent">
                <input type="hidden" name="a" value="52" />
                <input type="hidden" name="id" value="{{ $document->getKey() }}" />
                <input type="hidden" name="idshow" value="{{ $document->getKey() }}" />
                <input type="hidden" name="new_parent" value="" />
                <p>{{ ManagerTheme::getLexicon('resource_to_be_moved') }}: <b>{{ $document->getKey() }}</b></p>
                <span id="parentName">{{ ManagerTheme::getLexicon('move_resource_new_parent') }}</span>
            </form>
        </div>
    </div>
@endsection
