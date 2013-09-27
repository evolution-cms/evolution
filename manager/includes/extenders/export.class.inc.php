<?php
class EXPORT_SITE
{
	var $targetDir;
	var $generate_mode;
	var $total;
	var $count;
	var $ignore_ids;
	var $exportstart;
	var $repl_before;
	var $repl_after;
	var $output = array();
	
	function EXPORT_SITE()
	{
		global $modx;
		
		if(!defined('MODX_BASE_PATH'))  return false;
		$this->exportstart = $this->get_mtime();
		$this->count = 0;
		$this->setUrlMode();
		$this->dirCheckCount = 0;
		$this->generate_mode = 'crawl';
		$this->targetDir = $modx->config['base_path'] . 'temp/export';
		if(!isset($this->total)) $this->getTotal();
	}
	
	function setExportDir($dir)
	{
		$dir = str_replace('\\','/',$dir);
		$dir = rtrim($dir, '/');
		$this->targetDir = $dir;
	}
	
	function get_mtime()
	{
		$mtime = microtime();
		$mtime = explode(' ', $mtime);
		$mtime = $mtime[1] + $mtime[0];
		return $mtime;
	}
	
	function setUrlMode()
	{
		global $modx;
		
		if($modx->config['friendly_urls']==0)
		{
			$modx->config['friendly_urls']  = 1;
			$modx->config['use_alias_path'] = 1;
			$this->clearCache();
		}
		$modx->config['make_folders'] = '1';
	}
	
	function getTotal($ignore_ids='', $noncache='0')
	{
		global $modx;
		
		if($ignore_ids !== '')
		{
			$ignore_ids = explode(',', $ignore_ids);
			foreach($ignore_ids as $i=>$v)
			{
				$v = $modx->db->escape(trim($v));
				$ignore_ids[$i] = "'{$v}'";
			}
			$ignore_ids = join(',', $ignore_ids);
			$ignore_ids = "AND NOT id IN ({$ignore_ids})";
		}
		
		$this->ignore_ids = $ignore_ids;
		
		$noncache = $include_noncache==1 ? '' : 'AND cacheable=1';
		$where = "deleted=0 AND ((published=1 AND type='document') OR (isfolder=1)) {$noncache} {$ignore_ids}";
		$rs  = $modx->db->select('count(id) as total','[+prefix+]site_content',$where);
		$row = $modx->db->getRow($rs);
		$this->total = $row['total'];
		return $row['total'];
	}
	
	function removeDirectoryAll($directory='')
	{
		if(empty($directory)) $directory = $this->targetDir;
		$directory = rtrim($directory,'/');
		// if the path is not valid or is not a directory ...
		if(empty($directory)) return false;
		if(strpos($directory,MODX_BASE_PATH)===false) return FALSE;
		
		if(!is_dir($directory))          return FALSE;
		elseif(!is_readable($directory)) return FALSE;
		else
		{
			$files = glob($directory . '/*');
			if(!empty($files))
			{
    			foreach($files as $path)
    			{
    				if(is_dir($path)) $this->removeDirectoryAll($path);
    				else              $rs = unlink($path);
    			}
			}
		}
		if($directory !== $this->targetDir) $rs = rmdir($directory);
		
		return $rs;
	}

	function makeFile($docid, $filepath)
	{
		global  $modx,$_lang;
		$file_permission = octdec($modx->config['new_file_permissions']);
		if($this->generate_mode==='direct')
		{
			$back_lang = $_lang;
			$src = $modx->executeParser($docid);
			
			$_lang = $back_lang;
		}
		else $src = $this->curl_get_contents(MODX_SITE_URL . "index.php?id={$docid}");
		
		
		if($src !== false)
		{
			if($this->repl_before!==$this->repl_after) $src = str_replace($this->repl_before,$this->repl_after,$src);
			$result = file_put_contents($filepath,$src);
			if($result!==false) @chmod($filepath, $file_permission);
			
			if($result !== false) return 'success';
			else                  return 'failed_no_write';
		}
		else                      return 'no_retrieve';
	}

	function getFileName($docid, $alias='', $prefix, $suffix)
	{
		global $modx;
		
		if($alias==='') $filename = $prefix.$docid.$suffix;
		else
		{
			if($modx->config['suffix_mode']==='1' && strpos($alias, '.')!==false)
			{
				$suffix = '';
			}
			$filename = $prefix.$alias.$suffix;
		}
		return $filename;
	}

	function run($parent=0)
	{
		global $_lang;
		global $modx;
		$ignore_ids = $this->ignore_ids;
		$dirpath = $this->targetDir . '/';
		
		$prefix = $modx->config['friendly_url_prefix'];
		$suffix = $modx->config['friendly_url_suffix'];
		
		$tpl = ' <span class="[+status+]">[+msg1+]</span> [+msg2+]</span>';
		$ph = array();
		
		$ph['status'] = 'fail';
		$ph['msg1']   = $_lang['export_site_failed'];
		$ph['msg2']   = $_lang["export_site_failed_no_write"] . ' - ' . $dirpath;
		$msg_failed_no_write    = $this->parsePlaceholder($tpl,$ph);
		
		$ph['msg2']   = $_lang["export_site_failed_no_retrieve"];
		$msg_failed_no_retrieve = $this->parsePlaceholder($tpl,$ph);
		
		$ph['status'] = 'success';
		$ph['msg1']   = $_lang['export_site_success'];
		$ph['msg2']   = '';
		$msg_success            = $this->parsePlaceholder($tpl,$ph);
		
		$ph['msg2']   = $_lang['export_site_success_skip_doc'];
		$msg_success_skip_doc = $this->parsePlaceholder($tpl,$ph);
		
		$ph['msg2']   = $_lang['export_site_success_skip_dir'];
		$msg_success_skip_dir = $this->parsePlaceholder($tpl,$ph);
		
		$fields = "id, alias, pagetitle, isfolder, (content = '' AND template = 0) AS wasNull, published";
		$noncache = $_POST['includenoncache']==1 ? '' : 'AND cacheable=1';
		$where = "parent = '{$parent}' AND deleted=0 AND ((published=1 AND type='document') OR (isfolder=1)) {$noncache} {$ignore_ids}";
		$rs = $modx->db->select($fields,'[+prefix+]site_content',$where);
		
		$ph = array();
		$ph['total']     = $this->total;
		$folder_permission = octdec($modx->config['new_folder_permissions']);
		while($row = $modx->db->getRow($rs))
		{
			$this->count++;
			
			$row['count']     = $this->count;
			$row['url'] = $modx->makeUrl($row['id']);
			
			if (!$row['wasNull'])
			{ // needs writing a document
				$docname = $this->getFileName($row['id'], $row['alias'], $prefix, $suffix);
				$filename = $dirpath.$docname;
				if (!is_file($filename))
				{
					if($row['published']==='1')
					{
						$status = $this->makeFile($row['id'], $filename);
						switch($status)
						{
							case 'failed_no_write'   : $row['status'] = $msg_failed_no_write   ;
							case 'failed_no_retrieve': $row['status'] = $msg_failed_no_retrieve;
							default:                   $row['status'] = $msg_success;
						}
					}
					else $row['status'] = $msg_failed_no_retrieve;
				}
				else     $row['status'] = $msg_success_skip_doc;
				$this->output[] = $this->parsePlaceholder($_lang['export_site_exporting_document'], $row);
			}
			else
			{
				$row['status'] = $msg_success_skip_dir;
				$this->output[] = $this->parsePlaceholder($_lang['export_site_exporting_document'], $row);
			}
			if ($row['isfolder']==='1' && ($modx->config['suffix_mode']!=='1' || strpos($row['alias'],'.')===false))
			{ // needs making a folder
				$end_dir = ($row['alias']!=='') ? $row['alias'] : $row['id'];
				$dir_path = $dirpath . $end_dir;
				if(strpos($dir_path,MODX_BASE_PATH)===false) return FALSE;
				if (!is_dir($dir_path))
				{
					if (is_file($dir_path)) @unlink($dir_path);
					mkdir($dir_path);
					@chmod($dir_path, $folder_permission);
					
				}
				
				
				if($modx->config['make_folders']==='1' && $row['published']==='1')
				{
					if(is_file($filename)) rename($filename,$dir_path . '/index.html');
				}
				$this->targetDir = $dir_path;
				$this->run($row['id']);
			}
		}
		return join("\n", $this->output);
	}
	
    function curl_get_contents($url, $timeout = 30 )
    {
    	if(!function_exists('curl_init')) return @file_get_contents($url);
    	
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    function parsePlaceholder($tpl,$ph=array())
    {
    	foreach($ph as $k=>$v)
    	{
    		$k = "[+{$k}+]";
    		$tpl = str_replace($k,$v,$tpl);
    	}
    	return $tpl;
    }

    function clearCache()
    {
		include_once(MODX_BASE_PATH . 'manager/processors/cache_sync.class.processor.php');
		$sync = new synccache();
		$sync->setCachepath(MODX_BASE_PATH . 'assets/cache/');
		$sync->setReport(false);
		$sync->emptyCache();
    }
}
