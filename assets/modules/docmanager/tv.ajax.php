<?php
/**
 * This file includes slightly modified code from the MODx core distribution.
 */

include_once("../../cache/siteManager.php");

require_once '../../../'.MGR_DIR.'/includes/protect.inc.php';
include_once ('../../../'.MGR_DIR.'/includes/config.inc.php');
include_once (MODX_MANAGER_PATH.'includes/document.parser.class.inc.php');
include_once (MODX_BASE_PATH.'assets/modules/docmanager/classes/docmanager.class.php');
$modx = new DocumentParser;
$modx->getSettings();

$dm = new DocManager($modx);
$dm->getLang();
$dm->getTheme();
 
 $output = '';
 
 if(isset($_POST['tplID']) && is_numeric($_POST['tplID'])) {
 	$sql = "SELECT * FROM ".$modx->getFullTableName('site_tmplvars')." tv LEFT JOIN ".$modx->getFullTableName('site_tmplvar_templates')." ON tv.id = ".$modx->getFullTableName('site_tmplvar_templates').".tmplvarid WHERE ".$modx->getFullTableName('site_tmplvar_templates').".templateid ='". $_POST['tplID']."'";
 	$rs = $modx->db->query($sql);
 	$limit = $modx->db->getRecordCount($rs);
 	
 	if ($limit > 0) {
		require (MODX_MANAGER_PATH.'includes/tmplvars.commands.inc.php');
		$output.= "<table style='position:relative' border='0' cellspacing='0' cellpadding='3' width='96%'>";

		for ($i=0; $i<$limit; $i++) {
 			$row = $modx->db->getRow($rs);

				if($i>0 && $i<$limit) $output .= '<tr><td colspan="2"><div class="split"></div></td></tr>';
				
				$output.='<tr style="height: 24px;">
				<td align="left" valign="top" width="200">
					<span class=\'warning\'><input type=\'checkbox\' name=\'update_tv_' . $row['id'] . '\' id=\'cb_update_tv_' . $row['id'] . '\' value=\'yes\' />&nbsp;'.$row['caption'].'</span><br /><span class=\'comment\'>'.$row['description'].'</span>
				</td>
				<td valign="top" style="position:relative">';
				$base_url = str_replace("assets/modules/docmanager/", "", MODX_BASE_URL);
				$output.= renderFormElement($row['type'], $row['id'], $row['default_text'], $row['elements'], $row['value'], ' style="width:300px;"');
				$output.= '</td></tr>';
		}
		$output.='</table>';
		//$output.= '<br />'.$dm->lang['DM_tv_ignore_tv'].' <input type="text" id="ignoreTV" name="ignoreTV" size="50" value="" />';
 	} else {
 		print $dm->lang['DM_tv_no_tv'];
 	}
 	
 	print $output;
 } else {
 	print '';
 }
 


function renderFormElement($field_type, $field_id, $default_text, $field_elements, $field_value, $field_style='') {
	global $modx;
	global $dm;
	global $base_url;
	global $rb_base_url;

	$field_html ='';
	$field_value = ($field_value!="" ? $field_value : $default_text);

	switch ($field_type) {
		case "text": // handler for regular text boxes
		case "rawtext"; // non-htmlentity converted text boxes
		case "email": // handles email input fields
		case "number": // handles the input of numbers
			$field_html .=  '<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'" value="'.htmlspecialchars($field_value).'" '.$field_style.' tvtype="'.$field_type.'" onchange="documentDirty=true;" style="width:100%" />';
			break;
		case "textareamini": // handler for textarea mini boxes
			$field_html .=  '<textarea id="tv'.$field_id.'" name="tv'.$field_id.'" cols="40" rows="5" onchange="documentDirty=true;" style="width:100%">' . htmlspecialchars($field_value) .'</textarea>';
			break;
		case "textarea": // handler for textarea boxes
		case "rawtextarea": // non-htmlentity convertex textarea boxes
		case "htmlarea": // handler for textarea boxes (deprecated)
		case "richtext": // handler for textarea boxes
			$field_html .=  '<textarea id="tv'.$field_id.'" name="tv'.$field_id.'" cols="40" rows="15" onchange="documentDirty=true;" style="width:100%;">' . htmlspecialchars($field_value) .'</textarea>';
			break;
		case "date":
			$field_id = str_replace(array('-', '.'),'_', urldecode($field_id));	
            if($field_value=='') $field_value=0;
			$field_html .=  '<input id="tv'.$field_id.'" name="tv'.$field_id.'" class="DatePicker" type="text" value="' . ($field_value==0 || !isset($field_value) ? "" : $field_value) . '" onblur="documentDirty=true;" />';
			$field_html .=  ' <a onclick="document.forms[\'templatevariables\'].elements[\'tv'.$field_id.'\'].value=\'\';document.forms[\'templatevariables\'].elements[\'tv'.$field_id.'\'].onblur(); return true;" onmouseover="window.status=\'clear the date\'; return true;" onmouseout="window.status=\'\'; return true;" style="cursor:pointer; cursor:hand"><img src="media/style/'.(!empty($dm->theme) ? $dm->theme."/":"").'images/icons/cal_nodate.gif" width="16" height="16" border="0" alt="No date"></a>';

			$field_html .=  '<script type="text/javascript">';
			$field_html .=  '   	new DatePicker($(\'tv'.$field_id.'\'), {\'yearOffset\' : '.$modx->config['datepicker_offset']. ", 'format' : " . "'" . $modx->config['datetime_format']  . ' hh:mm:00\'' . '});';
			$field_html .=  '</script>';

			break;
		case "dropdown": // handler for select boxes
			$field_html .=  '<select id="tv'.$field_id.'" name="tv'.$field_id.'" size="1" onchange="documentDirty=true;">';
			$index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id));
			while (list($item, $itemvalue) = each ($index_list))
			{
				list($item,$itemvalue) =  (is_array($itemvalue)) ? $itemvalue : explode("==",$itemvalue);
				if (strlen($itemvalue)==0) $itemvalue = $item;
				$field_html .=  '<option value="'.htmlspecialchars($itemvalue).'"'.($itemvalue==$field_value ?' selected="selected"':'').'>'.htmlspecialchars($item).'</option>';
			}
			$field_html .=  "</select>";
			break;
		case "listbox": // handler for select boxes
			$field_html .=  '<select id="tv'.$field_id.'" name="tv'.$field_id.'" onchange="documentDirty=true;" size="8">';	
			$index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id));
			while (list($item, $itemvalue) = each ($index_list))
			{
				list($item,$itemvalue) =  (is_array($itemvalue)) ? $itemvalue : explode("==",$itemvalue);
				if (strlen($itemvalue)==0) $itemvalue = $item;
				$field_html .=  '<option value="'.htmlspecialchars($itemvalue).'"'.($itemvalue==$field_value ?' selected="selected"':'').'>'.htmlspecialchars($item).'</option>';
			}
			$field_html .=  "</select>";
			break;
		case "listbox-multiple": // handler for select boxes where you can choose multiple items
			$field_value = explode("||",$field_value);
			$field_html .=  '<select id="tv'.$field_id.'[]" name="tv'.$field_id.'[]" multiple="multiple" onchange="documentDirty=true;" size="8">';
			$index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id));
			while (list($item, $itemvalue) = each ($index_list))
			{
				list($item,$itemvalue) =  (is_array($itemvalue)) ? $itemvalue : explode("==",$itemvalue);
				if (strlen($itemvalue)==0) $itemvalue = $item;
				$field_html .=  '<option value="'.htmlspecialchars($itemvalue).'"'.(in_array($itemvalue,$field_value) ?' selected="selected"':'').'>'.htmlspecialchars($item).'</option>';
			}
			$field_html .=  "</select>";
			break;
		case "url": // handles url input fields
			$urls= array(''=>'--', 'http://'=>'http://', 'https://'=>'https://', 'ftp://'=>'ftp://', 'mailto:'=>'mailto:');
			$field_html ='<table border="0" cellspacing="0" cellpadding="0"><tr><td><select id="tv'.$field_id.'_prefix" name="tv'.$field_id.'_prefix" onchange="documentDirty=true;">';
			foreach($urls as $k => $v){
				if(strpos($field_value,$v)===false) $field_html.='<option value="'.$v.'">'.$k.'</option>';
				else{
					$field_value = str_replace($v,'',$field_value);
					$field_html.='<option value="'.$v.'" selected="selected">'.$k.'</option>';
				}
			}
			$field_html .='</select></td><td>';
			$field_html .=  '<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'" value="'.htmlspecialchars($field_value).'" width="100" '.$field_style.' onchange="documentDirty=true;" /></td></tr></table>';
			break;
		case "checkbox": // handles check boxes
			$field_value = !is_array($field_value) ? explode("||",$field_value) : $field_value;
			$index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id));
			static $i=0;
			while (list($item, $itemvalue) = each ($index_list))
			{
				list($item,$itemvalue) =  (is_array($itemvalue)) ? $itemvalue : explode("==",$itemvalue);
				if (strlen($itemvalue)==0) $itemvalue = $item;
				$field_html .=  '<input type="checkbox" value="'.htmlspecialchars($itemvalue).'" id="tv_'.$i.'" name="tv'.$field_id.'[]" '. (in_array($itemvalue,$field_value)?" checked='checked'":"").' onchange="documentDirty=true;" /><label for="tv_'.$i.'">'.$item.'</label><br />';
				$i++;
			}
			break;
		case "option": // handles radio buttons
			$index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id));
			while (list($item, $itemvalue) = each ($index_list))
			{
				list($item,$itemvalue) =  (is_array($itemvalue)) ? $itemvalue : explode("==",$itemvalue);
				if (strlen($itemvalue)==0) $itemvalue = $item;
				$field_html .=  '<input type="radio" value="'.htmlspecialchars($itemvalue).'" name="tv'.$field_id.'" '.($itemvalue==$field_value ?'checked="checked"':'').' onchange="documentDirty=true;" />'.$item.'<br />';
			}
			break;
		case "image":	// handles image fields using htmlarea image manager
			global $ResourceManagerLoaded;
			global $content,$use_editor,$which_editor;
			if (!$ResourceManagerLoaded && !(($content['richtext']==1 || $_GET['a']==4) && $use_editor==1 && $which_editor==3)){ 
				$field_html .="
				<script type=\"text/javascript\">
						var lastImageCtrl;
						var lastFileCtrl;
						function OpenServerBrowser(url, width, height ) {
							var iLeft = (screen.width  - width) / 2 ;
							var iTop  = (screen.height - height) / 2 ;

							var sOptions = 'toolbar=no,status=no,resizable=yes,dependent=yes' ;
							sOptions += ',width=' + width ;
							sOptions += ',height=' + height ;
							sOptions += ',left=' + iLeft ;
							sOptions += ',top=' + iTop ;

							var oWindow = window.open( url, 'FCKBrowseWindow', sOptions ) ;
						}			
						function BrowseServer(ctrl) {
							lastImageCtrl = ctrl;
							var w = screen.width * 0.7;
							var h = screen.height * 0.7;
							OpenServerBrowser('media/browser/mcpuk/browser.php?Type=images', w, h);
						}
						
						function BrowseFileServer(ctrl) {
							lastFileCtrl = ctrl;
							var w = screen.width * 0.7;
							var h = screen.height * 0.7;
							OpenServerBrowser('media/browser/mcpuk/browser.php?Type=files', w, h);
						}
						
						function SetUrl(url, width, height, alt){
							if(lastFileCtrl) {
								var c = document.templatevariables[lastFileCtrl];
								if(c) c.value = url;
								lastFileCtrl = '';
							} else if(lastImageCtrl) {
								var c = document.templatevariables[lastImageCtrl];
								if(c) c.value = url;
								lastImageCtrl = '';
							} else {
								return;
							}
						}
				</script>";
				$ResourceManagerLoaded  = true;					
			} 
			$field_html .='<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'"  value="'.$field_value .'" '.$field_style.' onchange="documentDirty=true;" />&nbsp;<input type="button" value="'.$dm->lang['insert'].'" onclick="BrowseServer(\'tv'.$field_id.'\')" />';
			break;
		case "file": // handles the input of file uploads
		/* Modified by Timon for use with resource browser */
			global $ResourceManagerLoaded;
			global $content,$use_editor,$which_editor;
			if (!$ResourceManagerLoaded && !(($content['richtext']==1 || $_GET['a']==4) && $use_editor==1 && $which_editor==3)){
			/* I didn't understand the meaning of the condition above, so I left it untouched ;-) */ 
				$field_html .="
				<script type=\"text/javascript\">
						var lastImageCtrl;
						var lastFileCtrl;
						function OpenServerBrowser(url, width, height ) {
							var iLeft = (screen.width  - width) / 2 ;
							var iTop  = (screen.height - height) / 2 ;

							var sOptions = 'toolbar=no,status=no,resizable=yes,dependent=yes' ;
							sOptions += ',width=' + width ;
							sOptions += ',height=' + height ;
							sOptions += ',left=' + iLeft ;
							sOptions += ',top=' + iTop ;

							var oWindow = window.open( url, 'FCKBrowseWindow', sOptions ) ;
						}
						
							function BrowseServer(ctrl) {
							lastImageCtrl = ctrl;
							var w = screen.width * 0.7;
							var h = screen.height * 0.7;
							OpenServerBrowser('media/browser/mcpuk/browser.php?Type=images', w, h);
						}
									
						function BrowseFileServer(ctrl) {
							lastFileCtrl = ctrl;
							var w = screen.width * 0.7;
							var h = screen.height * 0.7;
							OpenServerBrowser('media/browser/mcpuk/browser.php?Type=files', w, h);
						}
						
						function SetUrl(url, width, height, alt){
							if(lastFileCtrl) {
								var c = document.templatevariables[lastFileCtrl];
								if(c) c.value = url;
								lastFileCtrl = '';
							} else if(lastImageCtrl) {
								var c = document.templatevariables[lastImageCtrl];
								if(c) c.value = url;
								lastImageCtrl = '';
							} else {
								return;
							}
						}
				</script>";
				$ResourceManagerLoaded  = true;					
			} 
			$field_html .='<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'"  value="'.$field_value .'" '.$field_style.' onchange="documentDirty=true;" />&nbsp;<input type="button" value="'.$dm->lang['insert'].'" onclick="BrowseFileServer(\'tv'.$field_id.'\')" />';
            
			break;
		default: // the default handler -- for errors, mostly
			$field_html .=  '<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'" value="'.htmlspecialchars($field_value).'" '.$field_style.' onchange="documentDirty=true;" />';
	} // end switch statement
	return $field_html;
} // end renderFormElement function


function ParseIntputOptions($v) {
        global $modx;
	$a = array();
	if(is_array($v)) return $v;
	else if(is_resource($v)) {
		while ($cols = $modx->db->getRow($v)) $a[] = $cols;
	}
	else $a = explode("||", $v);
	return $a;
}
	
 
?>
