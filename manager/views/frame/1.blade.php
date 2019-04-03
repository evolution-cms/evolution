<!DOCTYPE html>
<html dir="{{ ManagerTheme::getTextDir() }}" lang="{{ ManagerTheme::getLang() }}" xml:lang="{{ ManagerTheme::getLang() }}">
<head>
    <title>{{ $modx->getConfig('site_name') }} - (EVO CMS Manager)</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= ManagerTheme::getCharset()?>" />
    <meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width" />
    <meta name="theme-color" content="#1d2023" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="stylesheet" type="text/css" href="{{ $css }}" />
    @if($modx->getConfig('show_picker'))
        <link rel="stylesheet" href="media/style/common/spectrum/spectrum.css" />
        <link rel="stylesheet" type="text/css" href="{{ ManagerTheme::getThemeUrl() }}css/color.switcher.css" />
    @endif
    <link rel="icon" type="image/ico" href="{{ ManagerTheme::getStyle('favicon') }}" />
    <style>
        #tree { width: {{ $MODX_widthSideBar }}rem }
        #main, #resizer { left: {{ $MODX_widthSideBar }}rem }
        .ios #main { -webkit-overflow-scrolling: touch; overflow-y: scroll; }
    </style>
    <script type="text/javascript">
        if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
            document.documentElement.className += ' ios';
        }
    </script>
    <script src="media/script/jquery/jquery.min.js" type="text/javascript"></script>
    <script type="text/javascript">
        // GLOBAL variable modx
        var modx = {
            MGR_DIR: '{{ MGR_DIR }}',
            MODX_SITE_URL: '{{ MODX_SITE_URL }}',
            MODX_MANAGER_URL: '{{ MODX_MANAGER_URL }}',
            user: {
                role: {{ (int)$user['role'] }},
                username: '{{ $user['username'] }}'
            },
            config: {
                mail_check_timeperiod: {{ $modx->getConfig('mail_check_timeperiod') }},
                menu_height: {{ (int)$modx->getConfig('manager_menu_height') }},
                tree_width: {{ (int)$MODX_widthSideBar }},
                tree_min_width: <?= (int)$tree_min_width ?>,
                session_timeout: <?= (int)$modx->getConfig('session_timeout') ?>,
                site_start: <?= (int)$modx->getConfig('site_start') ?>,
                tree_page_click: {{ $modx->getConfig('tree_page_click') }},
                theme: '{{ ManagerTheme::getTheme() }}',
                theme_mode: '{{ ManagerTheme::getThemeStyle() }}',
                which_browser: '<?= $user['which_browser'] ?>',
                layout: <?= (int)$modx->getConfig('manager_layout') ?>,
                textdir: '<?= ManagerTheme::getTextDir() ?>',
                global_tabs: <?= (int)$modx->getConfig('global_tabs') ?>

            },
            lang: {
                already_deleted: "<?= $_lang['already_deleted'] ?>",
                cm_unknown_error: "<?= $_lang['cm_unknown_error'] ?>",
                collapse_tree: "<?= $_lang['collapse_tree'] ?>",
                confirm_delete_resource: "<?= $_lang['confirm_delete_resource'] ?>",
                confirm_empty_trash: "<?= $_lang['confirm_empty_trash'] ?>",
                confirm_publish: "<?= $_lang['confirm_publish'] ?>",
                confirm_remove_locks: "<?= $_lang['confirm_remove_locks'] ?>",
                confirm_resource_duplicate: "<?= $_lang['confirm_resource_duplicate'] ?>",
                confirm_undelete: "<?= $_lang['confirm_undelete'] ?>",
                confirm_unpublish: "<?= $_lang['confirm_unpublish'] ?>",
                empty_recycle_bin: "<?= $_lang['empty_recycle_bin'] ?>",
                empty_recycle_bin_empty: "<?= $_lang['empty_recycle_bin_empty'] ?>",
                error_no_privileges: "<?= $_lang["error_no_privileges"] ?>",
                expand_tree: "<?= $_lang['expand_tree'] ?>",
                inbox: "<?= $_lang['inbox'] ?>",
                loading_doc_tree: "<?= $_lang['loading_doc_tree'] ?>",
                loading_menu: "<?= $_lang['loading_menu'] ?>",
                not_deleted: "<?= $_lang['not_deleted'] ?>",
                unable_set_link: "<?= $_lang['unable_set_link'] ?>",
                unable_set_parent: "<?= $_lang['unable_set_parent'] ?>",
                working: "<?= $_lang['working'] ?>",
                paging_prev: "<?= $_lang["paging_prev"] ?>"
            },
            style: {
                actions_file: '<?= addslashes($_style['actions_file']) ?>',
                actions_pencil: '<?= addslashes($_style['actions_pencil']) ?>',
                actions_plus: '<?= addslashes($_style['actions_plus']) ?>',
                actions_reply: '<?= addslashes($_style['actions_reply']) ?>',
                collapse_tree: '<?= addslashes('<i class="fa fa-arrow-circle-up"></i>') ?>',
                email: '<?= addslashes('<i class="fa fa-envelope"></i>') ?>',
                empty_recycle_bin: '<?= addslashes('<i class="fa fa-trash"></i>') ?>',
                empty_recycle_bin_empty: '<?= addslashes('<i class="fa fa-trash-o"></i>') ?>',
                expand_tree: '<?= addslashes('<i class="fa fa-arrow-circle-down"></i>') ?>',
                icons_external_link: '<?= addslashes('<i class="fa fa-external-link"></i>') ?>',
                icons_working: '<?= addslashes($_style['tree_working']) ?>',
                tree_info: '<?= addslashes($_style['tree_info']) ?>',
                tree_folder: '<?= addslashes($_style['tree_folder_new']) ?>',
                tree_folder_secure: '<?= addslashes($_style['tree_folder_secure']) ?>',
                tree_folderopen: '<?= addslashes($_style['tree_folderopen_new']) ?>',
                tree_folderopen_secure: '<?= addslashes($_style['tree_folderopen_secure']) ?>',
                tree_minusnode: '<?= addslashes($_style["tree_minusnode"]) ?>',
                tree_plusnode: '<?= addslashes($_style['tree_plusnode']) ?>',
                tree_preview_resource: '<?= addslashes($_style['tree_preview_resource']) ?>'
            },
            permission: {
                assets_images: <?= $modx->hasPermission('assets_images') ? 1 : 0 ?>,
                delete_document: <?= $modx->hasPermission('delete_document') ? 1 : 0 ?>,
                edit_chunk: <?= $modx->hasPermission('edit_chunk') ? 1 : 0 ?>,
                edit_plugin: <?= $modx->hasPermission('edit_plugin') ? 1 : 0 ?>,
                edit_snippet: <?= $modx->hasPermission('edit_snippet') ? 1 : 0 ?>,
                edit_template: <?= $modx->hasPermission('edit_template') ? 1 : 0 ?>,
                messages: <?= $modx->hasPermission('messages') ? 1 : 0 ?>,
                new_document: <?= $modx->hasPermission('new_document') ? 1 : 0 ?>,
                publish_document: <?= $modx->hasPermission('publish_document') ? 1 : 0 ?>,
                dragndropdocintree: <?= ($modx->hasPermission('new_document') && $modx->hasPermission('edit_document') && $modx->hasPermission('save_document') ? 1 : 0) ?>

            },
            plugins: {
                ElementsInTree: <?= isset($modx->pluginCache['ElementsInTree']) ? 1 : 0 ?>,
                EVOmodal: <?= isset($modx->pluginCache['EVO.modal']) ? 1 : 0 ?>

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
        $opened = array_filter(
            array_map(
                'intval',
                explode(
                    '|',
                    isset($_SESSION['openedArray']) && is_scalar($_SESSION['openedArray']) ? $_SESSION['openedArray'] : ''
                )
            )
        );
        echo (empty($opened) ? '' : 'modx.openedArray[' . implode("] = 1;\n		modx.openedArray[", $opened) . '] = 1;') . "\n";
        ?>
    </script>
    <script src="{{ ManagerTheme::getThemeUrl() }}js/modx.min.js?v=<?= EVO_INSTALL_TIME ?>"></script>
    <?php if ($modx->getConfig('show_picker')) { ?>
    <script src="media/script/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="media/script/spectrum/spectrum.evo.min.js" type="text/javascript"></script>
    <script src="{{ ManagerTheme::getThemeUrl() }}js/color.switcher.js" type="text/javascript"></script>
    <?php } ?>
    <?php
    // invoke OnManagerTopPrerender event
    $evtOut = $modx->invokeEvent('OnManagerTopPrerender', $_REQUEST);
    if (is_array($evtOut)) {
        echo implode("\n", $evtOut);
    }
    ?>
</head>
<body class="<?= $body_class ?>">
<input type="hidden" name="sessToken" id="sessTokenInput" value="<?= isset($_SESSION['mgrToken']) ? $_SESSION['mgrToken'] : '' ?>" />
<div id="frameset">
    <div id="mainMenu" class="dropdown">
        <div class="container">
            <div class="row">
                <div class="cell" data-evocp="bgmColor">
                {!! $menu !!}
                </div>
                <div class="cell" data-evocp="bgmColor">
                    <ul id="settings" class="nav">
                        <li id="searchform">
                            <form action="index.php?a=71" method="post" target="main">
                                <input type="hidden" value="Search" name="submitok" />
                                <label for="searchid" class="label_searchid">
                                    <i class="fa fa-search"></i>
                                </label>
                                <input type="text" id="searchid" name="searchid" size="25" />
                                <div class="mask"></div>
                            </form>
                        </li>
                        @if ($modx->getConfig('show_newresource_btn') && $modx->hasPermission('new_document'))
                            <li id="newresource" class="dropdown newresource">
                                <a href="javascript:;" class="dropdown-toggle" onclick="return false;" title="<?= $_lang['add_resource'] ?>"><i class="fa fa-plus"></i></a>
                                <ul class="dropdown-menu">
                                    <?php if ($modx->hasPermission('new_document')) { ?>
                                    <li>
                                        <a onclick="" href="index.php?a=4" target="main">
                                            <i class="fa fa-file"></i><?= $_lang['add_resource'] ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a onclick="" href="index.php?a=72" target="main">
                                            <i class="fa fa-link"></i><?= $_lang['add_weblink'] ?>
                                        </a>
                                    </li>
                                    <?php } ?>
                                    @if ($modx->getConfig('use_browser') && $modx->hasPermission('assets_images'))
                                        <li>
                                            <a onclick="" href="media/browser/{{ $modx->getConfig('which_browser') }}/browse.php?&type=images" target="main">
                                                <i class="fa fa-camera"></i><?= $_lang['images_management'] ?>
                                            </a>
                                        </li>
                                    @endif
                                    @if($modx->getConfig('use_browser') && $modx->hasPermission('assets_files'))
                                        <li>
                                            <a onclick="" href="media/browser/{{ $modx->getConfig('which_browser') }}/browse.php?&type=files" target="main">
                                                <i class="fa fa-files-o"></i><?= $_lang['files_management'] ?>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        <li id="preview">
                            <a href="../" target="_blank" title="<?= $_lang['preview'] ?>">
                                <i class="fa fa-desktop"></i>
                            </a>
                        </li>
                        <li id="account" class="dropdown account">
                            <a href="javascript:;" class="dropdown-toggle" onclick="return false;">
                                <span class="username"><?= entities($user['username'], $modx->getConfig('modx_charset')) ?></span>
                                <?php if ($user['photo']) { ?>
                                <span class="icon photo" style="background-image: url(<?= MODX_SITE_URL . entities($user['photo'], $modx->getConfig('modx_charset')) ?>);"></span>
                                <?php } else { ?>
                                <span class="icon"><i class="fa fa-user-circle"></i></span>
                                <?php } ?>
                                <i id="msgCounter"></i>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if ($modx->hasPermission('messages')): ?>
                                <li id="newMail"></li>
                                <?php endif; ?>
                                <?php if ($modx->hasPermission('change_password')) { ?>
                                <li>
                                    <a onclick="" href="index.php?a=28" target="main">
                                        <i class="fa fa-lock"></i><?= $_lang['change_password'] ?>
                                    </a>
                                </li>
                                <?php } ?>
                                <li>
                                    <a href="index.php?a=8">
                                        <i class="fa fa-sign-out"></i><?= $_lang['logout'] ?>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <?php if ($modx->hasPermission('settings') || $modx->hasPermission('view_eventlog') || $modx->hasPermission('logs') || $modx->hasPermission('help')) { ?>
                        <li id="system" class="dropdown">
                            <a href="javascript:;" class="dropdown-toggle" title="<?= $_lang['system'] ?>" onclick="return false;"><i class="fa fa-cogs"></i></a>
                            <ul class="dropdown-menu">
                                <?php if ($modx->hasPermission('settings')) { ?>
                                <li>
                                    <a href="index.php?a=17" target="main">
                                        <i class="fa fa-sliders fw"></i><?= $_lang['edit_settings'] ?>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if ($modx->hasPermission('view_eventlog')) { ?>
                                <li>
                                    <a href="index.php?a=70" target="main">
                                        <i class="fa fa-calendar"></i><?= $_lang['site_schedule'] ?>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if ($modx->hasPermission('view_eventlog')) { ?>
                                <li>
                                    <a href="index.php?a=114" target="main">
                                        <i class="fa fa-exclamation-triangle"></i><?= $_lang['eventlog_viewer'] ?>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if ($modx->hasPermission('logs')) { ?>
                                <li>
                                    <a href="index.php?a=13" target="main">
                                        <i class="fa fa-user-secret"></i><?= $_lang['view_logging'] ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="index.php?a=53" target="main">
                                        <i class="fa fa-info-circle"></i><?= $_lang['view_sysinfo'] ?>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if ($modx->hasPermission('help')) { ?>
                                <li>
                                    <a href="index.php?a=9" target="main">
                                        <i class="fa fa-question-circle"></i><?= $_lang['help'] ?>
                                    </a>
                                </li>
                                <?php } ?>

                                <?php
                                $style = $modx->getConfig('settings_version') !== $modx->getVersionData('version') ? 'style="color:#ffff8a;"' : '';
                                $version = 'Evolution';
                                ?>
                                <?php
                                    echo sprintf('<li><span class="dropdown-item" title="%s &ndash; %s" %s>' . $version . ' %s</span></li>', $modx->getPhpCompat()->entities($modx->getConfig('site_name')), $modx->getVersionData('full_appname'), $style, $modx->getConfig('settings_version'));
                                ?>
                            </ul>
                        </li>
                        <?php } ?>
                        @if($modx->getConfig('show_fullscreen_btn'))
                            <li id="fullscreen">
                                <a href="javascript:;" onclick="toggleFullScreen();" id="toggleFullScreen" title="<?= $_lang["toggle_fullscreen"] ?>">
                                    <i class="fa fa-expand"></i>
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
        @if ($modx->getConfig('global_tabs'))
            <div class="tab-row-container evo-tab-row">
                <div class="tab-row"><h2 id="evo-tab-home" class="tab selected" data-target="evo-tab-page-home"><i class="fa fa-home"></i></h2></div>
            </div>
            <div id="evo-tab-page-home" class="evo-tab-page show iframe-scroller">
                <iframe id="mainframe" src="index.php?a=<?= $initMainframeAction ?>" scrolling="auto" frameborder="0" onload="modx.main.onload(event);"></iframe>
            </div>
        @else
            <div class="iframe-scroller">
                <iframe id="mainframe" name="main" src="index.php?a=<?= $initMainframeAction ?>" scrolling="auto" frameborder="0" onload="modx.main.onload(event);"></iframe>
            </div>
        @endif
        <script>
            if (/iPhone|iPad|iPod/i.test(navigator.userAgent)) {
                document.getElementById('mainframe').setAttribute('scrolling', 'no');
                document.getElementsByClassName("tabframes").setAttribute("scrolling", "no");
            }
        </script>
        <div id="mainloader"></div>
    </div>
    <div id="resizer"></div>
    <div id="searchresult"></div>

    <div id="floater" class="dropdown">
        <?php
        $sortParams = array(
            'tree_sortby',
            'tree_sortdir',
            'tree_nodename'
        );
        foreach ($sortParams as $param) {
            if (isset($_REQUEST[$param])) {
                $modx->getManagerApi()->saveLastUserSetting($param, $_REQUEST[$param]);
                $_SESSION[$param] = $_REQUEST[$param];
            } else if (!isset($_SESSION[$param])) {
                $_SESSION[$param] = $modx->getManagerApi()->getLastUserSetting($param);
            }
        }
        ?>
        <form name="sortFrm" id="sortFrm">
            <div class="form-group">
                <input type="hidden" name="dt" value="<?= (isset($_REQUEST['dt']) ? htmlspecialchars($_REQUEST['dt'])
                    : '') ?>" />
                <label><?= $_lang["sort_tree"] ?></label>
                <select name="sortby" class="form-control">
                    <option value="isfolder" <?= $_SESSION['tree_sortby'] == 'isfolder' ? "selected='selected'" : "" ?>><?= $_lang['folder'] ?></option>
                    <option value="pagetitle" <?= $_SESSION['tree_sortby'] == 'pagetitle' ? "selected='selected'" : "" ?>><?= $_lang['pagetitle'] ?></option>
                    <option value="longtitle" <?= $_SESSION['tree_sortby'] == 'longtitle' ? "selected='selected'" : "" ?>><?= $_lang['long_title'] ?></option>
                    <option value="id" <?= $_SESSION['tree_sortby'] == 'id' ? "selected='selected'" : "" ?>><?= $_lang['id'] ?></option>
                    <option value="menuindex" <?= $_SESSION['tree_sortby'] == 'menuindex' ? "selected='selected'" : "" ?>><?= $_lang['resource_opt_menu_index'] ?></option>
                    <option value="createdon" <?= $_SESSION['tree_sortby'] == 'createdon' ? "selected='selected'" : "" ?>><?= $_lang['createdon'] ?></option>
                    <option value="editedon" <?= $_SESSION['tree_sortby'] == 'editedon' ? "selected='selected'" : "" ?>><?= $_lang['editedon'] ?></option>
                    <option value="publishedon" <?= $_SESSION['tree_sortby'] == 'publishedon' ? "selected='selected'" : "" ?>><?= $_lang['page_data_publishdate'] ?></option>
                    <option value="alias" <?= $_SESSION['tree_sortby'] == 'alias' ? "selected='selected'" : "" ?>><?= $_lang['page_data_alias'] ?></option>
                </select>
            </div>
            <div class="form-group">
                <select name="sortdir" class="form-control">
                    <option value="DESC" <?= $_SESSION['tree_sortdir'] == 'DESC' ? "selected='selected'" : "" ?>><?= $_lang['sort_desc'] ?></option>
                    <option value="ASC" <?= $_SESSION['tree_sortdir'] == 'ASC' ? "selected='selected'" : "" ?>><?= $_lang['sort_asc'] ?></option>
                </select>
            </div>
            <div class="form-group">
                <label><?= $_lang["setting_resource_tree_node_name"] ?></label>
                <select name="nodename" class="form-control">
                    <option value="default" <?= $_SESSION['tree_nodename'] == 'default' ? "selected='selected'" : "" ?>><?= trim($_lang['default'], ':') ?></option>
                    <option value="pagetitle" <?= $_SESSION['tree_nodename'] == 'pagetitle' ? "selected='selected'" : "" ?>><?= $_lang['pagetitle'] ?></option>
                    <option value="longtitle" <?= $_SESSION['tree_nodename'] == 'longtitle' ? "selected='selected'" : "" ?>><?= $_lang['long_title'] ?></option>
                    <option value="menutitle" <?= $_SESSION['tree_nodename'] == 'menutitle' ? "selected='selected'" : "" ?>><?= $_lang['resource_opt_menu_title'] ?></option>
                    <option value="alias" <?= $_SESSION['tree_nodename'] == 'alias' ? "selected='selected'" : "" ?>><?= $_lang['alias'] ?></option>
                    <option value="createdon" <?= $_SESSION['tree_nodename'] == 'createdon' ? "selected='selected'" : "" ?>><?= $_lang['createdon'] ?></option>
                    <option value="editedon" <?= $_SESSION['tree_nodename'] == 'editedon' ? "selected='selected'" : "" ?>><?= $_lang['editedon'] ?></option>
                    <option value="publishedon" <?= $_SESSION['tree_nodename'] == 'publishedon' ? "selected='selected'" : "" ?>><?= $_lang['page_data_publishdate'] ?></option>
                </select>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="showonlyfolders" value="<?= ($_SESSION['tree_show_only_folders'] ? 1 : '') ?>" onclick="this.value = (this.value ? '' : 1);" <?= ($_SESSION['tree_show_only_folders'] ? '' : ' checked="checked"') ?> /> <?= $_lang['view_child_resources_in_container'] ?></label>
            </div>
            <div class="text-center">
                <a href="javascript:;" class="btn btn-primary" onclick="modx.tree.updateTree();modx.tree.showSorter(event);" title="<?= $_lang['sort_tree'] ?>"><?= $_lang['sort_tree'] ?></a>
            </div>
        </form>
    </div>

<?php
if(!function_exists('constructLink')) {
    /**
     * @param string $action
     * @param string $img
     * @param string $text
     * @param bool $allowed
     */
    function constructLink($action, $img, $text, $allowed)
    {
        if ((bool)$allowed) {
            echo sprintf('<div class="menuLink" id="item%s" onclick="modx.tree.menuHandler(%s);">', $action,
                $action);
            echo sprintf('<i class="%s"></i> %s</div>', $img, $text);
        }
    }
}
?>

<!-- Contextual Menu Popup Code -->
    <div id="mx_contextmenu" class="dropdown" onselectstart="return false;">
        <div id="nameHolder">&nbsp;</div>
        <?php
        constructLink(3, 'fa fa-file-o', $_lang["create_resource_here"], $modx->hasPermission('new_document')); // new Resource
        constructLink(2, 'fa fa-pencil-square-o', $_lang["edit_resource"], $modx->hasPermission('edit_document')); // edit
        constructLink(5, 'fa fa-arrows', $_lang["move_resource"], $modx->hasPermission('save_document')); // move
        constructLink(7, 'fa fa-clone', $_lang["resource_duplicate"], $modx->hasPermission('new_document')); // duplicate
        constructLink(11, 'fa fa-sort-numeric-asc', $_lang["sort_menuindex"], !!($modx->hasPermission('edit_document') && $modx->hasPermission('save_document'))); // sort menu index
        ?>
        <div class="seperator"></div>
        <?php
        constructLink(9, 'fa fa-check', $_lang["publish_resource"], $modx->hasPermission('publish_document')); // publish
        constructLink(10, 'fa fa-close', $_lang["unpublish_resource"], $modx->hasPermission('publish_document')); // unpublish
        constructLink(4, 'fa fa-trash', $_lang["delete_resource"], $modx->hasPermission('delete_document')); // delete
        constructLink(8, 'fa fa-undo', $_lang["undelete_resource"], $modx->hasPermission('delete_document')); // undelete
        ?>
        <div class="seperator"></div>
        <?php
        constructLink(6, 'fa fa-link', $_lang["create_weblink_here"], $modx->hasPermission('new_document')); // new Weblink
        ?>
        <div class="seperator"></div>
        <?php
        constructLink(1, 'fa fa-info', $_lang["resource_overview"], $modx->hasPermission('view_document')); // view
        constructLink(12, 'fa fa-eye', $_lang["preview_resource"], 1); // preview
        ?>

    </div>

    <script type="text/javascript">

        if (document.getElementById('treeMenu')) {
            <?php if($modx->hasPermission('edit_template') || $modx->hasPermission('edit_snippet') || $modx->hasPermission('edit_chunk') || $modx->hasPermission('edit_plugin')) { ?>

            document.getElementById('treeMenu_openelements').onclick = function(e) {
                e.preventDefault();
                if (modx.config.global_tabs && !e.shiftKey) {
                    modx.tabs({url: '<?= MODX_MANAGER_URL ?>index.php?a=76', title: '<?= $_lang["elements"] ?>'});
                } else {
                    var randomNum = '<?= $_lang["elements"] ?>';
                    if (e.shiftKey) {
                        randomNum += ' #' + Math.floor((Math.random() * 999999) + 1);
                    }
                    modx.openWindow({
                        url: '<?= MODX_MANAGER_URL ?>index.php?a=76',
                        title: randomNum
                    });
                }
            };
            <?php } ?>
            @if($modx->getConfig('use_browser') && $modx->hasPermission('assets_images'))

            document.getElementById('treeMenu_openimages').onclick = function(e) {
                e.preventDefault();
                if (modx.config.global_tabs && !e.shiftKey) {
                    modx.tabs({url: '<?= MODX_MANAGER_URL ?>media/browser/{{ $modx->getConfig('which_browser') }}/browse.php?filemanager=media/browser/{{ $modx->getConfig('which_browser') }}/browse.php&type=images', title: '<?= $_lang["images_management"] ?>'});
                } else {
                    var randomNum = '<?= $_lang["files_files"] ?>';
                    if (e.shiftKey) {
                        randomNum += ' #' + Math.floor((Math.random() * 999999) + 1);
                    }
                    modx.openWindow({
                        url: '<?= MODX_MANAGER_URL ?>media/browser/{{ $modx->getConfig('which_browser') }}/browse.php?&type=images',
                        title: randomNum
                    });
                }
            };
            @endif
            @if($modx->getConfig('use_browser') && $modx->hasPermission('assets_files'))

            document.getElementById('treeMenu_openfiles').onclick = function(e) {
                e.preventDefault();
                if (modx.config.global_tabs && !e.shiftKey) {
                    modx.tabs({url: '<?= MODX_MANAGER_URL ?>media/browser/{{ $modx->getConfig('which_browser') }}/browse.php?filemanager=media/browser/{{ $modx->getConfig('which_browser') }}/browse.php&type=files', title: '<?= $_lang["files_files"] ?>'});
                } else {
                    var randomNum = '<?= $_lang["files_files"] ?>';
                    if (e.shiftKey) {
                        randomNum += ' #' + Math.floor((Math.random() * 999999) + 1);
                    }
                    modx.openWindow({
                        url: '<?= MODX_MANAGER_URL ?>media/browser/{{ $modx->getConfig('which_browser') }}/browse.php?&type=files',
                        title: randomNum
                    });
                }
            };
            @endif

        }

    </script>
    @if ($modx->getConfig('show_fullscreen_btn'))
        <script>
            function toggleFullScreen()
            {
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
                icon.toggleClass('fa-expand fa-compress');
            });
        </script>
    @endif
    {!! $modx->invokeEvent('OnManagerFrameLoader', ['action' => ManagerTheme::getActionId()]); !!}
</div>
@if($modx->getConfig('show_picker'))
    <div class="evocp-box">
        <div class="evocp-icon"><i class="evocpicon fa fa-paint-brush" aria-hidden="true" ></i></div>
        <div class="evocp-frame">
            <h2 >COLOR SWITCHER</h2>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 data-toggle="collapse" data-target=".bgmcolors"><i class="fa togglearrow fa-chevron-down" aria-hidden="true"></i> <i class="fa fa-bars" aria-hidden="true"></i> Menu Background</h3><a title="<?= $_lang['reset'] ?>" href="javascript:;" onclick="cleanLocalStorageReloadAll('my_evo_bgmcolor')" class="pull-right resetcolor btn btn-secondary"><i class="fa fa-refresh"></i></a>
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
                    <input type="color" class="color" id="bgmPicker" name="evocpCustombgmColor" value="#cf2626" placeholder="color code...">
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 span data-toggle="collapse" data-target=".menuColors"><i class="fa togglearrow fa-chevron-right" aria-hidden="true"></i> <i class="fa fa-bars" aria-hidden="true"></i> Menu links</h3> <a title="<?= $_lang['reset'] ?>" href="javascript:;" onclick="cleanLocalStorageReloadMain('my_evo_menuColor')" class="pull-right resetcolor btn btn-secondary"><i class="fa fa-refresh"></i></a>
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
                    <input class="color" type="color" id="menucolorPicker" name="evocpCustommenuColor" value="#cf2626" placeholder="color code...">
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 data-toggle="collapse" data-target=".menuHColors"><i class="fa togglearrow fa-chevron-right" aria-hidden="true"></i> <i class="fa fa-bars" aria-hidden="true"></i> Menu links:hover </h3><a title="<?= $_lang['reset'] ?>" href="javascript:;" onclick="cleanLocalStorageReloadMain('my_evo_menuHColor')" class="pull-right resetcolor btn btn-secondary"><i class="fa fa-refresh"></i></a>
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
                    <input class="color" type="color" id="menuHcolorPicker" name="evocpCustommenuHColor" value="#cf2626" placeholder="color code...">
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 data-toggle="collapse" data-target=".cpcolors"><i class="fa togglearrow fa-chevron-right" aria-hidden="true"></i> <i class="fa fa-font" aria-hidden="true"></i> Text color </h3><a title="<?= $_lang['reset'] ?>" href="javascript:;" onclick="cleanLocalStorageReloadMain('my_evo_color')" class="pull-right resetcolor btn btn-secondary"><i class="fa fa-refresh"></i></a>
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
                    <input class="color" type="color" id="textcolorPicker" name="textcolorPicker" value="#cf2626" placeholder="color code...">
                </div>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 data-toggle="collapse" data-target=".alinkcolors"><i class="fa togglearrow fa-chevron-right" aria-hidden="true"></i> <i class="fa fa-link" aria-hidden="true"></i> Links Color</h3> <a title="<?= $_lang['reset'] ?>" href="javascript:;" onclick="cleanLocalStorageReloadMain('my_evo_alinkcolor')" class="pull-right resetcolor btn btn-secondary"><i class="fa fa-refresh"></i></a>
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
                    <input class="color" type="color" id="linkcolorPicker" name="alinkcolorPicker" value="#cf2626" placeholder="color code...">
                </div>
            </div>
            <hr/>
            <input type="reset" onclick="cleanLocalStorageReloadAll('my_evo_alinkcolor,my_evo_menuColor,my_evo_menuHColor,my_evo_bgmcolor,my_evo_color')" class="btn btn-secondary" value="<?= $_lang['reset'] ?>">
        </div>
    </div>
    <script>
        $("#bgmPicker").spectrum({
            showButtons: false,
            preferredFormat: "hex3",
            containerClassName: 'bgmPicker',
            showInput: true,
            allowEmpty:true
        });
        $("#menucolorPicker").spectrum({
            showButtons: false,
            preferredFormat: "hex3",
            containerClassName: 'menucolorPicker',
            replacerClassName: 'evo-cp-replacer',
            showInput: true,
            allowEmpty:true
        });
        $("#menuHcolorPicker").spectrum({
            showButtons: false,
            preferredFormat: "hex3",
            containerClassName: 'menuHcolorPicker',
            replacerClassName: 'evo-cp-replacer',
            showInput: true,
            allowEmpty:true
        });
        $("#textcolorPicker").spectrum({
            showButtons: false,
            preferredFormat: "hex3",
            containerClassName: 'textcolorPicker',
            replacerClassName: 'evo-cp-replacer',
            showInput: true,
            allowEmpty:true
        });
        $("#linkcolorPicker").spectrum({
            showButtons: false,
            preferredFormat: "hex3",
            containerClassName: 'linkcolorPicker',
            replacerClassName: 'evo-cp-replacer',
            showInput: true,
            allowEmpty:true
        });
    </script>
@endif
</body>
</html>
