<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('view_eventlog')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

// get id
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

$ds = $modx->getDatabase()->select('el.*, IFNULL(wu.username,mu.username) as username', $modx->getDatabase()->getFullTableName("event_log") . " el 
		LEFT JOIN " . $modx->getDatabase()->getFullTableName("manager_users") . " mu ON mu.id=el.user AND el.usertype=0
		LEFT JOIN " . $modx->getDatabase()->getFullTableName("web_users") . " wu ON wu.id=el.user AND el.usertype=1", "el.id='{$id}'");
$content = $modx->getDatabase()->getRow($ds);

?>
<script type="text/javascript">
	var actions = {
		delete: function() {
			if(confirm("<?= $_lang['confirm_delete_eventlog'] ?>") === true) {
				document.location.href = "index.php?id=" + document.resource.id.value + "&a=116";
			}
		},
		cancel: function() {
			documentDirty = false;
			document.location.href = 'index.php?a=114';
		}
	};
</script>

<h1><?= $_lang['eventlog'] ?></h1>

<?= $_style['actionbuttons']['dynamic']['canceldelete'] ?>

<form name="resource" method="get">
	<input type="hidden" name="id" value="<?= $id ?>" />
	<input type="hidden" name="a" value="<?= $modx->getManagerApi()->action ?>" />
	<input type="hidden" name="listmode" value="<?= $_REQUEST['listmode'] ?>" />
	<input type="hidden" name="op" value="" />
	<div class="tab-page">
		<div class="container container-body">
			<?php
			$date = $modx->toDateFormat($content["createdon"]);
			if($content["type"] == 1) {
				$icon = $_style['actions_info'] . ' text-info';
				$msgtype = $_lang["information"];
			} else if($content["type"] == 2) {
				$icon = $_style['actions_triangle'] . ' text-warning';
				$msgtype = $_lang["warning"];
			} else if($content["type"] == 3) {
				$icon = $_style['actions_error'] . ' text-danger';
				$msgtype = $_lang["error"];
			}
			?>
			<p><b><?= $content['source'] . " - " . $_lang['eventlog_viewer'] ?></b></p>
			<p>
				<i class="<?= $icon ?>"></i> <?= $msgtype ?>
			</p>
			<table class="table">
				<tr>
					<td width="25%" valign="top"><?= $_lang["event_id"] ?>:</td>
					<td width="25%" valign="top"><?= $content["eventid"] ?></td>
					<td width="25%" valign="top"><?= $_lang["source"] ?>:</td>
					<td width="25%" valign="top"><?= $content["source"] ?></td>
				</tr>
				<tr>
					<td width="25%" valign="top"><?= $_lang["date"] ?>:</td>
					<td width="25%" valign="top"><?= $date ?></td>
					<td width="25%" valign="top"><?= $_lang["user"] ?>:</td>
					<td width="25%" valign="top"><?= $content["username"] ?></td>
				</tr>
				<tr>
					<td width="100%" colspan="4"><br />
						<?= $content["description"] ?>
					</td>
				</tr>
			</table>
		</div>
	</div>
</form>
