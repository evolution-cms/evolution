<?php

class DocManager {
	var $modx = null;
	var $lang = array();
	var $ph = array();
	var $theme = '';
	var $fileRegister = array();
	
    function DocManager(&$modx) {
    	$this->modx = $modx;
    }
    
    function getLang() {
    	$_lang = array();
    	$ph = array();
		$managerLanguage = $this->modx->config['manager_language'];

		$userId = $this->modx->getLoginUserID();
		if (!empty($userId)) {
			$lang = $this->modx->db->select('setting_name, setting_value', $this->modx->getFullTableName('user_settings'), 'setting_name=\'manager_language\' AND user='.$userId);
	
			if ($this->modx->db->getRecordCount($lang) > 0) {
	   			$row = $this->modx->db->getRow($lang);
	   	 		$managerLanguage = $row['setting_value'];
			}
		}
		
		include MODX_MANAGER_PATH.'includes/lang/english.inc.php';
		if($managerLanguage != 'english') {
			if (file_exists(MODX_MANAGER_PATH.'includes/lang/'.$managerLanguage.'.inc.php')) {
     			include MODX_MANAGER_PATH.'includes/lang/'.$managerLanguage.'.inc.php';
			}
		}
		
		include MODX_BASE_PATH.'assets/modules/docmanager/lang/english.inc.php';
		if($managerLanguage != 'english') {
			if (file_exists(MODX_BASE_PATH.'assets/modules/docmanager/lang/'.$managerLanguage.'.inc.php')) {
     			include MODX_BASE_PATH.'assets/modules/docmanager/lang/'.$managerLanguage.'.inc.php';
			}
		}
		$this->lang = $_lang;
		foreach ($_lang as $key => $value) {
			$ph['lang.'.$key] = $value;
		}
		return $ph;
    }
    
    function getTheme() {
    	$theme = $this->modx->db->select('setting_value', $this->modx->getFullTableName('system_settings'), 'setting_name=\'manager_theme\'', '');
		if ($this->modx->db->getRecordCount($theme)) {
			$theme = $this->modx->db->getRow($theme);
			$this->theme = ($theme['setting_value'] <> '') ? '/' . $theme['setting_value'] : '';
			return $this->theme;
		} else {
			return '';
		}
    }
    
    function getFileContents($file) {
    	if (empty($file)) {
    		return false;
    	} else {
	    	$file = MODX_BASE_PATH.'assets/modules/docmanager/templates/'.$file;
	    	if(array_key_exists($file, $this->fileRegister)) {
	    		return $this->fileRegister[$file];
	    	} else {
	    		$contents = file_get_contents($file);
	    		$this->fileRegister[$file] = $contents;
	    		return $contents;
	    	}
	    }
    }
    
    function loadTemplates() {
    	$this->fileGetContents('main.tpl');
    }
    
    function parseTemplate($tpl, $values = array()) {
    	$tpl = array_key_exists($tpl, $this->fileRegister) ? $this->fileRegister[$tpl] : $this->getFileContents($tpl);
    	if($tpl) {
    		foreach ($values as $key => $value) {
    			$tpl = str_replace('[+'.$key.'+]', $value, $tpl); 
    		}
    		$tpl = preg_replace('/(\[\+.*?\+\])/' ,'', $tpl);
    		return $tpl;
    	} else {
    		return '';
    	}
    }
}
?>