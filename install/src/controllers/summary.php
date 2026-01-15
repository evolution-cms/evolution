<?php
if (!function_exists('f_owc')) {
    /**
     * @param $path
     * @param $data
     * @param null|int $mode
     */
    function f_owc($path, $data, $mode = null)
    {
        try {
            // make an attempt to create the file
            $hnd = fopen($path, 'w');
            fwrite($hnd, $data);
            fclose($hnd);

            if (!is_null($mode)) {
                @chmod($path, $mode);
            }
        } catch (Exception $e) {
            // Nothing, this is NOT normal
            unset($e);
        }
    }
}

$installMode = isset($_POST['installmode']) ? (int)$_POST['installmode'] : 0;
if (!isset($_lang)) {
    $_lang = [];
}

echo '<div class="stepcontainer">
      <ul class="progressbar">
          <li class="visited">' . $_lang['choose_language'] . '</li>
          <li class="visited">' . $_lang['installation_mode'] . '</li>
          <li class="visited">' . $_lang['optional_items'] . '</li>
          <li class="active">' . $_lang['preinstall_validation'] . '</li>
          <li>' . $_lang['install_results'] . '</li>
  </ul>
  <div class="clearleft"></div>
</div>';

echo '<h2>' . $_lang['preinstall_validation'] . '</h2>';
echo '<h3>' . $_lang['summary_setup_check'] . '</h3>';

$errors = 0;

// check PHP version
define('PHP_MIN_VERSION', '8.3.0');
$phpMinVersion = PHP_MIN_VERSION; // Maybe not necessary. For backward compatibility
echo '<p>' . $_lang['checking_php_version'];
// -1 if left is less, 0 if equal, +1 if left is higher
if (version_compare(phpversion(), PHP_MIN_VERSION) < 0) {
    $errors++;
    $tmp = $_lang['you_running_php'] . phpversion() . str_replace('[+min_version+]', PHP_MIN_VERSION, $_lang["modx_requires_php"]);
    echo '<span class="notok">' . $_lang['failed'] . '</span>' . $tmp . '</p>';
} else {
    echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
}

$required_extensions = [
    'ctype' => false,
    'dom' => true,
    'fileinfo' => true,
    'filter' => true,
    'hash' => true,
    'json' => true,
    'libxml' => true,
    'mbstring' => false,
    'openssl' => true,
    'pcre' => true,
    'reflection' => false, // optional composer requires justinrainbow/json-schema -> marc-mabe/php-enum -> ext-reflection
    'session' => true,
    'simplexml' => false, // broken updater functionality
    'tokenizer' => true, // Laravel requirement without polyfill
    'xml' => true,
    'xmlreader' => true,
    'zip' => true, // ZipArchive requirement. ext-zip is no longer bundled with PHP7.4+ so must be installed and enabled
];

$loaded_extensions = get_loaded_extensions();

foreach ($required_extensions as $ext_name => $is_mandatory) {
    echo '<p>' . str_replace('[+extensions+]', $ext_name, $_lang['checking_extensions']);
    if (!in_array($ext_name, $loaded_extensions)) {
        if ($is_mandatory) {
            echo '<span class="notok">' . $_lang['failed'] . '</span></p>';
            $errors++;
        } else {
            echo '<span class="ok">' . $_lang['not_found'] . '</span></p>';
        }
        echo '<p><strong>' . str_replace('[+missing_extension+]', $ext_name,
            $is_mandatory ? $_lang['missing_mandatory_extension'] : $_lang['missing_recommended_extension']
        ) . '</strong></p>';
    } else {
        echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
    }
}

// check sessions
echo '<p>' . $_lang['checking_sessions'];
if ($_SESSION['test'] != 1) {
    echo '<span class="notok">' . $_lang['failed'] . '</span></p>';
    $errors++;
} else {
    echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
}

// cache writable?
echo '<p>' . $_lang['checking_if_cache_writable'];
if (!is_writable("../assets/cache")) {
    $errors++;
    echo '<span class="notok">' . $_lang['failed'] . '</span></p>';
} else {
    echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
}

// cache files writable?
echo '<p>' . $_lang['checking_if_cache_file_writable'];
$tmp = "../assets/cache/siteCache.idx.php";
if (!file_exists($tmp)) {
    f_owc($tmp, "<?php //EVO site cache file ?>");
}
if (!is_writable($tmp)) {
    $errors++;
    echo '<span class="notok">' . $_lang['failed'] . '</span></p>';
} else {
    echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
}

// File Browser directories exists?
echo '<p>' . $_lang['checking_if_images_exist'];
switch (true) {
    case !file_exists("../assets/images"):
    case !file_exists("../assets/files"):
    case !file_exists("../assets/backup"):
    case !file_exists("../assets/.thumbs"):
        $errors++;
        echo '<span class="notok">' . $_lang['failed'] . '</span></p>';
        break;
    default:
        echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
}

// File Browser directories writable?
echo '<p>' . $_lang['checking_if_images_writable'];
switch (true) {
    case !is_writable("../assets/images"):
    case !is_writable("../assets/files"):
    case !is_writable("../assets/backup"):
    case !is_writable("../assets/.thumbs"):
        $errors++;
        echo '<span class="notok">' . $_lang['failed'] . '</span></p>';
        break;
    default:
        echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
}

// export exists?
echo '<p>' . $_lang['checking_if_export_exists'];
if (!file_exists("../assets/export")) {
    echo '<span class="notok">' . $_lang['failed'] . '</span></p>';
    $errors++;
} else {
    echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
}

// export writable?
echo '<p>' . $_lang['checking_if_export_writable'];
if (!is_writable("../assets/export")) {
    echo '<span class="notok">' . $_lang['failed'] . '</span></p>';
    $errors++;
} else {
    echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
}

// config.inc.php writable?
echo '<p>' . $_lang['checking_if_config_exist_and_writable'];
$tmp = '../' . MGR_DIR . '/includes/config.inc.php';
if (!is_file($tmp)) {
    f_owc($tmp, "<?php //EVO configuration file ?>", 0666);
} else {
    @chmod($tmp, 0666);
}
$isWriteable = is_writable($tmp);
if (!$isWriteable) {
    $errors++;
    echo '<span class="notok">' . $_lang['failed'] . '</span></p><p><strong>' . $_lang['config_permissions_note'] . '</strong></p>';
} else {
    echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
}

// connect to the database
if ($installMode == 1) {
    $db_config = include_once EVO_CORE_PATH . 'config/database/connections/default.php';
    $database_server = $db_config['host'];
    $database_user = $db_config['username'];
    $database_password = $db_config['password'];
    $database_collation = $db_config['collation'];
    $database_charset = substr($database_collation, 0, strpos($database_collation, '_') - 1);
    $database_connection_charset = $db_config['charset'];
    $database_connection_method = $db_config['method'];
    $dbase = $db_config['database'];
    $table_prefix = $db_config['prefix'];
    $database_type = $db_config['driver'];
} else {
    // get db info from post
    $database_type = strip_tags($_POST['database_type']);
    $database_server = strip_tags($_POST['databasehost']);
    $database_user = $_SESSION['databaseloginname'];
    $database_password = $_SESSION['databaseloginpassword'];
    $database_collation = strip_tags($_POST['database_collation']);
    $database_charset = substr($database_collation, 0, strpos($database_collation, '_') - 1);
    $database_connection_charset = $_POST['database_connection_charset'];
    $database_connection_method = $_POST['database_connection_method'];
    if ($database_type === 'sqlite') {
        $database_name = strip_tags($_POST['database_name']); // TODO: replace strip_tags with validation everywhere
        $dbase = EVO_CORE_PATH . "database/$database_name.sqlite";
    } else {
        $dbase = '`' . strip_tags($_POST['database_name']) . '`';
    }
    $table_prefix = strip_tags($_POST['tableprefix']);
}
echo '<p>' . $_lang['creating_database_connection'];
$host = explode(':', $database_server, 2);
$pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
try {
    $dbh = null;
    if ($database_type === 'sqlite') {
        $dbh = new PDO('sqlite:' . $dbase, null, null, $pdoOptions);
    } else {
        $dbh = new PDO($database_type . ':host=' . $database_server . ';dbname=' . $_POST['database_name'], $database_user, $database_password, $pdoOptions);
    }
    echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
} catch (PDOException $e) {
    $errors++;
    echo '<span class="notok">' . $_lang['database_connection_failed'] . '</span><p />' . $_lang['database_connection_failed_note'] . $e->getMessage() . '</p>';
    echo '<span class="notok">' . $_lang['database_use_failed'] . '</span><p />' . $_lang['database_use_failed_note'] . '</p>';
}

// check the database collation if not specified in the configuration
if ($dbh && $database_type === 'mysql' && empty ($database_connection_charset)) {
    $rs = null;
    try {
        $rs = $dbh->query("SHOW SESSION VARIABLES LIKE 'collation_database'");
    } catch (PDOException $e) {
        // try another way
    }
    if (!$rs) {
        try {
            $rs = $dbh->query("SHOW SESSION VARIABLES LIKE 'collation_server'");
        } catch (PDOException $e) {
            // it is error now
        }
    }
    if (!$rs) {
        $errors++;
    } else {
        try {
            if ($collation = $rs->fetch(PDO::FETCH_NUM)) {
                $database_collation = $collation[1];
            }
        } catch (PDOException $e) {
            // Use default collation if query fails
        }
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
if ($dbh && $errors === 0) {
    echo '<p>' . $_lang['checking_table_prefix'] . $table_prefix . '`: ';

    try {
        $dbh->query("SELECT COUNT(*) FROM {$table_prefix}site_content");
        $tableExists = true;
    } catch (\PDOException $exception) {
        $tableExists = false;
    }
    $isValid = ($installMode === 0) ? !$tableExists : $tableExists;
    if ($isValid) {
        echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
    } else {
        echo '<span class="notok">' . $_lang['failed'] . '</span></b>';
        if ($installMode == 0) {
            echo $_lang['table_prefix_already_inuse'] . '</p>';
            echo '<p>' . $_lang['table_prefix_already_inuse_note'] . '</p>';
        } else {
            echo $_lang['table_prefix_not_exist'] . '</p>';
            echo '<p>' . $_lang['table_prefix_not_exist_note'] . '</p>';
        }
        $errors++;
    }
}

if (is_writable("../assets/cache")) {
    if (file_exists('../assets/cache/installProc.inc.php')) {
        @chmod('../assets/cache/installProc.inc.php', 0755);
        unlink('../assets/cache/installProc.inc.php');
    }
    f_owc("../assets/cache/installProc.inc.php", '<?php $installStartTime = ' . time() . '; ?>');
}

if ($installMode > 0 && isset($_POST['installdata']) && $_POST['installdata'] == "1") {
    echo '<p class="notes"><strong>' . $_lang['sample_web_site'] . ':</strong> ' . $_lang['sample_web_site_note'] . '</p>';
}

if ($errors > 0) {
    echo '<p>';
    echo $_lang['setup_cannot_continue'] . ' ';

    if ($errors > 1) {
        echo $errors . " " . $_lang['errors'] . $_lang['please_correct_errors'] . $_lang['and_try_again_plural'];
    } else {
        echo $_lang['error'] . $_lang['please_correct_error'] . $_lang['and_try_again'];
    }
    echo str_replace('[+support_forum_link_tag+]',
        '<a href="' . $_lang['help_link'] . '" target="_blank">' . $_lang['help_title'] . '</a>', $_lang['visit_forum']);
    echo '</p>';
}

echo '<p>&nbsp;</p>';

$nextAction = $errors > 0 ? 'summary' : 'install';
$nextButton = $errors > 0 ? $_lang['retry'] : $_lang['install'];
$nextVisibility = $errors > 0 || isset($_POST['chkagree']) ? '' : 'hidden';
$agreeToggle = $errors > 0 ? 'disabled' : '';
?>
<form name="install" id="install_form" action="index.php?action=<?php echo $nextAction ?>" method="post">
    <div>
        <input type="hidden" value="<?php echo $install_language ?>" name="language"/>
        <input type="hidden" value="<?php echo $manager_language ?>" name="managerlanguage"/>
        <input type="hidden" value="<?php echo $installMode ?>" name="installmode"/>
        <input type="hidden" value="<?php echo trim($dbase, '`'); ?>" name="database_name"/>
        <input type="hidden" value="<?php echo $database_type ?>" name="database_type"/>
        <input type="hidden" value="<?php echo $table_prefix ?>" name="tableprefix"/>
        <input type="hidden" value="<?php echo $database_collation ?>" name="database_collation"/>
        <input type="hidden" value="<?php echo $database_connection_charset ?>" name="database_connection_charset"/>
        <input type="hidden" value="<?php echo $database_connection_method ?>" name="database_connection_method"/>
        <input type="hidden" value="<?php echo $database_server ?>" name="databasehost"/>
        <input type="hidden" value="<?php echo strip_tags($_POST['cmsadmin']) ?>" name="cmsadmin"/>
        <input type="hidden" value="<?php echo strip_tags($_POST['cmsadminemail']) ?>" name="cmsadminemail"/>
        <input type="hidden" value="<?php echo strip_tags($_POST['cmspassword']) ?>" name="cmspassword"/>
        <input type="hidden" value="<?php echo strip_tags($_POST['cmspasswordconfirm']) ?>" name="cmspasswordconfirm"/>
        <input type="hidden" value="1" name="options_selected"/>
        <input type="hidden" value="<?php echo $_POST['installdata'] ?? '' ?>" name="installdata"/>
        <?php
        $templates = isset ($_POST['template']) ? $_POST['template'] : [];
        foreach ($templates as $i => $template) echo '<input type="hidden" name="template[]" value="' . $template . '" />';

        $tvs = isset ($_POST['tv']) ? $_POST['tv'] : [];
        foreach ($tvs as $i => $tv) echo '<input type="hidden" name="tv[]" value="' . $tv . '" />';

        $chunks = isset ($_POST['chunk']) ? $_POST['chunk'] : [];
        foreach ($chunks as $i => $chunk) echo '<input type="hidden" name="chunk[]" value="' . $chunk . '" />';

        $snippets = isset ($_POST['snippet']) ? $_POST['snippet'] : [];
        foreach ($snippets as $i => $snippet) echo '<input type="hidden" name="snippet[]" value="' . $snippet . '" />';

        $plugins = isset ($_POST['plugin']) ? $_POST['plugin'] : [];
        foreach ($plugins as $i => $plugin) echo '<input type="hidden" name="plugin[]" value="' . $plugin . '" />';

        $modules = isset ($_POST['module']) ? $_POST['module'] : [];
        foreach ($modules as $i => $module) echo '<input type="hidden" name="module[]" value="' . $module . '" />';
        ?>
    </div>
    <h2><?php echo $_lang['agree_to_terms']; ?></h2>
    <p class="agreeHolder">
        <input type="checkbox" value="1" id="chkagree" name="chkagree"
            <?php echo isset($_POST['chkagree']) ? 'checked="checked" ' : ''; ?><?php echo $agreeToggle; ?>/>
        <label for="chkagree"> <?php echo str_replace(['[+license_file_link_tag+]','[+license_link_tag+]'],
                ['<a href="../assets/docs/license.txt" target="_blank">Evolution CMS license (GNU GPL v3)</a>',
                    '<a href="https://www.gnu.org/licenses/translations.html" target="_blank">GNU.org</a>,
                     <a href="https://wikipedia.org/wiki/GNU_General_Public_License" target="_blank">Wikipedia</a>'],
                $_lang['iagree_box']) ?> </label>
    </p>
    <p class="buttonlinks">
        <a class="prev" title="<?php echo $_lang['btnback_value'] ?>"><span><?php echo $_lang['btnback_value'] ?></span></a>
        <a id="nextbutton" title="<?php echo $nextButton ?>" <?php echo $nextVisibility; ?>><span><?php echo $nextButton ?></span></a>
    </p>
    <script type="text/javascript" nonce="<?=csrfNonce()?>">
      document.querySelector('.buttonlinks .prev').onclick = () => {
        document.getElementById('install_form').action='index.php?action=options&language=<?php echo $install_language ?>';
        document.getElementById('install_form').submit();
      }
      document.querySelector('.buttonlinks #nextbutton').onclick = () => {
        document.getElementById('install_form').submit();
      }
      document.querySelector('#chkagree').onclick = () => {
        if (document.getElementById('chkagree').checked) {
          document.getElementById('nextbutton').removeAttribute('hidden')
        } else{
          document.getElementById('nextbutton').setAttribute('hidden', 'hidden');
        }
      }
    </script>
</form>
