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
        'view'      => evo()->getConfig('friendly_urls') ? UrlProcessor::makeUrl($id) : EVO_SITE_URL . 'index.php?id=' . $id,
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
                $childs = $childs->where(function ($q) {
                    $q->where('site_content.privatemgr', 0)->orWhereIn('document_groups.document_group', $_SESSION['mgrDocgroups']);
                });
            } else {
                $childs = $childs->where('site_content.privatemgr', 0);
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

    <h1>
        <i class="{{ $_style['icon_info'] }}"></i>
        {{ entities(iconv_substr($content['pagetitle'], 0, 50, evo()->getConfig('modx_charset')), evo()->getConfig('modx_charset')) }}
        @if(iconv_strlen($content['pagetitle'], evo()->getConfig('modx_charset')) > 50)
            ...
        @endif
        <small>({{ (int)$_REQUEST['id'] }})</small>
    </h1>

    {!! $_style['actionbuttons']['static']['document'] !!}

    <div class="tab-pane" id="childPane">
        <script type="text/javascript">
            docSettings = new WebFXTabPane(document.getElementById("childPane"), @if(evo()->getConfig('remember_last_tab')) true
            @else false @endif);
        </script>

        <!-- General -->
        <div class="tab-page" id="tabdocGeneral">
            <h2 class="tab">{{ ManagerTheme::getLexicon('settings_general') }}</h2>
            <script type="text/javascript">docSettings.addTabPage(document.getElementById("tabdocGeneral"));</script>
            <div class="container container-body">
                <table>
                    <tr>
                        <td colspan="2"><b>{{ ManagerTheme::getLexicon('page_data_general') }}</b></td>
                    </tr>
                    <tr>
                        <td width="200" valign="top">{{ ManagerTheme::getLexicon('resource_title') }}:</td>
                        <td><b><?= entities($content['pagetitle']) ?></b></td>
                    </tr>
                    <tr>
                        <td width="200" valign="top">{{ ManagerTheme::getLexicon('long_title') }}:</td>
                        <td>
                            <small><?= $content['longtitle'] != '' ? entities($content['longtitle'],
                                    evo()->getConfig('modx_charset')) : "(<i>" . ManagerTheme::getLexicon('not_set') . "</i>)" ?>
                            </small>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">{{ ManagerTheme::getLexicon('resource_description') }}:</td>
                        <td><?= $content['description'] != '' ? entities($content['description'],
                                evo()->getConfig('modx_charset')) : "(<i>" . ManagerTheme::getLexicon('not_set') . "</i>)" ?>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">{{ ManagerTheme::getLexicon('resource_summary') }}:</td>
                        <td><?= $content['introtext'] != '' ? entities($content['introtext'],
                                evo()->getConfig('modx_charset')) : "(<i>" . ManagerTheme::getLexicon('not_set') . "</i>)" ?>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top">{{ ManagerTheme::getLexicon('type') }}:</td>
                        <td><?= $content['type'] == 'reference' ? ManagerTheme::getLexicon('weblink') : ManagerTheme::getLexicon('resource') ?></td>
                    </tr>
                    <tr>
                        <td valign="top">{{ ManagerTheme::getLexicon('resource_alias') }}:</td>
                        <td><?= $content['alias'] != '' ? entities($content['alias'], evo()->getConfig('modx_charset')) : "(<i>" . ManagerTheme::getLexicon('not_set') . "</i>)" ?></td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2"><b>{{ManagerTheme::getLexicon('page_data_changes')}}</b></td>
                    </tr>
                    <tr>
                        <td>{{ManagerTheme::getLexicon('page_data_created')}}:</td>
                        <td>{{evo()->toDateFormat($content['createdon'] + evo()->timestamp(0))}}
                            @if($createdbyname)
                                (<b>{{$createdbyname}}</b>)
                            @else
                                (<i>{{ManagerTheme::getLexicon('not_set')}}</i>)
                            @endif
                        </td>
                    </tr>
                    <?php if($editedbyname != '') { ?>
                    <tr>
                        <td>{{ManagerTheme::getLexicon('page_data_edited')}}:</td>
                        <td><?= evo()->toDateFormat($content['editedon'] + evo()->timestamp(0)) ?>
                            (<b><?= entities($editedbyname, evo()->getConfig('modx_charset')) ?></b>)
                        </td>
                    </tr>
                    <?php } ?>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2"><b>{{ ManagerTheme::getLexicon('page_data_status') }}</b></td>
                    </tr>
                    <tr>
                        <td>{{ ManagerTheme::getLexicon('page_data_status') }}:</td>
                        <td><?= $content['published'] == 0 ? '<span class="unpublishedDoc">' . ManagerTheme::getLexicon('page_data_unpublished') . '</span>' : '<span class="publisheddoc">' . ManagerTheme::getLexicon('page_data_published') . '</span>' ?></td>
                    </tr>
                    <tr>
                        <td>{{ ManagerTheme::getLexicon('page_data_publishdate') }}:</td>
                        <td><?= $content['pub_date'] == 0 ? "(<i>" . ManagerTheme::getLexicon('not_set') . "</i>)" : evo()->toDateFormat($content['pub_date']) ?></td>
                    </tr>
                    <tr>
                        <td>{{ ManagerTheme::getLexicon('page_data_unpublishdate') }}:</td>
                        <td><?= $content['unpub_date'] == 0 ? "(<i>" . ManagerTheme::getLexicon('not_set') . "</i>)" : evo()->toDateFormat($content['unpub_date']) ?></td>
                    </tr>
                    <tr>
                        <td>{{ ManagerTheme::getLexicon('page_data_cacheable') }}:</td>
                        <td><?= $content['cacheable'] == 0 ? ManagerTheme::getLexicon('no') : ManagerTheme::getLexicon('yes') ?></td>
                    </tr>
                    <tr>
                        <td>{{ ManagerTheme::getLexicon('page_data_searchable') }}:</td>
                        <td><?= $content['searchable'] == 0 ? ManagerTheme::getLexicon('no') : ManagerTheme::getLexicon('yes') ?></td>
                    </tr>
                    <tr>
                        <td>{{ ManagerTheme::getLexicon('resource_opt_menu_index') }}:</td>
                        <td><?= entities($content['menuindex'], evo()->getConfig('modx_charset')) ?></td>
                    </tr>
                    <tr>
                        <td>{{ ManagerTheme::getLexicon('resource_opt_show_menu') }}:</td>
                        <td><?= $content['hidemenu'] == 1 ? ManagerTheme::getLexicon('no') : ManagerTheme::getLexicon('yes') ?></td>
                    </tr>
                    <tr>
                        <td>{{ ManagerTheme::getLexicon('page_data_web_access') }}:</td>
                        <td><?= $content['privateweb'] == 0 ? ManagerTheme::getLexicon('public') : '<b style="color: #821517">' . ManagerTheme::getLexicon('private') . '</b><i class="' . $_style['icon_lock'] . '"></i>' ?></td>
                    </tr>
                    <tr>
                        <td>{{ ManagerTheme::getLexicon('page_data_mgr_access') }}:</td>
                        <td><?= $content['privatemgr'] == 0 ? ManagerTheme::getLexicon('public') : '<b style="color: #821517">' . ManagerTheme::getLexicon('private') . '</b><i class="' . $_style['icon_lock'] . '"></i>' ?></td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td colspan="2"><b>{{ ManagerTheme::getLexicon('page_data_markup') }}</b></td>
                    </tr>
                    <tr>
                        <td>{{ ManagerTheme::getLexicon('page_data_template') }}:</td>
                        <td><?= $content['template'] == 0 ? "(<i>" . ManagerTheme::getLexicon('not_set') . "</i>)" : entities($templatename,
                                evo()->getConfig('modx_charset')) ?></td>
                    </tr>
                    <tr>
                        <td>{{ ManagerTheme::getLexicon('page_data_editor') }}:</td>
                        <td><?= $content['richtext'] == 0 ? ManagerTheme::getLexicon('no') : ManagerTheme::getLexicon('yes') ?></td>
                    </tr>
                    <tr>
                        <td>{{ ManagerTheme::getLexicon('page_data_folder') }}:</td>
                        <td><?= $content['isfolder'] == 0 ? ManagerTheme::getLexicon('no') : ManagerTheme::getLexicon('yes') ?></td>
                    </tr>
                </table>
            </div>
        </div><!-- end tab-page -->

        @if($content['isfolder'])
            <!-- View Children -->
            <div class="tab-page" id="tabChildren">
                <h2 class="tab">{{ ManagerTheme::getLexicon('view_child_resources_in_container') }}</h2>
                <script type="text/javascript">docSettings.addTabPage(document.getElementById("tabChildren"));</script>
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
            </div><!-- end tab-page -->
        @endif
    </div><!-- end documentPane -->

    @if(is_numeric(get_by_key($_GET, 'tab')))
        <script type="text/javascript">
            docSettings.setSelectedIndex({{ $_GET['tab'] }});
        </script>
    @endif

    @if(!empty($show_preview))
        <div class="sectionHeader">{{ ManagerTheme::getLexicon('preview') }}</div>
        <div class="sectionBody" id="lyr2">
            <iframe src="{{ EVO_SITE_URL }}index.php?id={{ $id }}&z=manprev" frameborder="0" border="0" id="previewIframe"></iframe>
        </div>
    @endif
@endsection
