<?php
/**
 * Evolution CMS ka.php language file
 *
 * @author Evolution CMS Team
 * @version 3.5.x
 * @date 2026-10-29 20:30:13
 *
 * @language Georgian
 * @package Evolution CMS
 * @subpackage assets
 * Please commit your language changes here: https://github.com/evolution-cms/evolution
 */

$_lang['pluginname'] = 'განმაახლებელი';
$_lang['system_update'] = 'ხელმისაწვდომია CMS-ის ახალი ვერსია';
$_lang['are_you_sure_update'] = 'ნამდვილად გსურთ სისტემის განახლების გაშვება?';
$_lang["cms_outdated_msg"] = 'შიგთავსის მართვის სისტემა მოძველებულია. განახლებისთვის დაუკავშირდით საიტის დეველოპერებს. მიმდინარე ვერსია';
$_lang['bkp_before_msg'] = 'სისტემის განახლებამდე გირჩევთ სარეზერვო ასლის შექმნას. განახლება სრულდება თქვენი პასუხისმგებლობით !!';
$_lang['updateButton_txt'] = 'განახლება ვერსიამდე';
$_lang['updateButtonCommit_txt'] = 'განახლება ამ კომიტამდე';
$_lang['table_commitdate'] = 'კომიტის თარიღი';
$_lang['table_titleauthor'] = 'სათაური (ავტორი)';
//Error Messages
$_lang['error_curl'] = 'PHP-ში უნდა ჩართოთ CURL ფუნქცია';
$_lang['error_zip'] = 'PHP-ში უნდა ჩართოთ ZIP';
$_lang['error_openssl'] = 'PHP-ში უნდა ჩართოთ OpenSSL ფუნქცია';
$_lang['error_overwrite'] = 'Evolution CMS-ის ფაილების გადაწერა შეუძლებელია';
$_lang['error_failedtogetfeed'] = 'არხის მიღება ვერ მოხერხდა';

$_lang['artisan_update'] = 'განახლებისთვის გაუშვით კონსოლის ბრძანება <b>core</b> საქაღალდიდან: <b>php artisan make:site update</b>';
$_lang["help_donate_msg"] = 'შეუძინეთ ყავა Evolution CMS-ის დეველოპერებს <a href="https://ko-fi.com/evolutioncms" target="_blank">ko-fi.com/evolutioncms ☕</a>-ზე. გახდით Evolution CMS-ის მხარდამჭერი ❤️ დღესვე!';

$_lang['updater_notice_title'] = 'ხელმისაწვდომია CMS-ის ახალი ვერსია';
$_lang['updater_notice_text_1'] = 'განახლება ნებაყოფლობითია, თუმცა მნიშვნელოვნად აუმჯობესებს სტაბილურობას, წარმადობასა და დაცვას მავნე პროგრამებისა და DDoS შეტევებისგან.';
$_lang['updater_notice_text_2'] = 'სისტემის განახლება შეგიძლიათ ხელით. პროცესი ტექნიკურ ცოდნას მოითხოვს, ამიტომ უმჯობესია ის სისტემის ადმინისტრატორს მიანდოთ.';
$_lang['updater_notice_text_3'] = 'ან განახლების შესასრულებლად დაუკავშირდით თქვენს დეველოპერებს.';
$_lang['updater_versions_line'] = 'მიმდინარე: %s -> ხელმისაწვდომი: %s';

$_lang['updater_severity_critical'] = 'კრიტიკული';
$_lang['updater_severity_warning'] = 'გაფრთხილება';
$_lang['updater_severity_info'] = 'ინფორმაცია';

$_lang['updater_action_release'] = 'ცვლილებების ჟურნალი';
$_lang['updater_action_release_all'] = 'ყველა გამოშვება';
$_lang['updater_action_branch'] = 'შტოს/რეფერენსის ნახვა';
$_lang['updater_action_branch_all'] = 'ყველა შტო';
$_lang['updater_action_support'] = 'დეველოპერებთან დაკავშირება';
$_lang['updater_action_hide_day'] = 'არ აჩვენო 1 დღის განმავლობაში';
$_lang['updater_action_hide_today'] = 'დღეს აღარ აჩვენო ეს შეტყობინება';
$_lang['updater_support_hint'] = 'მხარდაჭერა:';

$_lang['updater_mail_subject'] = '[EVO] საიტის განახლების მოთხოვნა %s: %s -> %s';
$_lang['updater_mail_line_site'] = 'საიტი: %s';
$_lang['updater_mail_line_update'] = 'განახლება: %s -> %s';
$_lang['updater_mail_line_request'] = 'გთხოვთ, შეაფასოთ ვადა და ღირებულება.';

$_lang['updater_widget_title'] = 'ხელმისაწვდომია CMS-ის ახალი ვერსია';
$_lang['updater_badge_current'] = 'მიმდინარე';
$_lang['updater_badge_available'] = 'ხელმისაწვდომი';
$_lang['updater_current_full'] = 'სრული ვერსია: %s';
$_lang['updater_action_support_with_email'] = 'დეველოპერებთან დაკავშირება: %s';
$_lang['updater_cli_summary'] = 'ხელით განახლება (CLI)';
$_lang['updater_cli_intro'] = 'თუ ხელით ანახლებთ, ტერმინალში გაუშვით ეს ბრძანება:';
$_lang['updater_cli_command'] = 'php artisan make:site update';
$_lang['updater_branch_target_label'] = 'შტო/რეფერენსი';
$_lang['updater_branch_mode_notice'] = 'ჩართულია განვითარების შტოს/რეფერენსის განახლების რეჟიმი. გამოიყენეთ მხოლოდ ტესტირებისთვის ან კონტროლირებადი ტექნიკური მომსახურებისთვის.';
$_lang['updater_line_mode_notice'] = 'ეს საიტი მიჰყვება %s-ს, რომელსაც ახალი კომიტები აქვს. გამოშვებაზე დასაბრუნებლად core საქაღალდეში გაუშვით php artisan make:site update <release>.';
$_lang['updater_action_compare'] = 'ცვლილებების ნახვა';
$_lang['updater_notice_backup_warning'] = 'განახლებამდე არ დაგავიწყდეთ სარეზერვო ასლის შექმნა.';
$_lang['updater_backup_action_label'] = 'სარეზერვო ასლის შექმნა';
$_lang['updater_live_update_button'] = 'ხელით განახლება (WEB)';
$_lang['updater_live_update_title'] = 'სისტემის განახლება';
$_lang['updater_live_update_intro'] = 'დამგეგმავი და შემსრულებელი ხელმისაწვდომია, ამიტომ Evolution CMS-ს შეუძლია განახლების რიგში ჩაყენება და აქ მიმდინარეობის პირდაპირ ჩვენება.';
$_lang['updater_live_update_backup'] = 'გაგრძელებამდე შექმენით შემოწმებული სარეზერვო ასლი. განმაახლებელი ცვლის ფაილებსა და მონაცემთა ბაზის მონაცემებს.';
$_lang['updater_live_update_backup_checkbox'] = 'განახლებამდე მონაცემთა ბაზის სარეზერვო ასლის შექმნა';
$_lang['updater_live_update_current'] = 'მონაცემთა ბაზის მიმდინარე ვერსია';
$_lang['updater_live_update_current_version'] = 'მონაცემთა ბაზის მიმდინარე ვერსია';
$_lang['updater_live_update_target'] = 'სამიზნე ვერსია';
$_lang['updater_live_update_branch_target'] = 'სამიზნე შტო/რეფერენსი';
$_lang['updater_live_update_repository'] = 'რეპოზიტორია';
$_lang['updater_live_update_health'] = 'დამგეგმავი / შემსრულებელი';
$_lang['updater_live_update_confirm'] = 'განახლების დაწყება';
$_lang['updater_live_update_cancel'] = 'გაუქმება';
$_lang['updater_live_update_queueing'] = 'განახლება რიგში დგება...';
$_lang['updater_live_update_queued'] = 'განახლების დავალება რიგშია. შემსრულებლის მოლოდინი...';
$_lang['updater_live_update_status'] = 'სტატუსი';
$_lang['updater_live_update_step'] = 'ნაბიჯი';
$_lang['updater_live_update_progress'] = 'მიმდინარეობა';
$_lang['updater_live_update_close'] = 'დახურვა';
$_lang['updater_live_update_close_reload'] = 'დახურვა და ხელახლა ჩატვირთვა';
$_lang['updater_live_update_completed'] = 'განახლება დასრულდა. დახურეთ ეს ფანჯარა მართვის პანელის ხელახლა ჩასატვირთად და ახალი ვერსიის შესამოწმებლად.';
$_lang['updater_live_update_response_changed'] = 'განახლების დროს მართვის პანელის პასუხი შეიცვალა. დახურეთ ეს ფანჯარა მართვის პანელის ხელახლა ჩასატვირთად და საბოლოო მდგომარეობის სანახავად.';
$_lang['updater_live_update_failed'] = 'განახლების დაწყება ვერ მოხერხდა.';
$_lang['updater_live_update_invalid_response'] = 'მართვის პანელმა განახლების არასწორი პასუხი დააბრუნა.';
