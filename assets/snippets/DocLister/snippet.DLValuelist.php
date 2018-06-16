<?php
if (! defined('MODX_BASE_PATH')) {
    die('HACK???');
}

include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');

$p = &$modx->event->params;
if (! is_array($p)) {
    $p = array();
}

$titleField = APIhelpers::getkey($p, 'titleField', 'pagetitle');
$valueField = APIhelpers::getkey($p, 'valueField', 'id');

$p = array_merge(
    array(
        'idType'     => 'parents',
        'valueField' => $valueField,
        'titleField' => $titleField,
        'api'        => implode(",", array($titleField, $valueField)),
        'controller' => 'site_content',
        'debug'      => '0'
    ),
    $p
);

$json = $modx->runSnippet("DocLister", $p);
$json = jsonHelper::jsonDecode($json, array('assoc' => true));
$out = array();

$nopTitle = APIhelpers::getkey($p, 'addNopTitle');
if ($nopTitle) {
    $nopValue = APIhelpers::getkey($p, 'addNopValue');
    $out[] = $nopTitle . '==' . $nopValue;
}

foreach ($json as $el) {
    $out[] = APIhelpers::getkey($el, $titleField) . '==' . APIhelpers::getkey($el, $valueField);
}
if (APIhelpers::getkey($p, 'debug')) {
    $key = APIhelpers::getkey($p, 'sysKey', 'dl') . '.debug';
    $debugStack = $modx->getPlaceholder($key);
    if (! empty($debugStack)) {
        $modx->logEvent(0, 1, $debugStack, 'DocLister [DLValueList]');
    }
}

return implode("||", $out);
