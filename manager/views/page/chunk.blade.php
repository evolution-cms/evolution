@extends('manager::template.page')
@section('content')
    @push('scripts.top')
        <script>
          // Added for RTE selection
          function changeRTE()
          {
            var whichEditor = document.getElementById('which_editor');
            if (whichEditor) {
              for (var i = 0; i < whichEditor.length; i++) {
                if (whichEditor[i].selected) {
                  newEditor = whichEditor[i].value;
                  break;
                }
              }
            }

            documentDirty = false;
            document.mutate.a.value = '{{ $action }}';
            document.mutate.which_editor.value = newEditor;
            document.mutate.submit();
          }

          var actions = {
            save: function() {
              documentDirty = false;
              form_save = true;
              document.mutate.save.click();
            },
            duplicate: function() {
              if (confirm('{{ ManagerTheme::getLexicon('confirm_duplicate_record') }}') === true) {
                documentDirty = false;
                document.location.href = "index.php?id={{ $data->getKey() }}&a=97&_token={{ csrf_token() }}";
              }
            },
            delete: function() {
              if (confirm('{{ ManagerTheme::getLexicon('confirm_delete_htmlsnippet') }}') === true) {
                documentDirty = false;
                document.location.href = 'index.php?id=' + document.mutate.id.value + '&a=80&_token={{ csrf_token() }}';
              }
            },
            cancel: function() {
              documentDirty = false;
              document.location.href = 'index.php?a=76&tab=2';
            }
          };

          document.addEventListener('DOMContentLoaded', function() {
            var h1help = document.querySelector('h1 > .help');
            h1help.onclick = function() {
              document.querySelector('.element-edit-message').classList.toggle('show');
            };

            var extension = document.getElementById('chunkfileextension'),
                fileBlock = document.getElementById('assigned-chunk-file'),
                filenameLabel = document.getElementById('chunk-filename'),
                nameField = document.getElementsByName('name')[0];

            // The format list can be empty, in which case the block is not
            // rendered and there is nothing to keep up to date.
            if (!fileBlock || !filenameLabel) {
              return;
            }

            var directory = fileBlock.dataset.directory || '',
                savedName = fileBlock.dataset.name || '';

            // The same encoding ChunkFileStore::encode() applies, so the name
            // shown while typing is the name that will be on disk. Only ASCII
            // hazards are listed; everything above them - Cyrillic, dashes -
            // is left alone, exactly as on the PHP side.
            var RESERVED = /^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$/i;

            var hex = function(ch) {
              var code = ch.charCodeAt(0).toString(16).toUpperCase();

              return '%' + (code.length < 2 ? '0' + code : code);
            };

            var encodeChunkName = function(value) {
              var out = value.replace(/[%<>:"\/\\|?*\u0000-\u001F\u007F]/g, hex);

              if (out.charAt(0) === '.') {
                out = '%2E' + out.slice(1);
              }

              if (/[. ]$/.test(out)) {
                out = out.slice(0, -1) + hex(out.slice(-1));
              }

              if (RESERVED.test(out)) {
                out = hex(out.charAt(0)) + out.slice(1);
              }

              return out;
            };

            // What the store refuses outright: an empty name, and one whose
            // encoded filename overruns the filesystem. The rest - normal form,
            // and a clash with another chunk's file - needs more than this form
            // knows, so the save says so instead.
            var byteLength = function(value) {
              return new TextEncoder().encode(value).length;
            };

            var previewName = function() {
              var value = (nameField ? nameField.value : savedName).trim();

              return value === '' ? '' : encodeChunkName(value);
            };

            var updateFilename = function() {
              var filename = previewName(),
                  selected = extension ? extension.value : '',
                  // The same 255 the store measures, on the same bytes: the
                  // whole filename, extension included.
                  usable = filename !== '' && byteLength(filename + '.' + selected) <= 255;

              filenameLabel.innerText = usable
                ? '/' + directory + filename + '.' + selected
                : {{ Illuminate\Support\Js::from(ManagerTheme::getLexicon('chunk_file_unusable_name', 'this name cannot become a filename')) }};
            };

            if (extension) {
              extension.addEventListener('change', updateFilename);
            }
            if (nameField) {
              nameField.addEventListener('input', updateFilename);
            }

            updateFilename();
          });

        </script>
    @endpush

    <form class="htmlsnippet" id="mutate" name="mutate" method="post" action="index.php">
        @csrf
        {!! get_by_key($events, 'OnChunkFormPrerender') !!}

        <input type="hidden" name="a" value="79" />
        <input type="hidden" name="id" value="{{ $data->getKey() }}" />
        <input type="hidden" name="mode" value="{{ $action }}" />

        <h1>
            <i class="{{ $_style['icon_chunk'] }}"></i>
            @if($data->name)
                {{ $data->name }}
                <small>({{ $data->getKey() }})</small>
            @else
                {{ ManagerTheme::getLexicon('new_htmlsnippet') }}
            @endif
            <i class="{{ $_style['icon_question_circle'] }} help"></i>
        </h1>

        @include('manager::partials.actionButtons', $actionButtons)

        <div class="container element-edit-message">
            <div class="alert alert-info">{{ ManagerTheme::lexiconHtml('htmlsnippet_msg') }}</div>
        </div>

        <div class="tab-pane" id="chunkPane">
            <script>
              var tpChunk = new WebFXTabPane(document.getElementById('chunkPane'), false);
            </script>

            <div class="tab-page" id="tabGeneral">
                <h2 class="tab">{{ ManagerTheme::getLexicon('settings_general') }}</h2>
                <script>tpChunk.addTabPage(document.getElementById('tabGeneral'));</script>

                <div class="container container-body">
                    @include('manager::form.row', [
                        'for' => 'name',
                        'label' => ManagerTheme::getLexicon('htmlsnippet_name'),
                        'element' => '<div class="form-control-name clearfix">' .
                            ManagerTheme::view('form.inputElement', [
                                'name' => 'name',
                                'value' => $data->name,
                                'class' => 'form-control-lg',
                                'attributes' => 'onchange="documentDirty=true;" maxlength="100"'
                            ]) .
                            (EvolutionCMS()->hasPermission('save_role')
                            ? '<label class="custom-control" data-tooltip="' . ManagerTheme::getLexicon('lock_htmlsnippet') . "\n" . ManagerTheme::getLexicon('lock_htmlsnippet_msg') .'">' .
                            ManagerTheme::view('form.inputElement', [
                                'type' => 'checkbox',
                                'name' => 'locked',
                                'checked' => ($data->locked == 1)
                            ]) .
                            '<i class="' . $_style['icon_lock'] . '"></i>
                            </label>
                            <small class="form-text text-danger hide" id="savingMessage"></small>
                            <script>if (!document.getElementsByName(\'name\')[0].value) document.getElementsByName(\'name\')[0].focus();</script>'
                            : '') .
                            '</div>'
                    ])

                    @include('manager::form.input', [
                        'name' => 'description',
                        'id' => 'description',
                        'label' => ManagerTheme::getLexicon('htmlsnippet_desc'),
                        'value' => $data->description,
                        'attributes' => 'onchange="documentDirty=true;" maxlength="255"'
                    ])

                    @include('manager::form.select', [
                        'name' => 'categoryid',
                        'id' => 'categoryid',
                        'label' => ManagerTheme::getLexicon('existing_category'),
                        'value' => $data->category,
                        'first' => [
                            'text' => ''
                        ],
                        'options' => $categories->pluck('category', 'id'),
                        'attributes' => 'onchange="documentDirty=true;"'
                    ])

                    @include('manager::form.input', [
                        'name' => 'newcategory',
                        'id' => 'newcategory',
                        'label' => ManagerTheme::getLexicon('new_category'),
                        'value' => (isset($data->newcategory) ? $data->newcategory : ''),
                        'attributes' => 'onchange="documentDirty=true;" maxlength="45"'
                    ])

                    <div class="form-row">
                        <label for="disabled">
                            @include('manager::form.inputElement', [
                                'type' => 'checkbox',
                                'name' => 'disabled',
                                'value' => 'on',
                                'checked' => ($data->disabled === 1)
                            ])
                            @if($data->disabled == 1)
                                <span class="text-danger">{{ ManagerTheme::getLexicon('disabled') }}</span>
                            @else
                                {{ ManagerTheme::getLexicon('disabled') }}
                            @endif
                        </label>
                    </div>

                    @if(!empty($chunkFormats))
                        <div class="form-group" id="assigned-chunk-file"
                             data-name="{{ $data->name }}"
                             data-directory="{{ $chunkFileDirectory }}"
                             data-extension="{{ $chunkFormatDefault }}">
                            {{ ManagerTheme::getLexicon('chunk_assigned_file', 'Chunk file') }}:
                            <strong id="chunk-filename"></strong>

                            @if(count($chunkFormats) > 1)
                                {{-- Only worth asking once a plugin has put a second
                                     format in the list. --}}
                                <div class="create-check">
                                    <select name="chunkfileextension" id="chunkfileextension"
                                            onchange="documentDirty=true;">
                                        @foreach($chunkFormats as $extension => $label)
                                            <option value="{{ $extension }}"
                                                    @if($extension === $chunkFormatDefault) selected @endif
                                            >{{ $label }} (.{{ $extension }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <input type="hidden" name="chunkfileextension" id="chunkfileextension"
                                       value="{{ $chunkFormatDefault }}">
                            @endif
                        </div>
                    @endif
                </div>

                <!-- HTML text editor start -->
                <div class="navbar navbar-editor">
                    <span>{{ ManagerTheme::getLexicon('chunk_code') }}</span>
                    @if(get_by_key(EvolutionCMS()->config, 'use_editor') == 1)
                        <span class="float-right">
                            {{ ManagerTheme::getLexicon('which_editor_title') }}
                            @include('manager::form.selectElement', [
                                'name' => 'which_editor',
                                'value' => $which_editor,
                                'first' => [
                                    'value' => 'none',
                                    'text' => ManagerTheme::getLexicon('none')
                                ],
                                'options' => get_by_key($events, 'OnRichTextEditorRegister'),
                                'as' => 'values',
                                'class' => 'form-control-sm',
                                'attributes' => 'onchange="changeRTE();"'
                            ])
                        </span>
                    @endif
                </div>
                <div class="section-editor clearfix">
                    @include('manager::form.textareaElement', [
                        'name' => 'post',
                        'value' => (isset($data->post) ? $data->post : $data->snippet),
                        'class' => 'phptextarea',
                        'rows' => 20,
                        'attributes' => 'onChange="documentDirty=true;"'
                    ])
                </div>
                <!-- HTML text editor end -->

            </div>

            {!! get_by_key($events, 'OnChunkFormRender') !!}
        </div>
        <input type="submit" name="save" style="display:none;" />
    </form>

    @if(get_by_key(EvolutionCMS()->config, 'use_editor') == 1)
        {!! get_by_key($events, 'OnRichTextEditorInit') !!}
    @endif
@endsection
