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
$_lang['system_update'] = 'A new CMS version is available';
$_lang['are_you_sure_update'] = 'Are you sure you want to run a system update?';
$_lang["cms_outdated_msg"] = 'Content management system is outdated. To update contact the site developers. Current version';
$_lang['bkp_before_msg'] = 'We strongly recommend making a backup before upgrading the system, the update is performed at your own risk !!';
$_lang['updateButton_txt'] = 'Update to version';
$_lang['updateButtonCommit_txt'] = 'Update to this commit';
$_lang['table_commitdate'] = 'Commit Date';
$_lang['table_titleauthor'] = 'Title (Author)';
//Error Messages
$_lang['error_curl'] = 'You need to enable CURL function in PHP';
$_lang['error_zip'] = 'It is necessary to enable the ZIP in PHP';
$_lang['error_openssl'] = 'You need to enable OpenSSL function in PHP';
$_lang['error_overwrite'] = 'Evolution CMS files are not available for overwriting';
$_lang['error_failedtogetfeed'] = 'Failed to retrieve feed';

$_lang['artisan_update'] = 'For update run console command from <b>core</b> folder: <b>php artisan make:site update</b>';
$_lang["help_donate_msg"] = 'Buy coffee from the Evolution CMS developers at <a href="https://ko-fi.com/evolutioncms" target="_blank">ko-fi.com/evolutioncms ☕</a>. Become a fan of Evolution CMS ❤️ today!';

$_lang['updater_notice_title'] = 'A new CMS version is available';
$_lang['updater_notice_text_1'] = 'Updating is optional, but it significantly improves stability, performance, and protection against malware and DDoS attacks.';
$_lang['updater_notice_text_2'] = 'You can update the system manually. The process requires technical skills, so it is better to delegate it to your system administrator.';
$_lang['updater_notice_text_3'] = 'Or contact your developers to perform the update.';
$_lang['updater_versions_line'] = 'Current: %s -> Available: %s';

$_lang['updater_severity_critical'] = 'Critical';
$_lang['updater_severity_warning'] = 'Warning';
$_lang['updater_severity_info'] = 'Info';

$_lang['updater_action_release'] = 'Changelog';
$_lang['updater_action_release_all'] = 'All releases';
$_lang['updater_action_support'] = 'Contact developers';
$_lang['updater_action_hide_day'] = 'Do not show for 1 day';
$_lang['updater_action_hide_today'] = 'Do not show this message again today';
$_lang['updater_support_hint'] = 'Support:';

$_lang['updater_mail_subject'] = '[EVO] Site update request %s: %s -> %s';
$_lang['updater_mail_line_site'] = 'Site: %s';
$_lang['updater_mail_line_update'] = 'Update: %s -> %s';
$_lang['updater_mail_line_request'] = 'Please estimate timeline and cost.';

$_lang['updater_widget_title'] = 'A new CMS version is available';
$_lang['updater_badge_current'] = 'Current';
$_lang['updater_badge_available'] = 'Available';
$_lang['updater_current_full'] = 'Full version: %s';
$_lang['updater_action_support_with_email'] = 'Contact developers: %s';
$_lang['updater_cli_summary'] = 'Manual update (CLI)';
$_lang['updater_cli_intro'] = 'If you are updating manually, run this command in terminal:';
$_lang['updater_cli_command'] = 'php artisan make:site update';
$_lang['updater_notice_backup_warning'] = 'Do not forget to create a backup before updating.';
