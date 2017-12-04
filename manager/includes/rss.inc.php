<?php
 /*
 *  MODX Manager Home Page Implmentation by pixelchutes (www.pixelchutes.com)
 *  Based on kudo's kRSS Module v1.0.72
 *
 *  Written by: kudo, based on MagpieRSS
 *  Contact: kudo@kudolink.com
 *  Created: 11/05/2006 (November 5)
 *  For: MODX cms (modx.com)
 *  Name: kRSS
 *  Version (MODX Module): 1.0.72
 *  Version (Magpie): 0.72
 */

/* Configuration
---------------------------------------------- */
// Here you can set the urls to retrieve the RSS from. Simply add a $urls line following the numbering progress in the square brakets.

$urls['modx_news_content'] = $rss_url_news;
$urls['modx_security_notices_content'] = $rss_url_security;

// How many items per Feed?
$itemsNumber = '3';

/* End of configuration
NO NEED TO EDIT BELOW THIS LINE
---------------------------------------------- */

// include MagPieRSS
require_once(MODX_MANAGER_PATH.'media/rss/rss_fetch.inc');
// Convert relative path into absolute url
function rel2abs( $rel, $base ) {
	// parse base URL  and convert to local variables: $scheme, $host,  $path
	extract( parse_url( $base ) );
	if ( strpos( $rel,"//" ) === 0 ) {
		return $scheme . ':' . $rel;
	}
	// return if already absolute URL
	if ( parse_url( $rel, PHP_URL_SCHEME ) != '' ) {
		return $rel;
	}
	// queries and anchors
	if ( $rel[0] == '#' || $rel[0] == '?' ) {
		return $base . $rel;
	}
	// remove non-directory element from path
	$path = preg_replace( '#/[^/]*$#', '', $path );
	// destroy path if relative url points to root
	if ( $rel[0] ==  '/' ) {
		$path = '';
	}
	// dirty absolute URL
	$abs = $host . $path . "/" . $rel;
	// replace '//' or  '/./' or '/foo/../' with '/'
	$abs = preg_replace( "/(\/\.?\/)/", "/", $abs );
	$abs = preg_replace( "/\/(?!\.\.)[^\/]+\/\.\.\//", "/", $abs );
	// absolute URL is ready!
	return $scheme . '://' . $abs;
}
$feedData = array();

// create Feed
foreach ($urls as $section=>$url) {
	$output = '';
    $rss = @fetch_rss($url);
    if( !$rss ){
    	$feedData[$section] = 'Failed to retrieve ' . $url;
    	continue;
	}
    $output .= '<ul>';

    $items = array_slice($rss->items, 0, $itemsNumber);
    foreach ($items as $item) {
        $href = rel2abs($item['link'],'https://github.com');
        $title = $item['title'];
        $pubdate = $item['pubdate'];
        $pubdate = $modx->toDateFormat(strtotime($pubdate));
        $description = strip_tags($item['description']);
        if (strlen($description) > 199) {
            $description = substr($description, 0, 200);
            $description .= '...<br />Read <a href="'.$href.'" target="_blank">more</a>.';
        }
        $output .= '<li><a href="'.$href.'" target="_blank">'.$title.'</a> - <b>'.$pubdate.'</b><br />'.$description.'</li>';
    }

    $output .= '</ul>';
	$feedData[$section] = $output;
}
?>
