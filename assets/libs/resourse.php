<?php
/**
version: 0.4.2

Author:
	* Bumkaka from modx.im
	* Agel_Nash <agel_nash@xaker.ru>

USE:
require_once('assets/libs/resourse.php');
$resourse=resourse::Instance($modx);

#------------------------------------------------------
* Add new document without invoke event and clear cache
$resourse->document()->set('titl','Пропаганда')->set('pagetitle',$i)->save(null,false);

* Add new document without invoke event and call clear cache
$resourse->document()->set('titl','Пропаганда')->set('pagetitle',$i)->save(null,true);

* Add new document call event and without clear cache
$resourse->document()->set('titl','Пропаганда')->set('pagetitle',$i)->save(true,false);

#-------------------------------------------------------
#Edit resourse #13 
$resourse->edit(13)->set('pagetitle','new pagetitle')->save(null,false);

#-------------------------------------------------------
$resourse->delete(8);


//JSON && PHP < 5.3
$t = test::Instance();
function asd($json){
	$t = test::Instance();
	foreach($json as $key=>$val){
		$t->set($key,$val);
	}
}
$t->fromJson($json,'asd'); 

//JSON && PHP >= 5.3
$t = test::Instance();
$t->fromJson($json, function($json) use ($t){
	foreach($json as $key=>$val){
		$t->set($key,$val);
	}
});
*/


if(!defined('MODX_BASE_PATH')) {die('What are you doing? Get out of here!');}


class resourse {
	static $_instance = null;
	private $_modx = null;
	private $id = 0;
	private $field = array();
	private $tv = array();
	private $tvid = array();
	private $log = array();
	private $edit = 0;
	private $dafeult_field ;
	private $table=array('"'=>'_',"'"=>'_',' '=>'_','.'=>'_',','=>'_','а'=>'a','б'=>'b','в'=>'v',
		'г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y','к'=>'k',
		'л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u',
		'ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'sch','ь'=>'','ы'=>'y','ъ'=>'',
		'э'=>'e','ю'=>'yu','я'=>'ya','А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D','Е'=>'E',
		'Ё'=>'E','Ж'=>'Zh','З'=>'Z','И'=>'I','Й'=>'Y','К'=>'K','Л'=>'L','М'=>'M','Н'=>'N',
		'О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F','Х'=>'H','Ц'=>'C',
		'Ч'=>'Ch','Ш'=>'Sh','Щ'=>'Sch','Ь'=>'','Ы'=>'Y','Ъ'=>'','Э'=>'E','Ю'=>'Yu','Я'=>'Ya',
	);

	private $set;	
	private $flag = false;
	private $_table = array('site_content','site_tmplvar_contentvalues','site_tmplvars','site_templates','web_user_settings');
	
	private function __construct($modx){
		try{
			if($modx instanceof DocumentParser){
				$this->modx = $modx;
			} else throw new Exception('MODX should be instance of DocumentParser');
			
			if(!$this->makeTable()) throw new Exception('Not exists table');
			
		}catch(Exception $e){ die($e->getMessage()); }
		
		$this->get_TV();
	}
	
	private final function __clone(){throw new Exception('Clone is not allowed');}
	
	static public function Instance($modx){
		if (self::$_instance == NULL){self::$_instance = new self($modx);}
		return self::$_instance;
	}
	
	public function document($id=0){
		$this->newDoc = $id == 0;
		$this->id = $id;
		$this->field=array();
		$this->set=array();
		$this->default_field = array(
			'type'=>'document',
			'contentType'=>'text/html',
			'pagetitle'=>'New document',
			'longtitle'=>'',
			'description'=>'',
			'alias'=>'',
			'link_attributes'=>'',
			'published'=>'1',
			'pub_date'=>'0',
			'unpub_date'=>'0',
			'parent'=>'0',
			'isfolder'=>'0',
			'introtext'=>'',
			'content'=>'',
			'richtext'=>'1',
			'template'=>'0',
			'menuindex'=>'0',
			'searchable'=>'1',
			'cacheable'=>'1',
			'createdon'=>time(),
			'createdby'=>'0',
			'editedon'=>'0',
			'editedby'=>'0',
			'deleted'=>'0',
			'deletedon'=>'0',
			'deletedby'=>'0',
			'publishedon'=>'0',
			'publishedby'=>'0',
			'menutitle'=>'',
			'donthit'=>'0',
			'haskeywords'=>'0',
			'hasmetatags'=>'0',
			'privateweb'=>'0',
			'privatemgr'=>'0',
			'content_dispo'=>'0',
			'hidemenu'=>'1',
			'alias_visible'=>'1'
		);
		$this->flag = true;
		return $this;
	}
	
	private function makeTable(){
		//@TODO: check exists table
		$flag = true;
		foreach($this->_table as $item){
			$this->_table[$item] = $this->modx->getFullTableName($item);
		}
		return $flag;
	}
	
	private function Uset($key){
		if(!isset($this->field[$key])){ 
			$this->set[]= "{$key}=''";
			$this->log[] =  '{$key} is empty';
		} else {
			try{
				if(is_scalar($this->field[$key])){
					$this->set[]= "{$key}='{$this->field[$key]}'";
				} else throw new Exception("{$key} is not scalar <pre>".print_r($this->field[$key],true)."</pre>");
			}catch(Exception $e){ die($e->getMessage()); }
		}
		return $this;
	}
	
	private function invokeEvent($name,$data=array(),$flag=false){
		$flag = (isset($flag) && $flag!='') ? (bool)$flag : false;
		if($flag){
			$this->modx->invokeEvent($name,$data);
		}
		return $this;
	}
	
	public function clearCache($fire_events = null){
		$this->modx->clearCache();
		include_once (MODX_MANAGER_PATH . '/processors/cache_sync.class.processor.php');
		$sync = new synccache();
		$sync->setCachepath(MODX_BASE_PATH . "assets/cache/");
		$sync->setReport(false);
		$sync->emptyCache();
		$this->invokeEvent('OnSiteRefresh',array(),$fire_events);
	}
	
	public function list_log($flush = false){
		echo '<pre>'.print_r($this->log,true).'</pre>';
		if($flush) $this->clearLog();
		return $this;
	}
	
	public function clearLog(){
		$this->log = array();
		return $this;
	}
	
	public function set($key,$value){
		if(is_scalar($value) && is_scalar($key) && !empty($key)){
			switch($key){
				case 'template': {
					$value = trim($value);
					$value = $this->setTemplate($value);
					break;
				}
			}
			$this->field[$key] = $value;
		}
		return $this;
	}
	
	private function setTemplate($tpl) {
		if(!is_numeric($tpl) || $tpl != (int) $tpl) {
			try{
				if(is_scalar($tpl)){
					$sql = "SELECT id FROM {$this->_table['site_templates']} WHERE templatename = '{$tpl}'";
					$rs = $this->query($sql);
					if(!$rs || $this->modx->db->getRecordCount($rs) <= 0) throw new Exception("Template {$tpl} is not exists");
					$tpl = $this->modx->db->getValue($rs);
				} else throw new Exception("Invalid template name: ".print_r($tpl,1));
			}catch(Exception $e){
				$tpl = 0;
				die($e->getMessage()); 
			}
		}
		return (int)$tpl;
	}
		
	public function get($key){
		return isset($this->field[$key]) ? $this->field[$key] : null;
	}
	
	private function getAlias(){
		if ($this->modx->config['friendly_urls'] && $this->modx->config['automatic_alias'] && $this->get('alias') == ''){
			$alias = mb_strtolower(strtr($this->get('pagetitle'), $this->table));
		}else{
			if($this->get('alias')!=''){
				$alias = $this->get('alias');
			}else{
				$alias = '';
			}
		}
		return $this->checkAlias($alias);
	}
	
	private function query($SQL){
		return $this->modx->db->query($SQL);
	}
	
	public function get_TV(){
		$result = $this->query('SELECT id,name FROM '.$this->_table['site_tmplvars']);
		while($row = $this->modx->db->GetRow($result)) {
			$this->tv[$row['name']] = $row['id'];
			$this->tvid[$row['id']] = $row['name'];
		}
	}
	
	public function fromArray($data){
		foreach($data as $key=>$value) $this->set($key,$value);
		return $this;
	}
	
	public function edit($id){
		if(!$this->flag) $this->document($id);
		
		$result = $this->query("SELECT * from {$this->_table['site_content']} where id=".(int)$id);
		$this->fromArray($this->modx->db->getRow($result));

		$result = $this->query("SELECT * from {$this->_table['site_tmplvar_contentvalues']} where contentid=".(int)$id);
		while ($row = $this->modx->db->getRow($result)){
			$this->set($this->tvid[$row['tmplvarid']], $row['value']);
		}
		unset($this->field['id']);
		return $this;
	}
	
	private function systemID(){
		$ignore = array(
			0, //empty document
			(int)$this->modx->config['site_start'],
			(int)$this->modx->config['error_page'],
			(int)$this->modx->config['unauthorized_page'],
			(int)$this->modx->config['site_unavailable_page']
		);
		$data = $this->query("SELECT DISTINCT setting_value FROM {$this->_table['web_user_settings']} WHERE setting_name='login_home' AND setting_value!=''");
		$data = $this->modx->db->makeArray($data);
		foreach($data as $item){
			$ignore[]=(int)$item['setting_value'];
		}
		return array_unique($ignore);
		
	}
	
	public function delete($ids,$fire_events = null){
		//@TODO: delete with SET deleted=1
		$ignore = $this->systemID();
		$_ids = $this->cleanIDs($ids, ',', $ignore);
		try{
			if(is_array($_ids) && $_ids!=array()){
				$this->invokeEvent('OnBeforeEmptyTrash',array(
					"ids"=>$_ids
				),$fire_events);
		
				$id = $this->sanitarIn($_ids);
				$this->query("DELETE from {$this->_table['site_content']} where id IN ({$id})");
				$this->query("DELETE from {$this->_table['site_tmplvar_contentvalues']} where contentid IN ({$id})");
				
				$this->invokeEvent('OnEmptyTrash',array(
					"ids"=>$_ids
				),$fire_events);
			} else throw new Exception('Invalid IDs list for delete: <pre>'.print_r($ids,1).'</pre> please, check ignore list: <pre>'.print_r($ignore,1).'</pre>');
		}catch(Exception $e){ die($e->getMessage()); }
		
		return $this;
	}
	
	final private function cleanIDs($IDs,$sep=',',$ignore = array()) {
        $out=array();
        if(!is_array($IDs)){
			try{
				if(is_scalar($IDs)){
					$IDs=explode($sep, $IDs);
				} else {
					$IDs = array();
					throw new Exception('Invalid IDs list <pre>'.print_r($IDs,1).'</pre>');
				}
			} catch(Exception $e){ die($e->getMessage()); }
        }
        foreach($IDs as $item){
            $item = trim($item);
            if(is_numeric($item) && (int)$item>=0){ //Fix 0xfffffffff 
				if(!empty($ignore) && in_array((int)$item, $ignore, true)){
					$this->log[] =  'Ignore id '.(int)$item;
				}else{
					$out[]=(int)$item;
				}
            }
        }
		print_r($ignore);
        $out = array_unique($out);
		return $out;
	}
	
	final protected function check($id){
           return (is_array($id) && $id!=array()) ? true : false;
    }
	
	final protected function sanitarIn($data,$sep=','){
		if(!is_array($data)){
			$data=explode($sep,$data);
		}
		$out=array();
		foreach($data as $item){
			$out[]=$this->modx->db->escape($item);
		}
		$out="'".implode("','",$out)."'";
		return $out;
	}
	
	public function fromJson($data,$callback=null){
		try{
			if(is_scalar($data) && !empty($data)){
				$json = json_decode($data);
			}else throw new Exception("json is not string with json data");
			if ($this->jsonError($json)) { 
				if(isset($callback) && is_callable($callback)){
					call_user_func_array($callback,array($json));
				}else{
					if(isset($callback)) throw new Exception("Can't call callback JSON unpack <pre>".print_r($callback,1)."</pre>");
					foreach($json as $key=>$val){
						$this->set($key,$val);
					}
				}
			} else throw new Exception('Error from JSON decode: <pre>'.print_r($data,1).'</pre>');
		}catch(Exception $e){ die($e->getMessage()); }
		return $this;
	}
	
	public function toJson($callback=null){
		try{
			$data = $this->toArray();
			$json = json_encode($data);
			if(!$this->jsonError($data,$json)) {
				$json = false;
				throw new Exception('Error from JSON decode: <pre>'.print_r($data,1).'</pre>');
			}
		}catch(Exception $e){ die($e->getMessage()); }
		return $json;
	}
	
	private function jsonError($data){
		$flag = false;
		if(!function_exists('json_last_error')){
			function json_last_error(){
				return JSON_ERROR_NONE;
			}
		}
		if(json_last_error() === JSON_ERROR_NONE && is_object($data) && $data instanceof stdClass){
			$flag = true;
		}
		return $flag;
	}
	
	public function toArray(){
		return $this->field;
	}
	
	private function checkAlias($alias){
		if($this->modx->config['friendly_urls']){
			$flag = false;
			$_alias = $this->modx->db->escape($alias);
			if(!$this->modx->config['allow_duplicate_alias'] || ($this->modx->config['allow_duplicate_alias'] && $this->modx->conifg['use_alias_path'])){
				$flag = $this->modx->db->getValue($this->query("SELECT id FROM {$this->_table['site_content']} WHERE alias='{$_alias}' AND parent={$this->get('parent')} LIMIT 1"));
			} else {
				$flag = $this->modx->db->getValue($this->query("SELECT id FROM {$this->_table['site_content']} WHERE alias='{$_alias}' LIMIT 1"));
			}
			if(($flag && $this->newDoc) || (!$this->newDoc && $flag && $this->id != $flag)){
				$suffix = substr($alias, -2);
				if(preg_match('/-(\d+)/',$suffix,$tmp) && isset($tmp[1]) && (int)$tmp[1]>1){
					$suffix = (int)$tmp[1] + 1;
					$alias = substr($alias, 0, -2) . '-'. $suffix;
				}else{
					$alias .= '-2';
				}
				$alias = $this->checkAlias($alias);
			}
		}
		return $alias;
	}
	
	public function save($fire_events = null,$clearCache = false){
		try{
			if(!$this->flag){
				throw new Exception('You need flush document field before set and save resource');
			}
		}catch(Exception $e){ die($e->getMessage()); }
		
		if ($this->field['pagetitle'] == '') {
			$this->log[] =  'Pagetitle is empty in <pre>'.print_r($this->field,true).'</pre>';
			return false;
		}
		$this->set('alias',$this->getAlias());

		$this->invokeEvent('OnBeforeDocFormSave',array (
			"mode" => $this->newDoc ? "new" : "upd",
			"id" => $this->id ? $this->id : ''
		),$fire_events);
		
		$fld = $this->toArray();
		foreach($this->default_field as $key=>$value){
			if ($this->newDoc && $this->get($key) == '' && $this->get($key)!==$value){
				$this->set($key,$value);
			}
			$this->Uset($key);
			unset($fld[$key]);
		}
		if (!empty($this->set)){
			if($this->newDoc){
				$SQL = "INSERT into {$this->_table['site_content']} SET ".implode(', ', $this->set);
			}else{
				$SQL = "UPDATE {$this->_table['site_content']} SET ".implode(', ', $this->set)." WHERE id = ".$this->id;
			}
			$this->query($SQL);
		}
		
		if($this->newDoc) $this->id = $this->modx->db->getInsertId();
		
		foreach($fld as $key=>$value){
			if ($value=='') continue;
 			if ($this->tv[$key]!=''){
				$rc = $this->query("SELECT value FROM {$this->_table['site_tmplvar_contentvalues']} WHERE `contentid` = '{$this->id}' AND `tmplvarid` = '{$this->tv[$key]}';");
				$row = mysql_fetch_assoc($rc);
				if (is_array($row)) {
					if ($row[0] != $value) {
						$result = $this->query("UPDATE {$this->_table['site_tmplvar_contentvalues']} SET `value` = '{$value}' WHERE `contentid` = '{$this->id}' AND `tmplvarid` = '{$this->tv[$key]}';");
				    }
				}else{	
					$result = $this->query("INSERT into {$this->_table['site_tmplvar_contentvalues']} SET `contentid` = {$this->id},`tmplvarid` = {$this->tv[$key]},`value` = '{$value}';");
				}
			}
		}
		$this->invokeEvent('OnDocFormSave',array (
			"mode" => $this->newDoc ? "new" : "upd",
			"id" => $this->id
		),$fire_events);
		
		if($clearCache){ 
			$this->clearCache($fire_events); 
		}
		$this->flag = false;
		return $this->id;
	}
}