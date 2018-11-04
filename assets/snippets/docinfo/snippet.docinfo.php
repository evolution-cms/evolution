<?php
/**
 * DocInfo
 *
 * @category  parser
 * @version   0.4.1
 * @license     GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @param string $field Значение какого поля необходимо достать
 * @param int $docid ID документа
 * @param int $templid ID шаблона документа
 * @param int $limit Максимальная глубина вложенности для поиска по шаблону документа
 * @param int $tv является ли поле TV параметром (0 - нет || 1 - да)
 * @param int $render Преобразовывать ли значение TV параметра в соответствии с его визуальным компонентом
 * @return string Значение поля документа или его TV параметра
 * @author akool, Agel_Nash <Agel_Nash@xaker.ru>
 *
 * @TODO getTemplateVarOutput не применяет визуальный компонент к TV параметрам у которых значение совпадает со
 *     значением по умолчанию
 *
 * @example
 *       [[DocInfo? &docid=`15` &field=`pagetitle`]]
 *       [[DocInfo? &docid=`10` &field=`tvname`]]
 *       [[DocInfo? &docid=`3` &field=`tvname` &render=`1`]]
 *       [[DocInfo? &docid=`3` &templid=`2` &field=`tvname` &limit=`2`]]
 */
if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}
$default_field = array(
    'type', 'contentType', 'pagetitle', 'longtitle', 'description', 'alias', 'link_attributes', 'published', 'pub_date',
    'unpub_date', 'parent', 'isfolder', 'introtext', 'content', 'richtext', 'template', 'menuindex', 'searchable',
    'cacheable', 'createdon', 'createdby', 'editedon', 'editedby', 'deleted', 'deletedon', 'deletedby', 'publishedon',
    'publishedby', 'menutitle', 'donthit', 'privateweb', 'privatemgr', 'content_dispo', 'hidemenu', 'alias_visible'
);
$docid = (isset($docid) && (int)$docid > 0) ? (int)$docid : $modx->documentIdentifier;
$templid = isset($templid) ? (int)$templid : null;
$doc = array();
if ($templid !== null && $templid >= 0) {
    $doc['parent'] = $docid;
    $limit = (isset($limit) && (int)$limit >= 0) ? (int)$limit : 10;
    do {
        $doc = $modx->getDocument($doc['parent'], 'id, parent, template');
        if ($doc === false) {
            return '';
        }
        if ($doc['template'] == (int)$templid) {
            $docid = $doc['id'];
        }
        $limit--;
    } while ($doc['template'] != (int)$templid && (int)$doc['parent'] > 0 && $limit >= 0);
    if ($doc['template'] != (int)$templid) {
        return '';
    }
}
$field = (isset($field)) ? $field : 'pagetitle';
$render = (isset($render)) ? (bool)$render : false;
$output = '';
if (in_array($field, $default_field)) {
    $doc = $modx->getPageInfo($docid, '1', $field);
    $output = $doc[$field];
} else {
    if (isset($render) && true === $render) {
        $tv = $modx->getTemplateVarOutput($field, $docid);
        $output = $tv[$field];
    } else {
        $tv = $modx->getTemplateVar($field, '*', $docid);
        $output = ($tv['value'] != '') ? $tv['value'] : $tv['defaultText'];
    }
}

return $output;
