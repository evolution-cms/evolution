<?php
if(!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	header('HTTP/1.0 404 Not Found');
	exit('error');
}
define('IN_MANAGER_MODE', true);  // we use this to make sure files are accessed through
define('MODX_API_MODE', true);
include_once(__DIR__ . '/../../index.php');
$modx->db->connect();
$modx->getSettings();
$modx->invokeEvent('OnManagerPageInit');
$modx->loadExtension('ManagerAPI');
$modx->loadExtension('phpass');

$core_path = MODX_MANAGER_PATH . 'includes/';

extract($modx->config, EXTR_OVERWRITE);

// include_once the language file
$_lang = array();
include_once("{$core_path}lang/english.inc.php");

if($manager_language !== 'english' && is_file("{$core_path}lang/{$manager_language}.inc.php")) {
	include_once("{$core_path}lang/{$manager_language}.inc.php");
}
// allow custom language overrides not altered by future EVO-updates
if(is_file("{$core_path}lang/override/{$manager_language}.inc.php")) {
	include_once("{$core_path}lang/override/{$manager_language}.inc.php");
}
// include the logger
include_once("{$core_path}log.class.inc.php");

// Initialize System Alert Message Queque
if(!isset($_SESSION['SystemAlertMsgQueque'])) {
	$_SESSION['SystemAlertMsgQueque'] = array();
}
$SystemAlertMsgQueque = &$_SESSION['SystemAlertMsgQueque'];

// initiate the content manager class
// for backward compatibility

$username = $modx->db->escape($modx->htmlspecialchars($_REQUEST['username'], ENT_NOQUOTES));
$givenPassword = $modx->htmlspecialchars($_REQUEST['password'], ENT_NOQUOTES);
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
$from = '[+prefix+]manager_users AS mu, [+prefix+]user_attributes AS ua';
$where = "BINARY mu.username='{$username}' and ua.internalKey=mu.id";
$rs = $modx->db->select($fields, $from, $where);
$limit = $modx->db->getRecordCount($rs);

if($limit == 0 || $limit > 1) {
	jsAlert($_lang['login_processor_unknown_user']);
	return;
}

$row = $modx->db->getRow($rs);

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
$rs = $modx->db->select('setting_name, setting_value', '[+prefix+]user_settings', "user='{$internalKey}' AND setting_value!=''");
while($row = $modx->db->getRow($rs)) {
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
	$log = new logHandler;
	$log->initAndWriteLog("Login Fail (Temporary Block)", $internalKey, $username, "119", $internalKey, "IP: " . $ip);
	jsAlert($_lang['login_processor_many_failed_logins']);
	return;
}

// blocked due to number of login errors, but get to try again
if($failedlogins >= $failed_allowed && $blockeduntildate < time()) {
	$fields = array();
	$fields['failedlogincount'] = '0';
	$fields['blockeduntil'] = time() - 1;
	$modx->db->update($fields, '[+prefix+]user_attributes', "internalKey='{$internalKey}'");
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
	$hashType = $modx->manager->getHashType($dbasePassword);
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

$blocked_minutes = (int)$modx->config['blocked_minutes'];

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
$rs = $modx->db->select('*', $modx->getFullTableName('user_roles'), "id='{$role}'");
$_SESSION['mgrPermissions'] = $modx->db->getRow($rs);

// successful login so reset fail count and update key values
$modx->db->update('failedlogincount=0, ' . 'logincount=logincount+1, ' . 'lastlogin=thislogin, ' . 'thislogin=' . time() . ', ' . "sessionid='{$currentsessionid}'", '[+prefix+]user_attributes', "internalKey='{$internalKey}'");

// get user's document groups
$i = 0;
$rs = $modx->db->select('uga.documentgroup', $modx->getFullTableName('member_groups') . ' ug
		INNER JOIN ' . $modx->getFullTableName('membergroup_access') . ' uga ON uga.membergroup=ug.user_group', "ug.member='{$internalKey}'");
$_SESSION['mgrDocgroups'] = $modx->db->getColumn('documentgroup', $rs);

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
$rs = $modx->db->select('lasthit', $modx->getFullTableName('active_user_sessions'), "internalKey='{$internalKey}'");
$activeSession = $modx->db->getValue($rs);
if(!$activeSession) {
	$rs = $modx->db->select('lasthit', $modx->getFullTableName('active_users'), "internalKey='{$internalKey}' AND action != 8");
	if($lastHit = $modx->db->getValue($rs)) {
		$_SESSION['show_logout_reminder'] = array(
			'type' => 'logout_reminder',
			'lastHit' => $lastHit
		);
	}
}

$log = new logHandler;
$log->initAndWriteLog('Logged in', $modx->getLoginUserID(), $_SESSION['mgrShortname'], '58', '-', 'MODX');

// invoke OnManagerLogin event
$modx->invokeEvent('OnManagerLogin', array(
		'userid' => $internalKey,
		'username' => $username,
		'userpassword' => $givenPassword,
		'rememberme' => $rememberme
	));

// check if we should redirect user to a web page
$rs = $modx->db->select('setting_value', '[+prefix+]user_settings', "user='{$internalKey}' AND setting_name='manager_login_startup'");
$id = (int)$modx->db->getValue($rs);
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

/**
 * show javascript alert
 *
 * @param string $msg
 */
function jsAlert($msg) {
	$modx = evolutionCMS();
	if($_POST['ajax'] != 1) {
		echo "<script>window.setTimeout(\"alert('" . addslashes($modx->db->escape($msg)) . "')\",10);history.go(-1)</script>";
	} else {
		echo $msg . "\n";
	}
}

/**
 * @param string $username
 * @param string $givenPassword
 * @param string $dbasePassword
 * @return bool
 */
function login($username, $givenPassword, $dbasePassword) {
	$modx = evolutionCMS();
	return $modx->phpass->CheckPassword($givenPassword, $dbasePassword);
}

/**
 * @param int $internalKey
 * @param string $givenPassword
 * @param string $dbasePassword
 * @param string $username
 * @return bool
 */
function loginV1($internalKey, $givenPassword, $dbasePassword, $username) {
	$modx = evolutionCMS();

	$user_algo = $modx->manager->getV1UserHashAlgorithm($internalKey);

	if(!isset($modx->config['pwd_hash_algo']) || empty($modx->config['pwd_hash_algo'])) {
		$modx->config['pwd_hash_algo'] = 'UNCRYPT';
	}

	if($user_algo !== $modx->config['pwd_hash_algo']) {
		$bk_pwd_hash_algo = $modx->config['pwd_hash_algo'];
		$modx->config['pwd_hash_algo'] = $user_algo;
	}

	if($dbasePassword != $modx->manager->genV1Hash($givenPassword, $internalKey)) {
		return false;
	}

	updateNewHash($username, $givenPassword);

	return true;
}

/**
 * @param int $internalKey
 * @param string $givenPassword
 * @param string $dbasePassword
 * @param string $username
 * @return bool
 */
function loginMD5($internalKey, $givenPassword, $dbasePassword, $username) {
	$modx = evolutionCMS();

	if($dbasePassword != md5($givenPassword)) {
		return false;
	}
	updateNewHash($username, $givenPassword);
	return true;
}

/**
 * @param string $username
 * @param string $password
 */
function updateNewHash($username, $password) {
	$modx = evolutionCMS();

	$field = array();
	$field['password'] = $modx->phpass->HashPassword($password);
	$modx->db->update($field, '[+prefix+]manager_users', "username='{$username}'");
}

/**
 * @param int $internalKey
 * @param int $failedlogins
 * @param int $failed_allowed
 * @param int $blocked_minutes
 */
function incrementFailedLoginCount($internalKey, $failedlogins, $failed_allowed, $blocked_minutes) {
	$modx = evolutionCMS();

	$failedlogins += 1;

	$fields = array('failedlogincount' => $failedlogins);
	if($failedlogins >= $failed_allowed) //block user for too many fail attempts
	{
		$fields['blockeduntil'] = time() + ($blocked_minutes * 60);
	}

	$modx->db->update($fields, '[+prefix+]user_attributes', "internalKey='{$internalKey}'");

	if($failedlogins < $failed_allowed) {
		//sleep to help prevent brute force attacks
		$sleep = (int) $failedlogins / 2;
		if($sleep > 5) {
			$sleep = 5;
		}
		sleep($sleep);
	}
	@session_destroy();
	session_unset();
	return;
}
