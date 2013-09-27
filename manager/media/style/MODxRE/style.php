<?php
/**
 * Filename:       media/style/$modx->config['manager_theme']/style.php
 * Function:       Manager style variables for images and icons.
 * Encoding:       UTF-8
 * Credit:         icons by Mark James of FamFamFam http://www.famfamfam.com/lab/icons/
 * Date:           18-Mar-2010
 * Version:        1.1
 * MODx version:   1.0.3
*/
$style_path = 'media/style/' . $modx->config['manager_theme'] . '/images/';

// Tree Menu Toolbar
$_style['add_doc_tree']             = '<img src="'.$style_path.'icons/folder_page_add.png" style="width:16px;height:16px" />';
$_style['add_weblink_tree']         = '<img src="'.$style_path.'icons/link_add.png" style="width:16px;height:16px" />';
$_style['collapse_tree']            = '<img src="'.$style_path.'icons/arrow_up.png" style="width:16px;height:16px" />';
$_style['empty_recycle_bin']        = '<img src="'.$style_path.'icons/trash_full.png" style="width:16px;height:16px" />';
$_style['empty_recycle_bin_empty']  = '<img src="'.$style_path.'icons/trash.png" style="width:16px;height:16px" />';
$_style['expand_tree']              = '<img src="'.$style_path.'icons/arrow_down.png" style="width:16px;height:16px" />';
$_style['hide_tree']                = '<img src="'.$style_path.'icons/application_side_contract.png" style="width:16px;height:16px" />';
$_style['refresh_tree']             = '<img src="'.$style_path.'icons/refresh.png" style="width:16px;height:16px" />';
$_style['show_tree']                = $style_path.'icons/application_side_expand.png';
$_style['sort_tree']                = '<img src="'.$style_path.'icons/sort.png" style="width:16px;height:16px" />';


// Tree Icons
$_style['tree_deletedpage']         = $style_path.'tree/deletedpage.gif';
$_style['tree_folder']              = $style_path.'tree/application_double.png'; /* folder.png */
$_style['tree_folderopen']          = $style_path.'tree/application_double.png'; /* folder-open.png */
$_style['tree_folder_secure']       = $style_path.'tree/application_double_key.png';
$_style['tree_folderopen_secure']   = $style_path.'tree/application_double_key.png';
$_style['tree_globe']               = $style_path.'tree/globe.gif';
$_style['tree_linkgo']              = $style_path.'tree/link_go.png';
$_style['tree_minusnode']           = $style_path.'tree/minusnode.gif';
$_style['tree_page']                = $style_path.'tree/application.png';
$_style['tree_page_home']           = $style_path.'tree/application_home.gif';
$_style['tree_page_404']            = $style_path.'tree/application_404.gif';
$_style['tree_page_hourglass']      = $style_path.'tree/application_hourglass.gif';
$_style['tree_page_info']           = $style_path.'tree/application_info.gif';
$_style['tree_page_blank']          = $style_path.'tree/application.png';
$_style['tree_page_css']            = $style_path.'tree/application_css.png';
$_style['tree_page_html']           = $style_path.'tree/application_html.png';
$_style['tree_page_xml']            = $style_path.'tree/application_xml.png';
$_style['tree_page_js']             = $style_path.'tree/application_js.png';
$_style['tree_page_rss']            = $style_path.'tree/application_rss.png';
$_style['tree_page_pdf']            = $style_path.'tree/application_pdf.png';
$_style['tree_page_word']           = $style_path.'tree/application_word.png';
$_style['tree_page_excel']          = $style_path.'tree/application_excel.png';
$_style['tree_plusnode']            = $style_path.'tree/plusnode.gif';
$_style['tree_showtree']            = '<img src="'.$style_path.'tree/sitemap.png" width="16" height="16" align="absmiddle" />';
$_style['tree_weblink']             = $style_path.'tree/link_go.png';

$_style['tree_page_secure']         = $style_path.'tree/application_key.png';
$_style['tree_page_blank_secure']   = $style_path.'tree/application_html_secure.png';
$_style['tree_page_css_secure']     = $style_path.'tree/application_css_secure.png';
$_style['tree_page_html_secure']    = $style_path.'tree/application_html_secure.png';
$_style['tree_page_xml_secure']     = $style_path.'tree/application_xml_secure.png';
$_style['tree_page_js_secure']      = $style_path.'tree/application_js_secure.png';
$_style['tree_page_rss_secure']     = $style_path.'tree/application_rss_secure.png';
$_style['tree_page_pdf_secure']     = $style_path.'tree/application_pdf_secure.png';
$_style['tree_page_word_secure']    = $style_path.'tree/application_word_secure.png';
$_style['tree_page_excel_secure']   = $style_path.'tree/application_excel_secure.png';


// Icons
$_style['icons_add']                = $style_path.'icons/save.png';
$_style['icons_cal']                = $style_path.'icons/cal.gif';
$_style['icons_cal_nodate']         = $style_path.'icons/cal_nodate.gif';
$_style['icons_cancel']             = $style_path.'icons/stop.png';
$_style['icons_close']              = $style_path.'icons/stop.png';
$_style['icons_delete']             = $style_path.'icons/delete.png';
$_style['icons_delete_document']    = $style_path.'icons/delete.png';
$_style['icons_resource_overview']  = $style_path.'icons/information.png';
$_style['icons_resource_duplicate'] = $style_path.'icons/page_white_copy.png';
$_style['icons_edit_document']      = $style_path.'icons/save.png';
$_style['icons_email']              = $style_path.'icons/email.png';
$_style['icons_folder']             = $style_path.'icons/folder.gif';
$_style['icons_home']               = $style_path.'icons/home.gif';
$_style['icons_information']        = $style_path.'icons/information.png';
$_style['icons_loading_doc_tree']   = $style_path.'icons/information.png'; // top bar
$_style['icons_mail']               = $style_path.'icons/email.png'; // top bar
$_style['icons_message_forward']    = $style_path.'icons/forward.gif';
$_style['icons_message_reply']      = $style_path.'icons/reply.gif';
$_style['icons_modules']            = $style_path.'icons/modules.gif';
$_style['icons_move_document']      = $style_path.'icons/page_white_go.png';
$_style['icons_new_document']       = $style_path.'icons/page_white_add.png';
$_style['icons_new_weblink']        = $style_path.'icons/world_link.png';
$_style['icons_preview_resource']   = $style_path.'icons/page_white_magnify.png';
$_style['icons_publish_document']   = $style_path.'icons/clock_play.png';
$_style['icons_refresh']            = $style_path.'icons/refresh.png'; 
$_style['icons_save']               = $style_path.'icons/save.png';
$_style['icons_set_parent']         = $style_path.'icons/layout_go.png';
$_style['icons_table']              = $style_path.'icons/table.gif'; 
$_style['icons_undelete_resource']  = $style_path.'icons/b092.gif';
$_style['icons_unpublish_resource'] = $style_path.'icons/clock_stop.png';
$_style['icons_user']               = $style_path.'icons/user.gif';
$_style['icons_weblink']            = $style_path.'icons/world_link.png';
$_style['icons_working']            = $style_path.'icons/exclamation.png'; // top bar

// Tabs
$_style['icons_tab_preview']        = $style_path.'icons/preview.png';

// Indicators
$_style['icons_tooltip']            = $style_path.'icons/b02.gif';
$_style['icons_tooltip_over']       = $style_path.'icons/b02_trans.gif';

// Large Icons
$_style['icons_backup_large']       = $style_path.'icons/backup.gif';
$_style['icons_mail_large']         = $style_path.'icons/mail_generic.gif';
$_style['icons_modules_large']      = $style_path.'icons/modules.gif';
$_style['icons_resources_large']    = $style_path.'icons/resources.gif';
$_style['icons_security_large']     = $style_path.'icons/security.gif';
$_style['icons_webusers_large']     = $style_path.'icons/web_users.gif';

// Miscellaneous
$_style['ajax_loader']              = '<p>'.$_lang['loading_page'].'</p><p><img src="'.$style_path.'misc/ajax-loader.gif" alt="Please wait" /></p>';
$_style['tx']                       = $style_path.'misc/_tx_.gif';
$_style['icons_right_arrow']        = $style_path.'icons/circlerightarrow.gif';
?>