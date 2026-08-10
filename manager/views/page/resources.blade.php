@extends('manager::template.page')

@push('scripts.bot')
    <script>
        var trans = @js($unlockTranslations),
            mraTrans = @js($mraTranslations);
    </script>
    <script src="media/script/jquery.quicksearch.js"></script>
    <script src="media/script/bootstrap/js/bootstrap.min.js"></script>
    <script src="media/script/resources-functions.js"></script>
@endpush

@section('content')
    <h1>
        <i class="{{ $_style['icon_elements'] }}"></i>{{ ManagerTheme::getLexicon('element_management') }}
    </h1>

    <div class="sectionBody">
        <div class="tab-pane" id="resourcesPane">
            <script type="text/javascript">
                tpResources = new WebFXTabPane(document.getElementById('resourcesPane'), false);
            </script>

            @foreach($tabs as $tab)
                @if($tab instanceof EvolutionCMS\Interfaces\ManagerTheme\TabControllerInterface)
                    @include(ManagerTheme::getViewName($tab->getView()), $tab->getParameters())
                @else
                    {!! $tab !!}
                @endif
            @endforeach

            @if($activeTab !== '')
                <script> tpResources.setSelectedTab('{{ $activeTab }}');</script>
            @endif
        </div>
    </div>
@endsection
