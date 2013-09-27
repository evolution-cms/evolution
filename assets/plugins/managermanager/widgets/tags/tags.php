<?php

//---------------------------------------------------------------------------------
// mm_widget_tags
// Adds a tag selection widget to the specified TVs
//---------------------------------------------------------------------------------
function mm_widget_tags($fields, $delimiter=',', $source='', $display_count=false, $roles='', $templates='') {

	global $modx, $content, $mm_fields;
	$e = &$modx->Event;

	if ($e->name == 'OnDocFormRender' && useThisRule($roles, $templates)){

		$output = '';

		// if we've been supplied with a string, convert it into an array
		$fields = makeArray($fields);

		// And likewise for the data source (if supplied)
		$source = (empty($source)?$fields:makeArray($source));


		// Which template is this page using?
		if (isset($content['template'])) {
			$page_template = $content['template'];
		} else {
			// If no content is set, it's likely we're adding a new page at top level. 
			// So use the site default template. This may need some work as it might interfere with a default template set by MM?
			$page_template = $modx->config['default_template']; 
		}
		

		// Does this page's template use any of these TVs? If not, quit.
		$field_tvs = tplUseTvs($page_template, $fields);
		if ($field_tvs == false) {
			return;
		}	
		
		$source_tvs = tplUseTvs($page_template, $source);
		if ($source_tvs == false) {
			return;
		}	
		

		// Insert some JS  and a style sheet into the head
		$output .= "//  -------------- Tag widget include ------------- \n";
		$output .= includeJs($modx->config['base_url'] .'assets/plugins/managermanager/widgets/tags/tags.js');
		$output .= includeCss($modx->config['base_url'] .'assets/plugins/managermanager/widgets/tags/tags.css');


		// Go through each of the fields supplied
		foreach ($fields as $targetTv) {
			
				$tv_id = $mm_fields[$targetTv]['fieldname'];

				// Make an SQL friendly list of fields to look at:
				//$escaped_sources = array();
				//foreach ($source as $s) {
                                        //$s=substr($s,2,1);
				//	$escaped_sources[] = "'".$s."'";
				//}
				$sql_sources = implode(',',$source_tvs[0]);

				// Get the list of current values for this TV
				$sql = "SELECT `value` FROM ".$modx->getFullTableName('site_tmplvar_contentvalues')." WHERE tmplvarid IN (".$sql_sources.")";

				$result= $modx->db->query($sql);
				$all_docs = $modx->db->makeArray( $result );

				$foundTags = array();
				foreach ($all_docs as $theDoc) {
					$theTags = explode($delimiter, $theDoc['value']);
					foreach ($theTags as $t) {
						$foundTags[trim($t)]++;
					}
				}

				// Sort the TV values (case insensitively)
				uksort($foundTags, 'strcasecmp');

				$lis = '';
				foreach($foundTags as $t=>$c) {
					$lis .= '<li title="Used '.$c.' times">'.jsSafe($t).($display_count?' ('.$c.')':'').'</li>';
				}

				$html_list = '<ul class="mmTagList" id="'.$tv_id.'_tagList">'.$lis.'</ul>';

				// Insert the list of tags after the field
				$output .= '
				//  -------------- Tag widget for '.$targetTv.' ('.$tv_id.') --------------
				$j("#'.$tv_id.'").after(\''.$html_list.'\');
				';

				// Initiate the tagCompleter class for this field
				$output .= 'var '.$tv_id.'_tags = new TagCompleter("'.$tv_id.'", "'.$tv_id.'_tagList", "'.$delimiter.'"); ';

		}
		$e->output($output . "\n");
	}
}
?>