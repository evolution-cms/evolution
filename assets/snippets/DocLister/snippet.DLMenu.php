<?php
if (!isset($params['controller'])) {
    $params['controller'] = 'site_content_menu';
}
if (!isset($params['addWhereList'])) {
    $params['addWhereList'] = 'c.hidemenu = 0';
}
if (!isset($params['sortBy'])) {
    $params['sortBy'] = 'c.menuindex';
    if (isset($params['sortType1']) && $params['sortType1'] === 'doclist') {
        $params['sortBy1'] = 'c.id';
    }
}
if (!isset($params['sortDir'])) {
    $params['sortDir'] = 'ASC';
}
$params['depth'] = 0;
return $modx->runSnippet('DocLister', $params);
