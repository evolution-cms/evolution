<?php
/**
 * Ditto Snippet - language strings for use in the snippet
 * Filename:       assets/snippets/ditto/lang/russian-UTF8.inc.php
 * Language:       Russian
 * Encoding:       UTF8
 * Translated by:  Russian MODX community, Jaroslav Sidorkin, based on translation by modx.ru
 * Date:           9 May 2010
 * Version:        2.1.0
*/
setlocale (LC_ALL, 'ru_RU.UTF-8');
$_lang['language'] = "russian-UTF8";

$_lang['abbr_lang'] = "ru";

$_lang['file_does_not_exist'] = "не существует. Пожалуйста, проверьте файл.";

$_lang['extender_does_not_exist'] = "- данное расширение отсутствует. Пожалуйста, проверьте его.";

$_lang['default_template'] = <<<TPL

    <div class="ditto_item" id="ditto_item_[+id+]">
        <h3 class="ditto_pageTitle"><a href="[~[+id+]~]">[+pagetitle+]</a></h3>
        <div class="ditto_documentInfo">Автор: <strong>[+author+]</strong> от [+date+]</div>
        <div class="ditto_introText">[+introtext+]</div>
    </div>

TPL;
$_lang['missing_placeholders_tpl'] = 'В одном из шаблонов Ditto (чанков) недостает тегов, проверьте следующий шаблон: <br /><br /><hr /><br /><br />';

$_lang["bad_tpl"] = "<p>&[+tpl+] или не содержит каких-либо плейсхолдеров, или является неверным названием чанка, блоком кода или именем файла. Пожалуйста, проверьте его.</p>";

$_lang['no_documents'] = '<p>Записей не найдено.</p>';

$_lang['resource_array_error'] = 'Ошибка массива ресурсов';
 
$_lang['prev'] = "&lt; назад";

$_lang['next'] = "далее &gt;";

$_lang['button_splitter'] = "|";

$_lang['default_copyright'] = "[(site_name)] 2009";

$_lang['invalid_class'] = "Неверный класс Ditto. Пожалуйста, проверьте его.";

$_lang['none'] = "Нет";

$_lang['edit'] = "Редактировать";

$_lang['dateFormat'] = "%d.%b.%y %H:%M";

// Debug Tab Names

$_lang['info'] = "Информация";

$_lang['modx'] = "MODx";

$_lang['fields'] = "Поля";

$_lang['templates'] = "Шаблоны";

$_lang['filters'] = "Фильтры";

$_lang['prefetch_data'] = "Предварительные данные";

$_lang['retrieved_data'] = "Полученные данные";

// Debug Text

$_lang['placeholders'] = "Плейсхолдеры";

$_lang['params'] = "Параметры";

$_lang['basic_info'] = "Основная информация";

$_lang['document_info'] = "Информация о ресурсе";

$_lang['debug'] = "Отладка";

$_lang['version'] = "Версия";

$_lang['summarize'] = "Число выводимых записей (summarize):";

$_lang['total'] = "Всего в базе данных:";

$_lang['sortBy'] = "Сортировать по (sortBy):";

$_lang['sortDir'] = "Порядок сортировки (sortDir):";

$_lang['start'] = "Начать с";
	 
$_lang['stop'] = "Закончить на";

$_lang['ditto_IDs'] = "ID";

$_lang['ditto_IDs_selected'] = "Выбранные ID";

$_lang['ditto_IDs_all'] = "Все ID";

$_lang['open_dbg_console'] = "Открыть консоль отладки";

$_lang['save_dbg_console'] = "Скачать отчет отладки";

?>