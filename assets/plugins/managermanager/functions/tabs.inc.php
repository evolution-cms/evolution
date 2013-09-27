<?php



//---------------------------------------------------------------------------------
// mm_renameTab
// Rename a tab
//--------------------------------------------------------------------------------- 
function mm_renameTab($tab, $newname, $roles='', $templates='') {

	global $modx;
	$e = &$modx->Event;
			
	// if the current page is being edited by someone in the list of roles, and uses a template in the list of templates
	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)){
		
		
		$output = " // ----------- Rename tab -------------- \n";
		
			switch ($tab) {
			
				case 'general': 
					$output .= '$j("div#documentPane h2:nth-child(1) span").empty().prepend("'.jsSafe($newname).'");' . "\n";
				break;
				
				case 'settings': 
					$output .= '$j("div#documentPane h2:nth-child(2) span").empty().prepend("'.jsSafe($newname).'");' . "\n";
				break;
				
				// This is =<1.0.0 only
				case 'meta': 
					if ($modx->hasPermission('edit_doc_metatags')  && $modx->config['show_meta'] != "0") {
						$output .= '$j("div#documentPane h2:nth-child(3) span").empty().prepend("'.jsSafe($newname).'");' . "\n";
					}
				break;
				
				// This is 1.0.1 specific
				case 'access': 
					$access_index = ($modx->config['show_meta'] == "0") ? 3 : 4;
					$output .= '$j("div#documentPane h2:nth-child('.$access_index .') span").empty().prepend("'.jsSafe($newname).'");' . "\n";
				break;
				

			} // end switch
			$e->output($output . "\n");
	}	// end if
} // end function




//---------------------------------------------------------------------------------
// mm_hideTabs
// Hide a tab
//---------------------------------------------------------------------------------
function mm_hideTabs($tabs, $roles='', $templates='') {

	global $modx;
	$e = &$modx->Event;
	
	// if we've been supplied with a string, convert it into an array 
	$tabs = makeArray($tabs);
			
	// if the current page is being edited by someone in the list of roles, and uses a template in the list of templates
	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)){

	$output = " // ----------- Hide tabs -------------- \n";
	
	
		foreach($tabs as $tab) {
	
			switch ($tab) {
			
				case 'general': 
					$output .= 'if (tpSettings.getSelectedIndex() == 0) { tpSettings.setSelectedIndex(1); } ' . "\n"; // if we are hiding the currently active tab, make another visible
					$output .= '$j("div#documentPane h2:nth-child(1)").hide(); ' . "\n";
					$output .= '$j("#tabGeneral").hide();';
				break;
				
				case 'settings': 
					$output .= 'if (tpSettings.getSelectedIndex() == 1) { tpSettings.setSelectedIndex(0); } ' . "\n";
					$output .= '$j("div#documentPane h2:nth-child(2)").hide(); ' . "\n";
					$output .= '$j("#tabSettings").hide();';
				break;
				
				// =< v1.0.0 only
				case 'meta': 
					if($modx->hasPermission('edit_doc_metatags') && $modx->config['show_meta'] != "0") {
						$output .= 'if (tpSettings.getSelectedIndex() == 2) { tpSettings.setSelectedIndex(0); } ' . "\n";
						$output .= '$j("div#documentPane h2:nth-child(3)").hide(); ' . "\n";
						$output .= '$j("#tabMeta").hide(); ';
					}
				break;
				
				// Meta tags tab is removed by default in version 1.0.1+ but can still be enabled via a preference.
				// Access tab was only added in 1.0.1
				// Since counting the tabs is the only way of determining this, we need to know if this has been activated
				// If meta tabs are active, the "access" tab is index 4 in the HTML; otherwise index 3. 
				// If config['show_meta'] is NULL, this is a version before this option existed, e.g. < 1.0.1
				// For versions => 1.0.1, 0 is the default value to not show them, 1 is the option to show them.
				case 'access': 
					$access_index = ($modx->config['show_meta'] == "0") ? 3 : 4;
					$output .= 'if (tpSettings.getSelectedIndex() == '.($access_index-1).') { tpSettings.setSelectedIndex(0); } ' . "\n";
					$output .= '$j("div#documentPane h2:nth-child('.$access_index.')").hide(); ' . "\n";
					$output .= '$j("#tabAccess").hide();';
				break;
			} // end switch
			$e->output($output . "\n");
		} // end foreach
	}	// end if
} // end function










//---------------------------------------------------------------------------------
// mm_createTab
// Create a new tab
//--------------------------------------------------------------------------------- 
function mm_createTab($name, $id, $roles='', $templates='', $intro='', $width='680') {

	global $modx;
	$e = &$modx->Event;
			
	// if the current page is being edited by someone in the list of roles, and uses a template in the list of templates
	if ((($e->name == 'OnDocFormRender') || ($e->name == 'OnPluginFormRender')) && useThisRule($roles, $templates)){
	
		// Plugin page tabs use a differen name for the tab object
		$js_tab_object = ($e->name == 'OnPluginFormRender') ? 'tpSnippet' : 'tpSettings';

	
	$output = " // ----------- Create tab -------------- \n";

		$empty_tab = '		
<div class="tab-page" id="tab'.$id.'">
	<h2 class="tab">'.$name.'</h2>
	<div class="tabIntro" id="tab-intro-'.$id.'">'.$intro.'</div>
	<table width="'.$width.'" border="0" cellspacing="0" cellpadding="0" id="table-'.$id.'">
	</table>
</div>
		';
		
		// Clean up for js output
		$empty_tab = str_replace( array("\n", "\t", "\r") , '', $empty_tab);
		$output .='$j';
		$output .= "('div#'+mm_lastTab).after('".$empty_tab."'); \n";
		$output .= "mm_lastTab = 'tab".$id."'; \n";
		$output .= $js_tab_object. '.addTabPage( document.getElementById( "tab'.$id.'" ) ); ';
		
		$e->output($output . "\n");

	}	// end if
} // end function





?>