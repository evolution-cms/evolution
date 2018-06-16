<?php

if (!function_exists('getChildrenForDelete')) {
    /**
     * @param int $parent
     */
    function getChildrenForDelete($parent)
    {
        $modx = evolutionCMS();
        global $children;
        global $site_start;
        global $site_unavailable_page;
        global $error_page;
        global $unauthorized_page;

        $parent = $modx->getDatabase()->escape($parent);
        $rs = $modx->getDatabase()->select('id', $modx->getDatabase()->getFullTableName('site_content'), "parent={$parent} AND deleted=0");
        // the document has children documents, we'll need to delete those too
        while ($childid = $modx->getDatabase()->getValue($rs)) {
            if ($childid == $site_start) {
                $modx->webAlertAndQuit("The document you are trying to delete is a folder containing document {$childid}. This document is registered as the 'Site start' document, and cannot be deleted. Please assign another document as your 'Site start' document and try again.");
            }
            if ($childid == $site_unavailable_page) {
                $modx->webAlertAndQuit("The document you are trying to delete is a folder containing document {$childid}. This document is registered as the 'Site unavailable page' document, and cannot be deleted. Please assign another document as your 'Site unavailable page' document and try again.");
            }
            if ($childid == $error_page) {
                $modx->webAlertAndQuit("The document you are trying to delete is a folder containing document {$childid}. This document is registered as the 'Site error page' document, and cannot be deleted. Please assign another document as your 'Site error page' document and try again.");
            }
            if ($childid == $unauthorized_page) {
                $modx->webAlertAndQuit("The document you are trying to delete is a folder containing document {$childid}. This document is registered as the 'Site unauthorized page' document, and cannot be deleted. Please assign another document as your 'Site unauthorized page' document and try again.");
            }
            $children[] = $childid;
            getChildrenForDelete($childid);
            //echo "Found childNode of parentNode $parent: ".$childid."<br />";
        }
    }
}

if(!function_exists('duplicateDocument')) {
    /**
     * @param int $docid
     * @param null|int $parent
     * @param int $_toplevel
     * @return int
     */
    function duplicateDocument($docid, $parent = null, $_toplevel = 0)
    {
        $modx = evolutionCMS();
        global $_lang;

        // invoke OnBeforeDocDuplicate event
        $evtOut = $modx->invokeEvent('OnBeforeDocDuplicate', array(
            'id' => $docid
        ));

        // if( !in_array( 'false', array_values( $evtOut ) ) ){}
        // TODO: Determine necessary handling for duplicateDocument "return $newparent" if OnBeforeDocDuplicate were able to conditially control duplication
        // [DISABLED]: Proceed with duplicateDocument if OnBeforeDocDuplicate did not return false via: $event->output('false');

        $userID = $modx->getLoginUserID();

        $tblsc = $modx->getDatabase()->getFullTableName('site_content');

        // Grab the original document
        $rs = $modx->getDatabase()->select('*', $tblsc, "id='{$docid}'");
        $content = $modx->getDatabase()->getRow($rs);

        // Handle incremental ID
        switch ($modx->config['docid_incrmnt_method']) {
            case '1':
                $from = "{$tblsc} AS T0 LEFT JOIN {$tblsc} AS T1 ON T0.id + 1 = T1.id";
                $rs = $modx->getDatabase()->select('MIN(T0.id)+1', $from, "T1.id IS NULL");
                $content['id'] = $modx->getDatabase()->getValue($rs);
                break;
            case '2':
                $rs = $modx->getDatabase()->select('MAX(id)+1', $tblsc);
                $content['id'] = $modx->getDatabase()->getValue($rs);
                break;

            default:
                unset($content['id']); // remove the current id.
        }

        // Once we've grabbed the document object, start doing some modifications
        if ($_toplevel == 0) {
            // count duplicates
            $pagetitle = $modx->getDatabase()->getValue($modx->getDatabase()->select('pagetitle', $modx->getDatabase()->getFullTableName('site_content'),
                "id='{$docid}'"));
            $pagetitle = $modx->getDatabase()->escape($pagetitle);
            $count = $modx->getDatabase()->getRecordCount($modx->getDatabase()->select('pagetitle', $modx->getDatabase()->getFullTableName('site_content'),
                "pagetitle LIKE '{$pagetitle} Duplicate%'"));
            if ($count >= 1) {
                $count = ' ' . ($count + 1);
            } else {
                $count = '';
            }

            $content['pagetitle'] = $_lang['duplicated_el_suffix'] . $count . ' ' . $content['pagetitle'];
            $content['alias'] = null;
        } elseif ($modx->config['friendly_urls'] == 0 || $modx->config['allow_duplicate_alias'] == 0) {
            $content['alias'] = null;
        }

        // change the parent accordingly
        if ($parent !== null) {
            $content['parent'] = $parent;
        }

        // Change the author
        $content['createdby'] = $userID;
        $content['createdon'] = time();
        // Remove other modification times
        $content['editedby'] = $content['editedon'] = $content['deleted'] = $content['deletedby'] = $content['deletedon'] = 0;

        // [FS#922] Should the published status be honored? - sirlancelot
//	if ($modx->hasPermission('publish_document')) {
//		if ($modx->config['publish_default'])
//			$content['pub_date'] = $content['pub_date']; // should this be changed to 1?
//		else	$content['pub_date'] = 0;
//	} else {
        // User can't publish documents
//		$content['published'] = $content['pub_date'] = 0;
//	}

        // Set the published status to unpublished by default (see above ... commit #3388)
        $content['published'] = $content['pub_date'] = 0;

        // Escape the proper strings
        $content = $modx->getDatabase()->escape($content);

        // Duplicate the Document
        $newparent = $modx->getDatabase()->insert($content, $tblsc);

        // duplicate document's TVs
        duplicateTVs($docid, $newparent);
        duplicateAccess($docid, $newparent);

        // invoke OnDocDuplicate event
        $evtOut = $modx->invokeEvent('OnDocDuplicate', array(
            'id'     => $docid,
            'new_id' => $newparent
        ));

        // Start duplicating all the child documents that aren't deleted.
        $_toplevel++;
        $rs = $modx->getDatabase()->select('id', $tblsc, "parent='{$docid}' AND deleted=0", 'id ASC');
        while ($row = $modx->getDatabase()->getRow($rs)) {
            duplicateDocument($row['id'], $newparent, $_toplevel);
        }

        // return the new doc id
        return $newparent;
    }
}

if(!function_exists('duplicateTVs')) {
    /**
     * Duplicate Document TVs
     *
     * @param int $oldid
     * @param int $newid
     */
    function duplicateTVs($oldid, $newid)
    {
        $modx = evolutionCMS();

        $tbltvc = $modx->getDatabase()->getFullTableName('site_tmplvar_contentvalues');

        $newid = (int)$newid;
        $oldid = (int)$oldid;

        $modx->getDatabase()->insert(
            array('contentid' => '', 'tmplvarid' => '', 'value' => ''), $tbltvc, // Insert into
            "{$newid}, tmplvarid, value", $tbltvc, "contentid='{$oldid}'" // Copy from
        );
    }
}

if(!function_exists('duplicateAccess')) {
    /**
     * Duplicate Document Access Permissions
     *
     * @param int $oldid
     * @param int $newid
     */
    function duplicateAccess($oldid, $newid)
    {
        $modx = evolutionCMS();

        $tbldg = $modx->getDatabase()->getFullTableName('document_groups');

        $newid = (int)$newid;
        $oldid = (int)$oldid;

        $modx->getDatabase()->insert(
            array('document' => '', 'document_group' => ''), $tbldg, // Insert into
            "{$newid}, document_group", $tbldg, "document='{$oldid}'" // Copy from
        );
    }
}

if(!function_exists('evalModule')) {
    /**
     * evalModule
     *
     * @param string $moduleCode
     * @param array $params
     * @return string
     */
    function evalModule($moduleCode, $params)
    {
        $modx = evolutionCMS();
        $modx->event->params = &$params; // store params inside event object
        if (is_array($params)) {
            extract($params, EXTR_SKIP);
        }
        ob_start();
        $mod = eval($moduleCode);
        $msg = ob_get_contents();
        ob_end_clean();
        if (isset($php_errormsg)) {
            $error_info = error_get_last();
            switch ($error_info['type']) {
                case E_NOTICE :
                    $error_level = 1;
                case E_USER_NOTICE :
                    break;
                case E_DEPRECATED :
                case E_USER_DEPRECATED :
                case E_STRICT :
                    $error_level = 2;
                    break;
                default:
                    $error_level = 99;
            }
            if ($modx->config['error_reporting'] === '99' || 2 < $error_level) {
                $modx->messageQuit('PHP Parse Error', '', true, $error_info['type'], $error_info['file'],
                    $_SESSION['itemname'] . ' - Module', $error_info['message'], $error_info['line'], $msg);
                $modx->event->alert("An error occurred while loading. Please see the event log for more information<p>{$msg}</p>");
            }
        }
        unset($modx->event->params);

        return $mod . $msg;
    }
}

if(!function_exists('allChildren')) {
    /**
     * @param int $currDocID
     * @return array
     */
    function allChildren($currDocID)
    {
        $modx = evolutionCMS();
        $children = array();
        $currDocID = $modx->getDatabase()->escape($currDocID);
        $rs = $modx->getDatabase()->select('id', $modx->getDatabase()->getFullTableName('site_content'), "parent = '{$currDocID}'");
        while ($child = $modx->getDatabase()->getRow($rs)) {
            $children[] = $child['id'];
            $children = array_merge($children, allChildren($child['id']));
        }

        return $children;
    }
}

if(!function_exists('jsAlert')) {
    /**
     * show javascript alert
     *
     * @param string $msg
     */
    function jsAlert($msg)
    {
        $modx = evolutionCMS();
        if ($_POST['ajax'] != 1) {
            echo "<script>window.setTimeout(\"alert('" . addslashes($modx->getDatabase()->escape($msg)) . "')\",10);history.go(-1)</script>";
        } else {
            echo $msg . "\n";
        }
    }
}

if(!function_exists('login')) {
    /**
     * @param string $username
     * @param string $givenPassword
     * @param string $dbasePassword
     * @return bool
     */
    function login($username, $givenPassword, $dbasePassword)
    {
        $modx = evolutionCMS();

        return $modx->getPasswordHash()->CheckPassword($givenPassword, $dbasePassword);
    }
}

if(!function_exists('loginV1')) {
    /**
     * @param int $internalKey
     * @param string $givenPassword
     * @param string $dbasePassword
     * @param string $username
     * @return bool
     */
    function loginV1($internalKey, $givenPassword, $dbasePassword, $username)
    {
        $modx = evolutionCMS();

        $user_algo = $modx->getManagerApi()->getV1UserHashAlgorithm($internalKey);

        if (!isset($modx->config['pwd_hash_algo']) || empty($modx->config['pwd_hash_algo'])) {
            $modx->config['pwd_hash_algo'] = 'UNCRYPT';
        }

        if ($user_algo !== $modx->config['pwd_hash_algo']) {
            $bk_pwd_hash_algo = $modx->config['pwd_hash_algo'];
            $modx->config['pwd_hash_algo'] = $user_algo;
        }

        if ($dbasePassword != $modx->getManagerApi()->genV1Hash($givenPassword, $internalKey)) {
            return false;
        }

        updateNewHash($username, $givenPassword);

        return true;
    }
}

if(!function_exists('loginMD5')) {
    /**
     * @param int $internalKey
     * @param string $givenPassword
     * @param string $dbasePassword
     * @param string $username
     * @return bool
     */
    function loginMD5($internalKey, $givenPassword, $dbasePassword, $username)
    {
        $modx = evolutionCMS();

        if ($dbasePassword != md5($givenPassword)) {
            return false;
        }
        updateNewHash($username, $givenPassword);

        return true;
    }
}

if(!function_exists('updateNewHash')) {
    /**
     * @param string $username
     * @param string $password
     */
    function updateNewHash($username, $password)
    {
        $modx = evolutionCMS();

        $field = array();
        $field['password'] = $modx->getPasswordHash()->HashPassword($password);
        $modx->getDatabase()->update(
            $field,
            $modx->getDatabase()->getFullTableName('manager_users'),
            "username='{$username}'"
        );
    }
}

if(!function_exists('incrementFailedLoginCount')) {
    /**
     * @param int $internalKey
     * @param int $failedlogins
     * @param int $failed_allowed
     * @param int $blocked_minutes
     */
    function incrementFailedLoginCount($internalKey, $failedlogins, $failed_allowed, $blocked_minutes)
    {
        $modx = evolutionCMS();

        $failedlogins += 1;

        $fields = array('failedlogincount' => $failedlogins);
        if ($failedlogins >= $failed_allowed) //block user for too many fail attempts
        {
            $fields['blockeduntil'] = time() + ($blocked_minutes * 60);
        }

        $modx->getDatabase()->update(
            $fields,
            $modx->getDatabase()->getFullTableName('user_attributes'),
            "internalKey='{$internalKey}'"
        );

        if ($failedlogins < $failed_allowed) {
            //sleep to help prevent brute force attacks
            $sleep = (int)$failedlogins / 2;
            if ($sleep > 5) {
                $sleep = 5;
            }
            sleep($sleep);
        }
        @session_destroy();
        session_unset();

        return;
    }
}

if(!function_exists('saveUserGroupAccessPermissons')) {
    /**
     * saves module user group access
     */
    function saveUserGroupAccessPermissons()
    {
        $modx = evolutionCMS();
        global $id, $newid;
        global $use_udperms;

        if ($newid) {
            $id = $newid;
        }
        $usrgroups = $_POST['usrgroups'];

        // check for permission update access
        if ($use_udperms == 1) {
            // delete old permissions on the module
            $modx->getDatabase()->delete($modx->getDatabase()->getFullTableName("site_module_access"), "module='{$id}'");
            if (is_array($usrgroups)) {
                foreach ($usrgroups as $value) {
                    $modx->getDatabase()->insert(array(
                        'module'    => $id,
                        'usergroup' => stripslashes($value),
                    ), $modx->getDatabase()->getFullTableName('site_module_access'));
                }
            }
        }
    }
}

if(!function_exists('saveEventListeners')) {
# Save Plugin Event Listeners
    function saveEventListeners($id, $sysevents, $mode)
    {
        $modx = evolutionCMS();
        // save selected system events
        $formEventList = array();
        foreach ($sysevents as $evtId) {
            if (!preg_match('@^[1-9][0-9]*$@', $evtId)) {
                $evtId = getEventIdByName($evtId);
            }
            if ($mode == '101') {
                $rs = $modx->getDatabase()->select(
                    'max(priority) as priority',
                    $modx->getDatabase()->getFullTableName('site_plugin_events'),
                    "evtid='{$evtId}'"
                );
            } else {
                $rs = $modx->getDatabase()->select(
                    'priority',
                    $modx->getDatabase()->getFullTableName('site_plugin_events'),
                    "evtid='{$evtId}' and pluginid='{$id}'"
                );
            }
            $prevPriority = $modx->getDatabase()->getValue($rs);
            if ($mode == '101') {
                $priority = isset($prevPriority) ? $prevPriority + 1 : 1;
            } else {
                $priority = isset($prevPriority) ? $prevPriority : 1;
            }
            $formEventList[] = array('pluginid' => $id, 'evtid' => $evtId, 'priority' => $priority);
        }

        $evtids = array();
        foreach ($formEventList as $eventInfo) {
            $where = vsprintf("pluginid='%s' AND evtid='%s'", $eventInfo);
            $modx->getDatabase()->save(
                $eventInfo,
                $modx->getDatabase()->getFullTableName('site_plugin_events'),
                $where
            );
            $evtids[] = $eventInfo['evtid'];
        }

        $rs = $modx->getDatabase()->select(
            '*',
            $modx->getDatabase()->getFullTableName('site_plugin_events'),
            sprintf("pluginid='%s'", $id)
        );
        $dbEventList = array();
        $del = array();
        while ($row = $modx->getDatabase()->getRow($rs)) {
            if (!in_array($row['evtid'], $evtids)) {
                $del[] = $row['evtid'];
            }
        }

        if (empty($del)) {
            return;
        }

        foreach ($del as $delid) {
            $modx->getDatabase()->delete(
                $modx->getDatabase()->getFullTableName('site_plugin_events'),
                sprintf("evtid='%s' AND pluginid='%s'", $delid, $id)
            );
        }
    }
}

if(!function_exists('getEventIdByName')) {
    /**
     * @param string $name
     * @return string|int
     */
    function getEventIdByName($name)
    {
        $modx = evolutionCMS();
        static $eventIds = array();

        if (isset($eventIds[$name])) {
            return $eventIds[$name];
        }

        $rs = $modx->getDatabase()->select(
            'id, name',
            $modx->getDatabase()->getFullTableName('system_eventnames')
        );
        while ($row = $modx->getDatabase()->getRow($rs)) {
            $eventIds[$row['name']] = $row['id'];
        }

        return $eventIds[$name];
    }
}

if(!function_exists('saveTemplateAccess')) {
    /**
     * @param int $id
     */
    function saveTemplateAccess($id)
    {
        $modx = evolutionCMS();
        if ($_POST['tvsDirty'] == 1) {
            $newAssignedTvs = $_POST['assignedTv'];

            // Preserve rankings of already assigned TVs
            $rs = $modx->getDatabase()->select("tmplvarid, rank", $modx->getDatabase()->getFullTableName('site_tmplvar_templates'),
                "templateid='{$id}'", "");

            $ranksArr = array();
            $highest = 0;
            while ($row = $modx->getDatabase()->getRow($rs)) {
                $ranksArr[$row['tmplvarid']] = $row['rank'];
                $highest = $highest < $row['rank'] ? $row['rank'] : $highest;
            };

            $modx->getDatabase()->delete($modx->getDatabase()->getFullTableName('site_tmplvar_templates'), "templateid='{$id}'");
            if (empty($newAssignedTvs)) {
                return;
            }
            foreach ($newAssignedTvs as $tvid) {
                if (!$id || !$tvid) {
                    continue;
                }    // Dont link zeros
                $modx->getDatabase()->insert(array(
                    'templateid' => $id,
                    'tmplvarid'  => $tvid,
                    'rank'       => isset($ranksArr[$tvid]) ? $ranksArr[$tvid] : $highest += 1 // append TVs to rank
                ), $modx->getDatabase()->getFullTableName('site_tmplvar_templates'));
            }
        }
    }
}

if(!function_exists('saveTemplateVarAccess')) {
    /**
     * @return void
     */
    function saveTemplateVarAccess()
    {
        global $id, $newid;
        $modx = evolutionCMS();

        if ($newid) {
            $id = $newid;
        }
        $templates = $_POST['template']; // get muli-templates based on S.BRENNAN mod

        // update template selections
        $tbl_site_tmplvar_templates = $modx->getDatabase()->getFullTableName('site_tmplvar_templates');

        $getRankArray = array();

        $getRank = $modx->getDatabase()->select("templateid, rank", $tbl_site_tmplvar_templates, "tmplvarid='{$id}'");

        while ($row = $modx->getDatabase()->getRow($getRank)) {
            $getRankArray[$row['templateid']] = $row['rank'];
        }

        $modx->getDatabase()->delete($tbl_site_tmplvar_templates, "tmplvarid = '{$id}'");
        if (!empty($templates)) {
            for ($i = 0; $i < count($templates); $i++) {
                $setRank = ($getRankArray[$templates[$i]]) ? $getRankArray[$templates[$i]] : 0;
                $modx->getDatabase()->insert(array(
                    'tmplvarid'  => $id,
                    'templateid' => $templates[$i],
                    'rank'       => $setRank,
                ), $tbl_site_tmplvar_templates);
            }
        }
    }
}

if(!function_exists('saveDocumentAccessPermissons')) {
    function saveDocumentAccessPermissons()
    {
        global $id, $newid;
        $modx = evolutionCMS();
        global $use_udperms;

        $tbl_site_tmplvar_templates = $modx->getDatabase()->getFullTableName('site_tmplvar_access');

        if ($newid) {
            $id = $newid;
        }
        $docgroups = $_POST['docgroups'];

        // check for permission update access
        if ($use_udperms == 1) {
            // delete old permissions on the tv
            $modx->getDatabase()->delete($tbl_site_tmplvar_templates, "tmplvarid='{$id}'");
            if (is_array($docgroups)) {
                foreach ($docgroups as $value) {
                    $modx->getDatabase()->insert(array(
                        'tmplvarid'     => $id,
                        'documentgroup' => stripslashes($value),
                    ), $tbl_site_tmplvar_templates);
                }
            }
        }
    }
}

if (!function_exists('sendMailMessageForUser')) {
    /**
     * Send an email to the user
     *
     * @param string $email
     * @param string $uid
     * @param string $pwd
     * @param string $ufn
     */
    function sendMailMessageForUser($email, $uid, $pwd, $ufn, $message, $url)
    {
        $modx = evolutionCMS();
        global $_lang;
        global $emailsubject, $emailsender;
        global $site_name;
        $message = sprintf($message, $uid, $pwd); // use old method
        // replace placeholders
        $message = str_replace("[+uid+]", $uid, $message);
        $message = str_replace("[+pwd+]", $pwd, $message);
        $message = str_replace("[+ufn+]", $ufn, $message);
        $message = str_replace("[+sname+]", $modx->getPhpCompat()->entities($site_name), $message);
        $message = str_replace("[+saddr+]", $emailsender, $message);
        $message = str_replace("[+semail+]", $emailsender, $message);
        $message = str_replace("[+surl+]", $url, $message);

        $param = array();
        $param['from'] = "{$site_name}<{$emailsender}>";
        $param['subject'] = $emailsubject;
        $param['body'] = $message;
        $param['to'] = $email;
        $param['type'] = 'text';
        $rs = $modx->sendmail($param);
        if (!$rs) {
            $modx->getManagerApi()->saveFormValues();
            $modx->messageQuit("{$email} - {$_lang['error_sending_email']}");
        }
    }
}

if (!function_exists('saveWebUserSettings')) {
// Save User Settings
    function saveWebUserSettings($id)
    {
        $modx = evolutionCMS();
        $tbl_web_user_settings = $modx->getDatabase()->getFullTableName('web_user_settings');

        $settings = array(
            "login_home",
            "allowed_ip",
            "allowed_days"
        );

        $modx->getDatabase()->delete($tbl_web_user_settings, "webuser='{$id}'");

        foreach ($settings as $n) {
            $vl = $_POST[$n];
            if (is_array($vl)) {
                $vl = implode(",", $vl);
            }
            if ($vl != '') {
                $f = array();
                $f['webuser'] = $id;
                $f['setting_name'] = $n;
                $f['setting_value'] = $vl;
                $f = $modx->getDatabase()->escape($f);
                $modx->getDatabase()->insert($f, $tbl_web_user_settings);
            }
        }
    }
}

if (!function_exists('saveManagerUserSettings')) {
    /**
     * Save User Settings
     *
     * @param int $id
     */
    function saveManagerUserSettings($id)
    {
        $modx = evolutionCMS();
        $tbl_user_settings = $modx->getDatabase()->getFullTableName('user_settings');

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

        $modx->getDatabase()->delete($tbl_user_settings, "user='{$id}'");

        foreach ($settings as $n => $vl) {
            if (is_array($vl)) {
                $vl = implode(",", $vl);
            }
            if ($vl != '') {
                $f = array();
                $f['user'] = $id;
                $f['setting_name'] = $n;
                $f['setting_value'] = $vl;
                $f = $modx->getDatabase()->escape($f);
                $modx->getDatabase()->insert($f, $tbl_user_settings);
            }
        }
    }
}

if (!function_exists('webAlertAndQuit')) {
    /**
     * Web alert -  sends an alert to web browser
     *
     * @param $msg
     */
    function webAlertAndQuit($msg, $action)
    {
        global $id, $modx;
        $mode = $_POST['mode'];
        $modx->getManagerApi()->saveFormValues($mode);
        $modx->webAlertAndQuit($msg, "index.php?a={$mode}" . ($mode === $action ? "&id={$id}" : ''));
    }
}

if (!function_exists('getChildrenForUnDelete')) {
    /**
     * @param int $parent
     */
    function getChildrenForUnDelete($parent)
    {

        $modx = evolutionCMS();
        global $children;
        global $deltime;

        $rs = $modx->getDatabase()->select('id', $modx->getDatabase()->getFullTableName('site_content'),
            "parent='" . (int)$parent . "' AND deleted=1 AND deletedon='" . (int)$deltime . "'");
        // the document has children documents, we'll need to delete those too
        while ($row = $modx->getDatabase()->getRow($rs)) {
            $children[] = $row['id'];
            getChildrenForUnDelete($row['id']);
            //echo "Found childNode of parentNode $parent: ".$row['id']."<br />";
        }
    }
}
