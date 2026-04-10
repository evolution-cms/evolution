<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

// PROCESSOR FIRST
if($_SESSION['mgrRole'] == 1) {
    if(!empty($_REQUEST['b']) && $_REQUEST['b'] == 'resetSysfilesChecksum' && EvolutionCMS()->hasPermission('settings')) {
        $current = EvolutionCMS()->getManagerApi()->getSystemChecksum(EvolutionCMS()->config['check_files_onlogin']);
        if(!empty($current)) {
            EvolutionCMS()->getManagerApi()->setSystemChecksum($current);
            EvolutionCMS()->clearCache('full');
            EvolutionCMS()->config['sys_files_checksum'] = $current;
        };
    }
}

// NOW CHECK CONFIG
$warnings = [];
$sysfiles_check = EvolutionCMS()->getManagerApi()->checkSystemChecksum();
if ($sysfiles_check!=='0') {
    $warnings[] = [$_lang['configcheck_sysfiles_mod']];
}

if (file_exists("../install/")) {
    $warnings[] = [$_lang['configcheck_installer']];
}

if (!extension_loaded('gd') || !extension_loaded('zip')) {
    $warnings[] = [$_lang['configcheck_php_gdzip']];
}

if(!isset(EvolutionCMS()->config['_hide_configcheck_validate_referer']) || EvolutionCMS()->config['_hide_configcheck_validate_referer'] !== '1') {
    if(isset($_SESSION['mgrPermissions']['settings']) && $_SESSION['mgrPermissions']['settings'] == '1') {
        if (EvolutionCMS()->getConfig('validate_referer') == '0') {
            $warnings[] = [$_lang['configcheck_validate_referer']];
        }
    }
}

// check for Template Switcher plugin
if(!isset(EvolutionCMS()->config['_hide_configcheck_templateswitcher_present']) || EvolutionCMS()->config['_hide_configcheck_templateswitcher_present'] !== '1') {
    if(isset($_SESSION['mgrPermissions']['edit_plugin']) && $_SESSION['mgrPermissions']['edit_plugin'] == '1') {

        $row = \EvolutionCMS\Models\SitePlugin::select('name','disabled')->where(function($q) {
            $q->whereIn('name',['TemplateSwitcher', 'Template Switcher', 'templateswitcher', 'template_switcher', 'template switcher'])
                ->orWhere('plugincode','LIKE','%TemplateSwitcher%');
        })->first();
        if(!is_null($row) && $row->disabled == 0) {
            $warnings[] = [$_lang['configcheck_templateswitcher_present']];
            $tplName = $row->name;
            $script = <<<JS
<script type="text/javascript">
    function deleteTemplateSwitcher(){
        if(confirm('{$_lang["confirm_delete_plugin"]}')) {
            var myAjax = new Ajax('index.php?a=118', {
                method: 'post',
                data: 'action=updateplugin&key=_delete_&lang=$tplName'
            });
            myAjax.addEvent('onComplete', function(resp){
                fieldset = $('templateswitcher_present_warning_wrapper').getParent().getParent();
                var sl = new Fx.Slide(fieldset);
                sl.slideOut();
            });
            myAjax.request();
        }
    }
    function disableTemplateSwitcher(){
        var myAjax = new Ajax('index.php?a=118', {
            method: 'post',
            data: 'action=updateplugin&lang={$tplName}&key=disabled&value=1'
        });
        myAjax.addEvent('onComplete', function(resp){
            fieldset = $('templateswitcher_present_warning_wrapper').getParent().getParent();
            var sl = new Fx.Slide(fieldset);
            sl.slideOut();
        });
        myAjax.request();
    }
</script>

JS;
            EvolutionCMS()->regClientScript($script);
        }
    }
}
$unathorized_page_id = EvolutionCMS()->getConfig('unauthorized_page');
$error_page_id = EvolutionCMS()->getConfig('error_page');
$pages = \EvolutionCMS\Models\SiteContent::select (['id', 'published', 'privateweb'])
    ->whereIn('id', [$unathorized_page_id, $error_page_id])
    ->get();
foreach ($pages as $page) {
    if ($page->id == $unathorized_page_id && !$page->published) {
        $warnings[] = [$_lang['configcheck_unauthorizedpage_unpublished']];
    }
    if ($page->id == $unathorized_page_id && $page->privateweb) {
        $warnings[] = [$_lang['configcheck_unauthorizedpage_unavailable']];
    }
    if ($page->id == $error_page_id && !$page->published) {
        $warnings[] = [$_lang['configcheck_errorpage_unavailable']];
    }
    if ($page->id == $error_page_id && $page->privateweb) {
        $warnings[] = [$_lang['configcheck_errorpage_unavailable']];
    }
}
if (!function_exists('checkSiteCache')) {
    /**
     * @return bool
     */
    function checkSiteCache() {
        $modx = evolutionCMS();
        $checked= true;
        if (file_exists(EVO_BASE_PATH . 'assets/cache/siteCache.idx.php')) {
            $checked= @include_once (EVO_BASE_PATH . 'assets/cache/siteCache.idx.php');
        }

        return $checked;
    }
}

if (!is_writable(EVO_BASE_PATH . "assets/cache/")) {
    $warnings[] = [$_lang['configcheck_cache']];
}

if (!checkSiteCache()) {
    $warnings[]= [$lang['configcheck_sitecache_integrity']];
}

if (!is_writable(EVO_BASE_PATH . "assets/images/")) {
    $warnings[] = [$_lang['configcheck_images']];
}

if(strpos(EvolutionCMS()->config['rb_base_dir'], EVO_BASE_PATH) !==0 && strpos(EvolutionCMS()->config['rb_base_dir'], '[(base_path)]') !==0) {
    $warnings[] = [$_lang['configcheck_rb_base_dir']];
}

if(strpos(EvolutionCMS()->config['filemanager_path'], EVO_BASE_PATH) !==0 && strpos(EvolutionCMS()->config['rb_base_dir'], '[(base_path)]assets/') !==0) {
    $warnings[] = [$_lang['configcheck_filemanager_path']];
}

// clear file info cache
clearstatcache();
if (!empty($warnings)) {

    if(!isset(EvolutionCMS()->config['send_errormail'])) EvolutionCMS()->config['send_errormail']='3';
    $config_check_results = "<h3>".$_lang['configcheck_notok']."</h3>";

    for ($i=0;$i<count($warnings);$i++) {
        switch ($warnings[$i][0]) {
            case $_lang['configcheck_configinc'];
                $warnings[$i][1] = $_lang['configcheck_configinc_msg'];
                if(empty($_SESSION["mgrConfigCheck"])) EvolutionCMS()->logEvent(0,3,$warnings[$i][1],$_lang['configcheck_configinc']);
                break;
            case $_lang['configcheck_installer'] :
                $warnings[$i][1] = $_lang['configcheck_installer_msg'];
                if(empty($_SESSION["mgrConfigCheck"])) EvolutionCMS()->logEvent(0,3,$warnings[$i][1],$_lang['configcheck_installer']);
                break;
            case $_lang['configcheck_cache'] :
                $warnings[$i][1] = $_lang['configcheck_cache_msg'];
                if(empty($_SESSION["mgrConfigCheck"])) EvolutionCMS()->logEvent(0,2,$warnings[$i][1],$_lang['configcheck_cache']);
                break;
            case $_lang['configcheck_images'] :
                $warnings[$i][1] = $_lang['configcheck_images_msg'];
                if(empty($_SESSION["mgrConfigCheck"])) EvolutionCMS()->logEvent(0,2,$warnings[$i][1],$_lang['configcheck_images']);
                break;
            case $_lang['configcheck_sysfiles_mod']:
                $warnings[$i][1] = $_lang["configcheck_sysfiles_mod_msg"];
                $warnings[$i][2] = '<ul><li>'. implode('</li><li>', $sysfiles_check) .'</li></ul>';
                if(EvolutionCMS()->hasPermission('settings')) {
                    $warnings[$i][2] .= '<ul class="actionButtons" style="float:right"><li><a href="index.php?a=2&b=resetSysfilesChecksum" onclick="return confirm(\'' . $_lang["reset_sysfiles_checksum_alert"] . '\')">' . $_lang["reset_sysfiles_checksum_button"] . '</a></li></ul>';
                }
                if(empty($_SESSION["mgrConfigCheck"])) EvolutionCMS()->logEvent(0,3,$warnings[$i][1]." ".implode(', ',$sysfiles_check),$_lang['configcheck_sysfiles_mod']);
                break;
            case $_lang['configcheck_lang_difference'] :
                $warnings[$i][1] = $_lang['configcheck_lang_difference_msg'];
                break;
            case $_lang['configcheck_php_gdzip'] :
                $warnings[$i][1] = $_lang['configcheck_php_gdzip_msg'];
                break;
            case $_lang['configcheck_unauthorizedpage_unpublished'] :
                $warnings[$i][1] = $_lang['configcheck_unauthorizedpage_unpublished_msg'];
                break;
            case $_lang['configcheck_errorpage_unpublished'] :
                $warnings[$i][1] = $_lang['configcheck_errorpage_unpublished_msg'];
                break;
            case $_lang['configcheck_unauthorizedpage_unavailable'] :
                $warnings[$i][1] = $_lang['configcheck_unauthorizedpage_unavailable_msg'];
                break;
            case $_lang['configcheck_errorpage_unavailable'] :
                $warnings[$i][1] = $_lang['configcheck_errorpage_unavailable_msg'];
                break;
            case $_lang['configcheck_validate_referer'] :
                $msg = $_lang['configcheck_validate_referer_msg'];
                $msg .= '<br />' . sprintf($_lang["configcheck_hide_warning"], 'validate_referer');
                $warnings[$i][1] = "<span id=\"validate_referer_warning_wrapper\">{$msg}</span>\n";
                break;
            case $_lang['configcheck_templateswitcher_present'] :
                $msg = $_lang["configcheck_templateswitcher_present_msg"];
                if(isset($_SESSION['mgrPermissions']['save_plugin']) && $_SESSION['mgrPermissions']['save_plugin'] == '1') {
                    $msg .= '<br />' . $_lang["configcheck_templateswitcher_present_disable"];
                }
                if(isset($_SESSION['mgrPermissions']['delete_plugin']) && $_SESSION['mgrPermissions']['delete_plugin'] == '1') {
                    $msg .= '<br />' . $_lang["configcheck_templateswitcher_present_delete"];
                }
                $msg .= '<br />' . sprintf($_lang["configcheck_hide_warning"], 'templateswitcher_present');
                $warnings[$i][1] = "<span id=\"templateswitcher_present_warning_wrapper\">{$msg}</span>\n";
                break;
            case $_lang['configcheck_rb_base_dir'] :
                $warnings[$i][1] = $_lang['configcheck_rb_base_dir_msg'];
                break;
            case $_lang['configcheck_filemanager_path'] :
                $warnings[$i][1] = $_lang['configcheck_filemanager_path_msg'];
                break;
            default :
                $warnings[$i][1] = $_lang['configcheck_default_msg'];
        }

        $admin_warning = $_SESSION['mgrRole']!=1 ? $_lang['configcheck_admin'] : "" ;
        $config_check_results .= "
            <fieldset>
            <p><strong>".$_lang['configcheck_warning']."</strong> '".$warnings[$i][0]."'</p>
            <p style=\"padding-left:1em\"><em>".$_lang['configcheck_what']."</em><br />
            ".$warnings[$i][1]." ".$admin_warning."</p>
            ".(isset($warnings[$i][2]) ? '<div style="padding-left:1em">'.$warnings[$i][2].'</div>' : '')."
            </fieldset>
";
        if ($i!=count($warnings)-1) {
            $config_check_results .= "<br />";
        }
    }
    $_SESSION["mgrConfigCheck"]=true;
} else {
    $config_check_results = $_lang['configcheck_ok'];
}
