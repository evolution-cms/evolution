@extends('manager::template.page')
@section('content')
    <h1>
        <i class="{{ $_style['icon_lock'] }}"></i>{{ ManagerTheme::getLexicon('two_factor_title') }}
    </h1>

    <div class="tab-page">
        <div class="container container-body">
            @include('manager::two_factor.account')
        </div>
    </div>
@endsection
