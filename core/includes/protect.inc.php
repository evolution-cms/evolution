<?php
/**
 *    Protect against some common security flaws
 */

// Null is evil
if (isset($_SERVER['QUERY_STRING']) && strpos(urldecode($_SERVER['QUERY_STRING']), chr(0)) !== false) {
    evo_exit();
}

modx_sanitize_gpc($_GET);
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    modx_sanitize_gpc($_POST);
}
modx_sanitize_gpc($_COOKIE);
modx_sanitize_gpc($_REQUEST);

$safeKeys = ['PHP_SELF', 'HTTP_USER_AGENT', 'HTTP_REFERER', 'QUERY_STRING'];
foreach ($safeKeys as $key) {
    $_SERVER[$key] = isset($_SERVER[$key]) ? htmlspecialchars($_SERVER[$key], ENT_QUOTES) : null;
}
unset($key);
