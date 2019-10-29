<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

switch ($modx->manager->action) {
    case 78:
        if (!$modx->hasPermission('edit_chunk')) {
            $modx->webAlertAndQuit($_lang["error_no_privileges"]);
        }
        break;
    case 77:
        if (!$modx->hasPermission('new_chunk')) {
            $modx->webAlertAndQuit($_lang["error_no_privileges"]);
        }
        break;
    default:
        $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

// Get table names (alphabetical)
$tbl_site_htmlsnippets = $modx->getFullTableName('site_htmlsnippets');

// check to see the snippet editor isn't locked
if ($lockedEl = $modx->elementIsLocked(3, $id)) {
    $modx->webAlertAndQuit(sprintf($_lang['lock_msg'], $lockedEl['username'], $_lang['chunk']));
}
// end check for lock

// Lock snippet for other users to edit
$modx->lockElement(3, $id);

$content = array();
if (isset($_REQUEST['id']) && $_REQUEST['id'] != '' && is_numeric($_REQUEST['id'])) {
    $rs = $modx->db->select('*', $tbl_site_htmlsnippets, "id='{$id}'");
    $content = $modx->db->getRow($rs);
    if (!$content) {
        $modx->webAlertAndQuit("Chunk not found for id '{$id}'.");
    }
    $_SESSION['itemname'] = $content['name'];
    if ($content['locked'] == 1 && $_SESSION['mgrRole'] != 1) {
        $modx->webAlertAndQuit($_lang["error_no_privileges"]);
    }
} else if (isset($_REQUEST['itemname'])) {
    $content['name'] = $_REQUEST['itemname'];
} else {
    $_SESSION['itemname'] = $_lang["new_htmlsnippet"];
    $content['category'] = (int)$_REQUEST['catid'];
}

if ($modx->manager->hasFormValues()) {
    $modx->manager->loadFormValues();
}

if (isset($_POST['which_editor'])) {
    $which_editor = $_POST['which_editor'];
} else {
    $which_editor = $content['editor_name'] != 'none' ? $content['editor_name'] : 'none';
}

$content = array_merge($content, $_POST);

// Add lock-element JS-Script
$lockElementId = $id;
$lockElementType = 3;
require_once(MODX_MANAGER_PATH . 'includes/active_user_locks.inc.php');

// Print RTE Javascript function
?>
    <script language="javascript" type="text/javascript">
        // Added for RTE selection
        function changeRTE()
        {
            var whichEditor = document.getElementById('which_editor');
            if (whichEditor) {
                for (var i = 0; i < whichEditor.length; i++) {
                    if (whichEditor[i].selected) {
                        newEditor = whichEditor[i].value;
                        break;
                    }
                }
            }

            documentDirty = false;
            document.mutate.a.value = <?= $action ?>;
            document.mutate.which_editor.value = newEditor;
            document.mutate.submit();
        }

        var actions = {
            save: function() {
                documentDirty = false;
                form_save = true;
                document.mutate.save.click();
            }, duplicate: function() {
                if (confirm('<?= $_lang['confirm_duplicate_record'] ?>') === true) {
                    documentDirty = false;
                    document.location.href = "index.php?id=<?= $_REQUEST['id'] ?>&a=97";
                }
            }, delete: function() {
                if (confirm('<?= $_lang['confirm_delete_htmlsnippet'] ?>') === true) {
                    documentDirty = false;
                    document.location.href = 'index.php?id=' + document.mutate.id.value + '&a=80';
                }
            }, cancel: function() {
                documentDirty = false;
                document.location.href = 'index.php?a=76';
            },
        };

        document.addEventListener('DOMContentLoaded', function() {
            var h1help = document.querySelector('h1 > .help');
            h1help.onclick = function() {
                document.querySelector('.element-edit-message').classList.toggle('show');
            };
        });

    </script>

    <form name="mutate" method="post" action="index.php" id="mutate" class="htmlsnippet">
        <?php

        // invoke OnChunkFormPrerender event
        $evtOut = $modx->invokeEvent('OnChunkFormPrerender', array(
            'id' => $id,
        ));
        if (is_array($evtOut)) {
            echo implode('', $evtOut);
        }

        ?>
        <input type="hidden" name="a" value="79" />
        <input type="hidden" name="id" value="<?= $_REQUEST['id'] ?>" />
        <input type="hidden" name="mode" value="<?= $modx->manager->action ?>" />

        <h1>
            <i class="fa fa-th-large"></i><?= ($content['name'] ? $content['name'] . '<small>(' . $content['id'] . ')</small>' : $_lang['new_htmlsnippet']) ?><i class="fa fa-question-circle help"></i>
        </h1>

        <?= $_style['actionbuttons']['dynamic']['element'] ?>

        <div class="container element-edit-message">
            <div class="alert alert-info"><?= $_lang['htmlsnippet_msg'] ?></div>
        </div>

        <div class="tab-pane" id="chunkPane">
            <script type="text/javascript">
                tpChunk = new WebFXTabPane(document.getElementById('chunkPane'), <?= ($modx->config['remember_last_tab'] == 1 ? 'true' : 'false') ?> );
            </script>

            <div class="tab-page" id="tabGeneral">
                <h2 class="tab"><?= $_lang["settings_general"] ?></h2>
                <script type="text/javascript">tpChunk.addTabPage(document.getElementById('tabGeneral'));</script>
                <div class="container container-body">
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['htmlsnippet_name'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <div class="form-control-name clearfix">
                                <input name="name" type="text" maxlength="100" value="<?= $modx->htmlspecialchars($content['name']) ?>" class="form-control form-control-lg" onchange="documentDirty=true;" />
                                <?php if ($modx->hasPermission('save_role')): ?>
                                    <label class="custom-control" title="<?= $_lang['lock_htmlsnippet'] . "\n" . $_lang['lock_htmlsnippet_msg'] ?>" tooltip>
                                        <input name="locked" type="checkbox" value="on"<?= ($content['locked'] == 1 || $content['locked'] == 'on' ? ' checked="checked"' : '') ?> />
                                        <i class="fa fa-lock"></i>
                                    </label>
                                <?php endif; ?>
                            </div>
                            <script>if (!document.getElementsByName('name')[0].value) {
                                    document.getElementsByName('name')[0].focus();
                                }</script>
                            <small class="form-text text-danger hide" id="savingMessage"></small>
                        </div>
                    </div>
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['htmlsnippet_desc'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <input name="description" type="text" maxlength="255" value="<?= $modx->htmlspecialchars($content['description']) ?>" class="form-control" onchange="documentDirty=true;" />
                        </div>
                    </div>
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['existing_category'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <select name="categoryid" class="form-control" onchange="documentDirty=true;">
                                <option>&nbsp;</option>
                                <?php
                                include_once(MODX_MANAGER_PATH . 'includes/categories.inc.php');
                                foreach (getCategories() as $n => $v) {
                                    echo "\t\t\t\t" . '<option value="' . $v['id'] . '"' . ($content['category'] == $v['id'] || (empty($content['category']) && $_POST['categoryid'] == $v['id']) ? ' selected="selected"' : '') . '>' . $modx->htmlspecialchars($v['category']) . "</option>\n";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['new_category'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <input name="newcategory" type="text" maxlength="45" value="<?= isset($content['newcategory']) ? $content['newcategory'] : '' ?>" class="form-control" onChange="documentDirty=true;" />
                        </div>
                    </div>
                    <?php if ($_SESSION['mgrRole'] == 1): ?>
                        <div class="form-row">
                            <label><input name="disabled" type="checkbox" value="on"<?= ($content['disabled'] == 1 ? ' checked="checked"' : '') ?> /> <?= ($content['disabled'] == 1 ? "<span class='text-danger'>" . $_lang['disabled'] . "</span>" : $_lang['disabled']) ?></label>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- HTML text editor start -->
                <div class="navbar navbar-editor">
                    <span><?= $_lang['chunk_code'] ?></span>
                    <span class="float-right"><?= $_lang['which_editor_title'] ?>
                        <select class="form-control form-control-sm" id="which_editor" name="which_editor" size="1" onchange="changeRTE();">
						<option value="none"<?= $which_editor == 'none' ? ' selected="selected"' : '' ?>><?= $_lang['none'] ?></option>
                            <?php
                            // invoke OnRichTextEditorRegister event
                            $evtOut = $modx->invokeEvent('OnRichTextEditorRegister');
                            if (is_array($evtOut)) {
                                foreach ($evtOut as $i => $editor) {
                                    echo "\t" . '<option value="' . $editor . '"' . ($which_editor == $editor ? ' selected="selected"' : '') . '>' . $editor . "</option>\n";
                                }
                            }
                            ?>
					</select>
				</span>
                </div>
                <div class="section-editor clearfix">
                    <textarea dir="ltr" class="phptextarea" id="post" name="post" rows="20" onChange="documentDirty=true;"><?= isset($content['post']) ? $modx->htmlspecialchars($content['post']) : $modx->htmlspecialchars($content['snippet']) ?></textarea>
                </div>
                <!-- HTML text editor end -->
            </div>

            <?php

            // invoke OnChunkFormRender event
            $evtOut = $modx->invokeEvent('OnChunkFormRender', array(
                'id' => $id,
            ));
            if (is_array($evtOut)) {
                echo implode('', $evtOut);
            }
            ?>
        </div>
        <input type="submit" name="save" style="display:none;" />
    </form>
<?php
// invoke OnRichTextEditorInit event
if ($use_editor == 1) {
    $evtOut = $modx->invokeEvent('OnRichTextEditorInit', array(
        'editor' => $which_editor,
        'elements' => array(
            'post',
        ),
    ));
    if (is_array($evtOut)) {
        echo implode('', $evtOut);
    }
}
