<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 21.05.2016
 * Time: 11:51
 */

setlocale(LC_ALL, 'ru_RU.UTF-8');

$_lang = array();
$_lang['reminder.default_skipTpl'] = '@CODE:Авторизовані користувачі не можуть відновлювати пароль.';
$_lang['reminder.default_reportTpl'] = '@CODE:Для відновлення пароля перейдіть за посиланням: <a href="[+reset.url+]">[+reset.url+]</a>';
$_lang['reminder.users_only'] = 'Тільки зареєстровані користувачі можуть відновлювати паролі.';
$_lang['reminder.update_failed'] = 'Не вдалося виконати операцію.';
$_lang['reminder.default_successTpl'] = '@CODE:Вам відправлено лист з посиланням для відновлення пароля.';
$_lang['reminder.default_resetSuccessTpl'] = '@CODE:Вам відправлено лист з новим паролем.';

return $_lang;
