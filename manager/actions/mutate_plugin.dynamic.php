<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

switch ($modx->manager->action) {
    case 102:
        if (!$modx->hasPermission('edit_plugin')) {
            $modx->webAlertAndQuit($_lang["error_no_privileges"]);
        }
        break;
    case 101:
        if (!$modx->hasPermission('new_plugin')) {
            $modx->webAlertAndQuit($_lang["error_no_privileges"]);
        }
        break;
    default:
        $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

$tbl_site_plugins = $modx->getFullTableName('site_plugins');
$tbl_site_plugin_events = $modx->getFullTableName('site_plugin_events');
$tbl_system_eventnames = $modx->getFullTableName('system_eventnames');

// check to see the plugin isn't locked
if ($lockedEl = $modx->elementIsLocked(5, $id)) {
    $modx->webAlertAndQuit(sprintf($_lang['lock_msg'], $lockedEl['username'], $_lang['plugin']));
}
// end check for lock

// Lock plugin for other users to edit
$modx->lockElement(5, $id);

if (isset($_GET['id'])) {
    $rs = $modx->db->select('*', $tbl_site_plugins, "id='{$id}'");
    $content = $modx->db->getRow($rs);
    if (!$content) {
        header("Location: {$modx->config['site_url']}");
    }
    $_SESSION['itemname'] = $content['name'];
    if ($content['locked'] == 1 && $modx->hasPermission('save_role') != 1) {
        $modx->webAlertAndQuit($_lang["error_no_privileges"]);
    }
    $content['properties'] = str_replace("&", "&amp;", $content['properties']);
} else {
    $_SESSION['itemname'] = $_lang["new_plugin"];
    $content['category'] = (int)$_REQUEST['catid'];
}

if ($modx->manager->hasFormValues()) {
    $modx->manager->loadFormValues();
}

$content = array_merge($content, $_POST);

// Add lock-element JS-Script
$lockElementId = $id;
$lockElementType = 5;
require_once(MODX_MANAGER_PATH . 'includes/active_user_locks.inc.php');

/**
 * @param bool $cond
 * @return string
 */
function bold($cond = false)
{
    return ($cond !== false) ? ' style="background-color:#777;color:#fff;"' : '';
}

?>
<script language="JavaScript">

    var actions = {
        save: function() {
            documentDirty = false;
            form_save = true;
            document.mutate.save.click();
            saveWait('mutate');
        }, duplicate: function() {
            if (confirm('<?= $_lang['confirm_duplicate_record'] ?>') === true) {
                documentDirty = false;
                document.location.href = "index.php?id=<?= $_REQUEST['id'] ?>&a=105";
            }
        }, delete: function() {
            if (confirm('<?= $_lang['confirm_delete_plugin'] ?>') === true) {
                documentDirty = false;
                document.location.href = 'index.php?id=' + document.mutate.id.value + '&a=104';
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
                    dt = ar[1];	    // data type
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

            createAssignEventsButton();

        } catch (e) {
            t = e + '\n\n' + props;
        }

        td = document.getElementById('displayparams');
        td.innerHTML = t;
        tr.style.display = '';
        if(JSON.stringify(currentParams) === '{}')return;


        implodeParameters();
    }

    function setParameter(key, dt, ctrl)
    {
        var v, arrValues, cboxes = [];
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
        if (typeof myCodeMirrors != 'undefined') {
            myCodeMirrors['properties'].setValue(stringified);
        } else {
            f.properties.value = stringified;
        }
        if (first) {
            documentDirty = false;
            first = false;
        }
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

    function getEventsList()
    {
        var cboxes = document.getElementsByName('sysevents[]');
        var len = cboxes.length;
        var s = [];
        for (var i = 0; i < len; i++) {
            if (cboxes[i].checked) {
                s.push(cboxes[i].id);
            }
        }
        return s.join();
    }

    function createAssignEventsButton()
    {
        if (document.getElementById('assignEvents') === null) {
            var button = document.createElement('div');
            button.setAttribute('id', 'assignEvents');
            button.className = 'container container-body';
            button.innerHTML = '<a class="btn btn-primary" href="javascript:;" onclick="assignEvents();return false;"><?= $_lang["set_automatic"] ?></a>';
            var tab = document.getElementById('tabEvents');
            tab.insertBefore(button, tab.firstChild);
        }
    }

    function assignEvents()
    {
        // remove all events first
        var sysevents = document.getElementsByName('sysevents[]');
        for (var i = 0; i < sysevents.length; i++) {
            sysevents[i].checked = false;
        }
        // set events
        var events = internal[0]['events'];
        events = events.split(',');
        for (var i = 0; i < events.length; i++) {
            document.getElementById(events[i]).checked = true;
        }
    }

    function setDefaultParam(key, show)
    {
        if (typeof currentParams[key][0]['default'] != 'undefined') {
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
    <?php
    // invoke OnPluginFormPrerender event
    $evtOut = $modx->invokeEvent("OnPluginFormPrerender", array("id" => $id));
    if (is_array($evtOut)) {
        echo implode("", $evtOut);
    }

    // Prepare internal params & info-tab via parseDocBlock
    $plugincode = isset($content['plugincode']) ? $modx->db->escape($content['plugincode']) : '';
    $parsed = $modx->parseDocBlockFromString($plugincode);
    $docBlockList = $modx->convertDocBlockIntoList($parsed);
    $internal = array();
    $internal[0]['events'] = isset($parsed['events']) ? $parsed['events'] : '';
    ?>
    var internal = <?= json_encode($internal) ?>;

    document.addEventListener('DOMContentLoaded', function() {
        var h1help = document.querySelector('h1 > .help');
        h1help.onclick = function() {
            document.querySelector('.element-edit-message').classList.toggle('show');
        };
    });

</script>

<form name="mutate" method="post" action="index.php" enctype="multipart/form-data">
    <input type="hidden" name="a" value="103">
    <input type="hidden" name="id" value="<?= $content['id'] ?>">
    <input type="hidden" name="mode" value="<?= $modx->manager->action ?>">

    <h1>
        <i class="fa fa-plug"></i><?= ($content['name'] ? $content['name'] . '<small>(' . $content['id'] . ')</small>' : $_lang['new_plugin']) ?><i class="fa fa-question-circle help"></i>
    </h1>

    <?= $_style['actionbuttons']['dynamic']['element'] ?>

    <div class="container element-edit-message">
        <div class="alert alert-info"><?= $_lang['plugin_msg'] ?></div>
    </div>

    <div class="tab-pane" id="pluginPane">
        <script type="text/javascript">
            tpSnippet = new WebFXTabPane(document.getElementById('pluginPane'), <?= ($modx->config['remember_last_tab'] == 1 ? 'true' : 'false') ?> );
        </script>

        <!-- General -->
        <div class="tab-page" id="tabPlugin">
            <h2 class="tab"><?= $_lang["settings_general"] ?></h2>
            <script type="text/javascript">tpSnippet.addTabPage(document.getElementById('tabPlugin'));</script>
            <div class="container container-body">
                <div class="form-group">
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['plugin_name'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <div class="form-control-name clearfix">
                                <input name="name" type="text" maxlength="100" value="<?= $modx->htmlspecialchars($content['name']) ?>" class="form-control form-control-lg" onchange="documentDirty=true;" />
                                <?php if ($modx->hasPermission('save_role')): ?>
                                <label class="custom-control" title="<?= $_lang['lock_plugin'] . "\n" . $_lang['lock_plugin_msg'] ?>" tooltip>
                                    <input name="locked" type="checkbox" value="on"<?= ($content['locked'] == 1 ? ' checked="checked"' : '') ?> />
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
                        <label class="col-md-3 col-lg-2"><?= $_lang['plugin_desc'] ?></label>
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
                                    echo '<option value="' . $v['id'] . '"' . ($content["category"] == $v["id"] ? ' selected="selected"' : '') . '>' . $modx->htmlspecialchars($v["category"]) . "</option>";
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
                    <div class="form-row">
                        <label><input name="disabled" type="checkbox" value="on"<?= ($content['disabled'] == 1 ? ' checked="checked"' : '') ?> /> <?= ($content['disabled'] == 1 ? "<span class='text-danger'>" . $_lang['plugin_disabled'] . "</span>" : $_lang['plugin_disabled']) ?></label>
                    </div>
                    <div class="form-row">
                        <label>
                            <input name="parse_docblock" type="checkbox"<?= ($modx->manager->action == 101 ? ' checked="checked"' : '') ?> value="1" /> <?= $_lang['parse_docblock'] ?></label>
                        <small class="form-text text-muted"><?= $_lang['parse_docblock_msg'] ?></small>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- PHP text editor start -->
            <div class="navbar navbar-editor">
                <span><?= $_lang['plugin_code'] ?></span>
            </div>
            <div class="section-editor clearfix">
                <textarea dir="ltr" name="post" class="phptextarea" rows="20" wrap="soft" onchange="documentDirty=true;"><?= (isset($content['post']) ? $modx->htmlspecialchars($content['post']) : $modx->htmlspecialchars($content['plugincode'])) ?></textarea>
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
            <h2 class="tab"><?= $_lang["settings_properties"] ?></h2>
            <script type="text/javascript">tpSnippet.addTabPage(document.getElementById('tabProps'));</script>
            <div class="container container-body">
                <div class="form-group">
                    <div class="row form-row">
                        <label class="col-md-3 col-lg-2"><?= $_lang['import_params'] ?></label>
                        <div class="col-md-9 col-lg-10">
                            <select name="moduleguid" class="form-control" onchange="documentDirty=true;">
                                <option>&nbsp;</option>
                                <?php
                                $ds = $modx->db->select('sm.id,sm.name,sm.guid', $modx->getFullTableName("site_modules") . " sm 
								INNER JOIN " . $modx->getFullTableName("site_module_depobj") . " smd ON smd.module=sm.id AND smd.type=30
								INNER JOIN " . $modx->getFullTableName("site_plugins") . " sp ON sp.id=smd.resource", "smd.resource='{$id}' AND sm.enable_sharedparams='1'", 'sm.name');
                                while ($row = $modx->db->getRow($ds)) {
                                    echo "<option value='" . $row['guid'] . "'" . ($content["moduleguid"] == $row["guid"] ? " selected='selected'" : "") . ">" . $modx->htmlspecialchars($row["name"]) . "</option>";
                                }
                                ?>
                            </select>
                            <small class="form-text text-muted"><?= $_lang['import_params_msg'] ?></small>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <a href="javascript:;" class="btn btn-primary" onclick="tpSnippet.pages[1].select();showParameters(this);return false;"><?= $_lang['update_params'] ?></a>
                </div>
            </div>
            <!-- HTML text editor start -->
            <div class="section-editor clearfix">
                <textarea dir="ltr" name="properties" class="phptextarea" rows="20" onChange="showParameters(this);documentDirty=true;"><?= $content['properties'] ?></textarea>
            </div>
            <!-- HTML text editor end -->
        </div>

        <!-- System Events -->
        <div class="tab-page" id="tabEvents">
            <h2 class="tab"><?= $_lang["settings_events"] ?></h2>
            <script type="text/javascript">tpSnippet.addTabPage(document.getElementById('tabEvents'));</script>
            <div class="container container-body">
                <p><?= $_lang['plugin_event_msg'] ?></p>
                <?php

                // get selected events
                if (is_numeric($id) && $id > 0) {
                    $rs = $modx->db->select('evtid', $tbl_site_plugin_events, "pluginid='{$id}'");
                    $evts = $modx->db->getColumn('evtid', $rs);
                } else {
                    if (isset($content['sysevents']) && is_array($content['sysevents'])) {
                        $evts = $content['sysevents'];
                    } else {
                        $evts = array();
                    }
                }

                // display system events
                $evtnames = array();
                $services = array(
                    "Parser Service Events",
                    "Manager Access Events",
                    "Web Access Service Events",
                    "Cache Service Events",
                    "Template Service Events",
                    "User Defined Events"
                );
                $rs = $modx->db->select('*', $tbl_system_eventnames, '', 'service DESC, groupname, name');
                $limit = $modx->db->getRecordCount($rs);
                if ($limit == 0) {
                    echo "";
                } else {
                    while ($row = $modx->db->getRow($rs)) {
                        // display records
                        if ($srv != $row['service']) {
                            $srv = $row['service'];
                            if (count($evtnames) > 0) {
                                echoEventRows($evtnames);
                            }
                            echo '<hr class="clear">';
                            echo '<div class="form-group"><b>' . $services[$srv - 1] . '</b></div>';
                        }
                        // display group name
                        if ($grp != $row['groupname']) {
                            $grp = $row['groupname'];
                            if (count($evtnames) > 0) {
                                echoEventRows($evtnames);
                            }
                            echo '<hr class="clear">';
                            echo '<div class="form-group"><b>' . $row['groupname'] . '</b></div>';
                        }
                        $evtnames[] = '<input name="sysevents[]" id="' . $row['name'] . '" type="checkbox" ' . (in_array($row['id'], $evts) ? ' checked="checked" ' : '') . 'class="inputBox" value="' . $row['id'] . '" /> <label for="' . $row['name'] . '" ' . bold(in_array($row['id'], $evts)) . '> ' . $row['name'] . '</label>' . "\n";
                        if (count($evtnames) == 2) {
                            echoEventRows($evtnames);
                        }
                    }
                }
                if (count($evtnames) > 0) {
                    echoEventRows($evtnames);
                }

                function echoEventRows(&$evtnames)
                {
                    echo '<div class="row form-row"><div class="col-sm-6 col-md-4 col-lg-3">' . implode('</div><div class="col-sm-6 col-md-4 col-lg-3">', $evtnames) . '</div></div>';
                    $evtnames = array();
                }

                ?>
            </div>
        </div>

        <!-- docBlock Info -->
        <div class="tab-page" id="tabDocBlock">
            <h2 class="tab"><?= $_lang['information'] ?></h2>
            <script type="text/javascript">tpSnippet.addTabPage(document.getElementById('tabDocBlock'));</script>
            <div class="container container-body">
                <?= $docBlockList ?>
            </div>
        </div>

    </div>
    <input type="submit" name="save" style="display:none">

    <?php
    // invoke OnPluginFormRender event
    $evtOut = $modx->invokeEvent("OnPluginFormRender", array("id" => $id));
    if (is_array($evtOut)) {
        echo implode("", $evtOut);
    }
    ?>
</form>
<script type="text/javascript">
    setTimeout('showParameters()', 10);
</script>
