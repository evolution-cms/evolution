<?php


//---------------------------------------------------------------------------------
//   Utility functions
// 
//--------------------------------------------------------------------------------- 


// Pass useThisRule a comma separated list of allowed roles and templates, and it will
// return TRUE or FALSE to indicate whether this rule should be run on this page
function useThisRule($roles='', $templates='') {

	global $mm_current_page, $modx;
	$e = &$modx->Event;
	
	$exclude_roles = false;
	$exclude_templates = false;
	
	// Are they negative roles?
	if (substr($roles, 0, 1) == '!') {
		$roles = substr($roles, 1);
		$exclude_roles = true;
	}
	
	// Are they negative templates?
	if (substr($templates, 0, 1) == '!') {
		$templates = substr($templates, 1);
		$exclude_templates = true;
	}
	
	// Make the lists into arrays
	$roles = makeArray($roles);
	$templates = makeArray($templates);
	
	// Does the current role match the conditions supplied?
	$match_role_list = ($exclude_roles) ? !in_array($mm_current_page['role'], $roles) : in_array($mm_current_page['role'], $roles);

	// Does the current template match the conditions supplied?
	$match_template_list = ($exclude_templates) ? !in_array($mm_current_page['template'], $templates) : in_array($mm_current_page['template'], $templates);
	
	// If we've matched either list in any way, return true	
	if ( ($match_role_list || count($roles)==0) && ($match_template_list || count($templates)==0) ) {
		return true;
	} 
	
	return false;
}





// Makes a commas separated list into an array
function makeArray($csv) {
	
	// If we've already been supplied an array, just return it
	if (is_array($csv)) {
		return $csv;
	}	
	
	// Else if we have an empty string
	if (trim($csv)=='') {
		return array();
	}
	
	// Otherwise, turn it into an array
	$return = explode(',',$csv);
	array_walk( $return, create_function('$v, $k', 'return trim($v);'));	// Remove any whitespace
	return $return;
}





// Make an output JS safe
function jsSafe($str) {
	global $modx;
	
	// Only PHP versions > 5.2.3 allow us to prevent double_encoding
	// If you are using an older version of PHP, and use characters which require 
	// HTML entity encoding in new label names, etc you will have to specify the
	// actual character, not a pre-encoded version
	if (version_compare(PHP_VERSION, '5.2.3') >= 0) {
		return htmlentities($str, ENT_QUOTES, $modx->config['modx_charset'], false);
	} else {
		return htmlentities($str, ENT_QUOTES, $modx->config['modx_charset']);
	}
}






// Does the specified template use the specified TVs?
// $tpl_id = Template ID (int)
// $tvs = TV names - either array or comma separated list
// $types = TV types - e.g. image
function tplUseTvs($tpl_id, $tvs='', $types='') {
	
	// If it's a blank template, it can't have TVs
	if($tpl_id == 0){return false;}
	
	global $modx;
	
	// Make the TVs and field types into an array
	$fields = makeArray($tvs); 
	$types = makeArray($types); 
	
	// Get the DB table names
	$tv_table = $modx->getFullTableName('site_tmplvars');	
	$rel_table = $modx->getFullTableName('site_tmplvar_templates');
	
	// Are we looking at specific TVs, or all?
	$tvs_sql = !empty($fields) ? ' AND tvs.name IN ' . makeSqlList($fields) : '';
	
	// Are we looking at specific TV types, or all?
	$types_sql = !empty($types) ? ' AND type IN ' . makeSqlList($types) : '';
	
	// Make the SQL for this template
	$cur_tpl = !empty($tpl_id) ? ' AND rel.templateid = ' . $tpl_id : '';
		
	// Do the SQL query	
	$result = $modx->db->query("SELECT id FROM $tv_table tvs LEFT JOIN $rel_table rel ON rel.tmplvarid = tvs.id WHERE 1=1  $cur_tpl $tvs_sql $types_sql");

	// If we have results, return them, otherwise return false
	if ( $modx->db->getRecordCount($result) == 0) {
		return false;	
	} else {
		return $modx->db->makeArray($result);
	}
}





// Create a MySQL-safe list from an array
function makeSqlList($arr) {
	global $modx;
	
	$arr = makeArray($arr);
	foreach($arr as $k=>$tv) {
        //if (substr($tv, 0, 2) == 'tv') {$tv=substr($tv,2);}
		$arr[$k] = "'".$modx->db->escape($tv)."'"; // Escape them for MySQL
	}
	$sql = " (".implode(',',$arr).") ";
	return $sql;
}




// Generates the code needed to include an external script file. 
// $url is the URL of the external script
// $output_type is either js or html - depending on where the output is appearing
function includeJs($url, $output_type='js') {
	
	if ($output_type == 'js') {
		return '$j("head").append(\' <script src="'.$url.'" type="text/javascript"></scr\'+\'ipt> \'); ' . "\n";
	} else if ($output_type == 'html') {
		return '<script src="'.$url.'" type="text/javascript"></script>' . "\n";
	} else {
		return;	
	}
	
	
}



// Generates the code needed to include an external CSS file. 
// $url is any URL
// $output_type is either js or html - depending on where the output is appearing
function includeCss($url, $output_type='js') {
	if ($output_type == 'js') {
		return  '$j("head").append(\' <link href="'.$url.'" rel="stylesheet" type="text/css" /> \'); ' . "\n";	
	} else if ($output_type == 'html') {
		return  '<link href="'.$url.'" rel="stylesheet" type="text/css" />' . "\n";	
	} else {
		return;	
	}
}



//function tvIdFromName($tv_id) {
//	
//	global $modx;
//	
//	// Get the DB table names
//	$tv_table = $modx->getFullTableName('site_tmplvars');
//	
//	$tv_id = mysql_escape_string($tv_id);
//	
//	$result = $modx->db->query("SELECT id FROM $tv_table tvs WHERE name = '$tv_id'");
//	$result = $modx->db->makeArray($result);
//	
//	
//	
//	
//	
//	// If we have results, return them, otherwise return false
//	if ( $modx->db->getRecordCount($result) == 0) {
//		return false;	
//	} else {
//		print_r($result);
//		return $result[0]['id'];
//	}
//
//}


?>
