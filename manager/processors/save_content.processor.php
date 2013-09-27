<?php
if (IN_MANAGER_MODE != "true")
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

if (!$modx->hasPermission('save_document')) {
	$e->setError(3);
	$e->dumpError();
}

// preprocess POST values
$id = is_numeric($_POST['id']) ? $_POST['id'] : '';
$introtext = $modx->db->escape($_POST['introtext']);
$content = $modx->db->escape($_POST['ta']);
$pagetitle = $modx->db->escape($_POST['pagetitle']);
$description = $modx->db->escape($_POST['description']);
$alias = $modx->db->escape($_POST['alias']);
$link_attributes = $modx->db->escape($_POST['link_attributes']);
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
$keywords = $_POST['keywords'];
$metatags = $_POST['metatags'];
$contentType = $modx->db->escape($_POST['contentType']);
$contentdispo = intval($_POST['content_dispo']);
$longtitle = $modx->db->escape($_POST['longtitle']);
$donthit = intval($_POST['donthit']);
$menutitle = $modx->db->escape($_POST['menutitle']);
$hidemenu = intval($_POST['hidemenu']);
$aliasvisible = $_POST['alias_visible'];

/************* webber ********/
$sd=isset($_POST['dir'])?'&dir='.$_POST['dir']:'&dir=DESC';
$sb=isset($_POST['sort'])?'&sort='.$_POST['sort']:'&sort=pub_date';
$pg=isset($_POST['page'])?'&page='.(int)$_POST['page']:'';
$add_path=$sd.$sb.$pg;



if (trim($pagetitle == "")) {
	if ($type == "reference") {
		$pagetitle = $_lang['untitled_weblink'];
	} else {
		$pagetitle = $_lang['untitled_resource'];
	}
}

// get table names
$tbl_document_groups            = $modx->getFullTableName('document_groups');
$tbl_documentgroup_names        = $modx->getFullTableName('documentgroup_names');
$tbl_member_groups              = $modx->getFullTableName('member_groups');
$tbl_membergroup_access         = $modx->getFullTableName('membergroup_access');
$tbl_keyword_xref               = $modx->getFullTableName('keyword_xref');
$tbl_site_content               = $modx->getFullTableName('site_content');
$tbl_site_content_metatags      = $modx->getFullTableName('site_content_metatags');
$tbl_site_tmplvar_access        = $modx->getFullTableName('site_tmplvar_access');
$tbl_site_tmplvar_contentvalues = $modx->getFullTableName('site_tmplvar_contentvalues');
$tbl_site_tmplvar_templates     = $modx->getFullTableName('site_tmplvar_templates');
$tbl_site_tmplvars              = $modx->getFullTableName('site_tmplvars');

$actionToTake = "new";
if ($_POST['mode'] == '73' || $_POST['mode'] == '27') {
	$actionToTake = "edit";
}

// friendly url alias checks
if ($friendly_urls) {
	// auto assign alias
	if (!$alias && $automatic_alias) {
		$alias = strtolower($modx->stripAlias(trim($pagetitle)));
		if(!$allow_duplicate_alias) {
			if ($modx->db->getValue("SELECT COUNT(id) FROM " . $tbl_site_content . " WHERE id<>'$id' AND alias='$alias'") != 0) {
				$cnt = 1;
				$tempAlias = $alias;
				while ($modx->db->getValue("SELECT COUNT(id) FROM " . $tbl_site_content . " WHERE id<>'$id' AND alias='$tempAlias'") != 0) {
					$tempAlias = $alias;
					$tempAlias .= $cnt;
					$cnt++;
				}
				$alias = $tempAlias;
			}
		}else{
                if ($modx->db->getValue("SELECT COUNT(id) FROM " . $tbl_site_content . " WHERE id<>'$id' AND parent=$parent AND alias='$alias'") != 0) {
                        $cnt = 1;
                        $tempAlias = $alias;
                        while ($modx->db->getValue("SELECT COUNT(id) FROM " . $tbl_site_content . " WHERE id<>'$id' AND parent=$parent AND alias='$tempAlias'") != 0) {
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
			$docid = $modx->db->getValue("SELECT id FROM " . $tbl_site_content . " WHERE id<>'$id' AND alias='$alias' AND parent=$parent LIMIT 1");
		} else {
			$docid = $modx->db->getValue("SELECT id FROM " . $tbl_site_content . " WHERE id<>'$id' AND alias='$alias' LIMIT 1");
		}
		if ($docid > 0) {
			if ($actionToTake == 'edit') {
				$modx->manager->saveFormValues(27);
				$url = "index.php?a=27&id=" . $id;
				include_once "header.inc.php";
				$modx->webAlert(sprintf($_lang["duplicate_alias_found"], $docid, $alias), $url);
				include_once "footer.inc.php";
				exit;
			} else {
				$modx->manager->saveFormValues(4);
				$url = "index.php?a=4";
				include_once "header.inc.php";
				$modx->webAlert(sprintf($_lang["duplicate_alias_found"], $docid, $alias), $url);
				include_once "footer.inc.php";
				exit;
			}
		}
	}

	// strip alias of special characters
	elseif ($alias) {
		$alias = $modx->stripAlias($alias);
		//webber
		$docid = $modx->db->getValue("SELECT id FROM " . $tbl_site_content . " WHERE id<>'$id' AND alias='$alias' AND parent=$parent LIMIT 1");
                if ($docid > 0) {
                        if ($actionToTake == 'edit') {
                                $modx->manager->saveFormValues(27);
                                $url = "index.php?a=27&id=" . $id;
                                include_once "header.inc.php";
                                $modx->webAlert(sprintf($_lang["duplicate_alias_found"], $docid, $alias), $url);
                                include_once "footer.inc.php";
                                exit;
                        } else {
                                $modx->manager->saveFormValues(4);
                                $url = "index.php?a=4";
                                include_once "header.inc.php";
                                $modx->webAlert(sprintf($_lang["duplicate_alias_found"], $docid, $alias), $url);
                                include_once "footer.inc.php";
                                exit;
                        }
                }
        //end webber        
	}
}
elseif ($alias) {
	$alias = $modx->stripAlias($alias);
}

// determine published status
$currentdate = time() + $modx->config['server_offset_time'];

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
$tmplvars = array ();
if ($_SESSION['mgrDocgroups']) {
	$docgrp = implode(",", $_SESSION['mgrDocgroups']);
}

// ensure that user has not made this document inaccessible to themselves
if($_SESSION['mgrRole'] != 1 && is_array($document_groups)) {
	$document_group_list = implode(',', $document_groups);
	$document_group_list = implode(',', array_filter(explode(',',$document_group_list), 'is_numeric'));
	if(!empty($document_group_list)) {
		$sql = "SELECT COUNT(mg.id) FROM {$tbl_membergroup_access} mga, {$tbl_member_groups} mg
 WHERE mga.membergroup = mg.user_group
 AND mga.documentgroup IN({$document_group_list})
 AND mg.member = {$_SESSION['mgrInternalKey']};";
		$rs = $modx->db->query($sql);
		$count = $modx->db->getValue($rs);
		if($count == 0) {
			if ($actionToTake == 'edit') {
				$modx->manager->saveFormValues(27);
				$url = "index.php?a=27&id=" . $id;
				include_once "header.inc.php";
				$modx->webAlert(sprintf($_lang["resource_permissions_error"]), $url);
				include_once "footer.inc.php";
				exit;
			} else {
				$modx->manager->saveFormValues(4);
				$url = "index.php?a=4";
				include_once "header.inc.php";
				$modx->webAlert(sprintf($_lang["resource_permissions_error"]), $url);
				include_once "footer.inc.php";
				exit;
			}
		}
	}
}

$sql = "SELECT DISTINCT tv.*, IF(tvc.value!='',tvc.value,tv.default_text) as value ";
$sql .= "FROM $tbl_site_tmplvars AS tv ";
$sql .= "INNER JOIN $tbl_site_tmplvar_templates AS tvtpl ON tvtpl.tmplvarid = tv.id ";
$sql .= "LEFT JOIN $tbl_site_tmplvar_contentvalues AS tvc ON tvc.tmplvarid=tv.id AND tvc.contentid = '$id' ";
$sql .= "LEFT JOIN $tbl_site_tmplvar_access tva ON tva.tmplvarid=tv.id  ";
$sql .= "WHERE tvtpl.templateid = '" . $template . "' AND (1='" . $_SESSION['mgrRole'] . "' OR ISNULL(tva.documentgroup)" . ((!$docgrp) ? "" : " OR tva.documentgroup IN ($docgrp)") . ") ORDER BY tv.rank;";
$rs = $modx->db->query($sql);
while ($row = $modx->db->getRow($rs)) {
	$tmplvar = '';
	switch ($row['type']) {
		case 'url':
			$tmplvar = $_POST["tv" . $row['id']];
			if ($_POST["tv" . $row['id'] . '_prefix'] != '--') {
				$tmplvar = str_replace(array (
					"feed://",
					"ftp://",
					"http://",
					"https://",
					"mailto:"
				), "", $tmplvar);
				$tmplvar = $_POST["tv" . $row['id'] . '_prefix'] . $tmplvar;
			}
		break;
		case 'file':
			$tmplvar = $_POST["tv" . $row['id']];
		break;
		default:
			if (is_array($_POST["tv" . $row['id']])) {
				// handles checkboxes & multiple selects elements
				$feature_insert = array ();
				$lst = $_POST["tv" . $row['id']];
				while (list ($featureValue, $feature_item) = each($lst)) {
					$feature_insert[count($feature_insert)] = $feature_item;
				}
				$tmplvar = implode("||", $feature_insert);
			} else {
				$tmplvar = $_POST["tv" . $row['id']];
			}
		break;
	}
	// save value if it was modified
	if (strlen($tmplvar) > 0 && $tmplvar != $row['default_text']) {
		$tmplvars[$row['id']] = array (
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
	$rs = $modx->db->select('*', $tbl_site_content, 'id='.$id);
	$limit = $modx->db->getRecordCount($rs);
	if ($limit > 1) {
		$e->setError(6);
		$e->dumpError();
	}
	if ($limit < 1) {
		$e->setError(7);
		$e->dumpError();
	}
	$existingDocument = $modx->db->getRow($rs);
}

// check to see if the user is allowed to save the document in the place he wants to save it in
if ($use_udperms == 1) {
	if ($existingDocument['parent'] != $parent) {
		include_once "./processors/user_documents_permissions.class.php";
		$udperms = new udperms();
		$udperms->user = $modx->getLoginUserID();
		$udperms->document = $parent;
		$udperms->role = $_SESSION['mgrRole'];

		if (!$udperms->checkPermissions()) {
			if ($actionToTake == 'edit') {
				$modx->manager->saveFormValues(27);
				$url = "index.php?a=27&id=" . $id;
				include_once "header.inc.php";
				$modx->webAlert(sprintf($_lang['access_permission_parent_denied'], $docid, $alias), $url);
				include_once "footer.inc.php";
				exit;
			} else {
				$modx->manager->saveFormValues(4);
				$url = "index.php?a=4";
				include_once "header.inc.php";
				$modx->webAlert(sprintf($_lang['access_permission_parent_denied'], $docid, $alias), $url);
				include_once "footer.inc.php";
				exit;
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
				$rs = $modx->db->select('MIN(T0.id)+1', $from, "T1.id IS NULL");
				$id = $modx->db->getValue($rs);
				break;
			case '2':
				$rs = $modx->db->select('MAX(id)+1',$tbl_site_content);
				$id = $modx->db->getValue($rs);
			break;
			
			default:
				$id = '';
		}
		
		$modx->invokeEvent("OnBeforeDocFormSave", array (
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

		$sql = "INSERT INTO $tbl_site_content (introtext,content, pagetitle, longtitle, type, description, alias, link_attributes, isfolder, richtext, published, parent, template, menuindex, searchable, cacheable, createdby, createdon, editedby, editedon, publishedby, publishedon, pub_date, unpub_date, contentType, content_dispo, donthit, menutitle, hidemenu, alias_visible)
						VALUES('" . $introtext . "','" . $content . "', '" . $pagetitle . "', '" . $longtitle . "', '" . $type . "', '" . $description . "', '" . $alias . "', '" . $link_attributes . "', '" . $isfolder . "', '" . $richtext . "', '" . $published . "', '" . $parent . "', '" . $template . "', '" . $menuindex . "', '" . $searchable . "', '" . $cacheable . "', '" . $modx->getLoginUserID() . "', " . $currentdate . ", '" . $modx->getLoginUserID() . "', " . $currentdate . ", " . $publishedby . ", " . $publishedon . ", '$pub_date', '$unpub_date', '$contentType', '$contentdispo', '$donthit', '$menutitle', '$hidemenu', '$aliasvisible')";

		$rs = $modx->db->query($sql);
		if (!$rs) {
			$modx->manager->saveFormValues(27);
			echo "An error occured while attempting to save the new document: " . $modx->db->getLastError();
			exit;
		}

		if (!$key = $modx->db->getInsertId()) {
			$modx->manager->saveFormValues(27);
			echo "Couldn't get last insert key!";
			exit;
		}

		$tvChanges = array();
		foreach ($tmplvars as $field => $value) {
			if (is_array($value)) {
				$tvId = $value[0];
				$tvVal = $value[1];
				$tvChanges[] = array('tmplvarid' => $tvId, 'contentid' => $key, 'value' => $modx->db->escape($tvVal));
			}
		}
		if (!empty($tvChanges)) {
			foreach ($tvChanges as $tv) {
				$rs = $modx->db->insert($tv, $tbl_site_tmplvar_contentvalues);
			}
		}

		// document access permissions
		$docgrp_save_attempt = false;
		if ($use_udperms == 1 && is_array($document_groups)) {
			$new_groups = array();
			foreach ($document_groups as $value_pair) {
				// first, split the pair (this is a new document, so ignore the second value
				list($group) = explode(',', $value_pair); // @see actions/mutate_content.dynamic.php @ line 1138 (permissions list)
				$new_groups[] = '('.(int)$group.','.$key.')';
			}
			$saved = true;
			if (!empty($new_groups)) {
				$sql = 'INSERT INTO '.$tbl_document_groups.' (document_group, document) VALUES '. implode(',', $new_groups);
				$saved = $modx->db->query($sql) ? $saved : false;
				$docgrp_save_attempt = true;
			}
		} else {
			$isManager = $modx->hasPermission('access_permissions');
			$isWeb     = $modx->hasPermission('web_access_permissions');
			if($use_udperms && !($isManager || $isWeb) && $parent != 0) {
				// inherit document access permissions
				$sql = "INSERT INTO $tbl_document_groups (document_group, document) SELECT document_group, $key FROM $tbl_document_groups WHERE document = $parent";
				$saved = $modx->db->query($sql);
				$docgrp_save_attempt = true;
			}
		}
		if ($docgrp_save_attempt && !$saved) {
			$modx->manager->saveFormValues(27);
			echo "An error occured while attempting to add the document to a document_group.";
			exit;
		}


		// update parent folder status
		if ($parent != 0) {
			$fields = array('isfolder' => 1);
			$rs = $modx->db->update($fields, $tbl_site_content, 'id='.$_REQUEST['parent']);
			if (!$rs) {
				echo "An error occured while attempting to change the document's parent to a folder.";
			}
		}

		// save META Keywords
		saveMETAKeywords($key);

		// invoke OnDocFormSave event
		$modx->invokeEvent("OnDocFormSave", array (
			"mode" => "new",
			"id" => $key
		));

		// secure web documents - flag as private
		include MODX_MANAGER_PATH . "includes/secure_web_documents.inc.php";
		secureWebDocument($key);

		// secure manager documents - flag as private
		include MODX_MANAGER_PATH . "includes/secure_mgr_documents.inc.php";
		secureMgrDocument($key);

		if ($syncsite == 1) {
			// empty cache
			include_once "cache_sync.class.processor.php";
			$sync = new synccache();
			$sync->setCachepath("../assets/cache/");
			$sync->setReport(false);
			$sync->emptyCache();
		}

		// redirect/stay options
		if ($_POST['stay'] != '') {
			// weblink
			if ($_POST['mode'] == "72")
				$a = ($_POST['stay'] == '2') ? "27&id=$key" : "72&pid=$parent";
			// document
			if ($_POST['mode'] == "4")
				$a = ($_POST['stay'] == '2') ? "27&id=$key" : "4&pid=$parent";
			$header = "Location: index.php?a=" . $a . "&r=1&stay=" . $_POST['stay'];
		} else {
			$header = "Location: index.php?r=1&id=$key&a=7&dv=1";
		}
		header($header);

		break;
	case 'edit' :

		// get the document's current parent
		$rs = $modx->db->select('parent', $tbl_site_content, 'id='.$_REQUEST['id']);
		if (!$rs) {
			$modx->manager->saveFormValues(27);
			echo "An error occured while attempting to find the document's current parent.";
			exit;
		}
		
		$row = $modx->db->getRow($rs);
		$oldparent = $row['parent'];
		$doctype = $row['type'];

		if ($id == $site_start && $published == 0) {
			$modx->manager->saveFormValues(27);
			echo "Document is linked to site_start variable and cannot be unpublished!";
			exit;
		}
		if ($id == $site_start && ($pub_date != "0" || $unpub_date != "0")) {
			$modx->manager->saveFormValues(27);
			echo "Document is linked to site_start variable and cannot have publish or unpublish dates set!";
			exit;
		}
		if ($parent == $id) {
			$modx->manager->saveFormValues(27);
			echo "Document can not be it's own parent!";
			exit;
		}
		// check to see document is a folder
		$rs = $modx->db->select('COUNT(id)', $tbl_site_content, 'parent='. $_REQUEST['id']);
		if (!$rs) {
			$modx->manager->saveFormValues(27);
			echo "An error occured while attempting to find the document's children.";
			exit;
		}
		$row = $modx->db->getRow($rs);
		if ($row['COUNT(id)'] > 0) {
			$isfolder = 1;
		}

		// set publishedon and publishedby
		$was_published = $modx->db->getValue("SELECT published FROM $tbl_site_content WHERE id='$id'");

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
		}
		elseif ($was_published && !$published) {
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
		$sql = "UPDATE $tbl_site_content SET introtext='$introtext', content='$content', pagetitle='$pagetitle', longtitle='$longtitle', type='$type', description='$description', alias='$alias', link_attributes='$link_attributes',
				isfolder=$isfolder, richtext=$richtext, published=$published, pub_date=$pub_date, unpub_date=$unpub_date, parent=$parent, template=$template, menuindex='$menuindex',
				searchable=$searchable, cacheable=$cacheable, editedby=" . $modx->getLoginUserID() . ", editedon=" . $currentdate . ", publishedon=$publishedon, publishedby=$publishedby, contentType='$contentType', content_dispo='$contentdispo', donthit='$donthit', menutitle='$menutitle', hidemenu='$hidemenu', alias_visible='$aliasvisible'  WHERE id=$id;";

		$rs = $modx->db->query($sql);
		if (!$rs) {
			echo "An error occured while attempting to save the edited document. The generated SQL is: <i> $sql </i>.";
		}

		// update template variables
		$rs = $modx->db->select('id, tmplvarid', $tbl_site_tmplvar_contentvalues, 'contentid='. $id);
		$tvIds = array ();
		while ($row = $modx->db->getRow($rs)) {
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
					$tvChanges[] = array(array('tmplvarid' => $tvId, 'contentid' => $id, 'value' => $modx->db->escape($tvVal)), array('id' => $tvIds[$tvId]));
				} else {
					$tvAdded[] = array('tmplvarid' => $tvId, 'contentid' => $id, 'value' => $modx->db->escape($tvVal));
				}
			}
		}

		if (!empty($tvDeletions)) {
			$rs = $modx->db->delete($tbl_site_tmplvar_contentvalues, 'id IN('.implode(',', $tvDeletions).')');
		}
			
		if (!empty($tvAdded)) {
			foreach ($tvAdded as $tv) {
				$rs = $modx->db->insert($tv, $tbl_site_tmplvar_contentvalues);
			}
		}
		
		if (!empty($tvChanges)) {
			foreach ($tvChanges as $tv) {
				$rs = $modx->db->update($tv[0], $tbl_site_tmplvar_contentvalues, 'id='.$tv[1]['id']);
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
			$sql = 'SELECT groups.id, groups.document_group FROM '.$tbl_document_groups.' AS groups '.
			       'LEFT JOIN '.$tbl_documentgroup_names.' AS dgn ON dgn.id = groups.document_group '.
			       'WHERE ((1='.(int)$isManager.' AND dgn.private_memgroup) '.
			       'OR    (1='.(int)$isWeb.' AND dgn.private_webgroup))'.
			       'AND groups.document = '.$id;
			$rs = $modx->db->query($sql);
			$old_groups = array();
			while ($row = $modx->db->getRow($rs)) $old_groups[$row['document_group']] = $row['id'];

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
			$saved = true;
			if (!empty($insertions)) {
				$sql_insert = 'INSERT INTO '.$tbl_document_groups.' (document_group, document) VALUES '.implode(',', $insertions);
				$saved = $modx->db->query($sql_insert) ? $saved : false;
			}
			if (!empty($old_groups)) {
				$sql_delete = 'DELETE FROM '.$tbl_document_groups.' WHERE id IN ('.implode(',', $old_groups).')';
				$saved = $modx->db->query($sql_delete) ? $saved : false;
			}
			// necessary to remove all permissions as document is public
			if ((isset($_POST['chkalldocs']) && $_POST['chkalldocs'] == 'on')) {
				$sql_delete = 'DELETE FROM '.$tbl_document_groups.' WHERE document='.$id;
				$saved = $modx->db->query($sql_delete) ? $saved : false;
			}
			if (!$saved) {
				$modx->manager->saveFormValues(27);
				echo "An error occured while saving document groups.";
				exit;
			}
		}

		// do the parent stuff
		if ($parent != 0) {
			$fields = array('isfolder' => 1);
			$rs = $modx->db->update($fields, $tbl_site_content, 'id='.$_REQUEST['parent']);
			if (!$rs) {
				echo "An error occured while attempting to change the new parent to a folder.";
			}
		}

		// finished moving the document, now check to see if the old_parent should no longer be a folder
		$rs = $modx->db->select('COUNT(id)', $tbl_site_content, 'parent='.$oldparent);
		if (!$rs) {
			echo "An error occured while attempting to find the old parents' children.";
		}
		$row = $modx->db->getRow($rs);
		$limit = $row['COUNT(id)'];

		if ($limit == 0) {
			$fields = array('isfolder' => 0);
			$rs = $modx->db->update($fields, $tbl_site_content, 'id='.$oldparent);
			if (!$rs) {
				echo "An error occured while attempting to change the old parent to a regular document.";
			}
		}

		// save META Keywords
		saveMETAKeywords($id);

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

		if ($syncsite == 1) {
			// empty cache
			include_once "cache_sync.class.processor.php";
			$sync = new synccache();
			$sync->setCachepath("../assets/cache/");
			$sync->setReport(false);
			$sync->emptyCache();
		}
		
		if ($_POST['refresh_preview'] == '1')
			$header = "Location: ../index.php?id=$id&z=manprev";
		else {
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
				$header = "Location: index.php?r=1&id=$id&a=7&dv=1".$add_path;
			}
		}
		header($header);
		break;
	default :
		header("Location: index.php?a=7");
		exit;
}

/**
 * Format alias to be URL-safe
 *
 * @deprecated Use $modx->stripAlias()
 * @param string Alias to be formatted
 * @return string Safe alias
 */
function stripAlias($alias) {
   return $GLOBALS['modx']->stripAlias($alias);
}

// -- Save META Keywords --
function saveMETAKeywords($id) {
	global $modx, $keywords, $metatags,
	       $tbl_keyword_xref,
	       $tbl_site_content,
	       $tbl_site_content_metatags;

	if ($modx->hasPermission('edit_doc_metatags')) {
		// keywords - remove old keywords first
		$modx->db->delete($tbl_keyword_xref, "content_id=$id");
		for ($i = 0; $i < count($keywords); $i++) {
			$kwid = $keywords[$i];
			$flds = array (
				'content_id' => $id,
				'keyword_id' => $kwid
			);
			$modx->db->insert($flds, $tbl_keyword_xref);
		}
		// meta tags - remove old tags first
		$modx->db->delete($tbl_site_content_metatags, "content_id=$id");
		for ($i = 0; $i < count($metatags); $i++) {
			$kwid = $metatags[$i];
			$flds = array (
				'content_id' => $id,
				'metatag_id' => $kwid
			);
			$modx->db->insert($flds, $tbl_site_content_metatags);
		}
		$flds = array (
			'haskeywords' => (count($keywords) ? 1 : 0),
			'hasmetatags' => (count($metatags) ? 1 : 0)
		);
		$modx->db->update($flds, $tbl_site_content, "id=$id");
	}
}

?>
