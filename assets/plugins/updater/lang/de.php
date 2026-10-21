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
$_lang['system_update'] = 'Eine neue CMS-Version ist verfügbar';
$_lang['are_you_sure_update'] = 'Möchten Sie wirklich ein Systemupdate durchführen?';
$_lang["cms_outdated_msg"] = 'Das Content-Management-System ist veraltet. Wenden Sie sich zum Aktualisieren an die Site-Entwickler. Aktuelle Version';
$_lang['bkp_before_msg'] = 'Wir empfehlen dringend, vor dem Upgrade des Systems ein Backup zu erstellen. Das Update erfolgt auf eigene Gefahr!!';
$_lang['updateButton_txt'] = 'Update auf Version';
$_lang['updateButtonCommit_txt'] = 'Update auf diesen Commit';
$_lang['table_commitdate'] = 'Commit-Datum';
$_lang['table_titleauthor'] = 'Titel (Autor)';
//Error Messages
$_lang['error_curl'] = 'Sie müssen die CURL-Funktion in PHP aktivieren';
$_lang['error_zip'] = 'ZIP muss in PHP aktiviert werden';
$_lang['error_openssl'] = 'Sie müssen die OpenSSL-Funktion in PHP aktivieren';
$_lang['error_overwrite'] = 'Evolution CMS-Dateien können nicht überschrieben werden';
$_lang['error_failedtogetfeed'] = 'Feed konnte nicht abgerufen werden';

$_lang['artisan_update'] = 'Führen Sie zum Aktualisieren den Konsolenbefehl aus dem Ordner <b>core</b> aus: <b>php artisan make:site update</b>';
$_lang['updater_notice_backup_warning'] = 'Vergessen Sie nicht, vor dem Update ein Backup der Website zu erstellen.';
$_lang['updater_backup_action_label'] = 'ein Backup der Website erstellen';
$_lang['updater_live_update_button'] = 'Manuelles Update (WEB)';
$_lang['updater_line_mode_notice'] = 'Diese Website folgt %s, wofür neue Commits vorliegen. Um zu einem Release zurückzukehren, führen Sie php artisan make:site update <release> im Ordner core aus.';
$_lang['updater_action_compare'] = 'Änderungen anzeigen';
$_lang['help_donate_msg'] = 'Spendieren Sie den Entwicklern von Evolution CMS einen Kaffee auf <a href="https://ko-fi.com/evolutioncms" target="_blank">ko-fi.com/evolutioncms ☕</a>. Werden Sie noch heute ein Fan von Evolution CMS ❤️!';
$_lang['updater_notice_title'] = 'Eine neue CMS-Version ist verfügbar';
$_lang['updater_notice_text_1'] = 'Das Update ist optional, verbessert aber die Stabilität, Leistung und den Schutz vor Schadsoftware und DDoS-Angriffen erheblich.';
$_lang['updater_notice_text_2'] = 'Sie können das System manuell aktualisieren. Dafür sind technische Kenntnisse erforderlich. Überlassen Sie dies daher am besten Ihrem Systemadministrator.';
$_lang['updater_notice_text_3'] = 'Oder wenden Sie sich für das Update an Ihre Entwickler.';
$_lang['updater_versions_line'] = 'Aktuell: %s -> Verfügbar: %s';
$_lang['updater_severity_critical'] = 'Kritisch';
$_lang['updater_severity_warning'] = 'Warnung';
$_lang['updater_severity_info'] = 'Information';
$_lang['updater_action_release'] = 'Änderungsprotokoll';
$_lang['updater_action_release_all'] = 'Alle Releases';
$_lang['updater_action_branch'] = 'Branch/Referenz anzeigen';
$_lang['updater_action_branch_all'] = 'Alle Branches';
$_lang['updater_action_support'] = 'Entwickler kontaktieren';
$_lang['updater_action_hide_day'] = '1 Tag lang nicht anzeigen';
$_lang['updater_action_hide_today'] = 'Diese Meldung heute nicht mehr anzeigen';
$_lang['updater_support_hint'] = 'Support:';
$_lang['updater_mail_subject'] = '[EVO] Anfrage zur Website-Aktualisierung %s: %s -> %s';
$_lang['updater_mail_line_site'] = 'Website: %s';
$_lang['updater_mail_line_update'] = 'Update: %s -> %s';
$_lang['updater_mail_line_request'] = 'Bitte schätzen Sie den Zeitaufwand und die Kosten.';
$_lang['updater_widget_title'] = 'Eine neue CMS-Version ist verfügbar';
$_lang['updater_badge_current'] = 'Aktuell';
$_lang['updater_badge_available'] = 'Verfügbar';
$_lang['updater_current_full'] = 'Vollständige Version: %s';
$_lang['updater_action_support_with_email'] = 'Entwickler kontaktieren: %s';
$_lang['updater_cli_summary'] = 'Manuelles Update (CLI)';
$_lang['updater_cli_intro'] = 'Führen Sie bei einem manuellen Update diesen Befehl im Terminal aus:';
$_lang['updater_cli_command'] = 'php artisan make:site update';
$_lang['updater_branch_target_label'] = 'Branch/Referenz';
$_lang['updater_branch_mode_notice'] = 'Der Update-Modus für Entwicklungsbranches/Referenzen ist aktiviert. Verwenden Sie ihn nur für Tests oder kontrollierte Wartungsarbeiten.';
$_lang['updater_live_update_title'] = 'Systemupdate';
$_lang['updater_live_update_intro'] = 'Zeitplaner und Worker sind verfügbar. Evolution CMS kann das Update daher in die Warteschlange stellen und den Fortschritt hier live anzeigen.';
$_lang['updater_live_update_backup'] = 'Erstellen und überprüfen Sie vor dem Fortfahren ein Backup. Das Update ändert Dateien und Datenbankinhalte.';
$_lang['updater_live_update_backup_checkbox'] = 'Vor dem Update ein Datenbank-Backup erstellen';
$_lang['updater_live_update_current'] = 'Aktuelle Datenbankversion';
$_lang['updater_live_update_current_version'] = 'aktuelle Datenbankversion';
$_lang['updater_live_update_target'] = 'Zielversion';
$_lang['updater_live_update_branch_target'] = 'Zielbranch/Referenz';
$_lang['updater_live_update_repository'] = 'Repository';
$_lang['updater_live_update_health'] = 'Zeitplaner / Worker';
$_lang['updater_live_update_confirm'] = 'Update starten';
$_lang['updater_live_update_cancel'] = 'Abbrechen';
$_lang['updater_live_update_queueing'] = 'Update wird in die Warteschlange gestellt...';
$_lang['updater_live_update_queued'] = 'Update-Aufgabe wurde in die Warteschlange gestellt. Warten auf Worker...';
$_lang['updater_live_update_status'] = 'Status';
$_lang['updater_live_update_step'] = 'Schritt';
$_lang['updater_live_update_progress'] = 'Fortschritt';
$_lang['updater_live_update_close'] = 'Schließen';
$_lang['updater_live_update_close_reload'] = 'Schließen und neu laden';
$_lang['updater_live_update_completed'] = 'Update abgeschlossen. Schließen Sie dieses Fenster, um den Manager neu zu laden und die neue Version zu überprüfen.';
$_lang['updater_live_update_response_changed'] = 'Die Antwort des Managers hat sich während des Updates geändert. Schließen Sie dieses Fenster, um den Manager neu zu laden und den endgültigen Status zu lesen.';
$_lang['updater_live_update_failed'] = 'Update konnte nicht gestartet werden.';
$_lang['updater_live_update_invalid_response'] = 'Der Manager hat eine ungültige Update-Antwort zurückgegeben.';
