<?php
if(!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	header('HTTP/1.0 404 Not Found');
	exit('error');
}
define('IN_MANAGER_MODE', true);  // we use this to make sure files are accessed through
define('MODX_API_MODE', true);
include_once(__DIR__ . '/../../index.php');
$modx->getDatabase()->connect();
$modx->getSettings();
$modx->invokeEvent('OnManagerPageInit');

$core_path = MODX_MANAGER_PATH . 'includes/';
// include_once the language file
$_lang = array();
include_once("{$core_path}lang/english.inc.php");

if($manager_language !== 'english' && is_file("{$core_path}lang/{$manager_language}.inc.php")) {
	include_once("{$core_path}lang/{$manager_language}.inc.php");
}

// Initialize System Alert Message Queque
if(!isset($_SESSION['SystemAlertMsgQueque'])) {
	$_SESSION['SystemAlertMsgQueque'] = array();
}
$SystemAlertMsgQueque = &$_SESSION['SystemAlertMsgQueque'];

// initiate the content manager class
// for backward compatibility

$username = $modx->getDatabase()->escape($modx->getPhpCompat()->htmlspecialchars($_REQUEST['username'], ENT_NOQUOTES));
$givenPassword = $modx->getPhpCompat()->htmlspecialchars($_REQUEST['password'], ENT_NOQUOTES);
$captcha_code = $_REQUEST['captcha_code'];
$rememberme = $_REQUEST['rememberme'];
$failed_allowed = $modx->config['failed_login_attempts'];

// invoke OnBeforeManagerLogin event
$modx->invokeEvent('OnBeforeManagerLogin', array(
		'username' => $username,
		'userpassword' => $givenPassword,
		'rememberme' => $rememberme
	));
$fields = 'mu.*, ua.*';
$from = $modx->getDatabase()->getFullTableName('manager_users') . ' AS mu, ' .
    $modx->getDatabase()->getFullTableName('user_attributes') . ' AS ua';
$where = "BINARY mu.username='{$username}' and ua.internalKey=mu.id";
$rs = $modx->getDatabase()->select($fields, $from, $where);
$limit = $modx->getDatabase()->getRecordCount($rs);

if($limit == 0 || $limit > 1) {
	jsAlert($_lang['login_processor_unknown_user']);
	return;
}

$row = $modx->getDatabase()->getRow($rs);

$internalKey = $row['internalKey'];
$dbasePassword = $row['password'];
$failedlogins = $row['failedlogincount'];
$blocked = $row['blocked'];
$blockeduntildate = $row['blockeduntil'];
$blockedafterdate = $row['blockedafter'];
$registeredsessionid = $row['sessionid'];
$role = $row['role'];
$lastlogin = $row['lastlogin'];
$nrlogins = $row['logincount'];
$fullname = $row['fullname'];
$email = $row['email'];

// get the user settings from the database
$rs = $modx->getDatabase()->select(
    'setting_name, setting_value',
    $modx->getDatabase()->getFullTableName('user_settings'),
    "user='{$internalKey}' AND setting_value!=''"
);
while($row = $modx->getDatabase()->getRow($rs)) {
	extract($row);
	${$setting_name} = $setting_value;
}

// blocked due to number of login errors.
if($failedlogins >= $failed_allowed && $blockeduntildate > time()) {
	@session_destroy();
	session_unset();
	if($cip = getenv("HTTP_CLIENT_IP")) {
		$ip = $cip;
	} elseif($cip = getenv("HTTP_X_FORWARDED_FOR")) {
		$ip = $cip;
	} elseif($cip = getenv("REMOTE_ADDR")) {
		$ip = $cip;
	} else {
		$ip = "UNKNOWN";
	}
	$log = new EvolutionCMS\Legacy\LogHandler();
	$log->initAndWriteLog("Login Fail (Temporary Block)", $internalKey, $username, "119", $internalKey, "IP: " . $ip);
	jsAlert($_lang['login_processor_many_failed_logins']);
	return;
}

// blocked due to number of login errors, but get to try again
if($failedlogins >= $failed_allowed && $blockeduntildate < time()) {
	$fields = array();
	$fields['failedlogincount'] = '0';
	$fields['blockeduntil'] = time() - 1;
	$modx->getDatabase()->update(
	    $fields,
        $modx->getDatabase()->getFullTableName('user_attributes'),
        "internalKey='{$internalKey}'"
    );
}

// this user has been blocked by an admin, so no way he's loggin in!
if($blocked == '1') {
	@session_destroy();
	session_unset();
	jsAlert($_lang['login_processor_blocked1']);
	return;
}

// blockuntil: this user has a block until date
if($blockeduntildate > time()) {
	@session_destroy();
	session_unset();
	jsAlert($_lang['login_processor_blocked2']);
	return;
}

// blockafter: this user has a block after date
if($blockedafterdate > 0 && $blockedafterdate < time()) {
	@session_destroy();
	session_unset();
	jsAlert($_lang['login_processor_blocked3']);
	return;
}

// allowed ip
if($allowed_ip) {
	if(($hostname = gethostbyaddr($_SERVER['REMOTE_ADDR'])) && ($hostname != $_SERVER['REMOTE_ADDR'])) {
		if(gethostbyname($hostname) != $_SERVER['REMOTE_ADDR']) {
			jsAlert($_lang['login_processor_remotehost_ip']);
			return;
		}
	}
	if(!in_array($_SERVER['REMOTE_ADDR'], array_filter(array_map('trim', explode(',', $allowed_ip))))) {
		jsAlert($_lang['login_processor_remote_ip']);
		return;
	}
}

// allowed days
if($allowed_days) {
	$date = getdate();
	$day = $date['wday'] + 1;
	if(!in_array($day,explode(',',$allowed_days))) {
		jsAlert($_lang['login_processor_date']);
		return;
	}
}

// invoke OnManagerAuthentication event
$rt = $modx->invokeEvent('OnManagerAuthentication', array(
		'userid' => $internalKey,
		'username' => $username,
		'userpassword' => $givenPassword,
		'savedpassword' => $dbasePassword,
		'rememberme' => $rememberme
	));

// check if plugin authenticated the user
$matchPassword = false;
if(!isset($rt) || !$rt || (is_array($rt) && !in_array(true, $rt))) {
	// check user password - local authentication
	$hashType = $modx->getManagerApi()->getHashType($dbasePassword);
	if($hashType == 'phpass') {
		$matchPassword = login($username, $_REQUEST['password'], $dbasePassword);
	} elseif($hashType == 'md5') {
		$matchPassword = loginMD5($internalKey, $_REQUEST['password'], $dbasePassword, $username);
	} elseif($hashType == 'v1') {
		$matchPassword = loginV1($internalKey, $_REQUEST['password'], $dbasePassword, $username);
	} else {
		$matchPassword = false;
	}
} else if($rt === true || (is_array($rt) && in_array(true, $rt))) {
	$matchPassword = true;
}

if(!$matchPassword) {
	jsAlert($_lang['login_processor_wrong_password']);
	incrementFailedLoginCount($internalKey, $failedlogins, $failed_allowed, $blocked_minutes);
	return;
}

if($modx->config['use_captcha'] == 1) {
	if(!isset ($_SESSION['veriword'])) {
		jsAlert($_lang['login_processor_captcha_config']);
		return;
	} elseif($_SESSION['veriword'] != $captcha_code) {
		jsAlert($_lang['login_processor_bad_code']);
		incrementFailedLoginCount($internalKey, $failedlogins, $failed_allowed, $blocked_minutes);
		return;
	}
}

$modx->cleanupExpiredLocks();
$modx->cleanupMultipleActiveUsers();

$currentsessionid = session_id();

$_SESSION['usertype'] = 'manager'; // user is a backend user

// get permissions
$_SESSION['mgrShortname'] = $username;
$_SESSION['mgrFullname'] = $fullname;
$_SESSION['mgrEmail'] = $email;
$_SESSION['mgrValidated'] = 1;
$_SESSION['mgrInternalKey'] = $internalKey;
$_SESSION['mgrFailedlogins'] = $failedlogins;
$_SESSION['mgrLastlogin'] = $lastlogin;
$_SESSION['mgrLogincount'] = $nrlogins; // login count
$_SESSION['mgrRole'] = $role;
$rs = $modx->getDatabase()->select('*', $modx->getDatabase()->getFullTableName('user_roles'), "id='{$role}'");
$_SESSION['mgrPermissions'] = $modx->getDatabase()->getRow($rs);

// successful login so reset fail count and update key values
$modx->getDatabase()->update(
    'failedlogincount=0, ' . 'logincount=logincount+1, ' . 'lastlogin=thislogin, ' . 'thislogin=' . time() . ', ' . "sessionid='{$currentsessionid}'",
    $modx->getDatabase()->getFullTableName('user_attributes'),
    "internalKey='{$internalKey}'"
);

// get user's document groups
$i = 0;
$rs = $modx->getDatabase()->select('uga.documentgroup', $modx->getDatabase()->getFullTableName('member_groups') . ' ug
		INNER JOIN ' . $modx->getDatabase()->getFullTableName('membergroup_access') . ' uga ON uga.membergroup=ug.user_group', "ug.member='{$internalKey}'");
$_SESSION['mgrDocgroups'] = $modx->getDatabase()->getColumn('documentgroup', $rs);

$_SESSION['mgrToken'] = md5($currentsessionid);

if($rememberme == '1') {
	$_SESSION['modx.mgr.session.cookie.lifetime'] = (int)$modx->config['session.cookie.lifetime'];

	// Set a cookie separate from the session cookie with the username in it.
	// Are we using secure connection? If so, make sure the cookie is secure
	global $https_port;

	$secure = ((isset ($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || $_SERVER['SERVER_PORT'] == $https_port);
	if(version_compare(PHP_VERSION, '5.2', '<')) {
		setcookie('modx_remember_manager', $_SESSION['mgrShortname'], time() + 60 * 60 * 24 * 365, MODX_BASE_URL, '; HttpOnly', $secure);
	} else {
		setcookie('modx_remember_manager', $_SESSION['mgrShortname'], time() + 60 * 60 * 24 * 365, MODX_BASE_URL, NULL, $secure, true);
	}
} else {
	$_SESSION['modx.mgr.session.cookie.lifetime'] = 0;

	// Remove the Remember Me cookie
	setcookie('modx_remember_manager', '', time() - 3600, MODX_BASE_URL);
}

// Check if user already has an active session, if not check if user pressed logout end of last session
$rs = $modx->getDatabase()->select('lasthit', $modx->getDatabase()->getFullTableName('active_user_sessions'), "internalKey='{$internalKey}'");
$activeSession = $modx->getDatabase()->getValue($rs);
if(!$activeSession) {
	$rs = $modx->getDatabase()->select('lasthit', $modx->getDatabase()->getFullTableName('active_users'), "internalKey='{$internalKey}' AND action != 8");
	if($lastHit = $modx->getDatabase()->getValue($rs)) {
		$_SESSION['show_logout_reminder'] = array(
			'type' => 'logout_reminder',
			'lastHit' => $lastHit
		);
	}
}

$log = new EvolutionCMS\Legacy\LogHandler();
$log->initAndWriteLog('Logged in', $modx->getLoginUserID(), $_SESSION['mgrShortname'], '58', '-', 'MODX');

// invoke OnManagerLogin event
$modx->invokeEvent('OnManagerLogin', array(
		'userid' => $internalKey,
		'username' => $username,
		'userpassword' => $givenPassword,
		'rememberme' => $rememberme
	));

// check if we should redirect user to a web page
$rs = $modx->getDatabase()->select(
    'setting_value',
    $modx->getDatabase()->getFullTableName('user_settings'),
    "user='{$internalKey}' AND setting_name='manager_login_startup'"
);
$id = (int)$modx->getDatabase()->getValue($rs);
if($id > 0) {
	$header = 'Location: ' . $modx->makeUrl($id, '', '', 'full');
	if($_POST['ajax'] == 1) {
		echo $header;
	} else {
		header($header);
	}
} else {
	$header = 'Location: ' . MODX_MANAGER_URL;
	if($_POST['ajax'] == 1) {
		echo $header;
	} else {
		header($header);
	}
}
