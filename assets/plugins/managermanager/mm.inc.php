<?php
/**
 * ManagerManager plugin
 * @version 0.6.3 (2018-01-22)
 *
 * @for Evo 1.4.x
 *
 * @desc Used to manipulate the display of document fields in the manager.
 *
 * @link http://code.divandesign.biz/modx/managermanager/0.6.2
 *
 * @author DivanDesign studio (www.DivanDesign.biz), Nick Crossland (www.rckt.co.uk)
 *
 * @inspiration HideEditor plugin by Timon Reinhard and Gildas; HideManagerFields by Brett @ The Man Can!
 *
 * @license Released under the GNU General Public License: http://creativecommons.org/licenses/GPL/2.0/
 *
 * @copyright 2014
 */

global $mm_version;
$mm_version = '0.6.3';

// Bring in some preferences which have been set on the configuration tab of the plugin, and normalise them

// Current event
global $e;
$e = &$modx->Event;

if (!isset($e->params['config_chunk'])){$e->params['config_chunk'] = '';}

$jsUrls = array(
	'jq' => array(
		'url' => $modx->config['site_url'].'assets/plugins/managermanager/js/jquery-1.9.1.min.js',
		'name' => 'jquery',
		'version' => '1.9.1'
	),
	'mm' => array(
		'url' => $modx->config['site_url'].'assets/plugins/managermanager/js/jquery.ddMM.js',
		'name' => 'ddMM',
		'version' => '1.2.1'
	),
	'ddTools' => array(
		'url' => $modx->config['site_url'].'assets/plugins/managermanager/js/jquery.ddTools-1.8.6.min.js',
		'name' => 'jquery.ddTools',
		'version' => '1.8.6'
	)
);

$pluginDir = $modx->config['base_path'].'assets/plugins/managermanager/';

// Set variables
global $content, $template, $default_template, $mm_current_page, $mm_fields, $mm_includedJsCss;

if (!is_array($mm_includedJsCss)){
	$mm_includedJsCss = array();
}

//Include ddTools (needed for some widgets)
include_once($pluginDir.'modx.ddtools.class.php');
//Include Utilites
include_once($pluginDir.'utilities.inc.php');

// When loading widgets, ignore folders / files beginning with these chars
$ignore_first_chars = array('.', '_', '!');

// Include widgets
// We look for a PHP file with the same name as the directory - e.g.
// /widgets/widgetname/widgetname.php
$widget_dir = $pluginDir.'widgets';

if ($handle = opendir($widget_dir)){
	while (false !== ($file = readdir($handle))){
		if (!in_array(substr($file, 0, 1), $ignore_first_chars) && $file != '..' && is_dir($widget_dir.'/'.$file)){
				include_once($widget_dir.'/'.$file.'/'.$file.'.php');
		}
	}

	closedir($handle);
}

$mm_current_page = array();

//Get page template
if (isset($e->params['template'])){
	$mm_current_page['template'] = $e->params['template'];
}else if (isset($_POST['template'])){
	$mm_current_page['template'] = $_POST['template'];
}else if (isset($content['template'])){
	$mm_current_page['template'] = $content['template'];
}else if (isset($template)){
	$mm_current_page['template'] = $template;
}else{
	$mm_current_page['template'] = $default_template;
}

$mm_current_page['role'] = $_SESSION['mgrRole'];

// What are the fields we can change, and what types are they?
$mm_fields = array(
	'pagetitle' => array('fieldtype' => 'input', 'fieldname' => 'pagetitle', 'dbname' => 'pagetitle', 'tv' => false),
	'longtitle' => array('fieldtype' => 'input', 'fieldname' => 'longtitle', 'dbname' => 'longtitle', 'tv' => false),
	'description' => array('fieldtype' => 'input', 'fieldname' => 'description', 'dbname' => 'description', 'tv' => false),
	'alias' => array('fieldtype' => 'input', 'fieldname' => 'alias', 'dbname' => 'alias', 'tv' => false),
	'link_attributes' => array('fieldtype' => 'input', 'fieldname' => 'link_attributes', 'dbname' => 'link_attributes', 'tv' => false),
	'introtext' => array('fieldtype' => 'textarea', 'fieldname' => 'introtext', 'dbname' => 'introtext', 'tv' => false),
	'template' => array('fieldtype' => 'select', 'fieldname' => 'template', 'dbname' => 'template', 'tv' => false),
	'menutitle' => array('fieldtype' => 'input', 'fieldname' => 'menutitle','dbname' => 'menutitle', 'tv' => false),
	'menuindex' => array('fieldtype' => 'input', 'fieldname' => 'menuindex', 'dbname' => 'menuindex', 'tv' => false),
	'show_in_menu' => array('fieldtype' => 'input', 'fieldname' => 'hidemenucheck','dbname' => 'hidemenu', 'tv' => false),
	// synonym for show_in_menu
	'hide_menu' => array('fieldtype' => 'input', 'fieldname' => 'hidemenucheck', 'dbname' => 'hidemenu', 'tv' => false),
	'parent' => array('fieldtype' => 'input', 'fieldname' => 'parent', 'dbname' => 'parent', 'tv' => false),
	'is_folder' => array('fieldtype' => 'input', 'fieldname' => 'isfoldercheck', 'dbname' => 'isfolder', 'tv' => false),
	'alias_visible' => array('fieldtype' => 'input', 'fieldname' => 'alias_visible_check', 'dbname' => 'alias_visible', 'tv' => false),
	'is_richtext' => array('fieldtype' => 'input', 'fieldname' => 'richtextcheck','dbname' => 'richtext', 'tv' => false),
	'log' => array('fieldtype' => 'input', 'fieldname' => 'donthitcheck', 'dbname' => 'donthit', 'tv' => false),
	'published' => array('fieldtype' => 'input', 'fieldname' => 'publishedcheck','dbname' => 'published', 'tv' => false),
	'pub_date' => array('fieldtype' => 'input', 'fieldname' => 'pub_date', 'dbname' => 'pub_date', 'tv' => false),
	'unpub_date' => array('fieldtype' => 'input', 'fieldname' => 'unpub_date', 'dbname' => 'unpub_date', 'tv' => false),
	'searchable' => array('fieldtype' => 'input', 'fieldname' => 'searchablecheck','dbname' => 'searchable', 'tv' => false),
	'cacheable' => array('fieldtype' => 'input', 'fieldname' => 'cacheablecheck', 'dbname' => 'cacheable', 'tv' => false),
	'clear_cache' => array('fieldtype' => 'input', 'fieldname' => 'syncsitecheck','dbname' => '', 'tv' => false),
	'content_type' => array('fieldtype' => 'select', 'fieldname' => 'contentType', 'dbname' => 'contentType', 'tv' => false),
	'content_dispo' => array('fieldtype' => 'select', 'fieldname' => 'content_dispo', 'dbname' => 'content_dispo', 'tv' => false),
	'keywords' => array('fieldtype' => 'select', 'fieldname' => 'keywords[]', 'dbname' => '', 'tv' => false),
	'metatags' => array('fieldtype' => 'select', 'fieldname' => 'metatags[]', 'dbname' => '', 'tv' => false),
	'content' => array('fieldtype' => 'textarea', 'fieldname' => 'ta', 'dbname' => 'content', 'tv' => false),
	'which_editor' => array('fieldtype' => 'select', 'fieldname' => 'which_editor','dbname' => '', 'tv' => false),
	'resource_type' => array('fieldtype' => 'select', 'fieldname' => 'type', 'dbname' => 'isfolder', 'tv' => false),
	'weblink' => array('fieldtype' => 'input', 'fieldname' => 'ta', 'dbname' => 'content', 'tv' => false)
);

// Add in TVs to the list of available fields
$all_tvs = $modx->db->makeArray($modx->db->select('name,type,id', $modx->getFullTableName('site_tmplvars'), '', 'name ASC'));
foreach ($all_tvs as $thisTv){
	// What is the field name?
	$n = $thisTv['name'];

	// Checkboxes place an underscore in the ID, so accommodate this...
	$fieldname_suffix = '';

	// What fieldtype is this TV type?
	$thisTvI = explode(":", $thisTv['type']);
	switch ($thisTvI['0']){
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
	if (!isset($mm_fields[$n])){
		$mm_fields[$n] = array('fieldtype' => $t, 'fieldname' => 'tv'.$thisTv['id'].$fieldname_suffix, 'dbname' => '', 'tv' => true);
	}
}

// Get the contents of the config chunk, and put it in the “make_changes” function, to be run at the appropriate moment later on
if (!function_exists('make_changes')){
	function make_changes($chunk){
		//Global modx object & $content for rules
		global $modx, $content;

		$config_file = $modx->config['base_path'].'assets/plugins/managermanager/mm_rules.inc.php';

		//See if there is any chunk output (e.g. it exists, and is not empty)
		$chunk_output = $modx->getChunk($chunk);
		if (!empty($chunk_output)){
			// If there is, run it.
			eval($chunk_output);
			return "// Getting rules from chunk: $chunk \n\n";
		//If there's no chunk output, read in the file.
		}else if (is_readable($config_file)){
			include($config_file);
			return "// Getting rules from file: $config_file \n\n";
		}else{
			return "// No rules found \n\n";
		}
	}
}

if (!function_exists('initJQddManagerManager')){
	function initJQddManagerManager(){
		global $modx, $mm_fields;

		$output =
'
$j.ddMM.config.site_url = "'.$modx->config['site_url'].'";
$j.ddMM.config.datetime_format = "'.$modx->config['datetime_format'].'";
$j.ddMM.config.datepicker_offset = '.$modx->config['datepicker_offset'].';

$j.ddMM.urls.manager = "'.MODX_MANAGER_URL.'";

$j.ddMM.fields = $j.parseJSON(\''.json_encode($mm_fields).'\');
';

		return $output;
	}
}

// The start of adding or editing a document (before the main form)
switch ($e->name){
	// if it's the plugin config form, give us a copy of all the relevant values
	case 'OnPluginFormRender':
		// The ID of the plugin we're editing
		$plugin_id_editing = $e->params['id'];
		$result = $modx->db->select('name', $modx->getFullTableName('site_plugins'), 'id='.$plugin_id_editing);
		$plugin_editing_name = $modx->db->getValue($result);

		// if it's the right plugin
		if (strtolower($plugin_editing_name) == 'managermanager'){
			// Get all templates
			$result = $modx->db->select('templatename, id, description', $modx->getFullTableName('site_templates'), '', 'templatename ASC');
			$all_templates = $modx->db->makeArray($result);

			$template_table = '<table>';
			$template_table .= '<tr><th class="gridHeader">Template name</th><th class="gridHeader">Template description</th><th class="gridHeader">ID</th></tr>';
			$template_table .= '<tr><td class="gridItem">(blank)</td><td class="gridItem">Blank</td><td class="gridItem">0</td></tr>';

			foreach ($all_templates as $count => $tpl){
				$class = ($count % 2) ? 'gridItem':'gridAltItem';
				$template_table .= '<tr>';
				$template_table .= '<td class="'.$class.'">'.jsSafe($tpl['templatename']).'</td>';
				$template_table .= '<td class="'.$class.'">'.jsSafe($tpl['description']).'</td>';
				$template_table .= '<td class="'.$class.'">'.$tpl['id'].'</td>';
				$template_table .= '</tr>';
			}

			$template_table .= '</table>';

			// Get all tvs
			$result = $modx->db->select('name,caption,id', $modx->getFullTableName('site_tmplvars'), '', 'name ASC');
			$all_tvs = $modx->db->makeArray($result);
			$tvs_table = '<table>';
			$tvs_table .= '<tr><th class="gridHeader">TV name</th><th class="gridHeader">TV caption</th><th class="gridHeader">ID</th></tr>';

			foreach ($all_tvs as $count => $tv){
				$class = ($count % 2) ? 'gridItem' : 'gridAltItem';
				$tvs_table .= '<tr>';
				$tvs_table .= '<td class="'.$class.'">'.jsSafe($tv['name']).'</td>';
				$tvs_table .= '<td class="'.$class.'">'.jsSafe($tv['caption']).'</td>';
				$tvs_table .= '<td class="'.$class.'">'.$tv['id'].'</td>';
				$tvs_table .= '</tr>';
			}

			$tvs_table .= '</table>';

			// Get all roles
			$result = $modx->db->select('name, id', $modx->getFullTableName('user_roles'), '', 'name ASC');
			$all_roles = $modx->db->makeArray($result);

			$roles_table = '<table>';
			$roles_table .= '<tr><th class="gridHeader">Role name</th><th class="gridHeader">ID</th></tr>';

			foreach ($all_roles as $count => $role){
				$class = ($count % 2) ? 'gridItem' : 'gridAltItem';
				$roles_table .= '<tr>';
				$roles_table .= '<td class="'.$class.'">'.jsSafe($role['name']).'</td>';
				$roles_table .= '<td class="'.$class.'">'.$role['id'].'</td>';
				$roles_table .= '</tr>';
			}

			$roles_table .= '</table>';

			// Load the jquery library
			$output = '<!-- Begin ManagerManager output -->'."\n";
			if(!isset($modx->config['mgr_jquery_path']) || empty($modx->config['mgr_jquery_path']))
				$output .= includeJsCss($jsUrls['jq']['url'], 'html', $jsUrls['jq']['name'], $jsUrls['jq']['version']);
			$output .= includeJsCss($jsUrls['mm']['url'], 'html', $jsUrls['mm']['name'], $jsUrls['mm']['version']);

			$output .= '<script type="text/javascript">'."\n";
			//produces var $j = jQuery.noConflict();
			$output .= "var \$j = jQuery.noConflict(); \n";

			$output .= initJQddManagerManager();

			$output .= "mm_lastTab = 'tabEvents'; \n";
			$e->output($output);

			mm_createTab('Templates, TVs &amp; Roles', 'rolestemplates', '', '', '<p>These are the IDs for current templates,tvs and roles in your site.</p>'.$template_table.'&nbsp;'.$tvs_table.'&nbsp;'.$roles_table);

			$e->output('</script>');
			$e->output('<!-- End ManagerManager output -->'."\n");
		}
	break;

	case 'OnDocFormPrerender':
		$e->output("<!-- Begin ManagerManager output -->\n");
		// Load the js libraries
		if(!isset($modx->config['mgr_jquery_path']) || empty($modx->config['mgr_jquery_path']))
			$e->output(includeJsCss($jsUrls['jq']['url'], 'html', $jsUrls['jq']['name'], $jsUrls['jq']['version']));
		$e->output(includeJsCss($jsUrls['mm']['url'], 'html', $jsUrls['mm']['name'], $jsUrls['mm']['version']));
		$e->output(includeJsCss($jsUrls['ddTools']['url'], 'html', $jsUrls['ddTools']['name'], $jsUrls['ddTools']['version']));

		// Create a mask to cover the page while the fields are being rearranged
		$e->output(
'
<div id="loadingmask">&nbsp;</div>
<script type="text/javascript">
window.$j = jQuery.noConflict();
'.initJQddManagerManager().'
$j("#loadingmask").css({
	width: "100%",
	minHeight: "100%",
	position: "absolute",
	zIndex: "1000",
	backgroundColor: "#ffffff"
});
				
$j(function(){
	$j("#loadingmask").css({height: $j("body").height()});
});
</script>
');

		//Just run widgets
		make_changes($e->params['config_chunk']);

		$e->output("<!-- End ManagerManager output -->\n");
	break;

	// The main document editing form
	case 'OnDocFormRender':
		// Include the JQuery call
		$e->output('
<!-- ManagerManager Plugin :: '.$mm_version.' -->
<!-- This document is using template: '. $mm_current_page['template'] .' -->
<!-- You are logged into the following role: '. $mm_current_page['role'] .' -->

<script type="text/javascript" charset="'.$modx->config['modx_charset'].'">
	var mm_lastTab = "tabGeneral";
	var mm_sync_field_count = 0;
	var synch_field = new Array();
	
	$j(document).ready(function(){
		// Lets handle errors nicely...
		try {
			// Change section index depending on Content History running or not
			//ch-body is the CH id name (currently at least)
			var sidx = ($j("div.sectionBody:eq(1)").attr("id") == "ch-body") ? 1 : 0;
			
			// Give IDs to the sections of the form
			// This assumes they appear in a certain order
			$j("div.sectionHeader:eq(sidx)").attr("id", "sectionContentHeader");
			$j("div.sectionHeader:eq(sidx+1)").attr("id", "sectionTVsHeader");
			
			$j("div.sectionBody:eq(sidx+1)").attr("id", "sectionContentBody");
			$j("div.sectionBody:eq(sidx+2)").attr("id", "sectionTVsBody");
		');

		// Get the JS for the changes & display the status
		$e->output(make_changes($e->params['config_chunk']));

		// Close it off
		$e->output('
			// Misc tidying up
			
			// General tab table container is too narrow for receiving TVs -- make it a bit wider
			$j("div#tabGeneral table").attr("width", "100%");
			
			// if template variables containers are empty, remove their section
			if ($j("div.tmplvars :input").length == 0){
				// Still contains an empty table and some dividers
				$j("div.tmplvars").hide();
				// Still contains an empty table and some dividers
				$j("div.tmplvars").prev("div").hide();
				//$j("#sectionTVsHeader").hide();
			}
			
			// If template category is empty, hide the optgroup
			$j("#template optgroup").each(function(){
				var $this = $j(this),
					visibleOptions = 0;
					
				$this.find("option").each(function(){
					if ($j(this).css("display") != "none"){visibleOptions++;}
				});
					
				if (visibleOptions == 0){$this.remove();}
			});
			function hideTabsAfterOnload(){
				if (tpSettings.pages[tpSettings.getSelectedIndex()] ===undefined){
					//load all tabs for check first visible
					var $mm_hideTabs_allTabs = $j();
					for (var i = 0; i < tpSettings.pages.length - 1; i++){
						$mm_hideTabs_allTabs = $mm_hideTabs_allTabs.add(tpSettings.pages[i].tab);
					}
					//Activate the first visible tab
					$mm_hideTabs_allTabs.filter(":visible").eq(0).trigger("click");
				}
			}
			// DOM2
			if (typeof window.addEventListener !== "undefined") {
			  window.addEventListener("load", hideTabsAfterOnload);
			}// IE
			else if (typeof window.attachEvent !== "undefined") {
			  window.attachEvent("onload", hideTabsAfterOnload);
			}
		}catch(e){
			// If theres an error, fail nicely
			alert("ManagerManager: An error has occurred: " + e.name + " - " + e.message);
		}finally{
			// Whatever happens, hide the loading mask
			$j("#loadingmask").hide();
		}
	});
</script>
<!-- ManagerManager Plugin :: End -->
		');
	break;

	case 'OnTVFormRender':
		// Should we remove deprecated Template variable types from the TV creation list?
		$remove_deprecated_tv_types = ($e->params['remove_deprecated_tv_types_pref'] == 'yes') ? true : false;

		if ($remove_deprecated_tv_types){
			// Load the jquery library
			echo '<!-- Begin ManagerManager output -->';
			if(!isset($modx->config['mgr_jquery_path']) || empty($modx->config['mgr_jquery_path']))
				echo includeJsCss($jsUrls['jq']['url'], 'html', $jsUrls['jq']['name'], $jsUrls['jq']['version']);

			// Create a mask to cover the page while the fields are being rearranged
			echo '
<script type="text/javascript">
	var $j = jQuery.noConflict();
	
	$j("select[name=type] option").each(function(){
		var $this = $j(this);
		if(!($this.text().match("deprecated") == null)){
			$this.remove();
		}
	});
</script>
			';
			echo '<!-- End ManagerManager output -->';
		}
	break;

	case 'OnDocDuplicate':
		//Get document template from db
		$mm_current_page['template'] = $modx->db->getValue($modx->db->select('template', ddTools::$tables['site_content'], '`id` = '.$e->params['new_id']));

		//Just run widgets
		make_changes($e->params['config_chunk']);
	break;

	case 'OnDocFormSave':
	case 'OnBeforeDocFormSave':
		//Just run widgets
		make_changes($e->params['config_chunk']);
	break;
}
?>
