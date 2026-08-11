@extends('manager::template.page')
@section('content')
    @push('scripts.top')
        <script type="text/javascript">
            var actions = {
                save: function() {
                    var el = document.getElementById('updated');
                    if (el) {
                        el.style.display = 'none';
                    }
                    el = document.getElementById('updating');
                    if (el) {
                        el.style.display = 'block';
                    }
                    setTimeout('document.sortableListForm.submit()', 1000);
                }, cancel: function() {
                    window.location.href = 'index.php?a=76&tab=4';
                },
            };
        </script>
    @endpush

    <h1>
        <i class="{{ $_style['icon_sort_num_asc'] }}"></i>{{ ManagerTheme::getLexicon('plugin_priority_title') }}
    </h1>

    {{ ManagerTheme::styleHtml('actionbuttons.dynamic.save') }}

    <div class="tab-page">
        <div class="container container-body">
            <b>{{ ManagerTheme::getLexicon('plugin_priority') }}</b>
            <p>{{ ManagerTheme::getLexicon('plugin_priority_instructions') }}</p>

            @if($updateMsg)
                <span class="text-success" id="updated">{{ ManagerTheme::getLexicon('sort_updated') }}</span>
            @endif

            <span class="text-danger" style="display:none;" id="updating">{{ ManagerTheme::getLexicon('sort_updating') }}</span>

            <form action="" method="post" name="sortableListForm">
                @csrf
                @foreach($events as $event)
                    <div class="form-group clearfix">
                        <strong>{{ $event->name }}</strong>
                        <ul id="{{ $event->getKey() }}" class="sortableList">
                            @foreach($event->plugins as $plugin)
                                <li id="item_{{ $plugin->getKey() }}"@if($plugin->disabled) class="disabledPlugin"@endif>
                                    <input type="hidden" name="priority[{{ $event->id }}][]" value="{{ $plugin->id }}">
                                    <i class="{{ $_style['icon_plugin'] }}"></i> {{ $plugin->name }}@if($plugin->disabled) (hide) @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </form>
        </div>
    </div>

    <script type="text/javascript">
        evo.sortable('.sortableList > li');
    </script>
@endsection
