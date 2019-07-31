<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('logs')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

/**
 * @param array $array
 * @param string $checkKey
 * @return array
 */
function array_unique_multi($array, $checkKey)
{
    // Use the builtin if we're not a multi-dimensional array
    if (!is_array(current($array)) || empty($checkKey)) {
        return array_unique($array);
    }

    $ret = array();
    $checkValues = array(); // contains the unique key Values
    foreach ($array as $key => $current) {
        if (in_array($current[$checkKey], $checkValues)) {
            continue;
        } // duplicate

        $checkValues[] = $current[$checkKey];
        $ret[$key] = $current;
    }
    return $ret;
}

/**
 * @param array $array
 * @param string $key
 * @return array
 */
function record_sort($array, $key)
{
    $hash = array();
    foreach ($array as $k => $v) {
        $hash[$k] = $v[$key];
    }

    natsort($hash);

    $records = array();
    foreach ($hash as $k => $row) {
        $records[$k] = $array[$k];
    }

    return $records;
}

$rs = $modx->db->select('DISTINCT internalKey, username, action, itemid, itemname', $modx->getFullTableName('manager_log'));
$logs = $modx->db->makeArray($rs);
?>
    <h1>
        <?= $_style['page_manager_logs'] ?><?= $_lang['mgrlog_view'] ?>
    </h1>

    <div class="tab-page">
        <div class="container container-body">
            <div class="element-edit-message-tab alert alert-warning"><?= $_lang["mgrlog_query_msg"] ?></div>

            <form name="logging" method="post" action="index.php" class="form-group">
                <input type="hidden" name="a" value="13">
                <div class="row form-row">
                    <div class="col-sm-4 col-md-3 col-lg-2"><b><?= $_lang["mgrlog_user"] ?></b></div>
                    <div class="col-sm-8 col-md-5 col-lg-4">
                        <select name="searchuser" class="form-control">
                            <option value="0"><?= $_lang["mgrlog_anyall"] ?></option>
                            <?php
                            // get all users currently in the log
                            $logs_user = record_sort(array_unique_multi($logs, 'internalKey'), 'username');
                            foreach ($logs_user as $row) {
                                $selectedtext = $row['internalKey'] == $_REQUEST['searchuser'] ? ' selected="selected"' : '';
                                echo "\t\t" . '<option value="' . $row['internalKey'] . '"' . $selectedtext . '>' . $row['username'] . "</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="row form-row">
                    <div class="col-sm-4 col-md-3 col-lg-2"><b><?= $_lang["mgrlog_action"] ?></b></div>
                    <div class="col-sm-8 col-md-5 col-lg-4">
                        <select name="action" class="form-control">
                            <option value="0"><?= $_lang["mgrlog_anyall"] ?></option>
                            <?php
                            // get all available actions in the log
                            include_once "actionlist.inc.php";
                            $logs_actions = record_sort(array_unique_multi($logs, 'action'), 'action');
                            foreach ($logs_actions as $row) {
                                $action = getAction($row['action']);
                                if ($action == 'Idle') {
                                    continue;
                                }
                                $selectedtext = $row['action'] == $_REQUEST['action'] ? ' selected="selected"' : '';
                                echo "\t\t" . '<option value="' . $row['action'] . '"' . $selectedtext . '>' . $row['action'] . ' - ' . $action . "</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="row form-row">
                    <div class="col-sm-4 col-md-3 col-lg-2"><b><?= $_lang["mgrlog_itemid"] ?></b></div>
                    <div class="col-sm-8 col-md-5 col-lg-4">
                        <select name="itemid" class="form-control">
                            <option value="0"><?= $_lang["mgrlog_anyall"] ?></option>
                            <?php
                            // get all itemid currently in logging
                            $logs_items = record_sort(array_unique_multi($logs, 'itemid'), 'itemid');
                            foreach ($logs_items as $row) {
                                $selectedtext = $row['itemid'] == $_REQUEST['itemid'] ? ' selected="selected"' : '';
                                echo "\t\t" . '<option value="' . $row['itemid'] . '"' . $selectedtext . '>' . $row['itemid'] . "</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="row form-row">
                    <div class="col-sm-4 col-md-3 col-lg-2"><b><?= $_lang["mgrlog_itemname"] ?></b></div>
                    <div class="col-sm-8 col-md-5 col-lg-4">
                        <select name="itemname" class="form-control">
                            <option value="0"><?= $_lang["mgrlog_anyall"] ?></option>
                            <?php
                            // get all itemname currently in logging
                            $logs_names = record_sort(array_unique_multi($logs, 'itemname'), 'itemname');
                            foreach ($logs_names as $row) {
                                $selectedtext = $row['itemname'] == $_REQUEST['itemname'] ? ' selected="selected"' : '';
                                echo "\t\t" . '<option value="' . $row['itemname'] . '"' . $selectedtext . '>' . $row['itemname'] . "</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="row form-row">
                    <div class="col-sm-4 col-md-3 col-lg-2"><b><?= $_lang["mgrlog_msg"] ?></b></div>
                    <div class="col-sm-8 col-md-5 col-lg-4">
                        <input type="text" name="message" class="form-control" value="<?= $_REQUEST['message'] ?>" />
                    </div>
                </div>
                <div class="row form-row">
                    <div class="col-sm-4 col-md-3 col-lg-2"><b><?= $_lang["mgrlog_datefr"] ?></b></div>
                    <div class="col-sm-8 col-md-5 col-lg-4">
                        <div class="input-group">
                            <input type="text" id="datefrom" name="datefrom" class="form-control unstyled DatePicker" value="<?= isset($_REQUEST['datefrom']) ? $_REQUEST['datefrom'] : "" ?>" />
                            <i onClick="document.logging.datefrom.value=''; return true;" class="clearDate <?php echo $_style["actions_calendar_delete"] ?>" title="<?php echo $_lang['remove_date']; ?>"></i>
                        </div>
                    </div>
                </div>
                <div class="row form-row">
                    <div class="col-sm-4 col-md-3 col-lg-2"><b><?= $_lang["mgrlog_dateto"] ?></b></div>
                    <div class="col-sm-8 col-md-5 col-lg-4">
                        <div class="input-group">
                            <input type="text" id="dateto" name="dateto" class="form-control unstyled DatePicker" value="<?= isset($_REQUEST['dateto']) ? $_REQUEST['dateto'] : "" ?>" />
                            <i onClick="document.logging.dateto.value=''; return true;" class="clearDate <?php echo $_style["actions_calendar_delete"] ?>" title="<?php echo $_lang['remove_date']; ?>"></i>
                        </div>
                    </div>
                </div>
                <div class="row form-row">
                    <div class="col-sm-4 col-md-3 col-lg-2"><b><?= $_lang["mgrlog_results"] ?></b></div>
                    <div class="col-sm-8 col-md-5 col-lg-4">
                        <input type="text" name="nrresults" class="form-control" value="<?= isset($_REQUEST['nrresults']) ? $_REQUEST['nrresults'] : $number_of_logs ?>" />
                    </div>
                </div>

                <a class="btn btn-success" href="javascript:;" onclick="documentDirty=false;document.logging.log_submit.click();"><i class="<?= $_style["actions_search"] ?>"></i> <?= $_lang['search'] ?></a>
                <a class="btn btn-secondary" href="index.php?a=2" onclick="documentDirty=false;"><i class="<?= $_style["actions_cancel"] ?>"></i> <?= $_lang['cancel'] ?></a>

                <input type="submit" name="log_submit" value="<?= $_lang["mgrlog_searchlogs"] ?>" style="display:none;" />
            </form>

        </div>
    </div>

    <div class="navbar">
        <?= $_lang["mgrlog_qresults"] ?>
    </div>

    <div class="tab-page">
    <div class="container container-body">

<?php
if (isset($_REQUEST['log_submit'])) {
    // get the selections the user made.
    $sqladd = array();
    if ($_REQUEST['searchuser'] != 0) {
        $sqladd[] = "internalKey='" . (int)$_REQUEST['searchuser'] . "'";
    }
    if ($_REQUEST['action'] != 0) {
        $sqladd[] = "action=" . (int)$_REQUEST['action'];
    }
    if ($_REQUEST['itemid'] != 0 || $_REQUEST['itemid'] == "-") {
        $sqladd[] = "itemid='" . $_REQUEST['itemid'] . "'";
    }
    if ($_REQUEST['itemname'] != '0') {
        $sqladd[] = "itemname='" . $modx->db->escape($_REQUEST['itemname']) . "'";
    }
    if ($_REQUEST['message'] != "") {
        $sqladd[] = "message LIKE '%" . $modx->db->escape($_REQUEST['message']) . "%'";
    }
    // date stuff
    if ($_REQUEST['datefrom'] != "") {
        $sqladd[] = "timestamp>" . $modx->toTimeStamp($_REQUEST['datefrom']);
    }
    if ($_REQUEST['dateto'] != "") {
        $sqladd[] = "timestamp<" . $modx->toTimeStamp($_REQUEST['dateto']);
    }

    // If current position is not set, set it to zero
    if (!isset($_REQUEST['int_cur_position']) || $_REQUEST['int_cur_position'] == 0) {
        $int_cur_position = 0;
    } else {
        $int_cur_position = $_REQUEST['int_cur_position'];
    }

    // Number of result to display on the page, will be in the LIMIT of the sql query also
    $int_num_result = is_numeric($_REQUEST['nrresults']) ? $_REQUEST['nrresults'] : $number_of_logs;

    $extargv = "&a=13&searchuser=" . $_REQUEST['searchuser'] . "&action=" . $_REQUEST['action'] . "&itemid=" . $_REQUEST['itemid'] . "&itemname=" . $_REQUEST['itemname'] . "&message=" . $_REQUEST['message'] . "&dateto=" . $_REQUEST['dateto'] . "&datefrom=" . $_REQUEST['datefrom'] . "&nrresults=" . $int_num_result . "&log_submit=" . $_REQUEST['log_submit']; // extra argv here (could be anything depending on your page)

    // build the sql
    $limit = $num_rows = $modx->db->getValue($modx->db->select('COUNT(*)', $modx->getFullTableName('manager_log'), (!empty($sqladd) ? implode(' AND ', $sqladd) : '')));

    $rs = $modx->db->select('*', $modx->getFullTableName('manager_log'), (!empty($sqladd) ? implode(' AND ', $sqladd) : ''), 'timestamp DESC, id DESC', "{$int_cur_position}, {$int_num_result}");

if ($limit < 1) {
    echo '<p>' . $_lang["mgrlog_emptysrch"] . '</p>';
} else {
    echo '<p>' . $_lang["mgrlog_sortinst"] . '</p>';

    include_once "paginate.inc.php";
    // New instance of the Paging class, you can modify the color and the width of the html table
    $p = new Paging($num_rows, $int_cur_position, $int_num_result, $extargv);

    // Load up the 2 array in order to display result
    $array_paging = $p->getPagingArray();
    $array_row_paging = $p->getPagingRowArray();
    $current_row = $int_cur_position / $int_num_result;

    // Display the result as you like...
    print "<p>" . $_lang["paging_showing"] . " " . $array_paging['lower'];
    print " " . $_lang["paging_to"] . " " . $array_paging['upper'];
    print " (" . $array_paging['total'] . " " . $_lang["paging_total"] . ")<br />";
    $paging = $array_paging['first_link'] . $_lang["paging_first"] . (isset($array_paging['first_link']) ? "</a> " : " ");
    $paging .= $array_paging['previous_link'] . $_lang["paging_prev"] . (isset($array_paging['previous_link']) ? "</a> " : " ");
    $pagesfound = sizeof($array_row_paging);
    if ($pagesfound > 6) {
        $paging .= $array_row_paging[$current_row - 2]; // ."&nbsp;";
        $paging .= $array_row_paging[$current_row - 1]; // ."&nbsp;";
        $paging .= $array_row_paging[$current_row]; // ."&nbsp;";
        $paging .= $array_row_paging[$current_row + 1]; // ."&nbsp;";
        $paging .= $array_row_paging[$current_row + 2]; // ."&nbsp;";
    } else {
        for ($i = 0; $i < $pagesfound; $i++) {
            $paging .= $array_row_paging[$i] . "&nbsp;";
        }
    }
    $paging .= $array_paging['next_link'] . $_lang["paging_next"] . (isset($array_paging['next_link']) ? "</a> " : " ") . " ";
    $paging .= $array_paging['last_link'] . $_lang["paging_last"] . (isset($array_paging['last_link']) ? "</a> " : " ") . " ";
    // The above exemple print somethings like:
    // Results 1 to 20 of 597  <<< 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 >>>
    // Of course you can now play with array_row_paging in order to print
    // only the results you would like...
    ?>

    <script type="text/javascript" src="media/script/tablesort.js"></script>

    <div class="pagination">
        <?= $paging ?>
    </div>

    <div class="row">
        <div class="table-responsive">
            <table class="table data">
                <thead>
                <tr>
                    <th class="sortable" width="1%"><?= $_lang["mgrlog_username"] ?></th>
                    <th class="sortable"><?= $_lang["mgrlog_action"] ?></th>
                    <th class="sortable sortable-numeric" width="1%"><?= $_lang["mgrlog_itemid"] ?></th>
                    <th class="sortable"><?= $_lang["mgrlog_itemname"] ?></th>
                    <th class="sortable" width="1%"><?= $_lang["mgrlog_time"] ?></th>
                    <th class="sortable" width="1%">IP</th>
                    <th class="sortable" width="1%">USER_AGENT</th>
                </tr>
                </thead>
                <tbody>
                <?php
                // grab the entire log file...
                $logentries = array();
                $i = 0;
                while ($logentry = $modx->db->getRow($rs)) {
                    if (!preg_match("/^[0-9]+$/", $logentry['itemid'])) {
                        $item = '<div style="text-align:center;">-</div>';
                    } elseif ($logentry['action'] == 3 || $logentry['action'] == 27 || $logentry['action'] == 5) {
                        $item = '<a href="index.php?a=3&amp;id=' . $logentry['itemid'] . '">' . $logentry['itemname'] . '</a>';
                    } else {
                        $item = $logentry['itemname'];
                    }
                    //index.php?a=13&searchuser=' . $logentry['internalKey'] . '&action=' . $logentry['action'] . '&itemname=' . $logentry['itemname'] . '&log_submit=true'
                    $user_drill = 'index.php?a=13&searchuser=' . $logentry['internalKey'] . '&itemname=0&log_submit=true';
                    ?>
                    <tr>
                        <td><?= '<a href="' . $user_drill . '">' . $logentry['username'] . '</a>' ?></td>
                        <td class="text-nowrap"><?= '[' . $logentry['action'] . '] ' . $logentry['message'] ?></td>
                        <td class="text-xs-right"><?= $logentry['itemid'] ?></td>
                        <td><?= $item ?></td>
                        <td class="text-nowrap"><?= $modx->toDateFormat($logentry['timestamp'] + $server_offset_time) ?></td>
                        <td class="text-nowrap"><?= $logentry['ip'] ?></td>
                        <td class="text-nowrap"><?= $logentry['useragent'] ?></td>
                    </tr>
                    <?php
                    $i++;
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="pagination">
        <?= $paging ?>
    </div>
<?php
}
?>
    </div>
    </div>
    <?php
    // HACK: prevent multiple "Viewing logging" entries after a search has taken place.
    // @see index.php @ 915
    global $action;
    $action = 1;
} else {
    echo $_lang["mgrlog_noquery"];
}
