<?php

if (!function_exists('revision')) {
    /**
     * Build a cache-versioned URL for a file located below the public web root.
     *
     * The revision is the file modification time, so browsers can cache the URL until the file
     * actually changes. Missing or invalid paths still produce a regular public URL.
     *
     * @param string $path Path relative to the public web root.
     * @return string Public URL with an mtime-based version query parameter when available.
     * @since 3.5.8
     */
    function revision(string $path = ''): string
    {
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');
        $url = EVO_BASE_URL . $path;

        if ($path === '' || str_contains($path, "\0") || preg_match('#(?:^|/)\.\.(?:/|$)#', $path)) {
            return $url;
        }

        $file = EVO_BASE_PATH . str_replace('/', DIRECTORY_SEPARATOR, $path);
        $mtime = is_file($file) ? @filemtime($file) : false;

        return $mtime === false ? $url : $url . '?v=' . $mtime;
    }
}

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
     * Converts seconds into a user-friendly time format. Unit abbreviations
     * are translated using the current application locale. English examples:
     * - Less than 60 seconds: "45s"
     * - Less than 1 hour: "5m 30s"
     * - Less than 1 day: "2h 15m"
     * - 1 day or more: "9d 9h 56m"
     *
     * @param float $seconds Number of seconds to format
     * @return string Human-readable time format
     *
     * @example
     * niceEta(45.5);     // "46s"
     * niceEta(150);      // "2m 30s"
     * niceEta(3600);     // "1h 0m"
     * niceEta(8100);     // "2h 15m"
     * niceEta(813360);   // "9d 9h 56m"
     */
    function niceEta(float $seconds): string
    {
        $units = [
            'day' => 'd',
            'hour' => 'h',
            'minute' => 'm',
            'second' => 's',
        ];

        if (class_exists(\Illuminate\Container\Container::class)) {
            $container = \Illuminate\Container\Container::getInstance();

            if ($container->bound('translator')) {
                $translator = $container->make('translator');

                foreach ($units as $unit => $fallback) {
                    $key = 'global.time_unit_' . $unit . '_short';
                    $translation = $translator->get($key);
                    $units[$unit] = $translation === $key ? $fallback : $translation;
                }
            }
        }

        if ($seconds < 60) {
            return sprintf('%.0f%s', $seconds, $units['second']);
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return sprintf('%.0f%s %.0f%s', $minutes, $units['minute'], $remainingSeconds, $units['second']);
        } elseif ($seconds < 86400) {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return sprintf('%.0f%s %.0f%s', $hours, $units['hour'], $minutes, $units['minute']);
        } else {
            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return sprintf(
                '%.0f%s %.0f%s %.0f%s',
                $days,
                $units['day'],
                $hours,
                $units['hour'],
                $minutes,
                $units['minute']
            );
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
    function js_json($value, int $options = 0): \Illuminate\Support\HtmlString
    {
        $json = json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | $options
        );

        // Already-safe JSON: no raw `<`, `>` or unescaped quote can leave the literal, so the
        // value is returned as Htmlable and templates can print it with `{{ }}`.
        return new \Illuminate\Support\HtmlString($json === false ? 'null' : $json);
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

if (!function_exists('safe_html')) {
    /**
     * Turn untrusted, possibly HTML-flavoured text into markup that is safe to print with `{{ }}`.
     *
     * The whole value is HTML-escaped first, so no attribute, no protocol handler and no element
     * an attacker wrote can survive. Only afterwards a fixed allow list of attribute-free
     * formatting tags is restored. Because the restore step works on the *escaped* string and
     * matches nothing but a tag name with optional slashes, `<img onerror=...>` or
     * `<a href="javascript:...">` can never come back - the worst an attacker can inject is a
     * harmless line break.
     *
     * Escaping runs with `$double_encode = false`, so text that already contains entities
     * (`&amp;`, `&#039;`) keeps its original meaning instead of being encoded a second time.
     *
     * The return value is `Htmlable`, which `e()` - and therefore Blade's `{{ }}` - prints
     * verbatim. That is the intended replacement for `{!! !!}` on stored, user-influenced HTML.
     *
     * @param mixed $value Raw stored value.
     * @param array<int, string>|null $tags Allowed attribute-free tags, defaults to the formatting set.
     * @return HtmlString
     * @since 3.5.8
     */
    function safe_html($value, ?array $tags = null): \Illuminate\Support\HtmlString
    {
        if ($value instanceof \Illuminate\Contracts\Support\Htmlable) {
            $value = $value->toHtml();
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            $value = '';
        }

        $value = (string) $value;

        $tags = $tags ?? [
            'br', 'hr', 'p', 'b', 'strong', 'i', 'em', 'u', 's', 'small',
            'code', 'pre', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'span', 'div',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'sub', 'sup', 'blockquote',
        ];

        $tags = array_values(array_filter(array_map(
            static fn($tag) => preg_match('~^[a-z][a-z0-9]*$~i', (string) $tag) ? strtolower((string) $tag) : null,
            $tags
        )));

        $escaped = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);

        if ($tags !== []) {
            // Only `<`, an optional slash, an allowed tag name, optional whitespace, an optional
            // slash and `>` are turned back into markup. Everything the pattern matched is
            // re-emitted unchanged, so `<br />` stays `<br />` and `<br/>` stays `<br/>`.
            $escaped = preg_replace(
                '~&lt;(/?)(' . implode('|', $tags) . ')(\s*)(/?)&gt;~i',
                '<$1$2$3$4>',
                $escaped
            );
        }

        return new \Illuminate\Support\HtmlString($escaped);
    }
}

if (!function_exists('icon_markup')) {
    /**
     * Normalise a manager theme icon into HTML.
     *
     * Theme icons arrive in three shapes: ready-made SVG/HTML markup, a `tabler-*` icon name, or
     * a plain CSS class list. Class lists are escaped before they land in the `class` attribute,
     * so a malformed or third-party theme value cannot close the attribute and inject markup.
     *
     * @param string|\Illuminate\Contracts\Support\Htmlable|null $icon
     * @param string $attributes Additional literal attributes, e.g. ' aria-hidden="true"'.
     * @return string
     * @since 3.5.8
     */
    function icon_markup($icon, string $attributes = ''): string
    {
        if ($icon instanceof \Illuminate\Contracts\Support\Htmlable) {
            $icon = $icon->toHtml();
        }

        $icon = is_scalar($icon) ? trim((string) $icon) : '';

        if ($icon === '') {
            return '';
        }

        if (strpos($icon, '<') !== false) {
            return $icon;
        }

        if (strpos($icon, 'tabler-') === 0 && function_exists('svg')) {
            return svg($icon)->toHtml();
        }

        return '<i class="' . htmlspecialchars($icon, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false) . '"' .
            $attributes . '></i>';
    }
}

if (!function_exists('icon_html')) {
    /**
     * Same as icon_markup(), but returned as `Htmlable`.
     *
     * `e()` - and therefore Blade's `{{ }}` - leaves `Htmlable` untouched, so this is the direct
     * replacement for `{!! $_style['icon_x'] !!}` with no double encoding.
     *
     * @param string|\Illuminate\Contracts\Support\Htmlable|null $icon
     * @param string $attributes
     * @return HtmlString
     * @since 3.5.8
     */
    function icon_html($icon, string $attributes = ''): \Illuminate\Support\HtmlString
    {
        return new \Illuminate\Support\HtmlString(icon_markup($icon, $attributes));
    }
}

if (!function_exists('sort_direction')) {
    /**
     * Normalise a user supplied sort direction to `ASC` or `DESC`.
     *
     * Manager list pages copy the requested direction straight into generated links; anything
     * outside the two valid keywords is attacker controlled text and is dropped.
     *
     * @param mixed $direction
     * @param string $default
     * @return string
     * @since 3.5.8
     */
    function sort_direction($direction, string $default = 'DESC'): string
    {
        $direction = is_scalar($direction) ? strtoupper(trim((string) $direction)) : '';

        if ($direction === 'ASC' || $direction === 'DESC') {
            return $direction;
        }

        return strtoupper($default) === 'ASC' ? 'ASC' : 'DESC';
    }
}

if (!function_exists('sort_column')) {
    /**
     * Normalise a user supplied sort column to a plain identifier.
     *
     * Only characters that can appear in a column name survive, so the value is safe both for the
     * query builder and for the links the manager renders back into the page.
     *
     * @param mixed $column
     * @param string $default
     * @return string
     * @since 3.5.8
     */
    function sort_column($column, string $default = 'createdon'): string
    {
        $column = is_scalar($column) ? trim((string) $column) : '';

        return preg_match('~^[A-Za-z_][A-Za-z0-9_]*$~', $column) ? $column : $default;
    }
}
