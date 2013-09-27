<?php

// Default configuration file - AjaxSearch 1.9.3
// Keep care all these values are required

$dcfg['version'] = '1.9.3';
$dcfg['config'] = '';
$dcfg['debug'] = 0;
$dcfg['timeLimit'] = 60;
$dcfg['language'] = $modx->config['manager_language'];
$dcfg['asId'] = '';
$dcfg['ajaxSearch'] = 1;
$dcfg['advSearch'] = 'oneword';
$dcfg['whereSearch'] = 'content|tv';
$dcfg['sites'] = '';
$dcfg['subSearch'] = '';
$dcfg['category'] = '';
$dcfg['display'] = 'unmixed';
$dcfg['tags'] = '';
$dcfg['init'] = 'none';
$dcfg['withTvs'] = '';
$dcfg['tvPhx'] = '';
$dcfg['order'] = 'publishedon,pagetitle';
$dcfg['rank'] = '';
$dcfg['maxWords'] = 5;
$dcfg['minChars'] = 3;
$dcfg['showInputForm'] = 1;
$dcfg['showIntro'] = 1;
$dcfg['resultsPage'] = 0;
$dcfg['grabMax'] = 6;
$dcfg['extract'] = '1:content,description,introtext,tv_value';
$dcfg['extractLength'] = 200;
$dcfg['extractEllips'] = '...';
$dcfg['extractSeparator'] = '<br />';
$dcfg['formatDate'] = 'd/m/y : H:i:s';
$dcfg['highlightResult'] = 1;
$dcfg['pagingType'] = 1;
$dcfg['showPagingAlways'] = 0;
$dcfg['pageLinkSeparator'] = ' | ';
$dcfg['landingPage'] = false;
$dcfg['showResults'] = true;
$dcfg['parents'] = '';
$dcfg['documents'] = '';
$dcfg['depth'] = 10;
$dcfg['hideMenu'] = 2;
$dcfg['hideLink'] = 0;
$dcfg['filter'] = '';
$dcfg['output'] = 0;
$dcfg['tplInput'] = '@FILE:' . AS_SPATH . 'templates/input.tpl.html';
$dcfg['tplResults'] = '@FILE:' . AS_SPATH . 'templates/results.tpl.html';
$dcfg['tplGrpResult'] = '@FILE:' . AS_SPATH . 'templates/grpResult.tpl.html';
$dcfg['tplResult'] = '@FILE:' . AS_SPATH . 'templates/result.tpl.html';
$dcfg['tplPaging0'] = '@FILE:' . AS_SPATH . 'templates/paging0.tpl.html';
$dcfg['tplPaging1'] = '@FILE:' . AS_SPATH . 'templates/paging1.tpl.html';
$dcfg['tplComment'] = '@FILE:' . AS_SPATH . 'templates/comment.tpl.html';
$dcfg['stripInput'] = 'defaultStripInput';
$dcfg['stripOutput'] = 'defaultStripOutput';
$dcfg['searchWordList'] = '';
$dcfg['breadcrumbs'] = '';
$dcfg['clearDefault'] = 0;
$dcfg['jsClearDefault'] = AS_SPATH . 'js/clearDefault/clearDefault.js';
$dcfg['mbstring'] = 1;
$dcfg['asLog'] = '0:0:200';

$dcfg['liveSearch'] = 0;
$dcfg['ajaxMax'] = 5;
$dcfg['showMoreResults'] = 0;
$dcfg['moreResultsPage'] = 0;
$dcfg['opacity'] = 1.;
$dcfg['tplAjaxResults'] = '@FILE:' . AS_SPATH . 'templates/ajaxResults.tpl.html';
$dcfg['tplAjaxGrpResult'] = '@FILE:' . AS_SPATH . 'templates/ajaxGrpResult.tpl.html';
$dcfg['tplAjaxResult'] = '@FILE:' . AS_SPATH . 'templates/ajaxResult.tpl.html';
$dcfg['tplPaging2'] = '@FILE:' . AS_SPATH . 'templates/paging2.tpl.html';
$dcfg['jscript'] = 'mootools';
$dcfg['addJscript'] = 1;
$dcfg['jsMooTools'] = MODX_MANAGER_URL.'/media/script/mootools/mootools.js';
$dcfg['jsMooTools2'] = AS_SPATH . 'js/mootools2/mootools-1.2.4.js';
$dcfg['jsJquery'] = 'assets/js/jquery.min.js';

// For a global parameter initialisation use the following syntax $__param = 'value';
// To overwrite parameter snippet call use $param = 'value';

?>