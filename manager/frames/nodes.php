<?php
/**
 *  Tree Nodes
 *  Build and return document tree view nodes
 *
 */
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.');
}

// save folderstate
if (isset($_REQUEST['opened'])) {
    $_SESSION['openedArray'] = $_REQUEST['opened'];
}
if (isset($_REQUEST['savestateonly'])) {
    exit('send some data');
} //??

$indent = (int)$_REQUEST['indent'];
$parent = (int)$_REQUEST['parent'];
$expandAll = (int)$_REQUEST['expandAll'];
$output = '';
$hereid = isset($_REQUEST['id']) && is_numeric($_REQUEST['id']) ? $_REQUEST['id'] : '';

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
        $modx->getManagerApi()->saveLastUserSetting($param, $_REQUEST[$param]);
    }
}

// icons by content type
$icons = getIconInfo($_style);

if (isset($_SESSION['openedArray'])) {
    $opened = array_filter(array_map('intval', explode('|', $_SESSION['openedArray'])));
} else {
    $opened = array();
}

$opened2 = array();
$closed2 = array();

//makeHTML($indent, $parent, $expandAll, $hereid);
echo makeHTML($indent, $parent, $expandAll, $hereid);

// check for deleted documents on reload
if ($expandAll == 2) {
    $rs = $modx->getDatabase()->select('id', $modx->getDatabase()->getFullTableName('site_content'), 'deleted=1 LIMIT 1');
    if ($modx->getDatabase()->getRecordCount($rs)) {
        echo '<span id="binFull"></span>'; // add a special element to let system now that the bin is full
    }
}
