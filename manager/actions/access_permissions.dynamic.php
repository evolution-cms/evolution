<?php
if(IN_MANAGER_MODE != 'true') die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.');
if(!$modx->hasPermission('access_permissions')) {
	$e->setError(3);
	$e->dumpError();
}

// Get table names (alphabetical)
$tbl_document_groups     = $modx->getFullTableName('document_groups');
$tbl_documentgroup_names = $modx->getFullTableName('documentgroup_names');
$tbl_manager_users       = $modx->getFullTableName('manager_users');
$tbl_member_groups       = $modx->getFullTableName('member_groups');
$tbl_membergroup_access  = $modx->getFullTableName('membergroup_access');
$tbl_membergroup_names   = $modx->getFullTableName('membergroup_names');
$tbl_site_content        = $modx->getFullTableName('site_content');

// find all document groups, for the select :)
$rs = $modx->db->select('*','[+prefix+]documentgroup_names','','name');
if ($modx->db->getRecordCount($rs) < 1) {
	$docgroupselector = '[no groups to add]';
} else {
	$docgroupselector = '<select name="docgroup">'."\n";
	while ($row = $modx->db->getRow($rs)) {
		$docgroupselector .= "\t".'<option value="'.$row['id'].'">'.$row['name']."</option>\n";
	}
	$docgroupselector .= "</select>\n";
}

$rs = $modx->db->select('*','[+prefix+]membergroup_names','','name');
if ($modx->db->getRecordCount($rs) < 1) {
	$usrgroupselector = '[no user groups]';
} else {
	$usrgroupselector = '<select name="usergroup">'."\n";
	while ($row = $modx->db->getRow($rs)) {
		$usrgroupselector .= "\t".'<option value="'.$row['id'].'">'.$row['name']."</option>\n";
	}
	$usrgroupselector .= "</select>\n";
}

?>
<script type="text/javascript" src="media/script/tabpane.js"></script>

<h1><?php echo $_lang['mgr_access_permissions']?></h1>

<div class="sectionBody">
<p><?php echo $_lang['access_permissions_introtext']?></p><?php echo $use_udperms!=1 ? '<p>'.$_lang['access_permissions_off'].'</p>' : ''?>

<div class="tab-pane" id="uapPane">
<script type="text/javascript">tp1 = new WebFXTabPane( document.getElementById( "uapPane" ), true );</script>


<div class="tab-page" id="tabPage1">
<h2 class="tab"><?php echo $_lang['access_permissions_user_groups']?></h2>
<script type="text/javascript">tp1.addTabPage( document.getElementById( "tabPage1" ) );</script>
<?php
// User Groups

	echo '<p>'.$_lang['access_permissions_users_tab'].'</p>';

?>
	<table width="300" border="0" cellspacing="1" cellpadding="3" bgcolor="#ccc">
		<thead>
		<tr><td><b><?php echo $_lang['access_permissions_add_user_group']?></b></td></tr>
		</thead>
		<tr class="row1"><td>
			<form method="post" action="index.php" name="accesspermissions" style="margin: 0px;">
				<input type="hidden" name="a" value="41" />
				<input type="hidden" name="operation" value="add_user_group" />
				<input type="text" value="" name="newusergroup" />&nbsp;
				<input type="submit" value="<?php echo $_lang['submit']?>" />
			</form>
		</td></tr>
	</table>
	<br />
<?php
	$sql = 'SELECT groupnames.*, users.id AS user_id, users.username user_name '.
	       'FROM '.$tbl_membergroup_names.' AS groupnames '.
	       'LEFT JOIN '.$tbl_member_groups.' AS groups ON groups.user_group = groupnames.id '.
	       'LEFT JOIN '.$tbl_manager_users.' AS users ON users.id = groups.member '.
	       'ORDER BY groupnames.name';
	$rs = $modx->db->query($sql);
	if ($modx->db->getRecordCount($rs) < 1) {
		echo '<span class="warning">'.$_lang['no_groups_found'].'</span>';
	} else {
		echo "<ul>\n";
		$pid = '';
		while ($row = $modx->db->getRow($rs)) {
			if ($row['id'] !== $pid) {
				if ($pid != '') echo "</li></ul></li>\n"; // close previous one

				// display the current user group with a rename/delete form
				echo '<li><form method="post" action="index.php" name="accesspermissions" style="margin-top: 0.5em;">'."\n".
				     "\t".'<input type="hidden" name="a" value="41" />'."\n".
				     "\t".'<input type="hidden" name="groupid" value="'.$row['id'].'" />'."\n".
				     "\t".'<input type="hidden" name="operation" value="rename_user_group" />'."\n".
				     "\t".'<input type="text" name="newgroupname" value="'.htmlspecialchars($row['name']).'" />&nbsp;'."\n".
				     "\t".'<input type="submit" value="'.$_lang['rename'].'" />&nbsp;'."\n".
				     "\t".'<input type="button" value="'.$_lang['delete'].'" onclick="document.location.href=\'index.php?a=41&usergroup='.$row['id'].'&operation=delete_user_group\';" />'."\n".
				     '</form>';

				echo "<ul>\n";
				echo "\t<li>".$_lang['access_permissions_users_in_group'].' ';
			}
			if (!$row['user_id']) {
				// no users in group
				echo '<i>'.$_lang['access_permissions_no_users_in_group'].'</i>';
				$pid = $row['id'];
				continue;
			}
			if ($pid == $row['id']) echo ', '; // comma separation :)
			echo '<a href="index.php?a=12&amp;id='.$row['user_id'].'">'.$row['user_name'].'</a>';
			$pid = $row['id'];
		}
		echo "</li></ul></li>\n";
		echo "</ul>\n";
	}
?>
</div>


<div class="tab-page" id="tabPage2">
<h2 class="tab"><?php echo $_lang['access_permissions_resource_groups']?></h2>
<script type="text/javascript">tp1.addTabPage( document.getElementById( "tabPage2" ) );</script>
<?php
// Document Groups

	echo '<p>'.$_lang['access_permissions_resources_tab'].'</p>';
?>
	<table width="300" border="0" cellspacing="1" cellpadding="3" bgcolor="#ccc">
		<thead>
		<tr><td><b><?php echo $_lang['access_permissions_add_resource_group']?></b></td></tr>
		</thead>
		<tr class="row1"><td>
			<form method="post" action="index.php" name="accesspermissions" style="margin: 0px;">
				<input type="hidden" name="a" value="41" />
				<input type="hidden" name="operation" value="add_document_group" />
				<input type="text" value="" name="newdocgroup" />&nbsp;
				<input type="submit" value="<?php echo $_lang['submit']?>" />
			</form>
		</td></tr>
	</table>
	<br />
<?php
	$sql = 'SELECT dgnames.id, dgnames.name, sc.id AS doc_id, sc.pagetitle AS doc_title '.
	       'FROM '.$tbl_documentgroup_names.' AS dgnames '.
	       'LEFT JOIN '.$tbl_document_groups.' AS dg ON dg.document_group = dgnames.id '.
	       'LEFT JOIN '.$tbl_site_content.' AS sc ON sc.id = dg.document '.
	       'ORDER BY dgnames.name, sc.id';
	$rs = $modx->db->query($sql);
	if ($modx->db->getRecordCount($rs) < 1) {
		echo '<span class="warning">'.$_lang['no_groups_found'].'</span>';
	} else {
		echo '<table width="600" border="0" cellspacing="1" cellpadding="3" bgcolor="#ccc">'."\n".
		     "\t".'<thead>'."\n".
		     "\t".'<tr><td><b>'.$_lang['access_permissions_resource_groups'].'</b></td></tr>'."\n".
		     "\t".'</thead>'."\n";
		$pid = '';
		while ($row = $modx->db->getRow($rs)) {
			if ($row['id'] !== $pid) {
				if ($pid != '') echo "</td></tr>\n"; // close previous one

				echo '<tr><td class="row3"><form method="post" action="index.php" name="accesspermissions" style="margin: 0px;">'."\n".
				     "\t".'<input type="hidden" name="a" value="41" />'."\n".
				     "\t".'<input type="hidden" name="groupid" value="'.$row['id'].'" />'."\n".
				     "\t".'<input type="hidden" name="operation" value="rename_document_group" />'."\n".
				     "\t".'<input type="text" name="newgroupname" value="'.htmlspecialchars($row['name']).'">&nbsp;'."\n".
				     "\t".'<input type="submit" value="'.$_lang['rename'].'">'."\n".
				     "\t".'<input type="button" value="'.$_lang['delete'].'" onclick="document.location.href=\'index.php?a=41&documentgroup='.$row['id'].'&operation=delete_document_group\';" />'."\n".
				     '</form>';

				echo '</td></tr><tr><td class="row2">'.$_lang['access_permissions_resources_in_group'].' ';
			}
			if (!$row['doc_id']) {
				// no documents in group
				echo $_lang['access_permissions_no_resources_in_group'];
				$pid = $row['id'];
				continue;
			}
			if ($pid == $row['id']) echo ", \n";
			echo '<a href="index.php?a=3&amp;id='.$row['doc_id'].'" title="'.htmlspecialchars($row['doc_title']).'">'.$row['doc_id'].'</a>';
			$pid = $row['id'];
		}
		echo '</table>';
	}
?>
</div>

<div class="tab-page" id="tabPage3">
<h2 class="tab"><?php echo $_lang['access_permissions_links']?></h2>
<script type="text/javascript">tp1.addTabPage( document.getElementById( "tabPage3" ) );</script>
<?php
// User/Document Group Links

	echo '<p>'.$_lang['access_permissions_links_tab'].'</p>';

	$sql = 'SELECT groupnames.*, groupacc.id AS link_id, dgnames.id AS dg_id, dgnames.name AS dg_name '.
	       'FROM '.$tbl_membergroup_names.' AS groupnames '.
	       'LEFT JOIN '.$tbl_membergroup_access.' AS groupacc ON groupacc.membergroup = groupnames.id '.
	       'LEFT JOIN '.$tbl_documentgroup_names.' AS dgnames ON dgnames.id = groupacc.documentgroup '.
	       'ORDER BY name';
	$rs = $modx->db->query($sql);
	if ($modx->db->getRecordCount($rs) < 1) {
		echo '<span class="warning">'.$_lang['no_groups_found'].'</span><br />';
	} else {
		?>
		<table border="0" cellspacing="1" cellpadding="3" bgcolor="#ccc">
			<thead>
			<tr><td><b><?php echo $_lang["access_permissions_group_link"] ?></b></td></tr>
			</thead>
			<tr class="row1"><td>
				<form method="post" action="index.php" name="accesspermissions" style="margin: 0px;">
					<input type="hidden" name="a" value="41" />
					<input type="hidden" name="operation" value="add_document_group_to_user_group" />
					<?php echo $_lang["access_permissions_link_user_group"]?>
					<?php echo $usrgroupselector?>
					<?php echo $_lang["access_permissions_link_to_group"]?>
					<?php echo $docgroupselector?>
					<input type="submit" value="<?php echo $_lang['submit']?>">
				</form>
			</td></tr>
		</table>
		<br />
		<?php
		echo "<ul>\n";
		$pid = '';
		while ($row = $modx->db->getRow($rs)) {
			if ($row['id'] != $pid) {
				if ($pid != '') echo "</ul></li>\n"; // close previous one
				echo '<li><b>'.$row['name'].'</b>';

				if (!$row['dg_id']) {
					echo ' &raquo; <i>'.$_lang['no_groups_found']."</i></li>\n";
					$pid = '';
					continue;
				} else {
					echo "<ul>\n";
				}
			}
			if (!$row['dg_id']) continue;
			echo "\t<li>".$row['dg_name'];
			echo ' <small><i>(<a href="index.php?a=41&amp;coupling='.$row['link_id'].'&amp;operation=remove_document_group_from_user_group">';
			echo $_lang['remove'].'</a>)</i></small>';
			echo "</li>\n";

			$pid = $row['id'];
		}
		echo '</ul>';
	}
?>
</div>

</div>
</div>
