<?php
if(IN_MANAGER_MODE != "true") {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('delete_template')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if($id == 0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// delete the template, but first check it doesn't have any documents using it
$rs = $modx->db->select('id, pagetitle,introtext', $modx->getFullTableName('site_content'), "template='{$id}' AND deleted=0");
$limit = $modx->db->getRecordCount($rs);
if($limit > 0) {
	include "header.inc.php";
	?>

	<h1><?php echo $_lang['manage_templates']; ?></h1>

	<div class="section">
		<div class="sectionHeader"><?php echo $_lang['manage_templates']; ?></div>
		<div class="sectionBody">
			<p>This template is in use.</p>
			<p>Please set the documents using the template to another template.</p>
			<p>Documents using this template:</p>
			<ul>
				<?php
				while($row = $modx->db->getRow($rs)) {
					echo '<li><span style="width: 200px"><a href="index.php?id=' . $row['id'] . '&a=27">' . $row['pagetitle'] . '</a></span>' . ($row['introtext'] != '' ? ' - ' . $row['introtext'] : '') . '</li>';
				}
				?>
			</ul>
		</div>
	</div>
	<?php
	include_once "footer.inc.php";
	exit;
}

if($id == $default_template) {
	$modx->webAlertAndQuit("This template is set as the default template. Please choose a different default template in the MODX configuration before deleting this template.");
}

// Set the item name for logger
$name = $modx->db->getValue($modx->db->select('templatename', $modx->getFullTableName('site_templates'), "id='{$id}'"));
$_SESSION['itemname'] = $name;

// invoke OnBeforeTempFormDelete event
$modx->invokeEvent("OnBeforeTempFormDelete", array(
		"id" => $id
	));

// delete the document.
$modx->db->delete($modx->getFullTableName('site_templates'), "id='{$id}'");

$modx->db->delete($modx->getFullTableName('site_tmplvar_templates'), "templateid='{$id}'");

// invoke OnTempFormDelete event
$modx->invokeEvent("OnTempFormDelete", array(
		"id" => $id
	));

// empty cache
$modx->clearCache('full');

// finished emptying cache - redirect
$header = "Location: index.php?a=76&r=2";
header($header);
