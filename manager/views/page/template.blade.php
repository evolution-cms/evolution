@extends('manager::template.page')
@section('content')
    @push('scripts.top')
        <?php /** @var EvolutionCMS\Models\SiteTemplate $data */ ?>
        <script>

            var actions = {
                save: function () {
                    documentDirty = false;
                    form_save = true;
                    document.mutate.save.click();
                    //saveWait('mutate');
                },
                duplicate: function () {
                    if (confirm("{{ ManagerTheme::getLexicon('confirm_duplicate_record') }}") === true) {
                        documentDirty = false;
                        document.location.href = "index.php?id={{ $data->getKey() }}&a=96&_token={{ csrf_token() }}";
                    }
                },
                delete: function () {
                    if (confirm("{{ ManagerTheme::getLexicon('confirm_delete_template') }}") === true) {
                        documentDirty = false;
                        document.location.href = 'index.php?id={{ $data->getKey() }}&a=21&_token={{ csrf_token() }}';
                    }
                },
                cancel: function () {
                    documentDirty = false;
                    document.location.href = 'index.php?a=76&tab=0';
                }
            };

            document.addEventListener('DOMContentLoaded', function () {
                var h1help = document.querySelector('h1 > .help');
                h1help.onclick = function () {
                    document.querySelector('.element-edit-message').classList.toggle('show');
                };

                var checkContainer = document.getElementById('assigned-template-file'),
                    filenameLabel = document.getElementById('template-filename'),
                    alias = document.getElementById('templatealias'),
                    templatename = document.getElementsByName('templatename')[0],
                    extension = document.getElementById('templatefileextension');

                // The engine list can be empty, in which case the block is not
                // rendered at all and there is nothing to keep up to date.
                if (checkContainer && filenameLabel && alias) {
                    var note = document.getElementById('template-file-note'),
                        source = document.getElementById('templatesource'),
                        savedAlias = checkContainer.dataset.alias || '',
                        savedSource = checkContainer.dataset.source || '',
                        savedExtension = checkContainer.dataset.extension || '',
                        winner = checkContainer.dataset.winner || '',
                        existing = [];

                    try {
                        existing = JSON.parse(checkContainer.dataset.existing || '[]');
                    } catch (e) {
                        existing = [];
                    }

                    // What is on disk was read for the alias as saved. Rename
                    // the alias in the form and it says nothing about the new
                    // one, so the warnings go quiet rather than lie.
                    var noteFor = function(selected) {
                        // Moving the code out of the database and into a file
                        // leaves the database copy behind untouched, and coming
                        // back later shows that copy rather than the file.
                        if (source && source.value !== savedSource) {
                            if (source.value === 'db' && savedSource === 'file') {
                                return {{ Illuminate\Support\Js::from(ManagerTheme::getLexicon('template_source_back_to_db', 'The editor now shows the database copy, and that is what will be saved. The file is left where it is.')) }};
                            }
                            if (source.value === 'file'
                                && existing.indexOf(extension ? extension.value : '') === -1) {
                                return {{ Illuminate\Support\Js::from(ManagerTheme::getLexicon('template_source_to_file', 'What is in the editor is written to the file on save. The database copy is kept as it is.')) }};
                            }
                        }

                        if (alias.value !== savedAlias || !existing.length) {
                            return '';
                        }

                        // Picking an engine whose file exists loads that file
                        // into the editor, so there is nothing to warn about:
                        // what is on screen is what will be written back.
                        if (existing.indexOf(selected) !== -1) {
                            return '';
                        }

                        // Until this template is saved with the new engine,
                        // the file that renders is still the one it is pinned
                        // to - or, with nothing pinned, whichever the view
                        // factory finds first.
                        if (!winner) {
                            return '';
                        }

                        return {{ Illuminate\Support\Js::from(ManagerTheme::getLexicon('template_file_shadowed', 'Until this template is saved, this file still renders:')) }} +
                            ' ' + savedAlias + '.' + winner;
                    };

                    // What the editor must show for a given pair of selectors:
                    // the database column, or the file that pair points at. A
                    // pair with no file yet keeps whatever is on screen - that
                    // is the code being moved into it.
                    var dbContent = {{ Illuminate\Support\Js::from($templateDbContent) }},
                        fileContents = {{ Illuminate\Support\Js::from($templateFileContents) }},
                        shownKey = savedSource === 'file' && savedExtension !== ''
                            ? 'file:' + savedExtension
                            : 'db';

                    var editorValue = function() {
                        if (window.myCodeMirrors && window.myCodeMirrors['post']) {
                            return window.myCodeMirrors['post'].getValue();
                        }

                        var box = document.getElementsByName('post')[0];

                        return box ? box.value : '';
                    };

                    var setEditorValue = function(value) {
                        var box = document.getElementsByName('post')[0];

                        if (box) {
                            box.value = value;
                        }

                        if (window.myCodeMirrors && window.myCodeMirrors['post']) {
                            window.myCodeMirrors['post'].setValue(value);
                        }
                    };

                    var lastLoaded = editorValue();

                    // Switching away from unsaved edits would drop them
                    // silently, so it is asked about rather than assumed.
                    var mayReplaceEditor = function() {
                        if (editorValue() === lastLoaded) {
                            return true;
                        }

                        return window.confirm(
                            {{ Illuminate\Support\Js::from(ManagerTheme::getLexicon('template_source_discard_edits', 'The editor has unsaved changes. Switching loads the other copy and discards them. Continue?')) }}
                        );
                    };

                    var syncEditor = function() {
                        var onFile = source && source.value === 'file',
                            selected = extension ? extension.value : '',
                            key = onFile ? 'file:' + selected : 'db';

                        if (key === shownKey) {
                            return;
                        }

                        // No file there yet: the editor's contents are what
                        // will be written into it, so they stay put.
                        if (onFile && !Object.prototype.hasOwnProperty.call(fileContents, selected)) {
                            shownKey = key;
                            return;
                        }

                        if (!mayReplaceEditor()) {
                            // Put the selectors back where they were.
                            if (shownKey === 'db') {
                                if (source) { source.value = 'db'; }
                            } else if (source) {
                                source.value = 'file';
                                if (extension) { extension.value = shownKey.slice(5); }
                            }
                            return;
                        }

                        setEditorValue(onFile ? fileContents[selected] : dbContent);
                        lastLoaded = editorValue();
                        shownKey = key;
                    };

                    // The editor is shared with the database view, where the
                    // code is EVO template markup; a file gets the highlighting
                    // of whatever engine reads it. The plugin publishes its
                    // instances on window, so no plugin change is needed - and
                    // if it is switched off, this simply does nothing.
                    var modes = {
                        'php': 'application/x-httpd-php',
                        'css': 'text/css'
                    };

                    // The CodeMirror plugin registers its EVO tag overlay as
                    // Evo-<mode>. Setting the bare name here would throw that
                    // overlay away and leave the template editor with no tag
                    // highlighting at all, so the overlay is preferred wherever
                    // the plugin defined one for the mode being asked for.
                    var overlaid = function(mode) {
                        return window.CodeMirror && CodeMirror.modes['Evo-' + mode]
                            ? 'Evo-' + mode
                            : mode;
                    };

                    var applyHighlighting = function() {
                        if (!window.myCodeMirrors || !window.myCodeMirrors['post']) {
                            return;
                        }

                        var onFile = source && source.value === 'file',
                            mode = onFile && extension
                                ? (modes[extension.value] || 'htmlmixed')
                                : 'htmlmixed';

                        try {
                            window.myCodeMirrors['post'].setOption('mode', overlaid(mode));
                        } catch (e) {
                            // An editor that will not take a mode is not worth
                            // breaking the form over.
                        }
                    };

                    // The file is named after the alias, and the alias is
                    // filled in from the name when it is left blank - so a
                    // template being created has a filename to show before its
                    // alias field has anything in it.
                    var previewName = function() {
                        var value = alias.value !== ''
                            ? alias.value
                            : (templatename ? templatename.value : '');

                        return value
                            .replace(/\s*/g, '')
                            .replace(/[^a-zA-Z0-9_-]+/g, '')
                            .toLowerCase();
                    };

                    var updateFilename = function() {
                        var onFile = source
                            ? (source.value === 'file' || (source.value === '' && existing.length))
                            : true;

                        // The engine and the filename only mean anything for a
                        // template that reads from a file.
                        if (!onFile) {
                            checkContainer.style.display = 'none';
                            if (note) {
                                var switching = noteFor(extension ? extension.value : '');
                                note.innerText = switching;
                                note.style.display = switching ? 'block' : 'none';
                            }
                            return;
                        }

                        // Choosing a file and being told nothing about which
                        // file is the state this block exists to prevent, so it
                        // stays visible even when the name is not usable yet.
                        checkContainer.style.display = 'block';

                        var filename = previewName(),
                            selected = extension ? extension.value : 'blade.php';

                        filenameLabel.innerText = filename !== ''
                            ? '/views/' + filename + '.' + selected
                            : {{ Illuminate\Support\Js::from(ManagerTheme::getLexicon('template_file_pending', 'named after the alias, once there is one')) }};

                        if (note) {
                            var message = noteFor(selected);
                            note.innerText = message;
                            note.style.display = message ? 'block' : 'none';
                        }

                        applyHighlighting();
                    };

                    var onSelectorChange = function() {
                        syncEditor();
                        updateFilename();
                    };

                    alias.addEventListener('change', updateFilename);
                    alias.addEventListener('input', updateFilename);
                    if (templatename) {
                        templatename.addEventListener('input', updateFilename);
                    }
                    if (source) {
                        source.addEventListener('change', onSelectorChange);
                    }
                    if (extension) {
                        extension.addEventListener('change', onSelectorChange);
                    }

                    updateFilename();

                    // The editor is created by a plugin whose script may not
                    // have run yet.
                    applyHighlighting();
                    window.setTimeout(applyHighlighting, 0);
                }
            });

        </script>
    @endpush

    <form name="mutate" method="post" action="index.php">
        @csrf
        {!! get_by_key($events, 'OnTempFormPrerender') !!}

        <input type="hidden" name="a" value="20">
        <input type="hidden" name="id" value="{{ $data->getKey() }}">
        <input type="hidden" name="mode" value="{{ $action }}">

        <h1>
            <i class="{{ $_style['icon_template'] }}"></i>
            @if($data->templatename)
                {{ $data->templatename }}<small>({{ $data->getKey() }})</small>
            @else
                {{ ManagerTheme::getLexicon('new_template') }}
            @endif
            <i class="{{ $_style['icon_question_circle'] }} help"></i>
        </h1>

        @include('manager::partials.actionButtons', $actionButtons)

        <div class="container element-edit-message">
            <div class="alert alert-info">{{ ManagerTheme::getLexicon('template_msg') }}</div>
        </div>

        <div class="tab-pane" id="templatesPane">
            <script>
                var tp = new WebFXTabPane(document.getElementById('templatesPane'), {{ get_by_key(EvolutionCMS()->config, 'remember_last_tab') ? 1 : 0 }});
            </script>

            <div class="tab-page" id="tabTemplate">
                <h2 class="tab">{{ ManagerTheme::getLexicon('template_edit_tab') }}</h2>
                <script>tp.addTabPage(document.getElementById('tabTemplate'));</script>

                <div class="container container-body">
                    <div class="form-group">
                        @include('manager::form.row', [
                            'for' => 'templatename',
                            'label' => ManagerTheme::getLexicon('template_name'),
                            'small' => ($data->getKey() == get_by_key(EvolutionCMS()->config, 'default_template') ? '<b class="text-danger">' . mb_strtolower(rtrim(ManagerTheme::getLexicon('defaulttemplate_title'), ':'), ManagerTheme::getCharset()) . '</b>' : ''),
                            'element' => '<div class="form-control-name clearfix">' .
                                ManagerTheme::view('form.inputElement', [
                                    'name' => 'templatename',
                                    'value' => $data->templatename,
                                    'class' => 'form-control-lg',
                                    'attributes' => 'onchange="documentDirty=true;"'
                                ]) .
                                (EvolutionCMS()->hasPermission('save_role')
                                ? '<label class="custom-control" data-tooltip="' . ManagerTheme::getLexicon('lock_template') . "\n" . ManagerTheme::getLexicon('lock_template_msg') .'">' .
                                 ManagerTheme::view('form.inputElement', [
                                    'type' => 'checkbox',
                                    'name' => 'locked',
                                    'checked' => ($data->locked == 1)
                                 ]) .
                                 '<i class="' . $_style['icon_lock'] . '"></i>
                                 </label>
                                 <small class="form-text text-danger hide" id="savingMessage"></small>
                                 <script>if (!document.getElementsByName(\'templatename\')[0].value) document.getElementsByName(\'templatename\')[0].focus();</script>'
                                : '') .
                                '</div>'
                        ])

                        @include('manager::form.input', [
                            'name' => 'templatealias',
                            'id' => 'templatealias',
                            'label' => ManagerTheme::getLexicon('alias'),
                            'value' => $data->templatealias,
                            'attributes' => 'onchange="documentDirty=true;" maxlength="255"'
                        ])

                        @include('manager::form.input', [
                            'name' => 'description',
                            'id' => 'description',
                            'label' => ManagerTheme::getLexicon('template_desc'),
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

                    </div>

                    @if(!empty($templateFileEngines))
                        <div class="form-group" id="template-source">
                            <label for="templatesource">
                                {{ ManagerTheme::getLexicon('template_source', 'Template code') }}
                            </label>
                            <select name="templatesource" id="templatesource" onchange="documentDirty=true;">
                                <option value="db" @if($templateSource === 'db') selected @endif
                                >{{ ManagerTheme::getLexicon('template_source_db', 'In the database') }}</option>
                                <option value="file" @if($templateSource === 'file') selected @endif
                                >{{ ManagerTheme::getLexicon('template_source_file', 'In a file') }}</option>
                                @if($templateSource !== 'db' && $templateSource !== 'file')
                                    {{-- Templates from before this setting existed: a matching file wins
                                         if one happens to be there. Offered only while that is still true,
                                         so nobody can pick it deliberately. --}}
                                    <option value="" selected
                                    >{{ ManagerTheme::getLexicon('template_source_auto', 'Automatic (a matching file wins)') }}</option>
                                @endif
                            </select>
                        </div>

                        <div class="form-group" id="assigned-template-file" style="display: none;"
                             data-alias="{{ $data->templatealias }}"
                             data-existing="{{ json_encode(array_keys($templateFileExisting)) }}"
                             data-winner="{{ $templateFileWinner }}"
                             data-source="{{ $templateSource }}"
                             data-extension="{{ $data->templatefileextension }}">
                            {{ ManagerTheme::getLexicon('template_assigned_file', ManagerTheme::getLexicon('template_assigned_blade_file', 'Corresponding template file')) }}:
                            <strong id="template-filename"></strong>

                            <div class="create-check">
                                {{-- Choosing to keep the code in a file is the whole
                                     instruction: the file is written on save, and
                                     brought into existence if it is not there yet. --}}
                                <select name="templatefileextension" id="templatefileextension"
                                        onchange="documentDirty=true;">
                                    @foreach($templateFileEngines as $extension => $engine)
                                        <option value="{{ $extension }}"
                                                @if($extension === $templateFileDefault) selected @endif
                                        >{{ $engine['label'] }} (.{{ $extension }})</option>
                                    @endforeach
                                </select>
                            </div>

                            @if(!empty($templateFileExisting))
                                <small class="form-text text-muted">
                                    {{ ManagerTheme::getLexicon('template_file_exists', 'Already on disk') }}:
                                    @foreach($templateFileExisting as $extension => $path)
                                        <code>{{ basename($path) }}</code>@if($extension === $templateFileWinner && count($templateFileExisting) > 1)
                                            ({{ ManagerTheme::getLexicon('template_file_wins', 'this one renders') }})@endif{{ $loop->last ? '' : ', ' }}
                                    @endforeach
                                </small>
                            @endif

                            <small class="form-text text-warning" id="template-file-note" style="display: none;"></small>
                        </div>
                    @endif

                    @if(EvolutionCMS()->hasPermission('save_role'))
                        <div class="form-group">
                            <label>
                                @include('manager::form.inputElement', [
                                    'name' => 'selectable',
                                    'id' => 'selectable',
                                    'type' => 'checkbox',
                                    'checked' => ($data->selectable == 1),
                                    'attributes' => 'onchange="documentDirty=true;"'
                                ])
                                {{ ManagerTheme::getLexicon('template_selectable') }}
                            </label>
                        </div>
                    @endif
                </div>

                <!-- HTML text editor start -->
                <div class="navbar navbar-editor">
                    <span>{{ ManagerTheme::getLexicon('template_code') }}</span>
                </div>
                <div class="section-editor clearfix">
                    @include('manager::form.textareaElement', [
                        'name' => 'post',
                        'value' => (isset($data->post) ? $data->post : $data->content),
                        'class' => 'phptextarea',
                        'rows' => 20,
                        'attributes' => 'onChange="documentDirty=true;"'
                    ])
                </div>
                <!-- HTML text editor end -->

                <input type="submit" name="save" style="display:none">
            </div>

            <div class="tab-page" id="tabAssignedTVs">
                <h2 class="tab">{{ ManagerTheme::getLexicon('template_assignedtv_tab') }}</h2>
                <script>tp.addTabPage(document.getElementById('tabAssignedTVs'));</script>
                <input type="hidden" name="tvsDirty" id="tvsDirty" value="0">

                <div class="container container-body">
                    @if($data->tvs->count() > 0)
                        <p>{{ ManagerTheme::getLexicon('template_tv_msg') }}</p>
                    @endif

                    @if(EvolutionCMS()->hasPermission('save_template') && $data->tvs->count() > 1 && $data->getKey())
                        <div class="form-group">
                            <a class="btn btn-primary"
                               href="?a=117&id={{ $data->getKey() }}">{{ ManagerTheme::getLexicon('template_tv_edit') }}</a>
                        </div>
                    @endif

                    @if($data->tvs->count() > 0)
                        <ul>
                            @foreach($data->tvs as $item)
                                @include('manager::page.template.tv', [
                                    'item' => $item,
                                    'tvSelected' => [$item->getKey()]
                                ])
                            @endforeach
                        </ul>
                    @else
                        {{ ManagerTheme::getLexicon('template_no_tv') }}
                    @endif

                    @if($tvOutCategory->count() || $categoriesWithTv->count())
                        <hr>
                        <p>{{ ManagerTheme::getLexicon('template_notassigned_tv') }}</p>
                    @endif

                    @if($tvOutCategory->count() > 0)
                        @component('manager::partials.panelCollapse', ['name' => 'tv_in_template', 'id' => 0, 'title' => ManagerTheme::getLexicon('no_category')])
                            <ul>
                                @foreach($tvOutCategory as $item)
                                    @include('manager::page.template.tv', compact('item', 'tvSelected'))
                                @endforeach
                            </ul>
                        @endcomponent
                    @endif

                    @foreach($categoriesWithTv as $cat)
                        @component('manager::partials.panelCollapse', ['name' => 'tv_in_template', 'id' => $cat->id, 'title' => $cat->name])
                            <ul>
                                @foreach($cat->tvs as $item)
                                    @if(! $data->tvs->contains('id', $item->getKey()))
                                        @include('manager::page.template.tv', compact('item', 'tvSelected'))
                                    @endif
                                @endforeach
                            </ul>
                        @endcomponent
                    @endforeach
                </div>
            </div>

            {!! get_by_key($events, 'OnTempFormRender') !!}
        </div>
    </form>
@endsection
