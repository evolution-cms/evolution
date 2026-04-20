<h2><?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true || !$modx->hasPermission('exec_module')) {
    die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.');
}

echo $_lang['install_results'];
?></h2>
<?php

// Use the same modernized processor as fast install, but keep the full
// options flow by honoring selected checkbox payload from action.options.php.
include 'instprocessor-fast.php';

$installSucceeded = ($errors == 0);
?>
<?php if (!$installSucceeded) { ?>
<p class="buttonlinks">
    <a href="javascript:closeInstallerResults();" title="<?php echo $_lang['btnclose_value']; ?>"><span><?php echo $_lang['btnclose_value']; ?></span></a>
</p>
<?php } ?>

<script type="text/javascript">
/* <![CDATA[ */
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
            return;
        }
    } catch (e) {}

    try {
        window.close();
    } catch (e) {}
}

refreshInstallerResultsState();

<?php if ($installSucceeded) { ?>
window.setTimeout(closeInstallerResults, 400);
<?php } ?>
/* ]]> */
</script>
