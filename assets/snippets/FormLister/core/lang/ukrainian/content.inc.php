<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 15.05.2016
 * Time: 1:26
 */

setlocale(LC_ALL, 'ru_RU.UTF-8');

$_lang = array();
$_lang['create.default_skipTpl'] = '@CODE:Тільки зареєстровані користувачі можуть створювати записи.';
$_lang['create.default_successTpl'] = '@CODE:Дані успішно збережені.';
$_lang['edit.default_skipEditTpl'] = '@CODE:Тільки зареєстровані користувачі можуть редагувати записи.';
$_lang['edit.default_badOwnerTpl'] = '@CODE:Тільки автор може редагувати цей запис.';
$_lang['edit.default_badRecordTpl'] = '@CODE:Ви не можете редагувати цей запис.';
$_lang['create.default_badGroupTpl'] = '@CODE:У вас немає дозволу створювати записи.';
$_lang['edit.default_badGroupTpl'] = '@CODE:У вас немає дозволу редагувати записи.';
$_lang['edit.update_failed'] = 'Неможливо зберегти дані.';
$_lang['edit.update_success'] = 'Дані успішно збережені.';

return $_lang;
