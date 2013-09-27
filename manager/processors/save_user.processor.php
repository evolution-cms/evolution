<?php
if (IN_MANAGER_MODE != "true")
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if (!$modx->hasPermission('save_user')) {
	$e->setError(3);
	$e->dumpError();
}
?>
<?php

// Web alert -  sends an alert to web browser
function webAlert($msg) {
	global $id, $modx;
	global $dbase, $table_prefix;
	$mode = $_POST['mode'];
	$url = "index.php?a=$mode" . ($mode == '12' ? "&id=" . $id : "");
	$modx->manager->saveFormValues($mode);
	include_once "header.inc.php";
	$modx->webAlert($msg, $url);
	include_once "footer.inc.php";
}

// Generate password
function generate_password($length = 10) {
	$allowable_characters = "abcdefghjkmnpqrstuvxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789";
	$ps_len = strlen($allowable_characters);
	mt_srand((double) microtime() * 1000000);
	$pass = "";
	for ($i = 0; $i < $length; $i++) {
		$pass .= $allowable_characters[mt_rand(0, $ps_len -1)];
	}
	return $pass;
}

if(isset($_POST['id'])) $id = intval($_POST['id']);
$oldusername = $_POST['oldusername'];
$newusername = !empty ($_POST['newusername']) ? trim($_POST['newusername']) : "New User";
$fullname = $modx->db->escape($_POST['fullname']);
$genpassword = $_POST['newpassword'];
$passwordgenmethod = $_POST['passwordgenmethod'];
$passwordnotifymethod = $_POST['passwordnotifymethod'];
$specifiedpassword = $_POST['specifiedpassword'];
$email = $modx->db->escape($_POST['email']);
$oldemail = $_POST['oldemail'];
$phone = $modx->db->escape($_POST['phone']);
$mobilephone = $modx->db->escape($_POST['mobilephone']);
$fax = $modx->db->escape($_POST['fax']);
$dob = !empty ($_POST['dob']) ? ConvertDate($_POST['dob']) : 0;
$country = $_POST['country'];
$street = $modx->db->escape($_POST['street']);
$city   = $modx->db->escape($_POST['city']);
$state = $modx->db->escape($_POST['state']);
$zip = $modx->db->escape($_POST['zip']);
$gender = !empty ($_POST['gender']) ? $_POST['gender'] : 0;
$photo = $modx->db->escape($_POST['photo']);
$comment = $modx->db->escape($_POST['comment']);
$role = !empty ($_POST['role']) ? $_POST['role'] : 0;
$failedlogincount = $_POST['failedlogincount'];
$blocked = !empty ($_POST['blocked']) ? $_POST['blocked'] : 0;
$blockeduntil = !empty ($_POST['blockeduntil']) ? ConvertDate($_POST['blockeduntil']) : 0;
$blockedafter = !empty ($_POST['blockedafter']) ? ConvertDate($_POST['blockedafter']) : 0;
$user_groups = $_POST['user_groups'];

// verify password
if ($passwordgenmethod == "spec" && $_POST['specifiedpassword'] != $_POST['confirmpassword']) {
	webAlert("Password typed is mismatched");
	exit;
}

// verify email
if ($email == '' || !preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i", $email)) {
	webAlert("E-mail address doesn't seem to be valid!");
	exit;
}

// verify admin security
if ($_SESSION['mgrRole'] != 1) {
	// Check to see if user tried to spoof a "1" (admin) role
	if ($role == 1) {
		webAlert("Illegal attempt to create/modify administrator by non-administrator!");
		exit;
	}
	// Verify that the user being edited wasn't an admin and the user ID got spoofed
	$sql = "SELECT role FROM $dbase.`" . $table_prefix . "user_attributes` AS mua WHERE internalKey = $id";
	if ($rs = $modx->db->query($sql)) {
		if ($rsQty = $modx->db->getRecordCount($rs)) {
			// There should only be one if there is one
			$row = $modx->db->getRow($rs);
			if ($row['role'] == 1) {
				webAlert("You cannot alter an administrative user.");
				exit;
			}
		}
	}

}

switch ($_POST['mode']) {
	case '11' : // new user
		// check if this user name already exist
		if (!$rs = $modx->db->select('id', '[+prefix+]manager_users', "username='{$newusername}'")) {
			webAlert("An error occurred while attempting to retrieve all users with username $newusername.");
			exit;
		}
		$limit = $modx->db->getRecordCount($rs);
		if ($limit > 0) {
			webAlert("User name is already in use!");
			exit;
		}

		// check if the email address already exist
		$sql = "SELECT id FROM $dbase.`" . $table_prefix . "user_attributes` WHERE email='$email'";
		if (!$rs = $modx->db->select('id', '[+prefix+]user_attributes', "email='{$email}'")) {
			webAlert("An error occurred while attempting to retrieve all users with email $email.");
			exit;
		}
		$limit = $modx->db->getRecordCount($rs);
		if ($limit > 0) {
			$row = $modx->db->getRow($rs);
			if ($row['id'] != $id) {
				webAlert("Email is already in use!");
				exit;
			}
		}

		// generate a new password for this user
		if ($specifiedpassword != "" && $passwordgenmethod == "spec") {
			if (strlen($specifiedpassword) < 6) {
				webAlert("Password is too short!");
				exit;
			} else {
				$newpassword = $specifiedpassword;
			}
		}
		elseif ($specifiedpassword == "" && $passwordgenmethod == "spec") {
			webAlert("You didn't specify a password for this user!");
			exit;
		}
		elseif ($passwordgenmethod == 'g') {
			$newpassword = generate_password(8);
		} else {
			webAlert("No password generation method specified!");
			exit;
		}

		// invoke OnBeforeUserFormSave event
		$modx->invokeEvent("OnBeforeUserFormSave", array (
			"mode" => "new",
		));

		// build the SQL
		$internalKey = $modx->db->insert(array('username'=>$newusername), '[+prefix+]manager_users');
		if (!$internalKey) {
			webAlert("An error occurred while attempting to save the user.");
			exit;
		}

		$field['password'] = $modx->manager->genHash($newpassword, $internalKey);
		$modx->db->update($field,'[+prefix+]manager_users',"id='{$internalKey}'");
		
		$field = array();
		$field = compact('internalKey','fullname','role','email','phone','mobilephone','fax','zip','street','city','state','country','gender','dob','photo','comment','blocked','blockeduntil','blockedafter');
		$rs = $modx->db->insert($field,'[+prefix+]user_attributes');
		if (!$rs) {
			webAlert("An error occurred while attempting to save the user's attributes.");
			exit;
		}

		// Save User Settings
		saveUserSettings($internalKey);

		// invoke OnManagerSaveUser event
		$modx->invokeEvent("OnManagerSaveUser", array (
			"mode" => "new",
			"userid" => $internalKey,
			"username" => $newusername,
			"userpassword" => $newpassword,
			"useremail" => $email,
			"userfullname" => $fullname,
			"userroleid" => $role
		));

		// invoke OnUserFormSave event
		$modx->invokeEvent("OnUserFormSave", array (
			"mode" => "new",
			"id" => $internalKey
		));

		/*******************************************************************************/
		// put the user in the user_groups he/ she should be in
		// first, check that up_perms are switched on!
		if ($use_udperms == 1) {
			if (count($user_groups) > 0) {
				for ($i = 0; $i < count($user_groups); $i++) {
					$f = array();
					$f['user_group'] = intval($user_groups[$i]);
					$f['member']     = $internalKey;
					$rs = $modx->db->insert($f, '[+prefix+]member_groups');
					if (!$rs) {
						webAlert("An error occurred while attempting to add the user to a user_group.");
						exit;
					}
				}
			}
		}
		// end of user_groups stuff!

		if ($passwordnotifymethod == 'e') {
			sendMailMessage($email, $newusername, $newpassword, $fullname);
			if ($_POST['stay'] != '') {
				$a = ($_POST['stay'] == '2') ? "12&id=$id" : "11";
				$header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
				header($header);
			} else {
				$header = "Location: index.php?a=75&r=2";
				header($header);
			}
		} else {
			if ($_POST['stay'] != '') {
				$a = ($_POST['stay'] == '2') ? "12&id=$internalKey" : "11";
				$stayUrl = "index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
			} else {
				$stayUrl = "index.php?a=75&r=2";
			}
			
			include_once "header.inc.php";
?>
			<h1><?php echo $_lang['user_title']; ?></h1>

			<div id="actions">
			<ul class="actionButtons">
				<li><a href="<?php echo $stayUrl ?>"><img src="<?php echo $_style["icons_save"] ?>" /> <?php echo $_lang['close']; ?></a></li>
			</ul>
			</div>
            <div class="section">
			<div class="sectionHeader"><?php echo $_lang['user_title']; ?></div>
			<div class="sectionBody">
			<div id="disp">
			<p>
			<?php echo sprintf($_lang["password_msg"], $newusername, $newpassword); ?>
			</p>
			</div>
			</div>
            </div>
		<?php

			include_once "footer.inc.php";
		}
		break;

	case '12' : // edit user
		// generate a new password for this user
		if ($genpassword == 1) {
			if ($specifiedpassword != "" && $passwordgenmethod == "spec") {
				if (strlen($specifiedpassword) < 6) {
					webAlert("Password is too short!");
					exit;
				} else {
					$newpassword = $specifiedpassword;
				}
			}
			elseif ($specifiedpassword == "" && $passwordgenmethod == "spec") {
				webAlert("You didn't specify a password for this user!");
				exit;
			}
			elseif ($passwordgenmethod == 'g') {
				$newpassword = generate_password(8);
			} else {
				webAlert("No password generation method specified!");
				exit;
			}
			$updatepasswordsql = ", password='" . $modx->manager->genHash($newpassword, $id) . "' ";
		}
		if ($passwordnotifymethod == 'e') {
			sendMailMessage($email, $newusername, $newpassword, $fullname);
		}

		// check if the username already exist
		$sql = "SELECT id FROM $dbase.`" . $table_prefix . "manager_users` WHERE username='$newusername'";
		if (!$rs = $modx->db->query($sql)) {
			webAlert("An error occurred while attempting to retrieve all users with username $newusername.");
			exit;
		}
		$limit = $modx->db->getRecordCount($rs);
		if ($limit > 0) {
			$row = $modx->db->getRow($rs);
			if ($row['id'] != $id) {
				webAlert("User name is already in use!");
				exit;
			}
		}

		// check if the email address already exists
		$sql = "SELECT internalKey FROM $dbase.`" . $table_prefix . "user_attributes` WHERE email='$email'";
		if (!$rs = $modx->db->query($sql)) {
			webAlert("An error occurred while attempting to retrieve all users with email $email.");
			exit;
		}
		$limit = $modx->db->getRecordCount($rs);
		if ($limit > 0) {
			$row = $modx->db->getRow($rs);
			if ($row['internalKey'] != $id) {
				webAlert("Email is already in use!");
				exit;
			}
		}

		// invoke OnBeforeUserFormSave event
		$modx->invokeEvent("OnBeforeUserFormSave", array (
			"mode" => "upd",
			"id" => $id
		));

		// update user name and password
		$sql = "UPDATE $dbase.`" . $table_prefix . "manager_users` SET username='$newusername'" . $updatepasswordsql . " WHERE id=$id";
		if (!$rs = $modx->db->query($sql)) {
			webAlert("An error occurred while attempting to update the user's data.");
			exit;
		}

		$sql = "UPDATE $dbase.`" . $table_prefix . "user_attributes` SET
					fullname='" . $fullname . "',
					role='$role',
					email='$email',
					phone='$phone',
					mobilephone='$mobilephone',
					fax='$fax',
					zip='$zip' ,
					street='$street',
					city='$city',
					state='$state',
					country='$country',
					gender='$gender',
					dob='$dob',
					photo='$photo',
					comment='$comment',
					failedlogincount='$failedlogincount',
					blocked=$blocked,
					blockeduntil='$blockeduntil',
					blockedafter='$blockedafter'
					WHERE internalKey=$id";
		if (!$rs = $modx->db->query($sql)) {
			webAlert("An error occurred while attempting to update the user's attributes.");
			exit;
		}

		// Save user settings
		saveUserSettings($id);

		// invoke OnManagerSaveUser event
		$modx->invokeEvent("OnManagerSaveUser", array (
			"mode" => "upd",
			"userid" => $id,
			"username" => $newusername,
			"userpassword" => $newpassword,
			"useremail" => $email,
			"userfullname" => $fullname,
			"userroleid" => $role,
			"oldusername" => (($oldusername != $newusername
		) ? $oldusername : ""), "olduseremail" => (($oldemail != $email) ? $oldemail : "")));

		// invoke OnManagerChangePassword event
		if ($updatepasswordsql)
			$modx->invokeEvent("OnManagerChangePassword", array (
				"userid" => $id,
				"username" => $newusername,
				"userpassword" => $newpassword
			));

		// invoke OnUserFormSave event
		$modx->invokeEvent("OnUserFormSave", array (
			"mode" => "upd",
			"id" => $id
		));

		/*******************************************************************************/
		// put the user in the user_groups he/ she should be in
		// first, check that up_perms are switched on!
		if ($use_udperms == 1) {
			// as this is an existing user, delete his/ her entries in the groups before saving the new groups
			$sql = "DELETE FROM $dbase.`" . $table_prefix . "member_groups` WHERE member=$id;";
			$rs = $modx->db->query($sql);
			if (!$rs) {
				webAlert("An error occurred while attempting to delete previous user_groups entries.");
				exit;
			}
			if (count($user_groups) > 0) {
				for ($i = 0; $i < count($user_groups); $i++) {
					$sql = "INSERT INTO $dbase.`" . $table_prefix . "member_groups` (user_group, member) values(" . intval($user_groups[$i]) . ", $id)";
					$rs = $modx->db->query($sql);
					if (!$rs) {
						webAlert("An error occurred while attempting to add the user to a user_group.<br />$sql;");
						exit;
					}
				}
			}
		}
		// end of user_groups stuff!
		/*******************************************************************************/
		if ($id == $modx->getLoginUserID() && ($genpassword !==1 && $passwordnotifymethod !='s')) {
?>
			<body bgcolor='#efefef'>
			<script language="JavaScript">
			alert("<?php echo $_lang["user_changeddata"]; ?>");
			top.location.href='index.php?a=8';
			</script>
			</body>
		<?php

			exit;
		}
		if ($genpassword == 1 && $passwordnotifymethod == 's') {
			if ($_POST['stay'] != '') {
				$a = ($_POST['stay'] == '2') ? "12&id=$id" : "11";
				$stayUrl = "index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
			} else {
				$stayUrl = "index.php?a=75&r=2";
			}
			
			include_once "header.inc.php";
?>
			<h1><?php echo $_lang['user_title']; ?></h1>

			<div id="actions">
			<ul class="actionButtons">
				<li><a href="<?php echo ($id == $modx->getLoginUserID()) ? 'index.php?a=8' : $stayUrl; ?>"><img src="<?php echo $_style["icons_save"] ?>" /> <?php echo ($id == $modx->getLoginUserID()) ? $_lang['logout'] : $_lang['close']; ?></a></li>
			</ul>
			</div>
            <div class="section">
			<div class="sectionHeader"><?php echo $_lang['user_title']; ?></div>
			<div class="sectionBody">
			<div id="disp">
			<p>
			<?php echo sprintf($_lang["password_msg"], $newusername, $newpassword).(($id == $modx->getLoginUserID()) ? ' '.$_lang['user_changeddata'] : ''); ?>
			</p>
			</div>
			</div>
            </div>
		<?php
			
			include_once "footer.inc.php";
		} else {
			if ($_POST['stay'] != '') {
				$a = ($_POST['stay'] == '2') ? "12&id=$id" : "11";
				$header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
				header($header);
			} else {
				$header = "Location: index.php?a=75&r=2";
				header($header);
			}
		}
		break;
	default :
		webAlert("Unauthorized access");
		exit;
}

// in case any plugins include a quoted_printable function
function save_user_quoted_printable($string) {
	$crlf = "\n" ;
	$string = preg_replace('!(\r\n|\r|\n)!', $crlf, $string) . $crlf ;
	$f[] = '/([\000-\010\013\014\016-\037\075\177-\377])/e' ;
	$r[] = "'=' . sprintf('%02X', ord('\\1'))" ; $f[] = '/([\011\040])' . $crlf . '/e' ;
	$r[] = "'=' . sprintf('%02X', ord('\\1')) . '" . $crlf . "'" ;
	$string = preg_replace($f, $r, $string) ;
	return trim(wordwrap($string, 70, ' =' . $crlf)) ;
}

// Send an email to the user
function sendMailMessage($email, $uid, $pwd, $ufn) {
	global $modx,$_lang,$signupemail_message;
	global $emailsubject, $emailsender;
	global $site_name, $site_start, $site_url;
	$manager_url = MODX_MANAGER_URL;
	$message = sprintf($signupemail_message, $uid, $pwd); // use old method
	// replace placeholders
	$message = str_replace("[+uid+]", $uid, $message);
	$message = str_replace("[+pwd+]", $pwd, $message);
	$message = str_replace("[+ufn+]", $ufn, $message);
	$message = str_replace("[+sname+]", $site_name, $message);
	$message = str_replace("[+saddr+]", $emailsender, $message);
	$message = str_replace("[+semail+]", $emailsender, $message);
	$message = str_replace("[+surl+]", $manager_url, $message);

	$param = array();
	$param['from']    = "{$site_name}<{$emailsender}>";
	$param['subject'] = $emailsubject;
	$param['body']    = $message;
	$param['to']      = $email;
	$rs = $modx->sendmail($param);
	if (!$rs) {
		webAlert("{$email} - {$_lang['error_sending_email']}");
		exit;
	}
}

// Save User Settings
function saveUserSettings($id) {
	global $modx;

	//$config = array();
	//$rs = $modx->db->query('SELECT * FROM '.$modx->getFullTableName('system_settings'));
	//while ($row = $modx->db->getRow($rs, 'num'))
	//	$config[$row[0]] = $row[1];

	// array of post values to ignore in this function
	$ignore = array(
		'id',
		'oldusername',
		'oldemail',
		'newusername',
		'fullname',
		'newpassword',
		'newpasswordcheck',
		'passwordgenmethod',
		'passwordnotifymethod',
		'specifiedpassword',
		'confirmpassword',
		'email',
		'phone',
		'mobilephone',
		'fax',
		'dob',
		'country',
		'street',
		'city',
		'state',
		'zip',
		'gender',
		'photo',
		'comment',
		'role',
		'failedlogincount',
		'blocked',
		'blockeduntil',
		'blockedafter',
		'user_groups',
		'mode',
		'blockedmode',
		'stay',
		'save',
		'theme_refresher'
	);

	// determine which settings can be saved blank (based on 'default_{settingname}' POST checkbox values)
	$defaults = array(
		'upload_images',
		'upload_media',
		'upload_flash',
		'upload_files'
	);

	// get user setting field names
	$settings= array ();
	foreach ($_POST as $n => $v) {
		if (in_array($n, $ignore) || (!in_array($n, $defaults) && trim($v) == '')) continue; // ignore blacklist and empties

		//if ($config[$n] == $v) continue; // ignore commonalities in base config

		$settings[$n] = $v; // this value should be saved
	}

	foreach ($defaults as $k) {
		if (isset($settings['default_'.$k]) && $settings['default_'.$k] == '1') {
			unset($settings[$k]);
		}
		unset($settings['default_'.$k]);
	}

	$usrTable = $modx->getFullTableName('user_settings');

	$modx->db->query('DELETE FROM '.$usrTable.' WHERE user='.$id);

	$savethese = array();
	foreach ($settings as $k => $v) {
	    if(is_array($v)) $v = implode(',', $v);
	    $savethese[] = '('.$id.', \''.$k.'\', \''.$modx->db->escape($v).'\')';
	}

	$sql = 'INSERT INTO '.$usrTable.' (user, setting_name, setting_value)
		VALUES '.implode(', ', $savethese);
	if (!@$rs = $modx->db->query($sql)) {
		die('Failed to update user settings!');
	}
}

// converts date format dd-mm-yyyy to php date
function ConvertDate($date) {
	global $modx;
	if ($date == "") {return "0";}
	else {}          {return $modx->toTimeStamp($date);}
}
?>