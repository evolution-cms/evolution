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
$_lang['system_update'] = 'Доступна нова версія системи управління сайтом';
$_lang['are_you_sure_update'] = 'Ви впевнені, що хочете запустити оновлення системи?';
$_lang["cms_outdated_msg"] = 'Система управління контентом застаріла. Для оновлення звертайтеся до розробників сайту. Поточна версія';
$_lang['bkp_before_msg'] = 'Ми наполегливо рекомендуємо зробити резервну копію перед оновленням системи. Оновлення виконується на власний ризик!';
$_lang['updateButton_txt'] = 'Оновлення до версії';
$_lang['updateButtonCommit_txt'] = 'Оновіть цей комміт';
$_lang['table_commitdate'] = 'Дата фіксації';
$_lang['table_titleauthor'] = 'Назва (автор)';
//Error Messages
$_lang['error_curl'] = 'Вам потрібно ввімкнути функцію CURL у PHP';
$_lang['error_zip'] = 'Необхідно включити ZIP в PHP';
$_lang['error_openssl'] = 'Вам потрібно ввімкнути функцію OpenSSL у PHP';
$_lang['error_overwrite'] = 'Файли Evolution CMS недоступні для перезапису';
$_lang['error_failedtogetfeed'] = 'Не вдалося отримати канал';

$_lang['artisan_update'] = 'Для оновлення запустіть консольну команду з <b>core</b> теки: <b>php artisan make:site update</b>';
$_lang["help_donate_msg"] = 'Купуйте каву розробникам Evolution CMS на <a href="https://ko-fi.com/evolutioncms" target="_blank">ko-fi.com/evolutioncms ☕</a>. Станьте прихильником Evolution CMS ❤️ сьогодні!';

$_lang['updater_notice_title'] = 'Доступна нова версія системи управління сайтом';
$_lang['updater_notice_text_1'] = 'Оновлення не обов\'язкове, але суттєво покращує стабільність роботи сайту, підвищує швидкість роботи та забезпечує надійніший захист від вірусів і DDoS-атак.';
$_lang['updater_notice_text_2'] = 'Систему можна оновити самостійно. Процес потребує певних навичок, тому краще довірити це системному адміністратору.';
$_lang['updater_notice_text_3'] = 'Або зверніться для оновлення до розробників.';
$_lang['updater_versions_line'] = 'Поточна: %s -> Доступна: %s';

$_lang['updater_severity_critical'] = 'Critical';
$_lang['updater_severity_warning'] = 'Warning';
$_lang['updater_severity_info'] = 'Info';

$_lang['updater_action_release'] = 'Що змінилось (Changelog)';
$_lang['updater_action_release_all'] = 'Усі релізи';
$_lang['updater_action_support'] = 'Написати розробникам';
$_lang['updater_action_hide_day'] = 'Більше не показувати (1 день)';
$_lang['updater_action_hide_today'] = 'Сьогодні більше не показувати це повідомлення';
$_lang['updater_support_hint'] = 'Підтримка:';

$_lang['updater_mail_subject'] = '[EVO] Оновлення сайту %s: %s -> %s';
$_lang['updater_mail_line_site'] = 'Сайт: %s';
$_lang['updater_mail_line_update'] = 'Оновлення: %s -> %s';
$_lang['updater_mail_line_request'] = 'Прошу оцінити терміни та вартість.';

$_lang['updater_widget_title'] = 'Доступна нова версія системи управління сайтом';
$_lang['updater_badge_current'] = 'Поточна';
$_lang['updater_badge_available'] = 'Доступна';
$_lang['updater_current_full'] = 'Повна версія: %s';
$_lang['updater_action_support_with_email'] = 'Написати розробникам: %s';
$_lang['updater_cli_summary'] = 'Самостійне оновлення (CLI)';
$_lang['updater_cli_intro'] = 'Якщо оновлюєте самостійно, виконайте команду в консолі:';
$_lang['updater_cli_command'] = 'php artisan make:site update';
$_lang['updater_notice_backup_warning'] = 'Не забудьте зробити резервну копію сайту перед оновленням.';
