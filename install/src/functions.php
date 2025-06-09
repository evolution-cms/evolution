<?php

const ANSI_BOLD_GREEN = "\033[1;32m";
const ANSI_BOLD_RED = "\033[1;31m";
const ANSI_BOLD_YELLOW = "\033[1;33m";
const ANSI_RESET = "\033[0m";

if (!function_exists('getLangOptions')) {
    /**
     * @param  string  $install_language
     * @return string
     */
    function getLangOptions($install_language = 'en')
    {
        $langs = [];
        if ($handle = opendir(__DIR__ . '/lang/')) {
            while (false !== ($file = readdir($handle))) {
                if (strpos($file, '.')) {
                    $langs[] = str_replace('.inc.php', '', $file);
                }
            }
            closedir($handle);
        }
        sort($langs);
        $_ = [];
        foreach ($langs as $language) {
            $abrv_language = explode('-', $language);
            $selected = ($language === $install_language) ? 'selected' : '';
            $_[] = '<option value="' . $language . '" ' . $selected . '>' . ucwords($abrv_language[0]) . '</option>' . "\n";
        }

        return implode("\n", $_);
    }
}

if (!function_exists('install_sessionCheck')) {
    function install_sessionCheck()
    {
        global $_lang;

        // session loop-back tester
        if (!isset($_GET['action']) || $_GET['action'] !== 'mode') {
            if (!isset($_SESSION['test']) || $_SESSION['test'] != 1) {
                echo '
<html>
<head>
	<title>Install Problem</title>
	<style type="text/css">
		*{margin:0;padding:0}
		body{margin:150px;background:#eee;}
		.install{padding:10px;border:3px solid #ffc565;background:#ffddb4;margin:0 auto;text-align:center;}
		p{ margin:20px 0; }
		a{margin-top:30px;padding:5px;}
	</style>
</head>
<body>
	<div class="install">
		<p>' . $_lang["session_problem"] . '</p>
		<p><a href="./">' . $_lang["session_problem_try_again"] . '</a></p>
	</div>
</body>
</html>';
                exit;
            }
        }
    }
}

if (!function_exists('csrfNonce')) {
    function csrfNonce()
    {
        return $GLOBALS['csrfNonce'] ?? $GLOBALS['csrfNonce'] = bin2hex(random_bytes(16));
    }
}

if (!function_exists('parse')) {
    /**
     * @param  string  $src
     * @param  array  $ph
     * @param  string  $left
     * @param  string  $right
     * @return string
     */
    function parse($src, $ph, $left = '[+', $right = '+]')
    {
        foreach ($ph as $k => $v) {
            $k = $left . $k . $right;
            $src = str_replace($k, $v, $src);
        }

        return $src;
    }
}

if (!function_exists('ph')) {
    /**
     * @return array
     */
    function ph()
    {
        global $_lang, $moduleName, $moduleVersion, $evo_textdir, $evo_release_date;
        $ph = [];

        if (isset($_SESSION['installmode'])) {
            $installmode = $_SESSION['installmode'];
        } else {
            $installmode = get_installmode();
        }

        $ph['pagetitle'] = $_lang['modx_install'];
        $ph['textdir'] = $evo_textdir ? ' id="rtl"' : '';
        $ph['version'] = $moduleVersion;
        $ph['release_date'] = ($evo_textdir ? '&rlm;' : '') . $evo_release_date;
        $ph['footer1'] = $_lang['modx_footer1'];
        $ph['footer2'] = $_lang['modx_footer2'];
        $ph['current_year'] = date('Y');
        $ph['csrf_nonce'] = csrfNonce();

        return $ph;
    }
}

if (!function_exists('get_installmode')) {
    /**
     * @return int
     */
    function get_installmode()
    {
        global $base_path, $database_server, $database_user, $database_password, $dbase, $table_prefix;

        $conf_path = "{$base_path}manager/includes/config.inc.php";
        if (!is_file($conf_path)) {
            $installmode = 0;
        } elseif (isset($_POST['installmode'])) {
            $installmode = $_POST['installmode'];
        } else {
            include_once("{$base_path}manager/includes/config.inc.php");

            if (!isset($dbase) || empty($dbase)) {
                $installmode = 0;
            } else {
                $host = explode(':', $database_server, 2);
                $conn = mysqli_connect($host[0], $database_user, $database_password, '',
                    isset($host[1]) ? $host[1] : null);
                if ($conn) {
                    $_SESSION['database_server'] = $database_server;
                    $_SESSION['database_user'] = $database_user;
                    $_SESSION['database_password'] = $database_password;

                    $dbase = trim($dbase, '`');
                    $rs = mysqli_select_db($conn, $dbase);
                } else {
                    $rs = false;
                }

                if ($rs) {
                    $_SESSION['dbase'] = $dbase;
                    $_SESSION['table_prefix'] = $table_prefix;
                    $_SESSION['database_collation'] = 'utf8mb4_general_ci';
                    $_SESSION['database_connection_method'] = 'SET CHARACTER SET';

                    $tbl_system_settings = "`{$dbase}`.`{$table_prefix}system_settings`";
                    $rs = mysqli_query($conn,
                        "SELECT setting_value FROM {$tbl_system_settings} WHERE setting_name='settings_version'");
                    if ($rs) {
                        $row = mysqli_fetch_assoc($rs);
                        $settings_version = $row['setting_value'];
                    } else {
                        $settings_version = '';
                    }

                    if (empty($settings_version)) {
                        $installmode = 0;
                    } else {
                        $installmode = 1;
                    }
                } else {
                    $installmode = 1;
                }
            }
        }
        return $installmode;
    }
}

if (!function_exists('getLangs')) {
    /**
     * @param  string  $install_language
     * @return string
     */
    function getLangs($install_language)
    {
        if ($install_language !== 'en' &&
            is_dir('../core/lang/' . $install_language)
        ) {
            $manager_language = $install_language;
        } else {
            $manager_language = 'en';
        }
        $langs = [];
        if ($handle = opendir('../core/lang')) {
            while (false !== ($file = readdir($handle))) {
                if (is_dir('../core/lang/' . $file) && $file != '.' && $file != '..') {
                    $langs[] = $file;
                }
            }
            closedir($handle);
        }
        sort($langs);
        $_ = [];
        foreach ($langs as $language) {
            $abrv_language = explode('.', $language);
            $selected = (strtolower($abrv_language[0]) == strtolower($manager_language)) ? ' selected' : '';
            $_[] = '<option value="' . $abrv_language[0] . '" ' . $selected . '>' . strtoupper($abrv_language[0]) . '</option>';
        }
        return implode("\n", $_);
    }
}

if (!function_exists('sortItem')) {
    function sortItem($array = [], $order = 'utf8mb4,utf8')
    {
        $rs = ['recommend' => ''];
        $order = explode(',', $order);
        foreach ($order as $v) {
            foreach ($array as $name => $sel) {
                if (strpos($name, $v) !== false) {
                    $rs[$name] = $array[$name];
                    unset($array[$name]);
                }
            }
        }
        $rs['unrecommend'] = '';
        return $rs + $array;
    }
}

if (!function_exists('getTemplates')) {
    /**
     * @param  array  $presets
     * @return string
     */
    function getTemplates($presets = [])
    {
        if (empty($presets)) {
            return '';
        }
        $selectedTemplates = isset ($_POST['template']) ? $_POST['template'] : [];
        $tpl = '<label><input type="checkbox" name="template[]" value="[+i+]" class="[+class+]" [+checked+] />[%install_update%] <span class="comname">[+name+]</span> - [+desc+]</label><hr />';
        $_ = [];
        $i = 0;
        $ph = [];
        foreach ($presets as $preset) {
            $ph['i'] = $i;
            $ph['name'] = isset($preset[0]) ? $preset[0] : '';
            $ph['desc'] = isset($preset[1]) ? $preset[1] : '';
            $ph['class'] = !in_array('sample', $preset[6]) ? 'toggle' : 'toggle demo';
            $ph['checked'] = in_array($i, $selectedTemplates) || (!isset($_POST['options_selected'])) ? 'checked' : '';
            $_[] = parse($tpl, $ph);
            $i++;
        }
        return (0 < count($_)) ? '<h3>[%templates%]</h3>' . implode("\n", $_) : '';
    }
}

if (!function_exists('getTVs')) {
    /**
     * @param  array  $presets
     * @return string
     */
    function getTVs($presets = [])
    {
        if (empty($presets)) {
            return '';
        }
        $selectedTvs = isset ($_POST['tv']) ? $_POST['tv'] : [];
        $tpl = '<label><input type="checkbox" name="tv[]" value="[+i+]" class="[+class+]" [+checked+] />[%install_update%] <span class="comname">[+name+]</span> - [+alterName+] <span class="description">([+desc+])</span></label><hr />';
        $_ = [];
        $i = 0;
        $ph = [];
        foreach ($presets as $preset) {
            $ph['i'] = $i;
            $ph['name'] = $preset[0];
            $ph['alterName'] = $preset[1];
            $ph['desc'] = $preset[2];
            $ph['class'] = !in_array('sample', $preset[12]) ? 'toggle' : 'toggle demo';
            $ph['checked'] = in_array($i, $selectedTvs) || (!isset($_POST['options_selected'])) ? 'checked' : '';
            $_[] = parse($tpl, $ph);
            $i++;
        }
        return (0 < count($_)) ? '<h3>[%tvs%]</h3>' . implode("\n", $_) : '';
    }
}

if (!function_exists('getChunks')) {
    /**
     * display chunks
     *
     * @param  array  $presets
     * @return string
     */
    function getChunks($presets = [])
    {
        if (empty($presets)) {
            return '';
        }
        $selected = isset ($_POST['chunk']) ? $_POST['chunk'] : [];
        $tpl = '<label><input type="checkbox" name="chunk[]" value="[+i+]" class="[+class+]" [+checked+] />[%install_update%] <span class="comname">[+name+]</span> - [+desc+]</label><hr />';
        $_ = [];
        $i = 0;
        $ph = [];
        foreach ($presets as $preset) {
            $ph['i'] = $i;
            $ph['name'] = $preset[0];
            $ph['desc'] = $preset[1];
            $ph['class'] = !in_array('sample', $preset[5]) ? 'toggle' : 'toggle demo';
            $ph['checked'] = in_array($i, $selected) || (!isset($_POST['options_selected'])) ? 'checked' : '';
            $_[] = parse($tpl, $ph);
            $i++;
        }
        return (0 < count($_)) ? '<h3>[%chunks%]</h3>' . implode("\n", $_) : '';
    }
}

if (!function_exists('getModules')) {
    /**
     * display modules
     *
     * @param  array  $presets
     * @return string
     */
    function getModules($presets = [])
    {
        if (empty($presets)) {
            return '';
        }
        $selected = isset ($_POST['module']) ? $_POST['module'] : [];
        $tpl = '<label><input type="checkbox" name="module[]" value="[+i+]" class="[+class+]" [+checked+] />[%install_update%] <span class="comname">[+name+]</span> - [+desc+]</label><hr />';
        $_ = [];
        $i = 0;
        $ph = [];
        foreach ($presets as $preset) {
            $ph['i'] = $i;
            $ph['name'] = $preset[0];
            $ph['desc'] = $preset[1];
            $ph['class'] = !in_array('sample', $preset[7]) ? 'toggle' : 'toggle demo';
            $ph['checked'] = in_array($i, $selected) || (!isset($_POST['options_selected'])) ? 'checked' : '';
            $_[] = parse($tpl, $ph);
            $i++;
        }
        return (0 < count($_)) ? '<h3>[%modules%]</h3>' . implode("\n", $_) : '';
    }
}

if (!function_exists('getPlugins')) {
    /**
     * display plugins
     *
     * @param  array  $presets
     * @return string
     */
    function getPlugins($presets = [])
    {
        if (!count($presets)) {
            return '';
        }
        $selected = isset ($_POST['plugin']) ? $_POST['plugin'] : [];
        $tpl = '<label><input type="checkbox" name="plugin[]" value="[+i+]" class="[+class+]" [+checked+] />[%install_update%] <span class="comname">[+name+]</span> - [+desc+]</label><hr />';
        $_ = [];
        $i = 0;
        $ph = [];
        foreach ($presets as $preset) {
            $ph['i'] = $i;
            $ph['name'] = $preset[0];
            $ph['desc'] = $preset[1];
            if (is_array($preset[8])) {
                $ph['class'] = !in_array('sample', $preset[8]) ? 'toggle' : 'toggle demo';
            } else {
                $ph['class'] = 'toggle demo';
            }
            $ph['checked'] = in_array($i, $selected) || (!isset($_POST['options_selected'])) ? 'checked' : '';
            $_[] = parse($tpl, $ph);
            $i++;
        }
        return (0 < count($_)) ? '<h3>[%plugins%]</h3>' . implode("\n", $_) : '';
    }
}

if (!function_exists('getSnippets')) {
    /**
     * display snippets
     *
     * @param  array  $presets
     * @return string
     */
    function getSnippets($presets = [])
    {
        if (!count($presets)) {
            return '';
        }
        $selected = isset ($_POST['snippet']) ? $_POST['snippet'] : [];
        $tpl = '<label><input type="checkbox" name="snippet[]" value="[+i+]" class="[+class+]" [+checked+] />[%install_update%] <span class="comname">[+name+]</span> - [+desc+]</label><hr />';
        $_ = [];
        $i = 0;
        $ph = [];
        foreach ($presets as $preset) {
            $ph['i'] = $i;
            $ph['name'] = $preset[0];
            $ph['desc'] = $preset[1];
            $ph['class'] = !in_array('sample', $preset[5]) ? 'toggle' : 'toggle demo';
            $ph['checked'] = in_array($i, $selected) || (!isset($_POST['options_selected'])) ? 'checked' : '';
            $_[] = parse($tpl, $ph);
            $i++;
        }
        return (0 < count($_)) ? '<h3>[%snippets%]</h3>' . implode("\n", $_) : '';
    }
}

if (!function_exists('parse_docblock')) {
    function parse_docblock($element_dir, $filename)
    {
        $params = [];
        $fullpath = $element_dir . '/' . $filename;
        if (is_readable($fullpath)) {
            $tpl = @fopen($fullpath, 'r');
            if ($tpl) {
                $params['filename'] = $filename;
                $docblock_start_found = false;
                $name_found = false;
                $description_found = false;

                while (!feof($tpl)) {
                    $line = fgets($tpl);
                    if (!$docblock_start_found) {
                        // find docblock start
                        if (strpos($line, '/**') !== false) {
                            $docblock_start_found = true;
                        }
                        continue;
                    } elseif (!$name_found) {
                        // find name
                        $ma = null;
                        if (preg_match("/^\s+\*\s+(.+)/", $line, $ma)) {
                            $params['name'] = trim($ma[1]);
                            $name_found = !empty($params['name']);
                        }
                        continue;
                    } elseif (!$description_found) {
                        // find description
                        $ma = null;
                        if (preg_match("/^\s+\*\s+(.+)/", $line, $ma)) {
                            $params['description'] = trim($ma[1]);
                            $description_found = !empty($params['description']);
                        }
                        continue;
                    } else {
                        $ma = null;
                        if (preg_match("/^\s+\*\s+\@([^\s]+)\s+(.+)/", $line, $ma)) {
                            $param = trim($ma[1]);
                            $val = trim($ma[2]);
                            if (!empty($param) && !empty($val)) {
                                if ($param == 'internal') {
                                    $ma = null;
                                    if (preg_match("/\@([^\s]+)\s+(.+)/", $val, $ma)) {
                                        $param = trim($ma[1]);
                                        $val = trim($ma[2]);
                                    }
                                    //if($val !== '0' && (empty($param) || empty($val))) {
                                    if (empty($param)) {
                                        continue;
                                    }
                                }
                                $params[$param] = $val;
                            }
                        } elseif (preg_match("/^\s*\*\/\s*$/", $line)) {
                            break;
                        }
                    }
                }
                @fclose($tpl);
            }
        }
        return $params;
    }
}

if (!function_exists('propertiesNameValue')) {
    /**
     * parses a resource property string and returns the result as an array
     * duplicate of method in documentParser class
     *
     * @param  string  $propertyString
     * @return array
     */
    function propertiesNameValue($propertyString)
    {
        $parameter = [];
        if (!empty ($propertyString)) {
            $tmpParams = explode('&', $propertyString);
            $countParams = count($tmpParams);
            for ($x = 0; $x < $countParams; $x++) {
                if (strpos($tmpParams[$x], '=', 0)) {
                    $pTmp = explode('=', $tmpParams[$x]);
                    $pvTmp = explode(';', trim($pTmp[1]));
                    if ($pvTmp[1] == 'list' && $pvTmp[3] != '') {
                        $parameter[trim($pTmp[0])] = $pvTmp[3];
                    } //list default
                    else {
                        if ($pvTmp[1] != 'list' && $pvTmp[2] != '') {
                            $parameter[trim($pTmp[0])] = $pvTmp[2];
                        }
                    }
                }
            }
        }
        return $parameter;
    }
}

if (!function_exists('propUpdate')) {
    /**
     * Property Update function
     *
     * @param  string  $new
     * @param  string  $old
     * @return string
     */
    function propUpdate($new, $old)
    {
        $newArr = parseProperties($new);
        $oldArr = parseProperties($old);
        foreach ($oldArr as $k => $v) {
            if (isset($v['0']['options'])) {
                $oldArr[$k]['0']['options'] = $newArr[$k]['0']['options'];
            }
        }
        $return = $oldArr + $newArr;
        $return = json_encode($return, JSON_UNESCAPED_UNICODE);
        $return = ($return !== '[]') ? $return : '';

        return $return;
    }
}

if (!function_exists('parseProperties')) {
    /**
     * @param  string  $propertyString
     * @param  bool|mixed  $json
     * @return string|array
     */
    function parseProperties($propertyString, $json = false)
    {
        $propertyString = str_replace('{}', '', $propertyString);
        $propertyString = str_replace('} {', ',', $propertyString);

        if (empty($propertyString) || $propertyString == '{}' || $propertyString == '[]') {
            $propertyString = '';
        }

        $jsonFormat = isJson($propertyString, true);
        $property = [];
        // old format
        if ($jsonFormat === false) {
            $props = explode('&', $propertyString);
            foreach ($props as $prop) {
                $prop = trim($prop);
                if ($prop === '') {
                    continue;
                }

                $arr = explode(';', $prop);
                if (!is_array($arr)) {
                    $arr = [];
                }
                $key = explode('=', isset($arr[0]) ? $arr[0] : '');
                if (!is_array($key) || empty($key[0])) {
                    continue;
                }

                $property[$key[0]]['0']['label'] = isset($key[1]) ? trim($key[1]) : '';
                $property[$key[0]]['0']['type'] = isset($arr[1]) ? trim($arr[1]) : '';
                switch ($property[$key[0]]['0']['type']) {
                    case 'list':
                    case 'list-multi':
                    case 'checkbox':
                    case 'radio':
                    case 'menu':
                        $property[$key[0]]['0']['value'] = isset($arr[3]) ? trim($arr[3]) : '';
                        $property[$key[0]]['0']['options'] = isset($arr[2]) ? trim($arr[2]) : '';
                        $property[$key[0]]['0']['default'] = isset($arr[3]) ? trim($arr[3]) : '';
                        break;
                    default:
                        $property[$key[0]]['0']['value'] = isset($arr[2]) ? trim($arr[2]) : '';
                        $property[$key[0]]['0']['default'] = isset($arr[2]) ? trim($arr[2]) : '';
                }
                $property[$key[0]]['0']['desc'] = '';

            }
            // new json-format
        } else {
            if (!empty($jsonFormat)) {
                $property = $jsonFormat;
            }
        }

        if ($json) {
            $property = json_encode($property, JSON_UNESCAPED_UNICODE);
        }
        $property = ($property !== '[]') ? $property : '';

        return $property;
    }
}

if (!function_exists('isJson')) {
    /**
     * @param  string  $string
     * @param  bool  $returnData
     * @return bool|mixed
     */
    function isJson($string, $returnData = false)
    {
        $data = json_decode($string, true);
        return (json_last_error() == JSON_ERROR_NONE) ? ($returnData ? $data : true) : false;
    }
}

if (!function_exists('getCreateDbCategory')) {
    /**
     * @param  string|int  $category
     * @param  SqlParser  $sqlParser
     * @return int
     */
    function getCreateDbCategory($category)
    {
        $category_id = 0;
        if (!empty($category)) {
            $categoryRecord = \EvolutionCMS\Models\Category::where('category', $category)->first();
            if (is_null($categoryRecord)) {
                $categoryRecord = \EvolutionCMS\Models\Category::firstOrCreate(['category' => $category]);
            }
            $category_id = $categoryRecord->getKey();
        }
        return $category_id;
    }
}

if (!function_exists('removeDocblock')) {
    /**
     * Remove installer Docblock only from components using plugin FileSource / fileBinding
     *
     * @param  string  $code
     * @param  string  $type
     * @return string
     */
    function removeDocblock($code, $type)
    {
        $cleaned = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', $code, 1);

        // Procedure taken from plugin.filesource.php
        switch ($type) {
            case 'snippet':
                $elm_name = 'snippets';
                $include = 'return require';
                $count = 47;
                break;

            case 'plugin':
                $elm_name = 'plugins';
                $include = 'require';
                $count = 39;
                break;

            default:
                return $cleaned;
        };
        if (substr(trim($cleaned), 0, $count) == $include . ' MODX_BASE_PATH.\'assets/' . $elm_name . '/') {
            return $cleaned;
        }

        // fileBinding not found - return code incl docblock
        return $code;
    }
}

if (!function_exists('removeFolder')) {
    /**
     * RemoveFolder
     *
     * @param  string  $path
     * @return string
     */
    function removeFolder($path)
    {
        $dir = realpath($path);
        if (!is_dir($dir)) {
            return;
        }

        $it = new RecursiveDirectoryIterator($dir);
        $files = new RecursiveIteratorIterator($it,
            RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->getFilename() === '.' || $file->getFilename() === '..') {
                continue;
            }
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return value($default);
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }

        if (($valueLength = strlen($value)) > 1 && $value[0] === '"' && $value[$valueLength - 1] === '"') {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('seed')) {
    function seed($folder = 'install')
    {
        $folder = $folder == 'update' ? 'update' : 'install';
        $namespace = 'EvolutionCMS\\Installer\\' . ($folder == 'update' ? 'Update\\' : 'Install\\');
        foreach (glob("../install/stubs/seeds/{$folder}/*.php") as $filename) {
            include_once $filename;
            $class = $namespace . basename($filename, '.php');
            if (class_exists($class) && is_subclass_of($class, 'Illuminate\\Database\\Seeder')) {
                \EvolutionCMS\Facades\Console::call('db:seed', ['--class' => '\\' . $class]);
            }
        }
    }
}

/**
 * Print a neutral info message (default color).
 *
 * @param string $text Text to print.
 * @return void
 */
if (!function_exists('info')) {
    function info(string $text): void
    {
        echo $text . PHP_EOL;
    }
}

/**
 * Print a success message (bold green).
 *
 * @param string $text Text to print.
 * @return void
 */
if (!function_exists('success')) {
    function success(string $text): void
    {
        echo ANSI_BOLD_GREEN . $text . ANSI_RESET . PHP_EOL;
    }
}

/**
 * Print a warning message (default color).
 *
 * @param string $text Text to print.
 * @return void
 */
if (!function_exists('warning')) {
    function warning(string $text): void
    {
        echo ANSI_BOLD_YELLOW . $text . ANSI_RESET . PHP_EOL;
    }
}

/**
 * Print an error message (bold red).
 *
 * @param string $text Text to print.
 * @return void
 */
if (!function_exists('error')) {
    function error(string $text): void
    {
        echo ANSI_BOLD_RED . $text . ANSI_RESET . PHP_EOL;
    }
}
