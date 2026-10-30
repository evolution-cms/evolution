<?php
/**
 * Evolution CMS et.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 20:30:13
 *
 * @language Estonian
 * @package Evolution CMS
 * @subpackage assets
 * Please commit your language changes here: https://github.com/evolution-cms/evolution
 */

/*List pack */
$_Lang['version'] = "Versioon";
$_Lang['suthor'] = "autor";
$_Lang['date'] = "Välja antud";
$_Lang['downloads'] = "Üles laaditud";
$_Lang['more'] = "rohkem";
$_Lang['install'] = "Paigalda";
$_Lang['reinstall'] = "Paigalda uuesti";
$_Lang['update'] = "Uuenda";
$_Lang['installed'] = "paigaldatud";
$_Lang['installed2'] = "Paigaldatud";
$_Lang['exit'] = "Välju";
$_Lang['alert_overwrite'] = "Selle lisandmooduli paigaldamine kirjutab olemasolevad üle. Kas jätkata?";

/*Main*/
$_Lang['store_name'] = "Pakettide haldus";
$_Lang['category'] = "Kategooriad";

/*Login form*/
$_Lang['enter_in_own_repository'] = "Logi oma hoidlasse sisse";
$_Lang['password'] = "Parool";
$_Lang['enter'] = "&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; Logi sisse &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;";
$_Lang['email'] = "E-post";
$_Lang['register'] = "Registreeru kohe";
$_Lang['own_repository'] = "Minu hoidla";
$_Lang['login_errors'] = "Vale e-posti aadress või parool";

/* & Tpl filters */
$_Lang['s1_1_reated_on'] = "Loodud";
$_Lang['s1_2_name'] = "Pealkiri";
$_Lang['s1_3_position'] = "Asukoht";
$_Lang['s1_4_published'] = "Uuendus";
$_Lang['s2_1_up'] = "Kahanev";
$_Lang['s2_2_down'] = "Kasvav";
$_Lang['search'] = "Otsi kategooriast: ";
$_Lang['sort_by'] = "Sordi:";
$_Lang['sort_default'] = "Vaikimisi";
$_Lang['sort_title_asc'] = "Nimi ↑";
$_Lang['sort_title_desc'] = "Nimi ↓";
$_Lang['sort_downloads_asc'] = "Paigaldusi ↑";
$_Lang['sort_downloads_desc'] = "Paigaldusi ↓";
$_Lang['console_category'] = "Paketid";
$_Lang['console_install_title'] = "Paigalda konsooli kaudu";
$_Lang['console_install_intro'] = "Selle paketi saab paigaldada käsitsi konsooli kaudu või automaatselt ajastaja abil.";
$_Lang['console_install_manual_label'] = "Käsitsi paigaldamine";
$_Lang['console_install_auto_label'] = "Automaatne paigaldamine";
$_Lang['console_install_auto_ready'] = "Ajastaja juba töötab. Saad lisada selle paketi automaatse paigaldamise järjekorda.";
$_Lang['console_install_auto_disabled'] = "Automaatne paigaldamine pole praegu saadaval. Käivita esmalt ajastaja.";
$_Lang['console_install_auto_permission'] = "Automaatne paigaldamine nõuab süsteemiülesannete haldamise õigust.";
$_Lang['console_install_scheduler_intro'] = "Automaatse paigaldamise lubamiseks käivita ajastaja sobivas keskkonnas:";
$_Lang['console_install_scheduler_local'] = "Kohalik";
$_Lang['console_install_scheduler_server'] = "Server";
$_Lang['console_install_scheduler_command_local'] = "php artisan schedule:work";
$_Lang['console_install_scheduler_command_server'] = "* * * * * cd {core_path} && php artisan schedule:run >/dev/null 2>&1";
$_Lang['console_install_auto_warning'] = "Ajastaja või tööprotsess ei tööta praegu korralikult. Saad paigaldamise siiski järjekorda lisada, kuid jälgi edenemist.";
$_Lang['console_install_step_open_core'] = "1. Mine selle projekti core-kataloogi:";
$_Lang['console_install_step_run_artisan'] = "2. Käivita artisan-käsk:";
$_Lang['console_install_command_label'] = "Käsk";
$_Lang['console_install_note'] = "Praegu näitab Store siin ainult käsku. Paigaldamine toimub konsooli kaudu, seega moodul ei muuda midagi vaikimisi.";
$_Lang['console_install_source_label'] = "Allikas";
$_Lang['source_label_legacy'] = "Pärand";
$_Lang['source_label_console'] = "";
$_Lang['popup_version'] = "Versioon";
$_Lang['popup_updated'] = "Uuendatud";
$_Lang['popup_author'] = "Autor";
$_Lang['popup_downloads'] = "Allalaadimised";
$_Lang['popup_source'] = "Allikas";
$_Lang['popup_readme'] = "README";
$_Lang['popup_open_repo'] = "Ava hoidla";
$_Lang['popup_loading'] = "Laadimine...";
$_Lang['popup_readme_missing'] = "Selle paketi README.md faili pole veel leitud.";
$_Lang['popup_copy_command'] = "Kopeeri käsk";
$_Lang['popup_copied'] = "Kopeeritud";
$_Lang['system_task_queue_install'] = "Lisa paigaldamine järjekorda";
$_Lang['system_task_modal_warning'] = "Hoiatus";
$_Lang['system_task_modal_status'] = "Olek";
$_Lang['system_task_modal_step'] = "Samm";
$_Lang['system_task_modal_progress'] = "Edenemine";
$_Lang['system_task_modal_elapsed'] = "Kulunud aeg";
$_Lang['system_task_modal_logs'] = "Logid";
$_Lang['system_task_modal_close'] = "Sulge";
$_Lang['system_task_modal_queue_error'] = "Seda süsteemiülesannet ei saa järjekorda lisada.";
$_Lang['system_task_modal_blocked_by_task'] = "Teine ülesanne takistab praegu seda toimingut.";
$_Lang['system_task_modal_blocked_task'] = "Takistav ülesanne";
$_Lang['system_task_modal_open_blocking'] = "Ava takistav ülesanne";
$_Lang['system_task_modal_skip_queued'] = "Jäta järjekorras olev ülesanne vahele";
$_Lang['system_task_modal_uninstall_note'] = "Praegu eemaldab see paketi ainult Composerist. Paketi loodud objekte ei eemaldata veel automaatselt.";
$_Lang['system_task_modal_cancel_queued'] = "Tühista järjekorras olev ülesanne";
$_Lang['system_task_modal_stale_local'] = "See ülesanne on olnud järjekorras üle minuti ja ajastaja ei paista töötavat. Käivita php artisan schedule:work uuesti või tühista see järjekorras olev ülesanne.";
$_Lang['system_task_modal_stale_server'] = "See ülesanne on olnud järjekorras üle minuti ning cron või ajastaja ei paista töötavat. Taaskäivita cron schedule:run või tühista see järjekorras olev ülesanne.";
$_Lang['console_uninstall_intro'] = "Selle konsoolipaketi eemaldamine eemaldab praegu paketi ainult Composerist. Paketi loodud objektid jäävad alles.";
$_Lang['console_uninstall_scheduler_intro'] = "Automaatse eemaldamise lubamiseks käivita ajastaja sobivas keskkonnas:";
$_Lang['console_uninstall_confirm'] = "Kas eemaldada see konsoolipakett Composerist?";
$_Lang['delete'] = "Kustuta";
$_Lang['delete_preview_title'] = "Kustuta pakett";
$_Lang['delete_preview_intro'] = "Vali, millised selle paketi failid ja halduri kirjed eemaldada.";
$_Lang['delete_preview_files_label'] = "Failid";
$_Lang['delete_preview_components_label'] = "Komponendid";
$_Lang['delete_preview_confirm'] = "Kustuta valitud";
$_Lang['delete_preview_cancel'] = "Tühista";
$_Lang['delete_preview_loading'] = "Kustutamise eelvaate ettevalmistamine...";
$_Lang['delete_preview_empty'] = "Sellest paketist ei leitud midagi kustutamiseks.";
$_Lang['delete_preview_success'] = "Pärandpakett kustutati.";
$_Lang['delete_preview_error'] = "Seda paketti ei saa kustutada.";
$_Lang['legacy_delete_error'] = "Kustutamise eelvaadet ei saa luua.";
$_Lang['legacy_delete_download_error'] = "Selle paketi arhiivi ei saa kustutamise eelvaate jaoks alla laadida.";
$_Lang['legacy_delete_archive_error'] = "Paketi arhiivi ei saa kontrollida.";
$_Lang['legacy_delete_manifest_missing'] = "Kustutamise eelvaade on aegunud. Palun avage see uuesti.";
$_Lang['legacy_delete_success'] = "Pärandpakett kustutati.";
$_Lang['dev_badge'] = "DEV";
$_Lang['dev_package_warning'] = "Arenduspakett: väljalase veel puudub, ei ole soovitatav tootmiskeskkonnas kasutada.";

/* Installation by file-upload */
$_Lang['install_file'] = "Paigalda failist";
$_Lang['choose_file_msg'] = "Valige üleslaadimiseks ja paigaldamiseks fail";
$_Lang['install_file_btn'] = "Paigalda";
$_Lang['install_file_success'] = "Paigaldamine õnnestus";
$_Lang['install_file_artifact_queued'] = 'Composeri pakett %1$s %2$s on paigaldusjärjekorras süsteemiülesandena #%3$s. Ajastaja paigaldab selle üleslaaditud arhiivist.';
$_Lang['install_file_artifact_no_version'] = 'Arhiiv sisaldab Composeri paketti %1$s, kuid versioon puudub. Lisage selle faili composer.json väli "version" või lisage versioon failinimesse, nt %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = 'Composeri paketti %1$s ei saanud järjekorda lisada: %2$s';
$_Lang['install_file_artifact_invalid'] = 'Arhiiv näib olevat Composeri pakett, kuid %1$s ei ole korrektne JSON koos paketi väljaga "name". Midagi ei paigaldatud.';

/* faq */
$_Lang['faq'] = '
<li><a href="https://extras.evo.im/faq/whatfor.html" target="_blank">Milleks on vaja hoidlat</a></li>
<li><a href="https://extras.evo.im/faq/howtoadd.html" target="_blank">Kuidas lisada paketti hoidlasse</a></li>
<li><a href="https://extras.evo.im/faq/howtocollect.html" target="_blank">Kuidas luua paigalduspaketti</a></li>';

?>
