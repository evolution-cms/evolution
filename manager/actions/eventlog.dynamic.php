<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('view_eventlog')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

// Get table Names (alphabetical)
$tbl_event_log = $modx->getFullTableName('event_log');
$tbl_manager_users = $modx->getFullTableName('manager_users');
$tbl_web_users = $modx->getFullTableName('web_users');

// initialize page view state - the $_PAGE object
$modx->manager->initPageViewState();

// get and save search string
if($_REQUEST['op'] == 'reset') {
	$sqlQuery = $query = '';
	$_PAGE['vs']['search'] = '';
} else {
	$sqlQuery = $query = isset($_REQUEST['search']) ? $_REQUEST['search'] : $_PAGE['vs']['search'];
	if(!is_numeric($sqlQuery)) {
		$sqlQuery = $modx->db->escape($query);
	}
	$_PAGE['vs']['search'] = $query;
}

// get & save listmode
$listmode = isset($_REQUEST['listmode']) ? $_REQUEST['listmode'] : $_PAGE['vs']['lm'];
$_PAGE['vs']['lm'] = $listmode;

// context menu
include_once MODX_MANAGER_PATH . "includes/controls/contextmenu.php";
$cm = new ContextMenu("cntxm", 150);
$cm->addItem($_lang['view_log'], "js:menuAction(1)", $_style['actions_preview']);
$cm->addSeparator();
$cm->addItem($_lang['delete'], "js:menuAction(2)", $_style['actions_delete'], (!$modx->hasPermission('delete_eventlog') ? 1 : 0));
echo $cm->render();

?>
<script type="text/javascript">
	function searchResource() {
		document.resource.op.value = "srch";
		document.resource.submit();
	};

	function resetSearch() {
		document.resource.search.value = '';
		document.resource.op.value = "reset";
		document.resource.submit();
	};

	function changeListMode() {
		var m = parseInt(document.resource.listmode.value) ? 1 : 0;
		if(m) document.resource.listmode.value = 0;
		else document.resource.listmode.value = 1;
		document.resource.submit();
	};

	var selectedItem;
	var contextm = <?= $cm->getClientScriptObject()?>;

	function showContentMenu(id, e) {
		selectedItem = id;
		contextm.style.left = (e.pageX || (e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft))) + "px";
		contextm.style.top = (e.pageY || (e.clientY + (document.documentElement.scrollTop || document.body.scrollTop))) + "px";
		contextm.style.visibility = "visible";
		e.cancelBubble = true;
		return false;
	};

	function menuAction(a) {
		var id = selectedItem;
		switch(a) {
			case 1:		// view log details
				window.location.href = 'index.php?a=115&id=' + id;
				break;
			case 2:		// clear log
				window.location.href = 'index.php?a=116&id=' + id;
				break;
		}
	}

	document.addEventListener('click', function() {
		contextm.style.visibility = "hidden";
	});

	document.addEventListener('DOMContentLoaded', function() {
		var h1help = document.querySelector('h1 > .help');
		h1help.onclick = function() {
			document.querySelector('.element-edit-message').classList.toggle('show')
		}
	});
</script>
<form name="resource" method="post">
	<input type="hidden" name="id" value="<?= $id ?>" />
	<input type="hidden" name="listmode" value="<?= $listmode ?>" />
	<input type="hidden" name="op" value="" />

	<h1>
		<?= $_style['page_eventlog'] ?> <?= $_lang['eventlog_viewer'] ?><?= $_style['icons_help'] ?>
	</h1>

	<div class="container element-edit-message">
		<div class="alert alert-info"><?= $_lang['eventlog_msg'] ?></div>
	</div>

	<div class="tab-page">
		<!-- load modules -->
		<div class="container container-body">
            <div class="searchbar form-group">
                <div class="input-group">
                    <div class="input-group-btn">
                        <a class="btn btn-danger btn-sm" href="index.php?a=116&cls=1"><i class="<?= $_style["actions_delete"] ?>"></i> <?= $_lang['clear_log'] ?></a>
                    </div>
                    <input class="form-control form-control-sm float-xs-right" name="search" type="text" value="<?= $query ?>" placeholder="<?= $_lang["search"] ?>" />
                    <div class="input-group-btn">
                        <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["search"] ?>" onclick="searchResource();return false;"><i class="<?= $_style['actions_search'] ?>"></i></a>
                        <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["reset"] ?>" onclick="resetSearch();return false;"><i class="<?= $_style['actions_refresh'] ?>"></i></a>
                        <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["list_mode"] ?>" onclick="changeListMode();return false;"><i class="<?= $_style['actions_table'] ?>"></i></a>
                    </div>
                </div>
            </div>
			<div class="row">
				<div class="table-responsive">
					<?php
					$ds = $modx->db->select("el.id, ELT(el.type , 'text-info {$_style['actions_info']}' , 'text-warning {$_style['actions_triangle']}' , 'text-danger {$_style['actions_error']}' ) as icon, el.createdon, el.source, el.eventid,IFNULL(wu.username,mu.username) as username", "{$tbl_event_log} AS el 
			LEFT JOIN {$tbl_manager_users} AS mu ON mu.id=el.user AND el.usertype=0
			LEFT JOIN {$tbl_web_users} AS wu ON wu.id=el.user AND el.usertype=1", ($sqlQuery ? "" . (is_numeric($sqlQuery) ? "(eventid='{$sqlQuery}') OR " : '') . "(source LIKE '%{$sqlQuery}%') OR (description LIKE '%{$sqlQuery}%')" : ""), "createdon DESC");
					include_once MODX_MANAGER_PATH . "includes/controls/datagrid.class.php";
					$grd = new DataGrid('', $ds, $number_of_results); // set page size to 0 t show all items
					$grd->pagerClass = '';
					$grd->pageClass = 'page-item';
					$grd->selPageClass = 'page-item active';
					$grd->noRecordMsg = $_lang['no_records_found'];
					$grd->cssClass = "table data nowrap eventlog";
					$grd->columnHeaderClass = "tableHeader";
					$grd->itemClass = "tableItem";
					$grd->altItemClass = "tableAltItem";
					$grd->fields = "type,source,createdon,eventid,username";
					$grd->columns = $_lang['type'] . " ," . $_lang['source'] . " ," . $_lang['date'] . " ," . $_lang['event_id'] . " ," . $_lang['sysinfo_userid'];
					$grd->colWidths = "1%,,1%,1%,1%";
					$grd->colAligns = "center,,,center,center";
					$grd->colTypes = "template:<a class='gridRowIcon' href='javascript:;' onclick='return showContentMenu([+id+],event);' title='" . $_lang['click_to_context'] . "'><i class='[+icon+]'></i></a>||template:<a href='index.php?a=115&id=[+id+]' title='" . $_lang['click_to_view_details'] . "'>[+source+]</a>||date: " . $modx->toDateFormat(null, 'formatOnly') . ' %I:%M %p';
					if($listmode == '1') {
						$grd->pageSize = 0;
					}
					if($_REQUEST['op'] == 'reset') {
						$grd->pageNumber = 1;
					}
					// render grid
					echo $grd->render();
					?>
				</div>
			</div>
		</div>
	</div>
</form>
