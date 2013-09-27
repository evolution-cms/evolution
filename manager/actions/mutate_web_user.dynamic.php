<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

switch((int) $_REQUEST['a']) {
  case 88:
    if(!$modx->hasPermission('edit_web_user')) {
      $e->setError(3);
      $e->dumpError();
    }
    break;
  case 87:
    if(!$modx->hasPermission('new_web_user')) {
      $e->setError(3);
      $e->dumpError();
    }
    break;
  default:
    $e->setError(3);
    $e->dumpError();
}

$user = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;


// check to see the snippet editor isn't locked
$sql = "SELECT internalKey, username FROM $dbase.`".$table_prefix."active_users` WHERE $dbase.`".$table_prefix."active_users`.action=88 AND $dbase.`".$table_prefix."active_users`.id=$user";
$rs = $modx->db->query($sql);
$limit = $modx->db->getRecordCount($rs);
if($limit>1) {
	for ($i=0;$i<$limit;$i++) {
		$lock = $modx->db->getRow($rs);
		if($lock['internalKey']!=$modx->getLoginUserID()) {
			$msg = sprintf($_lang["lock_msg"],$lock['username'],"web user");
			$e->setError(5, $msg);
			$e->dumpError();
		}
	}
}
// end check for lock

if($_REQUEST['a']=='88') {
	// get user attributes
	$sql = "SELECT * FROM $dbase.`".$table_prefix."web_user_attributes` WHERE $dbase.`".$table_prefix."web_user_attributes`.internalKey = ".$user.";";
	$rs = $modx->db->query($sql);
	$limit = $modx->db->getRecordCount($rs);
	if($limit>1) {
		echo "More than one user returned!<p>";
		exit;
	}
	if($limit<1) {
		echo "No user returned!<p>";
		exit;
	}
	$userdata = $modx->db->getRow($rs);

	// get user settings
	$sql = "SELECT wus.* FROM $dbase.`".$table_prefix."web_user_settings` wus WHERE wus.webuser = ".$user.";";
	$rs = $modx->db->query($sql);
	$usersettings = array();
	while($row=$modx->db->getRow($rs)) $usersettings[$row['setting_name']]=$row['setting_value'];
	extract($usersettings, EXTR_OVERWRITE);

	// get user name
	$sql = "SELECT * FROM $dbase.`".$table_prefix."web_users` WHERE $dbase.`".$table_prefix."web_users`.id = ".$user.";";
	$rs = $modx->db->query($sql);
	$limit = $modx->db->getRecordCount($rs);
	if($limit>1) {
		echo "More than one user returned while getting username!<p>";
		exit;
	}
	if($limit<1) {
		echo "No user returned while getting username!<p>";
		exit;
	}
	$usernamedata = $modx->db->getRow($rs);
	$_SESSION['itemname']=$usernamedata['username'];
} else {
	$userdata = array();
	$usersettings = array();
	$usernamedata = array();
	$_SESSION['itemname']="New web user";
}

// restore saved form
$formRestored = false;
if($modx->manager->hasFormValues()) {
	$modx->manager->loadFormValues();
	// restore post values
	$userdata = array_merge($userdata,$_POST);
	$userdata['dob'] = ConvertDate($userdata['dob']);
	$usernamedata['username'] = $userdata['newusername'];
	$usernamedata['oldusername'] = $_POST['oldusername'];
	$usersettings = array_merge($usersettings,$userdata);
	$usersettings['allowed_days'] = is_array($_POST['allowed_days']) ? implode(",",$_POST['allowed_days']):"";
	extract($usersettings, EXTR_OVERWRITE);
}

// converts date format dd-mm-yyyy to php date
function ConvertDate($date) {
	global $modx;
	if ($date == "") {return "0";}
        //else {}          {return $modx->toTimeStamp($date);}
        else          {return $modx->toTimeStamp($date);}
}

// include the country list language file
$_country_lang = array();
if($manager_language!="english" && file_exists($modx->config['site_manager_path']."includes/lang/country/".$manager_language."_country.inc.php")){
    include_once "lang/country/".$manager_language."_country.inc.php";
} else {
    include_once "lang/country/english_country.inc.php";
}

?>
<script type="text/javascript" src="media/calendar/datepicker.js"></script>
<script type="text/javascript">
window.addEvent('domready', function() {
	var dpOffset = <?php echo $modx->config['datepicker_offset']; ?>;
	var dpformat = "<?php echo $modx->config['datetime_format']; ?>";
	new DatePicker($('dob'), {'yearOffset': -90,'yearRange':1,'format':dpformat});
	if ($('blockeduntil')) {
		new DatePicker($('blockeduntil'), {'yearOffset': dpOffset,'format':dpformat + ' hh:mm:00'});
		new DatePicker($('blockedafter'), {'yearOffset': dpOffset,'format':dpformat + ' hh:mm:00'});
	}
});

function changestate(element) {
	documentDirty=true;
	currval = eval(element).value;
	if(currval==1) {
		eval(element).value=0;
	} else {
		eval(element).value=1;
	}
}

function changePasswordState(element) {
	currval = eval(element).value;
	if(currval==1) {
		document.getElementById("passwordBlock").style.display="block";
	} else {
		document.getElementById("passwordBlock").style.display="none";
	}
}

function changeblockstate(element, checkelement) {
	currval = eval(element).value;
	if(currval==1) {
		if(confirm("<?php echo $_lang['confirm_unblock']; ?>")==true){
			document.userform.blocked.value=0;
			document.userform.blockeduntil.value="";
			document.userform.blockedafter.value="";
			document.userform.failedlogincount.value=0;
			blocked.innerHTML="<b><?php echo $_lang['unblock_message']; ?></b>";
			blocked.className="TD";
			eval(element).value=0;
		} else {
			eval(checkelement).checked=true;
		}
	} else {
		if(confirm("<?php echo $_lang['confirm_block']; ?>")==true){
			document.userform.blocked.value=1;
			blocked.innerHTML="<b><?php echo $_lang['block_message']; ?></b>";
			blocked.className="warning";
			eval(element).value=1;
		} else {
			eval(checkelement).checked=false;
		}
	}
}

function resetFailed() {
	document.userform.failedlogincount.value=0;
	document.getElementById("failed").innerHTML="0";
}

function deleteuser() {
	if(confirm("<?php echo $_lang['confirm_delete_user']; ?>")==true) {
		document.location.href="index.php?id=" + document.userform.id.value + "&a=90";
	}
}

// change name
function changeName(){
	if(confirm("<?php echo $_lang['confirm_name_change']; ?>")==true) {
		var e1 = document.getElementById("showname");
		var e2 = document.getElementById("editname");
		e1.style.display = "none";
		e2.style.display = "<?php echo $displayStyle; ?>";
	}
};

// showHide - used by custom settings
function showHide(what, onoff){
	var all = document.getElementsByTagName( "*" );
	var l = all.length;
	var buttonRe = what;
	var id, el, stylevar;

	if(onoff==1) {
		stylevar = "<?php echo $displayStyle; ?>";
	} else {
		stylevar = "none";
	}

	for ( var i = 0; i < l; i++ ) {
		el = all[i]
		id = el.id;
		if ( id == "" ) continue;
		if (buttonRe.test(id)) {
			el.style.display = stylevar;
		}
	}
};

</script>


<form action="index.php?a=89" method="post" name="userform">
<?php
	// invoke OnWUsrFormPrerender event
	$evtOut = $modx->invokeEvent("OnWUsrFormPrerender",array("id" => $user));
	if(is_array($evtOut)) echo implode("",$evtOut);
?>
<input type="hidden" name="mode" value="<?php echo $_GET['a'] ?>" />
<input type="hidden" name="id" value="<?php echo $_GET['id'] ?>" />
<input type="hidden" name="blockedmode" value="<?php echo ($userdata['blocked']==1 || ($userdata['blockeduntil']>time() && $userdata['blockeduntil']!=0)|| ($userdata['blockedafter']<time() && $userdata['blockedafter']!=0) || $userdata['failedlogins']>3) ? "1":"0" ?>" />

<h1><?php echo $_lang['web_user_title']; ?></h1>

<div id="actions">
	<ul class="actionButtons">
			<li><a href="#" onclick="documentDirty=false; document.userform.save.click();"><img src="<?php echo $_style["icons_save"] ?>" /> <?php echo $_lang['save']; ?></a><span class="and"> + </span>
			<select id="stay" name="stay">
			  <?php if ($modx->hasPermission('new_web_user')) { ?>		
			  <option id="stay1" value="1" <?php echo $_REQUEST['stay']=='1' ? ' selected="selected"' : ''?> ><?php echo $_lang['stay_new']?></option>
			  <?php } ?>
			  <option id="stay2" value="2" <?php echo $_REQUEST['stay']=='2' ? ' selected="selected"' : ''?> ><?php echo $_lang['stay']?></option>
			  <option id="stay3" value=""  <?php echo $_REQUEST['stay']=='' ? ' selected="selected"' : ''?>  ><?php echo $_lang['close']?></option>
			</select>		
			</li>
			<li id="btn_del"><a href="#" onclick="deleteuser();"><img src="<?php echo $_style["icons_delete"] ?>" /> <?php echo $_lang['delete']; ?></a></li>
<?php if($_GET['a']!='88') { ?>
			<script type="text/javascript">document.getElementById("btn_del").className='disabled';</script>
<?php } ?>
			<li><a href="#" onclick="documentDirty=false;document.location.href='index.php?a=99';"><img src="<?php echo $_style["icons_cancel"] ?>" /> <?php echo $_lang['cancel']; ?></a></li>
	</ul>
</div>

<!-- Tab Start -->
<div class="sectionBody">
<link type="text/css" rel="stylesheet" href="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>style.css<?php echo "?$theme_refresher";?>" />
<script type="text/javascript" src="media/script/tabpane.js"></script>
<div class="tab-pane" id="webUserPane">
	<script type="text/javascript">
		tpUser = new WebFXTabPane(document.getElementById( "webUserPane" ), <?php echo $modx->config['remember_last_tab'] == 1 ? 'true' : 'false'; ?> );
	</script>
    <div class="tab-page" id="tabGeneral">
    	<h2 class="tab"><?php echo $_lang["settings_general"] ?></h2>
    	<script type="text/javascript">tpUser.addTabPage( document.getElementById( "tabGeneral" ) );</script>
		<table border="0" cellspacing="0" cellpadding="3">
		  <tr>
			<td colspan="3">
				<span id="blocked" class="warning"><?php if($userdata['blocked']==1 || ($userdata['blockeduntil']>time() && $userdata['blockeduntil']!=0)|| ($userdata['blockedafter']<time() && $userdata['blockedafter']!=0) || $userdata['failedlogins']>3) { ?><b><?php echo $_lang['user_is_blocked']; ?></b><?php } ?></span><br />
			</td>
		  </tr>
		  <?php if(!empty($userdata['id'])) { ?>
		  <tr id="showname" style="display: <?php echo ($_GET['a']=='88' && (!isset($usernamedata['oldusername'])||$usernamedata['oldusername']==$usernamedata['username'])) ? $displayStyle : 'none';?> ">
			<td colspan="3">
				<img src="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/icons/user.gif" alt="." />&nbsp;<b><?php echo !empty($usernamedata['oldusername']) ? $usernamedata['oldusername']:$usernamedata['username']; ?></b> - <span class="comment"><a href="#" onclick="changeName();return false;"><?php echo $_lang["change_name"]; ?></a></span>
				<input type="hidden" name="oldusername" value="<?php echo htmlspecialchars(!empty($usernamedata['oldusername']) ? $usernamedata['oldusername']:$usernamedata['username']); ?>" />
				<hr />
			</td>
		  </tr>
		  <?php } ?>
		  <tr id="editname" style="display:<?php echo $_GET['a']=='87'||(isset($usernamedata['oldusername']) && $usernamedata['oldusername']!=$usernamedata['username']) ? $displayStyle : 'none' ; ?>">
			<td><?php echo $_lang['username']; ?>:</td>
			<td>&nbsp;</td>
			<td><input type="text" name="newusername" class="inputBox" value="<?php echo htmlspecialchars(isset($_POST['newusername']) ? $_POST['newusername'] : $usernamedata['username']); ?>" onchange='documentDirty=true;' maxlength="100" /></td>
		  </tr>
		  <tr>
			<td valign="top"><?php echo $_GET['a']=='87' ? $_lang['password'].":" : $_lang['change_password_new'].":" ; ?></td>
			<td>&nbsp;</td>
			<td><input name="newpasswordcheck" type="checkbox" onclick="changestate(document.userform.newpassword);changePasswordState(document.userform.newpassword);"<?php echo $_REQUEST['a']=="87" ? " checked disabled": "" ; ?>><input type="hidden" name="newpassword" value="<?php echo $_REQUEST['a']=="87" ? 1 : 0 ; ?>" onchange="documentDirty=true;" /><br />
				<span style="display:<?php echo $_REQUEST['a']=="87" ? "block": "none" ; ?>" id="passwordBlock">
				<fieldset style="width:300px">
				<legend><b><?php echo $_lang['password_gen_method']; ?></b></legend>
				<input type=radio name="passwordgenmethod" value="g" <?php echo $_POST['passwordgenmethod']=="spec" ? "" : 'checked="checked"'; ?> /><?php echo $_lang['password_gen_gen']; ?><br />
				<input type=radio name="passwordgenmethod" value="spec" <?php echo $_POST['passwordgenmethod']=="spec" ? 'checked="checked"' : ""; ?>><?php echo $_lang['password_gen_specify']; ?> <br />
				<div style="padding-left:20px">
				<label for="specifiedpassword" style="width:120px"><?php echo $_lang['change_password_new']; ?>:</label>
				<input type="password" name="specifiedpassword" onchange="documentdirty=true;" onkeypress="document.userform.passwordgenmethod[1].checked=true;" size="20" /><br />
				<label for="confirmpassword" style="width:120px"><?php echo $_lang['change_password_confirm']; ?>:</label>
				<input type="password" name="confirmpassword" onchange="documentdirty=true;" onkeypress="document.userform.passwordgenmethod[1].checked=true;" size="20" /><br />
				<small><span class="warning" style="font-weight:normal"><?php echo $_lang['password_gen_length']; ?></span></small>
				</div>
				</fieldset>
				<br />
				<fieldset style="width:300px">
				<legend><b><?php echo $_lang['password_method']; ?></b></legend>
				<input type=radio name="passwordnotifymethod" value="e" <?php echo $_POST['passwordnotifymethod']=="e" ? 'checked="checked"' : ""; ?> /><?php echo $_lang['password_method_email']; ?><br />
				<input type=radio name="passwordnotifymethod" value="s" <?php echo $_POST['passwordnotifymethod']=="e" ? "" : 'checked="checked"'; ?> /><?php echo $_lang['password_method_screen']; ?>
				</fieldset>
				</span>
			</td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_full_name']; ?>:</td>
			<td>&nbsp;</td>
			<td><input type="text" name="fullname" class="inputBox" value="<?php echo htmlspecialchars(isset($_POST['fullname']) ? $_POST['fullname'] : $userdata['fullname']); ?>" onchange="documentDirty=true;" /></td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_email']; ?>:</td>
			<td>&nbsp;</td>
			<td>
			<input type="text" name="email" class="inputBox" value="<?php echo  isset($_POST['email']) ? $_POST['email'] : $userdata['email']; ?>" onchange="documentDirty=true;" />
			<input type="hidden" name="oldemail" value="<?php echo htmlspecialchars(!empty($userdata['oldemail']) ? $userdata['oldemail']:$userdata['email']); ?>" />
			</td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_phone']; ?>:</td>
			<td>&nbsp;</td>
			<td><input type="text" name="phone" class="inputBox" value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : $userdata['phone']; ?>" onchange="documentDirty=true;" /></td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_mobile']; ?>:</td>
			<td>&nbsp;</td>
			<td><input type="text" name="mobilephone" class="inputBox" value="<?php echo isset($_POST['mobilephone']) ? $_POST['mobilephone'] : $userdata['mobilephone']; ?>" onchange="documentDirty=true;" /></td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_fax']; ?>:</td>
			<td>&nbsp;</td>
			<td><input type="text" name="fax" class="inputBox" value="<?php echo isset($_POST['fax']) ? $_POST['fax'] : $userdata['fax']; ?>" onchange="documentDirty=true;" /></td>
		  </tr>
		<tr>
			<td><?php echo $_lang['user_street']; ?>:</td>
			<td>&nbsp;</td>
			<td><input type="text" name="street" class="inputBox" value="<?php echo htmlspecialchars($userdata['street']); ?>" onchange="documentDirty=true;" /></td>
		</tr>
		<tr>
			<td><?php echo $_lang['user_city']; ?>:</td>
			<td>&nbsp;</td>
			<td><input type="text" name="city" class="inputBox" value="<?php echo htmlspecialchars($userdata['city']); ?>" onchange="documentDirty=true;" /></td>
		</tr>
		  <tr>
			<td><?php echo $_lang['user_state']; ?>:</td>
			<td>&nbsp;</td>
			<td><input type="text" name="state" class="inputBox" value="<?php echo isset($_POST['state']) ? $_POST['state'] : $userdata['state']; ?>" onchange="documentDirty=true;" /></td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_zip']; ?>:</td>
			<td>&nbsp;</td>
			<td><input type="text" name="zip" class="inputBox" value="<?php echo isset($_POST['zip']) ? $_POST['zip'] : $userdata['zip']; ?>" onchange="documentDirty=true;" /></td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_country']; ?>:</td>
			<td>&nbsp;</td>
			<td>
			<select size="1" name="country" onchange="documentDirty=true;">
            <?php $chosenCountry = isset($_POST['country']) ? $_POST['country'] : $userdata['country']; ?>
			<option value="" <?php (!isset($chosenCountry) ? ' selected' : '') ?> >&nbsp;</option>
				<?php
				foreach ($_country_lang as $key => $country) {
				 echo "<option value=\"$key\"".(isset($chosenCountry) && $chosenCountry == $key ? ' selected' : '') .">$country</option>";
				}
				?>
            </select>
            </td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_dob']; ?>:</td>
			<td>&nbsp;</td>
			<td>
				<input type="text" id="dob" name="dob" class="DatePicker" value="<?php echo isset($_POST['dob']) ? $_POST['dob'] : ($userdata['dob'] ? $modx->toDateFormat($userdata['dob'],'dateOnly'):""); ?>" onblur='documentDirty=true;'>
				<a onclick="document.userform.dob.value=''; return true;" onmouseover="window.status='<?php echo $_lang['remove_date']; ?>'; return true;" onmouseout="window.status=''; return true;" style="cursor:pointer; cursor:hand"><img align="absmiddle" src="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/icons/cal_nodate.gif" width="16" height="16" border="0" alt="<?php echo $_lang['remove_date']; ?>"></a>
			</td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_gender']; ?>:</td>
			<td>&nbsp;</td>
			<td><select name="gender" onchange="documentDirty=true;">
				<option value=""></option>
				<option value="1" <?php echo ($_POST['gender']=='1'||$userdata['gender']=='1')? "selected='selected'":""; ?>><?php echo $_lang['user_male']; ?></option>
				<option value="2" <?php echo ($_POST['gender']=='2'||$userdata['gender']=='2')? "selected='selected'":""; ?>><?php echo $_lang['user_female']; ?></option>
				<option value="3" <?php echo ($_POST['gender']=='3'||$userdata['gender']=='3')? "selected='selected'":""; ?>><?php echo $_lang['user_other']; ?></option>
				</select>
			</td>
		  </tr>
		  <tr>
			<td valign="top"><?php echo $_lang['comment']; ?>:</td>
			<td>&nbsp;</td>
			<td>
				<textarea type="text" name="comment" class="inputBox"  rows="5" onchange="documentDirty=true;"><?php echo htmlspecialchars(isset($_POST['comment']) ? $_POST['comment'] : $userdata['comment']); ?></textarea>
			</td>
		  </tr>
		<?php if($_GET['a']=='88') { ?>
		  <tr>
			<td><?php echo $_lang['user_logincount']; ?>:</td>
			<td>&nbsp;</td>
			<td><?php echo $userdata['logincount'] ?></td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_prevlogin']; ?>:</td>
			<td>&nbsp;</td>
			<td><?php echo $modx->toDateFormat($userdata['lastlogin']+$server_offset_time) ?></td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_failedlogincount']; ?>:</td>
			<td>&nbsp;<input type="hidden" name="failedlogincount"  onchange='documentDirty=true;' value="<?php echo $userdata['failedlogincount']; ?>"></td>
			<td><span id='failed'><?php echo $userdata['failedlogincount'] ?></span>&nbsp;&nbsp;&nbsp;[<a href="javascript:resetFailed()"><?php echo $_lang['reset_failedlogins']; ?></a>]</td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_block']; ?>:</td>
			<td>&nbsp;</td>
			<td><input name="blockedcheck" type="checkbox" onclick="changeblockstate(document.userform.blockedmode, document.userform.blockedcheck);"<?php echo ($userdata['blocked']==1||($userdata['blockeduntil']>time() && $userdata['blockeduntil']!=0)||($userdata['blockedafter']<time() && $userdata['blockedafter']!=0)) ? " checked='checked'": "" ; ?> /><input type="hidden" name="blocked" value="<?php echo ($userdata['blocked']==1||($userdata['blockeduntil']>time() && $userdata['blockeduntil']!=0))?1:0; ?>"></td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_blockeduntil']; ?>:</td>
			<td>&nbsp;</td>
			<td>
				<input type="text" id="blockeduntil" name="blockeduntil" class="DatePicker" value="<?php echo isset($_POST['blockeduntil']) ? $_POST['blockeduntil'] : ($userdata['blockeduntil'] ? $modx->toDateFormat($userdata['blockeduntil']):""); ?>" onblur='documentDirty=true;' readonly="readonly">
				<a onclick="document.userform.blockeduntil.value=''; return true;" onmouseover="window.status='<?php echo $_lang['remove_date']; ?>'; return true;" onmouseout="window.status=''; return true;" style="cursor:pointer; cursor:hand"><img align="absmiddle" src="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/icons/cal_nodate.gif" width="16" height="16" border="0" alt="<?php echo $_lang['remove_date']; ?>" /></a>
			</td>
		  </tr>
		  <tr>
			<td><?php echo $_lang['user_blockedafter']; ?>:</td>
			<td>&nbsp;</td>
			<td>
				<input type="text" id="blockedafter" name="blockedafter" class="DatePicker" value="<?php echo isset($_POST['blockedafter']) ? $_POST['blockedafter'] : ($userdata['blockedafter'] ? $modx->toDateFormat($userdata['blockedafter']):""); ?>" onblur='documentDirty=true;' readonly="readonly">
				<a onclick="document.userform.blockedafter.value=''; return true;" onmouseover="window.status='<?php echo $_lang['remove_date']; ?>'; return true;" onmouseout="window.status=''; return true;" style="cursor:pointer; cursor:hand"><img align="absmiddle" src="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/icons/cal_nodate.gif" width="16" height="16" border="0" alt="<?php echo $_lang['remove_date']; ?>" /></a>
			</td>
		  </tr>
		<?php
		}
		?>
		</table>
	</div>
	<!-- Settings -->
    <div class="tab-page" id="tabSettings">
    	<h2 class="tab"><?php echo $_lang["settings_users"] ?></h2>
    	<script type="text/javascript">tpUser.addTabPage( document.getElementById( "tabSettings" ) );</script>
        <table border="0" cellspacing="0" cellpadding="3">
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["login_homepage"] ?></b></td>
            <td ><input onchange="documentDirty=true;" type='text' maxlength='50' style="width: 100px;" name="login_home" value="<?php echo isset($_POST['login_home']) ? $_POST['login_home'] : $usersettings['login_home']; ?>"></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["login_homepage_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"valign="top"><b><?php echo $_lang["login_allowed_ip"] ?></b></td>
            <td ><input onchange="documentDirty=true;"  type="text" maxlength='255' style="width: 300px;" name="allowed_ip" value="<?php echo isset($_POST['allowed_ip']) ? $_POST['allowed_ip'] : $usersettings['allowed_ip']; ?>" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["login_allowed_ip_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td nowrap class="warning"valign="top"><b><?php echo $_lang["login_allowed_days"] ?></b></td>
            <td>
            	<input onchange="documentDirty=true;" type="checkbox" name="allowed_days[]" value="1" <?php echo strpos($usersettings['allowed_days'],'1')!==false ? "checked='checked'":""; ?> /> <?php echo $_lang['sunday']; ?><br />
            	<input onchange="documentDirty=true;" type="checkbox" name="allowed_days[]" value="2" <?php echo strpos($usersettings['allowed_days'],'2')!==false ? "checked='checked'":""; ?> /> <?php echo $_lang['monday']; ?><br />
            	<input onchange="documentDirty=true;" type="checkbox" name="allowed_days[]" value="3" <?php echo strpos($usersettings['allowed_days'],'3')!==false ? "checked='checked'":""; ?> /> <?php echo $_lang['tuesday']; ?><br />
            	<input onchange="documentDirty=true;" type="checkbox" name="allowed_days[]" value="4" <?php echo strpos($usersettings['allowed_days'],'4')!==false ? "checked='checked'":""; ?> /> <?php echo $_lang['wednesday']; ?><br />
            	<input onchange="documentDirty=true;" type="checkbox" name="allowed_days[]" value="5" <?php echo strpos($usersettings['allowed_days'],'5')!==false ? "checked='checked'":""; ?> /> <?php echo $_lang['thursday']; ?><br />
            	<input onchange="documentDirty=true;" type="checkbox" name="allowed_days[]" value="6" <?php echo strpos($usersettings['allowed_days'],'6')!==false ? "checked='checked'":""; ?> /> <?php echo $_lang['friday']; ?><br />
            	<input onchange="documentDirty=true;" type="checkbox" name="allowed_days[]" value="7" <?php echo strpos($usersettings['allowed_days'],'7')!==false ? "checked='checked'":""; ?> /> <?php echo $_lang['saturday']; ?><br />
            </td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["login_allowed_days_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
		</table>
	</div>
	<!-- Photo -->
    <div class="tab-page" id="tabPhoto">
    	<h2 class="tab"><?php echo $_lang["settings_photo"] ?></h2>
    	<script type="text/javascript">tpUser.addTabPage( document.getElementById( "tabPhoto" ) );</script>
    	<script type="text/javascript">
			function OpenServerBrowser(url, width, height ) {
				var iLeft = (screen.width  - width) / 2 ;
				var iTop  = (screen.height - height) / 2 ;

				var sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes" ;
				sOptions += ",width=" + width ;
				sOptions += ",height=" + height ;
				sOptions += ",left=" + iLeft ;
				sOptions += ",top=" + iTop ;

				var oWindow = window.open( url, "FCKBrowseWindow", sOptions ) ;
			}
			function BrowseServer() {
				var w = screen.width * 0.7;
				var h = screen.height * 0.7;
				OpenServerBrowser("<?php echo MODX_MANAGER_URL;?>media/browser/mcpuk/browser.php?Type=images", w, h);
			}
			function SetUrl(url, width, height, alt){
				document.userform.photo.value = url;
				document.images['iphoto'].src = "<?php echo $base_url; ?>" + url;
			}
		</script>
        <table border="0" cellspacing="0" cellpadding="3">
          <tr>
            <td nowrap class="warning"><b><?php echo $_lang["user_photo"] ?></b></td>
            <td><input onchange="documentDirty=true;" type='text' maxlength='255' style="width: 150px;" name="photo" value="<?php echo htmlspecialchars(isset($_POST['photo']) ? $_POST['photo'] : $userdata['photo']); ?>" /> <input type="button" value="<?php echo $_lang['insert']; ?>" onclick="BrowseServer();" /></td>
          </tr>
          <tr>
            <td width="200">&nbsp;</td>
            <td class='comment'><?php echo $_lang["user_photo_message"] ?></td>
          </tr>
          <tr>
            <td colspan="2"><div class='split'></div></td>
          </tr>
          <tr>
            <td colspan="2" align="center"><img name="iphoto" src="<?php echo isset($_POST['photo']) ? (strpos($_POST['photo'],"http://")===false?MODX_SITE_URL:"").$_POST['photo'] : !empty($userdata['photo']) ? (strpos($userdata['photo'],"http://")===false?MODX_SITE_URL:"").$userdata['photo']: "media/style/$manager_theme/images/misc/_tx_.gif"; ?>" /></td>
          </tr>
		</table>
	</div>

<?php
if($use_udperms==1) {
$groupsarray = array();

if($_GET['a']=='88') { // only do this bit if the user is being edited
	$sql = "SELECT * FROM $dbase.`".$table_prefix."web_groups` where webuser=".$_GET['id']."";
	$rs = $modx->db->query($sql);
	$limit = $modx->db->getRecordCount($rs);
	for ($i = 0; $i < $limit; $i++) {
		$currentgroup=$modx->db->getRow($rs);
		$groupsarray[$i] = $currentgroup['webgroup'];
	}
}

// retain selected user groups between post
if(is_array($_POST['user_groups'])) {
	foreach($_POST['user_groups'] as $n => $v) $groupsarray[] = $v;
}

    echo '<div class="tab-page" id="tabPermissions">
              <h2 class="tab">'.$_lang['web_access_permissions'].'</h2>
              <script type="text/javascript">tpUser.addTabPage( document.getElementById( "tabPermissions" ) );</script>
            ';
    echo '<p>'. $_lang['access_permissions_user_message'] . '</p>';
	$sql = "SELECT name, id FROM $dbase.`".$table_prefix."webgroup_names` ORDER BY name";
	$rs = $modx->db->query($sql);
	$limit = $modx->db->getRecordCount($rs);
	for($i=0; $i<$limit; $i++) {
	   $row=$modx->db->getRow($rs);
           echo '<input type="checkbox" name="user_groups[]" value="'.$row['id'].'"'.(in_array($row['id'], $groupsarray) ? ' checked="checked"' : '').' />'.$row['name'].'<br />';
	}
    
    echo '</div>';

}

    echo '<input type="submit" name="save" style="display:none">';

	// invoke OnWUsrFormRender event
	$evtOut = $modx->invokeEvent("OnWUsrFormRender",array("id" => $user));
	if(is_array($evtOut)) echo implode("",$evtOut);
?>

    </div>
</div>
</form>
