<?php
/**
 * Evolution CMS ro.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 23:20:04
 *
 * @language Romanian
 * @package Evolution CMS
 * @subpackage assets
 * Please commit your language changes here: https://github.com/evolution-cms/evolution
 */

/*List pack */
$_Lang['version'] = "Versiune";
$_Lang['suthor'] = "autor";
$_Lang['date'] = "Publicat";
$_Lang['downloads'] = "Încărcat";
$_Lang['more'] = "mai mult";
$_Lang['install'] = "Instalează";
$_Lang['reinstall'] = "Reinstalează";
$_Lang['update'] = "Actualizează";
$_Lang['installed'] = "instalat";
$_Lang['installed2'] = "Instalat";
$_Lang['exit'] = "Ieșire";
$_Lang['alert_overwrite'] = "Instalarea acestui supliment le va suprascrie pe cele existente. Continuați?";

/*Main*/
$_Lang['store_name'] = "Gestionarea pachetelor";
$_Lang['category'] = "Categorii";

/*Login form*/
$_Lang['enter_in_own_repository'] = "Autentificați-vă în depozitul dvs.";
$_Lang['password'] = "Parolă";
$_Lang['enter'] = "&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; Autentificare &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;";
$_Lang['email'] = "E-mail";
$_Lang['register'] = "Înregistrați-vă acum";
$_Lang['own_repository'] = "Depozitul meu";
$_Lang['login_errors'] = "E-mail sau parolă incorectă";

/* & Tpl filters */
$_Lang['s1_1_reated_on'] = "Creat";
$_Lang['s1_2_name'] = "Titlu";
$_Lang['s1_3_position'] = "Poziție";
$_Lang['s1_4_published'] = "Actualizare";
$_Lang['s2_1_up'] = "Descrescător";
$_Lang['s2_2_down'] = "Crescător";
$_Lang['search'] = "Caută în categorie: ";
$_Lang['sort_by'] = "Sortează după:";
$_Lang['sort_default'] = "Implicit";
$_Lang['sort_title_asc'] = "Nume ↑";
$_Lang['sort_title_desc'] = "Nume ↓";
$_Lang['sort_downloads_asc'] = "Instalări ↑";
$_Lang['sort_downloads_desc'] = "Instalări ↓";
$_Lang['console_category'] = "Pachete";
$_Lang['console_install_title'] = "Instalare prin consolă";
$_Lang['console_install_intro'] = "Acest pachet poate fi instalat manual prin consolă sau automat prin planificator.";
$_Lang['console_install_manual_label'] = "Instalare manuală";
$_Lang['console_install_auto_label'] = "Instalare automată";
$_Lang['console_install_auto_ready'] = "Planificatorul rulează deja. Puteți pune acest pachet în coadă pentru instalare automată.";
$_Lang['console_install_auto_disabled'] = "Instalarea automată nu este disponibilă momentan. Porniți mai întâi planificatorul.";
$_Lang['console_install_auto_permission'] = "Instalarea automată necesită permisiunea de a gestiona sarcinile de sistem.";
$_Lang['console_install_scheduler_intro'] = "Pentru a activa instalarea automată, porniți planificatorul în mediul corespunzător:";
$_Lang['console_install_scheduler_local'] = "Local";
$_Lang['console_install_scheduler_server'] = "Server";
$_Lang['console_install_scheduler_command_local'] = "php artisan schedule:work";
$_Lang['console_install_scheduler_command_server'] = "* * * * * cd {core_path} && php artisan schedule:run >/dev/null 2>&1";
$_Lang['console_install_auto_warning'] = "Planificatorul sau procesul de lucru nu funcționează complet corect momentan. Puteți totuși pune instalarea în coadă, dar urmăriți progresul.";
$_Lang['console_install_step_open_core'] = "1. Accesați directorul core al acestui proiect:";
$_Lang['console_install_step_run_artisan'] = "2. Rulați comanda artisan:";
$_Lang['console_install_command_label'] = "Comandă";
$_Lang['console_install_note'] = "Deocamdată, Store afișează aici doar comanda. Instalarea se face în continuare prin consolă, astfel încât modulul să nu modifice nimic fără știrea dvs.";
$_Lang['console_install_source_label'] = "Sursă";
$_Lang['source_label_legacy'] = "Moștenit";
$_Lang['source_label_console'] = "";
$_Lang['popup_version'] = "Versiune";
$_Lang['popup_updated'] = "Actualizat";
$_Lang['popup_author'] = "Autor";
$_Lang['popup_downloads'] = "Descărcări";
$_Lang['popup_source'] = "Sursă";
$_Lang['popup_readme'] = "README";
$_Lang['popup_open_repo'] = "Deschide depozitul";
$_Lang['popup_loading'] = "Se încarcă...";
$_Lang['popup_readme_missing'] = "Fișierul README.md nu a fost găsit încă pentru acest pachet.";
$_Lang['popup_copy_command'] = "Copiază comanda";
$_Lang['popup_copied'] = "Copiat";
$_Lang['system_task_queue_install'] = "Pune instalarea în coadă";
$_Lang['system_task_modal_warning'] = "Avertisment";
$_Lang['system_task_modal_status'] = "Stare";
$_Lang['system_task_modal_step'] = "Pas";
$_Lang['system_task_modal_progress'] = "Progres";
$_Lang['system_task_modal_elapsed'] = "Timp scurs";
$_Lang['system_task_modal_logs'] = "Jurnale";
$_Lang['system_task_modal_close'] = "Închide";
$_Lang['system_task_modal_queue_error'] = "Această sarcină de sistem nu poate fi pusă în coadă.";
$_Lang['system_task_modal_blocked_by_task'] = "O altă sarcină blochează momentan această acțiune.";
$_Lang['system_task_modal_blocked_task'] = "Sarcină care blochează";
$_Lang['system_task_modal_open_blocking'] = "Deschide sarcina care blochează";
$_Lang['system_task_modal_skip_queued'] = "Omite sarcina din coadă";
$_Lang['system_task_modal_uninstall_note'] = "Deocamdată, această acțiune elimină pachetul doar din Composer. Elementele create de pachet nu sunt încă șterse automat.";
$_Lang['system_task_modal_cancel_queued'] = "Anulează sarcina din coadă";
$_Lang['system_task_modal_stale_local'] = "Această sarcină este în coadă de peste un minut, iar planificatorul pare oprit. Porniți din nou php artisan schedule:work sau anulați această sarcină din coadă.";
$_Lang['system_task_modal_stale_server'] = "Această sarcină este în coadă de peste un minut, iar cron sau planificatorul pare oprit. Reluați cron schedule:run sau anulați această sarcină din coadă.";
$_Lang['console_uninstall_intro'] = "Eliminarea acestui pachet de consolă îl elimină momentan doar din Composer. Elementele create de pachet rămân pe loc.";
$_Lang['console_uninstall_scheduler_intro'] = "Pentru a activa eliminarea automată, porniți planificatorul în mediul corespunzător:";
$_Lang['console_uninstall_confirm'] = "Eliminați acest pachet de consolă din Composer?";
$_Lang['delete'] = "Șterge";
$_Lang['delete_preview_title'] = "Șterge pachetul";
$_Lang['delete_preview_intro'] = "Selectați fișierele și înregistrările din administrare care trebuie eliminate pentru acest pachet.";
$_Lang['delete_preview_files_label'] = "Fișiere";
$_Lang['delete_preview_components_label'] = "Componente";
$_Lang['delete_preview_confirm'] = "Șterge selecția";
$_Lang['delete_preview_cancel'] = "Anulează";
$_Lang['delete_preview_loading'] = "Se pregătește previzualizarea ștergerii...";
$_Lang['delete_preview_empty'] = "Nu s-a găsit nimic din acest pachet pentru ștergere.";
$_Lang['delete_preview_success'] = "Pachetul vechi a fost șters.";
$_Lang['delete_preview_error'] = "Acest pachet nu poate fi șters.";
$_Lang['legacy_delete_error'] = "Previzualizarea ștergerii nu poate fi generată.";
$_Lang['legacy_delete_download_error'] = "Arhiva acestui pachet nu poate fi descărcată pentru previzualizarea ștergerii.";
$_Lang['legacy_delete_archive_error'] = "Arhiva pachetului nu poate fi inspectată.";
$_Lang['legacy_delete_manifest_missing'] = "Previzualizarea ștergerii a expirat. Deschideți-o din nou.";
$_Lang['legacy_delete_success'] = "Pachetul vechi a fost șters.";
$_Lang['dev_badge'] = "DEV";
$_Lang['dev_package_warning'] = "Pachet în dezvoltare: încă nu are o versiune publicată; nu este recomandat pentru producție.";

/* Installation by file-upload */
$_Lang['install_file'] = "Instalează din fișier";
$_Lang['choose_file_msg'] = "Alegeți un fișier de încărcat și instalat";
$_Lang['install_file_btn'] = "Instalează";
$_Lang['install_file_success'] = "Instalare reușită";
$_Lang['install_file_artifact_queued'] = 'Pachetul Composer %1$s %2$s a fost pus în coadă pentru instalare ca sarcina de sistem #%3$s. Planificatorul îl instalează din arhiva încărcată.';
$_Lang['install_file_artifact_no_version'] = 'Arhiva conține pachetul Composer %1$s, dar nu și o versiune. Adăugați "version" în composer.json sau includeți versiunea în numele fișierului, de exemplu %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = 'Pachetul Composer %1$s nu a putut fi pus în coadă: %2$s';
$_Lang['install_file_artifact_invalid'] = 'Arhiva pare a fi un pachet Composer, dar %1$s nu este un JSON valid cu un "name" de pachet. Nu s-a instalat nimic.';

/* faq */
$_Lang['faq'] = '
<li><a href="https://extras.evo.im/faq/whatfor.html" target="_blank">De ce aveți nevoie de depozit</a></li>
<li><a href="https://extras.evo.im/faq/howtoadd.html" target="_blank">Cum adăugați un pachet în depozit</a></li>
<li><a href="https://extras.evo.im/faq/howtocollect.html" target="_blank">Cum creați un pachet pentru instalare</a></li>';

?>
