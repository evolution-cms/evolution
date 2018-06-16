<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

switch ((int)$modx->getManagerApi()->action) {
    case 35:
        if (!$modx->hasPermission('edit_role')) {
            $modx->webAlertAndQuit($_lang["error_no_privileges"]);
        }
        break;
    case 38:
        if (!$modx->hasPermission('new_role')) {
            $modx->webAlertAndQuit($_lang["error_no_privileges"]);
        }
        break;
    default:
        $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$role = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

$tbl_user_roles = $modx->getDatabase()->getFullTableName('user_roles');

// check to see the snippet editor isn't locked
if ($lockedEl = $modx->elementIsLocked(8, $role)) {
    $modx->webAlertAndQuit(sprintf($_lang['lock_msg'], $lockedEl['username'], $_lang['role']));
}
// end check for lock

// Lock snippet for other users to edit
$modx->lockElement(8, $role);

if ($modx->getManagerApi()->action == '35') {
    $rs = $modx->getDatabase()->select('*', $tbl_user_roles, "id='{$role}'");
    $roledata = $modx->getDatabase()->getRow($rs);
    if (!$roledata) {
        $modx->webAlertAndQuit("No role returned!");
    }
    $_SESSION['itemname'] = $roledata['name'];
} else {
    $roledata = 0;
    $_SESSION['itemname'] = $_lang["new_role"];
}

// Add lock-element JS-Script
$lockElementId = $role;
$lockElementType = 8;
require_once(MODX_MANAGER_PATH . 'includes/active_user_locks.inc.php');
?>
<script type="text/javascript">
    function changestate(element) {
        documentDirty = true;
        if (parseInt(element.value) === 1) {
            element.value = 0;
        } else {
            element.value = 1;
        }
    }

    var actions = {
        save: function () {
            documentDirty = false;
            form_save = true;
            document.userform.save.click();
        },
        delete: function () {
            if (confirm("<?= $_lang['confirm_delete_role'] ?>") === true) {
                document.location.href = "index.php?id=" + document.userform.id.value + "&a=37";
            }
        },
        cancel: function () {
            documentDirty = false;
            document.location.href = 'index.php?a=86';
        }
    }

</script>

<form action="index.php?a=36" method="post" name="userform" enctype="multipart/form-data">
    <input type="hidden" name="mode" value="<?= $modx->getManagerApi()->action ?>">
    <input type="hidden" name="id" value="<?= $_GET['id'] ?>">

    <h1>
        <i class="fa fa-legal"></i><?= ($roledata['name'] ? $roledata['name'] . '<small>(' . $roledata['id'] . ')</small>' : $_lang['role_title']) ?>
    </h1>

    <?= $_style['actionbuttons']['dynamic']['savedelete'] ?>

    <div class="tab-page">
        <div class="container container-body">
            <div class="form-group">
                <div class="row form-row">
                    <div class="col-md-3 col-lg-2"><?= $_lang['role_name'] ?>:</div>
                    <div class="col-md-9 col-lg-10"><input class="form-control form-control-lg" name="name" type="text"
                                                           maxlength="50" value="<?= $roledata['name'] ?>"/></div>
                </div>
                <div class="row form-row">
                    <div class="col-md-3 col-lg-2"><?= $_lang['resource_description'] ?>:</div>
                    <div class="col-md-9 col-lg-10"><input name="description" type="text" maxlength="255"
                                                           value="<?= $roledata['description'] ?>" size="60"/></div>
                </div>
            </div>

            <div class="container">

                <div class="row">
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-group">
                            <h3><?= $_lang['page_data_general'] ?></h3>
                            <?php
                            echo render_form('frames', $_lang['role_frames'], 'disabled');
                            echo render_form('home', $_lang['role_home'], 'disabled');
                            echo render_form('messages', $_lang['role_messages']);
                            echo render_form('logout', $_lang['role_logout'], 'disabled');
                            echo render_form('help', $_lang['role_help']);
                            echo render_form('action_ok', $_lang['role_actionok'], 'disabled');
                            echo render_form('error_dialog', $_lang['role_errors'], 'disabled');
                            echo render_form('about', $_lang['role_about'], 'disabled');
                            echo render_form('credits', $_lang['role_credits'], 'disabled');
                            echo render_form('change_password', $_lang['role_change_password']);
                            echo render_form('save_password', $_lang['role_save_password']);
                            ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-group">
                            <h3><?= $_lang['role_content_management'] ?></h3>
                            <?php
                            echo render_form('view_document', $_lang['role_view_docdata'], 'disabled');
                            echo render_form('new_document', $_lang['role_create_doc']);
                            echo render_form('edit_document', $_lang['role_edit_doc']);
                            echo render_form('change_resourcetype', $_lang['role_change_resourcetype']);
                            echo render_form('save_document', $_lang['role_save_doc']);
                            echo render_form('publish_document', $_lang['role_publish_doc']);
                            echo render_form('delete_document', $_lang['role_delete_doc']);
                            echo render_form('empty_trash', $_lang['role_empty_trash']);
                            echo render_form('empty_cache', $_lang['role_cache_refresh']);
                            echo render_form('view_unpublished', $_lang['role_view_unpublished']);
                            ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3 form-group">
                        <div class="form-group">
                            <h3><?= $_lang['role_file_management'] ?></h3>
                            <?php
                            echo render_form('file_manager', $_lang['role_file_manager']);
                            echo render_form('assets_files', $_lang['role_assets_files']);
                            echo render_form('assets_images', $_lang['role_assets_images']);
                            ?>
                        </div>
                        <div class="form-group">
                            <h3><?= $_lang['category_management'] ?></h3>
                            <?php
                            echo render_form('category_manager', $_lang['role_category_manager']);
                            ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-group">
                            <h3><?= $_lang['role_module_management'] ?></h3>
                            <?php
                            echo render_form('new_module', $_lang['role_new_module']);
                            echo render_form('edit_module', $_lang['role_edit_module']);
                            echo render_form('save_module', $_lang['role_save_module']);
                            echo render_form('delete_module', $_lang['role_delete_module']);
                            echo render_form('exec_module', $_lang['role_run_module']);
                            ?>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-group">
                            <h3><?= $_lang['role_template_management'] ?></h3>
                            <?php
                            echo render_form('new_template', $_lang['role_create_template']);
                            echo render_form('edit_template', $_lang['role_edit_template']);
                            echo render_form('save_template', $_lang['role_save_template']);
                            echo render_form('delete_template', $_lang['role_delete_template']);
                            ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-group">
                            <h3><?= $_lang['role_snippet_management'] ?></h3>
                            <?php
                            echo render_form('new_snippet', $_lang['role_create_snippet']);
                            echo render_form('edit_snippet', $_lang['role_edit_snippet']);
                            echo render_form('save_snippet', $_lang['role_save_snippet']);
                            echo render_form('delete_snippet', $_lang['role_delete_snippet']);
                            ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-group">
                            <h3><?= $_lang['role_chunk_management'] ?></h3>
                            <?php
                            echo render_form('new_chunk', $_lang['role_create_chunk']);
                            echo render_form('edit_chunk', $_lang['role_edit_chunk']);
                            echo render_form('save_chunk', $_lang['role_save_chunk']);
                            echo render_form('delete_chunk', $_lang['role_delete_chunk']);
                            ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-group">
                            <h3><?= $_lang['role_plugin_management'] ?></h3>
                            <?php
                            echo render_form('new_plugin', $_lang['role_create_plugin']);
                            echo render_form('edit_plugin', $_lang['role_edit_plugin']);
                            echo render_form('save_plugin', $_lang['role_save_plugin']);
                            echo render_form('delete_plugin', $_lang['role_delete_plugin']);
                            ?>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-group">
                            <h3><?= $_lang['role_user_management'] ?></h3>
                            <?php
                            echo render_form('new_user', $_lang['role_new_user']);
                            echo render_form('edit_user', $_lang['role_edit_user']);
                            echo render_form('save_user', $_lang['role_save_user']);
                            echo render_form('delete_user', $_lang['role_delete_user']);
                            ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-group">
                            <h3><?= $_lang['role_web_user_management'] ?></h3>
                            <?php
                            echo render_form('new_web_user', $_lang['role_new_web_user']);
                            echo render_form('edit_web_user', $_lang['role_edit_web_user']);
                            echo render_form('save_web_user', $_lang['role_save_web_user']);
                            echo render_form('delete_web_user', $_lang['role_delete_web_user']);
                            ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-group">
                            <h3><?= $_lang['role_udperms'] ?></h3>
                            <?php
                            echo render_form('access_permissions', $_lang['role_access_persmissions']);
                            echo render_form('web_access_permissions', $_lang['role_web_access_persmissions']);
                            ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-group">
                            <h3><?= $_lang['role_role_management'] ?></h3>
                            <?php
                            echo render_form('new_role', $_lang['role_new_role']);
                            echo render_form('edit_role', $_lang['role_edit_role']);
                            echo render_form('save_role', $_lang['role_save_role']);
                            echo render_form('delete_role', $_lang['role_delete_role']);
                            ?>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-group">
                            <h3><?= $_lang['role_eventlog_management'] ?></h3>
                            <?php
                            echo render_form('view_eventlog', $_lang['role_view_eventlog']);
                            echo render_form('delete_eventlog', $_lang['role_delete_eventlog']);
                            ?>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="form-group">
                            <h3><?= $_lang['role_config_management'] ?></h3>
                            <?php
                            echo render_form('logs', $_lang['role_view_logs']);
                            echo render_form('settings', $_lang['role_edit_settings']);
                            echo render_form('bk_manager', $_lang['role_bk_manager']);
                            echo render_form('import_static', $_lang['role_import_static']);
                            echo render_form('export_static', $_lang['role_export_static']);
                            echo render_form('remove_locks', $_lang['role_remove_locks']);
                            echo render_form('display_locks', $_lang['role_display_locks']);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <input type="submit" name="save" style="display:none">
</form>
