<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('messages')) {
    $e->setError(3);
    $e->dumpError();
}
?>
<h1><?php echo $_lang['messages_title']; ?></h1>

<?php if(isset($_REQUEST['id']) && $_REQUEST['m']=='r') { ?>
<div class="section">
<div class="sectionHeader"><?php echo $_lang['messages_read_message']; ?></div><div class="sectionBody" id="lyr3">
<?php
$sql = "SELECT * FROM $dbase.`".$table_prefix."user_messages` WHERE $dbase.`".$table_prefix."user_messages`.id=".(int)$_REQUEST['id'];
$rs = $modx->db->query($sql);
$limit = $modx->db->getRecordCount($rs);
if($limit!=1) {
    echo "Wrong number of messages returned!";
} else {
    $message=$modx->db->getRow($rs);
    if($message['recipient']!=$modx->getLoginUserID()) {
        echo $_lang['messages_not_allowed_to_read'];
    } else {
        // output message!
        // get the name of the sender
        $sender = $message['sender'];
        if($sender==0) {
            $sendername = $_lang['messages_system_user'];
        } else {
            $sql = "SELECT username FROM $dbase.`".$table_prefix."manager_users` WHERE id=$sender";
            $rs2 = $modx->db->query($sql);
            $row2 = $modx->db->getRow($rs2);
            $sendername = $row2['username'];
        }
?>
<table width="600" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td colspan="2">
    <ul class="actionButtons">
        <li id="btn_reply"><a href="index.php?a=10&t=c&m=rp&id=<?php echo $message['id']; ?>"><img src="<?php echo $_style["icons_message_reply"] ?>" /> <?php echo $_lang['messages_reply']; ?></a></li>
        <li><a href="index.php?a=10&t=c&m=f&id=<?php echo $message['id']; ?>"><img src="<?php echo $_style["icons_message_forward"] ?>" /> <?php echo $_lang['messages_forward']; ?></a></li>
        <li><a href="index.php?a=65&id=<?php echo $message['id']; ?>"><img src="<?php echo $_style["icons_delete_document"] ?>" /> <?php echo $_lang['delete']; ?></a></li>
		<?php if($message['sender']==0) { ?>
			<script type="text/javascript">document.getElementById("btn_reply").className='disabled';</script>
		<?php } ?>
    </ul>
    </td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td style="width: 120px;"><b><?php echo $_lang['messages_from']; ?>:</b></td>
    <td style="width: 480px;"><?php echo $sendername; ?></td>
  </tr>
  <tr>
    <td><b><?php echo $_lang['messages_sent']; ?>:</b></td>
    <td><?php echo $modx->toDateFormat($message['postdate']+$server_offset_time); ?></td>
  </tr>
  <tr>
    <td><b><?php echo $_lang['messages_subject']; ?>:</b></td>
    <td><?php echo $message['subject']; ?></td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td colspan="2">
    <?php
    // format the message :)
    $message = str_replace ("\n", "<br />", $message['message']);
    $dashcount = substr_count($message, "-----");
    $message = str_replace ("-----", "<i style='color:#666;'>", $message);
    for( $i=0; $i<$dashcount; $i++ ){
    $message .= "</i>";
    }

    echo $message;
    ?>

    </td>
  </tr>
</table>
<?php
        // mark the message as read
        $sql = "UPDATE $dbase.`".$table_prefix."user_messages` SET $dbase.`".$table_prefix."user_messages`.messageread=1 WHERE $dbase.`".$table_prefix."user_messages`.id=".$_REQUEST['id'];
        $rs = $modx->db->query($sql);
    }
}
?>
    </div>
</div>
<?php } ?>

<div class="section">
<div class="sectionHeader"><?php echo $_lang['messages_inbox']; ?></div><div class="sectionBody">
<?php
// Get  number of rows
$sql = "SELECT count(id) FROM $dbase.`".$table_prefix."user_messages` WHERE recipient=".$modx->getLoginUserID()."";
$rs=$modx->db->query($sql);
$countrows = $modx->db->getRow($rs);
$num_rows = $countrows['count(id)'];

// ==============================================================
// Exemple Usage
// Note: I make 2 query to the database for this exemple, it
// could (and should) be made with only one query...
// ==============================================================

// If current position is not set, set it to zero
if( !isset( $_REQUEST['int_cur_position'] ) || $_REQUEST['int_cur_position'] == 0 ){
  $int_cur_position = 0;
} else {
    $int_cur_position = (int)$_REQUEST['int_cur_position'];
}

// Number of result to display on the page, will be in the LIMIT of the sql query also
$int_num_result = $number_of_messages;


$extargv =  "&a=10"; // extra argv here (could be anything depending on your page)

include_once "paginate.inc.php";
// New instance of the Paging class, you can modify the color and the width of the html table
$p = new Paging( $num_rows, $int_cur_position, $int_num_result, $extargv );

// Load up the 2 array in order to display result
$array_paging = $p->getPagingArray();
$array_row_paging = $p->getPagingRowArray();

// Display the result as you like...
$pager .= $_lang['showing']." ". $array_paging['lower'];
$pager .=  " ".$_lang['to']." ". $array_paging['upper'];
$pager .=  " (". $array_paging['total']." ".$_lang['total'].")";
$pager .=  "<br />". $array_paging['previous_link'] ."&lt;&lt;" . (isset($array_paging['previous_link']) ? "</a> " : " ");
for( $i=0; $i<sizeof($array_row_paging); $i++ ){
  $pager .=  $array_row_paging[$i] ."&nbsp;";
}
$pager .=  $array_paging['next_link'] ."&gt;&gt;". (isset($array_paging['next_link']) ? "</a>" : "");

// The above exemple print somethings like:
// Results 1 to 20 of 597  <<< 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 >>>
// Of course you can now play with array_row_paging in order to print
// only the results you would like...

$sql = "SELECT * FROM $dbase.`".$table_prefix."user_messages` WHERE $dbase.`".$table_prefix."user_messages`.recipient=".$modx->getLoginUserID()." ORDER BY postdate DESC LIMIT ".$int_cur_position.", ".$int_num_result;
$rs = $modx->db->query($sql);
$limit = $modx->db->getRecordCount($rs);
if($limit<1) {
    echo $_lang['messages_no_messages'];
} else {
echo $pager;
$dotablestuff = 1;
?>
<script type="text/javascript" src="media/script/tablesort.js"></script>
  <table border=0 cellpadding=0 cellspacing=0  class="sortabletable sortable-onload-5 rowstyle-even" id="table-1" width='100%'>
    <thead>
      <tr bgcolor='#CCCCCC'>
        <th width="12"></th>
        <th width="60%" class="sortable"><b><?php echo $_lang['messages_subject']; ?></b></th>
        <th class="sortable"><b><?php echo $_lang['messages_from']; ?></b></th>
        <th class="sortable"><b><?php echo $_lang['messages_private']; ?></b></th>
        <th width="20%" class="sortable"><b><?php echo $_lang['messages_sent']; ?></b></th>
      </tr>
    </thead>
    <tbody>
<?php
        for ($i = 0; $i < $limit; $i++) {
            $message = $modx->db->getRow($rs);
            $sender = $message['sender'];
            if($sender==0) {
                $sendername = "[System]";
            } else {
                $sql = "SELECT username FROM $dbase.`".$table_prefix."manager_users` WHERE id=$sender";
                $rs2 = $modx->db->query($sql);
                $row2 = $modx->db->getRow($rs2);
                $sendername = $row2['username'];
            }
            $messagestyle = $message['messageread']==0 ? "messageUnread" : "messageRead";
?>
    <tr>
      <td ><?php echo $message['messageread']==0 ? "<img src='media/style/" . ($manager_theme ? "$manager_theme/":"") ."images/icons/new1-09.gif'>" : ""; ?></td>
      <td class="<?php echo $messagestyle; ?>" style="cursor: pointer; text-decoration: underline;" onClick="document.location.href='index.php?a=10&id=<?php echo $message['id']; ?>&m=r';"><?php echo $message['subject']; ?></td>
      <td ><?php echo $sendername; ?></td>
      <td ><?php echo $message['private']==0 ? $_lang['no'] : $_lang['yes'] ; ?></td>
      <td ><?php echo $modx->toDateFormat($message['postdate']+$server_offset_time); ?></td>
    </tr>
    <?php
            }
    }

if($dotablestuff==1) { ?>
</tbody>
</table>
<?php } ?>
    </div></div>
<div class="section">
<div class="sectionHeader"><?php echo $_lang['messages_compose']; ?></div><div class="sectionBody">
<?php
if(($_REQUEST['m']=='rp' || $_REQUEST['m']=='f') && isset($_REQUEST['id'])) {
    $sql = "SELECT * FROM $dbase.`".$table_prefix."user_messages` WHERE $dbase.`".$table_prefix."user_messages`.id=".$_REQUEST['id'];
    $rs = $modx->db->query($sql);
    $limit = $modx->db->getRecordCount($rs);
    if($limit!=1) {
        echo "Wrong number of messages returned!";
    } else {
        $message=$modx->db->getRow($rs);
        if($message['recipient']!=$modx->getLoginUserID()) {
            echo $_lang['messages_not_allowed_to_read'];
        } else {
            // output message!
            // get the name of the sender
            $sender = $message['sender'];
            if($sender==0) {
                $sendername = "[System]";
            } else {
                $sql = "SELECT username FROM $dbase.`".$table_prefix."manager_users` WHERE id=$sender";
                $rs2 = $modx->db->query($sql);
                $row2 = $modx->db->getRow($rs2);
                $sendername = $row2['username'];
            }
            $subjecttext = $_REQUEST['m']=='rp' ? "Re: " : "Fwd: ";
            $subjecttext .= $message['subject'];
            $messagetext = "\n\n\n-----\n".$_lang['messages_from'].": $sendername\n".$_lang['messages_sent'].": ".$modx->toDateFormat($message['postdate']+$server_offset_time)."\n".$_lang['messages_subject'].": ".$message['subject']."\n\n".$message['message'];
            if($_REQUEST['m']=='rp') {
                $recipientindex = $message['sender'];
            }
        }
    }
}



?>

<script type="text/javascript">
function hideSpans(showSpan) {
    document.getElementById("userspan").style.display="none";
    document.getElementById("groupspan").style.display="none";
    document.getElementById("allspan").style.display="none";
    if(showSpan==1) {
        document.getElementById("userspan").style.display="block";
    }
    if(showSpan==2) {
        document.getElementById("groupspan").style.display="block";
    }
    if(showSpan==3) {
        document.getElementById("allspan").style.display="block";
    }
}
</script>
<form action="index.php?a=66" method="post" name="messagefrm">
<fieldset style="width: 600px;">
<LEGEND><b><?php echo $_lang['messages_send_to']; ?>:</b></LEGEND>
<table width="100%"  border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
    <INPUT TYPE=RADIO NAME="sendto" VALUE="u" checked onClick='hideSpans(1);'><?php echo $_lang['messages_user']; ?>&nbsp;&nbsp;&nbsp;
    <INPUT TYPE=RADIO NAME="sendto" VALUE="g" onClick='hideSpans(2);'><?php echo $_lang['messages_group']; ?>&nbsp;&nbsp;&nbsp;
    <INPUT TYPE=RADIO NAME="sendto" VALUE="a" onClick='hideSpans(3);'><?php echo $_lang['messages_all']; ?>&nbsp;&nbsp;<br />
<span id='userspan' style="display:block;"> <?php echo $_lang['messages_select_user']; ?>:&nbsp;
    <?php
    // get all usernames
    $sql = "SELECT username, id FROM $dbase.`".$table_prefix."manager_users`";
    $rs = $modx->db->query($sql);
    ?>
    <select name="user" class="inputBox" style="width:150px">
    <?php
        while ($row = $modx->db->getRow($rs)) {
            ?>
            <option value="<?php echo $row['id']; ?>" ><?php echo $row['username']; ?></option>
            <?php
        }
    ?>
    </select>
</span>
<span id='groupspan' style="display:none;"> <?php echo $_lang['messages_select_group']; ?>:&nbsp;
    <?php
    // get all usernames
    $sql = "SELECT name, id FROM $dbase.`".$table_prefix."user_roles`";
    $rs = $modx->db->query($sql);
    ?>
    <select name="group" class="inputBox" style="width:150px">
    <?php
    while ($row = $modx->db->getRow($rs)) {
        ?>
        <option value="<?php echo $row['id']; ?>" ><?php echo $row['name']; ?></option>
        <?php
    }
    ?>
</select>
</span>
<span id='allspan' style="display:none;">
</span>
    </td>
  </tr>
</table>
</fieldset>

<p>

<fieldset style="width: 600px;">
<LEGEND><b><?php echo $_lang['messages_message']; ?>:</b></LEGEND>

<table width="100%"  border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td><?php echo $_lang['messages_subject']; ?>:</td>
    <td><input name="messagesubject" type=text class="inputBox" style="width: 500px;" maxlength="60" value="<?php echo $subjecttext; ?>"></td>
  </tr>
  <tr>
    <td valign="top"><?php echo $_lang['messages_message']; ?>:</td>
    <td><textarea name="messagebody" style="width:500px; height: 200px;" onLoad="this.focus()" class="inputBox"><?php echo $messagetext; ?></textarea></td>
  </tr>
  <tr>
    <td></td>
  </tr>
</table>

<ul class="actionButtons">
        <li><a href="#" onclick="documentDirty=false; document.messagefrm.submit();"><img src="<?php echo $_style["icons_save"] ?>" /> <?php echo $_lang['messages_send']; ?></a></li>
        <li><a href="index.php?a=10&t=c"><img src="<?php echo $_style["icons_cancel"] ?>" /> <?php echo $_lang['cancel']; ?></a></li>
</ul>

</fieldset>
</form>
</div></div>

<?php
// count messages again, as any action on the messages page may have altered the message count
$sql="SELECT count(*) FROM $dbase.`".$table_prefix."user_messages` where recipient=".$modx->getLoginUserID()." and messageread=0;";
$rs = $modx->db->query($sql);
$row = $modx->db->getRow($rs);
$_SESSION['nrnewmessages'] = $row['count(*)'];
$sql="SELECT count(*) FROM $dbase.`".$table_prefix."user_messages` where recipient=".$modx->getLoginUserID()."";
$rs = $modx->db->query($sql);
$row = $modx->db->getRow($rs);
$_SESSION['nrtotalmessages'] = $row['count(*)'];
$messagesallowed = $modx->hasPermission('messages');
?>
<script type="text/javascript">
function msgCountAgain() {
    try {
        top.mainMenu.startmsgcount(<?php echo $_SESSION['nrnewmessages'] ; ?>,<?php echo $_SESSION['nrtotalmessages'] ; ?>,<?php echo $messagesallowed ? 1:0 ; ?>);
    } catch(oException) {
        vv = window.setTimeout('msgCountAgain()',1500);
    }
}

v = setTimeout('msgCountAgain()', 1500); // do this with a slight delay so it overwrites msgCount()

</script>
