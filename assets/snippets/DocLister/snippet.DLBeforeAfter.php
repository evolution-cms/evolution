<?php
include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');
if (!function_exists('validateMonth')) {

    /**
     * @param $val
     * @return bool
     */
    function validateDate($val)
    {
        $flag = false;
        if (is_string($val)) {
            $val = explode("-", $val, 3);
            $flag = (count($val) == 3 && is_array($val) && strlen($val[2]) == 2 && strlen($val[1]) == 2 && strlen($val[0]) == 4); //Валидация содержимого массива
            $flag = ($flag && (int)$val[2] > 0 && (int)$val[2] <= 31); //Валидация дня
            $flag = ($flag && (int)$val[1] > 0 && (int)$val[1] <= 12); //Валидация месяца
            $flag = ($flag && (int)$val[0] > 1900 && (int)$val[0] <= 2100); //Валидация года
        }

        return $flag;
    }

}

if (!function_exists('buildUrl')) {

    /**
     * @param $url
     * @param int $start
     * @return array|string
     */
    function buildUrl($url, $start = 0)
    {
        $params = parse_url($url, PHP_URL_QUERY);
        parse_str(html_entity_decode($params), $params);
        $requestName = 'start';
        if ($requestName != '' && is_array($params)) {
            $params = array_merge($params, array($requestName => null));
            if (!empty($start)) {
                $params[$requestName] = $start;
            }
            $q = http_build_query($params);
            $url = explode("?", $url, 2);
            $url = $url[0];
            if (!empty($q)) {
                $url .= "?" . $q;
            }
        }

        return $url;
    }

}

$params = is_array($modx->event->params) ? $modx->event->params : array();

$out = $beforePage = $afterPage = '';

$display = (int)APIHelpers::getkey($params, 'display', '10');

$dateSource = APIHelpers::getkey($params, 'dateSource', 'content');
$dateField = APIHelpers::getkey($params, 'dateField', 'if(pub_date=0,createdon,pub_date)');

$tmp = date("Y-m-d H:i:s");
$currentDay = APIHelpers::getkey($params, 'currentDay', $tmp); // Текущий день
if (!validateDate($currentDay)) {
    $currentDay = $tmp;
}

$start = (int)APIHelpers::getkey($_GET, 'start', '0');
$elements = array(
    'offset' => $start
);
//Если положительное значение, то нужы события предстоящие. Если отрицательное - прошедшее
$rule = ($start >= 0) ? 'after' : 'before';
$noRule = ($start >= 0) ? 'before' : 'after';
if ($start < 0) {
    $start = abs($start) > $display ? ($start + $display) : 0;
}
$d = $modx->getDatabase()->escape($currentDay);
if ($dateSource == 'tv') {
    $params['tvSortType'] = 'TVDATETIME';
    $query = array(
        'after'  => "STR_TO_DATE(`dltv_" . $dateField . "_1`.`value`,'%d-%m-%Y %H:%i:%s') >= '" . $d . "'",
        'before' => "STR_TO_DATE(`dltv_" . $dateField . "_1`.`value`,'%d-%m-%Y %H:%i:%s') < '" . $d . "'"
    );
} else {
    $query = array(
        'after'  => "FROM_UNIXTIME(" . $dateField . ") >= '" . $d . "'",
        'before' => "FROM_UNIXTIME(" . $dateField . ") < '" . $d . "'"
    );
}
$sort = array(
    'after'  => 'ASC',
    'before' => 'DESC',
);
$params = array_merge($params, array(
    'display'      => $display,
    'sortBy'       => $dateField,
    'sortDir'      => $sort[$rule],
    'addWhereList' => $query[$rule],
    'offset'       => abs($start),
    'saveDLObject' => 'DLBeforeAfter'
));

$out = $modx->runSnippet("DocLister", $params);
$DLObj = $modx->getPlaceholder('DLBeforeAfter');
$DLObj->debug->clearLog();
$DLObj->AddTable = array();

$elements = array_merge(array(
    $rule     => $DLObj->getChildrenCount(),
    'display' => $DLObj->getCFGDef('display', $display),
    $noRule   => 0
), $elements);

$DLObj->setConfig(array(
    'addWhereList' => $query[$noRule],
    'sortDir'      => $sort[$noRule],
    'offset'       => 0
));
$DLObj->AddTable = array();
$elements[$noRule] = $DLObj->getChildrenCount();

$afterStart = $beforeStart = null;
switch (true) {
    case ($elements['offset'] > 0):
        $beforeStart = $elements['offset'] - $elements['display'];
        if ($elements['offset'] + $elements['display'] < $elements['after']) {
            $afterStart = $elements['offset'] + $elements['display'];
        } else {
            $afterStart = null;
        }
        break;
    case ($elements['offset'] < 0):
        $afterStart = $elements['offset'] + $elements['display'];
        if (abs($elements['offset']) + $elements['display'] <= $elements['before']) {
            $beforeStart = $elements['offset'] - $elements['display'];
        } else {
            $beforeStart = null;
        }
        break;
    default: // ($start = 0)
        if ($elements['display'] < $elements['after']) {
            $afterStart = $elements['display'];
        } else {
            $afterStart = null;
        }
        if ($elements['display'] <= $elements['before']) {
            $beforeStart = -1 * $elements['display'];
        } else {
            $beforeStart = null;
        }
}
$pageParams = array(
    'elementsBefore' => $elements['before'],
    'elementsAfter'  => $elements['after'],
    'pagesBefore'    => ceil($elements['before'] / $elements['display']),
    'pagesAfter'     => ceil($elements['after'] / $elements['display'])
);

if (!is_null($beforeStart)) {
    $tpl = $DLObj->getCFGDef('TplPrevP', '@CODE: <a href="[+url+]">Назад</a>');
    $beforePage = $DLObj->parseChunk($tpl, array_merge($pageParams, array(
        'url'      => buildUrl($DLObj->getUrl(), $beforeStart),
        'offset'   => $beforeStart,
        'elements' => $elements['before'],
        'pages'    => ceil($elements['before'] / $elements['display'])
    )));
} else {
    if ($DLObj->getCFGDef("PrevNextAlwaysShow", 0)) {
        $tpl = $DLObj->getCFGDef('TplPrevI', '@CODE: Назад');
        $beforePage = $DLObj->parseChunk($tpl, array_merge($pageParams, array(
            'elements' => $elements['before'],
            'pages'    => ceil($elements['before'] / $elements['display'])
        )));
    }
}
$modx->setPlaceholder('pages.before', $beforePage);

if (!is_null($afterStart)) {
    $tpl = $DLObj->getCFGDef('TplNextP', '@CODE: <a href="[+url+]">Далее</a>');
    $afterPage = $DLObj->parseChunk($tpl, array_merge($pageParams, array(
        'url'      => buildUrl($DLObj->getUrl(), $afterStart),
        'offset'   => $afterStart,
        'elements' => $elements['after'],
        'pages'    => ceil($elements['before'] / $elements['display'])
    )));
} else {
    if ($DLObj->getCFGDef("PrevNextAlwaysShow", 0)) {
        $tpl = $DLObj->getCFGDef('TplNextI', '@CODE: Далее');
        $afterPage = $DLObj->parseChunk($tpl, array_merge($pageParams, array(
            'elements' => $elements['after'],
            'pages'    => ceil($elements['before'] / $elements['display'])
        )));
    }
}
$modx->setPlaceholder('pages.after', $afterPage);

$debug = $DLObj->getCFGDef('debug', 0);
if ($debug) {
    if ($debug > 0) {
        $out = $DLObj->debug->showLog() . $out;
    } else {
        $out .= $DLObj->debug->showLog();
    }
}

return $out;
