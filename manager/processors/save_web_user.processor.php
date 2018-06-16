<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('save_web_user')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$tbl_web_users = $modx->getDatabase()->getFullTableName('web_users');
$tbl_web_user_attributes = $modx->getDatabase()->getFullTableName('web_user_attributes');
$tbl_web_groups = $modx->getDatabase()->getFullTableName('web_groups');

$input = $_POST;
foreach($input as $k => $v) {
	if($k !== 'comment') {
		$v = $modx->getPhpCompat()->htmlspecialchars($v, ENT_NOQUOTES);
	}
	$input[$k] = $v;
}

$id = (int)$input['id'];
$oldusername = $input['oldusername'];
$newusername = !empty ($input['newusername']) ? trim($input['newusername']) : "New User";
$esc_newusername = $modx->getDatabase()->escape($newusername);
$fullname = $input['fullname'];
$genpassword = $input['newpassword'];
$passwordgenmethod = $input['passwordgenmethod'];
$passwordnotifymethod = $input['passwordnotifymethod'];
$specifiedpassword = $input['specifiedpassword'];
$email = trim($input['email']);
$esc_email = $modx->getDatabase()->escape($email);
$oldemail = $input['oldemail'];
$phone = $input['phone'];
$mobilephone = $input['mobilephone'];
$fax = $input['fax'];
$dob = !empty ($input['dob']) ? $modx->toTimeStamp($input['dob']) : 0;
$country = $input['country'];
$street = $input['street'];
$city = $input['city'];
$state = $input['state'];
$zip = $input['zip'];
$gender = !empty($input['gender']) ? $input['gender'] : 0;
$photo = $input['photo'];
$comment = $input['comment'];
$role = !empty($input['role']) ? $input['role'] : 0;
$failedlogincount = !empty($input['failedlogincount']) ? $input['failedlogincount'] : 0;
$blocked = !empty($input['blocked']) ? $input['blocked'] : 0;
$blockeduntil = !empty($input['blockeduntil']) ? $modx->toTimeStamp($input['blockeduntil']) : 0;
$blockedafter = !empty($input['blockedafter']) ? $modx->toTimeStamp($input['blockedafter']) : 0;
$user_groups = $input['user_groups'];

// verify password
if($passwordgenmethod == "spec" && $input['specifiedpassword'] != $input['confirmpassword']) {
	webAlertAndQuit("Password typed is mismatched", 88);
}

// verify email
if($email == '' || !preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,24}$/i", $email)) {
	webAlertAndQuit("E-mail address doesn't seem to be valid!", 88);
}

switch($input['mode']) {
	case '87' : // new user
		// check if this user name already exist
		$rs = $modx->getDatabase()->select('count(id)', $tbl_web_users, "username='{$esc_newusername}'");
		$limit = $modx->getDatabase()->getValue($rs);
		if($limit > 0) {
			webAlertAndQuit("User name is already in use!", 88);
		}

		// check if the email address already exist
		if ($modx->config['allow_multiple_emails'] != 1) {
			$rs = $modx->getDatabase()->select('count(id)', $tbl_web_user_attributes, "email='{$esc_email}' AND id!='{$id}'");
			$limit = $modx->getDatabase()->getValue($rs);
			if($limit > 0) {
				webAlertAndQuit("Email is already in use!", 88);
			}
		}

		// generate a new password for this user
		if($specifiedpassword != "" && $passwordgenmethod == "spec") {
			if(strlen($specifiedpassword) < 6) {
				webAlertAndQuit("Password is too short!", 88);
			} else {
				$newpassword = $specifiedpassword;
			}
		} elseif($specifiedpassword == "" && $passwordgenmethod == "spec") {
			webAlertAndQuit("You didn't specify a password for this user!", 88);
		} elseif($passwordgenmethod == 'g') {
			$newpassword = generate_password(8);
		} else {
			webAlertAndQuit("No password generation method specified!", 88);
		}

		// invoke OnBeforeWUsrFormSave event
		$modx->invokeEvent("OnBeforeWUsrFormSave", array(
			"mode" => "new",
		));

		// create the user account
		$field = array();
		$field['username'] = $esc_newusername;
		$field['password'] = md5($newpassword);
		$internalKey = $modx->getDatabase()->insert($field, $tbl_web_users);

		$field = compact('internalKey', 'fullname', 'role', 'email', 'phone', 'mobilephone', 'fax', 'zip', 'street', 'city', 'state', 'country', 'gender', 'dob', 'photo', 'comment', 'blocked', 'blockeduntil', 'blockedafter');
		$field = $modx->getDatabase()->escape($field);
		$modx->getDatabase()->insert($field, $tbl_web_user_attributes);

		// Save User Settings
        saveWebUserSettings($internalKey);

		// Set the item name for logger
		$_SESSION['itemname'] = $newusername;

		/*******************************************************************************/
		// put the user in the user_groups he/ she should be in
		// first, check that up_perms are switched on!
		if($use_udperms == 1) {
			if(!empty($user_groups)) {
				for($i = 0; $i < count($user_groups); $i++) {
					$f = array();
					$f['webgroup'] = (int)$user_groups[$i];
					$f['webuser'] = $internalKey;
					$modx->getDatabase()->insert($f, $tbl_web_groups);
				}
			}
		}
		// end of user_groups stuff!

        // invoke OnWebSaveUser event
        $modx->invokeEvent("OnWebSaveUser", array(
            "mode" => "new",
            "userid" => $internalKey,
            "username" => $newusername,
            "userpassword" => $newpassword,
            "useremail" => $email,
            "userfullname" => $fullname
        ));

        // invoke OnWUsrFormSave event
        $modx->invokeEvent("OnWUsrFormSave", array(
            "mode" => "new",
            "id" => $internalKey
        ));

		if($passwordnotifymethod == 'e') {
            sendMailMessageForUser($email, $newusername, $newpassword, $fullname, $websignupemail_message, $site_url);
			if($input['stay'] != '') {
				$a = ($input['stay'] == '2') ? "88&id={$internalKey}" : "87";
				$header = "Location: index.php?a={$a}&r=2&stay=" . $input['stay'];
				header($header);
			} else {
				$header = "Location: index.php?a=99&r=2";
				header($header);
			}
		} else {
			if($input['stay'] != '') {
				$a = ($input['stay'] == '2') ? "88&id={$internalKey}" : "87";
				$stayUrl = "index.php?a={$a}&r=2&stay=" . $input['stay'];
			} else {
				$stayUrl = "index.php?a=99&r=2";
			}

			include_once "header.inc.php";
			?>

			<h1><?php echo $_lang['web_user_title']; ?></h1>

			<div id="actions">
				<ul class="actionButtons">
					<li class="transition"><a href="<?php echo $stayUrl ?>"><i class="<?php echo $_style["actions_save"] ?>"></i> <?php echo $_lang['edit']; ?></a></li>
				</ul>
			</div>

			<div class="section">
				<div class="sectionHeader"><?php echo $_lang['web_user_title']; ?></div>
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
	case '88' : // edit user
		// generate a new password for this user
		if($genpassword == 1) {
			if($specifiedpassword != "" && $passwordgenmethod == "spec") {
				if(strlen($specifiedpassword) < 6) {
					webAlertAndQuit("Password is too short!", 88);
				} else {
					$newpassword = $specifiedpassword;
				}
			} elseif($specifiedpassword == "" && $passwordgenmethod == "spec") {
				webAlertAndQuit("You didn't specify a password for this user!", 88);
			} elseif($passwordgenmethod == 'g') {
				$newpassword = generate_password(8);
			} else {
				webAlertAndQuit("No password generation method specified!", 88);
			}
		}
		if($passwordnotifymethod == 'e') {
            sendMailMessageForUser($email, $newusername, $newpassword, $fullname, $websignupemail_message, $site_url);
		}

		// check if the username already exist
		$rs = $modx->getDatabase()->select('count(id)', $tbl_web_users, "username='{$esc_newusername}' AND id!='{$id}'");
		$limit = $modx->getDatabase()->getValue($rs);
		if($limit > 0) {
			webAlertAndQuit("User name is already in use!", 88);
		}

		// check if the email address already exists
		if ($modx->config['allow_multiple_emails'] != 1) {
			$rs = $modx->getDatabase()->select('count(internalKey)', $tbl_web_user_attributes, "email='{$esc_email}' AND internalKey!='{$id}'");
			$limit = $modx->getDatabase()->getValue($rs);
			if($limit > 0) {
				webAlertAndQuit("Email is already in use!", 88);
			}
		}

		// invoke OnBeforeWUsrFormSave event
		$modx->invokeEvent("OnBeforeWUsrFormSave", array(
			"mode" => "upd",
			"id" => $id
		));

		// update user name and password
		$field = array();
		$field['username'] = $esc_newusername;
		if($genpassword == 1) {
			$field['password'] = md5($newpassword);
		}
		$modx->getDatabase()->update($field, $tbl_web_users, "id='{$id}'");
		$field = compact('fullname', 'role', 'email', 'phone', 'mobilephone', 'fax', 'zip', 'street', 'city', 'state', 'country', 'gender', 'dob', 'photo', 'comment', 'failedlogincount', 'blocked', 'blockeduntil', 'blockedafter');
		$field = $modx->getDatabase()->escape($field);
		$modx->getDatabase()->update($field, $tbl_web_user_attributes, "internalKey='{$id}'");

		// Save User Settings
        saveWebUserSettings($id);

		// Set the item name for logger
		$_SESSION['itemname'] = $newusername;

		/*******************************************************************************/
		// put the user in the user_groups he/ she should be in
		// first, check that up_perms are switched on!
		if($use_udperms == 1) {
			// as this is an existing user, delete his/ her entries in the groups before saving the new groups
			$modx->getDatabase()->delete($tbl_web_groups, "webuser='{$id}'");
			if(!empty($user_groups)) {
				for($i = 0; $i < count($user_groups); $i++) {
					$field = array();
					$field['webgroup'] = (int)$user_groups[$i];
					$field['webuser'] = $id;
					$modx->getDatabase()->insert($field, $tbl_web_groups);
				}
			}
		}
		// end of user_groups stuff!
		/*******************************************************************************/

        // invoke OnWebSaveUser event
        $modx->invokeEvent("OnWebSaveUser", array(
            "mode" => "upd",
            "userid" => $id,
            "username" => $newusername,
            "userpassword" => $newpassword,
            "useremail" => $email,
            "userfullname" => $fullname,
            "oldusername" => (($oldusername != $newusername) ? $oldusername : ""),
            "olduseremail" => (($oldemail != $email) ? $oldemail : "")
        ));

        // invoke OnWebChangePassword event
        if($genpassword == 1) {
            $modx->invokeEvent("OnWebChangePassword", array(
                "userid" => $id,
                "username" => $newusername,
                "userpassword" => $newpassword
            ));
        }

        // invoke OnWUsrFormSave event
        $modx->invokeEvent("OnWUsrFormSave", array(
            "mode" => "upd",
            "id" => $id
        ));

		if($genpassword == 1 && $passwordnotifymethod == 's') {
			if($input['stay'] != '') {
				$a = ($input['stay'] == '2') ? "88&id={$id}" : "87";
				$stayUrl = "index.php?a={$a}&r=2&stay=" . $input['stay'];
			} else {
				$stayUrl = "index.php?a=99&r=2";
			}

			include_once "header.inc.php";
			?>

			<h1><?php echo $_lang['web_user_title']; ?></h1>

			<div id="actions">
				<ul class="actionButtons">
					<li class="transition"><a href="<?php echo $stayUrl ?>"><i class="<?php echo $_style["actions_save"] ?>"></i> <?php echo $_lang['edit']; ?></a></li>
				</ul>
			</div>

			<div class="section">
				<div class="sectionHeader"><?php echo $_lang['web_user_title']; ?></div>
				<div class="sectionBody">
					<div id="disp">
						<p><?php echo sprintf($_lang["password_msg"], $newusername, $newpassword); ?></p>
					</div>
				</div>
			</div>
			<?php

			include_once "footer.inc.php";
		} else {
			if($input['stay'] != '') {
				$a = ($input['stay'] == '2') ? "88&id={$id}" : "87";
				$header = "Location: index.php?a={$a}&r=2&stay=" . $input['stay'];
				header($header);
			} else {
				$header = "Location: index.php?a=99&r=2";
				header($header);
			}
		}
		break;
	default :
		webAlertAndQuit("No operation set in request.", 88);
}
