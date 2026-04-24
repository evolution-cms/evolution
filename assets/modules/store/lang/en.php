<?php
/*List pack */
$_Lang['version'] = "Version";
$_Lang['suthor'] = "author";
$_Lang['date'] = "Issued";
$_Lang['downloads'] = "Uploaded";
$_Lang['more'] = "more";
$_Lang['install'] = "Install";
$_Lang['reinstall'] = "Reinstall";
$_Lang['update'] = "Update";
$_Lang['installed'] = "established";
$_Lang['installed2'] = "Installed";
$_Lang['exit'] = "Exit";
$_Lang['alert_overwrite'] = "Installing this add-on will overwrite the existing ones. Continue?";

/*Main*/
$_Lang['store_name'] = "Package Management";
$_Lang['category'] = "Categories";

/*Login form*/
$_Lang['enter_in_own_repository'] = "Login to your repository";
$_Lang['password'] = "Password";
$_Lang['enter'] = "&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; Login &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;";
$_Lang['email'] = "Email";
$_Lang['register'] = "Sign up now";
$_Lang['own_repository'] = "My Repository";
$_Lang['login_errors'] = "Incorrect Email or Password";

/* & Tpl filters */
$_Lang['s1_1_reated_on'] = "Created";
$_Lang['s1_2_name'] = "Title";
$_Lang['s1_3_position'] = "Position";
$_Lang['s1_4_published'] = "Update";
$_Lang['s2_1_up'] = "Descending";
$_Lang['s2_2_down'] = "Ascending";
$_Lang['search'] = "Search in category: ";
$_Lang['sort_by'] = "Sort by:";
$_Lang['sort_default'] = "Default";
$_Lang['sort_title_asc'] = "Name ↑";
$_Lang['sort_title_desc'] = "Name ↓";
$_Lang['sort_downloads_asc'] = "Installs ↑";
$_Lang['sort_downloads_desc'] = "Installs ↓";
$_Lang['console_category'] = "Packages";
$_Lang['console_install_title'] = "Install via console";
$_Lang['console_install_intro'] = "This package can be installed manually through the console or automatically through the scheduler.";
$_Lang['console_install_manual_label'] = "Manual install";
$_Lang['console_install_auto_label'] = "Automatic install";
$_Lang['console_install_auto_ready'] = "The scheduler is already running. You can queue this package for automatic install.";
$_Lang['console_install_auto_disabled'] = "Automatic install is not available right now. Start the scheduler first.";
$_Lang['console_install_auto_permission'] = "Automatic install requires permission to manage system tasks.";
$_Lang['console_install_scheduler_intro'] = "To enable automatic install, start the scheduler in the appropriate environment:";
$_Lang['console_install_scheduler_local'] = "Local";
$_Lang['console_install_scheduler_server'] = "Server";
$_Lang['console_install_scheduler_command_local'] = "php artisan schedule:work";
$_Lang['console_install_scheduler_command_server'] = "* * * * * cd {core_path} && php artisan schedule:run >/dev/null 2>&1";
$_Lang['console_install_auto_warning'] = "The scheduler or worker is not fully healthy right now. You can still queue the install, but monitor the progress.";
$_Lang['console_install_step_open_core'] = "1. Go to this project's core directory:";
$_Lang['console_install_step_run_artisan'] = "2. Run the artisan command:";
$_Lang['console_install_command_label'] = "Command";
$_Lang['console_install_note'] = "For now Store only shows the command here. Installation stays in the console flow so nothing is changed silently from the module.";
$_Lang['console_install_source_label'] = "Source";
$_Lang['source_label_legacy'] = "Legacy";
$_Lang['source_label_console'] = "";
$_Lang['popup_version'] = "Version";
$_Lang['popup_updated'] = "Updated";
$_Lang['popup_author'] = "Author";
$_Lang['popup_downloads'] = "Downloads";
$_Lang['popup_source'] = "Source";
$_Lang['popup_readme'] = "README";
$_Lang['popup_open_repo'] = "Open repository";
$_Lang['popup_loading'] = "Loading...";
$_Lang['popup_readme_missing'] = "README.md was not found for this package yet.";
$_Lang['popup_copy_command'] = "Copy command";
$_Lang['popup_copied'] = "Copied";
$_Lang['system_task_queue_install'] = "Queue install";
$_Lang['system_task_modal_warning'] = "Warning";
$_Lang['system_task_modal_status'] = "Status";
$_Lang['system_task_modal_step'] = "Step";
$_Lang['system_task_modal_progress'] = "Progress";
$_Lang['system_task_modal_elapsed'] = "Elapsed";
$_Lang['system_task_modal_logs'] = "Logs";
$_Lang['system_task_modal_close'] = "Close";
$_Lang['system_task_modal_queue_error'] = "Unable to queue this system task.";
$_Lang['system_task_modal_blocked_by_task'] = "Another task is currently blocking this action.";
$_Lang['system_task_modal_blocked_task'] = "Blocking task";
$_Lang['system_task_modal_open_blocking'] = "Open blocking task";
$_Lang['system_task_modal_skip_queued'] = "Skip queued task";
$_Lang['system_task_modal_uninstall_note'] = "For now this only removes the package from Composer. Package-created artifacts are not cleaned up automatically yet.";
$_Lang['system_task_modal_cancel_queued'] = "Cancel queued task";
$_Lang['system_task_modal_stale_local'] = "This task has been queued for over a minute and the scheduler does not seem to be running. Start php artisan schedule:work again or cancel this queued task.";
$_Lang['system_task_modal_stale_server'] = "This task has been queued for over a minute and cron or scheduler does not seem to be running. Resume cron schedule:run or cancel this queued task.";
$_Lang['console_uninstall_intro'] = "Removing this console package currently only removes the package from Composer. Artifacts created by the package are left in place.";
$_Lang['console_uninstall_scheduler_intro'] = "To enable automatic removal, start the scheduler in the appropriate environment:";
$_Lang['console_uninstall_confirm'] = "Remove this console package from Composer?";
$_Lang['delete'] = "Delete";
$_Lang['delete_preview_title'] = "Delete package";
$_Lang['delete_preview_intro'] = "Select what should be removed from files and manager records for this package.";
$_Lang['delete_preview_files_label'] = "Files";
$_Lang['delete_preview_components_label'] = "Components";
$_Lang['delete_preview_confirm'] = "Delete selected";
$_Lang['delete_preview_cancel'] = "Cancel";
$_Lang['delete_preview_loading'] = "Preparing delete preview...";
$_Lang['delete_preview_empty'] = "Nothing from this package was found for deletion.";
$_Lang['delete_preview_success'] = "Legacy package was deleted.";
$_Lang['delete_preview_error'] = "Unable to delete this package.";
$_Lang['legacy_delete_error'] = "Unable to build delete preview.";
$_Lang['legacy_delete_download_error'] = "Unable to download this package archive for delete preview.";
$_Lang['legacy_delete_archive_error'] = "Unable to inspect the package archive.";
$_Lang['legacy_delete_manifest_missing'] = "Delete preview has expired. Please open it again.";
$_Lang['legacy_delete_success'] = "Legacy package was deleted.";
$_Lang['dev_badge'] = "DEV";
$_Lang['dev_package_warning'] = "Dev package: no release yet, not recommended for production.";

/* Installation by file-upload */
$_Lang['install_file'] = "Install by file";
$_Lang['choose_file_msg'] = "Choose a file to upload and install";
$_Lang['install_file_btn'] = "Install";
$_Lang['install_file_success'] = "Installation successful";

/* faq */
$_Lang['faq'] = '
<li><a href="https://extras.evo.im/faq/whatfor.html" target="_blank">Why do you need repository</a></li>
<li><a href="https://extras.evo.im/faq/howtoadd.html" target="_blank">How to add a package to the repository</a></li>
<li><a href="https://extras.evo.im/faq/howtocollect.html" target="_blank">How to create a package for installation</a></li>';

?>
