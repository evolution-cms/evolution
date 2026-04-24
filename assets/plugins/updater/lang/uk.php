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
$_lang['updater_action_branch'] = 'Переглянути branch/ref';
$_lang['updater_action_branch_all'] = 'Усі гілки';
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
$_lang['updater_branch_target_label'] = 'Branch/ref';
$_lang['updater_branch_mode_notice'] = 'Увімкнено режим оновлення з branch/ref. Використовуйте його лише для тестів або контрольованого обслуговування.';
$_lang['updater_notice_backup_warning'] = 'Не забудьте зробити резервну копію сайту перед оновленням.';
$_lang['updater_live_update_button'] = 'Оновити в менеджері';
$_lang['updater_live_update_title'] = 'Оновлення системи';
$_lang['updater_live_update_intro'] = 'Scheduler і worker доступні, тому Evolution CMS може поставити оновлення в чергу і показувати прогрес тут.';
$_lang['updater_live_update_backup'] = 'Перед продовженням зробіть перевірену резервну копію. Оновлення змінює файли та дані бази.';
$_lang['updater_live_update_backup_checkbox'] = 'Зробити резервну копію бази перед оновленням';
$_lang['updater_live_update_current'] = 'Поточна версія бази';
$_lang['updater_live_update_current_version'] = 'поточна версія бази';
$_lang['updater_live_update_target'] = 'Цільова версія';
$_lang['updater_live_update_branch_target'] = 'Цільова branch/ref';
$_lang['updater_live_update_repository'] = 'Репозиторій';
$_lang['updater_live_update_health'] = 'Scheduler / worker';
$_lang['updater_live_update_confirm'] = 'Запустити оновлення';
$_lang['updater_live_update_cancel'] = 'Скасувати';
$_lang['updater_live_update_queueing'] = 'Ставимо оновлення в чергу...';
$_lang['updater_live_update_queued'] = 'Задачу оновлення створено. Очікуємо worker...';
$_lang['updater_live_update_status'] = 'Статус';
$_lang['updater_live_update_step'] = 'Крок';
$_lang['updater_live_update_progress'] = 'Прогрес';
$_lang['updater_live_update_close'] = 'Закрити';
$_lang['updater_live_update_close_reload'] = 'Закрити й перезавантажити';
$_lang['updater_live_update_completed'] = 'Оновлення завершено. Закрийте це вікно, щоб перезавантажити менеджер і перевірити нову версію.';
$_lang['updater_live_update_response_changed'] = 'Відповідь менеджера змінилась під час оновлення. Закрийте це вікно, щоб перезавантажити менеджер і побачити фінальний стан.';
$_lang['updater_live_update_failed'] = 'Не вдалося запустити оновлення.';
$_lang['updater_live_update_invalid_response'] = 'Менеджер повернув некоректну відповідь оновлення.';
