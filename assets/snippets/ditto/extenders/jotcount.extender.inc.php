<?php
// запрашиваем комментарии из базы, попутно счита€ их количество через COUNT(*) (удаленные и неопубликованные не считаем)
$result = $modx->db->select('uparent, COUNT(*)', $modx->getFullTableName("jot_content"), 'published=1 AND deleted=0 GROUP BY uparent', 'COUNT(*) DESC');
$counts = $modx->db->makeArray( $result );

// приводим массив в вид document_id => comments_count
$jotcount = array();
foreach($counts as $k=>$v) $jotcount[$v['uparent']] = $v['COUNT(*)'];
// некрасиво "глобализуем" массив дл€ вызова из функции jotph()
$GLOBALS['jotcount'] = $jotcount;

// добавл€ем обработчик плейсхолдера [+jotcount+] в ditto
$placeholders['jotcount'] = array(array("id","*"),"jotph","id");

// фукнци€, заполн€юща€ [+jotcount+] дл€ вывода в шаблоне &tpl
if(!function_exists("jotph")) {
  function jotph($resource) {
    global $jotcount;
    if(!$r = $jotcount[$resource['id']]) $r = 0;
    return $r;
  }
}
?>