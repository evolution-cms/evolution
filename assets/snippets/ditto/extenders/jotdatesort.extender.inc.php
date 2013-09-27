<?php
/*
http://community.modx-cms.ru/blog/addons/6532.html
мануал 
Плейсхолдеры:
[+jotcount+] — количество комментариев
[+jotlastdate+] — дата последнего комментария (в timestamp)
[+jotlastauthor+] — автор последнего комментария

Параметры:
&jotfilter=`1` не выводить без комментариев
&jotauthorfield=`name` — поле имени автора комментария (гостя)

Пример вызова Ditto:
[[Ditto? &extenders=`jotdatesort` &startID=`2` &tpl=`DittoNews` &jotfilter=`1` &jotauthorfield=`Гость`]]

где:
&extenders=`jotdatesort` — это часть имени файла экстендера "jotdatesort.extender.inc.php"

Чанк DittoNews, просто для примера:
<h3><a href="[~[+id+]~]">[+title+]</a></h3>
Количество комментариев: [+jotcount+]<br />
Автор комментария: [+jotlastauthor+]<br />
Дата последнего комментария: [+jotlastdate:date=`%d.%m.%Y, %H:%M:%S`+]<br />
Дата поста: [+date+]<br />
Автор поста: [+author+]

Обращаю внимание на [+jotlastdate:date=`%d.%m.%Y, %H:%M:%S`+] — это pHX, т.к. экстендер выводит дату комментария в timestamp
*/
/* Поле имени */
if (!isset($jotauthorfield)) $jotauthorfield = "name";

/* Запрос в базу */
$sql = "SELECT uparent, maxcount, maxdate, fullname FROM
	(SELECT MAX(id) as maxid, uparent, COUNT(*) as maxcount, MAX(createdon) as maxdate FROM " . $modx->getFullTableName('jot_content') . " WHERE published=1 AND deleted=0 GROUP BY uparent) tab1
	LEFT JOIN
	((SELECT a.id,mua.fullname FROM " . $modx->getFullTableName('jot_content') . " as a 
	LEFT JOIN " . $modx->getFullTableName('user_attributes') . " as mua ON mua.internalKey=a.createdby WHERE a.createdby>0) 
	UNION (SELECT a.id,wua.fullname FROM " . $modx->getFullTableName('jot_content') . " as a 
	LEFT JOIN " . $modx->getFullTableName('web_user_attributes') . " as wua ON wua.internalKey=-a.createdby WHERE a.createdby<0)
	UNION (SELECT a.id,c.content as fullname FROM " . $modx->getFullTableName('jot_content') . " as a 
	LEFT JOIN " . $modx->getFullTableName('jot_fields') . " as c ON c.id = a.id AND c.label = '$jotauthorfield' WHERE a.createdby=0)) tab2 
	ON tab1.maxid = tab2.id ORDER BY maxcount DESC";
	
$result = $modx->db->query($sql);
$rs = $modx->db->makeArray( $result );

$jotcount = array();
$jotlastdate = array();
$jotlastauthor = array();
foreach($rs as $v) {
	$jotcount[$v['uparent']] = $v['maxcount'];
	$jotlastdate[$v['uparent']] = $v['maxdate'];
	$jotlastauthor[$v['uparent']] = $v['fullname'];
}
/* Количество комментариев */
$GLOBALS['jotcount'] = $jotcount;
$placeholders['jotcount'] = array("id","jotcountph",);
if(!function_exists("jotcountph")) {
  function jotcountph($resource) {
    global $jotcount;
    if(!$r = $jotcount[$resource['id']]) $r = 0;
    return $r;
  }
}

/* Последняя дата */
$GLOBALS['jotlastdate'] = $jotlastdate;
$placeholders['jotlastdate'] = array("id","jotlastdateph",);
if(!function_exists("jotlastdateph")) {
  function jotlastdateph($resource) {
    global $jotlastdate;
    if(!$r = $jotlastdate[$resource['id']]) $r = 0;
    return $r;
  }
}

/* Автор последнего комментария */
$GLOBALS['jotlastauthor'] = $jotlastauthor;
$placeholders['jotlastauthor'] = array("id","jotlastauthorph",);
if(!function_exists("jotlastauthorph")) {
  function jotlastauthorph($resource) {
    global $jotlastauthor;
    if(!$r = $jotlastauthor[$resource['id']]) $r = '';
    return $r;
  }
}

/* Сортировка по последней дате */
$orderBy['custom'][] = array('id,publishedon,createdon','jotlastdatesort');
$ditto->advSort = true;
if(!function_exists('jotlastdatesort')){
	function jotlastdatesort($a, $b){
		$aa=$GLOBALS['jotlastdate'][$a['id']];
		$bb=$GLOBALS['jotlastdate'][$b['id']];
		if (!$aa) {$aa = $a['publishedon'] ? $a['publishedon'] : $a['createdon'];}
		if (!$bb) {$bb = $b['publishedon'] ? $b['publishedon'] : $b['createdon'];}
		if ($aa == $bb){return 0;}
		return ($aa > $bb)?-1:1;
	}
}

/* Фильтр по наличию комментариев */
$GLOBALS["jotfilter"] = isset($jotfilter) ? $jotfilter : 0;
$filters["custom"]["jotcountFilter"] = array("id","jotcountFilter"); 
if (!function_exists("jotcountFilter")) {
	function jotcountFilter($resource) {
		global $jotcount,$jotfilter;
		if($jotfilter && !$jotcount[$resource['id']]) return 0;
		return 1;
	}
}
?>