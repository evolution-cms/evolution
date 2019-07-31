<?php
/**
 * Filename:       media/style/$modx->config['manager_theme']/style.php
 * Function:       Manager style variables for images and icons.
 * Encoding:       UTF-8
 * Credit:         icons by Mark James of FamFamFam http://www.famfamfam.com/lab/icons/
 * Date:           18-Mar-2010
 * Version:        1.1
 * MODX version:   1.0.3
 */
$style_path = 'media/style/' . $modx->config['manager_theme'] . '/images/';
$modx->config['mgr_date_picker_path'] = 'media/calendar/datepicker.inc.php';
if(!$modx->config['lang_code']) {
	global $modx_lang_attribute;
	$modx->config['lang_code'] = !$modx_lang_attribute ? 'en' : $modx_lang_attribute;
}

if($_GET['a'] == 2) {
	include_once('welcome.php');
}

// Favicon
$_style['favicon']                  = (file_exists(MODX_BASE_PATH . 'favicon.ico') ? MODX_SITE_URL . 'favicon.ico' : 'media/style/' . $modx->config['manager_theme'] . '/images/favicon.ico');

//Main Menu
$_style['menu_search']              = '<i class="fa fa-search"></i>';
$_style['menu_preview_site']        = '<i class="fa fa-desktop"></i>';
$_style['menu_new_resource']        = '<i class="fa fa-plus"></i>';
$_style['menu_system']              = '<i class="fa fa-cogs"></i>';
$_style['menu_user']                = '<i class="fa fa-user-circle"></i>';
// full screen
$_style['menu_expand']              = 'fa-expand';
$_style['menu_compress']            = 'fa-compress';
//help toggle
$_style['icons_help']               = '<i class="fa fa-question-circle help"></i>';
//pages
$_style['page_settings']            = '<i class="fa fa-sliders fw"></i>';
$_style['page_shedule']             = '<i class="fa fa-calendar"></i>';
$_style['page_eventlog']            = '<i class="fa fa-exclamation-triangle"></i>';
$_style['page_manager_logs']        = '<i class="fa fa-user-secret"></i>';
$_style['page_sys_info']            = '<i class="fa fa-info-circle"></i>';
$_style['page_help']                = '<i class="fa fa-question-circle"></i>';
$_style['page_change_password']     = '<i class="fa fa-lock"></i>';
$_style['page_logout']              = '<i class="fa fa-sign-out"></i>';

// Tree Menu Toolbar
$_style['add_doc_tree']             = '<i class="fa fa-file"></i>';
$_style['add_weblink_tree']         = '<i class="fa fa-link"></i>';
$_style['collapse_tree']            = '<i class="fa fa-arrow-circle-up"></i>';
$_style['empty_recycle_bin']        = '<i class="fa fa-trash"></i>';
$_style['empty_recycle_bin_empty']  = '<i class="fa fa-trash-o"></i>';
$_style['expand_tree']              = '<i class="fa fa-arrow-circle-down"></i>';
$_style['hide_tree']                = '<i class="fa fa-caret-square-o-left"></i>';
$_style['refresh_tree']             = '<i class="fa fa-refresh"></i>';
$_style['show_tree']                = $style_path.'tree/expand.png';
$_style['sort_tree']                = '<i class="fa fa-sort"></i>';
$_style['sort_menuindex']           = '<i class="fa fa-sort-numeric-asc"></i>';
$_style['element_management']       = '<i class="fa fa-th"></i>';
$_style['images_management']        = '<i class="fa fa-camera"></i>';
$_style['files_management']         = '<i class="fa fa-files-o"></i>';
$_style['email']                    = '<i class="fa fa-envelope"></i>';

//Tree Contextual Menu Popup
$_style['ctx_new_document']         = 'fa fa-file-o';
$_style['ctx_edit_document']        = 'fa fa-pencil-square-o';
$_style['ctx_move_document']        = 'fa fa-arrows';
$_style['ctx_resource_duplicate']   = 'fa fa-clone';
$_style['ctx_sort_menuindex']       = 'fa fa-sort-numeric-asc';
$_style['ctx_publish_document']     = 'fa fa-check';
$_style['ctx_unpublish_resource']   = 'fa fa-close';
$_style['ctx_delete']               = 'fa fa-trash';
$_style['ctx_undelete_resource']    = 'fa fa-undo';
$_style['ctx_weblink']              = 'fa fa-link';
$_style['ctx_resource_overview']    = 'fa fa-info';
$_style['ctx_preview_resource']     = 'fa fa-eye';

// Tree Icons
$_style['tree_deletedpage']         = "<i class='fa fa-ban'></i>";
$_style['tree_folder']              = $style_path.'tree/folder-close-alt.png'; /* folder.png */
$_style['tree_folderopen']          = $style_path.'tree/folder-open-alt.png'; /* folder-open.png */
$_style['tree_globe']               = $style_path.'tree/globe.png';
$_style['tree_folder_new']          = "<i class='fa fa-folder'></i>"; /* folder.png */
$_style['tree_folderopen_new']      = "<i class='fa fa-folder-open'></i>"; /* folder-open.png */
$_style['tree_folder_secure']       = "<i class='fa fa-folder'><i class='fa fa-lock'></i></i>";
$_style['tree_folderopen_secure']   = "<i class='fa fa-folder-open'><i class='fa fa-lock'></i></i>";
$_style['tree_linkgo']              = "<i class='fa fa-link'></i>";
$_style['tree_page']                = "<i class='fa fa-file-o'></i>";
$_style['tree_page_home']           = "<i class='fa fa-home'></i>";
$_style['tree_page_404']            = "<i class='fa fa-exclamation-triangle'></i>";
$_style['tree_page_hourglass']      = "<i class='fa fa-clock-o'></i>";
$_style['tree_page_info']           = "<i class='fa fa-info'></i>";
$_style['tree_page_blank']          = "<i class='fa fa-file-o'></i>";
$_style['tree_page_css']            = "<i class='fa fa-file-code-o'></i>";
$_style['tree_page_html']           = "<i class='fa fa-file-o'></i>";
$_style['tree_page_xml']            = "<i class='fa fa-file-code-o'></i>";
$_style['tree_page_js']             = "<i class='fa fa-file-code-o'></i>";
$_style['tree_page_rss']            = "<i class='fa fa-file-code-o'></i>";
$_style['tree_page_pdf']            = "<i class='fa fa-file-pdf-o'></i>";
$_style['tree_page_word']           = "<i class='fa fa-file-word-o'></i>";
$_style['tree_page_excel']          = "<i class='fa fa-file-excel-o'></i>";

$_style['tree_minusnode']           = "<i class='fa fa-angle-down'></i>";//$style_path.'tree/angle-down.png';
$_style['tree_plusnode']            = "<i class='fa fa-angle-right'></i>";//$style_path.'tree/angle-right.png';
$_style['tree_weblink']             = $style_path.'tree/link.png';
$_style['tree_preview_resource']    = "<i class='fa fa-eye'></i>";//$style_path.'icons/eye.png';

$_style['tree_showtree']            = '<i class="fa fa-sitemap"></i>';
$_style['tree_working']             = '<i class="fa fa-warning"></i>';
$_style['tree_info']                = '<i class="fa fa-info-circle"></i>';

$_style['tree_page_secure']         = "<i class='fa fa-file-o'><i class='fa fa-lock'></i></i>";
$_style['tree_page_blank_secure']   = "<i class='fa fa-file-o'><i class='fa fa-lock'></i></i>";
$_style['tree_page_css_secure']     = "<i class='fa fa-file-code-o'><i class='fa fa-lock'></i></i>";
$_style['tree_page_html_secure']    = "<i class='fa fa-file-o'><i class='fa fa-lock'></i></i>";
$_style['tree_page_xml_secure']     = "<i class='fa fa-file-code-o'><i class='fa fa-lock'></i></i>";
$_style['tree_page_js_secure']      = "<i class='fa fa-file-code-o'><i class='fa fa-lock'></i></i>";
$_style['tree_page_rss_secure']     = "<i class='fa fa-file-code-o'></i>";
$_style['tree_page_pdf_secure']     = "<i class='fa fa-file-pdf-o'><i class='fa fa-lock'></i></i>";
$_style['tree_page_word_secure']    = "<i class='fa fa-file-word-o'><i class='fa fa-lock'></i></i>";
$_style['tree_page_excel_secure']   = "<i class='fa fa-file-excel-o'><i class='fa fa-lock'></i></i>";

//search
$_style['icons_external_link']      = '<i class="fa fa-external-link"></i>';

//View Resource data
$_style['icons_new_document']       = 'fa fa-file-o';
$_style['icons_new_weblink']        = 'fa fa-link';
$_style['icons_move_document']      = 'fa fa-arrows';
$_style['icons_publish_document']   = 'fa fa-check';
$_style['icons_unpublish_resource'] = 'fa fa-close';
$_style['icons_delete_resource']    = 'fa fa-trash';
$_style['icons_undelete_resource']  = 'fa fa-undo';
$_style['icons_resource_overview']  = 'fa fa-info';
$_style['icons_edit_resource']      = 'fa fa-pencil-square-o';
//context menu
$_style['icons_resource_duplicate'] = $style_path.'icons/clone.png';
$_style['icons_edit_document']      = $style_path.'icons/save.png';
$_style['icons_delete_document']    = $style_path.'icons/trash.png';
//locks
$_style['icons_preview_resource']   = $style_path.'icons/eye.png';//$style_path.'icons/eye.png';
$_style['icons_secured']            = "<i class='fa fa-lock'></i>";//$style_path.'icons/lock.png';

//file manager icons
$_style['files_save']               = 'fa fa-floppy-o';
$_style['files_folder']             = 'fa fa-folder-o';
$_style['files_deleted_folder']     = 'fa fa-folder-o';
$_style['files_folder-open']        = 'fa fa-folder-open-o';
$_style['files_page_php']           = 'fa fa-file-o';
$_style['files_page_html']          = 'fa fa-file-o';
$_style['files_cancel']             = 'fa fa-times-circle';
$_style['files_top']                = 'fa fa-folder-open-o';
$_style['files_add']                = 'fa fa-plus-circle';
$_style['files_upload']             = 'fa fa-upload';
$_style['files_delete']             = 'fa fa-trash';
$_style['files_duplicate']          = 'fa fa-clone';
$_style['files_rename']             = 'fa fa-i-cursor';
$_style['files_view']               = 'fa fa-eye';
$_style['files_download']           = 'fa fa-download';
$_style['files_unzip']              = 'fa fa-file-archive-o';
$_style['files_edit']               = 'fa fa-pencil-square-o';

//Action buttons
$_style['actions_save']             = 'fa fa-floppy-o';
$_style['actions_duplicate']        = 'fa fa-clone';
$_style['actions_delete']           = 'fa fa-trash';
$_style['actions_cancel']           = 'fa fa-times-circle';
$_style['actions_close']            = 'fa fa-times-circle';
$_style['actions_add']              = 'fa fa-plus-circle';
$_style['actions_preview']          = 'fa fa-eye';
$_style['actions_run']              = 'fa fa-play';
$_style['actions_stop']             = 'fa fa-stop';
$_style['actions_new']              = 'fa fa-plus-circle';
$_style['actions_help']             = 'fa fa-question-circle';
$_style['actions_sort']             = 'fa fa-sort';
$_style['actions_options']          = 'fa fa-check-square';
$_style['actions_categories']       = 'fa fa-folder-open';
$_style['actions_search']           = 'fa fa-search';
$_style['actions_file']             = 'fa fa-file-o';
$_style['actions_folder']           = 'fa fa-folder-o';
$_style['actions_folder_open']      = 'fa fa-folder-open-o';
$_style['actions_calendar']         = 'fa fa-calendar';
$_style['actions_calendar_delete']  = 'fa fa-calendar-times-o';
$_style['actions_angle_up']         = 'fa fa-angle-up';
$_style['actions_angle_down']       = 'fa fa-angle-down';
$_style['actions_angle_left']       = 'fa fa-angle-left';
$_style['actions_angle_right']      = 'fa fa-angle-right';
$_style['actions_chain']            = 'fa fa-chain';
$_style['actions_chain_broken']     = 'fa fa-chain-broken';
$_style['actions_edit']             = 'fa fa-edit';
$_style['actions_move']             = 'fa fa-arrows';
$_style['actions_pencil']           = 'fa fa-pencil';
$_style['actions_reply']            = 'fa fa-reply';
$_style['actions_plus']             = 'fa fa-plus';
$_style['actions_refresh']          = 'fa fa-refresh';
$_style['actions_error']            = 'fa fa-times-circle';
$_style['actions_info']             = 'fa fa-info-circle';
$_style['actions_triangle']         = 'fa fa-exclamation-triangle';
$_style['actions_table']            = 'fa fa-table';

//for back compatibility

$_style['icons_save']               = $style_path.'icons/save.png';
$_style['icons_delete']             = $style_path.'icons/trash.png';
$_style['icons_deleted_folder']     = $style_path.'tree/deletedfolder.png';
$_style['icons_unzip']              = $style_path.'icons/download-alt.png';


// Indicators
$_style['icons_tooltip']            = 'fa fa-question-circle';
$_style['icons_tooltip_over']       = $style_path.'icons/question-sign.png';
$_style['icons_cal']                = $style_path.'icons/calendar.png';
$_style['icons_cal_nodate']         = $style_path.'icons/calendar.png';
$_style['icons_set_parent']         = $style_path.'icons/folder-open.png';

//modules
$_style['icons_module']            = 'fa fa-cube';
$_style['icons_modules']            = 'fa fa-cubes'; //$style_path.'icons/modules.png';
$_style['icons_run']                = $style_path.'icons/play.png';

//users and webusers
$_style['icons_user']               = 'fa fa-user-circle-o'; //$style_path.'icons/user.png';
$_style['icons_web_user']           = 'fa fa-user'; //$style_path.'icons/user.png';

//Messages
$_style['icons_message_unread']     = $style_path.'icons/email.png';
$_style['icons_message_forward']    = $style_path.'icons/forward.png';
$_style['icons_message_reply']      = $style_path.'icons/reply.png';

// Icons
$_style['icons_add']                = $style_path.'icons/add.png';
$_style['icons_cancel']             = $style_path.'icons/cancel.png';
$_style['icons_close']              = $style_path.'icons/stop.png';
$_style['icons_refresh']            = $style_path.'icons/refresh.png';
$_style['icons_table']              = $style_path.'icons/table.png';

// top bar
$_style['icons_loading_doc_tree']   = $style_path.'icons/info-sign.png';
$_style['icons_mail']               = $style_path.'icons/email.png';
$_style['icons_working']            = $style_path.'icons/exclamation.png';

//event log
$_style['icons_event1']             = $style_path.'icons/event1.png';
$_style['icons_event2']             = $style_path.'icons/event2.png';
$_style['icons_event3']             = $style_path.'icons/event3.png';


//nowhere in the manager
$_style['icons_folder']             = $style_path.'icons/folder.png';
$_style['icons_email']              = $style_path.'icons/email.png';
$_style['icons_home']               = $style_path.'icons/home.png';
$_style['icons_sort_menuindex']     = $style_path.'icons/sort_index.png';
$_style['icons_weblink']            = $style_path.'icons/world_link.png';
$_style['icons_tab_preview']        = $style_path.'icons/preview.png'; // Tabs
$_style['icons_information']        = $style_path.'icons/info-sign.png';


// Miscellaneous
$_style['ajax_loader']              = '<p>'.$_lang['loading_page'].'</p><p><i class="fa fa-spinner fa-spin"></i></p>';
$_style['tx']                       = $style_path.'misc/_tx_.gif';
$_style['icons_right_arrow']        = $style_path.'icons/arrow-right.png';
$_style['fade']                     = $style_path.'misc/fade.gif';
$_style['ed_save']                  = $style_path.'misc/ed_save.gif';

// actions buttons templates
$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';
if (!empty($modx->config['global_tabs']) && !isset($_SESSION['stay'])) {
    $_REQUEST['stay'] = 2;
}
if (isset($_REQUEST['stay'])) {
    $_SESSION['stay'] = $_REQUEST['stay'];
} else if (isset($_SESSION['stay'])) {
    $_REQUEST['stay'] = $_SESSION['stay'];
}
$stay = isset($_REQUEST['stay']) ? $_REQUEST['stay'] : '';
$addnew = 0;
$run = 0;
switch($action) {
	case '3':
	case '4':
	case '27':
	case '72':
		if($modx->hasPermission('new_document')) {
			$addnew = 1;
		}
		break;
	case '16':
	case '19':
		if($modx->hasPermission('new_template')) {
			$addnew = 1;
		}
		break;
	case '300':
	case '301':
		if($modx->hasPermission('new_snippet') && $modx->hasPermission('new_chunk') && $modx->hasPermission('new_plugin')) {
			$addnew = 1;
		}
		break;
	case '77':
	case '78':
		if($modx->hasPermission('new_chunk')) {
			$addnew = 1;
		}
		break;
	case '22':
	case '23':
		if($modx->hasPermission('new_snippet')) {
			$addnew = 1;
		}
		break;
	case '101':
	case '102':
		if($modx->hasPermission('new_plugin')) {
			$addnew = 1;
		}
		break;
	case '106':
	case '107':
	case '108':
		if($modx->hasPermission('new_module')) {
			$addnew = 1;
		}
		if($modx->hasPermission('exec_module')) {
			$run = 1;
		}
		break;
	case '88':
		if($modx->hasPermission('new_web_user')) {
			$addnew = 1;
		}
		break;
}

$disabled = ($action == '19' || $action == '300' || $action == '77' || $action == '23' || $action == '101' || $action == '4' || $action == '72' || $action == '87' || $action == '11' || $action == '107' || $action == '38') ? ' disabled' : '';

$_style['actionbuttons'] = array(
	'dynamic' => array(
		'document' => '<div id="actions">
			<div class="btn-group">
				<div class="btn-group">
					<a id="Button1" class="btn btn-success" href="javascript:;" onclick="actions.save();">
						<i class="' . $_style["actions_save"] . '"></i><span>' . $_lang['save'] . '</span>
					</a>
					<span class="btn btn-success plus dropdown-toggle"></span>
					<select id="stay" name="stay">
						' . ($addnew ? '
							<option id="stay1" value="1" ' . ($stay == '1' ? ' selected="selected"' : '') . '>' . $_lang['stay_new'] . '</option>
						' : '') . '
						<option id="stay2" value="2" ' . ($stay == '2' ? ' selected="selected"' : '') . '>' . $_lang['stay'] . '</option>
						<option id="stay3" value="" ' . ($stay == '' ? ' selected="selected"' : '') . '>' . $_lang['close'] . '</option>
					</select>
				</div>' .
					($addnew ? '
					<a id="Button6" class="btn btn-secondary' . $disabled . '" href="javascript:;" onclick="actions.duplicate();">
						<i class="' . $_style["actions_duplicate"] . '"></i><span>' . $_lang['duplicate'] . '</span>
					</a>
					' : '') . '
				<a id="Button3" class="btn btn-secondary' . $disabled . '" href="javascript:;" onclick="actions.delete();">
					<i class="' . $_style["actions_delete"] . '"></i><span>' . $_lang['delete'] . '</span>
				</a>
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["actions_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
				<a id="Button4" class="btn btn-secondary" href="javascript:;" onclick="actions.view();">
					<i class="' . $_style["actions_preview"] . '"></i><span>' . $_lang['preview'] . '</span>
				</a>
			</div>
		</div>',
		'user' => '<div id="actions">
			<div class="btn-group">
				<div class="btn-group">
					<a id="Button1" class="btn btn-success" href="javascript:;" onclick="actions.save();">
						<i class="' . $_style["actions_save"] . '"></i><span>' . $_lang['save'] . '</span>
					</a>
					<span class="btn btn-success plus dropdown-toggle"></span>
					<select id="stay" name="stay">
						' . ($addnew ? '
							<option id="stay1" value="1" ' . ($stay == '1' ? ' selected="selected"' : '') . '>' . $_lang['stay_new'] . '</option>
						' : '') . '
						<option id="stay2" value="2" ' . ($stay == '2' ? ' selected="selected"' : '') . '>' . $_lang['stay'] . '</option>
						<option id="stay3" value="" ' . ($stay == '' ? ' selected="selected"' : '') . '>' . $_lang['close'] . '</option>
					</select>
				</div>
				<a id="Button3" class="btn btn-secondary' . $disabled . '" href="javascript:;" onclick="actions.delete();">
					<i class="' . $_style["actions_delete"] . '"></i><span>' . $_lang['delete'] . '</span>
				</a>
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["actions_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
			</div>
		</div>',
		'element' => '<div id="actions">
			<div class="btn-group">
				<div class="btn-group">
					<a id="Button1" class="btn btn-success" href="javascript:;" onclick="actions.save();">
						<i class="' . $_style["actions_save"] . '"></i><span>' . $_lang['save'] . '</span>
					</a>
					<span class="btn btn-success plus dropdown-toggle"></span>
					<select id="stay" name="stay">
						' . ($addnew ? '
							<option id="stay1" value="1" ' . ($stay == '1' ? ' selected="selected"' : '') . '>' . $_lang['stay_new'] . '</option>
						' : '') . '
						<option id="stay2" value="2" ' . ($stay == '2' ? ' selected="selected"' : '') . '>' . $_lang['stay'] . '</option>
						<option id="stay3" value="" ' . ($stay == '' ? ' selected="selected"' : '') . '>' . $_lang['close'] . '</option>
					</select>
				</div>
				' . ($addnew ? '
				<a id="Button6" class="btn btn-secondary' . $disabled . '" href="javascript:;" onclick="actions.duplicate();">
					<i class="' . $_style["actions_duplicate"] . '"></i><span>' . $_lang['duplicate'] . '</span>
				</a>
				' : '') . '
				<a id="Button3" class="btn btn-secondary' . $disabled . '" href="javascript:;" onclick="actions.delete();">
					<i class="' . $_style["actions_delete"] . '"></i><span>' . $_lang['delete'] . '</span>
				</a>
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["actions_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
				' . ($run ? '
				<a id="Button4" class="btn btn-secondary' . $disabled . '" href="javascript:;" onclick="actions.run();">
					<i class="' . $_style["actions_run"] . '"></i><span>' . $_lang['run_module'] . '</span>
				</a>
				' : '') . '
			</div>
		</div>',
		'newmodule' => ($addnew ? '<div id="actions">
			<div class="btn-group">
				<a id="newModule" class="btn btn-secondary" href="javascript:;" onclick="actions.new();">
					<i class="' . $_style["actions_new"] . '"></i><span>' . $_lang['new_module'] . '</span>
				</a>
			</div>
		</div>' : ''),
		'close' => '<div id="actions">
			<div class="btn-group">
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.close();">
					<i class="' . $_style["actions_close"] . '"></i><span>' . $_lang['close'] . '</span>
				</a>
			</div>
		</div>',
		'save' => '<div id="actions">
			<div class="btn-group">
				<a id="Button1" class="btn btn-success" href="javascript:;" onclick="actions.save();">
					<i class="' . $_style["actions_save"] . '"></i><span>' . $_lang['save'] . '</span>
				</a>
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["actions_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
			</div>
		</div>',
		'savedelete' => '<div id="actions">
			<div class="btn-group">
				<a id="Button1" class="btn btn-success" href="javascript:;" onclick="actions.save();">
					<i class="' . $_style["actions_save"] . '"></i><span>' . $_lang['save'] . '</span>
				</a>
				<a id="Button3" class="btn btn-secondary' . $disabled . '" href="javascript:;" onclick="actions.delete();">
					<i class="' . $_style["actions_delete"] . '"></i><span>' . $_lang['delete'] . '</span>
				</a>
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["actions_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
			</div>
		</div>',
		'cancel' => '<div id="actions">
			<div class="btn-group">
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["actions_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
			</div>
		</div>',
		'canceldelete' => '<div id="actions">
			<div class="btn-group">
				<a id="Button3" class="btn btn-secondary' . $disabled . '" href="javascript:;" onclick="actions.delete();">
					<i class="' . $_style["actions_delete"] . '"></i><span>' . $_lang['delete'] . '</span>
				</a>
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["actions_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
			</div>
		</div>',
	),
	'static' => array(
		'document' => '<div id="actions">
			<div class="btn-group">' .
				($addnew ? '
					<a class="btn btn-secondary" href="javascript:;" onclick="actions.new();">
						<i class="' . $_style["icons_new_document"] . '"></i><span>' . $_lang['create_resource_here'] . '</span>
					</a>
					<a class="btn btn-secondary" href="javascript:;" onclick="actions.newlink();">
						<i class="' . $_style["icons_new_weblink"] . '"></i><span>' . $_lang['create_weblink_here'] . '</span>
					</a>
				' : '') . '
				<a id="Button1" class="btn btn-success" href="javascript:;" onclick="actions.edit();">
					<i class="' . $_style["actions_edit"] . '"></i><span>' . $_lang['edit'] . '</span>
				</a>
				<a id="Button2" class="btn btn-secondary" href="javascript:;" onclick="actions.move();">
					<i class="' . $_style["actions_move"] . '"></i><span>' . $_lang['move'] . '</span>
				</a>
				<a id="Button6" class="btn btn-secondary" href="javascript:;" onclick="actions.duplicate();">
					<i class="' . $_style["actions_duplicate"] . '"></i><span>' . $_lang['duplicate'] . '</span>
				</a>
				<a id="Button3" class="btn btn-secondary" href="javascript:;" onclick="actions.delete();">
					<i class="' . $_style["actions_delete"] . '"></i><span>' . $_lang['delete'] . '</span>
				</a>
				<a id="Button4" class="btn btn-secondary" href="javascript:;" onclick="actions.view();">
					<i class="' . $_style["actions_preview"] . '"></i><span>' . $_lang['preview'] . '</span>
				</a>
			</div>
		</div>',
		'cancel' => '<div id="actions">
			<div class="btn-group">
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["actions_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
			</div>
		</div>',
	)
);
