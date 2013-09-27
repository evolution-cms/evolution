<?php if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

unset($_SESSION['itemname']); // clear this, because it's only set for logging purposes

if($modx->hasPermission('settings') && (!isset($settings_version) || $settings_version!=$modx->getVersionData('version'))) {
    // seems to be a new install - send the user to the configuration page
    echo '<script type="text/javascript">document.location.href="index.php?a=17";</script>';
    exit;
}

$script = <<<JS
        <script type="text/javascript">
        function hideConfigCheckWarning(key){
            var myAjax = new Ajax('index.php?a=118', {
                method: 'post',
                data: 'action=setsetting&key=_hide_configcheck_' + key + '&value=1'
            });
            myAjax.addEvent('onComplete', function(resp){
                fieldset = $(key + '_warning_wrapper').getParent().getParent();
                var sl = new Fx.Slide(fieldset);
                sl.slideOut();
            });
            myAjax.request();
        }
        </script>

JS;
$modx->regClientScript($script);

// set placeholders
$modx->setPlaceholder('theme',$manager_theme ? $manager_theme : '');
$modx->setPlaceholder('home', $_lang["home"]);
$modx->setPlaceholder('logo_slogan',$_lang["logo_slogan"]);
$modx->setPlaceholder('site_name',$site_name);
$modx->setPlaceholder('welcome_title',$_lang['welcome_title']);

// setup message info
if($modx->hasPermission('messages')) {
		include_once MODX_MANAGER_PATH.'includes/messageCount.inc.php';
		$_SESSION['nrtotalmessages'] = $nrtotalmessages;
		$_SESSION['nrnewmessages'] = $nrnewmessages;

    $msg = '<a href="index.php?a=10"><img src="'.$_style['icons_mail_large'].'" /></a>
    <span style="color:#909090;font-size:15px;font-weight:bold">&nbsp;'.$_lang["inbox"].($_SESSION['nrnewmessages']>0 ? " (<span style='color:red'>".$_SESSION['nrnewmessages']."</span>)":"").'</span><br />
    <span class="comment">'.sprintf($_lang["welcome_messages"], $_SESSION['nrtotalmessages'], "<span style='color:red;'>".$_SESSION['nrnewmessages']."</span>").'</span>';
    $modx->setPlaceholder('MessageInfo',$msg);
}

// setup icons
if($modx->hasPermission('new_user')||$modx->hasPermission('edit_user')) { 
    $icon = '<a class="hometblink" href="index.php?a=75"><img src="'.$_style['icons_security_large'].'" alt="'.$_lang['user_management_title'].'" /><br />'.$_lang['security'].'</a>';     
    $modx->setPlaceholder('SecurityIcon',$icon);
}
if($modx->hasPermission('new_web_user')||$modx->hasPermission('edit_web_user')) { 
    $icon = '<a class="hometblink" href="index.php?a=99"><img src="'.$_style['icons_webusers_large'].'" alt="'.$_lang['web_user_management_title'].'" /><br />'.$_lang['web_users'].'</a>';
    $modx->setPlaceholder('WebUserIcon',$icon);
}
if($modx->hasPermission('new_module') || $modx->hasPermission('edit_module')) {
    $icon = '<a class="hometblink" href="index.php?a=106"><img src="'.$_style['icons_modules_large'].'" alt="'.$_lang['manage_modules'].'" /><br />'.$_lang['modules'].'</a>';
    $modx->setPlaceholder('ModulesIcon',$icon);
}
if($modx->hasPermission('new_template') || $modx->hasPermission('edit_template') || $modx->hasPermission('new_snippet') || $modx->hasPermission('edit_snippet') || $modx->hasPermission('new_plugin') || $modx->hasPermission('edit_plugin') || $modx->hasPermission('manage_metatags')) {
    $icon = '<a class="hometblink" href="index.php?a=76"><img src="'.$_style['icons_resources_large'].'" alt="'.$_lang['element_management'].'" /><br />'.$_lang['elements'].'</a>';
    $modx->setPlaceholder('ResourcesIcon',$icon);
}
if($modx->hasPermission('bk_manager')) {
    $icon = '<a class="hometblink" href="index.php?a=93"><img src="'.$_style['icons_backup_large'].'" alt="'.$_lang['bk_manager'].'" /><br />'.$_lang['backup'].'</a>';
    $modx->setPlaceholder('BackupIcon',$icon);
}

// do some config checks
if (($modx->config['warning_visibility'] == 0 && $_SESSION['mgrRole'] == 1) || $modx->config['warning_visibility'] == 1) {
    include_once "config_check.inc.php";
    $modx->setPlaceholder('settings_config',$_lang['settings_config']);
    $modx->setPlaceholder('configcheck_title',$_lang['configcheck_title']);
    if($config_check_results != $_lang['configcheck_ok']) {    
        $modx->setPlaceholder('config_check_results',$config_check_results);
        $modx->setPlaceholder('config_display','block');
    }
    else {
        $modx->setPlaceholder('config_display','none');
    }
} else {
     $modx->setPlaceholder('config_display','none');
}

// include rss feeds for important forum topics
include_once "rss.inc.php"; 

// modx news
$modx->setPlaceholder('modx_news',$_lang["modx_news_tab"]);
$modx->setPlaceholder('modx_news_title',$_lang["modx_news_title"]);
$modx->setPlaceholder('modx_news_content',$feedData['modx_news_content']);

// security notices
$modx->setPlaceholder('modx_security_notices',$_lang["security_notices_tab"]);
$modx->setPlaceholder('modx_security_notices_title',$_lang["security_notices_title"]);
$modx->setPlaceholder('modx_security_notices_content',$feedData['modx_security_notices_content']);

// recent document info
$html = $_lang["activity_message"].'<br /><br /><ul>';
$sql = "SELECT id, pagetitle, description FROM $dbase.`".$table_prefix."site_content` WHERE $dbase.`".$table_prefix."site_content`.deleted=0 AND ($dbase.`".$table_prefix."site_content`.editedby=".$modx->getLoginUserID()." OR $dbase.`".$table_prefix."site_content`.createdby=".$modx->getLoginUserID().") ORDER BY editedon DESC LIMIT 10";
$rs = $modx->db->query($sql);
$limit = $modx->db->getRecordCount($rs);
if($limit<1) {
    $html .= '<li>'.$_lang['no_activity_message'].'</li>';
} else {
    for ($i = 0; $i < $limit; $i++) {
        $content = $modx->db->getRow($rs);
        if($i==0) {
            $syncid = $content['id'];
        }
        $html.='<li><span style="width: 40px; text-align:right;">'.$content['id'].'</span> - <span style="width: 200px;"><a href="index.php?a=3&amp;id='.$content['id'].'">'.$content['pagetitle'].'</a></span>'.($content['description']!='' ? ' - '.$content['description'] : '').'</li>';
    }
}
$html.='</ul>';
$modx->setPlaceholder('recent_docs',$_lang['recent_docs']);
$modx->setPlaceholder('activity_title',$_lang['activity_title']);
$modx->setPlaceholder('RecentInfo',$html);

// user info
$modx->setPlaceholder('info',$_lang['info']);
$modx->setPlaceholder('yourinfo_title',$_lang['yourinfo_title']);
$html = '
    <p>'.$_lang["yourinfo_message"].'</p>
    <table border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td width="150">'.$_lang["yourinfo_username"].'</td>
        <td width="20">&nbsp;</td>
        <td><b>'.$modx->getLoginUserName().'</b></td>
      </tr>
      <tr>
        <td>'.$_lang["yourinfo_role"].'</td>
        <td>&nbsp;</td>
        <td><b>'.$_SESSION['mgrPermissions']['name'].'</b></td>
      </tr>
      <tr>
        <td>'.$_lang["yourinfo_previous_login"].'</td>
        <td>&nbsp;</td>
        <td><b>'.$modx->toDateFormat($_SESSION['mgrLastlogin']+$server_offset_time).'</b></td>
      </tr>
      <tr>
        <td>'.$_lang["yourinfo_total_logins"].'</td>
        <td>&nbsp;</td>
        <td><b>'.($_SESSION['mgrLogincount']+1).'</b></td>
      </tr>
    </table>
';
$modx->setPlaceholder('UserInfo',$html);

// online users
$modx->setPlaceholder('online',$_lang['online']);
$modx->setPlaceholder('onlineusers_title',$_lang['onlineusers_title']);
    $timetocheck = (time()-(60*20));//+$server_offset_time;

    include_once "actionlist.inc.php";

    $sql = "SELECT * FROM $dbase.`".$table_prefix."active_users` WHERE $dbase.`".$table_prefix."active_users`.lasthit>'$timetocheck' ORDER BY username ASC";
    $rs = $modx->db->query($sql);
    $limit = $modx->db->getRecordCount($rs);
    if($limit<1) {
        $html = "<p>".$_lang['no_active_users_found']."</p>";
    } else {
        $html = $_lang["onlineusers_message"].'<b>'.strftime('%H:%M:%S', time()+$server_offset_time).'</b>):<br /><br />
                <table border="0" cellpadding="1" cellspacing="1" width="100%" bgcolor="#ccc">
                  <thead>
                    <tr>
                      <td><b>'.$_lang["onlineusers_user"].'</b></td>
                      <td><b>'.$_lang["onlineusers_userid"].'</b></td>
                      <td><b>'.$_lang["onlineusers_ipaddress"].'</b></td>
                      <td><b>'.$_lang["onlineusers_lasthit"].'</b></td>
                      <td><b>'.$_lang["onlineusers_action"].'</b></td>
                    </tr>
                  </thead>
                  <tbody>
        ';
        for ($i = 0; $i < $limit; $i++) {
            $activeusers = $modx->db->getRow($rs);
            $currentaction = getAction($activeusers['action'], $activeusers['id']);
            $webicon = ($activeusers['internalKey']<0)? "<img src='media/style/{$manager_theme}/images/tree/globe.gif' alt='Web user' />":"";
            $html.= "<tr bgcolor='#FFFFFF'><td><b>".$activeusers['username']."</b></td><td>$webicon&nbsp;".abs($activeusers['internalKey'])."</td><td>".$activeusers['ip']."</td><td>".strftime('%H:%M:%S', $activeusers['lasthit']+$server_offset_time)."</td><td>$currentaction</td></tr>";
        }
        $html.= '
                </tbody>
                </table>
        ';
    }
$modx->setPlaceholder('OnlineInfo',$html);

// invoke event OnManagerWelcomePrerender
$evtOut = $modx->invokeEvent('OnManagerWelcomePrerender');
if(is_array($evtOut)) {
    $output = implode("",$evtOut);
    $modx->setPlaceholder('OnManagerWelcomePrerender', $output);
}

// invoke event OnManagerWelcomeHome
$evtOut = $modx->invokeEvent('OnManagerWelcomeHome');
if(is_array($evtOut)) {
    $output = implode("",$evtOut);
    $modx->setPlaceholder('OnManagerWelcomeHome', $output);
}

// invoke event OnManagerWelcomeRender
$evtOut = $modx->invokeEvent('OnManagerWelcomeRender');
if(is_array($evtOut)) {
    $output = implode("",$evtOut);
    $modx->setPlaceholder('OnManagerWelcomeRender', $output);
}

// load template
if(!isset($modx->config['manager_welcome_tpl']) || empty($modx->config['manager_welcome_tpl'])) {
	$modx->config['manager_welcome_tpl'] = MODX_MANAGER_PATH . 'media/style/common/welcome.tpl'; 
}

$target = $modx->config['manager_welcome_tpl'];
$target = str_replace('[+base_path+]', MODX_BASE_PATH, $target);
$target = $modx->mergeSettingsContent($target);

if(substr($target,0,1)==='@') {
	if(substr($target,0,6)==='@CHUNK') {
		$target = trim(substr($target,7));
		$welcome_tpl = $modx->getChunk($target);
	}
	elseif(substr($target,0,5)==='@FILE') {
		$target = trim(substr($target,6));
		$welcome_tpl = file_get_contents($target);
	}
} else {
	$chunk = $modx->getChunk($target);
	if($chunk!==false && !empty($chunk)) {
		$welcome_tpl = $chunk;
	}
	elseif(is_file(MODX_BASE_PATH . $target)) {
		$target = MODX_BASE_PATH . $target;
		$welcome_tpl = file_get_contents($target);
	}
	elseif(is_file(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/welcome.tpl')) {
		$target = MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/welcome.tpl';
		$welcome_tpl = file_get_contents($target);
	}
	elseif(is_file(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/html/welcome.html')) { // ClipperCMS compatible
		$target = MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/html/welcome.html';
		$welcome_tpl = file_get_contents($target);
	}
	else {
		$target = MODX_MANAGER_PATH . 'media/style/common/welcome.tpl';
		$welcome_tpl = file_get_contents($target);
	}
}

// merge placeholders
$welcome_tpl = $modx->mergePlaceholderContent($welcome_tpl);
$welcome_tpl = preg_replace('~\[\+(.*?)\+\]~', '', $welcome_tpl); //cleanup
if ($js= $modx->getRegisteredClientScripts()) {
	$welcome_tpl .= $js;
}

echo $welcome_tpl;
