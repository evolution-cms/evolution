<?php
/**
 * Evolution CMS kk.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 20:30:13
 *
 * @language Kazakh
 * @package Evolution CMS
 * @subpackage assets
 * Please commit your language changes here: https://github.com/evolution-cms/evolution
 */

/*List pack */
$_Lang['version'] = 'Нұсқа';
$_Lang['suthor'] = 'автор';
$_Lang['date'] = 'Шығарылған күні';
$_Lang['downloads'] = 'Жүктелген';
$_Lang['more'] = 'толығырақ';
$_Lang['install'] = 'Орнату';
$_Lang['reinstall'] = 'Қайта орнату';
$_Lang['update'] = 'Жаңарту';
$_Lang['installed'] = 'орнатылған';
$_Lang['installed2'] = 'Орнатылған';
$_Lang['exit'] = 'Шығу';
$_Lang['alert_overwrite'] = 'Бұл қосымшаны орнату бар файлдарды қайта жазады. Жалғастыру керек пе?';

/*Main*/
$_Lang['store_name'] = 'Пакеттерді басқару';
$_Lang['category'] = 'Санаттар';

/*Login form*/
$_Lang['enter_in_own_repository'] = 'Репозиторийіңізге кіру';
$_Lang['password'] = 'Құпиясөз';
$_Lang['enter'] = '&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; Кіру &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;';
$_Lang['email'] = 'Электрондық пошта';
$_Lang['register'] = 'Тіркелу';
$_Lang['own_repository'] = 'Менің репозиторийім';
$_Lang['login_errors'] = 'Электрондық пошта немесе құпиясөз қате';

/* & Tpl filters */
$_Lang['s1_1_reated_on'] = 'Жасалған';
$_Lang['s1_2_name'] = 'Атауы';
$_Lang['s1_3_position'] = 'Орны';
$_Lang['s1_4_published'] = 'Жаңарту';
$_Lang['s2_1_up'] = 'Кему ретімен';
$_Lang['s2_2_down'] = 'Өсу ретімен';
$_Lang['search'] = 'Санаттан іздеу: ';
$_Lang['sort_by'] = 'Сұрыптау:';
$_Lang['sort_default'] = 'Әдепкі';
$_Lang['sort_title_asc'] = 'Атауы ↑';
$_Lang['sort_title_desc'] = 'Атауы ↓';
$_Lang['sort_downloads_asc'] = 'Орнатулар ↑';
$_Lang['sort_downloads_desc'] = 'Орнатулар ↓';
$_Lang['console_category'] = 'Пакеттер';
$_Lang['console_install_title'] = 'Консоль арқылы орнату';
$_Lang['console_install_intro'] = 'Бұл пакетті консоль арқылы қолмен немесе жоспарлағыш арқылы автоматты түрде орнатуға болады.';
$_Lang['console_install_manual_label'] = 'Қолмен орнату';
$_Lang['console_install_auto_label'] = 'Автоматты орнату';
$_Lang['console_install_auto_ready'] = 'Жоспарлағыш іске қосылған. Пакетті автоматты орнату кезегіне қоюға болады.';
$_Lang['console_install_auto_disabled'] = 'Автоматты орнату қазір қолжетімсіз. Алдымен жоспарлағышты іске қосыңыз.';
$_Lang['console_install_auto_permission'] = 'Автоматты орнату үшін жүйелік тапсырмаларды басқару рұқсаты қажет.';
$_Lang['console_install_scheduler_intro'] = 'Автоматты орнатуды қосу үшін тиісті ортада жоспарлағышты іске қосыңыз:';
$_Lang['console_install_scheduler_local'] = 'Жергілікті';
$_Lang['console_install_scheduler_server'] = 'Сервер';
$_Lang['console_install_scheduler_command_local'] = 'php artisan schedule:work';
$_Lang['console_install_scheduler_command_server'] = '* * * * * cd {core_path} && php artisan schedule:run >/dev/null 2>&1';
$_Lang['console_install_auto_warning'] = 'Жоспарлағыш немесе орындаушы қазір толық жұмыс істемейді. Орнатуды кезекке қоюға болады, бірақ орындалуын бақылаңыз.';
$_Lang['console_install_step_open_core'] = '1. Осы жобаның core каталогына өтіңіз:';
$_Lang['console_install_step_run_artisan'] = '2. artisan пәрменін орындаңыз:';
$_Lang['console_install_command_label'] = 'Пәрмен';
$_Lang['console_install_note'] = 'Әзірге Store мұнда тек пәрменді көрсетеді. Орнату консоль арқылы орындалады, модуль жасырын өзгерістер енгізбейді.';
$_Lang['console_install_source_label'] = 'Дереккөз';
$_Lang['source_label_legacy'] = 'Ескі пішім';
$_Lang['source_label_console'] = '';
$_Lang['popup_version'] = 'Нұсқа';
$_Lang['popup_updated'] = 'Жаңартылған';
$_Lang['popup_author'] = 'Автор';
$_Lang['popup_downloads'] = 'Жүктеулер';
$_Lang['popup_source'] = 'Дереккөз';
$_Lang['popup_readme'] = 'README';
$_Lang['popup_open_repo'] = 'Репозиторийді ашу';
$_Lang['popup_loading'] = 'Жүктелуде...';
$_Lang['popup_readme_missing'] = 'Бұл пакет үшін README.md әлі табылмады.';
$_Lang['popup_copy_command'] = 'Пәрменді көшіру';
$_Lang['popup_copied'] = 'Көшірілді';
$_Lang['system_task_queue_install'] = 'Орнатуды кезекке қою';
$_Lang['system_task_modal_warning'] = 'Ескерту';
$_Lang['system_task_modal_status'] = 'Күйі';
$_Lang['system_task_modal_step'] = 'Қадам';
$_Lang['system_task_modal_progress'] = 'Орындалуы';
$_Lang['system_task_modal_elapsed'] = 'Өткен уақыт';
$_Lang['system_task_modal_logs'] = 'Журналдар';
$_Lang['system_task_modal_close'] = 'Жабу';
$_Lang['system_task_modal_queue_error'] = 'Бұл жүйелік тапсырманы кезекке қою мүмкін болмады.';
$_Lang['system_task_modal_blocked_by_task'] = 'Қазір басқа тапсырма бұл әрекетті бұғаттап тұр.';
$_Lang['system_task_modal_blocked_task'] = 'Бұғаттайтын тапсырма';
$_Lang['system_task_modal_open_blocking'] = 'Бұғаттайтын тапсырманы ашу';
$_Lang['system_task_modal_skip_queued'] = 'Кезектегі тапсырманы өткізу';
$_Lang['system_task_modal_uninstall_note'] = 'Әзірге бұл пакетті тек Composer ішінен жояды. Пакет жасаған нысандар әлі автоматты түрде тазаланбайды.';
$_Lang['system_task_modal_cancel_queued'] = 'Кезектегі тапсырмадан бас тарту';
$_Lang['system_task_modal_stale_local'] = 'Бұл тапсырма кезекте бір минуттан артық тұр, жоспарлағыш жұмыс істемейтін сияқты. php artisan schedule:work пәрменін қайта іске қосыңыз немесе кезектегі тапсырмадан бас тартыңыз.';
$_Lang['system_task_modal_stale_server'] = 'Бұл тапсырма кезекте бір минуттан артық тұр, cron немесе жоспарлағыш жұмыс істемейтін сияқты. cron schedule:run жұмысын жалғастырыңыз немесе кезектегі тапсырмадан бас тартыңыз.';
$_Lang['console_uninstall_intro'] = 'Бұл консольдік пакетті жою әзірге оны тек Composer ішінен жояды. Пакет жасаған нысандар орнында қалады.';
$_Lang['console_uninstall_scheduler_intro'] = 'Автоматты жоюды қосу үшін тиісті ортада жоспарлағышты іске қосыңыз:';
$_Lang['console_uninstall_confirm'] = 'Бұл консольдік пакетті Composer ішінен жою керек пе?';
$_Lang['delete'] = 'Жою';
$_Lang['delete_preview_title'] = 'Пакетті жою';
$_Lang['delete_preview_intro'] = 'Осы пакеттің қандай файлдары мен басқару жазбаларын жою керектігін таңдаңыз.';
$_Lang['delete_preview_files_label'] = 'Файлдар';
$_Lang['delete_preview_components_label'] = 'Құрамдастар';
$_Lang['delete_preview_confirm'] = 'Таңдалғандарды жою';
$_Lang['delete_preview_cancel'] = 'Бас тарту';
$_Lang['delete_preview_loading'] = 'Жоюды алдын ала қарау дайындалуда...';
$_Lang['delete_preview_empty'] = 'Бұл пакеттен жоятын ештеңе табылмады.';
$_Lang['delete_preview_success'] = 'Ескі пішімдегі пакет жойылды.';
$_Lang['delete_preview_error'] = 'Бұл пакетті жою мүмкін болмады.';
$_Lang['legacy_delete_error'] = 'Жоюды алдын ала қарауды дайындау мүмкін болмады.';
$_Lang['legacy_delete_download_error'] = 'Жоюды алдын ала қарау үшін пакет архивін жүктеу мүмкін болмады.';
$_Lang['legacy_delete_archive_error'] = 'Пакет архивін тексеру мүмкін болмады.';
$_Lang['legacy_delete_manifest_missing'] = 'Жоюды алдын ала қараудың мерзімі өтті. Оны қайта ашыңыз.';
$_Lang['legacy_delete_success'] = 'Ескі пішімдегі пакет жойылды.';
$_Lang['dev_badge'] = 'DEV';
$_Lang['dev_package_warning'] = 'Әзірлеу пакеті: шығарылымы әлі жоқ, жұмыс сайтына ұсынылмайды.';

/* Installation by file-upload */
$_Lang['install_file'] = 'Файлдан орнату';
$_Lang['choose_file_msg'] = 'Жүктеу және орнату үшін файлды таңдаңыз';
$_Lang['install_file_btn'] = 'Орнату';
$_Lang['install_file_success'] = 'Орнату сәтті аяқталды';
$_Lang['install_file_artifact_queued'] = 'Composer пакеті %1$s %2$s #%3$s жүйелік тапсырмасы ретінде орнату кезегіне қойылды. Жоспарлағыш оны жүктелген архивтен орнатады.';
$_Lang['install_file_artifact_no_version'] = 'Архивте %1$s Composer пакеті бар, бірақ нұсқасы жоқ. composer.json файлына "version" қосыңыз немесе нұсқаны файл атауында көрсетіңіз, мысалы %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = '%1$s Composer пакетін кезекке қою мүмкін болмады: %2$s';
$_Lang['install_file_artifact_invalid'] = 'Архив Composer пакетіне ұқсайды, бірақ %1$s пакет "name" өрісі бар жарамды JSON емес. Ештеңе орнатылмады.';

/* faq */
$_Lang['faq'] = '<li><a href="https://extras.evo.im/faq/whatfor.html" target="_blank">Репозиторий не үшін қажет</a></li><li><a href="https://extras.evo.im/faq/howtoadd.html" target="_blank">Репозиторийге пакетті қалай қосуға болады</a></li><li><a href="https://extras.evo.im/faq/howtocollect.html" target="_blank">Орнатуға арналған пакетті қалай жасауға болады</a></li>';

?>
