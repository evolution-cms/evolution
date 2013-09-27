<?php
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}
( isset($maxCrumbs) ) ? $maxCrumbs : $maxCrumbs = 100;
( isset($pathThruUnPub) ) ? $pathThruUnPub : $pathThruUnPub = 1;
( isset($respectHidemenu) ) ? (int)$respectHidemenu : $respectHidemenu = 1;
( isset($showCurrentCrumb) ) ? $showCurrentCrumb : $showCurrentCrumb = 1;
( isset($currentAsLink) ) ? $currentAsLink : $currentAsLink = 0;
( isset($linkTextField) ) ? $linkTextField : $linkTextField = 'menutitle,pagetitle,longtitle';
( isset($linkDescField) ) ? $linkDescField : $linkDescField = 'description,longtitle,pagetitle,menutitle';
( isset($showCrumbsAsLinks) ) ? $showCrumbsAsLinks : $showCrumbsAsLinks = 1;
( isset($templateSet) ) ? $templateSet : $templateSet = 'defaultString';
( isset($crumbGap) ) ? $crumbGap : $crumbGap = '...';
( isset($stylePrefix) ) ? $stylePrefix : $stylePrefix = 'B_';
( isset($showHomeCrumb) ) ? $showHomeCrumb : $showHomeCrumb = 1;
( isset($homeId) ) ? (int)$homeId : $homeId = $modx->config['site_start'];
( isset($homeCrumbTitle) ) ? $homeCrumbTitle : $homeCrumbTitle = '';
( isset($homeCrumbDescription) ) ? $homeCrumbDescription : $homeCrumbDescription = '';
( isset($showCrumbsAtHome) ) ? $showCrumbsAtHome : $showCrumbsAtHome = 0;
( isset($hideOn) ) ? $hideOn : $hideOn = '';
( isset($hideUnder) ) ? $hideUnder : $hideUnder = '';
( isset($stopIds) ) ? $stopIds : $stopIds = '';
( isset($ignoreIds) ) ? $ignoreids : $ignoreids = '';
( isset($crumbSeparator) ) ? $separator = $crumbSeparator : $separator = ' &raquo; ';
( isset($separator) ) ? $separator : $separator = ' &raquo; ';
$templates = array(
    'defaultString' => array(
        'crumb' => '[+crumb+]',
        'separator' => ' '.$separator.' ',
        'crumbContainer' => '<span class="[+crumbBoxClass+]">[+crumbs+]</span>',
        'lastCrumbWrapper' => '<span class="[+lastCrumbClass+]">[+lastCrumbSpanA+]</span>',
        'firstCrumbWrapper' => '<span class="[+firstCrumbClass+]">[+firstCrumbSpanA+]</span>'
    ),
    'defaultList' => array(
        'crumb' => '<li>[+crumb+]</li>',
        'separator' => '',
        'crumbContainer' => '<ul class="[+crumbBoxClass+]">[+crumbs+]</ul>',
        'lastCrumbWrapper' => '<span class="[+lastCrumbClass+]">[+lastCrumbSpanA+]</span>',
        'firstCrumbWrapper' => '<span class="[+firstCrumbClass+]">[+firstCrumbSpanA+]</span>'
    ),
);
// Return blank if necessary: on home page
if ( !$showCrumbsAtHome && $homeId == $modx->documentObject['id'] )
{
    return '';
}
// Return blank if necessary: specified pages
if ( $hideOn || $hideUnder )
{
    // Create array of hide pages
    $hideOn = str_replace(' ','',$hideOn);
    $hideOn = explode(',',$hideOn);

    // Get more hide pages based on parents if needed
    if ( $hideUnder )
    {
        $hiddenKids = array();
        // Get child pages to hide
        $hideKidsQuery = $modx->db->select('id',$modx->getFullTableName("site_content"),"parent IN ($hideUnder)");
        while ( $hideKid = $modx->db->getRow($hideKidsQuery) )
        {
            $hiddenKids[] = $hideKid['id'];
        }
        // Merge with hideOn pages
        $hideOn = array_merge($hideOn,$hiddenKids);
    }

    if ( in_array($modx->documentObject['id'],$hideOn) )
    {
        return '';
    }

}
// Initialize ------------------------------------------------------------------
// Put certain parameters in arrays
$stopIds = str_replace(' ','',$stopIds);
$stopIds = explode(',',$stopIds);
$linkTextField = str_replace(' ','',$linkTextField);
$linkTextField = explode(',',$linkTextField);
$linkDescField = str_replace(' ','',$linkDescField);
$linkDescField = explode(',',$linkDescField);
$ignoreIds = str_replace(' ','',$ignoreIds);
$ignoreIds = explode(',',$ignoreIds);

/* $crumbs
 * Crumb elements are: id, parent, pagetitle, longtitle, menutitle, description,
 * published, hidemenu
 */
$crumbs = array();
$parent = $modx->documentObject['parent'];
$output = '';
$maxCrumbs += ($showCurrentCrumb) ? 1 : 0;

// Replace || in snippet parameters that accept them with =
$crumbGap = str_replace('||','=',$crumbGap);

// Curent crumb ----------------------------------------------------------------

// Decide if current page is to be a crumb
if ( $showCurrentCrumb )
{
    $crumbs[] = array(
        'id' => $modx->documentObject['id'],
        'parent' => $modx->documentObject['parent'],
        'pagetitle' => $modx->documentObject['pagetitle'],
        'longtitle' => $modx->documentObject['longtitle'],
        'menutitle' => $modx->documentObject['menutitle'],
        'description' => $modx->documentObject['description']);
}

// Intermediate crumbs ---------------------------------------------------------


// Iterate through parents till we hit root or a reason to stop
$loopSafety = 0;
while ( $parent && $parent!=$modx->config['site_start'] && $loopSafety < 1000 )
{
    // Get next crumb
    $tempCrumb = $modx->getPageInfo($parent,0,"id,parent,pagetitle,longtitle,menutitle,description,published,hidemenu");

    // Check for include conditions & add to crumbs
    if (
        $tempCrumb['published'] &&
        ( !$tempCrumb['hidemenu'] || !$respectHidemenu ) &&
        !in_array($tempCrumb['id'],$ignoreIds)
    )
    {
        // Add crumb
        $crumbs[] = array(
        'id' => $tempCrumb['id'],
        'parent' => $tempCrumb['parent'],
        'pagetitle' => $tempCrumb['pagetitle'],
        'longtitle' => $tempCrumb['longtitle'],
        'menutitle' => $tempCrumb['menutitle'],
        'description' => $tempCrumb['description']);
    }

    // Check stop conditions
    if (
        in_array($tempCrumb['id'],$stopIds) ||  // Is one of the stop IDs
        !$tempCrumb['parent'] || // At root
        ( !$tempCrumb['published'] && !$pathThruUnPub ) // Unpublished
    )
    {
        // Halt making crumbs
        break;
    }

    // Reset parent
    $parent = $tempCrumb['parent'];

    // Increment loop safety
    $loopSafety++;
}

// Home crumb ------------------------------------------------------------------

if ( $showHomeCrumb && $homeId != $modx->documentObject['id'] && $homeCrumb = $modx->getPageInfo($homeId,0,"id,parent,pagetitle,longtitle,menutitle,description,published,hidemenu") )
{
    $crumbs[] = array(
    'id' => $homeCrumb['id'],
    'parent' => $homeCrumb['parent'],
    'pagetitle' => $homeCrumb['pagetitle'],
    'longtitle' => $homeCrumb['longtitle'],
    'menutitle' => $homeCrumb['menutitle'],
    'description' => $homeCrumb['description']);
}


// Process each crumb ----------------------------------------------------------
$pretemplateCrumbs = array();

foreach ( $crumbs as $c )
{

    // Skip if we've exceeded our crumb limit but we're waiting to get to home
    if ( count($pretemplateCrumbs) > $maxCrumbs && $c['id'] != $homeId )
    {
        continue;
    }

    $text = '';
    $title = '';
    $pretemplateCrumb = '';

    // Determine appropriate span/link text: home link specified
    if ( $c['id'] == $homeId && $homeCrumbTitle )
    {
        $text = $homeCrumbTitle;
    }
    else
    // Determine appropriate span/link text: home link not specified
    {
        for ($i = 0; !$text && $i < count($linkTextField); $i++)
        {
            if ( $c[$linkTextField[$i]] )
            {
                $text = $c[$linkTextField[$i]];
            }
        }
    }

    // Determine link/span class(es)
    if ( $c['id'] == $homeId )
    {
        $crumbClass = $stylePrefix.'homeCrumb';
    }
    else if ( $modx->documentObject['id'] == $c['id'] )
    {
        $crumbClass = $stylePrefix.'currentCrumb';
    }
    else
    {
        $crumbClass = $stylePrefix.'crumb';
    }

    // Make link
    if (
        ( $c['id'] != $modx->documentObject['id'] && $showCrumbsAsLinks ) ||
        ( $c['id'] == $modx->documentObject['id'] && $currentAsLink )
    )
    {
        // Determine appropriate title for link: home link specified
        if ( $c['id'] == $homeId && $homeCrumbDescription )
        {
            $title = htmlspecialchars($homeCrumbDescription);
        }
        else
        // Determine appropriate title for link: home link not specified
        {
            for ($i = 0; !$title && $i < count($linkDescField); $i++)
            {
                if ( $c[$linkDescField[$i]] )
                {
                    $title = htmlspecialchars($c[$linkDescField[$i]]);
                }
            }
        }


        $pretemplateCrumb .= '<a class="'.$crumbClass.'" href="'.($c['id'] == $modx->config['site_start'] ? $modx->config['base_url'] : $modx->makeUrl($c['id'])).'" title="'.$title.'">'.$text.'</a>';
    }
    else
    // Make a span instead of a link
    {
       $pretemplateCrumb .= '<span class="'.$crumbClass.'">'.$text.'</span>';
    }

    // Add crumb to pretemplate crumb array
    $pretemplateCrumbs[] = $pretemplateCrumb;

    // If we have hit the crumb limit
    if ( count($pretemplateCrumbs) == $maxCrumbs )
    {
        if ( count($crumbs) > ($maxCrumbs + (($showHomeCrumb) ? 1 : 0)) )
        {
            // Add gap
            $pretemplateCrumbs[] = '<span class="'.$stylePrefix.'hideCrumb'.'">'.$crumbGap.'</span>';
        }

        // Stop here if we're not looking for the home crumb
        if ( !$showHomeCrumb )
        {
            break;
        }
    }
}

// Put in correct order for output
$pretemplateCrumbs = array_reverse($pretemplateCrumbs);

// Wrap first/last spans
$pretemplateCrumbs[0] = str_replace(
    array('[+firstCrumbClass+]','[+firstCrumbSpanA+]'),
    array($stylePrefix.'firstCrumb',$pretemplateCrumbs[0]),
    $templates[$templateSet]['firstCrumbWrapper']
);
$pretemplateCrumbs[(count($pretemplateCrumbs)-1)] = str_replace(
    array('[+lastCrumbClass+]','[+lastCrumbSpanA+]'),
    array($stylePrefix.'lastCrumb',$pretemplateCrumbs[(count($pretemplateCrumbs)-1)]),
    $templates[$templateSet]['lastCrumbWrapper']
);

// Insert crumbs into crumb template
$processedCrumbs = array();
foreach ( $pretemplateCrumbs as $pc )
{
    $processedCrumbs[] = str_replace('[+crumb+]',$pc,$templates[$templateSet]['crumb']);
}

// Combine crumbs together into one string with separator
$processedCrumbs = implode($templates[$templateSet]['separator'],$processedCrumbs);

// Put crumbs into crumb container template
$container = str_replace(
    array('[+crumbBoxClass+]','[+crumbs+]'),
    array($stylePrefix.'crumbBox',$processedCrumbs),
    $templates[$templateSet]['crumbContainer']
    );

// Return crumbs
return $container;
?>