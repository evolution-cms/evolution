<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('save_user')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$modx->loadExtension('phpass');

$tbl_manager_users = $modx->getFullTableName('manager_users');
$tbl_user_attributes = $modx->getFullTableName('user_attributes');
$tbl_member_groups = $modx->getFullTableName('member_groups');

$input = $_POST;

$id = (int)$input['id'];
$oldusername = $input['oldusername'];
$newusername = !empty ($input['newusername']) ? trim($input['newusername']) : "New User";
$fullname = $input['fullname'];
$genpassword = $input['newpassword'];
$passwordgenmethod = $input['passwordgenmethod'];
$passwordnotifymethod = $input['passwordnotifymethod'];
$specifiedpassword = $input['specifiedpassword'];
$email = trim($input['email']);
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
if ($passwordgenmethod == "spec" && $input['specifiedpassword'] != $input['confirmpassword']) {
    webAlertAndQuit("Password typed is mismatched");
}

// verify email
if ($email == '' || !preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,24}$/i", $email)) {
    webAlertAndQuit("E-mail address doesn't seem to be valid!");
}

// verify admin security
if ($_SESSION['mgrRole'] != 1) {
    // Check to see if user tried to spoof a "1" (admin) role
    if (!$modx->hasPermission('save_user')) {
        webAlertAndQuit("Illegal attempt to create/modify administrator by non-administrator!", 12);
    }
    // Verify that the user being edited wasn't an admin and the user ID got spoofed
    $rs = $modx->db->select('count(internalKey)', $tbl_user_attributes, "internalKey='{$id}' AND role=1");
    $limit = $modx->db->getValue($rs);
    if ($limit > 0) {
        webAlertAndQuit("You cannot alter an administrative user.");
    }

}

switch ($input['mode']) {
    case '11' : // new user
        // check if this user name already exist
        $rs = $modx->db->select('count(id)', $tbl_manager_users,
            sprintf("username='%s'", $modx->db->escape($newusername)));
        $limit = $modx->db->getValue($rs);
        if ($limit > 0) {
            webAlertAndQuit("User name is already in use!");
        }

        // check if the email address already exist
        $rs = $modx->db->select('count(internalKey)', $tbl_user_attributes,
            sprintf("email='%s' AND id!='%s'", $modx->db->escape($email), $id));
        $limit = $modx->db->getValue($rs);
        if ($limit > 0) {
            webAlertAndQuit("Email is already in use!");
        }

        // generate a new password for this user
        if ($specifiedpassword != "" && $passwordgenmethod == "spec") {
            if (strlen($specifiedpassword) < 6) {
                webAlertAndQuit("Password is too short!");
            } else {
                $newpassword = $specifiedpassword;
            }
        } elseif ($specifiedpassword == "" && $passwordgenmethod == "spec") {
            webAlertAndQuit("You didn't specify a password for this user!");
        } elseif ($passwordgenmethod == 'g') {
            $newpassword = generate_password(8);
        } else {
            webAlertAndQuit("No password generation method specified!");
        }

        // invoke OnBeforeUserFormSave event
        $modx->invokeEvent("OnBeforeUserFormSave", array(
            "mode" => "new",
        ));

        // create the user account
        $internalKey = $modx->db->insert(array('username' => $modx->db->escape($newusername)), $tbl_manager_users);

        $field = array();
        $field['password'] = $modx->phpass->HashPassword($newpassword);
        $modx->db->update($field, $tbl_manager_users, "id='{$internalKey}'");

        $field = compact('internalKey', 'fullname', 'role', 'email', 'phone', 'mobilephone', 'fax', 'zip', 'street',
            'city', 'state', 'country', 'gender', 'dob', 'photo', 'comment', 'blocked', 'blockeduntil', 'blockedafter');
        $field = $modx->db->escape($field);
        $modx->db->insert($field, $tbl_user_attributes);

        // Save user settings
        saveUserSettings($internalKey);

        // invoke OnManagerSaveUser event
        $modx->invokeEvent("OnManagerSaveUser", array(
            "mode" => "new",
            "userid" => $internalKey,
            "username" => $newusername,
            "userpassword" => $newpassword,
            "useremail" => $email,
            "userfullname" => $fullname,
            "userroleid" => $role
        ));

        // invoke OnUserFormSave event
        $modx->invokeEvent("OnUserFormSave", array(
            "mode" => "new",
            "id" => $internalKey
        ));

        // Set the item name for logger
        $_SESSION['itemname'] = $newusername;

        /*******************************************************************************/
        // put the user in the user_groups he/ she should be in
        // first, check that up_perms are switched on!
        if ($use_udperms == 1) {
            if (!empty($user_groups)) {
                for ($i = 0; $i < count($user_groups); $i++) {
                    $f = array();
                    $f['user_group'] = (int)$user_groups[$i];
                    $f['member'] = $internalKey;
                    $modx->db->insert($f, $tbl_member_groups);
                }
            }
        }
        // end of user_groups stuff!

        if ($passwordnotifymethod == 'e') {
            sendMailMessage($email, $newusername, $newpassword, $fullname);
            if ($input['stay'] != '') {
                $a = ($input['stay'] == '2') ? "12&id={$internalKey}" : "11";
                $header = "Location: index.php?a={$a}&r=2&stay=" . $input['stay'];
                header($header);
            } else {
                $header = "Location: index.php?a=75&r=2";
                header($header);
            }
        } else {
            if ($input['stay'] != '') {
                $a = ($input['stay'] == '2') ? "12&id={$internalKey}" : "11";
                $stayUrl = "index.php?a={$a}&r=2&stay=" . $input['stay'];
            } else {
                $stayUrl = "index.php?a=75&r=2";
            }

            include_once "header.inc.php";
            ?>

            <h1><?php echo $_lang['user_title']; ?></h1>

            <div id="actions">
                <div class="btn-group">
                    <a class="btn" href="<?php echo $stayUrl ?>"><i class="<?php echo $_style["actions_save"] ?>"></i> <?php echo $_lang['edit']; ?>
                    </a>
                </div>
            </div>

            <div class="sectionBody">
                <div class="tab-page">
                    <div class="container container-body" id="disp">
                        <p>
                            <?php echo sprintf($_lang["password_msg"], $modx->htmlspecialchars($newusername),
                                $modx->htmlspecialchars($newpassword)); ?>
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
                    webAlertAndQuit("Password is too short!");
                } else {
                    $newpassword = $specifiedpassword;
                }
            } elseif ($specifiedpassword == "" && $passwordgenmethod == "spec") {
                webAlertAndQuit("You didn't specify a password for this user!");
            } elseif ($passwordgenmethod == 'g') {
                $newpassword = generate_password(8);
            } else {
                webAlertAndQuit("No password generation method specified!");
            }
        }
        if ($passwordnotifymethod == 'e') {
            sendMailMessage($email, $newusername, $newpassword, $fullname);
        }

        // check if the username already exist
        $rs = $modx->db->select('count(id)', $tbl_manager_users,
            sprintf("username='%s' AND id!='%s'", $modx->db->escape($newusername), $id));
        $limit = $modx->db->getValue($rs);
        if ($limit > 0) {
            webAlertAndQuit("User name is already in use!");
        }

        // check if the email address already exists
        $rs = $modx->db->select('count(internalKey)', $tbl_user_attributes,
            sprintf("email='%s' AND internalKey!='%s'", $modx->db->escape($email), $id));
        $limit = $modx->db->getValue($rs);
        if ($limit > 0) {
            webAlertAndQuit("Email is already in use!");
        }

        // invoke OnBeforeUserFormSave event
        $modx->invokeEvent("OnBeforeUserFormSave", array(
            "mode" => "upd",
            "id" => $id
        ));

        // update user name and password
        $field = array();
        $field['username'] = $modx->db->escape($newusername);
        if ($genpassword == 1) {
            $field['password'] = $modx->phpass->HashPassword($newpassword);
        }
        $modx->db->update($field, $tbl_manager_users, "id='{$id}'");
        $field = compact('fullname', 'role', 'email', 'phone', 'mobilephone', 'fax', 'zip', 'street', 'city', 'state',
            'country', 'gender', 'dob', 'photo', 'comment', 'failedlogincount', 'blocked', 'blockeduntil',
            'blockedafter');
        $field = $modx->db->escape($field);
        $modx->db->update($field, $tbl_user_attributes, "internalKey='{$id}'");

        // Save user settings
        saveUserSettings($id);

        // Set the item name for logger
        $_SESSION['itemname'] = $newusername;

        // invoke OnManagerSaveUser event
        $modx->invokeEvent("OnManagerSaveUser", array(
            "mode" => "upd",
            "userid" => $id,
            "username" => $newusername,
            "userpassword" => $newpassword,
            "useremail" => $email,
            "userfullname" => $fullname,
            "userroleid" => $role,
            "oldusername" => (($oldusername != $newusername) ? $oldusername : ""),
            "olduseremail" => (($oldemail != $email) ? $oldemail : "")
        ));

        // invoke OnManagerChangePassword event
        if ($genpassword == 1) {
            $modx->invokeEvent("OnManagerChangePassword", array(
                "userid" => $id,
                "username" => $newusername,
                "userpassword" => $newpassword
            ));
        }

        // invoke OnUserFormSave event
        $modx->invokeEvent("OnUserFormSave", array(
            "mode" => "upd",
            "id" => $id
        ));

        /*******************************************************************************/
        // put the user in the user_groups he/ she should be in
        // first, check that up_perms are switched on!
        if ($use_udperms == 1) {
            // as this is an existing user, delete his/ her entries in the groups before saving the new groups
            $modx->db->delete($tbl_member_groups, "member='{$id}'");
            if (!empty($user_groups)) {
                for ($i = 0; $i < count($user_groups); $i++) {
                    $field = array();
                    $field['user_group'] = (int)$user_groups[$i];
                    $field['member'] = $id;
                    $modx->db->insert($field, $tbl_member_groups);
                }
            }
        }
        // end of user_groups stuff!
        /*******************************************************************************/
        if ($id == $modx->getLoginUserID() && ($genpassword !== 1 && $passwordnotifymethod != 's')) {
            $modx->webAlertAndQuit($_lang["user_changeddata"], 'javascript:top.location.href="index.php?a=8";');
        }
        if ($genpassword == 1 && $passwordnotifymethod == 's') {
            if ($input['stay'] != '') {
                $a = ($input['stay'] == '2') ? "12&id={$id}" : "11";
                $stayUrl = "index.php?a={$a}&r=2&stay=" . $input['stay'];
            } else {
                $stayUrl = "index.php?a=75&r=2";
            }

            include_once "header.inc.php";
            ?>

            <h1><?php echo $_lang['user_title']; ?></h1>

            <div id="actions">
                <div class="btn-group">
                    <a class="btn" href="<?php echo ($id == $modx->getLoginUserID()) ? 'index.php?a=8' : $stayUrl;
                    ?>"><i
                            class="<?php echo $_style["actions_save"] ?>"></i> <?php echo ($id == $modx->getLoginUserID()) ? $_lang['logout'] : $_lang['edit']; ?>
                    </a>
                </div>
            </div>

            <div class="sectionBody">
                <div class="tab-page">
                    <div class="container container-body" id="disp">
                        <p><?php echo sprintf($_lang["password_msg"], $modx->htmlspecialchars($newusername),
                                    $modx->htmlspecialchars($newpassword)) . (($id == $modx->getLoginUserID()) ? ' ' . $_lang['user_changeddata'] : ''); ?></p>
                    </div>
                </div>
            </div>
            <?php

            include_once "footer.inc.php";
        } else {
            if ($input['stay'] != '') {
                $a = ($input['stay'] == '2') ? "12&id={$id}" : "11";
                $header = "Location: index.php?a={$a}&r=2&stay=" . $input['stay'];
                header($header);
            } else {
                $header = "Location: index.php?a=75&r=2";
                header($header);
            }
        }
        break;
    default:
        webAlertAndQuit("No operation set in request.");
}

/**
 * Send an email to the user
 *
 * @param string $email
 * @param string $uid
 * @param string $pwd
 * @param string $ufn
 */
function sendMailMessage(
    $email,
    $uid,
    $pwd,
    $ufn
) {
    $modx = evolutionCMS();
    global $_lang, $signupemail_message;
    global $emailsubject, $emailsender;
    global $site_name;
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
    $param['from'] = "{$site_name}<{$emailsender}>";
    $param['subject'] = $emailsubject;
    $param['body'] = $message;
    $param['to'] = $email;
    $param['type'] = 'text';
    $rs = $modx->sendmail($param);
    if (!$rs) {
        $modx->manager->saveFormValues();
        $modx->messageQuit("{$email} - {$_lang['error_sending_email']}");
    }
}

/**
 * Save User Settings
 *
 * @param int $id
 */
function saveUserSettings($id)
{
    $modx = evolutionCMS();
    $tbl_user_settings = $modx->getFullTableName('user_settings');

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
    $settings = array();
    foreach ($_POST as $n => $v) {
        if (in_array($n, $ignore) || (!in_array($n, $defaults) && is_scalar($v) && trim($v) == '') || (!in_array($n,
                    $defaults) && is_array($v) && empty($v))) {
            continue;
        } // ignore blacklist and empties
        $settings[$n] = $v; // this value should be saved
    }

    foreach ($defaults as $k) {
        if (isset($settings['default_' . $k]) && $settings['default_' . $k] == '1') {
            unset($settings[$k]);
        }
        unset($settings['default_' . $k]);
    }

    $modx->db->delete($tbl_user_settings, "user='{$id}'");

    foreach ($settings as $n => $vl) {
        if (is_array($vl)) {
            $vl = implode(",", $vl);
        }
        if ($vl != '') {
            $f = array();
            $f['user'] = $id;
            $f['setting_name'] = $n;
            $f['setting_value'] = $vl;
            $f = $modx->db->escape($f);
            $modx->db->insert($f, $tbl_user_settings);
        }
    }
}

/**
 * Web alert -  sends an alert to web browser
 *
 * @param $msg
 */
function webAlertAndQuit($msg)
{
    global $id, $modx;
    $mode = $_POST['mode'];
    $modx->manager->saveFormValues($mode);
    $modx->webAlertAndQuit($msg, "index.php?a={$mode}" . ($mode == '12' ? "&id={$id}" : ''));
}

/**
 * Generate password
 *
 * @param int $length
 * @return string
 */
function generate_password($length = 10)
{
    $allowable_characters = "abcdefghjkmnpqrstuvxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    $ps_len = strlen($allowable_characters);
    mt_srand((double)microtime() * 1000000);
    $pass = "";
    for ($i = 0; $i < $length; $i++) {
        $pass .= $allowable_characters[mt_rand(0, $ps_len - 1)];
    }
    return $pass;
}
