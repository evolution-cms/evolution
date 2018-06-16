<?php
$modx->config['enable_filter'] = 1;

$modx->addSnippet('hasPermission','return $modx->hasPermission($key);');

if($modx->hasPermission('new_template') || $modx->hasPermission('edit_template') || $modx->hasPermission('new_snippet') || $modx->hasPermission('edit_snippet') || $modx->hasPermission('new_plugin') || $modx->hasPermission('edit_plugin') || $modx->hasPermission('manage_metatags'))
    $hasAnyPermission = 1;
else $hasAnyPermission = 0;
$modx->addSnippet('hasAnyPermission','global $hasAnyPermission; return $hasAnyPermission;');
$modx->addSnippet('getLoginUserName','return $modx->getLoginUserName();');
$code = 'global $_lang;return $_SESSION["nrtotalmessages"] ? sprintf($_lang["welcome_messages"], $_SESSION["nrtotalmessages"], \'<span style="color:red;">\' . $_SESSION["nrnewmessages"] . "</span>") : $_lang["messages_no_messages"];';
$modx->addSnippet('getMessageCount',$code);

// Large Icons
$_style['icons_backup_large']       = 'fa fa-database fa-fw fa-2x';
$_style['icons_mail_large']         = 'fa fa-envelope fa-fw fa-2x';
$_style['icons_modules_large']      = 'fa fa-cubes fa-fw fa-2x';
$_style['icons_resources_large']    = 'fa fa-th fa-fw fa-2x';
$_style['icons_security_large']     = 'fa fa-user fa-fw fa-2x';
$_style['icons_webusers_large']     = 'fa fa-users fa-fw fa-2x';
$_style['icons_help_large']         = 'fa fa-question-circle fa-fw fa-2x';
$_style['icons_resource_large']     = 'fa fa-file fa-fw fa-2x';
$_style['icons_weblink_large']      = 'fa fa-link fa-fw fa-2x';
$_style['icons_images_large']       = 'fa fa-camera fa-fw fa-2x';
$_style['icons_files_large']        = 'fa fa-files-o fa-fw fa-2x';
$_style['icons_password_large']     = 'fa fa-lock fa-fw fa-2x';
$_style['icons_logout_large']       = 'fa fa-sign-out fa-fw fa-2x';
