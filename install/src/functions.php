<?php

const ANSI_BOLD_GREEN = "\033[1;32m";
const ANSI_BOLD_RED = "\033[1;31m";
const ANSI_BOLD_YELLOW = "\033[1;33m";
const ANSI_RESET = "\033[0m";
const ADMIN_PASSWORD_MIN_LENGTH = 8;
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

function escapeHtmlAttribute($unescaped) {
    return htmlspecialchars($unescaped, ENT_QUOTES, 'UTF-8');
}

function adminPasswordMinLengthMessage(): string
{
    return sprintf('Admin password should have at least %d characters', ADMIN_PASSWORD_MIN_LENGTH);
}

function getDatabaseCharset($database_collation, $driver): string
{
    if ($driver === 'pgsql') {
            // "en_US.UTF-8", "C.UTF-8", "en_US.utf8", "en-US-x-icu", "fr_FR.iso88591", "ja_JP.eucjp"
        if (strpos($database_collation, '.') !== false) {
            $database_charset = substr($database_collation, strpos($database_collation, '.') + 1);
        } else {
                // Default for C, POSIX, ICU or unknown collations
            $database_charset = 'UTF8';
        }
        $database_charset = str_ireplace(['utf-8', 'utf8'], 'UTF8', $database_charset);
    } elseif ($driver === 'sqlite') {
        $database_charset = 'utf8';
    } else {
            // MySQL 5.7 & 8.0: "utf8mb4_general_ci", "utf8_unicode_ci", "latin1_swedish_ci"
            // MySQL 8.0+: "utf8mb4_0900_ai_ci" (with version number)
        $first_underscore_pos = strpos($database_collation, '_');
        if ($first_underscore_pos !== false) {
            $database_charset = substr($database_collation, 0, $first_underscore_pos);
        } else {
                // Fallback if no underscore found (shouldn't happen with valid collations)
            $database_charset = 'utf8mb4';
        }
    }
    return $database_charset;
}

function databaseHostParts(string $host): array
{
    $host = trim($host);
    $port = null;

    if (preg_match('/^\[([0-9a-f:.]+)\](?::([0-9]{1,5}))?$/i', $host, $matches)) {
        $host = $matches[1];
        $port = $matches[2] ?? null;
    } elseif (substr_count($host, ':') === 1) {
        [$host, $port] = explode(':', $host, 2);
    }

    if ($port !== null && ($port === '' || filter_var($port, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 65535],
    ]) === false)) {
        throw new InvalidArgumentException('Database port should be a number from 1 to 65535');
    }

    return ['host' => $host, 'port' => $port];
}

function databaseDsn(string $driver, string $host, ?string $database = null): string
{
    $parts = databaseHostParts($host);
    $dsn = $driver . ':host=' . $parts['host'];

    if ($parts['port'] !== null) {
        $dsn .= ';port=' . $parts['port'];
    }

    if ($database !== null && $database !== '') {
        $dsn .= ';dbname=' . $database;
    }

    return $dsn;
}

function dbConnect(string $driver, string $host, $db, ?string $user = null, ?string $password = null, ?array $pdoOptions = null) {
    if ($driver === 'sqlite') {
        $dbh = new PDO('sqlite:' . EVO_CORE_PATH . "database/$db.sqlite", null, null, $pdoOptions);
    } else {
        $dbh = new PDO(databaseDsn($driver, $host, $driver === 'pgsql' ? $db : null), $user, $password, $pdoOptions);
    }
    return $dbh;
}

function sqliteDbNameToPath(string $name) {
    return EVO_CORE_PATH . 'database' . DIRECTORY_SEPARATOR . "$name.sqlite";
}

function sqliteDbPathToName(string $path) {
    return str_replace('.sqlite', '',
        str_replace(EVO_CORE_PATH . 'database' . DIRECTORY_SEPARATOR, '', $path));
}

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

function csrfNonce()
{
    return $GLOBALS['csrfNonce'] ?? $GLOBALS['csrfNonce'] = bin2hex(random_bytes(16));
}

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

function validateDbType($type)
{
    if (!in_array($type, ['pgsql', 'sqlite', 'mysql'])) {
        throw new InvalidArgumentException("Database type should be pgsql, sqlite or mysql");
    }
    return $type;
}


function validateDbName($name)
{
    $name = trim($name ?? '', ' `');
    if (strlen($name) === 0) {
        throw new InvalidArgumentException("Database name should not be empty");
    }
    if (strlen($name) >= 64) {
        throw new InvalidArgumentException("Database name should be shorter than 64 characters");
    }
    if (preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{0,63}$/', $name) === false) {
        throw new InvalidArgumentException("Database name should begin with letter and contain only letters,"
            . " numbers, dashes, and underscores");
    }
    return $name;
}

function validateDbCollation($collation)
{
    $collation = trim($collation ?? '');
    if (preg_match('/^[a-zA-Z][a-zA-Z0-9_.-]{0,63}$/', $collation) === false) {
        throw new InvalidArgumentException("Database name should begin with letter and contain only letters,"
            . " numbers, dots, dashes, and underscores");
    }
    return $collation;
}

function validateDbUser($username, $type)
{
    $username = trim($username ?? '');
    if ($type === 'sqlite' && empty($username)) {
        return $username;
    }
    if (preg_match('/^[a-zA-Z][a-zA-Z0-9_]+$/', $username) === false) {
        throw new InvalidArgumentException("Database user should begin with letter and contain only letters,"
            . " numbers, and underscores");
    };
    return $username;
}

function validateDbPassword($password, $type)
{
    $password = trim($password ?? '');
    if ($type === 'sqlite' && empty($password)) {
        return $password;
    }
    if (empty($password)) {
        throw new InvalidArgumentException("Database password should not be empty");
    }
    return $password;
}

function validateDbHost($host, $type)
{
    $host = trim($host ?? '');
    if ($type === 'sqlite' && empty($host)) {
        return $host;
    }
    $parts = databaseHostParts($host);
    if (!$ip = filter_var($parts['host'], FILTER_VALIDATE_IP)) {
        if (!$parts['host'] = filter_var($parts['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new InvalidArgumentException("Database host should be valid IP or hostname");
        }
    } else {
        $parts['host'] = $ip;
    }
    return $parts['host'] . ($parts['port'] !== null ? ':' . $parts['port'] : '');
}

function validateTablePrefix($prefix)
{
    $prefix = trim($prefix);
    if (preg_match('/^[a-zA-Z0-9_]{0,40}_?$/', $prefix) === false) {
        throw new InvalidArgumentException('Invalid table prefix');
    }
    return $prefix;
}

function validateAdminUsername($username)
{
    $username = trim($username);
    if (strlen($username) === 0 || strlen($username) >= 64 ||
        preg_match('/^[a-zA-Z][a-zA-Z0-9._-]{0,63}$/', $username) === false) {
        throw new InvalidArgumentException('Admin username should begin with letter and contain only letters,'
         . " numbers, dashes, dots and underscores");
    }
    return $username;
}

function validateAdminEmail($email)
{
    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    if ($email === false) {
        throw new InvalidArgumentException('E-mail should be valid');
    }
    return $email;
}

function validateAdminPassword($password)
{
    $password = trim($password);
    if (strlen($password) < ADMIN_PASSWORD_MIN_LENGTH) {
        throw new InvalidArgumentException(adminPasswordMinLengthMessage());
    }
    return $password;
}

function validateLangCode($langCode)
{
    if (!in_array($langCode, explode(' ', 'az be bg cs da de en es fa fi fr he it ja nl nn pl pt ru sv uk zh'))) {
        throw new InvalidArgumentException('Language code should be 2 characters lowercase');
    }
    return $langCode;
}

/**
 * @return array
 */
function ph($_lang, $moduleVersion, $evo_textdir, $evo_release_date)
{
    $ph = [];
    $ph['pagetitle'] = $_lang['modx_install'];
    $ph['textdir'] = $evo_textdir ? ' id="rtl"' : '';
    $ph['version'] = $moduleVersion;
    $ph['release_date'] = ($evo_textdir ? '&rlm;' : '') . $evo_release_date;
    $ph['footer1'] = $_lang['modx_footer1'];
    $ph['footer2'] = $_lang['modx_footer2'];
    $ph['current_year'] = date('Y');
    $ph['vendor_link_tag'] = '<a href="https://evo.im/">Evolution CMS</a>';
    $ph['csrf_nonce'] = csrfNonce();

    return $ph;
}

/**
 * @deprecated config.inc.php was split to Laravel configs
 * @return int
 */
function get_installmode()
{
    global $base_path, $database_server, $database_user, $database_password, $database_name, $table_prefix;

    $conf_path = "{$base_path}manager/includes/config.inc.php";
    if (!is_file($conf_path)) {
        $installmode = 0;
    } elseif (isset($_POST['installmode'])) {
        $installmode = $_POST['installmode'];
    } else {
        include_once("{$base_path}manager/includes/config.inc.php");

        if (!isset($database_name) || empty($database_name)) {
            $installmode = 0;
        } else {
            try {
                $conn = new PDO(databaseDsn('mysql', $database_server), $database_user, $database_password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $_SESSION['database_server'] = $database_server;
                $_SESSION['database_user'] = $database_user;
                $_SESSION['database_password'] = $database_password;

                $database_name = trim($database_name, '` ');

                $rs = $conn->exec("USE `$database_name`");
            } catch (PDOException $e) {
                $rs = false;
            }

            if ($rs !== false) {
                $_SESSION['table_prefix'] = $table_prefix;
                $_SESSION['database_collation'] = 'utf8mb4_general_ci';

                $tbl_system_settings = "`{$database_name}`.`{$table_prefix}system_settings`";

                try {
                    $stmt = $conn->query("SELECT setting_value FROM {$tbl_system_settings} WHERE setting_name='settings_version'");
                    if ($stmt) {
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $settings_version = $row['setting_value'] ?? '';
                    } else {
                        $settings_version = '';
                    }
                } catch (PDOException $e) {
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

/**
 * Existing installs may carry a single synthetic baseline row instead of the
 * full historical install migration list. Register those older migrations so
 * upgrade mode only runs genuinely new schema changes.
 */
function bootstrapInstallMigrationHistory(string $migrationsPath, string $baselineMigration = '2025_12_25_000000_initial_schema'): int
{
    if (!class_exists(\Illuminate\Support\Facades\DB::class) ||
        !class_exists(\Illuminate\Support\Facades\Schema::class)
    ) {
        return 0;
    }

    try {
        if (!\Illuminate\Support\Facades\Schema::hasTable('migrations_install') ||
            !\Illuminate\Support\Facades\Schema::hasTable('active_user_locks')
        ) {
            return 0;
        }

        $registered = \Illuminate\Support\Facades\DB::table('migrations_install')
            ->pluck('migration')
            ->all();

        if (!in_array($baselineMigration, $registered, true)) {
            return 0;
        }

        $historical = [];
        foreach (glob(rtrim($migrationsPath, '/') . '/*.php') as $migrationFile) {
            $migration = basename($migrationFile, '.php');
            if (strcmp($migration, $baselineMigration) < 0 && !in_array($migration, $registered, true)) {
                $historical[] = $migration;
            }
        }

        if ($historical === []) {
            return 0;
        }

        sort($historical);
        $batch = (int) (\Illuminate\Support\Facades\DB::table('migrations_install')->max('batch') ?: 1);
        $rows = array_map(static function ($migration) use ($batch) {
            return [
                'migration' => $migration,
                'batch' => $batch,
            ];
        }, $historical);

        \Illuminate\Support\Facades\DB::table('migrations_install')->insert($rows);

        return count($historical);
    } catch (\Throwable $exception) {
        return 0;
    }
}

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

/**
 * @param  array  $presets
 * @return string
 */
function getTemplates($presets = [])
{
    if (empty($presets)) {
        return '';
    }
    $selectedTemplates = isset($_POST['template']) ? $_POST['template'] : [];
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

/**
 * @param  array  $presets
 * @return string
 */
function getTVs($presets = [])
{
    if (empty($presets)) {
        return '';
    }
    $selectedTvs = isset($_POST['tv']) ? $_POST['tv'] : [];
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
    $selected = isset($_POST['chunk']) ? $_POST['chunk'] : [];
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
    $selected = isset($_POST['module']) ? $_POST['module'] : [];
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
    $selected = isset($_POST['plugin']) ? $_POST['plugin'] : [];
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
    $selected = isset($_POST['snippet']) ? $_POST['snippet'] : [];
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
    if (!empty($propertyString)) {
        $tmpParams = explode('&', $propertyString);
        $countParams = count($tmpParams);
        for ($x = 0; $x < $countParams; $x++) {
            if (strpos($tmpParams[$x], '=', 0)) {
                $pTmp = explode('=', $tmpParams[$x]);
                $pvTmp = explode(';', trim($pTmp[1]));
                if ($pvTmp[1] == 'list' && $pvTmp[3] != '') {
                    $parameter[trim($pTmp[0])] = $pvTmp[3];
                } else {
                    if ($pvTmp[1] != 'list' && $pvTmp[2] != '') {
                        $parameter[trim($pTmp[0])] = $pvTmp[2];
                    }
                }
            }
        }
    }
    return $parameter;
}

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
    if (substr(trim($cleaned), 0, $count) == $include . ' EVO_BASE_PATH.\'assets/' . $elm_name . '/') {
        return $cleaned;
    }

        // fileBinding not found - return code incl docblock
    return $code;
}

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
    $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
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

/**
 * Gets the value of an environment variable.
 *
 * @param  string  $key
 * @param  mixed  $default
 * @return mixed
 */
if (!function_exists('env')) {
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

/**
 * Return the default value of the given value.
 *
 * @param  mixed  $value
 * @return mixed
 */
if (!function_exists('value')) {
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

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

/**
 * Print a neutral info message (default color).
 *
 * Guarded because Laravel declares a global info() of its own. The installers load this file
 * before the core bootstraps, so this declaration normally wins and Laravel's guarded helper
 * stands down. Under the test harness the order is reversed - Composer maps Laravel's helpers
 * at autoload time - and an unguarded declaration here fatals the whole run.
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
function success(string $text): void
{
    echo ANSI_BOLD_GREEN . $text . ANSI_RESET . PHP_EOL;
}

/**
 * Print a warning message (default color).
 *
 * @param string $text Text to print.
 * @return void
 */
function warning(string $text): void
{
    echo ANSI_BOLD_YELLOW . $text . ANSI_RESET . PHP_EOL;
}

/**
 * Print an error message (bold red).
 *
 * @param string $text Text to print.
 * @return void
 */
function error(string $text): void
{
    echo ANSI_BOLD_RED . $text . ANSI_RESET . PHP_EOL;
}
