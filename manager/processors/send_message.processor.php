<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('messages')) {
	$e->setError(3);
	$e->dumpError();
}

//$db->debug = true;


$sendto = $_REQUEST['sendto'];
$userid = $_REQUEST['user'];
$groupid = $_REQUEST['group'];
$subject = addslashes($_REQUEST['messagesubject']);
if($subject=="") $subject="(no subject)";
$message = addslashes($_REQUEST['messagebody']);
if($message=="") $message="(no message)";
$postdate = time();

if($sendto=='u') {
	if($userid==0) {
		$e->setError(13);
		$e->dumpError();
	}
	$sql = "INSERT INTO $dbase.`".$table_prefix."user_messages` (recipient, sender, subject, message, postdate, type, private)
			values($userid, ".$modx->getLoginUserID().", '$subject', '$message', $postdate, 'Message', 1);";
	$rs = $modx->db->query($sql);
}

if($sendto=='g') {
	if($groupid==0) {
		$e->setError(14);
		$e->dumpError();
	}
	$sql = "SELECT internalKey FROM $dbase.`".$table_prefix."user_attributes` WHERE $dbase.`".$table_prefix."user_attributes`.role=$groupid";
	$rs = $modx->db->query($sql);
	$limit = $modx->db->getRecordCount($rs);
	for( $i=0; $i<$limit; $i++ ){
		$row=$modx->db->getRow($rs);
		if($row['internalKey']!=$modx->getLoginUserID()) {
			$sql2 = "INSERT INTO $dbase.`".$table_prefix."user_messages` (recipient, sender, subject, message, postdate, type, private)
					values(".$row['internalKey'].", ".$modx->getLoginUserID().", '$subject', '$message', $postdate, 'Message', 0);";
			$rs2 = $modx->db->query($sql2);
		}
	}
}


if($sendto=='a') {
	$sql = "SELECT id FROM $dbase.`".$table_prefix."manager_users`";
	$rs = $modx->db->query($sql);
	$limit = $modx->db->getRecordCount($rs);
	for( $i=0; $i<$limit; $i++ ){
		$row=$modx->db->getRow($rs);
		if($row['id']!=$modx->getLoginUserID()) {
			$sql2 = "INSERT INTO $dbase.`".$table_prefix."user_messages` (recipient, sender, subject, message, postdate, type, private)
					values(".$row['id'].", ".$modx->getLoginUserID().", '$subject', '$message', $postdate, 'Message', 0);";
			$rs2 = $modx->db->query($sql2);
		}
	}
}

//exit;


$header = "Location: index.php?a=10";
header($header);
?>