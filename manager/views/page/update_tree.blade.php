@extends('manager::template.page')
@section('content')
    @push('scripts.top')
        <script language="javascript">
            var actions = {
                cancel: function() {
                    documentDirty = false;
                    document.location.href = 'index.php?a=2';
                }
            };
        </script>
    @endpush

    <h1>
        <i class="{{ ManagerTheme::getStyle('icon_sitemap') }}"></i>{{ ManagerTheme::getLexicon('update_tree') }}
    </h1>
    <div class="tab-page">
    {!! ManagerTheme::getStyle('actionbuttons.static.cancel') !!}



            <div class="container container-body">
                <p>
                    {!! ManagerTheme::getLexicon('update_tree_description') !!}
                </p>
                @if($count < 3000)

                    @if($finish == 1)
                        <div class="alert alert-success" role="alert">
                            {!! sprintf(ManagerTheme::getLexicon('update_tree_time'), $count, $end) !!}
                        </div>
                    @endif

                    <form  method="post" name="exportFrm">

                        <button type="submit" name="start" class="btn btn-primary"> <i class="{{ ManagerTheme::getStyle('icon_sitemap') }}"></i> {{ ManagerTheme::getLexicon('update_tree') }}</button>
                    </form>

                @else
                    <div class="alert alert-danger" role="alert">
                        {!! ManagerTheme::getLexicon('update_tree_danger') !!}
                    </div>
                @endif
            </div>

            </div>




@endsection
