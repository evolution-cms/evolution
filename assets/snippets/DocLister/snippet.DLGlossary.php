<?php
if (! defined('MODX_BASE_PATH')) {
    die('HACK???');
}

require_once(MODX_BASE_PATH . "assets/snippets/DocLister/lib/sqlHelper.class.php");

switch (true) {
    case ( ! empty($fromget)):
        /** Брать ли данные из GET */
        $data = $_GET;
        $from = $fromget;
        break;
    case ( ! empty($frompost)):
        /** Брать ли данные из POST */
        $data = $_POST;
        $from = $frompost;
        break;
    default:
        $from = $data = null;
}
if (! empty($from)) {
    $char = (isset($data[$from]) && is_scalar($data[$from])) ? $data[$from] : null;
}
$char = ( ! empty($char) || (isset($char) && $char == 0)) ? $char : '';
/** С какого символа должен начинаться текст */

$field = ! empty($field) ? $field : 'c.pagetitle';
/** Поле по которому фильтровать */

$setActive = ! empty($setActive) ? true : false;
/** Активировать наборы символов */

$regexpSep = ! empty($regexpSep) ? $regexpSep : '||';
/** Разделитель в наборах регулярок */

$regexp = ! empty($regexp) ? $regexp : 'a-z||0-9||а-я';
/** Наборы поддерживаемых регулярок */
$regexp = explode($regexpSep, $regexp);

$loadfilter = ! empty($loadfilter) ? $loadfilter : '';
/** Какой фильтр загружать */

$register = empty($register) ? true : false; //Чувствительность к регистру.

if (preg_match("/\s+/", $field)) {
    /** SQL-injection protection :-)  */
    $char = '';
}

$out = $where = '';
$action = "like-r";

if ($char !== null) {
    if ($register) {
        $char = mb_strtolower($char, 'UTF-8');
    }

    if (mb_strlen($char, 'UTF-8') == 1) {
        $char = preg_match('/^[а-яa-z0-9]/iu', $char) ? $char : null;
    } else {
        if ($setActive && in_array($char, $regexp)) {
            $action = "regexp";
            $char = "^[{$char}]";
        } else {
            $char = null;
        }
    }
}

if ($char === null) {
    $modx->sendErrorPage();
}

$p = &$modx->event->params;
if (! is_array($p)) {
    $p = array();
}
if (! empty($loadfilter)) {
    $field = explode(".", $field);
    $field = end($field);
    if (! empty($p['filters'])) {
        $p['filters'] = rtrim(trim($p['filters']), ";") . ";";
    }
    $p['filters'] = "AND({$loadfilter}:{$field}:{$action}:{$char})";
} else {
    $field = sqlHelper::tildeField($field);
    if ($action === 'regexp') {
        $where = $field . " REGEXP '" . $modx->getDatabase()->escape($char) . "'";
    } else {
        $where = sqlHelper::LikeEscape($modx, $field, $char, '=', '[+value+]%');
    }
    if (empty($p['addWhereList'])) {
        $p['addWhereList'] = $where;
    } else {
        $p['addWhereList'] = sqlHelper::trimLogicalOp($p['addWhereList']) . " AND " . $where;
    }
}
return $modx->runSnippet("DocLister", $p);
