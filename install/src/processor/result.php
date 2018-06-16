<?php
//:: EVO Installer Setup file
//:::::::::::::::::::::::::::::::::::::::::
if (is_file($base_path . 'assets/cache/siteManager.php')) {
    include_once($base_path . 'assets/cache/siteManager.php');
}
if (!defined('MGR_DIR')) {
    define('MGR_DIR', 'manager');
}

require_once(dirname(dirname(dirname(__DIR__))) . '/' . MGR_DIR . '/includes/version.inc.php');

$chunkPath = $base_path . 'install/assets/chunks';
$snippetPath = $base_path . 'install/assets/snippets';
$pluginPath = $base_path . 'install/assets/plugins';
$modulePath = $base_path . 'install/assets/modules';
$templatePath = $base_path . 'install/assets/templates';
$tvPath = $base_path . 'install/assets/tvs';

// setup Template template files - array : name, description, type - 0:file or 1:content, parameters, category
$mt = &$moduleTemplates;
if (is_dir($templatePath) && is_readable($templatePath)) {
    $d = dir($templatePath);
    while (false !== ($tplfile = $d->read())) {
        if (substr($tplfile, -4) !== '.tpl') {
            continue;
        }
        $params = parse_docblock($templatePath, $tplfile);
        if (is_array($params) && (count($params) > 0)) {
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
                isset($params['save_sql_id_as']) ? $params['save_sql_id_as'] : null
                // Nessecary to fix template-ID for demo-site
            );
        }
    }
    $d->close();
}

// setup Template Variable template files
$mtv = &$moduleTVs;
if (is_dir($tvPath) && is_readable($tvPath)) {
    $d = dir($tvPath);
    while (false !== ($tplfile = $d->read())) {
        if (substr($tplfile, -4) !== '.tpl') {
            continue;
        }
        $params = parse_docblock($tvPath, $tplfile);
        if (is_array($params) && (count($params) > 0)) {
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
                "$templatePath/{$params['filename']}",
                /* not currently used */
                $params['template_assignments'] !== "*" ? $params['template_assignments'] : implode(",",
                    array_map(create_function('$v', 'return $v[0];'), $mt)),
                /* comma-separated list of template names */
                $params['modx_category'],
                $params['lock_tv'],
                /* value should be 1 or 0 */
                array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : false
            );
        }
    }
    $d->close();
}

// setup chunks template files - array : name, description, type - 0:file or 1:content, file or content
$mc = &$moduleChunks;
if (is_dir($chunkPath) && is_readable($chunkPath)) {
    $d = dir($chunkPath);
    while (false !== ($tplfile = $d->read())) {
        if (substr($tplfile, -4) != '.tpl') {
            continue;
        }
        $params = parse_docblock($chunkPath, $tplfile);
        if (is_array($params) && count($params) > 0) {
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
if (is_dir($snippetPath) && is_readable($snippetPath)) {
    $d = dir($snippetPath);
    while (false !== ($tplfile = $d->read())) {
        if (substr($tplfile, -4) != '.tpl') {
            continue;
        }
        $params = parse_docblock($snippetPath, $tplfile);
        if (is_array($params) && count($params) > 0) {
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
if (is_dir($pluginPath) && is_readable($pluginPath)) {
    $d = dir($pluginPath);
    while (false !== ($tplfile = $d->read())) {
        if (substr($tplfile, -4) != '.tpl') {
            continue;
        }
        $params = parse_docblock($pluginPath, $tplfile);
        if (is_array($params) && count($params) > 0) {
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
if (is_dir($modulePath) && is_readable($modulePath)) {
    $d = dir($modulePath);
    while (false !== ($tplfile = $d->read())) {
        if (substr($tplfile, -4) != '.tpl') {
            continue;
        }
        $params = parse_docblock($modulePath, $tplfile);
        if (is_array($params) && count($params) > 0) {
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
                            'table'  => 'templates',
                            'column' => 'templatename',
                            'type'   => 50,
                            'name'   => trim($dependency[1])
                        );
                        break;
                    case 'tv':
                    case 'tmplvar':
                        $mdp[] = array(
                            'module' => $params['name'],
                            'table'  => 'tmplvars',
                            'column' => 'name',
                            'type'   => 60,
                            'name'   => trim($dependency[1])
                        );
                        break;
                    case 'chunk':
                    case 'htmlsnippet':
                        $mdp[] = array(
                            'module' => $params['name'],
                            'table'  => 'htmlsnippets',
                            'column' => 'name',
                            'type'   => 10,
                            'name'   => trim($dependency[1])
                        );
                        break;
                    case 'snippet':
                        $mdp[] = array(
                            'module' => $params['name'],
                            'table'  => 'snippets',
                            'column' => 'name',
                            'type'   => 40,
                            'name'   => trim($dependency[1])
                        );
                        break;
                    case 'plugin':
                        $mdp[] = array(
                            'module' => $params['name'],
                            'table'  => 'plugins',
                            'column' => 'name',
                            'type'   => 30,
                            'name'   => trim($dependency[1])
                        );
                        break;
                    case 'resource':
                        $mdp[] = array(
                            'module' => $params['name'],
                            'table'  => 'content',
                            'column' => 'pagetitle',
                            'type'   => 20,
                            'name'   => trim($dependency[1])
                        );
                        break;
                }
            }
        }
    }
    $d->close();
}

// setup callback function
$callBackFnc = 'clean_up';

