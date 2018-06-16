<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('logs')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$res = $modx->getDatabase()->query("show variables like 'character_set_database'");
$charset = $modx->getDatabase()->getRow($res, 'num');
$res = $modx->getDatabase()->query("show variables like 'collation_database'");
$collation = $modx->getDatabase()->getRow($res, 'num');

$serverArr = array(
    $_lang['modx_version'] => $modx->getVersionData('version') . ' ' . $newversiontext,
    $_lang['release_date'] => $modx->getVersionData('release_date'),
    'PHP Version' => phpversion(),
    'phpInfo()' => '<a class="text-underline" href="javascript:;" onclick="viewPHPInfo();return false;">' . $_lang['view'] . '</a>',
    $_lang['access_permissions'] => ($use_udperms == 1 ? $_lang['enabled'] : $_lang['disabled']),
    $_lang['servertime'] => strftime('%H:%M:%S', time()),
    $_lang['localtime'] => strftime('%H:%M:%S', time() + $server_offset_time),
    $_lang['serveroffset'] => $server_offset_time / (60 * 60) . ' h',
    $_lang['database_name'] => trim($dbase, '`'),
    $_lang['database_server'] => $database_server,
    $_lang['database_version'] => $modx->getDatabase()->getVersion(),
    $_lang['database_charset'] => $charset[1],
    $_lang['database_collation'] => $collation[1],
    $_lang['table_prefix'] => $modx->getDatabase()->config['table_prefix'],
    $_lang['cfg_base_path'] => MODX_BASE_PATH,
    $_lang['cfg_base_url'] => MODX_BASE_URL,
    $_lang['cfg_manager_url'] => MODX_MANAGER_URL,
    $_lang['cfg_manager_path'] => MODX_MANAGER_PATH,
    $_lang['cfg_site_url'] => MODX_SITE_URL
);
?>

<h1>
    <?= $_style['page_sys_info'] ?><?= $_lang['view_sysinfo'] ?>
</h1>

<script type="text/javascript">
    function viewPHPInfo()
    {
        dontShowWorker = true; // prevent worker from being displayed
        window.location.href = 'index.php?a=200';
    };
</script>

<!-- server -->
<div class="tab-page">
    <div class="container container-body">
        <p><b>Server</b></p>
        <div class="row">
            <div class="table-responsive">
                <table class="table data table-sm nowrap">
                    <tbody>
                    <?php
                    foreach ($serverArr as $key => $value) {
                        ?>
                        <tr>
                            <td width="1%"><?= $key ?></td>
                            <td>&nbsp;</td>
                            <td><b><?= $value ?></b></td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<p>&nbsp;</p>

<!-- database -->
<div class="tab-page">
    <div class="container container-body">
        <p><b><?= $_lang['database_tables'] ?></b></p>
        <div class="row">
            <div class="table-responsive">
                <table class="table data nowrap">
                    <thead>
                    <tr>
                        <td><?= $_lang["database_table_tablename"] ?></td>
                        <td width="1%"></td>
                        <td class="text-xs-center"><?= $_lang["database_table_records"] ?></td>
                        <td class="text-xs-center"><?= $_lang["database_table_datasize"] ?></td>
                        <td class="text-xs-center"><?= $_lang["database_table_overhead"] ?></td>
                        <td class="text-xs-center"><?= $_lang["database_table_effectivesize"] ?></td>
                        <td class="text-xs-center"><?= $_lang["database_table_indexsize"] ?></td>
                        <td class="text-xs-center"><?= $_lang["database_table_totalsize"] ?></td>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $sql = "SHOW TABLE STATUS FROM $dbase LIKE '" . $modx->getDatabase()->escape($modx->getDatabase()->config['table_prefix']) . "%';";
                    $rs = $modx->getDatabase()->query($sql);
                    $i = 0;
                    while ($log_status = $modx->getDatabase()->getRow($rs)) {
                        ?>
                        <tr>
                            <td class="text-primary"><b><?= $log_status['Name'] ?></b></td>
                            <td class="text-xs-center"><?= (!empty($log_status['Comment']) ? '<i class="' . $_style['actions_help'] . '" data-tooltip="' . $log_status['Comment'] . '"></i>' : '') ?></td>
                            <td class="text-xs-right"><?= $log_status['Rows'] ?></td>

                            <?php
                            $truncateable = array(
                                $modx->getDatabase()->config['table_prefix'] . 'event_log',
                                $modx->getDatabase()->config['table_prefix'] . 'manager_log',
                            );
                            if ($modx->hasPermission('settings') && in_array($log_status['Name'], $truncateable)) {
                                echo "<td class=\"text-xs-right\">";
                                echo "<a class=\"text-danger\" href='index.php?a=54&mode=$action&u=" . $log_status['Name'] . "' title='" . $_lang['truncate_table'] . "'>" . $modx->nicesize($log_status['Data_length'] + $log_status['Data_free']) . "</a>";
                                echo "</td>";
                            } else {
                                echo "<td class=\"text-xs-right\">" . $modx->nicesize($log_status['Data_length'] + $log_status['Data_free']) . "</td>";
                            }

                            if ($modx->hasPermission('settings')) {
                                echo "<td class=\"text-xs-right\">" . ($log_status['Data_free'] > 0 ? "<a class=\"text-danger\" href='index.php?a=54&mode=$action&t=" . $log_status['Name'] . "' title='" . $_lang['optimize_table'] . "' ><span>" . $modx->nicesize($log_status['Data_free']) . "</span></a>" : "-") . "</td>";
                            } else {
                                echo "<td class=\"text-xs-right\">" . ($log_status['Data_free'] > 0 ? $modx->nicesize($log_status['Data_free']) : "-") . "</td>";
                            }
                            ?>
                            <td class="text-xs-right"><?= $modx->nicesize($log_status['Data_length'] - $log_status['Data_free']) ?></td>
                            <td class="text-xs-right"><?= $modx->nicesize($log_status['Index_length']) ?></td>
                            <td class="text-xs-right"><?= $modx->nicesize($log_status['Index_length'] + $log_status['Data_length'] + $log_status['Data_free']) ?></td>
                        </tr>
                        <?php
                        $total = $total + $log_status['Index_length'] + $log_status['Data_length'];
                        $totaloverhead = $totaloverhead + $log_status['Data_free'];
                    }
                    ?>
                    <tr class="unstyled">
                        <td class="text-xs-right"><?= $_lang['database_table_totals'] ?></td>
                        <td colspan="3">&nbsp;</td>
                        <td class="text-xs-right"><?= $totaloverhead > 0 ? "<b class=\"text-danger\">" . $modx->nicesize($totaloverhead) . "</b><br />(" . number_format($totaloverhead) . " B)" : "-" ?></td>
                        <td colspan="2">&nbsp;</td>
                        <td class="text-xs-right"><?= "<b>" . $modx->nicesize($total) . "</b><br />(" . number_format($total) . " B)" ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($totaloverhead > 0) { ?>
            <br>
            <p class="alert alert-danger"><?= $_lang['database_overhead'] ?></p>
        <?php } ?>
    </div>
</div>
