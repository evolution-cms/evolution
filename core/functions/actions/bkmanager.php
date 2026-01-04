<?php
if(!function_exists('import_sql')) {
    /**
     * @param string $source
     * @param string $result_code
     */
    function import_sql($source, $result_code = 'import_ok')
    {
        $modx = evolutionCMS();
        global $e;

        $rs = null;
        if ($modx->getLockedElements() !== []) {
            $modx->webAlertAndQuit("At least one Resource is still locked or edited right now by any user. Remove locks or ask users to log out before proceeding.");
        }

        $settings = getSettings();

        if (strpos($source, "\r") !== false) {
            $source = str_replace([
                "\r\n",
                "\n",
                "\r"
            ], "\n", $source);
        }
        $sql_array = preg_split('@;[ \t]*\n@', $source);
        foreach ($sql_array as $sql_entry) {
            $sql_entry = trim($sql_entry, "\r\n; ");
            if (empty($sql_entry)) {
                continue;
            }

            $rs = $modx->getDatabase()->query($sql_entry);


        }
        restoreSettings($settings);

        $modx->clearCache();

        $_SESSION['last_result'] = ($rs !== null) ? null : $modx->getDatabase()->makeArray($rs);
        $_SESSION['result_msg'] = $result_code;
    }
}

if (!function_exists('import_sql_from_file')) {
    function import_sql_from_file($path, $result_code = 'import_ok')
    {
        if (!file_exists($path)) {
            return false;
        }
        $fp = fopen($path, 'r');
        $output = '';
        while (($buffer = fgets($fp)) !== false) {
            $output .= $buffer . "\n";
            if (strlen($output) > 5040000 && $buffer === "\n") {

                import_sql($output);
                $output = '';
            }
        }

        if(!empty($output)){
            import_sql($output);
        }
    }
}

if(!function_exists('dumpSql')) {
    /**
     * @param string $dumpstring
     * @return bool
     */
    function dumpSql($dumpTempFilePath)
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        $modx = evolutionCMS();
        $today = $modx->toDateFormat(time(), 'dateOnly');
        $today = str_replace('/', '-', $today);
        $today = strtolower($today);
        $size = filesize($dumpTempFilePath);
        if (!headers_sent()) {
            header('Expires: 0');
            header('Cache-Control: private');
            header('Pragma: cache');
            header('Content-type: application/download');
            header("Content-Length: {$size}");
            header("Content-Disposition: attachment; filename={$today}_database_backup.sql");
        }
        readfile($dumpTempFilePath);
        unlink($dumpTempFilePath);
        exit;
    }
}

if(!function_exists('snapshot')) {
    /**
     * @param string $dumpstring
     * @return bool
     */
    function snapshot($dumpTempFilePath,$snapshootFile)
    {
        rename($dumpTempFilePath, $snapshootFile);
        return true;
    }
}

if(!function_exists('getSettings')) {
    /**
     * @return array
     */
    function getSettings()
    {
        $modx = evolutionCMS();
        $tbl_system_settings = $modx->getDatabase()->getFullTableName('system_settings');

        $rs = $modx->getDatabase()->select('setting_name, setting_value', $tbl_system_settings);

        $settings = [];
        while ($row = $modx->getDatabase()->getRow($rs)) {
            switch ($row['setting_name']) {
                case 'rb_base_dir':
                case 'filemanager_path':
                case 'site_url':
                case 'base_url':
                    $settings[$row['setting_name']] = $row['setting_value'];
                    break;
            }
        }

        return $settings;
    }
}

if(!function_exists('restoreSettings')) {
    /**
     * @param array $settings
     */
    function restoreSettings($settings)
    {
        $modx = evolutionCMS();
        $tbl_system_settings = $modx->getDatabase()->getFullTableName('system_settings');

        foreach ($settings as $k => $v) {
            $modx->getDatabase()->update(['setting_value' => $v], $tbl_system_settings, "setting_name='{$k}'");
        }
    }
}

if(!function_exists('parsePlaceholder')) {
    /**
     * @param string $tpl
     * @param array $ph
     * @return string
     */
    function parsePlaceholder($tpl = '', $ph = [])
    {
        if (empty($ph) || empty($tpl)) {
            return $tpl;
        }

        foreach ($ph as $k => $v) {
            $k = "[+{$k}+]";
            $tpl = str_replace($k, $v, $tpl);
        }

        return $tpl;
    }
}
