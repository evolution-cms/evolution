<?php

define('MODX_API_MODE', true);
define('IN_MANAGER_MODE', true);

include_once("./../../../../index.php");

$modx->db->connect();

if (empty ($modx->config)) {
    $modx->getSettings();
}

if (!isset($_SESSION['mgrValidated']) || !isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') || ($_SERVER['REQUEST_METHOD'] != 'POST')) {
    $modx->sendErrorPage();
}

$modx->sid = session_id();
$modx->loadExtension("ManagerAPI");

$_lang = array();
include_once MODX_MANAGER_PATH . '/includes/lang/english.inc.php';
if ($modx->config['manager_language'] != 'english') {
    include_once MODX_MANAGER_PATH . '/includes/lang/' . $modx->config['manager_language'] . '.inc.php';
}
include_once MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/style.php';

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';
$frame = isset($_REQUEST['f']) ? $_REQUEST['f'] : '';
$role = isset($_SESSION['mgrRole']) && $_SESSION['mgrRole'] == 1 ? 1 : 0;
$docGroups = isset($_SESSION['mgrDocgroups']) && is_array($_SESSION['mgrDocgroups']) ? implode(',', $_SESSION['mgrDocgroups']) : '';

// set limit sql query
$limit = !empty($modx->config['number_of_results']) ? (int) $modx->config['number_of_results'] : 100;

if (isset($action)) {
    switch ($action) {

        case '1': {

            switch ($frame) {
                case 'nodes':
                    include_once MODX_MANAGER_PATH . '/frames/nodes.php';

                    break;
            }

            break;
        }

        case '76': {

            $elements = isset($_REQUEST['elements']) && is_scalar($_REQUEST['elements']) ? htmlentities($_REQUEST['elements']) : '';

            if ($elements) {
                $output = '';
                $items = '';
                $sql = '';
                $a = '';
                $filter = !empty($_REQUEST['filter']) && is_scalar($_REQUEST['filter']) ? addcslashes(trim($_REQUEST['filter']), '%*_') : '';
                $sqlLike = $filter ? 'WHERE t1.name LIKE "' . $modx->db->escape($filter) . '%"' : '';
                $sqlLimit = $sqlLike ? '' : 'LIMIT ' . $limit;

                switch ($elements) {
                    case 'element_templates':
                        $a = 16;
                        $sqlLike = $filter ? 'WHERE t1.templatename LIKE "' . $modx->db->escape($filter) . '%"' : '';
                        $sql = $modx->db->query('SELECT t1.*, t1.templatename AS name
                        FROM ' . $modx->getFullTableName('site_templates') . ' AS t1
                        ' . $sqlLike . '
                        ORDER BY t1.templatename ASC
                        ' . $sqlLimit);

                        if ($modx->hasPermission('new_template')) {
                            $output .= '<li><a id="a_19" href="index.php?a=19" target="main"><i class="fa fa-plus"></i>' . $_lang['new_template'] . '</a></li>';
                        }

                        break;

                    case 'element_tplvars':
                        $a = 301;
                        $sql = $modx->db->query('SELECT t1.*, IF(t2.templateid,0,1) AS disabled
                        FROM ' . $modx->getFullTableName('site_tmplvars') . ' AS t1
                        LEFT JOIN ' . $modx->getFullTableName('site_tmplvar_templates') . ' AS t2 ON t1.id=t2.tmplvarid
                        ' . $sqlLike . '
                        GROUP BY t1.id
                        ORDER BY t1.name ASC
                        ' . $sqlLimit);

                        if ($modx->hasPermission('edit_template') && $modx->hasPermission('edit_snippet') && $modx->hasPermission('edit_chunk') && $modx->hasPermission('edit_plugin')) {
                            $output .= '<li><a id="a_300" href="index.php?a=300" target="main"><i class="fa fa-plus"></i>' . $_lang['new_tmplvars'] . '</a></li>';
                        }

                        break;

                    case 'element_htmlsnippets':
                        $a = 78;
                        $sql = $modx->db->query('SELECT t1.*
                        FROM ' . $modx->getFullTableName('site_htmlsnippets') . ' AS t1
                        ' . $sqlLike . '
                        ORDER BY t1.name ASC
                        ' . $sqlLimit);

                        if ($modx->hasPermission('new_chunk')) {
                            $output .= '<li><a id="a_77" href="index.php?a=77" target="main"><i class="fa fa-plus"></i>' . $_lang['new_htmlsnippet'] . '</a></li>';
                        }

                        break;

                    case 'element_snippets':
                        $a = 22;
                        $sql = $modx->db->query('SELECT t1.*
                        FROM ' . $modx->getFullTableName('site_snippets') . ' AS t1
                        ' . $sqlLike . '
                        ORDER BY t1.name ASC
                        ' . $sqlLimit);

                        if ($modx->hasPermission('new_snippet')) {
                            $output .= '<li><a id="a_23" href="index.php?a=23" target="main"><i class="fa fa-plus"></i>' . $_lang['new_snippet'] . '</a></li>';
                        }

                        break;

                    case 'element_plugins':
                        $a = 102;
                        $sql = $modx->db->query('SELECT t1.*
                        FROM ' . $modx->getFullTableName('site_plugins') . ' AS t1
                        ' . $sqlLike . '
                        ORDER BY t1.name ASC
                        ' . $sqlLimit);

                        if ($modx->hasPermission('new_plugin')) {
                            $output .= '<li><a id="a_101" href="index.php?a=101" target="main"><i class="fa fa-plus"></i>' . $_lang['new_plugin'] . '</a></li>';
                        }

                        break;
                }

                if ($count = $modx->db->getRecordCount($sql)) {
                    if ($count == $limit) {
                        $output .= '<li class="item-input"><input type="text" name="filter" class="dropdown-item form-control form-control-sm" autocomplete="off" /></li>';
                    }
                    while ($row = $modx->db->getRow($sql)) {
                        if (($row['disabled'] || $row['locked']) && $role != 1) {
                            continue;
                        }

                        $items .= '<li class="item ' . ($row['disabled'] ? 'disabled' : '') . ($row['locked'] ? ' locked' : '') . '"><a id="a_' . $a . '__id_' . $row['id'] . '" href="index.php?a=' . $a . '&id=' . $row['id'] . '" target="main" data-parent-id="a_76__elements_' . $elements . '">' . $row['name'] . ' <small>(' . $row['id'] . ')</small></a></li>' . "\n";
                    }
                }

                if (isset($_REQUEST['filter'])) {
                    $output = $items;
                } else {
                    $output .= $items;
                }

                echo $output;
            }

            break;
        }

        case '75': {
            $a = 12;
            $output = '';
            $items = '';
            $filter = !empty($_REQUEST['filter']) && is_scalar($_REQUEST['filter']) ? addcslashes(trim($_REQUEST['filter']), '\%*_') : '';
            $sqlLike = $filter ? 'WHERE t1.username LIKE "' . $modx->db->escape($filter) . '%"' : '';
            $sqlLimit = $sqlLike ? '' : 'LIMIT ' . $limit;

            $sql = $modx->db->query('SELECT t1.*, t1.username AS name, t2.blocked
				FROM ' . $modx->getFullTableName('manager_users') . ' AS t1
				LEFT JOIN ' . $modx->getFullTableName('user_attributes') . ' AS t2 ON t1.id=t2.internalKey
				' . $sqlLike . '
				ORDER BY t1.username ASC
				' . $sqlLimit);

            if ($modx->hasPermission('new_user')) {
                $output .= '<li><a id="a_11" href="index.php?a=11" target="main"><i class="fa fa-plus"></i>' . $_lang['new_user'] . '</a></li>';
            }

            if ($count = $modx->db->getRecordCount($sql)) {
                if ($count == $limit) {
                    $output .= '<li class="item-input"><input type="text" name="filter" class="dropdown-item form-control form-control-sm" autocomplete="off" /></li>';
                }
                while ($row = $modx->db->getRow($sql)) {
                    $items .= '<li class="item ' . ($row['blocked'] ? 'disabled' : '') . '"><a id="a_' . $a . '__id_' . $row['id'] . '" href="index.php?a=' . $a . '&id=' . $row['id'] . '" target="main">' . $row['name'] . ' <small>(' . $row['id'] . ')</small></a></li>';
                }
            }

            if (isset($_REQUEST['filter'])) {
                $output = $items;
            } else {
                $output .= $items;
            }

            echo $output;

            break;
        }

        case '99': {
            $a = 88;
            $output = '';
            $items = '';
            $filter = !empty($_REQUEST['filter']) && is_scalar($_REQUEST['filter']) ? addcslashes(trim($_REQUEST['filter']), '\%*_') : '';
            $sqlLike = $filter ? 'WHERE t1.username LIKE "' . $modx->db->escape($filter) . '%"' : '';
            $sqlLimit = $sqlLike ? '' : 'LIMIT ' . $limit;

            $sql = $modx->db->query('SELECT t1.*, t1.username AS name, t2.blocked
				FROM ' . $modx->getFullTableName('web_users') . ' AS t1
				LEFT JOIN ' . $modx->getFullTableName('web_user_attributes') . ' AS t2 ON t1.id=t2.internalKey
				' . $sqlLike . '
				ORDER BY t1.username ASC
				' . $sqlLimit);

            if ($modx->hasPermission('new_web_user')) {
                $output .= '<li><a id="a_87" href="index.php?a=87" target="main"><i class="fa fa-plus"></i>' . $_lang['new_web_user'] . '</a></li>';
            }

            if ($count = $modx->db->getRecordCount($sql)) {
                if ($count == $limit) {
                    $output .= '<li class="item-input"><input type="text" name="filter" class="dropdown-item form-control form-control-sm" autocomplete="off" /></li>';
                }
                while ($row = $modx->db->getRow($sql)) {
                    $items .= '<li class="item ' . ($row['blocked'] ? 'disabled' : '') . '"><a id="a_' . $a . '__id_' . $row['id'] . '" href="index.php?a=' . $a . '&id=' . $row['id'] . '" target="main">' . $row['name'] . ' <small>(' . $row['id'] . ')</small></a></li>';
                }
            }

            if (isset($_REQUEST['filter'])) {
                $output = $items;
            } else {
                $output .= $items;
            }

            echo $output;

            break;
        }

        case 'modxTagHelper': {
            $name = isset($_REQUEST['name']) && is_scalar($_REQUEST['name']) ? $modx->db->escape($_REQUEST['name']) : false;
            $type = isset($_REQUEST['type']) && is_scalar($_REQUEST['type']) ? $modx->db->escape($_REQUEST['type']) : false;
            $contextmenu = '';

            if ($role && $name && $type) {
                switch ($type) {
                    case 'Snippet':
                    case 'SnippetNoCache': {

                        $sql = $modx->db->query('SELECT *
						FROM ' . $modx->getFullTableName('site_snippets') . '
						WHERE name="' . $name . '"
						LIMIT 1');

                        if ($modx->db->getRecordCount($sql)) {
                            $row = $modx->db->getRow($sql);
                            $contextmenu = array(
                                'header' => array(
                                    'innerHTML' => '<i class="fa fa-code"></i> ' . $row['name']
                                ),
                                'item' => array(
                                    'innerHTML' => '<i class="fa fa-pencil-square-o"></i> ' . $_lang['edit'],
                                    'url' => "index.php?a=22&id=" . $row['id']
                                )
                            );
                            if (!empty($row['description'])) {
                                $contextmenu['seperator'] = '';
                                $contextmenu['description'] = array(
                                    'innerHTML' => '<i class="fa fa-info"></i> ' . $row['description']
                                );
                            }
                        } else {
                            $contextmenu = array(
                                'header' => array(
                                    'innerHTML' => '<i class="fa fa-code"></i> ' . $name
                                ),
                                'item' => array(
                                    'innerHTML' => '<i class="fa fa-plus"></i> ' . $_lang['new_snippet'],
                                    'url' => "index.php?a=23&itemname=" . $name
                                )
                            );
                        }

                        break;
                    }
                    case 'Chunk' : {

                        $sql = $modx->db->query('SELECT *
						FROM ' . $modx->getFullTableName('site_htmlsnippets') . '
						WHERE name="' . $name . '"
						LIMIT 1');

                        if ($modx->db->getRecordCount($sql)) {
                            $row = $modx->db->getRow($sql);
                            $contextmenu = array(
                                'header' => array(
                                    'innerHTML' => '<i class="fa fa-th-large"></i> ' . $row['name']
                                ),
                                'item' => array(
                                    'innerHTML' => '<i class="fa fa-pencil-square-o"></i> ' . $_lang['edit'],
                                    'url' => "index.php?a=78&id=" . $row['id']
                                )
                            );
                            if (!empty($row['description'])) {
                                $contextmenu['seperator'] = '';
                                $contextmenu['description'] = array(
                                    'innerHTML' => '<i class="fa fa-info"></i> ' . $row['description']
                                );
                            }
                        } else {
                            $contextmenu = array(
                                'header' => array(
                                    'innerHTML' => '<i class="fa fa-th-large"></i> ' . $name
                                ),
                                'item' => array(
                                    'innerHTML' => '<i class="fa fa-plus"></i> ' . $_lang['new_htmlsnippet'],
                                    'url' => "index.php?a=77&itemname=" . $name
                                )
                            );
                        }

                        break;
                    }
                    case 'AttributeValue': {
                        $sql = $modx->db->query('SELECT *
						FROM ' . $modx->getFullTableName('site_htmlsnippets') . '
						WHERE name="' . $name . '"
						LIMIT 1');

                        if ($modx->db->getRecordCount($sql)) {
                            $row = $modx->db->getRow($sql);
                            $contextmenu = array(
                                'header' => array(
                                    'innerText' => $row['name']
                                ),
                                'item' => array(
                                    'innerHTML' => '<i class="fa fa-pencil-square-o"></i> ' . $_lang['edit'],
                                    'url' => "index.php?a=78&id=" . $row['id']
                                )
                            );
                            if (!empty($row['description'])) {
                                $contextmenu['seperator'] = '';
                                $contextmenu['description'] = array(
                                    'innerHTML' => '<i class="fa fa-info"></i> ' . $row['description']
                                );
                            }
                        } else {

                            $sql = $modx->db->query('SELECT *
							FROM ' . $modx->getFullTableName('site_snippets') . '
							WHERE name="' . $name . '"
							LIMIT 1');

                            if ($modx->db->getRecordCount($sql)) {
                                $row = $modx->db->getRow($sql);
                                $contextmenu = array(
                                    'header' => array(
                                        'innerHTML' => '<i class="fa fa-code"></i> ' . $row['name']
                                    ),
                                    'item' => array(
                                        'innerHTML' => '<i class="fa fa-pencil-square-o"></i> ' . $_lang['edit'],
                                        'url' => "index.php?a=22&id=" . $row['id']
                                    )
                                );
                                if (!empty($row['description'])) {
                                    $contextmenu['seperator'] = '';
                                    $contextmenu['description'] = array(
                                        'innerHTML' => '<i class="fa fa-info"></i> ' . $row['description']
                                    );
                                }
                            } else {
                                $contextmenu = array(
                                    'header' => array(
                                        'innerHTML' => '<i class="fa fa-code"></i> ' . $name
                                    ),
                                    'item' => array(
                                        'innerHTML' => '<i class="fa fa-plus"></i> ' . $_lang['new_htmlsnippet'],
                                        'url' => "index.php?a=77&itemname=" . $name
                                    ),
                                    'item2' => array(
                                        'innerHTML' => '<i class="fa fa-plus"></i> ' . $_lang['new_snippet'],
                                        'url' => "index.php?a=23&itemname=" . $name
                                    )
                                );
                            }
                        }

                        break;
                    }
                    case 'Placeholder' :
                    case 'Tv' : {
                        $default_field = array(
                            'id',
                            'type',
                            'contentType',
                            'pagetitle',
                            'longtitle',
                            'description',
                            'alias',
                            'link_attributes',
                            'published',
                            'pub_date',
                            'unpub_date',
                            'parent',
                            'isfolder',
                            'introtext',
                            'content',
                            'richtext',
                            'template',
                            'menuindex',
                            'searchable',
                            'cacheable',
                            'createdon',
                            'createdby',
                            'editedon',
                            'editedby',
                            'deleted',
                            'deletedon',
                            'deletedby',
                            'publishedon',
                            'publishedby',
                            'menutitle',
                            'donthit',
                            'haskeywords',
                            'hasmetatags',
                            'privateweb',
                            'privatemgr',
                            'content_dispo',
                            'hidemenu',
                            'alias_visible'
                        );

                        if (in_array($name, $default_field)) {
                            return;
                        }

                        $sql = $modx->db->query('SELECT *
						FROM ' . $modx->getFullTableName('site_tmplvars') . '
						WHERE name="' . $name . '"
						LIMIT 1');

                        if ($modx->db->getRecordCount($sql)) {
                            $row = $modx->db->getRow($sql);
                            $contextmenu = array(
                                'header' => array(
                                    'innerHTML' => '<i class="fa fa-list-alt"></i> ' . $row['name']
                                ),
                                'item' => array(
                                    'innerHTML' => '<i class="fa fa-pencil-square-o"></i> ' . $_lang['edit'],
                                    'url' => "index.php?a=301&id=" . $row['id']
                                )
                            );
                            if (!empty($row['description'])) {
                                $contextmenu['seperator'] = '';
                                $contextmenu['description'] = array(
                                    'innerHTML' => '<i class="fa fa-info"></i> ' . $row['description']
                                );
                            }
                        } else {
                            $contextmenu = array(
                                'header' => array(
                                    'innerHTML' => '<i class="fa fa-list-alt"></i> ' . $name
                                ),
                                'item' => array(
                                    'innerHTML' => '<i class="fa fa-plus"></i> ' . $_lang['new_tmplvars'],
                                    'url' => "index.php?a=300&itemname=" . $name
                                )
                            );
                        }

                        break;
                    }
                }
                echo json_encode($contextmenu, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE);
                break;
            }

            break;
        }

        case 'movedocument' : {
            $json = array();

            if ($modx->hasPermission('new_document') && $modx->hasPermission('edit_document') && $modx->hasPermission('save_document')) {
                $id = !empty($_REQUEST['id']) ? (int)$_REQUEST['id'] : '';
                $parent = isset($_REQUEST['parent']) ? (int)$_REQUEST['parent'] : 0;
                $menuindex = isset($_REQUEST['menuindex']) && is_scalar($_REQUEST['menuindex']) ? $_REQUEST['menuindex'] : 0;

                // set parent
                if ($id && $parent >= 0) {

                    // find older parent
                    $parentOld = $modx->db->getValue($modx->db->select('parent', $modx->getFullTableName('site_content'), 'id=' . $id));

                    $eventOut = $modx->invokeEvent('onBeforeMoveDocument', [
                        'id_document' => $id,
                        'old_parent'  => $parentOld,
                        'new_parent'  => $parent,
                    ]);

                    if (is_array($eventOut) && count($eventOut) > 0) {
                        $eventParent = array_pop($eventOut);

                        if ($eventParent == $parentOld) {
                            $json['errors'] = $_lang['error_movedocument2'];
                        } else {
                            $parent = $eventParent;
                        }
                    }

                    if (empty($json['errors'])) {
                        // check privileges user for move docs
                        if (!empty($modx->config['tree_show_protected']) && $role != 1) {
                            $sql = $modx->db->select('*', $modx->getFullTableName('document_groups'), 'document IN(' . $id . ',' . $parent . ',' . $parentOld . ')');
                            if ($modx->db->getRecordCount($sql)) {
                                $document_groups = array();
                                while ($row = $modx->db->getRow($sql)) {
                                    $document_groups[$row['document']]['groups'][] = $row['document_group'];
                                }
                                foreach ($document_groups as $key => $value) {
                                    if (($key == $parent || $key == $parentOld || $key == $id) && !in_array($role, $value['groups'])) {
                                        $json['errors'] = $_lang["error_no_privileges"];
                                    }
                                }
                                if ($json['errors']) {
                                    header('content-type: application/json');
                                    echo json_encode($json, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE);
                                    break;
                                }
                            }
                        }

                        if ($parent == 0 && $parent != $parentOld && !$modx->config['udperms_allowroot'] && $role != 1) {
                            $json['errors'] = $_lang["error_no_privileges"];
                        } else {
                            // set new parent
                            $modx->db->update(array(
                                'parent' => $parent
                            ), $modx->getFullTableName('site_content'), 'id=' . $id);
                            // set parent isfolder = 1
                            $modx->db->update(array(
                                'isfolder' => 1
                            ), $modx->getFullTableName('site_content'), 'id=' . $parent);

                            if ($parent != $parentOld) {
                                // check children docs and set parent isfolder
                                if ($modx->db->getRecordCount($modx->db->select('id', $modx->getFullTableName('site_content'), 'parent=' . $parentOld))) {
                                    $modx->db->update(array(
                                        'isfolder' => 1
                                    ), $modx->getFullTableName('site_content'), 'id=' . $parentOld);
                                } else {
                                    $modx->db->update(array(
                                        'isfolder' => 0
                                    ), $modx->getFullTableName('site_content'), 'id=' . $parentOld);
                                }
                            }

                            // set menuindex
                            if (!empty($menuindex)) {
                                $menuindex = explode(',', $menuindex);
                                foreach ($menuindex as $key => $value) {
                                    $modx->db->query('UPDATE ' . $modx->getFullTableName('site_content') . ' SET menuindex=' . $key . ' WHERE id=' . $value);
                                }
                            } else {
                                // TODO: max(*) menuindex
                            }

                            if (!$json['errors']) {
                                $json['success'] = $_lang["actioncomplete"];

                                $modx->invokeEvent('onAfterMoveDocument', [
                                    'id_document' => $id,
                                    'old_parent'  => $parentOld,
                                    'new_parent'  => $parent,
                                ]);
                            }
                        }
                    }
                }
            } else {
                $json['errors'] = $_lang["error_no_privileges"];
            }

            header('content-type: application/json');
            echo json_encode($json, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE);

            break;
        }

        case 'getLockedElements': {
            $type = isset($_REQUEST['type']) ? (int)$_REQUEST['type'] : 0;
            $id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

            $output = !!$modx->elementIsLocked($type, $id, true);

            if (!$output) {
                $docgrp = (isset($_SESSION['mgrDocgroups']) && is_array($_SESSION['mgrDocgroups'])) ? implode(',', $_SESSION['mgrDocgroups']) : '';
                $docgrp_cond = $docgrp ? ' OR dg.document_group IN (' . $docgrp . ')' : '';
                $sql = '
                    SELECT MAX(IF(1=' . $role . ' OR sc.privatemgr=0' . $docgrp_cond . ', 0, 1)) AS locked
                    FROM ' . $modx->getFullTableName('site_content') . ' AS sc 
                    LEFT JOIN ' . $modx->getFullTableName('document_groups') . ' dg ON dg.document=sc.id
                    WHERE sc.id=' . $id . ' GROUP BY sc.id';
                $sql = $modx->db->query($sql);
                if ($modx->db->getRecordCount($sql)) {
                    $row = $modx->db->getRow($sql);
                    $output = !!$row['locked'];
                }
            }
            
            echo $output;

            break;
        }
    }
}
