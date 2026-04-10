<h2><?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true || ! $modx->hasPermission('exec_module')) {
    die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.');
}

echo $_lang['install_results']?></h2>
<?php


//ob_start();
include "instprocessor.php";
//$content = ob_get_contents();
//ob_end_clean();
//echo $content;

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

<?php
    }
}
?>
    <p class="buttonlinks">
        <a href="javascript:closeInstallerResults();" title="<?php echo $_lang['btnclose_value']?>"><span><?php echo $_lang['btnclose_value']?></span></a>
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
		window.location.href = "index.php?a=2";
	}
}

var installerResultsRefreshed = false;

function findStoreHostWindow() {
    var candidates = [];

    try { if (window.parent && window.parent !== window) candidates.push(window.parent); } catch (e) {}
    try { if (window.parent && window.parent.parent && window.parent.parent !== window.parent) candidates.push(window.parent.parent); } catch (e) {}
    try { if (window.top && window.top !== window.parent) candidates.push(window.top); } catch (e) {}

    for (var i = 0; i < candidates.length; i++) {
        try {
            if (candidates[i] && candidates[i].store && typeof candidates[i].store.refreshInstalledState === 'function') {
                return candidates[i];
            }
        } catch (e) {}
    }

    return null;
}

function refreshInstallerResultsState() {
    if (installerResultsRefreshed) {
        return;
    }

    installerResultsRefreshed = true;

    var hostWindow = findStoreHostWindow();

    try {
        if (hostWindow && hostWindow.store && typeof hostWindow.store.refreshInstalledState === 'function') {
            hostWindow.store.refreshInstalledState();
        }
    } catch (e) {}

    try {
        if (top && top.mainMenu && typeof top.mainMenu.reloadtree === 'function') {
            top.mainMenu.reloadtree();
        }
    } catch (e) {}

}

function closeInstallerResults() {
    refreshInstallerResultsState();

    try {
        if (parent && parent.jQuery && parent.jQuery.fancybox) {
            parent.jQuery.fancybox.close();
            return;
        }
    } catch (e) {}

    try {
        if (parent && parent.$ && parent.$.fancybox) {
            parent.$.fancybox.close();
        }
    } catch (e) {}
}

window.setTimeout(refreshInstallerResultsState, 50);
/* ]]> */
</script>
