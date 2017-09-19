<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('messages')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$sendto = $_REQUEST['sendto'];
$userid = $_REQUEST['user'];
$groupid = $_REQUEST['group'];
$subject = $modx->db->escape($_REQUEST['messagesubject']);
if($subject=="") $subject="(no subject)";
$message = $modx->db->escape($_REQUEST['messagebody']);
if($message=="") $message="(no message)";
$postdate = time();

if($sendto=='u') {
	if($userid==0) {
		$modx->webAlertAndQuit($_lang["error_no_user_selected"]);
	}
	$modx->db->insert(
		array(
			'recipient' => $userid,
			'sender'    => $modx->getLoginUserID(),
			'subject'   => $subject,
			'message'   => $message,
			'postdate'  => $postdate,
			'type'      => 'Message',
			'private'   => 1,
		), $modx->getFullTableName('user_messages'));
}

if($sendto=='g') {
	if($groupid==0) {
		$modx->webAlertAndQuit($_lang["error_no_group_selected"]);
	}
	$rs = $modx->db->select('internalKey', $modx->getFullTableName('user_attributes'), "role='{$groupid}' AND internalKey!='".$modx->getLoginUserID()."'");
	while ($row=$modx->db->getRow($rs)) {
		$modx->db->insert(
			array(
				'recipient' => $row['internalKey'],
				'sender'    => $modx->getLoginUserID(),
				'subject'   => $subject,
				'message'   => $message,
				'postdate'  => $postdate,
				'type'      => 'Message',
				'private'   => 0,
			), $modx->getFullTableName('user_messages'));
	}
}


if($sendto=='a') {
	$rs = $modx->db->select('id', $modx->getFullTableName('manager_users'), "id!='".$modx->getLoginUserID()."'");
	while ($row=$modx->db->getRow($rs)) {
		$modx->db->insert(
			array(
				'recipient' => $row['id'],
				'sender'    => $modx->getLoginUserID(),
				'subject'   => $subject,
				'message'   => $message,
				'postdate'  => $postdate,
				'type'      => 'Message',
				'private'   => 0,
			), $modx->getFullTableName('user_messages'));
	}
}

$header = "Location: index.php?a=10";
header($header);
