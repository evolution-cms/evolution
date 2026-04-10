

<form name="install" id="install_form" action="/manager/?id=<?php echo (int) $_GET['id']; ?>&a=112&action=install2_step" method="post">
  <div>
    <input type="hidden" value="1" name="options_selected" />
  </div>

<?php

if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true || ! $modx->hasPermission('exec_module')) {
    die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.');
}

# load setup information file
$setupPath = $modulePath;
include "{$setupPath}/setup.info.php";

echo "<h2>" . $_lang['optional_items'] . "</h2><p>" . $_lang['optional_items_note'] . "</p>";

if (is_file( EVO_BASE_PATH . 'assets/cache/store/install/install/setup.data.sql')) {
$chk = isset ($_POST['installdata']) && $_POST['installdata'] == "1" ? 'checked="checked"' : "";
echo '<img src="/assets/modules/store/installer/img/sample_site.png" class="options" alt="Sample Data" />';
echo "<h3>" . $_lang['sample_web_site'] . "</h3>";
echo "<p><input type=\"checkbox\" name=\"installdata\" id=\"installdata_field\" value=\"1\" $chk />&nbsp;<label for=\"installdata_field\" >" . $_lang['install_overwrite'] . " <span class=\"comname\" >" . $_lang['sample_web_site'] . "</span></label></p><p><em style='color:red;'>&nbsp;" . $_lang['sample_web_site_note'] . "</em></p><hr />";
}

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
        <!-- тут кнопку отменить разве что поставить<a href="javascript:document.getElementById('install_form').action='index.php?action=<?php echo ((isset($installMode) && $installMode == 1) ? 'mode' : 'connection'); ?>';document.getElementById('install_form').submit();" class="prev" title="<?php echo $_lang['btnback_value']?>"><span><?php echo $_lang['btnback_value']?></span></a>-->
        <a href="javascript:document.getElementById('install_form').submit();" title="<?php echo $_lang['install']?>"><span><?php echo $_lang['install']?></span></a>
    </p>

</form>
<script type="text/javascript" src="<?php echo EVO_SITE_URL;?>assets/modules/store/installer/jquery-1.4.4.min.js"></script>
<script type="text/javascript">
    $(document).ready(function(){

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
