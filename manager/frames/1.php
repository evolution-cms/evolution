<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
header("X-XSS-Protection: 0");

$_SESSION['browser'] = (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 1') !== false) ? 'legacy_IE' : 'modern';

// invoke OnManagerPreFrameLoader
$modx->invokeEvent('OnManagerPreFrameLoader', array('action' => $action));

$mxla = $modx_lang_attribute ? $modx_lang_attribute : 'en';

if (!isset($modx->config['manager_menu_height'])) {
    $modx->config['manager_menu_height'] = 2.2; // rem
}

if (!isset($modx->config['manager_tree_width'])) {
    $modx->config['manager_tree_width'] = 20; // rem
}

if (isset($_SESSION['onLoginForwardToAction']) && is_int($_SESSION['onLoginForwardToAction'])) {
    $initMainframeAction = $_SESSION['onLoginForwardToAction'];
    unset($_SESSION['onLoginForwardToAction']);
} else {
    $initMainframeAction = 2; // welcome.static
}

if (!isset($_SESSION['tree_show_only_folders'])) {
    $_SESSION['tree_show_only_folders'] = 0;
}

$body_class = '';
$menu_height = $modx->config['manager_menu_height'];
$tree_width = $modx->config['manager_tree_width'];
$tree_min_width = 0;

if (isset($_COOKIE['MODX_widthSideBar'])) {
    $MODX_widthSideBar = $_COOKIE['MODX_widthSideBar'];
} else {
    $MODX_widthSideBar = $tree_width;
}

if (!$MODX_widthSideBar) {
    $body_class .= 'sidebar-closed';
}

$theme_modes = array('', 'lightness', 'light', 'dark', 'darkness');
if (!empty($theme_modes[$_COOKIE['MODX_themeMode']])) {
    $body_class .= ' ' . $theme_modes[$_COOKIE['MODX_themeMode']];
} elseif (!empty($theme_modes[$modx->config['manager_theme_mode']])) {
    $body_class .= ' ' . $theme_modes[$modx->config['manager_theme_mode']];
}

$navbar_position = $modx->config['manager_menu_position'];
if ($navbar_position == 'left') {
    $body_class .= ' navbar-left navbar-left-icon-and-text';
}

if (isset($modx->pluginCache['ElementsInTree'])) {
    $body_class .= ' ElementsInTree';
}

$unlockTranslations = array(
    'msg' => $_lang["unlock_element_id_warning"],
    'type1' => $_lang["lock_element_type_1"],
    'type2' => $_lang["lock_element_type_2"],
    'type3' => $_lang["lock_element_type_3"],
    'type4' => $_lang["lock_element_type_4"],
    'type5' => $_lang["lock_element_type_5"],
    'type6' => $_lang["lock_element_type_6"],
    'type7' => $_lang["lock_element_type_7"],
    'type8' => $_lang["lock_element_type_8"]
);

foreach ($unlockTranslations as $key => $value) {
    $unlockTranslations[$key] = iconv($modx->config["modx_charset"], "utf-8", $value);
}

$user = $modx->getUserInfo($modx->getLoginUserID());
if ($user['which_browser'] == 'default') {
    $user['which_browser'] = $modx->config['which_browser'];
}

$css = 'media/style/' . $modx->config['manager_theme'] . '/css/page.css?v=' . $lastInstallTime;

if ($modx->config['manager_theme'] == 'default') {
    if (!file_exists(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/styles.min.css') && is_writable(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css')) {
        require_once MODX_BASE_PATH . 'assets/lib/Formatter/CSSMinify.php';
        $minifier = new Formatter\CSSMinify();
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/common/bootstrap/css/bootstrap.min.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/common/font-awesome/css/font-awesome.min.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/fonts.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/forms.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/mainmenu.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/tree.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/custom.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/tabpane.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/contextmenu.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/index.css');
        $minifier->addFile(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/main.css');
        $css = $minifier->minify();
        file_put_contents(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/styles.min.css', $css);
    }
    if (file_exists(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/styles.min.css')) {
        $css = 'media/style/' . $modx->config['manager_theme'] . '/css/styles.min.css?v=' . $lastInstallTime;
    }
}

$modx->config['global_tabs'] = (int)($modx->config['global_tabs'] && ($user['role'] == 1 || $modx->hasPermission('edit_template') || $modx->hasPermission('edit_chunk') || $modx->hasPermission('edit_snippet') || $modx->hasPermission('edit_plugin')));

?>
<!DOCTYPE html>
<html <?= (isset($modx_textdir) && $modx_textdir ? 'dir="rtl" lang="' : 'lang="') . $mxla . '" xml:lang="' . $mxla . '"' ?>>
<head>
    <title><?= $site_name ?>- (EVO CMS Manager)</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?= $modx_manager_charset ?>" />
    <meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width" />
    <meta name="theme-color" content="#1d2023" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="stylesheet" type="text/css" href="<?= $css ?>" />
    <?php if ($modx->config['show_picker'] != "0") { ?>
        <link rel="stylesheet" href="media/style/common/spectrum/spectrum.css" />
        <link rel="stylesheet" type="text/css" href="media/style/<?= $modx->config['manager_theme'] ?>/css/color.switcher.css" />
    <?php } ?>
    <link rel="icon" type="image/ico" href="<?= $_style['favicon'] ?>" />
    <style>
        #tree { width: <?= $MODX_widthSideBar ?>rem }
        #main, #resizer { left: <?= $MODX_widthSideBar ?>rem }
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
        MGR_DIR: '<?= MGR_DIR ?>',
        MODX_SITE_URL: '<?= MODX_SITE_URL ?>',
        MODX_MANAGER_URL: '<?= MODX_MANAGER_URL ?>',
        user: {
          role: <?= (int)$user['role'] ?>,
          username: "<?= html_escape($user['username'], $modx->config['modx_charset']) ?>"
        },
        config: {
          mail_check_timeperiod: <?= $modx->config['mail_check_timeperiod'] ?>,
          menu_height: <?= (int)$menu_height ?>,
          tree_width: <?= (int)$tree_width ?>,
          tree_min_width: <?= (int)$tree_min_width ?>,
          session_timeout: <?= (int)$modx->config['session_timeout'] ?>,
          site_start: <?= (int)$modx->config['site_start'] ?>,
          tree_page_click: <?=(!empty($modx->config['tree_page_click']) ? (int)$modx->config['tree_page_click'] : 27) ?>,
          theme: "<?= html_escape($modx->config['manager_theme'], $modx->config['modx_charset']) ?>",
          theme_mode: "<?= html_escape($modx->config['manager_theme_mode'], $modx->config['modx_charset']) ?>",
          which_browser: '<?= $user['which_browser'] ?>',
          layout: <?= (int)$manager_layout ?>,
          textdir: '<?= $modx_textdir ?>',
          global_tabs: <?= $modx->config['global_tabs'] ?>

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
          collapse_tree: '<?= addslashes($_style['collapse_tree']) ?>',
          email: '<?= addslashes($_style['email']) ?>',
          empty_recycle_bin: '<?= addslashes($_style['empty_recycle_bin']) ?>',
          empty_recycle_bin_empty: '<?= addslashes($_style['empty_recycle_bin_empty']) ?>',
          expand_tree: '<?= addslashes($_style['expand_tree']) ?>',
          icons_external_link: '<?= addslashes($_style['icons_external_link']) ?>',
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
      $opened = array_filter(array_map('intval', explode('|', $_SESSION['openedArray'])));
      echo (empty($opened) ? '' : 'modx.openedArray[' . implode("] = 1;\n		modx.openedArray[", $opened) . '] = 1;') . "\n";
      ?>
    </script>
    <script src="media/style/<?= $modx->config['manager_theme'] ?>/js/modx.min.js?v=<?= $lastInstallTime ?>"></script>
    <?php if ($modx->config['show_picker'] != "0") { ?>
        <script src="media/script/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="media/script/spectrum/spectrum.evo.min.js" type="text/javascript"></script>
        <script src="media/style/<?= $modx->config['manager_theme'] ?>/js/color.switcher.js" type="text/javascript"></script>
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
                    <?php include('mainmenu.php') ?>
                </div>
                <div class="cell" data-evocp="bgmColor">
                    <ul id="settings" class="nav">
                        <li id="searchform">
                            <form action="index.php?a=71" method="post" target="main">
                                <input type="hidden" value="Search" name="submitok" />
                                <label for="searchid" class="label_searchid">
                                    <?= $_style['menu_search'] ?>
                                </label>
                                <input type="text" id="searchid" name="searchid" size="25" />
                                <div class="mask"></div>
                            </form>
                        </li>
                        <?php if ($modx->config['show_newresource_btn'] != "0") { ?>
                            <?php if ($modx->hasPermission('new_document')) { ?>
                                <li id="newresource" class="dropdown newresource">
                                    <a href="javascript:;" class="dropdown-toggle" onclick="return false;" title="<?= $_lang['add_resource'] ?>"><?= $_style['menu_new_resource'] ?></a>
                                    <ul class="dropdown-menu">
                                        <?php if ($modx->hasPermission('new_document')) { ?>
                                            <li>
                                                <a onclick="" href="index.php?a=4" target="main">
                                                    <?= $_style['add_doc_tree'] ?><?= $_lang['add_resource'] ?>
                                                </a>
                                            </li>
                                            <li>
                                                <a onclick="" href="index.php?a=72" target="main">
                                                    <?= $_style['add_weblink_tree'] ?><?= $_lang['add_weblink'] ?>
                                                </a>
                                            </li>
                                        <?php } ?>
                                        <?php if ($use_browser && $modx->hasPermission('assets_images')) { ?>
                                            <li>
                                                <a onclick="" href="media/browser/<?= $which_browser ?>/browse.php?&type=images" target="main">
                                                    <?= $_style['images_management'] ?><?= $_lang['images_management'] ?>
                                                </a>
                                            </li>
                                        <?php } ?>
                                        <?php if ($use_browser && $modx->hasPermission('assets_files')) { ?>
                                            <li>
                                                <a onclick="" href="media/browser/<?= $which_browser ?>/browse.php?&type=files" target="main">
                                                    <?= $_style['files_management'] ?><?= $_lang['files_management'] ?>
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            <?php } ?>
                        <?php } ?>
                        <li id="preview">
                            <a href="../" target="_blank" title="<?= $_lang['preview'] ?>">
                                <?= $_style['menu_preview_site'] ?>
                            </a>
                        </li>
                        <li id="account" class="dropdown account">
                            <a href="javascript:;" class="dropdown-toggle" onclick="return false;">
                                <span class="username"><?= html_escape($user['username'], $modx->config['modx_charset']) ?></span>
                                <?php if ($user['photo']) { ?>
                                    <span class="icon photo" style="background-image: url(<?= MODX_SITE_URL . html_escape($user['photo'], $modx->config['modx_charset']) ?>);"></span>
                                <?php } else { ?>
                                    <span class="icon"><?= $_style['menu_user'] ?></span>
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
                                            <?= $_style['page_change_password'] ?><?= $_lang['change_password'] ?>
                                        </a>
                                    </li>
                                <?php } ?>
                                <li>
                                    <a href="index.php?a=8">
                                        <?= $_style['page_logout'] ?><?= $_lang['logout'] ?>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <?php if ($modx->hasPermission('settings') || $modx->hasPermission('view_eventlog') || $modx->hasPermission('logs') || $modx->hasPermission('help')) { ?>
                            <li id="system" class="dropdown">
                                <a href="javascript:;" class="dropdown-toggle" title="<?= $_lang['system'] ?>" onclick="return false;"><?= $_style['menu_system'] ?></a>
                                <ul class="dropdown-menu">
                                    <?php if ($modx->hasPermission('settings')) { ?>
                                        <li>
                                            <a href="index.php?a=17" target="main">
                                                <?= $_style['page_settings'] ?><?= $_lang['edit_settings'] ?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if ($modx->hasPermission('view_eventlog')) { ?>
                                        <li>
                                            <a href="index.php?a=70" target="main">
                                                <?= $_style['page_shedule'] ?><?= $_lang['site_schedule'] ?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if ($modx->hasPermission('view_eventlog')) { ?>
                                        <li>
                                            <a href="index.php?a=114" target="main">
                                                <?= $_style['page_eventlog'] ?></i><?= $_lang['eventlog_viewer'] ?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if ($modx->hasPermission('logs')) { ?>
                                        <li>
                                            <a href="index.php?a=13" target="main">
                                                <?= $_style['page_manager_logs'] ?><?= $_lang['view_logging'] ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="index.php?a=53" target="main">
                                                <?= $_style['page_sys_info'] ?><?= $_lang['view_sysinfo'] ?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php if ($modx->hasPermission('help')) { ?>
                                        <li>
                                            <a href="index.php?a=9" target="main">
                                                <?= $_style['page_help'] ?><?= $_lang['help'] ?>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <?php
                                    $style = $modx->config['settings_version'] != $modx->getVersionData('version') ? 'style="color:#ffff8a;"' : '';
                                    $version = 'Evolution';
                                    ?>
                                    <?php
                                    echo sprintf('<li><span class="dropdown-item" title="%s &ndash; %s" %s>' . $version . ' %s</span></li>', $site_name, $modx->getVersionData('full_appname'), $style, $modx->config['settings_version']);
                                    ?>
                                </ul>
                            </li>
                        <?php } ?>
                        <?php if ($modx->config['show_fullscreen_btn'] != "0") { ?>
                            <li id="fullscreen">
                                <a href="javascript:;" onclick="toggleFullScreen();" id="toggleFullScreen" title="<?= $_lang["toggle_fullscreen"] ?>">
                                    <i class="fa <?= $_style['menu_expand'] ?>"></i>
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div id="tree">
        <?php include('tree.php') ?>
    </div>
    <div id="main">
        <?php if ($modx->config['global_tabs']): ?>
            <div class="tab-row-container evo-tab-row">
                <div class="tab-row"><h2 id="evo-tab-home" class="tab selected" data-target="evo-tab-page-home"><i class="fa fa-home"></i></h2></div>
            </div>
            <div id="evo-tab-page-home" class="evo-tab-page show iframe-scroller">
                <iframe id="mainframe" src="index.php?a=<?= $initMainframeAction ?>" scrolling="auto" frameborder="0" onload="modx.main.onload(event);"></iframe>
            </div>
        <?php else: ?>
            <div class="iframe-scroller">
                <iframe id="mainframe" name="main" src="index.php?a=<?= $initMainframeAction ?>" scrolling="auto" frameborder="0" onload="modx.main.onload(event);"></iframe>
            </div>
        <?php endif; ?>
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
                $modx->manager->saveLastUserSetting($param, $_REQUEST[$param]);
                $_SESSION[$param] = $_REQUEST[$param];
            } else if (!isset($_SESSION[$param])) {
                $_SESSION[$param] = $modx->manager->getLastUserSetting($param);
            }
        }
        ?>
        <form name="sortFrm" id="sortFrm">
            <div class="form-group">
                <input type="hidden" name="dt" value="<?= htmlspecialchars($_REQUEST['dt']) ?>" />
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

    <!-- Contextual Menu Popup Code -->
    <div id="mx_contextmenu" class="dropdown" onselectstart="return false;">
        <div id="nameHolder">&nbsp;</div>
        <?php
        constructLink(3, $_style["ctx_new_document"], $_lang["create_resource_here"], $modx->hasPermission('new_document')); // new Resource
        constructLink(2, $_style["ctx_edit_document"], $_lang["edit_resource"], $modx->hasPermission('edit_document')); // edit
        constructLink(5, $_style["ctx_move_document"], $_lang["move_resource"], $modx->hasPermission('save_document')); // move
        constructLink(7, $_style["ctx_resource_duplicate"], $_lang["resource_duplicate"], $modx->hasPermission('new_document')); // duplicate
        constructLink(11, $_style["ctx_sort_menuindex"], $_lang["sort_menuindex"], !!($modx->hasPermission('edit_document') && $modx->hasPermission('save_document'))); // sort menu index
        ?>
        <div class="seperator"></div>
        <?php
        constructLink(9, $_style["ctx_publish_document"], $_lang["publish_resource"], $modx->hasPermission('publish_document')); // publish
        constructLink(10, $_style["ctx_unpublish_resource"], $_lang["unpublish_resource"], $modx->hasPermission('publish_document')); // unpublish
        constructLink(4, $_style["ctx_delete"], $_lang["delete_resource"], $modx->hasPermission('delete_document')); // delete
        constructLink(8, $_style["ctx_undelete_resource"], $_lang["undelete_resource"], $modx->hasPermission('delete_document')); // undelete
        ?>
        <div class="seperator"></div>
        <?php
        constructLink(6, $_style["ctx_weblink"], $_lang["create_weblink_here"], $modx->hasPermission('new_document')); // new Weblink
        ?>
        <div class="seperator"></div>
        <?php
        constructLink(1, $_style["ctx_resource_overview"], $_lang["resource_overview"], $modx->hasPermission('view_document')); // view
        constructLink(12, $_style["ctx_preview_resource"], $_lang["preview_resource"], 1); // preview
        ?>

    </div>

    <?php
    /**
     * @param string $action
     * @param string $img
     * @param string $text
     * @param bool $allowed
     */
    function constructLink($action, $img, $text, $allowed)
    {
        if ((bool)$allowed) {
            echo sprintf('<div class="menuLink" id="item%s" onclick="modx.tree.menuHandler(%s);">', $action, $action);
            echo sprintf('<i class="%s"></i> %s</div>', $img, $text);
        }
    }

    ?>

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
          <?php if($use_browser && $modx->hasPermission('assets_images')) { ?>

        document.getElementById('treeMenu_openimages').onclick = function(e) {
          e.preventDefault();
          if (modx.config.global_tabs && !e.shiftKey) {
            modx.tabs({url: '<?= MODX_MANAGER_URL . 'media/browser/' . $which_browser . '/browse.php?filemanager=media/browser/' . $which_browser . '/browse.php&type=images' ?>', title: '<?= $_lang["images_management"] ?>'});
          } else {
            var randomNum = '<?= $_lang["files_files"] ?>';
            if (e.shiftKey) {
              randomNum += ' #' + Math.floor((Math.random() * 999999) + 1);
            }
            modx.openWindow({
              url: '<?= MODX_MANAGER_URL ?>media/browser/<?= $which_browser ?>/browse.php?&type=images',
              title: randomNum
            });
          }
        };
          <?php } ?>
          <?php if($use_browser && $modx->hasPermission('assets_files')) { ?>

        document.getElementById('treeMenu_openfiles').onclick = function(e) {
          e.preventDefault();
          if (modx.config.global_tabs && !e.shiftKey) {
            modx.tabs({url: '<?= MODX_MANAGER_URL . 'media/browser/' . $which_browser . '/browse.php?filemanager=media/browser/' . $which_browser . '/browse.php&type=files' ?>', title: '<?= $_lang["files_files"] ?>'});
          } else {
            var randomNum = '<?= $_lang["files_files"] ?>';
            if (e.shiftKey) {
              randomNum += ' #' + Math.floor((Math.random() * 999999) + 1);
            }
            modx.openWindow({
              url: '<?= MODX_MANAGER_URL ?>media/browser/<?= $which_browser ?>/browse.php?&type=files',
              title: randomNum
            });
          }
        };
          <?php } ?>

      }

    </script>
    <?php if ($modx->config['show_fullscreen_btn'] != "0") { ?>
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
            icon.toggleClass('<?= $_style['menu_expand'] ?> <?= $_style['menu_compress'] ?>');
          });
        </script>
    <?php } ?>
    <?php
    // invoke OnManagerFrameLoader
    $modx->invokeEvent('OnManagerFrameLoader', array('action' => $action));
    ?>

</div>
<?php if ($modx->config['show_picker'] != "0") {
    include('media/style/' . $modx->config['manager_theme'] . '/color.switcher.php');
} ?>
</body>
</html>
