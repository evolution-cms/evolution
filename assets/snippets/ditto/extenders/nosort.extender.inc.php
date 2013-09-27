<?php
// Äטעעמ גûחûגאורü ס ןאנאלוענאלט: 'extenders' => 'nosort', 'sortBy' => 'id'
if (!function_exists('nosort')){
	function nosort($a, $b){
		$pos_a=array_search($a['id'],$GLOBALS['documents']);
		$pos_b=array_search($b['id'],$GLOBALS['documents']);
		if ($pos_a == $pos_b){return 0;}
		return ($pos_a < $pos_b)?-1:1;
	}
}
$GLOBALS['documents']=explode(',',$documents);
$orderBy['custom'][]=array('id', 'nosort');
$ditto->advSort = true;
?>