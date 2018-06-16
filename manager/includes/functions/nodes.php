<?php

if(!function_exists('makeHTML')) {
    /**
     * @param int $indent
     * @param int $parent
     * @param int $expandAll
     * @param string $hereid
     * @return string
     */
    function makeHTML($indent, $parent, $expandAll, $hereid = '')
    {
        $modx = evolutionCMS();
        global $icons, $_style, $_lang, $opened, $opened2, $closed2, $modx_textdir;

        $output = '';

        // setup spacer
        $level = 0;
        $spacer = '<span class="indent">';
        for ($i = 2; $i <= $indent; $i++) {
            $spacer .= '<i></i>';
            $level++;
        }
        $spacer .= '</span>';

        // manage order-by
        if (!isset($_SESSION['tree_sortby']) && !isset($_SESSION['tree_sortdir'])) {
            // This is the first startup, set default sort order
            $_SESSION['tree_sortby'] = 'menuindex';
            $_SESSION['tree_sortdir'] = 'ASC';
        }

        switch ($_SESSION['tree_sortby']) {
            case 'createdon':
            case 'editedon':
            case 'publishedon':
            case 'pub_date':
            case 'unpub_date':
                $sortby = sprintf('CASE WHEN %s IS NULL THEN 1 ELSE 0 END, %s', 'sc.' . $_SESSION['tree_sortby'],
                    'sc.' . $_SESSION['tree_sortby']);
                break;
            default:
                $sortby = 'sc.' . $_SESSION['tree_sortby'];
        };

        $orderby = $modx->getDatabase()->escape($sortby . ' ' . $_SESSION['tree_sortdir']);

        // Folder sorting gets special setup ;) Add menuindex and pagetitle
        if ($_SESSION['tree_sortby'] == 'isfolder') {
            $orderby .= ', menuindex ASC, pagetitle';
        }

        $tblsc = $modx->getDatabase()->getFullTableName('site_content');
        $tbldg = $modx->getDatabase()->getFullTableName('document_groups');
        $tblst = $modx->getDatabase()->getFullTableName('site_templates');
        // get document groups for current user
        $docgrp = (isset($_SESSION['mgrDocgroups']) && is_array($_SESSION['mgrDocgroups'])) ? implode(',',
            $_SESSION['mgrDocgroups']) : '';
        $showProtected = false;
        if (isset ($modx->config['tree_show_protected'])) {
            $showProtected = (boolean)$modx->config['tree_show_protected'];
        }
        $mgrRole = (isset ($_SESSION['mgrRole']) && (string)$_SESSION['mgrRole'] === '1') ? '1' : '0';
        if ($showProtected == false) {
            $access = "AND (1={$mgrRole} OR sc.privatemgr=0" . (!$docgrp ? ')' : " OR dg.document_group IN ({$docgrp}))");
        } else {
            $access = '';
        }
        $docgrp_cond = $docgrp ? "OR dg.document_group IN ({$docgrp})" : '';
        $field = "DISTINCT sc.id, pagetitle, longtitle, menutitle, parent, isfolder, published, pub_date, unpub_date, richtext, searchable, cacheable, deleted, type, template, templatename, menuindex, donthit, hidemenu, alias, contentType, privateweb, privatemgr,
        MAX(IF(1={$mgrRole} OR sc.privatemgr=0 {$docgrp_cond}, 1, 0)) AS hasAccess, GROUP_CONCAT(document_group SEPARATOR ',') AS roles";
        $from = "{$tblsc} AS sc LEFT JOIN {$tbldg} dg on dg.document = sc.id LEFT JOIN {$tblst} st on st.id = sc.template";
        $where = "(parent={$parent}) {$access} GROUP BY sc.id";
        $result = $modx->getDatabase()->select($field, $from, $where, $orderby);
        if ($modx->getDatabase()->getRecordCount($result) == 0) {
            $output .= sprintf('<div><a class="empty">%s%s&nbsp;<span class="empty">%s</span></a></div>', $spacer,
                $_style['tree_deletedpage'], $_lang['empty_folder']);
        }

        $nodeNameSource = $_SESSION['tree_nodename'] == 'default' ? $modx->config['resource_tree_node_name'] : $_SESSION['tree_nodename'];

        while ($row = $modx->getDatabase()->getRow($result)) {
            $node = '';
            $nodetitle = getNodeTitle($nodeNameSource, $row);
            $nodetitleDisplay = $nodetitle;
            $treeNodeClass = 'node';
            $treeNodeClass .= $row['hasAccess'] == 0 ? ' protected' : '';

            if ($row['deleted'] == 1) {
                $treeNodeClass .= ' deleted';
            } elseif ($row['published'] == 0) {
                $treeNodeClass .= ' unpublished';
            } elseif ($row['hidemenu'] == 1) {
                $treeNodeClass .= ' hidemenu';
            }

            if ($row['id'] == $hereid) {
                $treeNodeClass .= ' current';
            }

            $weblinkDisplay = $row['type'] == 'reference' ? sprintf('&nbsp;%s', $_style['tree_linkgo']) : '';
            $pageIdDisplay = '<small>(' . ($modx_textdir ? '&rlm;' : '') . $row['id'] . ')</small>';

            // Prepare displaying user-locks
            $lockedByUser = '';
            $rowLock = $modx->elementIsLocked(7, $row['id'], true);
            if ($rowLock && $modx->hasPermission('display_locks')) {
                if ($rowLock['sid'] == $modx->sid) {
                    $title = $modx->parseText($_lang["lock_element_editing"], array(
                        'element_type' => $_lang["lock_element_type_7"],
                        'lasthit_df'   => $rowLock['lasthit_df']
                    ));
                    $lockedByUser = '<span title="' . $title . '" class="editResource">' . $_style['tree_preview_resource'] . '</span>';
                } else {
                    $title = $modx->parseText($_lang["lock_element_locked_by"], array(
                        'element_type' => $_lang["lock_element_type_7"],
                        'username'     => $rowLock['username'],
                        'lasthit_df'   => $rowLock['lasthit_df']
                    ));
                    if ($modx->hasPermission('remove_locks')) {
                        $lockedByUser = '<span onclick="modx.tree.unlockElement(7, ' . $row['id'] . ', this);return false;" title="' . $title . '" class="lockedResource">' . $_style['icons_secured'] . '</span>';
                    } else {
                        $lockedByUser = '<span title="' . $title . '" class="lockedResource">' . $_style['icons_secured'] . '</span>';
                    }
                }
            }

            $url = $modx->makeUrl($row['id']);

            $title = '';
            if (isDateNode($nodeNameSource)) {
                $title = $_lang['pagetitle'] . ': ' . $row['pagetitle'] . '[+lf+]';
            }
            $title .= $_lang['id'] . ': ' . $row['id'];
            $title .= '[+lf+]' . $_lang['resource_opt_menu_title'] . ': ' . $row['menutitle'];
            $title .= '[+lf+]' . $_lang['resource_opt_menu_index'] . ': ' . $row['menuindex'];
            $title .= '[+lf+]' . $_lang['alias'] . ': ' . (!empty($row['alias']) ? $row['alias'] : '-');
            $title .= '[+lf+]' . $_lang['template'] . ': ' . $row['templatename'];
            $title .= '[+lf+]' . $_lang['publish_date'] . ': ' . $modx->toDateFormat($row['pub_date']);
            $title .= '[+lf+]' . $_lang['unpublish_date'] . ': ' . $modx->toDateFormat($row['unpub_date']);
            $title .= '[+lf+]' . $_lang['page_data_web_access'] . ': ' . ($row['privateweb'] ? $_lang['private'] : $_lang['public']);
            $title .= '[+lf+]' . $_lang['page_data_mgr_access'] . ': ' . ($row['privatemgr'] ? $_lang['private'] : $_lang['public']);
            $title .= '[+lf+]' . $_lang['resource_opt_richtext'] . ': ' . ($row['richtext'] == 0 ? $_lang['no'] : $_lang['yes']);
            $title .= '[+lf+]' . $_lang['page_data_searchable'] . ': ' . ($row['searchable'] == 0 ? $_lang['no'] : $_lang['yes']);
            $title .= '[+lf+]' . $_lang['page_data_cacheable'] . ': ' . ($row['cacheable'] == 0 ? $_lang['no'] : $_lang['yes']);
            $title = $modx->getPhpCompat()->htmlspecialchars($title);
            $title = str_replace('[+lf+]', ' &#13;', $title);   // replace line-breaks with empty space as fall-back

            $data = array(
                'id'               => $row['id'],
                'pagetitle'        => $row['pagetitle'],
                'longtitle'        => $row['longtitle'],
                'menutitle'        => $row['menutitle'],
                'parent'           => $parent,
                'isfolder'         => $row['isfolder'],
                'published'        => $row['published'],
                'deleted'          => $row['deleted'],
                'type'             => $row['type'],
                'menuindex'        => $row['menuindex'],
                'donthit'          => $row['donthit'],
                'hidemenu'         => $row['hidemenu'],
                'alias'            => $row['alias'],
                'contenttype'      => $row['contentType'],
                'privateweb'       => $row['privateweb'],
                'privatemgr'       => $row['privatemgr'],
                'hasAccess'        => $row['hasAccess'],
                'template'         => $row['template'],
                'nodetitle'        => $nodetitle,
                'url'              => $url,
                'title'            => $title,
                'nodetitleDisplay' => $nodetitleDisplay,
                'weblinkDisplay'   => $weblinkDisplay,
                'pageIdDisplay'    => $pageIdDisplay,
                'lockedByUser'     => $lockedByUser,
                'treeNodeClass'    => $treeNodeClass,
                'treeNodeSelected' => $row['id'] == $hereid ? ' treeNodeSelected' : '',
                'tree_page_click'  => $modx->config['tree_page_click'],
                'showChildren'     => 1,
                'openFolder'       => 1,
                'contextmenu'      => '',
                'tree_minusnode'   => $_style['tree_minusnode'],
                'tree_plusnode'    => $_style['tree_plusnode'],
                'spacer'           => $spacer,
                'subMenuState'     => '',
                'level'            => $level,
                'isPrivate'        => 0,
                'roles'            => ($row['roles'] ? $row['roles'] : '')
            );

            $ph = $data;
            $ph['nodetitle_esc'] = addslashes($nodetitle);
            $ph['indent'] = $indent + 1;
            $ph['expandAll'] = $expandAll;
            $ph['isPrivate'] = ($row['privateweb'] || $row['privatemgr']) ? 1 : 0;

            if (!$row['isfolder']) {
                $tpl = getTplSingleNode();
                switch ($row['id']) {
                    case $modx->config['site_start']            :
                        $icon = $_style['tree_page_home'];
                        break;
                    case $modx->config['error_page']            :
                        $icon = $_style['tree_page_404'];
                        break;
                    case $modx->config['site_unavailable_page'] :
                        $icon = $_style['tree_page_hourglass'];
                        break;
                    case $modx->config['unauthorized_page']     :
                        $icon = $_style['tree_page_info'];
                        break;
                    default:
                        if (isset($icons[$row['contentType']])) {
                            $icon = $icons[$row['contentType']];
                        } else {
                            $icon = $_style['tree_page'];
                        }
                }
                $ph['icon'] = $icon;

                // invoke OnManagerNodePrerender event
                $prenode = $modx->invokeEvent("OnManagerNodePrerender", array('ph' => $ph));
                if (is_array($prenode)) {
                    $phnew = array();
                    foreach ($prenode as $pnode) {
                        $phnew = array_merge($phnew, unserialize($pnode));
                    }
                    $ph = (count($phnew) > 0) ? $phnew : $ph;
                }

                if ($ph['contextmenu']) {
                    $ph['contextmenu'] = ' data-contextmenu="' . _htmlentities($ph['contextmenu']) . '"';
                }

                if ($_SESSION['tree_show_only_folders']) {
                    if ($row['parent'] == 0) {
                        $node .= $modx->parseText($tpl, $ph);
                    } else {
                        $node .= '';
                    }
                } else {
                    $node .= $modx->parseText($tpl, $ph);
                }

            } else {
                $ph['icon_folder_open'] = $_style['tree_folderopen_new'];
                $ph['icon_folder_close'] = $_style['tree_folder_new'];

                if ($_SESSION['tree_show_only_folders']) {
                    $tpl = getTplFolderNodeNotChildren();
                    $checkFolders = checkIsFolder($row['id'], 1) ? 1 : 0; // folders
                    $checkDocs = checkIsFolder($row['id'], 0) ? 1 : 0; // no folders
                    $ph['tree_page_click'] = 3;

                    // expandAll: two type for partial expansion
                    if ($expandAll == 1 || ($expandAll == 2 && in_array($row['id'], $opened))) {
                        if ($expandAll == 1) {
                            $opened2[] = $row['id'];
                        }
                        $ph['icon'] = $ph['icon_folder_open'];
                        $ph['icon_node_toggle'] = $ph['tree_minusnode'];
                        $ph['node_toggle'] = 1;
                        $ph['subMenuState'] = ' open';

                        if (($checkDocs && !$checkFolders) || (!$checkDocs && !$checkFolders)) {
                            $ph['showChildren'] = 1;
                            $ph['icon_node_toggle'] = '';
                            $ph['icon'] = $ph['icon_folder_close'];
                        } elseif (!$checkDocs && $checkFolders) {
                            $ph['showChildren'] = 0;
                            $ph['openFolder'] = 2;
                        } else {
                            $ph['openFolder'] = 2;
                        }

                        // invoke OnManagerNodePrerender event
                        $prenode = $modx->invokeEvent("OnManagerNodePrerender", array(
                            'ph'     => $ph,
                            'opened' => '1'
                        ));
                        if (is_array($prenode)) {
                            $phnew = array();
                            foreach ($prenode as $pnode) {
                                $phnew = array_merge($phnew, unserialize($pnode));
                            }
                            $ph = (count($phnew) > 0) ? $phnew : $ph;
                        }

                        if ($ph['contextmenu']) {
                            $ph['contextmenu'] = ' data-contextmenu="' . _htmlentities($ph['contextmenu']) . '"';
                        }

                        $node .= $modx->parseText($tpl, $ph);
                        if ($checkFolders) {
                            $node .= makeHTML($indent + 1, $row['id'], $expandAll, $hereid);
                        }
                        $node .= '</div></div>';
                    } else {
                        $closed2[] = $row['id'];
                        $ph['icon'] = $ph['icon_folder_close'];
                        $ph['icon_node_toggle'] = $ph['tree_plusnode'];
                        $ph['node_toggle'] = 0;

                        if (($checkDocs && !$checkFolders) || (!$checkDocs && !$checkFolders)) {
                            $ph['showChildren'] = 1;
                            $ph['icon_node_toggle'] = '';
                        } elseif (!$checkDocs && $checkFolders) {
                            $ph['showChildren'] = 0;
                            $ph['openFolder'] = 2;
                        } else {
                            $ph['openFolder'] = 2;
                        }

                        // invoke OnManagerNodePrerender event
                        $prenode = $modx->invokeEvent("OnManagerNodePrerender", array(
                            'ph'     => $ph,
                            'opened' => '0'
                        ));
                        if (is_array($prenode)) {
                            $phnew = array();
                            foreach ($prenode as $pnode) {
                                $phnew = array_merge($phnew, unserialize($pnode));
                            }
                            $ph = (count($phnew) > 0) ? $phnew : $ph;
                        }

                        if ($ph['contextmenu']) {
                            $ph['contextmenu'] = ' data-contextmenu="' . _htmlentities($ph['contextmenu']) . '"';
                        }

                        $node .= $modx->parseText($tpl, $ph);
                        $node .= '</div></div>';
                    }
                } else {
                    $tpl = getTplFolderNode();
                    // expandAll: two type for partial expansion
                    if ($expandAll == 1 || ($expandAll == 2 && in_array($row['id'], $opened))) {
                        if ($expandAll == 1) {
                            $opened2[] = $row['id'];
                        }
                        $ph['icon'] = $ph['icon_folder_open'];
                        $ph['icon_node_toggle'] = $ph['tree_minusnode'];
                        $ph['node_toggle'] = 1;
                        $ph['subMenuState'] = ' open';

                        if ($ph['donthit'] == 1) {
                            $ph['tree_page_click'] = 3;
                            $ph['icon_node_toggle'] = '';
                            $ph['icon'] = $ph['icon_folder_close'];
                            $ph['showChildren'] = 0;
                        }

                        // invoke OnManagerNodePrerender event
                        $prenode = $modx->invokeEvent("OnManagerNodePrerender", array(
                            'ph'     => $ph,
                            'opened' => '1'
                        ));
                        if (is_array($prenode)) {
                            $phnew = array();
                            foreach ($prenode as $pnode) {
                                $phnew = array_merge($phnew, unserialize($pnode));
                            }
                            $ph = (count($phnew) > 0) ? $phnew : $ph;
                            if ($ph['showChildren'] == 0) {
                                unset($opened2[$row['id']]);
                                $ph['node_toggle'] = 0;
                                $ph['subMenuState'] = '';
                            }
                        }

                        if ($ph['showChildren'] == 0) {
                            $ph['icon_node_toggle'] = '';
                            $ph['donthit'] = 1;
                            $ph['icon'] = $ph['icon_folder_close'];
                            $tpl = getTplFolderNodeNotChildren();
                        }

                        if ($ph['contextmenu']) {
                            $ph['contextmenu'] = ' data-contextmenu="' . _htmlentities($ph['contextmenu']) . '"';
                        }

                        $node .= $modx->parseText($tpl, $ph);
                        if ($ph['donthit'] == 0) {
                            $node .= makeHTML($indent + 1, $row['id'], $expandAll, $hereid);
                        }
                        $node .= '</div></div>';
                    } else {
                        $closed2[] = $row['id'];
                        $ph['icon'] = $ph['icon_folder_close'];
                        $ph['icon_node_toggle'] = $ph['tree_plusnode'];
                        $ph['node_toggle'] = 0;

                        if ($ph['donthit'] == 1) {
                            $ph['tree_page_click'] = 3;
                            $ph['icon_node_toggle'] = '';
                            $ph['icon'] = $ph['icon_folder_close'];
                            $ph['showChildren'] = 0;
                        }

                        // invoke OnManagerNodePrerender event
                        $prenode = $modx->invokeEvent("OnManagerNodePrerender", array(
                            'ph'     => $ph,
                            'opened' => '0'
                        ));
                        if (is_array($prenode)) {
                            $phnew = array();
                            foreach ($prenode as $pnode) {
                                $phnew = array_merge($phnew, unserialize($pnode));
                            }
                            $ph = (count($phnew) > 0) ? $phnew : $ph;
                        }

                        if ($ph['showChildren'] == 0) {
                            $ph['icon_node_toggle'] = '';
                            $ph['donthit'] = 1;
                            $ph['icon'] = $ph['icon_folder_close'];
                            $tpl = getTplFolderNodeNotChildren();
                        }

                        if ($ph['contextmenu']) {
                            $ph['contextmenu'] = ' data-contextmenu="' . _htmlentities($ph['contextmenu']) . '"';
                        }

                        $node .= $modx->parseText($tpl, $ph);
                        $node .= '</div></div>';
                    }
                }
            }

            // invoke OnManagerNodeRender event
            $data['node'] = $node;
            $evtOut = $modx->invokeEvent('OnManagerNodeRender', $data);
            if (is_array($evtOut)) {
                $evtOut = implode("\n", $evtOut);
            }
            if ($evtOut != '') {
                $node = trim($evtOut);
            }

            $output .= $node;
        }

        return $output;
    }
}

if(!function_exists('getIconInfo')) {
    /**
     * @param array $_style
     * @return array
     */
    function getIconInfo($_style)
    {
        if (!isset($_style['tree_page_gif'])) {
            $_style['tree_page_gif'] = $_style['tree_page'];
        }
        if (!isset($_style['tree_page_jpg'])) {
            $_style['tree_page_jpg'] = $_style['tree_page'];
        }
        if (!isset($_style['tree_page_png'])) {
            $_style['tree_page_png'] = $_style['tree_page'];
        }

        return array(
            'text/html'                => $_style['tree_page_html'],
            'text/plain'               => $_style['tree_page'],
            'text/xml'                 => $_style['tree_page_xml'],
            'text/css'                 => $_style['tree_page_css'],
            'text/javascript'          => $_style['tree_page_js'],
            'application/rss+xml'      => $_style['tree_page_rss'],
            'application/pdf'          => $_style['tree_page_pdf'],
            'application/vnd.ms-word'  => $_style['tree_page_word'],
            'application/vnd.ms-excel' => $_style['tree_page_excel'],
            'image/gif'                => $_style['tree_page_gif'],
            'image/jpg'                => $_style['tree_page_jpg'],
            'image/png'                => $_style['tree_page_png']
        );
    }
}

if(!function_exists('getNodeTitle')) {
    /**
     * @param string $nodeNameSource
     * @param array $row
     * @return string
     */
    function getNodeTitle($nodeNameSource, $row)
    {
        $modx = evolutionCMS();

        switch ($nodeNameSource) {
            case 'menutitle':
                $nodetitle = $row['menutitle'] ? $row['menutitle'] : $row['pagetitle'];
                break;
            case 'alias':
                $nodetitle = $row['alias'] ? $row['alias'] : $row['id'];
                if (strpos($row['alias'], '.') === false) {
                    if ($row['isfolder'] != 1 || $modx->config['make_folders'] != 1) {
                        $nodetitle .= $modx->config['friendly_url_suffix'];
                    }
                }
                $nodetitle = $modx->config['friendly_url_prefix'] . $nodetitle;
                break;
            case 'pagetitle':
                $nodetitle = $row['pagetitle'];
                break;
            case 'longtitle':
                $nodetitle = $row['longtitle'] ? $row['longtitle'] : $row['pagetitle'];
                break;
            case 'createdon':
            case 'editedon':
            case 'publishedon':
            case 'pub_date':
            case 'unpub_date':
                $doc = $modx->getDocumentObject('id', $row['id']);
                $date = $doc[$nodeNameSource];
                if (!empty($date)) {
                    $nodetitle = $modx->toDateFormat($date);
                } else {
                    $nodetitle = '- - -';
                }
                break;
            default:
                $nodetitle = $row['pagetitle'];
        }
        $nodetitle = $modx->getPhpCompat()->htmlspecialchars(str_replace(array(
            "\r\n",
            "\n",
            "\r"
        ), ' ', $nodetitle), ENT_COMPAT);

        return $nodetitle;
    }
}

if(!function_exists('isDateNode')) {
    /**
     * @param string $nodeNameSource
     * @return bool
     */
    function isDateNode($nodeNameSource)
    {
        switch ($nodeNameSource) {
            case 'createdon':
            case 'editedon':
            case 'publishedon':
            case 'pub_date':
            case 'unpub_date':
                return true;
            default:
                return false;
        }
    }
}

if(!function_exists('checkIsFolder')) {
    /**
     * @param int $parent
     * @param int $isfolder
     * @return int
     */
    function checkIsFolder($parent = 0, $isfolder = 1)
    {
        $modx = evolutionCMS();

        return (int)$modx->getDatabase()->getValue($modx->getDatabase()->query('SELECT count(*) FROM ' . $modx->getDatabase()->getFullTableName('site_content') . ' WHERE parent=' . $parent . ' AND isfolder=' . $isfolder . ' '));
    }
}

if(!function_exists('_htmlentities')) {
    /**
     * @param mixed $array
     * @return string
     */
    function _htmlentities($array)
    {
        $modx = evolutionCMS();

        $array = json_encode($array, JSON_UNESCAPED_UNICODE);
        $array = htmlentities($array, ENT_COMPAT, $modx->config['modx_charset']);

        return $array;
    }
}

if(!function_exists('getTplSingleNode')) {
    /**
     * @return string
     */
    function getTplSingleNode()
    {
        return '<div id="node[+id+]"><a class="[+treeNodeClass+]"
        onclick="modx.tree.treeAction(event,[+id+]);"
        oncontextmenu="modx.tree.showPopup(event,[+id+],\'[+nodetitle_esc+]\');"
        data-id="[+id+]"
        data-title-esc="[+nodetitle_esc+]"
        data-published="[+published+]"
        data-deleted="[+deleted+]"
        data-isfolder="[+isfolder+]"
        data-href="[+url+]"
        data-private="[+isPrivate+]"
        data-roles="[+roles+]"
        data-level="[+level+]"
        data-treepageclick="[+tree_page_click+]"
        [+contextmenu+]
        >[+spacer+]<span
        class="icon"
        onclick="modx.tree.showPopup(event,[+id+],\'[+nodetitle_esc+]\');return false;"
        oncontextmenu="this.onclick(event);return false;"
        >[+icon+]</span>[+lockedByUser+]<span
        class="title"
        title="[+title+]">[+nodetitleDisplay+][+weblinkDisplay+]</span>[+pageIdDisplay+]</a></div>';
    }
}

if(!function_exists('getTplFolderNode')) {
    /**
     * @return string
     */
    function getTplFolderNode()
    {
        return '<div id="node[+id+]"><a class="[+treeNodeClass+]"
        onclick="modx.tree.treeAction(event,[+id+]);"
        oncontextmenu="modx.tree.showPopup(event,[+id+],\'[+nodetitle_esc+]\');"
        data-id="[+id+]"
        data-title-esc="[+nodetitle_esc+]"
        data-published="[+published+]"
        data-deleted="[+deleted+]"
        data-isfolder="[+isfolder+]"
        data-href="[+url+]"
        data-private="[+isPrivate+]"
        data-roles="[+roles+]"
        data-level="[+level+]"
        data-icon-expanded="[+tree_plusnode+]"
        data-icon-collapsed="[+tree_minusnode+]"
        data-icon-folder-open="[+icon_folder_open+]"
        data-icon-folder-close="[+icon_folder_close+]"
        data-treepageclick="[+tree_page_click+]"
        data-showchildren="[+showChildren+]"
        data-openfolder="[+openFolder+]"
        data-indent="[+indent+]"
        data-expandall="[+expandAll+]"
        [+contextmenu+]
        >[+spacer+]<span
        class="toggle"
        onclick="modx.tree.toggleNode(event, [+id+]);"
        oncontextmenu="this.onclick(event);"
        >[+icon_node_toggle+]</span><span
        class="icon"
        onclick="modx.tree.showPopup(event,[+id+],\'[+nodetitle_esc+]\');return false;"
        oncontextmenu="this.onclick(event);return false;"
        >[+icon+]</span>[+lockedByUser+]<span
        class="title"
        title="[+title+]">[+nodetitleDisplay+][+weblinkDisplay+]</span>[+pageIdDisplay+]</a><div>';
    }
}
if(!function_exists('getTplFolderNodeNotChildren')) {
    /**
     * @return string
     */
    function getTplFolderNodeNotChildren()
    {
        return '<div id="node[+id+]"><a class="[+treeNodeClass+]"
        onclick="modx.tree.treeAction(event,[+id+]);"
        oncontextmenu="modx.tree.showPopup(event,[+id+],\'[+nodetitle_esc+]\');"
        data-id="[+id+]"
        data-title-esc="[+nodetitle_esc+]"
        data-published="[+published+]"
        data-deleted="[+deleted+]"
        data-isfolder="[+isfolder+]"
        data-href="[+url+]"
        data-private="[+isPrivate+]"
        data-roles="[+roles+]"
        data-level="[+level+]"
        data-icon-expanded="[+tree_plusnode+]"
        data-icon-collapsed="[+tree_minusnode+]"
        data-icon-folder-open="[+icon_folder_open+]"
        data-icon-folder-close="[+icon_folder_close+]"
        data-treepageclick="[+tree_page_click+]"
        data-showchildren="[+showChildren+]"
        data-openfolder="[+openFolder+]"
        data-indent="[+indent+]"
        data-expandall="[+expandAll+]"
        [+contextmenu+]
        >[+spacer+]<span
        class="icon"
        onclick="modx.tree.showPopup(event,[+id+],\'[+nodetitle_esc+]\');return false;"
        oncontextmenu="this.onclick(event);return false;"
        >[+icon+]</span>[+lockedByUser+]<span
        class="title"
        title="[+title+]">[+nodetitleDisplay+][+weblinkDisplay+]</span>[+pageIdDisplay+]</a><div>';
    }
}
