<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('save_document')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

// preprocess POST values
$id = is_numeric($_POST['id']) ? $_POST['id'] : '';
$introtext = $modx->getDatabase()->escape($_POST['introtext']);
$content = $modx->getDatabase()->escape($_POST['ta']);
$pagetitle = $modx->getDatabase()->escape($_POST['pagetitle']);
$description = $modx->getDatabase()->escape($_POST['description']);
$alias = $modx->getDatabase()->escape($_POST['alias']);
$link_attributes = $modx->getDatabase()->escape($_POST['link_attributes']);
$isfolder = $_POST['isfolder'];
$richtext = $_POST['richtext'];
$published = $_POST['published'];
$parent = $_POST['parent'] != '' ? $_POST['parent'] : 0;
$template = $_POST['template'];
$menuindex = !empty($_POST['menuindex']) ? $_POST['menuindex'] : 0;
$searchable = $_POST['searchable'];
$cacheable = $_POST['cacheable'];
$syncsite = $_POST['syncsite'];
$pub_date = $_POST['pub_date'];
$unpub_date = $_POST['unpub_date'];
$document_groups = (isset($_POST['chkalldocs']) && $_POST['chkalldocs'] == 'on') ? array() : $_POST['docgroups'];
$type = $_POST['type'];
$contentType = $modx->getDatabase()->escape($_POST['contentType']);
$contentdispo = (int) $_POST['content_dispo'];
$longtitle = $modx->getDatabase()->escape($_POST['longtitle']);
$donthit = (int) $_POST['donthit'];
$menutitle = $modx->getDatabase()->escape($_POST['menutitle']);
$hidemenu = (int) $_POST['hidemenu'];
$aliasvisible = $_POST['alias_visible'];

/************* webber ********/
$sd = isset($_POST['dir']) ? '&dir='.$_POST['dir'] : '&dir=DESC';
$sb = isset($_POST['sort']) ? '&sort='.$_POST['sort'] : '&sort=pub_date';
$pg = isset($_POST['page']) ? '&page='.(int) $_POST['page'] : '';
$add_path = $sd.$sb.$pg;



$no_esc_pagetitle = $_POST['pagetitle'];
if (trim($no_esc_pagetitle) == "") {
    if ($type == "reference") {
        $no_esc_pagetitle = $pagetitle = $_lang['untitled_weblink'];
    } else {
        $no_esc_pagetitle = $pagetitle = $_lang['untitled_resource'];
    }
}

// get table names
$tbl_document_groups            = $modx->getDatabase()->getFullTableName('document_groups');
$tbl_documentgroup_names        = $modx->getDatabase()->getFullTableName('documentgroup_names');
$tbl_member_groups              = $modx->getDatabase()->getFullTableName('member_groups');
$tbl_membergroup_access         = $modx->getDatabase()->getFullTableName('membergroup_access');
$tbl_site_content               = $modx->getDatabase()->getFullTableName('site_content');
$tbl_site_tmplvar_access        = $modx->getDatabase()->getFullTableName('site_tmplvar_access');
$tbl_site_tmplvar_contentvalues = $modx->getDatabase()->getFullTableName('site_tmplvar_contentvalues');
$tbl_site_tmplvar_templates     = $modx->getDatabase()->getFullTableName('site_tmplvar_templates');
$tbl_site_tmplvars              = $modx->getDatabase()->getFullTableName('site_tmplvars');

$actionToTake = "new";
if ($_POST['mode'] == '73' || $_POST['mode'] == '27') {
    $actionToTake = "edit";
}

// friendly url alias checks
if ($friendly_urls) {
    // auto assign alias
    if (!$alias && $automatic_alias) {
        $alias = strtolower($modx->stripAlias(trim($pagetitle)));
        if (!$allow_duplicate_alias) {
            if ($modx->getDatabase()->getValue($modx->getDatabase()->select('COUNT(id)', $tbl_site_content, "id<>'$id' AND alias='$alias'")) != 0) {
                $cnt = 1;
                $tempAlias = $alias;
                while ($modx->getDatabase()->getValue($modx->getDatabase()->select('COUNT(id)', $tbl_site_content, "id<>'$id' AND alias='$tempAlias'")) != 0) {
                    $tempAlias = $alias;
                    $tempAlias .= $cnt;
                    $cnt++;
                }
                $alias = $tempAlias;
            }
        } else {
            if ($modx->getDatabase()->getValue($modx->getDatabase()->select('COUNT(id)', $tbl_site_content, "id<>'$id' AND parent=$parent AND alias='$alias'")) != 0) {
                $cnt = 1;
                $tempAlias = $alias;
                while ($modx->getDatabase()->getValue($modx->getDatabase()->select('COUNT(id)', $tbl_site_content, "id<>'$id' AND parent=$parent AND alias='$tempAlias'")) != 0) {
                    $tempAlias = $alias;
                    $tempAlias .= $cnt;
                    $cnt++;
                }
                $alias = $tempAlias;
            }
        }
    }

    // check for duplicate alias name if not allowed
    elseif ($alias && !$allow_duplicate_alias) {
        $alias = $modx->stripAlias($alias);
        if ($use_alias_path) {
            // only check for duplicates on the same level if alias_path is on
            $docid = $modx->getDatabase()->getValue($modx->getDatabase()->select('id', $tbl_site_content, "id<>'$id' AND alias='$alias' AND parent=$parent", '', 1));
        } else {
            $docid = $modx->getDatabase()->getValue($modx->getDatabase()->select('id', $tbl_site_content, "id<>'$id' AND alias='$alias'", '', 1));
        }
        if ($docid > 0) {
            if ($actionToTake == 'edit') {
                $modx->getManagerApi()->saveFormValues(27);
                $modx->webAlertAndQuit(sprintf($_lang["duplicate_alias_found"], $docid, $alias), "index.php?a=27&id={$id}");
            } else {
                $modx->getManagerApi()->saveFormValues(4);
                $modx->webAlertAndQuit(sprintf($_lang["duplicate_alias_found"], $docid, $alias), "index.php?a=4");
            }
        }
    }

    // strip alias of special characters
    elseif ($alias) {
        $alias = $modx->stripAlias($alias);
        //webber
        $docid = $modx->getDatabase()->getValue($modx->getDatabase()->select('id', $tbl_site_content, "id<>'$id' AND alias='$alias' AND parent=$parent", '', 1));
        if ($docid > 0) {
            if ($actionToTake == 'edit') {
                $modx->getManagerApi()->saveFormValues(27);
                $modx->webAlertAndQuit(sprintf($_lang["duplicate_alias_found"], $docid, $alias), "index.php?a=27&id={$id}");
            } else {
                $modx->getManagerApi()->saveFormValues(4);
                $modx->webAlertAndQuit(sprintf($_lang["duplicate_alias_found"], $docid, $alias), "index.php?a=4");
            }
        }
        //end webber
    }
}
elseif ($alias) {
    $alias = $modx->stripAlias($alias);
}

// determine published status
$currentdate = $_SERVER['REQUEST_TIME'] + $modx->config['server_offset_time'];

if (empty ($pub_date)) {
    $pub_date = 0;
} else {
    $pub_date = $modx->toTimeStamp($pub_date);

    if ($pub_date < $currentdate) {
        $published = 1;
    }
    elseif ($pub_date > $currentdate) {
        $published = 0;
    }
}

if (empty ($unpub_date)) {
    $unpub_date = 0;
} else {
    $unpub_date = $modx->toTimeStamp($unpub_date);
    if ($unpub_date < $currentdate) {
        $published = 0;
    }
}

// get document groups for current user
$tmplvars = array();
if ($_SESSION['mgrDocgroups']) {
    $docgrp = implode(",", $_SESSION['mgrDocgroups']);
}

// ensure that user has not made this document inaccessible to themselves
if ($_SESSION['mgrRole'] != 1 && is_array($document_groups)) {
    $document_group_list = implode(',', $document_groups);
    $document_group_list = implode(',', array_filter(explode(',', $document_group_list), 'is_numeric'));
    if (!empty($document_group_list)) {
        $rs = $modx->getDatabase()->select('COUNT(mg.id)', "{$tbl_membergroup_access} AS mga, {$tbl_member_groups} AS mg", "mga.membergroup = mg.user_group AND mga.documentgroup IN({$document_group_list}) AND mg.member = {$_SESSION['mgrInternalKey']}");
        $count = $modx->getDatabase()->getValue($rs);
        if ($count == 0) {
            if ($actionToTake == 'edit') {
                $modx->getManagerApi()->saveFormValues(27);
                $modx->webAlertAndQuit(sprintf($_lang["resource_permissions_error"]), "index.php?a=27&id={$id}");
            } else {
                $modx->getManagerApi()->saveFormValues(4);
                $modx->webAlertAndQuit(sprintf($_lang["resource_permissions_error"]), "index.php?a=4");
            }
        }
    }
}

$rs = $modx->getDatabase()->select(
    "DISTINCT tv.*, IF(tvc.value!='',tvc.value,tv.default_text) as value",
    "{$tbl_site_tmplvars} AS tv
        INNER JOIN {$tbl_site_tmplvar_templates} AS tvtpl ON tvtpl.tmplvarid = tv.id
        LEFT JOIN {$tbl_site_tmplvar_contentvalues} AS tvc ON tvc.tmplvarid=tv.id AND tvc.contentid = '{$id}'
        LEFT JOIN {$tbl_site_tmplvar_access} AS tva ON tva.tmplvarid=tv.id",
    "tvtpl.templateid = '{$template}' AND (1='{$_SESSION['mgrRole']}' OR ISNULL(tva.documentgroup)".((!$docgrp) ? "" : " OR tva.documentgroup IN ($docgrp)").")",
    "tv.rank"
    );
while ($row = $modx->getDatabase()->getRow($rs)) {
    $tmplvar = '';
    switch ($row['type']) {
        case 'url':
            $tmplvar = $_POST["tv".$row['id']];
            if ($_POST["tv".$row['id'].'_prefix'] != '--') {
                $tmplvar = str_replace(array(
                    "feed://",
                    "ftp://",
                    "http://",
                    "https://",
                    "mailto:"
                ), "", $tmplvar);
                $tmplvar = $_POST["tv".$row['id'].'_prefix'].$tmplvar;
            }
        break;
        case 'file':
            $tmplvar = $_POST["tv".$row['id']];
        break;
        default:
            if (is_array($_POST["tv".$row['id']])) {
                // handles checkboxes & multiple selects elements
                $feature_insert = array();
                $lst = $_POST["tv".$row['id']];
                while (list ($featureValue, $feature_item) = each($lst)) {
                    $feature_insert[count($feature_insert)] = $feature_item;
                }
                $tmplvar = implode("||", $feature_insert);
            } else {
                $tmplvar = $_POST["tv".$row['id']];
            }
        break;
    }
    // save value if it was modified
    if (strlen($tmplvar) > 0 && $tmplvar != $row['default_text']) {
        $tmplvars[$row['id']] = array(
            $row['id'],
            $tmplvar
        );
    } else {
        // Mark the variable for deletion
        $tmplvars[$row['name']] = $row['id'];
    }
}

// get the document, but only if it already exists
if ($actionToTake != "new") {
    $rs = $modx->getDatabase()->select('*', $tbl_site_content, "id='{$id}'");
    $existingDocument = $modx->getDatabase()->getRow($rs);
    if (!$existingDocument) {
        $modx->webAlertAndQuit($_lang["error_no_results"]);
    }

}

// check to see if the user is allowed to save the document in the place he wants to save it in
if ($use_udperms == 1) {
    if ($existingDocument['parent'] != $parent) {
        $udperms = new EvolutionCMS\Legacy\Permissions();
        $udperms->user = $modx->getLoginUserID();
        $udperms->document = $parent;
        $udperms->role = $_SESSION['mgrRole'];

        if (!$udperms->checkPermissions()) {
            if ($actionToTake == 'edit') {
                $modx->getManagerApi()->saveFormValues(27);
                $modx->webAlertAndQuit(sprintf($_lang['access_permission_parent_denied'], $docid, $alias), "index.php?a=27&id={$id}");
            } else {
                $modx->getManagerApi()->saveFormValues(4);
                $modx->webAlertAndQuit(sprintf($_lang['access_permission_parent_denied'], $docid, $alias), "index.php?a=4");
            }
        }
    }
}

switch ($actionToTake) {
        case 'new' :

            // invoke OnBeforeDocFormSave event
            switch($modx->config['docid_incrmnt_method'])
            {
            case '1':
                $from = "{$tbl_site_content} AS T0 LEFT JOIN {$tbl_site_content} AS T1 ON T0.id + 1 = T1.id";
                $where = "T1.id IS NULL";
                $rs = $modx->getDatabase()->select('MIN(T0.id)+1', $from, "T1.id IS NULL");
                $id = $modx->getDatabase()->getValue($rs);
                break;
            case '2':
                $rs = $modx->getDatabase()->select('MAX(id)+1', $tbl_site_content);
                $id = $modx->getDatabase()->getValue($rs);
            break;

            default:
                $id = '';
            }

        $modx->invokeEvent("OnBeforeDocFormSave", array(
            "mode" => "new",
            "id" => $id
        ));

        // deny publishing if not permitted
        if (!$modx->hasPermission('publish_document')) {
            $pub_date = 0;
            $unpub_date = 0;
            $published = 0;
        }

        $publishedon = ($published ? $currentdate : 0);
        $publishedby = ($published ? $modx->getLoginUserID() : 0);

        if ((!empty($pub_date)) && ($published)) {
            $publishedon = $pub_date;
        }

        $dbInsert = array(
            "introtext"        => $introtext,
            "content"          => $content,
            "pagetitle"        => $pagetitle,
            "longtitle"        => $longtitle,
            "type"             => $type,
            "description"      => $description,
            "alias"            => $alias,
            "link_attributes"  => $link_attributes,
            "isfolder"         => $isfolder,
            "richtext"         => $richtext,
            "published"        => $published,
            "parent"           => $parent,
            "template"         => $template,
            "menuindex"        => $menuindex,
            "searchable"       => $searchable,
            "cacheable"        => $cacheable,
            "createdby"        => $modx->getLoginUserID(),
            "createdon"        => $currentdate,
            "editedby"         => $modx->getLoginUserID(),
            "editedon"         => $currentdate,
            "publishedby"      => $publishedby,
            "publishedon"      => $publishedon,
            "pub_date"         => $pub_date,
            "unpub_date"       => $unpub_date,
            "contentType"      => $contentType,
            "content_dispo"    => $contentdispo,
            "donthit"          => $donthit,
            "menutitle"        => $menutitle,
            "hidemenu"         => $hidemenu,
            "alias_visible"    => $aliasvisible
        );

        if ($id != '')
            $dbInsert["id"] = $id;

        $key = $modx->getDatabase()->insert($dbInsert, $tbl_site_content);

        $tvChanges = array();
        foreach ($tmplvars as $field => $value) {
            if (is_array($value)) {
                $tvId = $value[0];
                $tvVal = $value[1];
                $tvChanges[] = array('tmplvarid' => $tvId, 'contentid' => $key, 'value' => $modx->getDatabase()->escape($tvVal));
            }
        }
        if (!empty($tvChanges)) {
            foreach ($tvChanges as $tv) {
                $modx->getDatabase()->insert($tv, $tbl_site_tmplvar_contentvalues);
            }
        }

        // document access permissions
        if ($use_udperms == 1 && is_array($document_groups)) {
            $new_groups = array();
            foreach ($document_groups as $value_pair) {
                // first, split the pair (this is a new document, so ignore the second value
                list($group) = explode(',', $value_pair); // @see actions/mutate_content.dynamic.php @ line 1138 (permissions list)
                $new_groups[] = '('.(int) $group.','.$key.')';
            }
            $saved = true;
            if (!empty($new_groups)) {
                $modx->getDatabase()->query("INSERT INTO {$tbl_document_groups} (document_group, document) VALUES ".implode(',', $new_groups));
            }
        } else {
            $isManager = $modx->hasPermission('access_permissions');
            $isWeb     = $modx->hasPermission('web_access_permissions');
            if ($use_udperms && !($isManager || $isWeb) && $parent != 0) {
                // inherit document access permissions
                $modx->getDatabase()->insert(
                    array(
                        'document_group' =>'',
                        'document'       =>''
                        ), $tbl_document_groups, // Insert into
                    "document_group, {$key}", $tbl_document_groups, "document = '{$parent}'"); // Copy from
            }
        }


        // update parent folder status
        if ($parent != 0) {
            $fields = array('isfolder' => 1);
            $modx->getDatabase()->update($fields, $tbl_site_content, "id='{$_REQUEST['parent']}'");
        }

        // invoke OnDocFormSave event
        $modx->invokeEvent("OnDocFormSave", array(
            "mode" => "new",
            "id" => $key
        ));

        // secure web documents - flag as private
        include MODX_MANAGER_PATH."includes/secure_web_documents.inc.php";
        secureWebDocument($key);

        // secure manager documents - flag as private
        include MODX_MANAGER_PATH."includes/secure_mgr_documents.inc.php";
        secureMgrDocument($key);

        // Set the item name for logger
        $_SESSION['itemname'] = $no_esc_pagetitle;

        if ($syncsite == 1) {
            // empty cache
            $modx->clearCache('full');
        }

        // redirect/stay options
        if ($_POST['stay'] != '') {
            // weblink
            if ($_POST['mode'] == "72")
                $a = ($_POST['stay'] == '2') ? "27&id=$key" : "72&pid=$parent";
            // document
            if ($_POST['mode'] == "4")
                $a = ($_POST['stay'] == '2') ? "27&id=$key" : "4&pid=$parent";
            $header = "Location: index.php?a=".$a."&r=1&stay=".$_POST['stay'];
        } else {
            $header = "Location: index.php?a=3&id=$key&r=1";
        }

        if (headers_sent()) {
            $header = str_replace('Location: ', '', $header);
            echo "<script>document.location.href='$header';</script>\n";
        } else {
            header($header);
        }


        break;
        case 'edit' :

            // get the document's current parent
            $oldparent = $existingDocument['parent'];
            $doctype = $existingDocument['type'];

            if ($id == $site_start && $published == 0) {
                $modx->getManagerApi()->saveFormValues(27);
                $modx->webAlertAndQuit("Document is linked to site_start variable and cannot be unpublished!");
            }
            $today = $_SERVER['REQUEST_TIME'] + $modx->config['server_offset_time'];
            if ($id == $site_start && ($pub_date > $today || $unpub_date != "0")) {
                $modx->getManagerApi()->saveFormValues(27);
                $modx->webAlertAndQuit("Document is linked to site_start variable and cannot have publish or unpublish dates set!");
            }
            if ($parent == $id) {
                $modx->getManagerApi()->saveFormValues(27);
                $modx->webAlertAndQuit("Document can not be it's own parent!");
            }

            $parents = $modx->getParentIds($parent);
            if (in_array($id, $parents)) {
                $modx->webAlertAndQuit("Document descendant can not be it's parent!");
            }

            // check to see document is a folder
            $rs = $modx->getDatabase()->select('count(id)', $tbl_site_content, "parent='{$id}'");
            $count = $modx->getDatabase()->getValue($rs);
            if ($count > 0) {
                $isfolder = 1;
            }

            // set publishedon and publishedby
            $was_published = $existingDocument['published'];

            // keep original publish state, if change is not permitted
            if (!$modx->hasPermission('publish_document')) {
                $published = $was_published;
                $pub_date = 'pub_date';
                $unpub_date = 'unpub_date';
            }

            // if it was changed from unpublished to published
            if (!$was_published && $published) {
                $publishedon = $currentdate;
                $publishedby = $modx->getLoginUserID();
                }elseif ((!empty($pub_date)&& $pub_date<=$currentdate && $published)) {
                $publishedon = $pub_date;
                $publishedby = $modx->getLoginUserID();
                   }elseif ($was_published && !$published) {
                $publishedon = 0;
                $publishedby = 0;
            } else {
                $publishedon = 'publishedon';
                $publishedby = 'publishedby';
            }

            // invoke OnBeforeDocFormSave event
            $modx->invokeEvent("OnBeforeDocFormSave", array (
                "mode" => "upd",
                "id" => $id
            ));

            // update the document
            $modx->getDatabase()->update(
                "introtext='{$introtext}', "
                . "content='{$content}', "
                . "pagetitle='{$pagetitle}', "
                . "longtitle='{$longtitle}', "
                . "type='{$type}', "
                . "description='{$description}', "
                . "alias='{$alias}', "
                . "link_attributes='{$link_attributes}', "
                . "isfolder={$isfolder}, "
                . "richtext={$richtext}, "
                . "published={$published}, "
                . "pub_date={$pub_date}, "
                . "unpub_date={$unpub_date}, "
                . "parent={$parent}, "
                . "template={$template}, "
                . "menuindex={$menuindex}, "
                . "searchable={$searchable}, "
                . "cacheable={$cacheable}, "
                . "editedby=" . $modx->getLoginUserID() . ", "
                . "editedon={$currentdate}, "
                . "publishedon={$publishedon}, "
                . "publishedby={$publishedby}, "
                . "contentType='{$contentType}', "
                . "content_dispo={$contentdispo}, "
                . "donthit={$donthit}, "
                . "menutitle='{$menutitle}', "
                . "hidemenu={$hidemenu}, "
                . "alias_visible={$aliasvisible}"
                , $tbl_site_content, "id='{$id}'");

            // update template variables
            $rs = $modx->getDatabase()->select('id, tmplvarid', $tbl_site_tmplvar_contentvalues, "contentid='{$id}'");
            $tvIds = array ();
            while ($row = $modx->getDatabase()->getRow($rs)) {
                $tvIds[$row['tmplvarid']] = $row['id'];
            }
            $tvDeletions = array();
            $tvChanges = array();
            foreach ($tmplvars as $field => $value) {
                if (!is_array($value)) {
                    if (isset($tvIds[$value])) $tvDeletions[] = $tvIds[$value];
                } else {
                    $tvId = $value[0];
                    $tvVal = $value[1];

                    if (isset($tvIds[$tvId])) {
                        $tvChanges[] = array(array('tmplvarid' => $tvId, 'contentid' => $id, 'value' => $modx->getDatabase()->escape($tvVal)), array('id' => $tvIds[$tvId]));
                    } else {
                        $tvAdded[] = array('tmplvarid' => $tvId, 'contentid' => $id, 'value' => $modx->getDatabase()->escape($tvVal));
                    }
                }
            }

            if (!empty($tvDeletions)) {
                $modx->getDatabase()->delete($tbl_site_tmplvar_contentvalues, 'id IN('.implode(',', $tvDeletions).')');
            }

            if (!empty($tvAdded)) {
                foreach ($tvAdded as $tv) {
                    $modx->getDatabase()->insert($tv, $tbl_site_tmplvar_contentvalues);
                }
            }

            if (!empty($tvChanges)) {
                foreach ($tvChanges as $tv) {
                    $modx->getDatabase()->update($tv[0], $tbl_site_tmplvar_contentvalues, "id='{$tv[1]['id']}'");
                }
            }

            // set document permissions
            if ($use_udperms == 1 && is_array($document_groups)) {
                $new_groups = array();
                // process the new input
                foreach ($document_groups as $value_pair) {
                    list($group, $link_id) = explode(',', $value_pair); // @see actions/mutate_content.dynamic.php @ line 1138 (permissions list)
                    $new_groups[$group] = $link_id;
                }

                // grab the current set of permissions on this document the user can access
                $isManager = $modx->hasPermission('access_permissions');
                $isWeb     = $modx->hasPermission('web_access_permissions');
                $rs = $modx->getDatabase()->select(
                    'groups.id, groups.document_group',
                    "{$tbl_document_groups} AS groups
                    LEFT JOIN {$tbl_documentgroup_names} AS dgn ON dgn.id = groups.document_group",
                    "((1=".(int)$isManager." AND dgn.private_memgroup) OR (1=".(int)$isWeb." AND dgn.private_webgroup)) AND groups.document = '{$id}'"
                    );
                $old_groups = array();
                while ($row = $modx->getDatabase()->getRow($rs)) $old_groups[$row['document_group']] = $row['id'];

                // update the permissions in the database
                $insertions = $deletions = array();
                foreach ($new_groups as $group => $link_id) {
                    if (array_key_exists($group, $old_groups)) {
                        unset($old_groups[$group]);
                        continue;
                    } elseif ($link_id == 'new') {
                        $insertions[] = '('.(int)$group.','.$id.')';
                    }
                }
                if (!empty($insertions)) {
                    $modx->getDatabase()->query("INSERT INTO {$tbl_document_groups} (document_group, document) VALUES ".implode(',', $insertions));
                }
                if (!empty($old_groups)) {
                    $modx->getDatabase()->delete($tbl_document_groups, "id IN (".implode(',', $old_groups).")");
                }
                // necessary to remove all permissions as document is public
                if ((isset($_POST['chkalldocs']) && $_POST['chkalldocs'] == 'on')) {
                    $modx->getDatabase()->delete($tbl_document_groups, "document='{$id}'");
                }
            }

            // do the parent stuff
            if ($parent != 0) {
                $fields = array('isfolder' => 1);
                $modx->getDatabase()->update($fields, $tbl_site_content, "id='{$_REQUEST['parent']}'");
            }

            // finished moving the document, now check to see if the old_parent should no longer be a folder
            $rs = $modx->getDatabase()->select('COUNT(id)', $tbl_site_content, "parent='{$oldparent}'");
            $limit = $modx->getDatabase()->getValue($rs);

            if ($limit == 0) {
                $fields = array('isfolder' => 0);
                $modx->getDatabase()->update($fields, $tbl_site_content, "id='{$oldparent}'");
            }


            // invoke OnDocFormSave event
            $modx->invokeEvent("OnDocFormSave", array (
                "mode" => "upd",
                "id" => $id
            ));

            // secure web documents - flag as private
            include MODX_MANAGER_PATH . "includes/secure_web_documents.inc.php";
            secureWebDocument($id);

            // secure manager documents - flag as private
            include MODX_MANAGER_PATH . "includes/secure_mgr_documents.inc.php";
            secureMgrDocument($id);

            // Set the item name for logger
            $_SESSION['itemname'] = $no_esc_pagetitle;

            if ($syncsite == 1) {
                // empty cache
                $modx->clearCache('full');
            }

            if ($_POST['refresh_preview'] == '1')
                $header = "Location: ".MODX_SITE_URL."index.php?id=$id&z=manprev";
            else {
                if ($_POST['stay'] != '2' && $id > 0) {
                    $modx->unlockElement(7, $id);
                }
                if ($_POST['stay'] != '') {
                    $id = $_REQUEST['id'];
                    if ($type == "reference") {
                        // weblink
                        $a = ($_POST['stay'] == '2') ? "27&id=$id" : "72&pid=$parent";
                    } else {
                        // document
                        $a = ($_POST['stay'] == '2') ? "27&id=$id" : "4&pid=$parent";
                    }
                    $header = "Location: index.php?a=" . $a . "&r=1&stay=" . $_POST['stay'].$add_path;
                } else {
                    $header = "Location: index.php?a=3&id=$id&r=1".$add_path;
                }
            }
            if (headers_sent()) {
                $header = str_replace('Location: ','',$header);
                echo "<script>document.location.href='$header';</script>\n";
            } else {
                header($header);
            }
            break;
        default :
            $modx->webAlertAndQuit("No operation set in request.");
}
