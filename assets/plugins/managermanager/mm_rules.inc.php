<?php
//group comment_top
//Роль «Редакторы»
$editorsRole = 2;
//Роль «Оптимизаторы»
$seoRole = 4;
// Для всех ролей
mm_renameField("description","Описание (meta «description»)");
mm_renameField("longtitle","Расширенный заголовок (meta «title»)");
mm_renameField("menuindex","Позиция");
mm_renameField("show_in_menu","Отображать");
mm_hideFields("link_attributes,content_dispo");
mm_requireFields("pagetitle");
mm_moveFieldsToTab("published", "general");
mm_widget_showimagetvs(); // Always give a preview of Image TVs

//group comment_bottom
//Системный шаблон
$systemTpl = 4;
mm_default("show_in_menu", 0, "", $systemTpl);
mm_default("is_richtext", 0, "", $systemTpl);
mm_default("log", 0, "", $systemTpl);
mm_default("searchable", 0, "", $systemTpl);
mm_hideFields("longtitle,description,introtext,menutitle,show_in_menu,isfolder,is_richtext,log,searchable", "", $systemTpl);
mm_createTab("Backup", "backup", "1", $systemTpl);
mm_moveFieldsToTab("backupField", "backup", "1", $systemTpl);
mm_hideFields("content_type", "", "!$systemTpl");
// Для всех, кроме администраторов
// mm_hideFields("loginName ", "!1");
mm_hideFields("template,parent,is_folder,is_richtext,log,searchable,cacheable,clear_cache,inheritTpl", "!1");
mm_hideTabs("settings", "!1");
// Для всех, кроме редакторов
mm_createTab("SEO", "seoTab", "!$editorsRole", "!$systemTpl", "<p>Здесь вы можете отредактировать всё, что касается поисковой оптимизации.</p>");
mm_moveFieldsToTab("longtitle,description,ddkeywords,sitemap_changefreq,sitemap_priority", "seoTab", "!$editorsRole");
//Для редакторов
mm_hideFields("longtitle,description", "$editorsRole");

?>