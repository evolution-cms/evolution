<?php
$userid = (int)$value;
if (!isset(evo()->getModifiers()->cache['ui'][$userid])) {
    if ($userid < 0) $user = evo()->getWebUserInfo(abs($userid));
    else             $user = evo()->getUserInfo($userid);
    evo()->getModifiers()->cache['ui'][$userid] = $user;
} else {
    $user = evo()->getModifiers()->cache['ui'][$userid];
}
$user['name'] = !empty($user['fullname']) ? $user['fullname'] : $user['username'];

return $user[$opt];
