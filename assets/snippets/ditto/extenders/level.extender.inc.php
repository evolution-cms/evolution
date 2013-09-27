<?php
/* Вывод документов определенного уровня 
Параметр &level — уровень от корня сайта
*/

$GLOBALS["level"] = isset($level) ? (int)$level : 1;
$filters["custom"]["levelFilter"] = array("id","levelFilter"); 
if (!function_exists("levelFilter")) {
	function levelFilter($resource) {
		global $modx,$level;
		if (count($modx->getParentIds($resource['id'])) == $level) {
			return 1;
		} else {
			return 0;
		}
	}
}
?>