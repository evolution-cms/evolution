<?php
	// DISPLAY FORM ELEMENTS
	function renderFormElement($field_type, $field_id, $default_text, $field_elements, $field_value, $field_style='', $row = array()) {
		global $modx;
		global $base_url;
		global $rb_base_url;
		global $manager_theme;
		global $_lang;
		global $content;

		$field_html ='';
		$field_value = ($field_value!="" ? $field_value : $default_text);

		switch ($field_type) {

			case "text": // handler for regular text boxes
			case "rawtext"; // non-htmlentity converted text boxes
				$field_html .=  '<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'" value="'.htmlspecialchars($field_value).'" '.$field_style.' tvtype="'.$field_type.'" onchange="documentDirty=true;" style="width:100%" />';
				break;
			case "email": // handles email input fields
				$field_html .=  '<input type="email" id="tv'.$field_id.'" name="tv'.$field_id.'" value="'.htmlspecialchars($field_value).'" '.$field_style.' tvtype="'.$field_type.'" onchange="documentDirty=true;" style="width:50%" />';
				break;
			case "number": // handles the input of numbers
				$field_html .=  '<input type="number" id="tv'.$field_id.'" name="tv'.$field_id.'" value="'.htmlspecialchars($field_value).'" '.$field_style.' tvtype="'.$field_type.'" onchange="documentDirty=true;" style="width:50%" onkeyup="this.value=this.value.replace(/[^\d-,.+]/,\'\')"/>';
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
				$field_html .=  ' <a onclick="document.forms[\'mutate\'].elements[\'tv'.$field_id.'\'].value=\'\';document.forms[\'mutate\'].elements[\'tv'.$field_id.'\'].onblur(); return true;" onmouseover="window.status=\'clear the date\'; return true;" onmouseout="window.status=\'\'; return true;" style="cursor:pointer; cursor:hand"><img src="media/style/'.($manager_theme ? "$manager_theme/":"").'images/icons/cal_nodate.gif" width="16" height="16" border="0" alt="No date"></a>';

				$field_html .=  '<script type="text/javascript">';
				$field_html .=  '	window.addEvent(\'domready\', function() {';
				$field_html .=  '   	new DatePicker($(\'tv'.$field_id.'\'), {\'yearOffset\' : '.$modx->config['datepicker_offset']. ", 'format' : " . "'" . $modx->config['datetime_format']  . ' hh:mm:00\'' . '});';
				$field_html .=  '});';
				$field_html .=  '</script>';

				break;
			case "dropdown": // handler for select boxes
				$field_html .=  '<select id="tv'.$field_id.'" name="tv'.$field_id.'" size="1" onchange="documentDirty=true;">';
				$index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id,'','tvform'));
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
				$index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id,'','tvform'));
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
				$field_html .=  '<select id="tv'.$field_id.'" name="tv'.$field_id.'[]" multiple="multiple" onchange="documentDirty=true;" size="8">';
				$index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id,'','tvform'));
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
				$index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id,'','tvform'));
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
				$index_list = ParseIntputOptions(ProcessTVCommand($field_elements, $field_id,'','tvform'));
				while (list($item, $itemvalue) = each ($index_list))
				{
					list($item,$itemvalue) =  (is_array($itemvalue)) ? $itemvalue : explode("==",$itemvalue);
					if (strlen($itemvalue)==0) $itemvalue = $item;
					$field_html .=  '<input type="radio" value="'.htmlspecialchars($itemvalue).'" name="tv'.$field_id.'" '.($itemvalue==$field_value ?'checked="checked"':'').' onchange="documentDirty=true;" />'.$item.'<br />';
				}
				break;
			case "image":	// handles image fields using htmlarea image manager
				global $_lang;
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
								var w = screen.width * 0.5;
								var h = screen.height * 0.5;
								OpenServerBrowser('".MODX_MANAGER_URL."media/browser/mcpuk/browser.php?Type=images', w, h);
							}
							
							function BrowseFileServer(ctrl) {
								lastFileCtrl = ctrl;
								var w = screen.width * 0.5;
								var h = screen.height * 0.5;
								OpenServerBrowser('".MODX_MANAGER_URL."media/browser/mcpuk/browser.php?Type=files', w, h);
							}
							
							function SetUrl(url, width, height, alt){
								if(lastFileCtrl) {
									var c = document.mutate[lastFileCtrl];
									if(c) c.value = url;
									lastFileCtrl = '';
								} else if(lastImageCtrl) {
									var c = document.mutate[lastImageCtrl];
									if(c) c.value = url;
									lastImageCtrl = '';
								} else {
									return;
								}
							}
					</script>";
					$ResourceManagerLoaded  = true;					
				} 
				$field_html .='<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'"  value="'.$field_value .'" '.$field_style.' onchange="documentDirty=true;" />&nbsp;<input type="button" value="'.$_lang['insert'].'" onclick="BrowseServer(\'tv'.$field_id.'\')" />';
				break;
			case "file": // handles the input of file uploads
			/* Modified by Timon for use with resource browser */
                global $_lang;
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
								var w = screen.width * 0.5;
								var h = screen.height * 0.5;
								OpenServerBrowser('".MODX_MANAGER_URL."media/browser/mcpuk/browser.php?Type=images', w, h);
							}
										
							function BrowseFileServer(ctrl) {
								lastFileCtrl = ctrl;
								var w = screen.width * 0.5;
								var h = screen.height * 0.5;
								OpenServerBrowser('".MODX_MANAGER_URL."media/browser/mcpuk/browser.php?Type=files', w, h);
							}
							
							function SetUrl(url, width, height, alt){
								if(lastFileCtrl) {
									var c = document.mutate[lastFileCtrl];
									if(c) c.value = url;
									lastFileCtrl = '';
								} else if(lastImageCtrl) {
									var c = document.mutate[lastImageCtrl];
									if(c) c.value = url;
									lastImageCtrl = '';
								} else {
									return;
								}
							}
					</script>";
					$ResourceManagerLoaded  = true;					
				} 
				$field_html .='<input type="text" id="tv'.$field_id.'" name="tv'.$field_id.'"  value="'.$field_value .'" '.$field_style.' onchange="documentDirty=true;" />&nbsp;<input type="button" value="'.$_lang['insert'].'" onclick="BrowseFileServer(\'tv'.$field_id.'\')" />';
                
				break;

            case 'custom_tv':
                $custom_output = '';
                /* If we are loading a file */
                if(substr($field_elements, 0, 5) == "@FILE") {
                    $file_name = MODX_BASE_PATH . trim(substr($field_elements, 6));
                    if( !file_exists($file_name) ) {
                        $custom_output = $file_name . ' does not exist';
                    } else {
                        $custom_output = file_get_contents($file_name);
                    }
                } elseif(substr($field_elements, 0, 8) == '@INCLUDE') {
                    $file_name = MODX_BASE_PATH . trim(substr($field_elements, 9));
                    if( !file_exists($file_name) ) {
                        $custom_output = $file_name . ' does not exist';
                    } else {
                        ob_start();
                        include $file_name;
                        $custom_output = ob_get_contents();
                        ob_end_clean();
                    }
                } elseif(substr($field_elements, 0, 6) == "@CHUNK") {
                    $chunk_name = trim(substr($field_elements, 7));
                    $chunk_body = $modx->getChunk($chunk_name);
                    if($chunk_body == false) {
                        $custom_output = $_lang['chunk_no_exist']
                            . '(' . $_lang['htmlsnippet_name']
                            . ':' . $chunk_name . ')';
                    } else {
                        $custom_output = $chunk_body;
                    }
                } elseif(substr($field_elements, 0, 5) == "@EVAL") {
                    $eval_str = trim(substr($field_elements, 6));
                    $custom_output = eval($eval_str);
                } else {
                    $custom_output = $field_elements;
                }
                $replacements = array(
                    '[+field_type+]'   => $field_type,
                    '[+field_id+]'     => $field_id,
                    '[+default_text+]' => $default_text,
                    '[+field_value+]'  => htmlspecialchars($field_value),
                    '[+field_style+]'  => $field_style,
                );
                $custom_output = str_replace(array_keys($replacements), $replacements, $custom_output);
                $modx->documentObject = $content;
                $custom_output = $modx->parseDocumentSource($custom_output);
                $field_html .= $custom_output;
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
			while ($cols = $modx->db->getRow($v,'num')) $a[] = $cols;
		}
		else $a = explode("||", $v);
		return $a;
	}	
?>