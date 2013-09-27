<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

switch((int) $_REQUEST['a'])
{
	case 35:
		if(!$modx->hasPermission('edit_role'))
		{
			$e->setError(3);
			$e->dumpError();
		}
		break;
	case 38:
		if(!$modx->hasPermission('new_role'))
		{
			$e->setError(3);
			$e->dumpError();
		}
		break;
	default:
		$e->setError(3);
		$e->dumpError();
}

$role = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

// check to see the role editor isn't locked
$rs = $modx->db->select('internalKey, username','[+prefix+]active_users',"action=35 and id='{$role}'");
$total = $modx->db->getRecordCount($rs);
if($total>1) {
	for ($i=0;$i<$total;$i++) {
		$lock = $modx->db->getRow($rs);
		if($lock['internalKey']!=$modx->getLoginUserID()) {
			$msg = sprintf($_lang["lock_msg"],$lock['username'],$_lang['role']);
			$e->setError(5, $msg);
			$e->dumpError();
		}
	}
}
// end check for lock



if($_REQUEST['a']=='35')
{
	$rs = $modx->db->select('*','[+prefix+]user_roles',"id='{$role}'");
	$total = $modx->db->getRecordCount($rs);
	if($total>1) {
		echo "More than one role returned!<p>";
		exit;
	}
	if($total<1) {
		echo "No role returned!<p>";
		exit;
	}
	$roledata = $modx->db->getRow($rs);
	$_SESSION['itemname']=$roledata['name'];
} else {
	$roledata = 0;
	$_SESSION['itemname']="New role";
}



?>
<script type="text/javascript">
function changestate(element) {
	documentDirty=true;
	currval = eval(element).value;
	if(currval==1) {
		eval(element).value=0;
	} else {
		eval(element).value=1;
	}
}

function deletedocument() {
	if(confirm("<?php echo $_lang['confirm_delete_role']; ?>")==true) {
		document.location.href="index.php?id=" + document.userform.id.value + "&a=37";
	}
}

</script>
<form action="index.php?a=36" method="post" name="userform" enctype="multipart/form-data">
<input type="hidden" name="mode" value="<?php echo $_GET['a'] ?>">
<input type="hidden" name="id" value="<?php echo $_GET['id'] ?>">

<h1><?php echo $_lang['role_title']; ?></h1>

<div id="actions">
	<ul class="actionButtons">
			<li><a href="#" onclick="documentDirty=false; document.userform.save.click();"><img src="<?php echo $_style["icons_save"] ?>" /> <?php echo $_lang['save'] ?></a></li>
			<li id="btn_del"><a href="#" onclick="deletedocument();"><img src="<?php echo $_style["icons_delete"] ?>" /> <?php echo $_lang['delete'] ?></a></li>
			<li><a href="#" onclick="documentDirty=false;document.location.href='index.php?a=86';"><img src="<?php echo $_style["icons_cancel"] ?>" /> <?php echo $_lang['cancel'] ?></a></li>
	</ul>
	<?php if($_GET['a']=='38') { ?>
	<script type="text/javascript">document.getElementById("btn_del").className='disabled';</script>
	<?php } ?>
</div>

<div class="section">
<div class="sectionBody">
<fieldset>
<table>
  <tr>
    <td><?php echo $_lang['role_name']; ?>:</td>
    <td><input name="name" type="text" maxlength="50" value="<?php echo $roledata['name'] ; ?>"></td>
  </tr>
  <tr>
    <td><?php echo $_lang['resource_description']; ?>:</td>
    <td><input name="description" type="text" maxlength="255" value="<?php echo $roledata['description'] ; ?>" size="60"></td>
  </tr>
</table>
</fieldset>

<style type="text/css">
label {display:block;}
table td {vertical-align:top;}
.sectionBody fieldset {border:none;}
</style>
<table>
<tr>
<td>
<fieldset>
<h3><?php echo $_lang['page_data_general']; ?></h3>
<?php
	echo render_form('frames',          $_lang['role_frames'], 'disabled');
	echo render_form('home',            $_lang['role_home'], 'disabled');
	echo render_form('messages',        $_lang['role_messages']);
	echo render_form('logout',          $_lang['role_logout'], 'disabled');
	echo render_form('help',            $_lang['role_help']);
	echo render_form('action_ok',       $_lang['role_actionok'], 'disabled');
	echo render_form('error_dialog',    $_lang['role_errors'], 'disabled');
	echo render_form('about',           $_lang['role_about'], 'disabled');
	echo render_form('credits',         $_lang['role_credits'], 'disabled');
	echo render_form('change_password', $_lang['role_change_password']);
	echo render_form('save_password',   $_lang['role_save_password']);
?>
</fieldset>
</td>
<td>
<fieldset>
<h3><?php echo $_lang['role_content_management']; ?></h3>
<?php
	echo render_form('view_document',     $_lang['role_view_docdata'], 'disabled');
	echo render_form('new_document',      $_lang['role_create_doc']);
	echo render_form('edit_document',     $_lang['role_edit_doc']);
	echo render_form('save_document',     $_lang['role_save_doc']);
	echo render_form('publish_document',  $_lang['role_publish_doc']);
	echo render_form('delete_document',   $_lang['role_delete_doc']);
	echo render_form('empty_trash',       $_lang['role_empty_trash']);
	echo render_form('edit_doc_metatags', $_lang['role_edit_doc_metatags']);
	echo render_form('empty_cache',       $_lang['role_cache_refresh']);
	echo render_form('view_unpublished',  $_lang['role_view_unpublished']);
?>
</fieldset>
</td>
</tr>
</table>

<table>
<tr>
<td>
<fieldset>
<h3><?php echo $_lang['role_template_management']; ?></h3>
<?php
	echo render_form('new_template',    $_lang['role_create_template']);
	echo render_form('edit_template',   $_lang['role_edit_template']);
	echo render_form('save_template',   $_lang['role_save_template']);
	echo render_form('delete_template', $_lang['role_delete_template']);
?>
</fieldset>
</td>
<td>
<fieldset>
<h3><?php echo $_lang['role_snippet_management']; ?></h3>
<?php
	echo render_form('new_snippet',    $_lang['role_create_snippet']);
	echo render_form('edit_snippet',   $_lang['role_edit_snippet']);
	echo render_form('save_snippet',   $_lang['role_save_snippet']);
	echo render_form('delete_snippet', $_lang['role_delete_snippet']);
?>
</fieldset>
</td>
<td>
<fieldset>
<h3><?php echo $_lang['role_chunk_management']; ?></h3>
<?php
	echo render_form('new_chunk',    $_lang['role_create_chunk']);
	echo render_form('edit_chunk',   $_lang['role_edit_chunk']);
	echo render_form('save_chunk',   $_lang['role_save_chunk']);
	echo render_form('delete_chunk', $_lang['role_delete_chunk']);
?>
</fieldset>
</td>
<td>
<fieldset>
<h3><?php echo $_lang['role_plugin_management']; ?></h3>
<?php
	echo render_form('new_plugin',    $_lang['role_create_plugin']);
	echo render_form('edit_plugin',   $_lang['role_edit_plugin']);
	echo render_form('save_plugin',   $_lang['role_save_plugin']);
	echo render_form('delete_plugin', $_lang['role_delete_plugin']);
?>
</fieldset>
</td>
</tr>
</table>

<fieldset>
<h3><?php echo $_lang['role_module_management']; ?></h3>
<?php
	echo render_form('new_module',    $_lang['role_new_module']);
	echo render_form('edit_module',   $_lang['role_edit_module']);
	echo render_form('save_module',   $_lang['role_save_module']);
	echo render_form('delete_module', $_lang['role_delete_module']);
	echo render_form('exec_module',   $_lang['role_run_module']);
?>
</fieldset>

<table>
<tr>
<td>
<fieldset>
<h3><?php echo $_lang['role_user_management']; ?></h3>
<?php
	echo render_form('new_user',    $_lang['role_new_user']);
	echo render_form('edit_user',   $_lang['role_edit_user']);
	echo render_form('save_user',   $_lang['role_save_user']);
	echo render_form('delete_user', $_lang['role_delete_user']);
?>
</fieldset>
</td>
<td>
<fieldset>
<h3><?php echo $_lang['role_web_user_management']; ?></h3>
<?php
	echo render_form('new_web_user',    $_lang['role_new_web_user']);
	echo render_form('edit_web_user',   $_lang['role_edit_web_user']);
	echo render_form('save_web_user',   $_lang['role_save_web_user']);
	echo render_form('delete_web_user', $_lang['role_delete_web_user']);
?>
</fieldset>
</td>
<td>
<fieldset>
<h3><?php echo $_lang['role_udperms']; ?></h3>
<?php
	echo render_form('access_permissions',     $_lang['role_access_persmissions']);
	echo render_form('web_access_permissions', $_lang['role_web_access_persmissions']);
?>
</fieldset>
</td>
<td>
<fieldset>
<h3><?php echo $_lang['role_role_management']; ?></h3>
<?php
	echo render_form('new_role',    $_lang['role_new_role']);
	echo render_form('edit_role',   $_lang['role_edit_role']);
	echo render_form('save_role',   $_lang['role_save_role']);
	echo render_form('delete_role', $_lang['role_delete_role']);
?>
</fieldset>
</td>
</tr>
</table>

<table>
<tr>
<td>
<fieldset>
<h3><?php echo $_lang['role_eventlog_management']; ?></h3>
<?php
	echo render_form('view_eventlog',   $_lang['role_view_eventlog']);
	echo render_form('delete_eventlog', $_lang['role_delete_eventlog']);
?>
</fieldset>
</td>
<td>
<fieldset>
<h3><?php echo $_lang['role_config_management']; ?></h3>
<?php
	echo render_form('logs',            $_lang['role_view_logs']);
	echo render_form('settings',        $_lang['role_edit_settings']);
	echo render_form('file_manager',    $_lang['role_file_manager']);
	echo render_form('bk_manager',      $_lang['role_bk_manager']);
	echo render_form('manage_metatags', $_lang['role_manage_metatags']);
	echo render_form('import_static',   $_lang['role_import_static']);
	echo render_form('export_static',   $_lang['role_export_static']);
	echo render_form('remove_locks',    $_lang['role_remove_locks']);
?>
</fieldset>
</td>
</tr>
</table>

<input type="submit" name="save" style="display:none">
</form>
</div>
</div>



<?php
function render_form($name, $label, $status='')
{
	global $modx,$roledata;
	
	$tpl = <<< EOT
<label>
	<input name="[+name+]check" class="click" type="checkbox" onchange="changestate(document.userform.[+name+])" [+checked+] [+status+]>
	<input type="hidden" class="[+set+]" name="[+name+]" value="[+value+]">
	[+label+]
</label>

EOT;
	$checked = ($roledata[$name]==1) ? 'checked' : '';
	$value   = ($roledata[$name]==1) ? 1 : 0;
	if($status=='disabled')
	{
		$checked = 'checked';
		$value   = 1;
		$set     = 'fix';
	}
	else $set = 'set';
	
	$output = $tpl;
	$output = str_replace('[+name+]',    $name, $output);
	$output = str_replace('[+checked+]', $checked, $output);
	$output = str_replace('[+status+]',  $status, $output);
	$output = str_replace('[+value+]',   $value, $output);
	$output = str_replace('[+label+]',   $label, $output);
	$output = str_replace('[+set+]',     $set, $output);
	return $output;
}
