<?php
/**
 * mm_ddGMap
 * @version 1.2b (2014-05-14)
 * 
 * @desc Widget for ManagerManager plugin allowing Google Maps integration.
 * 
 * @uses ManagerManager plugin 0.6.1.
 * 
 * @param $tvs {comma separated string} - TV names to which the widget is applied. @required
 * @param $roles {comma separated string} - The roles that the widget is applied to (when this parameter is empty then widget is applied to the all roles). Default: ''.
 * @param $templates {comma separated string} - Id of the templates to which this widget is applied (when this parameter is empty then widget is applied to the all templates). Default: ''.
 * @param $w {'auto'; integer} - Width of the map container. Default: 'auto'.
 * @param $h {integer} - Height of the map container. Default: 400.
 * @param $hideField {0; 1} - Original coordinates field hiding status (1 — hide, 0 — show). Default: 1.
 * @param $apiKey  (string) Api key from google console
 * 
 * @link http://code.divandesign.biz/modx/mm_ddgmap/1.2b
 * 
 * @copyright 2014, DivanDesign
 * http://www.DivanDesign.biz
 */

function mm_ddGMap($tvs, $roles = '', $templates = '', $w = 'auto', $h = '400', $hideField = true,$apiKey=''){
	if (!useThisRule($roles, $templates)){return;}
	
	global $modx;
	$e = &$modx->Event;
	
	if ($e->name == 'OnDocFormPrerender'){
		global $modx_lang_attribute;
		
		//The main js file including
		$output = includeJsCss($modx->config['site_url'].'assets/plugins/managermanager/widgets/ddgmap/jquery.ddMM.mm_ddGMap.js', 'html', 'jquery.ddMM.mm_ddGMap', '1.0');
		//The Google.Maps library including
		$output .= includeJsCss('//maps.google.com/maps/api/js?sensor=false&hl='.$modx_lang_attribute.'&callback=mm_ddGMap_init&key='.$apiKey, 'html', 'maps.google.com', '0');
		
		$e->output($output);
	}else if ($e->name == 'OnDocFormRender'){
		global $mm_current_page;
		
		$output = '';
		$tvs = makeArray($tvs);

		$usedTvs = tplUseTvs($mm_current_page['template'], $tvs, '', 'id', 'name');
		if ($usedTvs == false){return;}
		
		$output .= "//---------- mm_ddGMap :: Begin -----\n";
		
		//Iterate over supplied TVs instead of doing so to the result of tplUseTvs() to maintain rendering order.
		foreach ($tvs as $tv){
			//If this $tv is used in a current template
			if (isset($usedTvs[$tv])){
				$output .= 
'
$j("#tv'.$usedTvs[$tv]['id'].'").mm_ddGMap({
	hideField: '.intval($hideField).',
	width: "'.$w.'",
	height: "'.$h.'"
});
';
			}
		}
		
		$output .= "//---------- mm_ddGMap :: End -----\n";
		
		$e->output($output);
	}
}
?>