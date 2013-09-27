<?php 
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('save_snippet')) {
	$e->setError(3);
	$e->dumpError();	
}
?>
<?php

$id = intval($_POST['id']);
$name = $modx->db->escape(trim($_POST['name']));
$description = $modx->db->escape($_POST['description']);
$locked = $_POST['locked']=='on' ? 1 : 0 ;
$snippet = trim($modx->db->escape($_POST['post']));
// strip out PHP tags from snippets
if ( strncmp($snippet, "<?", 2) == 0 ) {
    $snippet = substr($snippet, 2);
    if ( strncmp( $snippet, "php", 3 ) == 0 ) $snippet = substr($snippet, 3);
    if ( substr($snippet, -2, 2) == '?>' ) $snippet = substr($snippet, 0, -2);
}
$properties = $modx->db->escape($_POST['properties']);
$moduleguid = $modx->db->escape($_POST['moduleguid']);
$sysevents = $_POST['sysevents'];

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

if($name=="") $name = "Untitled snippet";

switch ($_POST['mode']) {
    case '23':

		// invoke OnBeforeSnipFormSave event
		$modx->invokeEvent("OnBeforeSnipFormSave",
								array(
									"mode"	=> "new",
									"id"	=> $id
								));
								
		// disallow duplicate names for new snippets
		$sql = "SELECT COUNT(id) FROM {$dbase}.`{$table_prefix}site_snippets` WHERE name = '{$name}'";
		$rs = $modx->db->query($sql);
		$count = $modx->db->getValue($rs);
		if($count > 0) {
			$modx->event->alert(sprintf($_lang['duplicate_name_found_general'], $_lang["snippet"], $name));

			// prepare a few variables prior to redisplaying form...
			$_REQUEST['id'] = 0;
			$_REQUEST['a'] = '23';
			$_GET['a'] = '23';
			$content = array();
			$content['id'] = 0;
			$content = array_merge($content, $_POST);
			$content['locked'] = $content['locked'] == 'on' ? 1: 0;
			$content['category'] = $_POST['categoryid'];
			$content['snippet'] = preg_replace("/^\s*\<\?php/m", '', $_POST['post']);
			$content['snippet'] = preg_replace("/\?\>\s*/m", '', $content['snippet']);

			include 'header.inc.php';
			include(dirname(dirname(__FILE__)).'/actions/mutate_snippet.dynamic.php');
			include 'footer.inc.php';
			
			exit;
		}

		//do stuff to save the new doc
		$sql = "INSERT INTO $dbase.`".$table_prefix."site_snippets` (name, description, snippet, moduleguid, locked, properties, category) VALUES('".$name."', '".$description."', '".$snippet."', '".$moduleguid."', '".$locked."','".$properties."', '".$categoryid."');";
		$rs = $modx->db->query($sql);
		if(!$rs){
			echo "\$rs not set! New snippet not saved!";
			exit;
		} 
		else {	
			// get the id
			if(!$newid=$modx->db->getInsertId()) {
				echo "Couldn't get last insert key!";
				exit;
			}

			// invoke OnSnipFormSave event
			$modx->invokeEvent("OnSnipFormSave",
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
				$a = ($_POST['stay']=='2') ? "22&id=$newid":"23";
				$header="Location: index.php?a=".$a."&r=2&stay=".$_POST['stay'];
				header($header);
			} else {
				$header="Location: index.php?a=76&r=2";
				header($header);
			}
		}		
        break;
    case '22':
		// invoke OnBeforeSnipFormSave event
		$modx->invokeEvent("OnBeforeSnipFormSave",
								array(
									"mode"	=> "upd",
									"id"	=> $id
								));	
								
		//do stuff to save the edited doc	
		$sql = "UPDATE $dbase.`".$table_prefix."site_snippets` SET name='".$name."', description='".$description."', snippet='".$snippet."', moduleguid='".$moduleguid."', locked='".$locked."', properties='".$properties."', category='".$categoryid."'  WHERE id='".$id."';";
		$rs = $modx->db->query($sql);
		if(!$rs){
			echo "\$rs not set! Edited snippet not saved!";
			exit;
		} 
		else {		
			// invoke OnSnipFormSave event
			$modx->invokeEvent("OnSnipFormSave",
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
			if($_POST['runsnippet']) run_snippet($snippet);
			// finished emptying cache - redirect	
			if($_POST['stay']!='') {
				$a = ($_POST['stay']=='2') ? "22&id=$id":"23";
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
?>