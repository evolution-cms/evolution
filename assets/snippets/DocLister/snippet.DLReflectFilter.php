<?php
/**
 * [[DLReflectFilter?
 *        &idType=`parents`
 *        &parents=`87`
 *        &tpl=`listNews`
 *        &paginate=`pages`
 *        &display=`2`
 *        &reflectSource=`tv`
 *        &reflectField=`date`
 *        &reflectType=`month`
 *        &tvList=`date`
 *        &sortDir=`DESC`
 *    ]]
 *  [+pages+]
 */
include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');
include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLReflect.class.php');

$params = is_array($modx->event->params) ? $modx->event->params : array();

/**
 * reflectType
 *        Тип фильтрации. Возможные значения:
 *            month - по месяцам (значение по умолчанию)
 *            year - по годам
 */
$reflectType = APIHelpers::getkey($params, 'reflectType', 'month');
if (!in_array($reflectType, array('year', 'month'))) {
    return '';
}

list($dateFormat, $sqlDateFormat, $reflectValidator) = DLReflect::switchReflect($reflectType, function () {
    return array('m-Y', '%m-%Y', array('DLReflect', 'validateMonth'));
}, function () {
    return array('Y', '%Y', array('DLReflect', 'validateYear'));
});

$reflectSource = APIHelpers::getkey($params, 'reflectSource', 'content');
$reflectField = APIHelpers::getkey($params, 'reflectField', 'if(pub_date=0,createdon,pub_date)');

$tmp = date($dateFormat);
/**
 * currentReflect
 *        Текущая дата (месяц в формате 00-0000 или год в формате 0000), где:
 *            00 - Номер месяца с ведущим нулем (01, 02, 03, ..., 12)
 *            0000 - Год
 *        Если не указан в параметре, то генерируется автоматически текущая дата
 */
$selectCurrentReflect = APIHelpers::getkey($params, 'selectCurrentReflect', 1);
if ($selectCurrentReflect) {
    $currentReflect = APIHelpers::getkey($params, 'currentReflect', $tmp);
    if (!call_user_func($reflectValidator, $currentReflect)) {
        $currentReflect = $tmp;
    }
} else {
    $currentReflect = null;
}
/**
 * activeReflect
 *        Дата которую выбрал пользователь.
 *
 *        Если параметр не задан, то в качестве значения по умолчанию используется значение параметра currentReflect
 *        При наличии ГЕТ параметра month/year, приоритет отдается ему
 */
$tmp = APIHelpers::getkey($params, 'activeReflect', $currentReflect);
$tmpGet = APIHelpers::getkey($_GET, $reflectType, $tmp);
if (!call_user_func($reflectValidator, $tmpGet)) {
    $activeReflect = $tmp;
    if (!call_user_func($reflectValidator, $activeReflect)) {
        $activeReflect = $currentReflect;
    }
} else {
    $activeReflect = $tmpGet;
}
if ($activeReflect) {
    $v = $modx->getDatabase()->escape($activeReflect);
    if ($reflectSource === 'tv') {
        $params['tvSortType'] = 'TVDATETIME';
        $query = 'STR_TO_DATE(`dltv_' . $reflectField . "_1`.`value`, '%d-%m-%Y %H:%i:%s')";
    } else {
        $query = 'FROM_UNIXTIME(' . $reflectField . ')';
    }
    $params['addWhereList'] = 'DATE_FORMAT(' . $query . ", '" . $sqlDateFormat . "')='" . $v . "'";
} else {
    if ($reflectSource === 'tv') {
        $params['tvSortType'] = 'TVDATETIME';
    }
}
$params['sortBy'] = $reflectField;

return $modx->runSnippet('DocLister', $params);
