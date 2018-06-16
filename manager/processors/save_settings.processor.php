<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('settings')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}
$data = $_POST;
// lose the POST now, gets rid of quirky issue with Safari 3 - see FS#972
unset($_POST);

if($data['friendly_urls']==='1' && strpos($_SERVER['SERVER_SOFTWARE'],'IIS')===false)
{
	$htaccess        = $modx->config['base_path'] . '.htaccess';
	$sample_htaccess = $modx->config['base_path'] . 'ht.access';
	$dir = '/' . trim($modx->config['base_url'],'/');
	if(is_file($htaccess))
	{
		$_ = file_get_contents($htaccess);
		if(strpos($_,'RewriteBase')===false)
		{
			$warnings[] = $_lang["settings_friendlyurls_alert2"];
		}
		elseif(is_writable($htaccess))
		{
			$_ = preg_replace('@RewriteBase.+@',"RewriteBase {$dir}", $_);
			if(!@file_put_contents($htaccess,$_))
			{
				$warnings[] = $_lang["settings_friendlyurls_alert2"];
			}
		}
	}
	elseif(is_file($sample_htaccess))
	{
		if(!@rename($sample_htaccess,$htaccess))
        {
        	$warnings[] = $_lang["settings_friendlyurls_alert"];
		}
		elseif($modx->config['base_url']!=='/')
		{
			$_ = file_get_contents($htaccess);
			$_ = preg_replace('@RewriteBase.+@',"RewriteBase {$dir}", $_);
			if(!@file_put_contents($htaccess,$_))
			{
				$warnings[] = $_lang["settings_friendlyurls_alert2"];
			}
		}
	}
}

if (file_exists(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/styles.min.css')) {
    unlink(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/css/styles.min.css');
}

$data['filemanager_path'] = str_replace('[(base_path)]',MODX_BASE_PATH,$data['filemanager_path']);
$data['rb_base_dir']      = str_replace('[(base_path)]',MODX_BASE_PATH,$data['rb_base_dir']);

if (isset($data) && count($data) > 0) {
	if(isset($data['manager_language'])) {
		$lang_path = MODX_MANAGER_PATH . 'includes/lang/' . $data['manager_language'] . '.inc.php';
		if(is_file($lang_path)) {
			include($lang_path);
            global $modx_lang_attribute;
            $data['lang_code'] = !$modx_lang_attribute ? 'en' : $modx_lang_attribute;
		}
	}
	$savethese = array();
	$data['sys_files_checksum'] = $modx->getManagerApi()->getSystemChecksum($data['check_files_onlogin']);
	$data['mail_check_timeperiod'] = (int)$data['mail_check_timeperiod'] < 60 ? 60 : $data['mail_check_timeperiod']; // updateMail() in mainMenu no faster than every minute
	foreach ($data as $k => $v) {
		switch ($k) {
            case 'settings_version':{
                if($modx->getVersionData('version')!=$data['settings_version']){
                    $modx->logEvent(17,2,'<pre>'.var_export($data['settings_version'],true).'</pre>','fake settings_version');
                    $v = $modx->getVersionData('version');
                }
                break;
            }
			case 'error_page':
			case 'unauthorized_page':
			if (trim($v) == '' || !is_numeric($v)) {
				$v = $data['site_start'];
			}
			break;

			case 'lst_custom_contenttype':
			case 'txt_custom_contenttype':
				// Skip these
				$k = '';
				break;
			case 'rb_base_dir':
			case 'rb_base_url':
			case 'filemanager_path':
				$v = trim($v);
				$v = rtrim($v,'/') . '/';
				break;
            case 'manager_language':
                $langDir = realpath(MODX_MANAGER_PATH . 'includes/lang');
                $langFile = realpath(MODX_MANAGER_PATH . 'includes/lang/' . $v . '.inc.php');
                $langFileDir = dirname($langFile);
                if($langDir !== $langFileDir || !file_exists($langFile)) {
                    $v = 'english';
                }
				break;
			case 'smtppw':
				if ($v !== '********************' && $v !== '') {
					$v = trim($v);
					$v = base64_encode($v) . substr(str_shuffle('abcdefghjkmnpqrstuvxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 7);
					$v = str_replace('=','%',$v);
				} elseif ($v === '********************') {
					$k = '';
				}
				break;
            case 'valid_hostnames':
				$v = str_replace(array(' ,', ', '), ',', $v);
				if ($v !== ',') {
					$v = ($v != 'MODX_SITE_HOSTNAMES') ? $v : '';
					$configString = '<?php' . "\n" . 'define(\'MODX_SITE_HOSTNAMES\', \'' . $v . '\');' . "\n";
					@file_put_contents(MODX_BASE_PATH . 'assets/cache/siteHostnames.php', $configString);
				}
				$k = '';
				break;
			case 'session_timeout':
				$mail_check_timeperiod = $data['mail_check_timeperiod'];
				$v = (int)$v < ($data['mail_check_timeperiod']/60+1) ? ($data['mail_check_timeperiod']/60+1) : $v; // updateMail() in mainMenu pings as per mail_check_timeperiod, so +1min is minimum
				break;
			default:
			break;
		}
		$v = is_array($v) ? implode(",", $v) : $v;

		$modx->config[$k] = $v;

		if(!empty($k)) $savethese[] = '(\''.$modx->getDatabase()->escape($k).'\', \''.$modx->getDatabase()->escape($v).'\')';
	}

	// Run a single query to save all the values
	$sql = "REPLACE INTO ".$modx->getDatabase()->getFullTableName("system_settings")." (setting_name, setting_value)
		VALUES ".implode(', ', $savethese);
	$modx->getDatabase()->query($sql);

	// Reset Template Pages
	if (isset($data['reset_template'])) {
		$newtemplate = (int)$data['default_template'];
		$oldtemplate = (int)$data['old_template'];
		$tbl = $modx->getDatabase()->getFullTableName('site_content');
		$reset = $data['reset_template'];
		if($reset==1) $modx->getDatabase()->update(array('template' => $newtemplate), $tbl, "type='document'");
		else if($reset==2) $modx->getDatabase()->update(array('template' => $newtemplate), $tbl, "template='{$oldtemplate}'");
	}

	// empty cache
	$modx->clearCache('full');
}
$header="Location: index.php?a=7&r=10";
header($header);
