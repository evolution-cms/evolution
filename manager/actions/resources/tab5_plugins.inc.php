<!-- plugins -->
<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

if (isset($resources->items['site_plugins'])) { ?>
    <div class="tab-page" id="tabPlugins">
        <h2 class="tab"><i class="fa fa-plug"></i> <?= $_lang["manage_plugins"] ?></h2>
        <script type="text/javascript">tpResources.addTabPage(document.getElementById('tabPlugins'))</script>

        <div id="plugins-info" class="msg-container" style="display:none">
            <div class="element-edit-message-tab"><?= $_lang['plugin_management_msg'] ?></div>
            <p class="viewoptions-message"><?= $_lang['view_options_msg'] ?></p>
        </div>

        <div id="_actions">
            <form class="btn-group form-group form-inline">
                <div class="input-group input-group-sm">
                    <input class="form-control filterElements-form" type="text" size="30" placeholder="<?= $_lang['element_filter_msg'] ?>" id="site_plugins_search" />
                    <div class="input-group-btn">
                        <?php if ($modx->hasPermission('new_plugin')) { ?>
                            <a class="btn btn-success" href="index.php?a=101"><i class="<?= $_style["actions_new"] ?>"></i> <span><?= $_lang['new_plugin'] ?></span></a>
                        <?php } ?>
                        <?php if ($modx->hasPermission('save_plugin')) { ?>
                            <a class="btn btn-secondary" href="index.php?a=100"><i class="<?= $_style["actions_sort"] ?>"></i> <span><?= $_lang['plugin_priority'] ?></span></a>
                        <?php } ?>
                        <?php
                        if ($modx->hasPermission('delete_plugin') && $_SESSION['mgrRole'] == 1) {
                            $tbl_site_plugins = $modx->getDatabase()->getFullTableName('site_plugins');
                            if ($modx->getDatabase()->getRecordCount($modx->getDatabase()->query("SELECT id FROM {$tbl_site_plugins} t1 WHERE disabled = 1 AND name IN (SELECT name FROM {$tbl_site_plugins} t2 WHERE t1.name = t2.name AND t1.id != t2.id)"))) { ?>
                                <a onclick="return confirm('<?php echo $_lang['purge_plugin_confirm'] ?>')" class="btn btn-danger" href="index.php?a=119"><?= $_lang['purge_plugin'] ?></a>
                                <?php
                            }
                        } ?>
                        <a class="btn btn-secondary" href="javascript:;" id="plugins-help"><i class="<?= $_style["actions_help"] ?>"></i> <span><?= $_lang['help'] ?></span></a>
                        <a class="btn btn-secondary switchform-btn" href="javascript:;" data-target="switchForm_site_plugins"><i class="fa fa-bars"></i> <span><?= $_lang['btn_view_options'] ?></span></a>
                    </div>
                </div>
            </form>
        </div>

        <?= renderViewSwitchButtons('site_plugins') ?>

        <?= createResourceList('site_plugins', $resources) ?>

        <script>
            initQuicksearch('site_plugins_search', 'site_plugins')
            initViews('pl', 'plugins', 'site_plugins')
        </script>
    </div>
<?php } ?>
