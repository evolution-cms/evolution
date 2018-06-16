<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('delete_template')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id == 0) {
	$modx->webAlertAndQuit($_lang["error_no_id"]);
}

// delete the template, but first check it doesn't have any documents using it
$rs = $modx->getDatabase()->select('id, pagetitle,introtext', $modx->getDatabase()->getFullTableName('site_content'), "template='{$id}' AND deleted=0");
$limit = $modx->getDatabase()->getRecordCount($rs);
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
				while($row = $modx->getDatabase()->getRow($rs)) {
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
$name = $modx->getDatabase()->getValue($modx->getDatabase()->select('templatename', $modx->getDatabase()->getFullTableName('site_templates'), "id='{$id}'"));
$_SESSION['itemname'] = $name;

// invoke OnBeforeTempFormDelete event
$modx->invokeEvent("OnBeforeTempFormDelete", array(
		"id" => $id
	));

// delete the document.
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('site_templates'), "id='{$id}'");

$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('site_tmplvar_templates'), "templateid='{$id}'");

// invoke OnTempFormDelete event
$modx->invokeEvent("OnTempFormDelete", array(
		"id" => $id
	));

// empty cache
$modx->clearCache('full');

// finished emptying cache - redirect
$header = "Location: index.php?a=76&r=2";
header($header);
