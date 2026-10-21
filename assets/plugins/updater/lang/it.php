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
$_lang['system_update'] = 'Aggiornamento di Sistema';
$_lang["cms_outdated_msg"] = 'Il Sistema di gestione dei contenuti è obsoleto. Per aggiornare contattare gli sviluppatori del sito. Versione attuale';
$_lang['bkp_before_msg'] = 'Si consiglia vivamente di fare un backup prima di aggiornare il sistema, l\'aggiornamento viene eseguito a proprio rischio !!';
$_lang['updateButton_txt'] = 'Aggiorna alla versione';
//Error Messages
$_lang['error_curl'] = 'È necessario abilitare la funzione CURL in PHP';
$_lang['error_zip'] = 'È necessario abilitare la funzione ZIP in PHP';
$_lang['error_openssl'] = 'È necessario abilitare la funzione OpenSSL in PHP';
$_lang['error_overwrite'] = 'I file di EVO non sono disponibili per la sovrascrittura';

$_lang['artisan_update'] = 'Per aggiornare, è necessario eseguire il comando della console dalla cartella  <b>core</b>: <b>php artisan make:site update</b>';
$_lang['updater_notice_backup_warning'] = 'Non dimenticare di creare un backup del sito prima dell\'aggiornamento.';
$_lang['updater_backup_action_label'] = 'creare un backup del sito';
$_lang['updater_live_update_button'] = 'Aggiornamento manuale (WEB)';
$_lang['updater_line_mode_notice'] = 'Questo sito segue %s, che ha nuovi commit. Per tornare a una release, esegui php artisan make:site update <release> nella cartella core.';
$_lang['updater_action_compare'] = 'Visualizza modifiche';
$_lang["help_donate_msg"] = 'Acquista il caffè dagli sviluppatori di Evolution CMS su <a href="https://ko-fi.com/evolutioncms" target="_blank">ko-fi.com/evolutioncms ☕</a>. Diventa un fan di Evolution CMS ❤️ oggi!';

$_lang['are_you_sure_update'] = 'Sei sicuro di voler eseguire un aggiornamento del sistema?';
$_lang['updateButtonCommit_txt'] = 'Aggiorna a questo commit';
$_lang['table_commitdate'] = 'Data del commit';
$_lang['table_titleauthor'] = 'Titolo (Autore)';
$_lang['error_failedtogetfeed'] = 'Impossibile recuperare il feed';
$_lang['updater_notice_title'] = 'È disponibile una nuova versione del CMS';
$_lang['updater_notice_text_1'] = 'L’aggiornamento è facoltativo, ma migliora notevolmente la stabilità, le prestazioni e la protezione da malware e attacchi DDoS.';
$_lang['updater_notice_text_2'] = 'Puoi aggiornare il sistema manualmente. Il processo richiede competenze tecniche, quindi è meglio affidarlo all’amministratore di sistema.';
$_lang['updater_notice_text_3'] = 'Oppure contatta i tuoi sviluppatori per eseguire l’aggiornamento.';
$_lang['updater_versions_line'] = 'Attuale: %s -> Disponibile: %s';
$_lang['updater_severity_critical'] = 'Critico';
$_lang['updater_severity_warning'] = 'Avviso';
$_lang['updater_severity_info'] = 'Informazioni';
$_lang['updater_action_release'] = 'Registro delle modifiche';
$_lang['updater_action_release_all'] = 'Tutte le versioni';
$_lang['updater_action_branch'] = 'Visualizza ramo/riferimento';
$_lang['updater_action_branch_all'] = 'Tutti i rami';
$_lang['updater_action_support'] = 'Contatta gli sviluppatori';
$_lang['updater_action_hide_day'] = 'Non mostrare per 1 giorno';
$_lang['updater_action_hide_today'] = 'Non mostrare più questo messaggio oggi';
$_lang['updater_support_hint'] = 'Assistenza:';
$_lang['updater_mail_subject'] = '[EVO] Richiesta di aggiornamento del sito %s: %s -> %s';
$_lang['updater_mail_line_site'] = 'Sito: %s';
$_lang['updater_mail_line_update'] = 'Aggiornamento: %s -> %s';
$_lang['updater_mail_line_request'] = 'Si prega di stimare tempi e costi.';
$_lang['updater_widget_title'] = 'È disponibile una nuova versione del CMS';
$_lang['updater_badge_current'] = 'Attuale';
$_lang['updater_badge_available'] = 'Disponibile';
$_lang['updater_current_full'] = 'Versione completa: %s';
$_lang['updater_action_support_with_email'] = 'Contatta gli sviluppatori: %s';
$_lang['updater_cli_summary'] = 'Aggiornamento manuale (CLI)';
$_lang['updater_cli_intro'] = 'Se aggiorni manualmente, esegui questo comando nel terminale:';
$_lang['updater_cli_command'] = 'php artisan make:site update';
$_lang['updater_branch_target_label'] = 'Ramo/riferimento';
$_lang['updater_branch_mode_notice'] = 'La modalità di aggiornamento da un ramo/riferimento di sviluppo è attiva. Usala solo per test o manutenzione controllata.';
$_lang['updater_live_update_title'] = 'Aggiornamento del sistema';
$_lang['updater_live_update_intro'] = 'Il pianificatore e il processo di lavoro sono disponibili, quindi Evolution CMS può accodare l’aggiornamento e mostrarne qui l’avanzamento in tempo reale.';
$_lang['updater_live_update_backup'] = 'Crea un backup verificato prima di continuare. L’aggiornamento modifica file e dati del database.';
$_lang['updater_live_update_backup_checkbox'] = 'Creare un backup del database prima dell’aggiornamento';
$_lang['updater_live_update_current'] = 'Versione attuale del database';
$_lang['updater_live_update_current_version'] = 'versione attuale del database';
$_lang['updater_live_update_target'] = 'Versione di destinazione';
$_lang['updater_live_update_branch_target'] = 'Ramo/riferimento di destinazione';
$_lang['updater_live_update_repository'] = 'Repository';
$_lang['updater_live_update_health'] = 'Pianificatore / processo di lavoro';
$_lang['updater_live_update_confirm'] = 'Avvia aggiornamento';
$_lang['updater_live_update_cancel'] = 'Annulla';
$_lang['updater_live_update_queueing'] = 'Accodamento dell’aggiornamento...';
$_lang['updater_live_update_queued'] = 'Attività di aggiornamento accodata. In attesa del processo di lavoro...';
$_lang['updater_live_update_status'] = 'Stato';
$_lang['updater_live_update_step'] = 'Passaggio';
$_lang['updater_live_update_progress'] = 'Avanzamento';
$_lang['updater_live_update_close'] = 'Chiudi';
$_lang['updater_live_update_close_reload'] = 'Chiudi e ricarica';
$_lang['updater_live_update_completed'] = 'Aggiornamento completato. Chiudi questa finestra per ricaricare il pannello di gestione e verificare la nuova versione.';
$_lang['updater_live_update_response_changed'] = 'La risposta del pannello di gestione è cambiata durante l’aggiornamento. Chiudi questa finestra per ricaricare il pannello e leggere lo stato finale.';
$_lang['updater_live_update_failed'] = 'Impossibile avviare l’aggiornamento.';
$_lang['updater_live_update_invalid_response'] = 'Il pannello di gestione ha restituito una risposta di aggiornamento non valida.';
