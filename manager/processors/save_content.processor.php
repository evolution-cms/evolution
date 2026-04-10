<?php
if(!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
$modx = EvolutionCMS();
if (!evo()->hasPermission('save_document')) {
    evo()->webAlertAndQuit(__("global.error_no_privileges"));
}

// preprocess POST values
$id = is_numeric($_POST['id']) ? $_POST['id'] : '';

$introtext = $_POST['introtext'] ?? '';
$content = $_POST['ta'] ?? '';
$pagetitle = $_POST['pagetitle'] ?? '';
$description = $_POST['description'] ?? '';
$alias = $_POST['alias'];
$link_attributes = $_POST['link_attributes'];
$isfolder = (int)$_POST['isfolder'];
$richtext = (int)$_POST['richtext'];
$published = (int)$_POST['published'];
$parentId = $parent = (int)get_by_key($_POST, 'parent', 0, 'is_scalar');
$template = (int)$_POST['template'];
$menuindex = !empty($_POST['menuindex']) ? (int)$_POST['menuindex'] : 0;
$searchable = (int)$_POST['searchable'];
$cacheable = (int)$_POST['cacheable'];
$syncsite = (int)$_POST['syncsite'];
$pub_date = $_POST['pub_date'];
$unpub_date = $_POST['unpub_date'];
$document_groups = (isset($_POST['chkalldocs']) && $_POST['chkalldocs'] == 'on') ? [] : get_by_key($_POST, 'docgroups', [], 'is_array');
$type = $_POST['type'];
$contentType = $_POST['contentType'];
$contentdispo = (int)$_POST['content_dispo'];
$longtitle = $_POST['longtitle'];
$hide_from_tree = (int)$_POST['hide_from_tree'];
$menutitle = $_POST['menutitle'];
$hidemenu = (int)$_POST['hidemenu'];
$aliasvisible = (int)$_POST['alias_visible'];

/************* webber ********/
$sd=isset($_POST['dir']) && strtolower($_POST['dir']) === 'asc' ? '&dir=ASC' : '&dir=DESC';
$sb=isset($_POST['sort'])?'&sort='.entities($_POST['sort'], $modx->getConfig('modx_charset')):'&sort=pub_date';
$pg=isset($_POST['page'])?'&page='.(int)$_POST['page']:'';
$add_path=$sd.$sb.$pg;



$no_esc_pagetitle = $_POST['pagetitle'];
if (trim($no_esc_pagetitle) == "") {
    if ($type == "reference") {
        $no_esc_pagetitle = $pagetitle = $_lang['untitled_weblink'];
    } else {
        $no_esc_pagetitle = $pagetitle = $_lang['untitled_resource'];
    }
}


$actionToTake = ((int)$id > 0 || $_POST['mode'] == '73' || $_POST['mode'] == '27') ? "edit" : "new";
$newResourceAction = ($type == "reference") ? "72" : "4";
$editResourceAction = "27";
$newResourceRedirect = "index.php?a={$newResourceAction}";
$editResourceRedirect = "index.php?a={$editResourceAction}&id={$id}";

// friendly url alias checks
if ($modx->getConfig('friendly_urls')) {
    // auto assign alias
    if (!$alias && $modx->getConfig('automatic_alias')) {
        $alias = strtolower($modx->stripAlias(trim($pagetitle)));
        if(!$modx->getConfig('allow_duplicate_alias')) {

            if (\EvolutionCMS\Models\SiteContent::withTrashed()
                    ->where('id', '<>', $id)
                    ->where('alias', $alias)->count() > 0) {
                $cnt = 1;
                $tempAlias = $alias;

                while (\EvolutionCMS\Models\SiteContent::withTrashed()
                        ->where('id', '<>', $id)
                        ->where('alias', $tempAlias)->count() > 0) {
                    $tempAlias = $alias;
                    $tempAlias .= $cnt;
                    $cnt++;
                }
                $alias = $tempAlias;
            }
        }else{
            if (\EvolutionCMS\Models\SiteContent::withTrashed()
                    ->where('id', '<>', $id)
                    ->where('alias', $alias)
                    ->where('parent', $parent)->count() > 0) {
                $cnt = 1;
                $tempAlias = $alias;
                while (\EvolutionCMS\Models\SiteContent::withTrashed()
                        ->where('id', '<>', $id)
                        ->where('alias', $tempAlias)
                        ->where('parent', $parent)->count() > 0) {
                    $tempAlias = $alias;
                    $tempAlias .= $cnt;
                    $cnt++;
                }
                $alias = $tempAlias;
            }
        }
    }

    // check for duplicate alias name if not allowed
    elseif ($alias && !$modx->getConfig('allow_duplicate_alias')) {
        $alias = $modx->stripAlias($alias);
        $docid = \EvolutionCMS\Models\SiteContent::withTrashed()->select('id')
            ->where('id', '<>', $id)
            ->where('alias', $alias);
        if ($modx->getConfig('use_alias_path')) {
            // only check for duplicates on the same level if alias_path is on
            $docid = $docid->where('parent', $parent);
        }
        $docid = $docid->first();
        if (!is_null($docid)) {
            if ($actionToTake == 'edit') {
                $modx->getManagerApi()->saveFormValues($editResourceAction);
                $modx->webAlertAndQuit(sprintf($_lang["duplicate_alias_found"], $docid->id, $alias), $editResourceRedirect);
            } else {
                $modx->getManagerApi()->saveFormValues($newResourceAction);
                $modx->webAlertAndQuit(sprintf($_lang["duplicate_alias_found"], $docid->id, $alias), $newResourceRedirect);
            }
        }
    }

    // strip alias of special characters
    elseif ($alias) {
        $alias = $modx->stripAlias($alias);
        $docid = \EvolutionCMS\Models\SiteContent::withTrashed()->select('id')
            ->where('id', '<>', $id)
            ->where('alias', $alias)
            ->where('parent', $parent)
            ->first();
        if (!is_null($docid)) {
            if ($actionToTake == 'edit') {
                $modx->getManagerApi()->saveFormValues($editResourceAction);
                $modx->webAlertAndQuit(sprintf($_lang["duplicate_alias_found"], $docid->id, $alias), $editResourceRedirect);
            } else {
                $modx->getManagerApi()->saveFormValues($newResourceAction);
                $modx->webAlertAndQuit(sprintf($_lang["duplicate_alias_found"], $docid->id, $alias), $newResourceRedirect);
            }
        }
    }
}
elseif ($alias) {
    $alias = $modx->stripAlias($alias);
}

// determine published status
$currentdate = $modx->timestamp((int)get_by_key($_SERVER, 'REQUEST_TIME', 0));

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
$tmplvars =[];
$docgrp = array_unique(\EvolutionCMS\Models\MemberGroup::query()
    ->join('membergroup_access', 'membergroup_access.membergroup', '=', 'member_groups.user_group')
    ->where('member_groups.member', $modx->getLoginUserID('mgr'))->pluck('documentgroup')->toArray());

// ensure that user has not made this document inaccessible to themselves
if($_SESSION['mgrRole'] != 1 && is_array($document_groups)) {
    $document_group_list = implode(',', $document_groups);
    $document_group_list = array_filter(explode(',', $document_group_list), 'is_numeric');
    if(!empty($document_group_list)) {
        $count = \EvolutionCMS\Models\MembergroupAccess::query()
            ->join('member_groups', 'membergroup_access.membergroup', '=', 'member_groups.user_group')
            ->whereIn('membergroup_access.documentgroup', $document_group_list)
            ->where('member_groups.member', $_SESSION['mgrInternalKey'])->count('member_groups.id');

        if($count == 0) {
            if ($actionToTake == 'edit') {
                $modx->getManagerApi()->saveFormValues($editResourceAction);
                $modx->webAlertAndQuit($_lang["resource_permissions_error"], $editResourceRedirect);
            } else {
                $modx->getManagerApi()->saveFormValues($newResourceAction);
                $modx->webAlertAndQuit($_lang["resource_permissions_error"], $newResourceRedirect);
            }
        }
    }
}

$tvs = \EvolutionCMS\Models\SiteTmplvar::query()->distinct()
    ->select('site_tmplvars.*', 'site_tmplvar_contentvalues.value')
    ->join('site_tmplvar_templates', 'site_tmplvar_templates.tmplvarid', '=', 'site_tmplvars.id')
    ->leftJoin('site_tmplvar_contentvalues', function ($join) use ($id) {
        $join->on('site_tmplvar_contentvalues.tmplvarid', '=', 'site_tmplvars.id');
        $join->on('site_tmplvar_contentvalues.contentid', '=', \DB::raw($id));
    })->leftjoin('site_tmplvar_access', 'site_tmplvar_access.tmplvarid', '=', 'site_tmplvars.id')
    ->where('site_tmplvar_templates.templateid', $template)->orderBy('site_tmplvars.rank');
if($_SESSION['mgrRole']!= 1){
    $tvs = $tvs->leftJoin('document_groups', 'site_tmplvar_contentvalues.contentid', '=', 'document_groups.document');
    $tvs = $tvs->where(function ($query) {
        $query->whereNull('site_tmplvar_access.documentgroup')
            ->orWhereIn('document_groups.document_group', $_SESSION['mgrDocgroups']);
    });
}
$tvs = $tvs->get();
foreach ($tvs->toArray() as $row) {
    $tmplvar = '';
    switch ($row['type']) {
        case 'url':
            $tmplvar = $_POST["tv" . $row['id']];
            if ($_POST["tv" . $row['id'] . '_prefix'] != '--') {
                $tmplvar = str_replace([
                    "feed://",
                    "ftp://",
                    "http://",
                    "https://",
                    "mailto:"
                ], "", $tmplvar);
                $tmplvar = $_POST["tv" . $row['id'] . '_prefix'] . $tmplvar;
            }
            break;
        case 'file':
            $tmplvar = $_POST["tv" . $row['id']];
            break;
        default:
            $tmp = get_by_key($_POST, 'tv' . $row['id']);
            if (is_array($tmp)) {
                // handles checkboxes & multiple selects elements
                $feature_insert = [];
                foreach ($tmp as $featureValue => $feature_item) {
                    $feature_insert[count($feature_insert)] = $feature_item;
                }
                $tmplvar = implode("||", $feature_insert);
            } else {
                $tmplvar = $tmp;
            }
            break;
    }
    // save value if it was modified
    if (!empty($tmplvar) && $tmplvar != $row['default_text']) {
        $tmplvars[$row['id']] = [
            $row['id'],
            $tmplvar
        ];
    } else {
        // Mark the variable for deletion
        $tmplvars[$row['name']] = $row['id'];
    }
}

// get the document, but only if it already exists
if ($actionToTake != "new") {
    $existingDocument = \EvolutionCMS\Models\SiteContent::withTrashed()->find($id);
    if (is_null($existingDocument)) {
        $modx->webAlertAndQuit($_lang["error_no_results"]);
    }
    $existingDocument = $existingDocument->toArray();
}



// check to see if the user is allowed to save the document in the place he wants to save it in
if ($modx->getConfig('use_udperms') == 1) {
    if (!isset($existingDocument) || $existingDocument['parent'] != $parent) {
        $udperms = new EvolutionCMS\Legacy\Permissions();
        $udperms->user = $modx->getLoginUserID('mgr');
        $udperms->document = $parent;
        $udperms->role = $_SESSION['mgrRole'];

        if (!$udperms->checkPermissions()) {
            if ($actionToTake == 'edit') {
                $modx->getManagerApi()->saveFormValues($editResourceAction);
                $modx->webAlertAndQuit($_lang['access_permission_parent_denied'], $editResourceRedirect);
            } else {
                $modx->getManagerApi()->saveFormValues($newResourceAction);
                $modx->webAlertAndQuit($_lang['access_permission_parent_denied'], $newResourceRedirect);
            }
        }
    }
}

$resourceArray = [
    "introtext"        => $introtext ,
    "content"          => $content ,
    "pagetitle"        => $pagetitle ,
    "longtitle"        => $longtitle ,
    "type"             => $type ,
    "description"      => $description ,
    "alias"            => $alias ,
    "link_attributes"  => $link_attributes ,
    "isfolder"         => $isfolder ,
    "richtext"         => $richtext ,
    "published"        => $published ,
    "parent"           => $parent ,
    "template"         => $template ,
    "menuindex"        => $menuindex ,
    "searchable"       => $searchable ,
    "cacheable"        => $cacheable ,
    "editedby"         => $modx->getLoginUserID('mgr') ,
    "editedon"         => $currentdate ,
    "pub_date"         => $pub_date ,
    "unpub_date"       => $unpub_date ,
    "contentType"      => $contentType ,
    "content_dispo"    => $contentdispo ,
    "hide_from_tree"   => $hide_from_tree ,
    "menutitle"        => $menutitle ,
    "hidemenu"         => $hidemenu ,
    "alias_visible"    => $aliasvisible
];

switch ($actionToTake) {
    case 'new' :
        $resourceArray['createdby'] = $modx->getLoginUserID('mgr');
        $resourceArray['createdon'] = $currentdate;
        // invoke OnBeforeDocFormSave event
        switch($modx->config['docid_incrmnt_method'])
        {
            case '1':
                $id = \EvolutionCMS\Models\SiteContent::withTrashed()
                    ->leftJoin('site_content as t1', function ($join) {
                        $join->on(\DB::raw(evo()->getDatabase()->getFullTableName('site_content').'.id +1'), '=', 't1.id');
                    })
                    ->whereNull('t1.id')->min('site_content.id');
                $id++;

                break;
            case '2':
                $id = \EvolutionCMS\Models\SiteContent::max('id');
                $id++;
                break;

            default:
                $id = '';
        }

        $modx->invokeEvent("OnBeforeDocFormSave", [
            "mode" => "new",
            "id" => $id
        ]);

        $parentDeleted = $parentId > 0 && empty(\EvolutionCMS\Models\SiteContent::find($parentId));
        if ($parentDeleted) {
            $resourceArray['deleted'] = 1;
        }
        // deny publishing if not permitted
        if (!$modx->hasPermission('publish_document')) {
            $pub_date = 0;
            $unpub_date = 0;
            $published = 0;
        }

        $publishedon = ($published ? $currentdate : 0);
        $publishedby = ($published ? $modx->getLoginUserID('mgr') : 0);

        if ((!empty($pub_date))&&($published)){
            $publishedon=$pub_date;
        }


        $resourceArray['pub_date'] = $pub_date;
        $resourceArray['publishedon'] = $publishedon;
        $resourceArray['publishedby'] = $publishedby;
        $resourceArray['unpub_date'] = $unpub_date;

        if ($id != '')
            $resourceArray["id"] = $id;

        $key = \EvolutionCMS\Models\SiteContent::withTrashed()->create($resourceArray)->getKey();

        $tvChanges = [];
        foreach ($tmplvars as $field => $value) {
            if (is_array($value)) {
                $tvId = $value[0];
                $tvVal = $value[1];
                \EvolutionCMS\Models\SiteTmplvarContentvalue::query()->create(['tmplvarid' => $tvId, 'contentid' => $key, 'value' => $tvVal]);
            }
        }


        // document access permissions
        if ($modx->getConfig('use_udperms') && $parent != 0) {
            $groupsParent = \EvolutionCMS\Models\DocumentGroup::select('document_group', 'document')
                ->where('document', $parent)->pluck('document_group')->toArray();
        } else {
            $groupsParent = [];
        }
        if ($modx->getConfig('use_udperms') == 1 && $modx->hasAnyPermissions(['manage_groups', 'manage_document_permissions']) && is_array($document_groups)) {
            $new_groups = [];
            $groupsToInsert = [];
            foreach ($document_groups as $value_pair) {
                // first, split the pair (this is a new document, so ignore the second value
                [$group] = explode(',', $value_pair); // @see actions/mutate_content.dynamic.php @ line 1138 (permissions list)
                $group = (int)$group;
                if ($modx->hasPermission('manage_groups')) {
                    $new_groups[] = ['document_group' => $group, 'document' => $key];
                    $groupsToInsert[] = $group;
                    continue;
                }
                if ($modx->hasPermission('manage_document_permissions')) {
                    if (in_array($group, $docgrp)) {
                        $new_groups[] = ['document_group' => $group, 'document' => $key];
                        $groupsToInsert[] = $group;
                    }
                }
            }
            if ($modx->hasPermission('manage_document_permissions')) {
                foreach ($groupsParent as $group) {
                    if (!in_array($group, $docgrp)) {
                        $new_groups[] = ['document_group' => $group, 'document' => $key];
                        $groupsToInsert[] = $group;
                    }
                }
            }
            if (!$modx->hasPermission('manage_groups')) {
                if (!array_intersect($groupsToInsert, $docgrp)) {
                    foreach ($groupsParent as $group){
                        $new_groups[] = ['document_group' => $group, 'document' => $key];
                    }
                }
            }
            if (!empty($new_groups)) {
                \EvolutionCMS\Models\DocumentGroup::query()->insertOrIgnore($new_groups);
            }
        } else {
            if(!($modx->hasAnyPermissions(['manage_groups', 'manage_document_permissions']))) {
                // inherit document access permissions
                foreach ($groupsParent as $group){
                    \EvolutionCMS\Models\DocumentGroup::insert(['document_group'=>$group, 'document'=>$key]);
                }
            }
        }

        // update parent folder status
        if ($resourceArray['parent'] != 0) {
            $fields = ['isfolder' => 1];
            \EvolutionCMS\Models\SiteContent::withTrashed()->where('id',$resourceArray['parent'])->update(['isfolder'=>1]);
        }

        // invoke OnDocFormSave event
        $modx->invokeEvent("OnDocFormSave", [
            "mode" => "new",
            "id" => $key
        ]);

        // secure web documents - flag as private
        include EVO_MANAGER_PATH . "includes/secure_web_documents.inc.php";
        secureWebDocument($key);
        secureMgrDocument($key);

        // Set the item name for logger
        $_SESSION['itemname'] = $no_esc_pagetitle;

        if ($syncsite == 1) {
            // empty cache
            $modx->clearCache('full');
        }

        // redirect/stay options
        if ($_POST['stay'] != '') {
            if ($type == "reference") {
                $a = ($_POST['stay'] == '2') ? "27&id=$key" : "72&pid=$parentId";
            } else {
                $a = ($_POST['stay'] == '2') ? "27&id=$key" : "4&pid=$parentId";
            }
            $redirectUrl = "index.php?a=" . $a . "&r=1&stay=" . $_POST['stay'];
        } else {
            $redirectUrl = "index.php?a=3&id=$key&r=1";
        }
        evo()->sendRedirect($redirectUrl, 0, headers_sent() ? 'REDIRECT_SCRIPT' : '');
        break;
    case 'edit' :
        // get the document's current parent
        $oldparent = $existingDocument['parent'];
        $doctype = $existingDocument['type'];

        if ($id == $modx->getConfig('site_start') && $published == 0) {
            $modx->getManagerApi()->saveFormValues(27);
            $modx->webAlertAndQuit("Document is linked to site_start variable and cannot be unpublished!");
        }
        $today = $modx->timestamp((int)get_by_key($_SERVER, 'REQUEST_TIME', 0));
        if ($id == $modx->getConfig('site_start') && ($pub_date > $today || $unpub_date != "0")) {
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
        $child = \EvolutionCMS\Models\SiteContent::withTrashed()->select('id')->where('parent', $id)->first();
        if (!is_null($child)) {
            $resourceArray['isfolder'] = 1;
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
            $publishedby = $modx->getLoginUserID('mgr');
        }elseif ((!empty($pub_date)&& $pub_date<=$currentdate && $published)) {
            $publishedon = $pub_date;
            $publishedby = $modx->getLoginUserID('mgr');
        }elseif ($was_published && !$published) {
            $publishedon = 0;
            $publishedby = 0;
        } else {
            $publishedon = $existingDocument['publishedon'];
            $publishedby = $existingDocument['publishedby'];
        }

        $resourceArray['pub_date'] = $pub_date;
        $resourceArray['publishedon'] = $publishedon;
        $resourceArray['publishedby'] = $publishedby;

        // invoke OnBeforeDocFormSave event
        $modx->invokeEvent("OnBeforeDocFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);
        $parentDeleted = $parentId > 0 && empty(\EvolutionCMS\Models\SiteContent::find($parentId));
        if ($parentDeleted) {
            $resourceArray['deleted'] = 1;
        }
        $resource = \EvolutionCMS\Models\SiteContent::withTrashed()->find($id);
        foreach($resourceArray as $key=>$value){
            $resource->{$key} = $value;
        }
        $resource->save();

        // update template variables
        $tvs = \EvolutionCMS\Models\SiteTmplvarContentvalue::select('id', 'tmplvarid')->where('contentid', $id)->get();
        $tvIds = [];
        foreach ($tvs as $tv) {
            $tvIds[$tv->tmplvarid] = $tv->id;
        }
        $tvDeletions = [];
        $tvChanges = [];
        $tvAdded = [];

        foreach ($tmplvars as $field => $value) {

            if (!is_array($value)) {
                if (isset($tvIds[$value])) $tvDeletions[] = $tvIds[$value];
            } else {
                $tvId = $value[0];
                $tvVal = $value[1];
                if (isset($tvIds[$tvId])) {
                    \EvolutionCMS\Models\SiteTmplvarContentvalue::query()->find($tvIds[$tvId])->update(['tmplvarid' => $tvId, 'contentid' => $id, 'value' => $tvVal]);
                } else {
                    \EvolutionCMS\Models\SiteTmplvarContentvalue::query()->create(['tmplvarid' => $tvId, 'contentid' => $id, 'value' => $tvVal]);
                }
            }
        }

        if (!empty($tvDeletions)) {
            \EvolutionCMS\Models\SiteTmplvarContentvalue::query()->whereIn('id', $tvDeletions)->delete();
        }

        // set document permissions
        if ($modx->getConfig('use_udperms') == 1 && $modx->hasAnyPermissions(['manage_groups', 'manage_document_permissions']) && is_array($document_groups)) {
            $new_groups = [];
            // process the new input
            foreach ($document_groups as $value_pair) {
                [$group, $link_id] = explode(',', $value_pair); // @see actions/mutate_content.dynamic.php @ line 1138 (permissions list)
                if (in_array($group, $docgrp) || $modx->hasPermission('manage_groups')) {
                    $new_groups[$group] = $link_id;
                }
            }

            // grab the current set of permissions on this document the user can access
            $documentGroups = \EvolutionCMS\Models\DocumentGroup::select('id','document_group')
                ->where('document', $id)->get();

            $old_groups = [];
            foreach ($documentGroups as $documentGroup) {
                if (in_array($documentGroup->document_group, $docgrp) || $modx->hasPermission('manage_groups')) {
                    $old_groups[$documentGroup->document_group] = $documentGroup->id;
                }
            }
            // update the permissions in the database
            $insertions = $deletions = [];
            foreach ($new_groups as $group => $link_id) {
                if (in_array($group, $docgrp) || $modx->hasPermission('manage_groups')) {
                    if (array_key_exists($group, $old_groups)) {
                        unset($old_groups[$group]);
                        continue;
                    } elseif ($link_id == 'new') {
                        $insertions[] = ['document_group' => (int) $group, 'document' => $id];
                    }
                }
            }
            if (!empty($insertions)) {
                \EvolutionCMS\Models\DocumentGroup::query()->insert($insertions);
            }
            if (!$modx->hasPermission('manage_groups')) {
                $remainingGroups = \EvolutionCMS\Models\DocumentGroup::select('document_groups.document_group')->whereNotIn('id',
                    $old_groups)->where('document_groups.document', $id)->pluck('document_group')->toArray();
                if (!empty($docgrp) && !array_intersect($docgrp, $remainingGroups)) {
                    $modx->webAlertAndQuit($_lang["resource_permissions_error"], "index.php?a=27&id={$id}");
                }
            }
            if (!empty($old_groups)) {
                \EvolutionCMS\Models\DocumentGroup::query()->whereIn('id', $old_groups)->delete();
            }
            // necessary to remove all permissions as document is public
            if ((isset($_POST['chkalldocs']) && $_POST['chkalldocs'] == 'on')) {
                \EvolutionCMS\Models\DocumentGroup::query()->where('document', $id)->delete();
            }
        }

        // do the parent stuff
        if ($resourceArray['parent'] != 0) {
            $parent = \EvolutionCMS\Models\SiteContent::withTrashed()->find($_REQUEST['parent']);
            $parent->isfolder = 1;
            $parent->save();
        }

        // finished moving the document, now check to see if the old_parent should no longer be a folder
        $countChildOldParent = \EvolutionCMS\Models\SiteContent::withTrashed()->where('parent', $oldparent)->count();

        if ($countChildOldParent == 0) {
            $oldParent = \EvolutionCMS\Models\SiteContent::withTrashed()->find($oldparent);
            $oldParent->isfolder = 0;
            $oldParent->save();
        }


        // invoke OnDocFormSave event
        $modx->invokeEvent("OnDocFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);

        // secure web documents - flag as private
        include EVO_MANAGER_PATH . "includes/secure_web_documents.inc.php";
        secureWebDocument($id);
        secureMgrDocument($id);


        // Set the item name for logger
        $_SESSION['itemname'] = $no_esc_pagetitle;

        if ($syncsite == 1) {
            // empty cache
            $modx->clearCache('full');
        }

        if ($_POST['refresh_preview'] == '1')
            $redirectUrl = EVO_SITE_URL . "index.php?id=$id&z=manprev";
        else {
            if ($_POST['stay'] != '2' && $id > 0) {
                $modx->unlockElement(7, $id);
            }
            if ($_POST['stay'] != '') {
                $id = $_REQUEST['id'];
                if ($type == "reference") {
                    // weblink
                    $a = ($_POST['stay'] == '2') ? "27&id=$id" : "72&pid=$parentId";
                } else {
                    // document
                    $a = ($_POST['stay'] == '2') ? "27&id=$id" : "4&pid=$parentId";
                }
                $redirectUrl = "index.php?a=" . $a . "&r=1&stay=" . $_POST['stay'] . $add_path;
            } else {
                $redirectUrl = "index.php?a=3&id=$id&r=1" . $add_path;
            }
        }
        evo()->sendRedirect($redirectUrl, 0, headers_sent() ? 'REDIRECT_SCRIPT' : '');
        break;
    default :
        $modx->webAlertAndQuit("No operation set in request.");
}
