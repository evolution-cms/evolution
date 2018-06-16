<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.');
}

unset($_SESSION['itemname']); // clear this, because it's only set for logging purposes

if($modx->hasPermission('settings') && (!isset($settings_version) || $settings_version != $modx->getVersionData('version'))) {
	// seems to be a new install - send the user to the configuration page
	exit('<script type="text/javascript">document.location.href="index.php?a=17";</script>');
}

// set placeholders
$ph = $_lang;
$_SESSION['nrtotalmessages'] = 0;
$_SESSION['nrnewmessages'] = 0;

// setup message info
if($modx->hasPermission('messages')) {
	include_once(MODX_MANAGER_PATH . 'includes/messageCount.inc.php');
	$_SESSION['nrtotalmessages'] = $nrtotalmessages;
	$_SESSION['nrnewmessages'] = $nrnewmessages;

	$msg = array();
	$msg[] = sprintf('<a href="index.php?a=10" target="main"><img src="%s" /></a>', $_style['icons_mail_large']);
	$nrnewmessages = $_SESSION['nrnewmessages'] > 0 ? ' (<span style="color:red">' . $_SESSION['nrnewmessages'] . '</span>)' : '';
	$msg[] = sprintf('<span style="color:#909090;font-size:15px;font-weight:bold">&nbsp;<a class="wm_messages_inbox_link" href="index.php?a=10" target="main">[%%inbox%%]</a>%s</span><br />', $nrnewmessages);
	$nrnewmessages = $_SESSION['nrnewmessages'] > 0 ? '<span style="color:red;">' . $_SESSION['nrnewmessages'] . '</span>' : '0';
	$welcome_messages = sprintf($_lang['welcome_messages'], $_SESSION['nrtotalmessages'], $nrnewmessages);
	$msg[] = sprintf('<span class="comment">%s</span>', $welcome_messages);
	$ph['MessageInfo'] = implode("\n", $msg);
}

$iconTpl = $modx->getChunk('manager#welcome\WrapIcon');
// setup icons
if($modx->hasPermission('new_user') || $modx->hasPermission('edit_user')) {
	$icon = '<i class="[&icons_security_large&]" alt="[%user_management_title%]"> </i>[%user_management_title%]';
	$ph['SecurityIcon'] = sprintf($iconTpl,$icon, 75);
}
if($modx->hasPermission('new_web_user') || $modx->hasPermission('edit_web_user')) {
	$icon = '<i class="[&icons_webusers_large&]" alt="[%web_user_management_title%]"> </i>[%web_user_management_title%]';
	$ph['WebUserIcon'] = sprintf($iconTpl,$icon, 99);
}
if($modx->hasPermission('new_module') || $modx->hasPermission('edit_module')) {
	$icon = '<i class="[&icons_modules_large&]" alt="[%manage_modules%]"> </i>[%modules%]';
	$ph['ModulesIcon'] = sprintf($iconTpl,$icon, 106);
}
if($modx->hasPermission('new_template') || $modx->hasPermission('edit_template') || $modx->hasPermission('new_snippet') || $modx->hasPermission('edit_snippet') || $modx->hasPermission('new_plugin') || $modx->hasPermission('edit_plugin') || $modx->hasPermission('manage_metatags')) {
	$icon = '<i class="[&icons_resources_large&]" alt="[%element_management%]"> </i>[%elements%]';
	$ph['ResourcesIcon'] = sprintf($iconTpl,$icon, 76);
}
if($modx->hasPermission('bk_manager')) {
	$icon = '<i class="[&icons_backup_large&]" alt="[%bk_manager%]"> </i>[%backup%]';
	$ph['BackupIcon'] = sprintf($iconTpl,$icon, 93);
}
if($modx->hasPermission('help')) {
	$icon = '<i class="[&icons_help_large&]" alt="[%help%]" /> </i>[%help%]';
	$ph['HelpIcon'] = sprintf($iconTpl,$icon, 9);
}

if($modx->hasPermission('new_document')) {
	$icon = '<i class="[&icons_resource_large&]"></i>[%add_resource%]';
	$ph['ResourceIcon'] = sprintf($iconTpl,$icon, 4);
	$icon = '<i class="[&icons_weblink_large&]"></i>[%add_weblink%]';
	$ph['WeblinkIcon'] = sprintf($iconTpl,$icon, 72);
}
if($modx->hasPermission('assets_images')) {
	$icon = '<i class="[&icons_images_large&]"></i>[%images_management%]';
	$ph['ImagesIcon'] = sprintf($iconTpl,$icon, 72);
}
if($modx->hasPermission('assets_files')) {
	$icon = '<i class="[&icons_files_large&]"></i>[%files_management%]';
	$ph['FilesIcon'] = sprintf($iconTpl,$icon, 72);
}
if($modx->hasPermission('change_password')) {
	$icon = '<i class="[&icons_password_large&]"></i>[%change_password%]';
	$ph['PasswordIcon'] = sprintf($iconTpl,$icon, 28);
}
$icon = '<i class="[&icons_logout_large&]"></i>[%logout%]';
$ph['LogoutIcon'] = sprintf($iconTpl,$icon, 8);

// do some config checks
if(($modx->config['warning_visibility'] == 0 && $_SESSION['mgrRole'] == 1) || $modx->config['warning_visibility'] == 1) {
	include_once(MODX_MANAGER_PATH . 'includes/config_check.inc.php');
	if($config_check_results != $_lang['configcheck_ok']) {
		$ph['config_check_results'] = $config_check_results;
		$ph['config_display'] = 'block';
	} else {
		$ph['config_display'] = 'none';
	}
} else {
	$ph['config_display'] = 'none';
}

// Check logout-reminder
if(isset($_SESSION['show_logout_reminder'])) {
	switch($_SESSION['show_logout_reminder']['type']) {
		case 'logout_reminder':
			$date = $modx->toDateFormat($_SESSION['show_logout_reminder']['lastHit'], 'dateOnly');
			$ph['logout_reminder_msg'] = str_replace('[+date+]', $date, $_lang['logout_reminder_msg']);
			break;
	}
	$ph['show_logout_reminder'] = 'block';
	unset($_SESSION['show_logout_reminder']);
} else {
	$ph['show_logout_reminder'] = 'none';
}

// Check multiple sessions
//$where = sprintf("internalKey='%s'", $modx->getDatabase()->escape($_SESSION['mgrInternalKey']));
//$rs = $modx->getDatabase()->select('count(*) AS count', $modx->getDatabase()->getFullTableName('active_user_sessions'), $where);
//$count = $modx->getDatabase()->getValue($rs);
/*
if($count > 1) {
	$ph['multiple_sessions_msg'] = $modx->parseText($_lang['multiple_sessions_msg'], array(
		'username' => $_SESSION['mgrShortname'],
		'total' => $count
	));
	$ph['show_multiple_sessions'] = 'block';
} else {
	$ph['show_multiple_sessions'] = 'none';
}*/
$ph['show_multiple_sessions'] = 'none';

$ph['RecentInfo'] = $modx->getChunk('manager#welcome\RecentInfo');

$tpl = '
<table class="table data">
	<tr>
		<td width="150">[%yourinfo_username%]</td>
		<td><b>[+username+]</b></td>
	</tr>
	<tr>
		<td>[%yourinfo_role%]</td>
		<td><b>[+role+]</b></td>
	</tr>
	<tr>
		<td>[%yourinfo_previous_login%]</td>
		<td><b>[+lastlogin+]</b></td>
	</tr>
	<tr>
		<td>[%yourinfo_total_logins%]</td>
		<td><b>[+logincount+]</b></td>
	</tr>
	<tr>
		<td>[%inbox%]</td>
		<td><a href="index.php?a=10" target="main"><b>[+msginfo+]</b></a></td>
	</tr>
</table>';

$nrnewmessages = '<span class="text-danger">' . $_SESSION['nrnewmessages'] . '</span>';

$ph['UserInfo'] = $modx->parseText($tpl, array(
	'username' => $modx->getLoginUserName(),
	'role' => $_SESSION['mgrPermissions']['name'],
	'lastlogin' => $modx->toDateFormat($_SESSION['mgrLastlogin'] + $server_offset_time),
	'logincount' => $_SESSION['mgrLogincount'] + 1,
	'msginfo' => sprintf($_lang['welcome_messages'], $_SESSION['nrtotalmessages'], $nrnewmessages)
));

$from = array();
$from[] = $modx->getDatabase()->getFullTableName('active_user_sessions');
$from[] = " us LEFT JOIN " . $modx->getDatabase()->getFullTableName('active_users') . " au ON au.sid=us.sid WHERE au.action <> '8'";
$rs = $modx->getDatabase()->select('*', $from, '', 'username ASC, au.sid ASC');

if($modx->getDatabase()->getRecordCount($rs) < 1) {
	$html = '<p>[%no_active_users_found%]</p>';
} else {
	include_once(MODX_MANAGER_PATH . 'includes/actionlist.inc.php');
	$now = $_SERVER['REQUEST_TIME'] + $server_offset_time;
	$ph['now'] = strftime('%H:%M:%S', $now);
	$timetocheck = ($now - (60 * 20)); //+$server_offset_time;
	$html = '
	<div class="card-body">
		[%onlineusers_message%] 
		<b>[+now+]</b>):
	</div>
	<div class="table-responsive">
	<table class="table data">
	<thead>
		<tr>
			<th>[%onlineusers_user%]</th>
			<th>ID</th>
			<th>[%onlineusers_ipaddress%]</th>
			<th>[%onlineusers_lasthit%]</th>
			<th>[%onlineusers_action%]&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
		</tr>
	</thead>
	<tbody>';

	$userList = array();
	$userCount = array();
	// Create userlist with session-count first before output
	while($activeusers = $modx->getDatabase()->getRow($rs)) {
		$userCount[$activeusers['internalKey']] = isset($userCount[$activeusers['internalKey']]) ? $userCount[$activeusers['internalKey']] + 1 : 1;

		$idle = $activeusers['lasthit'] < $timetocheck ? ' class="userIdle"' : '';
		$webicon = $activeusers['internalKey'] < 0 ? '<img src="[&tree_globe&]" alt="Web user" />&nbsp;' : '';
		$ip = $activeusers['ip'] === '::1' ? '127.0.0.1' : $activeusers['ip'];
		$currentaction = getAction($activeusers['action'], $activeusers['id']);
		$userList[] = array(
			$idle,
			'',
			$activeusers['username'],
			$webicon,
			abs($activeusers['internalKey']),
			$ip,
			strftime('%H:%M:%S', $activeusers['lasthit'] + $server_offset_time),
			$currentaction
		);
	}
	foreach($userList as $params) {
		$params[1] = $userCount[$params[4]] > 1 ? ' class="userMultipleSessions"' : '';
		$html .= "\n\t\t" . vsprintf('<tr%s><td><strong%s>%s</strong></td><td>%s%s</td><td>%s</td><td>%s</td><td>%s</td></tr>', $params);
	}

	$html .= '
	</tbody>
	</table>
</div>
';
}
$ph['OnlineInfo'] = $html;

// include rss feeds for important forum topics
include_once(MODX_MANAGER_PATH . 'includes/rss.inc.php');
$ph['modx_security_notices_content'] = $feedData['modx_security_notices_content'];
$ph['modx_news_content'] = $feedData['modx_news_content'];

$ph['theme'] = $modx->config['manager_theme'];
$ph['site_name'] = $modx->getPhpCompat()->entities($modx->config['site_name']);
$ph['home'] = $_lang['home'];
$ph['logo_slogan'] = $_lang['logo_slogan'];
$ph['welcome_title'] = $_lang['welcome_title'];
$ph['search'] = $_lang['search'];
$ph['settings_config'] = $_lang['settings_config'];
$ph['configcheck_title'] = $_lang['configcheck_title'];
$ph['online'] = $_lang['online'];
$ph['onlineusers_title'] = $_lang['onlineusers_title'];
$ph['recent_docs'] = $_lang['recent_docs'];
$ph['activity_title'] = $_lang['activity_title'];
$ph['info'] = $_lang['info'];
$ph['yourinfo_title'] = $_lang['yourinfo_title'];

$ph['modx_security_notices'] = $_lang['security_notices_tab'];
$ph['modx_security_notices_title'] = $_lang['security_notices_title'];
$ph['modx_news'] = $_lang['modx_news_tab'];
$ph['modx_news_title'] = $_lang['modx_news_title'];

$modx->toPlaceholders($ph);

$script = $modx->getChunk('manager#welcome\StartUpScript');
$modx->regClientScript($script);

// invoke event OnManagerWelcomePrerender
$evtOut = $modx->invokeEvent('OnManagerWelcomePrerender');
if(is_array($evtOut)) {
	$output = implode('', $evtOut);
	$ph['OnManagerWelcomePrerender'] = $output;
}

$widgets['welcome'] = array(
	'menuindex' => '10',
	'id' => 'welcome',
	'cols' => 'col-lg-6',
	'icon' => 'fa-home',
	'title' => '[%welcome_title%]',
	'body' => '
				<div class="wm_buttons card-body"> 
					<!--@IF:[[#hasPermission?key=new_document]]--> 
					<span class="wm_button">
						<a target="main" href="index.php?a=4">
							<i class="[&icons_resource_large&]"></i>
							<span>[%add_resource%]</span>
						</a>
					</span> 
					<span class="wm_button">
						<a target="main" href="index.php?a=72">
							<i class="[&icons_weblink_large&]"></i>
							<span>[%add_weblink%]</span>
						</a>
					</span> 
					<!--@ENDIF--> 
					<!--@IF:[[#hasPermission?key=assets_images]]--> 
					<span class="wm_button">
						<a target="main" href="media/browser/mcpuk/browse.php?filemanager=media/browser/mcpuk/browse.php&type=images">
							<i class="[&icons_images_large&]"></i>
							<span>[%images_management%]</span>
						</a>
					</span> 
					<!--@ENDIF-->
					<!--@IF:[[#hasPermission?key=assets_files]]--> 
					<span class="wm_button">
						<a target="main" href="media/browser/mcpuk/browse.php?filemanager=media/browser/mcpuk/browse.php&type=files">
							<i class="[&icons_files_large&]"></i>
							<span>[%files_management%]</span>
						</a>
					</span> 
					<!--@ENDIF-->
					<!--@IF:[[#hasPermission?key=change_password]]--> 
					<span class="wm_button">
						<a target="main" href="index.php?a=28">
							<i class="[&icons_password_large&]"></i>
							<span>[%change_password%]</span>
						</a>
					</span> 
					<!--@ENDIF--> 
					<span class="wm_button">
						<a target="_top" href="index.php?a=8">
							<i class="[&icons_logout_large&]"></i>
							<span>[%logout%]</span>
						</a>
					</span> 
				</div>
				<div class="userprofiletable card-body">
					<table>
						<tr>
							<td width="150">[%yourinfo_username%]</td>
							<td><b>[[#getLoginUserName]]</b></td>
						</tr>
						<tr>
							<td>[%yourinfo_role%]</td>
							<td><b>[[$_SESSION[\'mgrPermissions\'][\'name\'] ]]</b></td>
						</tr>
						<tr>
							<td>[%yourinfo_previous_login%]</td>
							<td><b>[[$_SESSION[\'mgrLastlogin\']:math(\'%s+[(server_offset_time)]\'):dateFormat]]</b></td>
						</tr>
						<tr>
							<td>[%yourinfo_total_logins%]</td>
							<td><b>[[$_SESSION[\'mgrLogincount\']:math(\'%s+1\')]]</b></td>
						</tr>
						<!--@IF:[[#hasPermission?key=messages]]-->
						<tr>
							<td>[%inbox%]</td>
							<td><a href="index.php?a=10" target="main"><b>[[#getMessageCount]]</b></a></td>
						</tr>
						<!--@ENDIF-->
					</table>
				</div>
		',
	'hide'=>'0'
);
$widgets['onlineinfo'] = array(
	'menuindex' => '20',
	'id' => 'onlineinfo',
	'cols' => 'col-lg-6',
	'icon' => 'fa-user',
	'title' => '[%onlineusers_title%]',
	'body' => '<div class="userstable">[+OnlineInfo+]</div>',
	'hide'=>'0'
);
$widgets['recentinfo'] = array(
	'menuindex' => '30',
	'id' => 'modxrecent_widget',
	'cols' => 'col-sm-12',
	'icon' => 'fa-pencil-square-o',
	'title' => '[%activity_title%]',
	'body' => '<div class="widget-stage">[+RecentInfo+]</div>',
	'hide'=>'0'
);
if ($modx->config['rss_url_news']) {
    $widgets['news'] = array(
        'menuindex' => '40',
        'id' => 'news',
        'cols' => 'col-sm-6',
        'icon' => 'fa-rss',
        'title' => '[%modx_news_title%]',
        'body' => '<div style="max-height:200px;overflow-y: scroll;padding: 1rem .5rem">[+modx_news_content+]</div>',
        'hide'=>'0'
    );
}
if ($modx->config['rss_url_security']) {
    $widgets['security'] = array(
        'menuindex' => '50',
        'id' => 'security',
        'cols' => 'col-sm-6',
        'icon' => 'fa-exclamation-triangle',
        'title' => '[%security_notices_title%]',
        'body' => '<div style="max-height:200px;overflow-y: scroll;padding: 1rem .5rem">[+modx_security_notices_content+]</div>',
        'hide'=>'0'
    );
}

// invoke OnManagerWelcomeHome event
$sitewidgets = $modx->invokeEvent("OnManagerWelcomeHome", array('widgets' => $widgets));
if(is_array($sitewidgets)) {
	$newwidgets = array();
    foreach($sitewidgets as $widget){
        $newwidgets = array_merge($newwidgets, unserialize($widget));
    }
    $widgets = (count($newwidgets) > 0) ? $newwidgets : $widgets;
}

usort($widgets, function ($a, $b) {
	return $a['menuindex'] - $b['menuindex'];
});

$tpl = $modx->getChunk('manager#welcome\Widget');
$output = '';
foreach($widgets as $widget) {
	if ($widget['hide'] != '1'){
		$output .= $modx->parseText($tpl, $widget);
	}
}
$ph['widgets'] = $output;

// load template
if(!isset($modx->config['manager_welcome_tpl']) || empty($modx->config['manager_welcome_tpl'])) {
	$modx->config['manager_welcome_tpl'] = MODX_MANAGER_PATH . 'media/style/common/welcome.tpl';
}

$target = $modx->config['manager_welcome_tpl'];
$target = str_replace('[+base_path+]', MODX_BASE_PATH, $target);
$target = $modx->mergeSettingsContent($target);

if(substr($target, 0, 1) === '@') {
	if(substr($target, 0, 6) === '@CHUNK') {
		$content = $modx->getChunk(trim(substr($target, 7)));
	} elseif(substr($target, 0, 5) === '@FILE') {
		$content = file_get_contents(trim(substr($target, 6)));
	} else {
		$content = '';
	}
} else {
	$chunk = $modx->getChunk($target);
	if($chunk !== false && !empty($chunk)) {
		$content = $chunk;
	} elseif(is_file(MODX_BASE_PATH . $target)) {
		$content = file_get_contents(MODX_BASE_PATH . $target);
	} elseif(is_file(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/welcome.tpl')) {
		$content = file_get_contents(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/welcome.tpl');
	} elseif(is_file(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/html/welcome.html')) // ClipperCMS compatible
	{
		$content = file_get_contents(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/html/welcome.html');
	} else {
		$content = file_get_contents(MODX_MANAGER_PATH . 'media/style/common/welcome.tpl');
	}
}

// merge placeholders
$content = $modx->mergeConditionalTagsContent($content);
$content = $modx->mergeSettingsContent($content);
$content = $modx->parseText($content, $ph);
if(strpos($content, '[+') !== false) {
	$modx->toPlaceholders($ph);
	$content = $modx->mergePlaceholderContent($content);
}
$content = $modx->parseDocumentSource($content);
$content = $modx->parseText($content, $_lang, '[%', '%]');
$content = $modx->parseText($content, $_style, '[&', '&]');
$content = $modx->cleanUpMODXTags($content); //cleanup

if($js = $modx->getRegisteredClientScripts()) {
	$content .= $js;
}

echo $content;

//<span class="conf">
//	<a href="javascript:;" class="setting"><i class="fa fa-cog"></i></a>
//  <a href="javascript:;" class="closed"><i class="fa fa-close"></i></a>
//</span>
