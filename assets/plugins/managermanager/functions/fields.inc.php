<?php





//---------------------------------------------------------------------------------
// mm_renameField
// Change the label for an element
//---------------------------------------------------------------------------------
function mm_renameField($field, $newlabel, $roles='', $templates='', $newhelp='') {

	global $mm_fields, $modx;
	$e = &$modx->Event;
		
	// if the current page is being edited by someone in the list of roles, and uses a template in the list of templates
	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)) {
	
	$output = " // ----------- Rename field -------------- \n";
		
			switch ($field) {
			
				// Exceptions
				case 'keywords':
					$output .= '$j("select[name*=keywords]").siblings("span.warning").empty().prepend("'.jsSafe($newlabel).'");';
				break;
				
				case 'metatags':
					$output .= '$j("select[name*=metatags]").siblings("span.warning").empty().prepend("'.jsSafe($newlabel).'");';
				break;
						
				case 'hidemenu':
				case 'show_in_menu':
					$output .= '$j("input[name=hidemenucheck]").siblings("span.warning").empty().prepend("'.jsSafe($newlabel).'");';
				break;
				
				case 'which_editor':
					$output .= '$j("#which_editor").prev("span.warning").empty().prepend("'.jsSafe($newlabel).'");';
				break;
							
				// Ones that follow the regular pattern
				default:

					if (isset($mm_fields[$field])) {
						$fieldtype = $mm_fields[$field]['fieldtype'];
						$fieldname = $mm_fields[$field]['fieldname'];                    
						$output .= '$j("'.$fieldtype.'[name='.$fieldname.']").parents("td").prev("td").children("span.warning").empty().prepend("'.jsSafe($newlabel).'");';
					} 
				
				break;
			}	// end switch
			
			$e->output($output . "\n");
			
			// If new help has been supplied, do that too
			if ($newhelp != '') {
				mm_changeFieldHelp($field, $newhelp, $roles, $templates);
			}
			
	} // end if
} // end function






//---------------------------------------------------------------------------------
// mm_hideFields
// Hide a field
//---------------------------------------------------------------------------------
function mm_hideFields($fields, $roles='', $templates='') {

	global $mm_fields, $modx;
	$e = &$modx->Event;	
		
	// if we've been supplied with a string, convert it into an array 
	$fields = makeArray($fields);
		
	// if the current page is being edited by someone in the list of roles, and uses a template in the list of templates
	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)) {
	
	$e->output(" // ----------- Hide fields -------------- \n");
	
		foreach ($fields as $field) {
		
			$output = '';
			
			switch ($field) {	
			
				// Exceptions
				case 'keywords':
					$output .= '$j("select[name*=keywords]").parent("td").hide();';
				break;
				
				case 'metatags':
					$output .= '$j("select[name*=metatags]").parent("td").hide()';
				break;
						
				case 'hidemenu':
				case 'show_in_menu':
					$output .= '$j("input[name=hidemenucheck]").parent("td").hide();';
				break;
						
				case 'menuindex':
					$output .= '$j("input[name=menuindex]").parents("table").parent("td").prev("td").children("span.warning").hide();' ."\n";
					$output .= '$j("input[name=menuindex]").parent("td").hide();';
				break;
				
				case 'which_editor':
					$output .= '$j("select#which_editor").prev("span.warning").hide();' . "\n";
					$output .= '$j("select#which_editor").hide();';
				break;
				
				case 'content':
					$output .= '$j("#sectionContentHeader, #sectionContentBody").hide();'; // For 1.0.0
					$output .= '$j("#ta").parent("div").parent("div").hide().prev("div").hide();'."\n"; // For 1.0.1
				break;
				
				case 'pub_date':
					$output .= '$j("input[name=pub_date]").parents("tr").next("tr").hide(); '."\n";
					$output .= '$j("input[name=pub_date]").parents("tr").hide(); ';
				break;
				
				case 'unpub_date':
					$output .= '$j("input[name=unpub_date]").parents("tr").next("tr").hide(); '."\n";
					$output .= '$j("input[name=unpub_date]").parents("tr").hide(); ';
				break;			
			
				// Ones that follow the regular pattern
				default:				
					if (isset($mm_fields[$field]))  { // Check the fields exist,  so we're not writing JS for elements that don't exist
						$output .= '$j("'.$mm_fields[$field]['fieldtype'].'[name='.$mm_fields[$field]['fieldname'].']").parents("tr").hide().next("tr").find("td[colspan=2]").parent("tr").hide(); ';
					} 				
				break;
			} // end switch
			$e->output($output . "\n");
		} // end foreach	
	} // end if
} // end function






//---------------------------------------------------------------------------------
// mm_changeFieldHelp
// Change the help text of a field
//---------------------------------------------------------------------------------
function mm_changeFieldHelp($field, $helptext='', $roles='', $templates='') {

	global $mm_fields, $modx;
	$e = &$modx->Event;	
		
	if ($helptext=='') {
		return;
	}
	
	// if the current page is being edited by someone in the list of roles, and uses a template in the list of templates
	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)) {
	
	$output = " // ----------- Change field help -------------- \n";
	
			switch ($field) {
			
	
				
				// Ones that follow the regular pattern
				default:
					// What type is this field?
					if (isset($mm_fields[$field])) {
						$fieldtype = $mm_fields[$field]['fieldtype'];
						$fieldname = $mm_fields[$field]['fieldname'];
						
						// Give the help button an ID, and modify the alt/title text
						$output .= '$j("'.$fieldtype.'[name='.$fieldname.']").siblings("img[style*=\'help\']").attr("id", "'.$fieldname.'-help").attr("alt", "'.jsSafe($helptext).'").attr("title", "'.jsSafe($helptext).'"); ';									
					} else {
						break;
					}
				
				
				
				break;
			} // end switch
						
			$e->output($output . "\n");
	} // end if
} // end function










//---------------------------------------------------------------------------------
// mm_moveFieldsToTab
// Move a field to a different tab
//--------------------------------------------------------------------------------- 
function mm_moveFieldsToTab($fields, $newtab, $roles='', $templates='') {

	global $modx, $mm_fields;
	$e = &$modx->Event;
	
	// if we've been supplied with a string, convert it into an array 
	$fields = makeArray($fields);
			
	// if the current page is being edited by someone in the list of roles, and uses a template in the list of templates
	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)) {
	
	$output = " // ----------- Move field to tab -------------- \n";
	
		// If it's one of the default tabs, we need to get the capitalisation right
		switch ($newtab) {
			case 'general':
			case 'settings':
			case 'access':
			case 'meta': // version 1.0.0 only, removed in 1.0.1
				$newtab = ucfirst($newtab);
			break;
		}
		
		// Make sure the new tab exists in the DOM
		$output .= "if ( \$j('#tab".$newtab."').length > 0) { \n";
		$output .= 'var ruleHtml = \'<tr style="height: 10px"><td colspan="2"><div class="split"></div></td></tr>\'; ';
		
		// Try and identify any URL type TVs
		$output .= '$j("select[id$=_prefix]").each( function() { $j(this).parents("tr:first").addClass("urltv"); }  ); ';
			
		// Go through each field that has been supplied
		foreach ($fields as $field) {

			switch ($field) {
				
				case 'content':
                    $output .= '$j("#content_body").appendTo("#tab'.$newtab.'");'. "\n";
                    $output .= '$j("#content_header").hide();' . "\n";
				break;	
			
				// We can't move these fields because they belong in a particular place
				case 'keywords':
				case 'metatags':
				case 'which_editor':
				case 'hidemenu':
				case 'show_in_menu':
				case 'menuindex':
					// Do nothing
				break;
				
				case 'pub_date':
					$output .= 'var helpline = $j("input[name=pub_date]").parents("tr").next("tr").appendTo("#tab'.$newtab.'>table:first"); ' . "\n";
					$output .= '$j(helpline).before($j("input[name=pub_date]").parents("tr")); ' . "\n";
					$output .= 'helpline.after(ruleHtml); '. "\n";
				break;

				case 'unpub_date':
					$output .= 'var helpline = $j("input[name=unpub_date]").parents("tr").next("tr").appendTo("#tab'.$newtab.'>table:first"); ' . "\n";
					$output .= '$j(helpline).before($j("input[name=unpub_date]").parents("tr")); ' . "\n";
					$output .= 'helpline.after(ruleHtml); '. "\n";
				break;
			
				
				default:
				
					// What type is this field?
					if (isset($mm_fields[$field])) {
						$fieldname = $mm_fields[$field]['fieldname'];
						$output .= '
						var toMove = $j(":input[name=\''.$fieldname.'\']").parents("tr:not(.urltv)"); // Identify the table row to move
						toMove.find("script").remove();
						toMove.next("tr").find("td[colspan=2]").parents("tr").remove(); // Get rid of line after, if there is one
						var movedTV = toMove.appendTo("#tab'.$newtab.'>table:first"); // Move the table row
						movedTV.after(ruleHtml); // Insert a rule after 
						$j("[name=\''.$fieldname.'\']:first").parents("td").removeAttr( "style" );  // This prevents an IE6/7 bug where the moved field would not be visible until you switched tabs
						';
					}	
								
						
				break;
			
			} // end switch	
		} // end foreach
		
		$output .= "}";
		$e->output($output . "\n");
		
	}	// end if
} // end function








//---------------------------------------------------------------------------------
// mm_requireFields
// Make fields required. Currently works with text fields only. 
// In the future perhaps this could deal with other elements.
// Originally version by Jelle Jager AKA TobyL - Make fields required
// Updated by ncrossland to utilise simpler field handline of MM 0.3.5+; bring jQuery code into line; add indication to required fields
//---------------------------------------------------------------------------------
function mm_requireFields($fields, $roles='', $templates=''){

	global $mm_fields, $modx;
	$e = &$modx->Event;

	// if we've been supplied with a string, convert it into an array
	$fields = makeArray($fields);

	// if the current page is being edited by someone in the list of roles, and uses a template in the list of templates
	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)) {

		$output = " // ----------- Require field -------------- \n";
		$output .= '		
		$j("head").append("<style>.mmRequired { background-image: none !important; background-color: #ff9999 !important; } .requiredIcon { color: #ff0000; font-weight: bold; margin-left: 3px; cursor: help; }</style>");
		var requiredHTML = "<span class=\"requiredIcon\" title=\"Required\">*</span>";
		';
		
		$submit_js = '';
		$load_js = '';

		foreach ($fields as $field) {
			
			//ignore for now
			switch ($field) {

				// fields for which this doesn't make sense - in my opinion anyway :)
				case 'keywords':
				case 'metatags':
				case 'hidemenu':
				case 'which_editor':
				case 'template':
				case 'menuindex':
				case 'show_in_menu':
				case 'parent':
				case 'is_folder':
				case 'is_richtext':
				case 'log':
				case 'searchable':
				case 'cacheable':
				case 'clear_cache':
				case 'content_type':
				case 'content_dispo':
				case 'which_editor':
					$output .='';
				break;
				
				// Pub/unpub dates don't have a type attribute on their input tag in 1.0.2, so add this. Won't do any harm to other versions
				case 'pub_date':
				case 'unpub_date':
				
					$load_js .= '
					$j("#pub_date, #unpub_date").each(function() { this.type = "text";  }); // Cant use jQuery attr function as datepicker class clashes with jQuery methods
					 ';
					
				
				// no break, because we want to do the things below too.

				// Ones that follow the regular pattern
				default:
						
					// What type is this field?		
					$fieldname = $mm_fields[$field]['fieldname'];
					
					// What jQuery selector should we use for this fieldtype?
					switch ($mm_fields[$field]['fieldtype']) {
						case 'textarea':
							$selector = "textarea[name=$fieldname]";
						break;
						case 'input': // If it's an input, we only want to do something if it's a text field
							$selector = "input[type=text][name=$fieldname]";
						break;	
						default:  // all other input types, do nothing
							$selector = '';
						break;	
					}
					
					// If we've found something we want to use
					if (!empty($selector)) {
					
						$submit_js .= '
						
						// The element we are targetting ('.$fieldname.')
						var $sel = $j("'.$selector.'");
						
						// Check if its valid
						if($j.trim($sel.val()) == ""){  // If it is empty
						
							// Find the label (this will be easier in Evo 1.1 with more semantic code)
							var lbl = $sel.parent("td").prev("td").children("span.warning").text().replace($j(requiredHTML).text(), "");
													
							// Add the label to the errors array. Would be nice to say which tab it is on, but no
							// easy way of doing this in 1.0.x as no semantic link between tabs and tab body
							errors.push(lbl);
							
							// Add an event so the hilight is removed upon focussing							
							$sel.addClass("mmRequired").focus(function(){
								$j(this).removeClass("mmRequired");
							});
						}
						';	
						
						
						$load_js .= '
						
						// Add an indicator this is required ('.$fieldname.')
						var $sel = $j("'.$selector.'");
						
						// Find the label (this will be easier in Evo 1.1 with more semantic code)
						var $lbl = $sel.parent("td").prev("td").children("span.warning").append(requiredHTML);
						
						';
					}
					
				break;
			}

		}



		$output .= $load_js . '
		
		$j("#mutate").submit(function(){ 
			
			var errors = []; 
			var msg = "";
			
			'.$submit_js.'
		
			if(errors.length > 0){ 
			
				var errMsg = errors.length + " required fields are missing:\n\n ";
				for (var i=0; i<errors.length; i++) {
					errMsg += " - " + errors[i] + " \n";
				}
				errMsg += " \nPlease correct the indicated fields.";
				
				alert(errMsg); 
				return false; 
			} else { 
				return true; 
			} 
		});
		';

		$e->output($output . "\n");

	} // end if

} // end function






?>