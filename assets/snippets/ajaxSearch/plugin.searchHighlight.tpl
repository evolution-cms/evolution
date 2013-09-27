/**
 * Search Highlight
 * 
 * Used with AjaxSearch to show search terms highlighted on page linked from search results
 *
 * @category 	plugin
 * @version 	1.5
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @internal	@properties
 * @internal	@events OnWebPagePrerender 
 * @internal	@modx_category Search
 * @internal    @legacy_names Search Highlighting
 */

/*
  ------------------------------------------------------------------------
  Plugin: Search_Highlight v1.5
  ------------------------------------------------------------------------
  Changes:
  18/03/10 - Remove possibility of XSS attempts being passed in the URL
           - look-behind assertion improved
  29/03/09 - Removed urldecode calls;
           - Added check for magic quotes - if set, remove slashes
           - Highlights terms searched for when target is a HTML entity
  18/07/08 - advSearch parameter and pcre modifier added
  10/02/08 - Strip_tags added to avoid sql injection and XSS. Use of $_REQUEST
  01/03/07 - Added fies/updates from forum from users mikkelwe/identity
  (better highlight replacement, additional div around term/removal message)
  ------------------------------------------------------------------------
  Description: When a user clicks on the link from the AjaxSearch results
    the target page will have the terms highlighted.
  ------------------------------------------------------------------------
  Created By:  Susan Ottwell (sottwell@sottwell.com)
               Kyle Jaebker (kjaebker@muddydogpaws.com)

  Refactored by Coroico (www.evo.wangba.fr) and TS
  ------------------------------------------------------------------------
  Based off the the code by Susan Ottwell (www.sottwell.com)
    http://modxcms.com/forums/index.php/topic,1237.0.html
  ------------------------------------------------------------------------
  CSS:
    The classes used for the highlighting are the same as the AjaxSearch
  ------------------------------------------------------------------------
  Notes:
    To add a link to remove the highlighting and to show the searchterms
    put the following on your page where you would like this to appear:

      <!--search_terms-->

    Example output for this:

      Search Terms: the, template
      Remove Highlighting

    Set the following variables to change the text:

      $termText - the text before the search terms
      $removeText - the text for the remove link
  ------------------------------------------------------------------------
*/
global $database_connection_charset;
// Conversion code name between html page character encoding and Mysql character encoding
// Some others conversions should be added if needed. Otherwise Page charset = Database charset
$pageCharset = array(
  'utf8' => 'UTF-8',
  'latin1' => 'ISO-8859-1',
  'latin2' => 'ISO-8859-2'
);

if (isset($_REQUEST['searched']) && isset($_REQUEST['highlight'])) {

  // Set these to customize the text for the highlighting key
  // --------------------------------------------------------
     $termText = '<div class="searchTerms">Search Terms: ';
     $removeText = 'Remove Highlighting';
  // --------------------------------------------------------

  $highlightText = $termText;
  $advsearch = 'oneword';

  $dbCharset = $database_connection_charset;
  $pgCharset = array_key_exists($dbCharset,$pageCharset) ? $pageCharset[$dbCharset] : $dbCharset;

  // magic quotes check
  if (get_magic_quotes_gpc()){
    $searched = strip_tags(stripslashes($_REQUEST['searched']));
    $highlight = strip_tags(stripslashes($_REQUEST['highlight']));
    if (isset($_REQUEST['advsearch'])) $advsearch = strip_tags(stripslashes($_REQUEST['advsearch']));
  }
  else {
    $searched = strip_tags($_REQUEST['searched']);
    $highlight = strip_tags($_REQUEST['highlight']);
    if (isset($_REQUEST['advsearch'])) $advsearch = strip_tags($_REQUEST['advsearch']);
  }

  if ($advsearch != 'nowords') {

    $searchArray = array();
    if ($advsearch == 'exactphrase') $searchArray[0] = $searched;
    else $searchArray = explode(' ', $searched);

    $searchArray = array_unique($searchArray);
    $nbterms = count($searchArray);
    $searchTerms = array();
    for($i=0;$i<$nbterms;$i++){
      // Consider all possible combinations
      $word_ents = array();
      $word_ents[] = $searchArray[$i];
      $word_ents[] = htmlentities($searchArray[$i], ENT_NOQUOTES, $pgCharset);
      $word_ents[] = htmlentities($searchArray[$i], ENT_COMPAT, $pgCharset);
      $word_ents[] = htmlentities($searchArray[$i], ENT_QUOTES, $pgCharset);
      // Avoid duplication
      $word_ents = array_unique($word_ents);
      foreach($word_ents as $word) $searchTerms[]= array('term' => $word, 'class' => $i+1);
    }

    $output = $modx->documentOutput; // get the parsed document
    $body = explode("<body", $output); // break out the head

    $highlightClass = explode(' ',$highlight); // break out the highlight classes
    /* remove possibility of XSS attempts being passed in URL */
    foreach ($highlightClass as $key => $value) {
       $highlightClass[$key] = preg_match('/[^A-Za-z0-9_-]/ms', $value) == 1 ? '' : $value;
    }

    $pcreModifier = ($pgCharset == 'UTF-8') ? 'iu' : 'i';
    $lookBehind = '/(?<!&|&[\w#]|&[\w#]\w|&[\w#]\w\w|&[\w#]\w\w\w|&[\w#]\w\w\w\w|&[\w#]\w\w\w\w\w)';  // avoid a match with a html entity
    $lookAhead = '(?=[^>]*<)/'; // avoid a match with a html tag

    $nbterms = count($searchTerms);
    for($i=0;$i<$nbterms;$i++){
      $word = $searchTerms[$i]['term'];
      $class = $highlightClass[0].' '.$highlightClass[$searchTerms[$i]['class']];

      $highlightText .= ($i > 0) ? ', ' : '';
      $highlightText .= '<span class="'.$class.'">'.$word.'</span>';

      $pattern = $lookBehind . preg_quote($word, '/') . $lookAhead . $pcreModifier;
      $replacement = '<span class="' . $class . '">${0}</span>';
      $body[1] = preg_replace($pattern, $replacement, $body[1]);
    }

    $output = implode("<body", $body);

    $removeUrl = $modx->makeUrl($modx->documentIdentifier);
    $highlightText .= '<br /><a href="'.$removeUrl.'" class="ajaxSearch_removeHighlight">'.$removeText.'</a></div>';

    $output = str_replace('<!--search_terms-->',$highlightText,$output);
    $modx->documentOutput = $output;
  }
}