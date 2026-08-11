@extends('manager::template.page')

@section('content')

    @push('scripts.top')
        <script>
          var actions = {
            cancel: function() {
              documentDirty = false;
              document.location.href = 'index.php?a=2';
            }
          };
        </script>
    @endpush

    <h1>
        <i class="{{ ManagerTheme::getStyle('icon_search') }}"></i>{{ ManagerTheme::getLexicon('search_criteria') }}
    </h1>

    @include('manager::partials.actionButtons', $actionButtons)

    <div class="tab-page">
        <div class="container container-body">
            <form name="searchform" method="post" action="index.php" enctype="multipart/form-data" class="form-group">
                @csrf
                <input type="hidden" name="a" value="71">

                <div class="row form-row">
                    <div class="col-md-3 col-lg-2">{{ ManagerTheme::getLexicon('search_criteria_top') }}</div>
                    <div class="col-md-9 col-lg-10">
                        <input name="searchfields" type="text"
                                value="{{ entities(get_by_key($_REQUEST, 'searchfields', '', 'is_scalar'), EvolutionCMS()->getConfig('modx_charset')) }}" />
                        <small class="form-text">{{ ManagerTheme::getLexicon('search_criteria_top_msg') }}</small>
                    </div>
                </div>

                <div class="row form-row">
                    <div class="col-md-3 col-lg-2">{{ ManagerTheme::getLexicon('search_criteria_template_id') }}</div>
                    <div class="col-md-9 col-lg-10">
                        <select name="templateid">
                            @foreach($templates as $template)
                                <option value="{{ $template['value'] }}"{{ $template['selected'] }}>{{ $template['title'] }}</option>
                            @endforeach
                        </select>
                        <small class="form-text">{{ ManagerTheme::getLexicon('search_criteria_template_id_msg') }}</small>
                    </div>
                </div>

                <div class="row form-row">
                    <div class="col-md-3 col-lg-2">URL</div>
                    <div class="col-md-9 col-lg-10">
                        <input name="url" type="text"
                                value="{{ entities(get_by_key($_REQUEST, 'url', '', 'is_scalar'), EvolutionCMS()->getConfig('modx_charset')) }}" />
                        <small class="form-text">{{ ManagerTheme::getLexicon('search_criteria_url_msg') }}</small>
                    </div>
                </div>

                <div class="row form-row">
                    <div class="col-md-3 col-lg-2">{{ ManagerTheme::getLexicon('search_criteria_content') }}</div>
                    <div class="col-md-9 col-lg-10">
                        <input name="content" type="text"
                                value="{{ entities(get_by_key($_REQUEST, 'content', '', 'is_scalar'), EvolutionCMS()->getConfig('modx_charset')) }}" />
                        <small class="form-text">{{ ManagerTheme::getLexicon('search_criteria_content_msg') }}</small>
                    </div>
                </div>

                <a class="btn btn-success" href="javascript:;" onClick="document.searchform.submitok.click();">
                    <i class="{{ ManagerTheme::getStyle('icon_search') }}"></i> {{ ManagerTheme::getLexicon('search') }}
                </a>

                <a class="btn btn-secondary" href="index.php?a=2">
                    <i class="{{ ManagerTheme::getStyle('icon_cancel') }}"></i> {{ ManagerTheme::getLexicon('cancel') }}
                </a>

                <input type="submit" value="Search" name="submitok" style="display:none" />
            </form>
        </div>
    </div>

    @if($isSubmitted)
        <div class="container navbar">{{ ManagerTheme::getLexicon('search_results') }}</div>

        <div class="tab-page">
            <div class="container container-body">
                @if(!$isAjax)
                    @if(count($results) < 1)
                        {{ ManagerTheme::getLexicon('search_empty') }}
                    @else
                        @php(printf('<p>' . ManagerTheme::getLexicon('search_results_returned_msg') . '</p>', count($results)))

                        @push('scripts.top')
                            <script src="media/script/tablesort.js"></script>
                        @endpush

                        <table class="grid sortabletable sortable-onload-2 rowstyle-even" id="table-1">
                            <thead>
                            <tr>
                                <th width="40"></th>
                                <th width="40" class="sortable">{{ ManagerTheme::getLexicon('search_results_returned_id') }}</th>
                                <th width="40"></th>
                                <th class="sortable">{{ ManagerTheme::getLexicon('search_results_returned_title') }}</th>
                                <th class="sortable">{{ ManagerTheme::getLexicon('search_results_returned_desc') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($results as $row)
                                <tr>
                                    <td class="text-center">
                                        <a href="index.php?a=3&id={{ $row['id'] }}" title="{{ ManagerTheme::getLexicon('search_view_docdata') }}">
                                            <i class="{{ ManagerTheme::getStyle('icon_info') }}"></i>
                                        </a>
                                    </td>
                                    <td class="text-right">{{ $row['id'] }}</td>
                                    <td class="text-center">
                                        <i class="{{ $row['iconClass'] }}"></i>
                                    </td>
                                    <td class="{{ $row['rowClass'] }}">
                                        <a href="index.php?a=27&id={{ $row['id'] }}">{{ $row['pagetitle'] }}</a>
                                    </td>
                                    <td class="{{ $row['rowClass'] }}">{{ $row['description'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                @else
                    @if(!empty($ajaxResults))
                        <div class="ajaxSearchResults">
                            <ul>
                                @foreach($ajaxResults as $k => $row)
                                    @if(!empty($row['results']))
                                        <li>
                                            <b>
                                                <i class="{{ $row['class'] }}"></i> {{ $row['title'] }}
                                            </b>
                                        </li>
                                        @foreach($row['results'] as $item)
                                            <li class="{{ $item['class'] }}">
                                                <a href="{{ $item['url'] }}" id="{{ $k }}_{{ $item['id'] }}" target="main">
                                                    {{ $item['title'] }}
                                                    <i class="{{ ManagerTheme::getStyle('icon_external_link') }}"></i>
                                                </a>
                                            </li>
                                        @endforeach
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif
@endsection