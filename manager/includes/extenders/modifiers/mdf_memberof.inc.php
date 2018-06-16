<?php
$userID = abs($modx->getLoginUserID('web'));
$modx->qs_hash = md5($modx->qs_hash."^{$userID}^");

$groupNames = ($this->strlen($opt) > 0 ) ? explode(',',$opt) : array();

// if $groupNames is not an array return false
if(!is_array($groupNames)) return 0;

// Creates an array with all webgroups the user id is in
if (isset($modx->getModifiers()->cache['mo'][$userID])) $grpNames = $modx->getModifiers()->cache['mo'][$userID];
else {
    $from = sprintf(
        $modx->getDatabase()->getFullTableName('webgroup_names') .
        " wgn INNER JOIN " .
        $modx->getDatabase()->getFullTableName('web_groups') .
        " wg ON wg.webgroup=wgn.id AND wg.webuser='%s'",

        $userID
    );
    $rs = $modx->getDatabase()->select('wgn.name',$from);
    $modx->getModifiers()->cache['mo'][$userID] = $grpNames = $modx->getDatabase()->getColumn('name',$rs);
}

// Check if a supplied group matches a webgroup from the array we just created
foreach($groupNames as $k=>$v) {
    if(in_array(trim($v),$grpNames)) return 1;
}

// If we get here the above logic did not find a match, so return false
return 0;
