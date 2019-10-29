<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('messages')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}
?>

<h1>
    <i class="fa fa-envelope"></i><?= $_lang['messages_title'] ?>
</h1>

<?php if (isset($_REQUEST['id']) && $_REQUEST['m'] == 'r') { ?>
    <div class="tab-page">
        <div class="container container-body" id="lyr3">
            <b><?= $_lang['messages_read_message'] ?></b>
            <?php
            $rs = $modx->db->select('*', $modx->getFullTableName('user_messages'), "id='" . (int)$_REQUEST['id'] . "'");
            $message = $modx->db->getRow($rs);
            if (!$message) {
                echo "Wrong number of messages returned!";
            } else {
                if ($message['recipient'] != $modx->getLoginUserID()) {
                    echo $_lang['messages_not_allowed_to_read'];
                } else {
                    // output message!
                    // get the name of the sender
                    $sender = $message['sender'];
                    if ($sender == 0) {
                        $sendername = $_lang['messages_system_user'];
                    } else {
                        $rs2 = $modx->db->select('username', $modx->getFullTableName('manager_users'), "id='{$sender}'");
                        $sendername = $modx->db->getValue($rs2);
                    }
                    ?>
                    <div class="btn-group float-right">
                        <a id="Button1" class="btn btn-secondary btn-sm<?= ($message['sender'] == 0 ? ' disabled' : '') ?>" href="index.php?a=10&t=c&m=rp&id=<?= $message['id'] ?>"><i class="fa fa-reply"></i> <?= $_lang['messages_reply'] ?></a>
                        <a id="Button2" class="btn btn-secondary btn-sm" href="index.php?a=10&t=c&m=f&id=<?= $message['id'] ?>"><i class="fa fa-forward"></i> <?= $_lang['messages_forward'] ?></a>
                        <a id="Button3" class="btn btn-outline-danger btn-sm" href="index.php?a=65&id=<?= $message['id'] ?>"><i class="<?= $_style["actions_delete"] ?>"></i> <?= $_lang['delete'] ?></a>
                    </div>
                    <p class="clearfix"></p>
                    <div class="form-group card">
                        <div class="card-header">
                            <table>
                                <tr>
                                    <td><b><?= $_lang['messages_from'] ?>:</b></td>
                                    <td>&nbsp;</td>
                                    <td><?= $sendername ?></td>
                                </tr>
                                <tr>
                                    <td><b><?= $_lang['messages_sent'] ?>:</b></td>
                                    <td>&nbsp;</td>
                                    <td><?= $modx->toDateFormat($message['postdate'] + $server_offset_time) ?></td>
                                </tr>
                                <tr>
                                    <td><b><?= $_lang['messages_subject'] ?>:</b></td>
                                    <td>&nbsp;</td>
                                    <td><?= $message['subject'] ?></td>
                                </tr>
                            </table>
                        </div>
                        <?php
                        // format the message :)
                        $message = str_replace("\n", '<br />', $message['message']);
                        $dashcount = substr_count($message, '-----');
                        $message = str_replace('-----', '<i class="text-muted">', $message);
                        for ($i = 0; $i < $dashcount; $i++) {
                            $message .= '</i>';
                        }
                        ?>
                        <div class="card-block"><?= $message ?></div>
                    </div>
                    <?php
                    // mark the message as read
                    $modx->db->update(array('messageread' => 1), $modx->getFullTableName('user_messages'), "id='{$_REQUEST['id']}'");
                }
            }
            ?>
        </div>
    </div>
    <p></p>
<?php } ?>

<div class="tab-page">
    <div class="container container-body">
        <p><b><?= $_lang['messages_inbox'] ?></b></p>
        <?php
        // Get  number of rows
        $rs = $modx->db->select('count(id)', $modx->getFullTableName('user_messages'), "recipient=" . $modx->getLoginUserID() . "");
        $num_rows = $modx->db->getValue($rs);

        // ==============================================================
        // Exemple Usage
        // Note: I make 2 query to the database for this exemple, it
        // could (and should) be made with only one query...
        // ==============================================================

        // If current position is not set, set it to zero
        if (!isset($_REQUEST['int_cur_position']) || $_REQUEST['int_cur_position'] == 0) {
            $int_cur_position = 0;
        } else {
            $int_cur_position = (int)$_REQUEST['int_cur_position'];
        }

        // Number of result to display on the page, will be in the LIMIT of the sql query also
        $int_num_result = $number_of_messages;

        $extargv = "&a=10"; // extra argv here (could be anything depending on your page)

        include_once "paginate.inc.php";
        // New instance of the Paging class, you can modify the color and the width of the html table
        $p = new Paging($num_rows, $int_cur_position, $int_num_result, $extargv);

        // Load up the 2 array in order to display result
        $array_paging = $p->getPagingArray();
        $array_row_paging = $p->getPagingRowArray();

        // Display the result as you like...
        $pager .= $_lang['showing'] . " " . $array_paging['lower'];
        $pager .= " " . $_lang['to'] . " " . $array_paging['upper'];
        $pager .= " (" . $array_paging['total'] . " " . $_lang['total'] . ")";
        $pager .= "<br />" . $array_paging['previous_link'] . "&lt;&lt;" . (isset($array_paging['previous_link']) ? "</a> " : " ");
        for ($i = 0; $i < sizeof($array_row_paging); $i++) {
            $pager .= $array_row_paging[$i] . "&nbsp;";
        }
        $pager .= $array_paging['next_link'] . "&gt;&gt;" . (isset($array_paging['next_link']) ? "</a>" : "");

        // The above exemple print somethings like:
        // Results 1 to 20 of 597  <<< 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 >>>
        // Of course you can now play with array_row_paging in order to print
        // only the results you would like...

        $rs = $modx->db->select('*', $modx->getFullTableName('user_messages'), "recipient=" . $modx->getLoginUserID() . "", 'postdate DESC', "{$int_cur_position}, {$int_num_result}");
        $limit = $modx->db->getRecordCount($rs);
        if ($limit < 1) {
            echo $_lang['messages_no_messages'];
        } else {
            $dotablestuff = 1;
            ?>
            <?= $pager ?>
            <script type="text/javascript" src="media/script/tablesort.js"></script>
            <div class="row">
                <div class="table-responsive">
                    <table class="table data nowrap table-sm" id="table-1">
                        <thead>
                        <tr>
                            <th width="12"></th>
                            <th width="60%" class="sortable"><b><?= $_lang['messages_subject'] ?></b></th>
                            <th class="sortable"><b><?= $_lang['messages_from'] ?></b></th>
                            <th class="sortable"><b><?= $_lang['messages_private'] ?></b></th>
                            <th width="20%" class="sortable"><b><?= $_lang['messages_sent'] ?></b></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        while ($message = $modx->db->getRow($rs)) {
                            $sender = $message['sender'];
                            if ($sender == 0) {
                                $sendername = "[System]";
                            } else {
                                $rs2 = $modx->db->select('username', $modx->getFullTableName('manager_users'), "id='{$sender}'");
                                $sendername = $modx->db->getValue($rs2);
                            }
                            $messagestyle = $message['messageread'] == 0 ? "text-primary" : "";
                            ?>
                            <tr>
                                <td><?= $message['messageread'] == 0 ? '<i class="fa fa-envelope"></i>' : "" ?></td>
                                <td class="<?= $messagestyle ?>" style="cursor: pointer;" onClick="window.location.href='index.php?a=10&id=<?= $message['id'] ?>&m=r';"><?= $message['subject'] ?></td>
                                <td><?= $sendername ?></td>
                                <td><?= $message['private'] == 0 ? $_lang['no'] : $_lang['yes'] ?></td>
                                <td><?= $modx->toDateFormat($message['postdate'] + $server_offset_time) ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<p></p>

<div class="tab-page">
    <div class="container container-body">
        <p><b><?= $_lang['messages_compose'] ?></b></p>
        <?php
        if (($_REQUEST['m'] == 'rp' || $_REQUEST['m'] == 'f') && isset($_REQUEST['id'])) {
            $rs = $modx->db->select('*', $modx->getFullTableName('user_messages'), "id='" . $_REQUEST['id'] . "'");
            $message = $modx->db->getRow($rs);
            if (!$message) {
                echo "Wrong number of messages returned!";
            } else {
                if ($message['recipient'] != $modx->getLoginUserID()) {
                    echo $_lang['messages_not_allowed_to_read'];
                } else {
                    // output message!
                    // get the name of the sender
                    $sender = $message['sender'];
                    if ($sender == 0) {
                        $sendername = "[System]";
                    } else {
                        $rs2 = $modx->db->select('username', $modx->getFullTableName('manager_users'), "id='{$sender}'");
                        $sendername = $modx->db->getValue($rs2);
                    }
                    $subjecttext = $_REQUEST['m'] == 'rp' ? "Re: " : "Fwd: ";
                    $subjecttext .= $message['subject'];
                    $messagetext = "\n\n\n-----\n" . $_lang['messages_from'] . ": $sendername\n" . $_lang['messages_sent'] . ": " . $modx->toDateFormat($message['postdate'] + $server_offset_time) . "\n" . $_lang['messages_subject'] . ": " . $message['subject'] . "\n\n" . $message['message'];
                    if ($_REQUEST['m'] == 'rp') {
                        $recipientindex = $message['sender'];
                    }
                }
            }
        }
        ?>

        <script type="text/javascript">
            function hideSpans(showSpan)
            {
                document.getElementById('userspan').style.display = 'none';
                document.getElementById('groupspan').style.display = 'none';
                document.getElementById('allspan').style.display = 'none';
                if (showSpan === 1) {
                    document.getElementById('userspan').style.display = 'block';
                }
                if (showSpan === 2) {
                    document.getElementById('groupspan').style.display = 'block';
                }
                if (showSpan === 3) {
                    document.getElementById('allspan').style.display = 'block';
                }
            }
        </script>
        <form name="messagefrm" method="post" action="index.php">
            <input type="hidden" name="a" value="66">
            <b><?= $_lang['messages_send_to'] ?>:</b>
            <div class="form-group form-inline">
                <div class="row form-row">
                    <div class="col-xs-12">
                        <label class="mr-1"><input class="form-check-input" type="radio" name="sendto" value="u" checked onClick='hideSpans(1);' /> <?= $_lang['messages_user'] ?></label>
                        <label class="mr-1"><input class="form-check-input" type="radio" name="sendto" value="g" onClick='hideSpans(2);' /> <?= $_lang['messages_group'] ?></label>
                        <label class="mr-1"><input class="form-check-input" type="radio" name="sendto" value="a" onClick='hideSpans(3);' /> <?= $_lang['messages_all'] ?></label>
                    </div>
                </div>
                <div id='userspan' style="display:block;"> <?= $_lang['messages_select_user'] ?>:&nbsp;
                    <?php
                    // get all usernames
                    $rs = $modx->db->select('username, id', $modx->getFullTableName('manager_users'));
                    ?>
                    <select name="user" class="form-control form-control-sm" size="1">
                        <?php
                        while ($row = $modx->db->getRow($rs)) {
                            ?>
                            <option value="<?= $row['id'] ?>"><?= $row['username'] ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
                <div id='groupspan' style="display:none;"> <?= $_lang['messages_select_group'] ?>:&nbsp;
                    <?php
                    // get all usernames
                    $rs = $modx->db->select('name, id', $modx->getFullTableName('user_roles'));
                    ?>
                    <select name="group" class="form-control form-control-sm" size="1">
                        <?php
                        while ($row = $modx->db->getRow($rs)) {
                            ?>
                            <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
                <div id='allspan' style="display:none;"></div>
            </div>
            <div class="form-group">
                <b><?= $_lang['messages_message'] ?>:</b>
                <div class="row form-row">
                    <div class="col-xs-12"><?= $_lang['messages_subject'] ?>:</div>
                    <div class="col-xs-12"><input name="messagesubject" type=text class="form-control" maxlength="60" value="<?= $subjecttext ?>" /></div>
                </div>
                <div class="row form-row">
                    <div class="col-xs-12"><?= $_lang['messages_message'] ?>:</div>
                    <div class="col-xs-12"><textarea name="messagebody" rows="10" onLoad="this.focus();" class="form-control"><?= $messagetext ?></textarea></div>
                </div>
            </div>
            <a href="javascript:;" class="btn btn-success" onclick="documentDirty=false; document.messagefrm.submit();"><i class="<?= $_style["actions_save"] ?>"></i> <?= $_lang['messages_send'] ?></a>
            <a href="index.php?a=10&t=c" class="btn btn-secondary"><i class="<?= $_style["actions_cancel"] ?>"></i> <?= $_lang['cancel'] ?></a>
        </form>
    </div>
</div>

<?php
// count messages again, as any action on the messages page may have altered the message count
$rs = $modx->db->select('COUNT(*)', $modx->getFullTableName('user_messages'), "recipient=" . $modx->getLoginUserID() . " and messageread=0");
$_SESSION['nrnewmessages'] = $modx->db->getValue($rs);
$rs = $modx->db->select('COUNT(*)', $modx->getFullTableName('user_messages'), "recipient=" . $modx->getLoginUserID() . "");
$_SESSION['nrtotalmessages'] = $modx->db->getValue($rs);
$messagesallowed = $modx->hasPermission('messages');
?>
<script type="text/javascript">
    function msgCountAgain()
    {
        try {
            top.mainMenu.startmsgcount(<?= $_SESSION['nrnewmessages'] ?>,<?= $_SESSION['nrtotalmessages'] ?>,<?= $messagesallowed ? 1 : 0 ?>);
        } catch (oException) {
            vv = window.setTimeout('msgCountAgain()', 1500);
        }
    }

    v = setTimeout('msgCountAgain()', 1500); // do this with a slight delay so it overwrites msgCount()
</script>
