<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 15.05.2016
 * Time: 1:26
 */

setlocale(LC_ALL, 'ru_RU.UTF-8');

$_lang = array();
$_lang['form.protectSubmit'] = 'Дані успішно відправлені. Немає потреби відправляти їх ще раз.';
$_lang['form.submitLimit'] = 'Відправляти форму можна 1 раз в ';
$_lang['form.minutes'] = 'хв';
$_lang['form.seconds'] = 'сек';
$_lang['form.dateFormat'] = 'm.d.Y в H:i:s';
$_lang['form.default_successTpl'] = '@CODE:Форма успішно відправлено в [+form.date.value+]';
$_lang['form.form_failed'] = 'Не вдалося відправити лист.';

return $_lang;
