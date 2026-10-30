<?php
/**
 * Evolution CMS lt.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 23:20:04
 *
 * @language Lithuanian
 * @package Evolution CMS
 * @subpackage assets
 * Please commit your language changes here: https://github.com/evolution-cms/evolution
 */

/*List pack */
$_Lang['version'] = "Versija";
$_Lang['suthor'] = "autorius";
$_Lang['date'] = "Išleista";
$_Lang['downloads'] = "Įkelta";
$_Lang['more'] = "daugiau";
$_Lang['install'] = "Įdiegti";
$_Lang['reinstall'] = "Įdiegti iš naujo";
$_Lang['update'] = "Atnaujinti";
$_Lang['installed'] = "įdiegta";
$_Lang['installed2'] = "Įdiegta";
$_Lang['exit'] = "Išeiti";
$_Lang['alert_overwrite'] = "Įdiegus šį priedą, esami bus perrašyti. Tęsti?";

/*Main*/
$_Lang['store_name'] = "Paketų valdymas";
$_Lang['category'] = "Kategorijos";

/*Login form*/
$_Lang['enter_in_own_repository'] = "Prisijunkite prie savo saugyklos";
$_Lang['password'] = "Slaptažodis";
$_Lang['enter'] = "&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; Prisijungti &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;";
$_Lang['email'] = "El. paštas";
$_Lang['register'] = "Registruotis dabar";
$_Lang['own_repository'] = "Mano saugykla";
$_Lang['login_errors'] = "Neteisingas el. paštas arba slaptažodis";

/* & Tpl filters */
$_Lang['s1_1_reated_on'] = "Sukurta";
$_Lang['s1_2_name'] = "Pavadinimas";
$_Lang['s1_3_position'] = "Pozicija";
$_Lang['s1_4_published'] = "Atnaujinimas";
$_Lang['s2_1_up'] = "Mažėjančiai";
$_Lang['s2_2_down'] = "Didėjančiai";
$_Lang['search'] = "Ieškoti kategorijoje: ";
$_Lang['sort_by'] = "Rikiuoti pagal:";
$_Lang['sort_default'] = "Numatytasis";
$_Lang['sort_title_asc'] = "Pavadinimas ↑";
$_Lang['sort_title_desc'] = "Pavadinimas ↓";
$_Lang['sort_downloads_asc'] = "Įdiegimai ↑";
$_Lang['sort_downloads_desc'] = "Įdiegimai ↓";
$_Lang['console_category'] = "Paketai";
$_Lang['console_install_title'] = "Diegimas per konsolę";
$_Lang['console_install_intro'] = "Šį paketą galima įdiegti rankiniu būdu per konsolę arba automatiškai per planuoklį.";
$_Lang['console_install_manual_label'] = "Rankinis diegimas";
$_Lang['console_install_auto_label'] = "Automatinis diegimas";
$_Lang['console_install_auto_ready'] = "Planuoklis jau veikia. Galite įtraukti šį paketą į automatinio diegimo eilę.";
$_Lang['console_install_auto_disabled'] = "Automatinis diegimas šiuo metu nepasiekiamas. Pirmiausia paleiskite planuoklį.";
$_Lang['console_install_auto_permission'] = "Automatiniam diegimui reikia leidimo valdyti sistemos užduotis.";
$_Lang['console_install_scheduler_intro'] = "Norėdami įjungti automatinį diegimą, paleiskite planuoklį atitinkamoje aplinkoje:";
$_Lang['console_install_scheduler_local'] = "Vietinė";
$_Lang['console_install_scheduler_server'] = "Serveris";
$_Lang['console_install_scheduler_command_local'] = "php artisan schedule:work";
$_Lang['console_install_scheduler_command_server'] = "* * * * * cd {core_path} && php artisan schedule:run >/dev/null 2>&1";
$_Lang['console_install_auto_warning'] = "Planuoklis arba vykdymo procesas šiuo metu veikia netinkamai. Vis tiek galite įtraukti diegimą į eilę, tačiau stebėkite eigą.";
$_Lang['console_install_step_open_core'] = "1. Pereikite į šio projekto core katalogą:";
$_Lang['console_install_step_run_artisan'] = "2. Paleiskite artisan komandą:";
$_Lang['console_install_command_label'] = "Komanda";
$_Lang['console_install_note'] = "Kol kas Store čia tik rodo komandą. Diegimas ir toliau vykdomas per konsolę, todėl modulis nieko nekeičia be jūsų žinios.";
$_Lang['console_install_source_label'] = "Šaltinis";
$_Lang['source_label_legacy'] = "Senasis";
$_Lang['source_label_console'] = "";
$_Lang['popup_version'] = "Versija";
$_Lang['popup_updated'] = "Atnaujinta";
$_Lang['popup_author'] = "Autorius";
$_Lang['popup_downloads'] = "Atsisiuntimai";
$_Lang['popup_source'] = "Šaltinis";
$_Lang['popup_readme'] = "README";
$_Lang['popup_open_repo'] = "Atverti saugyklą";
$_Lang['popup_loading'] = "Įkeliama...";
$_Lang['popup_readme_missing'] = "Šio paketo README.md failas dar nerastas.";
$_Lang['popup_copy_command'] = "Kopijuoti komandą";
$_Lang['popup_copied'] = "Nukopijuota";
$_Lang['system_task_queue_install'] = "Įtraukti diegimą į eilę";
$_Lang['system_task_modal_warning'] = "Įspėjimas";
$_Lang['system_task_modal_status'] = "Būsena";
$_Lang['system_task_modal_step'] = "Žingsnis";
$_Lang['system_task_modal_progress'] = "Eiga";
$_Lang['system_task_modal_elapsed'] = "Praėjęs laikas";
$_Lang['system_task_modal_logs'] = "Žurnalai";
$_Lang['system_task_modal_close'] = "Uždaryti";
$_Lang['system_task_modal_queue_error'] = "Nepavyko įtraukti šios sistemos užduoties į eilę.";
$_Lang['system_task_modal_blocked_by_task'] = "Šį veiksmą šiuo metu blokuoja kita užduotis.";
$_Lang['system_task_modal_blocked_task'] = "Blokuojanti užduotis";
$_Lang['system_task_modal_open_blocking'] = "Atverti blokuojančią užduotį";
$_Lang['system_task_modal_skip_queued'] = "Praleisti eilėje esančią užduotį";
$_Lang['system_task_modal_uninstall_note'] = "Kol kas paketas pašalinamas tik iš Composer. Paketo sukurti elementai dar nepašalinami automatiškai.";
$_Lang['system_task_modal_cancel_queued'] = "Atšaukti eilėje esančią užduotį";
$_Lang['system_task_modal_stale_local'] = "Ši užduotis eilėje laukia ilgiau nei minutę ir atrodo, kad planuoklis neveikia. Vėl paleiskite php artisan schedule:work arba atšaukite šią eilėje esančią užduotį.";
$_Lang['system_task_modal_stale_server'] = "Ši užduotis eilėje laukia ilgiau nei minutę ir atrodo, kad cron arba planuoklis neveikia. Atnaujinkite cron schedule:run vykdymą arba atšaukite šią eilėje esančią užduotį.";
$_Lang['console_uninstall_intro'] = "Šalinant šį konsolės paketą, jis kol kas pašalinamas tik iš Composer. Paketo sukurti elementai paliekami vietoje.";
$_Lang['console_uninstall_scheduler_intro'] = "Norėdami įjungti automatinį šalinimą, paleiskite planuoklį atitinkamoje aplinkoje:";
$_Lang['console_uninstall_confirm'] = "Pašalinti šį konsolės paketą iš Composer?";
$_Lang['delete'] = "Ištrinti";
$_Lang['delete_preview_title'] = "Ištrinti paketą";
$_Lang['delete_preview_intro'] = "Pasirinkite, kuriuos šio paketo failus ir administravimo įrašus pašalinti.";
$_Lang['delete_preview_files_label'] = "Failai";
$_Lang['delete_preview_components_label'] = "Komponentai";
$_Lang['delete_preview_confirm'] = "Ištrinti pasirinktus";
$_Lang['delete_preview_cancel'] = "Atšaukti";
$_Lang['delete_preview_loading'] = "Ruošiama šalinimo peržiūra...";
$_Lang['delete_preview_empty'] = "Šiame pakete nerasta nieko, ką būtų galima pašalinti.";
$_Lang['delete_preview_success'] = "Senojo formato paketas pašalintas.";
$_Lang['delete_preview_error'] = "Nepavyko pašalinti šio paketo.";
$_Lang['legacy_delete_error'] = "Nepavyko parengti šalinimo peržiūros.";
$_Lang['legacy_delete_download_error'] = "Nepavyko atsisiųsti šio paketo archyvo šalinimo peržiūrai.";
$_Lang['legacy_delete_archive_error'] = "Nepavyko patikrinti paketo archyvo.";
$_Lang['legacy_delete_manifest_missing'] = "Šalinimo peržiūros galiojimas baigėsi. Atidarykite ją iš naujo.";
$_Lang['legacy_delete_success'] = "Senojo formato paketas pašalintas.";
$_Lang['dev_badge'] = "DEV";
$_Lang['dev_package_warning'] = "Kuriamas paketas: dar nėra išleistos versijos, nerekomenduojamas naudojimui veikiančioje svetainėje.";

/* Installation by file-upload */
$_Lang['install_file'] = "Įdiegti iš failo";
$_Lang['choose_file_msg'] = "Pasirinkite failą įkelti ir įdiegti";
$_Lang['install_file_btn'] = "Įdiegti";
$_Lang['install_file_success'] = "Įdiegta sėkmingai";
$_Lang['install_file_artifact_queued'] = 'Composer paketas %1$s %2$s įtrauktas į diegimo eilę kaip sistemos užduotis Nr. %3$s. Planuoklis jį įdiegs iš įkelto archyvo.';
$_Lang['install_file_artifact_no_version'] = 'Archyve yra Composer paketas %1$s, tačiau nenurodyta versija. Į jo composer.json įtraukite "version" arba nurodykite versiją failo pavadinime, pvz., %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = 'Composer paketo %1$s nepavyko įtraukti į eilę: %2$s';
$_Lang['install_file_artifact_invalid'] = 'Archyvas panašus į Composer paketą, tačiau %1$s nėra tinkamas JSON su paketo "name". Nieko neįdiegta.';

/* faq */
$_Lang['faq'] = '
<li><a href="https://extras.evo.im/faq/whatfor.html" target="_blank">Kam reikalinga saugykla</a></li>
<li><a href="https://extras.evo.im/faq/howtoadd.html" target="_blank">Kaip įtraukti paketą į saugyklą</a></li>
<li><a href="https://extras.evo.im/faq/howtocollect.html" target="_blank">Kaip sukurti diegimui skirtą paketą</a></li>';

?>
