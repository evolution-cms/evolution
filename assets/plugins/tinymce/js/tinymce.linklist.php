<?php
// Sorted Link List for TinyMCE
// v1.0.3
// By NCrossland
//
// Changelog:
// 1.0: First release
// 1.0.1: Update to fix broken accented characters (thanks davidm)
// 1.0.2: Added choice of breadcrumbs or tree-style display (thanks raum). Setting for charset (thanks mmjaeger)
// 1.0.3: Added a choice of tree indent styles (thanks raum). Added ability to sort by menuindex and improved avoiding showing unpublished documents. 
//
// To do:
// * Based on Modx DB API, rather than raw SQL
// * If can't do the above, use less database queries
// * Make interface prettier -- proper icons for documents and file paths. Maybe a select element isn't best for this?!
// * Make options available in manager
// * integrate into TinyMCE plugin release
//
// Installation:
// Replace the contents of tinymce.linklist.php with this file. Done!

// Config options
$templates_to_ignore = array();	// Template IDs to ignore from the link list
$include_page_ids    = false;
$charset             = 'UTF-8';
$mode                = 'tree'; // breadcrumbs or tree
$tree_style          = '1'; // What style should the tree use? Choose 1,2,3 or 4
$sortby              = 'menuindex'; // Could be menuindex or menutitle
$limit               = 0;
$recent              = 0;

/* That's it to config! */
include_once(dirname(__FILE__)."/../../../cache/siteManager.php");
$tree_styles = array('|--', '&#9494;&nbsp;', '&#9658;&nbsp;', 'L&nbsp;');
define('MODX_API_MODE', true);
define("IN_MANAGER_MODE", "true");
$self = 'assets/plugins/tinymce/js/tinymce.linklist.php';
$base_path = str_replace($self,'',str_replace('\\','/',__FILE__));
$mtime = microtime();
$manage_path = '../../../../'.MGR_DIR.'/';
include($manage_path . 'includes/config.inc.php');
include($manage_path . 'includes/document.parser.class.inc.php');
$modx = new DocumentParser;
$mtime = explode(" ",$mtime);
$modx->tstart = $mtime[1] + $mtime[0];;
$modx->mstart = memory_get_usage();
startCMSSession();
$modx->db->connect();
$modx->getSettings();

/* only display if manager user is logged in */
if ($modx->getLoginUserType() !== 'manager')
{
    // Make output a real JavaScript file!
    header('Content-type: application/x-javascript'); // browser will now recognize the file as a valid JS file
    
    // prevent browser from caching
    header('pragma: no-cache');
    header('expires: 0'); // i.e. contents have already expired
    
    echo 'var tinyMCELinkList = new Array();';
    exit();
}

$cache_path = $base_path . 'assets/cache/mce_linklist.pageCache.php';
if(file_exists($cache_path))
{
	$output = file_get_contents($cache_path);
}
else
{
	$linklist = new LINKLIST();
	
	$allpages = $linklist->getAllPages($limit,$recent);
	if (!is_array($allpages) ) {die();}
	
	$list = array();
	
	foreach($allpages as $page)
	{
		if (!in_array($page['template'], $templates_to_ignore) )
		{
			$caption = '';
			$page['parents'] = array_reverse($page['parents']);
			$breadcrumbs = array();
			$sortcrumbs = array();
			$published = $page['published'];
			foreach ($page['parents'] as $parent)
			{
				$p = $linklist->getPage($parent);
				
				// Assemble what will be displayed
				if($p['menutitle'])
				{
					$breadcrumbs[] = htmlentities($p['menutitle'],ENT_QUOTES,$charset) . " ({$page['id']})";
				}
				else
				{
					$breadcrumbs[] = htmlentities($p['pagetitle'],ENT_QUOTES,$charset) . " ({$page['id']})";
				}
				
				// How will it be sorted?
				if ($sortby == 'menuindex')
				{
					$more_sortby_types = array('menutitle','pagetitle');
					foreach ($more_sortby_types as $backup_sort_type)
					{
						if ( $page[$backup_sort_type] != '')
						{
							$sortcrumbs[] = sprintf("%010d", $p[$sortby]);
							break;
						}
					}
				}
				else
				{
					$sortcrumbs[] = $p[$sortby];
				}
				
				if ($p['published'] !== '1')
				{
					$published = 0;
				}
			}
			if ($mode=='tree')
			{	// tree mode
				$bc_count = count($breadcrumbs);
				if ($bc_count>1)
				{
					$caption = str_repeat('&nbsp;', ($bc_count-1)*3);
					$caption .= $tree_styles[$tree_style-1];
					$caption .= $breadcrumbs[$bc_count-1];
				}
				else
				{
					$caption = $breadcrumbs[0];
				}
			}
			else
			{	// breadcrumb mode
				$caption = implode('&gt;', $breadcrumbs);
			}
			
			$keyname = implode('-', $sortcrumbs);
			
			// Check for duplicates
			$sc_count = count($sortcrumbs)-1;
			while (isset($list[$keyname]))
			{
				$sortcrumbs[$sc_count] += 1000000000;
				$keyname = implode('-', $sortcrumbs);
			}
			
			//$caption = $keyname;
			
			$output = '["' .$caption;
			if ($include_page_ids)
			{
				$output .= ' (' . $page['id'] . ')';
			}
			$output .= '", "[~' . $page['id'] . '~]"]';
			
			if ($published == '1')
			{
				$list[$keyname] = $output;
			}
		}
	}
	
	// Sort the list by it's keys
	ksort($list);
	
	// Output the array separated by commas
	$list_output = implode(", \n", $list);
	
	// Output as javascript
	$output = "var tinyMCELinkList = new Array(\n". $list_output .");";
	file_put_contents($cache_path,$output);
}

// Make output a real JavaScript file!
header("Content-type: text/javascript; charset=".$modx->config['modx_charset']); // browser will now recognize the file as a valid JS file

// prevent browser from caching
header('pragma: no-cache');
header('expires: 0'); // i.e. contents have already expired

echo $output;

class LINKLIST
{
	function LINKLIST()
	{
	}
	
	function getAllPages($limit=0,$recent=0,$id=0, $sort='parent', $dir='ASC', $fields='pagetitle, id, menutitle, parent, template, menuindex, published')
	{
		global $modx;
		
		// modify field names to use sc. table reference
		$fields = preg_replace('@\s*@','',$fields);
		$fields = 'sc.'.implode(',sc.',explode(',',$fields));
		$sort   = preg_replace('@\s*@','',$sort);
		$sort   = 'sc.'.implode(',sc.',explode(',',$sort));
		
		if($recent!==0 && preg_match('@^[0-9]+$@',$recent))
		{
			$where_recent = time() - ($recent * 3600 * 24);
			$where_recent = "AND {$where_recent} < sc.editedon";
			$fields .= ',sc.editedon';
		}
		else $where_recent = '';
		
		if($limit!==0 && preg_match('@^[0-9]+$@',$limit))
		{
			$limit =  "LIMIT {$limit}";
		}
		else
		{
			$limit =  "LIMIT 2000";
		}
		
		$tblsc = $modx->getFullTableName('site_content');
		$tbldg = $modx->getFullTableName('document_groups');
	
	    $sql = "SELECT DISTINCT $fields FROM $tblsc sc
	      LEFT JOIN $tbldg dg on dg.document = sc.id
	      WHERE sc.published=1 AND sc.deleted=0 {$where_recent}
	      ORDER BY sc.editedon DESC, {$sort} {$dir}
	      {$limit};";
		$resourceArray = $this->doSql($sql);
		$count = count($resourceArray);
		for($i=0; $i<$count; $i++)
		{
			$p = $this->getAllParents($resourceArray[$i]['id']);
			$resourceArray[$i]['parents'] = $p;
		}
	    return $resourceArray;
	}
	
	function getAllParents($doc_id) {
		$return_array = array($doc_id);
		while ($doc_id = $this->getParent($doc_id))
		{
			if($doc_id===0) break;
			$return_array[] = $doc_id;
		} 
		return $return_array;
	}
	
	function getParent($doc_id) {
		$r = $this->getPage($doc_id);
		return $r['parent'];
	}
	
	function getPage($doc_id)
	{
		global $modx;
		global $page_cache;
		
		// If already cached, return this instead of doing another MySQL query
		if (isset($page_cache[$doc_id]))
		{
			return $page_cache[$doc_id];
		}
		
	    $tblsc = $modx->getFullTableName('site_content');
	    $tbldg = $modx->getFullTableName('document_groups');
	
	    $sql = "SELECT sc.parent, sc.menutitle, sc.pagetitle, sc.menuindex, sc.published FROM $tblsc sc
	      LEFT JOIN $tbldg dg on dg.document = sc.id
	      WHERE sc.published=1 AND sc.deleted=0 AND sc.id=$doc_id;";
		  
		$resourceArray = $this->doSql($sql);
		
		// If we have got this far, it must not have been cached already, so lets do it now.
		$page_cache[$doc_id] = $resourceArray[0];
	
	    return $resourceArray[0];
	}
	
	function doSql($sql)
	{
		global $modx;
		// Connecting, selecting database
		
		$result = $modx->db->query($sql);
		$resourceArray = array();
		while($par = $modx->db->getRow($result))
		{
			$resourceArray[] = $par;
		}
		
		return $resourceArray;
	}
}