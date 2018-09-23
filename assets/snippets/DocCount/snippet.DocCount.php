<?php
/**
 * DocCount
 *
 * @category  counter
 * @version   1.0.0
 * @license   GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @param int $parent ID документа родителя
 * @param int $depth глубина вложенности (<0, 0, >9 == 10, максимум)
 * @param int $published Какие документы считать (0 - неопубликованные || 1 - опубликованные || 2 - все)
 * @param int $deleted Какие документы считать (0 - неудалённые || 1 - удалённые || 2 - все)
 * @return int Количество дочерних документов
 * @author kanstudio <kanstudio@mail.ru>
 * 
 * @example
 *       [[DocCount? &parent=`[*id*]` &depth=`1` &published=`1` &deleted=`0`]] == [[DocCount]]
 *       [[DocCount? &depth=`0` &published=`2` &deleted=`2`]]
 */
if(!defined('MODX_BASE_PATH')){die('What are you doing? Get out of here!');}

$parent = (isset($parent) && (int)$parent>=0) ? (int)$parent : $modx->documentIdentifier;
$depth = isset($depth) ? (((int)$depth>0 && (int)$depth<10) ? (int)$depth : 10) : 1;
$published = isset($published) ? (((int)$published==0 || (int)$published==1) ? ' AND published = '.$published : '') : ' AND published = 1';
$deleted = isset($deleted) ? (((int)$deleted==0 || (int)$deleted==1) ? ' AND deleted = '.$deleted : '') : ' AND deleted = 0';

$tblsc = $modx->getFullTableName('site_content');
$r = $modx->db->select("id", "{$tblsc}", "type = 'document'".$published.$deleted);
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
