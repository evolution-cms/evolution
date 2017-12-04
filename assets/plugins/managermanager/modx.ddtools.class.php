<?php
/**
 * modx ddTools class
 * @version 0.12 (2014-05-23)
 * 
 * @uses modx 1.0.10 (Evo)
 * 
 * @link http://code.divandesign.biz/modx/ddtools/0.12
 * 
 * @copyright 2014, DivanDesign
 * http://www.DivanDesign.biz
 */

if (!class_exists('ddTools')){
class ddTools {
	//Contains names of document fields (`site_content`)
	public static $documentFields = array(
		'id',
		'type',
		'contentType',
		'pagetitle',
		'longtitle',
		'description',
		'alias',
		'link_attributes',
		'published',
		'pub_date',
		'unpub_date',
		'parent',
		'isfolder',
		'introtext',
		'content',
		'richtext',
		'template',
		'menuindex',
		'searchable',
		'cacheable',
		'createdby',
		'createdon',
		'editedby',
		'editedon',
		'deleted',
		'deletedon',
		'deletedby',
		'publishedon',
		'publishedby',
		'menutitle',
		'donthit',
		'haskeywords',
		'hasmetatags',
		'privateweb',
		'privatemgr',
		'content_dispo',
		'hidemenu'
	);
	
	//Contains full names of some db tables
	public static $tables = array(
		'site_content' => '',
		'site_tmplvars' => '',
		'site_tmplvar_templates' => '',
		'site_tmplvar_contentvalues' => '',
		'document_groups' => ''
	);
	
	/**
	 * screening
	 * @version 1.0 (2012-03-21)
	 * 
	 * @desc Screening chars in string.
	 * 
	 * @param $str {string} - String to screening. @required
	 * 
	 * @return {string} - Экранированная строка.
	 */
	public static function screening($str){
		$str = str_replace("\r\n", ' ', $str);
		$str = str_replace("\n", ' ', $str);
		$str = str_replace("\r", ' ', $str);
		$str = str_replace(chr(9), ' ', $str);
		$str = str_replace('  ', ' ', $str);
		$str = str_replace('[+', '\[\+', $str);
		$str = str_replace('+]', '\+\]', $str);
		$str = str_replace("'", "\'", $str);
		$str = str_replace('"', '\"', $str);
		
		return $str;
	}
	
	/**
	 * explodeAssoc
	 * @version 1.1.1 (2013-07-11)
	 * 
	 * @desc Splits string on two separators in the associative array.
	 * 
	 * @param $str {separated string} - String to explode. @required
	 * @param $splY {string} - Separator between pairs of key-value. Default: '||'.
	 * @param $splX {string} - Separator between key and value. Default: '::'.
	 * 
	 * @return {array: associative}
	 */
	public static function explodeAssoc($str, $splY = '||', $splX = '::'){
		$result = array();
		
		//Если строка пустая, выкидываем сразу
		if ($str == ''){return $result;}
		
		//Разбиваем по парам
		$str = explode($splY, $str);
		
		foreach ($str as $val){
			//Разбиваем на ключ-значение
			$val = explode($splX, $val);
			$result[$val[0]] = isset($val[1]) ? $val[1] : '';
		}
		
		return $result;
	}
	
	/**
	 * unfoldArray
	 * @version 1.0 (2013-04-23)
	 * 
	 * @desc Converts a multidimensional array into an one-dimensional one joining the keys with '.'. It can be helpful while using placeholders like [+size.width+].
	 * For example, array(
	 * 	'a': '',
	 * 	'b': array(
	 * 		'b1': '',
	 * 		'b2': array(
	 * 			'b21': '',
	 * 			'b22': ''
	 * 		)
	 * 	),
	 * 	'c': ''
	 * ) turns into array(
	 * 	'a': '',
	 * 	'b.b1': '',
	 * 	'b.b2.b21': '',
	 * 	'b.b2.b22': '',
	 * 	'c': ''
	 * ).
	 * 
	 * @param $arr {array} - An array to convert. @required
	 * @param $keyPrefix {string} - Prefix of the keys of an array (it's an internal varible, can be used if required). Default: ''.
	 * 
	 * @return {array} - Unfolded array.
	 */
	public static function unfoldArray($arr, $keyPrefix = ''){
		$output = array();
		
		//Перебираем массив
		foreach ($arr as $key => $val){
			//Если значение является массивом
			if (is_array($val)){
				//Запускаем рекурсию дальше
				$output = array_merge($output, self::unfoldArray($val, $keyPrefix.$key.'.'));
			//Если значение — не массив
			}else{
				//Запоминаем (в соответствии с ключом родителя)
				$output[$keyPrefix.$key] = $val;
			}
		}
		
		return $output;
	}
	
	/**
	 * sort2dArray
	 * @version 1.1 (2013-07-11)
	 * 
	 * @desc Sorts 2-dimensional array by multiple columns (like in SQL) using Hoare's method, also referred to as quicksort. The sorting is stable.
	 * 
	 * @param $array {array} - Array to sort. @required
	 * @param $sortBy {array} - Columns (second level keys) by which the array is sorted. @required
	 * @param $sortDir {1; -1} - Sort direction (1 == ASC; -1 == DESC). Default: 1.
	 * @param $i {integer} - Count, an internal variable used during recursive calls. Default: 0.
	 * 
	 * @return {array} - Sorted array.
	 */
	public static function sort2dArray($array, $sortBy, $sortDir = 1, $i = 0){
		//В качестве эталона получаем сортируемое значение (по первому условию сортировки) первого элемента
		$tek = $array[0][$sortBy[$i]];
		$tekIsNumeric = is_numeric($tek);
		
		$arrLeft = array();
		$arrRight = array();
		$arrCent = array();
		
		//Перебираем массив
		foreach ($array as $val){
			//Если эталон и текущее значение — числа
			if ($tekIsNumeric && is_numeric($val[$sortBy[$i]])){
				//Получаем нужную циферку
				$cmpRes = ($val[$sortBy[$i]] == $tek) ? 0 : (($val[$sortBy[$i]] > $tek) ? 1 : -1);
				//Если они строки
			}else{
				//Сравниваем текущее значение со значением эталонного
				$cmpRes = strcmp($val[$sortBy[$i]], $tek);
			}
			
			//Если меньше эталона, отбрасываем в массив меньших
			if ($cmpRes * $sortDir < 0){
				$arrLeft[] = $val;
			//Если больше - в массив больших
			}else if ($cmpRes * $sortDir > 0){
				$arrRight[] = $val;
			//Если раво - в центральный
			}else{
				$arrCent[] = $val;
			}
		}
		
		//Массивы меньших и массивы больших прогоняем по тому же алгоритму (если в них что-то есть)
		$arrLeft = (count($arrLeft) > 1) ? self::sort2dArray($arrLeft, $sortBy, $sortDir, $i) : $arrLeft;
		$arrRight = (count($arrRight) > 1) ? self::sort2dArray($arrRight, $sortBy, $sortDir, $i) : $arrRight;
		//Массив одинаковых прогоняем по следующему условию сортировки (если есть условие и есть что сортировать)
		$arrCent = ((count($arrCent) > 1) && $sortBy[$i + 1]) ? self::sort2dArray($arrCent, $sortBy, $sortDir, $i + 1) : $arrCent;
		
		//Склеиваем отсортированные меньшие, средние и большие
		return array_merge($arrLeft, $arrCent, $arrRight);
	}
	
	/**
	 * parseText
	 * @version 1.1 (2012-03-21)
	 * 
	 * @desc Like $modx->parseChunk, but takes a text.
	 * 
	 * @param $chunk {string} - String to parse. @required
	 * @param $chunkArr {array} - Array of values. Key — placeholder name, value — value. @required
	 * @param $prefix {string} - Placeholders prefix. Default: '[+'.
	 * @param $suffix {string} - Placeholders suffix. Default: '+]'.
	 * @param $mergeAll {boolean} -Additional parsing the document fields, settings, chunks. Default: true.
	 * 
	 * @return {string}
	 */
	public static function parseText($chunk, $chunkArr, $prefix= '[+', $suffix= '+]', $mergeAll = true){
		global $modx;
		
		//Если значения для парсинга не переданы, ничего не делаем
		if (!is_array($chunkArr)){
			return $chunk;
		}
		
		if ($mergeAll){
			$chunk = $modx->mergeDocumentContent($chunk);
			$chunk = $modx->mergeSettingsContent($chunk);
			$chunk = $modx->mergeChunkContent($chunk);
		}
		
		foreach ($chunkArr as $key => $value) {
			$chunk = str_replace($prefix.$key.$suffix, $value, $chunk);
		}
		
		return $chunk;
	}
	
	/**
	 * parseSource
	 * @version 1.0 (2012-02-13)
	 * 
	 * @desc Parse the source (run $modx->parseDocumentSource and $modx->rewriteUrls);
	 * 
	 * @param $sourse {string} - Text to parse. @required
	 * 
	 * @return {string}
	 */
	public static function parseSource($source){
		global $modx;
		
		return $modx->rewriteUrls($modx->parseDocumentSource($source));
	}
	
	/**
	 * explodeFieldsArr
	 * @version 1.0 (2012-03-20)
	 * 
	 * @desc Explode associative array of fields and TVs in two individual arrays.
	 * 
	 * @param $fields {array} - Associative array of document fields (from table `site_content`) or TVs values. @required
	 * 
	 * @return {array} - Массив из двух элементов, где первый — поля документа, второй — TV. Элементами массива TV являются ассоциативные массивы, в которых хранятся 'id' и 'val'.
	 */
	public static function explodeFieldsArr($fields = array()){
		global $modx;
		
		$tvs = array();
		//Перебираем поля, раскидываем на поля документа и TV
		foreach ($fields as $key => $val){
			//Если это не поле документа
			if (!in_array($key, self::$documentFields)){
				//Запоминаем как TV`шку
				$tvs[$key] = array('val' => $val);
				//Удаляем из полей
				unset($fields[$key]);
			}
		}
		
		//Если есть хоть одна TV
		if (count($tvs) > 0){
			//Получаем id всех необходимых TV
			$dbRes = $modx->db->select(
				"`name`, `id`",
				self::$tables['site_tmplvars'],
				"`name` IN ('".implode("','", array_keys($tvs))."')"
			);
			
			while ($row = $modx->db->getRow($dbRes)){
				$tvs[$row['name']]['id'] = $row['id'];
			}
		}
		
		return array($fields, $tvs);
	}
	
	/**
	 * createDocument
	 * @version 1.1 (2012-03-20)
	 * 
	 * @desc Create a new document.
	 * 
	 * @param $fields {array} - Array of document fields or TVs. Key — name, value — value. The pagetitle is required. @required
	 * @param $groups {array} - Array of document groups id.
	 * 
	 * @return {mixed} - ID нового документа или false, если что-то не так.
	 */
	public static function createDocument($fields = array(), $groups = false){
		global $modx;
		
		//Если нет хотя бы заголовка, выкидываем
		if (!$fields['pagetitle']){return false;}
		
		//Если не передана дата создания документа, ставим текущую
		if (!$fields['createdon']){$fields['createdon'] = time();}
		
		//Если не передано, кем документ создан, ставим 1
		if (!$fields['createdby']){$fields['createdby'] = 1;}
		
		//Если группы заданы, то это приватный документ
		if ($groups){$fields['privatemgr'] = 1;}
		
		//Если надо публиковать, поставим дату публикации текущей
		if ($fields['published'] == 1){$fields['pub_date'] = $fields['createdon'];}
		
		$fields = self::explodeFieldsArr($fields);
		
		//Вставляем новый документ в базу, получаем id, если что-то пошло не так, выкидываем
		$id = $modx->db->insert($fields[0], self::$tables['site_content']);
		
		if (!$id){return false;}
		
		//Если есть хоть одна TV
		if (count($fields[1]) > 0){
			//Перебираем массив TV
			foreach ($fields[1] as $key => $val){
				//Проверим, что id существует (а то ведь могли и именем ошибиться)
				if (isset($val['id'])){
					//Добавляем значение TV в базу
					$modx->db->insert(
						array('value' => $val['val'], 'tmplvarid' => $val['id'], 'contentid' => $id),
						self::$tables['site_tmplvar_contentvalues']
					);
				}
			}
		}
		
		//Если заданы группы (и на всякий проверим ID)
		if ($groups){
			//Перебираем все группы
			foreach ($groups as $gr){
				$modx->db->insert(array('document_group' => $gr, 'document' => $id), self::$tables['document_groups']);
			}
		}
		
		return $id;
	}
	
	/**
	 * updateDocument
	 * @version 1.2 (2012-10-26)
	 * 
	 * @desc Update a document.
	 * 
	 * @note $id и/или $where должны быть переданы
	 * 
	 * @param $id {integer; array} - Document id to update. @required
	 * @param $update {array} - Array of document fields or TVs to update. Key — name, value — value. @required
	 * @param $where {string} - SQL WHERE string. Default: ''.
	 * 
	 * @return {boolean} - true — если всё хорошо, или false — если такого документа нет, или ещё что-то пошло не так.
	 */
	public static function updateDocument($id = 0, $update = array(), $where = ''){
		global $modx;
		
		if ($id == 0 && trim($where) == ''){return false;}
		
		$where_sql = '';
		
		if (is_array($id) && count($id)){
			//Обрабатываем массив id
			$where_sql .= "`id` IN ('".implode("','", $id)."')";
		}else if (is_numeric($id) && $id != 0){
			//Обрабатываем числовой id
			$where_sql .= "`id`='$id'";
		}
		
		//Добавляем дополнительное условие
		if ($where != ''){
			$where_sql .= ($where_sql != '' ? ' AND ' : '').$where;
		}
		
		//Получаем id документов для обновления
		$update_ids_res = $modx->db->select('id', self::$tables['site_content'], $where_sql);
		
		if ($modx->db->getRecordCount($update_ids_res)){
			//Разбиваем на поля документа и TV
			$update = self::explodeFieldsArr($update);
			
			//Обновляем информацию по документу
			if (count($update[0])){
				$modx->db->update($update[0], self::$tables['site_content'], $where_sql);
			}
			
			//Если есть хоть одна TV
			if (count($update[1]) > 0){
				//Обновляем TV всех найденых документов
				while ($doc = $modx->db->getRow($update_ids_res)){
					//Перебираем массив TV
					foreach ($update[1] as $val){
						if (isset($val['id'])){
					        $modx->db->query('INSERT INTO ' . self::$tables['site_tmplvar_contentvalues'] . ' (contentid, tmplvarid, value) VALUES (' . $doc['id'] . ', ' . $val['id'] . ', "' . $val['val'] . '") ON DUPLICATE KEY UPDATE value = "' . $val['val'] . '"');
					    }
					}
				}
			}
			return true;
		}else{
			//Нечего обновлять
			return false;
		}
	}
	
	/**
	 * getDocuments
	 * @version 1.1 (2014-03-19)
	 * 
	 * @desc Returns required documents (documents fields).
	 * 
	 * @note
	 * Differences from the native method:
	 * 	— $published parameter can be set as ===false, and if it is then document publication status does not matter.
	 * 	— $deleted parameter can be set as ===false, and if it is then document publication status does not matter either.
	 * 
	 * @param $ids {array} - Documents Ids to get. @required
	 * @param $published {false; 0; 1} - Documents publication status which does not matter if published === false. Default: false.
	 * @param $deleted {false; 0; 1} - Documents removal status which does not matter if deleted === false. Default: 0.
	 * @param $fields {comma separated string; '*'} - Documents fields to get. Default: '*'.
	 * @param $where {string} - SQL WHERE clause. Default: ''.
	 * @param $sort {string} - A field to sort by. Default: 'menuindex'.
	 * @param $dir {'ASC'; 'DESC'} - Sorting direction. Default: 'ASC'.
	 * @param $limit {string} - SQL LIMIT (without 'LIMIT'). Default: ''.
	 * 
	 * @return {mixed} - Массив документов или false, если что-то не так.
	 */
	public static function getDocuments($ids = array(), $published = false, $deleted = 0, $fields = '*', $where = '', $sort = 'menuindex', $dir = 'ASC', $limit = ''){
		global $modx;
		
		if(is_string($ids)){
			if(strpos($ids, ',') !== false){
				$ids = array_filter(array_map('intval', explode(',', $ids)));
			}else{
				$ids = array($ids);
			}
		}
		
		if (count($ids) == 0){
			return false;
		}else{
			// modify field names to use sc. table reference
			$fields = 'sc.'.implode(',sc.', array_filter(array_map('trim', explode(',', $fields))));
			$sort = ($sort == '') ? '' : 'sc.'.implode(',sc.', array_filter(array_map('trim', explode(',', $sort))));
			if ($where != ''){
				$where = 'AND '.$where;
			}
			
			$published = ($published !== false) ? "AND sc.published = '{$published}'" : '';
			$deleted = ($deleted !== false) ? "AND sc.deleted = '{$deleted}'" : '';
			
			// get document groups for current user
			if ($docgrp = $modx->getUserDocGroups()){
				$docgrp = implode(',', $docgrp);
			}
			
			$access = ($modx->isFrontend() ? 'sc.privateweb=0' : '1="'.$_SESSION['mgrRole'].'" OR sc.privatemgr=0').(!$docgrp ? '' : ' OR dg.document_group IN ('.$docgrp.')');
			
			$result = $modx->db->select(
				"DISTINCT {$fields}",
				self::$tables['site_content']." sc LEFT JOIN ".self::$tables['document_groups']." dg on dg.document = sc.id",
				"(sc.id IN (".implode(',', $ids).") {$published} {$deleted} {$where}) AND ({$access}) GROUP BY sc.id",
				($sort ? "{$sort} {$dir}" : ""),
				$limit
			);
			
			$resourceArray = $modx->db->makeArray($result);
			
			return $resourceArray;
		}
	}
	
	/**
	 * getDocument
	 * @version 1.0 (2013-03-16)
	 * 
	 * @desc Returns required data of a document (document fields).
	 * 
	 * @note
	 * Differences from the native method:
	 * 	— $published parameter can be set as false, and if it is then document publication status does not matter.
	 * 	— $deleted parameter can be set as false, and if it is then document publication status does not matter either.
	 * 
	 * @param $id {integer} - Id of a document which data is being got. @required
	 * @param $fields {comma separated string; '*'} - Documents fields to get. Default: '*'.
	 * @param $published {false; 0; 1} - Document publication status which does not matter if published === false. Default: false.
	 * @param $deleted {false; 0; 1} - Document removal status which does not matter if published === false. Default: 0.
	 * 
	 * @return {mixed} - Массив данных документа или false, если что-то не так.
	 */
	public static function getDocument($id = 0, $fields = '*', $published = false, $deleted = 0){
		if ($id == 0){
			return false;
		}else{
			$docs = self::getDocuments(array($id), $published, $deleted, $fields, '', '', '', 1);
			
			if ($docs != false){
				return $docs[0];
			}else{
				return false;
			}
		}
	}
	
	/**
	 * getTemplateVars
	 * @version 1.1 (2014-03-19)
	 * 
	 * @desc Returns the TV and fields array of a document. 
	 * 
	 * @note
	 * Differences from the native method:
	 * 	— $published parameter can be set as false, and if it is then document publication status does not matter.
	 * 
	 * @param $idnames {array; '*'} - Id, TVs names, or documents fields to get. @required
	 * @param $fields {comma separated string; '*'} - Fields names in the TV table of MODx database. Default: '*'.
	 * @param $docid {integer; ''} - Id of a document to get. Default: Current document.
	 * @param $published {false; 0; 1} - Document publication status which does not matter if published === false. Default: false.
	 * @param $sort {comma separated string} - Fields of the TV table to sort by. Default: 'rank'.
	 * @param $dir {'ASC'; 'DESC'} - Sorting direction. Default: 'ASC'.
	 * 
	 * @return {mixed} - Массив TV или false, если что-то не так.
	 */
	public static function getTemplateVars($idnames = array(), $fields = '*', $docid = '', $published = false, $sort = 'rank', $dir = 'ASC'){
		global $modx;
		
		if (($idnames != '*' && !is_array($idnames)) || count($idnames) == 0){
			return false;
		}else{
			// get document record
			if ($docid == ''){
				$docid = $modx->documentIdentifier;
				$docRow = $modx->documentObject;
			}else{
				$docRow = self::getDocument($docid, '*', $published);
				
				if (!$docRow){
					return false;
				}
			}
			
			// get user defined template variables
			$fields = ($fields == '') ? 'tv.*' : 'tv.'.implode(',tv.', array_filter(array_map('trim', explode(',', $fields))));
			$sort = ($sort == '') ? '' : 'tv.'.implode(',tv.', array_filter(array_map('trim', explode(',', $sort))));
			
			if ($idnames == '*'){
				$query = 'tv.id<>0';
			}else{
				$query = (is_numeric($idnames[0]) ? 'tv.id' : 'tv.name')." IN ('".implode("','", $idnames)."')";
			}
			
			$rs = $modx->db->select(
				"{$fields}, IF(tvc.value != '', tvc.value, tv.default_text) as value",
				self::$tables['site_tmplvars']." tv
					INNER JOIN ".self::$tables['site_tmplvar_templates']." tvtpl ON tvtpl.tmplvarid = tv.id
					LEFT JOIN ".self::$tables['site_tmplvar_contentvalues']." tvc ON tvc.tmplvarid=tv.id AND tvc.contentid = '{$docid}'",
				"{$query} AND tvtpl.templateid = '{$docRow['template']}'",
				($sort ? "{$sort} {$dir}" : "")
			);
			
			$result = $modx->db->makeArray($rs);
			
			// get default/built-in template variables
			ksort($docRow);
			
			foreach ($docRow as $key => $value){
				if ($idnames == '*' || in_array($key, $idnames)){
					array_push($result, array(
						'name' => $key,
						'value' => $value
					));
				}
			}
			
			return $result;
		}
	}
	
	/**
	 * getTemplateVarOutput
	 * @version 1.0.1 (2014-03-19)
	 * 
	 * @desc Returns the associative array of fields and TVs of a document.
	 * 
	 * @note
	 * Differences from the native method:
	 * 	— $published parameter can be set as false, and if it is then document publication status does not matter.
	 * 
	 * @param $idnames {array; '*'} - Id, TVs names, or documents fields to get. @required
	 * @param $docid {integer; ''} - Id of a document to get. Default: Current document.
	 * @param $published {false; 0; 1} - Document publication status which does not matter if published === false. Default: false.
	 * @param $sep {string} - Separator that is used while concatenating in getTVDisplayFormat(). Default: ''.
	 * 
	 * @return {mixed} - Массив TV или false, если что-то не так.
	 */
	public static function getTemplateVarOutput($idnames = array(), $docid = '', $published = false, $sep = ''){
		global $modx;
		
		if (count($idnames) == 0){
			return false;
		}else{
			$output = array();
			$vars = ($idnames == '*' || is_array($idnames)) ? $idnames : array($idnames);
			
			$docid = intval($docid) ? intval($docid) : $modx->documentIdentifier;
			// remove sort for speed
			$result = self::getTemplateVars($vars, '*', $docid, $published, '', '');
			
			if ($result == false){
				return false;
			}else{
				$baspath = MODX_MANAGER_PATH.'includes';
				include_once $baspath.'/tmplvars.format.inc.php';
				include_once $baspath.'/tmplvars.commands.inc.php';
				
				for ($i= 0; $i < count($result); $i++){
					$row = $result[$i];
					
					if (!$row['id']){
						$output[$row['name']] = $row['value'];
					}else{
						$output[$row['name']] = getTVDisplayFormat($row['name'], $row['value'], $row['display'], $row['display_params'], $row['type'], $docid, $sep);
					}
				}
				
				return $output;
			}
		}
	}
	
	/**
	 * getDocumentChildren
	 * @version 1.1 (2014-03-19)
	 * 
	 * @desc Returns the associative array of a document fields.
	 * 
	 * @note
	 * Differences from the native method:
	 * 	— $published parameter can be set as false, and if it is then document publication status does not matter.
	 * 	— $deleted parameter can be set as false, and if it is then document publication status does not matter either.
	 * 
	 * @param $parentid {integer} - Id of parent document. Default: 0.
	 * @param $published {false; 0; 1} - Documents publication status which does not matter if published === false. Default: 1.
	 * @param $deleted {false; 0; 1} - Documents removal status which does not matter if deleted === false. Default: 0.
	 * @param $fields {comma separated string} - Documents fields to get. Default: '*'.
	 * @param $where {string} - SQL WHERE clause. Default: ''.
	 * @param $sort {string; comma separated string} - Transfer a few conditions separated with comma (like SQL) to multiple sort, but param “sortDir” must be '' in this case. Default: 'menuindex'.
	 * @param $dir {'ASC'; 'DESC'; ''} - Direction for sort. Default: 'ASC'.
	 * @param $limit {string} - SQL LIMIT (without 'LIMIT'). Default: ''.
	 * 
	 * @return {mixed} - Массив документов или false, если что-то не так.
	 */
	public static function getDocumentChildren($parentid = 0, $published = 1, $deleted = 0, $fields = '*', $where = '', $sort = 'menuindex', $dir = 'ASC', $limit = ''){
		global $modx;
		
		$published = ($published !== false) ? 'AND sc.published = '.$published : '';
		$deleted = ($deleted !== false) ? 'AND sc.deleted = '.$deleted : '';
		
		if ($where != ''){
			$where = 'AND '.$where;
		}
		
		// modify field names to use sc. table reference
		$fields = 'sc.' . implode(',sc.', array_filter(array_map('trim', explode(',', $fields))));
		$sort = ($sort == '') ? '' : 'sc.' . implode(',sc.', array_filter(array_map('trim', explode(',', $sort))));
		
		// get document groups for current user
		if ($docgrp = $modx->getUserDocGroups()){
			$docgrp = implode(',', $docgrp);
		}
		
		// build query
		$access = ($modx->isFrontend() ? 'sc.privateweb=0' : '1="'.$_SESSION['mgrRole'].'" OR sc.privatemgr=0').(!$docgrp ? '' : ' OR dg.document_group IN ('.$docgrp.')');
		
		$result = $modx->db->select(
			"DISTINCT {$fields}",
			self::$tables['site_content']." sc
				LEFT JOIN ".self::$tables['document_groups']." dg on dg.document = sc.id",
			"sc.parent = '{$parentid}' {$published} {$deleted} {$where} AND ({$access}) GROUP BY sc.id",
			($sort ? "{$sort} {$dir}" : ""),
			$limit
		);
		
		$resourceArray = $modx->db->makeArray($result);
		
		return $resourceArray;
	}
	
	/**
	 * getDocumentChildrenTVarOutput
	 * @version 1.2 (2014-03-19)
	 * 
	 * @desc Get necessary children of document.
	 * 
	 * @note
	 * Differences from the native method:
	 * 	— The parameter $where that allows an sql where condition to be set (only the fields of a required document can be used).
	 * 	— The parameter $resultKey that allows result array keys to be set as values of one of the document fields.
	 * 	— $modx->getDocumentChildren receives only IDs, other data is received later.
	 * 	— The $published parameter can be set as ===false so documents data can be got regardless of their publication status.
	 * 
	 * @param $parentid {integer} - Id of parent document. Default: 0.
	 * @param $tvidnames {array} - Array of document fields or TVs to get. Default: array($resultKey).
	 * @param $published {false; 0; 1} - Documents publication status which does not matter if published === false. Default: 1.
	 * @param $sortBy {string; comma separated string} - Transfer a few conditions separated with comma (like SQL) to multiple sort, but param “sortDir” must be '' in this case. Default: 'menuindex'.
	 * @param $sortDir {'ASC'; 'DESC'; ''} - Direction for sort. Default: 'ASC'.
	 * @param $where {string} - SQL WHERE condition (use only document fields, not TV). Default: ''.
	 * @param $resultKey {string; false} - Field, which values are keys into result array. Use the “false”, that result array keys just will be numbered. Default: 'id'.
	 * 
	 * @return {mixed} - Массив документов или false, если что-то не так.
	 */
	public static function getDocumentChildrenTVarOutput($parentid = 0, $tvidnames = array(), $published = 1, $sortBy = 'menuindex', $sortDir = 'ASC', $where = '', $resultKey = 'id'){
		//Получаем всех детей
		$docs = self::getDocumentChildren($parentid, $published, 0, 'id', $where, $sortBy, $sortDir);
		
		//Если ничего не получили, выкидываем
		if (!$docs){
			return false;
		}else{
			$result = array();
			
			$unsetResultKey = false;
			
			if ($resultKey !== false){
				if (is_array($tvidnames)){
					if (count($tvidnames) != 0 && !in_array($resultKey, $tvidnames)){
						$tvidnames[] = $resultKey;
						$unsetResultKey = true;
					}
				}else if ($tvidnames != '*' && $tvidnames != $resultKey){
					$tvidnames = array($tvidnames, $resultKey);
					$unsetResultKey = true;
				}
			}
			
			//Перебираем все документы
			for ($i = 0; $i < count($docs); $i++){
				$tvs = self::getTemplateVarOutput($tvidnames, $docs[$i]['id'], $published);
				
				//Если что-то есть
				if ($tvs){
					//Если нужно в качестве ключа использовать не индекс и такое поле есть
					if ($resultKey !== false && array_key_exists($resultKey, $tvs)){
						//Записываем результат с соответствующим ключом
						$result[$tvs[$resultKey]] = $tvs;
						
						if ($unsetResultKey){
							unset($result[$tvs[$resultKey]][$resultKey]);
						}
					}else{
						//Просто накидываем по индексу
						$result[] = $tvs;
					}
				}
			}
			
			return $result;
		}
	}
	
	/**
	 * parseFileNameVersion
	 * @version 1.1 (2013-10-10)
	 * 
	 * @desc Parses a file path and gets its name, version & extension.
	 * 
	 * @param $file {string; array} - String of file path or result array of pathinfo() function. @required
	 * 
	 * @return {array: associative} - Array of: 'name' {string} => File name; 'version' => File version; 'extension' => File extension.
	 */
	public static function parseFileNameVersion($file){
		//Если сразу передали массив
		if (is_array($file)){
			//Просто запоминаем его
			$fileinfo = $file;
			//А также запоминаем строку
			$file = $fileinfo['dirname'].'/'.$fileinfo['basename'];
		//Если передали строку
		}else{
			//Получаем необходимые данные
			$fileinfo = pathinfo($file);
		}
		
		//Fail by default
		$result = array(
			'name' => strtolower($file),
			'version' => '0',
			'extension' => !$fileinfo['extension'] ? '' : $fileinfo['extension']
		);
		
		//Try to get file version [0 — full name, 1 — script name, 2 — version, 3 — all chars after version]
		preg_match('/(\D*?)-?(\d(?:\.\d+)*(?:-?[A-Za-z])*)(.*)/', $fileinfo['basename'], $match);
		
		//If not fail
		if (count($match) >= 4){
			$result['name'] = strtolower($match[1]);
			$result['version'] = strtolower($match[2]);
		}
		
		return $result;
	}
	
	/**
	 * regEmptyClientScript
	 * @version 1.0.1 (2013-03-12)
	 * 
	 * @desc Adds a required JS-file into a required MODX inner list according to its version and name. The method is used to register the scripts, that has already been connected manually.
	 * Be advised that the method does not add script code, but register its name and version to avoid future connections with $modx->regClientScript and $modx->regClientStartupScript, and the script code will be deleted if the script had been connected with $modx->regClientScript or $modx->regClientStartupScript.
	 * 
	 * @see ddRegJsCssLinks snippet (http://code.divandesign.biz/modx/ddregjscsslinks), предназначенный для «правильного» подключения js и css. Даже при «ручном» подключении сниппет регистрирует то, что подключил, используя данный метод.
	 * 
	 * The parameters ara passed as an associative array, where:
	 * @param $name {string} - Script name. @required
	 * @param $version {string} - Script version. Default: '0'.
	 * @param $startup {boolean} - Is the script connected in the <head>? Default: false.
	 * 
	 * @return {array: associative} - Array of: 'name' {string} => Script name; 'version' {string} => Script version (если был ранее подключен более поздняя версия, вернётся она); 'useThisVer' {boolean} => Использовалась ли та версия, что передали; 'startup' {boolean} => Подключён ли скрипт в <head>?; 'pos' {integer} => Ключ зарегистрированного скрипта в соответствующем внутреннем массиве MODx.
	 */
	public static function regEmptyClientScript($options = array('name' => '', 'version' => '0', 'startup' => false)){
		global $modx;
		
		//Если ничего не передали или не передали хотя бы имя
		if (!is_array($options) || !isset($options['name']) || empty($options['name'])){
			//С пляжу
			return '';
		}
		
		//Приведём имя к нижнему регистру (чтоб сравнивать потом проще было, ведь нам пофиг)
		$name = strtolower($options['name']);
		//Если версия не задана, будет нулевая (полезно дальше при сравнении version_compare)
		$version = isset($options['version']) ? strtolower($options['version']) : '0';
		//Куда подключён скрипт: перед </head>, или перед </body>
		$startup = isset($options['startup']) ? $options['startup'] : false;
		//Ну мало ли
		unset($overwritepos);
		
		//По дефолту юзаем эту версию
		$useThisVer = true;
		
		//Если такой скрипт ужебыл подключён
		if (isset($modx->loadedjscripts[$name])){
			//Если он подключался в <header>
			if ($modx->loadedjscripts[$name]['startup']){
				//Этот пусть будет так же
				$startup = true;
			}
			
			//Сравниваем версию раннее подключённого скрипта с текущей: если старая меньше новой, надо юзать новую, иначе — старую
			$useThisVer = version_compare($modx->loadedjscripts[$name]['version'], $version, '<');
			
			//Если надо юзать старую версию
			if (!$useThisVer){
				//Запомним версию как старую. Здесь нам пофиг на его код, ведь новый код будет подключен мануально.
				$version = $modx->loadedjscripts[$name]['version'];
			}
			
			//Если новая версия должна подключаться в <header>, а старая подключалась перед </body>
			if ($startup == true && $modx->loadedjscripts[$name]['startup'] == false){
				//Снесём старый скрипт из массива подключения перед </body> (ведь новая подключится в <head>). Здесь нам пофиг на его код, ведь новый код будет подключен мануально.
				unset($modx->jscripts[$modx->loadedjscripts[$name]['pos']]);
				//Если новая версия должна подключаться перед </body> или старая уже подключалась перед </head>. На самом деле, сработает только если обе перед </body> или обе перед </head>, т.к. если старая была перед </head>, то новая выставится также кодом выше.
			}else{
				//Запомним позицию старого скрипта (порядок подключения может быть важен для зависимых скриптов), на новую пофиг. Дальше код старой просто перетрётся в соответсвтии с позицией.
				$overwritepos = $modx->loadedjscripts[$name]['pos'];
			}
		}
		
		//Если надо подключить перед </head>
		if ($startup){
			//Позиция такова: либо старая (уже вычислена), либо максимальное значение между нолём и одним из ключей массива подключённых скриптов + 1 (это, чтобы заполнить возможные дыры)
			$pos = isset($overwritepos) ? $overwritepos : max(array_merge(array(0), array_keys($modx->sjscripts))) + 1;
			if ($useThisVer){
				//Запоминаем пустую строку подключения в нужный массив, т.к. подключаем мануально.
				$modx->sjscripts[$pos] = '';
			}
		//Если надо подключить перед </body>, то всё по аналогии, только массив другой
		}else{
			$pos = isset($overwritepos) ? $overwritepos : max(array_merge(array(0), array_keys($modx->jscripts))) + 1;
			if ($useThisVer){
				$modx->jscripts[$pos] = '';
			}
		}
		
		//Запомним новоиспечённый скрипт для последующих обработок
		$modx->loadedjscripts[$name]['version'] = $version;
		$modx->loadedjscripts[$name]['startup'] = $startup;
		$modx->loadedjscripts[$name]['pos'] = $pos;
		
		return array(
			'name' => $name,
			'version' => $version,
			'useThisVer' => $useThisVer,
			'startup' => $startup,
			'pos' => $pos
		);
	}
	
	/**
	 * getDocumentIdByUrl
	 * @version 1.1 (2013-08-30)
	 * 
	 * @desc Gets id of a document by its url.
	 * 
	 * @param $url {string} - @required
	 * 
	 * @return {integer} - Document ID.
	 */
	public static function getDocumentIdByUrl($url){
		global $modx;
		
		$url = parse_url($url);
		$path = $url['path'];
		
		//Если в адресе не было хоста, значит он относительный
		if (empty($url['host'])){
			//Получаем хост из конфига
			$siteHost = parse_url($modx->getConfig('site_url'));
			
			//На всякий случай вышережем host из адреса (а то вдруг url просто без http:// передали) + лишние слэши по краям
			$path = trim($path, $siteHost['host'].'/');
		}else{
			//Просто убираем лишние слэши по краям
			$path = trim($url['path'], '/');
		}
		
		//Если путь пустой, то мы в корне
		if ($path == ''){
			return $modx->getConfig('site_start');
		//Если документ с таким путём есть
		}else if (!empty($modx->documentListing[$path])){
			//Возвращаем его id
			return $modx->documentListing[$path];
		//В противном случае возвращаем 0
		}else{
			return 0;
		}
	}
	
	/**
	 * verifyRenamedParams
	 * @version 1.0 (2014-05-23)
	 * 
	 * @desc The method checks an array for deprecated parameters and writes warning messages into the MODX event log. It returns an associative array, in which the correct parameter names are the keys and the parameter values are the values. You can use the “exctract” function to turn the array into variables of the current symbol table.
	 * 
	 * @param $params {array} - The associative array of the parameters of a snippet, in which the parameter names are the keys and the parameter values are the values. You can directly pass here the “$params” variable if you call the method inside of a snippet. @required
	 * @param $compliance {array} - An array of correspondence between old parameter names and new ones, in which the new names are the keys and the old names are the values. @required
	 * 
	 * @return {array} - An associative array, in which the correct parameter names are the keys and the parameter values are the values.
	 */
	public static function verifyRenamedParams($params, $compliance){
		$result = array();
		$msg = array();
		
		//Перебираем таблицу соответствия
		foreach ($compliance as $new => $old){
			//Если старый параметр задан, а новый — нет
			if (isset($params[$old]) && !isset($params[$new])){
				//Зададим
				$result[$new] = $params[$old];
				$msg[] .= '<li>“'.$old.'” must be renamed as “'.$new.'”;</li>';
			}
		}
		
		if (count($result) > 0){
			global $modx;
			
			$modx->logEvent(1, 2, '<p>Some of the snippet parameters have been renamed. Please, correct the following parameters:</p><ul>'.implode('', $msg).'</ul><br /><p>The snippet has been called in the document with id '.$modx->documentIdentifier.'.</p>', $modx->currentSnippet);
		}
		
		return $result;
	}
	
	/**
	 * removeDir
	 * @version 1.0 (2013-03-09)
	 * 
	 * @desc Removes a required folder with all contents recursively.
	 * 
	 * @param $dir {string} - Path to the directory, that should removed. @required
	 * 
	 * @return {boolean}
	 */
	public static function removeDir($dir){
		//Если не существует, ок
		if (!file_exists($dir)){return true;}
		
		//Получаем файлы в директории
		$files = array_diff(scandir($dir), array('.','..'));
		
		foreach ($files as $file){
			//Если это папка, обработаем её
			if (is_dir("$dir/$file")){
				self::removeDir("$dir/$file");
			}else{
				unlink("$dir/$file");
			}
		}
		
		return rmdir($dir);
	}
	
	/**
	 * generateRandomString
	 * @version 1.0 (2012-02-13)
	 * 
	 * @desc Generate random string with necessary length.
	 * 
	 * @param $length {integer} - Length of output string. Default: 8.
	 * @param $chars {string} - Chars to generate. Default: 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789'.
	 * 
	 * @return {string}
	 */
	public static function generateRandomString($length = 8, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789'){
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789';
		$numChars = strlen($chars);
		$string = '';
		for ($i = 0; $i < $length; $i++){
			$string .= substr($chars, rand(1, $numChars) - 1, 1);
		}
		
		return $string;
	}
}

//Решение спорное, но делать Синглтон очень не хотелось
foreach (ddTools::$tables as $key => $val){
	ddTools::$tables[$key] = $modx->getFullTableName($key);
}

if (method_exists($modx, 'getVersionData')){
	//В новом MODX в метод можно просто передать 'version' и сразу получить нужный элемент, но не в старом
	$modxVersionData = $modx->getVersionData();
	
	//If version of MODX > 1.0.11
	if (version_compare($modxVersionData['version'], '1.0.11', '>')){
		ddTools::$documentFields[] = 'alias_visible';
	}
}
}
?>