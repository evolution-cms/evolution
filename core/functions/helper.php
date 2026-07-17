<?php

if (!function_exists('createGUID')) {
    /**
     * create globally unique identifiers (guid)
     *
     * @return string
     */
    function createGUID()
    {
        mt_srand((float)microtime() * 1000000);
        $r = mt_rand();
        $u = uniqid(getmypid() . $r . (float)microtime() * 1000000, 1);
        return md5($u);
    }
}

if (!function_exists('generate_password')) {
    /**
     * Generate password
     *
     * @param int $length
     * @return string
     */
    function generate_password($length = 10)
    {
        $allowable_characters = 'abcdefghjkmnpqrstuvxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $ps_len = strlen($allowable_characters);
        mt_srand((float)microtime() * 1000000);
        $pass = "";
        for ($i = 0; $i < $length; $i++) {
            $pass .= $allowable_characters[mt_rand(0, $ps_len - 1)];
        }

        return $pass;
    }
}

if (!function_exists('entities')) {
    /**
     * @param string $string
     * @param string $charset
     * @return string
     */
    function entities($string, $charset = 'UTF-8')
    {
        return htmlentities($string, ENT_COMPAT | ENT_SUBSTITUTE, $charset, false);
    }
}

if (!function_exists('html_escape')) {
    /**
     * @param $str
     * @param string $charset
     * @return string
     * @deprecated use entities()
     */
    function html_escape($str, $charset = 'UTF-8')
    {
        return entities($str, $charset);
    }
}

if (!function_exists('get_by_key')) {
    /**
     * @param mixed $data
     * @param string|int $key
     * @param mixed $default
     * @param string|Closure $validate
     * @return mixed
     */
    function get_by_key($data, $key, $default = null, $validate = null)
    {
        $out = $default;
        $found = false;
        if (\is_array($data) && (\is_int($key) || \is_string($key)) && $key !== '') {
            if (\array_key_exists($key, $data)) {
                $out = $data[$key];
                $found = true;
            } else {
                $offset = 0;
                do {
                    if (($pos = \mb_strpos($key, '.', $offset)) > 0) {
                        $subData = get_by_key($data, \mb_substr($key, 0, $pos));
                        $offset = $pos + 1;
                        $subKey = mb_substr($key, $offset);
                        if (\is_array($subData) && array_key_exists($subKey, $subData)) {
                            $out = $subData[$subKey];
                            $found = true;
                            break;
                        }
                    } else {
                        break;
                    }
                } while (true);

                if ($found === false && ($pos = \mb_strpos($key, '.', $offset)) > 0) {
                    $subData = get_by_key($data, \mb_substr($key, 0, $pos));
                    $out = get_by_key($subData, \mb_substr($key, $pos + 1), $default, $validate);
                }
            }
        }

        if ($found && $validate && \is_callable($validate)) {
            if ($validate($out) === true) {
                return $out;
            }
            return $default;
        }

        return $out;
    }
}

if (!function_exists('is_cli')) {
    function is_cli()
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }
}

if (!function_exists('niceCount')) {
    /**
     * Format a quantity using compact SI suffixes.
     *
     * Values below one thousand are returned without a suffix. Larger values
     * are scaled through K, M, B, T, and Q while insignificant trailing zeroes
     * are removed. This keeps counters compact without changing their numeric
     * source value.
     *
     * @param int|float $count Quantity to format
     * @param int $precision Maximum decimal places for a scaled value
     * @return string Compact human-readable quantity
     * @since 3.5.7
     *
     * @example
     * niceCount(999); // "999"
     * niceCount(1500); // "1.5K"
     * niceCount(5000000); // "5M"
     */
    function niceCount(int|float $count, int $precision = 1): string
    {
        $value = (float)$count;
        if (!is_finite($value)) {
            return '0';
        }

        $precision = max(0, min(3, $precision));
        $units = ['', 'K', 'M', 'B', 'T', 'Q'];
        $unitIndex = 0;

        while (abs($value) >= 1000 && $unitIndex < count($units) - 1) {
            $value /= 1000;
            $unitIndex++;
        }

        if ($unitIndex === 0) {
            $formatted = number_format($value, $precision, '.', '');

            return $precision > 0 ? rtrim(rtrim($formatted, '0'), '.') : $formatted;
        }

        $decimals = $precision;
        if (abs(round($value, $decimals)) >= 1000 && $unitIndex < count($units) - 1) {
            $value /= 1000;
            $unitIndex++;
        }

        $formatted = number_format($value, $decimals, '.', '');
        if ($decimals > 0) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $formatted . $units[$unitIndex];
    }
}

if (!function_exists('niceCount')) {
    /**
     * Format a quantity using compact SI suffixes.
     *
     * Values below one thousand are returned without a suffix. Larger values
     * are scaled through K, M, B, T, and Q while insignificant trailing zeroes
     * are removed. This keeps counters compact without changing their numeric
     * source value.
     *
     * @param int|float $count Quantity to format
     * @param int $precision Maximum decimal places for a scaled value
     * @return string Compact human-readable quantity
     * @since 3.6.0
     *
     * @example
     * niceCount(999); // "999"
     * niceCount(1500); // "1.5K"
     * niceCount(5000000); // "5M"
     */
    function niceCount(int|float $count, int $precision = 1): string
    {
        $value = (float)$count;
        if (!is_finite($value)) {
            return '0';
        }

        $precision = max(0, min(3, $precision));
        $units = ['', 'K', 'M', 'B', 'T', 'Q'];
        $unitIndex = 0;

        while (abs($value) >= 1000 && $unitIndex < count($units) - 1) {
            $value /= 1000;
            $unitIndex++;
        }

        if ($unitIndex === 0) {
            $formatted = number_format($value, $precision, '.', '');

            return $precision > 0 ? rtrim(rtrim($formatted, '0'), '.') : $formatted;
        }

        $decimals = $precision;
        if (abs(round($value, $decimals)) >= 1000 && $unitIndex < count($units) - 1) {
            $value /= 1000;
            $unitIndex++;
        }

        $formatted = number_format($value, $decimals, '.', '');
        if ($decimals > 0) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $formatted . $units[$unitIndex];
    }
}

if (!function_exists('niceEta')) {
    /**
     * Format ETA seconds into human-readable format.
     *
     * Converts seconds into a user-friendly time format:
     * - Less than 60 seconds: "45s"
     * - Less than 1 hour: "5m 30s"
     * - 1 hour or more: "2h 15m"
     *
     * @param float $seconds Number of seconds to format
     * @return string Human-readable time format
     *
     * @example
     * niceEta(45.5);     // "46s"
     * niceEta(150);      // "2m 30s"
     * niceEta(3600);     // "1h 0m"
     * niceEta(8100);     // "2h 15m"
     */
    function niceEta(float $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%.0fs', $seconds);
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return sprintf('%.0fm %.0fs', $minutes, $remainingSeconds);
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return sprintf('%.0fh %.0fm', $hours, $minutes);
        }
    }
}

if (!function_exists('niceSize')) {
    /**
     * Format file size in human-readable format.
     *
     * Converts bytes to appropriate unit (B, KB, MB, GB, TB) with proper rounding.
     * Uses modern ISO/IEC 80000 standard formatting with uppercase units.
     *
     * @param int|float $size File size in bytes
     * @return string Formatted file size with unit
     *
     * @example
     * niceSize(1024); // "1 KB"
     * niceSize(1048576); // "1 MB"
     * niceSize(1536); // "1.5 KB"
     * niceSize(0); // "0 B"
     */
    function niceSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }
}

if (!function_exists('data_is_json')) {
    /**
     * @param $string
     * @param bool $returnData
     * @return bool|mixed
     */
    function data_is_json($string, $returnData = false)
    {
        $json = json_decode($string ?? '', true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return false;
        }

        if (!$returnData) {
            return true;
        }

        if (is_scalar($string)) {
            return $json;
        }
        return false;
    }
}

if (!function_exists('js_json')) {
    /**
     * Encode data for direct embedding into JavaScript.
     *
     * Uses JSON output instead of HTML escaping so translated strings keep
     * their literal apostrophes and quotes inside JS payloads.
     *
     * @param mixed $value
     * @param int $options
     * @return string
     */
    function js_json($value, int $options = 0): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | $options);

        return $json === false ? 'null' : $json;
    }
}

if (!function_exists('is_ajax')) {
    /**
     * @return bool
     */
    function is_ajax()
    {
        return (strtolower(get_by_key($_SERVER, 'HTTP_X_REQUESTED_WITH', '')) === 'xmlhttprequest');
    }
}

if (!function_exists('rename_key_arr')) {
    /**
     * Renaming array elements
     *
     * @param array $data
     * @param string $prefix
     * @param string $suffix
     * @param string $addPS separator prefix/suffix and array keys
     * @param string $sep flatten an multidimensional array and combine keys with separator
     * @return array
     */
    function rename_key_arr($data, $prefix = '', $suffix = '', $addPS = '.', $sep = '.')
    {
        if ($prefix === '' && $suffix === '') {
            return $data;
        }

        $InsertPrefix = ($prefix !== '') ? $prefix . $addPS : '';
        $InsertSuffix = ($suffix !== '') ? $addPS . $suffix : '';
        $out = [];
        foreach ($data as $key => $item) {
            $key = $InsertPrefix . $key;
            $val = null;
            switch (true) {
                case is_scalar($item):
                    $val = $item;
                    break;
                case is_array($item):
                    $val = rename_key_arr($item, $key . $sep, $InsertSuffix, '', $sep);
                    $out = array_merge($out, $val);
                    $val = '';
                    break;
            }
            $out[$key . $InsertSuffix] = $val;
        }

        return $out;
    }
}

if (!function_exists('replace_array')) {
    /**
     * @param $data
     * @param array $chars
     * @param bool $withKey
     * @return array|mixed|string
     */
    function replace_array(
        $data,
        array $chars = [
            '[' => '&#91;', ']' => '&#93;',
            '{' => '&#123;', '}' => '&#125;',
            '`' => '&#96;',
        ],
        $withKey = true
    )
    {
        switch (true) {
            case is_scalar($data):
                $out = str_replace(array_keys($chars), array_values($chars), $data);
                break;
            case is_array($data):
                $out = [];
                foreach ($data as $key => $val) {
                    $key = $withKey ? replace_array($key, $chars) : $key;
                    $out[$key] = replace_array($val, $chars);
                }
                break;
            default:
                $out = '';
        }
        return $out;
    }
}
