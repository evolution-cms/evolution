<?php
/**
 * EVO Cli Installer
 * php cli-install.php --database_server=localhost --database=db --database_user=dbuser --database_password=dbpass --table_prefix=evo_ --cmsadmin=admin --cmsadminemail=dmi3yy@gmail.com --cmspassword=123456 --language=ru --mode=new --installData=n --removeInstall=y
 */

$self = 'install/cli-install.php';
$path = dirname(__FILE__) . '/';
$base_path = str_replace($self,'',str_replace('\\','/', __FILE__));
define('MODX_API_MODE', true);
define('MODX_BASE_PATH', $base_path);
define('MODX_SITE_URL', '/');

require_once($path."functions.php");

// set error reporting
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

if (is_file($base_path."assets/cache/siteManager.php")) {
    include_once($base_path."assets/cache/siteManager.php");
}
if(!defined('MGR_DIR') && is_dir($base_path."manager")) {
    define('MGR_DIR', 'manager');
}

require_once($path."lang.php");
require_once($base_path.MGR_DIR.'/includes/version.inc.php');

$moduleName = "EVO";
$moduleVersion = $modx_branch.' '.$modx_version;
$moduleRelease = $modx_release_date;
$moduleSQLBaseFile = $path."setup.sql";
$moduleSQLDataFile = $path."setup.data.sql";
$moduleSQLResetFile = $path."setup.data.reset.sql";

$moduleChunks = array (); // chunks - array : name, description, type - 0:file or 1:content, file or content
$moduleTemplates = array (); // templates - array : name, description, type - 0:file or 1:content, file or content
$moduleSnippets = array (); // snippets - array : name, description, type - 0:file or 1:content, file or content,properties
$modulePlugins = array (); // plugins - array : name, description, type - 0:file or 1:content, file or content,properties, events,guid
$moduleModules = array (); // modules - array : name, description, type - 0:file or 1:content, file or content,properties, guid
$moduleTemplates = array (); // templates - array : name, description, type - 0:file or 1:content, file or content,properties
$moduleTVs = array (); // template variables - array : name, description, type - 0:file or 1:content, file or content,properties
$moduleDependencies = array(); // module depedencies - array : module, table, column, type, name
$errors= 0;


$installMode= 0;
$installData = 0;
$tableprefixauto = base_convert(rand(10, 20), 10, 36).substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), rand(0, 33), 3).'_';

$args = array_slice($argv, 1);

if ( empty($args) ){
    echo 'Install Evolution CMS'.PHP_EOL;
    //$installYes = readline("Type 'y' to continue: ");
    //if ($installYes != 'y') return;

    //set param manual
    $databasehost = readline($_lang['connection_screen_database_host']. ' [localhost] ');
    $databaseloginname = readline($_lang['connection_screen_database_login']. ' ');
    $databaseloginpassword = readline($_lang['connection_screen_database_pass']. ' ');
    $database_name = readline($_lang['connection_screen_database_name']. ' ');
    $tableprefix = readline($_lang['connection_screen_table_prefix']. ' ['.$tableprefixauto.'] ');
    $database_connection_method = readline($_lang['connection_screen_connection_method']. ' [SET CHARACTER SET] ');
    $database_collation = readline($_lang['connection_screen_collation']. ' [utf8_general_ci] ');
    $cmsadmin = readline($_lang['connection_screen_default_admin_login']. ' [admin] ');
    $cmsadminemail = readline($_lang['connection_screen_default_admin_email']. ' ');
    $cmspassword = readline($_lang['connection_screen_default_admin_password']. ' ');
    $managerlanguage = readline('Мanager language:' . ' [en] ');
    $installData = readline('Instal demo-site (y/n):' . ' [n] ');

}else{

    $cli_variables = [];
    foreach ($args as $arg) {
        $tmp = array_map('trim', explode('=', $arg));
        if (count($tmp) === 2) {
            $k = ltrim($tmp[0], '-');

            $cli_variables[$k] = $tmp[1];

        }
    }

    $databasehost = $cli_variables['database_server'];
    $databaseloginname = $cli_variables['database_user'];
    $databaseloginpassword = $cli_variables['database_password'];
    $database_name = $cli_variables['database'];
    $tableprefix = $cli_variables['table_prefix'];

    $cmsadmin = $cli_variables['cmsadmin'];
    $cmsadminemail = $cli_variables['cmsadminemail'];
    $cmspassword = $cli_variables['cmspassword'];

    $managerlanguage = $cli_variables['language'];
    $installData = $cli_variables['installData'];
    $mode = $cli_variables['mode'];
    $removeInstall = $cli_variables['removeInstall'];

}


if ($databasehost == '') { $databasehost= 'localhost'; }
if ($tableprefix == ''){ $tableprefix = $tableprefixauto; }
if ($database_connection_method == '') { $database_connection_method = 'SET CHARACTER SET'; }
if ($database_collation == '') { $database_collation = 'utf8_general_ci'; }
if ($cmsadmin == ''){ $cmsadmin = 'admin'; }
if ($managerlanguage == '') { $managerlanguage = 'en'; }
if ($installData == 'y') { $installData = 1;}
if ($mode == 'upgrade') { $installMode = 1;}

//добавить обработку языка

switch ($managerlanguage) {
    case 'ru':
        $managerlanguage = 'russian-UTF8';
        break;

    case 'en':
    default:
        $managerlanguage = 'english';
        break;
}

//////////////////////////////////////////////////////////////////////////////////////
if( ! function_exists('f_owc')){
    /**
     * @param $path
     * @param $data
     * @param null|int $mode
     */
    function f_owc($path, $data, $mode = null){
        try {
            // make an attempt to create the file
            $hnd = fopen($path, 'w');
            fwrite($hnd, $data);
            fclose($hnd);

            if(null !== $mode) chmod($path, $mode);
        }catch(Exception $e){
            // Nothing, this is NOT normal
            unset($e);
        }
    }
}

// check PHP version
define('PHP_MIN_VERSION', '5.4.0');
$phpMinVersion = PHP_MIN_VERSION; // Maybe not necessary. For backward compatibility
echo PHP_EOL . $_lang['checking_php_version'];
// -1 if left is less, 0 if equal, +1 if left is higher
if (version_compare(phpversion(), PHP_MIN_VERSION) < 0) {
    $errors++;
    $tmp = $_lang['you_running_php'] . phpversion() . str_replace('[+min_version+]', PHP_MIN_VERSION, $_lang["modx_requires_php"]);
    echo $_lang['failed'] . ' ' . $tmp . PHP_EOL;
} else {
    echo $_lang['ok'] . PHP_EOL;
}

// check directories
// cache exists?
echo strip_tags($_lang['checking_if_cache_exist']);
if (!file_exists($path."../assets/cache") || !file_exists($path."../assets/cache/rss")) {
    echo $_lang['failed'] . PHP_EOL;
    $errors++;
} else {
    echo $_lang['ok'] . PHP_EOL;
}


// cache writable?
echo strip_tags($_lang['checking_if_cache_writable']);
if (!is_writable($path."../assets/cache")) {
    $errors++;
    echo $_lang['failed'] . PHP_EOL;
} else {
    echo $_lang['ok'] . PHP_EOL;
}


// cache files writable?
echo strip_tags($_lang['checking_if_cache_file_writable']);
$tmp = $path."../assets/cache/siteCache.idx.php";
if ( ! file_exists($tmp)) {
    f_owc($tmp, "<?php //EVO site cache file ?>");
}
if ( ! is_writable($tmp)) {
    $errors++;
    echo $_lang['failed'] . PHP_EOL;
} else {
    echo $_lang['ok'] . PHP_EOL;
}


echo strip_tags($_lang['checking_if_cache_file2_writable']);
if ( ! is_writable($path."../assets/cache/sitePublishing.idx.php")) {
    $errors++;
    echo $_lang['failed'] . PHP_EOL;
} else {
    echo $_lang['ok'] . PHP_EOL;
}


// File Browser directories exists?
echo strip_tags($_lang['checking_if_images_exist']);
switch(true){
    case !file_exists($path."../assets/images"):
    case !file_exists($path."../assets/files"):
    case !file_exists($path."../assets/backup"):
    //case !file_exists("../assets/.thumbs"):
        $errors++;
        echo $_lang['failed'] . PHP_EOL;
        break;
    default:
        echo $_lang['ok'] . PHP_EOL;
}


// File Browser directories writable?
echo strip_tags($_lang['checking_if_images_writable']);
switch(true){
    case !is_writable($path."../assets/images"):
    case !is_writable($path."../assets/files"):
    case !is_writable($path."../assets/backup"):
    //case !is_writable("../assets/.thumbs"):
        $errors++;
        echo $_lang['failed'] . PHP_EOL;
        break;
    default:
        echo $_lang['ok'] . PHP_EOL;
}


// export exists?
echo strip_tags($_lang['checking_if_export_exists']);
if (!file_exists($path."../assets/export")) {
    echo $_lang['failed'] . PHP_EOL;
    $errors++;
} else {
    echo $_lang['ok'] . PHP_EOL;
}


// export writable?
echo strip_tags($_lang['checking_if_export_writable']);
if (!is_writable($path."../assets/export")) {
    echo $_lang['failed'] . PHP_EOL;
    $errors++;
} else {
    echo $_lang['ok'] . PHP_EOL;
}


// config.inc.php writable?
echo strip_tags($_lang['checking_if_config_exist_and_writable']);
$tmp = $path."../".MGR_DIR."/includes/config.inc.php";
if (!is_file($tmp)) {
    f_owc($tmp, "<?php //EVO configuration file ?>", 0666);
} else {
    @chmod($tmp, 0666);
}
$isWriteable = is_writable($tmp);
if (!$isWriteable) {
    $errors++;
    echo $_lang['failed'] . PHP_EOL;
} else {
    echo $_lang['ok'] . PHP_EOL;
}


// connect to the database
if ($installMode == 1) {
    include $path."../".MGR_DIR."/includes/config.inc.php";
} else {
    // get db info from post
    $database_server = $databasehost;
    $database_user = $databaseloginname;
    $database_password = $databaseloginpassword;
    $database_collation = $database_collation;
    $database_charset = substr($database_collation, 0, strpos($database_collation, '_') - 1);
    $database_connection_charset = $database_collation;
    $database_connection_method = $database_connection_method;
    $dbase = '`' . $database_name . '`';
    $table_prefix = $tableprefix;
}
echo $_lang['creating_database_connection'];
$host = explode(':', $database_server, 2);
if (!$conn = mysqli_connect($host[0], $database_user, $database_password,'', isset($host[1]) ? $host[1] : null)) {
    $errors++;
    echo $_lang['database_connection_failed'].PHP_EOL;
} else {
    echo $_lang['ok'].PHP_EOL;
}


// make sure we can use the database
if ($installMode > 0 && !mysqli_query($conn, "USE {$dbase}")) {
    $errors++;
    echo $_lang['database_use_failed'].PHP_EOL;
}

// check the database collation if not specified in the configuration
if (!isset ($database_connection_charset) || empty ($database_connection_charset)) {
    if (!$rs = mysqli_query($conn, "show session variables like 'collation_database'")) {
        $rs = mysqli_query($conn, "show session variables like 'collation_server'");
    }
    if ($rs && $collation = mysqli_fetch_row($rs)) {
        $database_collation = $collation[1];
    }
    if (empty ($database_collation)) {
        $database_collation = 'utf8_unicode_ci';
    }
    $database_charset = substr($database_collation, 0, strpos($database_collation, '_') - 1);
    $database_connection_charset = $database_charset;
}

// determine the database connection method if not specified in the configuration
if (!isset($database_connection_method) || empty($database_connection_method)) {
    $database_connection_method = 'SET CHARACTER SET';
}

// check table prefix
if ($conn && $installMode == 0) {
    echo $_lang['checking_table_prefix'] . $table_prefix . '`: ';
    if ($rs= mysqli_query($conn, "SELECT COUNT(*) FROM $dbase.`" . $table_prefix . "site_content`")) {
        echo $_lang['failed'] . ' ' . $_lang['table_prefix_already_inuse_note'] . PHP_EOL;
        $errors++;

    } else {
        echo $_lang['ok'] . PHP_EOL;
    }
} elseif ($conn && $installMode == 2) {
    echo $_lang['checking_table_prefix'] . $table_prefix . '`: ';
    if (!$rs = mysqli_query($conn, "SELECT COUNT(*) FROM $dbase.`" . $table_prefix . "site_content`")) {
        echo $_lang['failed'] . ' ' . $_lang['table_prefix_not_exist'] . PHP_EOL;
        $errors++;

  } else {
        echo $_lang['ok'] . PHP_EOL;
  }
}

// check mysql version
if ($conn) {
    echo $_lang['checking_mysql_version'];
    if ( version_compare(mysqli_get_server_info($conn), '5.0.51', '=') ) {
        echo $_lang['warning'] . ' ' . $_lang['mysql_5051'] . PHP_EOL;
        echo $_lang['mysql_5051_warning'] . PHP_EOL;
    } else {
        echo $_lang['ok'] . ' ' . $_lang['mysql_version_is'] . mysqli_get_server_info($conn) . PHP_EOL;
    }
}

// check for strict mode
if ($conn) {
    echo $_lang['checking_mysql_strict_mode'];
    $mysqlmode = mysqli_query($conn, "SELECT @@global.sql_mode");
    if (mysqli_num_rows($mysqlmode) > 0){
        $modes = mysqli_fetch_array($mysqlmode, MYSQLI_NUM);
        //$modes = array("STRICT_TRANS_TABLES"); // for testing
        // print_r($modes);
        foreach ($modes as $mode) {
            if (stristr($mode, "STRICT_TRANS_TABLES") !== false || stristr($mode, "STRICT_ALL_TABLES") !== false) {
                echo $_lang['warning'] . ' ' . $_lang['strict_mode'] . PHP_EOL;
                echo $_lang['strict_mode_error'] . PHP_EOL;
            } else {
                echo $_lang['ok'] . PHP_EOL;
            }
        }
    } else {
        echo $_lang['ok'] . PHP_EOL;
    }
}
// Version and strict mode check end

// andrazk 20070416 - add install flag and disable manager login
// assets/cache writable?
if (is_writable($path."../assets/cache")) {
    if (file_exists($path.'../assets/cache/installProc.inc.php')) {
        @chmod($path.'../assets/cache/installProc.inc.php', 0755);
        unlink($path.'../assets/cache/installProc.inc.php');
    }

    f_owc($path."../assets/cache/installProc.inc.php", '<?php $installStartTime = '.time().'; ?>');
}

if($installMode > 0 && $_POST['installdata'] == "1") {
    echo $_lang['sample_web_site'] . ': ' . $_lang['sample_web_site_note'] . PHP_EOL;
}

if ($errors > 0) {
    echo $_lang['setup_cannot_continue'] . ' ';

    if($errors > 1){
        echo $errors . " " . $_lang['errors'] . $_lang['please_correct_errors'] . $_lang['and_try_again_plural'];
    }else{
        echo $_lang['error'] . $_lang['please_correct_error'] . $_lang['and_try_again']. PHP_EOL;
    }

    die();
}



//////////////////////////////////////////////////////////////////////////////////////
$create = false;

// set timout limit
@ set_time_limit(120); // used @ to prevent warning when using safe mode?

//echo $_lang['setup_database'].PHP_EOL;



if ($installMode == 1) {
    include $path."../".MGR_DIR."/includes/config.inc.php";
} else {
    // get db info from post
    $database_server = $databasehost;
    $database_user = $databaseloginname;
    $database_password = $databaseloginpassword;
    $database_collation = $database_collation;
    $database_charset = substr($database_collation, 0, strpos($database_collation, '_'));
    $database_connection_charset = $database_charset;
    $database_connection_method = $database_connection_method;
    $dbase = "`" .$database_name. "`";
    $table_prefix = $tableprefix;
    $adminname = $cmsadmin;
    $adminemail = $cmsadminemail;
    $adminpass = $cmspassword;
    $managerlanguage = $managerlanguage;
    $custom_placeholders = array();
}

// set session name variable
if (!isset ($site_sessionname)) {
    $site_sessionname = 'SN' . uniqid('');
}

// get base path and url
$a = explode("install", str_replace("\\", "/", dirname($_SERVER["PHP_SELF"])));
if (count($a) > 1)
    array_pop($a);
$url = implode("install", $a);
reset($a);
$a = explode("install", str_replace("\\", "/", realpath(dirname(__FILE__))));
if (count($a) > 1)
    array_pop($a);
$pth = implode("install", $a);
unset ($a);
$base_url = $url . (substr($url, -1) != "/" ? "/" : "");
$base_path = $pth . (substr($pth, -1) != "/" ? "/" : "");

// connect to the database
echo $_lang['setup_database_create_connection'].': ';
$host = explode(':', $database_server, 2);
if (!$conn = mysqli_connect($host[0], $database_user, $database_password,'', isset($host[1]) ? $host[1] : null)) {
    echo $_lang["setup_database_create_connection_failed"]." ".$_lang['setup_database_create_connection_failed_note'].PHP_EOL;
    return;
} else {
    echo $_lang['ok'].PHP_EOL;
}

// select database
echo $_lang['setup_database_selection']. str_replace("`", "", $dbase) . "`: ";
if (!mysqli_select_db($conn, str_replace("`", "", $dbase))) {
    echo $_lang['setup_database_selection_failed']." ".$_lang['setup_database_selection_failed_note'].PHP_EOL;
    $create = true;
} else {
    if (function_exists('mysqli_set_charset')) mysqli_set_charset($conn, $database_charset);
    mysqli_query($conn, "{$database_connection_method} {$database_connection_charset}");
    echo $_lang['ok'].PHP_EOL;
}

// try to create the database
if ($create) {
    echo $_lang['setup_database_creation']. str_replace("`", "", $dbase) . "`: ";
    //  if(!@mysqli_create_db(str_replace("`","",$dbase), $conn)) {
    if (! mysqli_query($conn, "CREATE DATABASE $dbase DEFAULT CHARACTER SET $database_charset COLLATE $database_collation")) {
        echo $_lang['setup_database_creation_failed']." ".$_lang['setup_database_creation_failed_note'].PHP_EOL;
        $errors += 1;

        echo 'database charset: ' . $database_charset . PHP_EOL;
        echo 'database collation: ' . $database_collation . PHP_EOL;

        echo $_lang['setup_database_creation_failed_note2'] . PHP_EOL;

        die();

    } else {
        echo $_lang['ok'].PHP_EOL;
    }
}

// check table prefix
if ($installMode == 0) {
    echo $_lang['checking_table_prefix'] . $table_prefix . "`: ";
    if (@ $rs = mysqli_query($conn, "SELECT COUNT(*) FROM $dbase.`" . $table_prefix . "site_content`")) {
        echo $_lang['failed'] . " " . $_lang['table_prefix_already_inuse'] . PHP_EOL;
        $errors += 1;
        echo $_lang['table_prefix_already_inuse_note'] . PHP_EOL;
        return;
    } else {
        echo $_lang['ok'].PHP_EOL;
    }
}

if(!function_exists('parseProperties')) {
    /**
     * parses a resource property string and returns the result as an array
     * duplicate of method in documentParser class
     *
     * @param string $propertyString
     * @return array
     */
    function parseProperties($propertyString) {
        $parameter= array ();
        if (!empty ($propertyString)) {
            $tmpParams= explode("&", $propertyString);
            $countParams = count($tmpParams);
            for ($x= 0; $x < $countParams; $x++) {
                if (strpos($tmpParams[$x], '=', 0)) {
                    $pTmp= explode("=", $tmpParams[$x]);
                    $pvTmp= explode(";", trim($pTmp[1]));
                    if ($pvTmp[1] == 'list' && $pvTmp[3] != "")
                        $parameter[trim($pTmp[0])]= $pvTmp[3]; //list default
                    else
                        if ($pvTmp[1] != 'list' && $pvTmp[2] != "")
                            $parameter[trim($pTmp[0])]= $pvTmp[2];
                }
            }
        }
        return $parameter;
    }
}

// check status of Inherit Parent Template plugin
$auto_template_logic = 'sibling';
if ($installMode != 0) {
    $rs = mysqli_query($conn, "SELECT properties, disabled FROM $dbase.`" . $table_prefix . "site_plugins` WHERE name='Inherit Parent Template'");
    $row = mysqli_fetch_row($rs);
    if(!$row) {
        // not installed
        $auto_template_logic = 'system';
    } else {
        if($row[1] == 1) {
            // installed but disabled
            $auto_template_logic = 'system';
        } else {
            // installed, enabled .. see how it's configured
            $properties = parseProperties($row[0]);
            if(isset($properties['inheritTemplate'])) {
                if($properties['inheritTemplate'] == 'From First Sibling') {
                    $auto_template_logic = 'sibling';
                }
            }
        }
    }
}




// open db connection
$setupPath = realpath(dirname(__FILE__));
$chunkPath    = $path.'assets/chunks';
$snippetPath  = $path.'assets/snippets';
$pluginPath   = $path.'assets/plugins';
$modulePath   = $path.'assets/modules';
$templatePath = $path.'assets/templates';
$tvPath       = $path.'assets/tvs';

// setup Template template files - array : name, description, type - 0:file or 1:content, parameters, category
$mt = &$moduleTemplates;
if(is_dir($templatePath) && is_readable($templatePath)) {
    $d = dir($templatePath);
    while (false !== ($tplfile = $d->read()))
    {
        if(substr($tplfile, -4) != '.tpl') continue;
        $params = parse_docblock($templatePath, $tplfile);
        if(is_array($params) && (count($params)>0))
        {
            $description = empty($params['version']) ? $params['description'] : "<strong>{$params['version']}</strong> {$params['description']}";
            $mt[] = array
            (
                $params['name'],
                $description,
                // Don't think this is gonna be used ... but adding it just in case 'type'
                $params['type'],
                "$templatePath/{$params['filename']}",
                $params['modx_category'],
                $params['lock_template'],
                array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : false,
                isset($params['save_sql_id_as']) ? $params['save_sql_id_as'] : NULL // Nessecary to fix template-ID for demo-site
            );
        }
    }
    $d->close();
}

// setup Template Variable template files
$mtv = &$moduleTVs;
if(is_dir($tvPath) && is_readable($tvPath)) {
    $d = dir($tvPath);
    while (false !== ($tplfile = $d->read())) {
        if(substr($tplfile, -4) != '.tpl') continue;
        $params = parse_docblock($tvPath, $tplfile);
        if(is_array($params) && (count($params)>0)) {
            $description = empty($params['version']) ? $params['description'] : "<strong>{$params['version']}</strong> {$params['description']}";
            $mtv[] = array(
                $params['name'],
                $params['caption'],
                $description,
                $params['input_type'],
                $params['input_options'],
                $params['input_default'],
                $params['output_widget'],
                $params['output_widget_params'],
                "$templatePath/{$params['filename']}", /* not currently used */
                $params['template_assignments']!="*"?$params['template_assignments']:implode(',', array_map(function($value){return isset($value[0]) && is_scalar($value[0]);},$mt)), /* comma-separated list of template names */
                $params['modx_category'],
                $params['lock_tv'],  /* value should be 1 or 0 */
                array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : false
            );
        }
    }
    $d->close();
}

// setup chunks template files - array : name, description, type - 0:file or 1:content, file or content
$mc = &$moduleChunks;
if(is_dir($chunkPath) && is_readable($chunkPath)) {
    $d = dir($chunkPath);
    while (false !== ($tplfile = $d->read())) {
        if(substr($tplfile, -4) != '.tpl') {
            continue;
        }
        $params = parse_docblock($chunkPath, $tplfile);
        if(is_array($params) && count($params) > 0) {
            $mc[] = array(
                $params['name'],
                $params['description'],
                "$chunkPath/{$params['filename']}",
                $params['modx_category'],
                array_key_exists('overwrite', $params) ? $params['overwrite'] : 'true',
                array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : false
            );
        }
    }
    $d->close();
}

// setup snippets template files - array : name, description, type - 0:file or 1:content, file or content,properties
$ms = &$moduleSnippets;
if(is_dir($snippetPath) && is_readable($snippetPath)) {
    $d = dir($snippetPath);
    while (false !== ($tplfile = $d->read())) {
        if(substr($tplfile, -4) != '.tpl') {
            continue;
        }
        $params = parse_docblock($snippetPath, $tplfile);
        if(is_array($params) && count($params) > 0) {
            $description = empty($params['version']) ? $params['description'] : "<strong>{$params['version']}</strong> {$params['description']}";
            $ms[] = array(
                $params['name'],
                $description,
                "$snippetPath/{$params['filename']}",
                $params['properties'],
                $params['modx_category'],
                array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : false
            );
        }
    }
    $d->close();
}

// setup plugins template files - array : name, description, type - 0:file or 1:content, file or content,properties
$mp = &$modulePlugins;
if(is_dir($pluginPath) && is_readable($pluginPath)) {
    $d = dir($pluginPath);
    while (false !== ($tplfile = $d->read())) {
        if(substr($tplfile, -4) != '.tpl') {
            continue;
        }
        $params = parse_docblock($pluginPath, $tplfile);
        if(is_array($params) && count($params) > 0) {
            $description = empty($params['version']) ? $params['description'] : "<strong>{$params['version']}</strong> {$params['description']}";
            $mp[] = array(
                $params['name'],
                $description,
                "$pluginPath/{$params['filename']}",
                $params['properties'],
                $params['events'],
                $params['guid'],
                $params['modx_category'],
                $params['legacy_names'],
                array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : false,
                (int)$params['disabled']
            );
        }
    }
    $d->close();
}

// setup modules - array : name, description, type - 0:file or 1:content, file or content,properties, guid,enable_sharedparams
$mm = &$moduleModules;
$mdp = &$moduleDependencies;
if(is_dir($modulePath) && is_readable($modulePath)) {
    $d = dir($modulePath);
    while (false !== ($tplfile = $d->read())) {
        if(substr($tplfile, -4) != '.tpl') {
            continue;
        }
        $params = parse_docblock($modulePath, $tplfile);
        if(is_array($params) && count($params) > 0) {
            $description = empty($params['version']) ? $params['description'] : "<strong>{$params['version']}</strong> {$params['description']}";
            $mm[] = array(
                $params['name'],
                $description,
                "$modulePath/{$params['filename']}",
                $params['properties'],
                $params['guid'],
                (int)$params['shareparams'],
                $params['modx_category'],
                array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : false
            );
        }
        if ((int)$params['shareparams'] || !empty($params['dependencies'])) {
            $dependencies = explode(',', $params['dependencies']);
            foreach ($dependencies as $dependency) {
                $dependency = explode(':', $dependency);
                switch (trim($dependency[0])) {
                    case 'template':
                        $mdp[] = array(
                            'module' => $params['name'],
                            'table' => 'templates',
                            'column' => 'templatename',
                            'type' => 50,
                            'name' => trim($dependency[1])
                        );
                        break;
                    case 'tv':
                    case 'tmplvar':
                        $mdp[] = array(
                            'module' => $params['name'],
                            'table' => 'tmplvars',
                            'column' => 'name',
                            'type' => 60,
                            'name' => trim($dependency[1])
                        );
                        break;
                    case 'chunk':
                    case 'htmlsnippet':
                        $mdp[] = array(
                            'module' => $params['name'],
                            'table' => 'htmlsnippets',
                            'column' => 'name',
                            'type' => 10,
                            'name' => trim($dependency[1])
                        );
                        break;
                    case 'snippet':
                        $mdp[] = array(
                            'module' => $params['name'],
                            'table' => 'snippets',
                            'column' => 'name',
                            'type' => 40,
                            'name' => trim($dependency[1])
                        );
                        break;
                    case 'plugin':
                        $mdp[] = array(
                            'module' => $params['name'],
                            'table' => 'plugins',
                            'column' => 'name',
                            'type' => 30,
                            'name' => trim($dependency[1])
                        );
                        break;
                    case 'resource':
                        $mdp[] = array(
                            'module' => $params['name'],
                            'table' => 'content',
                            'column' => 'pagetitle',
                            'type' => 20,
                            'name' => trim($dependency[1])
                        );
                        break;
                }
            }
        }
    }
    $d->close();
}

// setup callback function
$callBackFnc = "clean_up";

function clean_up($sqlParser) {
    $ids = array();

    // secure web documents - privateweb
    mysqli_query($sqlParser->conn,"UPDATE `".$sqlParser->prefix."site_content` SET privateweb = 0 WHERE privateweb = 1");
    $sql =  "SELECT DISTINCT sc.id
             FROM `".$sqlParser->prefix."site_content` sc
             LEFT JOIN `".$sqlParser->prefix."document_groups` dg ON dg.document = sc.id
             LEFT JOIN `".$sqlParser->prefix."webgroup_access` wga ON wga.documentgroup = dg.document_group
             WHERE wga.id>0";
    $ds = mysqli_query($sqlParser->conn,$sql);
    if(!$ds) {
        echo "An error occurred while executing a query: ".mysqli_error($sqlParser->conn);
    }
    else {
        while($r = mysqli_fetch_assoc($ds)) $ids[]=$r["id"];
        if(count($ids)>0) {
            mysqli_query($sqlParser->conn,"UPDATE `".$sqlParser->prefix."site_content` SET privateweb = 1 WHERE id IN (".implode(", ",$ids).")");
            unset($ids);
        }
    }

    // secure manager documents privatemgr
    mysqli_query($sqlParser->conn,"UPDATE `".$sqlParser->prefix."site_content` SET privatemgr = 0 WHERE privatemgr = 1");
    $sql =  "SELECT DISTINCT sc.id
             FROM `".$sqlParser->prefix."site_content` sc
             LEFT JOIN `".$sqlParser->prefix."document_groups` dg ON dg.document = sc.id
             LEFT JOIN `".$sqlParser->prefix."membergroup_access` mga ON mga.documentgroup = dg.document_group
             WHERE mga.id>0";
    $ds = mysqli_query($sqlParser->conn,$sql);
    if(!$ds) {
        echo "An error occurred while executing a query: ".mysqli_error($sqlParser->conn);
    }
    else {
        while($r = mysqli_fetch_assoc($ds)) $ids[]=$r["id"];
        if(count($ids)>0) {
            mysqli_query($sqlParser->conn,"UPDATE `".$sqlParser->prefix."site_content` SET privatemgr = 1 WHERE id IN (".implode(", ",$ids).")");
            unset($ids);
        }
    }
}

function parse_docblock($element_dir, $filename) {
    $params = array();
    $fullpath = $element_dir . '/' . $filename;
    if(is_readable($fullpath)) {
        $tpl = @fopen($fullpath, "r");
        if($tpl) {
            $params['filename'] = $filename;
            $docblock_start_found = false;
            $name_found = false;
            $description_found = false;

            while(!feof($tpl)) {
                $line = fgets($tpl);
                if(!$docblock_start_found) {
                    // find docblock start
                    if(strpos($line, '/**') !== false) {
                        $docblock_start_found = true;
                    }
                    continue;
                } elseif(!$name_found) {
                    // find name
                    $ma = null;
                    if(preg_match("/^\s+\*\s+(.+)/", $line, $ma)) {
                        $params['name'] = trim($ma[1]);
                        $name_found = !empty($params['name']);
                    }
                    continue;
                } elseif(!$description_found) {
                    // find description
                    $ma = null;
                    if(preg_match("/^\s+\*\s+(.+)/", $line, $ma)) {
                        $params['description'] = trim($ma[1]);
                        $description_found = !empty($params['description']);
                    }
                    continue;
                } else {
                    $ma = null;
                    if(preg_match("/^\s+\*\s+\@([^\s]+)\s+(.+)/", $line, $ma)) {
                        $param = trim($ma[1]);
                        $val = trim($ma[2]);
                        if(!empty($param) && !empty($val)) {
                            if($param == 'internal') {
                                $ma = null;
                                if(preg_match("/\@([^\s]+)\s+(.+)/", $val, $ma)) {
                                    $param = trim($ma[1]);
                                    $val = trim($ma[2]);
                                }
                                //if($val !== '0' && (empty($param) || empty($val))) {
                                if(empty($param)) {
                                    continue;
                                }
                            }
                            $params[$param] = $val;
                        }
                    } elseif(preg_match("/^\s*\*\/\s*$/", $line)) {
                        break;
                    }
                }
            }
            @fclose($tpl);
        }
    }
    return $params;
}


include $path."sqlParser.class.php";
$sqlParser = new SqlParser($database_server, $database_user, $database_password, str_replace("`", "", $dbase), $table_prefix, $adminname, $adminemail, $adminpass, $database_connection_charset, $managerlanguage, $database_connection_method, $auto_template_logic);
$sqlParser->mode = ($installMode < 1) ? "new" : "upd";
/* image and file manager paths now handled via settings screen in Manager
$sqlParser->imageUrl = 'http://' . $_SERVER['SERVER_NAME'] . $base_url . "assets/";
$sqlParser->imageUrl = "assets/";
$sqlParser->imagePath = $base_path . "assets/";
$sqlParser->fileManagerPath = $base_path;
*/
$sqlParser->ignoreDuplicateErrors = true;
$sqlParser->connect();

// install/update database
echo $_lang['setup_database_creating_tables'];
if ($moduleSQLBaseFile) {
    $sqlParser->process($moduleSQLBaseFile);
    // display database results
    if ($sqlParser->installFailed == true) {
        $errors += 1;
        echo $_lang['database_alerts'] . PHP_EOL;
        echo $_lang['setup_couldnt_install'] . PHP_EOL;
        echo $_lang['installation_error_occured'] . PHP_EOL;
        for ($i = 0; $i < count($sqlParser->mysqlErrors); $i++) {
            echo $sqlParser->mysqlErrors[$i]["error"] . " " . $_lang['during_execution_of_sql'] . " " . strip_tags($sqlParser->mysqlErrors[$i]["sql"]) . PHP_EOL;
        }
        echo $_lang['some_tables_not_updated'] . PHP_EOL;
        die();
    } else {
        echo $_lang['ok'].PHP_EOL;
    }
}

// custom or not
if (file_exists($path."../assets/cache/siteManager.php")) {
    $mgrdir = 'include_once(dirname(__FILE__)."/../../assets/cache/siteManager.php");';
}else{
    $mgrdir = 'define(\'MGR_DIR\', \'manager\');';
}

// write the config.inc.php file if new installation
echo $_lang['writing_config_file'];

$confph = array();
$confph['database_server']    = $database_server;
$confph['user_name']          = mysqli_real_escape_string($conn, $database_user);
$confph['password']           = mysqli_real_escape_string($conn, $database_password);
$confph['connection_charset'] = $database_connection_charset;
$confph['connection_method']  = $database_connection_method;
$confph['dbase']              = str_replace('`', '', $dbase);
$confph['table_prefix']       = $table_prefix;
$confph['lastInstallTime']    = time();
$confph['site_sessionname']   = $site_sessionname;

$configString = file_get_contents($path.'config.inc.tpl');
$configString = parse($configString, $confph);

$filename = $base_path.MGR_DIR.'/includes/config.inc.php';
$configFileFailed = false;
if (@ !$handle = fopen($filename, 'w')) {
    $configFileFailed = true;
}

// write $somecontent to our opened file.
if (@ fwrite($handle, $configString) === FALSE) {
    $configFileFailed = true;
}
@ fclose($handle);

// try to chmod the config file go-rwx (for suexeced php)
$chmodSuccess = @chmod($filename, 0404);

if ($configFileFailed == true) {
    echo $_lang['failed'] . PHP_EOL;
    $errors += 1;

    echo $_lang['cant_write_config_file'] . ' ' . MGR_DIR .'/includes/config.inc.php' .PHP_EOL;
    echo ' '.PHP_EOL;
    echo ' '.PHP_EOL;
    echo $configString;
    echo ' '.PHP_EOL;
    echo ' '.PHP_EOL;
    echo $_lang['cant_write_config_file_note'] . PHP_EOL;
    die();

} else {
    echo $_lang['ok'].PHP_EOL;
}

// generate new site_id and set manager theme to default
if ($installMode == 0) {
    $siteid = uniqid('');
    mysqli_query($sqlParser->conn, "REPLACE INTO $dbase.`" . $table_prefix . "system_settings` (setting_name,setting_value) VALUES('site_id','$siteid'),('manager_theme','default')");
} else {
    // update site_id if missing
    $ds = mysqli_query($sqlParser->conn, "SELECT setting_name,setting_value FROM $dbase.`" . $table_prefix . "system_settings` WHERE setting_name='site_id'");
    if ($ds) {
        $r = mysqli_fetch_assoc($ds);
        $siteid = $r['setting_value'];
        if ($siteid == '' || $siteid = 'MzGeQ2faT4Dw06+U49x3') {
            $siteid = uniqid('');
            mysqli_query($sqlParser->conn, "REPLACE INTO $dbase.`" . $table_prefix . "system_settings` (setting_name,setting_value) VALUES('site_id','$siteid')");
        }
    }
}

// Reset database for installation of demo-site
if ($installData && $moduleSQLDataFile && $moduleSQLResetFile) {
    echo $_lang['resetting_database'];
    $sqlParser->process($moduleSQLResetFile);
    // display database results
    if ($sqlParser->installFailed == true) {
        $errors += 1;
        echo $_lang['database_alerts'] . PHP_EOL;
        echo $_lang['setup_couldnt_install'] . PHP_EOL;
        echo $_lang['installation_error_occured'] . PHP_EOL . PHP_EOL;
        /*
        for ($i = 0; $i < count($sqlParser->mysqlErrors); $i++) {
            echo "<em>" . $sqlParser->mysqlErrors[$i]["error"] . "</em>" . $_lang['during_execution_of_sql'] . "<span class='mono'>" . strip_tags($sqlParser->mysqlErrors[$i]["sql"]) . "</span>.<hr />";
        }
        echo "</p>";*/
        echo $_lang['some_tables_not_updated'] . PHP_EOL;
        die();
    } else {
        echo $_lang['ok'] . PHP_EOL;
    }
}

// Install Templates
$moduleTemplate = $mt;
if (!empty($moduleTemplate) || $installData) {
    echo PHP_EOL . $_lang['templates'] . ":" . PHP_EOL;
    //$selTemplates = $_POST['template'];
    foreach ($moduleTemplates as $k=>$moduleTemplate) {
        $installSample = in_array('sample', $moduleTemplate[6]) && $installData == 1;
        if($installSample || is_array($moduleTemplate)) {
            $name = mysqli_real_escape_string($conn, $moduleTemplate[0]);
            $desc = mysqli_real_escape_string($conn, $moduleTemplate[1]);
            $category = mysqli_real_escape_string($conn, $moduleTemplate[4]);
            $locked = mysqli_real_escape_string($conn, $moduleTemplate[5]);
            $filecontent = $moduleTemplate[3];
            $save_sql_id_as = $moduleTemplate[7]; // Nessecary for demo-site
            if (!file_exists($filecontent)) {
                echo "  $name: " . $_lang['unable_install_template'] . " '$filecontent' " . $_lang['not_found'] . PHP_EOL;
            } else {
                // Create the category if it does not already exist
                $category_id = getCreateDbCategory($category, $sqlParser);

                // Strip the first comment up top
                $template = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', file_get_contents($filecontent), 1);
                $template = mysqli_real_escape_string($conn, $template);

                // See if the template already exists
                $rs = mysqli_query($sqlParser->conn, "SELECT * FROM $dbase.`" . $table_prefix . "site_templates` WHERE templatename='$name'");

                if (mysqli_num_rows($rs)) {
                    if (!mysqli_query($sqlParser->conn, "UPDATE $dbase.`" . $table_prefix . "site_templates` SET content='$template', description='$desc', category=$category_id, locked='$locked'  WHERE templatename='$name' LIMIT 1;")) {
                        $errors += 1;
                        echo mysqli_error($sqlParser->conn) . PHP_EOL;
                        return;
                    }
                    if(!is_null($save_sql_id_as)) {
                        $sql_id = @mysqli_insert_id($sqlParser->conn);
                        if(!$sql_id) {
                            $idQuery = mysqli_fetch_assoc(mysqli_query($sqlParser->conn, "SELECT id FROM $dbase.`" . $table_prefix . "site_templates` WHERE templatename='$name' LIMIT 1;"));
                            $sql_id = $idQuery['id'];
                        }
                        $custom_placeholders[$save_sql_id_as] = $sql_id;
                    }
                    echo "  $name: " . $_lang['upgraded'] . PHP_EOL;
                } else {
                    if (!@ mysqli_query($sqlParser->conn, "INSERT INTO $dbase.`" . $table_prefix . "site_templates` (templatename,description,content,category,locked) VALUES('$name','$desc','$template',$category_id,'$locked');")) {
                        $errors += 1;
                        echo mysqli_error($sqlParser->conn) . PHP_EOL;
                        die();
                    }
                    if(!is_null($save_sql_id_as)) $custom_placeholders[$save_sql_id_as] = @mysqli_insert_id($sqlParser->conn);
                    echo "  $name: " . $_lang['installed'] . PHP_EOL;
                }
            }
        }
    }
}

// Install Template Variables
$moduleTVs = $mtv;
if (is_array($moduleTVs) || $installData) {
    echo PHP_EOL . $_lang['tvs'].': '.PHP_EOL;
    //$selTVs = $_POST['tv'];
    foreach ($moduleTVs as $k=>$moduleTV) {
        $installSample = in_array('sample', $moduleTV[12]) && $installData == 1;
        if($installSample || is_array($moduleTVs)) {
            $name = mysqli_real_escape_string($conn, $moduleTV[0]);
            $caption = mysqli_real_escape_string($conn, $moduleTV[1]);
            $desc = mysqli_real_escape_string($conn, $moduleTV[2]);
            $input_type = mysqli_real_escape_string($conn, $moduleTV[3]);
            $input_options = mysqli_real_escape_string($conn, $moduleTV[4]);
            $input_default = mysqli_real_escape_string($conn, $moduleTV[5]);
            $output_widget = mysqli_real_escape_string($conn, $moduleTV[6]);
            $output_widget_params = mysqli_real_escape_string($conn, $moduleTV[7]);
            $filecontent = $moduleTV[8];
            $assignments = $moduleTV[9];
            $category = mysqli_real_escape_string($conn, $moduleTV[10]);
            $locked = mysqli_real_escape_string($conn, $moduleTV[11]);


            // Create the category if it does not already exist
            $category = getCreateDbCategory($category, $sqlParser);

            $rs = mysqli_query($sqlParser->conn, "SELECT * FROM $dbase.`" . $table_prefix . "site_tmplvars` WHERE name='$name'");
            if (mysqli_num_rows($rs)) {
                $insert = true;
                while($row = mysqli_fetch_assoc($rs)) {
                    if (!mysqli_query($sqlParser->conn, "UPDATE $dbase.`" . $table_prefix . "site_tmplvars` SET type='$input_type', caption='$caption', description='$desc', category=$category, locked=$locked, elements='$input_options', display='$output_widget', display_params='$output_widget_params', default_text='$input_default' WHERE id={$row['id']};")) {
                        echo mysqli_error($sqlParser->conn) . PHP_EOL;
                        return;
                    }
                    $insert = false;
                }
                echo "  $name: " . $_lang['upgraded'] . PHP_EOL;
            } else {
                $q = "INSERT INTO $dbase.`" . $table_prefix . "site_tmplvars` (type,name,caption,description,category,locked,elements,display,display_params,default_text) VALUES('$input_type','$name','$caption','$desc',$category,$locked,'$input_options','$output_widget','$output_widget_params','$input_default');";
                if (!mysqli_query($sqlParser->conn, $q)) {
                    echo mysqli_error($sqlParser->conn) . PHP_EOL;
                    return;
                }
                echo "  $name: " . $_lang['installed'] . PHP_EOL;
            }

            // add template assignments
            $assignments = explode(',', $assignments);

            if (count($assignments) > 0) {

                // remove existing tv -> template assignments
                $ds=mysqli_query($sqlParser->conn, "SELECT id FROM $dbase.`".$table_prefix."site_tmplvars` WHERE name='$name' AND description='$desc';");
                $row = mysqli_fetch_assoc($ds);
                $id = $row["id"];
                mysqli_query($sqlParser->conn, 'DELETE FROM ' . $dbase . '.`' . $table_prefix . 'site_tmplvar_templates` WHERE tmplvarid = \'' . $id . '\'');

                // add tv -> template assignments
                foreach ($assignments as $assignment) {
                    $template = mysqli_real_escape_string($conn, $assignment);
                    $ts = mysqli_query($sqlParser->conn, "SELECT id FROM $dbase.`".$table_prefix."site_templates` WHERE templatename='$template';");
                    if ($ds && $ts) {
                        $tRow = mysqli_fetch_assoc($ts);
                        $templateId = $tRow['id'];
                        mysqli_query($sqlParser->conn, "INSERT INTO $dbase.`" . $table_prefix . "site_tmplvar_templates` (tmplvarid, templateid) VALUES($id, $templateId)");
                   }
                }
            }
        }
    }
}


$moduleChunks = $mc;
// Install Chunks
if (is_array ($moduleChunks) || $installData) {
    echo PHP_EOL . $_lang['chunks'] . ": " . PHP_EOL;
    foreach ($moduleChunks as $k=>$moduleChunk) {
        $installSample = in_array('sample', $moduleChunk[5]) && $installData == 1;
        $count_new_name = 0;
        if($installSample || is_array ($moduleChunks)) {

            $name = mysqli_real_escape_string($conn, $moduleChunk[0]);
            $desc = mysqli_real_escape_string($conn, $moduleChunk[1]);
            $category = mysqli_real_escape_string($conn, $moduleChunk[3]);
            $overwrite = mysqli_real_escape_string($conn, $moduleChunk[4]);
            $filecontent = $moduleChunk[2];

            if (!file_exists($filecontent))
                echo "  $name: " . $_lang['unable_install_chunk'] . " '$filecontent' " . $_lang['not_found'] . PHP_EOL;
            else {

                // Create the category if it does not already exist
                $category_id = getCreateDbCategory($category, $sqlParser);

                $chunk = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', file_get_contents($filecontent), 1);
                $chunk = mysqli_real_escape_string($conn, $chunk);
                $rs = mysqli_query($sqlParser->conn, "SELECT * FROM $dbase.`" . $table_prefix . "site_htmlsnippets` WHERE name='$name'");
                $count_original_name = mysqli_num_rows($rs);
                if($overwrite == 'false') {
                    $newname = $name . '-' . str_replace('.', '_', $modx_version);
                    $rs = mysqli_query($sqlParser->conn, "SELECT * FROM $dbase.`" . $table_prefix . "site_htmlsnippets` WHERE name='$newname'");
                    $count_new_name = mysqli_num_rows($rs);
                }
                $update = $count_original_name > 0 && $overwrite == 'true';
                if ($update) {
                    if (!mysqli_query($sqlParser->conn, "UPDATE $dbase.`" . $table_prefix . "site_htmlsnippets` SET snippet='$chunk', description='$desc', category=$category_id WHERE name='$name';")) {
                        $errors += 1;
                        echo mysqli_error($sqlParser->conn) . PHP_EOL;
                        return;
                    }
                    echo "  $name: " . $_lang['upgraded'] . PHP_EOL;
                } elseif($count_new_name == 0) {
                    if($count_original_name > 0 && $overwrite == 'false') {
                        $name = $newname;
                    }
                    if (!mysqli_query($sqlParser->conn, "INSERT INTO $dbase.`" . $table_prefix . "site_htmlsnippets` (name,description,snippet,category) VALUES('$name','$desc','$chunk',$category_id);")) {
                        $errors += 1;
                        echo mysqli_error($sqlParser->conn) . PHP_EOL;
                        return;
                    }
                    echo "  $name: " . $_lang['installed'] . PHP_EOL;
                }
            }
        }
    }
}

// Install Modules
$moduleModules = $mm;
if (is_array($moduleModules) || $installData) {
    echo PHP_EOL . $_lang['modules'] . ":" . PHP_EOL;
    //$selModules = $_POST['module'];
    foreach ($moduleModules as $k=>$moduleModule) {
        $installSample = in_array('sample', $moduleModule[7]) && $installData == 1;
        if($installSample || is_array($moduleModules)) {
            $name = mysqli_real_escape_string($conn, $moduleModule[0]);
            $desc = mysqli_real_escape_string($conn, $moduleModule[1]);
            $filecontent = $moduleModule[2];
            $properties = $moduleModule[3];
            $guid = mysqli_real_escape_string($conn, $moduleModule[4]);
            $shared = mysqli_real_escape_string($conn, $moduleModule[5]);
            $category = mysqli_real_escape_string($conn, $moduleModule[6]);
            if (!file_exists($filecontent))
                echo "  $name: " . $_lang['unable_install_module'] . " '$filecontent' " . $_lang['not_found'] . PHP_EOL;
            else {

                // Create the category if it does not already exist
                $category = getCreateDbCategory($category, $sqlParser);

                $module = end(preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent), 2));
                // $module = removeDocblock($module, 'module'); // Modules have no fileBinding, keep docblock for info-tab
                $module = mysqli_real_escape_string($conn, $module);
                $rs = mysqli_query($sqlParser->conn, "SELECT * FROM $dbase.`" . $table_prefix . "site_modules` WHERE name='$name'");
                if (mysqli_num_rows($rs)) {
                    $row = mysqli_fetch_assoc($rs);
                    $props = mysqli_real_escape_string($conn, propUpdate($properties,$row['properties']));
                    if (!mysqli_query($sqlParser->conn, "UPDATE $dbase.`" . $table_prefix . "site_modules` SET modulecode='$module', description='$desc', properties='$props', enable_sharedparams='$shared' WHERE name='$name';")) {
                        echo mysqli_error($sqlParser->conn) . PHP_EOL;
                        return;
                    }
                    echo "  $name: " . $_lang['upgraded'] . PHP_EOL;
                } else {
                    if ($properties != NULL ){
                        $properties = mysqli_real_escape_string($conn, parseProperties($properties, true));
                    }
                    if (!mysqli_query($sqlParser->conn, "INSERT INTO $dbase.`" . $table_prefix . "site_modules` (name,description,modulecode,properties,guid,enable_sharedparams,category) VALUES('$name','$desc','$module','$properties','$guid','$shared', $category);")) {
                        echo "<p>" . mysqli_error($sqlParser->conn) . "</p>";
                        return;
                    }
                    echo "  $name: " . $_lang['installed'] . PHP_EOL;
                }
            }
        }
    }
}

// Install Plugins
$modulePlugins = $mp;
if (is_array($modulePlugins) || $installData) {
    echo PHP_EOL . $_lang['plugins'] . ":" . PHP_EOL;
    $selPlugs = $_POST['plugin'];
    foreach ($modulePlugins as $k=>$modulePlugin) {
        //$installSample = in_array('sample', $modulePlugin[8]) && $installData == 1;
        if($installSample || is_array($modulePlugins)) {
            $name = mysqli_real_escape_string($conn, $modulePlugin[0]);
            $desc = mysqli_real_escape_string($conn, $modulePlugin[1]);
            $filecontent = $modulePlugin[2];
            $properties = $modulePlugin[3];
            $events = explode(",", $modulePlugin[4]);
            $guid = mysqli_real_escape_string($conn, $modulePlugin[5]);
            $category = mysqli_real_escape_string($conn, $modulePlugin[6]);
            $leg_names = '';
            $disabled = $modulePlugin[9];
            if(array_key_exists(7, $modulePlugin)) {
                // parse comma-separated legacy names and prepare them for sql IN clause
                $leg_names = "'" . implode("','", preg_split('/\s*,\s*/', mysqli_real_escape_string($conn, $modulePlugin[7]))) . "'";
            }
            if (!file_exists($filecontent))
                echo "  $name: " . $_lang['unable_install_plugin'] . " '$filecontent' " . $_lang['not_found'] . PHP_EOL;
            else {

                // disable legacy versions based on legacy_names provided
                if(!empty($leg_names)) {
                    $update_query = "UPDATE $dbase.`" . $table_prefix . "site_plugins` SET disabled='1' WHERE name IN ($leg_names);";
                    $rs = mysqli_query($sqlParser->conn, $update_query);
                }

                // Create the category if it does not already exist
                $category = getCreateDbCategory($category, $sqlParser);

                $plugin = end(preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent), 2));
                $plugin = removeDocblock($plugin, 'plugin');
                $plugin = mysqli_real_escape_string($conn, $plugin);
                $rs = mysqli_query($sqlParser->conn, "SELECT * FROM $dbase.`" . $table_prefix . "site_plugins` WHERE name='$name'");
                if (mysqli_num_rows($rs)) {
                    $insert = true;
                    while($row = mysqli_fetch_assoc($rs)) {
                        $props = mysqli_real_escape_string($conn, propUpdate($properties,$row['properties']));
                        if($row['description'] == $desc){
                            if (! mysqli_query($sqlParser->conn, "UPDATE $dbase.`" . $table_prefix . "site_plugins` SET plugincode='$plugin', description='$desc', properties='$props' WHERE id={$row['id']};")) {
                                echo mysqli_error($sqlParser->conn) . PHP_EOL;
                                return;
                            }
                            $insert = false;
                        } else {
                            if (!mysqli_query($sqlParser->conn, "UPDATE $dbase.`" . $table_prefix . "site_plugins` SET disabled='1' WHERE id={$row['id']};")) {
                                echo mysqli_error($sqlParser->conn).PHP_EOL;
                                return;
                            }
                        }
                    }
                    if($insert === true) {
                        $properties = mysqli_real_escape_string($conn, propUpdate($properties,$row['properties']));
                        if(!mysqli_query($sqlParser->conn, "INSERT INTO $dbase.`".$table_prefix."site_plugins` (name,description,plugincode,properties,moduleguid,disabled,category) VALUES('$name','$desc','$plugin','$properties','$guid','0',$category);")) {
                            echo mysqli_error($sqlParser->conn).PHP_EOL;
                            return;
                        }
                    }
                    echo "  $name: " . $_lang['upgraded'] . PHP_EOL;
                } else {
                    if ($properties != NULL ){
                        $properties = mysqli_real_escape_string($conn, parseProperties($properties, true));
                    }
                    if (!mysqli_query($sqlParser->conn, "INSERT INTO $dbase.`" . $table_prefix . "site_plugins` (name,description,plugincode,properties,moduleguid,category,disabled) VALUES('$name','$desc','$plugin','$properties','$guid',$category,$disabled);")) {
                        echo mysqli_error($sqlParser->conn) . PHP_EOL;
                        return;
                    }
                    echo "  $name: " . $_lang['installed'] . PHP_EOL;
                }
                // add system events
                if (count($events) > 0) {
                    $ds=mysqli_query($sqlParser->conn, "SELECT id FROM $dbase.`".$table_prefix."site_plugins` WHERE name='$name' AND description='$desc';");
                    if ($ds) {
                        $row = mysqli_fetch_assoc($ds);
                        $id = $row["id"];
                        $_events = implode("','", $events);
                        // add new events
                        mysqli_query($sqlParser->conn, "INSERT IGNORE INTO $dbase.`" . $table_prefix . "site_plugin_events` (pluginid, evtid, priority) SELECT '$id' as 'pluginid', se.id as 'evtid', IF(spe.priority IS NULL, 0, MAX(spe.priority) + 1) as 'priority' FROM $dbase.`" . $table_prefix . "system_eventnames` se LEFT JOIN $dbase.`" . $table_prefix . "site_plugin_events` spe ON spe.evtid = se.id WHERE name IN ('{$_events}') GROUP BY se.id");
                        // remove absent events
                        mysqli_query($sqlParser->conn, "DELETE `pe` FROM {$dbase}.`{$table_prefix}site_plugin_events` `pe` LEFT JOIN {$dbase}.`{$table_prefix}system_eventnames` `se` ON `pe`.`evtid`=`se`.`id` AND `name` IN ('{$_events}') WHERE ISNULL(`name`) AND `pluginid` = {$id}");
                    }
                }
            }
        }
    }
}

// Install Snippets
$moduleSnippet = $ms;
if (is_array($moduleSnippet) || $installData) {
    echo PHP_EOL . $_lang['snippets'] . ":" . PHP_EOL;
    //$selSnips = $_POST['snippet'];
    foreach ($moduleSnippets as $k=>$moduleSnippet) {
        $installSample = in_array('sample', $moduleSnippet[5]) && $installData == 1;
        if($installSample || is_array($moduleSnippet)) {
            $name = mysqli_real_escape_string($conn, $moduleSnippet[0]);
            $desc = mysqli_real_escape_string($conn, $moduleSnippet[1]);
            $filecontent = $moduleSnippet[2];
            $properties = $moduleSnippet[3];
            $category = mysqli_real_escape_string($conn, $moduleSnippet[4]);
            if (!file_exists($filecontent))
                echo "  $name: " . $_lang['unable_install_snippet'] . " '$filecontent' " . $_lang['not_found'] . PHP_EOL;
            else {

                // Create the category if it does not already exist
                $category = getCreateDbCategory($category, $sqlParser);

                $snippet = end(preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent)));
                $snippet = removeDocblock($snippet, 'snippet');
                $snippet = mysqli_real_escape_string($conn, $snippet);
                $rs = mysqli_query($sqlParser->conn, "SELECT * FROM $dbase.`" . $table_prefix . "site_snippets` WHERE name='$name'");
                if (mysqli_num_rows($rs)) {
                    $row = mysqli_fetch_assoc($rs);
                    $props = mysqli_real_escape_string($conn, propUpdate($properties,$row['properties']));
                    if (!mysqli_query($sqlParser->conn, "UPDATE $dbase.`" . $table_prefix . "site_snippets` SET snippet='$snippet', description='$desc', properties='$props' WHERE name='$name';")) {
                        echo mysqli_error($sqlParser->conn) . PHP_EOL;
                        return;
                    }
                    echo "  $name: " . $_lang['upgraded'] . PHP_EOL;
                } else {
                    if ($properties != NULL ){
                        $properties = mysqli_real_escape_string($conn, parseProperties($properties, true));
                    }
                    if (!mysqli_query($sqlParser->conn, "INSERT INTO $dbase.`" . $table_prefix . "site_snippets` (name,description,snippet,properties,category) VALUES('$name','$desc','$snippet','$properties',$category);")) {
                        echo mysqli_error($sqlParser->conn) . PHP_EOL;
                        return;
                    }
                    echo "  $name: " . $_lang['installed'] . PHP_EOL;
                }
            }
        }
    }
}

// Install demo-site
if ($installData && $moduleSQLDataFile) {
    echo PHP_EOL . $_lang['installing_demo_site'];
    $sqlParser->process($moduleSQLDataFile);
    // display database results
    if ($sqlParser->installFailed == true) {
        $errors += 1;
        echo $_lang['database_alerts'] . PHP_EOL;
        echo $_lang['setup_couldnt_install'] . PHP_EOL;
        echo $_lang['installation_error_occured'] . PHP_EOL . PHP_EOL;
        for ($i = 0; $i < count($sqlParser->mysqlErrors); $i++) {
            echo $sqlParser->mysqlErrors[$i]["error"] . " " . $_lang['during_execution_of_sql'] . " " . strip_tags($sqlParser->mysqlErrors[$i]["sql"]) . PHP_EOL;
        }

        echo $_lang['some_tables_not_updated'] . PHP_EOL;
        return;
    } else {
        $sql = sprintf("SELECT id FROM `%ssite_templates` WHERE templatename='EVO startup - Bootstrap'", $sqlParser->prefix);
        $rs = mysqli_query($sqlParser->conn, $sql);
        if(mysqli_num_rows($rs)) {
            $row = mysqli_fetch_assoc($rs);
            $sql = sprintf('UPDATE `%ssite_content` SET template=%s WHERE template=4', $sqlParser->prefix, $row['id']);
            mysqli_query($sqlParser->conn, $sql);
        }
        echo $_lang['ok'].PHP_EOL;
    }
}

// Install Dependencies
$moduleDependencies = $mdp;
foreach ($moduleDependencies as $dependency) {
    $ds = mysqli_query($sqlParser->conn, 'SELECT id, guid FROM ' . $dbase . '`' . $sqlParser->prefix . 'site_modules` WHERE name="' . $dependency['module'] . '"');
    if (!$ds) {
        echo mysqli_error($sqlParser->conn) . PHP_EOL;
        return;
    } else {
        $row = mysqli_fetch_assoc($ds);
        $moduleId = $row["id"];
        $moduleGuid = $row["guid"];
    }
    // get extra id
    $ds = mysqli_query($sqlParser->conn, 'SELECT id FROM ' . $dbase . '`' . $sqlParser->prefix . 'site_' . $dependency['table'] . '` WHERE ' . $dependency['column'] . '="' . $dependency['name'] . '"');
    if (!$ds) {
        echo mysqli_error($sqlParser->conn) . PHP_EOL;
        return;
    } else {
        $row = mysqli_fetch_assoc($ds);
        $extraId = $row["id"];
    }
    // setup extra as module dependency
    $ds = mysqli_query($sqlParser->conn, 'SELECT module FROM ' . $dbase . '`' . $sqlParser->prefix . 'site_module_depobj` WHERE module=' . $moduleId . ' AND resource=' . $extraId . ' AND type=' . $dependency['type'] . ' LIMIT 1');
    if (!$ds) {
        echo mysqli_error($sqlParser->conn) . PHP_EOL;
        return;
    } else {
        if (mysqli_num_rows($ds) === 0) {
            mysqli_query($sqlParser->conn, 'INSERT INTO ' . $dbase . '`' . $sqlParser->prefix . 'site_module_depobj` (module, resource, type) VALUES(' . $moduleId . ',' . $extraId . ',' . $dependency['type'] . ')');
            echo $dependency['module'] . ' Module: ' . $_lang['depedency_create'] . PHP_EOL;
        } else {
            mysqli_query($sqlParser->conn, 'UPDATE ' . $dbase . '`' . $sqlParser->prefix . 'site_module_depobj` SET module = ' . $moduleId . ', resource = ' . $extraId . ', type = ' . $dependency['type'] . ' WHERE module=' . $moduleId . ' AND resource=' . $extraId . ' AND type=' . $dependency['type']);
            echo $dependency['module'] . ' Module: ' . $_lang['depedency_update'] . PHP_EOL;
        }
        if ($dependency['type'] == 30 || $dependency['type'] == 40) {
            // set extra guid for plugins and snippets
            $ds = mysqli_query($sqlParser->conn, 'SELECT id FROM ' . $dbase . '`' . $sqlParser->prefix . 'site_' . $dependency['table'] . '` WHERE id=' . $extraId . ' LIMIT 1');
            if (!$ds) {
                echo mysqli_error($sqlParser->conn) . PHP_EOL;
                return;
            } else {
                if (mysqli_num_rows($ds) != 0) {
                    mysqli_query($sqlParser->conn, 'UPDATE ' . $dbase . '`' . $sqlParser->prefix . 'site_' . $dependency['table'] . '` SET moduleguid = ' . $moduleGuid . ' WHERE id=' . $extraId);
                    echo $dependency['name'] . ': ' . $_lang['guid_set'] . PHP_EOL;
                }
            }
        }
    }
}

// call back function
if ($callBackFnc != "")
    $callBackFnc ($sqlParser);

// Setup the MODX API -- needed for the cache processor
if (!defined('MODX_MANAGER_PATH')) define('MODX_MANAGER_PATH', $base_path.MGR_DIR.'/');
$database_type = 'mysqli';
// initiate a new document parser
include_once($path.'../'.MGR_DIR.'/includes/document.parser.class.inc.php');
$modx = new DocumentParser;
$modx->db->connect();
// always empty cache after install
include_once $path."../".MGR_DIR."/processors/cache_sync.class.processor.php";
$sync = new synccache();
$sync->setCachepath($path."../assets/cache/");
$sync->setReport(false);
$sync->emptyCache(); // first empty the cache

// try to chmod the cache go-rwx (for suexeced php)
$chmodSuccess = @chmod($path.'../assets/cache/siteCache.idx.php', 0600);
$chmodSuccess = @chmod($path.'../assets/cache/sitePublishing.idx.php', 0600);

// remove any locks on the manager functions so initial manager login is not blocked
mysqli_query($conn, "TRUNCATE TABLE `".$table_prefix."active_users`");

// close db connection
$sqlParser->close();

// andrazk 20070416 - release manager access
if (file_exists($path.'../assets/cache/installProc.inc.php')) {
    @chmod($path.'../assets/cache/installProc.inc.php', 0755);
    unlink($path.'../assets/cache/installProc.inc.php');
}

// setup completed!
echo PHP_EOL . $_lang['installation_successful'] . PHP_EOL . PHP_EOL;
//echo "<p>" . $_lang['to_log_into_content_manager'] . "</p>";
if ($installMode == 0) {
   echo strip_tags($_lang['installation_note']) . PHP_EOL;
} else {
   echo strip_tags($_lang['upgrade_note']) . PHP_EOL;
}


if ( empty($args) ){
    echo PHP_EOL . 'Remove install folder?'.PHP_EOL;
    $removeInstall = readline("Type 'y' or 'n' to continue: ");
}
//remove installFolder
if ($removeInstall == 'y') {
    removeFolder($path);
    removeFolder($base_path.'.tx');
    unlink($base_path.'README.md');
    echo 'Install folder deleted!'. PHP_EOL . PHP_EOL;
}

/**
 * RemoveFolder
 *
 * @param string $path
 * @return string
 */
function removeFolder($path)
{
    $dir = realpath($path);
    if (!is_dir($dir)) {
        return;
    }

    $it    = new RecursiveDirectoryIterator($dir);
    $files = new RecursiveIteratorIterator($it,
        RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        if ($file->getFilename() === "." || $file->getFilename() === "..") {
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
 * Property Update function
 *
 * @param string $new
 * @param string $old
 * @return string
 */
function propUpdate($new,$old){
    $newArr = parseProperties($new);
    $oldArr = parseProperties($old);
    foreach ($oldArr as $k => $v){
        if (isset($v['0']['options'])){
            $oldArr[$k]['0']['options'] = $newArr[$k]['0']['options'];
        }
    }
    $return = $oldArr + $newArr;
    $return = json_encode($return, JSON_UNESCAPED_UNICODE);
    $return = ($return !== '[]') ? $return : '';
    return $return;
}

/**
 * @param string $propertyString
 * @param bool|mixed $json
 * @return string
 */
function parseProperties($propertyString, $json=false) {
    $propertyString = str_replace('{}', '', $propertyString );
    $propertyString = str_replace('} {', ',', $propertyString );

    if(empty($propertyString)) return array();
    if($propertyString=='{}' || $propertyString=='[]') return array();

    $jsonFormat = isJson($propertyString, true);
    $property = array();
    // old format
    if ( $jsonFormat === false) {
        $props= explode('&', $propertyString);
        foreach ($props as $prop) {
            $prop = trim($prop);
            if($prop === '') {
                continue;
            }

            $arr = explode(';', $prop);
            if( ! is_array($arr)) {
                $arr = array();
            }
            $key = explode('=', isset($arr[0]) ? $arr[0] : '');
            if( ! is_array($key) || empty($key[0])) {
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
    } else if(!empty($jsonFormat)){
        $property = $jsonFormat;
    }
    if ($json) {
        $property = json_encode($property, JSON_UNESCAPED_UNICODE);
    }
    $property = ($property !== '[]') ? $property : '';
    return $property;
}

/**
 * @param string $string
 * @param bool $returnData
 * @return bool|mixed
 */
function isJson($string, $returnData=false) {
    $data = json_decode($string, true);
    return (json_last_error() == JSON_ERROR_NONE) ? ($returnData ? $data : true) : false;
}

/**
 * @param string|int $category
 * @param SqlParser $sqlParser
 * @return int
 */
function getCreateDbCategory($category, $sqlParser) {
    $dbase = $sqlParser->dbname;
    $dbase = '`' . trim($dbase,'`') . '`';
    $table_prefix = $sqlParser->prefix;
    $category_id = 0;
    if(!empty($category)) {
        $category = mysqli_real_escape_string($sqlParser->conn, $category);
        $rs = mysqli_query($sqlParser->conn, "SELECT id FROM $dbase.`".$table_prefix."categories` WHERE category = '".$category."'");
        if(mysqli_num_rows($rs) && ($row = mysqli_fetch_assoc($rs))) {
            $category_id = $row['id'];
        } else {
            $q = "INSERT INTO $dbase.`".$table_prefix."categories` (`category`) VALUES ('{$category}');";
            $rs = mysqli_query($sqlParser->conn, $q);
            if($rs) {
                $category_id = mysqli_insert_id($sqlParser->conn);
            }
        }
    }
    return $category_id;
}

/**
 * Remove installer Docblock only from components using plugin FileSource / fileBinding
 *
 * @param string $code
 * @param string $type
 * @return string
 */
function removeDocblock($code, $type) {

    $cleaned = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', $code, 1);

    // Procedure taken from plugin.filesource.php
    switch($type) {
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
    if(substr(trim($cleaned),0,$count) == $include.' MODX_BASE_PATH.\'assets/'.$elm_name.'/')
        return $cleaned;

    // fileBinding not found - return code incl docblock
    return $code;
}
