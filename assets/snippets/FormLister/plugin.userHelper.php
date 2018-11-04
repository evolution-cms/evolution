<?php
/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 25.06.2016
 * Time: 18:59
 */
$e = $modx->event;
include_once(MODX_BASE_PATH . 'assets/lib/MODxAPI/modUsers.php');
if ($e->name == 'OnWebAuthentication' && isset($userObj)) {
    /**
     * @var modUsers $userObj
     */
    if ($savedpassword != $userObj->getPassword($userpassword)) {
        $fails = (int)$userObj->get('failedlogincount');
        $userObj->set('failedlogincount', ++$fails);
        if ($fails > $maxFails) {
            $userObj->set('blockeduntil', time() + $blockTime);
            $userObj->set('failedlogincount', 0);
        }
        $userObj->save();
    }
}
if ($e->name == 'OnWebLogin' && isset($userObj)) {
    if (!$userObj->get('lastlogin')) {
        $userObj->set('lastlogin', time());
    } else {
        $userObj->set('lastlogin', $userObj->get('thislogin'));
    }
    $userObj->set('thislogin', time());
    $userObj->set('logincount', (int)$userObj->get('logincount') + 1);
    $userObj->set('failedlogincount', 0);
    $userObj->save(false, false);
    if (isset($_COOKIE[$cookieName])) {
        $userObj->setAutoLoginCookie($cookieName, $cookieLifetime);
    }
}
if ($e->name == 'OnWebPageInit' || $e->name == 'OnPageNotFound') {
    $user = new \modUsers($modx);
    if ($uid = $modx->getLoginUserID('web')) {
        if ($trackWebUserActivity == 'Yes') {
            $sid = $modx->sid = session_id();
            $pageId = (int)$modx->documentIdentifier;
            $q = $modx->db->query("REPLACE INTO {$modx->getFullTableName('active_users')} (`sid`, `internalKey`, `username`, `lasthit`, `action`, `id`) values('{$sid}',-{$uid}, '{$_SESSION['webShortname']}', '{$modx->time}', 998, {$pageId})");
            $modx->updateValidatedUserSession();
        }
        if (isset($_REQUEST[$logoutKey])) {
            $user->logOut($cookieName, true);
            $page = $modx->config['site_url'] . (isset($_REQUEST['q']) ? $_REQUEST['q'] : '');
            $query = $_GET;
            unset($query[$logoutKey], $query['q']);
            if ($query) {
                $page . '?' . http_build_query($query);
            }
            $modx->sendRedirect($page);
        } elseif (!$user->edit($uid)->getID() || $user->checkBlock($uid)) {
            $user->logOut($cookieName, true);
        }
    } else {
        $user->AutoLogin($cookieLifetime, $cookieName, true);
    }
}
