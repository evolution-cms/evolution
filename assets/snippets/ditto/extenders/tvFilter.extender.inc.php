<?php
/*
 * Ditto Extender: tvFilter
 * Filters out documents by TV assignment
 * Copyright (c) 2009-2010 Aleksander Maksymiuk, http://setpro.net.pl/
 * Parameters:
 *   tvFilterBy - TV name to filter by (required)
 *   tvFilterMode
 *   	1: excludes document if it has the requested TV assigned (default)
 *   	0: excludes document if it has not the requested TV assigned
 * Example: display all documents within 3, 4, and 5 containers that are not linked with dummy TV
 * 		[[Ditto? &parents=`3,4,5` &display=`all` &tpl=`...` &extenders=`tvFilter` &tvFilerBy=`dummy` tvFilterMode=`0`]]
 */

$GLOBALS['tvFilterBy'] = isset($tvFilterBy) ? $tvFilterBy : '';
$GLOBALS['tvFilterMode'] = isset($tvFilterMode) ? $tvFilterMode : 1;

$filters['custom']['tvFilter'] = array('template', 'tvFilter');

if (!function_exists('tvFilter')) {
	function tvFilter($resource) {
		global $modx;
		if (!$GLOBALS['tvFilterBy']) {
			# do nothing (leave document within result set)
			return 1;
		}
		$sql = "SELECT " . $modx->getFullTableName('site_tmplvars') . ".id " .
		    "FROM " . $modx->getFullTableName('site_tmplvars') . " " .
			"INNER JOIN " . $modx->getFullTableName('site_tmplvar_templates') . " " .
				"ON " . $modx->getFullTableName('site_tmplvars') . ".id = " . $modx->getFullTableName('site_tmplvar_templates') . ".tmplvarid " .
			"WHERE (" . $modx->getFullTableName('site_tmplvars') . ".name = '" . $GLOBALS['tvFilterBy'] . "') AND (" . $modx->getFullTableName('site_tmplvar_templates') . ".templateid = '" . $resource['template'] . "')";
		return $GLOBALS['tvFilterMode'] ? !mysql_numrows(mysql_query($sql)) : mysql_numrows(mysql_query($sql));
	}
}

?>