<?php 
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('save_template')) {
	$e->setError(3);
	$e->dumpError();	
}
?>
<?php

$id = intval($_POST['id']);
$name = $modx->db->escape(trim($_POST['name']));				
$description = $modx->db->escape($_POST['description']);
$caption = $modx->db->escape($_POST['caption']);
$type = $modx->db->escape($_POST['type']);
$elements = $modx->db->escape($_POST['elements']);
$default_text = $modx->db->escape($_POST['default_text']);
$rank = isset ($_POST['rank']) ? $modx->db->escape($_POST['rank']) : 0;
$display = $modx->db->escape($_POST['display']);
$params = $modx->db->escape($_POST['params']);
$locked = $_POST['locked']=='on' ? 1 : 0 ;

//Kyle Jaebker - added category support
if (empty($_POST['newcategory']) && $_POST['categoryid'] > 0) {
    $categoryid = $modx->db->escape($_POST['categoryid']);
} elseif (empty($_POST['newcategory']) && $_POST['categoryid'] <= 0) {
    $categoryid = 0;
} else {
    include_once "categories.inc.php";
    $catCheck = checkCategory($modx->db->escape($_POST['newcategory']));
    if ($catCheck) {
        $categoryid = $catCheck;
    } else {
        $categoryid = newCategory($_POST['newcategory']);
    }
}

if($caption =="") {
	$caption  = $name? $name: "Untitled variable";
}

switch ($_POST['mode']) {
    case '300':

		// invoke OnBeforeTVFormSave event
		$modx->invokeEvent("OnBeforeTVFormSave",
								array(
									"mode"	=> "new",
									"id"	=> $id
							));	      	

		// disallow duplicate names for new tvs
		$sql = "SELECT COUNT(id) FROM {$dbase}.`{$table_prefix}site_tmplvars` WHERE name = '{$name}'";
		$rs = $modx->db->query($sql);
		$count = $modx->db->getValue($rs);
        $nameerror = false;
		if($count > 0) {
            $nameerror = true;
			$modx->event->alert(sprintf($_lang['duplicate_name_found_general'], $_lang['tv'], $name));
        }
        // disallow reserved names
        if(in_array($name, array('id', 'type', 'contentType', 'pagetitle', 'longtitle', 'description', 'alias', 'link_attributes', 'published', 'pub_date', 'unpub_date', 'parent', 'isfolder', 'introtext', 'content', 'richtext', 'template', 'menuindex', 'searchable', 'cacheable', 'createdby', 'createdon', 'editedby', 'editedon', 'deleted', 'deletedon', 'deletedby', 'publishedon', 'publishedby', 'menutitle', 'donthit', 'haskeywords', 'hasmetatags', 'privateweb', 'privatemgr', 'content_dispo', 'hidemenu','alias_visible'))) {
            $nameerror = true;
            $_POST['name'] = '';
			$modx->event->alert(sprintf($_lang['reserved_name_warning'], $_lang['tv'], $name));
        }
        if($nameerror) {
			// prepare a few variables prior to redisplaying form...
			$content = array();
			$_REQUEST['id'] = 0;
			$content['id'] = 0;
			$_GET['a'] = '300';
			$_GET['stay'] = $_POST['stay'];
			$content = array_merge($content, $_POST);
			$content['locked'] = $content['locked'] == 'on' ? 1: 0;
			$content['category'] = $_POST['categoryid'];

			include 'header.inc.php';
			include(dirname(dirname(__FILE__)).'/actions/mutate_tmplvars.dynamic.php');
			include 'footer.inc.php';
			
			exit;
		}

		// Add new TV
		$sql = "INSERT INTO $dbase.`".$table_prefix."site_tmplvars` (name, description, caption, type, elements, default_text, display,display_params, rank, locked, category) VALUES('".$name."', '".$description."', '".$caption."', '".$type."', '".$elements."', '".$default_text."', '".$display."', '".$params."', '".$rank."', '".$locked."', ".$categoryid.");";
		$rs = $modx->db->query($sql);
		if(!$rs){
			echo "\$rs not set! New variable not saved!";
		} else {	
			// get the id
			if(!$newid=$modx->db->getInsertId()) {
				echo "Couldn't get last insert key!";
				exit;
			}			
			
			// save access permissions
			saveTemplateAccess();			
			saveDocumentAccessPermissons();

			// invoke OnTVFormSave event
			$modx->invokeEvent("OnTVFormSave",
									array(
										"mode"	=> "new",
										"id"	=> $newid
								));	    	
								
			// empty cache
			include_once "cache_sync.class.processor.php";
			$sync = new synccache();
			$sync->setCachepath("../assets/cache/");
			$sync->setReport(false);
			$sync->emptyCache(); // first empty the cache		
			// finished emptying cache - redirect
			if($_POST['stay']!='') {
				$a = ($_POST['stay']=='2') ? "301&id=$newid":"300";
				$header="Location: index.php?a=".$a."&r=2&stay=".$_POST['stay'];
				header($header);
			} else {
				$header="Location: index.php?a=76&r=2";
				header($header);
			}
		}		
        break;
    case '301':	 	
		// invoke OnBeforeTVFormSave event
		$modx->invokeEvent("OnBeforeTVFormSave",
								array(
									"mode"	=> "upd",
									"id"	=> $id
							));	 

    	// update TV
		$sql = "UPDATE $dbase.`".$table_prefix."site_tmplvars` SET ";
        $sql .= "name='".$name."', ";
		$sql .= "description='".$description."', ";
        $sql .= "caption='".$caption."', ";
        $sql .= "type='".$type."', ";
        $sql .= "elements='".$elements."', ";
        $sql .= "default_text='".$default_text."', ";
        $sql .= "display='".$display."', "; 
        $sql .= "display_params='".$params."', ";         
        $sql .= "rank='".$rank."', ";
        $sql .= "locked='".$locked."', ";
        $sql .= "category=".$categoryid;
        $sql .= " WHERE id='".$id."';";
		$rs = $modx->db->query($sql);
		if(!$rs){
			echo "\$rs not set! Edited variable not saved!";
		} else {		

			// save access permissions
			saveTemplateAccess();			
			saveDocumentAccessPermissons();

			// invoke OnTVFormSave event
			$modx->invokeEvent("OnTVFormSave",
									array(
										"mode"	=> "upd",
										"id"	=> $id
								));	 

			// empty cache
			include_once "cache_sync.class.processor.php";
			$sync = new synccache();
			$sync->setCachepath("../assets/cache/");
			$sync->setReport(false);
			$sync->emptyCache(); // first empty the cache		
			// finished emptying cache - redirect	
			if($_POST['stay']!='') {
				$a = ($_POST['stay']=='2') ? "301&id=$id":"300";
				$header="Location: index.php?a=".$a."&r=2&stay=".$_POST['stay'];
				header($header);
			} else {
				$header="Location: index.php?a=76&r=2";
				header($header);
			}
		}				
		
        break;
    default:
	?>
	Erm... You supposed to be here now?
	<?php
}

function saveTemplateAccess() {	
	global $dbase,$table_prefix;
	global $id,$newid;
	global $modx;

	if($newid) $id = $newid;
	$templates =  $_POST['template']; // get muli-templates based on S.BRENNAN mod
		
	// update template selections
	$tbl = "$dbase.`".$table_prefix."site_tmplvar_templates`";
	
    $getRankArray = array();

    $getRank = $modx->db->select("templateid, rank", $tbl, "tmplvarid=$id");

    while( $row = $modx->db->getRow( $getRank ) ) {
    $getRankArray[$row['templateid']] = $row['rank'];
    }
   
	
	$modx->db->query("DELETE FROM $tbl WHERE tmplvarid = $id");
	for($i=0;$i<count($templates);$i++){
	    $setRank = ($getRankArray[$templates[$i]]) ? $getRankArray[$templates[$i]] : 0;
		$modx->db->query("INSERT INTO $tbl (tmplvarid,templateid,rank) VALUES($id,".$templates[$i].",$setRank);");
	}	
}

function saveDocumentAccessPermissons(){
	global $id,$newid;
	global $modx,$dbase,$table_prefix,$use_udperms;

	if($newid) $id = $newid;
	$docgroups = $_POST['docgroups'];

	// check for permission update access
	if($use_udperms==1) {
		// delete old permissions on the tv
		$sql = "DELETE FROM $dbase.`".$table_prefix."site_tmplvar_access` WHERE tmplvarid=$id;";
		$rs = $modx->db->query($sql);
		if(!$rs){
			echo "An error occurred while attempting to delete previous template variable access permission entries.";
			exit;
		}	
		if(is_array($docgroups)) {
			foreach ($docgroups as $dgkey=>$value) {
				$sql = "INSERT INTO $dbase.`".$table_prefix."site_tmplvar_access` (tmplvarid,documentgroup) values($id,".stripslashes($value).")";
				$rs = $modx->db->query($sql);
				if(!$rs){
					echo "An error occured while attempting to save template variable acess permissions.";
					exit;
				}
			}
		}
	}
}
?>