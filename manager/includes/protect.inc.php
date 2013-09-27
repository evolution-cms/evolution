<?php
/**
 *    Protect against some common security flaws
 */

// php bug 53632 (php 4 <= 4.4.9 and php 5 <= 5.3.4)
if (strstr(str_replace('.','',serialize(array_merge($_GET, $_POST, $_COOKIE))), '22250738585072011')) {
    header('Status: 422 Unprocessable Entity');
    die();
}

// Null is evil
if (isset($_SERVER['QUERY_STRING']) && strpos(urldecode($_SERVER['QUERY_STRING']), chr(0)) !== false)
    die();

// Unregister globals
if (@ ini_get('register_globals')) {
    foreach ($_REQUEST as $key => $value) {
        $$key = null; // This is NOT paranoid because
        unset ($$key); // unset may not work.
    }
}

// sanitize array
if (!function_exists('modx_sanitize_gpc')) {
    function modx_sanitize_gpc(& $target, $count=0) {
    	global $sanitize_seed;
        $brackets = array('[[',']]','[!','!]','[*','*]','[(',')]','{{','}}','[+','+]','[~','~]','[^','^]');
        foreach($brackets as $bracket) {
            $r[] = $sanitize_seed . $bracket['0'] . $sanitize_seed . $bracket['1'] . $sanitize_seed;
        }
        foreach ($target as $key => $value) {
            if (is_array($value)) {
                $count++;
                if(10 < $count) {
                    echo 'too many nested array';
                    exit;
                }
                modx_sanitize_gpc($value, $count);
            }
            else {
                $value = str_replace($brackets,$r,$value);
                $value = preg_replace('/<script/i', 'sanitized_by_modx<s cript', $value);
                $value = preg_replace('/&#(\d+);/', 'sanitized_by_modx& #$1', $value);
                $target[$key] = $value;
            }
            $count=0;
        }
        return $target;
    }
}

global $sanitize_seed;
$sanitize_seed = 'sanitize_seed_' . base_convert(md5(__FILE__),16,36);

modx_sanitize_gpc($_GET);
if (!defined('IN_MANAGER_MODE') || (defined('IN_MANAGER_MODE') && (!IN_MANAGER_MODE || IN_MANAGER_MODE == 'false'))) {
    modx_sanitize_gpc($_POST);
}
modx_sanitize_gpc($_COOKIE);
modx_sanitize_gpc($_REQUEST);

foreach (array ('PHP_SELF', 'HTTP_USER_AGENT', 'HTTP_REFERER', 'QUERY_STRING') as $key) {
    $_SERVER[$key] = isset ($_SERVER[$key]) ? htmlspecialchars($_SERVER[$key], ENT_QUOTES) : null;
}

// Unset vars
unset ($key, $value);
