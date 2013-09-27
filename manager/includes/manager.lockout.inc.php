<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

if($_REQUEST['a']!='8' && isset($_SESSION['mgrValidated'])){
    
    $homeurl = $modx->makeUrl($manager_login_startup>0 ? $manager_login_startup:$site_start);
    $logouturl = './index.php?a=8';

    $modx->setPlaceholder('modx_charset',$modx_manager_charset);
    $modx->setPlaceholder('theme',$manager_theme);

    $modx->setPlaceholder('site_name',$site_name);
    $modx->setPlaceholder('logo_slogan',$_lang["logo_slogan"]);
    $modx->setPlaceholder('manager_lockout_message',$_lang["manager_lockout_message"]);

    $modx->setPlaceholder('home',$_lang["home"]);
    $modx->setPlaceholder('homeurl',$homeurl);
    $modx->setPlaceholder('logout',$_lang["logout"]);
    $modx->setPlaceholder('logouturl',$logouturl);
    $modx->setPlaceholder('manager_theme_url',MODX_MANAGER_URL . 'media/style/' . $modx->config['manager_theme'] . '/');
    $modx->setPlaceholder('year',date('Y'));

    // load template
    if(!isset($modx->config['manager_lockout_tpl']) || empty($modx->config['manager_lockout_tpl'])) {
    	$modx->config['manager_lockout_tpl'] = MODX_MANAGER_PATH . 'media/style/common/manager.lockout.tpl'; 
    }
    
    $target = $modx->config['manager_lockout_tpl'];
    $target = str_replace('[+base_path+]', MODX_BASE_PATH, $target);
    $target = $modx->mergeSettingsContent($target);
    
    if(substr($target,0,1)==='@') {
    	if(substr($target,0,6)==='@CHUNK') {
    		$target = trim(substr($target,7));
    		$lockout_tpl = $modx->getChunk($target);
    	}
    	elseif(substr($target,0,5)==='@FILE') {
    		$target = trim(substr($target,6));
    		$lockout_tpl = file_get_contents($target);
    	}
	} else {
    	$chunk = $modx->getChunk($target);
    	if($chunk!==false && !empty($chunk)) {
    		$lockout_tpl = $chunk;
    	}
    	elseif(is_file(MODX_BASE_PATH . $target)) {
    		$target = MODX_BASE_PATH . $target;
    		$lockout_tpl = file_get_contents($target);
    	}
    	elseif(is_file(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/manager.lockout.tpl')) {
    		$target = MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/manager.lockout.tpl';
    		$lockout_tpl = file_get_contents($target);
    	}
    	elseif(is_file(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/html/manager.lockout.html')) { // ClipperCMS compatible
    		$target = MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/html/manager.lockout.html';
    		$lockout_tpl = file_get_contents($target);
    	}
    	else {
    		$target = MODX_MANAGER_PATH . 'media/style/common/manager.lockout.tpl';
    		$lockout_tpl = file_get_contents($target);
    	}
	}

    // merge placeholders
    $lockout_tpl = $modx->mergePlaceholderContent($lockout_tpl);
    $regx = strpos($lockout_tpl,'[[+')!==false ? '~\[\[\+(.*?)\]\]~' : '~\[\+(.*?)\+\]~'; // little tweak for newer parsers
    $lockout_tpl = preg_replace($regx, '', $lockout_tpl); //cleanup

    echo $lockout_tpl;    
    
    exit;
}

?>