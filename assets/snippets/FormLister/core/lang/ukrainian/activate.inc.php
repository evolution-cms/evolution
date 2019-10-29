<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 21.05.2016
 * Time: 11:51
 */

setlocale(LC_ALL, 'ru_RU.UTF-8');

$_lang = array();
$_lang['activate.default_skipTpl'] = '@CODE:Ваш обліковий запис активовано';
$_lang['activate.default_reportTpl'] = '@CODE:Для активації облікового запису перейдіть за посиланням: <a href="[+reset.url+]">[+reset.url+]</a>';
$_lang['activate.no_activation'] = 'Цей обліковий запис не вимагає активації або не може бути активованим.';
$_lang['activate.update_failed'] = 'Не вдалося виконати операцію.';
$_lang['activate.default_successTpl'] = '@CODE:Вам відправлено лист з посиланням для активації облікового запису.';
$_lang['activate.default_activateSuccessTpl'] = '@CODE:Обліковий запис успішно активований.';

return $_lang;
