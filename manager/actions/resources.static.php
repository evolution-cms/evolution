<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

if(file_exists(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/functions.inc.php')) {
	include_once(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/functions.inc.php');
} else {
	include_once(MODX_MANAGER_PATH . 'actions/resources/functions.inc.php');
}
if(file_exists(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/mgrResources.class.php')) {
	include_once(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/mgrResources.class.php');
} else {
	include_once(MODX_MANAGER_PATH . 'actions/resources/mgrResources.class.php');
}

$resources = new mgrResources();

// Prepare lang-strings for "Lock Elements"
$unlockTranslations = array(
	'msg' => $_lang["unlock_element_id_warning"],
	'type1' => $_lang["lock_element_type_1"],
	'type2' => $_lang["lock_element_type_2"],
	'type3' => $_lang["lock_element_type_3"],
	'type4' => $_lang["lock_element_type_4"],
	'type5' => $_lang["lock_element_type_5"],
	'type6' => $_lang["lock_element_type_6"],
	'type7' => $_lang["lock_element_type_7"],
	'type8' => $_lang["lock_element_type_8"]
);
foreach($unlockTranslations as $key => $value) $unlockTranslations[$key] = iconv($modx->config["modx_charset"], "utf-8", $value);

// Prepare lang-strings for mgrResAction()
$mraTranslations = array(
	'create_new' => $_lang["create_new"],
	'edit' => $_lang["edit"],
	'duplicate' => $_lang["duplicate"],
	'remove' => $_lang["remove"],
	'confirm_duplicate_record' => $_lang["confirm_duplicate_record"],
	'confirm_delete_template' => $_lang["confirm_delete_template"],
	'confirm_delete_tmplvars' => $_lang["confirm_delete_tmplvars"],
	'confirm_delete_htmlsnippet' => $_lang["confirm_delete_htmlsnippet"],
	'confirm_delete_snippet' => $_lang["confirm_delete_htmlsnippet"],
	'confirm_delete_plugin' => $_lang["confirm_delete_plugin"],
	'confirm_delete_module' => $_lang["confirm_delete_module"],
);
foreach($mraTranslations as $key => $value) $mraTranslations[$key] = iconv($modx->config["modx_charset"], "utf-8", $value);
?>
<script>var trans = <?php echo json_encode($unlockTranslations); ?>;</script>
<script>var mraTrans = <?php echo json_encode($mraTranslations); ?>;</script>

<script type="text/javascript" src="media/script/jquery.quicksearch.js"></script>
<script type="text/javascript" src="media/script/jquery.nucontextmenu.js"></script>
<script type="text/javascript" src="media/script/bootstrap/js/bootstrap.min.js?v4.3.1"></script>
<script type="text/javascript" src="actions/resources/functions.js"></script>

<h1>
	<i class="fa fa-th"></i><?php echo $_lang['element_management']; ?>
</h1>

<div class="sectionBody">
	<div class="tab-pane" id="resourcesPane">
		<script type="text/javascript">
			tpResources = new WebFXTabPane(document.getElementById("resourcesPane"), true);
		</script>

		<?php
		if(file_exists(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/tab1_templates.inc.php')) {
			include_once(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/tab1_templates.inc.php');
		} else {
			include_once(MODX_MANAGER_PATH . '/actions/resources/tab1_templates.inc.php');
		}

		if(file_exists(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/tab2_templatevars.inc.php')) {
			include_once(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/tab2_templatevars.inc.php');
		} else {
			include_once(MODX_MANAGER_PATH . '/actions/resources/tab2_templatevars.inc.php');
		}

		if(file_exists(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/tab3_chunks.inc.php')) {
			include_once(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/tab3_chunks.inc.php');
		} else {
			include_once(MODX_MANAGER_PATH . '/actions/resources/tab3_chunks.inc.php');
		}

		if(file_exists(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/tab4_snippets.inc.php')) {
			include_once(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/tab4_snippets.inc.php');
		} else {
			include_once(MODX_MANAGER_PATH . '/actions/resources/tab4_snippets.inc.php');
		}

		if(file_exists(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/tab5_plugins.inc.php')) {
			include_once(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/tab5_plugins.inc.php');
		} else {
			include_once(MODX_MANAGER_PATH . '/actions/resources/tab5_plugins.inc.php');
		}

		if(file_exists(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/tab6_categoryview.inc.php')) {
			include_once(MODX_MANAGER_PATH . '/media/style/' . $modx->config['manager_theme'] . '/actions/resources/tab6_categoryview.inc.php');
		} else {
			include_once(MODX_MANAGER_PATH . '/actions/resources/tab6_categoryview.inc.php');
		}


		if(is_numeric($_GET['tab'])) {
			echo '<script type="text/javascript"> tpResources.setSelectedIndex( ' . $_GET['tab'] . ' );</script>';
		}
		?>
	</div>
</div>
