<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!EvolutionCMS()->hasPermission('bk_manager')) {
    EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

$dbase = EvolutionCMS()->getDatabase()->getConfig('database');

if (!EvolutionCMS()->getConfig('snapshot_path')) {
    if (is_dir(EVO_BASE_PATH . 'temp/backup/')) {
        EvolutionCMS()->setConfig('snapshot_path', EVO_BASE_PATH . 'temp/backup/');
    } else {
        EvolutionCMS()->setConfig('snapshot_path', EVO_BASE_PATH . 'assets/backup/');
    }
}

$tempFile = EvolutionCMS()->getConfig('snapshot_path') . 'temp.php';
if (file_exists($tempFile)) {
    unlink($tempFile);
}

// Backup Manager by Raymond:
$mode = isset($_POST['mode']) ? $_POST['mode'] : '';
$driver = EvolutionCMS()->getDatabase()->getConfig('driver');

function parseBackupSnapshotDetailValue(string $line, string $fileLabel): string
{
    $value = trim(substr($line, strlen($fileLabel)));
    $value = ltrim($value, " \t:");

    return trim($value, " \t\n\r\0\x0B`");
}

if ($mode == 'restore1') {
    if (isset($_POST['textarea']) && !empty($_POST['textarea'])) {
        $source = trim($_POST['textarea']);
        $_SESSION['textarea'] = $source . "\n";
        switch ($driver) {
            case 'pgsql':
                \DB::raw($source);
                break;
            default:
                import_sql($source);
                break;
        }
    } else {
        switch ($driver) {
            case 'pgsql':
                $tempfile_path = EVO_BASE_PATH . 'assets/backup/temp.php';
                file_put_contents($tempfile_path,  file_get_contents($_FILES['sqlfile']['tmp_name']));

                $dump_request = 'PGPASSWORD="'.EvolutionCMS()->getDatabase()->getConfig('password').'" psql --host '.EvolutionCMS()->getDatabase()->getConfig('host').' --username ' . EvolutionCMS()->getDatabase()->getConfig('username') . ' --dbname ' . $dbase . ' < '.$tempfile_path;
                exec($dump_request, $data, $data_second);
                unlink($tempfile_path);
                break;
            default:
                import_sql_from_file($_FILES['sqlfile']['tmp_name']);
                break;
        }
    }

    header('Location: index.php?r=9&a=93');
    exit;
} elseif ($mode == 'restore2') {
    $path = EvolutionCMS()->getConfig('snapshot_path') . $_POST['filename'];
    if (file_exists($path)) {
        switch ($driver) {
            case 'pgsql':
                $dump_request = 'PGPASSWORD="'.EvolutionCMS()->getDatabase()->getConfig('password').'" psql --host '.EvolutionCMS()->getDatabase()->getConfig('host').' --username ' . EvolutionCMS()->getDatabase()->getConfig('username') . ' --dbname ' . $dbase . ' < '.$path;
                exec($dump_request, $data, $data_second);
                break;
            default :
                import_sql_from_file($path);
                break;
        }
        if (headers_sent()) {
            echo "<script>document.location.href='index.php?r=9&a=93';</script>\n";
        } else {
            header("Location: index.php?r=9&a=93");
        }
    }

    exit;
} elseif ($mode == 'backup') {
    $tables = isset($_POST['chk']) ? $_POST['chk'] : '';
    if (!is_array($tables)) {
        EvolutionCMS()->webAlertAndQuit("Please select a valid table from the list below.");
    }

    /*
    * Code taken from Ralph A. Dahlgren MySQLdumper Snippet - Etomite 0.6 - 2004-09-27
    * Modified by Raymond 3-Jan-2005
    * Perform MySQLdumper data dump
    */

    @set_time_limit(120); // set timeout limit to 2 minutes
    switch ($driver) {
        case 'pgsql':
            $tempfile_path = EVO_BASE_PATH . 'assets/backup/temp.php';
            $clean = '';
            if ($_POST['droptables'] == 'on') {
                $clean = '--clean';
            }
            $table_str = ' -t ' . implode(' -t ', $tables);

            $dump_request = 'pg_dump postgresql://' . EvolutionCMS()->getDatabase()->getConfig('username') . ':'.EvolutionCMS()->getDatabase()->getConfig('password').'@'.EvolutionCMS()->getDatabase()->getConfig('host').'/' . $dbase . ' --clean --inserts --no-owner --no-privileges '. $table_str .'> ' . $tempfile_path;

            exec($dump_request, $data, $data_second);
            dumpSql($tempfile_path);
            break;
        case 'sqlite':
        case 'sqlite3':
            $dumper = new EvolutionCMS\Support\SqliteDumper($dbase);
            $dumper->setDBtables($tables);
            $dumper->setDroptables((isset($_POST['droptables']) ? true : false));
            $dumpfinished = $dumper->createDump('dumpSql');
            if ($dumpfinished) {
                exit;
            } else {
                EvolutionCMS()->webAlertAndQuit('Unable to Backup Database');
            }
            break;
        default:
            $dumper = new EvolutionCMS\Support\MysqlDumper($dbase);
            $dumper->setDBtables($tables);

            $dumper->setDroptables((isset($_POST['droptables']) ? true : false));
            $dumpfinished = $dumper->createDump('dumpSql');
            if ($dumpfinished) {
                exit;
            } else {
                EvolutionCMS()->webAlertAndQuit('Unable to Backup Database');
            }
            break;
    }
// MySQLdumper class can be found below
} elseif ($mode == 'snapshot') {
    if (!is_dir(rtrim(EvolutionCMS()->getConfig('snapshot_path'), '/'))) {
        mkdir(rtrim(EvolutionCMS()->getConfig('snapshot_path'), '/'));
        @chmod(rtrim(EvolutionCMS()->getConfig('snapshot_path'), '/'), 0777);
    }
    if (!is_file(EvolutionCMS()->getConfig('snapshot_path').".htaccess")) {
        $htaccess = "order deny,allow\ndeny from all\n";
        file_put_contents(EvolutionCMS()->getConfig('snapshot_path').".htaccess", $htaccess);
    }
    if (!is_writable(rtrim(EvolutionCMS()->getConfig('snapshot_path'), '/'))) {
        EvolutionCMS()->webAlertAndQuit(parsePlaceholder($_lang["bkmgr_alert_mkdir"], ['snapshot_path' => EvolutionCMS()->getConfig('snapshot_path')]));
    }
    $dumpfinished = false;
    $today = date('Y-m-d_H-i-s');
    global $path;
    $path = EvolutionCMS()->getConfig('snapshot_path').$today.".sql";
    switch ($driver) {
        case 'pgsql':
//            $lf = "\n";
//            $version = EvolutionCMS()->getVersionData();
//            $output = "# " . addslashes(EvolutionCMS()->getPhpCompat()->entities(EvolutionCMS()->getConfig('site_name'))) . " Database Dump{$lf}";
//            $output .= "# Evolution CMS Version:{$version['version']}{$lf}";
//            $output .= "# {$lf}";
//            $output .= "# Host: {EvolutionCMS()->getDatabase()->getConfig('host')}{$lf}";
//            $output .= "# Generation Time: " . EvolutionCMS()->toDateFormat(time()) . $lf;
//            $output .= "# Server version: " . EvolutionCMS()->getDatabase()->getVersion() . $lf;
//            $output .= "# PHP Version: " . phpversion() . $lf;
//            $output .= "# Database: `{EvolutionCMS()->getDatabase()->getConfig('database')}`{$lf}";
//            $output .= "# Description: " . trim($_REQUEST['backup_title']) . "{$lf}";
//            $output .= "#";
            $dump_request = 'pg_dump postgresql://' . EvolutionCMS()->getDatabase()->getConfig('username') . ':'.EvolutionCMS()->getDatabase()->getConfig('password').'@'.EvolutionCMS()->getDatabase()->getConfig('host').'/' . $dbase . ' --clean --inserts --no-owner --no-privileges > ' . $path;

            exec($dump_request, $data, $data_second);
            if ($data_second == 0) {
                $output = file_get_contents($path);
                file_put_contents($path, $output);
                $dumpfinished = true;
            }
            break;
        case 'sqlite':
        case 'sqlite3':
            $prefix = EvolutionCMS()->getDatabase()->getConfig('prefix');
            $tables = EvolutionCMS\Support\SqliteDumper::listTables($prefix);

            $dumper = new EvolutionCMS\Support\SqliteDumper($dbase);
            $dumper->setDBtables($tables);
            $dumper->setSnapshotFile($path);
            $dumper->setDroptables(true);
            $dumpfinished = $dumper->createDump('snapshot');
            break;
        default:
            $sql = "SHOW TABLE STATUS FROM `{$dbase}` LIKE '" . EvolutionCMS()->getDatabase()->escape(EvolutionCMS()->getDatabase()->getConfig('prefix')) . "%'";
            $rs = EvolutionCMS()->getDatabase()->query($sql);
            $tables = EvolutionCMS()->getDatabase()->getColumn('Name', $rs);

            @set_time_limit(120); // set timeout limit to 2 minutes
            $dumper = new EvolutionCMS\Support\MysqlDumper($dbase);
            $dumper->setDBtables($tables);
            $dumper->setSnapshotFile($path);
            $dumper->setDroptables(true);
            $dumpfinished = $dumper->createDump('snapshot');

            $pattern = EvolutionCMS()->getConfig('snapshot_path')."*.sql";
            $files = glob($pattern, GLOB_NOCHECK);
            $total = ($files[0] !== $pattern) ? count($files) : 0;
            arsort($files);
            while (10 < $total && $limit < 50) {
                $del_file = array_pop($files);
                unlink($del_file);
                $total = count($files);
                $limit++;
            }
            break;
    }

    if ($dumpfinished) {
        $_SESSION['result_msg'] = 'snapshot_ok';
        header("Location: index.php?a=93");
        exit;
    } else {
        EvolutionCMS()->webAlertAndQuit('Unable to Backup Database');
    }
} else {
    include_once EVO_MANAGER_PATH . "includes/header.inc.php";  // start normal header
}

if (isset($_SESSION['result_msg']) && $_SESSION['result_msg'] != '') {
    switch ($_SESSION['result_msg']) {
        case 'import_ok':
            $ph['result_msg_import'] = '<div class="alert alert-success">' . $_lang["bkmgr_import_ok"] . '</div>';
            $ph['result_msg_snapshot'] = '<div class="alert alert-success">' . $_lang["bkmgr_import_ok"] . '</div>';
            break;
        case 'snapshot_ok':
            $ph['result_msg_import'] = '';
            $ph['result_msg_snapshot'] = '<div class="alert alert-success">' . $_lang["bkmgr_snapshot_ok"] . '</div>';
            break;
    }
    $_SESSION['result_msg'] = '';
} else {
    $ph['result_msg_import'] = '';
    $ph['result_msg_snapshot'] = '';
}

?>
    <script language="javascript">
        var actions = {
            cancel: function () {
                documentDirty = false;
                document.location.href = 'index.php?a=2';
            },
        };

        function selectAll() {
            var f = document.forms['frmdb'];
            var c = f.elements['chk[]'];
            for (var i = 0; i < c.length; i++) {
                c[i].checked = f.chkselall.checked;
            }
        }

        function backup() {
            var f = document.forms['frmdb'];
            f.mode.value = 'backup';
            f.target = 'fileDownloader';
            f.submit();
            return false;
        }

        function confirmRevert(filename) {
            var m = '<?= $_lang["bkmgr_restore_confirm"] ?>';
            m = m.replace('[+filename+]', filename);
            if (confirm(m) === true) {
                document.restore2.filename.value = filename;
                document.restore2.save.click();
            }
        }

        function showhide(a) {
            var f = document.getElementById('sqlfile');
            var t = document.getElementById('textarea');
            if (a == 'file') {
                f.style.display = 'block';
                t.style.display = 'none';
            } else {
                t.style.display = 'block';
                f.style.display = 'none';
            }
        }

        <?= (isset($_REQUEST['r']) ? " doRefresh(" . $_REQUEST['r'] . ");" : "") ?>

    </script>

    <h1>
        <i class="<?= $_style['icon_database'] ?>"></i><?= $_lang['bk_manager'] ?>
    </h1>

<?= ManagerTheme::getStyle('actionbuttons.static.cancel') ?>

    <div class="tab-pane" id="dbmPane">
        <script type="text/javascript">
            tpDBM = new WebFXTabPane(document.getElementById('dbmPane'));
        </script>

        <div class="tab-page" id="tabBackup">
            <h2 class="tab"><?= $_lang['backup'] ?></h2>
            <script type="text/javascript">tpDBM.addTabPage(document.getElementById('tabBackup'));</script>

            <div class="container container-body">
                <form name="frmdb" method="post">
                    <input type="hidden" name="mode" value=""/>
                    <p>
                        <a href="javascript:;" class="btn btn-primary" onclick="backup();return false;"> <i
                                    class="<?= $_style['icon_save'] ?>"></i> <?= $_lang['database_table_clickbackup'] ?>
                        </a>
                        <label><input type="checkbox" name="droptables"
                                      checked="checked"/><?= $_lang['database_table_droptablestatements'] ?></label>
                    </p>
                    <div class="row">
                        <div class="table-responsive">
                            <table class="table data nowrap">
                                <thead>
                                <tr>
                                    <td><label class="form-check form-check-label"><input type="checkbox" name="chkselall"
                                                                                          class="form-check-input"
                                                                                          onclick="selectAll();"
                                                                                          title="Select All Tables"/> <?= $_lang['database_table_tablename'] ?>
                                        </label></td>
                                    <td width="1%"></td>
                                    <td class="text-xs-center"><?= $_lang['database_table_records'] ?></td>
                                    <td class="text-xs-center"><?= $_lang['database_collation'] ?></td>
                                    <td class="text-xs-center"><?= $_lang['database_table_datasize'] ?></td>
                                    <td class="text-xs-center"><?= $_lang['database_table_overhead'] ?></td>
                                    <td class="text-xs-center"><?= $_lang['database_table_effectivesize'] ?></td>
                                    <td class="text-xs-center"><?= $_lang['database_table_indexsize'] ?></td>
                                    <td class="text-xs-center"><?= $_lang['database_table_totalsize'] ?></td>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $prefix = EvolutionCMS()->getDatabase()->escape(EvolutionCMS()->getDatabase()->getConfig('prefix'));

                                switch (EvolutionCMS()->getDatabase()->getConfig()['driver']) {
                                    case 'pgsql':
                                        $sql = "SELECT *, tablename as Name
                 FROM pg_catalog.pg_tables WHERE
            schemaname != 'information_schema' AND tablename LIKE '%" . $prefix . "%'";

                                        $array = EvolutionCMS()->getDatabase()->makeArray(
                                                EvolutionCMS()->getDatabase()->query($sql)
                                        );
                                        break;

                                    case 'mysql':
                                        $sql = 'SHOW TABLE STATUS FROM `' . EvolutionCMS()->getDatabase()->getConfig('database') . '` LIKE "' . $prefix . '%"';

                                        $array = EvolutionCMS()->getDatabase()->makeArray(
                                                EvolutionCMS()->getDatabase()->query($sql)
                                        );
                                        break;
                                    case 'sqlite':
                                    case 'sqlite3':
                                        $tables = EvolutionCMS\Support\SqliteDumper::listTables($prefix);
                                        $array = [];
                                        $pdo = \DB::connection()->getPdo();
                                        foreach ($tables as $tableName) {
                                            $quotedName = '"' . str_replace('"', '""', $tableName) . '"';
                                            $stmt = $pdo->query('SELECT COUNT(*) FROM ' . $quotedName);
                                            $count = $stmt ? $stmt->fetchColumn() : 0;
                                            $array[] = [
                                                'Name' => $tableName,
                                                'Rows' => (int) $count,
                                                'Collation' => '-',
                                                'Data_length' => 0,
                                                'Data_free' => 0,
                                                'Index_length' => 0,
                                                'Comment' => '',
                                            ];
                                        }
                                        break;
                                    default:
                                        $array = [];
                                        break;
                                }
                                $i = 0;
                                $total = 0;
                                $totaloverhead = 0;
                                foreach ($array as $db_status) {
                                    if (isset($db_status['tablename'])) {
                                        $db_status['Name'] = $db_status['tablename'];
                                    }
                                    if (isset($tables) && is_array($tables)) {
                                        $table_string = implode(',', $tables);
                                    } else {
                                        $table_string = '';
                                    }

                                    echo '<tr>' . "\n" . '<td><label class="form-check form-check-label"><input type="checkbox" name="chk[]" class="form-check-input" value="' . $db_status['Name'] . '"' . (strstr($table_string, $db_status['Name']) === false ? '' : ' checked="checked"') . ' /><b class="text-primary">' . $db_status['Name'] . '</b></label></td>' . "\n";
                                    echo '<td class="text-xs-center">' . (!empty($db_status['Comment']) ? '<i class="' . $_style['icon_info_circle'] . '" data-tooltip="' . $db_status['Comment'] . '"></i>' : '') . '</td>' . "\n";
                                    echo '<td class="text-xs-right">' . $db_status['Rows'] . '</td>' . "\n";
                                    echo '<td class="text-xs-right">' . $db_status['Collation'] . '</td>' . "\n";

                                    // Enable record deletion for certain tables (TRUNCATE TABLE) if they're not already empty
                                    $truncateable = [
                                            EvolutionCMS()->getDatabase()->getConfig('prefix') . 'event_log',
                                            EvolutionCMS()->getDatabase()->getConfig('prefix') . 'manager_log',
                                    ];
                                    if (EvolutionCMS()->hasPermission('settings') && in_array($db_status['Name'], $truncateable) && $db_status['Rows'] > 0) {
                                        echo '<td class="text-xs-right"><a class="text-danger" href="index.php?a=54&mode=93&u=' . $db_status['Name'] . '" title="' . $_lang['truncate_table'] . '">' . niceSize($db_status['Data_length'] + $db_status['Data_free']) . '</a>' . '</td>' . "\n";
                                    } else {
                                        echo '<td class="text-xs-right">' . niceSize($db_status['Data_length'] + $db_status['Data_free']) . '</td>' . "\n";
                                    }

                                    if (EvolutionCMS()->hasPermission('settings')) {
                                        echo '<td class="text-xs-right">' . ($db_status['Data_free'] > 0 ? '<a class="text-danger" href="index.php?a=54&mode=93&t=' . $db_status['Name'] . '" title="' . $_lang['optimize_table'] . '">' . niceSize($db_status['Data_free']) . '</a>' : '-') . '</td>' . "\n";
                                    } else {
                                        echo '<td class="text-xs-right">' . ($db_status['Data_free'] > 0 ? niceSize($db_status['Data_free']) : '-') . '</td>' . "\n";
                                    }

                                    echo '<td class="text-xs-right">' . niceSize($db_status['Data_length'] - $db_status['Data_free']) . '</td>' . "\n" . '<td class="text-xs-right">' . niceSize($db_status['Index_length']) . '</td>' . "\n" . '<td class="text-xs-right">' . EvolutionCMS()->nicesize($db_status['Index_length'] + $db_status['Data_length'] + $db_status['Data_free']) . '</td>' . "\n" . "</tr>";

                                    $total += $db_status['Index_length'] + $db_status['Data_length'];
                                    $totaloverhead += $db_status['Data_free'];
                                }
                                ?>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td class="text-xs-right"><?= $_lang['database_table_totals'] ?></td>
                                    <td colspan="4">&nbsp;</td>
                                    <td class="text-xs-right"><?= $totaloverhead > 0 ? '<b class="text-danger">' . niceSize($totaloverhead) . '</b><br />(' . number_format($totaloverhead) . ' B)' : '-' ?></td>
                                    <td colspan="2">&nbsp;</td>
                                    <td class="text-xs-right"><?= "<b>" . niceSize($total) . "</b><br />(" . number_format($total) . " B)" ?></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <?php if ($totaloverhead > 0) { ?>
                        <br>
                        <p class="alert alert-danger"><?= $_lang['database_overhead'] ?></p>
                    <?php } ?>
                </form>
            </div>
        </div>
        <!-- This iframe is used when downloading file backup file -->
        <iframe name="fileDownloader" width="1" height="1" style="display:none; width:1px; height:1px;"></iframe>
        <div class="tab-page" id="tabRestore">
            <h2 class="tab"><?= $_lang["bkmgr_restore_title"] ?></h2>
            <script type="text/javascript">tpDBM.addTabPage(document.getElementById('tabRestore'));</script>

            <div class="container container-body">
                <?= $ph['result_msg_import'] ?>
                <div class="element-edit-message-tab alert alert-warning">
                    <?= $_lang["bkmgr_restore_msg"] ?>
                </div>
                <form method="post" name="mutate" enctype="multipart/form-data" action="index.php">
                    <input type="hidden" name="a" value="93"/>
                    <input type="hidden" name="mode" value="restore1"/>
                    <?php
                    if (isset($_SESSION['textarea']) && !empty($_SESSION['textarea'])) {
                        $value = $_SESSION['textarea'];
                        unset($_SESSION['textarea']);
                        $_SESSION['console_mode'] = 'text';
                        $f_display = 'none';
                        $t_display = 'block';
                    } else {
                        $value = '';
                        $_SESSION['console_mode'] = 'file';
                        $f_display = 'block';
                        $t_display = 'none';
                    }

                    if (isset($_SESSION['last_result']) || !empty($_SESSION['last_result'])) {
                        $last_result = $_SESSION['last_result'];
                        unset($_SESSION['last_result']);
                        if (count($last_result) < 1) {
                            $result = '';
                        } else {
                            $last_result = array_merge([], array_diff($last_result, ['']));
                            foreach ($last_result['0'] as $k => $v) {
                                $title[] = $k;
                            }
                            $result = '<thead><tr><th>' . implode('</th><th>', $title) . '</th></tr></thead>';
                            $result .= '<tbody>';
                            foreach ($last_result as $row) {
                                $result_value = [];
                                if ($row) {
                                    foreach ($row as $k => $v) {
                                        $result_value[] = $v;
                                    }
                                    $result .= '<tr><td>' . implode('</td><td>', $result_value) . '</td></tr>';
                                }
                            }
                            $result .= '</tbody>';
                            $result = '<table class="table data">' . $result . '</table>';
                        }
                    }

                    function checked($cond) {
                        if ($cond) {
                            return ' checked';
                        }
                    } ?>
                    <p>
                        <label><input type="radio" name="sel"
                                      onclick="showhide('file');" <?= checked(!isset($_SESSION['console_mode']) || $_SESSION['console_mode'] !== 'text') ?> /> <?= $_lang["bkmgr_run_sql_file_label"] ?>
                        </label>
                        <label><input type="radio" name="sel"
                                      onclick="showhide('textarea');" <?= checked(isset($_SESSION['console_mode']) && $_SESSION['console_mode'] === 'text') ?> /> <?= $_lang["bkmgr_run_sql_direct_label"] ?>
                        </label>
                    </p>
                    <div class="form-group"><input type="file" name="sqlfile" id="sqlfile"
                                                   style="display:<?= $f_display ?>;"/></div>
                    <div id="textarea" style="display:<?= $t_display ?>;">
                        <textarea name="textarea" rows="10"><?= $value ?></textarea>
                    </div>
                    <a href="javascript:;" class="btn btn-primary" onclick="document.mutate.save.click();"> <i
                                class="<?= $_style['icon_save'] ?>"></i> <?= $_lang["bkmgr_run_sql_submit"] ?></a>
                    <input type="submit" name="save" style="display:none;"/>
                </form>
                <?php if (isset($result)): ?>
                    <b><?= $_lang["bkmgr_run_sql_result"] ?></b>
                    <div class="row">
                        <div class="table-responsive"><?= $result ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-page" id="tabSnapshot">
            <h2 class="tab"><?= $_lang["bkmgr_snapshot_title"] ?></h2>
            <script type="text/javascript">tpDBM.addTabPage(document.getElementById('tabSnapshot'));</script>

            <div class="container container-body">
                <?= $ph['result_msg_snapshot'] ?>
                <div class="element-edit-message-tab alert alert-warning">
                    <?= parsePlaceholder($_lang["bkmgr_snapshot_msg"], ['snapshot_path' => "snapshot_path=".EvolutionCMS()->getConfig('snapshot_path')]) ?>
                </div>
                <form method="post" name="snapshot" action="index.php">
                    <input type="hidden" name="a" value="93"/>
                    <input type="hidden" name="mode" value="snapshot"/>
                    <?= $_lang["description"] ?>
                    <div class="form-group input-group">
                        <input type="text" name="backup_title" class="form-control" maxlength="350"/>
                        <div class="input-group-btn">
                            <a href="javascript:;" class="btn btn-success" onclick="document.snapshot.save.click();"> <i
                                        class="<?= $_style['icon_save'] ?>"></i> <?= $_lang["bkmgr_snapshot_submit"] ?>
                            </a>
                        </div>
                    </div>
                    <input type="submit" name="save" style="display:none;"/>
                </form>
                <div>
                    <b><?= $_lang["bkmgr_snapshot_list_title"] ?></b>
                </div>
                <form method="post" name="restore2" action="index.php">
                    <input type="hidden" name="a" value="93"/>
                    <input type="hidden" name="mode" value="restore2"/>
                    <input type="hidden" name="filename" value=""/>
                    <?php
                    $pattern = EvolutionCMS()->getConfig('snapshot_path')."*.sql";
                    $files = glob($pattern, GLOB_NOCHECK);
                    $total = ($files[0] !== $pattern) ? count($files) : 0;
                    $detailFields = [
                            'Evolution CMS Version',
                            'Host',
                            'Generation Time',
                            'Server version',
                            'PHP Version',
                            'Database',
                            'Description'
                    ];
                    if (is_array($files) && 0 < $total) {
                        ?>
                        <div class="row">
                            <div class="table-responsive">
                                <table class="table data nowrap">
                                    <thead>
                                    <tr>
                                        <th><?= $_lang["files_filename"] ?></th>
                                        <th width="1%"></th>
                                        <th><?= $_lang["files_filesize"] ?></th>
                                        <th><?= $_lang["description"] ?></th>
                                        <th><?= $_lang["evo_version"] ?></th>
                                        <th><?= $_lang["database_name"] ?></th>
                                        <th width="1%"><?= $_lang["onlineusers_action"] ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    arsort($files);
                                    while ($file = array_shift($files)) {
                                        $filename = substr($file, strrpos($file, '/') + 1);
                                        $filesize = niceSize(filesize($file));

                                        $file = fopen($file, "r");
                                        $count = 0;
                                        $details = [];
                                        while ($count < 11) {
                                            $line = fgets($file);
                                            foreach ($detailFields as $label) {
                                                foreach (['# ', '-- '] as $prefix) {
                                                    $fileLabel = $prefix . $label;
                                                    if (strpos($line, $fileLabel) !== false) {
                                                        $details[$label] = htmlentities(
                                                            parseBackupSnapshotDetailValue($line, $fileLabel),
                                                            ENT_QUOTES,
                                                            ManagerTheme::getCharset()
                                                        );
                                                        break;
                                                    }
                                                }
                                            }
                                            $count++;
                                        };
                                        fclose($file);

                                        $tooltipLines = [];
                                        foreach (['Server version', 'PHP Version', 'Host'] as $label) {
                                            if (!empty($details[$label])) {
                                                $tooltipLines[] = $label . ': ' . $details[$label];
                                            }
                                        }
                                        $tooltip = htmlspecialchars(implode("\n", $tooltipLines), ENT_QUOTES, ManagerTheme::getCharset());
                                        ?>
                                        <tr>
                                            <td><?= $filename ?></td>
                                            <td><?= $tooltip !== '' ? '<i class="' . $_style['icon_question_circle'] . '" data-tooltip="' . $tooltip . '"></i>' : '' ?></td>
                                            <td><?= $filesize ?></td>
                                            <td><?= ($details['Description'] ?? 'Undefined') ?></td>
                                            <td><?= ($details['Evolution CMS Version'] ?? 'Undefined') ?></td>
                                            <td><?= ($details['Database'] ?? 'Undefined') ?></td>
                                            <td><a href="javascript:;" onclick="confirmRevert('<?= $filename ?>');"
                                                   title="<?= $tooltip !== '' ? $tooltip : $_lang["bkmgr_restore_submit"] ?>"><?= $_lang["bkmgr_restore_submit"] ?></a>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php
                    } else {
                        echo $_lang["bkmgr_snapshot_nothing"];
                    }
                    ?>
                    <input type="submit" name="save" style="display:none;"/>
                </form>
            </div>
        </div>

    </div>
<?php

$tab = get_by_key($_GET, 'tab', false);
if (is_numeric($tab)) {
    echo '<script type="text/javascript">tpDBM.setSelectedIndex( ' . $_GET['tab'] . ' );</script>';
}

include_once EVO_MANAGER_PATH . "includes/footer.inc.php"; // send footer
?>

<?php

/**
 * @deprecated use EvolutionCMS\Support\MysqlDumper
 */
class Mysqldumper extends EvolutionCMS\Support\MysqlDumper
{
}
