<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

if (isset($_SESSION['mgrValidated']) && $_SESSION['usertype']!='manager'){
//		if (isset($_COOKIE[session_name()])) {
//			setcookie(session_name(), '', 0, MODX_BASE_URL);
//		}
		@session_destroy();
		// start session
//	    startCMSSession();
}

// andrazk 20070416 - if installer is running, destroy active sessions
$pth = dirname(__FILE__);
if (file_exists($pth.'/../../assets/cache/installProc.inc.php')) {
	include_once($pth.'/../../assets/cache/installProc.inc.php');
	if (isset($installStartTime)) {
		if ((time() - $installStartTime) > 5 * 60) { // if install flag older than 5 minutes, discard
			unset($installStartTime);
			@ chmod($pth.'/../../assets/cache/installProc.inc.php', 0755);
			unlink($pth.'/../../assets/cache/installProc.inc.php');
		} 
		else {
			if ($_SERVER['REQUEST_METHOD'] != 'POST') {
				if (isset($_COOKIE[session_name()])) {
					session_unset();
					@session_destroy();
//					setcookie(session_name(), '', 0, MODX_BASE_URL);
				}
				$installGoingOn = 1;
			}
		}
	}
}

// andrazk 20070416 - if session started before install and was not destroyed yet
if (isset($lastInstallTime)) {
  if (isset($_SESSION['mgrValidated'])) {
		if (isset($_SESSION['modx.session.created.time'])) {
			if ($_SESSION['modx.session.created.time'] < $lastInstallTime) {
					if ($_SERVER['REQUEST_METHOD'] != 'POST') {
					if (isset($_COOKIE[session_name()])) {
						session_unset();
						@session_destroy();
//						setcookie(session_name(), '', 0, MODX_BASE_URL);
					}
					header('HTTP/1.0 307 Redirect');
					header('Location: '.MODX_MANAGER_URL.'index.php?installGoingOn=2');
				}
			}
		}
	}
}

if(!isset($_SESSION['mgrValidated'])){
	if(isset($manager_language)) {
		// establish fallback to English default
		include_once "lang/english.inc.php";
		// include localized overrides
		include_once "lang/".$manager_language.".inc.php";
	}
	else {
		include_once "lang/english.inc.php";
	}

	$modx->setPlaceholder('modx_charset',$modx_manager_charset);
	$modx->setPlaceholder('theme',$manager_theme);

	// invoke OnManagerLoginFormPrerender event
	$evtOut = $modx->invokeEvent('OnManagerLoginFormPrerender');
	$html = is_array($evtOut) ? implode('',$evtOut) : '';
	$modx->setPlaceholder('OnManagerLoginFormPrerender',$html);

	$modx->setPlaceholder('site_name',$site_name);
	$modx->setPlaceholder('manager_path',MGR_DIR);
	$modx->setPlaceholder('logo_slogan',$_lang["logo_slogan"]);
	$modx->setPlaceholder('login_message',$_lang["login_message"]);
	$modx->setPlaceholder('manager_theme_url',MODX_MANAGER_URL . 'media/style/' . $modx->config['manager_theme'] . '/');
	$modx->setPlaceholder('year',date('Y'));

	// andrazk 20070416 - notify user of install/update
	if (isset($_GET['installGoingOn'])) {
		$installGoingOn = $_GET['installGoingOn'];
	}
	if (isset($installGoingOn)) {			
		switch ($installGoingOn) {
		 case 1 : $modx->setPlaceholder('login_message',"<p><span class=\"fail\">".$_lang["login_cancelled_install_in_progress"]."</p><p>".$_lang["login_message"]."</p>"); break;
		 case 2 : $modx->setPlaceholder('login_message',"<p><span class=\"fail\">".$_lang["login_cancelled_site_was_updated"]."</p><p>".$_lang["login_message"]."</p>"); break;
		}
	}

	if($use_captcha==1)  {
		$modx->setPlaceholder('login_captcha_message',$_lang["login_captcha_message"]);
		$modx->setPlaceholder('captcha_image','<a href="'.MODX_MANAGER_URL.'" class="loginCaptcha"><img id="captcha_image" src="'.$modx->getManagerPath().'includes/veriword.php?rand='.rand().'" alt="'.$_lang["login_captcha_message"].'" /></a>');
		$modx->setPlaceholder('captcha_input','<label>'.$_lang["captcha_code"].'</label> <input type="text" name="captcha_code" tabindex="3" value="" />');
	}

	// login info
	$uid =  isset($_COOKIE['modx_remember_manager']) ? preg_replace('/[^a-zA-Z0-9\-_@\.]*/', '',  $_COOKIE['modx_remember_manager']) :''; 
	$modx->setPlaceholder('uid',$uid);
	$modx->setPlaceholder('username',$_lang["username"]);
	$modx->setPlaceholder('password',$_lang["password"]);

	// remember me
	$html =  isset($_COOKIE['modx_remember_manager']) ? 'checked="checked"' :'';
	$modx->setPlaceholder('remember_me',$html);
	$modx->setPlaceholder('remember_username',$_lang["remember_username"]);
	$modx->setPlaceholder('login_button',$_lang["login_button"]);
	
	// invoke OnManagerLoginFormRender event
	$evtOut = $modx->invokeEvent('OnManagerLoginFormRender');
	$html = is_array($evtOut) ? '<div id="onManagerLoginFormRender">'.implode('',$evtOut).'</div>' : '';
	$modx->setPlaceholder('OnManagerLoginFormRender',$html);

	// load template
    if(!isset($modx->config['manager_login_tpl']) || empty($modx->config['manager_login_tpl'])) {
    	$modx->config['manager_login_tpl'] = MODX_MANAGER_PATH . 'media/style/common/login.tpl'; 
    }
    
    $target = $modx->config['manager_login_tpl'];
    $target = str_replace('[+base_path+]', MODX_BASE_PATH, $target);
    $target = $modx->mergeSettingsContent($target);
    
    if(substr($target,0,1)==='@') {
    	if(substr($target,0,6)==='@CHUNK') {
    		$target = trim(substr($target,7));
    		$login_tpl = $modx->getChunk($target);
    	}
    	elseif(substr($target,0,5)==='@FILE') {
    		$target = trim(substr($target,6));
    		$login_tpl = file_get_contents($target);
    	}
	} else {
    	$chunk = $modx->getChunk($target);
    	if($chunk!==false && !empty($chunk)) {
    		$login_tpl = $chunk;
	}
    	elseif(is_file(MODX_BASE_PATH . $target)) {
    		$target = MODX_BASE_PATH . $target;
    		$login_tpl = file_get_contents($target);
    	}
    	elseif(is_file(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/login.tpl')) {
    		$target = MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/login.tpl';
    		$login_tpl = file_get_contents($target);
    	}
    	elseif(is_file(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/html/login.html')) { // ClipperCMS compatible
    		$target = MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/html/login.html';
    		$login_tpl = file_get_contents($target);
	}
	else {
    		$target = MODX_MANAGER_PATH . 'media/style/common/login.tpl';
    		$login_tpl = file_get_contents($target);
    	}
	}

    // merge placeholders
    $login_tpl = $modx->mergePlaceholderContent($login_tpl);
    $regx = strpos($login_tpl,'[[+')!==false ? '~\[\[\+(.*?)\]\]~' : '~\[\+(.*?)\+\]~'; // little tweak for newer parsers
    $login_tpl = preg_replace($regx, '', $login_tpl); //cleanup

    echo $login_tpl;

    exit;

} else {
	// log the user action
	if ($cip = getenv("HTTP_CLIENT_IP"))
		$ip = $cip;
	elseif ($cip = getenv("HTTP_X_FORWARDED_FOR"))
		$ip = $cip;
	elseif ($cip = getenv("REMOTE_ADDR"))
		$ip = $cip;
	else	$ip = "UNKNOWN";
	
	$_SESSION['ip'] = $ip;

    $itemid = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : '';
    $lasthittime = time();
    $action = isset($_REQUEST['a']) ? (int) $_REQUEST['a'] : 1;

    if($action !== 1) {
			if (!intval($itemid)) $itemid= null;
		$sql = sprintf('REPLACE INTO %s (internalKey, username, lasthit, action, id, ip)
			VALUES (%d, \'%s\', \'%d\', \'%s\', %s, \'%s\')',
			$modx->getFullTableName('active_users'), // Table
			$modx->getLoginUserID(),
			$_SESSION['mgrShortname'],
			$lasthittime,
			(string)$action,
			$itemid == null ? var_export(null, true) : $itemid,
			$ip
		);
		if(!$rs=$modx->db->query($sql)) {
			echo "error replacing into active users! SQL: ".$sql."\n".$modx->db->getLastError();
			exit;
		}
	}
}
?>