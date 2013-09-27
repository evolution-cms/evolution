<?php



//---------------------------------------------------------------------------------
// mm_hideTemplates
// Hide a template within the dropdown list of templates
// Based on code submitted by Metaller
//---------------------------------------------------------------------------------
function mm_hideTemplates($tplIds, $roles='', $templates='') {
        	global  $modx;
        	
        	$e = &$modx->Event;
			
			$tplIds = makeArray($tplIds);

        	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)) {
				
				$output = " // ----------- Hide templates -------------- \n";

        		foreach ($tplIds as $tpl) {
					$output .= 'if ($j("select#template").val() != '.$tpl. ') { '. "\n";
        			$output .= '$j("select#template option[value='.$tpl.']").hide();' . "\n";
					$output .= '}' . "\n";
        		}
        		$e->output($output . "\n");
        	}
} 
		
		
	
	
	
?>
