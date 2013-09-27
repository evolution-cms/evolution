<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

// ajaxSearch version being executed
define('AS_VERSION', '1.9.3');
// Path where ajaxSearch is installed
define('AS_SPATH', 'assets/snippets/ajaxSearch/');
//include snippet file
define('AS_PATH', MODX_BASE_PATH . AS_SPATH);

//------------------------------------------------------------------------------
// Configuration - general AjaxSearch snippet setup options
//------------------------------------------------------------------------------
global $modx;
$tstart = $modx->getMicroTime();

$cfg = array(); // current configuration
$cfg['version'] = AS_VERSION;

// Load the default configuration $dcfg to get the default values
$default = AS_PATH . 'configs/default.config.php';
if (file_exists($default)) include $default;
else return "<h3>AjaxSearch error: $default not found !<br />Check the existing of this file!</h3>";
if (!isset($dcfg)) return "<h3>AjaxSearch error: default configuration array not defined in $default!<br /> Check the content of this file!</h3>";

if ($dcfg['version'] != AS_VERSION) return "<h3>AjaxSearch error: Version number mismatch. Check the content of the default configuration file!</h3>";

// check the possible use of deprecated parameters (since 1.8.5)
$readme = "ajaxSearch_version_193.txt";
if (isset($searchWordList)) return "<h3>AjaxSearch error: searchWordList is a deprecated parameter. Read " . $readme . " file.</h3>";
if (isset($resultsPage)) return "<h3>AjaxSearch error: resultsPage is a deprecated parameter. Read " . $readme . " file.</h3>";
if (isset($AS_showForm)) return "<h3>AjaxSearch error: AS_showForm parameter has been renamed showInputForm. Read " . $readme . " file.</h3>";
if (isset($AS_landing)) return "<h3>AjaxSearch error: AS_landing parameter has been renamed landingPage. Read " . $readme . " file.</h3>";
if (isset($AS_showResults)) return "<h3>AjaxSearch error: AS_showResults parameter has been renamed showResults. Read " . $readme . " file.</h3>";

// Load a custom configuration file if required
// config_name - Other config installed in the configs folder or in any folder within the MODx base path via @FILE
// Configuration files should be named in the form: <config_name>.config.php
// Default: '' - no custom config
$cfg['config'] = isset($config) ? $config : $dcfg['config'];
if ($cfg['config']) {
    $config = $cfg['config'];
    $lconfig = (substr($config, 0, 6) != "@FILE:") ? AS_PATH . "configs/$config.config.php" : $modx->config['base_path'] . trim(substr($config, 6, strlen($config)-6));
    if (file_exists($lconfig)) include $lconfig;
    else return "<h3>AjaxSearch error: " . $lconfig . " not found !<br />Check your config parameter or your config file name!</h3>";
}

// &debug = [ 0 | 1 | 2 | 3 ]
// 1,2,3 : File mode - Output logged into a file named ajaxSearch_log.txt in ajaxSearch/debug/ directory.
// this directory should be writable.
// Default: 0 - no logs
$cfg['debug'] = isset($debug) ? $debug : (isset($__debug) ? $__debug : $dcfg['debug']);

// &timeLimit = [ int | 60 ]
// Max execution time in seconds for the AjaxSearch script
// 0 - If set to zero, no time limit is imposed
// Default: 60 - 1 minute.
$cfg['timeLimit'] = isset($timeLimit) ? $timeLimit : (isset($__timeLimit) ? $__timeLimit : $dcfg['timeLimit']);

// &language [ language_name | manager_language ] (optional)
// Default: $modx->config['manager_language'] - manager language used
$cfg['language'] = isset($language) ? $language : (isset($__language) ? $__language : $dcfg['language']);

// &ajaxSearch [1 | 0] (as passed in snippet variable ONLY)
// Use this to display the search results using ajax You must include the Mootools library in your template
// Default: 1 - ajax mode selected
$cfg['ajaxSearch'] = isset($ajaxSearch) ? $ajaxSearch : (isset($__ajaxSearch) ? $__ajaxSearch : $dcfg['ajaxSearch']);
// avoid the use of @FILE: prefix with ajax mode
if ((substr($cfg['config'], 0, 6) == "@FILE:") && $cfg['ajaxSearch'])
	return "<h3>AjaxSearch error: @FILE: prefix not allowed !<br />Check your config parameter or your config file name!</h3>";

// &advSearch [ 'exactphrase' | 'allwords' | 'nowords' | 'oneword' ]
// Advanced search:
// - exactphrase : provides the documents which contain the exact phrase
// - allwords : provides the documents which contain all the words
// - nowords : provides the documents which do not contain the words
// - oneword : provides the document which contain at least one word
// Default: 'oneword'
$cfg['advSearch'] = isset($advSearch) ? $advSearch : (isset($__advSearch) ? $__advSearch : $dcfg['advSearch']);

// &asId - Unique id for AjaxSearch instance
// this allows to distinguish several Ajaxsearch instances on the same page
// Any combination of characters a-z, underscores, and numbers 0-9
// This is case sensitive. Default = empty string
// With ajax mode, the first snippet call of the page shouldn't use the asId parameter
$cfg['asId'] = isset($asId) ? $asId : (isset($__asId) ? $__asId : $dcfg['asId']);

// &whereSearch
// Define where should occur the search
// a separated list of keywords describing the tables where to search
// keywords allowed :
// "content" for site_content, "tv" for site_tmplvar_contentvalues, "jot" for jot_content, "maxigallery" for maxigallery
// you could add your own keywords. But the keyword should be a user function which describes the tables to use
// all the text fields are searchable but you could specify the fields like this:
// whereSearch=`content:pagetitle,introtext,content|tv:tv_value|maxigallery:gal_title`
// Default: 'content|tv'
$cfg['whereSearch'] = isset($whereSearch) ? $whereSearch : (isset($__whereSearch) ? $__whereSearch : $dcfg['whereSearch']);

// &sites : [comma separated list of sites]
// sites allow to define sites where to do the search
$cfg['sites'] = isset($sites) ? $sites : (isset($__sites) ? $__sites : $dcfg['sites']);

// &subSearch  [comma separated list of subsites]
// subSearch allow to define sub-domains or subsites where to do the search
$cfg['subSearch'] = isset($subSearch) ? $subSearch : (isset($__subSearch) ? $__subSearch : $dcfg['subSearch']);

// &category  [ tv_name ]
// Any combination of characters a-z, underscores, and numbers 0-9
// This is case sensitive. Default = empty string
// Name of a TV. The category of a MODx document is provided by this TV content
$cfg['category'] = isset($category) ? $category : (isset($__category) ? $__category : $dcfg['category']);

// &display [ 'mixed' | 'unmixed' ]
// When results comes from differents sites, subsites or categories, you could choose to display the results mixed or unmixed.
// Default: unmixed
// Unmixed mode display the results grouped by site, subsite or category. Each group of results could be paginated.
// Mixed mode mixe all the results coming from the differents area.
// With unmixed mode, results are ordered by the field provided by the first field of the order parameter
$cfg['display'] = isset($display) ? $display : (isset($__display) ? $__display : $dcfg['display']);

// &init  [ 'none' | 'all' ]
// init defines if the search display all the results or none when the search term is an empty string
// Default: none
$cfg['init'] = isset($init) ? $init : (isset($__init) ? $__init : $dcfg['init']);

// &withTvs - Define which Tvs are used for the search in Tvs
// a comma separated list of TV names
// Default: '' - all TVs are used (empty list)
$cfg['withTvs'] = isset($withTvs) ? $withTvs : (isset($__withTvs) ? $__withTvs : $dcfg['withTvs']);

// &order - Define the sort order of results
// Comma separated list of fields defined as searchable in the table definition
// to suppress the sorting, use &order=``
// Default: 'pub_date,pagetitle'
$cfg['order'] = isset($order) ? $order : (isset($__order) ? $__order : $dcfg['order']);

// &rank - Define the rank of search results. Results are sorted by rank value
// Comma separated list of fields with optionally user defined weight
// Default: 'pagetitle:100,extract'
// to suppress the rank sorting, use &rank=``;
// &rank sort occurs after the &order sort
$cfg['rank'] = isset($rank) ? $rank : (isset($__rank) ? $__rank : $dcfg['rank']);

// &maxWords [ 1 < int < 10 ]
// Maximum number of words for searching
// Default: 5
$cfg['maxWords'] = isset($maxWords) ? intval($maxWords) : (isset($__maxWords) ? intval($__maxWords) : $dcfg['maxWords']);

// &minChars [  2 < int < 100 ]
// Minimum number of characters to require for a word to be valid for searching.
// length of each word with $advSearch = 'allwords', 'oneword' or 'nowords'
// length of the search string with possible spaces with $advSearch = 'exactphrase'
// Default: 3
$cfg['minChars'] = isset($minChars) ? intval($minChars) : (isset($__minChars) ? intval($__minChars) : $dcfg['minChars']);

// &showInputForm [0 | 1]
// If you would like to turn off the search form when showing results you can set this to false.(1=true, 0=false)
// Default: 1
$cfg['showInputForm'] = isset($showInputForm) ? $showInputForm : (isset($__showInputForm) ? $__showInputForm : $dcfg['showInputForm']);

// &showIntro [0 | 1]
// If you would like to turn off the intro message beyond the input form you can set this to false.(1=true, 0=false)
// Default: 1
$cfg['showIntro'] = isset($showIntro) ? $showIntro : (isset($__showIntro) ? $__showIntro : $dcfg['showIntro']);

// &grabMax [ int ]
// Set to the max number of records you would like on each page. Set to 0 if unlimited.
// Default: 10
$cfg['grabMax'] = isset($grabMax) ? intval($grabMax) : (isset($__grabMax) ? intval($__grabMax) : $dcfg['grabMax']);

// &extract [ n:searchable fields list | 1:content,description,introtext,tv_content]
// show the search terms highlighted in a little extract
// n : maximum number of extracts displayed
// ordered searchable fields list : separated list of fields define as searchable in the table definition
// Default: '1:content,description,introtext,tv_value' - One extract from content then description,introtext,tv_value
$cfg['extract'] = isset($extract) ? $extract : (isset($__extract) ? $__extract : $dcfg['extract']);

// &extractLength [ 50 < int < 800]
// Length of extract around the search words found - between 50 and 800 characters
// Default: 200
$cfg['extractLength'] = isset($extractLength) ? intval($extractLength) : (isset($__extractLength) ? intval($__extractLength) : $dcfg['extractLength']);

// &extractEllips [ string ]
// Ellipside to mark the star and the end of  an extract when the sentence is cutting
// Default: '...'
$cfg['extractEllips'] = isset($extractEllips) ? $extractEllips : (isset($__extractEllips) ? $__extractEllips : $dcfg['extractEllips']);

// &extractSeparator [ string ]
// Any html tag to mark the separation between extracts
// Default: '<br />' - but you could also choose for instance '<hr />'
$cfg['extractSeparator'] = isset($extractSeparator) ? $extractSeparator : (isset($__extractSeparator) ? $__extractSeparator : $dcfg['extractSeparator']);

// &formatDate [ string ]
// The format of outputted dates. See http://www.php.net/manual/en/function.date.php
// Default: 'd/m/y : H:i:s' - e.g: 21/01/08 : 23:09:22
$cfg['formatDate'] = isset($formatDate) ? $formatDate : (isset($__formatDate) ? $__formatDate : $dcfg['formatDate']);

// &highlightResult [1 | 0]
// create links so that search terms will be highlighted when linked page clicked
// Default: 1 - Results highlighted
$cfg['highlightResult'] = isset($highlightResult) ? $highlightResult : (isset($__highlightResult) ? $__highlightResult : $dcfg['highlightResult']);

// &pagingType[ 0 | 1 | 2 ]
// Determine the pagination type used - Default 1 : Previous - X-Y/Z - Next
$cfg['pagingType'] = isset($pagingType) ? $pagingType : (isset($__pagingType) ? $__pagingType : $dcfg['pagingType']);

// &pageLinkSeparator [ string ]
// What you want, if anything, between your page link numbers
// Default: ' | '
$cfg['pageLinkSeparator'] = isset($pageLinkSeparator) ? $pageLinkSeparator : (isset($__pageLinkSeparator) ? $__pageLinkSeparator : $dcfg['pageLinkSeparator']);

// &showPagingAlways[1 | 0]
// Determine whether or not to always show paging
$cfg['showPagingAlways'] = isset($showPagingAlways) ? $showPagingAlways : (isset($__showPagingAlways) ? $__showPagingAlways : $dcfg['showPagingAlways']);

// &landingPage  [int] set the page to show the results page (non Ajax search)
// Default: false
$cfg['landingPage'] = isset($landingPage) ? $landingPage : (isset($__landingPage) ? $__landingPage : $dcfg['landingPage']);

// &showResults  [1 | 0]  establish whether to show the results or not
// Default: 1
$cfg['showResults'] = isset($showResults) ? $showResults : (isset($__showResults) ? $__showResults : $dcfg['showResults']);

// &parents [ [ in | not in ] : comma separated list of Ids | '' ]
// Ids of documents to retrieve their children to &depth depth  where to do the search in or not in
// Default: '' - empty list
$cfg['parents'] = isset($parents) ? $parents : (isset($__parents) ? $__parents : $dcfg['parents']);

// &documents [ [ in | not in ] : comma separated list of Ids | '' ]
// Ids of documents where to do the search in or not in
// Default: '' - empty list
$cfg['documents'] = isset($documents) ? $documents : (isset($__documents) ? $__documents : $dcfg['documents']);

// &depth [ 0 < int ] Number of levels deep to retrieve documents
// Default: 10
$cfg['depth'] = isset($depth) ? intval($depth) : (isset($__depth) ? intval($__depth) : $dcfg['depth']);

// &hideMenu [0 | 1| 2]  Search in hidden documents from menu.
// 0 - search only in documents visible from menu
// 1 - search only in documents hidden from menu
// 2 - search in hidden or visible documents from menu
// Default: 2
$cfg['hideMenu'] = isset($hideMenu) ? $hideMenu : (isset($__hideMenu) ? $__hideMenu : $dcfg['hideMenu']);

// &hideLink [0 | 1 ]   Search in content of type reference (link)
// 0 - search in content of type document AND reference
// 1 - search only in content of type document
// Default: 1
$cfg['hideLink'] = isset($hideLink) ? $hideLink : (isset($__hideLink) ? $__hideLink : $dcfg['hideLink']);

// &filter - Basic filtering : remove unwanted documents that meets the criteria of the filter
// See Ditto 2 Basic filtering and the ajaxSearch demo site for more information
// Default: '' - empty list
$cfg['filter'] = isset($filter) ? $filter : (isset($__filter) ? $__filter : $dcfg['filter']);

// &output [0 | 1 ]Custom layout
// Default: 0 - Results are listed just under the input form
// 1 - custom layout. put [+as.inputForm+] and [+as.results+] where you want to define the layout
$cfg['output'] = isset($output) ? $output : (isset($__output) ? $__output : $dcfg['output']);

// &tplInput - Chunk to style the ajaxSearch input form
// Default: '@FILE:' . AS_SPATH . 'templates/input.tpl.html'
$cfg['tplInput'] = isset($tplInput) ? $tplInput : (isset($__tplInput) ? $__tplInput : $dcfg['tplInput']);

// &tplResults - Chunk to style the non-ajax output results outer
// Default: '@FILE:' . AS_SPATH . 'templates/results.tpl.html'
$cfg['tplResults'] = isset($tplResults) ? $tplResults : (isset($__tplResults) ? $__tplResults : $dcfg['tplResults']);

// &tplGrpResult - Chunk to style the non-ajax output group result outer
// Default: '@FILE:' . AS_SPATH . 'templates/grpResult.tpl.html'
$cfg['tplGrpResult'] = isset($tplGrpResult) ? $tplGrpResult : (isset($__tplGrpResult) ? $__tplGrpResult : $dcfg['tplGrpResult']);

// &tplResult - Chunk to style each output result
// Default: "@FILE:" . AS_SPATH . 'templates/result.tpl.html'
$cfg['tplResult'] = isset($tplResult) ? $tplResult : (isset($__tplResult) ? $__tplResult : $dcfg['tplResult']);

// &tplComment - Chunk to style the comment form (Also used with the ajax mode)
// Default: '@FILE:' . AS_SPATH . 'templates/comment.tpl.html'
$cfg['tplComment'] = isset($tplComment) ? $tplComment : (isset($__tplComment) ? $__tplComment : $dcfg['tplComment']);

// &tplPaging0 - Chunk to style the paging links - type 0
// Default: '@FILE:' . AS_SPATH . 'templates/paging0.tpl.html'
$cfg['tplPaging0'] = isset($tplPaging0) ? $tplPaging0 : (isset($__tplPaging0) ? $__tplPaging0 : $dcfg['tplPaging0']);

// &tplPaging1 - Chunk to style the paging links - type 1
// Default: '@FILE:' . AS_SPATH . 'templates/paging1.tpl.html'
$cfg['tplPaging1'] = isset($tplPaging1) ? $tplPaging1 : (isset($__tplPaging1) ? $__tplPaging1 : $dcfg['tplPaging1']);

// &tplPaging2 - Chunk to style the paging links - type 2
// Default: '@FILE:' . AS_SPATH . 'templates/paging2.tpl.html'
$cfg['tplPaging2'] = isset($tplPaging2) ? $tplPaging2 : (isset($__tplPaging2) ? $__tplPaging2 : $dcfg['tplPaging2']);

// &stripInput - stripInput user function name
// Default: 'defaultStripInput'
$cfg['stripInput'] = isset($stripInput) ? $stripInput : (isset($__stripInput) ? $__stripInput : $dcfg['stripInput']);

// &stripOutput - stripOutput user function name
// Default: 'defaultStripOutput'
$cfg['stripOutput'] = isset($stripOutput) ? $stripOutput : (isset($__stripOutput) ? $__stripOutput : $dcfg['stripOutput']);

// &breadcrumbs
// 0 : disallow the breadcrumbs link
// Name of the breadcrumbs function : allow the breadcrumbs link
// The function name could be followed by some parameter initialization
// e.g: &breadcrumbs=`Breadcrumbs,showHomeCrumb:0,showCrumbsAtHome:1`
// Default: '' - empty string
$cfg['breadcrumbs'] = isset($breadcrumbs) ? $breadcrumbs : (isset($__breadcrumbs) ? $__breadcrumbs : $dcfg['breadcrumbs']);

// &tvPhx - display and set placeHolders for TV (template variables)
// 0 : disallow the feature
// 1 : allow the display of all Modx TVs of the document found (default)
// 'tb_alias:display_function_name[,[tb_alias:display_function_name]*]' : set up placeholders for custom joined tables
// Default: 1 - tvPhx allowed for TV only
$cfg['tvPhx'] = isset($tvPhx) ? $tvPhx : (isset($__tvPhx) ? $__tvPhx : $dcfg['tvPhx']);

// &clearDefault - Clearing default text
// Set this to 1 if you would like to include the clear default js function
// add the class "cleardefault" to your input text form and set this parameter
// Default: 0
$cfg['clearDefault'] = isset($clearDefault) ? $clearDefault : (isset($__clearDefault) ? $__clearDefault : $dcfg['clearDefault']);

// &jsClearDefault - Location of the js library
// Default: AS_SPATH . 'js/clearDefault.js'
$cfg['jsClearDefault'] = $dcfg['jsClearDefault'];

// &mbstring - php_mbstring extension available [0 | 1]
// Default: 1 - extension available
$cfg['mbstring'] = isset($mbstring) ? $mbstring : (isset($__mbstring) ? $__mbstring : $dcfg['mbstring']);

//  &asLog - ajaxSearch log [ level [: comment [: purge]]]
//  level:
//        0 : disallow the ajaxSearch log (Default)
//        1 : failed search requests are logged
//        2 : all ajaxSearch requests are logged
//  comment:
//        0 : user comment not allowed (Default)
//        1 : user comment allowed
//  purge: number of logs allowed before to do an automatic purge of the table
//        Default: 200
$cfg['asLog'] = isset($asLog) ? $asLog : (isset($__asLog) ? $__asLog : $dcfg['asLog']);


//------------------------------------------------------------------------------
// Configuration - Simple Ajax mode
//------------------------------------------------------------------------------

if ($cfg['ajaxSearch']) {

    // $liveSearch [1 | 0] (as passed in snippet variable ONLY)
    // Set this to 1 if you would like to use the live search (i.e. results as you type)
    // Default: 0 - livesearch mode inactivated
    $cfg['liveSearch'] = isset($liveSearch) ? $liveSearch : (isset($__liveSearch) ? $__liveSearch : $dcfg['liveSearch']);

    // &ajaxMax [int] - The maximum number of results to show for the ajaxsearch
    // Default: 6
    $cfg['ajaxMax'] = isset($ajaxMax) ? $ajaxMax : (isset($__ajaxMax) ? $__ajaxMax : $dcfg['ajaxMax']);

    // &moreResultsPage [int]
    // The document id of the page you want the more results link to point to
    // Default: 0
    $cfg['moreResultsPage'] = isset($moreResultsPage) ? $moreResultsPage : (isset($__moreResultsPage) ? $__moreResultsPage : $dcfg['moreResultsPage']);

    // &opacity - set the opacity of the div ajaxSearch_output
    // Should be a float value: [ 0. < float <= 1. ]
    // Default: 1.
    $cfg['opacity'] = isset($opacity) ? $opacity : (isset($__opacity) ? $__opacity : $dcfg['opacity']);

    // &tplAjaxResults - Chunk to style the ajax output results outer
    // Default: '' - empty string
    $cfg['tplAjaxResults'] = isset($tplAjaxResults) ? $tplAjaxResults : (isset($__tplAjaxResults) ? $__tplAjaxResults : $dcfg['tplAjaxResults']);

    // &tplAjaxGrpResult - Chunk to style each ajax output group result outer
    // Default: '' - empty string
    $cfg['tplAjaxGrpResult'] = isset($tplAjaxGrpResult) ? $tplAjaxGrpResult : (isset($__tplAjaxGrpResult) ? $__tplAjaxGrpResult : $dcfg['tplAjaxGrpResult']);

    // &tplAjaxResult - Chunk to style each ajax output result
    // Default: '' - empty string
    $cfg['tplAjaxResult'] = isset($tplAjaxResult) ? $tplAjaxResult : (isset($__tplAjaxResult) ? $__tplAjaxResult : $dcfg['tplAjaxResult']);

    // &jscript ['jquery'|'mootools2'|'mootools']
    // Set this to jquery if you would like use the jquery library
    // set mootools2 to use the version 1.2 of mootools (limited to JS functions used by AS)
    // Default: 'mootools' - use the version 1.11 of mootools provided with MODx
    $cfg['jscript'] = isset($jscript) ? $jscript : (isset($__jscript) ? $__jscript : $dcfg['jscript']);

    // &addJscript [1 | 0]
    // Set this to 1 if you would like to include or not the mootool/jquery library in the header of your pages automatically
    // Default: 1
    $cfg['addJscript'] = isset($addJscript) ? $addJscript : (isset($__addJscript) ? $__addJscript : $dcfg['addJscript']);

    // &jsMooTools - Location of the mootools javascript library (current version of MODx)
    // Default: MGR_DIR.'media/script/mootools/mootools.js'
    $cfg['jsMooTools'] = isset($jsMooTools) ? $jsMooTools : (isset($__jsMooTools) ? $__jsMooTools : $dcfg['jsMooTools']);

    // &jsMooTools2 - Location of an alternative mootools javascript library
    // Default: AS_SPATH . 'js/mootools1.2/mootools.js' - contains only the required functions for AS
    // to use an another library, use this parameter and change the ajaxSearch/js/ajaxSearch1/ajaxSearch-mootools2.js file
    $cfg['jsMooTools2'] = isset($jsMooTools2) ? $jsMooTools2 : (isset($__jsMooTools2) ? $__jsMooTools2 : $dcfg['jsMooTools2']);

    // &jsQuery - Location of the jquery javascript library
    // Default: AS_SPATH . 'js/jquery/jquery.js'
    $cfg['jsJquery'] = isset($jsJquery) ? $jsJquery : (isset($__jsJquery) ? $__jsJquery : $dcfg['jsJquery']);
}

// ========================================================== End of config
include_once AS_PATH . "classes/ajaxSearch.class.inc.php";
if (class_exists('AjaxSearch')) {
    $as = new AjaxSearch();
    $output = $as->run($tstart, $dcfg, $cfg);
} else {
    $output = "<h3>error: AjaxSearch class not found</h3>";
}
$elapsedTime = $modx->getMicroTime() - $tstart;
$etime = sprintf("%.4fs",$elapsedTime);
//$f=fopen('test.txt','a+');fwrite($f,"etime=".$etime."\n\n");
return $output;