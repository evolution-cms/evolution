<?php


//---------------------------------------------------------------------------------
// mmdefault
// Sets a default value for a field when creating a new document
//---------------------------------------------------------------------------------
function mm_default($field, $value='', $roles='', $templates='', $eval=false) {	
	
	
	global $mm_fields, $modx;
	$e = &$modx->Event;
		
	// if we aren't creating a new document or folder, we don't want to do this
	// Which action IDs so we want to do this for?
	// 85 =
	// 4  =
	// 72 = Create new weblink
	
	$allowed_actions = array('85','4','72');
	if (!in_array($modx->manager->action, $allowed_actions)) {
		return;
	} 
	
	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)) {
		
		// What's the new value, and does it include PHP?
		$new_value = ($eval) ? eval($value) : $value;
		
		$output = " // ----------- Change defaults -------------- \n";
		
		
		// Work out the correct date time format based on the config setting
		switch($modx->config['datetime_format']) {
			case 'dd-mm-YYYY':
				$date_format = 'd-m-Y';
			break;	
			case 'mm/dd/YYYY':
				$date_format = 'm-d-Y';
			break;
			case 'YYYY/mm/dd':
				$date_format = 'Y-m-d';
			break;
		}
		
		
		switch ($field) {
			case 'pub_date':
				$new_value = ($new_value=='') ? date("$date_format H:i:s") : $new_value;
				$output .= '$j("input[name=pub_date]").val("'.jsSafe($new_value).'"); '."\n";
			break;
			
			case 'unpub_date':
				$new_value = ($new_value=='') ? date("$date_format H:i:s") : $new_value;
				$output .= '$j("input[name=unpub_date]").val("'.jsSafe($new_value).'"); '."\n";
			break;
			
			case 'published':
				$new_value = ($value)?'1':'0';
				$output .= '$j("input[name=published]").val("'.$new_value.'"); '."\n";
				if ($value) {
					$output .= '$j("input[name=publishedcheck]").attr("checked", "checked"); '."\n";
				} else {
					$output .= '$j("input[name=publishedcheck]").removeAttr("checked"); '."\n";
				}
			break;
			
			case 'hide_menu':
				$new_value = ($value)?'1':'0';
				$output .= '$j("input[name=hidemenu]").val("'.$new_value.'"); '."\n";
				if (!$value) {
					$output .= '$j("input[name=hidemenucheck]").attr("checked", "checked"); '."\n";
				} else {
					$output .= '$j("input[name=hidemenucheck]").removeAttr("checked"); '."\n";
				}					
			break;
			
			case 'show_in_menu':
				$new_value = ($value)?'0':'1'; // Note these are reversed from what you'd think
				$output .= '$j("input[name=hidemenu]").val("'.$new_value.'"); '."\n";
				if ($value) {
					$output .= '$j("input[name=hidemenucheck]").attr("checked", "checked"); '."\n";
				} else {
					$output .= '$j("input[name=hidemenucheck]").removeAttr("checked"); '."\n";
				}
			break;
			
			case 'searchable':
				$new_value = ($value)?'1':'0';
				$output .= '$j("input[name=searchable]").val("'.$new_value.'"); '."\n";
				if ($value) {
					$output .= '$j("input[name=searchablecheck]").attr("checked", "checked"); '."\n";
				} else {
					$output .= '$j("input[name=searchablecheck]").removeAttr("checked"); '."\n";
				}
			break;
			
			case 'cacheable':
				$new_value = ($value)?'1':'0';
				$output .= '$j("input[name=cacheable]").val("'.$new_value.'"); '."\n";
				if ($value) {
					$output .= '$j("input[name=cacheablecheck]").attr("checked", "checked"); '."\n";
				} else {
					$output .= '$j("input[name=cacheablecheck]").removeAttr("checked"); '."\n";
				}
			break;
			
			case 'clear_cache':
				$new_value = ($value)?'1':'0';
				$output .= '$j("input[name=syncsite]").val("'.$new_value.'"); '."\n";
				if ($value) {
					$output .= '$j("input[name=syncsitecheck]").attr("checked", "checked"); '."\n";
				} else {
					$output .= '$j("input[name=syncsitecheck]").removeAttr("checked"); '."\n";
				}
			break;
			
			case 'container':
			case 'is_folder':
				$new_value = ($value)?'1':'0';
				$output .= '$j("input[name=isfolder]").val("'.$new_value.'"); '."\n";
				if ($value) {
					$output .= '$j("input[name=isfoldercheck]").attr("checked", "checked"); '."\n";
				} else {
					$output .= '$j("input[name=isfoldercheck]").removeAttr("checked"); '."\n";
				}
			break;
			
			case 'is_richtext':
			case 'richtext':
				$new_value = ($value)?'1':'0';
				$output .= 'var originalRichtextValue = $j("#which_editor:first").val(); '."\n";
				$output .= '$j("input[name=richtext]").val("'.$new_value.'"); '."\n";
				if ($value) {
					$output .= '$j("input[name=richtextcheck]").attr("checked", "checked"); '."\n";
				} else {
					$output .= '
					$j("input[name=richtextcheck]").removeAttr("checked");
					// Make the RTE displayed match the default value that has been set here
					if (originalRichtextValue != "none") {
						$j("#which_editor").val("none");
						changeRTE();
					}				
					
					';
					$output .= ''."\n";
					
				}
			break;			
			
			
			case 'log':
				$new_value = ($value)?'0':'1';	// Note these are reversed from what you'd think
				$output .= '$j("input[name=donthit]").val("'.$new_value.'"); '."\n";
				if ($value) {
					$output .= '$j("input[name=donthitcheck]").attr("checked", "checked"); '."\n";
				} else {
					$output .= '$j("input[name=donthitcheck]").removeAttr("checked"); '."\n";
				}
			break;
			
			
			case 'content_type':
				$output .= '$j("select[name=contentType]").val("'.$new_value.'");' . "\n";			
			break;
			
			
			
			default:
				$output .= '$j("*[name='.$field.']").val("'.$new_value.'");' . "\n"; //return;
			break;
		}	
		$e->output($output . "\n");	
	
	} 
	
}



//---------------------------------------------------------------------------------
// mm_inherit
// Inherit values from a parent
//---------------------------------------------------------------------------------
function mm_inherit($fields, $roles='', $templates='') {

	global $mm_fields, $modx;
	$e = &$modx->Event;
	
	// if we've been supplied with a string, convert it into an array 
	$fields = makeArray($fields);
	
	// if we aren't creating a new document or folder, we don't want to do this
	if (!($modx->manager->action == "85" || $modx->manager->action == "4")) {
		return;
	} 
	
	// Are we using this rule?
	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)) {
		
		// Get the parent info
		if (isset($_REQUEST['pid'])){
			$parentID = $modx->getPageInfo($_REQUEST['pid'],0,'id');
			$parentID = $parentID['id'];
		} else {
			$parentID = 0;
		}
	
		$output = " // ----------- Inherit (from page $parentID)-------------- \n";
	
		
		
		foreach ($fields as $field) {
			
			// get some info about the field we are being asked to use
			if (isset($mm_fields[$field]['dbname'])) {
						$fieldtype = $mm_fields[$field]['fieldtype'];
						$fieldname = $mm_fields[$field]['fieldname'];
						$dbname = $mm_fields[$field]['dbname'];
						if(!empty($mm_fields[$field]['tv'])) $dbname = $field;
						
						// Get this field data from the parent
						$newArray = !empty($parentID) ? $modx->getTemplateVarOutput($dbname, $parentID) : false;
						$newvalue = !empty($parentID) ? $newArray[$dbname] : '';
			} else {
				break;	 // If it's not something stored in the database, don't get the value
			}
			
			$output .= "// fieldtype $fieldtype		
			// fieldname $fieldname			
			// dbname $dbname			
			// newvalue $newvalue 	
				";
 						 
			switch ($field) {
				
				case 'log':
				case 'hide_menu':
				case 'show_in_menu':
					$output .=  '$j("input[name='.$fieldname.']").attr("checked", "'.($newvalue?'':'checked').'"); ';
				break;	
				
				case 'is_folder':
				case 'is_richtext':
				case 'searchable':
				case 'cacheable':
				case 'published':			
					$output .=  '$j("input[name='.$fieldname.']").attr("checked", "'.($newvalue?'checked':'').'"); ';
				break;	
				
				case 'pub_date':
				case 'unpub_date':
					$output .=  '$j("input[name='.$fieldname.']").val("'.date('d-m-Y H:i:s', $newvalue).'"); ';
				break;					
						
				default:
					
					switch ($fieldtype) {
						case 'textarea':
							$output .=  '$j("textarea[name='.$fieldname.']").html("' . jsSafe($newvalue) . '"); ';
						break;
						
						default: 
							if(!empty($newvalue)) $output .=  '$j("'.$fieldtype.'[name='.$fieldname.']").val("' . jsSafe($newvalue) . '"); ';
						break;	
					}
				break;	
			}	
			
		}
		
		$e->output($output . "\n");		
	}
}






//---------------------------------------------------------------------------------
// mm_synch_fields
// Synch two fields in real time
//--------------------------------------------------------------------------------- 
function mm_synch_fields($fields, $roles='', $templates='') {

	global $modx, $mm_fields;
	$e = &$modx->Event;
	
	// if we've been supplied with a string, convert it into an array 
	$fields = makeArray($fields);
	
	// We need at least 2 values
	if (count($fields)<2) {
		return;
	}
		
	// if the current page is being edited by someone in the list of roles, and uses a template in the list of templates
	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)) {
	
	$output = " // ----------- Synch fields -------------- \n";
	
			$output .= '
				synch_field[mm_sync_field_count] = new Array();
			';
	
		foreach ($fields as $field) {
		
			if (isset($mm_fields[$field])) { 	
				$fieldtype = $mm_fields[$field]['fieldtype'];
				$fieldname = $mm_fields[$field]['fieldname'];
				
				$valid_fieldtypes = array('input', 'textarea');
				
				// Make sure we're dealing with an input
				if (!in_array($fieldtype, $valid_fieldtypes)) {
					break;
				}
				
				// Add this field to the array of fields being synched
				$output .= '
					synch_field[mm_sync_field_count].push($j("'.$fieldtype.'[name='.$fieldname.']"));
				';
			
			// Or we don't recognise it
			} else {break;}	

			

		} // end foreach
		
		// Output some javascript to sync these fields
		$output .= '
			$j.each(synch_field[mm_sync_field_count], function(i,n) {
				$j.each(synch_field[mm_sync_field_count], function(j,m) {
					if (i!=j) {
						n.keyup( function() { 
							m.val($j(this).val());
						 } );
					}
				});
			});
			
			mm_sync_field_count++;
		';
		
		$e->output($output . "\n");
		
	}	// end if
}	// end function




?>
