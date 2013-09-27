<?php
/**
 * modx ddTools class
 * @version: 0.1 (2012-02-13)
 * 
 * @uses modx 1.5 (Evo)
 *
 * @copyright Copyright 2012, DivanDesign
 * http://www.DivanDesign.ru
 */

if (!class_exists('ddTools')){
class ddTools {
	/**
	 * createDocument
	 * @version 1.0 (2012-02-13)
	 *
	 * Создаёт новый документ.
	 *
	 * @param $fields {array} - Ассоциативный массив значений полей документа (в таблице `site_content`).
	 * @param $groups {array} - Индексированный массив id групп, к которым должен принадлежать документ.
	 *
	 * @return {mixed} - ID нового документа или false, если что-то не так.
	 */
	public static function createDocument($fields = array(), $groups = false){
		global $modx;
		
		//Если нет хотя бы заголовка, выкидываем
		if (!$fields['pagetitle']) return false;
			
		//Если не передана дата создания документа, ставим текущую
		if (!$fields['createdon']) $fields['createdon'] = time();
			
		//Если не передано, кем документ создан, ставим 1
		if (!$fields['createdby']) $fields['createdby'] = 1;
			
		//Если группы заданы, то это приватный документ
		if ($groups) $fields['privatemgr'] = 1;
			
		//Если надо публиковать, поставим дату публикации текущей
		if ($fields['published'] == 1) $fields['pub_date'] = $fields['createdon'];
			
		//Вставляем новый документ в базу, получаем id, если что-то пошло не так, выкидываем
		$id = $modx->db->insert($fields, $modx->getFullTableName('site_content'));
			
		//Если заданы группы (и на всякий проверим ID)
		if ($groups && $id){
			//Перебираем все группы
			foreach ($groups as $gr){
				$modx->db->insert(array('document_group' => $gr, 'document' => $id), $modx->getFullTableName('document_groups'));
			}
		}
	
		return $id;
	}
	
	/**
	 * udateDocument
	 * @version 1.0 (2012-02-13)
	 *
	 * Обновляет информацию по документу.
	 *
	 * @desc $id и/или $where должны быть переданы
	 *
	 * @param $id {integer} - ID документа, который необхоидмо отредактировать.
	 * @param $update {array} - Ассоциативный массив значений полей документа (в таблице `site_content`).
	 * @param $where {string} - SQL условие WHERE.
	 * 
	 * @return {mixed} - ID отредактированного документа или false, если что-то не так.
	 */
	public static function udateDocument($id = 0, $update = array(), $where = ''){
		global $modx;
		
		//Формируем WHERE для SQL
		$where = (($id != 0) ? "`id`='$id'" : "") . (($id != 0 && $where != '') ? " OR " : "") . $where;
	
		//Обновляем информацию по документу, получаем id, если что-то пошло не так, выкидываем
		return $modx->db->update($update, $modx->getFullTableName('site_content'), $where);
	}
	
	/**
	 * generateString
	 * @version 1.0 (2012-02-13)
	 *
	 * Генерация строки заданного размера.
	 *
	 * @param $length {integer} - Размер строки на выходе.
	 * @param $chars {string} - Символы для генерации.
	 *
	 * @return {string}
	 */
	public static function generateString($length = 8, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789'){
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789';
		$numChars = strlen($chars);
		$string = '';
		for ($i = 0; $i < $length; $i++){
			$string .= substr($chars, rand(1, $numChars) - 1, 1);
		}
		
		return $string;
	}

	/**
	 * parseText
	 * @version 1.0 (2012-02-13)
	 * 
	 * Аналог модексовского parseChunk, только принимает текст.
	 *
	 * @param $chunk {string} - Строка, которую нужно парсить.
	 * @param $chunkArr {array} - Ассоциативный массив значений.
	 * @param $prefix {string} - Префикс плэйсхолдеров.
	 * @param $suffix {string} - Суффикс плэйсхолдеров.
	 * 
	 * @return {string}
	 */
	public static function parseText($chunk, $chunkArr, $prefix= '[+', $suffix= '+]'){
		global $modx;
		
		//Если значения для парсинга не переданы, ничего не делаем
		if (!is_array($chunkArr)){
			return $chunk;
		}
		
		//TODO: Возможно, стоит убрать в одельный параметр
		$chunk = $modx->mergeDocumentContent($chunk);
		$chunk = $modx->mergeSettingsContent($chunk);
		$chunk = $modx->mergeChunkContent($chunk);
		
		foreach ($chunkArr as $key => $value) {
			$chunk = str_replace($prefix.$key.$suffix, $value, $chunk);
		}
		
		return $chunk;
	}
	
	/**
	 * parseSourse
	 * @version 1.0 (2012-02-13)
	 * 
	 * Парсит ресурс.
	 *
	 * @param $sourse {string}
	 * 
	 * @return {string}
	 */
	public static function parseSourse($sourse){
		global $modx;
		
		return $modx->rewriteUrls($modx->parseDocumentSource($sourse));
	}
}
}

?>