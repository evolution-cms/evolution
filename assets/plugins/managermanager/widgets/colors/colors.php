<?php


//---------------------------------------------------------------------------------
// mm_widget_colors
// Adds a color selection widget to the specified TVs
//--------------------------------------------------------------------------------- 
function mm_widget_colors($fields, $default='#ffffff', $roles='', $templates='') {
	global $modx, $content, $mm_fields;
	$e = &$modx->Event;
	
	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)) {
		
		$output = '';
		
		// if we've been supplied with a string, convert it into an array 
		$fields = makeArray($fields);
		
		// Which template is this page using?
		if (isset($content['template'])) {
			$page_template = $content['template'];
		} else {
			// If no content is set, it's likely we're adding a new page at top level. 
			// So use the site default template. This may need some work as it might interfere with a default template set by MM?
			$page_template = $modx->config['default_template']; 
		}
		
		// Does this page's template use any of these TVs? If not, quit.
        $tv_count = tplUseTvs($page_template, $fields);
		
		if ($tv_count === false) {
			return;	
		}
	
		// Insert some JS 
		$output .= includeJs($modx->config['base_url'] .'assets/plugins/managermanager/widgets/colors/farbtastic.js');
		
		// Insert some CSS 
		$output .= includeCss($modx->config['base_url'] .'assets/plugins/managermanager/widgets/colors/farbtastic.css');
			
		// Go through each of the fields supplied
		foreach ($fields as $tv) {
				
				$tv_id = $mm_fields[$tv]['fieldname'];
				
				$output .= ' 
				// ----------- Color widget for  '.$tv_id.'  --------------
                $j("#'.$tv_id.'").css("background-image","none");
				$j("#'.$tv_id.'").after(\'<div id="colorpicker'.$tv_id.'"></div>\');
				if ($j("#'.$tv_id.'").val() == "") { 
					$j("#'.$tv_id.'").val("'.$default.'");	
				}
				$j("#colorpicker'.$tv_id.'").farbtastic("#'.$tv_id.'");
				$j("#colorpicker'.$tv_id.'").mouseup( function() { // mark the document as dirty, or the value wont be saved
														$j("#'.$tv_id.'").trigger("change");
															   });
				';
		}
		
		$e->output($output . "\n");
		
	} // end of "if use this rule"
	
}

?>
