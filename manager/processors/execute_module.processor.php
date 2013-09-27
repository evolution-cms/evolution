<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('exec_module')) {	
	$e->setError(3);
	$e->dumpError();	
}

if(isset($_GET['id'])) {
	$id = intval($_GET['id']);
} else {
	$id=0;
}

// make sure the id's a number
if(!is_numeric($id)) {
	echo "Passed ID is NaN!";
	exit;
}

// check if user has access permission, except admins
if($_SESSION['mgrRole']!=1){
	$sql = "SELECT sma.usergroup,mg.member " .
		"FROM ".$modx->getFullTableName("site_module_access")." sma " .
		"LEFT JOIN ".$modx->getFullTableName("member_groups")." mg ON mg.user_group = sma.usergroup AND member='".$modx->getLoginUserID()."'".
		"WHERE sma.module = '$id'";
	$rs = $modx->db->query($sql);

	//initialize permission to -1, if it stays -1 no permissions
	//attached so permission granted
	$permissionAccessInt = -1;

	while ($row = $modx->db->getRow($rs)) {
		if($row["usergroup"] && $row["member"]) {
			//if there are permissions and this member has permission, ofcourse
			//this is granted
			$permissionAccessInt = 1;
		} elseif ($permissionAccessInt==-1) {
			//if there are permissions but this member has no permission and the
			//variable was still in init state we set permission to 0; no permissions
			$permissionAccessInt = 0;
		}
	}

	if($permissionAccessInt==0) {
		echo "<script type='text/javascript'>" .
		"function jsalert(){ alert('You do not sufficient privileges to execute this module.');" .
		"window.location.href='index.php?a=106';}".
		"setTimeout('jsalert()',100)".
		"</script>";
		exit;
	}
}

// get module data
$sql = "SELECT * " .
		"FROM ".$modx->getFullTableName("site_modules")." " .
		"WHERE id = $id;";
$rs = $modx->db->query($sql);
$limit = $modx->db->getRecordCount($rs);
if($limit>1) {
	echo "<script type='text/javascript'>" .
			"function jsalert(){ alert('Multiple modules sharing same unique id $id. Please contact the Site Administrator');" .
			"window.location.href='index.php?a=106';}".
			"setTimeout('jsalert()',100)".
			"</script>";
	exit;
}
if($limit<1) {
	echo "<script type='text/javascript'>" .
			"function jsalert(){ alert('No record found for id $id');" .
			"window.location.href='index.php?a=106';}" .
			"setTimeout('jsalert()',100)".
			"</script>";
	exit;
}
$content = $modx->db->getRow($rs);
if($content['disabled']) {
	echo "<script type='text/javascript'>" .
			"function jsalert(){ alert('This module is disabled and cannot be executed.');" .
			"window.location.href='index.php?a=106';}" .
			"setTimeout('jsalert()',100)".
			"</script>";
	exit;
}

// load module configuration
$parameter = array();
if(!empty($content["properties"])){
	$tmpParams = explode("&",$content["properties"]);
	for($x=0; $x<count($tmpParams); $x++) {
		$pTmp = explode("=", $tmpParams[$x]);
		$pvTmp = explode(";", trim($pTmp[1]));
		if ($pvTmp[1]=='list' && $pvTmp[3]!="") $parameter[$pTmp[0]] = $pvTmp[3]; //list default
		else if($pvTmp[1]!='list' && $pvTmp[2]!="") $parameter[$pTmp[0]] = $pvTmp[2];
	}
}

// Set the item name for logger
$_SESSION['itemname'] = $content['name'];

$output = evalModule($content["modulecode"],$parameter);
echo $output;
include MODX_MANAGER_PATH."includes/sysalert.display.inc.php";

// evalModule
function evalModule($moduleCode,$params){
	global $modx;
	$etomite = &$modx;
	$modx->event->params = &$params; // store params inside event object
	if(is_array($params)) {
		extract($params, EXTR_SKIP);
	}
	ob_start();
	$mod = eval($moduleCode);
	$msg = ob_get_contents();
	ob_end_clean();
	if (isset($php_errormsg))
	{
		$error_info = error_get_last();
        switch($error_info['type'])
        {
        	case E_NOTICE :
        		$error_level = 1;
        	case E_USER_NOTICE :
        		break;
        	case E_DEPRECATED :
        	case E_USER_DEPRECATED :
        	case E_STRICT :
        		$error_level = 2;
        		break;
        	default:
        		$error_level = 99;
        }
		if($modx->config['error_reporting']==='99' || 2<$error_level)
		{
			extract($error_info);
			$result = $modx->messageQuit('PHP Parse Error', '', true, $type, $file, $content['name'] . ' - Module', $text, $line, $msg);
			$modx->event->alert("An error occurred while loading. Please see the event log for more information<p>{$msg}</p>");
		}
	}
	unset($modx->event->params);
	return $mod.$msg;
}
