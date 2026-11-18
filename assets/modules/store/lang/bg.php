<?php
/*
 * Keys missing here are shown in English: StoreContextService::loadLanguage()
 * loads en.php first and this file on top of it.
 */

/* Installation by file-upload */
$_Lang['install_file_artifact_queued'] = 'Composer пакетът %1$s %2$s е добавен в опашката за инсталиране като системна задача #%3$s. Планировчикът ще го инсталира от каченото архивно копие.';
$_Lang['install_file_artifact_no_version'] = 'Архивът съдържа Composer пакета %1$s, но без версия. Добавете "version" в неговия composer.json или посочете версията в името на файла, например %2$s-1.0.0.zip.';
$_Lang['install_file_artifact_failed'] = 'Composer пакетът %1$s не можа да бъде добавен в опашката: %2$s';
$_Lang['install_file_artifact_invalid'] = 'Архивът прилича на Composer пакет, но %1$s не е валиден JSON с име на пакет "name". Нищо не е инсталирано.';

$_Lang['check_updates'] = 'Проверка за обновления';
$_Lang['check_updates_title'] = 'Потърсете нови версии на Evolution CMS и разширенията сега, без да чакате ежедневната проверка';
$_Lang['check_updates_done'] = 'Информацията за обновления е опреснена: списъкът с пакети показва най-новите версии. Отворете началната страница, за да видите версията на системата и остарелите разширения:';
$_Lang['check_updates_dashboard'] = 'Начало';
$_Lang['check_updates_partial'] = 'Информацията за обновления е опреснена, но каталогът с пакети не можа да бъде изтеглен или запазен: списъкът по-долу може да е остарял. Отворете началната страница, за да видите версията на системата и остарелите разширения:';
$_Lang['check_updates_failed'] = 'Неуспешна проверка за обновления. Опитайте отново по-късно.';
