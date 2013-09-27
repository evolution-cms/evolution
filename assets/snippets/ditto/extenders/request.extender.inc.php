<?php
/*
 * Title: Request
 * Purpose:
 *  	Adds support for changing Ditto parameters via URL
 * 
 * Note:
 * 		- All variables must be prefixed with ditto_ for the snippet to recognize them!
 * 		- If a Ditto id is set use the sntax 
*/
$variables = array();
$stripTags = isset($stripTags) ? $stripTags : 1;
/*
	Param: stripTags
	
	Purpose:
	Remove HTML tags from the parameters provided

	Options:
	0 - off
	1 - on
	
	Default:
	1 - on
*/
$bad = isset($bad) ? explode(",",$bad) : array("seeThroughtUnpub","showInMenuOnly","showPublishedOnly","debug","start","config","extenders","dittoID");
/*
	Param: bad
	
	Purpose:
	Parameters that are not allowed to be set
	
	Options:
	Any valid Ditto options separated by commas
	
	Default:
	"seeThroughtUnpub,showInMenuOnly,showPublishedOnly,debug,start,config,extenders,dittoID"
*/
$good = isset($good) ? explode(",",$good) : false;
/*
	Param: good
	
	Purpose:
	Parameters that are allowed to be set
	
	Options:
	Any valid Ditto options separated by commas
	
	Default:
	All parameters execpt those in &bad
*/
foreach ($_REQUEST as $name=>$value) {
	$saneName = str_replace($dittoID, "", substr($name, 6));
	$dID = ($dittoID == "") ? true : strpos($name, $dittoID);
	if ((substr($name, 0, 6) == "ditto_" && $dID) && !in_array($saneName,$bad) && ($good == false || in_array($saneName,$good)) && !preg_match("/[\^`~!\/@\\#\}\$%:;\)\(\{&\*=\|'\+]/", $value)){
		if ($stripTags) $var = $modx->stripTags($value);
		if ($saneName == 'orderBy') {
			$variables[$saneName]  = array('parsed'=>array(),'custom'=>array(),'unparsed'=>trim($value));
		}else{
			$variables[$saneName] = trim($value);
	    }
	}
}
/*
	Param: dbg
	
	Purpose:
	Output variables being set
	
	Options:
	0 - off
	1 - on
	
	Default:
	0 - off
*/
if ($_REQUEST[$dittoID."dbg"]==1) {print_r($variables);}
extract($variables);

// ------------------------------------------------------------------------------//
// Kudo's MultiFilter Code 														 //
// ------------------------------------------------------------------------------//
// Accepts ditto_filter, ditto_filter_2, with continuous numbering				 //
// Note: For complex filtering start with ditto_filter_1 (with one as number)!   //
// ------------------------------------------------------------------------------//
  
if (isset($filter) && isset($filter_2)) {
		$i = 2;
		while (isset(${'filter_'.$i})) {
			$filter .= '|'.${'filter_'.$i};
			$i++;
		}
	} elseif (!isset($filter) && isset($filter_1)) {
		$filter = $filter_1;
		$i = 2;
		while (isset(${'filter_'.$i})) {
			$filter .= '|'.${'filter_'.$i};
			$i++;
		}
}

?>