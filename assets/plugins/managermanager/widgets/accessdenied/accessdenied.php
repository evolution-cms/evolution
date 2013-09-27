<?php

//---------------------------------------------------------------------------------
// mm_widget_accessdenied
// Close access for some documents by ids.
// Icon by designmagus.com
// Originally written by Metaller
//--------------------------------------------------------------------------------- 
function mm_widget_accessdenied($ids='', $message='',  $roles='') {
	
	global $modx, $content;
	$e = &$modx->Event;
	
	if (empty($message))  $message='<span>Access denied</span>Access to current document closed for security reasons.';
	
	if ($e->name == 'OnDocFormRender' && useThisRule($roles)) {
		
		$docid = (int)$_GET[id];		

		$ids = makeArray($ids);
		
		$output = "//  -------------- accessdenied widget include ------------- \n";
		
		if (in_array($docid, $ids)) 
		{
			$output .= includeCss($modx->config['base_url'] . 'assets/plugins/managermanager/widgets/accessdenied/accessdenied.css'); 
			
			$output .= '
			$j("input, div, form[name=mutate]").remove(); // Remove all content from the page
			$j("body").prepend(\'<div id="aback"><div id="amessage">'.$message.'</div></div>\');
			$j("#aback").css({height: $j("body").height()} );';
		}
	
		$e->output($output . "\n");	// Send the output to the browser
	} // end if
	
}

?>
