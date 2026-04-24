<?php
/* List pack */
$_Lang['version'] = "Версія";
$_Lang['author'] = "Автор";
$_Lang['date'] = "Випущено";
$_Lang['downloads'] = "Завантажено";
$_Lang['more'] = "more";
$_Lang['install'] = "Встановити";
$_Lang['reinstall'] = "Перевстановити";
$_Lang['update'] = "Оновити";
$_Lang['installed'] = "Встановлено";
$_Lang['installed2'] = "Встановлено";
$_Lang['exit'] = "Вихід";
$_Lang['alert_overwrite'] = "Встановлення цього доповнення перезапише наявні файли. Продовжити?";

/* Main */
$_Lang['store_name'] = "Керування пакетами";
$_Lang['category'] = "Категорії";

/* Login form */
$_Lang['enter_in_own_repository'] = "Увійти до власного репозиторію";
$_Lang['password'] = "Пароль";
$_Lang['enter'] = "Увійти";
$_Lang['email'] = "Email";
$_Lang['register'] = "Зареєструватися";
$_Lang['own_repository'] = "Мій репозиторій";
$_Lang['login_errors'] = "Неправильний Email або пароль";

/* &tpl filters */
$_Lang['s1_1_reated_on'] = "Даті створення";
$_Lang['s1_2_name'] = "Назві";
$_Lang['s1_3_position'] = "Позиції";
$_Lang['s1_4_published'] = "Опубліковано";
$_Lang['s2_1_up'] = "За спаданням";
$_Lang['s2_2_down'] = "За зростанням";
$_Lang['search'] = "Пошук за категорією: ";
$_Lang['sort_by'] = "Сортувати:";
$_Lang['sort_default'] = "За замовч.";
$_Lang['sort_title_asc'] = "Назва ↑";
$_Lang['sort_title_desc'] = "Назва ↓";
$_Lang['sort_downloads_asc'] = "Інстали ↑";
$_Lang['sort_downloads_desc'] = "Інстали ↓";
$_Lang['console_category'] = "Packages";
$_Lang['console_install_title'] = "Встановлення через консоль";
$_Lang['console_install_intro'] = "Цей пакет можна встановити вручну через консоль або автоматично через планувальник задач.";
$_Lang['console_install_manual_label'] = "Ручне встановлення";
$_Lang['console_install_auto_label'] = "Автоматичне встановлення";
$_Lang['console_install_auto_ready'] = "Планувальник уже працює. Пакет можна одразу поставити в чергу на автоматичне встановлення.";
$_Lang['console_install_auto_disabled'] = "Автоматичне встановлення зараз недоступне. Спочатку запустіть планувальник.";
$_Lang['console_install_auto_permission'] = "Для автоматичного встановлення потрібні права на керування system tasks.";
$_Lang['console_install_scheduler_intro'] = "Щоб автоматичне встановлення працювало, запустіть планувальник у потрібному середовищі:";
$_Lang['console_install_scheduler_local'] = "Локально";
$_Lang['console_install_scheduler_server'] = "На сервері";
$_Lang['console_install_scheduler_command_local'] = "php artisan schedule:work";
$_Lang['console_install_scheduler_command_server'] = "* * * * * cd {core_path} && php artisan schedule:run >/dev/null 2>&1";
$_Lang['console_install_auto_warning'] = "Планувальник або worker зараз працює нестабільно. Можна ставити в чергу, але варто стежити за прогресом.";
$_Lang['console_install_step_open_core'] = "1. Перейдіть у папку core цього проєкту:";
$_Lang['console_install_step_run_artisan'] = "2. Запустіть artisan команду:";
$_Lang['console_install_command_label'] = "Команда";
$_Lang['console_install_note'] = "Поки що Store лише показує тут команду. Саме встановлення лишається в консольному flow, щоб модуль нічого тихо не змінював.";
$_Lang['console_install_source_label'] = "Джерело";
$_Lang['source_label_legacy'] = "Legacy";
$_Lang['source_label_console'] = "";
$_Lang['popup_version'] = "Версія";
$_Lang['popup_updated'] = "Оновлено";
$_Lang['popup_author'] = "Автор";
$_Lang['popup_downloads'] = "Завантажень";
$_Lang['popup_source'] = "Джерело";
$_Lang['popup_readme'] = "README";
$_Lang['popup_open_repo'] = "Відкрити репозиторій";
$_Lang['popup_loading'] = "Завантаження...";
$_Lang['popup_readme_missing'] = "README.md для цього пакета поки не знайдено.";
$_Lang['popup_copy_command'] = "Копіювати команду";
$_Lang['popup_copied'] = "Скопійовано";
$_Lang['system_task_queue_install'] = "Поставити в чергу";
$_Lang['system_task_modal_warning'] = "Попередження";
$_Lang['system_task_modal_status'] = "Статус";
$_Lang['system_task_modal_step'] = "Крок";
$_Lang['system_task_modal_progress'] = "Прогрес";
$_Lang['system_task_modal_elapsed'] = "Минуло часу";
$_Lang['system_task_modal_logs'] = "Лог";
$_Lang['system_task_modal_close'] = "Закрити";
$_Lang['system_task_modal_queue_error'] = "Не вдалося поставити system task у чергу.";
$_Lang['system_task_modal_blocked_by_task'] = "Цю дію зараз блокує інша task.";
$_Lang['system_task_modal_blocked_task'] = "Блокуюча task";
$_Lang['system_task_modal_open_blocking'] = "Відкрити блокуючу task";
$_Lang['system_task_modal_skip_queued'] = "Пропустити queued task";
$_Lang['system_task_modal_uninstall_note'] = "Поки що це тільки видалення пакета з Composer. Артефакти, які пакет уже створив, автоматично не прибираються.";
$_Lang['system_task_modal_cancel_queued'] = "Скасувати queued task";
$_Lang['system_task_modal_stale_local'] = "Ця task висить у черзі вже більше хвилини, а планувальник схоже не працює. Знову запустіть php artisan schedule:work або скасуйте queued task.";
$_Lang['system_task_modal_stale_server'] = "Ця task висить у черзі вже більше хвилини, а cron або scheduler схоже не працює. Відновіть cron schedule:run або скасуйте queued task.";
$_Lang['console_uninstall_intro'] = "Видалення цього console-пакета поки що прибирає тільки сам пакет із Composer. Створені ним артефакти залишаються.";
$_Lang['console_uninstall_scheduler_intro'] = "Щоб автоматичне видалення працювало, запустіть планувальник у потрібному середовищі:";
$_Lang['console_uninstall_confirm'] = "Видалити цей console-пакет із Composer?";
$_Lang['delete'] = "Видалити";
$_Lang['delete_preview_title'] = "Видалення пакета";
$_Lang['delete_preview_intro'] = "Оберіть, що саме потрібно видалити з файлів і записів менеджера для цього пакета.";
$_Lang['delete_preview_files_label'] = "Файли";
$_Lang['delete_preview_components_label'] = "Компоненти";
$_Lang['delete_preview_confirm'] = "Видалити вибране";
$_Lang['delete_preview_cancel'] = "Скасувати";
$_Lang['delete_preview_loading'] = "Готуємо preview видалення...";
$_Lang['delete_preview_empty'] = "Для цього пакета нічого не знайдено для видалення.";
$_Lang['delete_preview_success'] = "Legacy пакет видалено.";
$_Lang['delete_preview_error'] = "Не вдалося видалити цей пакет.";
$_Lang['legacy_delete_error'] = "Не вдалося побудувати preview видалення.";
$_Lang['legacy_delete_download_error'] = "Не вдалося завантажити архів цього пакета для preview видалення.";
$_Lang['legacy_delete_archive_error'] = "Не вдалося розібрати архів пакета.";
$_Lang['legacy_delete_manifest_missing'] = "Preview видалення вже недоступний. Відкрийте його ще раз.";
$_Lang['legacy_delete_success'] = "Legacy пакет видалено.";
$_Lang['dev_badge'] = "DEV";
$_Lang['dev_package_warning'] = "Дев-пакет: поки без релізу, не рекомендовано для production.";

/* Installation by file-upload */
$_Lang['install_file'] = "Встановлення з архіву";
$_Lang['choose_file_msg'] = "Виберіть файл";
$_Lang['install_file_btn'] = "Встановити";
$_Lang['install_file_success'] = "Встановлення завершено";

/* FAQ */
$_Lang['faq'] = '
<li><a href="https://extras.evo.im/ru/faq/whatfor.html" target="_blank">Навіщо власний репозиторій</a></li>
<li><a href="https://extras.evo.im/ru/faq/howtoadd.html" target="_blank">Як додати пакет до репозиторію</a></li>
<li><a href="https://extras.evo.im/ru/faq/howtocollect.html" target="_blank">Як правильно зібрати пакет для встановлення</a></li>';
?>
