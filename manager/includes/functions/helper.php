<?php

if(!function_exists('createGUID')) {
    /**
     * create globally unique identifiers (guid)
     *
     * @return string
     */
    function createGUID()
    {
        srand((double)microtime() * 1000000);
        $r = rand();
        $u = uniqid(getmypid() . $r . (double)microtime() * 1000000, 1);
        $m = md5($u);

        return $m;
    }
}

if(!function_exists('generate_password')) {
// Generate password
    function generate_password($length = 10)
    {
        $allowable_characters = "abcdefghjkmnpqrstuvxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789";
        $ps_len = strlen($allowable_characters);
        mt_srand((double)microtime() * 1000000);
        $pass = "";
        for ($i = 0; $i < $length; $i++) {
            $pass .= $allowable_characters[mt_rand(0, $ps_len - 1)];
        }

        return $pass;
    }
}

if (! function_exists('entities')) {
    /**
     * @param  string $string
     * @param  string $charset
     * @return mixed
     */
    function entities($string, $charset = 'UTF-8')
    {
        return htmlentities($string, ENT_COMPAT | ENT_SUBSTITUTE, $charset, false);
    }
}

if (! function_exists('get_by_key')) {
    /**
     * @param mixed $data
     * @param string|int $key
     * @param mixed $default
     * @param Closure $validate
     * @return mixed
     */
    function get_by_key($data, $key, $default = null, $validate = null)
    {
        $out = $default;
        if (is_array($data) && (is_int($key) || is_string($key)) && $key !== '' && array_key_exists($key, $data)) {
            $out = $data[$key];
        }
        if (! empty($validate) && is_callable($validate)) {
            $out = (($validate($out) === true) ? $out : $default);
        }
        return $out;
    }
}
