<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('logs')) {
	$e->setError(3);
	$e->dumpError();
}

function array_unique_multi($array, $checkKey) {
	// Use the builtin if we're not a multi-dimensional array
	if (!is_array(current($array)) || empty($checkKey)) return array_unique($array);

	$ret = array();
	$checkValues = array(); // contains the unique key Values
	foreach ($array as $key => $current) {
		if (in_array($current[$checkKey], $checkValues)) continue; // duplicate

		$checkValues[] = $current[$checkKey];
		$ret[$key] = $current;
	}
	return $ret;
}

function record_sort($array, $key) {
	$hash = array();
	foreach ($array as $k => $v) $hash[$k] = $v[$key];

	natsort($hash);

	$records = array();
	foreach ($hash as $k => $row)
		$records[$k] = $array[$k];

	return $records;
}

// function to check date and convert to us date
function convertdate($date) {
	global $_lang, $modx;
	$timestamp = $modx->toTimeStamp($date);
	return $timestamp;
}

$sql = 'SELECT DISTINCT internalKey, username, action, itemid, itemname FROM '.$modx->getFullTableName('manager_log');
$rs = $modx->db->query($sql);

$logs = array();
while ($row = $modx->db->getRow($rs)) $logs[] = $row;

?>
<script type="text/javascript" src="media/calendar/datepicker.js"></script>
<script type="text/javascript">
window.addEvent('domready', function() {
	var dpOffset = <?php echo $modx->config['datepicker_offset']; ?>;
	var dpformat = "<?php echo $modx->config['datetime_format']; ?>" + ' hh:mm:00';
	new DatePicker($('datefrom'), {'yearOffset': dpOffset,'format':dpformat});
	new DatePicker($('dateto'), {'yearOffset': dpOffset,'format':dpformat});
});
</script>
<h1><?php echo $_lang["mgrlog_view"]?></h1>
<div class="section">
<div class="sectionHeader"><?php echo $_lang["mgrlog_query"]?></div><div class="sectionBody" id="lyr1">
<p><?php echo $_lang["mgrlog_query_msg"]?></p>
<form action="index.php?a=13" name="logging" method="POST">
<table border="0" cellpadding="2" cellspacing="0">
 <thead>
  <tr>
    <td width="200"><b><?php echo $_lang["mgrlog_field"]?></b></td>
    <td align="right"><b><?php echo $_lang["mgrlog_value"]?></b></td>
  </tr>
 </thead>
 <tbody>
  <tr>
    <td><b><?php echo $_lang["mgrlog_user"]?></b></td>
    <td align="right">
	<select name="searchuser" class="inputBox" style="width:240px">
		<option value="0"><?php echo $_lang["mgrlog_anyall"]?></option>
<?php
	// get all users currently in the log
	$logs_user = record_sort(array_unique_multi($logs, 'internalKey'), 'username');
	foreach ($logs_user as $row) {
		$selectedtext = $row['internalKey'] == $_REQUEST['searchuser'] ? ' selected="selected"' : '';
		echo "\t\t".'<option value="'.$row['internalKey'].'"'.$selectedtext.'>'.$row['username']."</option>\n";
	}
?>	</select>
    </td>
  </tr>
  <tr bgcolor="#eeeeee">
    <td><b><?php echo $_lang["mgrlog_action"]; ?></b></td>
    <td align="right">
	<select name="action" class="inputBox" style="width:240px;">
		<option value="0"><?php echo $_lang["mgrlog_anyall"]; ?></option>
<?php
	// get all available actions in the log
	include_once "actionlist.inc.php";
	$logs_actions = record_sort(array_unique_multi($logs, 'action'), 'action');
	foreach ($logs_actions as $row) {
		$action = getAction($row['action']);
		if ($action == 'Idle') continue;
		$selectedtext = $row['action'] == $_REQUEST['action'] ? ' selected="selected"' : '';
		echo "\t\t".'<option value="'.$row['action'].'"'.$selectedtext.'>'.$row['action'].' - '.$action."</option>\n";
	}
?>	</select>
    </td>
  </tr>
  <tr bgcolor="#ffffff">
    <td><b><?php echo $_lang["mgrlog_itemid"]; ?></b></td>
    <td align="right">
	<select name="itemid" class="inputBox" style="width:240px">
		<option value="0"><?php echo $_lang["mgrlog_anyall"]; ?></option>
<?php
	// get all itemid currently in logging
	$logs_items = record_sort(array_unique_multi($logs, 'itemid'), 'itemid');
	foreach ($logs_items as $row) {
		$selectedtext = $row['itemid'] == $_REQUEST['itemid'] ? ' selected="selected"' : '';
		echo "\t\t".'<option value="'.$row['itemid'].'"'.$selectedtext.'>'.$row['itemid']."</option>\n";
	}
?>	</select>
    </td>
  </tr>
  <tr bgcolor="#eeeeee">
    <td><b><?php echo $_lang["mgrlog_itemname"]; ?></b></td>
    <td align="right">
	<select name="itemname" class="inputBox" style="width:240px">
		<option value="0"><?php echo $_lang["mgrlog_anyall"]; ?></option>
<?php
	// get all itemname currently in logging
	$logs_names = record_sort(array_unique_multi($logs, 'itemname'), 'itemname');
	foreach ($logs_names as $row) {
		$selectedtext = $row['itemname'] == $_REQUEST['itemname'] ? ' selected="selected"' : '';
		echo "\t\t".'<option value="'.$row['itemname'].'"'.$selectedtext.'>'.$row['itemname']."</option>\n";
	}
?>	</select>
    </td>
  </tr>
  <tr bgcolor="#ffffff">
    <td><b><?php echo $_lang["mgrlog_msg"]; ?></b></td>
    <td align="right">
      <input type="text" name="message" class="inputbox" style="width:240px" value="<?php echo $_REQUEST['message']; ?>" />
    </td>
  </tr>
  <tr bgcolor="#eeeeee">
    <td><b><?php echo $_lang["mgrlog_datefr"]; ?></b></td>
        <td align="right">
        	<input type="text" id="datefrom" name="datefrom" class="DatePicker" value="<?php echo isset($_REQUEST['datefrom']) ? $_REQUEST['datefrom'] : "" ; ?>" />
		  	<a onclick="document.logging.datefrom.value=''; return true;" onmouseover="window.status='Don\'t set a date'; return true;" onmouseout="window.status=''; return true;" style="cursor:pointer; cursor:hand"><img src="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/icons/cal_nodate.gif" width="16" height="16" border="0" alt="No date" /></a>
	  </td>
  </tr>
  <tr bgcolor="#ffffff">
    <td><b><?php echo $_lang["mgrlog_dateto"]; ?></b></td>
    <td align="right">
		  <input type="text" id="dateto" name="dateto" class="DatePicker" value="<?php echo isset($_REQUEST['dateto']) ? $_REQUEST['dateto'] : "" ; ?>" />
		  <a onclick="document.logging.dateto.value=''; return true;" onmouseover="window.status='Don\'t set a date'; return true;" onmouseout="window.status=''; return true;" style="cursor:pointer; cursor:hand"><img src="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/icons/cal_nodate.gif" width="16" height="16" border="0" alt="No date" /></a>
		 </td>
  </tr>
  <tr bgcolor="#eeeeee">
    <td><b><?php echo $_lang["mgrlog_results"]; ?></b></td>
    <td align="right">
      <input type="text" name="nrresults" class="inputbox" style="width:100px" value="<?php echo isset($_REQUEST['nrresults']) ? $_REQUEST['nrresults'] : $number_of_logs; ?>" /><img src="<?php echo $_style['tx']; ?>" border="0" />
    </td>
  </tr>
  <tr bgcolor="#FFFFFF">
    <td colspan="2">
	<ul class="actionButtons">
		<li><a href="#" onclick="document.logging.log_submit.click();"><img src="<?php echo $_style["icons_save"] ?>" /> <?php echo $_lang['search']; ?></a></li>
		<li><a href="index.php?a=2"><img src="<?php echo $_style["icons_cancel"] ?>" /> <?php echo $_lang['cancel']; ?></a></li>
	</ul>
      <input type="submit" name="log_submit" value="<?php echo $_lang["mgrlog_searchlogs"]?>" style="display:none;" />
    </td>
  </tr>
  </tbody>
</table>
</form>
</div></div>
<div class="section">
<div class="sectionHeader"><?php echo $_lang["mgrlog_qresults"]; ?></div><div class="sectionBody" id="lyr2">
<?php
if(isset($_REQUEST['log_submit'])) {
	// get the selections the user made.
	$sqladd = array();
	if($_REQUEST['searchuser']!=0)	$sqladd[] = "internalKey='".intval($_REQUEST['searchuser'])."'";
	if($_REQUEST['action']!=0)	$sqladd[] = "action=".intval($_REQUEST['action']);
	if($_REQUEST['itemid']!=0 || $_REQUEST['itemid']=="-")
					$sqladd[] = "itemid='".$_REQUEST['itemid']."'";
	if($_REQUEST['itemname']!='0')	$sqladd[] = "itemname='".$modx->db->escape($_REQUEST['itemname'])."'";
	if($_REQUEST['message']!="")	$sqladd[] = "message LIKE '%".$modx->db->escape($_REQUEST['message'])."%'";
	// date stuff
	if($_REQUEST['datefrom']!="")	$sqladd[] = "timestamp>".convertdate($_REQUEST['datefrom']);
	if($_REQUEST['dateto']!="")	$sqladd[] = "timestamp<".convertdate($_REQUEST['dateto']);

	// If current position is not set, set it to zero
	if( !isset( $_REQUEST['int_cur_position'] ) || $_REQUEST['int_cur_position'] == 0 ){
		 $int_cur_position = 0;
	} else {
		$int_cur_position = $_REQUEST['int_cur_position'];
	}

	// Number of result to display on the page, will be in the LIMIT of the sql query also
	$int_num_result = is_numeric($_REQUEST['nrresults']) ? $_REQUEST['nrresults'] : $number_of_logs;

	$extargv = "&a=13&searchuser=".$_REQUEST['searchuser']."&action=".$_REQUEST['action'].
		"&itemid=".$_REQUEST['itemid']."&itemname=".$_REQUEST['itemname']."&message=".
		$_REQUEST['message']."&dateto=".$_REQUEST['dateto']."&datefrom=".
		$_REQUEST['datefrom']."&nrresults=".$int_num_result."&log_submit=".$_REQUEST['log_submit']; // extra argv here (could be anything depending on your page)

	// build the sql
	$limit = $num_rows = $modx->db->getValue(
	           'SELECT COUNT(*) FROM '.$modx->getFullTableName('manager_log').
               (!empty($sqladd) ? ' WHERE '.implode(' AND ', $sqladd) : '')
    );
        
	$sql = 'SELECT * FROM '.$modx->getFullTableName('manager_log').
		(!empty($sqladd) ? ' WHERE '.implode(' AND ', $sqladd) : '').
		' ORDER BY timestamp DESC'.
		' LIMIT '.$int_cur_position.', '.$int_num_result;

	$rs = $modx->db->query($sql);
	if($limit<1) {
		echo '<p>'.$_lang["mgrlog_emptysrch"].'</p>';
	} else {
		echo '<p>'.$_lang["mgrlog_sortinst"].'</p>';

		include_once "paginate.inc.php";
		// New instance of the Paging class, you can modify the color and the width of the html table
		$p = new Paging( $num_rows, $int_cur_position, $int_num_result, $extargv );

		// Load up the 2 array in order to display result
		$array_paging = $p->getPagingArray();
		$array_row_paging = $p->getPagingRowArray();
		$current_row = $int_cur_position/$int_num_result;

		// Display the result as you like...
		print "<p>". $_lang["paging_showing"]." ". $array_paging['lower'];
		print " ". $_lang["paging_to"] . " ". $array_paging['upper'];
		print " (". $array_paging['total'] . " " . $_lang["paging_total"] . ")";
		print "<br />". $array_paging['first_link'] . $_lang["paging_first"] . (isset($array_paging['first_link']) ? "</a> " : " ");
		print $array_paging['previous_link'] . $_lang["paging_prev"] . (isset($array_paging['previous_link']) ? "</a> " : " ");
		$pagesfound = sizeof($array_row_paging);
		if($pagesfound>6) {
			print $array_row_paging[$current_row-2]; // ."&nbsp;";
			print $array_row_paging[$current_row-1]; // ."&nbsp;";
			print $array_row_paging[$current_row]; // ."&nbsp;";
			print $array_row_paging[$current_row+1]; // ."&nbsp;";
			print $array_row_paging[$current_row+2]; // ."&nbsp;";
		} else {
			for( $i=0; $i<$pagesfound; $i++ ){
				print $array_row_paging[$i] ."&nbsp;";
			}
		}
		print $array_paging['next_link'] . $_lang["paging_next"] . (isset($array_paging['next_link']) ? "</a> " : " ") . " ";
		print $array_paging['last_link'] . $_lang["paging_last"] . (isset($array_paging['last_link']) ? "</a> " : " ") . "</p>";
		// The above exemple print somethings like:
		// Results 1 to 20 of 597  <<< 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 >>>
		// Of course you can now play with array_row_paging in order to print
		// only the results you would like...
		?>
		<script type="text/javascript" src="media/script/tablesort.js"></script>
		<table border="0" cellpadding="2" cellspacing="1" bgcolor="#ccc" class="sortabletable rowstyle-even" id="table-1" width="%100">
		<thead><tr>
			<th class="sortable"><b><?php echo $_lang["mgrlog_username"]; ?></b></th>
			<th class="sortable"><b><?php echo $_lang["mgrlog_actionid"]; ?></b></th>
			<th class="sortable"><b><?php echo $_lang["mgrlog_itemid"]; ?></b></th>
			<th class="sortable"><b><?php echo $_lang["mgrlog_itemname"]; ?></b></th>
			<th class="sortable"><b><?php echo $_lang["mgrlog_msg"]; ?></b></th>
			<th class="sortable"><b><?php echo $_lang["mgrlog_time"]; ?></b></th>
		</tr></thead>
		<tbody>
		<?php
		// grab the entire log file...
		$logentries = array();
		$i = 0;
		while ($logentry = $modx->db->getRow($rs)) {
			?><tr class="<?php echo ($i % 2 ? 'even' : ''); ?>">
			<td><?php echo '<a href="index.php?a=12&amp;id='.$logentry['internalKey'].'">'.$logentry['username'].'</a>'; ?></td>
			<td><?php echo $logentry['action']; ?></td>
			<td><?php echo $logentry['itemid']=="-" ? "" : $logentry['itemid'] ; ?></td>
			<td><?php echo $logentry['itemname']; ?></td>
			<td><?php echo $logentry['message']; ?></td>
			<td><?php echo $modx->toDateFormat($logentry['timestamp']+$server_offset_time); ?></td>
		</tr>
		<?php
		$i++;
		}
		?>
	</tbody>
	</table>
	<?php
	}
	?>
	</div></div>
	<?php
	// HACK: prevent multiple "Viewing logging" entries after a search has taken place.
	// @see index.php @ 915
	global $action; $action = 1;
} else {
    echo $_lang["mgrlog_noquery"];
}
?>
