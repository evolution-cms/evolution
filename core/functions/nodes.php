<?php

if (!function_exists('treeSvg')) {
    /**
     * Render a Tabler SVG icon for tree UI.
     *
     * @param string $name
     * @param string $class
     * @param array $attributes
     * @return string
     */
    function treeSvg($name, $class = '', array $attributes = [])
    {
        return svg('tabler-' . $name, $class, $attributes)->toHtml();
    }
}

if (!function_exists('treeSvgData')) {
    /**
     * Encode SVG for safe data-attribute usage.
     *
     * @param string $svg
     * @return string
     */
    function treeSvgData($svg)
    {
        return 'b64:' . base64_encode($svg);
    }
}

if (!function_exists('makeHTML')) {
    /**
     * @param int $indent
     * @param int $parent
     * @param int $expandAll
     * @param string $hereid
     * @return string
     */
    function makeHTML($indent, $parent, $expandAll, $hereid = '')
    {
        $modx = evo();
        global $icons, $_style, $_lang, $opened, $opened2, $closed2, $modx_textdir;

        $output = '';

        // setup spacer
        $level = 0;
        $spacer = '<span class="indent" style="display:inline-flex;align-items:center;flex:0 0 auto;">';
        for ($i = 2; $i <= $indent; $i++) {
            $spacer .= '<i style="display:inline-block;width:1.25rem;height:1rem;flex:0 0 auto;"></i>';
            $level++;
        }
        $spacer .= '</span>';

        // manage order-by
        if (!isset($_SESSION['tree_sortby']) && !isset($_SESSION['tree_sortdir'])) {
            // This is the first startup, set default sort order
            $_SESSION['tree_sortby'] = 'menuindex';
            $_SESSION['tree_sortdir'] = 'ASC';
        }

        $sc = evo()->getDatabase()->getFullTableName('site_content');

        switch ($_SESSION['tree_sortby']) {
            case 'createdon':
            case 'editedon':
            case 'publishedon':
            case 'pub_date':
            case 'unpub_date':
                $sortby = 'CASE WHEN ' . $sc . '.' . $_SESSION['tree_sortby'] . ' IS NULL THEN 1 ELSE 0 END, ' . $sc . '.' . $_SESSION['tree_sortby'];
                break;
            default:
                $sortby = $sc . '.' . $_SESSION['tree_sortby'];
        };

        $orderBy = $sortby . ' ' . ($_SESSION['tree_sortdir'] ?? 'ASC');

        // get document groups for current user
        if (isset($_SESSION['mgrDocgroups']) && is_array($_SESSION['mgrDocgroups'])) {
            $docgrp = implode(',', $_SESSION['mgrDocgroups']);
        } else {
            $docgrp = '';
        }

        if ($modx->getConfig('tree_show_protected') !== null) {
            $showProtected = (boolean)$modx->getConfig('tree_show_protected');
        } else {
            $showProtected = false;
        }
        $mgrRole = (isset ($_SESSION['mgrRole']) && (string)$_SESSION['mgrRole'] === '1') ? '1' : '0';

        $docgrp_cond = $docgrp ? 'OR dg.document_group IN (' . $docgrp . ')' : '';
        $mgrRole = (int)$mgrRole;
        $docgrp_cond = $docgrp_cond;

        $result = \EvolutionCMS\Models\SiteContent::query()->withTrashed()->select('site_content.id', 'site_content.pagetitle', 'longtitle',
            'menutitle', 'parent', 'isfolder'
            , 'published', 'pub_date', 'unpub_date', 'richtext', 'searchable', 'cacheable'
            , 'deleted', 'type', 'template', 'templatename', 'menuindex', 'hide_from_tree', 'hidemenu', 'alias'
            , 'contentType', 'privateweb', 'privatemgr'
        )
            ->leftJoin('document_groups', 'site_content.id', '=', 'document_groups.document')
            ->leftJoin('site_templates', 'site_content.template', '=', 'site_templates.id')
            ->where('parent', (int)$parent)
            ->orderByRaw($orderBy);

        // Folder sorting gets special setup ;) Add menuindex and pagetitle
        if ($_SESSION['tree_sortby'] === 'isfolder') {
            $result = $result->orderBy('menuindex', 'ASC')->orderBy('pagetitle', 'ASC');
        }
        // orderBy('menuindex', 'ASC')->orderBy('pagetitle', 'ASC');
//'privatemgr',\DB::raw('MAX(IF(1='.$mgrRole.' OR privatemgr=0 '.$docgrp_cond.', 1, 0)) AS hasAccess'),
        if (!$showProtected) {
            if (!$docgrp) {
                if ($mgrRole != 1) {
                    $result = $result->where(function ($q) use ($mgrRole) {
                        $q->orWhere('privatemgr', 0);
                    });
                }
            } else {
                if ($mgrRole != 1) {
                    $result = $result->where(function ($q) use ($mgrRole) {
                        $q->where('privatemgr', 0)
                            ->orWhereIn('document_group', $_SESSION['mgrDocgroups']);
                    });
                }
            }
        }
        $result->groupBy(['site_content.id', 'site_content.pagetitle', 'longtitle',
            'menutitle', 'parent', 'isfolder'
            , 'published', 'pub_date', 'unpub_date', 'richtext', 'searchable', 'cacheable'
            , 'deleted', 'type', 'template', 'templatename', 'menuindex', 'hide_from_tree', 'hidemenu', 'alias'
            , 'contentType', 'privateweb', 'privatemgr']);
        $result = $result->get();


        if ($result->count() == 0) {
            $output .= '<div><a class="empty">' . $spacer . treeSvg('info-circle') . '&nbsp;<span class="empty">' . $_lang['empty_folder'] . '</span></a></div>';
        }

        if ($_SESSION['tree_nodename'] === 'default') {
            $nodeNameSource = $modx->getConfig('resource_tree_node_name');
        } else {
            $nodeNameSource = $_SESSION['tree_nodename'];
        }
        foreach ($result as $item) {
            $row = $item->toArray();
            $row['roles'] = '';
            $row['nomove'] = 0;
            $row['hasAccess'] = 0;
            if ($mgrRole == 1 || $row['privatemgr'] == 0) {
                $row['hasAccess'] = 1;
            }
            $node = '';
            $nodetitle = getNodeTitle($nodeNameSource, $row);
            $nodetitleDisplay = $nodetitle;
            $treeNodeClass = 'node';
            if (!$row['hasAccess']) {
                $treeNodeClass .= ' protected';
            }

            if ($row['deleted']) {
                $treeNodeClass .= ' deleted';
            } elseif (!$row['published']) {
                $treeNodeClass .= ' unpublished';
            } elseif ($row['hidemenu']) {
                $treeNodeClass .= ' hidemenu';
            }

            if ($row['id'] == $hereid) {
                $treeNodeClass .= ' current';
            }

            if ($row['type'] === 'reference') {
                $weblinkDisplay = '<span class="weblink-icon" style="display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:0.9rem;height:0.9rem;">' . treeSvg('link', '', ['width' => 14, 'height' => 14]) . '</span>';
            } else {
                $weblinkDisplay = '';
            }
            if ($modx_textdir) {
                $pageIdDisplay = '<small style="margin-left:0.25rem;flex:0 0 auto;">(&rlm;' . $row['id'] . ')</small>';
            } else {
                $pageIdDisplay = '<small style="margin-left:0.25rem;flex:0 0 auto;">(' . $row['id'] . ')</small>';
            }

            // Prepare displaying user-locks
            $lockedByUser = '';
            $rowLock = $modx->elementIsLocked(7, $row['id'], true);
            if ($rowLock && $modx->hasPermission('display_locks')) {
                if ($rowLock['sid'] == $modx->sid) {
                    $title = $modx->parseText(
                        $_lang['lock_element_editing']
                        , [
                            'element_type' => $_lang['lock_element_type_7'],
                            'lasthit_df' => $rowLock['lasthit_df']
                        ]
                    );
                    $lockedByUser = '<span title="' . $title . '" class="editResource" style="display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:1rem;height:1rem;margin-right:0.15rem;">' . treeSvg('eye', '', ['width' => 16, 'height' => 16]) . '</span>';
                } else {
                    $title = $modx->parseText($_lang['lock_element_locked_by'], [
                        'element_type' => $_lang['lock_element_type_7'],
                        'username' => $rowLock['username'],
                        'lasthit_df' => $rowLock['lasthit_df']
                    ]);
                    if ($modx->hasPermission('remove_locks')) {
                        $lockedByUser = '<span onclick="modx.tree.unlockElement(7, ' . $row['id'] . ', this);return false;" title="' . $title . '" class="lockedResource" style="display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:1rem;height:1rem;margin-right:0.15rem;">' . treeSvg('lock', '', ['width' => 16, 'height' => 16]) . '</span>';
                    } else {
                        $lockedByUser = '<span title="' . $title . '" class="lockedResource" style="display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:1rem;height:1rem;margin-right:0.15rem;">' . treeSvg('lock', '', ['width' => 16, 'height' => 16]) . '</span>';
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

            $treePlusnode = treeSvg('chevron-right', '', ['width' => 12, 'height' => 12]);
            $treeMinusnode = treeSvg('chevron-down', '', ['width' => 12, 'height' => 12]);
            $folderOpenIcon = treeSvg('folder-open', '', ['width' => 16, 'height' => 16]);
            $folderCloseIcon = treeSvg('folder', '', ['width' => 16, 'height' => 16]);
            $iconAttrCharset = $modx->getConfig('modx_charset');
            $treePlusnodeData = treeSvgData($treePlusnode);
            $treeMinusnodeData = treeSvgData($treeMinusnode);
            $folderOpenIconData = treeSvgData($folderOpenIcon);
            $folderCloseIconData = treeSvgData($folderCloseIcon);

            $data = [
                'id' => $row['id'],
                'pagetitle' => $row['pagetitle'],
                'longtitle' => $row['longtitle'],
                'menutitle' => $row['menutitle'],
                'parent' => $parent,
                'isfolder' => $row['isfolder'],
                'published' => $row['published'],
                'deleted' => $row['deleted'],
                'type' => $row['type'],
                'menuindex' => $row['menuindex'],
                'hide_from_tree' => $row['hide_from_tree'],
                'hidemenu' => $row['hidemenu'],
                'alias' => $row['alias'],
                'contenttype' => $row['contentType'],
                'privateweb' => $row['privateweb'],
                'privatemgr' => $row['privatemgr'],
                'hasAccess' => $row['hasAccess'],
                'template' => $row['template'],
                'nodetitle' => $nodetitle,
                'url' => $url,
                'title' => $title,
                'nodetitleDisplay' => $nodetitleDisplay,
                'weblinkDisplay' => $weblinkDisplay,
                'pageIdDisplay' => $pageIdDisplay,
                'lockedByUser' => $lockedByUser,
                'treeNodeClass' => $treeNodeClass,
                'treeNodeSelected' => $row['id'] == $hereid ? ' treeNodeSelected' : '',
                'tree_page_click' => $modx->getConfig('tree_page_click'),
                'showChildren' => 1,
                'openFolder' => 1,
                'contextmenu' => '',
                'tree_minusnode' => $treeMinusnode,
                'tree_plusnode' => $treePlusnode,
                'tree_minusnode_attr' => htmlspecialchars($treeMinusnodeData, ENT_QUOTES, $iconAttrCharset),
                'tree_plusnode_attr' => htmlspecialchars($treePlusnodeData, ENT_QUOTES, $iconAttrCharset),
                'icon_folder_open' => $folderOpenIcon,
                'icon_folder_close' => $folderCloseIcon,
                'icon_folder_open_attr' => htmlspecialchars($folderOpenIconData, ENT_QUOTES, $iconAttrCharset),
                'icon_folder_close_attr' => htmlspecialchars($folderCloseIconData, ENT_QUOTES, $iconAttrCharset),
                'spacer' => $spacer,
                'subMenuState' => '',
                'level' => $level,
                'isPrivate' => 0,
                'roles' => ($row['roles'] ? $row['roles'] : ''),
                'nomove' => 0
            ];

            $ph = $data;
            $ph['nodetitle_esc'] = addslashes($nodetitle);
            $ph['indent'] = $indent + 1;
            $ph['expandAll'] = $expandAll;
            $ph['isPrivate'] = ($row['privateweb'] || $row['privatemgr']) ? 1 : 0;

            if (!$row['isfolder']) {
                $tpl = getTplSingleNode();
                switch ($row['id']) {
                    case $modx->getConfig('site_start') :
                        $ph['nomove'] = 1;
                        $icon = treeSvg('home');
                        break;
                    case $modx->getConfig('error_page') :
                        $ph['nomove'] = 1;
                        $icon = treeSvg('info-triangle');
                        break;
                    case $modx->getConfig('site_unavailable_page') :
                        $ph['nomove'] = 1;
                        $icon = treeSvg('clock');
                        break;
                    case $modx->getConfig('unauthorized_page') :
                        $ph['nomove'] = 1;
                        $icon = treeSvg('info-circle');
                        break;
                    default:
                        if (isset($icons[$row['contentType']])) {
                            $icon = $icons[$row['contentType']];
                        } else {
                            $icon = treeSvg('file', '', ['width' => 16, 'height' => 16]);
                        }
                }
                $ph['icon'] = $icon;

                // invoke OnManagerNodePrerender event
                $prenode = $modx->invokeEvent('OnManagerNodePrerender', ['ph' => $ph]);
                if (is_array($prenode)) {
                    $phnew = [];
                    foreach ($prenode as $pnode) {
                        $pnode = unserialize($pnode);
                        foreach ($pnode as $k => $v) {
                            $phnew[$k] = $v;
                        }
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
                        $prenode = $modx->invokeEvent('OnManagerNodePrerender', [
                            'ph' => $ph,
                            'opened' => '1'
                        ]);
                        if (is_array($prenode)) {
                            $phnew = [];
                            foreach ($prenode as $pnode) {
                                $pnode = unserialize($pnode);
                                foreach ($pnode as $k => $v) {
                                    $phnew[$k] = $v;
                                }
                            }
                            if ($phnew) {
                                $ph = $phnew;
                            }
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
                        $prenode = $modx->invokeEvent('OnManagerNodePrerender', [
                            'ph' => $ph,
                            'opened' => '0'
                        ]);
                        if (is_array($prenode)) {
                            $phnew = [];
                            foreach ($prenode as $pnode) {
                                $pnode = unserialize($pnode);
                                foreach ($pnode as $k => $v) {
                                    $phnew[$k] = $v;
                                }
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

                        if ($ph['hide_from_tree'] == 1) {
                            $ph['tree_page_click'] = 3;
                            $ph['icon_node_toggle'] = '';
                            $ph['icon'] = $ph['icon_folder_close'];
                            $ph['showChildren'] = 0;
                        }

                        // invoke OnManagerNodePrerender event
                        $prenode = $modx->invokeEvent('OnManagerNodePrerender', [
                            'ph' => $ph,
                            'opened' => '1'
                        ]);
                        if (is_array($prenode)) {
                            $phnew = [];
                            foreach ($prenode as $pnode) {
                                $pnode = unserialize($pnode);
                                foreach ($pnode as $k => $v) {
                                    $phnew[$k] = $v;
                                }
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
                            $ph['hide_from_tree'] = 1;
                            $ph['icon'] = $ph['icon_folder_close'];
                            $tpl = getTplFolderNodeNotChildren();
                        }

                        if ($ph['contextmenu']) {
                            $ph['contextmenu'] = ' data-contextmenu="' . _htmlentities($ph['contextmenu']) . '"';
                        }

                        $node .= $modx->parseText($tpl, $ph);
                        if ($ph['hide_from_tree'] == 0) {
                            $node .= makeHTML($indent + 1, $row['id'], $expandAll, $hereid);
                        }
                        $node .= '</div></div>';
                    } else {
                        $closed2[] = $row['id'];
                        $ph['icon'] = $ph['icon_folder_close'];
                        $ph['icon_node_toggle'] = $ph['tree_plusnode'];
                        $ph['node_toggle'] = 0;

                        if ($ph['hide_from_tree'] == 1) {
                            $ph['tree_page_click'] = 3;
                            $ph['icon_node_toggle'] = '';
                            $ph['icon'] = $ph['icon_folder_close'];
                            $ph['showChildren'] = 0;
                        }

                        // invoke OnManagerNodePrerender event
                        $prenode = $modx->invokeEvent('OnManagerNodePrerender', [
                            'ph' => $ph,
                            'opened' => '0'
                        ]);
                        if (is_array($prenode)) {
                            $phnew = [];
                            foreach ($prenode as $pnode) {
                                $pnode = unserialize($pnode);
                                foreach ($pnode as $k => $v) {
                                    $phnew[$k] = $v;
                                }
                            }
                            $ph = (count($phnew) > 0) ? $phnew : $ph;
                        }

                        if ($ph['showChildren'] == 0) {
                            $ph['icon_node_toggle'] = '';
                            $ph['hide_from_tree'] = 1;
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

if (!function_exists('getIconInfo')) {
    /**
     * @param array $_style
     * @return array
     */
    function getIconInfo($_style)
    {
        return [
            'text/plain' => treeSvg('file-text', '', ['width' => 16, 'height' => 16]),
            'text/html' => treeSvg('file-text', '', ['width' => 16, 'height' => 16]),
            'text/xml' => treeSvg('file-code', '', ['width' => 16, 'height' => 16]),
            'text/css' => treeSvg('file-code', '', ['width' => 16, 'height' => 16]),
            'text/javascript' => treeSvg('file-code', '', ['width' => 16, 'height' => 16]),
            'image/gif' => treeSvg('photo', '', ['width' => 16, 'height' => 16]),
            'image/jpg' => treeSvg('photo', '', ['width' => 16, 'height' => 16]),
            'image/png' => treeSvg('photo', '', ['width' => 16, 'height' => 16]),
            'application/pdf' => treeSvg('file', '', ['width' => 16, 'height' => 16]),
            'application/rss+xml' => treeSvg('file-code', '', ['width' => 16, 'height' => 16]),
            'application/vnd.ms-word' => treeSvg('file', '', ['width' => 16, 'height' => 16]),
            'application/vnd.ms-excel' => treeSvg('file', '', ['width' => 16, 'height' => 16]),
        ];
    }
}

if (!function_exists('getNodeTitle')) {
    /**
     * @param string $nodeNameSource
     * @param array $row
     * @return string
     */
    function getNodeTitle($nodeNameSource, $row)
    {
        $modx = evo();

        switch ($nodeNameSource) {
            case 'menutitle':
                $nodetitle = $row['menutitle'] ? $row['menutitle'] : $row['pagetitle'];
                break;
            case 'alias':
                $nodetitle = $row['alias'] ? $row['alias'] : $row['id'];
                if (strpos($row['alias'], '.') === false) {
                    if ($row['isfolder'] != 1 || $modx->getConfig('make_folders') != 1) {
                        $nodetitle .= $modx->getConfig('friendly_url_suffix');
                    }
                }
                $nodetitle = $modx->getConfig('friendly_url_prefix') . $nodetitle;
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
        $nodetitle = $modx->getPhpCompat()->htmlspecialchars(str_replace([
            "\r\n",
            "\n",
            "\r"
        ], ' ', $nodetitle), ENT_COMPAT);

        return $nodetitle;
    }
}

if (!function_exists('isDateNode')) {
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

if (!function_exists('checkIsFolder')) {
    /**
     * @param int $parent
     * @param int $isfolder
     * @return int
     */
    function checkIsFolder($parent = 0, $isfolder = 1)
    {
        return (int)\EvolutionCMS\Models\SiteContent::query()->where('parent', $parent)->where('isfolder', $isfolder)->count();
    }
}

if (!function_exists('_htmlentities')) {
    /**
     * @param mixed $array
     * @return string
     */
    function _htmlentities($array)
    {
        $modx = evo();

        $array = json_encode($array, JSON_UNESCAPED_UNICODE);
        $array = htmlentities($array, ENT_COMPAT, $modx->getConfig('modx_charset'));

        return $array;
    }
}

if (!function_exists('getTplSingleNode')) {
    /**
     * @return string
     */
    function getTplSingleNode()
    {
        return '<div id="node[+id+]"><a class="[+treeNodeClass+]"
        onclick="modx.tree.treeAction(event,[+id+]);"
        oncontextmenu="modx.tree.showPopup(event,[+id+],\'[+nodetitle_esc+]\');"
        style="display:flex;align-items:center;gap:0.15rem;padding:0.15rem 0.25rem;line-height:1.35;width:100%;min-width:0;"
        data-id="[+id+]"
        data-title-esc="[+nodetitle_esc+]"
        data-published="[+published+]"
        data-deleted="[+deleted+]"
        data-isfolder="[+isfolder+]"
        data-href="[+url+]"
        data-private="[+isPrivate+]"
        data-roles="[+roles+]"
        data-nomove="[+nomove+]"
        data-level="[+level+]"
        data-treepageclick="[+tree_page_click+]"
        [+contextmenu+]
        >[+spacer+]<span
        class="tree-toggle-spacer"
        style="display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:0.75rem;height:0.75rem;margin-right:0.15rem;"></span><span
        class="icon"
        style="display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:1rem;height:1rem;margin-right:0.15rem;"
        onclick="modx.tree.showPopup(event,[+id+],\'[+nodetitle_esc+]\');return false;"
        oncontextmenu="this.onclick(event);return false;"
        >[+icon+]</span>[+lockedByUser+]<span
        class="title"
        title="[+title+]"
        style="display:flex;align-items:center;gap:0.15rem;flex:1 1 auto;min-width:0;"><span
        class="node-title-text"
        style="display:block;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">[+nodetitleDisplay+]</span>[+weblinkDisplay+][+pageIdDisplay+]</span></a></div>';
    }
}

if (!function_exists('getTplFolderNode')) {
    /**
     * @return string
     */
    function getTplFolderNode()
    {
        return '<div id="node[+id+]"><a class="[+treeNodeClass+]"
        onclick="modx.tree.treeAction(event,[+id+]);"
        oncontextmenu="modx.tree.showPopup(event,[+id+],\'[+nodetitle_esc+]\');"
        style="display:flex;align-items:center;gap:0.15rem;padding:0.15rem 0.25rem;line-height:1.35;width:100%;min-width:0;"
        data-id="[+id+]"
        data-title-esc="[+nodetitle_esc+]"
        data-published="[+published+]"
        data-deleted="[+deleted+]"
        data-isfolder="[+isfolder+]"
        data-href="[+url+]"
        data-private="[+isPrivate+]"
        data-roles="[+roles+]"
        data-nomove="[+nomove+]"
        data-level="[+level+]"
        data-icon-expanded="[+tree_plusnode_attr+]"
        data-icon-collapsed="[+tree_minusnode_attr+]"
        data-icon-folder-open="[+icon_folder_open_attr+]"
        data-icon-folder-close="[+icon_folder_close_attr+]"
        data-treepageclick="[+tree_page_click+]"
        data-showchildren="[+showChildren+]"
        data-openfolder="[+openFolder+]"
        data-indent="[+indent+]"
        data-expandall="[+expandAll+]"
        [+contextmenu+]
        >[+spacer+]<span
        class="tree-toggle"
        style="display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:0.75rem;height:0.75rem;margin-right:0.15rem;opacity:0.7;"
        onclick="modx.tree.toggleNode(event, [+id+]);"
        oncontextmenu="this.onclick(event);"
        >[+icon_node_toggle+]</span><span
        class="icon"
        style="display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:1rem;height:1rem;margin-right:0.15rem;"
        onclick="modx.tree.showPopup(event,[+id+],\'[+nodetitle_esc+]\');return false;"
        oncontextmenu="this.onclick(event);return false;"
        >[+icon+]</span>[+lockedByUser+]<span
        class="title"
        title="[+title+]"
        style="display:flex;align-items:center;gap:0.15rem;flex:1 1 auto;min-width:0;"><span
        class="node-title-text"
        style="display:block;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">[+nodetitleDisplay+]</span>[+weblinkDisplay+][+pageIdDisplay+]</span></a><div>';
    }
}
if (!function_exists('getTplFolderNodeNotChildren')) {
    /**
     * @return string
     */
    function getTplFolderNodeNotChildren()
    {
        return '<div id="node[+id+]"><a class="[+treeNodeClass+]"
        onclick="modx.tree.treeAction(event,[+id+]);"
        oncontextmenu="modx.tree.showPopup(event,[+id+],\'[+nodetitle_esc+]\');"
        style="display:flex;align-items:center;gap:0.15rem;padding:0.15rem 0.25rem;line-height:1.35;width:100%;min-width:0;"
        data-id="[+id+]"
        data-title-esc="[+nodetitle_esc+]"
        data-published="[+published+]"
        data-deleted="[+deleted+]"
        data-isfolder="[+isfolder+]"
        data-href="[+url+]"
        data-private="[+isPrivate+]"
        data-roles="[+roles+]"
        data-nomove="[+nomove+]"
        data-level="[+level+]"
        data-icon-expanded="[+tree_plusnode_attr+]"
        data-icon-collapsed="[+tree_minusnode_attr+]"
        data-icon-folder-open="[+icon_folder_open_attr+]"
        data-icon-folder-close="[+icon_folder_close_attr+]"
        data-treepageclick="[+tree_page_click+]"
        data-showchildren="[+showChildren+]"
        data-openfolder="[+openFolder+]"
        data-indent="[+indent+]"
        data-expandall="[+expandAll+]"
        [+contextmenu+]
        >[+spacer+]<span
        class="tree-toggle-spacer"
        style="display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:0.75rem;height:0.75rem;margin-right:0.15rem;"></span><span
        class="icon"
        style="display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:1rem;height:1rem;margin-right:0.15rem;"
        onclick="modx.tree.showPopup(event,[+id+],\'[+nodetitle_esc+]\');return false;"
        oncontextmenu="this.onclick(event);return false;"
        >[+icon+]</span>[+lockedByUser+]<span
        class="title"
        title="[+title+]"
        style="display:flex;align-items:center;gap:0.15rem;flex:1 1 auto;min-width:0;"><span
        class="node-title-text"
        style="display:block;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">[+nodetitleDisplay+]</span>[+weblinkDisplay+][+pageIdDisplay+]</span></a><div>';
    }
}
