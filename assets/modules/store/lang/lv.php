<?php
/**
 * Evolution CMS lv.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 23:20:04
 *
 * @language Latvian
 * @package Evolution CMS
 * @subpackage assets
 * Please commit your language changes here: https://github.com/evolution-cms/evolution
 */

/*List pack */
$_Lang['version'] = "Versija";
$_Lang['suthor'] = "autors";
$_Lang['date'] = "Izlaists";
$_Lang['downloads'] = "Augšupielādēts";
$_Lang['more'] = "vairāk";
$_Lang['install'] = "Instalēt";
$_Lang['reinstall'] = "Pārinstalēt";
$_Lang['update'] = "Atjaunināt";
$_Lang['installed'] = "instalēts";
$_Lang['installed2'] = "Instalēts";
$_Lang['exit'] = "Iziet";
$_Lang['alert_overwrite'] = "Instalējot šo papildinājumu, esošie tiks pārrakstīti. Turpināt?";

/*Main*/
$_Lang['store_name'] = "Pakotņu pārvaldība";
$_Lang['category'] = "Kategorijas";

/*Login form*/
$_Lang['enter_in_own_repository'] = "Piesakieties savā repozitorijā";
$_Lang['password'] = "Parole";
$_Lang['enter'] = "&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; Pieteikties &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;";
$_Lang['email'] = "E-pasts";
$_Lang['register'] = "Reģistrēties tagad";
$_Lang['own_repository'] = "Mans repozitorijs";
$_Lang['login_errors'] = "Nepareizs e-pasts vai parole";

/* & Tpl filters */
$_Lang['s1_1_reated_on'] = "Izveidots";
$_Lang['s1_2_name'] = "Nosaukums";
$_Lang['s1_3_position'] = "Pozīcija";
$_Lang['s1_4_published'] = "Atjauninājums";
$_Lang['s2_1_up'] = "Dilstoši";
$_Lang['s2_2_down'] = "Augoši";
$_Lang['search'] = "Meklēt kategorijā: ";
$_Lang['sort_by'] = "Kārtot pēc:";
$_Lang['sort_default'] = "Noklusējums";
$_Lang['sort_title_asc'] = "Nosaukums ↑";
$_Lang['sort_title_desc'] = "Nosaukums ↓";
$_Lang['sort_downloads_asc'] = "Instalācijas ↑";
$_Lang['sort_downloads_desc'] = "Instalācijas ↓";
$_Lang['console_category'] = "Pakotnes";
$_Lang['console_install_title'] = "Instalēšana konsolē";
$_Lang['console_install_intro'] = "Šo pakotni var instalēt manuāli konsolē vai automātiski ar plānotāju.";
$_Lang['console_install_manual_label'] = "Manuāla instalēšana";
$_Lang['console_install_auto_label'] = "Automātiska instalēšana";
$_Lang['console_install_auto_ready'] = "Plānotājs jau darbojas. Varat ievietot šo pakotni automātiskās instalēšanas rindā.";
$_Lang['console_install_auto_disabled'] = "Automātiska instalēšana pašlaik nav pieejama. Vispirms palaidiet plānotāju.";
$_Lang['console_install_auto_permission'] = "Automātiskai instalēšanai ir nepieciešama atļauja pārvaldīt sistēmas uzdevumus.";
$_Lang['console_install_scheduler_intro'] = "Lai iespējotu automātisku instalēšanu, palaidiet plānotāju atbilstošajā vidē:";
$_Lang['console_install_scheduler_local'] = "Lokāli";
$_Lang['console_install_scheduler_server'] = "Serveris";
$_Lang['console_install_scheduler_command_local'] = "php artisan schedule:work";
$_Lang['console_install_scheduler_command_server'] = "* * * * * cd {core_path} && php artisan schedule:run >/dev/null 2>&1";
$_Lang['console_install_auto_warning'] = "Plānotājs vai darba process pašlaik nedarbojas pilnībā pareizi. Joprojām varat ievietot instalēšanu rindā, taču sekojiet tās gaitai.";
$_Lang['console_install_step_open_core'] = "1. Atveriet šī projekta core direktoriju:";
$_Lang['console_install_step_run_artisan'] = "2. Izpildiet artisan komandu:";
$_Lang['console_install_command_label'] = "Komanda";
$_Lang['console_install_note'] = "Pagaidām Store šeit tikai parāda komandu. Instalēšana joprojām notiek konsolē, lai modulis neko nemainītu bez jūsu ziņas.";
$_Lang['console_install_source_label'] = "Avots";
$_Lang['source_label_legacy'] = "Mantotais";
$_Lang['source_label_console'] = "";
$_Lang['popup_version'] = "Versija";
$_Lang['popup_updated'] = "Atjaunināts";
$_Lang['popup_author'] = "Autors";
$_Lang['popup_downloads'] = "Lejupielādes";
$_Lang['popup_source'] = "Avots";
$_Lang['popup_readme'] = "README";
$_Lang['popup_open_repo'] = "Atvērt repozitoriju";
$_Lang['popup_loading'] = "Ielādē...";
$_Lang['popup_readme_missing'] = "Šai pakotnei README.md fails vēl nav atrasts.";
$_Lang['popup_copy_command'] = "Kopēt komandu";
$_Lang['popup_copied'] = "Nokopēts";
$_Lang['system_task_queue_install'] = "Ievietot instalēšanu rindā";
$_Lang['system_task_modal_warning'] = "Brīdinājums";
$_Lang['system_task_modal_status'] = "Statuss";
$_Lang['system_task_modal_step'] = "Solis";
$_Lang['system_task_modal_progress'] = "Norise";
$_Lang['system_task_modal_elapsed'] = "Pagājušais laiks";
$_Lang['system_task_modal_logs'] = "Žurnāli";
$_Lang['system_task_modal_close'] = "Aizvērt";
$_Lang['system_task_modal_queue_error'] = "Neizdevās ievietot šo sistēmas uzdevumu rindā.";
$_Lang['system_task_modal_blocked_by_task'] = "Cits uzdevums pašlaik bloķē šo darbību.";
$_Lang['system_task_modal_blocked_task'] = "Bloķējošais uzdevums";
$_Lang['system_task_modal_open_blocking'] = "Atvērt bloķējošo uzdevumu";
$_Lang['system_task_modal_skip_queued'] = "Izlaist rindā esošo uzdevumu";
$_Lang['system_task_modal_uninstall_note'] = "Pagaidām pakotne tiek noņemta tikai no Composer. Pakotnes izveidotie elementi vēl netiek automātiski izdzēsti.";
$_Lang['system_task_modal_cancel_queued'] = "Atcelt rindā esošo uzdevumu";
$_Lang['system_task_modal_stale_local'] = "Šis uzdevums rindā gaida jau vairāk nekā minūti, un šķiet, ka plānotājs nedarbojas. Vēlreiz palaidiet php artisan schedule:work vai atceliet šo rindā esošo uzdevumu.";
$_Lang['system_task_modal_stale_server'] = "Šis uzdevums rindā gaida jau vairāk nekā minūti, un šķiet, ka cron vai plānotājs nedarbojas. Atsāciet cron schedule:run izpildi vai atceliet šo rindā esošo uzdevumu.";
$_Lang['console_uninstall_intro'] = "Noņemot šo konsoles pakotni, tā pašlaik tiek noņemta tikai no Composer. Pakotnes izveidotie elementi paliek savās vietās.";
$_Lang['console_uninstall_scheduler_intro'] = "Lai iespējotu automātisku noņemšanu, palaidiet plānotāju atbilstošajā vidē:";
$_Lang['console_uninstall_confirm'] = "Noņemt šo konsoles pakotni no Composer?";
$_Lang['delete'] = "Dzēst";
$_Lang['delete_preview_title'] = "Dzēst pakotni";
$_Lang['delete_preview_intro'] = "Izvēlieties, kuri šīs pakotnes faili un administrācijas ieraksti ir jānoņem.";
$_Lang['delete_preview_files_label'] = "Faili";
$_Lang['delete_preview_components_label'] = "Komponenti";
$_Lang['delete_preview_confirm'] = "Dzēst atlasītos";
$_Lang['delete_preview_cancel'] = "Atcelt";
$_Lang['delete_preview_loading'] = "Tiek sagatavots dzēšanas priekšskatījums...";
$_Lang['delete_preview_empty'] = "Šajā pakotnē netika atrasts nekas dzēšams.";
$_Lang['delete_preview_success'] = "Vecā formāta pakotne ir izdzēsta.";
$_Lang['delete_preview_error'] = "Šo pakotni neizdevās izdzēst.";
$_Lang['legacy_delete_error'] = "Neizdevās sagatavot dzēšanas priekšskatījumu.";
$_Lang['legacy_delete_download_error'] = "Neizdevās lejupielādēt šīs pakotnes arhīvu dzēšanas priekšskatījumam.";
$_Lang['legacy_delete_archive_error'] = "Neizdevās pārbaudīt pakotnes arhīvu.";
$_Lang['legacy_delete_manifest_missing'] = "Dzēšanas priekšskatījuma derīguma termiņš ir beidzies. Atveriet to vēlreiz.";
$_Lang['legacy_delete_success'] = "Vecā formāta pakotne ir izdzēsta.";
$_Lang['dev_badge'] = "DEV";
$_Lang['dev_package_warning'] = "Izstrādes pakotne: vēl nav izlaistas versijas, nav ieteicama lietošanai darba vidē.";

/* Installation by file-upload */
$_Lang['install_file'] = "Instalēt no faila";
$_Lang['choose_file_msg'] = "Izvēlieties failu augšupielādei un instalēšanai";
$_Lang['install_file_btn'] = "Instalēt";
$_Lang['install_file_success'] = "Instalēšana veiksmīga";
$_Lang['install_file_artifact_queued'] = 'Composer pakotne %1$s %2$s ir pievienota instalēšanas rindai kā sistēmas uzdevums Nr. %3$s. Plānotājs to instalēs no augšupielādētā arhīva.';
$_Lang['install_file_artifact_no_version'] = 'Arhīvā ir Composer pakotne %1$s, bet nav norādīta versija. Pievienojiet "version" tās composer.json failam vai norādiet versiju faila nosaukumā, piemēram, %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = 'Composer pakotni %1$s neizdevās pievienot rindai: %2$s';
$_Lang['install_file_artifact_invalid'] = 'Arhīvs izskatās pēc Composer pakotnes, bet %1$s nav derīgs JSON ar pakotnes "name". Nekas netika instalēts.';

/* faq */
$_Lang['faq'] = '
<li><a href="https://extras.evo.im/faq/whatfor.html" target="_blank">Kāpēc nepieciešama krātuve</a></li>
<li><a href="https://extras.evo.im/faq/howtoadd.html" target="_blank">Kā pievienot pakotni krātuvei</a></li>
<li><a href="https://extras.evo.im/faq/howtocollect.html" target="_blank">Kā izveidot instalējamu pakotni</a></li>';

?>
