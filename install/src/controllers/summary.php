<?php
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

$installMode = isset($_POST['installmode']) ? (int)$_POST['installmode'] : 0;
if( ! isset($_lang)) $_lang = array();

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
define('PHP_MIN_VERSION', '5.4.0');
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


// check if iconv is available
echo '<p>' . $_lang['checking_iconv'];
$iconv = (int) function_exists('iconv');
if ($iconv == '0'){
    echo '<span class="notok">' . $_lang['failed'].'</span></p><p><strong>'.$_lang['checking_iconv_note'].'</strong></p>';
    $errors++;
} else {
    echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
}
// check sessions
echo '<p>' . $_lang['checking_sessions'];
if ($_SESSION['test'] != 1) {
    echo '<span class="notok">' . $_lang['failed'].  '</span></p>';
    $errors++;
} else {
    echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
}


// check directories
// cache exists?
echo '<p>' . $_lang['checking_if_cache_exist'];
if (!file_exists("../assets/cache") || !file_exists("../assets/cache/rss")) {
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
if ( ! file_exists($tmp)) {
    f_owc($tmp, "<?php //EVO site cache file ?>");
}
if ( ! is_writable($tmp)) {
    $errors++;
    echo '<span class="notok">' . $_lang['failed'] . '</span></p>';
} else {
    echo '<span class="ok">'.$_lang['ok'].'</span></p>';
}


echo '<p>'.$_lang['checking_if_cache_file2_writable'];
if ( ! is_writable("../assets/cache/sitePublishing.idx.php")) {
    $errors++;
    echo '<span class="notok">'.$_lang['failed'].'</span></p>';
} else {
    echo '<span class="ok">'.$_lang['ok'].'</span></p>';
}


// File Browser directories exists?
echo '<p>'.$_lang['checking_if_images_exist'];
switch(true){
    case !file_exists("../assets/images"):
    case !file_exists("../assets/files"):
    case !file_exists("../assets/backup"):
    case !file_exists("../assets/.thumbs"):
        $errors++;
        echo '<span class="notok">'.$_lang['failed'].'</span></p>';
        break;
    default:
        echo '<span class="ok">'.$_lang['ok'].'</span></p>';
}


// File Browser directories writable?
echo '<p>'.$_lang['checking_if_images_writable'];
switch(true){
    case !is_writable("../assets/images"):
    case !is_writable("../assets/files"):
    case !is_writable("../assets/backup"):
    case !is_writable("../assets/.thumbs"):
        $errors++;
        echo '<span class="notok">'.$_lang['failed'].'</span></p>';
        break;
    default:
        echo '<span class="ok">'.$_lang['ok'].'</span></p>';
}


// export exists?
echo '<p>'.$_lang['checking_if_export_exists'];
if (!file_exists("../assets/export")) {
    echo '<span class="notok">'.$_lang['failed'].'</span></p>';
    $errors++;
} else {
    echo '<span class="ok">'.$_lang['ok'].'</span></p>';
}


// export writable?
echo '<p>'.$_lang['checking_if_export_writable'];
if (!is_writable("../assets/export")) {
    echo '<span class="notok">'.$_lang['failed'].'</span></p>';
    $errors++;
} else {
    echo '<span class="ok">'.$_lang['ok'].'</span></p>';
}


// config.inc.php writable?
echo '<p>'.$_lang['checking_if_config_exist_and_writable'];
$tmp = "../".MGR_DIR."/includes/config.inc.php";
if (!is_file($tmp)) {
    f_owc($tmp, "<?php //EVO configuration file ?>", 0666);
} else {
    @chmod($tmp, 0666);
}
$isWriteable = is_writable($tmp);
if (!$isWriteable) {
    $errors++;
    echo '<span class="notok">'.$_lang['failed'].'</span></p><p><strong>'.$_lang['config_permissions_note'].'</strong></p>';
} else {
    echo '<span class="ok">'.$_lang['ok'].'</span></p>';
}


// connect to the database
if ($installMode == 1) {
    include "../".MGR_DIR."/includes/config.inc.php";
} else {
    // get db info from post
    $database_server = $_POST['databasehost'];
    $database_user = $_SESSION['databaseloginname'];
    $database_password = $_SESSION['databaseloginpassword'];
    $database_collation = $_POST['database_collation'];
    $database_charset = substr($database_collation, 0, strpos($database_collation, '_') - 1);
    $database_connection_charset = $_POST['database_connection_charset'];
    $database_connection_method = $_POST['database_connection_method'];
    $dbase = '`' . $_POST['database_name'] . '`';
    $table_prefix = $_POST['tableprefix'];
}
echo '<p>'.$_lang['creating_database_connection'];
if (!$conn = mysqli_connect($database_server, $database_user, $database_password)) {
    $errors++;
    echo '<span class="notok">'.$_lang['database_connection_failed'].'</span><p />'.$_lang['database_connection_failed_note'].'</p>';
} else {
    echo '<span class="ok">'.$_lang['ok'].'</span></p>';
}


// make sure we can use the database
if ($installMode > 0 && !mysqli_query($conn, "USE {$dbase}")) {
    $errors++;
    echo '<span class="notok">'.$_lang['database_use_failed'].'</span><p />'.$_lang["database_use_failed_note"].'</p>';
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
    echo '<p>' . $_lang['checking_table_prefix'] . $table_prefix . '`: ';
    if ($rs= mysqli_query($conn, "SELECT COUNT(*) FROM $dbase.`" . $table_prefix . "site_content`")) {
        echo '<span class="notok">' . $_lang['failed'] . '</span></b>' . $_lang['table_prefix_already_inuse'] . '</p>';
        $errors++;
        echo "<p>" . $_lang['table_prefix_already_inuse_note'] . '</p>';
    } else {
        echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
    }
} elseif ($conn && $installMode == 2) {
    echo '<p>' . $_lang['checking_table_prefix'] . $table_prefix . '`: ';
    if (!$rs = mysqli_query($conn, "SELECT COUNT(*) FROM $dbase.`" . $table_prefix . "site_content`")) {
        echo '<span class="notok">' . $_lang['failed'] . '</span></b>' . $_lang['table_prefix_not_exist'] . '</p>';
        $errors++;
        echo '<p>' . $_lang['table_prefix_not_exist_note'] . '</p>';
  } else {
        echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
  }
}

// check mysql version
if ($conn) {
    echo '<p>' . $_lang['checking_mysql_version'];
    if ( version_compare(mysqli_get_server_info($conn), '5.0.51', '=') ) {
        echo '<span class="notok">'  . $_lang['warning'] . '</span></b>&nbsp;&nbsp;<strong>' . $_lang['mysql_5051'] . '</strong></p>';
        echo '<p><span class="notok">' . $_lang['mysql_5051_warning'] . '</span></p>';
    } else {
        echo '<span class="ok">' . $_lang['ok'] . '</span>&nbsp;&nbsp;<strong>' . $_lang['mysql_version_is'] . mysqli_get_server_info($conn) . '</strong></p>';
    }
}

// check for strict mode
if ($conn) {
    echo '<p>'. $_lang['checking_mysql_strict_mode'];
    $mysqlmode = mysqli_query($conn, "SELECT @@global.sql_mode");
    if (mysqli_num_rows($mysqlmode) > 0){
        $modes = mysqli_fetch_array($mysqlmode, MYSQLI_NUM);
        //$modes = array("STRICT_TRANS_TABLES"); // for testing
        // print_r($modes);
        foreach ($modes as $mode) {
            if (stristr($mode, "STRICT_TRANS_TABLES") !== false || stristr($mode, "STRICT_ALL_TABLES") !== false) {
                echo '<span class="notok">' . $_lang['warning'] . '</span></b> <strong>&nbsp;&nbsp;' . $_lang['strict_mode'] . '</strong></p>';
                echo '<p><span class="notok">' . $_lang['strict_mode_error'] . '</span></p>';
            } else {
                echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
            }
        }
    } else {
        echo '<span class="ok">' . $_lang['ok'] . '</span></p>';
    }
}
// Version and strict mode check end

// andrazk 20070416 - add install flag and disable manager login
// assets/cache writable?
if (is_writable("../assets/cache")) {
    if (file_exists('../assets/cache/installProc.inc.php')) {
        @chmod('../assets/cache/installProc.inc.php', 0755);
        unlink('../assets/cache/installProc.inc.php');
    }

    f_owc("../assets/cache/installProc.inc.php", '<?php $installStartTime = '.time().'; ?>');
}

if($installMode > 0 && $_POST['installdata'] == "1") {
    echo '<p class="notes"><strong>' . $_lang['sample_web_site'] . ':</strong> ' . $_lang['sample_web_site_note'] . '</p>';
}

if ($errors > 0) {
    echo '<p>';
    echo $_lang['setup_cannot_continue'] . ' ';

    if($errors > 1){
        echo $errors . " " . $_lang['errors'] . $_lang['please_correct_errors'] . $_lang['and_try_again_plural'];
    }else{
        echo $_lang['error'] . $_lang['please_correct_error'] . $_lang['and_try_again'];
    }

    echo $_lang['visit_forum'];
    echo '</p>';
}

echo '<p>&nbsp;</p>';

$nextAction= $errors > 0 ? 'summary' : 'install';
$nextButton= $errors > 0 ? $_lang['retry'] : $_lang['install'];
$nextVisibility= $errors > 0 || isset($_POST['chkagree']) ? 'visible' : 'hidden';
$agreeToggle= $errors > 0 ? '' : ' onclick="if(document.getElementById(\'chkagree\').checked){document.getElementById(\'nextbutton\').style.visibility=\'visible\';}else{document.getElementById(\'nextbutton\').style.visibility=\'hidden\';}"';
?>
<form name="install" id="install_form" action="index.php?action=<?php echo $nextAction ?>" method="post">
  <div>
    <input type="hidden" value="<?php echo $install_language?>" name="language" />
    <input type="hidden" value="<?php echo $manager_language?>" name="managerlanguage" />
    <input type="hidden" value="<?php echo $installMode ?>" name="installmode" />
    <input type="hidden" value="<?php echo trim($_POST['database_name'], '`'); ?>" name="database_name" />
    <input type="hidden" value="<?php echo $_POST['tableprefix'] ?>" name="tableprefix" />
    <input type="hidden" value="<?php echo $_POST['database_collation'] ?>" name="database_collation" />
    <input type="hidden" value="<?php echo $_POST['database_connection_charset'] ?>" name="database_connection_charset" />
    <input type="hidden" value="<?php echo $_POST['database_connection_method'] ?>" name="database_connection_method" />
    <input type="hidden" value="<?php echo $_POST['databasehost'] ?>" name="databasehost" />
    <input type="hidden" value="<?php echo $_POST['cmsadmin'] ?>" name="cmsadmin" />
    <input type="hidden" value="<?php echo $_POST['cmsadminemail'] ?>" name="cmsadminemail" />
    <input type="hidden" value="<?php echo $_POST['cmspassword'] ?>" name="cmspassword" />
    <input type="hidden" value="<?php echo $_POST['cmspasswordconfirm'] ?>" name="cmspasswordconfirm" />

    <input type="hidden" value="1" name="options_selected" />

    <input type="hidden" value="<?php echo $_POST['installdata'] ?>" name="installdata" />
<?php
    $templates = isset ($_POST['template']) ? $_POST['template'] : array ();
    foreach ($templates as $i => $template) echo '<input type="hidden" name="template[]" value="'.$template.'" />';

    $tvs = isset ($_POST['tv']) ? $_POST['tv'] : array ();
    foreach ($tvs as $i => $tv) echo '<input type="hidden" name="tv[]" value="'.$tv.'" />';

    $chunks = isset ($_POST['chunk']) ? $_POST['chunk'] : array ();
    foreach ($chunks as $i => $chunk) echo '<input type="hidden" name="chunk[]" value="'.$chunk.'" />';

    $snippets = isset ($_POST['snippet']) ? $_POST['snippet'] : array ();
    foreach ($snippets as $i => $snippet) echo '<input type="hidden" name="snippet[]" value="'.$snippet.'" />';

    $plugins = isset ($_POST['plugin']) ? $_POST['plugin'] : array ();
    foreach ($plugins as $i => $plugin) echo '<input type="hidden" name="plugin[]" value="'.$plugin.'" />';

    $modules = isset ($_POST['module']) ? $_POST['module'] : array ();
    foreach ($modules as $i => $module) echo '<input type="hidden" name="module[]" value="'.$module.'" />';
?>
</div>

<h2><?php echo $_lang['agree_to_terms'];?></h2>
<p>
<input type="checkbox" value="1" id="chkagree" name="chkagree" style="line-height:18px" <?php echo isset($_POST['chkagree']) ? 'checked="checked" ':""; ?><?php echo $agreeToggle;?>/><label for="chkagree" style="display:inline;float:none;line-height:18px;"> <?php echo $_lang['iagree_box']?> </label>
</p>
    <p class="buttonlinks">
        <a href="javascript:document.getElementById('install_form').action='index.php?action=options&language=<?php echo $install_language?>';document.getElementById('install_form').submit();" class="prev" title="<?php echo $_lang['btnback_value']?>"><span><?php echo $_lang['btnback_value']?></span></a>
        <a id="nextbutton" href="javascript:document.getElementById('install_form').submit();" title="<?php echo $nextButton ?>" style="visibility:<?php echo $nextVisibility;?>"><span><?php echo $nextButton ?></span></a>
    </p>
</form>
