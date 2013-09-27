<?php

if (file_exists(dirname(__FILE__)."/../assets/cache/siteManager.php")) {
    include_once(dirname(__FILE__)."/../assets/cache/siteManager.php");
}else{
define('MGR_DIR', 'manager');
}

// Determine upgradeability
$upgradeable = 0;
if (file_exists("../".MGR_DIR."/includes/config.inc.php")) {
    // Include the file so we can test its validity
    include "../".MGR_DIR."/includes/config.inc.php";
    // We need to have all connection settings - tho prefix may be empty so we have to ignore it
    if ($dbase) {
        if (!@ $conn = mysql_connect($database_server, $database_user, $database_password)) {
            $upgradeable = isset ($_POST['installmode']) && $_POST['installmode'] == 'new' ? 0 : 2;
        }
        elseif (!@ mysql_select_db(trim($dbase, '`'), $conn)) {
            $upgradeable = isset ($_POST['installmode']) && $_POST['installmode'] == 'new' ? 0 : 2;
        } else {
            $upgradeable = 1;
        }
    } else {
        $upgradeable= 2;
    }
}
?>
<form name="install" id="install_form" action="index.php?action=connection" method="post">

	<?php
		echo '	<h2>' . $_lang['welcome_message_welcome'] . '</h2>';
		echo '	<p>' . $_lang['welcome_message_text'] . ' ' . $_lang['welcome_message_start'] . '</p>';
	?>

	<div>
		<input type="hidden" value="<?php echo $install_language?>" name="language" />
	</div>
	<h2 style="margin:1em 0"><?php echo $_lang['installation_mode']?></h2>
	<div>
		<div class="installImg"><img src="img/install_new.png" alt="new install" /></div>
		<div class="installDetails">
			<h3><input type="radio" name="installmode" id="installmode1" value="0" <?php echo !$upgradeable ? 'checked="checked"':'' ?> />
			<label for="installmode1" class="nofloat"><?php echo $_lang['installation_new_installation']?></label></h3>
			<p><?php echo $_lang['installation_install_new_copy'] . $moduleName?></p>
			<p><strong><?php echo $_lang['installation_install_new_note']?></strong></p>
		</div>
	</div>
	<hr />
	<div>
		<div class="installImg"><img src="img/install_upg.png" alt="upgrade existing install" /></div>
		<div class="installDetails">
			<h3><input type="radio" name="installmode" id="installmode2" value="1" <?php echo $upgradeable !== 1 ? 'disabled="disabled"' : '' ?> <?php echo ($_POST['installmode']=='1' || $upgradeable === 1) ? 'checked="checked"':'' ?> />
			<label for="installmode2" class="nofloat"><?php echo $_lang['installation_upgrade_existing']?></label></h3>
			<p><?php echo $_lang['installation_upgrade_existing_note']?></p>
		</div>
	</div>
	<hr />
  	<div>
		<div class="installImg"><img src="img/install_adv.png" alt="advanced MODX upgrade" /></div>
		<div class="installDetails">
			<h3><input type="radio" name="installmode" id="installmode3" value="2" <?php echo !$upgradeable ? 'disabled="disabled"':'' ?> <?php echo ($_POST['installmode']=='2' || $upgradeable === 2) ? 'checked="checked"':'' ?> />
			<label for="installmode3" class="nofloat"><?php echo $_lang['installation_upgrade_advanced']?></label></h3>
			<p><?php echo $_lang['installation_upgrade_advanced_note']?></p>
		</div>
	</div>

    <p class="buttonlinks">
        <a href="javascript:document.getElementById('install_form').action='index.php?action=language';document.getElementById('install_form').submit();" class="prev" title="<?php echo $_lang['btnback_value']?>"><span><?php echo $_lang['btnback_value']?></span></a>
        <a style="display:inline;" href="javascript:if(document.getElementById('installmode2').checked){document.getElementById('install_form').action='index.php?action=options';}document.getElementById('install_form').submit();" title="<?php echo $_lang['btnnext_value']?>"><span><?php echo $_lang['btnnext_value']?></span></a>
    </p>
</form>