<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!EvolutionCMS()->hasPermission('edit_module')) {
    EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

$mxla = ManagerTheme::getLang();

/**
 * Resource Selector
 * Created by Raymond Irving May, 2005
 *
 * Selects a resource and returns the id values to the window.opener["callback"]() function as an array.
 * The name of the callback function is passed via the url as &cb
 */

// Every value below is echoed back into this page - $cb and $rt land inside a <script> block -
// so each one is constrained at the point it is read rather than at each of its sinks.

// get name of callback function; this is looked up on window.opener, so a JS identifier path is
// the only shape it can legitimately take
$cb = preg_replace('/[^A-Za-z0-9_$.]/', '', (string)get_by_key($_REQUEST, 'cb', '', 'is_scalar'));

// get resource type
$rt = preg_replace('/[^a-z0-9_]/', '', strtolower((string)get_by_key($_REQUEST, 'rt', '', 'is_scalar')));

// get selection method: s - single (default), m - multiple
$sm = strtolower((string)get_by_key($_REQUEST, 'sm', '', 'is_scalar')) === 'm' ? 'm' : 's';

// get search string
$query = (string)get_by_key($_REQUEST, 'search', '', 'is_scalar');

// list mode is a flag, never free text
$listmode = (int)get_by_key($_REQUEST, 'listmode', 0, 'is_scalar');

// select SQL
switch ($rt) {
    case "snip":
        $title = $_lang["snippet"];
        $ds = \EvolutionCMS\Models\SiteSnippet::query()->select('id', 'name', 'description')->orderBy('name');
        if(isset($query) && $query != ''){
            $ds = $ds->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%')
                    ->orWhere('description', 'LIKE', '%' . $query . '%');
            });
        }
        break;

    case "tpl":
        $title = $_lang["template"];
        $ds = \EvolutionCMS\Models\SiteTemplate::query()->select('id', 'templatename as name', 'description')->orderBy('name');
        break;

    case("tv"):
        $title = $_lang["tv"];
        $ds = \EvolutionCMS\Models\SiteTmplvar::query()->select('id', 'name', 'description')->orderBy('name');
        break;

    case("chunk"):
        $title = $_lang["chunk"];
        $ds = \EvolutionCMS\Models\SiteHtmlsnippet::query()->select('id', 'name', 'description')->orderBy('name');
        break;

    case("plug"):
        $title = $_lang["plugin"];
        $ds = \EvolutionCMS\Models\SitePlugin::query()->select('id', 'name', 'description')->orderBy('name');
        break;

    case("doc"):
        $title = $_lang["resource"];
        $ds = \EvolutionCMS\Models\SiteContent::query()->select('id', 'pagetitle as name', 'longtitle as description')->orderBy('name');
        break;

}

if(isset($query) && $query != ''){
    $ds = $ds->where(function ($q) use ($query) {
        $q->where('name', 'LIKE', '%' . $query . '%')
            ->orWhere('description', 'LIKE', '%' . $query . '%');
    });
}
include_once EVO_MANAGER_PATH . "includes/header.inc.php";
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
    <?= $title . " - " . $_lang['element_selector_title'] ?><i class="<?= $_style['icon_question_circle'] ?> help"></i>
</h1>

<div id="actions">
    <div class="btn-group">
        <a id="Button1" class="btn btn-success" href="javascript:;" onclick="saveSelection()"><i class="<?= $_style['icon_add'] ?>"></i> <span><?= $_lang['insert'] ?></span></a>
        <a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="window.close()"><i class="<?= $_style['icon_cancel'] ?>"></i> <span><?= $_lang['cancel'] ?></span></a>
    </div>
</div>

<div class="container element-edit-message">
    <div class="alert alert-info"><?= $_lang['element_selector_msg'] ?></div>
</div>

<form name="selector" method="get">
    <input type="hidden" name="id" value="<?= (int)$id ?>" />
    <input type="hidden" name="a" value="<?= EvolutionCMS()->getManagerApi()->action ?>" />
    <input type="hidden" name="listmode" value="<?= $listmode ?>" />
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
                        <input class="form-control form-control-sm" name="search" type="text" value="<?= entities($query, EvolutionCMS()->getConfig('modx_charset')) ?>" placeholder="<?= $_lang["search"] ?>" />
                        <div class="input-group-append">
                            <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["search"] ?>" onclick="searchResource();return false;"><i class="<?= $_style['icon_search'] ?>"></i></a>
                            <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["reset"] ?>" onclick="resetSearch();return false;"><i class="<?= $_style['icon_refresh'] ?>"></i></a>
                            <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["list_mode"] ?>" onclick="changeListMode();return false;"><i class="<?= $_style['icon_table'] ?>"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="table-responsive">
                    <?php
                    $grd = new \EvolutionCMS\Support\DataGrid('', $ds, 0); // set page size to 0 t show all items
                    $grd->noRecordMsg = $_lang["no_records_found"];
                    $grd->cssClass = "table data nowrap";
                    $grd->columnHeaderClass = "tableHeader";
                    $grd->itemClass = "tableItem";
                    $grd->altItemClass = "tableAltItem";
                    $grd->columns = $_lang["name"] . " ," . $_lang["description"];
                    $grd->colTypes = "template:<input type='" . ($sm == 'm' ? 'checkbox' : 'radio') . "' name='id[]' value='[+id+]' onclick='setCheckbox(this);'> [+value+]";
                    $grd->colWidths = "45%";
                    $grd->fields = "name,description";
                    if ($listmode === 1) {
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
