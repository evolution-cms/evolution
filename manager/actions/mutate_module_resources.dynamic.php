<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

if(!$modx->hasPermission('edit_module')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

// Get table names (alphabetical)
$tbl_active_users = $modx->getDatabase()->getFullTableName('active_users');
$tbl_site_content = $modx->getDatabase()->getFullTableName('site_content');
$tbl_site_htmlsnippets = $modx->getDatabase()->getFullTableName('site_htmlsnippets');
$tbl_site_module_depobj = $modx->getDatabase()->getFullTableName('site_module_depobj');
$tbl_site_modules = $modx->getDatabase()->getFullTableName('site_modules');
$tbl_site_plugins = $modx->getDatabase()->getFullTableName('site_plugins');
$tbl_site_snippets = $modx->getDatabase()->getFullTableName('site_snippets');
$tbl_site_templates = $modx->getDatabase()->getFullTableName('site_templates');
$tbl_site_tmplvars = $modx->getDatabase()->getFullTableName('site_tmplvars');

// initialize page view state - the $_PAGE object
$modx->getManagerApi()->initPageViewState();

// check to see the  editor isn't locked
$rs = $modx->getDatabase()->select('username', $tbl_active_users, "action=108 AND id='{$id}' AND internalKey!='" . $modx->getLoginUserID() . "'");
if($username = $modx->getDatabase()->getValue($rs)) {
	$modx->webAlertAndQuit(sprintf($_lang['lock_msg'], $username, 'module'));
}
// end check for lock

// take action
switch($_REQUEST['op']) {
	case 'add':
		// convert ids to numbers
		$opids = array_filter(array_map('intval', explode(',', $_REQUEST['newids'])));

		if(count($opids) > 0) {
			// 1-snips, 2-tpls, 3-tvs, 4-chunks, 5-plugins, 6-docs
			$rt = strtolower($_REQUEST["rt"]);
			if($rt == 'chunk') {
				$type = 10;
			}
			if($rt == 'doc') {
				$type = 20;
			}
			if($rt == 'plug') {
				$type = 30;
			}
			if($rt == 'snip') {
				$type = 40;
			}
			if($rt == 'tpl') {
				$type = 50;
			}
			if($rt == 'tv') {
				$type = 60;
			}
			$modx->getDatabase()->delete($tbl_site_module_depobj, "module='{$id}' AND resource IN (" . implode(',', $opids) . ") AND type='{$type}'");
			foreach($opids as $opid) {
				$modx->getDatabase()->insert(array(
					'module' => $id,
					'resource' => $opid,
					'type' => $type,
				), $tbl_site_module_depobj);
			}
		}
		break;
	case 'del':
		// convert ids to numbers
		$opids = array_filter(array_map('intval', $_REQUEST['depid']));

		// get resources that needs to be removed
		$ds = $modx->getDatabase()->select('*', $tbl_site_module_depobj, "id IN (" . implode(",", $opids) . ")");
		// loop through resources and look for plugins and snippets
		$plids = array();
		$snid = array();
		while($row = $modx->getDatabase()->getRow($ds)) {
			if($row['type'] == '30') {
				$plids[$i] = $row['resource'];
			}
			if($row['type'] == '40') {
				$snids[$i] = $row['resource'];
			}
		}
		// get guid
		$ds = $modx->getDatabase()->select('guid', $tbl_site_modules, "id='{$id}'");
		$guid = $modx->getDatabase()->getValue($ds);
		// reset moduleguid for deleted resources
		if(($cp = count($plids)) || ($cs = count($snids))) {
			if($cp) {
				$modx->getDatabase()->update(array('moduleguid' => ''), $tbl_site_plugins, "id IN (" . implode(',', $plids) . ") AND moduleguid='{$guid}'");
			}
			if($cs) {
				$modx->getDatabase()->update(array('moduleguid' => ''), $tbl_site_plugins, "id IN (" . implode(',', $snids) . ") AND moduleguid='{$guid}'");
			}
			// reset cache
			$modx->clearCache('full');
		}
		$modx->getDatabase()->delete($tbl_site_module_depobj, "id IN (" . implode(',', $opids) . ")");
		break;
}

// load record
$rs = $modx->getDatabase()->select('*', $tbl_site_modules, "id = '{$id}'");
$content = $modx->getDatabase()->getRow($rs);
if(!$content) {
	$modx->webAlertAndQuit("Module not found for id '{$id}'.");
}
$_SESSION['itemname'] = $content['name'];
if($content['locked'] == 1 && $_SESSION['mgrRole'] != 1) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

?>
<script type="text/javascript">

	function removeDependencies() {
		if(confirm("<?php echo $_lang['confirm_delete_record']; ?>") == true) {
			documentDirty = false;
			document.mutate.op.value = "del";
			document.mutate.submit();
		}
	};

	function addSnippet() {
		openSelector("snip", "m", "setResource");
	};

	function addDocument() {
		openSelector("doc", "m", "setResource");
	};

	function addTemplate() {
		openSelector("tpl", "m", "setResource");
	};

	function addTV() {
		openSelector("tv", "m", "setResource");
	};

	function addChunk() {
		openSelector("chunk", "m", "setResource");
	};

	function addPlugin() {
		openSelector("plug", "m", "setResource");
	};

	function setResource(rt, ids) {
		if(ids.length == 0) return;
		document.mutate.op.value = "add";
		document.mutate.rt.value = rt;
		document.mutate.newids.value = ids.join(",");
		document.mutate.submit();
	};

	function openSelector(resource, mode, callback, w, h) {
		var win
		w = w ? w : 600;
		h = h ? h : 400;
		url = "index.php?a=84&sm=" + mode + "&rt=" + resource + "&cb=" + callback
		// center on parent
		if(window.screenX) {
			var x = window.screenX + (window.outerWidth - w) / 2;
			var y = window.screenY + (window.outerHeight - h) / 2;
		} else {
			var x = (screen.availWidth - w) / 2;
			var y = (screen.availHeight - h) / 2;
		}
		self.chkBoxArray = {}; //reset checkbox array;
		win = window.open(url, "resource_selector", "left=" + x + ",top=" + y + ",height=" + h + ",width=" + w + ",status=yes,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no");
		win.opener = self;
	};

	var actions = {
		close: function() {
			document.location.href = 'index.php?a=106';
		}
	}
</script>

<form name="mutate" method="post" action="index.php?a=113">
	<input type="hidden" name="op" value="" />
	<input type="hidden" name="rt" value="" />
	<input type="hidden" name="newids" value="" />
	<input type="hidden" name="id" value="<?php echo $content['id']; ?>" />

	<h1>
		<i class="fa fa-cogs"></i><?= ($content['name'] ? $content['name'] . '<small>(' . $content['id'] . ')</small>' : $_lang['module_resource_title']) ?>
	</h1>

	<?php echo $_style['actionbuttons']['dynamic']['close'] ?>

	<div class="section">
		<div class="sectionHeader"><?php echo $content["name"] . " - " . $_lang['module_resource_title']; ?></div>
		<div class="sectionBody">
			<p><?php echo $_lang['module_resource_msg']; ?></p>
			<br />
			<!-- Dependencies -->
			<table width="100%" border="0" cellspacing="1" cellpadding="2">
				<tr>
					<td valign="top" align="left">
						<?php
						$ds = $modx->getDatabase()->select("smd.id,COALESCE(ss.name,st.templatename,sv.name,sc.name,sp.name,sd.pagetitle) as name,
				CASE smd.type
					WHEN 10 THEN 'Chunk'
					WHEN 20 THEN 'Document'
					WHEN 30 THEN 'Plugin'
					WHEN 40 THEN 'Snippet'
					WHEN 50 THEN 'Template'
					WHEN 60 THEN 'TV'
				END as type", "{$tbl_site_module_depobj} AS smd
					LEFT JOIN {$tbl_site_htmlsnippets} AS sc ON sc.id = smd.resource AND smd.type = '10'
					LEFT JOIN {$tbl_site_content} AS sd ON sd.id = smd.resource AND smd.type = '20'
					LEFT JOIN {$tbl_site_plugins} AS sp ON sp.id = smd.resource AND smd.type = '30'
					LEFT JOIN {$tbl_site_snippets} AS ss ON ss.id = smd.resource AND smd.type = '40'
					LEFT JOIN {$tbl_site_templates} AS st ON st.id = smd.resource AND smd.type = '50'
					LEFT JOIN {$tbl_site_tmplvars} AS sv ON sv.id = smd.resource AND smd.type = '60'", "smd.module={$id}", "smd.type,name");
						$grd = new \EvolutionCMS\Support\DataGrid('', $ds, 0); // set page size to 0 t show all items
						$grd->noRecordMsg = $_lang["no_records_found"];
						$grd->cssClass = "grid";
						$grd->columnHeaderClass = "gridHeader";
						$grd->itemClass = "gridItem";
						$grd->altItemClass = "gridAltItem";
						$grd->columns = $_lang["element_name"] . " ," . $_lang["type"];
						$grd->colTypes = "template:<input type='checkbox' name='depid[]' value='[+id+]'> [+value+]";
						$grd->fields = "name,type";
						echo $grd->render();
						?>
					</td>
					<td valign="top" style="width: 150px;">
						<a class="btn btn-block btn-danger text-left" style="margin-bottom:10px;" href="javascript:;" onclick="removeDependencies();return false;"><i class="<?php echo $_style["actions_delete"] ?>"></i> <?php echo $_lang['remove']; ?></a>
						<div class="btn-group-vertical" style="min-width: 100%">
							<a class="btn btn-block btn-secondary text-left" href="javascript:;" onclick="addSnippet();return false;"><i class="<?php echo $_style["actions_add"] ?>"></i> <?php echo $_lang['add_snippet']; ?></a>
							<a class="btn btn-block btn-secondary text-left" href="javascript:;" onclick="addDocument();return false;"><i class="<?php echo $_style["actions_add"] ?>"></i> <?php echo $_lang['add_doc']; ?></a>
							<a class="btn btn-block btn-secondary text-left" href="javascript:;" onclick="addChunk();return false;"><i class="<?php echo $_style["actions_add"] ?>"></i> <?php echo $_lang['add_chunk']; ?></a>
							<a class="btn btn-block btn-secondary text-left" href="javascript:;" onclick="addPlugin();return false;"><i class="<?php echo $_style["actions_add"] ?>"></i> <?php echo $_lang['add_plugin']; ?></a>
							<a class="btn btn-block btn-secondary text-left" href="javascript:;" onclick="addTV();return false;"><i class="<?php echo $_style["actions_add"] ?>"></i> <?php echo $_lang['add_tv']; ?></a>
							<a class="btn btn-block btn-secondary text-left" href="javascript:;" onclick="addTemplate();return false;"><i class="<?php echo $_style["actions_add"] ?>"></i> <?php echo $_lang['add_template']; ?></a>
						</div>
					</td>
				</tr>
			</table>
		</div>
	</div>
	<input type="submit" name="save" style="display:none">
</form>
