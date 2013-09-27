<?php
/*
 * Ditto Extender: countDocs
 * Counts documents returned by Ditto
 * Copyright (c) 2009-2010 Aleksander Maksymiuk, <a href="http://setpro.net.pl/" target="_blank" rel="nofollow">http://setpro.net.pl/</a>
 
 * Parameters:
 *  no parameters required
 * Example: count documents within 3, 4, and 5 containers
 *  [[Ditto? &parents=`3,4,5` &extenders=`countDocs` &display=`1` &tpl=`@CODE:[+count+]`]]
 * Please notice in the above example 'display' parameter set to 1 -> it was done
 * because 'count' placeholder is set to each document within result set while we (most likely)
 * need this value to be returned only once
 */
 
$GLOBALS['docCounter'] = 0;
 
$filters['custom']['countDocs'] = array('id', 'countDocuments');
 
if (!function_exists('countDocuments')) {
    function countDocuments($resource) {
        # count documents
        $GLOBALS['docCounter']++;
        return 1;
    }
}
 
$placeholders['count'] = array('id', 'setCountPlaceholder');
 
if (!function_exists('setCountPlaceholder')) {
    function setCountPlaceholder($resource) {
        return $GLOBALS['docCounter'];
    }
}
 
?>