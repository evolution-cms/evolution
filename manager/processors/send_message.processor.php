<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('messages')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$sendto = $_REQUEST['sendto'];
$userid = $_REQUEST['user'];
$groupid = $_REQUEST['group'];
$subject = $modx->getDatabase()->escape($_REQUEST['messagesubject']);
if($subject=="") $subject="(no subject)";
$message = $modx->getDatabase()->escape($_REQUEST['messagebody']);
if($message=="") $message="(no message)";
$postdate = time();

if($sendto=='u') {
	if($userid==0) {
		$modx->webAlertAndQuit($_lang["error_no_user_selected"]);
	}
	$modx->getDatabase()->insert(
		array(
			'recipient' => $userid,
			'sender'    => $modx->getLoginUserID(),
			'subject'   => $subject,
			'message'   => $message,
			'postdate'  => $postdate,
			'type'      => 'Message',
			'private'   => 1,
		), $modx->getDatabase()->getFullTableName('user_messages'));
}

if($sendto=='g') {
	if($groupid==0) {
		$modx->webAlertAndQuit($_lang["error_no_group_selected"]);
	}
	$rs = $modx->getDatabase()->select('internalKey', $modx->getDatabase()->getFullTableName('user_attributes'), "role='{$groupid}' AND internalKey!='".$modx->getLoginUserID()."'");
	while ($row=$modx->getDatabase()->getRow($rs)) {
		$modx->getDatabase()->insert(
			array(
				'recipient' => $row['internalKey'],
				'sender'    => $modx->getLoginUserID(),
				'subject'   => $subject,
				'message'   => $message,
				'postdate'  => $postdate,
				'type'      => 'Message',
				'private'   => 0,
			), $modx->getDatabase()->getFullTableName('user_messages'));
	}
}


if($sendto=='a') {
	$rs = $modx->getDatabase()->select('id', $modx->getDatabase()->getFullTableName('manager_users'), "id!='".$modx->getLoginUserID()."'");
	while ($row=$modx->getDatabase()->getRow($rs)) {
		$modx->getDatabase()->insert(
			array(
				'recipient' => $row['id'],
				'sender'    => $modx->getLoginUserID(),
				'subject'   => $subject,
				'message'   => $message,
				'postdate'  => $postdate,
				'type'      => 'Message',
				'private'   => 0,
			), $modx->getDatabase()->getFullTableName('user_messages'));
	}
}

$header = "Location: index.php?a=10";
header($header);
