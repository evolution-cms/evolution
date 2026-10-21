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
$_lang['system_update'] = 'Dostępna jest nowa wersja CMS';
$_lang["cms_outdated_msg"] = 'System zarządzania treścią jest nieaktualny. Aby zaktualizować system skontaktuj się z twórcą strony. Aktualna wersja';
$_lang['bkp_before_msg'] = 'Wysoce zalecamy stworzenie kopii bezpieczeństwa przed aktualizacją systemu. Aktualizację przeprowadzasz na własne ryzyko!';
$_lang['updateButton_txt'] = 'Zaktualizuj do wersji';
//Error Messages
$_lang['error_curl'] = 'Musisz włączyć funkcję CURL w PHP';
$_lang['error_zip'] = 'Wymagane jest włączenie ZIP w PHP';
$_lang['error_openssl'] = 'Musisz włączyć funkcję OpenSSL w PHP';
$_lang['error_overwrite'] = 'Nie udało się nadpisać plików systemu';

$_lang['artisan_update'] = 'Aby zaktualizować, musisz uruchomić polecenie konsoli z folderu  <b>core</b>: <b>php artisan make:site update</b>';
$_lang['updater_notice_backup_warning'] = 'Nie zapomnij utworzyć kopii zapasowej witryny przed aktualizacją.';
$_lang['updater_backup_action_label'] = 'utworzyć kopię zapasową witryny';
$_lang['updater_live_update_button'] = 'Ręczna aktualizacja (WEB)';
$_lang['updater_line_mode_notice'] = 'Ta witryna śledzi %s, gdzie są nowe commity. Aby wrócić do wydania, uruchom php artisan make:site update <release> w folderze core.';
$_lang['updater_action_compare'] = 'Pokaż zmiany';
$_lang['help_donate_msg'] = 'Postaw kawę twórcom Evolution CMS na <a href="https://ko-fi.com/evolutioncms" target="_blank">ko-fi.com/evolutioncms ☕</a>. Zostań fanem Evolution CMS ❤️ już dziś!';
$_lang['are_you_sure_update'] = 'Czy na pewno chcesz przeprowadzić aktualizację systemu?';
$_lang['updateButtonCommit_txt'] = 'Zaktualizuj do tego commita';
$_lang['table_commitdate'] = 'Data commita';
$_lang['table_titleauthor'] = 'Tytuł (autor)';
$_lang['error_failedtogetfeed'] = 'Nie udało się pobrać kanału';
$_lang['updater_notice_title'] = 'Dostępna jest nowa wersja CMS';
$_lang['updater_notice_text_1'] = 'Aktualizacja jest opcjonalna, ale znacznie poprawia stabilność, wydajność oraz ochronę przed złośliwym oprogramowaniem i atakami DDoS.';
$_lang['updater_notice_text_2'] = 'Możesz zaktualizować system ręcznie. Proces wymaga wiedzy technicznej, dlatego najlepiej zlecić go administratorowi systemu.';
$_lang['updater_notice_text_3'] = 'Możesz też skontaktować się z twórcami witryny, aby przeprowadzili aktualizację.';
$_lang['updater_versions_line'] = 'Aktualna: %s -> Dostępna: %s';
$_lang['updater_severity_critical'] = 'Krytyczny';
$_lang['updater_severity_warning'] = 'Ostrzeżenie';
$_lang['updater_severity_info'] = 'Informacja';
$_lang['updater_action_release'] = 'Lista zmian';
$_lang['updater_action_release_all'] = 'Wszystkie wydania';
$_lang['updater_action_branch'] = 'Pokaż gałąź/ref';
$_lang['updater_action_branch_all'] = 'Wszystkie gałęzie';
$_lang['updater_action_support'] = 'Skontaktuj się z twórcami';
$_lang['updater_action_hide_day'] = 'Nie pokazuj przez 1 dzień';
$_lang['updater_action_hide_today'] = 'Nie pokazuj ponownie tej wiadomości dzisiaj';
$_lang['updater_support_hint'] = 'Wsparcie:';
$_lang['updater_mail_subject'] = '[EVO] Prośba o aktualizację witryny %s: %s -> %s';
$_lang['updater_mail_line_site'] = 'Witryna: %s';
$_lang['updater_mail_line_update'] = 'Aktualizacja: %s -> %s';
$_lang['updater_mail_line_request'] = 'Proszę o oszacowanie terminu i kosztu.';
$_lang['updater_widget_title'] = 'Dostępna jest nowa wersja CMS';
$_lang['updater_badge_current'] = 'Aktualna';
$_lang['updater_badge_available'] = 'Dostępna';
$_lang['updater_current_full'] = 'Pełna wersja: %s';
$_lang['updater_action_support_with_email'] = 'Skontaktuj się z twórcami: %s';
$_lang['updater_cli_summary'] = 'Ręczna aktualizacja (CLI)';
$_lang['updater_cli_intro'] = 'Jeśli aktualizujesz ręcznie, uruchom to polecenie w terminalu:';
$_lang['updater_cli_command'] = 'php artisan make:site update';
$_lang['updater_branch_target_label'] = 'Gałąź/ref';
$_lang['updater_branch_mode_notice'] = 'Tryb aktualizacji gałęzi/ref deweloperskiej jest włączony. Używaj go tylko do testów lub kontrolowanych prac konserwacyjnych.';
$_lang['updater_live_update_title'] = 'Aktualizacja systemu';
$_lang['updater_live_update_intro'] = 'Harmonogram i proces roboczy są dostępne, więc Evolution CMS może dodać aktualizację do kolejki i pokazywać tutaj jej postęp na żywo.';
$_lang['updater_live_update_backup'] = 'Przed kontynuowaniem utwórz i zweryfikuj kopię zapasową. Aktualizator zmienia pliki i dane w bazie danych.';
$_lang['updater_live_update_backup_checkbox'] = 'Utwórz kopię zapasową bazy danych przed aktualizacją';
$_lang['updater_live_update_current'] = 'Aktualna wersja bazy danych';
$_lang['updater_live_update_current_version'] = 'aktualna wersja bazy danych';
$_lang['updater_live_update_target'] = 'Wersja docelowa';
$_lang['updater_live_update_branch_target'] = 'Docelowa gałąź/ref';
$_lang['updater_live_update_repository'] = 'Repozytorium';
$_lang['updater_live_update_health'] = 'Harmonogram / proces roboczy';
$_lang['updater_live_update_confirm'] = 'Rozpocznij aktualizację';
$_lang['updater_live_update_cancel'] = 'Anuluj';
$_lang['updater_live_update_queueing'] = 'Dodawanie aktualizacji do kolejki...';
$_lang['updater_live_update_queued'] = 'Zadanie aktualizacji dodane do kolejki. Oczekiwanie na proces roboczy...';
$_lang['updater_live_update_status'] = 'Status';
$_lang['updater_live_update_step'] = 'Krok';
$_lang['updater_live_update_progress'] = 'Postęp';
$_lang['updater_live_update_close'] = 'Zamknij';
$_lang['updater_live_update_close_reload'] = 'Zamknij i odśwież';
$_lang['updater_live_update_completed'] = 'Aktualizacja zakończona. Zamknij to okno, aby odświeżyć panel administracyjny i sprawdzić nową wersję.';
$_lang['updater_live_update_response_changed'] = 'Odpowiedź panelu administracyjnego zmieniła się podczas aktualizacji. Zamknij to okno, aby odświeżyć panel i odczytać stan końcowy.';
$_lang['updater_live_update_failed'] = 'Nie można rozpocząć aktualizacji.';
$_lang['updater_live_update_invalid_response'] = 'Panel administracyjny zwrócił nieprawidłową odpowiedź dotyczącą aktualizacji.';
