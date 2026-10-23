<?php
/**
 * Filename:       media/style/ManagerTheme::getTheme()/style.php
 * Function:       Manager style variables for images and icons.
 * Encoding:       UTF-8
 * Credit:         icons by Mark James of FamFamFam http://www.famfamfam.com/lab/icons/
 * Date:           18-Mar-2010
 * Version:        1.1
 * MODX version:   1.0.3
 */
// Use the instance while ManagerTheme is being built, avoiding facade recursion.
// Legacy modules such as sLang also include this file from their own controllers.
$style_theme = isset($this) && $this instanceof \EvolutionCMS\ManagerTheme
    ? $this->getTheme()
    : evo()->getConfig('manager_theme', 'default');
$style_path = 'media/style/' . $style_theme . '/images/';
evo()->config['mgr_date_picker_path'] = 'media/calendar/datepicker.inc.php';

if (!empty($_GET['a']) && $_GET['a'] == 2) {
    evo()->config['enable_filter'] = 1;

    evo()->addSnippet('hasPermission', 'return evo()->hasPermission($key);');

    if (evo()->hasPermission('new_template') || evo()->hasPermission('edit_template') || evo()->hasPermission('new_snippet') || evo()->hasPermission('edit_snippet') || evo()->hasPermission('new_plugin') || evo()->hasPermission('edit_plugin') || evo()->hasPermission('manage_metatags')) {
        $hasAnyPermission = 1;
    } else {
        $hasAnyPermission = 0;
    }

    evo()->addSnippet('hasAnyPermission', 'global $hasAnyPermission; return $hasAnyPermission;');

    evo()->addSnippet('getLoginUserName', 'return evo()->getLoginUserName();');
}

// Favicon
$_style['favicon'] = (file_exists(EVO_BASE_PATH . 'favicon.ico') ? EVO_SITE_URL . 'favicon.ico' : $style_path . 'favicon.ico');

// Icons
$_style['icon_size_2x'] = ' evo-icon-2x';
$_style['icon_size_fix'] = ' evo-icon-fw';
$_style['icon_spin'] = ' evo-icon-spin';

$_style['icon_add'] = 'evo-icon evo-icon-circle-plus';
$_style['icon_angle_down'] = 'evo-icon evo-icon-chevron-down';
$_style['icon_angle_left'] = 'evo-icon evo-icon-chevron-left';
$_style['icon_angle_right'] = 'evo-icon evo-icon-chevron-right';
$_style['icon_angle_up'] = 'evo-icon evo-icon-chevron-up';
$_style['icon_archive'] = 'evo-icon evo-icon-file-zip';
$_style['icon_arrow_down_circle'] = 'evo-icon evo-icon-circle-arrow-down';
$_style['icon_arrow_up_circle'] = 'evo-icon evo-icon-circle-arrow-up';
$_style['icon_ban'] = 'evo-icon evo-icon-ban';
$_style['icon_bars'] = 'evo-icon evo-icon-menu-2';
$_style['icon_brush'] = 'evo-icon evo-icon-brush';
$_style['icon_calendar'] = 'evo-icon evo-icon-calendar';
$_style['icon_calendar_close'] = 'evo-icon evo-icon-calendar-x';
$_style['icon_category'] = 'evo-icon evo-icon-box-multiple';
$_style['icon_camera'] = 'evo-icon evo-icon-camera';
$_style['icon_cancel'] = 'evo-icon evo-icon-circle-x';
$_style['icon_chain'] = 'evo-icon evo-icon-link';
$_style['icon_chain_broken'] = 'evo-icon evo-icon-link-off';
$_style['icon_check'] = 'evo-icon evo-icon-check';
$_style['icon_check_square'] = 'evo-icon evo-icon-square-check';
$_style['icon_chevron_down'] = 'evo-icon evo-icon-chevron-down';
$_style['icon_chevron_right'] = 'evo-icon evo-icon-chevron-right';
$_style['icon_chunk'] = 'evo-icon evo-icon-layout-grid';
$_style['icon_circle'] = 'evo-icon evo-icon-circle';
$_style['icon_clock'] = 'evo-icon evo-icon-clock';
$_style['icon_clone'] = 'evo-icon evo-icon-copy';
$_style['icon_close'] = 'evo-icon evo-icon-x';
$_style['icon_code'] = 'evo-icon evo-icon-code';
$_style['icon_code_file'] = 'evo-icon evo-icon-file-code';
$_style['icon_cog'] = 'evo-icon evo-icon-settings';
$_style['icon_cogs'] = 'evo-icon evo-icon-settings-cog';
$_style['icon_compress'] = 'evo-icon evo-icon-minimize';
$_style['icon_database'] = 'evo-icon evo-icon-database';
$_style['icon_desktop'] = 'evo-icon evo-icon-device-desktop';
$_style['icon_document'] = 'evo-icon evo-icon-file';
$_style['icon_download'] = 'evo-icon evo-icon-download';
$_style['icon_edit'] = 'evo-icon evo-icon-edit';
$_style['icon_disable'] = 'evo-icon evo-icon-circle-x';
$_style['icon_enable'] = 'evo-icon evo-icon-circle-check';
$_style['icon_elements'] = 'evo-icon evo-icon-layout-grid';
$_style['icon_excel'] = 'evo-icon evo-icon-file-spreadsheet';
$_style['icon_expand'] = 'evo-icon evo-icon-maximize';
$_style['icon_external_link'] = 'evo-icon evo-icon-external-link';
$_style['icon_eye'] = 'evo-icon evo-icon-eye';
$_style['icon_file'] = 'evo-icon evo-icon-file';
$_style['icon_files'] = 'evo-icon evo-icon-files';
$_style['icon_folder'] = 'evo-icon evo-icon-folder';
$_style['icon_folder_open'] = 'evo-icon evo-icon-folder-open';
$_style['icon_font'] = 'evo-icon evo-icon-typography';
$_style['icon_forward'] = 'evo-icon evo-icon-arrow-forward';
$_style['icon_globe'] = 'evo-icon evo-icon-world';
$_style['icon_home'] = 'evo-icon evo-icon-home';
$_style['icon_hourglass'] = 'evo-icon evo-icon-hourglass-high';
$_style['icon_i_cursor'] = 'evo-icon evo-icon-cursor-text';
$_style['icon_image'] = 'evo-icon evo-icon-file-type-jpg';
$_style['icon_info'] = 'evo-icon evo-icon-info-small';
$_style['icon_info_circle'] = 'evo-icon evo-icon-info-circle';
$_style['icon_info_triangle'] = 'evo-icon evo-icon-alert-triangle';
$_style['icon_loader'] = 'evo-icon evo-icon-loader-2';
$_style['icon_lock'] = 'evo-icon evo-icon-lock';
$_style['icon_unlock'] = 'evo-icon evo-icon-lock-open';
$_style['icon_link'] = 'evo-icon evo-icon-link';
$_style['icon_logout'] = 'evo-icon evo-icon-logout';
$_style['icon_mail'] = 'evo-icon evo-icon-mail';
$_style['icon_module'] = 'evo-icon evo-icon-cube';
$_style['icon_modules'] = 'evo-icon evo-icon-packages';
$_style['icon_move'] = 'evo-icon evo-icon-arrows-move';
$_style['icon_pdf'] = 'evo-icon evo-icon-file-type-pdf';
$_style['icon_pencil'] = 'evo-icon evo-icon-pencil';
$_style['icon_play'] = 'evo-icon evo-icon-player-play';
$_style['icon_plugin'] = 'evo-icon evo-icon-plug';
$_style['icon_plus'] = 'evo-icon evo-icon-plus';
$_style['icon_question_circle'] = 'evo-icon evo-icon-help-circle';
$_style['icon_recycle'] = 'evo-icon evo-icon-recycle';
$_style['icon_refresh'] = 'evo-icon evo-icon-refresh';
$_style['icon_reply'] = 'evo-icon evo-icon-corner-up-left';
$_style['icon_role'] = 'evo-icon evo-icon-gavel';
$_style['icon_save'] = 'evo-icon evo-icon-device-floppy';
$_style['icon_search'] = 'evo-icon evo-icon-search';
$_style['icon_sitemap'] = 'evo-icon evo-icon-sitemap';
$_style['icon_sliders'] = 'evo-icon evo-icon-adjustments-horizontal';
$_style['icon_sort'] = 'evo-icon evo-icon-arrows-sort';
$_style['icon_sort_num_asc'] = 'evo-icon evo-icon-sort-ascending-numbers';
$_style['icon_stop'] = 'evo-icon evo-icon-player-stop';
$_style['icon_table'] = 'evo-icon evo-icon-table';
$_style['icon_tachometer'] = 'evo-icon evo-icon-dashboard';
$_style['icon_template'] = 'evo-icon evo-icon-news';
$_style['icon_theme'] = 'evo-icon evo-icon-brightness-half';
$_style['icon_trash'] = 'evo-icon evo-icon-trash-filled';
$_style['icon_trash_alt'] = 'evo-icon evo-icon-trash';
$_style['icon_tv'] = 'evo-icon evo-icon-list-details';
$_style['icon_undo'] = 'evo-icon evo-icon-arrow-back-up';
$_style['icon_upload'] = 'evo-icon evo-icon-upload';
$_style['icon_user'] = 'evo-icon evo-icon-user-circle';
$_style['icon_users'] = 'evo-icon evo-icon-users';
$_style['icon_user_access'] = 'evo-icon evo-icon-accessible';
$_style['icon_user_secret'] = 'evo-icon evo-icon-user-shield';
$_style['icon_no_user_role'] = 'evo-icon evo-icon-user';
$_style['icon_web_user'] = 'evo-icon evo-icon-user';
$_style['icon_web_user_access'] = 'evo-icon evo-icon-user';
$_style['icon_word'] = 'evo-icon evo-icon-file-type-doc';
$_style['icon_wrench'] = 'evo-icon evo-icon-tool';

$_style['tx'] = $style_path . 'misc/_tx_.gif';

// actions buttons templates
$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';
if (!empty(evo()->config['global_tabs']) && !isset($_SESSION['stay'])) {
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
switch ($action) {
    case '3':
    case '4':
    case '27':
    case '72':
        if (evo()->hasPermission('new_document')) {
            $addnew = 1;
        }
        break;
    case '16':
    case '19':
        if (evo()->hasPermission('new_template')) {
            $addnew = 1;
        }
        break;
    case '300':
    case '301':
        if (evo()->hasPermission('new_snippet') && evo()->hasPermission('new_chunk') && evo()->hasPermission('new_plugin')) {
            $addnew = 1;
        }
        break;
    case '77':
    case '78':
        if (evo()->hasPermission('new_chunk')) {
            $addnew = 1;
        }
        break;
    case '22':
    case '23':
        if (evo()->hasPermission('new_snippet')) {
            $addnew = 1;
        }
        break;
    case '101':
    case '102':
        if (evo()->hasPermission('new_plugin')) {
            $addnew = 1;
        }
        break;
    case '106':
    case '107':
    case '108':
        if (evo()->hasPermission('new_module')) {
            $addnew = 1;
        }
        if (evo()->hasPermission('exec_module')) {
            $run = 1;
        }
        break;
    case '88':
        if (evo()->hasPermission('new_user')) {
            $addnew = 1;
        }
        break;
}

$disabled = ($action == '19' || $action == '300' || $action == '77' || $action == '23' || $action == '101' || $action == '4' || $action == '72' || $action == '87' || $action == '11' || $action == '107' || $action == '38') ? ' disabled' : '';

$_style['actionbuttons'] = [
    'dynamic' => [
        'document' => '<div id="actions">
			<div class="btn-group">
			' . (isset($_REQUEST['id']) ? '
					<a class="btn btn-secondary" href="javascript:;" onclick="actions.new();">
						<i class="' . $_style["icon_document"] . '"></i><span>' . $_lang['create_resource_here'] . '</span>
					</a>
					<a class="btn btn-secondary" href="javascript:;" onclick="actions.newlink();">
						<i class="' . $_style["icon_chain"] . '"></i><span>' . $_lang['create_weblink_here'] . '</span>
					</a>
				' : '') . '
				<div class="btn-group">
					<a id="Button1" class="btn btn-success" href="javascript:;" onclick="actions.save();">
						<i class="' . $_style["icon_save"] . '"></i><span>' . $_lang['save'] . '</span>
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
						<i class="' . $_style["icon_clone"] . '"></i><span>' . $_lang['duplicate'] . '</span>
					</a>
					' : '') . '
				<a id="Button3" class="btn btn-secondary' . $disabled . '" href="javascript:;" onclick="actions.delete();">
					<i class="' . $_style["icon_trash"] . '"></i><span>' . $_lang['delete'] . '</span>
				</a>
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["icon_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
				<a id="Button4" class="btn btn-secondary" href="javascript:;" onclick="actions.view();">
					<i class="' . $_style["icon_eye"] . '"></i><span>' . $_lang['preview'] . '</span>
				</a>
			</div>
		</div>',
        'user' => '<div id="actions">
			<div class="btn-group">
				<div class="btn-group">
					<a id="Button1" class="btn btn-success" href="javascript:;" onclick="actions.save();">
						<i class="' . $_style["icon_save"] . '"></i><span>' . $_lang['save'] . '</span>
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
					<i class="' . $_style["icon_trash"] . '"></i><span>' . $_lang['delete'] . '</span>
				</a>
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["icon_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
			</div>
		</div>',
        'element' => '<div id="actions">
			<div class="btn-group">
				<div class="btn-group">
					<a id="Button1" class="btn btn-success" href="javascript:;" onclick="actions.save();">
						<i class="' . $_style["icon_save"] . '"></i><span>' . $_lang['save'] . '</span>
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
					<i class="' . $_style["icon_clone"] . '"></i><span>' . $_lang['duplicate'] . '</span>
				</a>
				' : '') . '
				<a id="Button3" class="btn btn-secondary' . $disabled . '" href="javascript:;" onclick="actions.delete();">
					<i class="' . $_style["icon_trash"] . '"></i><span>' . $_lang['delete'] . '</span>
				</a>
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["icon_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
				' . ($run ? '
				<a id="Button4" class="btn btn-secondary' . $disabled . '" href="javascript:;" onclick="actions.run();">
					<i class="' . $_style["icon_play"] . '"></i><span>' . $_lang['run_module'] . '</span>
				</a>
				' : '') . '
			</div>
		</div>',
        'newmodule' => ($addnew ? '<div id="actions">
			<div class="btn-group">
				<a id="newModule" class="btn btn-secondary" href="javascript:;" onclick="actions.new();">
					<i class="' . $_style["icon_add"] . '"></i><span>' . $_lang['new_module'] . '</span>
				</a>
			</div>
		</div>' : ''),
        'close' => '<div id="actions">
			<div class="btn-group">
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.close();">
					<i class="' . $_style["icon_close"] . '"></i><span>' . $_lang['close'] . '</span>
				</a>
			</div>
		</div>',
        'save' => '<div id="actions">
			<div class="btn-group">
				<a id="Button1" class="btn btn-success" href="javascript:;" onclick="actions.save();">
					<i class="' . $_style["icon_save"] . '"></i><span>' . $_lang['save'] . '</span>
				</a>
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["icon_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
			</div>
		</div>',
        'savedelete' => '<div id="actions">
			<div class="btn-group">
				<a id="Button1" class="btn btn-success" href="javascript:;" onclick="actions.save();">
					<i class="' . $_style["icon_save"] . '"></i><span>' . $_lang['save'] . '</span>
				</a>
				<a id="Button3" class="btn btn-secondary' . $disabled . '" href="javascript:;" onclick="actions.delete();">
					<i class="' . $_style["icon_trash"] . '"></i><span>' . $_lang['delete'] . '</span>
				</a>
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["icon_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
			</div>
		</div>',
        'cancel' => '<div id="actions">
			<div class="btn-group">
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["icon_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
			</div>
		</div>',
        'canceldelete' => '<div id="actions">
			<div class="btn-group">
				<a id="Button3" class="btn btn-secondary' . $disabled . '" href="javascript:;" onclick="actions.delete();">
					<i class="' . $_style["icon_trash"] . '"></i><span>' . $_lang['delete'] . '</span>
				</a>
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["icon_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
			</div>
		</div>',
    ],
    'static' => [
        'document' => '<div id="actions">
			<div class="btn-group">' .
            ($addnew ? '
					<a class="btn btn-secondary" href="javascript:;" onclick="actions.new();">
						<i class="' . $_style["icon_document"] . '"></i><span>' . $_lang['create_resource_here'] . '</span>
					</a>
					<a class="btn btn-secondary" href="javascript:;" onclick="actions.newlink();">
						<i class="' . $_style["icon_chain"] . '"></i><span>' . $_lang['create_weblink_here'] . '</span>
					</a>
				' : '') . '
				<a id="Button1" class="btn btn-success" href="javascript:;" onclick="actions.edit();">
					<i class="' . $_style["icon_edit"] . '"></i><span>' . $_lang['edit'] . '</span>
				</a>
				<a id="Button2" class="btn btn-secondary" href="javascript:;" onclick="actions.move();">
					<i class="' . $_style["icon_move"] . '"></i><span>' . $_lang['move'] . '</span>
				</a>
				<a id="Button6" class="btn btn-secondary" href="javascript:;" onclick="actions.duplicate();">
					<i class="' . $_style["icon_clone"] . '"></i><span>' . $_lang['duplicate'] . '</span>
				</a>
				<a id="Button3" class="btn btn-secondary" href="javascript:;" onclick="actions.delete();">
					<i class="' . $_style["icon_trash"] . '"></i><span>' . $_lang['delete'] . '</span>
				</a>
				<a id="Button4" class="btn btn-secondary" href="javascript:;" onclick="actions.view();">
					<i class="' . $_style["icon_eye"] . '"></i><span>' . $_lang['preview'] . '</span>
				</a>
			</div>
		</div>',
        'cancel' => '<div id="actions">
			<div class="btn-group">
				<a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="actions.cancel();">
					<i class="' . $_style["icon_cancel"] . '"></i><span>' . $_lang['cancel'] . '</span>
				</a>
			</div>
		</div>',
    ],
];
