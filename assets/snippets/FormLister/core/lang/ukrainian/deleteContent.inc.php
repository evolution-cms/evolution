<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 15.05.2016
 * Time: 1:26
 */

setlocale(LC_ALL, 'ru_RU.UTF-8');

$_lang = array();
$_lang['deleteContent.default_skipTpl'] = '@CODE:Тільки зареєстровані користувачі можуть видаляти записи.';
$_lang['deleteContent.default_successTpl'] = '@CODE:Запис успішно видалений.';
$_lang['deleteContent.default_badOwnerTpl'] = '@CODE:Тільки автор може видалити запись.';
$_lang['deleteContent.default_badRecordTpl'] = '@CODE:Ви не можете видалити цей запис.';
$_lang['deleteContent.delete_failed'] = 'Не вдалося видалити запис.';

return $_lang;
