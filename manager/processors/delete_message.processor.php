<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('messages')) {
	$e->setError(3);
	$e->dumpError();	
}

$id=$_REQUEST['id'];

// check the user is allowed to delete this message
$sql = "SELECT * FROM $dbase.`".$table_prefix."user_messages` WHERE $dbase.`".$table_prefix."user_messages`.id=$id";
$rs = $modx->db->query($sql);
$limit = $modx->db->getRecordCount($rs);
if($limit!=1) {
	echo "Wrong number of messages returned!";
	exit;
} else {
	$message=$modx->db->getRow($rs);
	if($message['recipient']!=$modx->getLoginUserID()) {
		echo "You are not allowed to delete this message!";
		exit;
	} else {
		// delete message
		$sql = "DELETE FROM $dbase.`".$table_prefix."user_messages` WHERE id=$id;";
		$rs = $modx->db->query($sql);
		if(!$rs) {
			echo "Something went wrong while trying to delete the message!";
			exit;
		} 
	}
}

$header = "Location: index.php?a=10";
header($header);

?>