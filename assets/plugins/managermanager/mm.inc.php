<?php
/**
 * @name ManagerManager
 * @version 0.3.11 (2012-02-15)
 * 
 * @for MODx Evolution 1.0.x
 * 
 * @author Nick Crossland - www.rckt.co.uk, studio DivanDesign - www.DivanDesign.biz
 * 
 * @description Used to manipulate the display of document fields in the manager.
 * 
 * @installation See /docs/install.htm
 * 
 * @inspiration HideEditor plugin by Timon Reinhard and Gildas; HideManagerFields by Brett @ The Man Can!
 * 
 * @license Released under the GNU General Public License: http://creativecommons.org/licenses/GPL/2.0/
*/

$mm_version = '0.3.11'; 


// Bring in some preferences which have been set on the configuration tab of the plugin, and normalise them



// JS URL
switch ($which_jquery) {
 case 'local (assets/js)':
  $js_url  = $js_default_url_local; 
 break;

 case 'remote (google code)':
  $js_url  = $js_default_url_remote;
 break;

 case 'manual url (specify below)':
  $js_url  = $js_src_override;
 break;
}

// Should we remove deprecated Template variable types from the TV creation list?
$remove_deprecated_tv_types = ($remove_deprecated_tv_types_pref == 'yes') ? true : false;


// When loading widgets / functions, ignore folders / files beginning with these chars
$ignore_first_chars = array('.', '_', '!'); 

// Include functions - we'll load all *.inc.php files in the "functions" folder
$function_dir = $modx->config['base_path'] . 'assets/plugins/managermanager/functions';
if ($handle = opendir($function_dir)) {
    while (false !== ($file = readdir($handle))) {
        if (!in_array(substr($file, 0, 1), $ignore_first_chars) && $file != ".." && substr($file, -8) == '.inc.php') {
            include_once($function_dir.'/'.$file);
        }
    }
    closedir($handle);
}


// Include widgets
// We look for a PHP file with the same name as the directory - e.g.
// /widgets/widgetname/widgetname.php
$widget_dir = $modx->config['base_path'] . 'assets/plugins/managermanager/widgets';
if ($handle = opendir($widget_dir)) {
    while (false !== ($file = readdir($handle))) {
        if (!in_array(substr($file, 0, 1), $ignore_first_chars)  && $file != ".."  && is_dir($widget_dir.'/'.$file)) {
            include_once($widget_dir.'/'.$file.'/'.$file.'.php');
        }
    }
    closedir($handle);
}



// Set variables
global $content,$default_template, $mm_current_page, $mm_fields;
$mm_current_page = array();
$mm_current_page['template'] = isset($_POST['template']) ? $_POST['template'] : isset($content['template']) ? $content['template'] : $default_template;
$mm_current_page['role'] = $_SESSION['mgrRole'];


// What are the fields we can change, and what types are they?
$mm_fields = array(
	'pagetitle' => array('fieldtype'=>'input', 'fieldname'=>'pagetitle', 'dbname'=>'pagetitle', 'tv'=>false),
	'longtitle' => array('fieldtype'=>'input', 'fieldname'=>'longtitle', 'dbname'=>'longtitle', 'tv'=>false),
	'description' => array('fieldtype'=>'input', 'fieldname'=>'description', 'dbname'=>'description', 'tv'=>false),
	'alias' => array('fieldtype'=>'input', 'fieldname'=>'alias', 'dbname'=>'alias', 'tv'=>false),
	'link_attributes' => array('fieldtype'=>'input', 'fieldname'=>'link_attributes', 'dbname'=>'link_attributes', 'tv'=>false),
	'introtext' => array('fieldtype'=>'textarea', 'fieldname'=>'introtext', 'dbname'=>'introtext', 'tv'=>false), 
	'template' => array('fieldtype'=>'select', 'fieldname'=>'template', 'dbname'=>'template', 'tv'=>false),  
	'menutitle' => array('fieldtype'=>'input', 'fieldname'=>'menutitle','dbname'=>'menutitle',  'tv'=>false),
	'menuindex' => array('fieldtype'=>'input', 'fieldname'=>'menuindex', 'dbname'=>'menuindex', 'tv'=>false),
	'show_in_menu' => array('fieldtype'=>'input', 'fieldname'=>'hidemenucheck','dbname'=>'hidemenu',  'tv'=>false),
	'hide_menu' => array('fieldtype'=>'input', 'fieldname'=>'hidemenucheck', 'dbname'=>'hidemenu', 'tv'=>false), // synonym for show_in_menu
	'parent' => array('fieldtype'=>'input', 'fieldname'=>'parent', 'dbname'=>'parent', 'tv'=>false),
	'is_folder' => array('fieldtype'=>'input', 'fieldname'=>'isfoldercheck', 'dbname'=>'isfolder', 'tv'=>false),
	'is_richtext' => array('fieldtype'=>'input', 'fieldname'=>'richtextcheck','dbname'=>'richtext',  'tv'=>false),
	'log' => array('fieldtype'=>'input', 'fieldname'=>'donthitcheck', 'dbname'=>'donthit', 'tv'=>false),
	'published' => array('fieldtype'=>'input', 'fieldname'=>'publishedcheck','dbname'=>'published',  'tv'=>false),
	'pub_date' => array('fieldtype'=>'input', 'fieldname'=>'pub_date', 'dbname'=>'pub_date', 'tv'=>false),
	'unpub_date' => array('fieldtype'=>'input', 'fieldname'=>'unpub_date', 'dbname'=>'unpub_date', 'tv'=>false),
	'searchable' => array('fieldtype'=>'input', 'fieldname'=>'searchablecheck','dbname'=>'searchable',  'tv'=>false),
	'cacheable' => array('fieldtype'=>'input', 'fieldname'=>'cacheablecheck', 'dbname'=>'cacheable', 'tv'=>false),
	'clear_cache' => array('fieldtype'=>'input', 'fieldname'=>'syncsitecheck','dbname'=>'',  'tv'=>false),
	'content_type' => array('fieldtype'=>'select', 'fieldname'=>'contentType', 'dbname'=>'contentType', 'tv'=>false),
	'content_dispo' => array('fieldtype'=>'select', 'fieldname'=>'content_dispo', 'dbname'=>'content_dispo', 'tv'=>false),
	'keywords' => array('fieldtype'=>'select', 'fieldname'=>'keywords[]', 'dbname'=>'', 'tv'=>false),
	'metatags' => array('fieldtype'=>'select', 'fieldname'=>'metatags[]', 'dbname'=>'', 'tv'=>false),
	'content' => array('fieldtype'=>'textarea', 'fieldname'=>'ta', 'dbname'=>'content', 'tv'=>false),
	'which_editor' => array('fieldtype'=>'select', 'fieldname'=>'which_editor','dbname'=>'',  'tv'=>false),
	'resource_type' => array('fieldtype'=>'select', 'fieldname'=>'type', 'dbname'=>'isfolder', 'tv'=>false),
	'weblink' => array('fieldtype'=>'input', 'fieldname'=>'ta', 'dbname'=>'content', 'tv'=>false)
);				


// Add in TVs to the list of available fields
$all_tvs = $modx->db->makeArray( $modx->db->select("name,type,id", $modx->db->config['table_prefix']."site_tmplvars", '', 'name ASC')   );
foreach ($all_tvs as $thisTv) {
	
	$n = $thisTv['name']; // What is the field name?

	// Checkboxes place an underscore in the ID, so accommodate this...
	$fieldname_suffix = '';
	
	switch ($thisTv['type']) { // What fieldtype is this TV type?
		case 'textarea':
		case 'rawtextarea': 
		case 'textareamini':
		case 'richtext':
		case 'custom_tv':
			$t = 'textarea';
		break;
		
		case 'dropdown': 
		case 'listbox':
			$t = 'select';
		break;
		
		case 'listbox-multiple':  
			$t = 'select';
			$fieldname_suffix = '[]';
		break;
		
		case 'checkbox':
			$t = 'input';
			$fieldname_suffix = '[]';
		break;
		
		default:
			$t = 'input';
		break;
	}
	
	// check if there are any name clashes between TVs and default field names? If there is, preserve the default field
	if (!isset($mm_fields[ $n ])) { 
		$mm_fields[ $n ] = array('fieldtype'=>$t, 'fieldname'=>'tv'.$thisTv['id'].$fieldname_suffix, 'dbname'=>'', 'tv'=>true);
	}
	
	$mm_fields[ 'tv'.$n ] = array('fieldtype'=>$t, 'fieldname'=>'tv'.$thisTv['id'].$fieldname_suffix, 'dbname'=>'', 'tv'=>true);
}

// Get the contents of the config chunk, and put it in the "make changes" function, to be run at the appropriate moment later on
if (!function_exists("make_changes")) {
	function make_changes($chunk) { 
	
		global $modx;	// Global modx object
		$config_file = $modx->config['base_path'] . 'assets/plugins/managermanager/mm_rules.inc.php';
		
		// See if there is any chunk output (e.g. it exists, and is not empty)
		$chunk_output = $modx->getChunk($chunk);
		if (!empty($chunk_output)) {
			eval($chunk_output); // If there is, run it.
			return;
		} else if (is_readable($config_file)) {	// If there's no chunk output, read in the file.
			include($config_file);
		}
	} 
} 




// Check the current event
global $e;
$e = &$modx->Event;



// The start of adding or editing a document (before the main form)
switch ($e->name) { 


// if it's the plugin config form, give us a copy of all the relevant values

case 'OnPluginFormRender':
	
	$plugin_id_editing = $e->params['id']; // The ID of the plugin we're editing
	$result = $modx->db->select("name, id", $modx->db->config['table_prefix']."site_plugins", "id=$plugin_id_editing");
	$all_plugins = $modx->db->makeArray( $result );
	$plugin_editing_name = $all_plugins[0]['name'];

	
	// if it's the right plugin
	if (strtolower($plugin_editing_name) == 'managermanager') {
	
		// Get all templates
		$result = $modx->db->select("templatename, id, description", $modx->db->config['table_prefix']."site_templates", '', 'templatename ASC');
		$all_templates = $modx->db->makeArray( $result );
		$template_table = '<table>';
		$template_table .= '<tr><th class="gridHeader">Template name</th><th class="gridHeader">Template description</th><th class="gridHeader">ID</th></tr>';
		$template_table .= '<tr><td class="gridItem">(blank)</td><td class="gridItem">Blank</td><td class="gridItem">0</td></tr>';
		foreach ($all_templates as $count=>$tpl) {
			$class = ($count % 2) ? 'gridItem':'gridAltItem';
			$template_table .= '<tr>';
			$template_table .= '<td class="'.$class.'">'.jsSafe($tpl['templatename']).'</td>';
			$template_table .= '<td class="'.$class.'">'.jsSafe($tpl['description']).'</td>';
			$template_table .= '<td class="'.$class.'">'.$tpl['id'].'</td>';
			$template_table .= '</tr>';
		}
		$template_table .= '</table>';

		// Get all tvs
		$result = $modx->db->select("name,caption,id", $modx->db->config['table_prefix']."site_tmplvars", '', 'name ASC');
		$all_tvs = $modx->db->makeArray( $result );
		$tvs_table = '<table>';
		$tvs_table .= '<tr><th class="gridHeader">TV name</th><th class="gridHeader">TV caption</th><th class="gridHeader">ID</th></tr>';
		
		foreach ($all_tvs as $count=>$tv) {
			$class = ($count % 2) ? 'gridItem':'gridAltItem';
			$tvs_table .= '<tr>';
			$tvs_table .= '<td class="'.$class.'">'.jsSafe($tv['name']).'</td>';
			$tvs_table .= '<td class="'.$class.'">'.jsSafe($tv['caption']).'</td>';
			$tvs_table .= '<td class="'.$class.'">'.$tv['id'].'</td>';
			$tvs_table .= '</tr>';
		}
		$tvs_table .= '</table>';
		
		
		// Get all roles
		$result = $modx->db->select("name, id", $modx->db->config['table_prefix']."user_roles", '', 'name ASC');
		$all_roles = $modx->db->makeArray( $result );
		$roles_table = '<table>';
		$roles_table .= '<tr><th class="gridHeader">Role name</th><th class="gridHeader">ID</th></tr>';
		foreach ($all_roles as $count=>$role) {
			$class = ($count % 2) ? 'gridItem':'gridAltItem';
			$roles_table .= '<tr>';
			$roles_table .= '<td class="'.$class.'">'.jsSafe($role['name']).'</td>';
			$roles_table .= '<td class="'.$class.'">'.$role['id'].'</td>';
			$roles_table .= '</tr>';
		}
		$roles_table .= '</table>';
			
		
		// Load the jquery library
		$output = '<!-- Begin ManagerManager output -->' . "\n";
		$output .= includeJs($js_url, 'html');
		
		$output .= '<script type="text/javascript">' . "\n";
		$output .= "var \$j = jQuery.noConflict(); \n"; //produces var  $j = jQuery.noConflict();

		$output .= "mm_lastTab = 'tabEvents'; \n";
		$e->output($output);
		
		mm_createTab('Templates, TVs &amp; Roles', 'rolestemplates', '', '', '<p>These are the IDs for current templates,tvs and roles in your site.</p>'.$template_table.'&nbsp;'.$tvs_table.'&nbsp;'.$roles_table);		
		
		$e->output('</script>');
		$e->output('<!-- End ManagerManager output -->' . "\n");		
	} 
	break;




case 'OnDocFormPrerender':

	// Are we clashing with the ShowImageTVs (or any other) plugins?
	//$conflicted_plugins = array('ShowImageTVs');
	//$conflicts = array();
	//foreach ($conflicted_plugins as $plg) {
		
	//	$sql= "SELECT * FROM " . $this->getFullTableName("site_plugins") . " WHERE name='" . $plg . "' OR name='" . strtolower($plg) . "'AND disabled=0;";
       // $result= $modx->db->query($sql);
       //	if ($modx->db->getRecordCount($result) > 0) {
       //	$conflicts[] = $plg;
	//	}
	//}
	//if (count($conflicts) > 0) {
	//	echo '		
	//	<script type="text/javascript">
	//		alert("You appear to be running '.(count($conflicts)>1?'some plugins which are':'a plugin which is').' incompatible with ManagerManager: \n\n  '.implode('  \n  ', $conflicts).'\n\nYou may experience errors or unpredictable behaviour. \n\nPlease see the ManagerManager documentation for details of how to fix this.");
	//	<script>	
	//	';	
	//}
	

	// Load the jquery library
	echo '<!-- Begin ManagerManager output -->';
	echo includeJs($js_url, 'html');	

	// Create a mask to cover the page while the fields are being rearranged
	echo '		
		<div id="loadingmask">&nbsp;</div>
		<script type="text/javascript">
		var $j = jQuery.noConflict();

			$j("#loadingmask").css( {width: "100%", height: $j("body").height(), position: "absolute", zIndex: "1000", backgroundColor: "#ffffff"} );
		</script>	
	';
	echo '<!-- End ManagerManager output -->';
	break;
	
	

case 'OnDocFormRender':
	
	// The main document editing form
	

    // Include the JQuery call
    $e->output( '
<!-- ManagerManager Plugin :: '.$mm_version.' -->
<!-- This document is using template: '. $mm_current_page['template'] .' -->
<!-- You are logged into the following role: '. $mm_current_page['role'] .' -->
		
<script type="text/javascript" charset="'.$modx->config['modx_charset'].'">
var $j = jQuery.noConflict();
		
var mm_lastTab = "tabGeneral"; 
var mm_sync_field_count = 0;
var synch_field = new Array();

$j(document).ready(function() {
	
	// Lets handle errors nicely...
	try {
						   					
	  // Change section index depending on Content History running or not                  
      var sidx = ($j("div.sectionBody:eq(1)").attr("id") == "ch-body")?1:0;  //ch-body is the CH id name (currently at least)
      
      // Give IDs to the sections of the form
      // This assumes they appear in a certain order
      $j("div.sectionHeader:eq(sidx)").attr("id", "sectionContentHeader");
      $j("div.sectionHeader:eq(sidx+1)").attr("id", "sectionTVsHeader");
   
      $j("div.sectionBody:eq(sidx+1)").attr("id", "sectionContentBody");
      $j("div.sectionBody:eq(sidx+2)").attr("id", "sectionTVsBody");						   

	'			
			);
	
	
	// Get the JS for the changes
  	
	
	// Where would we get the config file from?
	$config_file = $modx->config['base_path'] . 'assets/plugins/managermanager/mm_rules.inc.php';
		
	// See if there is any chunk output (e.g. it exists, and is not empty)
	$chunk_output = $modx->getChunk($config_chunk);
	if (!empty($chunk_output)) {
		$e->output("// Getting rules from chunk: $config_chunk \n\n");
		eval($chunk_output); // If there is, run it.
	} else if (is_readable($config_file)) {	// If there's no chunk output, read in the file.
		$e->output("// Getting rules from file: $config_file \n\n");
		include($config_file);
	} else {
		$e->output("// No rules found \n\n");
	}
		
	
    
    // Close it off
    $e->output( '	
	
		// Misc tidying up
		
		// General tab table container is too narrow for receiving TVs -- make it a bit wider
		$j("div#tabGeneral table").attr("width", "100%");
		
		// if template variables containers are empty, remove their section
		if ($j("div.tmplvars :input").length == 0) {
			$j("div.tmplvars").hide();	// Still contains an empty table and some dividers
			$j("div.tmplvars").prev("div").hide();	// Still contains an empty table and some dividers
			//$j("#sectionTVsHeader").hide(); 
		}
		
		// If template category is empty, hide the optgroup
		$j("#template optgroup").each( function() {
			var $this = $j(this),
			visibleOptions = 0;
			$this.find("option").each( function() {
				if ($j(this).css("display") != "none") 	visibleOptions++ ;
			});
			if (visibleOptions == 0) $this.hide();	
		});
		
		// Re-initiate the tooltips, in order for them to pick up any new help text which has been added
		// This bit is MooTools, matching code inserted further up the page
		if( !window.ie6 ) {
			$$(".tooltip").each(function(help_img) {	 
				help_img.setProperty("title", help_img.getProperty("alt") );			 
			});
			new Tips($$(".tooltip"), {className:"custom"} );
		}
	
	} catch (e) {
		// If theres an error, fail nicely
		alert("ManagerManager: An error has occurred: " + e.name + " - " + e.message);	
		
	} finally {	
		
		// Whatever happens, hide the loading mask
		$j("#loadingmask").hide();
	}
});
</script>
<!-- ManagerManager Plugin :: End -->
		');
	break;





case 'OnTVFormRender':

	if ($remove_deprecated_tv_types) {

		// Load the jquery library
		echo '<!-- Begin ManagerManager output -->';
		echo includeJs($js_url, 'html');	
	
		// Create a mask to cover the page while the fields are being rearranged
		echo '		
			<script type="text/javascript">
			var $j = jQuery.noConflict();
			$j("select[name=type] option").each( function() {
												var $this = $j(this);
												if( !($this.text().match("deprecated")==null )) {
													$this.remove();	
												}
														  });
			</script>	
		';
		echo '<!-- End ManagerManager output -->';
	}

break;


case 'OnBeforeDocFormSave':
	global $template;
	
	$mm_current_page['template'] = $template;
	
	make_changes($config_chunk);
break;

} // end switch



?>
