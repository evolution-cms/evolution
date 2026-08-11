@extends('manager::template.page')
@section('content')
    @push('scripts.top')
        <script>
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
            },
            cancel: function() {
              document.location.href = 'index.php?a=76&tab=1';
            }
          };

          function renderList()
          {
            var list = '';
            var els = document.querySelectorAll('.sortableList > li');
            for (var i = 0; i < els.length; i++) {
              list += els[i].id + ';';
            }
            document.getElementById('list').value = list;
          }

          var sortdir = 'asc';

          function sort()
          {
            var els = document.querySelectorAll('.sortableList > li');
            var keyA, keyB;
            if (sortdir === 'asc') {
              els = [].slice.call(els).sort(function(a, b) {
                keyA = a.innerText.toLowerCase();
                keyB = b.innerText.toLowerCase();
                return keyA.localeCompare(keyB);
              });
              sortdir = 'desc';
            } else {
              els = [].slice.call(els).sort(function(b, a) {
                keyA = a.innerText.toLowerCase();
                keyB = b.innerText.toLowerCase();
                return keyA.localeCompare(keyB);
              });
              sortdir = 'asc';
            }
            var ul = document.getElementById('sortlist');
            var list = '';
            for (var i = 0; i < els.length; i++) {
              ul.appendChild(els[i]);
              list += els[i].id + ';';
            }
            document.getElementById('list').value = list;
          }

          function resetSortOrder()
          {
            if (confirm('{{ ManagerTheme::getLexicon('confirm_reset_sort_order') }}') === true) {
              documentDirty = false;
              var input = document.createElement('input');
              input.type = 'hidden';
              input.name = 'reset';
              input.value = 'true';
              document.sortableListForm.appendChild(input);
              actions.save();
            }
          }
        </script>
    @endpush

    <h1>
        <i class="{{ ManagerTheme::getStyle('icon_sort_num_asc') }}"></i>{{ ManagerTheme::getLexicon('template_tv_edit_title') }}
    </h1>

    @include('manager::partials.actionButtons', $actionButtons)

    <div class="tab-page">
        <div class="container container-body">
            @if($tmplvars->count())
                <b>{{ ManagerTheme::getLexicon('template_tv_edit') }}</b>
                <p>{{ ManagerTheme::getLexicon('tmplvars_rank_edit_message') }}</p>
                <p>
                    <a class="btn btn-secondary" href="javascript:;" onclick="sort();return false;">
                        <i class="{{ ManagerTheme::getStyle('icon_sort') }}"></i> {{ ManagerTheme::getLexicon('sort_alphabetically') }}
                    </a>
                    <a class="btn btn-secondary" href="javascript:;" onclick="resetSortOrder();return false;">
                        <i class="{{ ManagerTheme::getStyle('icon_refresh') }}"></i> {{ ManagerTheme::getLexicon('reset_sort_order') }}
                    </a>
                </p>
                @if($updated)
                    <span class="text-success" id="updated">{{ ManagerTheme::getLexicon('sort_updated') }}</span>
                @endif
                <span class="text-danger" style="display:none;" id="updating">{{ ManagerTheme::getLexicon('sort_updating') }}</span>
                <div class="clearfix">
                    <ul id="sortlist" class="sortableList">
                        @foreach($tmplvars as $tv)
                            <li id="item_{{ $tv->id }}"><i class="{{ ManagerTheme::getStyle('icon_tv') }}"></i>
                                @if($tv->caption != '')
                                    {{ $tv->caption }}
                                @else
                                    {{ $tv->name }}
                                @endif
                                <small class="protectedNode" style="float:right">[*{{ $tv->name }}*]</small>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <p class="text-danger">{{ ManagerTheme::getLexicon('tmplvars_novars') }}</p>
            @endif
        </div>
    </div>

    <form action="" method="post" name="sortableListForm">
        @csrf
        <input type="hidden" name="listSubmitted" value="true" />
        <input type="hidden" id="list" name="list" value="" />
    </form>

    @push('scripts.bot')
        <script>
          evo.sortable('.sortableList > li', {
            complete: function() {
              renderList();
            }
          });
        </script>
    @endpush
@endsection