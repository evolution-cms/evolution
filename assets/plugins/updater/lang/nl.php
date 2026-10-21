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
$_lang['system_update'] = 'Er is een nieuwe CMS-versie beschikbaar';
$_lang["cms_outdated_msg"] = 'Het Content Management System is verouderd. Om deze bij te werken neem contact op met de website ontwikkelaar. Huidige versie';
$_lang['bkp_before_msg'] = 'We raden het maken van een back-up aan voor de upgrade van het systeem. De update wordt uitgevoerd op eigen risico!';
$_lang['updateButton_txt'] = 'Bijwerken naar versie';
//Error Messages
$_lang['error_curl'] = 'Het is noodzakelijk om de CURL in PHP in te schakelen';
$_lang['error_zip'] = 'Het is noodzakelijk om de ZIP in PHP in te schakelen';
$_lang['error_openssl'] = 'U moet de OpenSSL-functie in PHP inschakelen';
$_lang['error_overwrite'] = 'Bestanden kunnen niet worden overschreven';

$_lang['artisan_update'] = 'Om bij te werken, moet u de console-opdracht vanuit de map uitvoeren <b>core</b>: <b>php artisan make:site update</b>';
$_lang['updater_notice_backup_warning'] = 'Vergeet niet een back-up van de site te maken voordat u bijwerkt.';
$_lang['updater_backup_action_label'] = 'een back-up van de site te maken';
$_lang['updater_live_update_button'] = 'Handmatige update (WEB)';
$_lang['updater_line_mode_notice'] = 'Deze site volgt %s, waarvoor nieuwe commits beschikbaar zijn. Voer php artisan make:site update <release> uit in de map core om terug te keren naar een release.';
$_lang['updater_action_compare'] = 'Wijzigingen bekijken';
$_lang['help_donate_msg'] = 'Trakteer de ontwikkelaars van Evolution CMS op koffie via <a href="https://ko-fi.com/evolutioncms" target="_blank">ko-fi.com/evolutioncms ☕</a>. Word vandaag nog fan van Evolution CMS ❤️!';
$_lang['are_you_sure_update'] = 'Weet u zeker dat u een systeemupdate wilt uitvoeren?';
$_lang['updateButtonCommit_txt'] = 'Bijwerken naar deze commit';
$_lang['table_commitdate'] = 'Commitdatum';
$_lang['table_titleauthor'] = 'Titel (auteur)';
$_lang['error_failedtogetfeed'] = 'Kan feed niet ophalen';
$_lang['updater_notice_title'] = 'Er is een nieuwe CMS-versie beschikbaar';
$_lang['updater_notice_text_1'] = 'Bijwerken is optioneel, maar verbetert de stabiliteit, prestaties en bescherming tegen malware en DDoS-aanvallen aanzienlijk.';
$_lang['updater_notice_text_2'] = 'U kunt het systeem handmatig bijwerken. Dit vereist technische kennis, dus u kunt dit het beste aan uw systeembeheerder overlaten.';
$_lang['updater_notice_text_3'] = 'Of neem contact op met uw ontwikkelaars om de update uit te voeren.';
$_lang['updater_versions_line'] = 'Huidig: %s -> Beschikbaar: %s';
$_lang['updater_severity_critical'] = 'Kritiek';
$_lang['updater_severity_warning'] = 'Waarschuwing';
$_lang['updater_severity_info'] = 'Informatie';
$_lang['updater_action_release'] = 'Wijzigingslogboek';
$_lang['updater_action_release_all'] = 'Alle releases';
$_lang['updater_action_branch'] = 'Branch/ref bekijken';
$_lang['updater_action_branch_all'] = 'Alle branches';
$_lang['updater_action_support'] = 'Contact opnemen met ontwikkelaars';
$_lang['updater_action_hide_day'] = '1 dag niet tonen';
$_lang['updater_action_hide_today'] = 'Dit bericht vandaag niet meer tonen';
$_lang['updater_support_hint'] = 'Ondersteuning:';
$_lang['updater_mail_subject'] = '[EVO] Updateverzoek voor site %s: %s -> %s';
$_lang['updater_mail_line_site'] = 'Site: %s';
$_lang['updater_mail_line_update'] = 'Update: %s -> %s';
$_lang['updater_mail_line_request'] = 'Graag een schatting van de doorlooptijd en kosten.';
$_lang['updater_widget_title'] = 'Er is een nieuwe CMS-versie beschikbaar';
$_lang['updater_badge_current'] = 'Huidig';
$_lang['updater_badge_available'] = 'Beschikbaar';
$_lang['updater_current_full'] = 'Volledige versie: %s';
$_lang['updater_action_support_with_email'] = 'Contact opnemen met ontwikkelaars: %s';
$_lang['updater_cli_summary'] = 'Handmatige update (CLI)';
$_lang['updater_cli_intro'] = 'Als u handmatig bijwerkt, voer dan dit commando uit in de terminal:';
$_lang['updater_cli_command'] = 'php artisan make:site update';
$_lang['updater_branch_target_label'] = 'Branch/ref';
$_lang['updater_branch_mode_notice'] = 'De updatemodus voor een ontwikkelbranch/ref is ingeschakeld. Gebruik deze alleen voor tests of gecontroleerd onderhoud.';
$_lang['updater_live_update_title'] = 'Systeemupdate';
$_lang['updater_live_update_intro'] = 'De taakplanner en worker zijn beschikbaar. Evolution CMS kan de update dus in de wachtrij plaatsen en hier de voortgang live tonen.';
$_lang['updater_live_update_backup'] = 'Maak een gecontroleerde back-up voordat u doorgaat. De updater wijzigt bestanden en databasegegevens.';
$_lang['updater_live_update_backup_checkbox'] = 'Databaseback-up maken vóór de update';
$_lang['updater_live_update_current'] = 'Huidige databaseversie';
$_lang['updater_live_update_current_version'] = 'huidige databaseversie';
$_lang['updater_live_update_target'] = 'Doelversie';
$_lang['updater_live_update_branch_target'] = 'Doelbranch/ref';
$_lang['updater_live_update_repository'] = 'Repository';
$_lang['updater_live_update_health'] = 'Taakplanner / worker';
$_lang['updater_live_update_confirm'] = 'Update starten';
$_lang['updater_live_update_cancel'] = 'Annuleren';
$_lang['updater_live_update_queueing'] = 'Update in wachtrij plaatsen...';
$_lang['updater_live_update_queued'] = 'Updatetaak in wachtrij geplaatst. Wachten op worker...';
$_lang['updater_live_update_status'] = 'Status';
$_lang['updater_live_update_step'] = 'Stap';
$_lang['updater_live_update_progress'] = 'Voortgang';
$_lang['updater_live_update_close'] = 'Sluiten';
$_lang['updater_live_update_close_reload'] = 'Sluiten en opnieuw laden';
$_lang['updater_live_update_completed'] = 'Update voltooid. Sluit dit venster om het beheerpaneel opnieuw te laden en de nieuwe versie te controleren.';
$_lang['updater_live_update_response_changed'] = 'De reactie van het beheerpaneel is gewijzigd tijdens de update. Sluit dit venster om het beheerpaneel opnieuw te laden en de eindstatus te bekijken.';
$_lang['updater_live_update_failed'] = 'Kan update niet starten.';
$_lang['updater_live_update_invalid_response'] = 'Het beheerpaneel heeft een ongeldig antwoord op de update geretourneerd.';
