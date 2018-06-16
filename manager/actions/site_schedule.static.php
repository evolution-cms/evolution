<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('view_eventlog')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}
?>
<script type="text/javascript" src="media/script/tablesort.js"></script>

<h1>
	<?= $_style['page_shedule'] ?><?= $_lang['site_schedule'] ?>
</h1>

<div class="tab-page">
	<div class="container container-body">
		<div class="form-group" id="lyr1">
			<b><?= $_lang["publish_events"] ?></b>
			<?php
			$rs = $modx->getDatabase()->select('id, pagetitle, pub_date', $modx->getDatabase()->getFullTableName('site_content'), "pub_date > " . time() . "", 'pub_date ASC');
			$limit = $modx->getDatabase()->getRecordCount($rs);
			if($limit < 1) {
				?>
				<p><?= $_lang["no_docs_pending_publishing"] ?></p>
				<?php
			} else {
				?>
				<div class="table-responsive">
					<table class="grid sortabletable" id="table-1">
						<thead>
						<tr>
							<th class="sortable" style="width: 1%"><?= $_lang['id'] ?></th>
							<th class="sortable"><?= $_lang['resource'] ?></th>
							<th class="sortable text-right" style="width: 15%"><?= $_lang['publish_date'] ?></th>
						</tr>
						</thead>
						<tbody>
						<?php
						while($row = $modx->getDatabase()->getRow($rs)) {
							?>
							<tr>
								<td class="text-right"><?= $row['id'] ?></td>
								<td><a href="index.php?a=3&id=<?= $row['id'] ?>"><?= $row['pagetitle'] ?></a></td>
								<td class="text-nowrap text-right"><?= $modx->toDateFormat($row['pub_date'] + $server_offset_time) ?></td>
							</tr>
							<?php
						}
						?>
						</tbody>
					</table>
				</div>
				<?php
			}
			?>
		</div>
		<div class="form-group" id="lyr2">
			<b><?= $_lang["unpublish_events"] ?></b>
			<?php
			$rs = $modx->getDatabase()->select('id, pagetitle, unpub_date', $modx->getDatabase()->getFullTableName('site_content'), "unpub_date > " . time() . "", 'unpub_date ASC');
			$limit = $modx->getDatabase()->getRecordCount($rs);
			if($limit < 1) {
				?>
				<p><?= $_lang["no_docs_pending_unpublishing"] ?></p>
				<?php
			} else {
				?>
				<div class="table-responsive">
					<table class="grid sortabletable" id="table-2">
						<thead>
						<tr>
							<th class="sortable" style="width: 1%"><?= $_lang['id'] ?></th>
							<th class="sortable"><?= $_lang['resource'] ?></th>
							<th class="sortable text-right" style="width: 15%"><?= $_lang['unpublish_date'] ?></th>
						</tr>
						</thead>
						<tbody>
						<?php
						while($row = $modx->getDatabase()->getRow($rs)) {
							?>
							<tr>
								<td class="text-right"><?= $row['id'] ?></td>
								<td><a href="index.php?a=3&id=<?= $row['id'] ?>"><?= $row['pagetitle'] ?></a></td>
								<td class="text-nowrap text-right"><?= $modx->toDateFormat($row['unpub_date'] + $server_offset_time) ?></td>
							</tr>
							<?php
						}
						?>
						</tbody>
					</table>
				</div>
				<?php
			}
			?>
		</div>
		<div class="form-group">
			<b><?= $_lang["all_events"] ?></b>
			<?php
			$rs = $modx->getDatabase()->select('id, pagetitle, pub_date, unpub_date', $modx->getDatabase()->getFullTableName('site_content'), "pub_date > 0 OR unpub_date > 0", "pub_date DESC");
			$limit = $modx->getDatabase()->getRecordCount($rs);
			if($limit < 1) {
				?>
				<p><?= $_lang["no_docs_pending_pubunpub"] ?></p>
				<?php
			} else {
				?>
				<div class="table-responsive">
					<table class="grid sortabletable" id="table-3">
						<thead>
						<tr>
							<th class="sortable" style="width: 1%"><b><?= $_lang['id'] ?></b></th>
							<th class="sortable"><b><?= $_lang['resource'] ?></b></th>
							<th class="sortable text-right" style="width: 15%"><b><?= $_lang['publish_date'] ?></b></th>
							<th class="sortable text-right" style="width: 15%"><b><?= $_lang['unpublish_date'] ?></b></th>
						</tr>
						</thead>
						<tbody>
						<?php
						while($row = $modx->getDatabase()->getRow($rs)) {
							?>
							<tr>
								<td class="text-right"><?= $row['id'] ?></td>
								<td><a href="index.php?a=3&id=<?= $row['id'] ?>"><?= $row['pagetitle'] ?></a></td>
								<td class="text-nowrap text-right"><?= $row['pub_date'] == 0 ? "" : $modx->toDateFormat($row['pub_date'] + $server_offset_time) ?></td>
								<td class="text-nowrap text-right"><?= $row['unpub_date'] == 0 ? "" : $modx->toDateFormat($row['unpub_date'] + $server_offset_time) ?></td>
							</tr>
							<?php
						}
						?>
						</tbody>
					</table>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>
