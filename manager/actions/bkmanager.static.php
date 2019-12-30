<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('bk_manager')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}
require_once MODX_BASE_PATH.'assets/lib/Helpers/FS.php';

$dbase = trim($dbase, '`');

if (!isset($modx->config['snapshot_path'])) {
    if (is_dir(MODX_BASE_PATH . 'temp/backup/')) {
        $modx->config['snapshot_path'] = MODX_BASE_PATH . 'temp/backup/';
    } else {
        $modx->config['snapshot_path'] = MODX_BASE_PATH . 'assets/backup/';
    }
}

// Backup Manager by Raymond:

$mode = isset($_POST['mode']) ? $_POST['mode'] : '';

if ($mode == 'restore1') {
    if (isset($_POST['textarea']) && !empty($_POST['textarea'])) {
        $source = trim($_POST['textarea']);
        $_SESSION['textarea'] = $source . "\n";
        import_sql($source);
    } else {
        import_sql_from_file($_FILES['sqlfile']['tmp_name']);
    }

    header('Location: index.php?r=9&a=93');
    exit;
} elseif ($mode == 'restore2') {
    $path = $modx->config['snapshot_path'] . $_POST['filename'];
    if (file_exists($path)) {
        import_sql_from_file($path);
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
        $modx->webAlertAndQuit("Please select a valid table from the list below.");
    }

    /*
     * Code taken from Ralph A. Dahlgren MySQLdumper Snippet - Etomite 0.6 - 2004-09-27
     * Modified by Raymond 3-Jan-2005
     * Perform MySQLdumper data dump
     */
    @set_time_limit(120); // set timeout limit to 2 minutes
    $dumper = new Mysqldumper($dbase);
    $dumper->setDBtables($tables);
    $dumper->setDroptables((isset($_POST['droptables']) ? true : false));
    $dumpfinished = $dumper->createDump('dumpSql');
    if ($dumpfinished) {
        exit;
    } else {
        $modx->webAlertAndQuit('Unable to Backup Database');
    }

    // MySQLdumper class can be found below
} elseif ($mode == 'snapshot') {
    if (!is_dir(rtrim($modx->config['snapshot_path'], '/'))) {
        mkdir(rtrim($modx->config['snapshot_path'], '/'));
        @chmod(rtrim($modx->config['snapshot_path'], '/'), 0777);
    }
    if (!is_file("{$modx->config['snapshot_path']}.htaccess")) {
        $htaccess = "order deny,allow\ndeny from all\n";
        file_put_contents("{$modx->config['snapshot_path']}.htaccess", $htaccess);
    }
    if (!is_writable(rtrim($modx->config['snapshot_path'], '/'))) {
        $modx->webAlertAndQuit(parsePlaceholder($_lang["bkmgr_alert_mkdir"], array('snapshot_path' => $modx->config['snapshot_path'])));
    }
    $sql = "SHOW TABLE STATUS FROM `{$dbase}` LIKE '" . $modx->db->escape($modx->db->config['table_prefix']) . "%'";
    $rs = $modx->db->query($sql);
    $tables = $modx->db->getColumn('Name', $rs);
    $today = date('Y-m-d_H-i-s');
    global $path;
    $path = "{$modx->config['snapshot_path']}{$today}.sql";

    @set_time_limit(120); // set timeout limit to 2 minutes
    $dumper = new Mysqldumper($dbase);
    $dumper->setDBtables($tables);
    $dumper->setDroptables(true);
    $dumpfinished = $dumper->createDump('snapshot');

    $pattern = "{$modx->config['snapshot_path']}*.sql";
    $files = glob($pattern, GLOB_NOCHECK);
    $total = ($files[0] !== $pattern) ? count($files) : 0;

    arsort($files);
    while (10 < $total && $limit < 50) {
        $del_file = array_pop($files);
        unlink($del_file);
        $total = count($files);
        $limit++;
    }

    if ($dumpfinished) {
        $_SESSION['result_msg'] = 'snapshot_ok';
        header("Location: index.php?a=93");
        exit;
    } else {
        $modx->webAlertAndQuit('Unable to Backup Database');
    }
} else {
    include_once MODX_MANAGER_PATH . "includes/header.inc.php";  // start normal header
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
            cancel: function() {
                documentDirty = false;
                document.location.href = 'index.php?a=2';
            },
        };

        function selectAll()
        {
            var f = document.forms['frmdb'];
            var c = f.elements['chk[]'];
            for (var i = 0; i < c.length; i++) {
                c[i].checked = f.chkselall.checked;
            }
        }

        function backup()
        {
            var f = document.forms['frmdb'];
            f.mode.value = 'backup';
            f.target = 'fileDownloader';
            f.submit();
            return false;
        }

        function confirmRevert(filename)
        {
            var m = '<?= $_lang["bkmgr_restore_confirm"] ?>';
            m = m.replace('[+filename+]', filename);
            if (confirm(m) === true) {
                document.restore2.filename.value = filename;
                document.restore2.save.click();
            }
        }

        function showhide(a)
        {
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
        <i class="fa fa-database"></i><?= $_lang['bk_manager'] ?>
    </h1>

<?= $_style['actionbuttons']['static']['cancel'] ?>

    <div class="tab-pane" id="dbmPane">
        <script type="text/javascript">
            tpDBM = new WebFXTabPane(document.getElementById('dbmPane'));
        </script>

        <div class="tab-page" id="tabBackup">
            <h2 class="tab"><?= $_lang['backup'] ?></h2>
            <script type="text/javascript">tpDBM.addTabPage(document.getElementById('tabBackup'));</script>

            <div class="container container-body">
                <form name="frmdb" method="post">
                    <input type="hidden" name="mode" value="" />
                    <p>
                        <a href="javascript:;" class="btn btn-primary" onclick="backup();return false;"> <i class="<?= $_style['actions_save'] ?>"></i> <?= $_lang['database_table_clickbackup'] ?></a>
                        <label><input type="checkbox" name="droptables" checked="checked" /><?= $_lang['database_table_droptablestatements'] ?></label>
                    </p>
                    <div class="row">
                        <div class="table-responsive">
                            <table class="table data nowrap">
                                <thead>
                                <tr>
                                    <td><label class="form-check-label"><input type="checkbox" name="chkselall" class="form-check-input" onclick="selectAll();" title="Select All Tables" /> <?= $_lang['database_table_tablename'] ?></label></td>
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
                                $sql = "SHOW TABLE STATUS FROM `{$dbase}` LIKE '" . $modx->db->escape($modx->db->config['table_prefix']) . "%'";
                                $rs = $modx->db->query($sql);
                                $i = 0;
                                while ($db_status = $modx->db->getRow($rs)) {
                                    if (isset($tables)) {
                                        $table_string = implode(',', $table);
                                    } else {
                                        $table_string = '';
                                    }

                                    echo '<tr>' . "\n" . '<td><label class="form-check form-check-label"><input type="checkbox" name="chk[]" class="form-check-input" value="' . $db_status['Name'] . '"' . (strstr($table_string, $db_status['Name']) === false ? '' : ' checked="checked"') . ' /><b class="text-primary">' . $db_status['Name'] . '</b></label></td>' . "\n";
                                    echo '<td class="text-xs-center">' . (!empty($db_status['Comment']) ? '<i class="' . $_style['actions_help'] . '" data-tooltip="' . $db_status['Comment'] . '"></i>' : '') . '</td>' . "\n";
                                    echo '<td class="text-xs-right">' . $db_status['Rows'] . '</td>' . "\n";
                                    echo '<td class="text-xs-right">' . $db_status['Collation'] . '</td>' . "\n";

                                    // Enable record deletion for certain tables (TRUNCATE TABLE) if they're not already empty
                                    $truncateable = array(
                                        $modx->db->config['table_prefix'] . 'event_log',
                                        $modx->db->config['table_prefix'] . 'manager_log',
                                    );
                                    if ($modx->hasPermission('settings') && in_array($db_status['Name'], $truncateable) && $db_status['Rows'] > 0) {
                                        echo '<td class="text-xs-right"><a class="text-danger" href="index.php?a=54&mode=' . $action . '&u=' . $db_status['Name'] . '" title="' . $_lang['truncate_table'] . '">' . $modx->nicesize($db_status['Data_length'] + $db_status['Data_free']) . '</a>' . '</td>' . "\n";
                                    } else {
                                        echo '<td class="text-xs-right">' . $modx->nicesize($db_status['Data_length'] + $db_status['Data_free']) . '</td>' . "\n";
                                    }

                                    if ($modx->hasPermission('settings')) {
                                        echo '<td class="text-xs-right">' . ($db_status['Data_free'] > 0 ? '<a class="text-danger" href="index.php?a=54&mode=' . $action . '&t=' . $db_status['Name'] . '" title="' . $_lang['optimize_table'] . '">' . $modx->nicesize($db_status['Data_free']) . '</a>' : '-') . '</td>' . "\n";
                                    } else {
                                        echo '<td class="text-xs-right">' . ($db_status['Data_free'] > 0 ? $modx->nicesize($db_status['Data_free']) : '-') . '</td>' . "\n";
                                    }

                                    echo '<td class="text-xs-right">' . $modx->nicesize($db_status['Data_length'] - $db_status['Data_free']) . '</td>' . "\n" . '<td class="text-xs-right">' . $modx->nicesize($db_status['Index_length']) . '</td>' . "\n" . '<td class="text-xs-right">' . $modx->nicesize($db_status['Index_length'] + $db_status['Data_length'] + $db_status['Data_free']) . '</td>' . "\n" . "</tr>";

                                    $total = $total + $db_status['Index_length'] + $db_status['Data_length'];
                                    $totaloverhead = $totaloverhead + $db_status['Data_free'];
                                }
                                ?>
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td class="text-xs-right"><?= $_lang['database_table_totals'] ?></td>
                                    <td colspan="4">&nbsp;</td>
                                    <td class="text-xs-right"><?= $totaloverhead > 0 ? '<b class="text-danger">' . $modx->nicesize($totaloverhead) . '</b><br />(' . number_format($totaloverhead) . ' B)' : '-' ?></td>
                                    <td colspan="2">&nbsp;</td>
                                    <td class="text-xs-right"><?= "<b>" . $modx->nicesize($total) . "</b><br />(" . number_format($total) . " B)" ?></td>
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
                <form name="mutate" method="post" action="index.php" enctype="multipart/form-data">
                    <input type="hidden" name="a" value="93" />
                    <input type="hidden" name="mode" value="restore1" />
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
                            $last_result = array_merge(array(), array_diff($last_result, array('')));
                            foreach ($last_result['0'] as $k => $v) {
                                $title[] = $k;
                            }
                            $result = '<thead><tr><th>' . implode('</th><th>', $title) . '</th></tr></thead>';
                            $result .= '<tbody>';
                            foreach ($last_result as $row) {
                                $result_value = array();
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

                    function checked($cond)
                    {
                        if ($cond) {
                            return ' checked';
                        }
                    }

                    ?>
                    <p>
                        <label><input type="radio" name="sel" onclick="showhide('file');" <?= checked(!isset($_SESSION['console_mode']) || $_SESSION['console_mode'] !== 'text') ?> /> <?= $_lang["bkmgr_run_sql_file_label"] ?></label>
                        <label><input type="radio" name="sel" onclick="showhide('textarea');" <?= checked(isset($_SESSION['console_mode']) && $_SESSION['console_mode'] === 'text') ?> /> <?= $_lang["bkmgr_run_sql_direct_label"] ?></label>
                    </p>
                    <div class="form-group"><input type="file" name="sqlfile" id="sqlfile" style="display:<?= $f_display ?>;" /></div>
                    <div id="textarea" style="display:<?= $t_display ?>;">
                        <textarea name="textarea" rows="10"><?= $value ?></textarea>
                    </div>
                    <a href="javascript:;" class="btn btn-primary" onclick="document.mutate.save.click();"> <i class="<?= $_style['actions_save'] ?>"></i> <?= $_lang["bkmgr_run_sql_submit"] ?></a>
                    <input type="submit" name="save" style="display:none;" />
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
                    <?= parsePlaceholder($_lang["bkmgr_snapshot_msg"], array('snapshot_path' => "snapshot_path={$modx->config['snapshot_path']}")) ?>
                </div>
                <form name="snapshot" method="post" action="index.php">
                    <input type="hidden" name="a" value="93" />
                    <input type="hidden" name="mode" value="snapshot" />
                    <?= $_lang["description"] ?>
                    <div class="form-group input-group">
                        <input type="text" name="backup_title" class="form-control" maxlength="350" />
                        <div class="input-group-btn">
                            <a href="javascript:;" class="btn btn-success" onclick="document.snapshot.save.click();"> <i class="<?= $_style['actions_save'] ?>"></i> <?= $_lang["bkmgr_snapshot_submit"] ?></a>
                        </div>
                    </div>
                    <input type="submit" name="save" style="display:none;" />
                </form>
                <div>
                    <b><?= $_lang["bkmgr_snapshot_list_title"] ?></b>
                </div>
                <form name="restore2" method="post" action="index.php">
                    <input type="hidden" name="a" value="93" />
                    <input type="hidden" name="mode" value="restore2" />
                    <input type="hidden" name="filename" value="" />
                    <?php
                    $pattern = "{$modx->config['snapshot_path']}*.sql";
                    $files = glob($pattern, GLOB_NOCHECK);
                    $total = ($files[0] !== $pattern) ? count($files) : 0;
                    $detailFields = array(
                        'MODX Version',
                        'Host',
                        'Generation Time',
                        'Server version',
                        'PHP Version',
                        'Database',
                        'Description'
                    );
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
                                        <th><?= $_lang["modx_version"] ?></th>
                                        <th><?= $_lang["database_name"] ?></th>
                                        <th width="1%"><?= $_lang["onlineusers_action"] ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    arsort($files);
                                    while ($file = array_shift($files)) {
                                        $filename = substr($file, strrpos($file, '/') + 1);
                                        $filesize = $modx->nicesize(filesize($file));

                                        $file = fopen($file, "r");
                                        $count = 0;
                                        $details = array();
                                        while ($count < 11) {
                                            $line = fgets($file);
                                            foreach ($detailFields as $label) {
                                                $fileLabel = '# ' . $label;
                                                if (strpos($line, $fileLabel) !== false) {
                                                    $details[$label] = htmlentities(trim(str_replace(array(
                                                        $fileLabel,
                                                        ':',
                                                        '`'
                                                    ), '', $line)), ENT_QUOTES, $modx_manager_charset);
                                                }
                                            }
                                            $count++;
                                        };
                                        fclose($file);

                                        $tooltip = "Generation Time: " . $details["Generation Time"] . "\n";
                                        $tooltip .= "Server version: " . $details["Server version"] . "\n";
                                        $tooltip .= "PHP Version: " . $details["PHP Version"] . "\n";
                                        $tooltip .= "Host: " . $details["Host"] . "\n";
                                        ?>
                                        <tr>
                                            <td><?= $filename ?></td>
                                            <td><i class="<?= $_style['actions_help'] ?>" data-tooltip="<?= $tooltip ?>"></i></td>
                                            <td><?= $filesize ?></td>
                                            <td><?= $details['Description'] ?></td>
                                            <td><?= $details['MODX Version'] ?></td>
                                            <td><?= $details['Database'] ?></td>
                                            <td><a href="javascript:;" onclick="confirmRevert('<?= $filename ?>');" title="<?= $tooltip ?>"><?= $_lang["bkmgr_restore_submit"] ?></a></td>
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
                    <input type="submit" name="save" style="display:none;" />
                </form>
            </div>
        </div>

    </div>
<?php

if (is_numeric($_GET['tab'])) {
    echo '<script type="text/javascript">tpDBM.setSelectedIndex( ' . $_GET['tab'] . ' );</script>';
}

include_once "footer.inc.php"; // send footer
?>

<?php

/*
* @package  MySQLdumper
* @version  1.0
* @author   Dennis Mozes <opensource@mosix.nl>
* @url		http://www.mosix.nl/mysqldumper
* @since    PHP 4.0
* @copyright Dennis Mozes
* @license GNU/LGPL License: http://www.gnu.org/copyleft/lgpl.html
*
* Modified by Raymond for use with this module
*
**/

class Mysqldumper
{
    /**
     * @var array
     */
    public $_dbtables;
    /**
     * @var bool
     */
    public $_isDroptables;
    /**
     * @var string
     */
    public $dbname;
    /**
     * @var string
     */
    public $database_server;

    /**
     * Mysqldumper constructor.
     * @param string $dbname
     */
    public function __construct($dbname)
    {
        // Don't drop tables by default.
        $this->dbname = $dbname;
        $this->setDroptables(false);
    }

    /**
     * If set to true, it will generate 'DROP TABLE IF EXISTS'-statements for each table.
     *
     * @param bool $state
     */
    public function setDroptables($state)
    {
        $this->_isDroptables = $state;
    }

    /**
     * @param array $dbtables
     */
    public function setDBtables($dbtables)
    {
        $this->_dbtables = $dbtables;
    }

    /**
     * @param string $callBack
     * @return bool
     */
    public function createDump($callBack)
    {
        $modx = evolutionCMS();
        $createtable = array();
        $FS = \Helpers\FS::getInstance();

        // Set line feed
        $lf = "\n";
        $tempfile_path = $modx->config['base_path'] . 'assets/backup/temp.php';

        //если предедущая выгрузка завершылась не верно
        if($FS->checkFile($tempfile_path)){
            unlink($tempfile_path);
        }

        $result = $modx->db->query('SHOW TABLES');
        $tables = $this->result2Array(0, $result);
        foreach ($tables as $tblval) {
            $result = $modx->db->query("SHOW CREATE TABLE `{$tblval}`");

            $createtable[$tblval] = $this->result2Array(1, $result);

        }

        $version = $modx->getVersionData();

        // Set header
        $output = "#{$lf}";
        $output .= "# " . addslashes($modx->config['site_name']) . " Database Dump{$lf}";
        $output .= "# MODX Version:{$version['version']}{$lf}";
        $output .= "# {$lf}";
        $output .= "# Host: {$this->database_server}{$lf}";
        $output .= "# Generation Time: " . $modx->toDateFormat(time()) . $lf;
        $output .= "# Server version: " . $modx->db->getVersion() . $lf;
        $output .= "# PHP Version: " . phpversion() . $lf;
        $output .= "# Database: `{$this->dbname}`{$lf}";
        $output .= "# Description: " . trim($_REQUEST['backup_title']) . "{$lf}";
        $output .= "#";
        file_put_contents($tempfile_path, $output, FILE_APPEND | LOCK_EX);
        $output = '';

        // Generate dumptext for the tables.
        if (isset($this->_dbtables) && count($this->_dbtables)) {
            $this->_dbtables = implode(',', $this->_dbtables);
        } else {
            unset($this->_dbtables);
        }
        foreach ($tables as $tblval) {

            // check for selected table
            if (isset($this->_dbtables)) {
                if (strstr(",{$this->_dbtables},", ",{$tblval},") === false) {
                    continue;
                }
            }
            if ($callBack === 'snapshot') {
                if (!preg_match('@^' . $modx->db->config['table_prefix'] . '@', $tblval)) {
                    continue;
                }
            }

            $output .= "{$lf}{$lf}# --------------------------------------------------------{$lf}{$lf}";
            $output .= "#{$lf}# Table structure for table `{$tblval}`{$lf}";
            $output .= "#{$lf}{$lf}";
            // Generate DROP TABLE statement when client wants it to.
            if ($this->isDroptables()) {
                $output .= "SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;{$lf}";
                $output .= "DROP TABLE IF EXISTS `{$tblval}`;{$lf}";
                $output .= "SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;{$lf}{$lf}";
            }

            $output .= "{$createtable[$tblval][0]};{$lf}";
            $output .= $lf;
            $output .= "#{$lf}# Dumping data for table `{$tblval}`{$lf}#{$lf}";


            file_put_contents($tempfile_path, $output, FILE_APPEND | LOCK_EX);
            $output = '';

            //количество строк за запрос
            $rowByOneQuery = 10000;
            //делим на части
            $rowCount = $modx->db->getValue($modx->db->select("COUNT(*)",$tblval));
            // Находим общее число страниц
            $total = intval(($rowCount - 1) / $rowByOneQuery) + 1;

            $queryCounts = 0;

            for ($page = 1; $page <= $total; $page++) {
                $start = $page * $rowByOneQuery - $rowByOneQuery;
                $result = $modx->db->select('*', $tblval, '', '', "$start, $rowByOneQuery");
                while ($arr = $modx->db->getRow($result)) {
                    //формируем блок  значений
                    $insertdump = "(";
                    if (!is_array($arr)) $arr = array();

                    foreach ($arr as $key => $value) {
                        if (is_null($value)) {
                            $value = 'NULL';
                        } else {
                            $value = addslashes($value);
                            $value = str_replace(array(
                                "\r\n",
                                "\r",
                                "\n"
                            ), '\\n', $value);
                            $value = "'{$value}'";
                        }
                        $insertdump .= $value . ',';
                    }
                    $insertdump = rtrim($insertdump, ',') . ")";

                    //если еще небыло значен
                    if($queryCounts === 0){
                        $output .= $lf."INSERT INTO `{$tblval}` VALUES";
                    }
                    else{
                        $output .= ",";
                    }
                    $output .= $lf."  ".$insertdump;
                    $queryCounts++;

                    //если записали больше 30 строк з запрос ставим ; и сбрасивыем счетчик
                    if($queryCounts>30){
                        $output .= ";".$lf;
                        $queryCounts = 0;
                    }

                    //если большая строрки пишем в файл чтоб не перегрузить память

                    if (5040000 < strlen($output)) {
                        file_put_contents($tempfile_path, $output, FILE_APPEND | LOCK_EX);
                        $output = '';
                    }
                }
            }
            //если данные есть, и записано больше 0 строк данных ставим ; в конце
            if(!empty($output) && $queryCounts >0){
                $output .= ";".$lf;
            }

            //пишем блок в файл
            file_put_contents($tempfile_path, $output, FILE_APPEND | LOCK_EX);
            $output = '';
        }


        switch ($callBack) {
            case 'dumpSql':
                dumpSql($tempfile_path);
                break;
            case 'snapshot':
                snapshot($tempfile_path);
                break;
        }
        return true;
    }

    /**
     * @param int $numinarray
     * @param mysqli_result $resource
     * @return array
     */
    public function result2Array($numinarray = 0, $resource)
    {
        $modx = evolutionCMS();
        $array = array();
        while ($row = $modx->db->getRow($resource, 'num')) {
            $array[] = $row[$numinarray];
        }
        $modx->db->freeResult($resource);
        return $array;
    }

    /**
     * @return bool
     */
    public function isDroptables()
    {
        return $this->_isDroptables;
    }

    /**
     * @param string $key
     * @param mysqli_result $resource
     * @return array
     */
    public function loadObjectList($key = '', $resource)
    {
        $modx = evolutionCMS();
        $array = array();
        while ($row = $modx->db->getRow($resource, 'object')) {
            if ($key) {
                $array[$row->$key] = $row;
            } else {
                $array[] = $row;
            }
        }
        $modx->db->freeResult($resource);
        return $array;
    }

    /**
     * @param stdClass $obj
     * @return array|null
     */
    public function object2Array($obj)
    {
        $array = null;
        if (is_object($obj)) {
            $array = array();
            foreach (get_object_vars($obj) as $key => $value) {
                if (is_object($value)) {
                    $array[$key] = $this->object2Array($value);
                } else {
                    $array[$key] = $value;
                }
            }
        }
        return $array;
    }
}


/**
 * @param string $source
 * @param string $result_code
 */
function import_sql_from_file($path, $result_code = 'import_ok')
{
    $modx = evolutionCMS();
    if(!file_exists($path)){
        return false;
    }
    $fp = fopen($path,'r');

    $output = '';
    $inStructure = false;
    while (($buffer = fgets($fp)) !== false) {
        $output .= $buffer ."\n";
        if(strlen($output) > 5040000 && $buffer ===  "\n"){
            import_sql($output);
            $output = '';
        }
    }

    import_sql($output);
}


/**
 * @param string $source
 * @param string $result_code
 */
function import_sql($source, $result_code = 'import_ok')
{
    $modx = evolutionCMS(); global $e;

    $rs = null;
    if ($modx->getLockedElements() !== array()) {
        $modx->webAlertAndQuit("At least one Resource is still locked or edited right now by any user. Remove locks or ask users to log out before proceeding.");
    }

    $settings = getSettings();


    if (strpos($source, "\r") !== false) {
        $source = str_replace(array(
            "\r\n",
            "\n",
            "\r"
        ), "\n", $source);
    }
    $sql_array = preg_split('@;[ \t]*\n@', $source);

    foreach ($sql_array as $sql_entry) {
        $sql_entry = trim($sql_entry, "\r\n; ");
        if (empty($sql_entry)) {
            continue;
        }
        $rs = $modx->db->query($sql_entry);
    }
    restoreSettings($settings);

    $modx->clearCache();

    $_SESSION['last_result'] = ($rs !== null) ? null : $modx->db->makeArray($rs);
    $_SESSION['result_msg'] = $result_code;
}

/**
 * @param string $dumpTempFilePath
 * @return bool
 */
function dumpSql($dumpTempFilePath)
{
    $modx = evolutionCMS();
    $dumpstring = file_get_contents($dumpTempFilePath);
    $today = $modx->toDateFormat(time(), 'dateOnly');
    $today = str_replace('/', '-', $today);
    $today = strtolower($today);
    $size = strlen($dumpstring);
    if (!headers_sent()) {
        header('Expires: 0');
        header('Cache-Control: private');
        header('Pragma: cache');
        header('Content-type: application/download');
        header("Content-Length: {$size}");
        header("Content-Disposition: attachment; filename={$today}_database_backup.sql");
    }
    echo $dumpstring;
    return true;
}

/**
 * @param string $dumpTempFilePath
 * @return bool
 */
function snapshot($dumpTempFilePath)
{
    global $path;
    rename($dumpTempFilePath, $path);
    return true;
}

/**
 * @return array
 */
function getSettings()
{
    $modx = evolutionCMS();
    $tbl_system_settings = $modx->getFullTableName('system_settings');

    $rs = $modx->db->select('setting_name, setting_value', $tbl_system_settings);

    $settings = array();
    while ($row = $modx->db->getRow($rs)) {
        switch ($row['setting_name']) {
            case 'rb_base_dir':
            case 'filemanager_path':
            case 'site_url':
            case 'base_url':
                $settings[$row['setting_name']] = $row['setting_value'];
                break;
        }
    }
    return $settings;
}

/**
 * @param array $settings
 */
function restoreSettings($settings)
{
    $modx = evolutionCMS();
    $tbl_system_settings = $modx->getFullTableName('system_settings');

    foreach ($settings as $k => $v) {
        $modx->db->update(array('setting_value' => $v), $tbl_system_settings, "setting_name='{$k}'");
    }
}

/**
 * @param string $tpl
 * @param array $ph
 * @return string
 */
function parsePlaceholder($tpl = '', $ph = array())
{
    if (empty($ph) || empty($tpl)) {
        return $tpl;
    }

    foreach ($ph as $k => $v) {
        $k = "[+{$k}+]";
        $tpl = str_replace($k, $v, $tpl);
    }
    return $tpl;
}
