<?php
if(!defined('MODX_BASE_PATH')) {die('What are you doing? Get out of here!');}

/* Description:
 *      Aggregates documents to create blogs, article/news
 *      collections, and more,with full support for templating.
 * 
 * Author: 
 *      Mark Kaplan for MODx CMF
*/

//---Core Settings---------------------------------------------------- //

$ditto_version = "2.1.1";
    // Ditto version being executed

$ditto_base = isset($ditto_base) ? $modx->config['base_path'].$ditto_base : $modx->config['base_path']."assets/snippets/ditto/";
/*
    Param: ditto_base
    
    Purpose:
    Location of Ditto files

    Options:
    Any valid folder location containing the Ditto source code with a trailing slash

    Default:
    [(base_path)]assets/snippets/ditto/
*/
$dittoID = (!isset($id)) ? "" : $id."_";
$GLOBALS["dittoID"] = $dittoID;
/*
    Param: id

    Purpose:
    Unique ID for this Ditto instance for connection with other scripts (like Reflect) and unique URL parameters

    Options:
    Any combination of characters a-z, underscores, and numbers 0-9
    
    Note:
    This is case sensitive

    Default:
    "" - blank
*/      
$language = (isset($language))? $language : $modx->config['manager_language'];
if (!file_exists($ditto_base."lang/".$language.".inc.php")) {
    $language ="english";
}
/*
    Param: language

    Purpose:
    language for defaults, debug, and error messages

    Options:
    Any language name with a corresponding file in the &ditto_base/lang folder

    Default:
    "english"
*/
$format = (isset($format)) ? strtolower($format) : "html" ;
/*
    Param: format

    Purpose:
    Output format to use

    Options:
    - "html"
    - "json"
    - "xml"
    - "atom"
    - "rss"

    Default:
    "html"
*/
$config = (isset($config)) ? $config : "default";
/*
    Param: config

    Purpose:
    Load a custom configuration

    Options:
    "default" - default blank config file
    CONFIG_NAME - Other configs installed in the configs folder or in any folder within the MODx base path via @FILE

    Default:
    "default"
    
    Related:
    - <extenders>
*/
$debug = isset($debug)? $debug : 0;
/*
    Param: debug

    Purpose:
    Output debugging information

    Options:
    0 - off
    1 - on
    
    Default:
    0 - off
    
    Related:
    - <debug>
*/
$phx = (isset($phx))? $phx : 1;
/*
    Param: phx

    Purpose:
    Use PHx formatting

    Options:
    0 - off
    1 - on
    
    Default:
    1 - on
*/      
$extenders = isset($extenders) ? explode(",",$extenders) : array();
/*
    Param: extenders

    Purpose:
    Load an extender which adds functionality to Ditto

    Options:
    Any extender in the extenders folder or in any folder within the MODx base path via @FILE

    Default:
    [NULL]

    Related:
    - <config>
*/
    // Variable: extenders
    // Array that can be added to by configs or formats to load that extender
    
$placeholders = array();
    // Variable: placeholders
    // Initialize custom placeholders array for configs or extenders to add to

$filters = array("custom"=>array(),"parsed"=>array());
    // Variable: filters
    // Holds both the custom filters array for configs or extenders to add to 
    // and the parsed filters array. To add to this array, use the following format
    // (code)
    // $filters["parsed"][] = array("name" => array("source"=>$source,"value"=>$value,"mode"=>$mode));
    // $filters["custom"][] = array("source","callback_function");

$orderBy = array('parsed'=>array(),'custom'=>array(),'unparsed'=>$orderBy);
    // Variable: orderBy
    // An array that holds all criteria to sort the result set by. 
    // Note that using a custom sort will disable all other sorting.
    // (code)
    // $orderBy["parsed"][] = array("sortBy","sortDir");
    // $orderBy["custom"][] = array("sortBy","callback_function");
        
//---Includes-------------------------------------------------------- //

$files = array (
    "base_language" => $ditto_base."lang/english.inc.php",
    "language" => $ditto_base."lang/$language.inc.php",
    "main_class" => $ditto_base."classes/ditto.class.inc.php",
    "template_class" => $ditto_base."classes/template.class.inc.php",
    "filter_class" => $ditto_base."classes/filter.class.inc.php",
    "format" => $ditto_base."formats/$format.format.inc.php",
    "config" => $ditto_base."configs/default.config.php",
    "user_config" => (substr($config, 0, 5) != "@FILE") ? $ditto_base."configs/$config.config.php" : $modx->config['base_path'].trim(substr($config, 5))
);

if ($phx == 1) {
    $files["prePHx_class"] = $ditto_base."classes/phx.pre.class.inc.php";
}
if (isset($randomize)) {
    $files["randomize_class"] = $ditto_base."classes/random.class.inc.php";
}
if ($debug == 1) {
    $files["modx_debug_class"] = $ditto_base."debug/modxDebugConsole.class.php";
    $files["debug_class"] = $ditto_base."classes/debug.class.inc.php";
    $files["debug_templates"] = $ditto_base."debug/debug.templates.php";
}

$files = array_unique($files);
foreach ($files as $filename => $filevalue) {
    if (file_exists($filevalue) && strpos($filename,"class")) {
        include_once($filevalue);
    } else if (file_exists($filevalue)) {
        include($filevalue);
    } else if ($filename == "language") {
        $modx->logEvent(1, 3, "Language file does not exist Please check: " . $filevalue, "Ditto " . $ditto_version);
        return "Language file does not exist Please check: " . $filevalue;
    } else {
        $modx->logEvent(1, 3, $filevalue . " " . $_lang['file_does_not_exist'], "Ditto " . $ditto_version);
        return $filevalue . " " . $_lang['file_does_not_exist'];
    }
}

//---Initiate Class-------------------------------------------------- //
if (class_exists('ditto')) {
    $ditto = new ditto($dittoID,$format,$_lang,$dbg_templates);
        // create a new Ditto instance in the specified format and language with the requested debug level
} else {
    $modx->logEvent(1,3,$_lang['invalid_class'],"Ditto ".$ditto_version);
    return $_lang['invalid_class'];
}

//---Initiate Extenders---------------------------------------------- //
if (isset($tagData)) {
    $extenders[] = "tagging";
}
if(count($extenders) > 0) {
    $extenders = array_unique($extenders);
    foreach ($extenders as $extender) {
            if(substr($extender, 0, 5) != "@FILE") {
                $extender_path = $ditto_base."extenders/".$extender.".extender.inc.php";                
            } else {
                $extender_path = $modx->config['base_path'].trim(substr($extender, 5));
            }
            
            if (file_exists($extender_path)){
                include($extender_path);
            } else {
                $modx->logEvent(1, 3, $extender . " " . $_lang['extender_does_not_exist'], "Ditto ".$ditto_version);
                return $extender . " " . $_lang['extender_does_not_exist'];
            }       
    }   
}

//---Parameters------------------------------------------------------- /*
if (isset($startID)) {$parents = $startID;}
if (isset($summarize)) {$display = $summarize;}
if (isset($limit)) {$queryLimit = $limit;}
if (isset($sortBy) || isset($sortDir) || is_null($orderBy['unparsed'])) {
    $sortDir = isset($sortDir) ? strtoupper($sortDir) : 'DESC';
    $sortBy = isset($sortBy) ? $sortBy : "createdon";
    $orderBy['parsed'][]=array($sortBy,$sortDir);
}
    // Allow backwards compatibility

$idType = isset($documents) ? "documents" : "parents";
    // Variable: idType
    // type of IDs provided; can be either parents or documents

$parents = isset($parents) ? $ditto->cleanIDs($parents) : $modx->documentIdentifier;

/*
    Param: parents

    Purpose:
    IDs of containers for Ditto to retrieve their children to &depth depth

    Options:
    Any valid MODx document marked as a container

    Default:
    Current MODx Document

    Related:
    - <documents>
    - <depth>
*/
$documents = isset($documents) ? $ditto->cleanIDs($documents) : false;
/*
    Param: documents

    Purpose:
    IDs of documents for Ditto to retrieve

    Options:
    Any valid MODx document marked as a container

    Default:
    None

    Related:
    - <parents>
*/

$IDs = ($idType == "parents") ? $parents : $documents;
    // Variable: IDs
    // Internal variable which holds the set of IDs for Ditto to fetch

$depth = isset($depth) ? $depth : 1;
/*
    Param: depth

    Purpose:
    Number of levels deep to retrieve documents

    Options:
    Any number greater than or equal to 1
    0 - infinite depth

    Default:
    1

    Related:
    - <seeThruUnpub>
*/
$paginate = isset($paginate)? $paginate : 0;
/*
    Param: paginate

    Purpose:
    Paginate the results set into pages of &display length.
    Use &total to limit the number of documents retreived.

    Options:
    0 - off
    1 - on
    
    Default:
    0 - off
    
    Related:
    - <paginateAlwaysShowLinks>
    - <paginateSplitterCharacter>
    - <display>
*/
$dateSource = isset($dateSource) ? $dateSource : "createdon";
/*
    Param: dateSource

    Purpose:
    Source of the [+date+] placeholder

    Options:
    # - Any UNIX timestamp from MODx fields or TVs such as createdon, pub_date, or editedon
    
    Default:
    "createdon"
    
    Related:
    - <dateFormat>
*/
$dateFormat = isset($dateFormat)? $dateFormat : $_lang["dateFormat"];
/*
    Param: dateFormat

    Purpose:
    Format the [+date+] placeholder in human readable form

    Options:
    Any PHP valid strftime option

    Default:
    [LANG]
    
    Related:
    - <dateSource>
*/
$display = isset($display) ? $display : "all";
/*
    Param: display

    Purpose:
    Number of documents to display in the results

    Options:
    # - Any number
    "all" - All documents found

    Default:
    "all"
    
    Related:
    - <queryLimit>
    - <total>
*/
$total = isset($total) ? $total : "all";
/*
    Param: total

    Purpose:
    Number of documents to retrieve
    
    Options:
    # - Any number
    "all" - All documents found

    Default:
    "all" - All documents found
    
    Related:
    - <display>
    - <queryLimit>
*/
$showPublishedOnly = isset($showPublishedOnly) ? $showPublishedOnly : 1;
/*
    Param: showPublishedOnly

    Purpose:
    Show only published documents

    Options:
    0 - show only unpublished documents
    1 - show both published and unpublished documents
    
    Default:
    1 - show both published and unpublished documents
    
    Related:
    - <seeThruUnpub>
    - <hideFolders>
    - <showPublishedOnly>
    - <where>
*/
$showInMenuOnly = isset($showInMenuOnly) ? $showInMenuOnly : 0;
/*
    Param: showInMenuOnly

    Purpose:
    Show only documents visible in the menu

    Options:
    0 - show all documents
    1 - show only documents with the show in menu flag checked
    
    Default:
    0 - show all documents
    
    Related:
    - <seeThruUnpub>
    - <hideFolders>
    - <where>
*/
$hideFolders = isset($hideFolders)? $hideFolders : 0;
/*
    Param: hideFolders

    Purpose:
    Don't show folders in the returned results

    Options:
    0 - keep folders
    1 - remove folders
    
    Default:
    0 - keep folders
    
    Related:
    - <seeThruUnpub>
    - <showInMenuOnly>
    - <where>
*/
$hidePrivate = isset($hidePrivate)? $hidePrivate : 1;
/*
    Param: hidePrivate

    Purpose:
    Don't show documents the guest or user does not have permission to see

    Options:
    0 - show private documents
    1 - hide private documents
    
    Default:
    1 - hide private documents
    
    Related:
    - <seeThruUnpub>
    - <showInMenuOnly>
    - <where>
*/
$seeThruUnpub = (isset($seeThruUnpub))? $seeThruUnpub : 1 ;
/*
    Param: seeThruUnpub

    Purpose:
    See through unpublished folders to retrive their children
    Used when depth is greater than 1

    Options:
    0 - off
    1 - on
    
    Default:
    0 - off
    
    Related:
    - <hideFolders>
    - <showInMenuOnly>
    - <where>
*/
$queryLimit = (isset($queryLimit))? $queryLimit : 0;
/*
    Param: queryLimit

    Purpose:
    Number of documents to retrieve from the database, same as MySQL LIMIT

    Options:
    # - Any number
    0 - automatic

    Default:
    0 - automatic
    
    Related:
    - <where>
*/
$where = (isset($where))? $where : "";
/*
    Param: where

    Purpose:
    Custom MySQL WHERE statement

    Options:
    A valid MySQL WHERE statement using only document object items (no TVs)

    Default:
    [NULL]
    
    Related:
    - <queryLimit>
*/
$noResults = isset($noResults)? $ditto->getParam($noResults,"no_documents") : $_lang['no_documents'];
/*
    Param: noResults

    Purpose:
    Text or chunk to display when there are no results

    Options:
    Any valid chunk name or text

    Default:
    [LANG]
*/
$removeChunk = isset($removeChunk) ? explode(",",$removeChunk) : false;
/*
    Param: removeChunk

    Purpose:
    Name of chunks to be stripped from content separated by commas
    - Commonly used to remove comments

    Options:
    Any valid chunkname that appears in the output

    Default:
    [NULL]
*/
$hiddenFields = isset($hiddenFields) ? explode(",",$hiddenFields) : false;
/*
    Param: hiddenFields

    Purpose:
    Allow Ditto to retrieve fields its template parser cannot handle such as nested placeholders and [*fields*]

    Options:
    Any valid MODx fieldnames or TVs comma separated

    Default:
    [NULL]
*/
$offset = isset($start) ? $start : 0;
$start = (isset($_GET[$dittoID.'start'])) ? intval($_GET[$dittoID.'start']) : 0;
/*
    Param: start

    Purpose:
    Number of documents to skip in the results
    
    Options:
    Any number

    Default:
    0
*/
$globalFilterDelimiter = isset($globalFilterDelimiter) ? $globalFilterDelimiter : "|";
/*
    Param: globalFilterDelimiter

    Purpose:
    Filter delimiter used to separate filters in the filter string
    
    Options:
    Any character not used in the filters

    Default:
    "|"
    
    Related:
    - <localFilterDelimiter>
    - <filter>
    - <parseFilters>
*/
    
$localFilterDelimiter = isset($localFilterDelimiter) ? $localFilterDelimiter : ",";
/*
    Param: localFilterDelimiter

    Purpose:
    Delimiter used to separate individual parameters within each filter string
    
    Options:
    Any character not used in the filter itself

    Default:
    ","
    
    Related:
    - <globalFilterDelimiter>
    - <filter>
    - <parseFilters>
*/
$filters["custom"] = isset($cFilters) ? array_merge($filters["custom"],$cFilters) : $filters["custom"];
$filters["parsed"] = isset($parsedFilters) ? array_merge($filters["parsed"],$parsedFilters) : $filters["parsed"];
    // handle 2.0.0 compatibility
$filter = (isset($filter) || ($filters["custom"] != false) || ($filters["parsed"] != false)) ? $ditto->parseFilters($filter,$filters["custom"],$filters["parsed"],$globalFilterDelimiter,$localFilterDelimiter) : false;
/*
    Param: filter

    Purpose:
    Removes items not meeting a critera. Thus, if pagetitle == joe then it will be removed.
    Use in the format field,criteria,mode with the comma being the local delimiter

    *Mode* *Meaning*
    
    1 - !=
    2 - ==
    3 - <
    4 - >
    5 - <=
    6 - >=
    7 - Text not in field value
    8 - Text in field value
    9 - case insenstive version of #7
    10 - case insenstive version of #8
    11 - checks leading character of the field
    
    @EVAL:
        @EVAL in filters works the same as it does in MODx exect it can only be used 
        with basic filtering, not custom filtering (tagging, etc). Make sure that
        you return the value you wish Ditto to filter by and that the code is valid PHP.

    Default:
    [NULL]
    
    Related:
    - <localFilterDelimiter>
    - <globalFilterDelimiter>
    - <parseFilters>
*/
$keywords = (isset($keywords))? $keywords : 0;
/*  
    Param: keywords
    
    Purpose: 
    Enable fetching of associated keywords for each document
    Can be used as [+keywords+] or as a tagData source
    
    Options:
    0 - off
    1 - on
    
    Default:
    0 - off
*/

$randomize = (isset($randomize))? $randomize : 0;
/*  
    Param: randomize
    
    Purpose: 
    Randomize the order of the output
    
    Options:
    0 - off
    1 - on
    Any MODx field or TV for weighted random
    
    Default:
    0 - off
*/
$save = (isset($save))? $save : 0;
/*
    Param: save

    Purpose:
    Saves the ditto object and results set to placeholders
    for use by other snippets

    Options:
    0 - off; returns output
    1 - remaining; returns output
    2 - all;
    3 - all; returns ph only

    Default:
        0 - off; returns output
*/
$templates = array(
    "default" => "@CODE".$_lang['default_template'],
    "base" => $tpl,
    "alt" => $tplAlt,
    "first" => $tplFirst,
    "last" => $tplLast,
    "current" => $tplCurrentDocument
);
/*
    Param: tpl

    Purpose:
    User defined chunk to format the documents 

    Options:
    - Any valid chunk name
    - Code via @CODE
    - File via @FILE

    Default:
    [LANG]
*/
/*
    Param: tplAlt

    Purpose:
    User defined chunk to format every other document

    Options:
    - Any valid chunk name
    - Code via @CODE
    - File via @FILE

    Default:
    &tpl
*/
/*
    Param: tplFirst

    Purpose:
    User defined chunk to format the first document 

    Options:
    - Any valid chunk name
    - Code via @CODE
    - File via @FILE

    Default:
    &tpl
*/
/*
    Param: tplLast

    Purpose:
    User defined chunk to format the last document 

    Options:
    - Any valid chunk name
    - Code via @CODE
    - File via @FILE

    Default:
    &tpl
*/
/*
    Param: tplCurrentDocument

    Purpose:
    User defined chunk to format the current document

    Options:
    - Any valid chunk name
    - Code via @CODE
    - File via @FILE

    Default:
    &tpl
*/
$orderBy = $ditto->parseOrderBy($orderBy,$randomize);
/*
    Param: orderBy

    Purpose:
    Sort the result set

    Options:
    Any valid MySQL style orderBy statement

    Default:
    createdon DESC
*/
//-------------------------------------------------------------------- */
$templates = $ditto->template->process($templates);
    // parse the templates for TV's and store them for later use

$ditto->setDisplayFields($ditto->template->fields,$hiddenFields);
    // parse hidden fields
    
$ditto->parseFields($placeholders,$seeThruUnpub,$dateSource,$randomize);
    // parse the fields into the field array
    
$documentIDs = $ditto->determineIDs($IDs, $idType, $ditto->fields["backend"]["tv"], $orderBy, $depth, $showPublishedOnly, $seeThruUnpub, $hideFolders, $hidePrivate, $showInMenuOnly, $where, $keywords, $dateSource, $queryLimit, $display, $filter,$paginate, $randomize);
    // retrieves a list of document IDs that meet the criteria and populates the $resources array with them
$count = count($documentIDs);
    // count the number of documents to be retrieved
$count = $count-$offset;
    // handle the offset

if ($count > 0) {
    // if documents are returned continue with execution
    
    $total = ($total == "all") ? $count : min($total,$count);
        // set total equal to count if all documents are to be included
    
    $display = ($display == "all") ? min($count,$total) : min($display,$total);
        // allow show to use all option

    $stop = ($save != "1") ? min($total-$start,$display) : min($count,$total);
        // set initial stop count

    if($paginate == 1) {
        $max_paginate = isset($max_paginate)? $max_paginate : 50;
        $max_previous = isset($max_previous)? $max_previous : 25;
        $paginateAlwaysShowLinks = isset($paginateAlwaysShowLinks)? $paginateAlwaysShowLinks : 0;
        /*
            Param: paginateAlwaysShowLinks

            Purpose:
            Determine whether or not to always show previous next links

            Options:
            0 - off
            1 - on

            Default:
            0 - off
        
            Related:
            - <paginate>
            - <paginateSplitterCharacter>
        */
        $paginateSplitterCharacter = isset($paginateSplitterCharacter)? $paginateSplitterCharacter : $_lang['button_splitter'];
        /*
            Param: paginateSplitterCharacter

            Purpose:
            Splitter to use if always show is disabled

            Options:
            Any valid character

            Default:
            [LANG]
        
            Related:
            - <paginate>
            - <paginateSplitterCharacter>
        */
        $tplPaginatePrevious = isset($tplPaginatePrevious)? $ditto->template->fetch($tplPaginatePrevious) : "<a href='[+url+]' class='ditto_previous_link'>[+lang:previous+]</a>";
        /*
            Param: tplPaginatePrevious

            Purpose:
            Template for the previous link

            Options:
            - Any valid chunk name
            - Code via @CODE
            - File via @FILE

            Placeholders:
            url - URL for the previous link
            lang:previous - value of 'prev' from the language file
        
            Related:
            - <tplPaginateNext>
            - <paginateSplitterCharacter>
        */
        $tplPaginateNext = isset($tplPaginateNext)? $ditto->template->fetch($tplPaginateNext) : "<a href='[+url+]' class='ditto_next_link'>[+lang:next+]</a>";
        /*
            Param: tplPaginateNext

            Purpose:
            Template for the next link

            Options:
            - Any valid chunk name
            - Code via @CODE
            - File via @FILE

            Placeholders:
            url - URL for the next link
            lang:next - value of 'next' from the language file
        
            Related:
            - <tplPaginatePrevious>
            - <paginateSplitterCharacter>
        */
        $tplPaginateNextOff = isset($tplPaginateNextOff)? $ditto->template->fetch($tplPaginateNextOff) : "<span class='ditto_next_off ditto_off'>[+lang:next+]</span>";
        /*
            Param: tplPaginateNextOff

            Purpose:
            Template for the inside of the next link

            Options:
            - Any valid chunk name
            - Code via @CODE
            - File via @FILE

            Placeholders:
            lang:next - value of 'next' from the language file
        
            Related:
            - <tplPaginatePrevious>
            - <paginateSplitterCharacter>
        */
        $tplPaginatePreviousOff = isset($tplPaginatePreviousOff)? $ditto->template->fetch($tplPaginatePreviousOff) : "<span class='ditto_previous_off ditto_off'>[+lang:previous+]</span>";
        /*
            Param: tplPaginatePreviousOff

            Purpose:
            Template for the previous link when it is off

            Options:
            - Any valid chunk name
            - Code via @CODE
            - File via @FILE

            Placeholders:
            lang:previous - value of 'prev' from the language file
    
            Related:
            - <tplPaginatePrevious>
            - <paginateSplitterCharacter>
        */
        $tplPaginatePage = isset($tplPaginatePage)? $ditto->template->fetch($tplPaginatePage) : "<a class='ditto_page' href='[+url+]'>[+page+]</a>";
        /*
            Param: tplPaginatePage

            Purpose:
            Template for the page link

            Options:
            - Any valid chunk name
            - Code via @CODE
            - File via @FILE

            Placeholders:
            url - url for the page
            page - number of the page
    
            Related:
            - <tplPaginatePrevious>
            - <paginateSplitterCharacter>
        */
        $tplPaginateCurrentPage = isset($tplPaginateCurrentPage)? $ditto->template->fetch($tplPaginateCurrentPage) : "<span class='ditto_currentpage'>[+page+]</span>";
        /*
            Param: tplPaginateCurrentPage

            Purpose:
            Template for the current page link

            Options:
            - Any valid chunk name
            - Code via @CODE
            - File via @FILE

            Placeholders:
            page - number of the page
    
            Related:
            - <tplPaginatePrevious>
            - <paginateSplitterCharacter>
        */
        
        $ditto->paginate($start, $stop, $total, $display, $tplPaginateNext, $tplPaginatePrevious, $tplPaginateNextOff, $tplPaginatePreviousOff, $tplPaginatePage, $tplPaginateCurrentPage, $paginateAlwaysShowLinks, $paginateSplitterCharacter, $max_paginate, $max_previous);
            // generate the pagination placeholders
    }

    $dbFields = $ditto->fields["display"]["db"];
        // get the database fields
    $TVs = $ditto->fields["display"]["tv"];
        // get the TVs
    
    switch($orderBy['parsed'][0][1]) {
        case "DESC":
            $stop = ($ditto->prefetch === false) ? $stop + $start + $offset : $stop + $offset; 
            $start += $offset;
        break;
        case "ASC":
            $start += $offset;
            $stop += $start;
        break;
    }

    if ($ditto->prefetch !== false) {
        $documentIDs = array_slice($documentIDs,$start,$stop);
            // set the document IDs equal to the trimmed array
        $dbFields = array_diff($dbFields,$ditto->prefetch["fields"]["db"]);
            // calculate the difference between the database fields and those already prefetched
        $dbFields[] = "id";
            // append id to the db fields array
        $TVs = array_diff($TVs,$ditto->prefetch["fields"]["tv"]);
            // calculate the difference between the tv fields and those already prefetched
        $start = 0;
        $stop = min($display,($queryLimit != 0) ? $queryLimit : $display,count($documentIDs));
    } else {
        $queryLimit = ($queryLimit == 0) ? "" : $queryLimit;
    }
    
    $resource = $ditto->getDocuments($documentIDs, $dbFields, $TVs, $orderBy, $showPublishedOnly, 0, $hidePrivate, $where, $queryLimit, $keywords, $randomize, $dateSource);
        // retrieves documents
    $output = $header;
        // initialize the output variable and send the header

    if ($resource) {
        if ($randomize != "0" && $randomize != "1") {
            $resource = $ditto->weightedRandom($resource,$randomize,$stop);
                // randomize the documents
        }
        
        $resource = array_values($resource);

        for ($x=$start;$x<$stop;$x++) {
            $template = $ditto->template->determine($templates,$x,0,$stop,$resource[$x]["id"]);
                // choose the template to use and set the code of that template to the template variable
            $renderedOutput = $ditto->render($resource[$x], $template, $removeChunk, $dateSource, $dateFormat, $placeholders,$phx,abs($start-$x),$stop);
                // render the output using the correct template, in the correct format and language
            $modx->setPlaceholder($dittoID."item[".abs($start-$x)."]",$renderedOutput);
            /*
                Placeholder: item[x]

                Content:
                Individual items rendered output
            */
            $output .= $renderedOutput;
                // send the rendered output to the buffer
        }
    } else {
        $output .= $ditto->noResults($noResults,$paginate);
            // if no documents are found return a no documents found string
    }
    $output .= $footer;
        // send the footer

    // ---------------------------------------------------
    // Save Object
    // ---------------------------------------------------

    if($save) {
        $modx->setPlaceholder($dittoID."ditto_object", $ditto);
        $modx->setPlaceholder($dittoID."ditto_resource", ($save == "1") ? array_slice($resource,$display) : $resource);
    }
} else {
    $output = $header.$ditto->noResults($noResults,$paginate).$footer;
}
// ---------------------------------------------------
// Handle Debugging
// ---------------------------------------------------

if ($debug == 1) {
    $ditto_params =& $modx->event_params;
    if (!isset($_GET["ditto_".$dittoID."debug"])) {
    $_SESSION["ditto_debug_$dittoID"] = $ditto->debug->render_popup($ditto, $ditto_base, $ditto_version, $ditto_params, $documentIDs, array("db"=>$dbFields,"tv"=>$TVs), $display, $templates, $orderBy, $start, $stop, $total,$filter,$resource);
    }
    if (isset($_GET["ditto_".$dittoID."debug"])) {
        switch ($_GET["ditto_".$dittoID."debug"]) {
            case "open" :
                exit($_SESSION["ditto_debug_$dittoID"]);
            break;
            case "save" :
                $ditto->debug->save($_SESSION["ditto_debug_$dittoID"],"ditto".strtolower($ditto_version)."_debug_doc".$modx->documentIdentifier.".html");
            break;
        }
    } else {
        $output = $ditto->debug->render_link($dittoID,$ditto_base).$output;
    }
}
//outerTpl by Dmi3yy
if ($outerTpl && $resource) { 
  if ($modx->getChunk($outerTpl) != "") {
                        $outerTpl = $modx->getChunk($outerTpl);
        } else if(substr($outerTpl, 0, 5) == "@CODE") {
                        $outerTpl = trim(substr($outerTpl, 6));
        } 
  $output = str_replace('[+ditto+]',$output,$outerTpl);
}

return ($save != 3) ? $output : "";
?>