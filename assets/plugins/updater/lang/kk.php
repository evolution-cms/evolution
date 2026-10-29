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

$_lang['pluginname'] = 'Жаңартқыш';
$_lang['system_update'] = 'CMS жүйесінің жаңа нұсқасы қолжетімді';
$_lang['are_you_sure_update'] = 'Жүйені жаңартуды іске қосқыңыз келетініне сенімдісіз бе?';
$_lang["cms_outdated_msg"] = 'Мазмұнды басқару жүйесі ескірген. Жаңарту үшін сайт әзірлеушілеріне хабарласыңыз. Ағымдағы нұсқа';
$_lang['bkp_before_msg'] = 'Жүйені жаңартудан бұрын сақтық көшірме жасауды ұсынамыз, жаңарту өз тәуекеліңізбен орындалады!';
$_lang['updateButton_txt'] = 'Мына нұсқаға жаңарту';
$_lang['updateButtonCommit_txt'] = 'Осы коммитке жаңарту';
$_lang['table_commitdate'] = 'Коммит күні';
$_lang['table_titleauthor'] = 'Атауы (автор)';
//Error Messages
$_lang['error_curl'] = 'PHP ішінде CURL функциясын қосу қажет';
$_lang['error_zip'] = 'PHP ішінде ZIP кеңейтімін қосу қажет';
$_lang['error_openssl'] = 'PHP ішінде OpenSSL функциясын қосу қажет';
$_lang['error_overwrite'] = 'Evolution CMS файлдарын қайта жазу мүмкін емес';
$_lang['error_failedtogetfeed'] = 'Арнаны алу мүмкін болмады';

$_lang['artisan_update'] = 'Жаңарту үшін <b>core</b> қалтасынан консоль пәрменін орындаңыз: <b>php artisan make:site update</b>';
$_lang["help_donate_msg"] = 'Evolution CMS әзірлеушілеріне <a href="https://ko-fi.com/evolutioncms" target="_blank">ko-fi.com/evolutioncms ☕</a> арқылы кофе алып беріңіз. Бүгін Evolution CMS жанкүйері болыңыз ❤️!';

$_lang['updater_notice_title'] = 'CMS жүйесінің жаңа нұсқасы қолжетімді';
$_lang['updater_notice_text_1'] = 'Жаңарту міндетті емес, бірақ тұрақтылықты, өнімділікті және зиянды бағдарламалар мен DDoS шабуылдарынан қорғанысты айтарлықтай жақсартады.';
$_lang['updater_notice_text_2'] = 'Жүйені қолмен жаңарта аласыз. Бұл техникалық дағдыларды қажет етеді, сондықтан оны жүйелік әкімшіге тапсырған дұрыс.';
$_lang['updater_notice_text_3'] = 'Немесе жаңартуды орындау үшін әзірлеушілеріңізге хабарласыңыз.';
$_lang['updater_versions_line'] = 'Ағымдағы: %s -> Қолжетімді: %s';

$_lang['updater_severity_critical'] = 'Маңызды';
$_lang['updater_severity_warning'] = 'Ескерту';
$_lang['updater_severity_info'] = 'Ақпарат';

$_lang['updater_action_release'] = 'Өзгерістер журналы';
$_lang['updater_action_release_all'] = 'Барлық шығарылымдар';
$_lang['updater_action_branch'] = 'Тармақты/сілтемені қарау';
$_lang['updater_action_branch_all'] = 'Барлық тармақтар';
$_lang['updater_action_support'] = 'Әзірлеушілерге хабарласу';
$_lang['updater_action_hide_day'] = '1 күн көрсетпеу';
$_lang['updater_action_hide_today'] = 'Бүгін бұл хабарды қайта көрсетпеу';
$_lang['updater_support_hint'] = 'Қолдау:';

$_lang['updater_mail_subject'] = '[EVO] Сайтты жаңарту сұрауы %s: %s -> %s';
$_lang['updater_mail_line_site'] = 'Сайт: %s';
$_lang['updater_mail_line_update'] = 'Жаңарту: %s -> %s';
$_lang['updater_mail_line_request'] = 'Мерзімі мен құнын бағалаңыз.';

$_lang['updater_widget_title'] = 'CMS жүйесінің жаңа нұсқасы қолжетімді';
$_lang['updater_badge_current'] = 'Ағымдағы';
$_lang['updater_badge_available'] = 'Қолжетімді';
$_lang['updater_current_full'] = 'Толық нұсқа: %s';
$_lang['updater_action_support_with_email'] = 'Әзірлеушілерге хабарласу: %s';
$_lang['updater_cli_summary'] = 'Қолмен жаңарту (CLI)';
$_lang['updater_cli_intro'] = 'Қолмен жаңартсаңыз, терминалда мына пәрменді орындаңыз:';
$_lang['updater_cli_command'] = 'php artisan make:site update';
$_lang['updater_branch_target_label'] = 'Тармақ/сілтеме';
$_lang['updater_branch_mode_notice'] = 'Әзірлеу тармағынан/сілтемесінен жаңарту режимі қосылған. Оны тек сынақ немесе бақыланатын қызмет көрсету үшін пайдаланыңыз.';
$_lang['updater_line_mode_notice'] = 'Бұл сайт жаңа коммиттері бар %s тармағын қадағалайды. Шығарылымға оралу үшін core қалтасында php artisan make:site update <release> пәрменін орындаңыз.';
$_lang['updater_action_compare'] = 'Өзгерістерді қарау';
$_lang['updater_notice_backup_warning'] = 'Жаңартудан бұрын сақтық көшірме жасауды ұмытпаңыз.';
$_lang['updater_backup_action_label'] = 'сақтық көшірме жасау';
$_lang['updater_live_update_button'] = 'Қолмен жаңарту (WEB)';
$_lang['updater_live_update_title'] = 'Жүйені жаңарту';
$_lang['updater_live_update_intro'] = 'Жоспарлағыш пен орындаушы қолжетімді, сондықтан Evolution CMS жаңартуды кезекке қойып, орындалуын осында көрсете алады.';
$_lang['updater_live_update_backup'] = 'Жалғастырмас бұрын тексерілген сақтық көшірме жасаңыз. Жаңартқыш файлдар мен дерекқор деректерін өзгертеді.';
$_lang['updater_live_update_backup_checkbox'] = 'Жаңартудан бұрын дерекқордың сақтық көшірмесін жасау';
$_lang['updater_live_update_current'] = 'Дерекқордың ағымдағы нұсқасы';
$_lang['updater_live_update_current_version'] = 'дерекқордың ағымдағы нұсқасы';
$_lang['updater_live_update_target'] = 'Мақсатты нұсқа';
$_lang['updater_live_update_branch_target'] = 'Мақсатты тармақ/сілтеме';
$_lang['updater_live_update_repository'] = 'Репозиторий';
$_lang['updater_live_update_health'] = 'Жоспарлағыш / орындаушы';
$_lang['updater_live_update_confirm'] = 'Жаңартуды бастау';
$_lang['updater_live_update_cancel'] = 'Бас тарту';
$_lang['updater_live_update_queueing'] = 'Жаңарту кезекке қойылуда...';
$_lang['updater_live_update_queued'] = 'Жаңарту тапсырмасы кезекке қойылды. Орындаушы күтілуде...';
$_lang['updater_live_update_status'] = 'Күйі';
$_lang['updater_live_update_step'] = 'Қадам';
$_lang['updater_live_update_progress'] = 'Орындалуы';
$_lang['updater_live_update_close'] = 'Жабу';
$_lang['updater_live_update_close_reload'] = 'Жабу және қайта жүктеу';
$_lang['updater_live_update_completed'] = 'Жаңарту аяқталды. Басқару тақтасын қайта жүктеп, жаңа нұсқаны тексеру үшін терезені жабыңыз.';
$_lang['updater_live_update_response_changed'] = 'Жаңарту кезінде басқару тақтасының жауабы өзгерді. Басқару тақтасын қайта жүктеп, соңғы күйін көру үшін терезені жабыңыз.';
$_lang['updater_live_update_failed'] = 'Жаңартуды бастау мүмкін болмады.';
$_lang['updater_live_update_invalid_response'] = 'Басқару тақтасы жаңартуға жарамсыз жауап қайтарды.';
