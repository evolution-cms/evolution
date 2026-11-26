<?php
/*
 * Keys missing here are shown in English: StoreContextService::loadLanguage()
 * loads en.php first and this file on top of it.
 */

/* Installation by file-upload */
$_Lang['install_file_artifact_queued'] = 'Composer 包 %1$s %2$s 已作为系统任务 #%3$s 加入安装队列。调度器将从上传的归档中安装它。';
$_Lang['install_file_artifact_no_version'] = '归档中包含 Composer 包 %1$s，但没有版本。请在其 composer.json 中添加 "version"，或在文件名中写明版本，例如 %2$s-1.0.0.zip。';
$_Lang['install_file_artifact_failed'] = '无法将 Composer 包 %1$s 加入队列：%2$s';
$_Lang['install_file_artifact_invalid'] = '该归档看起来像 Composer 包，但 %1$s 不是包含包名 "name" 的有效 JSON。未安装任何内容。';

$_Lang['more'] = '更多';
$_Lang['check_updates'] = '检查更新';
$_Lang['check_updates_title'] = '立即查找 Evolution CMS 和扩展的新版本，无需等待每日检查';
$_Lang['check_updates_done'] = '更新信息已刷新：软件包列表显示最新版本。打开主页可查看系统版本和过时的扩展:';
$_Lang['check_updates_dashboard'] = '主页';
$_Lang['check_updates_partial'] = '更新信息已刷新，但无法获取或保存软件包目录：下面的列表可能已过时。打开主页可查看系统版本和过时的扩展:';
$_Lang['check_updates_failed'] = '无法检查更新。请稍后再试。';
