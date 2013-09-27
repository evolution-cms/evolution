<h2><?php echo $_lang['install_results']?></h2>
<?php
ob_start();
include "instprocessor.php";
$content = ob_get_contents();
ob_end_clean();
echo $content;

?>
<form name="install" id="install_form" action="index.php?action=options" method="post">
<?php
if ($errors == 0) {
	// check if install folder is removeable
    if (is_writable("../install")) { ?>
<span id="removeinstall" style="float:left;cursor:pointer;color:#505050;line-height:18px;" onclick="var chk=document.install.rminstaller; if(chk) chk.checked=!chk.checked;"><input type="checkbox" name="rminstaller" onclick="event.cancelBubble=true;" <?php echo (empty ($errors) ? 'checked="checked"' : '') ?> style="cursor:default;" /><?php echo $_lang['remove_install_folder_auto'] ?></span>
<?php 
    } else {
?>
<span id="removeinstall" style="float:left;color:#505050;line-height:18px;"><?php echo $_lang['remove_install_folder_manual']?></span>
<?php
    }
}
?>
    <p class="buttonlinks">
        <a href="javascript:closepage();" title="<?php echo $_lang['btnclose_value']?>"><span><?php echo $_lang['btnclose_value']?></span></a>
    </p>
	<br />
</form>
<br />
<script type="text/javascript">
/* <![CDATA[ */
function closepage(){
	var chk = document.install.rminstaller;
	if(chk && chk.checked) {
		// remove install folder and files
		window.location.href = "../<?php echo MGR_DIR;?>/processors/remove_installer.processor.php?rminstall=1";
	}
	else {
		window.location.href = "../<?php echo MGR_DIR;?>/";
	}
}
/* ]]> */
</script>
