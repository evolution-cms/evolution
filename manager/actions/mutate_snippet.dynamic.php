<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

switch ($modx->manager->action) {
    case 22:
        if (!$modx->hasPermission('edit_snippet')) {
            $modx->webAlertAndQuit($_lang["error_no_privileges"]);
        }
        break;
    case 23:
        if (!$modx->hasPermission('new_snippet')) {
            $modx->webAlertAndQuit($_lang["error_no_privileges"]);
        }
        break;
    default:
        $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

// Get table Names (alphabetical)
$tbl_site_module_depobj = $modx->getFullTableName('site_module_depobj');
$tbl_site_modules = $modx->getFullTableName('site_modules');
$tbl_site_snippets = $modx->getFullTableName('site_snippets');

// check to see the snippet editor isn't locked
if ($lockedEl = $modx->elementIsLocked(4, $id)) {
    $modx->webAlertAndQuit(sprintf($_lang['lock_msg'], $lockedEl['username'], $_lang['snippet']));
}
// end check for lock

// Lock snippet for other users to edit
$modx->lockElement(4, $id);

$content = array();
if (isset($_GET['id'])) {
    $rs = $modx->db->select('*', $tbl_site_snippets, "id='{$id}'");
    $content = $modx->db->getRow($rs);
    if (!$content) {
        header("Location: " . MODX_SITE_URL . "index.php?id=" . $site_start);
    }
    $_SESSION['itemname'] = $content['name'];
    if ($content['locked'] == 1 && $_SESSION['mgrRole'] != 1) {
        $modx->webAlertAndQuit($_lang["error_no_privileges"]);
    }
    $content['properties'] = str_replace("&", "&amp;", $content['properties']);
} else if (isset($_REQUEST['itemname'])) {
    $content['name'] = $_REQUEST['itemname'];
} else {
    $_SESSION['itemname'] = $_lang["new_snippet"];
    $content['category'] = (int)$_REQUEST['catid'];
}

if ($modx->manager->hasFormValues()) {
    $modx->manager->loadFormValues();
}

$content = array_merge($content, $_POST);

// Add lock-element JS-Script
$lockElementId = $id;
$lockElementType = 4;
require_once(MODX_MANAGER_PATH . 'includes/active_user_locks.inc.php');
?>
<script type="text/javascript">

    var actions = {
        save: function() {
            documentDirty = false;
            form_save = true;
            document.mutate.save.click();
            saveWait('mutate');
        }, duplicate: function() {
            if (confirm('<?= $_lang['confirm_duplicate_record'] ?>') === true) {
                documentDirty = false;
                document.location.href = "index.php?id=<?= $_REQUEST['id'] ?>&a=98";
            }
        }, delete: function() {
            if (confirm('<?= $_lang['confirm_delete_snippet'] ?>') === true) {
                documentDirty = false;
                document.location.href = 'index.php?id=' + document.mutate.id.value + '&a=25';
            }
        }, cancel: function() {
            documentDirty = false;
            document.location.href = 'index.php?a=76';
        },
    };

    function setTextWrap(ctrl, b)
    {
        if (!ctrl) {
            return;
        }
        ctrl.wrap = (b) ? 'soft' : 'off';
    }

    // Current Params/Configurations
    var currentParams = {};
    var snippetConfig = {};
    var first = true;

    function showParameters(ctrl)
    {
        var c, p, df, cp;
        var ar, label, value, key, dt, defaultVal, tr;

        currentParams = {}; // reset;

        if (ctrl && ctrl.form) {
            f = ctrl.form;
        } else {
            f = document.forms['mutate'];
            if (!f) {
                return;
            }
        }

        tr = document.getElementById('displayparamrow');

        // check if codemirror is used
        var props = typeof myCodeMirrors != 'undefined' && typeof myCodeMirrors['properties'] != 'undefined' ? myCodeMirrors['properties'].getValue() : f.properties.value, t, td, dp, desc;

        // convert old schemed setup parameters
        if (!IsJsonString(props)) {
            dp = props ? props.match(/([^&=]+)=(.*?)(?=&[^&=]+=|$)/g) : ''; // match &paramname=
            if (!dp) {
                tr.style.display = 'none';
            } else {
                for (p = 0; p < dp.length; p++) {
                    dp[p] = (dp[p] + '').replace(/^\s|\s$/, ''); // trim
                    ar = dp[p].match(/(?:[^\=]|==)+/g); // split by =, not by ==
                    key = ar[0];        // param
                    ar = (ar[1] + '').split(';');
                    label = ar[0];	// label
                    dt = ar[1];		// data type
                    value = decode((ar[2]) ? ar[2] : '');

                    // convert values to new json-format
                    if (key && (dt === 'menu' || dt === 'list' || dt === 'list-multi' || dt === 'checkbox' || dt === 'radio')) {
                        defaultVal = decode((ar[4]) ? ar[4] : ar[3]);
                        desc = decode((ar[5]) ? ar[5] : '');
                        currentParams[key] = [];
                        currentParams[key][0] = {'label': label, 'type': dt, 'value': ar[3], 'options': value, 'default': defaultVal, 'desc': desc};
                    } else if (key) {
                        defaultVal = decode((ar[3]) ? ar[3] : ar[2]);
                        desc = decode((ar[4]) ? ar[4] : '');
                        currentParams[key] = [];
                        currentParams[key][0] = {'label': label, 'type': dt, 'value': value, 'default': defaultVal, 'desc': desc};
                    }
                }
            }
        } else {
            currentParams = JSON.parse(props);
        }

        t = '<table width="100%" class="displayparams grid"><thead><tr><td><?= $_lang['parameter'] ?></td><td><?= $_lang['value'] ?></td><td style="text-align:right;white-space:nowrap"><?= $_lang["set_default"] ?> </td></tr></thead>';

        try {
            var type, options, found, info, sd;
            var ll, ls, sets = [], lv, arrValue, split;

            for (var key in currentParams) {

                if (key === 'internal' || currentParams[key][0]['label'] == undefined) {
                    return;
                }

                cp = currentParams[key][0];
                type = cp['type'];
                value = cp['value'];
                defaultVal = cp['default'];
                label = cp['label'] != undefined ? cp['label'] : key;
                desc = cp['desc'] + '';
                options = cp['options'] != undefined ? cp['options'] : '';

                ll = [];
                ls = [];
                if (options.indexOf('==') > -1) {
                    // option-format: label==value||label==value
                    sets = options.split('||');
                    for (i = 0; i < sets.length; i++) {
                        split = sets[i].split('==');
                        ll[i] = split[0];
                        ls[i] = split[1] != undefined ? split[1] : split[0];
                    }
                } else {
                    // option-format: value,value
                    ls = options.split(',');
                    ll = ls;
                }

                key   = key.replace(/\"/g, '&quot;');
                value = value.replace(/\"/g, '&quot;');

                switch (type) {
                    case 'int':
                        c = '<input type="text" name="prop_' + key + '" value="' + value + '" onchange="setParameter(\'' + key + '\',\'' + type + '\',this)" />';
                        break;
                    case 'menu':
                        c = '<select name="prop_' + key + '" onchange="setParameter(\'' + key + '\',\'' + type + '\',this)">';
                        if (currentParams[key] === options) {
                            currentParams[key] = ls[0];
                        } // use first list item as default
                        for (i = 0; i < ls.length; i++) {
                            c += '<option value="' + ls[i] + '"' + ((ls[i] === value) ? ' selected="selected"' : '') + '>' + ll[i] + '</option>';
                        }
                        c += '</select>';
                        break;
                    case 'list':
                        if (currentParams[key] === options) {
                            currentParams[key] = ls[0];
                        } // use first list item as default
                        c = '<select name="prop_' + key + '" onchange="setParameter(\'' + key + '\',\'' + type + '\',this)">';
                        for (i = 0; i < ls.length; i++) {
                            c += '<option value="' + ls[i] + '"' + ((ls[i] === value) ? ' selected="selected"' : '') + '>' + ll[i] + '</option>';
                        }
                        c += '</select>';
                        break;
                    case 'list-multi':
                        // value = typeof ar[3] !== 'undefined' ? (ar[3] + '').replace(/^\s|\s$/, "") : '';
                        arrValue = value.split(',');
                        if (currentParams[key] === options) {
                            currentParams[key] = ls[0];
                        } // use first list item as default
                        c = '<select name="prop_' + key + '" size="' + ls.length + '" multiple="multiple" onchange="setParameter(\'' + key + '\',\'' + type + '\',this)">';
                        for (i = 0; i < ls.length; i++) {
                            if (arrValue.length) {
                                found = false;
                                for (j = 0; j < arrValue.length; j++) {
                                    if (ls[i] === arrValue[j]) {
                                        found = true;
                                    }
                                }
                                if (found === true) {
                                    c += '<option value="' + ls[i] + '" selected="selected">' + ll[i] + '</option>';
                                } else {
                                    c += '<option value="' + ls[i] + '">' + ll[i] + '</option>';
                                }
                            } else {
                                c += '<option value="' + ls[i] + '">' + ll[i] + '</option>';
                            }
                        }
                        c += '</select>';
                        break;
                    case 'checkbox':
                        lv = (value + '').split(',');
                        c = '';
                        for (i = 0; i < ls.length; i++) {
                            c += '<label><input type="checkbox" name="prop_' + key + '[]" value="' + ls[i] + '"' + ((contains(lv, ls[i]) === true) ? ' checked="checked"' : '') + ' onchange="setParameter(\'' + key + '\',\'' + type + '\',this)" /> ' + ll[i] + '</label>&nbsp;';
                        }
                        break;
                    case 'radio':
                        c = '';
                        for (i = 0; i < ls.length; i++) {
                            c += '<label><input type="radio" name="prop_' + key + '" value="' + ls[i] + '"' + ((ls[i] === value) ? ' checked="checked"' : '') + ' onchange="setParameter(\'' + key + '\',\'' + type + '\',this)" /> ' + ll[i] + '</label>&nbsp;';
                        }
                        break;
                    case 'textarea':
                        c = '<textarea name="prop_' + key + '" rows="4" onchange="setParameter(\'' + key + '\',\'' + type + '\',this)">' + value + '</textarea>';
                        break;
                    default:  // string
                        c = '<input type="text" name="prop_' + key + '" value="' + value + '" onchange="setParameter(\'' + key + '\',\'' + type + '\',this)" />';
                        break;
                }

                info = '';
                info += desc ? '<br/><small>' + desc + '</small>' : '';
                sd = defaultVal != undefined ? '<a title="<?= $_lang["set_default"] ?>" href="javascript:;" class="btn btn-primary" onclick="setDefaultParam(\'' + key + '\',1);return false;"><i class="fa fa-refresh"></i></a>' : '';

                t += '<tr><td class="labelCell" width="20%"><span class="paramLabel">' + label + '</span><span class="paramDesc">' + info + '</span></td><td class="inputCell relative" width="74%">' + c + '</td><td style="text-align: center">' + sd + '</td></tr>';
            }

            t += '</table>';

        } catch (e) {
            t = e + '\n\n' + props;
        }

        td = document.getElementById('displayparams');
        td.innerHTML = t;
        tr.style.display = '';

        implodeParameters();
    }

    function setParameter(key, dt, ctrl)
    {
        var v;
        var arrValues, cboxes = [];
        if (!ctrl) {
            return null;
        }
        switch (dt) {
            case 'int':
                ctrl.value = parseInt(ctrl.value);
                if (isNaN(ctrl.value)) {
                    ctrl.value = 0;
                }
                v = ctrl.value;
                break;
            case 'menu':
            case 'list':
                v = ctrl.options[ctrl.selectedIndex].value;
                break;
            case 'list-multi':
                arrValues = [];
                for (var i = 0; i < ctrl.options.length; i++) {
                    if (ctrl.options[i].selected) {
                        arrValues.push(ctrl.options[i].value);
                    }
                }
                v = arrValues.toString();
                break;
            case 'checkbox':
                arrValues = [];
                cboxes = document.getElementsByName(ctrl.name);
                for (var i = 0; i < cboxes.length; i++) {
                    if (cboxes[i].checked) {
                        arrValues.push(cboxes[i].value);
                    }
                }
                v = arrValues.toString();
                break;
            default:
                v = ctrl.value + '';
                break;
        }
        currentParams[key][0]['value'] = v;
        implodeParameters();
    }

    // implode parameters
    function implodeParameters()
    {
        var stringified = JSON.stringify(currentParams, null, 2);
        if (typeof myCodeMirrors !== 'undefined') {
            myCodeMirrors['properties'].setValue(stringified);
        } else {
            f.properties.value = stringified;
        }
        if (first) {
            documentDirty = false;
            first = false;
        }
        ;
    }

    function encode(s)
    {
        s = s + '';
        s = s.replace(/\=/g, '%3D'); // =
        s = s.replace(/\&/g, '%26'); // &
        return s;
    }

    function decode(s)
    {
        s = s + '';
        s = s.replace(/\%3D/g, '='); // =
        s = s.replace(/\%26/g, '&'); // &
        return s;
    }

    /**
     * @return {boolean}
     */
    function IsJsonString(str)
    {
        try {
            JSON.parse(str);
        } catch (e) {
            return false;
        }
        return true;
    }

    function setDefaultParam(key, show)
    {
        if (typeof currentParams[key][0]['default'] !== 'undefined') {
            currentParams[key][0]['value'] = currentParams[key][0]['default'];
            if (show) {
                implodeParameters();
                showParameters();
            }
        }
    }

    function setDefaults()
    {
        var keys = Object.keys(currentParams);
        var last = keys[keys.length - 1], show;
        Object.keys(currentParams).forEach(function(key) {
            show = key === last ? 1 : 0;
            setDefaultParam(key, show);
        });
    }

    function contains(a, obj)
    {
        var i = a.length;
        while (i--) {
            if (a[i] === obj) {
                return true;
            }
        }
        return false;
    }

    document.addEventListener('DOMContentLoaded', function() {
        var h1help = document.querySelector('h1 > .help');
        h1help.onclick = function() {
            document.querySelector('.element-edit-message').classList.toggle('show');
        };
    });

</script>

<form name="mutate" method="post" action="index.php">
    <?php
    // invoke OnSnipFormPrerender event
    $evtOut = $modx->invokeEvent("OnSnipFormPrerender", array("id" => $id));
    if (is_array($evtOut)) {
        echo implode("", $evtOut);
    }

    // Prepare info-tab via parseDocBlock
    $snippetcode = isset($content['snippet']) ? $modx->db->escape($content['snippet']) : '';
    $parsed = $modx->parseDocBlockFromString($snippetcode);
    $docBlockList = $modx->convertDocBlockIntoList($parsed);
    ?>
    <input type="hidden" name="a" value="24">
    <input type="hidden" name="id" value="<?= $content['id'] ?>">
    <input type="hidden" name="mode" value="<?= $modx->manager->action ?>">

    <h1 class="pagetitle">
        <i class="fa fa-code"></i><?= ($content['name'] ? $content['name'] . '<small>(' . $content['id'] . ')</small>' : $_lang['new_snippet']) ?><i class="fa fa-question-circle help"></i>
    </h1>

    <?= $_style['actionbuttons']['dynamic']['element'] ?>

    <div class="container element-edit-message">
        <div class="alert alert-info"><?= $_lang['snippet_msg'] ?></div>
    </div>

    <div class="tab-pane" id="snipetPane">
        <script type="text/javascript">
            tpSnippet = new WebFXTabPane(document.getElementById('snipetPane'), <?= ($modx->config['remember_last_tab'] == 1 ? 'true' : 'false') ?> );
        </script>

        <!-- General -->
        <div class="tab-page" id="tabSnippet">
            <h2 class="tab"><?= $_lang['settings_general'] ?></h2>
            <script type="text/javascript">tpSnippet.addTabPage(document.getElementById('tabSnippet'));</script>
            <div class="container container-body">
                <div class="form-group">
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['snippet_name'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <div class="form-control-name clearfix">
                                <input name="name" type="text" maxlength="100" value="<?= $modx->htmlspecialchars($content['name']) ?>" class="form-control form-control-lg" onchange="documentDirty=true;" />
                                <?php if ($modx->hasPermission('save_role')): ?>
                                    <label class="custom-control" title="<?= $_lang['lock_snippet'] . "\n" . $_lang['lock_snippet_msg'] ?>" tooltip>
                                        <input name="locked" type="checkbox"<?= ($content['locked'] == 1 ? ' checked="checked"' : '') ?> />
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
                        <label class="col-md-3 col-lg-2"><?= $_lang['snippet_desc'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <input name="description" type="text" maxlength="255" value="<?= $content['description'] ?>" class="form-control" onchange="documentDirty=true;" />
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
                                    echo '<option value="' . $v['id'] . '"' . ($content['category'] == $v['id'] ? ' selected="selected"' : '') . '>' . $modx->htmlspecialchars($v['category']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['new_category'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <input name="newcategory" type="text" maxlength="45" value="" class="form-control" onchange="documentDirty=true;" />
                        </div>
                    </div>
                </div>
                <?php if ($modx->hasPermission('save_role')): ?>
                    <div class="form-group">
                        <?php if ($_SESSION['mgrRole'] == 1): ?>
                            <div class="form-row">
                                <label><input name="disabled" type="checkbox" value="on"<?= ($content['disabled'] == 1 ? ' checked="checked"' : '') ?> /> <?= ($content['disabled'] == 1 ? "<span class='text-danger'>" . $_lang['disabled'] . "</span>" : $_lang['disabled']) ?></label>
                            </div>
                        <?php endif; ?>
                        <div class="form-row">
                            <label>
                                <input name="parse_docblock" type="checkbox"<?= ($modx->manager->action == 23 ? ' checked="checked"' : '') ?> value="1" /> <?= $_lang['parse_docblock'] ?></label>
                            <small class="form-text text-muted"><?= $_lang['parse_docblock_msg'] ?></small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PHP text editor start -->
            <div class="navbar navbar-editor">
                <span><?= $_lang['snippet_code'] ?></span>
            </div>
            <div class="section-editor clearfix">
                <textarea dir="ltr" name="post" class="phptextarea" rows="20" wrap="soft" onchange="documentDirty=true;"><?= (isset($content['post']) ? trim($modx->htmlspecialchars($content['post'])) : "<?php" . "\n" . trim($modx->htmlspecialchars($content['snippet'])) . "\n") ?></textarea>
            </div>
            <!-- PHP text editor end -->
        </div>

        <!-- Config -->
        <div class="tab-page" id="tabConfig">
            <h2 class="tab"><?= $_lang["settings_config"] ?></h2>
            <script type="text/javascript">tpSnippet.addTabPage(document.getElementById('tabConfig'));</script>
            <div class="container container-body">
                <div class="form-group">
                    <a href="javascript:;" class="btn btn-primary" onclick="setDefaults(this);return false;"><?= $_lang['set_default_all'] ?></a>
                </div>
                <div id="displayparamrow">
                    <div id="displayparams"></div>
                </div>
            </div>
        </div>

        <!-- Properties -->
        <div class="tab-page" id="tabProps">
            <h2 class="tab"><?= $_lang['settings_properties'] ?></h2>
            <script type="text/javascript">tpSnippet.addTabPage(document.getElementById('tabProps'));</script>
            <div class="container container-body">
                <div class="form-group">
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['import_params'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <select name="moduleguid" class="form-control" onchange="documentDirty=true;">
                                <option>&nbsp;</option>
                                <?php
                                $ds = $modx->db->select('sm.id,sm.name,sm.guid', "{$tbl_site_modules} AS sm 
								INNER JOIN {$tbl_site_module_depobj} AS smd ON smd.module=sm.id AND smd.type=40 
								INNER JOIN {$tbl_site_snippets} AS ss ON ss.id=smd.resource", "smd.resource='{$id}' AND sm.enable_sharedparams=1", 'sm.name');
                                while ($row = $modx->db->getRow($ds)) {
                                    echo "<option value='" . $row['guid'] . "'" . ($content['moduleguid'] == $row['guid'] ? " selected='selected'" : "") . ">" . $modx->htmlspecialchars($row['name']) . "</option>";
                                }
                                ?>
                            </select>
                            <small class="form-text text-muted"><?= $_lang['import_params_msg'] ?></small>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <a href="javascript:;" class="btn btn-primary" onclick='tpSnippet.pages[1].select();showParameters(this);return false;'><?= $_lang['update_params'] ?></a>
                </div>
            </div>
            <!-- HTML text editor start -->
            <div class="section-editor clearfix">
                <textarea dir="ltr" name="properties" class="phptextarea" rows="20" onChange="showParameters(this);documentDirty=true;"><?= $content['properties'] ?></textarea>
            </div>
            <!-- HTML text editor end -->
        </div>

        <!-- docBlock Info -->
        <div class="tab-page" id="tabDocBlock">
            <h2 class="tab"><?= $_lang['information'] ?></h2>
            <script type="text/javascript">tpSnippet.addTabPage(document.getElementById('tabDocBlock'));</script>
            <div class="container container-body">
                <?= $docBlockList ?>
            </div>
        </div>

        <input type="submit" name="save" style="display:none">
        <?php
        // invoke OnSnipFormRender event
        $evtOut = $modx->invokeEvent("OnSnipFormRender", array("id" => $id));
        if (is_array($evtOut)) {
            echo implode("", $evtOut);
        }
        ?>
</form>

<script type="text/javascript">
    setTimeout('showParameters();', 10);
</script>
