<?php

global $site_sessionname;
$site_sessionname = genEvoSessionName(); // For legacy extras not using startCMSSession

function genEvoSessionName() {
    $_ = crc32(__FILE__);
    $_ = sprintf('%u', $_);
    return 'evo' . base_convert($_,10,36);
}

function startCMSSession(){
    
    global $site_sessionname, $https_port;
    
    session_name($site_sessionname);
    removeInvalidCmsSessionIds($site_sessionname);
    session_start();
    $cookieExpiration= 0;
    $secure = ((isset ($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || $_SERVER['SERVER_PORT'] == $https_port);
    
    if    (isset($_SESSION['mgrValidated'])) $context = 'mgr';
    elseif(isset($_SESSION['webValidated'])) $context = 'web';
    else {
        setcookie($site_sessionname, session_id(), $cookieExpiration, MODX_BASE_URL, null, $secure, true);
        return;
    }
    
    $key = "modx.{$context}.session.cookie.lifetime";
    if (isset($_SESSION[$key]) && is_numeric($_SESSION[$key])) {
        $cookieLifetime= intval($_SESSION[$key]);
        if($cookieLifetime) $cookieExpiration = $_SERVER['REQUEST_TIME']+$cookieLifetime;
    }
    if (!isset($_SESSION['modx.session.created.time'])) {
        $_SESSION['modx.session.created.time'] = $_SERVER['REQUEST_TIME'];
    }
    setcookie($site_sessionname, session_id(), $cookieExpiration, MODX_BASE_URL, null, $secure, true);
}

function removeInvalidCmsSessionFromStorage(&$storage, $session_name) {
    if (isset($storage[$session_name]) && ($storage[$session_name] === '' || $storage[$session_name] === 'deleted'))
    {
    unset($storage[$session_name]);
    }
}

function removeInvalidCmsSessionIds($session_name) {
    // session ids is invalid iff it is empty string
    // storage priorioty can see in PHP source ext/session/session.c
    removeInvalidCmsSessionFromStorage($_COOKIE, $session_name);
    removeInvalidCmsSessionFromStorage($_GET, $session_name);
    removeInvalidCmsSessionFromStorage($_POST, $session_name);
}
