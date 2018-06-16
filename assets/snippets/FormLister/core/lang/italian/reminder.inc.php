<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 21.05.2016
 * Time: 11:51
 */

$_lang = array();
$_lang['reminder.default_skipTpl'] = '@CODE:Devi fare logout per ripristinare la password.';
$_lang['reminder.default_reportTpl'] = '@CODE:Per ripristinare la password, procedere con il link: <a href="[+reset.url+]">[+reset.url+]</a>';
$_lang['reminder.users_only'] = 'Solo gli utenti registrati possono ripristinare le password.';
$_lang['reminder.update_failed'] = 'Impossibile procedere.';
$_lang['reminder.default_successTpl'] = '@CODE:Il link per ripristinare la password è stato inviato per posta.';
$_lang['reminder.default_resetSuccessTpl'] = '@CODE:La nuova password è stata inviata via mail.';

return $_lang;
