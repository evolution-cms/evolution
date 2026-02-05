<?php /** get the page to show document's data */ ?>
@extends('manager::template.page')
@section('content')
    <?php /*include_once evolutionCMS()->get('ManagerTheme')->getFileProcessor("actions/document_data.static.php"); */?>
    <?php
    if (isset($_REQUEST['id'])) {
        $id = (int) $_REQUEST['id'];
    } else {
        $id = 0;
    }

    if (isset($_GET['opened'])) {
        $_SESSION['openedArray'] = $_GET['opened'];
    }

    if ($_SESSION['tree_show_only_folders']) {
        $resource = \EvolutionCMS\Models\SiteContent::find($id);
        $parent = $id ? $resource->parent : 0;
        $isfolder = $resource->isfolder;
        if (!$isfolder && $parent != 0) {
            $id = $_REQUEST['id'] = $parent;
        }
    }

    // Get the document content
    $resources = \EvolutionCMS\Models\SiteContent::withTrashed()->select('site_content.*')->distinct()
        ->leftJoin('document_groups', 'document_groups.document', '=', 'site_content.id')
        ->where('site_content.id', $id);
    if ($_SESSION['mgrRole'] != 1) {
        if (is_array($_SESSION['mgrDocgroups']) && count($_SESSION['mgrDocgroups']) > 0) {
            $resources = $resources->where(function ($q) {
                $q->where('site_content.privatemgr', 0)
                    ->orWhereIn('document_groups.document_group', $_SESSION['mgrDocgroups']);
            });
        } else {
            $resources = $resources->where('site_content.privatemgr', 0);
        }
    }

    $content = $resources->first();
    if (!$content) {
        EvolutionCMS()->webAlertAndQuit(ManagerTheme::getLexicon('access_permission_denied'));
    }
    $content = $content->toArray();

    $sd = isset($_REQUEST['dir']) ? '&dir=' . $_REQUEST['dir'] : '&dir=DESC';
    $sb = isset($_REQUEST['sort']) ? '&sort=' . $_REQUEST['sort'] : '&sort=createdon';
    $pg = isset($_REQUEST['page']) ? '&page=' . (int) $_REQUEST['page'] : '';
    $add_path = $sd . $sb . $pg;

    $actions = [
        'new'       => 'index.php?pid=' . $_REQUEST['id'] . '&a=4',
        'newlink'   => 'index.php?pid=' . $_REQUEST['id'] . '&a=72',
        'edit'      => 'index.php?id=' . $_REQUEST['id'] . '&a=27',
        'save'      => '',
        'delete'    => 'index.php?id=' . $_REQUEST['id'] . '&a=6',
        'cancel'    => 'index.php?' . ($id == 0 ? 'a=2' : 'a=3&r=1&id=' . $id . $add_path),
        'move'      => 'index.php?id=' . $_REQUEST['id'] . '&a=51',
        'duplicate' => 'index.php?id=' . $_REQUEST['id'] . '&a=94',
        'view'      => evo()->getConfig('friendly_urls') ? UrlProcessor::makeUrl($id) : MODX_SITE_URL . 'index.php?id=' . $id,
    ];

    /**
     * "General" tab setup
     */

    // Get Creator's username
    $createdbyname = \EvolutionCMS\Models\User::find($content['createdby']);
    if (!is_null($createdbyname)) {
        $createdbyname = $createdbyname->username;
    }

    // Get Editor's username
    $editedbyname = \EvolutionCMS\Models\User::find($content['editedby']);
    if (!is_null($editedbyname)) {
        $editedbyname = $editedbyname->username;
    }

    // Get Template name
    $templatename = \EvolutionCMS\Models\SiteTemplate::query()->where('id', '=', $content['template'])->get()->toArray();
    if (count($templatename)) {
        $templatename = $templatename[0]['templatename'];
    }
    $notSetHtml = '<span class="italic text-base-content/60">' . ManagerTheme::getLexicon('not_set') . '</span>';
    $lockIcon = svg('tabler-lock')->toHtml();
    $publishedBadge = $content['published'] == 0
        ? '<span class="badge badge-warning badge-sm">' . ManagerTheme::getLexicon('page_data_unpublished') . '</span>'
        : '<span class="badge badge-success badge-sm">' . ManagerTheme::getLexicon('page_data_published') . '</span>';
    $webAccessBadge = $content['privateweb'] == 0
        ? '<span class="badge badge-ghost badge-sm">' . ManagerTheme::getLexicon('public') . '</span>'
        : '<span class="badge badge-error badge-sm inline-flex items-center gap-1">' . $lockIcon . ManagerTheme::getLexicon('private') . '</span>';
    $mgrAccessBadge = $content['privatemgr'] == 0
        ? '<span class="badge badge-ghost badge-sm">' . ManagerTheme::getLexicon('public') . '</span>'
        : '<span class="badge badge-error badge-sm inline-flex items-center gap-1">' . $lockIcon . ManagerTheme::getLexicon('private') . '</span>';
    $createdByLabel = $createdbyname
        ? '<span class="text-base-content/60">(' . entities($createdbyname, evo()->getConfig('modx_charset')) . ')</span>'
        : '<span class="text-base-content/60">(' . ManagerTheme::getLexicon('not_set') . ')</span>';
    $editedByLabel = $editedbyname
        ? '<span class="text-base-content/60">(' . entities($editedbyname, evo()->getConfig('modx_charset')) . ')</span>'
        : '<span class="text-base-content/60">(' . ManagerTheme::getLexicon('not_set') . ')</span>';

    // Set the item name for logger
    $_SESSION['itemname'] = $content['pagetitle'];

    if ($content['isfolder']) {
        /**
         * "View Children" tab setup
         */
        $maxpageSize = evo()->getConfig('number_of_results');
        define('MAX_DISPLAY_RECORDS_NUM', $maxpageSize);

        // predefined constants
        $filter_sort = [
            'createdon' => ManagerTheme::getLexicon('createdon'),
            'pub_date'  => ManagerTheme::getLexicon('page_data_publishdate'),
            'pagetitle' => ManagerTheme::getLexicon('pagetitle'),
            'menuindex' => ManagerTheme::getLexicon('resource_opt_menu_index'),
            'published' => ManagerTheme::getLexicon('resource_opt_is_published'),
        ];
        $filter_dir = [
            'ASC'  => ManagerTheme::getLexicon('sort_asc'),
            'DESC' => ManagerTheme::getLexicon('sort_desc'),
        ];

        // Get child document count
        $childs = \EvolutionCMS\Models\SiteContent::query()->select('site_content.*')->distinct()
            ->leftJoin('document_groups', 'document_groups.document', '=', 'site_content.id')
            ->where('site_content.parent', $id);

        if ($_SESSION['mgrRole'] != 1) {
            if (is_array($_SESSION['mgrDocgroups']) && count($_SESSION['mgrDocgroups']) > 0) {
                $childs = $resources->where(function ($q) {
                    $q->where('site_content.privatemgr', 0)->orWhereIn('document_groups.document_group', $_SESSION['mgrDocgroups']);
                });
            } else {
                $childs = $resources->where('site_content.privatemgr', 0);
            }
        }

        $numRecords = $childs->count();

        $sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'createdon';
        $dir = isset($_REQUEST['dir']) ? $_REQUEST['dir'] : 'DESC';
        $pg = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] - 1 : 0;

        // Get child documents (with paging)

        if ($numRecords > 0) {
            $childs = $childs->orderBy($sort, $dir)->offset($pg * MAX_DISPLAY_RECORDS_NUM)->limit(MAX_DISPLAY_RECORDS_NUM)->get();
            $resource = $childs->toArray();

            // CSS style for table
            //	$tableClass = 'grid';
            //	$rowHeaderClass = 'gridHeader';
            //	$rowRegularClass = 'gridItem';
            //	$rowAlternateClass = 'gridAltItem';
            $tableClass = 'table data nowrap';
            $columnHeaderClass = [
                'text-center',
                'text-left',
                'text-center',
                'text-center',
                'text-center',
                'text-center'
            ];
            $table = new \EvolutionCMS\Support\MakeTable();
            $table->setTableClass($tableClass);
            $table->setColumnHeaderClass($columnHeaderClass);
            //	evo()->getMakeTable()->setRowHeaderClass($rowHeaderClass);
            //	evo()->getMakeTable()->setRowRegularClass($rowRegularClass);
            //	evo()->getMakeTable()->setRowAlternateClass($rowAlternateClass);

            // Table header
            $listTableHeader = [
                'docid'     => ManagerTheme::getLexicon('id'),
                'title'     => ManagerTheme::getLexicon('resource_title'),
                'createdon' => ManagerTheme::getLexicon('createdon'),
                'pub_date'  => ManagerTheme::getLexicon('page_data_publishdate'),
                'status'    => ManagerTheme::getLexicon('page_data_status'),
                'edit'      => ManagerTheme::getLexicon('mgrlog_action'),
            ];
            $tbWidth = [
                '1%',
                '',
                '1%',
                '1%',
                '1%',
                '1%'
            ];
            $table->setColumnWidths($tbWidth);

            $icons = [
                'text/plain'               => '<i class="' . $_style['icon_document'] . '"></i>',
                'text/html'                => '<i class="' . $_style['icon_document'] . '"></i>',
                'text/xml'                 => '<i class="' . $_style['icon_code_file'] . '"></i>',
                'text/css'                 => '<i class="' . $_style['icon_code_file'] . '"></i>',
                'text/javascript'          => '<i class="' . $_style['icon_code_file'] . '"></i>',
                'image/gif'                => '<i class="' . $_style['icon_image'] . '"></i>',
                'image/jpg'                => '<i class="' . $_style['icon_image'] . '"></i>',
                'image/png'                => '<i class="' . $_style['icon_image'] . '"></i>',
                'application/pdf'          => '<i class="' . $_style['icon_pdf'] . '"></i>',
                'application/rss+xml'      => '<i class="' . $_style['icon_code_file'] . '"></i>',
                'application/vnd.ms-word'  => '<i class="' . $_style['icon_word'] . '"></i>',
                'application/vnd.ms-excel' => '<i class="' . $_style['icon_excel'] . '"></i>',
            ];

            $listDocs = [];
            foreach ($resource as $k => $children) {

                switch ($children['id']) {
                    case evo()->getConfig('site_start')            :
                        $icon = '<i class="' . $_style['icon_home'] . '"></i>';
                        break;
                    case evo()->getConfig('error_page')            :
                        $icon = '<i class="' . $_style['icon_info_triangle'] . '"></i>';
                        break;
                    case evo()->getConfig('site_unavailable_page') :
                        $icon = '<i class="' . $_style['icon_clock'] . '"></i>';
                        break;
                    case evo()->getConfig('unauthorized_page')     :
                        $icon = '<i class="' . $_style['icon_info'] . '"></i>';
                        break;
                    default:
                        if ($children['isfolder']) {
                            $icon = '<i class="' . $_style['icon_folder'] . '"></i>';
                        } else {
                            if (isset($icons[$children['contentType']])) {
                                $icon = $icons[$children['contentType']];
                            } else {
                                $icon = '<i class="' . $_style['icon_document'] . '"></i>';
                            }
                        }
                }

                $private = ($children['privateweb'] || $children['privatemgr'] ? ' private' : '');

                // дописуємо в заголовок клас для неопублікованих плюс по всім посиланням зворотній шлях
                // для збереження сортування
                $class = ($children['deleted'] ? 'text-danger text-decoration-through' : (!$children['published'] ? ' font-italic text-muted' : ' publish'));
                if (evo()->hasPermission('edit_document')) {
                    $title = '<span class="doc-item' . $private . '">' . $icon . '<a href="index.php?a=27&id=' . $children['id'] . $add_path . '">' . '<span class="' . $class . '">' . entities($children['pagetitle'],
                            evo()->getConfig('modx_charset')) . '</span></a></span>';
                } else {
                    $title = '<span class="doc-item' . $private . '">' . $icon . '<span class="' . $class . '">' . entities($children['pagetitle'],
                            evo()->getConfig('modx_charset')) . '</span></span>';
                }

                $icon_pub_unpub = (!$children['published'])
                    ? '<a href="index.php?a=61&id=' . $children['id'] . $add_path . '" title="' . ManagerTheme::getLexicon('publish_resource') . '"><i class="' . $_style['icon_check'] . '"></i></a>'
                    : '<a href="index.php?a=62&id=' . $children['id'] . $add_path . '" title="' . ManagerTheme::getLexicon('unpublish_resource') . '"><i class="' . $_style['icon_close'] . '" ></i></a>';

                $icon_del_undel = (!$children['deleted'])
                    ? '<a onclick="return confirm(\'' . ManagerTheme::getLexicon('confirm_delete_resource') . '\')" href="index.php?a=6&id=' . $children['id'] . $add_path . '" title="' . ManagerTheme::getLexicon('delete_resource') . '"><i class="' . $_style['icon_trash'] . '"></i></a>'
                    : '<a onclick="return confirm(\'' . ManagerTheme::getLexicon('confirm_undelete') . '\')" href="index.php?a=63&id=' . $children['id'] . $add_path . '" title="' . ManagerTheme::getLexicon('undelete_resource') . '"><i class="' . $_style['icon_undo'] . '"></i></a>';

                $listDocs[] = [
                    'docid'     => '<div class="text-right">' . $children['id'] . '</div>',
                    'title'     => $title,
                    'createdon' => '<div class="text-right">' . (evo()->toDateFormat($children['createdon'] + evo()->timestamp(0),
                            'dateOnly')) . '</div>',
                    'pub_date'  => '<div class="text-right">' . ($children['pub_date'] ? (evo()->toDateFormat($children['pub_date'] + evo()->timestamp(0),
                            'dateOnly')) : '') . '</div>',
                    'status'    => '<div class="text-nowrap">' . ($children['published'] == 0 ? '<span class="unpublishedDoc">' . ManagerTheme::getLexicon('page_data_unpublished') . '</span>' : '<span class="publishedDoc">' . ManagerTheme::getLexicon('page_data_published') . '</span>') . '</div>',
                    'edit'      => '<div class="actions text-center text-nowrap">' . (evo()->hasPermission('edit_document') ? '<a href="index.php?a=27&id=' . $children['id'] . $add_path . '" title="' . ManagerTheme::getLexicon('edit') . '"><i class="' . $_style['icon_edit'] . '"></i></a>
                    <a href="index.php?a=51&id=' . $children['id'] . $add_path . '" title="' . ManagerTheme::getLexicon('move') . '"><i
                    class="' . $_style['icon_move'] . '"></i></a>' . $icon_pub_unpub : '') . (evo()->hasPermission('delete_document') ? $icon_del_undel : '') . '</div>'
                ];
            }

            $table->createPagingNavigation($numRecords, 'a=3&id=' . $content['id'] . '&dir=' . $dir . '&sort=' . $sort);
            $children_output = $table->create($listDocs, $listTableHeader, 'index.php?a=3&id=' . $content['id']);
        } else {
            // No Child documents
            $children_output = '<div class="container"><p>' . ManagerTheme::getLexicon('resources_in_container_no') . '</p></div>';
            $add_path = '';
        }
    }
    ?>
    <script type="text/javascript">
        var actions = {
            new: function () {
                document.location.href = "{!! $actions['new'] !!}";
            },
            newlink: function () {
                document.location.href = "{!! $actions['newlink'] !!}";
            },
            edit: function () {
                document.location.href = "{!! $actions['edit'] !!}";
            },
            save: function () {
                documentDirty = false;
                form_save = true;
                document.mutate.save.click();
            },
            delete: function () {
                if (confirm("{{ ManagerTheme::getLexicon('confirm_delete_resource') }}") === true) {
                    document.location.href = "{!! $actions['delete'] !!}";
                }
            },
            cancel: function () {
                documentDirty = false;
                document.location.href = "{!! $actions['cancel'] !!}";
            },
            move: function () {
                document.location.href = "{!! $actions['move'] !!}";
            },
            duplicate: function () {
                if (confirm("{{ ManagerTheme::getLexicon('confirm_resource_duplicate') }}") === true) {
                    document.location.href = "{!! $actions['duplicate'] !!}";
                }
            },
            view: function () {
                window.open("{!! $actions['view'] !!}", "previewWin");
            }
        };
    </script>
    <script type="text/javascript" src="media/script/tablesort.js"></script>

    <div class="drawer-content w-full mx-auto min-h-full bg-base-100 p-5 lg:px-5 lg:py-5 space-y-4">
    <x-mary-card class="bg-base-200" body-class="pt-3">
        <x-slot:title>
            <span class="inline-flex items-center gap-2">
                {!! svg('tabler-info-circle')->toHtml() !!}
                <span>{{ entities(iconv_substr($content['pagetitle'], 0, 50, evo()->getConfig('modx_charset')), evo()->getConfig('modx_charset')) }}</span>
                @if(iconv_strlen($content['pagetitle'], evo()->getConfig('modx_charset')) > 50)
                    <span>...</span>
                @endif
                <span class="badge badge-ghost badge-sm">#{{ (int)$_REQUEST['id'] }}</span>
            </span>
        </x-slot:title>
        <x-slot:menu class="ml-auto">
            <div class="flex flex-col items-end gap-2">
                <div id="actions" class="flex flex-wrap justify-end gap-2">
                    <div class="join">
                        @if(evo()->hasPermission('new_document'))
                            <x-mary-button class="btn-xs btn-ghost btn-square join-item bg-base-200/60" onclick="actions.new();" tooltip="{{ $_lang['create_resource_here'] }}">
                                <span class="text-success [&_svg]:h-4 [&_svg]:w-4">{!! svg('tabler-file-plus')->toHtml() !!}</span>
                            </x-mary-button>
                            <x-mary-button class="btn-xs btn-ghost btn-square join-item bg-base-200/60" onclick="actions.newlink();" tooltip="{{ $_lang['create_weblink_here'] }}">
                                <span class="text-info [&_svg]:h-4 [&_svg]:w-4">{!! svg('tabler-link')->toHtml() !!}</span>
                            </x-mary-button>
                        @endif
                    </div>
                    <x-mary-button id="Button1" class="btn-xs btn-primary gap-2" onclick="actions.edit();" tooltip="{{ $_lang['edit'] }}">
                        <span class="[&_svg]:h-4 [&_svg]:w-4">{!! svg('tabler-pencil')->toHtml() !!}</span>
                        <span>{{ $_lang['edit'] }}</span>
                    </x-mary-button>
                    <div class="join">
                        <x-mary-button id="Button2" class="btn-xs btn-ghost btn-square join-item bg-base-200/60" onclick="actions.move();" tooltip="{{ $_lang['move'] }}">
                            <span class="text-secondary [&_svg]:h-4 [&_svg]:w-4">{!! svg('tabler-arrows-move')->toHtml() !!}</span>
                        </x-mary-button>
                        <x-mary-button id="Button6" class="btn-xs btn-ghost btn-square join-item bg-base-200/60" onclick="actions.duplicate();" tooltip="{{ $_lang['duplicate'] }}">
                            <span class="text-accent [&_svg]:h-4 [&_svg]:w-4">{!! svg('tabler-copy')->toHtml() !!}</span>
                        </x-mary-button>
                        <x-mary-button id="Button3" class="btn-xs btn-ghost btn-square join-item bg-base-200/60" onclick="actions.delete();" tooltip="{{ $_lang['delete'] }}">
                            <span class="text-error [&_svg]:h-4 [&_svg]:w-4">{!! svg('tabler-trash')->toHtml() !!}</span>
                        </x-mary-button>
                        <x-mary-button id="Button4" class="btn-xs btn-ghost btn-square join-item bg-base-200/60" onclick="actions.view();" tooltip="{{ $_lang['preview'] }}">
                            <span class="text-base-content/70 [&_svg]:h-4 [&_svg]:w-4">{!! svg('tabler-eye')->toHtml() !!}</span>
                        </x-mary-button>
                    </div>
                </div>
            </div>
        </x-slot:menu>
        @php
            $typeValue = $content['type'] == 'reference' ? ManagerTheme::getLexicon('weblink') : ManagerTheme::getLexicon('resource');
            $templateValue = $content['template'] == 0 ? ManagerTheme::getLexicon('not_set') : entities($templatename, evo()->getConfig('modx_charset'));
            $templateClass = $content['template'] == 0 ? 'text-base-content/60 italic' : 'text-info';
            $publishedLabel = $content['published'] == 0 ? ManagerTheme::getLexicon('page_data_unpublished') : ManagerTheme::getLexicon('page_data_published');
            $publishedClass = $content['published'] == 0 ? 'text-warning' : 'text-success';
        @endphp
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="text-base-content/60">{{ ManagerTheme::getLexicon('type') }}:</span>
            <span class="text-info">{{ $typeValue }}</span>
            <span class="opacity-50">•</span>
            <span class="text-base-content/60">{{ ManagerTheme::getLexicon('page_data_template') }}:</span>
            <span class="{{ $templateClass }}">{{ $templateValue }}</span>
            <span class="opacity-50">•</span>
            <span class="text-base-content/60">{{ ManagerTheme::getLexicon('page_data_status') }}:</span>
            <span class="{{ $publishedClass }}">{{ $publishedLabel }}</span>
        </div>
    </x-mary-card>

    @php
        $docTabs = [
            ['name' => 'doc-general', 'label' => ManagerTheme::getLexicon('settings_general')],
        ];
        if ($content['isfolder']) {
            $docTabs[] = ['name' => 'doc-children', 'label' => ManagerTheme::getLexicon('view_child_resources_in_container')];
        }
        $requestedTabIndex = (int) (is_numeric(get_by_key($_GET, 'tab')) ? $_GET['tab'] : 0);
        $requestedTabIndex = max(0, min($requestedTabIndex, count($docTabs) - 1));
        $selectedTabName = $docTabs[$requestedTabIndex]['name'] ?? $docTabs[0]['name'];
    @endphp

    <div data-doc-tabs>
        <div class="tabs tabs-box bg-primary/5 rounded-box w-fit p-1" role="tablist">
            @foreach($docTabs as $index => $tab)
                @php
                    $isActive = $tab['name'] === $selectedTabName;
                @endphp
                <button
                    type="button"
                    class="tab font-semibold {{ $isActive ? 'bg-primary rounded-box !text-white tab-active' : '' }}"
                    data-doc-tab="{{ $tab['name'] }}"
                    data-doc-index="{{ $index }}"
                    role="tab"
                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                >
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>

        <div class="mt-4">
            <div class="{{ $selectedTabName === 'doc-general' ? '' : 'hidden' }}" data-doc-panel="doc-general">
                <div class="space-y-4">
                    <x-mary-card separator class="bg-base-200" body-class="pt-3">
                        <x-slot:title>{{ ManagerTheme::getLexicon('page_data_general') }}</x-slot:title>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('resource_title') }}</div>
                                <div class="font-semibold">{{ entities($content['pagetitle']) }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('long_title') }}</div>
                                <div>{!! $content['longtitle'] != '' ? entities($content['longtitle'], evo()->getConfig('modx_charset')) : $notSetHtml !!}</div>
                            </div>
                            <div class="md:col-span-2">
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('resource_description') }}</div>
                                <div>{!! $content['description'] != '' ? entities($content['description'], evo()->getConfig('modx_charset')) : $notSetHtml !!}</div>
                            </div>
                            <div class="md:col-span-2">
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('resource_summary') }}</div>
                                <div>{!! $content['introtext'] != '' ? entities($content['introtext'], evo()->getConfig('modx_charset')) : $notSetHtml !!}</div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('type') }}</div>
                                <div>{{ $content['type'] == 'reference' ? ManagerTheme::getLexicon('weblink') : ManagerTheme::getLexicon('resource') }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('resource_alias') }}</div>
                                <div>{!! $content['alias'] != '' ? entities($content['alias'], evo()->getConfig('modx_charset')) : $notSetHtml !!}</div>
                            </div>
                        </div>
                    </x-mary-card>

                    <x-mary-card separator class="bg-base-200" body-class="pt-3">
                        <x-slot:title>{{ ManagerTheme::getLexicon('page_data_changes') }}</x-slot:title>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('page_data_created') }}</div>
                                <div>{{ evo()->toDateFormat($content['createdon'] + evo()->timestamp(0)) }} {!! $createdByLabel !!}</div>
                            </div>
                            @if($editedbyname)
                                <div>
                                    <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('page_data_edited') }}</div>
                                    <div>{{ evo()->toDateFormat($content['editedon'] + evo()->timestamp(0)) }} {!! $editedByLabel !!}</div>
                                </div>
                            @endif
                        </div>
                    </x-mary-card>

                    <x-mary-card separator class="bg-base-200" body-class="pt-3">
                        <x-slot:title>{{ ManagerTheme::getLexicon('page_data_status') }}</x-slot:title>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('page_data_status') }}</div>
                                <div>{!! $publishedBadge !!}</div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('page_data_publishdate') }}</div>
                                <div>{!! $content['pub_date'] == 0 ? $notSetHtml : evo()->toDateFormat($content['pub_date']) !!}</div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('page_data_unpublishdate') }}</div>
                                <div>{!! $content['unpub_date'] == 0 ? $notSetHtml : evo()->toDateFormat($content['unpub_date']) !!}</div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('page_data_cacheable') }}</div>
                                <div><span class="badge badge-ghost badge-sm">{{ $content['cacheable'] == 0 ? ManagerTheme::getLexicon('no') : ManagerTheme::getLexicon('yes') }}</span></div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('page_data_searchable') }}</div>
                                <div><span class="badge badge-ghost badge-sm">{{ $content['searchable'] == 0 ? ManagerTheme::getLexicon('no') : ManagerTheme::getLexicon('yes') }}</span></div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('resource_opt_menu_index') }}</div>
                                <div>{{ entities($content['menuindex'], evo()->getConfig('modx_charset')) }}</div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('resource_opt_show_menu') }}</div>
                                <div><span class="badge badge-ghost badge-sm">{{ $content['hidemenu'] == 1 ? ManagerTheme::getLexicon('no') : ManagerTheme::getLexicon('yes') }}</span></div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('page_data_web_access') }}</div>
                                <div>{!! $webAccessBadge !!}</div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('page_data_mgr_access') }}</div>
                                <div>{!! $mgrAccessBadge !!}</div>
                            </div>
                        </div>
                    </x-mary-card>

                    <x-mary-card separator class="bg-base-200" body-class="pt-3">
                        <x-slot:title>{{ ManagerTheme::getLexicon('page_data_markup') }}</x-slot:title>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('page_data_template') }}</div>
                                <div>{!! $content['template'] == 0 ? $notSetHtml : entities($templatename, evo()->getConfig('modx_charset')) !!}</div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('page_data_editor') }}</div>
                                <div><span class="badge badge-ghost badge-sm">{{ $content['richtext'] == 0 ? ManagerTheme::getLexicon('no') : ManagerTheme::getLexicon('yes') }}</span></div>
                            </div>
                            <div>
                                <div class="text-xs text-base-content/60">{{ ManagerTheme::getLexicon('page_data_folder') }}</div>
                                <div><span class="badge badge-ghost badge-sm">{{ $content['isfolder'] == 0 ? ManagerTheme::getLexicon('no') : ManagerTheme::getLexicon('yes') }}</span></div>
                            </div>
                        </div>
                    </x-mary-card>
                </div>
            </div>

        @if($content['isfolder'])
            <div class="{{ $selectedTabName === 'doc-children' ? '' : 'hidden' }}" data-doc-panel="doc-children">
                <div class="container container-body">
                    <div class="form-group clearfix">
                        @if($numRecords > 0)
                            <div class="float-xs-left">
                                <span class="publishedDoc">{{ $numRecords }} {{ ManagerTheme::getLexicon('resources_in_container') }} (<strong>{{ entities($content['pagetitle'], evo()->getConfig('modx_charset')) }}</strong>)</span>
                            </div>
                        @endif
                        <div class="float-right">
                            @if($numRecords > 0)
                                <select size="1" name="sort" class="form-control form-control-sm"
                                        onchange="document.location='index.php?a=3&id={{ $id }}&dir={{ $dir }}&sort=' + this.options[this.selectedIndex].value">
                                    @foreach($filter_sort as $key => $val)
                                        <option value="{{ $key }}"
                                                @if($key == $sort) selected @endif>{{ $val }}</option>
                                    @endforeach
                                </select>
                                <select size="1" name="dir" class="form-control form-control-sm"
                                        onchange="document.location='index.php?a=3&id={{ $id }}&sort={{ $sort }}&dir=' + this.options[this.selectedIndex].value">
                                    @foreach($filter_dir as $key => $val)
                                        <option value="{{ $key }}" @if($key == $dir) selected @endif>{{ $val }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                    <div class="row">
                        <div class="table-responsive">{!! $children_output !!}</div>
                    </div>
                </div>
            </div>
        @endif
        </div>
    </div>

    <script>
        (function () {
            var tabsRoot = document.querySelector('[data-doc-tabs]');
            if (!tabsRoot) return;
            var buttons = tabsRoot.querySelectorAll('[data-doc-tab]');
            var panels = tabsRoot.querySelectorAll('[data-doc-panel]');
            if (!buttons.length || !panels.length) return;

            function selectTab(targetName, targetIndex) {
                buttons.forEach(function (btn) {
                    var isActive = btn.getAttribute('data-doc-tab') === targetName;
                    btn.classList.toggle('bg-primary', isActive);
                    btn.classList.toggle('rounded', isActive);
                    btn.classList.toggle('tab-active', isActive);
                    if (isActive) {
                        btn.classList.add('!text-white');
                    } else {
                        btn.classList.remove('!text-white');
                    }
                    btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });
                panels.forEach(function (panel) {
                    panel.classList.toggle('hidden', panel.getAttribute('data-doc-panel') !== targetName);
                });
                if (typeof targetIndex === 'number') {
                    try {
                        var url = new URL(window.location.href);
                        url.searchParams.set('tab', String(targetIndex));
                        history.replaceState(null, '', url.toString());
                    } catch (e) {}
                }
            }

            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var name = btn.getAttribute('data-doc-tab');
                    var index = parseInt(btn.getAttribute('data-doc-index'), 10);
                    selectTab(name, isNaN(index) ? null : index);
                });
            });
        })();
    </script>

    @if(!empty($show_preview))
        <div class="sectionHeader">{{ ManagerTheme::getLexicon('preview') }}</div>
        <div class="sectionBody" id="lyr2">
            <iframe src="{{ MODX_SITE_URL }}index.php?id={{ $id }}&z=manprev" frameborder="0" border="0" id="previewIframe"></iframe>
        </div>
    @endif
    </div>
@endsection
