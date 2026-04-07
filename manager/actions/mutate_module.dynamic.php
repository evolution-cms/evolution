<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
switch ($modx->getManagerApi()->action) {
    case 107:
        if (!$modx->hasPermission('new_module')) {
            $modx->webAlertAndQuit($_lang["error_no_privileges"]);
        }
        break;
    case 108:
        if (!$modx->hasPermission('edit_module')) {
            $modx->webAlertAndQuit($_lang["error_no_privileges"]);
        }
        break;
    default:
        $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

// check to see the module editor isn't locked
if ($lockedEl = $modx->elementIsLocked(6, $id)) {
    $modx->webAlertAndQuit(sprintf($_lang['lock_msg'], $lockedEl['username'], $_lang['module']));
}
// end check for lock

// Lock snippet for other users to edit
$modx->lockElement(6, $id);

if (isset($_GET['id'])) {
    $content = \EvolutionCMS\Models\SiteModule::find($id);
    if (is_null($content)) {
        $modx->webAlertAndQuit("Module not found for id '{$id}'.");
    }
    $content = $content->toArray();
    $content['properties'] = str_replace("&", "&amp;", $content['properties']);
    $_SESSION['itemname'] = $content['name'];
    if ($content['locked'] == 1 && $_SESSION['mgrRole'] != 1) {
        $modx->webAlertAndQuit($_lang["error_no_privileges"]);
    }
} else {
    $_SESSION['itemname'] = $_lang["new_module"];
    $content['wrap'] = '1';
}
if ($modx->getManagerApi()->hasFormValues()) {
    $modx->getManagerApi()->loadFormValues();
}

$content = array_merge($content, $_POST);

// Add lock-element JS-Script
$lockElementId = $id;
$lockElementType = 6;
require_once(EVO_MANAGER_PATH . 'includes/active_user_locks.inc.php');
?>
<script src="media/script/element-properties.js"></script>
<script type="text/javascript">
    var elementProperties = new ElementProperties({
        name: 'elementProperties',
        lang: {
            parameter: '<?= ManagerTheme::getLexicon('parameter') ?>',
            value: '<?= ManagerTheme::getLexicon('value') ?>',
            set_default: '<?= ManagerTheme::getLexicon('set_default') ?>',
        },
        icon_refresh: '<?= ManagerTheme::getStyle('icon_refresh') ?>',
        table:'displayparams',
        tr: 'displayparamrow',
        td: 'displayparams',
    });
    function loadDependencies() {
        if (documentDirty) {
            if (!confirm("<?= $_lang['confirm_load_depends']?>")) {
                return;
            }
        }
        documentDirty = false;

        window.location.href = "index.php?id=<?= (isset($_REQUEST['id'])) ? $_REQUEST['id'] : "" ?>&a=113";
    }

    var actions = {
        save: function () {
            documentDirty = false;
            form_save = true;
            document.mutate.save.click();
            saveWait('mutate');
        },
        duplicate: function () {
            if (confirm("<?= $_lang['confirm_duplicate_record'] ?>") === true) {
                documentDirty = false;
                document.location.href = "index.php?id=<?= (isset($_REQUEST['id'])) ? $_REQUEST['id'] : "" ?>&a=111";
            }
        },
        delete: function () {
            if (confirm("<?= $_lang['confirm_delete_module'] ?>") === true) {
                documentDirty = false;
                document.location.href = "index.php?id=" + document.mutate.id.value + "&a=110";
            }
        },
        cancel: function () {
            documentDirty = false;
            document.location.href = 'index.php?a=76&tab=5';
        },
        run: function () {
            document.location.href = "index.php?id=<?= (isset($_REQUEST['id'])) ? $_REQUEST['id'] : "" ?>&a=112";
        }
    };

    function setTextWrap(ctrl, b) {
        if (!ctrl) return;
        ctrl.wrap = (b) ? "soft" : "off";
    }

    document.addEventListener('DOMContentLoaded', function () {
        var h1help = document.querySelector('h1 > .help');
        h1help.onclick = function () {
            document.querySelector('.element-edit-message').classList.toggle('show')
        }
    });

</script>

<form name="mutate" method="post" action="index.php" id="mutate" class="module">
    <?php
    // invoke OnModFormPrerender event
    $evtOut = $modx->invokeEvent('OnModFormPrerender', ['id' => $id]);
    if (is_array($evtOut)) {
        echo implode('', $evtOut);
    }

    // Prepare internal params & info-tab via parseDocBlock
    $modulecode = isset($content['modulecode']) ? $content['modulecode'] : '';
    $docBlock = $modx->parseDocBlockFromString($modulecode);
    $docBlockList = $modx->convertDocBlockIntoList($docBlock);
    $internal = [];
    ?>
    <input type="hidden" name="a" value="109">
    <input type="hidden" name="id" value="<?= (isset($content['id'])) ? $content['id'] : "" ?>">
    <input type="hidden" name="mode" value="<?= $modx->getManagerApi()->action ?>">

    <h1>
        <i class="<?= ((isset($content['icon']) && $content['icon'] != '') ? $content['icon'] : $_style['icon_module']) ?>"></i><?= (isset($content['name']) ? $content['name'] . '<small>(' . $content['id'] . ')</small>' : $_lang['new_module']) ?>
        <i class="<?= $_style['icon_question_circle'] ?> help"></i>
    </h1>

    <?= ManagerTheme::getStyle('actionbuttons.dynamic.element') ?>

    <div class="container element-edit-message">
        <div class="alert alert-info"><?= $_lang['module_msg'] ?></div>
    </div>

    <div class="tab-pane" id="modulePane">
        <script type="text/javascript">
            tp = new WebFXTabPane(document.getElementById("modulePane"), <?= ($modx->getConfig('remember_last_tab') === true ? 'true' : 'false') ?>);
        </script>

        <!-- General -->
        <div class="tab-page" id="tabModule">
            <h2 class="tab"><?= $_lang['settings_general'] ?></h2>
            <script type="text/javascript">tp.addTabPage(document.getElementById("tabModule"));</script>
            <div class="container container-body">
                <div class="form-group">
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['module_name'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <div class="form-control-name clearfix">
                                <?php
                                if (!isset($content['name'])) $content['name'] = '';
                                ?>
                                <input name="name" type="text" maxlength="100"
                                       value="<?= $modx->getPhpCompat()->htmlspecialchars($content['name']) ?>"
                                       class="form-control form-control-lg" onchange="documentDirty=true;"/>
                                <?php if ($modx->hasPermission('save_role')): ?>
                                    <label class="custom-control"
                                           title="<?= $_lang['lock_module'] . "\n" . $_lang['lock_module_msg'] ?>"
                                           tooltip>
                                        <input name="locked"
                                               type="checkbox"<?= ((isset($content['locked']) && $content['locked'] == 1) ? ' checked="checked"' : '') ?> />
                                        <i class="<?= $_style['icon_lock'] ?>"></i>
                                    </label>
                                <?php endif; ?>
                            </div>
                            <script>if (!document.getElementsByName("name")[0].value) document.getElementsByName("name")[0].focus();</script>
                            <small class="form-text text-danger hide" id="savingMessage"></small>
                        </div>
                    </div>
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['module_desc'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <input name="description" type="text" maxlength="255"
                                   value="<?= isset($content['description']) ? $content['description'] : "" ?>"
                                   class="form-control" onchange="documentDirty=true;"/>
                        </div>
                    </div>
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['existing_category'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <select name="categoryid" class="form-control" onchange="documentDirty=true;">
                                <option>&nbsp;</option>
                                <?php
                                include_once(EVO_MANAGER_PATH . 'includes/categories.inc.php');
                                foreach (getCategories() as $n => $v) {
                                    echo "\t\t\t" . '<option value="' . $v['id'] . '"' . ((isset($content['category']) && $content['category'] == $v['id']) ? ' selected="selected"' : '') . '>' . $modx->getPhpCompat()->htmlspecialchars($v['category']) . "</option>\n";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['new_category'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <input name="newcategory" type="text" maxlength="45" value="" class="form-control"
                                   onchange="documentDirty=true;"/>
                        </div>
                    </div>
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['icon'] ?>
                            <small class="text-muted"><?= $_lang["icon_description"] ?></small>
                        </label>
                        <div class="col-md-9 col-lg-10">
                            <div class="input-group">
                                <input type="text" maxlength="255" name="icon"
                                       value="<?= (isset($content['icon']) ? $content['icon'] : "") ?>"
                                       class="form-control" onchange="documentDirty=true;"/>
                            </div>
                        </div>
                    </div>
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2" for="enable_resource"><input name="enable_resource"
                                                                                      id="enable_resource"
                                                                                      title="<?= $_lang['enable_resource'] ?>"
                                                                                      type="checkbox"<?= ((isset($content['enable_resource']) && $content['enable_resource'] == 1) ? ' checked="checked"' : '') ?>
                                                                                      onclick="documentDirty=true;"/>
                            <span title="<?= $_lang['enable_resource'] ?>"><?= $_lang["element"] ?></span></label>
                        <div class="col-md-9 col-lg-10">
                            <input name="resourcefile" type="text" maxlength="255"
                                   value="<?= (isset($content['resourcefile']) ? $content['resourcefile'] : "") ?>"
                                   class="form-control" onchange="documentDirty=true;"/>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-row">
                        <label for="disabled"><input name="disabled" id="disabled" type="checkbox"
                                                     value="on"<?= ((isset($content['disabled']) && $content['disabled'] == 1) ? ' checked="checked"' : '') ?> />
                            <?= ((isset($content['disabled']) && $content['disabled'] == 1) ? '<span class="text-danger">' . $_lang['module_disabled'] . '</span>' : $_lang['module_disabled']) ?>
                        </label>
                    </div>
                    <div class="form-row">
                        <label for="parse_docblock">
                            <input name="parse_docblock" id="parse_docblock" type="checkbox"
                                   value="1"<?= ($modx->getManagerApi()->action == 107 ? ' checked="checked"' : '') ?> /> <?= $_lang['parse_docblock'] ?>
                        </label>
                        <small class="form-text text-muted"><?= $_lang['parse_docblock_msg'] ?></small>
                    </div>
                </div>
            </div>

            <!-- PHP text editor start -->
            <div class="navbar navbar-editor">
                <span><?= $_lang['module_code'] ?></span>
            </div>
            <div class="section-editor clearfix">
                <?php
                $strOut = '';
                if (isset($content['post']) && $content['post'] != '') {
                    $strOut = $content['post'];
                } else {
                    if (isset($content['modulecode']))
                        $strOut = $content['modulecode'];
                }

                ?>
                <textarea dir="ltr" class="phptextarea" name="post" rows="20" wrap="soft"
                          onchange="documentDirty=true;"><?= $modx->getPhpCompat()->htmlspecialchars($strOut) ?></textarea>
            </div>
            <!-- PHP text editor end -->
        </div>

        <!-- Configuration -->
        <div class="tab-page" id="tabConfig">
            <h2 class="tab"><?= $_lang["settings_config"] ?></h2>
            <script type="text/javascript">tp.addTabPage(document.getElementById("tabConfig"));</script>
            <div class="container container-body">
                <div class="form-group">
                    <a href="javascript:;" class="btn btn-primary"
                       onclick="setDefaults(this);return false;"><?= $_lang['set_default_all'] ?></a>
                </div>
                <div id="displayparamrow">
                    <div id="displayparams"></div>
                </div>
            </div>
        </div>

        <!-- Properties -->
        <div class="tab-page" id="tabParams">
            <h2 class="tab"><?= $_lang['settings_properties'] ?></h2>
            <script type="text/javascript">tp.addTabPage(document.getElementById("tabParams"));</script>
            <div class="container container-body">
                <div class="form-group">
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['guid'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <input name="guid" type="text" maxlength="32"
                                   value="<?= ($modx->getManagerApi()->action == 107 ? createGUID() : $content['guid']) ?>"
                                   class="form-control" onchange="documentDirty=true;"/>
                            <small class="form-text text-muted"><?= $_lang['import_params_msg'] ?></small>
                        </div>
                    </div>
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2" for="enable_sharedparams">
                            <input name="enable_sharedparams" id="enable_sharedparams"
                                   type="checkbox"<?= ((isset($content['enable_sharedparams']) && $content['enable_sharedparams'] == 1) ? ' checked="checked"' : '') ?>
                                   onclick="documentDirty=true;"/> <?= $_lang['enable_sharedparams'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <small class="form-text text-muted"><?= $_lang['enable_sharedparams_msg'] ?></small>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <a href="javascript:;" class="btn btn-primary"
                       onclick="tp.pages[1].select();elementProperties.showParameters(this);return false;"><?= $_lang['update_params'] ?></a>
                </div>
            </div>
            <!-- HTML text editor start -->
            <div class="section-editor clearfix">
                <textarea dir="ltr" name="properties" class="phptextarea" rows="20" wrap="soft"
                          onChange="elementProperties.showParameters(this);documentDirty=true;"><?= isset($content['properties']) ? $content['properties'] : '' ?></textarea>
            </div>
            <!-- HTML text editor end -->
        </div>
        <?php if ($modx->getManagerApi()->action == '108'): ?>
            <!-- Dependencies -->
            <div class="tab-page" id="tabDepend">
                <h2 class="tab"><?= $_lang['settings_dependencies'] ?></h2>
                <script type="text/javascript">tp.addTabPage(document.getElementById("tabDepend"));</script>
                <div class="container container-body">
                    <p><?= $_lang['module_viewdepend_msg'] ?></p>
                    <div class="form-group clearfix">
                        <a class="btn btn-primary" href="javascript:;" onclick="loadDependencies();return false;">
                            <i class="<?= $_style["icon_save"] ?>"></i> <?= $_lang['manage_depends'] ?></a>
                    </div>
                    <?php
                    $depobj = \EvolutionCMS\Models\SiteModuleDepobj::query()->select('site_module_depobj.id', \DB::raw('COALESCE(NULL'), 'site_snippets.name',
                        'site_templates.templatename', 'site_tmplvars.name', 'site_htmlsnippets.name', 'site_tmplvars.name', 'site_plugins.name', 'site_content.pagetitle', \DB::raw('NULL) as name'),
                        'site_module_depobj.type')
                        ->leftJoin('site_htmlsnippets', function ($join) {
                            $join->on('site_htmlsnippets.id', '=', 'site_module_depobj.resource');
                            $join->on('site_module_depobj.type', '=', \DB::raw(10));
                        })
                        ->leftJoin('site_content', function ($join) {
                            $join->on('site_content.id', '=', 'site_module_depobj.resource');
                            $join->on('site_module_depobj.type', '=', \DB::raw(20));
                        })
                        ->leftJoin('site_plugins', function ($join) {
                            $join->on('site_plugins.id', '=', 'site_module_depobj.resource');
                            $join->on('site_module_depobj.type', '=', \DB::raw(30));
                        })
                        ->leftJoin('site_snippets', function ($join) {
                            $join->on('site_snippets.id', '=', 'site_module_depobj.resource');
                            $join->on('site_module_depobj.type', '=', \DB::raw(40));
                        })
                        ->leftJoin('site_templates', function ($join) {
                            $join->on('site_templates.id', '=', 'site_module_depobj.resource');
                            $join->on('site_module_depobj.type', '=', \DB::raw(50));
                        })
                        ->leftJoin('site_tmplvars', function ($join) {
                            $join->on('site_tmplvars.id', '=', 'site_module_depobj.resource');
                            $join->on('site_module_depobj.type', '=', \DB::raw(60));
                        })
                        ->where('site_module_depobj.module', $id)
                        ->orderBy('site_module_depobj.type')
                        ->orderBy('name');
                    $grd = new \EvolutionCMS\Support\DataGrid('', $depobj, 0); // set page size to 0 t show all items
                    $grd->noRecordMsg = $_lang['no_records_found'];
                    $grd->prepareResult = ['type' => [10 => 'Chunk', 20 => 'Document', 30 => 'Plugin', 40 => 'Snippet', 50 => 'Template', 60 => 'TV']];
                    $grd->cssClass = 'grid';
                    $grd->columnHeaderClass = 'gridHeader';
                    $grd->itemClass = 'gridItem';
                    $grd->altItemClass = 'gridAltItem';
                    $grd->columns = $_lang['element_name'] . " ," . $_lang['type'];
                    $grd->fields = "name,type";
                    echo $grd->render();
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- access permission -->
        <?php if ($modx->getConfig('use_udperms') && $modx->hasAnyPermissions(['manage_groups', 'manage_module_permissions'])): ?>
        <div class="tab-page" id="tabPermissions">
            <h2 class="tab"><?= $_lang['access_permissions'] ?></h2>
            <script type="text/javascript">tp.addTabPage(document.getElementById("tabPermissions"));</script>
            <div class="container container-body">
                    <?php
                    // fetch user access permissions for the module
                    $groupsarray = \EvolutionCMS\Models\SiteModuleAccess::query()->where('module', $id)->pluck('usergroup')->toArray();
                        ?>
                        <!-- User Group Access Permissions -->
                        <script type="text/javascript">
                            function makePublic(b) {
                                var notPublic = false;
                                var f = document.forms['mutate'];
                                var chkpub = f['chkallgroups'];
                                var chks = f['usrgroups[]'];
                                if (!chks && chkpub) {
                                    chkpub.checked = true;
                                    return false;
                                } else if (!b && chkpub) {
                                    if (!chks.length) notPublic = chks.checked;
                                    else for (i = 0; i < chks.length; i++) if (chks[i].checked) notPublic = true;
                                    chkpub.checked = !notPublic;
                                } else {
                                    if (!chks.length) chks.checked = (b) ? false : chks.checked;
                                    else for (i = 0; i < chks.length; i++) if (b) chks[i].checked = false;
                                    chkpub.checked = true;
                                }
                            }
                        </script>
                        <p><?= $_lang['module_group_access_msg'] ?></p>
                        <?php
                    $chks = '';
                    $membergroupNames = \EvolutionCMS\Models\MembergroupName::query()->select('name', 'id')->get();
                    $notPublic = false;
                    foreach ($membergroupNames->toArray() as $row) {
                        $groupsarray = is_numeric($id) && $id > 0 ? $groupsarray : [];
                        $checked = in_array($row['id'], $groupsarray);
                        if ($checked) {
                            $notPublic = true;
                        }
                        $chks .= '<label><input type="checkbox" name="usrgroups[]" value="' . $row['id'] . '"' . ($checked ? ' checked="checked"' : '') . ' onclick="makePublic(false)" /> ' . $row['name'] . "</label><br />\n";
                    }
                    $chks = '<label><input type="checkbox" name="chkallgroups"' . (!$notPublic ? ' checked="checked"' : '') . ' onclick="makePublic(true)" /><span class="warning"> ' . $_lang['all_usr_groups'] . '</span></label><br />' . "\n" . $chks;

                    echo $chks;
                    ?>
            </div>
        </div>
        <?php endif; ?>
        <!-- docBlock Info -->
        <div class="tab-page" id="tabDocBlock">
            <h2 class="tab"><?= $_lang['information'] ?></h2>
            <script type="text/javascript">tp.addTabPage(document.getElementById("tabDocBlock"));</script>
            <div class="container container-body">
                <?= $docBlockList ?>
            </div>
        </div>

        <input type="submit" name="save" style="display:none;">
        <?php
        // invoke OnModFormRender event
        $evtOut = $modx->invokeEvent('OnModFormRender', ['id' => $id]);
        if (is_array($evtOut)) {
            echo implode('', $evtOut);
        }
        ?>
</form>
<script type="text/javascript">setTimeout('elementProperties.showParameters();', 10);</script>
