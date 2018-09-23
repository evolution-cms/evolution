<?php
/**
 * DocCount
 *
 * @category  counter
 * @version   1.1.0
 * @license   GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @param int $parent ID документа родителя
 * @param string $templates ID шаблонов дочерних документов, несколько через запятую
 * @param int $depth глубина вложенности (<0, 0, >9 == 10, максимум)
 * @param int $published Какие документы считать (0 - неопубликованные || 1 - опубликованные || 2 - все)
 * @return int Количество дочерних документов
 * @author kanstudio <kanstudio@mail.ru>
 * 
 * @example
 *       [[DocCount? &parent=`[*id*]` &templates=`0` &depth=`1` &published=`1`]] == [[DocCount]]
 *       [[DocCount? &depth=`0` &templates=`2,3` &published=`2`]]
 */
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

$parent = (isset($parent) && (int)$parent>=0) ? (int)$parent : $modx->documentIdentifier;
$templates = (isset($templates) && (int)$templates!=0) ? ' AND ('.preg_replace('/\s*,\s*/', " OR ", preg_replace('/(\d+)/', "template = $1", $templates)).')' : '';
$depth = isset($depth) ? (((int)$depth>0 && (int)$depth<10) ? (int)$depth : 10) : 1;
$published = isset($published) ? (((int)$published==0 || (int)$published==1) ? ' AND published = '.$published : '') : ' AND published = 1';

$tblsc = $modx->getFullTableName('site_content');
$r = $modx->db->select("id", "{$tblsc}", "type = 'document'".$published.$templates);
$rArray = $modx->db->makeArray($r);

if($parent == 0 && $depth == 10) return count($rArray);

$cArray = $modx->aliasListing;
$result = array_filter($rArray, function($doc) use ($parent, $depth, $cArray) {
	for($d = $depth, $k = (int)$doc['id']; $d > 0 && $k > 0; $d--) {
		if($cArray[$k]['parent'] == $parent) return true;
		else $k = $cArray[$k]['parent'];
	}
    return false;
});

return count($result);
