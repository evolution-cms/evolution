<?php

if (file_exists(dirname(__FILE__)."/../assets/cache/siteManager.php")) {
    include_once(dirname(__FILE__)."/../assets/cache/siteManager.php");
}else{
    define('MGR_DIR', 'manager');
}

$installMode = intval($_POST['installmode']);
if ($installMode == 0 || $installMode == 2) {
    $database_collation = isset($_POST['database_collation']) ? $_POST['database_collation'] : 'utf8_general_ci';
    $database_charset = substr($database_collation, 0, strpos($database_collation, '_'));
    $_POST['database_connection_charset'] = $database_charset;
    if(empty($_SESSION['databaseloginpassword']))
        $_SESSION['databaseloginpassword'] = $_POST['databaseloginpassword'];
    if(empty($_SESSION['databaseloginname']))
        $_SESSION['databaseloginname'] = $_POST['databaseloginname'];
}
elseif ($installMode == 1) {
    include "../".MGR_DIR."/includes/config.inc.php";

    if (@ $conn = mysql_connect($database_server, $database_user, $database_password)) {
        if (@ mysql_query("USE {$dbase}")) {
            if (!$rs = @ mysql_query("show session variables like 'collation_database'")) {
                $rs = @ mysql_query("show session variables like 'collation_server'");
            }
            if ($rs && $collation = mysql_fetch_row($rs)) {
                $database_collation = trim($collation[1]);
            }
        }
    }
    if (empty ($database_collation)) {
        $database_collation = 'utf8_unicode_ci';
    }
    $database_charset = substr($database_collation, 0, strpos($database_collation, '_'));
    if (!isset ($database_connection_charset) || empty ($database_connection_charset)) {
        $database_connection_charset = $database_charset;
    }

    if (!isset ($database_connection_method) || empty ($database_connection_method)) {
        $database_connection_method = 'SET CHARACTER SET';
        if (function_exists('mysql_set_charset')) mysql_set_charset($database_connection_charset);
    }
    if ($database_connection_method != 'SET NAMES' && $database_connection_charset != $database_charset) {
        $database_connection_method = 'SET NAMES';
    }

    $_POST['database_name'] = $dbase;
    $_POST['tableprefix'] = $table_prefix;
    $_POST['database_connection_charset'] = $database_connection_charset;
    $_POST['database_connection_method'] = $database_connection_method;
    $_POST['databasehost'] = $database_server;
    $_SESSION['databaseloginname'] = $database_user;
    $_SESSION['databaseloginpassword'] = $database_password;
}
?>

<form name="install" id="install_form" action="index.php?action=summary" method="post">
  <div>
    <input type="hidden" value="<?php echo $install_language;?>" name="language" />
    <input type="hidden" value="<?php echo $manager_language;?>" name="managerlanguage" />
    <input type="hidden" value="<?php echo $installMode; ?>" name="installmode" />
    <input type="hidden" value="<?php echo trim($_POST['database_name'], '`'); ?>" name="database_name" />
    <input type="hidden" value="<?php echo $_POST['tableprefix']; ?>" name="tableprefix" />
    <input type="hidden" value="<?php echo $_POST['database_collation']; ?>" name="database_collation" />
    <input type="hidden" value="<?php echo $_POST['database_connection_charset']; ?>" name="database_connection_charset" />
    <input type="hidden" value="<?php echo $_POST['database_connection_method']; ?>" name="database_connection_method" />
    <input type="hidden" value="<?php echo $_POST['databasehost']; ?>" name="databasehost" />
    <input type="hidden" value="<?php echo trim($_POST['cmsadmin']); ?>" name="cmsadmin" />
    <input type="hidden" value="<?php echo trim($_POST['cmsadminemail']); ?>" name="cmsadminemail" />
    <input type="hidden" value="<?php echo trim($_POST['cmspassword']); ?>" name="cmspassword" />
    <input type="hidden" value="<?php echo trim($_POST['cmspasswordconfirm']); ?>" name="cmspasswordconfirm" />
    <input type="hidden" value="1" name="options_selected" />
  </div>

<?php


# load setup information file
$setupPath = realpath(dirname(__FILE__));
include "{$setupPath}/setup.info.php";

echo "<h2>" . $_lang['optional_items'] . "</h2><p>" . $_lang['optional_items_note'] . "</p>";

$chk = isset ($_POST['installdata']) && $_POST['installdata'] == "1" ? 'checked="checked"' : "";
echo '<img src="img/sample_site.png" class="options" alt="Sample Data" />';
echo "<h3>" . $_lang['sample_web_site'] . "</h3>";
echo "<p><input type=\"checkbox\" name=\"installdata\" id=\"installdata_field\" value=\"1\" $chk />&nbsp;<label for=\"installdata_field\">" . $_lang['install_overwrite'] . " <span class=\"comname\">" . $_lang['sample_web_site'] . "</span></label></p><p><em>&nbsp;" . $_lang['sample_web_site_note'] . "</em></p><hr />";

// toggle options
echo "<h4>" . $_lang['checkbox_select_options'] . "</h4>
    <p class=\"actions\"><a id=\"toggle_check_all\" href=\"#\">" . $_lang['all'] . "</a> <a id=\"toggle_check_none\" href=\"#\">" . $_lang['none'] . "</a> <a id=\"toggle_check_toggle\" href=\"#\">" . $_lang['toggle'] . "</a></p>
    <br class=\"clear\" />
    <div id=\"installChoices\">";

$options_selected = isset ($_POST['options_selected']);

// display templates
$templates = isset ($_POST['template']) ? $_POST['template'] : array ();
$limit = count($moduleTemplates);
if ($limit > 0) {
    $tplOutput = '';
    for ($i = 0; $i < $limit; $i++) {
        $class = !in_array('sample', $moduleTemplates[$i][6]) ? 'toggle' : 'toggle demo';
        $chk = in_array($i, $templates) || (!$options_selected) ? 'checked="checked"' : "";
        $tplOutput .= "<input type=\"checkbox\" name=\"template[]\" value=\"$i\" class=\"{$class}\" $chk />" . $_lang['install_update'] . " <span class=\"comname\">" . $moduleTemplates[$i][0] . "</span> - " . $moduleTemplates[$i][1] . "<hr />\n";
    }
    if($tplOutput !== '') {
        echo "<h3>" . $_lang['templates'] . "</h3><br />";
        echo $tplOutput;
    }
}

// display template variables
$tvs = isset ($_POST['tv']) ? $_POST['tv'] : array ();
$limit = count($moduleTVs);
if ($limit > 0) {
    $tvOutput = '';
    for ($i = 0; $i < $limit; $i++) {
        $class = !in_array('sample', $moduleTVs[$i][12]) ? "toggle" : "toggle demo";
        $chk = in_array($i, $tvs) || (!$options_selected) ? 'checked="checked"' : "";
        $tvOutput .= "<input type=\"checkbox\" name=\"tv[]\" value=\"$i\" class=\"{$class}\" $chk />" . $_lang['install_update'] . " <span class=\"comname\">" . $moduleTVs[$i][0] . "</span> - " . $moduleTVs[$i][2] . "<hr />\n";
    }
    if($tvOutput != '') {
        echo "<h3>" . $_lang['tvs'] . "</h3><br />\n";
        echo $tvOutput;
    }
}

// display chunks
$chunks = isset ($_POST['chunk']) ? $_POST['chunk'] : array ();
$limit = count($moduleChunks);
if ($limit > 0) {
    $chunkOutput = '';
    for ($i = 0; $i < $limit; $i++) {
        $class = !in_array('sample', $moduleChunks[$i][5]) ? "toggle" : "toggle demo";
        $chk = in_array($i, $chunks) || (!$options_selected) ? 'checked="checked"' : "";
        $chunkOutput .= "<input type=\"checkbox\" name=\"chunk[]\" value=\"$i\" class=\"{$class}\" $chk />" . $_lang['install_update'] . " <span class=\"comname\">" . $moduleChunks[$i][0] . "</span> - " . $moduleChunks[$i][1] . "<hr />";
    }
    if($chunkOutput != '') {
        echo "<h3>" . $_lang['chunks'] . "</h3>";
        echo $chunkOutput;
    }
}

// display modules
$modules = isset ($_POST['module']) ? $_POST['module'] : array ();
$limit = count($moduleModules);
if ($limit > 0) {
    $moduleOutput = '';
    for ($i = 0; $i < $limit; $i++) {
        $class = !in_array('sample', $moduleModules[$i][7]) ? "toggle" : "toggle demo";
        $chk = in_array($i, $modules) || (!$options_selected) ? 'checked="checked"' : "";
        $moduleOutput .= "<input type=\"checkbox\" name=\"module[]\" value=\"$i\" class=\"{$class}\" $chk />" . $_lang['install_update'] . " <span class=\"comname\">" . $moduleModules[$i][0] . "</span> - " . $moduleModules[$i][1] . "<hr />";
    }
    if($moduleOutput != '') {
        echo "<h3>" . $_lang['modules'] . "</h3>";
        echo $moduleOutput;
    }
}

// display plugins
$plugins = isset ($_POST['plugin']) ? $_POST['plugin'] : array ();
$limit = count($modulePlugins);
if ($limit > 0) {
    $pluginOutput = '';
    for ($i = 0; $i < $limit; $i++) {
        $class = !in_array('sample', (array) $modulePlugins[$i][8]) ? "toggle" : "toggle demo";
        $chk = in_array($i, $plugins) || (!$options_selected) ? 'checked="checked"' : "";
        $pluginOutput .= "<input type=\"checkbox\" name=\"plugin[]\" value=\"$i\" class=\"{$class}\" $chk />" . $_lang['install_update'] . " <span class=\"comname\">" . $modulePlugins[$i][0] . "</span> - " . $modulePlugins[$i][1] . "<hr />";
    }
    if($pluginOutput != '') {
        echo "<h3>" . $_lang['plugins'] . "</h3>";
        echo $pluginOutput;
    }
}

// display snippets
$snippets = isset ($_POST['snippet']) ? $_POST['snippet'] : array ();
$limit = count($moduleSnippets);
if ($limit > 0) {
    $snippetOutput = '';
    for ($i = 0; $i < $limit; $i++) {
        $class = !in_array('sample', (array) $moduleSnippets[$i][5]) ? "toggle" : "toggle demo";
        $chk = in_array($i, $snippets) || (!$options_selected) ? 'checked="checked"' : "";
        $snippetOutput .= "<input type=\"checkbox\" name=\"snippet[]\" value=\"$i\" class=\"{$class}\" $chk />" . $_lang['install_update'] . " <span class=\"comname\">" . $moduleSnippets[$i][0] . "</span> - " . $moduleSnippets[$i][1] . "<hr />";
    }
    if($snippetOutput != '') {
        echo "<h3>" . $_lang['snippets'] . "</h3>";
        echo $snippetOutput;
    }
}
?>
    </div>
    <p class="buttonlinks">
        <a href="javascript:document.getElementById('install_form').action='index.php?action=<?php echo (($installMode == 1) ? 'mode' : 'connection'); ?>';document.getElementById('install_form').submit();" class="prev" title="<?php echo $_lang['btnback_value']?>"><span><?php echo $_lang['btnback_value']?></span></a>
        <a href="javascript:document.getElementById('install_form').submit();" title="<?php echo $_lang['install']?>"><span><?php echo $_lang['install']?></span></a>
    </p>

</form>
<script type="text/javascript" src="../assets/js/jquery.min.js"></script>
<script type="text/javascript">
    jQuery(function(){

        jQuery('#toggle_check_all').click(function(evt){
            evt.preventDefault();
            demo = jQuery('#installdata_field').attr('checked');
            jQuery('input:checkbox.toggle:not(:disabled)').attr('checked', true);
        });
        jQuery('#toggle_check_none').click(function(evt){
            evt.preventDefault();
            demo = jQuery('#installdata_field').attr('checked');
            jQuery('input:checkbox.toggle:not(:disabled)').attr('checked', false);
        });
        jQuery('#toggle_check_toggle').click(function(evt){
            evt.preventDefault();
            jQuery('input:checkbox.toggle:not(:disabled)').attr('checked', function(){
                return !jQuery(this).attr('checked');
            });
        });
        jQuery('#installdata_field').click(function(evt){
            handleSampleDataCheckbox();
        });

        var handleSampleDataCheckbox = function(){
            demo = jQuery('#installdata_field').attr('checked');
            jQuery('input:checkbox.toggle.demo').each(function(ix, el){
                if(demo) {
                    jQuery(this)
                        .attr('checked', true)
                        .attr('disabled', true)
                    ;
                } else {
                    jQuery(this)
                        .attr('disabled', false)
                    ;
                }
            });
        }

        // handle state of demo content checkbox on page load
        handleSampleDataCheckbox();
    });
</script>