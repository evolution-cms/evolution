<?php
/**
 * Document Manager Module - language strings for use in the module
 *
 * Filename:       assets/modules/docmanager/lang/russian-UTF8.inc.php
 * Language:       Russian
 * Encoding:       UTF8
 * Translated by:  Jaroslav Sidorkin
 * Date:           28 April 2010
 * Version:        1.0.1
 */
 
//-- RUSSIAN LANGUAGE FILE
 
//-- titles
$_lang['DM_module_title'] = 'Менеджер ресурсов';
$_lang['DM_action_title'] = 'Выберите действие';
$_lang['DM_range_title'] = 'Укажите диапазон ID ресурсов';
$_lang['DM_tree_title'] = 'Выберите ресурсы из дерева';
$_lang['DM_update_title'] = 'Обновление завершено';
$_lang['DM_sort_title'] = 'Редактор индексов меню';

//-- tabs
$_lang['DM_doc_permissions'] = 'Изменить права на ресурсы';
$_lang['DM_template_variables'] = 'Изменить параметры (TV)';
$_lang['DM_sort_menu'] = 'Сортировать пункты меню';
$_lang['DM_change_template'] = 'Изменить шаблон';
$_lang['DM_publish'] = 'Публиковать / Отменить публикацию';
$_lang['DM_other'] = 'Другие свойства';
 
//-- buttons
$_lang['DM_close'] = 'Закрыть менеджер ресурсов';
$_lang['DM_cancel'] = 'Назад';
$_lang['DM_go'] = 'Вперед';
$_lang['DM_save'] = 'Сохранить';
$_lang['DM_sort_another'] = 'Сортировать другое';

//-- templates tab
$_lang['DM_tpl_desc'] = 'Выберите в таблице шаблон, который вы хотите установить, и укажите ID ресурсов, которым вы хотите назначить выбранный шаблон. Можно указать диапазон ID или выбрать из дерева.';
$_lang['DM_tpl_no_templates'] = 'Шаблоны не найдены';
$_lang['DM_tpl_column_id'] = 'ID';
$_lang['DM_tpl_column_name'] = 'Название';
$_lang['DM_tpl_column_description'] = 'Описание';
$_lang['DM_tpl_blank_template'] = 'Пустой шаблон';

$_lang['DM_tpl_results_message'] = 'Если Вы хотите сделать еще какие-то изменения, воспользуйтесь кнопкой "Назад". Кэш будет очищен автоматически.';

//-- template variables tab
$_lang['DM_tv_desc'] = 'Выберите в таблице шаблон, параметры (TV) которого вы хотите установить / изменить - будут загружены редакторы значений всех сопоставленных шаблону параметров (TV) . Введите желаемые значения нужных параметров (TV) и укажите через запятую имена тех параметров (TV), значения которых изменять не нужно. Затем укажите ID ресурсов, в которых вы хотите установить указанные значения параметров (TV). Можно задать диапазон ID ресурсов или выбрать из дерева.';
$_lang['DM_tv_template_mismatch'] = 'Указанный ресурс не использует выбранный шаблон.';
$_lang['DM_tv_doc_not_found'] = 'Указанный ресурс не найден в базе данных.';
$_lang['DM_tv_no_tv'] = 'Нет параметров (TV), сопоставленных этому шаблону.';
$_lang['DM_tv_no_docs'] = 'Не выбраны ресурсы для обновления.';
$_lang['DM_tv_no_template_selected'] = 'Не выбран шаблон.';
$_lang['DM_tv_loading'] = 'Загружаются параметры (TV)...';
$_lang['DM_tv_ignore_tv'] = 'Не изменять параметры (TV) (имена через запятую):';
$_lang['DM_tv_ajax_insertbutton'] = 'Вставить';

//-- document permissions tab
$_lang['DM_doc_desc'] = 'Выберите в таблице группу ресурсов и желаемое действие (добавить / исключить), затем укажите ID ресурсов, которые должны быть изменены. Можно задать диапазон ID ресурсов или выбрать из дерева.';
$_lang['DM_doc_no_docs'] = 'Нет групп ресурсов. (Группу ресурсов можно создать в разделе "Пользователи &gt; Права (менеджеров / веб-пользователей) &gt; Группы ресурсов".)';
$_lang['DM_doc_column_id'] = 'ID';
$_lang['DM_doc_column_name'] = 'Название группы ресурсов';
$_lang['DM_doc_radio_add'] = 'Добавить в группу ресурсов';
$_lang['DM_doc_radio_remove'] = 'Исключить из группы ресурсов';

$_lang['DM_doc_skip_message1'] = 'Ресурс с ID';
$_lang['DM_doc_skip_message2'] = 'уже входит в указанную группу ресурсов (пропущен)';

//-- sort menu tab
$_lang['DM_sort_pick_item'] = 'Выберите в дереве корень сайта или любой контейнер, вложенные ресурсы которого вы хотите отсортировать в меню.'; 
$_lang['DM_sort_updating'] = 'Идет обновление ...';
$_lang['DM_sort_updated'] = 'Обновление успешно завершено';
$_lang['DM_sort_nochildren'] = 'Выбранный корневой элемент не содержит вложенных ресурсов.';
$_lang['DM_sort_noid'] = 'Не выбраны ресурсы для сортировки пунктов меню. Пожалуйста, нажмите "Назад" для выбора ресурсов.';

//-- other tab
$_lang['DM_other_header'] = 'Различные свойства ресурсов';
$_lang['DM_misc_label'] = 'Доступные свойства:';
$_lang['DM_misc_desc'] = 'Выберите свойство ресурса из выпадающего списка и укажите его желаемое значение. За одну операцию можно изменить только одно свойство.';

$_lang['DM_other_dropdown_publish'] = 'Опубликовать / Отменить публикацию';
$_lang['DM_other_dropdown_show'] = 'Показывать / Не показывать в меню';
$_lang['DM_other_dropdown_search'] = 'Разрешить / Запретить поиск в содержимом';
$_lang['DM_other_dropdown_cache'] = 'Кэшировать / Не кэшировать';
$_lang['DM_other_dropdown_richtext'] = 'Использовать / Не использовать HTML-редактор';
$_lang['DM_other_dropdown_delete'] = 'Удалить / Отменить удаление';

//-- radio button text
$_lang['DM_other_publish_radio1'] = 'Опубликовать'; 
$_lang['DM_other_publish_radio2'] = 'Отменить публикацию';
$_lang['DM_other_show_radio1'] = 'Не показывать в меню'; 
$_lang['DM_other_show_radio2'] = 'Показывать в меню';
$_lang['DM_other_search_radio1'] = 'Разрешить поиск'; 
$_lang['DM_other_search_radio2'] = 'Запретить поиск';
$_lang['DM_other_cache_radio1'] = 'Кэшировать'; 
$_lang['DM_other_cache_radio2'] = 'Не кэшировать';
$_lang['DM_other_richtext_radio1'] = 'Использовать HTML-редактор'; 
$_lang['DM_other_richtext_radio2'] = 'Не использовать HTML-редактор';
$_lang['DM_other_delete_radio1'] = 'Удалить'; 
$_lang['DM_other_delete_radio2'] = 'Отменить удаление';

//-- adjust dates 
$_lang['DM_adjust_dates_header'] = 'Установить даты ресурсов';
$_lang['DM_adjust_dates_desc'] = 'Можно изменить любые даты из перечисленных ниже. Используйте "Календарь" для установки дат.';
$_lang['DM_view_calendar'] = 'Календарь';
$_lang['DM_clear_date'] = 'Очистить дату';

//-- adjust authors
$_lang['DM_adjust_authors_header'] = 'Установить Авторов / Редакторов';
$_lang['DM_adjust_authors_desc'] = 'Выберите новых авторов / редакторов ресурса из выпадающих списков.';
$_lang['DM_adjust_authors_createdby'] = 'Ресурс создан:';
$_lang['DM_adjust_authors_editedby'] = 'Ресурс изменен:';
$_lang['DM_adjust_authors_noselection'] = 'Не изменять';

 //-- labels
$_lang['DM_date_pubdate'] = 'Дата публикации:';
$_lang['DM_date_unpubdate'] = 'Дата отмены публикации:';
$_lang['DM_date_createdon'] = 'Дата создания ресурса:';
$_lang['DM_date_editedon'] = 'Дата последнего изменения ресурса:';
//$_lang['DM_date_deletedon'] = 'Дата удаления ресурса';

$_lang['DM_date_notset'] = ' (не установлена)';
//deprecated
$_lang['DM_date_dateselect_label'] = 'Выберите дату: ';

//-- document select section
$_lang['DM_select_submit'] = 'Отправить';
$_lang['DM_select_range'] = 'Вернуться у выбору диапазона ID ресурсов';
$_lang['DM_select_range_text'] = '<p><b>Можно использовать следующий синтаксис при задании диапазона (вместо &quot;n&quot; указывайте число ID ресурса):</b></p>
<ul>
	<li><b>n*</b> - изменить свойства ресурса с ID=n и непосредственных дочерних ресурсов;</li>
	<li><b>n**</b> - изменить свойства ресурса с ID=n и ВСЕХ его дочерних ресурсов;</li>
	<li><b>n-n2</b> - изменить свойства для всех ресурсов, ID которых находятся в указанном диапазоне;</li>
	<li><b>n</b> - изменить свойства для одного ресурса с ID=n;</li>
	<li><b>n*,n**,n-n2,n</b> - можно сразу указать несколько диапазонов, разделяя их запятыми.</li>
</ul><br /><p><b>Пример:</b> 1*,4**,2-20,25 - будут изменены свойства для ресурса с ID=1 и его непосредственных дочерних ресурсов, ресурса с ID=4 и всех его дочерних ресурсов, ресурсов с ID в диапазоне от 2 до 20, и ресурса с ID=25.</p>';
$_lang['DM_select_tree'] = 'Просмотреть и выбрать ресурсы в дереве';

//-- process tree/range messages
$_lang['DM_process_noselection'] = 'Ничего не выбрано. ';
$_lang['DM_process_novalues'] = 'Никаких значений не задано.';
$_lang['DM_process_limits_error'] = 'Верхняя граница диапазона меньше нижней границы:';
$_lang['DM_process_invalid_error'] = 'Недопустимое значение:';
$_lang['DM_process_update_success'] = 'Изменение прошло успешно.';
$_lang['DM_process_update_error'] = 'Изменение завершено с ошибками:';
$_lang['DM_process_back'] = 'Назад';

//-- manager access logging
$_lang['DM_log_template'] = 'Менеджер ресурсов: шаблоны изменены.';
$_lang['DM_log_templatevariables'] = 'Менеджер ресурсов: параметры (TV) изменены.';
$_lang['DM_log_docpermissions'] = 'Менеджер ресурсов: права на ресурсы изменены.';
$_lang['DM_log_sortmenu'] = 'Менеджер ресурсов: изменение индексов пунктов меню завершено.';
$_lang['DM_log_publish'] = 'Менеджер ресурсов: свойство ресурсов "Опубликовать / Отменить публикацию" изменено.';
$_lang['DM_log_hidemenu'] = 'Менеджер ресурсов: свойство ресурсов "Показывать / Не показывать в меню" изменено.';
$_lang['DM_log_search'] = 'Менеджер ресурсов: свойство ресурсов "Разрешить / Запретить поиск в содержимом" изменено.';
$_lang['DM_log_cache'] = 'Менеджер ресурсов: свойство ресурсов "Кэшировать / Не кэшировать" изменено.';
$_lang['DM_log_richtext'] = 'Менеджер ресурсов: свойство ресурсов "Использовать / Не использовать HTML-редактор" изменено.';
$_lang['DM_log_delete'] = 'Менеджер ресурсов: удаление / отмена удаления прошла успешно.';
$_lang['DM_log_dates'] = 'Менеджер ресурсов: даты ресурсов изменены.';
$_lang['DM_log_authors'] = 'Менеджер ресурсов: авторы ресурсов изменены.';

?>