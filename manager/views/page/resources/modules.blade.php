<div class="tab-page {{ $tabPageName }}" id="{{ $tabIndexPageName }}">
    <h2 class="tab">
        <a href="?a=76&tab={{ $index }}"><i class="{{ $_style['icon_modules'] }}"></i>{{ ManagerTheme::getLexicon('modules') }}</a>
    </h2>

    <script>tpResources.addTabPage(document.getElementById('{{ $tabIndexPageName }}'));</script>

    <div id="{{ $tabIndexPageName }}-info" class="msg-container" style="display:none">
        <div class="element-edit-message-tab">{{ ManagerTheme::lexiconHtml('module_management_msg') }}</div>
        <p class="viewoptions-message">{{ ManagerTheme::getLexicon('view_options_msg') }}</p>
    </div>

    <div id="_actions">
        <form class="btn-group form-group form-inline">
            <div class="input-group input-group-sm">
                <input class="form-control filterElements-form" type="text" id="{{ $tabIndexPageName }}_search" size="30" placeholder="{{ ManagerTheme::getLexicon('element_filter_msg') }}" />
                <div class="input-group-btn">
                    @if(evolutionCMS()->hasPermission('new_module') && evolutionCMS()->hasPermission('save_module'))
                        <a class="btn btn-success" target="main" href="{{ (new EvolutionCMS\Models\SiteModule)->makeUrl('actions.new') }}">
                            <i class="{{ $_style['icon_add'] }}"></i>
                            <span>{{ ManagerTheme::getLexicon('new_module') }}</span>
                        </a>
                    @endif
                    <a class="btn btn-secondary" href="javascript:;" id="{{ $tabIndexPageName }}-help">
                        <i class="{{ $_style['icon_question_circle'] }}"></i>
                        <span>{{ ManagerTheme::getLexicon('help') }}</span>
                    </a>
                    <a class="btn btn-secondary switchform-btn" href="javascript:;" data-target="switchForm_{{ $tabIndexPageName }}">
                        <i class="{{ $_style['icon_bars']  }}"></i>
                        <span>{{ ManagerTheme::getLexicon('btn_view_options') }}</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    @include('manager::page.resources.helper.switchButtons', ['id' => $tabIndexPageName])

    <div class="clearfix"></div>
    <div class="panel-group no-transition">
        <div id="{{ $tabIndexPageName }}_content" class="resourceTable panel panel-default">
            @if(isset($outCategory) && $outCategory->count() > 0)
                @component('manager::partials.panelCollapse', ['name' => $tabIndexPageName . '_content', 'id' => 0, 'title' => ManagerTheme::getLexicon('no_category')])
                    <ul class="elements">
                        @foreach($outCategory as $item)
                            @include('manager::page.resources.elements.module', compact('item', 'tabIndexPageName', 'action'))
                        @endforeach
                    </ul>
                @endcomponent
            @endif

            @if(isset($categories))
                @foreach($categories as $cat)
                    @component('manager::partials.panelCollapse', ['name' => $tabIndexPageName . '_content', 'id' => $cat->id, 'title' => $cat->name])
                        <ul class="elements">
                            @foreach($cat->modules as $item)
                                @include('manager::page.resources.elements.module', compact('item', 'tabIndexPageName', 'action'))
                            @endforeach
                        </ul>
                    @endcomponent
                @endforeach
            @endif
        </div>
    </div>
    <div class="clearfix"></div>
</div>

@push('scripts.bot')
    <script>
        initQuicksearch('{{ $tabIndexPageName }}_search', '{{ $tabIndexPageName }}_content');
      initViews('ch', '{{ $tabIndexPageName }}', '{{ $tabIndexPageName }}_content');
    </script>
@endpush
