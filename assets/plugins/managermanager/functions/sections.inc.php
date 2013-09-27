<?php




//---------------------------------------------------------------------------------
// mm_renameSection
// Rename a section
//--------------------------------------------------------------------------------- 
function mm_renameSection($section, $newname, $roles='', $templates='') {

	global $modx;
	$e = &$modx->Event;
			
	// if the current page is being edited by someone in the list of roles, and uses a template in the list of templates
	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)) {
	
	$output = " // ----------- Rename section -------------- \n";
		
			switch ($section) {
			
				
				case 'content': 
					$output .= '$j("div#content_header").empty().prepend("'.jsSafe($newname).'");' . "\n";
				break;
				
				case 'tvs': 
					$output .= '
						$j("div#tv_header").empty().prepend("'.jsSafe($newname).'");	
					' ;
				break;
				
				case 'access': // These have moved to tabs in 1.0.1
					$output .= '$j("div#sectionAccessHeader").empty().prepend("'.jsSafe($newname).'");' . "\n";
				break;
				
				
			} // end switch
			$e->output($output . "\n");
	}	// end if
} // end function






//---------------------------------------------------------------------------------
// mm_hideSections
// Hides sections
//--------------------------------------------------------------------------------- 
function mm_hideSections($sections, $roles='', $templates='') {

	
	global $modx;
	$e = &$modx->Event;
	
	// if we've been supplied with a string, convert it into an array 
	$sections = makeArray($sections);
			
	// if the current page is being edited by someone in the list of roles, and uses a template in the list of templates
	if (useThisRule($roles, $templates)) {
	
	$output = " // ----------- Hide sections -------------- \n";
	
		foreach($sections as $section) {
	
			switch ($section) {
										
				case 'content': 
					$output .= '
					$j("#content_header").hide();
					$j("#content_body").hide(); 
					';	
				break;
				
				case 'tvs': 
					$output .= ' 
						$j("#tv_header").hide(); 
						$j("#tv_body").hide();
						';
				break;
				
				case 'access': // These have moved to tabs in 1.0.1
					$output .= '
					$j("#sectionAccessHeader").hide();
					$j("#sectionAccessBody").hide(); ';
				break;
				
			} // end switch
			$e->output($output . "\n");
		} // end foreach
	}	// end if
	
} // end function






?>
