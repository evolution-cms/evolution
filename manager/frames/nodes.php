<?php
/**
 *  Tree Nodes
 *  Build and return document tree view nodes
 *
 */
if (IN_MANAGER_MODE != 'true') {
    die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.');
}

include_once('nodes.functions.inc.php');

// save folderstate
if (isset($_REQUEST['opened'])) {
    $_SESSION['openedArray'] = $_REQUEST['opened'];
}
if (isset($_REQUEST['savestateonly'])) {
    exit('send some data');
} //??

$indent = intval($_REQUEST['indent']);
$parent = intval($_REQUEST['parent']);
$expandAll = intval($_REQUEST['expandAll']);
$output = '';
$theme = $manager_theme . "/";
$hereid = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : '';

if (isset($_REQUEST['showonlyfolders'])) {
    $_SESSION['tree_show_only_folders'] = $_REQUEST['showonlyfolders'];
}

// setup sorting
$sortParams = array(
    'tree_sortby',
    'tree_sortdir',
    'tree_nodename'
);
foreach ($sortParams as $param) {
    if (isset($_REQUEST[$param])) {
        $_SESSION[$param] = $_REQUEST[$param];
        $modx->manager->saveLastUserSetting($param, $_REQUEST[$param]);
    }
}

// icons by content type
$icons = getIconInfo($_style);
$iconsPrivate = getPrivateIconInfo($_style);

if (isset($_SESSION['openedArray'])) {
    $opened = array_filter(array_map('intval', explode('|', $_SESSION['openedArray'])));
} else {
    $opened = array();
}

$opened2 = array();
$closed2 = array();

//makeHTML($indent, $parent, $expandAll, $theme, $hereid);
echo makeHTML($indent, $parent, $expandAll, $theme, $hereid);

// check for deleted documents on reload
if ($expandAll == 2) {
    $rs = $modx->db->select('id', $modx->getFullTableName('site_content'), 'deleted=1 LIMIT 1');
    if ($modx->db->getRecordCount($rs)) {
        echo '<span id="binFull"></span>'; // add a special element to let system now that the bin is full
    }
}
