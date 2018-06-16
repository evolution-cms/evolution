<?php
$userid = (int)$value;
if (!isset($modx->getModifiers()->cache['ui'][$userid])) {
    if ($userid < 0) $user = $modx->getWebUserInfo(abs($userid));
    else             $user = $modx->getUserInfo($userid);
    $modx->getModifiers()->cache['ui'][$userid] = $user;
} else {
    $user = $modx->getModifiers()->cache['ui'][$userid];
}
$user['name'] = !empty($user['fullname']) ? $user['fullname'] : $user['username'];

return $user[$opt];
