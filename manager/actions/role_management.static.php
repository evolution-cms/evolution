<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('edit_user')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

?>
<!-- User Roles -->

<h1>
	<i class="fa fa-legal"></i><?= $_lang['role_management_title'] ?>
</h1>

<div class="tab-page">
	<div class="container container-body">
		<div class="form-group"><?= $_lang['role_management_msg'] ?> <a class="btn btn-secondary btn-sm" href="index.php?a=38"><i class="<?= $_style["actions_new"] ?> hide4desktop"></i> <?= $_lang['new_role'] ?></a></div>
		<div class="form-group">
			<?php
			$rs = $modx->getDatabase()->select('name, id, description', $modx->getDatabase()->getFullTableName('user_roles'), '', 'name');
			$limit = $modx->getDatabase()->getRecordCount($rs);
			if($limit < 1) {
				?>
				<p><?= $_lang["no_records_found"] ?></p>
				<?php
			} else {
				?>
				<div class="row">
					<div class="table-responsive">
						<table class="table data">
							<thead>
								<tr>
									<td><?= $_lang['role'] ?></td>
									<td><?= $_lang["description"] ?></td>
								</tr>
							</thead>
							<tbody>
							<?php
							while($row = $modx->getDatabase()->getRow($rs)) {
								if($row['id'] == 1) {
									?>
									<tr class="text-muted disabled">
										<td><b><?= $row['name'] ?></b></td>
										<td><span><?= $_lang['administrator_role_message'] ?></span></td>
									</tr>
									<?php
								} else {
									?>
									<tr>
										<td><a class="text-primary" href="index.php?id=<?= $row['id'] ?>&a=35"><?= $row['name'] ?></a></td>
										<td><?= $row['description'] ?></td>
									</tr>
									<?php
								}
							}
							?>
							</tbody>
						</table>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
</div>
