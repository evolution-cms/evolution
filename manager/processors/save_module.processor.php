<?php 
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('save_module')) {	
	$e->setError(3);
	$e->dumpError();	
}
?>
<?php

$id = intval($_POST['id']);
$name = $modx->db->escape(trim($_POST['name']));
$description = $modx->db->escape($_POST['description']);
$resourcefile = $modx->db->escape($_POST['resourcefile']);
$enable_resource = $_POST['enable_resource']=='on' ? 1 : 0 ;
$icon = $modx->db->escape($_POST['icon']);
//$category = intval($_POST['category']);
$disabled = $_POST['disabled']=='on' ? 1 : 0 ;
$wrap = $_POST['wrap']=='on' ? 1 : 0 ;
$locked = $_POST['locked']=='on' ? 1 : 0 ;
$modulecode = $modx->db->escape($_POST['post']);
$properties = $modx->db->escape($_POST['properties']);
$enable_sharedparams = $_POST['enable_sharedparams']=='on' ? 1 : 0 ;
$guid = $modx->db->escape($_POST['guid']);

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

if($name=="") $name = "Untitled module";

switch ($_POST['mode']) {
    case '107':
		// invoke OnBeforeModFormSave event
		$modx->invokeEvent("OnBeforeModFormSave",
							array(
								"mode"	=> "new",
								"id"	=> $id
							));
								
		// disallow duplicate names for new modules
		$sql = "SELECT COUNT(id) FROM {$dbase}.`{$table_prefix}site_modules` WHERE name = '{$name}'";
		$rs = $modx->db->query($sql);
		$count = $modx->db->getValue($rs);
		if($count > 0) {
			$modx->event->alert(sprintf($_lang['duplicate_name_found_module'], $name));

			// prepare a few variables prior to redisplaying form...
			$content = array();
			$_REQUEST['a'] = '107';
			$_GET['a'] = '107';
			$_GET['stay'] = $_POST['stay'];
			$content = array_merge($content, $_POST);
			$content['wrap'] = $wrap;
			$content['disabled'] = $disabled;
			$content['locked'] = $locked;
			$content['plugincode'] = $_POST['post'];
			$content['category'] = $_POST['categoryid'];
			$content['properties'] = $_POST['properties'];
			$content['modulecode'] = $_POST['post'];
			$content['enable_resource'] = $enable_resource;
			$content['enable_sharedparams'] = $enable_sharedparams;
			$content['usrgroups'] = $_POST['usrgroups'];


			include 'header.inc.php';
			include(dirname(dirname(__FILE__)).'/actions/mutate_module.dynamic.php');
			include 'footer.inc.php';
			
			exit;
		}

		// save the new module
		$sql = "INSERT INTO ".$modx->getFullTableName("site_modules")." (name, description, disabled, wrap, locked, icon, resourcefile, enable_resource, category, enable_sharedparams, guid, modulecode, properties) VALUES('".$name."', '".$description."', '".$disabled."', '".$wrap."', '".$locked."', '".$icon."', '".$resourcefile."', '".$enable_resource."', '".$categoryid."', '".$enable_sharedparams."', '".$guid."', '".$modulecode."', '".$properties."');";
		$rs = $modx->db->query($sql);
		if(!$rs){
			echo "\$rs not set! New module not saved!";
			exit;
		} 
		else {	
			// get the id
			if(!$newid=$modx->db->getInsertId()) {
				echo "Couldn't get last insert key!";
				exit;
			}
			
			// save user group access permissions
			saveUserGroupAccessPermissons();
			
			// invoke OnModFormSave event
			$modx->invokeEvent("OnModFormSave",
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
				$a = ($_POST['stay']=='2') ? "108&id=$newid":"107";
				$header="Location: index.php?a=".$a."&r=2&stay=".$_POST['stay'];
				header($header);
			} else {
				$header="Location: index.php?a=106&r=2";
				header($header);
			}
		}		
        break;
    case '108':
		// invoke OnBeforeModFormSave event
		$modx->invokeEvent("OnBeforeModFormSave",
							array(
								"mode"	=> "upd",
								"id"	=> $id
							));	
								
		// save the edited module	
		$sql = "UPDATE ".$modx->getFullTableName("site_modules")." SET name='".$name."', description='".$description."', icon='".$icon."', enable_resource='".$enable_resource."', resourcefile='".$resourcefile."', disabled='".$disabled."', wrap='".$wrap."', locked='".$locked."', category='".$categoryid."', enable_sharedparams='".$enable_sharedparams."', guid='".$guid."', modulecode='".$modulecode."', properties='".$properties."'  WHERE id='".$id."';";
		$rs = $modx->db->query($sql);
		if(!$rs){
			echo "\$rs not set! Edited module not saved!".$modx->db->getLastError();
			exit;
		} 
		else {	
			// save user group access permissions
			saveUserGroupAccessPermissons();
				
			// invoke OnModFormSave event
			$modx->invokeEvent("OnModFormSave",
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
				$a = ($_POST['stay']=='2') ? "108&id=$id":"107";
				$header="Location: index.php?a=".$a."&r=2&stay=".$_POST['stay'];
				header($header);
			} else {
				$header="Location: index.php?a=106&r=2";
				header($header);
			}
		}		
        break;        
    default:
    	// redirect to view modules
		$header="Location: index.php?a=106&r=2";
		header($header);
}

// saves module user group access
function saveUserGroupAccessPermissons(){
	global $modx;
	global $id,$newid;
	global $use_udperms;

	if($newid) $id = $newid;
	$usrgroups = $_POST['usrgroups'];

	// check for permission update access
	if($use_udperms==1) {
		// delete old permissions on the module
		$sql = "DELETE FROM ".$modx->getFullTableName("site_module_access")." WHERE module=$id;";
		$rs = $modx->db->query($sql);
		if(!$rs){
			echo "An error occured while attempting to delete previous module user access permission entries.";
			exit;
		}	
		if(is_array($usrgroups)) {
			foreach ($usrgroups as $ugkey=>$value) {
				$sql = "INSERT INTO ".$modx->getFullTableName("site_module_access")." (module,usergroup) values($id,".stripslashes($value).")";
				$rs = $modx->db->query($sql);
				if(!$rs){
					echo "An error occured while attempting to save module user acess permissions.";
					exit;
				}
			}
		}
	}
}
?>