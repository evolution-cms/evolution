<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}
/*
::::::::::::::::::::::::::::::::::::::::
 Snippet name: Wayfinder
 Short Desc: builds site navigation
 Version: 2.0.1
 Authors: 
	Kyle Jaebker (muddydogpaws.com)
	Ryan Thrash (vertexworks.com)
 Date: February 27, 2006
::::::::::::::::::::::::::::::::::::::::
Description:
    Totally refactored from original DropMenu nav builder to make it easier to
    create custom navigation by using chunks as output templates. By using templates,
    many of the paramaters are no longer needed for flexible output including tables,
    unordered- or ordered-lists (ULs or OLs), definition lists (DLs) or in any other
    format you desire.
::::::::::::::::::::::::::::::::::::::::
Example Usage:
    [[Wayfinder? &startId=`0`]]
::::::::::::::::::::::::::::::::::::::::
*/

$wayfinder_base = $modx->config['base_path']."assets/snippets/wayfinder/";

//Include a custom config file if specified
$config = (isset($config)) ? "{$wayfinder_base}configs/{$config}.config.php" : "{$wayfinder_base}configs/default.config.php";
if (file_exists($config)) {
	include("$config");
}

include_once("{$wayfinder_base}wayfinder.inc.php");

if (class_exists('Wayfinder')) {
   $wf = new Wayfinder();
} else {
    return 'error: Wayfinder class not found';
}

$wf->_config = array(
	'id' => isset($startId) ? $startId : $modx->documentIdentifier,
	'level' => isset($level) ? $level : 0,
	'includeDocs' => isset($includeDocs) ? $includeDocs : 0,
	'excludeDocs' => isset($excludeDocs) ? $excludeDocs : 0,
	'ph' => isset($ph) ? $ph : FALSE,
	'debug' => isset($debug) ? TRUE : FALSE,
	'ignoreHidden' => isset($ignoreHidden) ? $ignoreHidden : FALSE,
	'hideSubMenus' => isset($hideSubMenus) ? $hideSubMenus : FALSE,
	'useWeblinkUrl' => isset($useWeblinkUrl) ? $useWeblinkUrl : TRUE,
	'fullLink' => isset($fullLink) ? $fullLink : FALSE,
	'nl' => isset($removeNewLines) ? '' : "\n",
	'sortOrder' => isset($sortOrder) ? strtoupper($sortOrder) : 'ASC',
	'sortBy' => isset($sortBy) ? $sortBy : 'menuindex',
	'limit' => isset($limit) ? $limit : 0,
	'cssTpl' => isset($cssTpl) ? $cssTpl : FALSE,
	'jsTpl' => isset($jsTpl) ? $jsTpl : FALSE,
	'rowIdPrefix' => isset($rowIdPrefix) ? $rowIdPrefix : FALSE,
	'textOfLinks' => isset($textOfLinks) ? $textOfLinks : 'menutitle',
	'titleOfLinks' => isset($titleOfLinks) ? $titleOfLinks : 'pagetitle',
	'displayStart' => isset($displayStart) ? $displayStart : FALSE,
	'entityEncode' => isset($entityEncode) ? $entityEncode : TRUE,
);

//get user class definitions
$wf->_css = array(
	'first' => isset($firstClass) ? $firstClass : '',
	'last' => isset($lastClass) ? $lastClass : 'last',
	'here' => isset($hereClass) ? $hereClass : 'active',
	'parent' => isset($parentClass) ? $parentClass : '',
	'row' => isset($rowClass) ? $rowClass : '',
	'outer' => isset($outerClass) ? $outerClass : '',
	'inner' => isset($innerClass) ? $innerClass : '',
	'level' => isset($levelClass) ? $levelClass: '',
	'self' => isset($selfClass) ? $selfClass : '',
	'weblink' => isset($webLinkClass) ? $webLinkClass : '',
);

//get user templates
$wf->_templates = array(
	'outerTpl' => isset($outerTpl) ? $outerTpl : '',
	'rowTpl' => isset($rowTpl) ? $rowTpl : '',
	'parentRowTpl' => isset($parentRowTpl) ? $parentRowTpl : '',
	'parentRowHereTpl' => isset($parentRowHereTpl) ? $parentRowHereTpl : '',
	'hereTpl' => isset($hereTpl) ? $hereTpl : '',
	'innerTpl' => isset($innerTpl) ? $innerTpl : '',
	'innerRowTpl' => isset($innerRowTpl) ? $innerRowTpl : '',
	'innerHereTpl' => isset($innerHereTpl) ? $innerHereTpl : '',
	'activeParentRowTpl' => isset($activeParentRowTpl) ? $activeParentRowTpl : '',
	'categoryFoldersTpl' => isset($categoryFoldersTpl) ? $categoryFoldersTpl : '',
	'startItemTpl' => isset($startItemTpl) ? $startItemTpl : '',
);

//Process Wayfinder
$output = $wf->run();

if ($wf->_config['debug']) {
	$output .= $wf->renderDebugOutput();
}

//Ouput Results
if ($wf->_config['ph']) {
    $modx->setPlaceholder($wf->_config['ph'],$output);
    return;
} else {
    return $output;
}
?>