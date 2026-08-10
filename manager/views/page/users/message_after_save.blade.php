@extends('manager::template.page')
@section('content')
    <h1>{{ \Lang::get('global.web_user_title') }}</h1>

    <div id="actions">
        <div class="btn-group">
            <a class="btn btn-success" href="{{ $url }}"><i
                        class="{{ $_style["icon_edit"] }}"></i> {{ \Lang::get('global.edit') }}
            </a>
            <a class="btn btn-secondary" href="{{ $cancel_url }}"><i
                        class="{{ $_style["icon_cancel"] }}"></i> {{ \Lang::get('global.cancel') }}
            </a>
        </div>
    </div>

    <div class="sectionBody">
        <div class="tab-page">
            <div class="container container-body" id="disp">
                <p>
                    {{ $passwordMessage }}
                </p>
            </div>
        </div>
    </div>
@endsection
