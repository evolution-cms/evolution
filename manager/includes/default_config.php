<?php

$c  = &$settings;

$c['site_name']                = 'My MODX Site';
$c['site_start']               = 1;
$c['error_page']               = 1;
$c['unauthorized_page']        = 1;
$c['site_unavailable_page']    = '';
$c['top_howmany']              = 10;
$c['custom_contenttype']       = 'text/css,text/html,text/javascript,text/plain,text/xml';
$c['docid_incrmnt_method']     = 0;
$c['valid_hostnames']          = '';
$c['enable_filter']            = 0;
$c['minifyphp_incache']        = 0;
$c['rss_url_news']             = $_lang["rss_url_news_default"];
$c['rss_url_security']         = $_lang["rss_url_security_default"];
$c['friendly_urls']            = 0;
$c['friendly_url_prefix']      = 'p';
$c['friendly_url_suffix']      = '.html';
$c['make_folders']             = '0';
$c['seostrict']                = '0';
$c['aliaslistingfolder']       = '0';
$c['check_files_onlogin']      = "index.php\n.htaccess\nmanager/index.php\nmanager/includes/config.inc.php";
$c['use_captcha']              = 0;
$c['pwd_hash_algo']            = 0;
$c['rb_base_url']              = 'assets/';
$c['resource_tree_node_name']  = 'pagetitle';
$c['udperms_allowroot']        = 0;
$c['failed_login_attempts']    = 3;
$c['blocked_minutes']          = 10;
$c['error_reporting']          = '1';
$c['send_errormail']           = '0';
$c['enable_bindings']          = 0;
$c['captcha_words']            = $_lang["captcha_words_default"];
$c['emailsender']              = 'you@example.com';
$c['smtp_host']                = 'smtp.example.com';
$c['smtp_port']                = 25;
$c['smtp_username']            = $c['emailsender'];
$c['emailsubject']             = $_lang["emailsubject_default"];
$c['signupemail_message']      = $_lang["system_email_signup"];
$c['websignupemail_message']   = $_lang["system_email_websignup"];
$c['webpwdreminder_message']   = $_lang["system_email_webreminder"];
$c['warning_visibility']       = 1;
$c['tree_page_click']          = 3;
$c['use_breadcrumbs']          = 0;
$c['remember_last_tab']        = 0;
$c['tree_show_protected']      = 0;
$c['show_meta']                = 0;
$c['datepicker_offset']        = -10;
$c['number_of_logs']           = 100;
$c['mail_check_timeperiod']    = 60;
$c['number_of_messages']       = 40;
$c['number_of_results']        = 30;
$c['use_editor']               = 1;
$c['editor_css_path']          = '';
$c['filemanager_path']         = '[(base_path)]';
$c['upload_files']             = 'txt,php,html,htm,xml,js,css,cache,zip,gz,rar,z,tgz,tar,htaccess,pdf';
$c['upload_images']            = 'jpg,gif,png,ico,bmp,psd';
$c['upload_media']             = 'mp3,wav,au,wmv,avi,mpg,mpeg';
$c['upload_flash']             = 'swf,fla';
$c['upload_maxsize']           = '5000000';
$c['new_file_permissions']     = '0644';
$c['new_folder_permissions']   = '0755';
$c['use_browser']              = 1;
$c['which_browser']            = 'mcpuk';
$c['rb_webuser']               = 0;
$c['rb_base_dir']              = '[(base_path)]assets/';
$c['clean_uploaded_filename']  = 0;
$c['strip_image_paths']        = 0;
$c['maxImageWidth']            = 1600;
$c['maxImageHeight']           = 1200;
$c['thumbWidth']               = 150;
$c['thumbHeight']              = 150;
$c['thumbsDir']                = '.thumbs';
$c['jpegQuality']              = 90;
$c['denyZipDownload']          = 1;
$c['denyExtensionRename']      = 1;
$c['showHiddenFiles']          = 1;
$c['session_timeout']          = 15;
$c['site_unavailable_message'] = $_lang['siteunavailable_message_default'];
