<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die("<b>INCLUDE_ORDERING_ERROR</b><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('settings')) {
	$modx->webAlertAndQuit($_lang['error_no_privileges']);
}

// check to see the edit settings page isn't locked
$rs = $modx->getDatabase()->select('username', $modx->getDatabase()->getFullTableName('active_users'), "action=17 AND internalKey!='" . $modx->getLoginUserID() . "'");
if($username = $modx->getDatabase()->getValue($rs)) {
	$modx->webAlertAndQuit(sprintf($_lang['lock_settings_msg'], $username));
}
// end check for lock

// reload system settings from the database.
// this will prevent user-defined settings from being saved as system setting
$settings = array();
include_once(MODX_MANAGER_PATH . 'includes/default_config.php');
$rs = $modx->getDatabase()->select('setting_name, setting_value', $modx->getDatabase()->getFullTableName('system_settings'));
while($row = $modx->getDatabase()->getRow($rs)) {
	$settings[$row['setting_name']] = $row['setting_value'];
}
$settings['filemanager_path'] = preg_replace('@^' . preg_quote(MODX_BASE_PATH) . '@', '[(base_path)]', $settings['filemanager_path']);
$settings['rb_base_dir'] = preg_replace('@^' . preg_quote(MODX_BASE_PATH) . '@', '[(base_path)]', $settings['rb_base_dir']);

extract($settings, EXTR_OVERWRITE);

// load languages and keys
$lang_keys = array();
$dir = dir('includes/lang');
while($file = $dir->read()) {
	if(strpos($file, '.inc.php') > 0) {
		$endpos = strpos($file, '.');
		$languagename = substr($file, 0, $endpos);
		$lang_keys[$languagename] = get_lang_keys($file);
	}
}
$dir->close();
$displayStyle = ($_SESSION['browser'] === 'modern') ? 'table-row' : 'block';
?>
	<script type="text/javascript">
		var displayStyle = '<?php echo $displayStyle; ?>';
		var lang_chg = '<?php echo $_lang['confirm_setting_language_change']; ?>';

		var actions = {
			save: function() {
				documentDirty = false;
				document.settings.submit();
			},
			cancel: function() {
				documentDirty = false;
				document.location.href = 'index.php?a=2';
			}
		}
	</script>
	<script type="text/javascript" src="actions/mutate_settings/functions.js"></script>
	<form name="settings" action="index.php?a=30" method="post">

		<h1>
			<?php echo $_style['page_settings']; echo $_lang['settings_title']; ?>
		</h1>

		<?= $_style['actionbuttons']['dynamic']['save'] ?>

		<div class="sectionBody">
			<input type="hidden" name="site_id" value="<?php echo $site_id; ?>" />
			<input type="hidden" name="settings_version" value="<?php echo $modx->getVersionData('version'); ?>" />
			<!-- this field is used to check site settings have been entered/ updated after install or upgrade -->
			<?php if(!isset($settings_version) || $settings_version != $modx->getVersionData('version')) { ?>
				<div class='sectionBody'><p class='element-edit-message-tab alert alert-warning'><?php echo $_lang['settings_after_install']; ?></p></div>
			<?php } ?>
			<div class="tab-pane" id="settingsPane">
				<script type="text/javascript">
					tpSettings = new WebFXTabPane(document.getElementById('settingsPane'), <?php echo $modx->config['remember_last_tab'] == 1 ? 'true' : 'false'; ?> );
				</script>

				<?php
				include_once(MODX_MANAGER_PATH . 'actions/mutate_settings/tab1_site_settings.inc.php');
				include_once(MODX_MANAGER_PATH . 'actions/mutate_settings/tab2_furl_settings.inc.php');
				include_once(MODX_MANAGER_PATH . 'actions/mutate_settings/tab3_user_settings.inc.php');
				include_once(MODX_MANAGER_PATH . 'actions/mutate_settings/tab4_manager_settings.inc.php');
				include_once(MODX_MANAGER_PATH . 'actions/mutate_settings/tab5_security_settings.inc.php');
				include_once(MODX_MANAGER_PATH . 'actions/mutate_settings/tab6_filemanager_settings.inc.php');
				include_once(MODX_MANAGER_PATH . 'actions/mutate_settings/tab7_filebrowser_settings.inc.php');
				?>
			</div>
		</div>
	</form>
	<script>
		jQuery('input:radio').change(function() {
			documentDirty = true;
		});
		jQuery('#furlRowOn').change(function() {
			jQuery('.furlRow').fadeIn();
		});
		jQuery('#furlRowOff').change(function() {
			jQuery('.furlRow').fadeOut();
		});
		jQuery('#udPermsOn').change(function() {
			jQuery('.udPerms').slideDown();
		});
		jQuery('#udPermsOff').change(function() {
			jQuery('.udPerms').slideUp();
		});
		jQuery('#editorRowOn').change(function() {
			jQuery('.editorRow').slideDown();
		});
		jQuery('#editorRowOff').change(function() {
			jQuery('.editorRow').slideUp();
		});
		jQuery('#rbRowOn').change(function() {
			jQuery('.rbRow').fadeIn();
		});
		jQuery('#rbRowOff').change(function() {
			jQuery('.rbRow').fadeOut();
		});
		jQuery('#useSmtp').change(function() {
			jQuery('.smtpRow').fadeIn();
		});
		jQuery('#useMail').change(function() {
			jQuery('.smtpRow').fadeOut();
		});
		jQuery('#captchaOn').change(function() {
			jQuery('.captchaRow').fadeIn();
		});
		jQuery('#captchaOff').change(function() {
			jQuery('.captchaRow').fadeOut();
		});
	</script>
<?php
if(is_numeric($_GET['tab'])) {
	echo '<script type="text/javascript">tpSettings.setSelectedIndex( ' . $_GET['tab'] . ' );</script>';
}
