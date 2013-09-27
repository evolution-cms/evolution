<?php
/*
 * Ditto Extender: glossaryFilter - advanced filtering of documents
 * Released under the terms of General Public License
 * Copyright (c) 2010 Aleksander Maksymiuk, http://setpro.pl/

 * Last modified: 2010-08-30, 23:53

 * Parameters:
 *   filterVar - optional; documents attribute or template variable used for matching (default: pagetitle)
 *      notice: you should rather avoid to use content field for this purpose
 *   filterMode - optional; specifies how matching should be performed
 *      possible values are:
 *         class (default) - match with RegExp /^[...]/i where ... is substituted with the value of filterBy parameter
 *         custom - match with full-fledged RegExp provided with filterBy parameter
 *         chunk - match with full-fledged RegExp privided with a chunk whose name is kept within filterBy parameter
 *   filterBy - optional, however, you SHOULD specify at least this one parameter, otherwise extender does no filtering
 *   forceUTF8 - optional; specifies whether filterBy value should be treated as UTF-8 string or not
 *      it affects extender's behavior while running in class mode 
 *      possible values are: 0, 1 (default: 0)
 * Example:
 *   fetch documents within 3, 4, and 5 containers whose pagetitles start with A, B, C, or D
 *      [[Ditto? &parents=`3,4,5` &depth=`0` &display=`all` &extenders=`glossaryFilter` &filterBy=`A-D` &tpl=`...` ...]]
 *   fetch documents within 3, 4, and 5 containers whose aliases do not start with numeric
 *      [[Ditto? &parents=`3,4,5` &depth=`0` &display=`all` &extenders=`glossaryFilter` &filterVar=`alias` &filterBy=`^0-9` &tpl=`...` ...]]
 * Full article, sample implementations, downloading latest version:
 *   http://setpro.pl/software/ditto-stuff/glossary-extender
 */

$GLOBALS['filterVar'] = isset($filterVar) ? $filterVar : 'pagetitle';
$GLOBALS['filterMode'] = isset($filterMode) && preg_match('/^(chunk|class|custom)$/', $filterMode) ? $filterMode : 'class';
$GLOBALS['filterBy'] = isset($filterBy) ? $filterBy : '';
$GLOBALS['forceUTF8'] = isset($forceUTF8) ? $forceUTF8 : 0;
if ($GLOBALS['filterMode'] == 'chunk') {
    $GLOBALS['filterBy'] = $modx->getChunk($GLOBALS['filterBy']);
}
$GLOBALS['forceUTF8'] = $GLOBALS['forceUTF8'] ? 'u' : '';

$filters['custom']['glossaryFilter'] = array($GLOBALS['filterVar'], 'glossaryFilter');

if (!function_exists('glossaryFilter')) {
	function glossaryFilter($resource) {
		if (!$GLOBALS['filterBy']) {
			# do nothing (simply leave document within final dataset)
			return 1;
		}
        $regExpBegin = preg_match('/^(chunk|custom)$/', $GLOBALS['filterMode']) ? '' : '/^[';
        $regExpEnd = preg_match('/^(chunk|custom)$/', $GLOBALS['filterMode']) ? '' : ']/i' . $GLOBALS['forceUTF8'];
        # do filtering
        return preg_match($regExpBegin . $GLOBALS['filterBy'] . $regExpEnd, $resource[$GLOBALS['filterVar']]) ? 1 : 0;
	}
}

?>