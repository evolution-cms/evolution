<?php
// ---------------------------------------------------------------
// :: Updater
// ----------------------------------------------------------------
//
//
//
// ----------------------------------------------------------------
// :: Copyright & Licencing
// ----------------------------------------------------------------
//
//   GNU General Public License (GPL - http://www.gnu.org/copyleft/gpl.html)
//

$_lang['pluginname'] = 'Updater';
$_lang['system_update'] = 'Je dostupná nová verzia CMS';
$_lang["cms_outdated_msg"] = 'Systém na správu obsahu je zastaraný. Pre aktualizáciu kontaktujte vývojárov stránky. Aktuálna verzia';
$_lang['bkp_before_msg'] = 'Pred aktualizáciou systému dôrazne odporúčame vytvoriť zálohu. Aktualizáciu vykonávate na vlastné riziko!';
$_lang['updateButton_txt'] = 'Aktualizovať na verziu';
//Error Messages
$_lang['error_curl'] = 'V PHP je potrebné povoliť rozšírenie CURL';
$_lang['error_zip'] = 'V PHP je potrebné povoliť rozšírenie ZIP';
$_lang['error_openssl'] = 'V PHP je potrebné povoliť rozšírenie OpenSSL';
$_lang['error_overwrite'] = 'Súbory Evolution CMS nie je možné prepísať';

$_lang['artisan_update'] = 'Pre aktualizáciu spustite konzolový príkaz z priečinka <b>core</b>: <b>php artisan make:site update</b>';
$_lang['updater_notice_backup_warning'] = 'Pred aktualizáciou nezabudnite vytvoriť zálohu stránky.';
$_lang['updater_backup_action_label'] = 'vytvoriť zálohu';
$_lang['updater_live_update_button'] = 'Ručná aktualizácia (WEB)';
$_lang['updater_line_mode_notice'] = 'Táto stránka sleduje %s, kde sú nové commity. Ak sa chcete vrátiť k vydaniu, spustite php artisan make:site update <release> v priečinku core.';
$_lang['updater_action_compare'] = 'Zobraziť zmeny';
$_lang["help_donate_msg"] = 'Kúpte vývojárom Evolution CMS kávu na <a href="https://ko-fi.com/evolutioncms" target="_blank">ko-fi.com/evolutioncms ☕</a>. Staňte sa fanúšikom Evolution CMS ❤️ ešte dnes!';
$_lang['are_you_sure_update'] = 'Naozaj chcete spustiť aktualizáciu systému?';
$_lang['updateButtonCommit_txt'] = 'Aktualizovať na tento commit';
$_lang['table_commitdate'] = 'Dátum commitu';
$_lang['table_titleauthor'] = 'Názov (autor)';
$_lang['error_failedtogetfeed'] = 'Nepodarilo sa načítať kanál';
$_lang['updater_notice_title'] = 'Je dostupná nová verzia CMS';
$_lang['updater_notice_text_1'] = 'Aktualizácia je voliteľná, no výrazne zlepšuje stabilitu, výkon a ochranu pred škodlivým softvérom a útokmi DDoS.';
$_lang['updater_notice_text_2'] = 'Systém môžete aktualizovať ručne. Tento proces vyžaduje technické znalosti, preto je vhodnejšie zveriť ho správcovi systému.';
$_lang['updater_notice_text_3'] = 'Prípadne požiadajte svojich vývojárov o vykonanie aktualizácie.';
$_lang['updater_versions_line'] = 'Aktuálna: %s -> Dostupná: %s';
$_lang['updater_severity_critical'] = 'Kritické';
$_lang['updater_severity_warning'] = 'Upozornenie';
$_lang['updater_severity_info'] = 'Informácia';
$_lang['updater_action_release'] = 'Zoznam zmien';
$_lang['updater_action_release_all'] = 'Všetky vydania';
$_lang['updater_action_branch'] = 'Zobraziť vetvu/ref';
$_lang['updater_action_branch_all'] = 'Všetky vetvy';
$_lang['updater_action_support'] = 'Kontaktovať vývojárov';
$_lang['updater_action_hide_day'] = 'Nezobrazovať 1 deň';
$_lang['updater_action_hide_today'] = 'Dnes už túto správu nezobrazovať';
$_lang['updater_support_hint'] = 'Podpora:';
$_lang['updater_mail_subject'] = '[EVO] Žiadosť o aktualizáciu stránky %s: %s -> %s';
$_lang['updater_mail_line_site'] = 'Stránka: %s';
$_lang['updater_mail_line_update'] = 'Aktualizácia: %s -> %s';
$_lang['updater_mail_line_request'] = 'Prosím, odhadnite termín a cenu.';
$_lang['updater_widget_title'] = 'Je dostupná nová verzia CMS';
$_lang['updater_badge_current'] = 'Aktuálna';
$_lang['updater_badge_available'] = 'Dostupná';
$_lang['updater_current_full'] = 'Úplná verzia: %s';
$_lang['updater_action_support_with_email'] = 'Kontaktovať vývojárov: %s';
$_lang['updater_cli_summary'] = 'Ručná aktualizácia (CLI)';
$_lang['updater_cli_intro'] = 'Ak aktualizujete ručne, spustite v termináli tento príkaz:';
$_lang['updater_cli_command'] = 'php artisan make:site update';
$_lang['updater_branch_target_label'] = 'Vetva/ref';
$_lang['updater_branch_mode_notice'] = 'Je zapnutý režim aktualizácie vývojovej vetvy/ref. Používajte ho iba na testovanie alebo riadenú údržbu.';
$_lang['updater_live_update_title'] = 'Aktualizácia systému';
$_lang['updater_live_update_intro'] = 'Plánovač a pracovný proces sú dostupné, takže Evolution CMS môže zaradiť aktualizáciu do frontu a priebežne tu zobrazovať jej postup.';
$_lang['updater_live_update_backup'] = 'Pred pokračovaním vytvorte a overte zálohu. Aktualizátor mení súbory aj údaje v databáze.';
$_lang['updater_live_update_backup_checkbox'] = 'Pred aktualizáciou vytvoriť zálohu databázy';
$_lang['updater_live_update_current'] = 'Aktuálna verzia databázy';
$_lang['updater_live_update_current_version'] = 'aktuálna verzia databázy';
$_lang['updater_live_update_target'] = 'Cieľová verzia';
$_lang['updater_live_update_branch_target'] = 'Cieľová vetva/ref';
$_lang['updater_live_update_repository'] = 'Repozitár';
$_lang['updater_live_update_health'] = 'Plánovač / pracovný proces';
$_lang['updater_live_update_confirm'] = 'Spustiť aktualizáciu';
$_lang['updater_live_update_cancel'] = 'Zrušiť';
$_lang['updater_live_update_queueing'] = 'Zaraďovanie aktualizácie do frontu...';
$_lang['updater_live_update_queued'] = 'Úloha aktualizácie bola zaradená do frontu. Čaká sa na pracovný proces...';
$_lang['updater_live_update_status'] = 'Stav';
$_lang['updater_live_update_step'] = 'Krok';
$_lang['updater_live_update_progress'] = 'Priebeh';
$_lang['updater_live_update_close'] = 'Zavrieť';
$_lang['updater_live_update_close_reload'] = 'Zavrieť a obnoviť';
$_lang['updater_live_update_completed'] = 'Aktualizácia je dokončená. Zatvorte toto okno, aby sa obnovil správca a mohli ste overiť novú verziu.';
$_lang['updater_live_update_response_changed'] = 'Odpoveď správcu sa počas aktualizácie zmenila. Zatvorte toto okno, aby sa správca obnovil a zobrazil konečný stav.';
$_lang['updater_live_update_failed'] = 'Aktualizáciu sa nepodarilo spustiť.';
$_lang['updater_live_update_invalid_response'] = 'Správca vrátil neplatnú odpoveď aktualizácie.';
