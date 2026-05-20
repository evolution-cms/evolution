<?php
if (!function_exists('import_sql_split_statements')) {
    /**
     * Split SQL dumps on statement delimiters without breaking values that contain semicolons.
     *
     * Backup dumps can contain PHP code inside SQL string literals, for example @EVAL TV values.
     * A plain preg_split on ";\\n" breaks those rows when the PHP code itself contains semicolons.
     *
     * @param string $source
     * @return array
     */
    function import_sql_split_statements($source)
    {
        $statements = [];
        $statement = '';
        $quote = null;
        $lineComment = false;
        $blockComment = false;
        $length = strlen($source);

        for ($i = 0; $i < $length; $i++) {
            $char = $source[$i];
            $next = $source[$i + 1] ?? null;

            if ($lineComment) {
                $statement .= $char;
                if ($char === "\n") {
                    $lineComment = false;
                }
                continue;
            }

            if ($blockComment) {
                $statement .= $char;
                if ($char === '*' && $next === '/') {
                    $statement .= $next;
                    $i++;
                    $blockComment = false;
                }
                continue;
            }

            if ($quote !== null) {
                $statement .= $char;

                if ($char === '\\' && $quote !== '`' && $next !== null) {
                    $statement .= $next;
                    $i++;
                    continue;
                }

                if ($char === $quote) {
                    if ($next === $quote) {
                        $statement .= $next;
                        $i++;
                        continue;
                    }

                    $quote = null;
                }
                continue;
            }

            if ($char === '-' && $next === '-') {
                $statement .= $char . $next;
                $i++;
                $lineComment = true;
                continue;
            }

            if ($char === '#') {
                $statement .= $char;
                $lineComment = true;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $statement .= $char . $next;
                $i++;
                $blockComment = true;
                continue;
            }

            if ($char === '\'' || $char === '"' || $char === '`') {
                $statement .= $char;
                $quote = $char;
                continue;
            }

            if ($char === ';') {
                $sql = trim($statement, "\r\n\t ;");
                if ($sql !== '') {
                    $statements[] = $sql;
                }
                $statement = '';
                continue;
            }

            $statement .= $char;
        }

        $sql = trim($statement, "\r\n\t ;");
        if ($sql !== '') {
            $statements[] = $sql;
        }

        return $statements;
    }
}

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
        $driver = $modx->getDatabase()->getConfig('driver');
        if (in_array($driver, ['sqlite', 'sqlite3'], true)) {
            $rs = \DB::connection()->getPdo()->exec($source);
        } else {
            $sql_array = import_sql_split_statements($source);
            foreach ($sql_array as $sql_entry) {
                $sql_entry = trim($sql_entry, "\r\n; ");
                if (empty($sql_entry)) {
                    continue;
                }

                $rs = $modx->getDatabase()->query($sql_entry);
            }
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
        $evo = evolutionCMS();
        $driver = $evo->getDatabase()->getConfig('driver');
        if (in_array($driver, ['sqlite', 'sqlite3'], true)) {
            import_sql(file_get_contents($path), $result_code);
            return true;
        }

        $fp = fopen($path, 'r');
        $output = '';
        while (($buffer = fgets($fp)) !== false) {
            $output .= $buffer . "\n";
            if (strlen($output) > 5040000 && $buffer === "\n") {

                import_sql($output, $result_code);
                $output = '';
            }
        }

        if(!empty($output)){
            import_sql($output, $result_code);
        }

        return true;
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
        $driver = $modx->getDatabase()->getConfig('driver');
        $settings = [];

        if (in_array($driver, ['sqlite', 'sqlite3'], true)) {
            $rows = \DB::select('SELECT setting_name, setting_value FROM ' . $tbl_system_settings);
            foreach ($rows as $row) {
                switch ($row->setting_name) {
                    case 'rb_base_dir':
                    case 'filemanager_path':
                    case 'site_url':
                    case 'base_url':
                        $settings[$row->setting_name] = $row->setting_value;
                        break;
                }
            }

            return $settings;
        }

        $rs = $modx->getDatabase()->select('setting_name, setting_value', $tbl_system_settings);
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
        $driver = $modx->getDatabase()->getConfig('driver');

        if (in_array($driver, ['sqlite', 'sqlite3'], true)) {
            foreach ($settings as $k => $v) {
                \DB::table('system_settings')
                    ->where('setting_name', $k)
                    ->update(['setting_value' => $v]);
            }
            return;
        }

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
