<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('import_static')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

// Files to upload
$allowedfiles = array(
    'html',
    'htm',
    'shtml',
    'xml'
);
?>
<script language="javascript">
    var actions = {
        cancel: function () {
            documentDirty = false;
            document.location.href = 'index.php?a=2';
        }
    };

    parent.tree.ca = "parent";

    function setParent(pId, pName) {
        document.importFrm.parent.value = pId;
        document.getElementById('parentName').innerHTML = pId + " (" + pName + ")";
        if (pId !== 0)
            document.getElementById('reset').disabled = true;
        else
            document.getElementById('reset').disabled = false;
    }
</script>

<h1>
    <i class="fa fa-upload"></i><?= $_lang['import_site_html'] ?>
</h1>

<?= $_style['actionbuttons']['static']['cancel'] ?>

<div class="tab-page">
    <div class="container container-body">
        <?php
        if (!isset($_POST['import'])) {
            echo "<div class=\"element-edit-message\">" . $_lang['import_site_message'] . "</div>";
            ?>
            <form action="index.php" method="post" name="importFrm">
                <input type="hidden" name="import" value="import"/>
                <input type="hidden" name="a" value="95"/>
                <input type="hidden" name="parent" value="0"/>
                <table border="0" cellspacing="0" cellpadding="2">
                    <tr>
                        <td nowrap="nowrap"><b><?= $_lang['import_parent_resource'] ?></b></td>
                        <td>&nbsp;</td>
                        <td><b><span id="parentName">0 (<?= $modx->getPhpCompat()->entities($site_name) ?>)</span></b></td>
                    </tr>
                    <tr>
                        <td nowrap="nowrap" valign="top"><b><?= $_lang['import_site_maxtime'] ?></b></td>
                        <td>&nbsp;</td>
                        <td><input type="text" name="maxtime" value="30"/>
                            <br/>
                            <?= $_lang['import_site_maxtime_message'] ?>
                        </td>
                    </tr>
                    <tr>
                        <td nowrap="nowrap" valign="top"><b><?= $_lang["import_site.static.php1"] ?></b></td>
                        <td>&nbsp;</td>
                        <td><input type="checkbox" id="reset" name="reset" value="on"/>
                            <br/>
                            <?= $_lang["import_site.static.php2"] ?>
                        </td>
                    </tr>
                    <tr>
                        <td nowrap="nowrap" valign="top"><b><?= $_lang["import_site.static.php3"] ?></b></td>
                        <td>&nbsp;</td>
                        <td>
                            <label><input type="radio" name="object"
                                          value="body"/> <?= $_lang["import_site.static.php4"] ?></label>
                            <label><input type="radio" name="object" value="all"
                                          checked="checked"/> <?= $_lang["import_site.static.php5"] ?></label>
                            <br/>
                        </td>
                    </tr>
                </table>
                <a href="javascript:;" class="btn btn-primary" onclick="window.importFrm.submit();"><i
                            class="<?= $_style["actions_save"] ?>"></i> <?= $_lang["import_site_start"] ?></a>
            </form>
        <?php
        } else {
        run();
        $modx->clearCache('full');
        ?>
            <a href="javascript:;" class="btn btn-primary" onclick="window.location.href='index.php?a=2';"><i
                        class="<?= $_style["actions_close"] ?>"></i> <?= $_lang["close"] ?></a>
            <script type="text/javascript">
                top.mainMenu.reloadtree();
                parent.tree.ca = 'open';
            </script>
            <?php
        }
        ?>
    </div>
</div>
