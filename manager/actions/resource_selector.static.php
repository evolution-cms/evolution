<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('edit_module')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$mxla = $modx_lang_attribute ? $modx_lang_attribute : 'en';

/**
 * Resource Selector
 * Created by Raymond Irving May, 2005
 *
 * Selects a resource and returns the id values to the window.opener["callback"]() function as an array.
 * The name of the callback function is passed via the url as &cb
 */

// get name of callback function
$cb = $_REQUEST['cb'];

// get resource type
$rt = strtolower($_REQUEST['rt']);

// get selection method: s - single (default), m - multiple
$sm = strtolower($_REQUEST['sm']);

// get search string
$query = $_REQUEST['search'];
$sqlQuery = $modx->db->escape($query);

// select SQL
switch ($rt) {
    case "snip":
        $title = $_lang["snippet"];
        $ds = $modx->db->select('id,name,description', $modx->getFullTableName("site_snippets"), ($sqlQuery ? "(name LIKE '%{$sqlQuery}%') OR (description LIKE '%{$sqlQuery}%')" : ""), 'name');
        break;

    case "tpl":
        $title = $_lang["template"];
        $ds = $modx->db->select('id,templatename as name,description', $modx->getFullTableName("site_templates"), ($sqlQuery ? "(templatename LIKE '%{$sqlQuery}%') OR (description LIKE '%{$sqlQuery}%')" : ""), 'templatename');
        break;

    case("tv"):
        $title = $_lang["tv"];
        $ds = $modx->db->select('id,name,description', $modx->getFullTableName("site_tmplvars"), ($sqlQuery ? "(name LIKE '%{$sqlQuery}%') OR (description LIKE '%{$sqlQuery}%')" : ""), 'name');
        break;

    case("chunk"):
        $title = $_lang["chunk"];
        $ds = $modx->db->select('id,name,description', $modx->getFullTableName("site_htmlsnippets"), ($sqlQuery ? "(name LIKE '%{$sqlQuery}%') OR (description LIKE '%{$sqlQuery}%')" : ""), 'name');
        break;

    case("plug"):
        $title = $_lang["plugin"];
        $ds = $modx->db->select('id,name,description', $modx->getFullTableName("site_plugins"), ($sqlQuery ? "(name LIKE '%{$sqlQuery}%') OR (description LIKE '%{$sqlQuery}%')" : ""), 'name');
        break;

    case("doc"):
        $title = $_lang["resource"];
        $ds = $modx->db->select('id,pagetitle as name,longtitle as description', $modx->getFullTableName("site_content"), ($sqlQuery ? "(pagetitle LIKE '%{$sqlQuery}%') OR (longtitle LIKE '%{$sqlQuery}%')" : ""), 'pagetitle');
        break;

}

include_once MODX_MANAGER_PATH . "includes/header.inc.php";
?>
<script language="JavaScript" type="text/javascript">
    function saveSelection()
    {
        var ids = [];
        var ctrl = document.selector['id[]'];
        if (!ctrl.length && ctrl.checked) {
            ids[0] = ctrl.value;
        } else {
            for (i = 0; i < ctrl.length; i++) {
                if (ctrl[i].checked) {
                    ids[ids.length] = ctrl[i].value;
                }
            }
        }
        cb = window.opener["<?= $cb ?>"];
        if (cb) cb("<?= $rt ?>", ids);
        window.close();
    };

    function searchResource()
    {
        document.selector.op.value = "srch";
        document.selector.submit();
    };

    function resetSearch()
    {
        document.selector.search.value = "";
        searchResource()
    }

    function changeListMode()
    {
        var m = parseInt(document.selector.listmode.value) ? 1 : 0;
        if (m) document.selector.listmode.value = 0; else document.selector.listmode.value = 1;
        document.selector.submit();
    };

    // restore checkbox function
    function restoreChkBoxes()
    {
        var i, c, chk;
        var a = window.opener.chkBoxArray;
        var f = document.selector;
        chk = f.elements['id[]'];
        if (!chk.length) chk.checked = !!(a[chk.value]); else {
            for (i = 0; i < chk.length; i++) {
                c = chk[i];
                c.checked = !!(a[c.value]);
            }
        }
    };

    // set checkbox value
    function setCheckbox(chk)
    {
        var a = window.opener.chkBoxArray;
        a[chk.value] = chk.checked;
    };
    // restore checkboxes
    setTimeout("restoreChkBoxes();", 100);


    document.addEventListener('DOMContentLoaded', function() {
        var h1help = document.querySelector('h1 > .help');
        h1help.onclick = function() {
            document.querySelector('.element-edit-message').classList.toggle('show')
        }
    });

</script>

<h1>
    <?= $title . " - " . $_lang['element_selector_title'] ?><i class="fa fa-question-circle help"></i>
</h1>

<div id="actions">
    <div class="btn-group">
        <a id="Button1" class="btn btn-success" href="javascript:;" onclick="saveSelection()"><i class="<?= $_style['actions_add'] ?>"></i> <span><?= $_lang['insert'] ?></span></a>
        <a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="window.close()"><i class="<?= $_style['actions_cancel'] ?>"></i> <span><?= $_lang['cancel'] ?></span></a>
    </div>
</div>

<div class="container element-edit-message">
    <div class="alert alert-info"><?= $_lang['element_selector_msg'] ?></div>
</div>

<form name="selector" method="get">
    <input type="hidden" name="id" value="<?= $id ?>" />
    <input type="hidden" name="a" value="<?= $modx->manager->action ?>" />
    <input type="hidden" name="listmode" value="<?= $_REQUEST['listmode'] ?>" />
    <input type="hidden" name="op" value="" />
    <input type="hidden" name="rt" value="<?= $rt ?>" />
    <input type="hidden" name="rt" value="<?= $rt ?>" />
    <input type="hidden" name="sm" value="<?= $sm ?>" />
    <input type="hidden" name="cb" value="<?= $cb ?>" />

    <div class="tab-page">
        <div class="container container-body">
            <div class="row searchbar form-group">
                <div class="col-sm-12">
                    <div class="input-group float-right w-auto">
                        <input class="form-control form-control-sm" name="search" type="text" value="<?= $query ?>" placeholder="<?= $_lang["search"] ?>" />
                        <div class="input-group-btn">
                            <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["search"] ?>" onclick="searchResource();return false;"><i class="<?= $_style['actions_search'] ?>"></i></a>
                            <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["reset"] ?>" onclick="resetSearch();return false;"><i class="<?= $_style['actions_refresh'] ?>"></i></a>
                            <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["list_mode"] ?>" onclick="changeListMode();return false;"><i class="<?= $_style['actions_table'] ?>"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="table-responsive">
                    <?php
                    include_once MODX_MANAGER_PATH . "includes/controls/datagrid.class.php";
                    $grd = new DataGrid('', $ds, $number_of_results); // set page size to 0 t show all items
                    $grd->noRecordMsg = $_lang["no_records_found"];
                    $grd->cssClass = "table data nowrap";
                    $grd->columnHeaderClass = "tableHeader";
                    $grd->itemClass = "tableItem";
                    $grd->altItemClass = "tableAltItem";
                    $grd->columns = $_lang["name"] . " ," . $_lang["description"];
                    $grd->colTypes = "template:<input type='" . ($sm == 'm' ? 'checkbox' : 'radio') . "' name='id[]' value='[+id+]' onclick='setCheckbox(this);'> [+value+]";
                    $grd->colWidths = "45%";
                    $grd->fields = "name,description";
                    if ($_REQUEST['listmode'] == '1') {
                        $grd->pageSize = 0;
                    }
                    echo $grd->render();
                    ?>
                </div>
            </div>
        </div>
    </div>
</form>
</body>
</html>
