<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!($modx->hasPermission('new_module') || $modx->hasPermission('edit_module') || $modx->hasPermission('exec_module'))) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

// initialize page view state - the $_PAGE object
$modx->getManagerApi()->initPageViewState();

// get and save search string
if ($_REQUEST['op'] == 'reset') {
    $query = '';
    $_PAGE['vs']['search'] = '';
} else {
    $query = isset($_REQUEST['search']) ? $_REQUEST['search'] : $_PAGE['vs']['search'];
    $sqlQuery = $modx->getDatabase()->escape($query);
    $_PAGE['vs']['search'] = $query;
}

// get & save listmode
$listmode = isset($_REQUEST['listmode']) ? $_REQUEST['listmode'] : $_PAGE['vs']['lm'];
$_PAGE['vs']['lm'] = $listmode;


// context menu
$cm = new \EvolutionCMS\Support\ContextMenu("cntxm", 150);
$cm->addItem($_lang["run_module"], "js:menuAction(1)", $_style['actions_run'], (!$modx->hasPermission('exec_module') ? 1 : 0));
if ($modx->hasPermission('edit_module') || $modx->hasPermission('new_module') || $modx->hasPermission('delete_module')) {
    $cm->addSeparator();
}
$cm->addItem($_lang["edit"], "js:menuAction(2)", $_style['actions_edit'], (!$modx->hasPermission('edit_module') ? 1 : 0));
$cm->addItem($_lang["duplicate"], "js:menuAction(3)", $_style['actions_duplicate'], (!$modx->hasPermission('new_module') ? 1 : 0));
$cm->addItem($_lang["delete"], "js:menuAction(4)", $_style['actions_delete'], (!$modx->hasPermission('delete_module') ? 1 : 0));
echo $cm->render();

?>
<script type="text/javascript">
    var selectedItem;
    var contextm = <?= $cm->getClientScriptObject() ?>;

    function showContentMenu(id, e)
    {
        selectedItem = id;
        contextm.style.left = (e.pageX || (e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft)))<?= ($modx_textdir ? '-190' : '') ?>+ 'px'; //offset menu if RTL is selected
        contextm.style.top = (e.pageY || (e.clientY + (document.documentElement.scrollTop || document.body.scrollTop))) + 'px';
        contextm.style.visibility = 'visible';
        e.cancelBubble = true;
        return false;
    };

    function menuAction(a)
    {
        var id = selectedItem;
        switch (a) {
            case 1:		// run module
                dontShowWorker = true; // prevent worker from being displayed
                window.location.href = 'index.php?a=112&id=' + id;
                break;
            case 2:		// edit
                window.location.href = 'index.php?a=108&id=' + id;
                break;
            case 3:		// duplicate
                if (confirm('<?= $_lang['confirm_duplicate_record'] ?>') === true) {
                    window.location.href = 'index.php?a=111&id=' + id;
                }
                break;
            case 4:		// delete
                if (confirm('<?= $_lang['confirm_delete_module'] ?>') === true) {
                    window.location.href = 'index.php?a=110&id=' + id;
                }
                break;
        }
    }

    document.addEventListener('click', function() {
        contextm.style.visibility = 'hidden';
    });

    var actions = {
        new: function() {
            document.location.href = 'index.php?a=107';
        },
    };

    document.addEventListener('DOMContentLoaded', function() {
        var h1help = document.querySelector('h1 > .help');
        h1help.onclick = function() {
            document.querySelector('.element-edit-message').classList.toggle('show');
        };
    });

</script>

<h1>
    <i class="fa fa-cubes"></i><?= $_lang['module_management'] ?><i class="fa fa-question-circle help"></i>
</h1>

<?= $_style['actionbuttons']['dynamic']['newmodule'] ?>

<div class="container element-edit-message">
    <div class="alert alert-info"><?= $_lang['module_management_msg'] ?></div>
</div>

<div class="tab-page">
    <div class="table-responsive">
        <?php
        if ($_SESSION['mgrRole'] != 1 && !empty($modx->config['use_udperms'])) {
            $rs = $modx->getDatabase()->query('SELECT DISTINCT sm.id, sm.name, sm.description, mg.member, IF(disabled,"' . $_lang['yes'] . '","-") as disabled, IF(sm.icon<>"",sm.icon,"' . $_style['icons_modules'] . '") as icon
				FROM ' . $modx->getDatabase()->getFullTableName('site_modules') . ' AS sm
				LEFT JOIN ' . $modx->getDatabase()->getFullTableName('site_module_access') . ' AS sma ON sma.module = sm.id
				LEFT JOIN ' . $modx->getDatabase()->getFullTableName('member_groups') . ' AS mg ON sma.usergroup = mg.user_group
                WHERE (mg.member IS NULL OR mg.member = ' . $modx->getLoginUserID() . ') AND sm.disabled != 1 AND sm.locked != 1
                ORDER BY sm.name');
            if ($modx->hasPermission('edit_module')) {
                $title = "<a href='index.php?a=108&id=[+id+]' title='" . $_lang["module_edit_click_title"] . "'>[+value+]</a>";
            } else if ($modx->hasPermission('exec_module')) {
                $title = "<a href='index.php?a=112&id=[+id+]' title='" . $_lang["module_edit_click_title"] . "'>[+value+]</a>";
            } else {
                $title = '[+value+]';
            }
        } else {
            $rs = $modx->getDatabase()->select("id, name, description, IF(locked,'{$_lang['yes']}','-') as locked, IF(disabled,'{$_lang['yes']}','-') as disabled, IF(icon<>'',icon,'{$_style['icons_module']}') as icon", $modx->getDatabase()->getFullTableName("site_modules"), (!empty($sqlQuery) ? "(name LIKE '%{$sqlQuery}%') OR (description LIKE '%{$sqlQuery}%')" : ""), "name");
            $title = "<a href='index.php?a=108&id=[+id+]' title='" . $_lang["module_edit_click_title"] . "'>[+value+]</a>";
        }
        $grd = new \EvolutionCMS\Support\DataGrid('', $rs, $number_of_results); // set page size to 0 t show all items
        $grd->noRecordMsg = $_lang["no_records_found"];
        $grd->cssClass = "table data";
        $grd->columnHeaderClass = "tableHeader";
        $grd->itemClass = "tableItem";
        $grd->altItemClass = "tableAltItem";
        $grd->fields = "icon,name,description,locked,disabled";
        $grd->columns = $_lang["icon"] . " ," . $_lang["name"] . " ," . $_lang["description"] . " ," . $_lang["locked"] . " ," . $_lang["disabled"];
        $grd->colWidths = "34,,,60,60";
        $grd->colAligns = "center,,,center,center";
        $grd->colTypes = "template:<a class='tableRowIcon' href='javascript:;' onclick='return showContentMenu([+id+],event);' title='" . $_lang["click_to_context"] . "'><i class='[+value+]'></i></a>||template:" . $title;
        if ($listmode == '1') {
            $grd->pageSize = 0;
        }
        if ($_REQUEST['op'] == 'reset') {
            $grd->pageNumber = 1;
        }
        // render grid
        echo $grd->render();
        ?>
    </div>
</div>
