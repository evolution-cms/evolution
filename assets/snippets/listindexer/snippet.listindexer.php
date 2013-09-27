<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}
// added in 1.0.1: hidePrivate (hide items from unauthorized users)
//
// Derived from ListIndex 0.6j by jaredc@honeydewdesign.com
// Now supports Show In Menu
//
// This snippet was designed to be a VERY flexible way
// of showing what has been recently added. You can use
// this snippet to show news from one folder, or what has
// been recently added site wide. You can even show what's
// new in a 'section' (everything under a defined folder)!

// Configuration Settings

   // Set the following snippet defaults the way you would normally
   // use this snippet, then use snippet variables in other situations
   // to override the default behaviors.

   // GENERAL OPTIONS

   // $rootFolder [ NULL | string (comma separated page id's) ]
   // Decide which folder to pull recent documents from.
   // If you want to specify a few folders, comma separate them.
   // The default NULL will set current page as root. Using 0
   // would search site wide. Can be set with snippet parameter
   // $LIn_root like:
   // [[ListIndexer?LIn_root=3,6,88]] 
   $rootFolder = NULL;

   // $descendentDepth [ int ]
   // How many levels below the root folder(s) do you want to
   // include? Can be overridden in snippet call with LIn_depth:
   // [[ListIndexer?LIn_depth=2]]
   // Uncomment one of these common two or create your own:
   // $descendentDepth = 1; // just children of root folder(s)
   $descendentDepth = 0; // all decendents of root folder(s)

   // $seeThruUnpub [ true | false ]
   // When using descendents, do you want to consider files below
   // an unpublished (hidden) folder? Usually no. But you decide.
   // Snippet parameter: LIn_seeThru
   // [[ListIndexer?LIn_seeThru=1]]
   $seeThruUnpub = false;

   // $seeShowInMenu [ true | false ]
   // When using descendents, do you want to consider files flagged
   // to be hidden from the menus? Usually no. But you decide.
   // Snippet parameter: LIn_seeShowInMenu
   // [[ListIndexer?LIn_seeShowInMenu=1]]
   $seeShowInMenu = false;
   
   // $hidePrivate [ true | false ]
   // Hide items from users that don't have appropriate
   // rights to view. Usually true. But you decide.
   // Snippet parameter: LIn_hidePrivate
   // [[ListIndexer?LIn_hidePrivate=0]]
   $hidePrivate = true;

   // $mode [ 'short' | 'full' ]
   // Defines whether this list should be a full, paged
   // list of all documents, or a short list of the most
   // recent few (how many will be defined next). Can be
   // overridden in snippet call with $LIn_mode:
   // [[ListIndexer?LIn_mode=full]]
   $mode = 'short';
   
   // $sortBy [ 'alpha' | 'date' | 'menuindex' ]
   // The default date will sort by most recent items first, but
   // by using the 'alpha' option, and using full mode, you could
   // use this to create an index, or directory.
   // Settable with snippet call $LIn_sort:
   // [[ListIndexer?LIn_sort=alpha]]
   $sortBy = 'date';
   
   // $sortDir [ 'ASC' | 'DESC' ]
   // Sort direction ascending or descending. Is applied to whatever $sortBy
   // field you have chosen above. If left blank, menuindex and alpha will sort
   // ASC and date DESC.
   // $LIn_dir in snippet call:
   // [[ListIndexer?LIn_dir=ASC&LIn_sortBy=menuindex]]
   $sortDir = '';

   // WHAT TO DISPLAY

   // $defaultTitle [ string ]
   // If you want a default title for your list
   // you can declare it here. Or use an empty
   // string to leave this off. This can be overridden
   // in the snippet call with the variable $LIn_title:
   // [[ListIndexer?LIn_title=Some new title]]
   $defaultTitle = '';

   // $shortDesc [ true | false ]
   // Show the description on the short list, or not. Snippet
   // parameter $LIn_sDesc:
   // [[ListIndexer?LIn_sDesc=0]]
   $shortDesc = true;

   // $fullDesc [ true | false ]
   // Show the description on the full list, or not. Snippet
   // parameter $LIn_fDesc:
   // [[ListIndexer?LIn_fDesc=0]]
   $fullDesc = true;

   // $linkToIndex [ int ]
   // If you have a page set up as an 'index' for all the 
   // documents in this list, you can link to it by specifying 
   // its id- can also be set in snippet call with LIn_link:
   // [[ListIndexer?LIn_link=8]]
   // The default 0 will eliminate this link
   $linkToIndex = 0;

   // $indexText [ string ]
   // If you want a link to an index (probably a page running this
   // snippet in "full" mode), you can declare what you want that
   // link to say here. Or in the snippet call with LIn_indexText:
   // [[ListIndexer?LIn_indexText=Financial News Index]]
   $indexText = 'Index';

   // $showCreationDate [ true | false ]
   // Decide to include date of creation in output or not. From
   // snippet call $LIn_showDate use 1 (true) or 0 (false)
   // [[ListIndexer?LIn_showDate=1]]
   $showCreationDate = true;

   // $dateFormat [ string ]
   // Used to define how date will be displayed (if using date)
   // Y = 4 digit year     y = 2 digit year
   // M = Jan - Dec        m = 01 - 12
   // D = Sun - Sat        d = 01 -31
   // Other standard PHP characters may be used
   $dateFormat = 'Y.m.d';

   // $shortQty [ int ]
   // Number of entries to list in the short list. Can be
   // overridden in snippet call with $LIn_sQty:
   //[[ListIndexer?LIn_sQty=3]]
   $shortQty = 3;

   // PAGING

   // $fullQty [ int ]
   // Number of entries PER PAGE to list in the full list
   // Can be overridden in snippet call with $LIn_fQty:
   // [[ListIndexer?LIn_fQty=20]]
   // To show all set to 0 here or in snippet call
   $fullQty = 10;
   
   // $pageSeparator [ string ]
   // What you want your page number links to be separated by.
   // You NEED to include spaces if you want them. They are NOT
   // created automatically to facilitate styling ability.
   // For instance, " | " will render links like:
   // 1 | 2 | 3 | 4
   $pageSeparator = " | ";
   
   // $pgPosition [ 'top' | 'bottom' | 'both']
   // Pick where you want your pagination links to appear.
   $pgPosition = 'both';

   // PERFORMANCE

   // $useFastUrls [ true | false ]
   // IMPORTANT- using fast urls will reduce database queries
   // and improve performance WHEN IN FULL MODE ONLY and 
   // should NOT be used when multiple instances of this snippet
   // appear on the same page. With snippet call LIn_fast use 1
   // (true) or 0 (false)
   // [[ListIndexer?LIn_fast=0]]
   $useFastUrls = false;

   // $newLinesForLists [ true | false ]
   // Depending on how you want to style your list, you may
   // or may not want your <li>s on new lines. Generally, if you
   // are displaying then inline (horizontal, you do not want new
   // lines, but standard vertical block styling you do. This is
   // for IE, real browsers don't care.
   $newLinesForLists = true;

// Styles
//
// The following are the styles included in this snippet. It is up
// to you to include these styles in your stylesheet to get them to
// look the way you want.

   // div.LIn_title {}          List title div
   // ul.LIn_fullMode {}        UL class
   // ul.LIn_shortMode {}       UL class
   // span.LIn_date {}          Span surrounding pub/created date
   // span.LIn_desc {}          Span surrounding description
   // div.LIn_pagination        Div surrounding pagination links
   // span.LIn_currentPage {}   Span surrounding current page of
   //                           pagination (which wouldn't be css-able
   //                           by virtue of its <a> tag)


// **********************************************************************
// END CONFIG SETTINGS
// THE REST SHOULD TAKE CARE OF ITSELF
// **********************************************************************

// Take care of IE list issue
$ie = ($newLinesForLists)? "\n" : '' ;

// Use snippet call defined variables if set
$activeTitle = (isset($LIn_title))? $LIn_title : $defaultTitle ;
$mode = (isset($LIn_mode))? $LIn_mode : $mode ;
$descendentDepth = (isset($LIn_depth))? $LIn_depth : $descendentDepth ;
$seeThruUnpub = (isset($LIn_seeThru))? $LIn_seeThru : $seeThruUnpub ;
$seeShowInMenu = (isset($LIn_seeShowInMenu))? $LIn_seeShowInMenu : $seeShowInMenu ;
$hidePrivate = (isset($LIn_hidePrivate))? $LIn_hidePrivate : $hidePrivate;
$linkToIndex = (isset($LIn_link))? $LIn_link : $linkToIndex ;
$rootFolder = (isset($LIn_root))? $LIn_root : $rootFolder ;
$shortQty = (isset($LIn_sQty))? $LIn_sQty : $shortQty ;
$fullQty = (isset($LIn_fQty))? $LIn_fQty : $fullQty ;
$showCreationDate = (isset($LIn_showDate))? $LIn_showDate : $showCreationDate ;
$indexText = (isset($LIn_indexText))? $LIn_indexText : $indexText ;
$useFastUrls = (isset($LIn_fast))? $LIn_fast : $useFastUrls ;
$sortBy = (isset($LIn_sort))? $LIn_sort : $sortBy;
$shortDesc = (isset($LIn_sDesc))? $LIn_sDesc : $shortDesc ;
$fullDesc = (isset($LIn_fDesc))? $LIn_fDesc : $fullDesc ;
$sortDir = (isset($LIn_dir))? $LIn_dir : $sortDir ;
if ($sortDir == '') $sortDir = ($sortBy == 'date')? 'DESC' : 'ASC' ;


// Make useful variable shortcut for the content table
//$tbl = $modx->dbConfig['dbase'] . "." . $modx->dbConfig['table_prefix'] . "site_content";
$tblsc = $modx->getFullTableName("site_content");
$tbldg = $modx->getFullTableName("document_groups");

// Initialize output
$output = '';

// ---------------------------------------------------
// ---------------------------------------------------
// Query db for parent folders, or not. First check to
// see if a querystring cheat has been provided- this
// should speed things up considerably when using this
// in full mode. (a.k.a. fastUrls)
// ---------------------------------------------------
// ---------------------------------------------------
$inFolder= isset($_GET['LIn_f'])? $_GET['LIn_f']: 0;
if ((!$inFolder && $useFastUrls) || !$useFastUrls ){
  // Only run all the database queries if we don't already
  // know the folders AND fastUrls are desired.

  // ---------------------------------------------------
  // Seed list of viable parents
  // ---------------------------------------------------

  if ($rootFolder == NULL){
    $rootFolder = $modx->documentIdentifier;
  }
  // Set root level parent array
  $seedArray = explode(',',$rootFolder);
  $parentsArray = array();
  foreach($seedArray AS $seed){
    $parentsArray['level_0'][] = $seed;
  }

  // ---------------------------------------------------
  // Make array of all allowed parents
  // ---------------------------------------------------

  // Process valid parents
  $levelCounter = 1;

  while (((count($parentsArray) < $descendentDepth) || ($descendentDepth == 0)) && ($levelCounter <= count($parentsArray)) && ($levelCounter < 10)){

    // Find all decendant parents for this level
    $pLevel = 'level_'.($levelCounter - 1);
    $tempLevelArray = $parentsArray[$pLevel];

    foreach($tempLevelArray AS $p){

      // Get children who are parents (isfolder = 1)
      $validParentSql = "";
      $validParentSql .= "SELECT id FROM $tblsc sc WHERE ";
      $validParentSql .= "isfolder = 1 AND parent = $p ";
      $validParentSql .= "AND sc.deleted=0 ";
      $validParentSql .= ($seeThruUnpub)? ";" : "AND sc.published = 1;";

      // Run statement
      $rsTempParents = $modx->db->query($validParentSql);
      // Get number of results
      $countTempParents = $modx->db->getRecordCount($rsTempParents);

      // If there are results, put them in an array
      $tempValidArray = false;
      if ($countTempParents){
        for ($i=0;$i<$countTempParents;$i++){
          $tempId = $modx->db->getRow($rsTempParents);
          $tempValidArray[] = $tempId['id'];
        } // end while
      } // end if

    // populate next level of array 
    if ($tempValidArray){
      foreach($tempValidArray AS $kid){
        $kidLevel = 'level_'.$levelCounter;
        $parentsArray[$kidLevel][] = $kid;
      } // end foreach

    } // end if
    } // end foreach

    // Do next level
    $levelCounter++;

  } // end while

  // Finalize list of parents
  $validParents = '';
  foreach ($parentsArray AS $level){
    foreach ($level AS $validP){
      $validParents .= $validP . ',';
    }
  }

  // Remove trailing comma
  $validParents = substr($validParents,0,strlen($validParents)-1);

} else {
  $validParents = $_GET['LIn_f'];
}

// ---------------------------------------------------
// Make appropriate SQL statement to pull recent items
// ---------------------------------------------------

// get document groups for current user
if($docgrp = $modx->getUserDocGroups()) $docgrp = implode(",",$docgrp);

$access = " (".($modx->isFrontend() ? "sc.privateweb=0":"1='".$_SESSION['mgrRole']."' OR sc.privatemgr=0").
          (!$docgrp ? "":" OR dg.document_group IN ($docgrp)").") AND ";

// Initialize
$recentSql = "";
$recentSql .= "SELECT sc.id, pagetitle, description";
// Include pub_date or createdon date if date is desired
$recentSql .= ($showCreationDate)? ", IF(pub_date > 0, pub_date, createdon) AS pubDate ": " " ;
$recentSql .= "FROM $tblsc sc LEFT JOIN $tbldg dg on dg.document = sc.id ";
$recentSql .= "WHERE ";
$recentSql .= ($hidePrivate)? $access:"";
// Look everywhere, or just under valid parents
$recentSql .= (($rootFolder == 0) && $seeThruUnpub && ($descendentDepth == 0))? "" : "parent IN ($validParents) AND " ;
// Published
$recentSql .= "sc.published = 1 ";
// Show In Menu
$recentSql .= ($seeShowInMenu)? " " : " AND sc.hidemenu=0 " ;
// Not deleted
$recentSql .= "AND sc.deleted=0 ";
// Choose sort method
switch ($sortBy){
  case 'alpha':
    $recentSql .= "ORDER BY pagetitle ";
    break;
  case 'menuindex':
    $recentSql .= "ORDER BY menuindex ";
    break;
  default:
    $recentSql .= "ORDER BY IF(pub_date>0, pub_date, createdon) ";
    break;
}
// Provide a sort direction
$recentSql .= $sortDir;

// If this is a short list, just pull a limited number
$recentSql .= ($mode == 'short')? " LIMIT $shortQty;" : ";" ;

// Run statement
$rsRecent = $modx->db->query($recentSql);
// Count records
$recentLimit = $modx->db->getRecordCount($rsRecent);

// ---------------------------------------------------
// Generate pagination string if needed
// ---------------------------------------------------
$offsetParam = isset($_GET['LIn_o'])? $_GET['LIn_o']: 0;
$offset = ($offsetParam && ($mode == 'full'))? $offsetParam : 0 ;
$pagination = '';

// Don't bother unless there are enough records to justify it
if ( ($mode == 'full') && ($recentLimit > $fullQty) && ($fullQty) ){
  $fullUrl = $_SERVER['REQUEST_URI'];
  $urlPieces = parse_url($fullUrl);
  $urlPath = $urlPieces['path'];
  $otherQs = '';

  if ($urlPieces['query']){
    foreach($_GET AS $qsKey=>$qsValue){
    if (($qsKey != 'LIn_o') && ($qsKey != 'LIn_f')){
      $otherQs .= '&'.$qsKey.'='.$qsValue;
    }
  }
  } 
  
  $fastUrl = $urlPath.'?LIn_f='.$validParents.$otherQs;

  // Determine number of pages needed to show results
  $totalPages = ceil($recentLimit/$fullQty);
  
  // Make links
  for ($j = 0 ; $j < $totalPages; $j++){
    // only include links to OTHER pages, not current page
    if($offset == $j*$fullQty){
    $pagination .= '<span class="LIn_currentPage">'.($j+1) .'</span>';
  } else {
      $pagination .= '<a href="'.$fastUrl.'&LIn_o='.($j*$fullQty).'" title="'.($j+1).'">'.($j+1) .'</a>';
  }
  if ($j < $totalPages-1){
    $pagination .= $pageSeparator;
  }
  }
  
  // Make final pagination link set in it's own div
  $pagination = '<div class="LIn_pagination">'."\n".$pagination."\n</div>\n";
  
}


// ---------------------------------------------------
// Create title if wanted
// ---------------------------------------------------

if ($activeTitle){
  $output .= '<div class="LIn_title">'.$activeTitle.'</div>'."\n";
}

// ---------------------------------------------------
// Create list of recent items
// ---------------------------------------------------

// Include pagination
$output .= ($pgPosition == 'top' || $pgPosition == 'both')? $pagination : '' ;

$output .= '<ul class="LIn_'.$mode.'Mode">' . $ie;

$recentCounter = $offset;
if ($mode == 'short') {
  $recentCounterLimit = min($shortQty,$recentLimit);
} else {
  $recentCounterLimit = ($fullQty)? min(($fullQty+$offset),$recentLimit) : $recentLimit ;
}

while (($recentCounter < $recentCounterLimit) && $rsRecent && ($recentLimit > 0)){
  mysql_data_seek($rsRecent,$recentCounter);
  $recentRecord = $modx->db->getRow($rsRecent);
  $output .= '<li>';
  // Link to page
  $output .= '<a href="[~'.$recentRecord['id'].'~]" title="'.strip_tags($recentRecord['pagetitle']).'">'.$recentRecord['pagetitle'].'</a> ';
  // Date if desired
  if ($showCreationDate){
    $output .= '<span class="LIn_date">'.date($dateFormat,$recentRecord['pubDate']).'</span> ';
  }
  // Description if desired
  if ((($mode == 'short') && ($shortDesc)) || (($mode == 'full') && ($fullDesc))){
   $output .= '<span class="LIn_desc">'.$recentRecord['description'].'</span>';
  }
  // wrap it up
  $output .= '</li>' . $ie;
  $recentCounter ++;
}

$output .= '</ul>' . $ie;

$output .= ($pgPosition == 'bottom' || $pgPosition == 'both')? $pagination : '' ;

// ---------------------------------------------------
// Link to index
// ---------------------------------------------------

if ($linkToIndex) {

  $output .= '<div class="LIn_index">';
  $output .= '<a href="[~'.$linkToIndex.'~]" title="'.$indexText.'">'.$indexText.'</a>';
  $output .= '</div>';

}

// ---------------------------------------------------
// Send to browser
// ---------------------------------------------------

return $output;
?>