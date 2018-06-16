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

$forced = isset($_GET['force']) ? $_GET['force'] : 0;

// check for relations
if(!$forced) {
	$drs = $modx->getDatabase()->select('sc.id, sc.pagetitle,sc.description', $modx->getDatabase()->getFullTableName('site_content') . " AS sc
			INNER JOIN " . $modx->getDatabase()->getFullTableName('site_tmplvar_contentvalues') . " AS stcv ON stcv.contentid=sc.id", "stcv.tmplvarid='{$id}'");
	$count = $modx->getDatabase()->getRecordCount($drs);
	if($count > 0) {
		include_once "header.inc.php";
		?>
		<script>
			var actions = {
				delete: function() {
					document.location.href = "index.php?id=<?= $id ?>&a=303&force=1";
				},
				cancel: function() {
					window.location.href = 'index.php?a=301&id=<?= $id ?>';
				}
			};
		</script>

		<h1><?= $_lang['tmplvars'] ?></h1>

		<?= $_style['actionbuttons']['dynamic']['canceldelete'] ?>

		<div class="section">
			<div class="sectionHeader"><?= $_lang['tmplvars'] ?></div>
			<div class="sectionBody">
				<p><?= $_lang['tmplvar_inuse'] ?></p>
				<ul>
					<?php
					while($row = $modx->getDatabase()->getRow($drs)) {
						echo '<li><span style="width: 200px"><a href="index.php?id=' . $row['id'] . '&a=27">' . $row['pagetitle'] . '</a></span>' . ($row['description'] != '' ? ' - ' . $row['description'] : '') . '</li>';
					}
					?>
				</ul>
			</div>
		</div>
		<?php
		include_once "footer.inc.php";
		exit;
	}
}

// Set the item name for logger
$name = $modx->getDatabase()->getValue($modx->getDatabase()->select('name', $modx->getDatabase()->getFullTableName('site_tmplvars'), "id='{$id}'"));
$_SESSION['itemname'] = $name;

// invoke OnBeforeTVFormDelete event
$modx->invokeEvent("OnBeforeTVFormDelete", array(
	"id" => $id
));

// delete variable
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('site_tmplvars'), "id='{$id}'");

// delete variable's content values
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('site_tmplvar_contentvalues'), "tmplvarid='{$id}'");

// delete variable's template access
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('site_tmplvar_templates'), "tmplvarid='{$id}'");

// delete variable's access permissions
$modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('site_tmplvar_access'), "tmplvarid='{$id}'");

// invoke OnTVFormDelete event
$modx->invokeEvent("OnTVFormDelete", array(
	"id" => $id
));

// empty cache
$modx->clearCache('full');

// finished emptying cache - redirect
$header = "Location: index.php?a=76&r=2";
header($header);
