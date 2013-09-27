<?php

	/**
	 *	System Alert Message Queue Display file
	 *	Written By Raymond Irving, April, 2005
	 *
	 *	Used to display system alert messages inside the browser
	 *
	 */

	require_once(dirname(__FILE__).'/protect.inc.php');

	$sysMsgs = "";
	$limit = count($SystemAlertMsgQueque);
	for($i=0;$i<$limit;$i++) {
		$sysMsgs .= $SystemAlertMsgQueque[$i]."<hr sys/>";
	}
	// reset message queque
	unset($_SESSION['SystemAlertMsgQueque']);
	$_SESSION['SystemAlertMsgQueque'] = array();
	$SystemAlertMsgQueque = &$_SESSION['SystemAlertMsgQueque'];

	if($sysMsgs!="") {
?>

<?php // fetch the styles
	echo '<link rel="stylesheet" type="text/css" href="'.MODX_MANAGER_URL.'media/style/'.$manager_theme.'/style.css'.'" />';
?>
<script type="text/javascript">
// <![CDATA[
window.addEvent('domready', function() {
			var sysAlert = new Element('div').setProperties({
				'class': 'sysAlert'
			});
			sysAlert.innerHTML = '<?php echo $modx->db->escape($sysMsgs);?>';
			var boxHtml = new MooPrompt('<?php echo $_lang['sys_alert']; ?>', sysAlert, {
				buttons: 1,
				button1: 'Ok',
				width: 500
			});
});
// ]]>
</script>
<?php
	}
?>