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
$style_path = 'media/style/' . ManagerTheme::getTheme() . '/images/';
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
$_style['favicon'] = (file_exists(EVO_BASE_PATH . 'favicon.ico') ? EVO_SITE_URL . 'favicon.ico' : 'media/style/' . ManagerTheme::getTheme() . '/images/favicon.ico');

// Icons
$_style['icon_size_2x'] = ' fa-2x';
$_style['icon_size_fix'] = ' fa-fw';
$_style['icon_spin'] = ' fa-spin';

$_style['icon_add'] = 'fa fa-plus-circle';
$_style['icon_angle_down'] = 'fa fa-angle-down';
$_style['icon_angle_left'] = 'fa fa-angle-left';
$_style['icon_angle_right'] = 'fa fa-angle-right';
$_style['icon_angle_up'] = 'fa fa-angle-up';
$_style['icon_archive'] = 'fa fa-file-archive-o';
$_style['icon_arrow_down_circle'] = 'fa fa-arrow-circle-down';
$_style['icon_arrow_up_circle'] = 'fa fa-arrow-circle-up';
$_style['icon_ban'] = 'fa fa-ban';
$_style['icon_bars'] = 'fa fa-bars';
$_style['icon_brush'] = 'fa fa-paint-brush';
$_style['icon_calendar'] = 'fa fa-calendar';
$_style['icon_calendar_close'] = 'fa fa-calendar-times-o';
$_style['icon_category'] = 'fa fa-object-group';
$_style['icon_camera'] = 'fa fa-camera';
$_style['icon_cancel'] = 'fa fa-times-circle';
$_style['icon_chain'] = 'fa fa-link';
$_style['icon_chain_broken'] = 'fa fa-chain-broken';
$_style['icon_check'] = 'fa fa-check';
$_style['icon_check_square'] = 'fa fa-check-square';
$_style['icon_chevron_down'] = 'fa fa-chevron-down';
$_style['icon_chevron_right'] = 'fa fa-chevron-right';
$_style['icon_chunk'] = 'fa fa-th-large';
$_style['icon_circle'] = 'fa fa-circle';
$_style['icon_clock'] = 'fa fa-clock-o';
$_style['icon_clone'] = 'fa fa-clone';
$_style['icon_close'] = 'fa fa-close';
$_style['icon_code'] = 'fa fa-code';
$_style['icon_code_file'] = 'fa fa-file-code-o';
$_style['icon_cog'] = 'fa fa-cog';
$_style['icon_cogs'] = 'fa fa-cogs';
$_style['icon_compress'] = 'fa fa-compress';
$_style['icon_database'] = 'fa fa-database';
$_style['icon_desktop'] = 'fa fa-desktop';
$_style['icon_document'] = 'fa fa-file-o';
$_style['icon_download'] = 'fa fa-download';
$_style['icon_edit'] = 'fa fa-edit';
$_style['icon_disable'] = 'fa fa-times-circle-o';
$_style['icon_enable'] = 'fa fa-check-circle-o';
$_style['icon_elements'] = 'fa fa-th';
$_style['icon_excel'] = 'fa fa-file-excel-o';
$_style['icon_expand'] = 'fa fa-expand';
$_style['icon_external_link'] = 'fa fa-external-link';
$_style['icon_eye'] = 'fa fa-eye';
$_style['icon_file'] = 'fa fa-file-o';
$_style['icon_files'] = 'fa fa-files-o';
$_style['icon_folder'] = 'fa fa-folder';
$_style['icon_folder_open'] = 'fa fa-folder-open';
$_style['icon_font'] = 'fa fa-font';
$_style['icon_forward'] = 'fa fa-forward';
$_style['icon_globe'] = 'fa fa-globe';
$_style['icon_home'] = 'fa fa-home';
$_style['icon_hourglass'] = 'fa fa-hourglass-end';
$_style['icon_i_cursor'] = 'fa fa-i-cursor';
$_style['icon_image'] = 'fa fa-file-image-o';
$_style['icon_info'] = 'fa fa-info';
$_style['icon_info_circle'] = 'fa fa-info-circle';
$_style['icon_info_triangle'] = 'fa fa-exclamation-triangle';
$_style['icon_loader'] = 'fa fa-spinner';
$_style['icon_lock'] = 'fa fa-lock';
$_style['icon_unlock'] = 'fa fa-unlock-alt';
$_style['icon_logout'] = 'fa fa-sign-out';
$_style['icon_mail'] = 'fa fa-envelope';
$_style['icon_module'] = 'fa fa-cube';
$_style['icon_modules'] = 'fa fa-cubes';
$_style['icon_move'] = 'fa fa-arrows';
$_style['icon_pdf'] = 'fa fa-file-pdf-o';
$_style['icon_pencil'] = 'fa fa-pencil';
$_style['icon_play'] = 'fa fa-play';
$_style['icon_plugin'] = 'fa fa-plug';
$_style['icon_plus'] = 'fa fa-plus';
$_style['icon_question_circle'] = 'fa fa-question-circle';
$_style['icon_recycle'] = 'fa fa-recycle';
$_style['icon_refresh'] = 'fa fa-refresh';
$_style['icon_reply'] = 'fa fa-reply';
$_style['icon_role'] = 'fa fa-legal';
$_style['icon_save'] = 'fa fa-floppy-o';
$_style['icon_search'] = 'fa fa-search';
$_style['icon_sitemap'] = 'fa fa-sitemap';
$_style['icon_sliders'] = 'fa fa-sliders';
$_style['icon_sort'] = 'fa fa-sort';
$_style['icon_sort_num_asc'] = 'fa fa-sort-numeric-asc';
$_style['icon_stop'] = 'fa fa-stop';
$_style['icon_table'] = 'fa fa-table';
$_style['icon_tachometer'] = 'fa fa-tachometer';
$_style['icon_template'] = 'fa fa-newspaper-o';
$_style['icon_theme'] = 'fa fa-adjust';
$_style['icon_trash'] = 'fa fa-trash';
$_style['icon_trash_alt'] = 'fa fa-trash-o';
$_style['icon_tv'] = 'fa fa-list-alt';
$_style['icon_undo'] = 'fa fa-undo';
$_style['icon_upload'] = 'fa fa-upload';
$_style['icon_user'] = 'fa fa-user-circle-o';
$_style['icon_users'] = 'fa fa-users';
$_style['icon_user_access'] = 'fa fa-universal-access';
$_style['icon_user_secret'] = 'fa fa-user-secret';
$_style['icon_no_user_role'] = 'fa fa-user-o';
$_style['icon_web_user'] = 'fa fa-user';
$_style['icon_web_user_access'] = 'fa fa-male';
$_style['icon_word'] = 'fa fa-file-word-o';
$_style['icon_wrench'] = 'fa fa-wrench';

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
