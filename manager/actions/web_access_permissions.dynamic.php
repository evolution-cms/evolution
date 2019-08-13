<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('web_access_permissions')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

// find all document groups, for the select :)
$rs = $modx->db->select('*', $modx->getFullTableName('documentgroup_names'), '', 'name');
if($modx->db->getRecordCount($rs) < 1) {
	$docgroupselector = "[no groups to add]";
} else {
	$docgroupselector = '<select name="docgroup">' . "\n";
	while($row = $modx->db->getRow($rs)) {
		$docgroupselector .= "\t" . '<option value="' . $row['id'] . '">' . $row['name'] . "</option>\n";
	}
	$docgroupselector .= "</select>\n";
}

$rs = $modx->db->select('*', $modx->getFullTableName('webgroup_names'), '', 'name');
if($modx->db->getRecordCount($rs) < 1) {
	$usrgroupselector = '[no user groups]';
} else {
	$usrgroupselector = '<select name="usergroup">' . "\n";
	while($row = $modx->db->getRow($rs)) {
		$usrgroupselector .= "\t" . '<option value="' . $row['id'] . '">' . $row['name'] . "</option>\n";
	}
	$usrgroupselector .= "</select>\n";
}

?>
<script type="text/javascript">

	function deletegroup(groupid, type) {
		if(confirm("<?= $_lang['confirm_delete_group']?>") === true) {
			if(type === 'usergroup') {
				document.location.href = "index.php?a=92&usergroup=" + groupid + "&operation=delete_user_group";
			}
			else if(type === 'documentgroup') {
				document.location.href = "index.php?a=92&documentgroup=" + groupid + "&operation=delete_document_group";
			}
		}
	}

	document.addEventListener('DOMContentLoaded', function() {
		var h1help = document.querySelector('h1 > .help');
		h1help.onclick = function() {
			document.querySelector('.element-edit-message').classList.toggle('show')
		}
	});

</script>

<h1>
	<i class="fa fa-male"></i><?= $_lang['web_access_permissions'] ?><i class="fa fa-question-circle help"></i>
</h1>

<div class="container element-edit-message">
	<div class="alert alert-info"><?= $_lang['access_permissions_introtext'] ?></div>
</div>

<div class="container"><?= ($use_udperms != 1 ? '<div class="alert alert-danger">' . $_lang['access_permissions_off'] . '</div>' : '') ?></div>


<div class="tab-pane" id="wuapPane">
	<script type="text/javascript">
		tp1 = new WebFXTabPane(document.getElementById("wuapPane"), true);
	</script>

	<div class="tab-page" id="tabPage1">
		<h2 class="tab"><?= $_lang['web_access_permissions_user_groups'] ?></h2>
		<script type="text/javascript">tp1.addTabPage(document.getElementById("tabPage1"));</script>

		<div class="container container-body">
			<p class="element-edit-message-tab alert alert-warning"><?= $_lang['access_permissions_users_tab'] ?></p>
			<div class="form-group">
				<b><?= $_lang['access_permissions_add_user_group'] ?></b>
				<form name="accesspermissions" method="post" action="index.php">
					<input type="hidden" name="a" value="92" />
					<input type="hidden" name="operation" value="add_user_group" />
					<div class="input-group">
						<input class="form-control" type="text" value="" name="newusergroup" />
						<div class="input-group-btn">
							<input class="btn btn-success" type="submit" value="<?= $_lang['submit'] ?>" />
						</div>
					</div>
				</form>
			</div>
			<?php
			$rs = $modx->db->select('groupnames.*, users.id AS user_id, users.username user_name ', $modx->getFullTableName('webgroup_names') . " AS groupnames
			LEFT JOIN " . $modx->getFullTableName('web_groups') . " AS groups_resource ON groups_resource.webgroup = groupnames.id
			LEFT JOIN " . $modx->getFullTableName('web_users') . " AS users ON users.id = groups_resource.webuser", '', 'groupnames.name, user_name');
			if($modx->db->getRecordCount($rs) < 1) {
				?>
				<div class="text-danger"><?= $_lang['no_groups_found'] ?></div>
				<?php
			} else {
			?>
			<div class="form-group">
				<?php
				$pid = '';
				while($row = $modx->db->getRow($rs)) {
					if($pid != $row['id']) {
						if($pid != '') {
							echo '</div><div class="form-group">';
						}
						?>
						<form name="accesspermissions" method="post" action="index.php">
							<input type="hidden" name="a" value="92" />
							<input type="hidden" name="groupid" value="<?= $row['id'] ?>" />
							<input type="hidden" name="operation" value="rename_user_group" />
							<div class="input-group">
								<input class="form-control" type="text" name="newgroupname" value="<?= $modx->htmlspecialchars($row['name']) ?>" />
								<div class="input-group-btn">
									<input class="btn btn-secondary" type="submit" value="<?= $_lang['rename'] ?>" />
									<input class="btn btn-danger" type="button" value="<?= $_lang['delete'] ?>" onclick="deletegroup(<?= $row['id'] ?>,'usergroup');" />
								</div>
							</div>
						</form>
						<?= $_lang['web_access_permissions_users_in_group'] ?>
						<?php
					}
					if(!$row['user_id']) {
						?>
						<i><?= $_lang['access_permissions_no_users_in_group'] ?></i>
						<?php
						$pid = $row['id'];
						continue;
					}
					?>
					<?= ($pid == $row['id'] ? ', ' : '') ?><a href="index.php?a=88&id=<?= $row['user_id'] ?>"><?= $row['user_name'] ?></a>
					<?php
					$pid = $row['id'];
				}
				}
				?>
			</div>
		</div>
	</div>

	<div class="tab-page" id="tabPage2">
		<h2 class="tab"><?= $_lang['access_permissions_resource_groups'] ?></h2>
		<script type="text/javascript">tp1.addTabPage(document.getElementById("tabPage2"));</script>

		<div class="container container-body">
			<p class="element-edit-message-tab alert alert-warning"><?= $_lang['access_permissions_resources_tab'] ?></p>
			<div class="form-group">
				<b><?= $_lang['access_permissions_add_resource_group'] ?></b>
				<form name="accesspermissions" method="post" action="index.php">
					<input type="hidden" name="a" value="92" />
					<input type="hidden" name="operation" value="add_document_group" />
					<div class="input-group">
						<input class="form-control" type="text" value="" name="newdocgroup" />
						<div class="input-group-btn">
							<input class="btn btn-success" type="submit" value="<?= $_lang['submit'] ?>" />
						</div>
					</div>
				</form>
			</div>
			<?php
			$rs = $modx->db->select('dgnames.id, dgnames.name, sc.id AS doc_id, sc.pagetitle AS doc_title', $modx->getFullTableName('documentgroup_names') . " AS dgnames
			LEFT JOIN " . $modx->getFullTableName('document_groups') . " AS dg ON dg.document_group = dgnames.id
			LEFT JOIN " . $modx->getFullTableName('site_content') . " AS sc ON sc.id = dg.document", '', 'dgnames.name, sc.id');
			if($modx->db->getRecordCount($rs) < 1) {
				?>
				<div class="text-danger"><?= $_lang['no_groups_found'] ?></div>
				<?php
			} else {
			?>
			<div class="form-group">
				<?php
				$pid = '';
				while($row = $modx->db->getRow($rs)) {
					if($pid != $row['id']) {
						if($pid != '') {
							echo '</div><div class="form-group">';
						}
						?>
						<form name="accesspermissions" method="post" action="index.php">
							<input type="hidden" name="a" value="92" />
							<input type="hidden" name="groupid" value="<?= $row['id'] ?>" />
							<input type="hidden" name="operation" value="rename_document_group" />
							<div class="input-group">
								<input class="form-control" type="text" name="newgroupname" value="<?= $modx->htmlspecialchars($row['name']) ?>" />
								<div class="input-group-btn">
									<input class="btn btn-secondary" type="submit" value="<?= $_lang['rename'] ?>" />
									<input class="btn btn-danger" type="button" value="<?= $_lang['delete'] ?>" onclick="deletegroup(<?= $row['id'] ?>,'documentgroup');" />
								</div>
							</div>
						</form>
						<?= $_lang['access_permissions_resources_in_group'] ?>
						<?php
					}
					if(!$row['doc_id']) {
						?>
						<i><?= $_lang['access_permissions_no_resources_in_group'] ?></i>
						<?php
						$pid = $row['id'];
						continue;
					}
					?>
					<?= ($pid == $row['id'] ? ', ' : '') ?><a href="index.php?a=3&id=<?= $row['doc_id'] ?>" title="<?= $modx->htmlspecialchars($row['doc_title']) ?>"><?= $row['doc_id'] ?></a>
					<?php
					$pid = $row['id'];
				}
				}
				?>
			</div>
		</div>
	</div>

	<div class="tab-page" id="tabPage3">
		<h2 class="tab"><?= $_lang['access_permissions_links'] ?></h2>
		<script type="text/javascript">tp1.addTabPage(document.getElementById("tabPage3"));</script>

		<div class="container container-body">
			<p class="element-edit-message-tab alert alert-warning"><?= $_lang['access_permissions_links_tab'] ?></p>
			<?php
			$rs = $modx->db->select('groupnames.*, groupacc.id AS link_id, dgnames.id AS dg_id, dgnames.name AS dg_name', $modx->getFullTableName('webgroup_names') . " AS groupnames
			LEFT JOIN " . $modx->getFullTableName('webgroup_access') . " AS groupacc ON groupacc.webgroup = groupnames.id
			LEFT JOIN " . $modx->getFullTableName('documentgroup_names') . " AS dgnames ON dgnames.id = groupacc.documentgroup", '', 'name, dg_name');
			if($modx->db->getRecordCount($rs) < 1) {
				?>
				<div class="text-danger"><?= $_lang['no_groups_found'] ?></div>
				<?php
			} else {
				?>
				<div class="form-group">
					<b><?= $_lang["access_permissions_group_link"] ?></b>
					<form name="accesspermissions" method="post" action="index.php">
						<input type="hidden" name="a" value="92" />
						<input type="hidden" name="operation" value="add_document_group_to_user_group" />
						<?= $_lang["access_permissions_link_user_group"] ?>
						<?= $usrgroupselector ?>
						<?= $_lang["access_permissions_link_to_group"] ?>
						<?= $docgroupselector ?>
						<input class="btn btn-success" type="submit" value="<?= $_lang['submit'] ?>">
					</form>
				</div>
				<hr>
				<ul>
					<?php
					$pid = '';
					while($row = $modx->db->getRow($rs)) {
						if($row['id'] != $pid) {
							if($pid != '') {
								echo '</ul></li>';
							} // close previous one
							?>
							<li><b><?= $row['name'] ?></b></li>
							<?php
							if(!$row['dg_id']) {
								echo '<i>' . $_lang['no_groups_found'] . '</i></li>';
								$pid = '';
								continue;
							} else {
								echo '<ul>';
							}
						}
						if(!$row['dg_id']) {
							continue;
						}
						?>
						<li><?= $row['dg_name'] ?>
							<small><i>(<a class="text-danger" href="index.php?a=92&coupling=<?= $row['link_id'] ?>&operation=remove_document_group_from_user_group"><?= $_lang['remove'] ?></a>)</i></small>
						</li>
						<?php
						$pid = $row['id'];
					}
					?>
				</ul>
				<?php
			}
			?>
		</div>
	</div>

</div>
