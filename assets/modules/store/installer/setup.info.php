<?php
error_reporting(E_ALL & ~E_NOTICE);
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true || ! $modx->hasPermission('exec_module')) {
    die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.');
}

//:: MODx Installer Setup file
//:::::::::::::::::::::::::::::::::::::::::
require_once(MGR.'/includes/version.inc.php');
$installPath = MODX_BASE_PATH .'assets/cache/store/install';

$moduleName = "MODX";
include MODX_MANAGER_PATH . 'includes/version.inc.php';
$moduleVersion = $modx_branch.' '.$modx_version;
$moduleRelease = $modx_release_date;
$moduleSQLBaseFile = "setup.sql";
$moduleSQLDataFile = "install/setup.data.sql";
$chunkPath = $installPath .'/install/assets/chunks';
$snippetPath = $installPath .'/install/assets/snippets';
$pluginPath = $installPath .'/install/assets/plugins';
$modulePath = $installPath .'/install/assets/modules';
$templatePath = $installPath .'/install/assets/templates';
$tvPath = $installPath .'/install/assets/tvs';

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
                array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : []
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
                $params['template_assignments'], /* comma-separated list of template names */
                $params['modx_category'],
                $params['lock_tv'],  /* value should be 1 or 0 */
                array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : []
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
            $description = empty($params['version']) ? $params['description'] : "<strong>{$params['version']}</strong> {$params['description']}";
            $mc[] = array(
                $params['name'],
                $description,
                "$chunkPath/{$params['filename']}",
                $params['modx_category'],
                array_key_exists('overwrite', $params) ? $params['overwrite'] : 'true',
                array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : []
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
                $params['properties'] ?? '',
                $params['modx_category'],
                array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : []
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
                $params['properties'] ?? '',
                $params['events'],
                $params['guid'] ?? '',
                $params['modx_category'],
                $params['legacy_names'] ?? '',
                array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : [],
                (int)($params['disabled'] ?? 0)
            );
        }
    }
    $d->close();
}

// setup modules - array : name, description, type - 0:file or 1:content, file or content,properties, guid,enable_sharedparams
$mm = &$moduleModules;
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
                $params['properties'] ?? '',
                $params['guid'],
                (int)$params['shareparams'],
                $params['modx_category'],
                array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : []
            );
        }
    }
    $d->close();
}

// setup callback function
$callBackFnc = "clean_up";

function clean_up($sqlParser) {
    $ids = array();
    $mysqlVerOk = -1;

    if(function_exists("mysql_get_server_info")) {
        $mysqlVerOk = (version_compare(mysql_get_server_info(),"4.0.2")>=0);
    }

    // secure web documents - privateweb
    $modx->db->query("UPDATE `".$modx->db->config['table_prefix']."site_content` SET privateweb = 0 WHERE privateweb = 1");
    $sql =  "SELECT DISTINCT sc.id
             FROM `".$modx->db->config['table_prefix']."site_content` sc
             LEFT JOIN `".$modx->db->config['table_prefix']."document_groups` dg ON dg.document = sc.id
             LEFT JOIN `".$modx->db->config['table_prefix']."webgroup_access` wga ON wga.documentgroup = dg.document_group
             WHERE wga.id>0";
    $ds = $modx->db->query($sql);
    if(!$ds) {
        echo "An error occurred while executing a query: ".$modx->db->getLastError();
    }
    else {
        while($r = $modx->db->GetRow($ds,'assoc')) $ids[]=$r["id"];
        if(count($ids)>0) {
            mysql_query("UPDATE `".$modx->db->config['table_prefix']."site_content` SET privateweb = 1 WHERE id IN (".implode(", ",$ids).")");
            unset($ids);
        }
    }

    // secure manager documents privatemgr
    $modx->db->query("UPDATE `".$modx->db->config['table_prefix']."site_content` SET privatemgr = 0 WHERE privatemgr = 1");
    $sql =  "SELECT DISTINCT sc.id
             FROM `".$modx->db->config['table_prefix']."site_content` sc
             LEFT JOIN `".$modx->db->config['table_prefix']."document_groups` dg ON dg.document = sc.id
             LEFT JOIN `".$modx->db->config['table_prefix']."membergroup_access` mga ON mga.documentgroup = dg.document_group
             WHERE mga.id>0";
    $ds = $modx->db->query($sql);
    if(!$ds) {
        echo "An error occurred while executing a query: " . $modx->db->getLastError();
    }
    else {
        while($r = $modx->db->GetRow($ds,'assoc')) $ids[]=$r["id"];
        if(count($ids)>0) {
            $modx->db->query("UPDATE `".$modx->db->config['table_prefix']."site_content` SET privatemgr = 1 WHERE id IN (".implode(", ",$ids).")");
            unset($ids);
        }
    }

    /**** Add Quick Plugin to Module
    // get quick edit module id
    $ds = mysql_query("SELECT id FROM `".$sqlParser->prefix."site_modules` WHERE name='QuickEdit'");
    if(!$ds) {
        echo "An error occurred while executing a query: ".mysql_error();
    }
    else {
        $row = mysql_fetch_assoc($ds);
        $moduleid=$row["id"];
    }
    // get plugin id
    $ds = mysql_query("SELECT id FROM `".$sqlParser->prefix."site_plugins` WHERE name='QuickEdit'");
    if(!$ds) {
        echo "An error occurred while executing a query: ".mysql_error();
    }
    else {
        $row = mysql_fetch_assoc($ds);
        $pluginid=$row["id"];
    }
    // setup plugin as module dependency
    $ds = mysql_query("SELECT module FROM `".$sqlParser->prefix."site_module_depobj` WHERE module='$moduleid' AND resource='$pluginid' AND type='30' LIMIT 1");
    if(!$ds) {
        echo "An error occurred while executing a query: ".mysql_error();
    }
    elseif (mysql_num_rows($ds)==0){
        mysql_query("INSERT INTO `".$sqlParser->prefix."site_module_depobj` (module, resource, type) VALUES('$moduleid','$pluginid',30)");
    }
    ***/
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
            $docblock_end_found = false;

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
                        $docblock_end_found = true;
                        break;
                    }
                }
            }
            @fclose($tpl);
        }
    }
    return $params;
}
