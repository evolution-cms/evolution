<!DOCTYPE html>
<html dir="{{ManagerTheme::getTextDir()}}" lang="{{ManagerTheme::getLang()}}" xml:lang="{{ManagerTheme::getLang()}}">
<head>
    <title>{{evo()->getConfig('site_name')}} - (Evolution CMS Manager)</title>
    <meta http-equiv="Content-Type" content="text/html; charset={{ManagerTheme::getCharset()}}" />
    <meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width" />
    <meta name="theme-color" content="#1d2023" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="stylesheet" type="text/css" href="{{$css}}" />
    @if (evo()->getConfig('show_picker'))
        <link rel="stylesheet" href="media/style/common/spectrum/spectrum.css" />
        <link rel="stylesheet" type="text/css" href="{{ManagerTheme::getThemeUrl()}}css/color.switcher.css" />
    @endif
    <link rel="icon" type="image/ico" href="{{ManagerTheme::getStyle('favicon')}}" />
    <style>
        #tree{width:{{$MODX_widthSideBar}}rem}
        #main,#resizer{left:{{$MODX_widthSideBar}}rem}
        .ios #main{-webkit-overflow-scrolling:touch;overflow-y:scroll;}
    </style>
    <script>
        if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
            document.documentElement.className += ' ios';
        }
    </script>
    <script src="media/script/jquery/jquery.min.js" type="text/javascript"></script>
    <script>
        // GLOBAL variable modx
        var modx = {
            MGR_DIR: '{{MGR_DIR}}',
            MODX_SITE_URL: '{{MODX_SITE_URL}}',
            MODX_MANAGER_URL: '{{MODX_MANAGER_URL}}',
            user: {
                role: {{(int)$user['role']}},
                username: '{{$user['username']}}',
                groups: {!!json_encode(evo()->getUserDocGroups())!!}
            },
            config: {
                manager_title: '{{evo()->getConfig('site_name')}} - (Evolution CMS Manager)',
                menu_height: {{(int)evo()->getConfig('manager_menu_height')}},
                tree_width: {{(int)$MODX_widthSideBar}},
                tree_min_width: {{(int)$tree_min_width}},
                session_timeout: {{(int)evo()->getConfig('session_timeout')}},
                site_start: {{(int)evo()->getConfig('site_start')}},
                tree_page_click: {{evo()->getConfig('tree_page_click')}},
                theme: '{{ManagerTheme::getTheme()}}',
                theme_mode: '{{ManagerTheme::getThemeStyle()}}',
                which_browser: '{{$user['which_browser']}}',
                layout: {{(int)evo()->getConfig('manager_layout')}},
                textdir: '{{ManagerTheme::getTextDir()}}',
                global_tabs: {{(int)evo()->getConfig('global_tabs')}}
            },
            lang: {
                already_deleted: "{{ManagerTheme::getLexicon('already_deleted')}}",
                cm_unknown_error: "{{ManagerTheme::getLexicon('cm_unknown_error')}}",
                collapse_tree: "{{ManagerTheme::getLexicon('collapse_tree')}}",
                confirm_delete_resource: "{{ManagerTheme::getLexicon('confirm_delete_resource')}}",
                confirm_empty_trash: "{{ManagerTheme::getLexicon('confirm_empty_trash')}}",
                confirm_publish: "{{ManagerTheme::getLexicon('confirm_publish')}}",
                confirm_remove_locks: "{{ManagerTheme::getLexicon('confirm_remove_locks')}}",
                confirm_resource_duplicate: "{{ManagerTheme::getLexicon('confirm_resource_duplicate')}}",
                confirm_undelete: "{{ManagerTheme::getLexicon('confirm_undelete')}}",
                confirm_unpublish: "{{ManagerTheme::getLexicon('confirm_unpublish')}}",
                empty_recycle_bin: "{{ManagerTheme::getLexicon('empty_recycle_bin')}}",
                empty_recycle_bin_empty: "{{ManagerTheme::getLexicon('empty_recycle_bin_empty')}}",
                error_no_privileges: "{{ManagerTheme::getLexicon('error_no_privileges')}}",
                expand_tree: "{{ManagerTheme::getLexicon('expand_tree')}}",
                loading_doc_tree: "{{ManagerTheme::getLexicon('loading_doc_tree')}}",
                loading_menu: "{{ManagerTheme::getLexicon('loading_menu')}}",
                not_deleted: "{{ManagerTheme::getLexicon('not_deleted')}}",
                unable_set_link: "{{ManagerTheme::getLexicon('unable_set_link')}}",
                unable_set_parent: "{{ManagerTheme::getLexicon('unable_set_parent')}}",
                working: "{{ManagerTheme::getLexicon('working')}}",
                paging_prev: "{{ManagerTheme::getLexicon('paging_prev')}}"
            },
            style: {
                actions_file: '{!!addslashes($_style['icon_file'])!!}',
                actions_pencil: '{!!addslashes($_style['icon_pencil'])!!}',
                actions_plus: '{!!addslashes($_style['icon_plus'])!!}',
                actions_reply: '{!!addslashes($_style['icon_reply'])!!}',
                collapse_tree: '{!!addslashes('<i class="' . $_style['icon_arrow_up_circle'] . '"></i>')!!}',
                email: '{!!addslashes('<i class="' . $_style['icon_mail'] . '"></i>')!!}',
                expand_tree: '{!!addslashes('<i class="' . $_style['icon_arrow_down_circle'] . '"></i>')!!}',
                icon_angle_left: '{!!addslashes($_style['icon_angle_left'])!!}',
                icon_angle_right: '{!!addslashes($_style['icon_angle_right'])!!}',
                icon_chunk: '{!!addslashes($_style['icon_chunk'])!!}',
                icon_circle: '{!!addslashes($_style['icon_circle'])!!}',
                icon_code: '{!!addslashes($_style['icon_code'])!!}',
                icon_edit: '{!!addslashes($_style['icon_edit'])!!}',
                icon_element: '{!!addslashes($_style['icon_elements'])!!}',
                icon_folder: '{!!addslashes('<i class="' . $_style['icon_folder'] . '"></i>')!!}',
                icon_plugin: '{!!addslashes($_style['icon_plugin'])!!}',
                icon_refresh: '{!!addslashes($_style['icon_refresh'])!!}',
                icon_spin: '{!!addslashes($_style['icon_spin'])!!}',
                icon_template: '{!!addslashes($_style['icon_template'])!!}',
                icon_trash: '{!!addslashes('<i class="' . $_style['icon_trash'] . '"></i>')!!}',
                icon_trash_alt: '{!!addslashes('<i class="' . $_style['icon_trash_alt'] . '"></i>')!!}',
                icon_tv: '{!!addslashes($_style['icon_tv'])!!}',
                icons_external_link: '{!!addslashes('<i class="' . $_style['icon_external_link'] . '"></i>')!!}',
                icons_working: '{!!addslashes('<i class="' . $_style['icon_info_triangle'] . '"></i>')!!}',
                tree_folder: '{!!addslashes('<i class="' . $_style['icon_folder'] . '"></i>')!!}',
                tree_folder_secure: '{!!addslashes('<i class="' . $_style['icon_folder'] . '"></i>')!!}',
                tree_folderopen: '{!!addslashes('<i class="' . $_style['icon_folder_open'] . '"></i>')!!}',
                tree_folderopen_secure: '{!!addslashes('<i class="' . $_style['icon_folder_open'] . '"></i>')!!}',
                tree_info: '{!!addslashes('<i class="' . $_style['icon_info_circle'] . '"></i>')!!}',
                tree_minusnode: '{!!addslashes('<i class="' . $_style['icon_angle_down'] . '"></i>')!!}',
                tree_plusnode: '{!!addslashes('<i class="' . $_style['icon_angle_right'] . '"></i>')!!}',
                tree_preview_resource: '{!!addslashes('<i class="' . $_style['icon_eye'] . '"></i>')!!}'
            },
            permission: {
                assets_images: {{evo()->hasPermission('assets_images') ? 1 : 0}},
                delete_document: {{evo()->hasPermission('delete_document') ? 1 : 0}},
                edit_chunk: {{evo()->hasPermission('edit_chunk') ? 1 : 0}},
                edit_plugin: {{evo()->hasPermission('edit_plugin') ? 1 : 0}},
                edit_snippet: {{evo()->hasPermission('edit_snippet') ? 1 : 0}},
                edit_template: {{evo()->hasPermission('edit_template') ? 1 : 0}},
                new_document: {{evo()->hasPermission('new_document') ? 1 : 0}},
                publish_document: {{evo()->hasPermission('publish_document') ? 1 : 0}},
                dragndropdocintree: {{evo()->hasPermission('new_document') && evo()->hasPermission('edit_document') && evo()->hasPermission('save_document') ? 1 : 0}}
            },
            plugins: {
                ElementsInTree: {{isset(evo()->pluginCache['ElementsInTree']) ? 1 : 0}},
                EVOmodal: {{isset(evo()->pluginCache['EVO.modal']) ? 1 : 0}}
            },
            extend: function() {
                for (var i = 1; i < arguments.length; i++) {
                    for (var key in arguments[i]) {
                        if (arguments[i].hasOwnProperty(key)) {
                            arguments[0][key] = arguments[i][key];
                        }
                    }
                }
                return arguments[0];
            },
            extended: function(a) {
                for (var b in a) {
                    this[b] = a[b];
                }
                delete a[b];
            },
            openedArray: [],
            lockedElementsTranslation: <?= json_encode($unlockTranslations, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE) . "\n" ?>
        };
        <?php
        $opened = array_filter(array_map('intval', explode('|', isset($_SESSION['openedArray']) && is_scalar($_SESSION['openedArray']) ? $_SESSION['openedArray'] : '')));
        echo (empty($opened) ? '' : 'modx.openedArray[' . implode("] = 1;\n		modx.openedArray[", $opened) . '] = 1;') . "\n";
        ?>
    </script>
    <script src="{{ManagerTheme::getThemeUrl()}}js/modx.js?v={{evo()->getVersionData('version')}}"></script>
    @if ($modx->getConfig('show_picker'))
        <script src="media/script/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="media/script/spectrum/spectrum.evo.min.js" type="text/javascript"></script>
        <script src="{{ManagerTheme::getThemeUrl()}}js/color.switcher.js" type="text/javascript"></script>
    @endif
    <?php
    // invoke OnManagerTopPrerender event
    $evtOut = $modx->invokeEvent('OnManagerTopPrerender', $_REQUEST);
    if (is_array($evtOut)) {
        echo implode("\n", $evtOut);
    }
    ?>
</head>
<body class="{{$body_class}}">
<input type="hidden" name="sessToken" id="sessTokenInput" value="{{isset($_SESSION['mgrToken']) ? $_SESSION['mgrToken'] : ''}}" />
<div id="frameset">
    <div id="mainMenu" class="dropdown">
        <div class="container">
            <div class="row">
                <div class="cell" data-evocp="bgmColor">{!!$menu!!}</div>
                <div class="cell" data-evocp="bgmColor">
                    <ul id="settings" class="nav">
                        <li id="searchform">
                            <form action="index.php?a=71" method="post" target="main">
                                <input type="hidden" value="Search" name="submitok" />
                                <label for="searchid" class="label_searchid"><i class="{{$_style['icon_search']}}"></i></label>
                                <input type="text" id="searchid" name="searchid" size="25" />
                                <div class="mask"></div>
                            </form>
                        </li>
                        @if (evo()->getConfig('show_newresource_btn') && evo()->hasPermission('new_document'))
                            <li id="newresource" class="dropdown newresource">
                                <a href="javascript:;" class="dropdown-toggle" onclick="return false;" title="{{ManagerTheme::getLexicon('add_resource')}}">
                                    <i class="{{$_style['icon_plus']}}"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    @if (evo()->hasPermission('new_document'))
                                        <li>
                                            <a onclick="" href="index.php?a=4" target="main">
                                                <i class="{{$_style['icon_document']}}"></i>{{ManagerTheme::getLexicon('add_resource')}}
                                            </a>
                                        </li>
                                        <li>
                                            <a onclick="" href="index.php?a=72" target="main">
                                                <i class="{{$_style['icon_chain']}}"></i>{{ManagerTheme::getLexicon('add_weblink')}}
                                            </a>
                                        </li>
                                    @endif
                                    @if (evo()->getConfig('use_browser') && evo()->hasPermission('assets_images'))
                                        <li>
                                            <a onclick="" href="media/browser/{{evo()->getConfig('which_browser')}}/browse.php?&type=images" target="main">
                                                <i class="{{$_style['icon_camera']}}"></i>{{ManagerTheme::getLexicon('images_management')}}
                                            </a>
                                        </li>
                                    @endif
                                    @if (evo()->getConfig('use_browser') && evo()->hasPermission('assets_files'))
                                        <li>
                                            <a onclick="" href="media/browser/{{$modx->getConfig('which_browser')}}/browse.php?&type=files" target="main">
                                                <i class="{{$_style['icon_files']}}"></i>{{ManagerTheme::getLexicon('files_management')}}
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        <li id="preview">
                            <a href="../" target="_blank" title="{{ManagerTheme::getLexicon('preview')}}">
                                <i class="{{$_style['icon_desktop']}}"></i>
                            </a>
                        </li>
                        <li id="account" class="dropdown account">
                            <a href="javascript:;" class="dropdown-toggle" onclick="return false;">
                                <span class="username">{{entities($user['username'], evo()->getConfig('modx_charset'))}}</span>
                                @if ($user['photo'])
                                    <span class="icon photo" style="background-image: url({!!MODX_SITE_URL . entities($user['photo'], evo()->getConfig('modx_charset'))!!});"></span>
                                @else
                                    <span class="icon"><i class="{{$_style['icon_user']}}"></i></span>
                                @endif
                            </a>
                            <ul class="dropdown-menu">
                                @if (evo()->hasPermission('change_password'))
                                    <li>
                                        <a onclick="" href="index.php?a=28" target="main">
                                            <i class="{{$_style['icon_lock']}}"></i>{{ManagerTheme::getLexicon('change_password')}}
                                        </a>
                                    </li>
                                @endif
                                <li>
                                    <a href="index.php?a=8">
                                        <i class="{{$_style['icon_logout']}}"></i>{{ManagerTheme::getLexicon('logout')}}
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li id="theme">
                            <a id="treeMenu_theme_dark" onclick="modx.tree.toggleTheme(event)" title="{{ManagerTheme::getLexicon('manager_theme_mode_title')}}">
                                <i class="{{$_style['icon_theme']}}"></i>
                            </a>
                        </li>
                        @if (
                            evo()->hasPermission('settings') ||
                            evo()->hasPermission('view_eventlog') ||
                            evo()->hasPermission('logs') ||
                            evo()->hasPermission('help')
                        )
                            <li id="system" class="dropdown">
                                <a href="javascript:;" class="dropdown-toggle" title="{{ManagerTheme::getLexicon('system')}}" onclick="return false;">
                                    <i class="{{$_style['icon_cogs']}}"></i>
                                </a>
                                <ul class="dropdown-menu">
                                    @if (evo()->hasPermission('settings'))
                                        <li>
                                            <a href="index.php?a=17" target="main">
                                                <i class="{{$_style['icon_sliders']}}"></i>{{ManagerTheme::getLexicon('edit_settings')}}
                                            </a>
                                        </li>
                                    @endif
                                    @if (evo()->hasPermission('view_eventlog'))
                                        <li>
                                            <a href="index.php?a=70" target="main">
                                                <i class="{{$_style['icon_calendar']}}"></i>{{ManagerTheme::getLexicon('site_schedule')}}
                                            </a>
                                        </li>
                                    @endif
                                    @if (evo()->hasPermission('view_eventlog'))
                                        <li>
                                            <a href="index.php?a=114" target="main">
                                                <i class="{{$_style['icon_info_triangle']}}"></i>{{ManagerTheme::getLexicon('eventlog_viewer')}}
                                            </a>
                                        </li>
                                    @endif
                                    @if (evo()->hasPermission('logs'))
                                        <li>
                                            <a href="index.php?a=13" target="main">
                                                <i class="{{$_style['icon_user_secret']}}"></i>{{ManagerTheme::getLexicon('view_logging')}}
                                            </a>
                                        </li>
                                        <li>
                                            <a href="index.php?a=53" target="main">
                                                <i class="{{$_style['icon_info_circle']}}"></i>{{ManagerTheme::getLexicon('view_sysinfo')}}
                                            </a>
                                        </li>
                                    @endif
                                    @if (evo()->hasPermission('help'))
                                        <li>
                                            <a href="index.php?a=9" target="main">
                                                <i class="{{$_style['icon_question_circle']}}"></i>{{ManagerTheme::getLexicon('help')}}
                                            </a>
                                        </li>
                                    @endif
                                    @php
                                        $style = evo()->getConfig('settings_version') !== evo()->getVersionData('version') ? 'style="color:#ffff8a;"' : '';
                                        echo '<li><span class="dropdown-item" title="' . evo()->getPhpCompat()->entities(evo()->getConfig('site_name')) . ' &ndash; ' . evo()->getVersionData('full_appname') . '" ' . $style . '>' . evo()->getVersionData('branch') . ' ' . evo()->getConfig('settings_version') . '</span></li>';
                                    @endphp
                                </ul>
                            </li>
                        @endif
                        @if (evo()->getConfig('show_fullscreen_btn'))
                            <li id="fullscreen">
                                <a href="javascript:;" onclick="toggleFullScreen();" id="toggleFullScreen" title="{{ManagerTheme::getLexicon('toggle_fullscreen')}}">
                                    <i class="{{$_style['icon_expand']}}"></i>
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div id="tree">@include('manager::frame.tree')</div>
    <div id="main">
        @if (evo()->getConfig('global_tabs'))
            <div class="tab-row-container evo-tab-row">
                <div class="tab-row">
                    <h2 id="evo-tab-home" class="tab selected" data-target="evo-tab-page-home">
                        <i class="{{$_style['icon_home']}}"></i></h2>
                </div>
            </div>
            <div id="evo-tab-page-home" class="evo-tab-page show iframe-scroller">
                <iframe id="mainframe" src="index.php?a={{$initMainframeAction}}" scrolling="auto" frameborder="0" onload="modx.main.onload(event);"></iframe>
            </div>
        @else
            <div class="iframe-scroller">
                <iframe id="mainframe" name="main" src="index.php?a={{$initMainframeAction}}" scrolling="auto" frameborder="0" onload="modx.main.onload(event);"></iframe>
            </div>
        @endif
        <script>
            if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
                document.getElementById('mainframe').setAttribute('scrolling', 'no');
                document.getElementsByClassName("tabframes").setAttribute("scrolling", "no");
            }
        </script>
        <div id="mainloader"><div class="evo__logo"></div></div>
    </div>
    <div id="resizer"></div>
    <div id="searchresult"></div>
    <div id="floater" class="dropdown">
        @php
            $sortParams = ['tree_sortby', 'tree_sortdir', 'tree_nodename'];
            foreach ($sortParams as $param) {
                if (isset($_REQUEST[$param])) {
                    evo()->getManagerApi()->saveLastUserSetting($param, $_REQUEST[$param]);
                    $_SESSION[$param] = $_REQUEST[$param];
                } elseif (!isset($_SESSION[$param])) {
                    $_SESSION[$param] = evo()->getManagerApi()->getLastUserSetting($param);
                }
            }
        @endphp
        <form name="sortFrm" id="sortFrm">
            <div class="form-group">
                <input type="hidden" name="dt" value="{{isset($_REQUEST['dt']) ? htmlspecialchars($_REQUEST['dt']) : ''}}" />
                <label>{{ManagerTheme::getLexicon('sort_tree')}}</label>
                <select name="sortby" class="form-control">
                    <option value="isfolder" {{$_SESSION['tree_sortby'] == 'isfolder' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('folder')}}</option>
                    <option value="pagetitle" {{$_SESSION['tree_sortby'] == 'pagetitle' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('pagetitle')}}</option>
                    <option value="longtitle" {{$_SESSION['tree_sortby'] == 'longtitle' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('long_title')}}</option>
                    <option value="id" {{$_SESSION['tree_sortby'] == 'id' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('id')}}</option>
                    <option value="menuindex" {{$_SESSION['tree_sortby'] == 'menuindex' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('resource_opt_menu_index')}}</option>
                    <option value="createdon" {{$_SESSION['tree_sortby'] == 'createdon' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('createdon')}}</option>
                    <option value="editedon" {{$_SESSION['tree_sortby'] == 'editedon' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('editedon')}}</option>
                    <option value="publishedon" {{$_SESSION['tree_sortby'] == 'publishedon' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('page_data_publishdate')}}</option>
                    <option value="alias" {{$_SESSION['tree_sortby'] == 'alias' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('page_data_alias')}}</option>
                </select>
            </div>
            <div class="form-group">
                <select name="sortdir" class="form-control">
                    <option value="DESC" {{$_SESSION['tree_sortdir'] == 'DESC' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('sort_desc')}}</option>
                    <option value="ASC" {{$_SESSION['tree_sortdir'] == 'ASC' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('sort_asc')}}</option>
                </select>
            </div>
            <div class="form-group">
                <label>{{ManagerTheme::getLexicon('setting_resource_tree_node_name')}}</label>
                <select name="nodename" class="form-control">
                    <option value="default" {{$_SESSION['tree_nodename'] == 'default' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('default')}}</option>
                    <option value="pagetitle" {{$_SESSION['tree_nodename'] == 'pagetitle' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('pagetitle')}}</option>
                    <option value="longtitle" {{$_SESSION['tree_nodename'] == 'longtitle' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('long_title')}}</option>
                    <option value="menutitle" {{$_SESSION['tree_nodename'] == 'menutitle' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('resource_opt_menu_title')}}</option>
                    <option value="alias" {{$_SESSION['tree_nodename'] == 'alias' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('alias')}}</option>
                    <option value="createdon" {{$_SESSION['tree_nodename'] == 'createdon' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('createdon')}}</option>
                    <option value="editedon" {{$_SESSION['tree_nodename'] == 'editedon' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('editedon')}}</option>
                    <option value="publishedon" {{$_SESSION['tree_nodename'] == 'publishedon' ? 'selected' : ''}}>{{ManagerTheme::getLexicon('page_data_publishdate')}}</option>
                </select>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="showonlyfolders" value="{{$_SESSION['tree_show_only_folders'] ? 1 : ''}}" onclick="this.value = (this.value ? '' : 1);" {{$_SESSION['tree_show_only_folders'] ? '' : 'checked'}} />{{ManagerTheme::getLexicon('view_child_resources_in_container')}}
                </label>
            </div>
            <div class="text-center">
                <a href="javascript:;" class="btn btn-primary" onclick="modx.tree.updateTree();modx.tree.showSorter(event);" title="{{ManagerTheme::getLexicon('sort_tree')}}">{{ManagerTheme::getLexicon('sort_tree')}}</a>
            </div>
        </form>
    </div>
    <?php
    if (!function_exists('constructLink')) {
        /**
         * @param string $action
         * @param string $img
         * @param string $text
         * @param bool $allowed
         */
        function constructLink($action, $img, $text, $allowed)
        {
            if ((bool) $allowed) {
                echo '<div class="menuLink" id="item' . $action . '" onclick="modx.tree.menuHandler(' . $action . ');">';
                echo '<i class="' . $img . '"></i> ' . $text . '</div>';
            }
        }
    }
    ?><!-- Contextual Menu Popup Code -->
    <div id="mx_contextmenu" class="dropdown" onselectstart="return false;">
        <div id="nameHolder">&nbsp;</div>
        <?php
        constructLink(3, $_style['icon_document'], ManagerTheme::getLexicon('create_resource_here'), evo()->hasPermission('new_document')); // new Resource
        constructLink(2, $_style['icon_edit'], ManagerTheme::getLexicon('edit_resource'), evo()->hasPermission('edit_document')); // edit
        constructLink(5, $_style['icon_move'], ManagerTheme::getLexicon('move_resource'), evo()->hasPermission('save_document')); // move
        constructLink(7, $_style['icon_clone'], ManagerTheme::getLexicon('resource_duplicate'), evo()->hasPermission('new_document')); // duplicate
        constructLink(11, $_style['icon_sort_num_asc'], ManagerTheme::getLexicon('sort_menuindex'), !!(evo()->hasPermission('edit_document') && $modx->hasPermission('save_document'))); // sort menu index
        ?>
        <div class="seperator"></div>
        <?php
        constructLink(9, $_style['icon_check'], ManagerTheme::getLexicon('publish_resource'), evo()->hasPermission('publish_document')); // publish
        constructLink(10, $_style['icon_close'], ManagerTheme::getLexicon('unpublish_resource'), evo()->hasPermission('publish_document')); // unpublish
        constructLink(4, $_style['icon_trash'], ManagerTheme::getLexicon('delete_resource'), evo()->hasPermission('delete_document')); // delete
        constructLink(8, $_style['icon_undo'], ManagerTheme::getLexicon('undelete_resource'), evo()->hasPermission('delete_document')); // undelete
        ?>
        <div class="seperator"></div>
        <?php
        constructLink(6, $_style['icon_chain'], ManagerTheme::getLexicon('create_weblink_here'), evo()->hasPermission('new_document')); // new Weblink
        ?>
        <div class="seperator"></div>
        <?php
        constructLink(1, $_style['icon_info'], ManagerTheme::getLexicon('resource_overview'), evo()->hasPermission('view_document')); // view
        constructLink(12, $_style['icon_eye'], ManagerTheme::getLexicon('preview_resource'), 1); // preview
        ?>

    </div>

    <script type="text/javascript">
        if (document.getElementById('treeMenu')) {
            @if (evo()->getConfig('use_browser') && evo()->hasPermission('assets_images'))

            var treeMenuOpenImages = document.getElementById('treeMenu_openimages');
            if (treeMenuOpenImages) {
                treeMenuOpenImages.onclick = function(e) {
                    e.preventDefault();
                    if (modx.config.global_tabs && !e.shiftKey) {
                        modx.tabs({
                            url: '{{ MODX_MANAGER_URL }}media/browser/{{ evo()->getConfig('which_browser') }}/browse.php?filemanager=media/browser/{{ $modx->getConfig('which_browser') }}/browse.php&type=images',
                            title: '{{ ManagerTheme::getLexicon('images_management') }}'
                        });
                    } else {
                        var randomNum = '{{ ManagerTheme::getLexicon('files_files') }}';
                        if (e.shiftKey) {
                            randomNum += ' #' + Math.floor((Math.random() * 999999) + 1);
                        }
                        modx.openWindow({
                            url: '{{ MODX_MANAGER_URL }}media/browser/{{ evo()->getConfig('which_browser') }}/browse.php?&type=images',
                            title: randomNum
                        });
                    }
                };
            }
            @endif
            @if (evo()->getConfig('use_browser') && evo()->hasPermission('assets_files'))

            var treeMenuOpenFiles = document.getElementById('treeMenu_openfiles');
            if (treeMenuOpenFiles) {
                treeMenuOpenFiles.onclick = function(e) {
                    e.preventDefault();
                    if (modx.config.global_tabs && !e.shiftKey) {
                        modx.tabs({
                            url: '{{ MODX_MANAGER_URL }}media/browser/{{ evo()->getConfig('which_browser') }}/browse.php?filemanager=media/browser/{{ $modx->getConfig('which_browser') }}/browse.php&type=files',
                            title: '{{ ManagerTheme::getLexicon('files_files') }}'
                        });
                    } else {
                        var randomNum = '{{ ManagerTheme::getLexicon('files_files') }}';
                        if (e.shiftKey) {
                            randomNum += ' #' + Math.floor((Math.random() * 999999) + 1);
                        }
                        modx.openWindow({
                            url: '{{ MODX_MANAGER_URL }}media/browser/{{ evo()->getConfig('which_browser') }}/browse.php?&type=files',
                            title: randomNum
                        });
                    }
                };
            }
            @endif

        }
    </script>
    @if (evo()->getConfig('show_fullscreen_btn'))
        <script>
            function toggleFullScreen() {
                if ((document.fullScreenElement && document.fullScreenElement !== null) ||
                    (!document.mozFullScreen && !document.webkitIsFullScreen)) {
                    if (document.documentElement.requestFullScreen) {
                        document.documentElement.requestFullScreen();
                    } else if (document.documentElement.mozRequestFullScreen) {
                        document.documentElement.mozRequestFullScreen();
                    } else if (document.documentElement.webkitRequestFullScreen) {
                        document.documentElement.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);
                    }
                } else {
                    if (document.cancelFullScreen) {
                        document.cancelFullScreen();
                    } else if (document.mozCancelFullScreen) {
                        document.mozCancelFullScreen();
                    } else if (document.webkitCancelFullScreen) {
                        document.webkitCancelFullScreen();
                    }
                }
            }

            $('#toggleFullScreen').click(function() {
                var icon = $(this).find('i');
                icon.toggleClass('{{ $_style['icon_expand'] }} {{ $_style['icon_compress'] }}');
            });
        </script>
    @endif
    {!! evo()->invokeEvent('OnManagerFrameLoader', ['action' => ManagerTheme::getActionId()]) !!}
</div>
@if (evo()->getConfig('show_picker'))
    <div class="evocp-box">
        <div class="evocp-icon"><i class="evocpicon {{ $_style['icon_brush'] }}" aria-hidden="true"></i></div>
        <div class="evocp-frame">
            <h2>COLOR SWITCHER</h2>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 data-toggle="collapse" data-target=".bgmcolors"><i
                                class="togglearrow {{ $_style['icon_chevron_down'] }}" aria-hidden="true"></i> <i
                                class="{{ $_style['icon_bars'] }}" aria-hidden="true"></i> Menu Background</h3><a
                            title="{{ ManagerTheme::getLexicon('reset') }}" href="javascript:;"
                            onclick="cleanLocalStorageReloadAll('my_evo_bgmcolor')"
                            class="pull-right resetcolor btn btn-secondary"><i
                                class="{{ $_style['icon_refresh'] }}"></i></a>
                </div>
                <div class="panel-body collapse in bgmcolors">
                    <div class="evocp-bgmcolors">
                        <div class="evocp-bgmcolor">#000</div>
                        <div class="evocp-bgmcolor">#222</div>
                        <div class="evocp-bgmcolor">#333</div>
                        <div class="evocp-bgmcolor">#444</div>
                        <div class="evocp-bgmcolor">#555</div>
                        <div class="evocp-bgmcolor">#777</div>
                        <div class="evocp-bgmcolor">#888</div>
                        <div class="evocp-bgmcolor">#0f243e</div>
                        <div class="evocp-bgmcolor">#548dd4</div>
                        <div class="evocp-bgmcolor">#134f5c</div>
                        <div class="evocp-bgmcolor">#0b5394</div>
                        <div class="evocp-bgmcolor">#351c75</div>
                        <div class="evocp-bgmcolor">#741b47</div>
                        <div class="evocp-bgmcolor">#900</div>
                    </div>
                    <input type="color" class="color" id="bgmPicker" name="evocpCustombgmColor"
                           value="#cf2626" placeholder="color code...">
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 span data-toggle="collapse" data-target=".menuColors"><i
                                class="togglearrow {{ $_style['icon_chevron_right'] }}" aria-hidden="true"></i> <i
                                class="{{ $_style['icon_bars'] }}" aria-hidden="true"></i> Menu links</h3> <a
                            title="{{ ManagerTheme::getLexicon('reset') }}" href="javascript:;"
                            onclick="cleanLocalStorageReloadMain('my_evo_menuColor')"
                            class="pull-right resetcolor btn btn-secondary"><i
                                class="{{ $_style['icon_refresh'] }}"></i></a>
                </div>
                <div class="panel-body collapse menuColors">
                    <div class="evocp-menuColors">
                        <div class="evocp-menuColor">#000</div>
                        <div class="evocp-menuColor">#222</div>
                        <div class="evocp-menuColor">#555</div>
                        <div class="evocp-menuColor">#666</div>
                        <div class="evocp-menuColor evocp_light">#dedede</div>
                        <div class="evocp-menuColor evocp_light">#fafafa</div>
                        <div class="evocp-menuColor evocp_light">#fff</div>
                        <div class="evocp-menuColor">#b45f06</div>
                        <div class="evocp-menuColor">#38761d</div>
                        <div class="evocp-menuColor">#134f5c</div>
                        <div class="evocp-menuColor">#0b5394</div>
                        <div class="evocp-menuColor">#351c75</div>
                        <div class="evocp-menuColor">#741b47</div>
                        <div class="evocp-menuColor">#9d2661</div>
                    </div>
                    <input class="color" type="color" id="menucolorPicker" name="evocpCustommenuColor"
                           value="#cf2626" placeholder="color code...">
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 data-toggle="collapse" data-target=".menuHColors"><i
                                class="togglearrow {{ $_style['icon_chevron_right'] }}" aria-hidden="true"></i> <i
                                class="{{ $_style['icon_bars'] }}" aria-hidden="true"></i> Menu links:hover </h3><a
                            title="{{ ManagerTheme::getLexicon('reset') }}" href="javascript:;"
                            onclick="cleanLocalStorageReloadMain('my_evo_menuHColor')"
                            class="pull-right resetcolor btn btn-secondary"><i
                                class="{{ $_style['icon_refresh'] }}"></i></a>
                </div>
                <div class="panel-body collapse menuHColors">
                    <div class="evocp-menuHColors">
                        <div class="evocp-menuHColor">#000</div>
                        <div class="evocp-menuHColor">#222</div>
                        <div class="evocp-menuHColor">#555</div>
                        <div class="evocp-menuHColor">#666</div>
                        <div class="evocp-menuHColor evocp_light">#dedede</div>
                        <div class="evocp-menuHColor evocp_light">#fafafa</div>
                        <div class="evocp-menuHColor evocp_light">#fff</div>
                        <div class="evocp-menuHColor">#b45f06</div>
                        <div class="evocp-menuHColor">#38761d</div>
                        <div class="evocp-menuHColor">#134f5c</div>
                        <div class="evocp-menuHColor">#0b5394</div>
                        <div class="evocp-menuHColor">#351c75</div>
                        <div class="evocp-menuHColor">#741b47</div>
                        <div class="evocp-menuHColor">#9d2661</div>
                    </div>
                    <input class="color" type="color" id="menuHcolorPicker" name="evocpCustommenuHColor"
                           value="#cf2626" placeholder="color code...">
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 data-toggle="collapse" data-target=".cpcolors"><i
                                class="togglearrow {{ $_style['icon_chevron_right'] }}" aria-hidden="true"></i> <i
                                class="{{ $_style['icon_font'] }}" aria-hidden="true"></i> Text color </h3><a
                            title="{{ ManagerTheme::getLexicon('reset') }}" href="javascript:;"
                            onclick="cleanLocalStorageReloadMain('my_evo_color')"
                            class="pull-right resetcolor btn btn-secondary"><i
                                class="{{ $_style['icon_refresh'] }}"></i></a>
                </div>
                <div class="panel-body collapse cpcolors">
                    <div class="evocp-colors">
                        <div class="evocp-color">#000</div>
                        <div class="evocp-color">#222</div>
                        <div class="evocp-color">#333</div>
                        <div class="evocp-color">#444</div>
                        <div class="evocp-color">#555</div>
                        <div class="evocp-color">#777</div>
                        <div class="evocp-color">#888</div>
                        <div class="evocp-color">#b45f06</div>
                        <div class="evocp-color">#38761d</div>
                        <div class="evocp-color">#134f5c</div>
                        <div class="evocp-color">#0b5394</div>
                        <div class="evocp-color">#351c75</div>
                        <div class="evocp-color">#741b47</div>
                        <div class="evocp-color">#9d2661</div>
                    </div>
                    <input class="color" type="color" id="textcolorPicker" name="textcolorPicker"
                           value="#cf2626" placeholder="color code...">
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 data-toggle="collapse" data-target=".alinkcolors"><i
                                class="togglearrow {{ $_style['icon_chevron_right'] }}" aria-hidden="true"></i> <i
                                class="{{ $_style['icon_chain'] }}" aria-hidden="true"></i> Links Color</h3> <a
                            title="{{ ManagerTheme::getLexicon('reset') }}" href="javascript:;"
                            onclick="cleanLocalStorageReloadMain('my_evo_alinkcolor')"
                            class="pull-right resetcolor btn btn-secondary"><i
                                class="{{ $_style['icon_refresh'] }}"></i></a>
                </div>
                <div class="panel-body collapse alinkcolors">
                    <div class="evocp-alinkcolors">
                        <div class="evocp-alinkcolor">#000</div>
                        <div class="evocp-alinkcolor">#222</div>
                        <div class="evocp-alinkcolor">#555</div>
                        <div class="evocp-alinkcolor">#666</div>
                        <div class="evocp-alinkcolor">#dedede</div>
                        <div class="evocp-alinkcolor">#fafafa</div>
                        <div class="evocp-alinkcolor">#fff</div>
                        <div class="evocp-alinkcolor">#b45f06</div>
                        <div class="evocp-alinkcolor">#38761d</div>
                        <div class="evocp-alinkcolor">#134f5c</div>
                        <div class="evocp-alinkcolor">#0b5394</div>
                        <div class="evocp-alinkcolor">#351c75</div>
                        <div class="evocp-alinkcolor">#741b47</div>
                        <div class="evocp-alinkcolor">#9d2661</div>
                    </div>
                    <input class="color" type="color" id="linkcolorPicker" name="alinkcolorPicker"
                           value="#cf2626" placeholder="color code...">
                </div>
            </div>
            <hr />
            <input type="reset"
                   onclick="cleanLocalStorageReloadAll('my_evo_alinkcolor,my_evo_menuColor,my_evo_menuHColor,my_evo_bgmcolor,my_evo_color')"
                   class="btn btn-secondary" value="{{ ManagerTheme::getLexicon('reset') }}">
        </div>
    </div>
    <script>
        $("#bgmPicker").spectrum({
            showButtons: false,
            preferredFormat: "hex3",
            containerClassName: 'bgmPicker',
            showInput: true,
            allowEmpty: true
        });
        $("#menucolorPicker").spectrum({
            showButtons: false,
            preferredFormat: "hex3",
            containerClassName: 'menucolorPicker',
            replacerClassName: 'evo-cp-replacer',
            showInput: true,
            allowEmpty: true
        });
        $("#menuHcolorPicker").spectrum({
            showButtons: false,
            preferredFormat: "hex3",
            containerClassName: 'menuHcolorPicker',
            replacerClassName: 'evo-cp-replacer',
            showInput: true,
            allowEmpty: true
        });
        $("#textcolorPicker").spectrum({
            showButtons: false,
            preferredFormat: "hex3",
            containerClassName: 'textcolorPicker',
            replacerClassName: 'evo-cp-replacer',
            showInput: true,
            allowEmpty: true
        });
        $("#linkcolorPicker").spectrum({
            showButtons: false,
            preferredFormat: "hex3",
            containerClassName: 'linkcolorPicker',
            replacerClassName: 'evo-cp-replacer',
            showInput: true,
            allowEmpty: true
        });
    </script>
@endif
</body>
</html>