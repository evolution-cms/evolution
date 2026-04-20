<?php

if (!function_exists('evo_guest')) {
    /**
     * Show content if User isn't authenticated
     * @param $content
     * @return mixed|string
     */
    function evo_guest($content)
    {
        if (evo()->getLoginUserID()) {
            $content = '';
        }
        return $content;
    }
}

if (!function_exists('evo_auth')) {
    /**
     * Show content if User is authenticated
     * @param $content
     * @return mixed|string
     */
    function evo_auth($content)
    {
        if (!evo()->getLoginUserID()) {
            $content = '';
        }
        return $content;
    }
}

if (!function_exists('evo_role')) {
    /**
     * Show content if User is Role or authenticated is role is empty
     * @param string $role
     * @return bool
     */
    function evo_role(string $role = ''): bool
    {
        $out = false;
        if (!empty($role)) {
            $pms = get_by_key($_SESSION, 'webPermissions', [], 'is_array') ?: get_by_key($_SESSION, 'mgrPermissions', [], 'is_array');
            if (count($pms) && isset($pms['name']) && $role == $pms['name']) {
                $out = true;
            }
        } else {
            $pms = get_by_key($_SESSION, 'webValidated', false, 'is_int') ?: get_by_key($_SESSION, 'mgrValidated', false, 'is_int');
            if ($pms) {
                $out = true;
            }
        }

        return $out;
    }
}

if (!function_exists('var_debug')) {
    /**
     * Dumps information about a variable in Tracy Debug Bar.
     * @tracySkipLocation
     * @param mixed $var
     * @param string $title
     * @param array $options
     * @return mixed  variable itself
     */
    function var_debug($var, $title = null, ?array $options = null)
    {
        return EvolutionCMS\Tracy\Debugger::barDump($var, $title, $options);
    }
}

if (!function_exists('evo_parser')) {
    function evo_parser($content)
    {
        $core = evo();
        $minParserPasses = $core->minParserPasses;
        $maxParserPasses = $core->maxParserPasses;

        $core->minParserPasses = 2;
        $core->maxParserPasses = 10;

        $out = \EvolutionCMS\Parser::getInstance($core)->parseDocumentSource($content, $core);

        $core->minParserPasses = $minParserPasses;
        $core->maxParserPasses = $maxParserPasses;

        return $out;
    }
}

if (!function_exists('evo_raw_config_settings')) {
    function evo_raw_config_settings(): array
    {
        $configFile = config_path('cms/settings.php', !app()->isProduction());

        /** @var Illuminate\Filesystem\Filesystem $files */
        $files = app('files');

        if ($files->isFile($configFile)) {
            $config = $files->getRequire($configFile);
        }

        return isset($config) && is_array($config) ? $config : [];
    }
}

if (!function_exists('evo_save_config_settings')) {
    function evo_save_config_settings(array $config = []): bool
    {
        /** @var Illuminate\Filesystem\Filesystem $files */
        $files = app('files');

        $data = $files->put(
            config_path('cms/settings.php', !app()->isProduction()),
            '<?php return ' . var_export($config, true) . ';'
        );

        return is_bool($data) ? $data : true;
    }
}

if (!function_exists('evo_update_config_settings')) {
    function evo_update_config_settings(string $key, $data = null): bool
    {
        $config = evo_raw_config_settings();
        $config[$key] = $data;
        return evo_save_config_settings($config);
    }
}

if (!function_exists('evo_delete_config_settings')) {
    function evo_delete_config_settings(string $key)
    {
        $config = evo_raw_config_settings();
        unset($config[$key]);
        return evo_save_config_settings($config);
    }
}
