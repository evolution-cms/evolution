<?php
// step 5
use EvolutionCMS\Facades\Console;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (file_exists(dirname(__DIR__, 3) . '/assets/cache/siteManager.php')) {
    include_once dirname(__DIR__, 3) . '/assets/cache/siteManager.php';
} else {
    define('MGR_DIR', 'manager');
}

global $moduleName;
global $moduleVersion;
global $moduleSQLBaseFile;
global $moduleSQLDataFile;
global $moduleSQLResetFile;
global $moduleChunks;
global $moduleTemplates;
global $moduleSnippets;
global $modulePlugins;
global $moduleModules;
global $moduleTVs;
global $moduleDependencies;
global $errors;

// set timout limit
@set_time_limit(300); // used @ to prevent warning when using safe mode?

$installMode = (int)$_POST['installmode'];
$installData = $_POST['installdata'] == '1' ? 1 : 0;

// get db info from post
$database_type = validateDbType($_POST['database_type']);
$database_server = validateDbHost($_POST['databasehost'], $database_type);
$database_user = $_SESSION['databaseloginname'] ?? '';
$database_password = $_SESSION['databaseloginpassword'] ?? '';
$database_collation = validateDbCollation($_POST['database_collation']);
$database_connection_charset = validateDbCollation($_POST['database_connection_charset']);
$database_name = validateDbName($_POST['database_name']);
if ($installMode !== 1) {
    $adminname = validateAdminUsername($_POST['cmsadmin']);
    $adminemail = validateAdminEmail($_POST['cmsadminemail']);
    $adminpass = validateAdminPassword($_POST['cmspassword']);
    $adminpassconfirm = validateAdminPassword($_POST['cmspasswordconfirm']);
    $managerlanguage = validateLangCode($_POST['managerlanguage']);
}
$custom_placeholders = [];

// set session name variable
if (!isset($site_sessionname)) {
    $site_sessionname = 'SN' . uniqid('');
}

// get base path and url
$a = explode('install', str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])));
if (count($a) > 1) {
    array_pop($a);
}
$url = implode('install', $a);
reset($a);
$a = explode('install', str_replace('\\', '/', realpath(__DIR__)));
if (count($a) > 1) {
    array_pop($a);
}
$pth = implode('install', $a);
unset ($a);
$base_url = $url . (substr($url, -1) !== '/' ? '/' : '');
$base_path = $pth . (substr($pth, -1) !== '/' ? '/' : '');

// connect to the database
$host = explode(':', $database_server, 2);

global $conn;
try {
    $pdoOptions = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
    if ($database_type === 'sqlite') {
        $dbh = new PDO('sqlite:' . sqliteDbNameToPath($database_name));
    } else {
        $dbh = new PDO($database_type . ':host=' . $database_server . ';dbname=' . $database_name, $database_user, $database_password, $pdoOptions);
    }

    include dirname(__DIR__) . '/processor/result.php';

    if ($installMode == 1) {
        $installLevel = 3;
    } else {
        $installLevel = 1;
    }
    // select database
    if ($installLevel === 1) {
        // write the core/config/database/connections/default.php file if new installation
        $confph = [];
        $confph['database_server'] = addslashes($database_server);
        $confph['database_type'] = addslashes($database_type);
        $confph['user_name'] = addslashes($database_user);
        $confph['password'] = addslashes($database_password);
        $confph['connection_charset'] = addslashes($database_connection_charset);
        $confph['connection_collation'] = addslashes($database_collation);
        $confph['database_name'] = addslashes(
            $database_type === 'sqlite' ? sqliteDbNameToPath($database_name) : $database_name);
        $confph['table_prefix'] = addslashes($_POST['tableprefix'] ?? '');
        $confph['lastInstallTime'] = time();
        $confph['site_sessionname'] = addslashes($site_sessionname);
        $confph['database_engine'] = '';
        switch ($database_type) {
            case 'pgsql':
                $confph['database_port'] = '5432';
                break;
            case 'mysql':
                $confph['database_port'] = '3306';
                $serverVersion = $dbh->getAttribute(PDO::ATTR_SERVER_VERSION);
                if (version_compare($serverVersion, '5.7.6', '<')) {
                    $confph['database_engine'] = ", 'myisam'";
                } else {
                    $confph['database_engine'] = ", 'innodb'";
                }
                break;
            case 'sqlite':
                $confph['database_port'] = '';
                break;
        }
        $configString = file_get_contents(dirname(__DIR__, 2) . '/stubs/files/config/database/connections/default.tpl');
        $configString = parse($configString, $confph);

        $filename = EVO_CORE_PATH . 'config/database/connections/default.php';
        $configFileFailed = false;

        if (file_exists($filename)) {
            @chmod($filename, 0777);
        }

        if (@!$handle = fopen($filename, 'w')) {
            $configFileFailed = true;
        }

        // write $somecontent to our opened file.
        if (@fwrite($handle, $configString) === false) {
            $configFileFailed = true;
        }
        @fclose($handle);

        // try to chmod the config file go-rwx (for suexeced php)
        @chmod($filename, 0404);
        if ($configFileFailed === true) {
            $errors += 1;
        } else {
            $installLevel = 3;
        }
    }

    if ($installLevel === 2) {
        // check table prefix
        if ($installMode === 0) {
            try {
                $siteContent = \EvolutionCMS\Models\SiteContent::query()->count();
                $errors += 1;
            } catch (PDOException $exception) {
                $installLevel = 3;
            }
        } else {
            $installLevel = 3;
        }
    }

    if ($installLevel === 3) {
        $delete_file = dirname(__DIR__, 2) . '/stubs/file_for_delete.txt';
        if (file_exists($delete_file)) {
            $files = explode("\n", file_get_contents($delete_file));
            foreach ($files as $file) {
                $file = str_replace('{core}', EVO_CORE_PATH, $file);
                if (file_exists($file)) {
                    if (is_dir($file)) {
                        removeFolder($file);
                    } else {
                        unlink($file);
                    }
                }
            }
        }
        define('EVO_API_MODE', true);
        define('IN_MANAGER_MODE', true);
        define('IN_INSTALL_MODE', true);
        define('EVO_BASE_PATH', dirname(dirname(dirname(__DIR__))) . '/');
        define('SESSION_COOKIE_NAME', session_name());
        require_once(EVO_BASE_PATH . 'core/functions/helper.php');
        require_once(EVO_BASE_PATH . 'core/includes/define.inc.php');

        if (file_exists(EVO_BASE_PATH.'core/storage/bootstrap/services.php')) {
            unlink(EVO_BASE_PATH.'core/storage/bootstrap/services.php');
        }

        include(EVO_BASE_PATH . '/index.php');

        if ($installMode != 0 && $database_type == 'pgsql') {
            try {
                $result = \DB::table('migrations_install')->select('id')->orderBy('id', 'DESC')->first();
            } catch (\PDOException $exception) {
                $result = null;
                if (!str_contains($exception->getMessage(), 'SQLSTATE[42P01]')) {
                    throw $exception;
                }
            }
            if (!is_null($result)) {
                $new_id = $result->id;
                $new_id++;
                $table = table_prefix('migrations_install') . '_id_seq';
                \DB::statement('ALTER SEQUENCE '.$table.' RESTART WITH ' . $new_id);
            }
        }

        Console::call('migrate', ['--path' => EVO_BASE_PATH . 'install/stubs/migrations', '--realpath' => true, '--force' => true]);

        if ($installMode == 0) {
            seed('install');
            $field = [];
            $field['password'] = evo()->getPasswordHash()->HashPassword($adminpass);
            $field['username'] = $adminname;
            $managerUser = EvolutionCMS\Models\User::create($field);
            $internalKey = $managerUser->getKey();
            $verified = 1;
            $role = \EvolutionCMS\Models\UserRole::where('name', 'Administrator')->first()->getKey();
            $field = ['internalKey' => $internalKey, 'email' => $adminemail, 'role' => $role, 'verified' => 1];
            $managerUser->attributes()->create($field);
            $managerUser->attributes->role = $role;
            $managerUser->attributes->save();
            $systemSettings[] = ['setting_name' => 'manager_language', 'setting_value' => $managerlanguage];
            $systemSettings[] = ['setting_name' => 'auto_template_logic', 'setting_value' => 'sibling'];
            $systemSettings[] = ['setting_name' => 'emailsender', 'setting_value' => $adminemail];
            $systemSettings[] = ['setting_name' => 'fe_editor_lang', 'setting_value' => $managerlanguage];
            \EvolutionCMS\Models\SystemSetting::insert($systemSettings);
        } else {
            seed('update');
        }
        $installLevel = 4;
    }

    if ($installLevel === 4) {
        // generate new site_id and set manager theme to default
        if ($installMode == 0) {
            $siteid = uniqid('');
            \EvolutionCMS\Models\SystemSetting::insert([['setting_name' => 'site_id', 'setting_value' => $siteid], ['setting_name' => 'manager_theme', 'setting_value' => 'default']]);
        } else {
            // update site_id if missing
            $siteId = \EvolutionCMS\Models\SystemSetting::where('setting_name', 'site_id')->first();

            if (is_null($siteId)) {
                $siteid = uniqid('');
                \EvolutionCMS\Models\SystemSetting::insert(['setting_name' => 'site_id', 'setting_value' => $siteid]);
            }
        }

        // Reset database for installation of demo-site
        if ($installData && $moduleSQLDataFile && $moduleSQLResetFile) {
            $sqlParser->process($moduleSQLResetFile);
            // display database results
            if ($sqlParser->installFailed === true) {
                $errors += 1;
            } else {
                $installLevel = 5;
            }
        } else {
            $installLevel = 5;
        }
    }

    $installDataLevel = [];
    $errorData = false;
    // Install Templates
    if ($installLevel === 5 && (isset($_POST['template']) || $installData)) {
        $selTemplates = array_filter((array)($_POST['template'] ?? []), 'is_numeric');
        foreach ($moduleTemplates as $k => $moduleTemplate) {
            if (!is_array($moduleTemplate)) {
                continue;
            }
            $installDataLevel['templates'][$moduleTemplate[0]] = [
                'data' => [
                    'desc' => $moduleTemplate[1],
                    'category' => $moduleTemplate[4],
                    'locked' => $moduleTemplate[5],
                    'file' => $moduleTemplate[3],
                    'id' => $moduleTemplate[7],
                ],
                'type' => '', // update, create
            ];
            $installSample = in_array('sample', $moduleTemplate[6]) && $installData === 1;
            if ($installSample || in_array($k, $selTemplates)) {
                $name = $moduleTemplate[0];
                $desc = $moduleTemplate[1];
                $category = $moduleTemplate[4];
                $locked = $moduleTemplate[5];
                $filecontent = $moduleTemplate[3];
                $save_sql_id_as = $moduleTemplate[7]; // Nessecary for demo-site
                if (!file_exists($filecontent)) {
                    $installDataLevel['templates'][$moduleTemplate[0]]['error'] = ['type' => 'file_not_found'];
                } else {
                    // Create the category if it does not already exist
                    $category_id = getCreateDbCategory($category);

                    // Strip the first comment up top
                    $template = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', file_get_contents($filecontent), 1);

                    // See if the template already exists
                    $siteTemplate = \EvolutionCMS\Models\SiteTemplate::where('templatename', $name);

                    if ($siteTemplate->count() > 0) {
                        $siteTemplate = $siteTemplate->first();
                        $installDataLevel['templates'][$moduleTemplate[0]]['type'] = 'update';
                        $siteTemplate->content = $template;
                        $siteTemplate->description = $desc;
                        $siteTemplate->category = (int)$category_id;
                        $siteTemplate->locked = (int)$locked;
                        $siteTemplate->save();

                        if ($save_sql_id_as !== null) {
                            $sql_id = $siteTemplate->getKey();
                            $custom_placeholders[$save_sql_id_as] = $sql_id;
                        }
                    } else {
                        $installDataLevel['templates'][$moduleTemplate[0]]['type'] = 'create';
                        $siteTemplate = \EvolutionCMS\Models\SiteTemplate::create(['templatename' => $name, 'description' => $desc, 'content' => $template, 'category' => (int)$category_id, 'locked' => (int)$locked]);

                        if ($save_sql_id_as !== null) {
                            $custom_placeholders[$save_sql_id_as] = $siteTemplate->getKey();
                        }
                    }
                }
            } else {
                $installDataLevel['templates'][$moduleTemplate[0]]['type'] = 'skip';
            }
        }
    }

    // Install Template Variables
    if ($installLevel === 5 && $errorData === false && (isset($_POST['tv']) || $installData)) {
        $selTVs = array_filter((array)($_POST['tv'] ?? []), 'is_numeric');
        foreach ($moduleTVs as $k => $moduleTV) {
            $templateVariablesData = [
                'name' => $moduleTV[0],
                'desc' => $moduleTV[2],
                'caption' => $moduleTV[1],
                'category' => $moduleTV[10],
                'locked' => $moduleTV[11],
                'file' => $moduleTV[8],
                'input_type' => $moduleTV[3],
                'input_options' => $moduleTV[4],
                'input_default' => $moduleTV[5],
                'output_widget' => $moduleTV[6],
                'output_widget_params' => $moduleTV[7],
                'assignments' => $moduleTV[9]
            ];
            $installDataLevel['tvs'][$moduleTV[0]] = [
                'data' => $templateVariablesData,
                'type' => '', // update, create
            ];

            $installSample = in_array('sample', $moduleTV[12]) && $installData == 1;
            if ($installSample || in_array($k, $selTVs)) {
                // Create the category if it does not already exist
                $templateVariablesData['category'] = getCreateDbCategory($templateVariablesData['category']);
                $templateVariable = \EvolutionCMS\Models\SiteTmplvar::query()->updateOrCreate(['name' => $templateVariablesData['name']], $templateVariablesData);

                // add template assignments
                $assignments = explode(',', $templateVariablesData['assignments']);

                if (count($assignments) > 0) {
                    \EvolutionCMS\Models\SiteTmplvarTemplate::where('tmplvarid', $templateVariable->getKey());
                    foreach ($assignments as $assignment) {
                        $template = \EvolutionCMS\Models\SiteTemplate::where('templatename', $assignment)->first();
                        if (!is_null($template)) {
                            \EvolutionCMS\Models\SiteTmplvarTemplate::query()->create(['tmplvarid' => $templateVariable->getKey(), 'templateid' => $template->getKey()]);
                        }
                    }
                }
            }
        }
    }

    // Install Chunks
    if ($installLevel === 5 && $errorData === false && (isset ($_POST['chunk']) || $installData)) {
        $selChunks = array_filter((array)($_POST['chunk'] ?? []), 'is_numeric');
        foreach ($moduleChunks as $k => $moduleChunk) {
            if (!is_array($moduleChunk)) {
                continue;
            }
            $installDataLevel['chunks'][$moduleChunk[0]] = [
                'data' => [
                    'desc' => $moduleChunk[1],
                    'category' => $moduleChunk[3],
                    'overwrite' => $moduleChunk[4],
                    'file' => $moduleChunk[2],
                    'installset' => $moduleChunk[5]
                ],
                'type' => '', // update, create, overwrite, skip
            ];
            $installSample = in_array('sample', $moduleChunk[5]) && $installData == 1;
            $count_new_name = 0;
            if ($installSample || in_array($k, $selChunks)) {
                $name = $moduleChunk[0];
                $desc = $moduleChunk[1];
                $category = $moduleChunk[3];
                $overwrite = $moduleChunk[4];
                $filecontent = $moduleChunk[2];

                if (!file_exists($filecontent)) {
                    $installDataLevel['chunks'][$moduleChunk[0]]['error'] = ['type' => 'file_not_found'];
                } else {
                    // Create the category if it does not already exist
                    $category_id = getCreateDbCategory($category);
                    $chunk = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', file_get_contents($filecontent), 1);
                    $chunkRecordOld = \EvolutionCMS\Models\SiteHtmlsnippet::query()->where('name', $name);
                    $count_original_name = $chunkRecordOld->count();
                    if ($overwrite == 'false') {
                        $newname = $name . '-' . str_replace('.', '_', $evo_version);
                        $chunkRecord = \EvolutionCMS\Models\SiteHtmlsnippet::query()->where('name', $newname);
                        $count_new_name = $chunkRecord->count();
                    }
                    $update = $count_original_name > 0 && $overwrite === 'true';
                    if ($update) {
                        $installDataLevel['chunks'][$moduleChunk[0]]['type'] = 'update';
                        $chunkRecordOld->snippet = $chunk;
                        $chunkRecordOld->description = $desc;
                        $chunkRecordOld->category = $category_id;
                        $chunkRecordOld->save();
                    } elseif ($count_new_name == 0) {
                        if ($count_original_name > 0 && $overwrite == 'false') {
                            $installDataLevel['chunks'][$moduleChunk[0]]['type'] = 'overwrite';
                            $installDataLevel['chunks'][$moduleChunk[0]]['newname'] = $newname;
                            $name = $newname;
                        } else {
                            $installDataLevel['chunks'][$moduleChunk[0]]['type'] = 'create';
                        }
                        \EvolutionCMS\Models\SiteHtmlsnippet::insert(['name' => $name, 'description' => $desc, 'snippet' => $chunk, 'category' => $category_id]);
                    }
                }
            } else {
                $installDataLevel['chunks'][$moduleChunk[0]]['type'] = 'skip';
            }
        }
    }

    // Install Modules
    if ($installLevel === 5 && $errorData === false && (isset ($_POST['module']) || $installData)) {
        $selModules = array_filter((array)($_POST['module'] ?? []), 'is_numeric');
        foreach ($moduleModules as $k => $moduleModule) {
            if (!is_array($moduleModule)) {
                continue;
            }
            $installDataLevel['modules'][$moduleModule[0]] = [
                'data' => [
                    'desc' => $moduleModule[1],
                    'category' => $moduleModule[6],
                    'file' => $moduleModule[2],
                    'guid' => $moduleModule[4],
                    'props' => $moduleModule[3],
                    'shared' => $moduleModule[5],
                ],
                'type' => '', // update, create
            ];
            $installSample = in_array('sample', $moduleModule[7]) && $installData == 1;
            if ($installSample || in_array($k, $selModules)) {
                $name = $moduleModule[0];
                $desc = $moduleModule[1];
                $filecontent = $moduleModule[2];
                $properties = $moduleModule[3];
                $guid = $moduleModule[4];
                $shared = $moduleModule[5];
                $category = $moduleModule[6];
                if (!file_exists($filecontent)) {
                    $installDataLevel['modules'][$moduleModule[0]]['error'] = [
                        'type' => 'file_not_found'
                    ];
                } else {
                    // Create the category if it does not already exist
                    $category = getCreateDbCategory($category);
                    $array = preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent), 2);
                    $module = end($array);
                    // $module = removeDocblock($module, 'module'); // Modules have no fileBinding, keep docblock for info-tab
                    $moduleRecord = \EvolutionCMS\Models\SiteModule::query()->where('name', $name);

                    if ($moduleRecord->count() > 0) {
                        $installDataLevel['modules'][$moduleModule[0]]['type'] = 'update';
                        $moduleRecord = $moduleRecord->first();
                        $props = propUpdate($properties, $moduleRecord->properties);
                        $moduleRecord->properties = $props;
                        $moduleRecord->modulecode = $module;
                        $moduleRecord->description = $desc;
                        $moduleRecord->enable_sharedparams = (int)$shared;
                        $moduleRecord->save();
                    } else {
                        $installDataLevel['modules'][$moduleModule[0]]['type'] = 'create';
                        $properties = parseProperties($properties, true);
                        \EvolutionCMS\Models\SiteModule::create(['name' => $name,
                            'description' => $desc, 'modulecode' => $module, 'properties' => $properties,
                            'guid' => $guid, 'enable_sharedparams' => (int)$shared, 'category' => $category]);
                    }
                }
            } else {
                $installDataLevel['modules'][$moduleModule[0]]['type'] = 'skip';
            }
        }
    }
    // Install Plugins
    if ($installLevel === 5 && $errorData === false && (isset ($_POST['plugin']) || $installData)) {
        $selPlugs = array_filter((array)($_POST['plugin'] ?? []), 'is_numeric');
        foreach ($modulePlugins as $k => $modulePlugin) {
            if (!is_array($modulePlugin)) {
                continue;
            }
            $installDataLevel['plugins'][$modulePlugin[0]] = [
                'data' => [
                    'desc' => $modulePlugin[1],
                    'file' => $modulePlugin[2],
                    'category' => $modulePlugin[6],
                    'guid' => $modulePlugin[5],
                    'disabled' => $modulePlugin[9],
                    'events' => explode(',', $modulePlugin[4]),
                    'props' => $modulePlugin[3]
                ],
                'type' => '', // update, create
            ];

            $installSample = is_array($modulePlugin[8]) && in_array('sample', $modulePlugin[8]) && $installData == 1;

            if ($installSample || in_array($k, $selPlugs)) {
                $name = $modulePlugin[0];
                $desc = $modulePlugin[1];
                $filecontent = $modulePlugin[2];
                $properties = $modulePlugin[3];
                $events = explode(',', $modulePlugin[4]);
                $guid = $modulePlugin[5];
                if (is_null($guid)) $guid = '';
                $category = $modulePlugin[6];
                $leg_names = '';
                $disabled = $modulePlugin[9];
                if (array_key_exists(7, $modulePlugin)) {
                    // parse comma-separated legacy names and prepare them for sql IN clause
                    $leg_names = preg_split('/\s*,\s*/', $modulePlugin[7]);
                }
                if (!file_exists($filecontent)) {
                    $installDataLevel['plugins'][$modulePlugin[0]]['error'] = [
                        'type' => 'file_not_found'
                    ];
                } else {
                    // disable legacy versions based on legacy_names provided
                    if (!empty($leg_names)) {
                        \EvolutionCMS\Models\SitePlugin::query()->whereIn('name', $leg_names)->update(['disabled' => 1]);
                    }

                    // Create the category if it does not already exist
                    $category = getCreateDbCategory($category);
                    $array1 = preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent), 2);
                    $plugin = end($array1);
                    $plugin = removeDocblock($plugin, 'plugin');
                    $pluginRecords = \EvolutionCMS\Models\SitePlugin::query()->where('name', $name);

                    $prev_id = null;
                    if ($pluginRecords->count() > 0) {
                        $installDataLevel['plugins'][$modulePlugin[0]]['type'] = 'update';
                        $insert = true;
                        $pluginRecords = $pluginRecords->get();
                        foreach ($pluginRecords as $pluginRecord) {
                            $props = propUpdate($properties, $pluginRecord->properties);
                            if ($pluginRecord->description == $desc) {
                                $pluginRecord->plugincode = $plugin;
                                $pluginRecord->description = $desc;
                                $pluginRecord->properties = $props;
                                $pluginRecord->save();
                                $insert = false;
                            } else {
                                $pluginRecord->disabled = 1;
                                $pluginRecord->save();
                            }
                            $prev_id = $pluginRecord->getKey();
                        }
                        if ($insert === true) {
                            \EvolutionCMS\Models\SitePlugin::create(['name' => $name, 'description' => $desc, 'plugincode' => $plugin, 'properties' => $props, 'moduleguid' => $guid, 'disabled' => 0, 'category' => $category]);
                        }
                    } else {
                        $installDataLevel['plugins'][$modulePlugin[0]]['type'] = 'create';
                        $properties = parseProperties($properties, true);
                        \EvolutionCMS\Models\SitePlugin::create(['name' => $name, 'description' => $desc, 'plugincode' => $plugin, 'properties' => $properties, 'moduleguid' => $guid, 'disabled' => $disabled, 'category' => $category]);
                    }
                    // add system events
                    if (count($events) > 0) {
                        $sitePlugin = \EvolutionCMS\Models\SitePlugin::where('name', $name)->where('description', $desc)->first();
                        if (!is_null($sitePlugin)) {
                            $id = $sitePlugin->id;

                            $_events = implode("','", $events);
                            // add new events
                            foreach ($events as $event) {
                                $eventName = \EvolutionCMS\Models\SystemEventname::where('name', $event)->first();
                                if (!is_null($eventName)) {
                                    $prev_priority = null;
                                    if ($prev_id) {
                                        $pluginEvent = \EvolutionCMS\Models\SitePluginEvent::query()
                                            ->where('pluginid', $prev_id)
                                            ->where('evtid', $eventName->getKey())
                                            ->first();
                                        if (!is_null($pluginEvent)) {
                                            $prev_priority = $pluginEvent->priority;
                                        }
                                    }
                                    if (is_null($prev_priority)) {
                                        $pluginEvent = \EvolutionCMS\Models\SitePluginEvent::query()
                                            ->where('evtid', $eventName->getKey())
                                            ->orderBy('priority', 'DESC')
                                            ->first();
                                        if (!is_null($pluginEvent)) {
                                            $prev_priority = $pluginEvent->priority;
                                            $prev_priority++;
                                        }
                                    }
                                    if (is_null($prev_priority)) {
                                        $prev_priority = 0;
                                    }
                                    $arrInsert = ['pluginid' => $id, 'evtid' => $eventName->getKey(), 'priority' => $prev_priority];
                                    \EvolutionCMS\Models\SitePluginEvent::query()->firstOrCreate($arrInsert);
                                }
                            }

                            // remove absent events
                            \EvolutionCMS\Models\SitePluginEvent::query()->join('system_eventnames', function ($join) use ($events) {
                                $join->on('site_plugin_events.evtid', '=', 'system_eventnames.id')
                                    ->whereIn('name', $events);
                            })
                                ->whereNull('name')
                                ->where('pluginid', $id)
                                ->delete();
                        }
                    }
                }
            } else {
                $installDataLevel['plugins'][$modulePlugin[0]]['type'] = 'skip';
            }
        }
    }

    // Install Snippets
    if ($installLevel === 5 && $errorData === false && (isset ($_POST['snippet']) || $installData)) {
        $selSnips = array_filter((array)($_POST['snippet'] ?? []), 'is_numeric');
        foreach ($moduleSnippets as $k => $moduleSnippet) {
            if (!is_array($moduleSnippet)) {
                continue;
            }
            $installDataLevel['snippets'][$moduleSnippet[0]] = [
                'data' => [
                    'desc' => $moduleSnippet[1],
                    'category' => $moduleSnippet[4],
                    'props' => $moduleSnippet[3],
                    'file' => $moduleSnippet[2]
                ],
                'type' => '', // update, create, skip
            ];
            $installSample = in_array('sample', $moduleSnippet[5]) && $installData == 1;
            if ($installSample || in_array($k, $selSnips)) {
                $name = $moduleSnippet[0];
                $desc = $moduleSnippet[1];
                $filecontent = $moduleSnippet[2];
                $properties = $moduleSnippet[3];
                $category = $moduleSnippet[4];
                if (!file_exists($filecontent)) {
                    $installDataLevel['snippets'][$moduleSnippet[0]]['error'] = [
                        'type' => 'file_not_found'
                    ];
                } else {
                    // Create the category if it does not already exist
                    $category = getCreateDbCategory($category);
                    $array2 = preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent));
                    $snippet = end($array2);
                    $snippet = removeDocblock($snippet, 'snippet');
                    $snippetRecord = \EvolutionCMS\Models\SiteSnippet::where('name', $name);

                    if ($snippetRecord->count() > 0) {
                        $snippetRecord = $snippetRecord->first();
                        $installDataLevel['snippets'][$moduleSnippet[0]]['type'] = 'update';

                        $props = propUpdate($properties, $snippetRecord->properties);
                        $snippetRecord->snippet = $snippet;
                        $snippetRecord->description = $desc;
                        $snippetRecord->properties = $props;
                        $snippetRecord->save();
                    } else {
                        $installDataLevel['snippets'][$moduleSnippet[0]]['type'] = 'create';
                        $properties = parseProperties($properties, true);
                        \EvolutionCMS\Models\SiteSnippet::create(['name' => $name, 'description' => $desc, 'snippet' => $snippet, 'properties' => $properties, 'category' => (int)$category]);
                    }
                }
            } else {
                $installDataLevel['snippets'][$moduleSnippet[0]]['type'] = 'skip';
            }
        }
    }

    // Install demo-site
    if ($installLevel === 5 && $errorData === false && ($installData && $moduleSQLDataFile)) {
        $installDataLevel['demo'] = [];
        $sqlParser->process($moduleSQLDataFile);
        // display database results
        if ($sqlParser->installFailed === true) {
            $errors += 1;
            $sqlErrors = count($sqlParser->mysqlErrors);
            $installDataLevel['demo']['error'] = [];
            for ($i = 0; $i < $sqlErrors; $i++) {
                $installDataLevel['demo']['error'][] = [
                    'content' => $sqlParser->mysqlErrors[$i]['error'],
                    'sql' => $sqlParser->mysqlErrors[$i]['sql']
                ];
            }
            $errorData = true;
        } else {
            $installLevel = 6;
            $sql = "SELECT id FROM `" . table_prefix('site_templates') . "` WHERE templatename='Evolution CMS startup - Bootstrap'";
            $stmt = $sqlParser->conn->query($sql);
            if ($stmt->rowCount()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $sql = "UPDATE `" . table_prefix('site_content') . "` SET template=" . (int)$row['id'] . " WHERE template=4";
                $sqlParser->conn->exec($sql);
            }
        }
    }

    if ($errorData === false) {
        $installLevel = 6;
    }

    $errorInstall = false;
    if ($installLevel === 6) {
        $installDependencyLevel = [];

        // Install Dependencies
        foreach ($moduleDependencies as $dependency) {
            $installDependencyLevel[$dependency['module']] = [];
            $modules = \EvolutionCMS\Models\SiteModule::where('name', $dependency['module'])->first();
            $moduleId = $modules->id;
            $moduleGuid = $modules->guid;
            // get extra id
            $dependencyRecord = \DB('site_' . $dependency['table'])->where($dependency['column'], $dependency['name'])->first();
            $extraId = $dependencyRecord->getKey();
            $moduleDependenciesRecord = \EvolutionCMS\Models\SiteModuleDepobj::query()
                ->where('module', (int)$moduleId)
                ->where('resource', (int)$extraId)
                ->where('type', (int)$dependency['type'])->first();
            // setup extra as module dependency

            if (is_null($moduleDependenciesRecord)) {
                $moduleDependenciesRecord = \EvolutionCMS\Models\SiteModuleDepobj::create(['module' => (int)$moduleId, 'resource' => (int)$extraId, 'type' => (int)$dependency['type']]);
                $installDependencyLevel[$dependency['module']]['type'] = 'create';
            } else {
                $moduleDependenciesRecord->module = (int)$moduleId;
                $moduleDependenciesRecord->resource = (int)$extraId;
                $moduleDependenciesRecord->type = (int)$dependency['type'];
                $moduleDependenciesRecord->save();
                $installDependencyLevel[$dependency['module']]['type'] = 'update';
            }
            if ($dependency['type'] == 30 || $dependency['type'] == 40) {
                // set extra guid for plugins and snippets
                $dependencyRecord = \DB('site_' . $dependency['table'])->where($dependency['column'], $dependency['name'])->first();

                if (!is_null($dependencyRecord)) {
                    $dependencyRecord->moduleguid = (int)$moduleGuid;
                    $dependencyRecord->save();
                }
            }
        }
        if ($errorInstall === false) {
            $installLevel = 7;
        }
    }

    if ($installLevel === 7) {
        if (file_exists(MODX_BASE_PATH.'assets/cache/installProc.inc.php')) {
            unlink(MODX_BASE_PATH.'assets/cache/installProc.inc.php');
        }
        if (file_exists($base_path.'install.session.php')) {
            unlink($base_path.'install.session.php');
        }
        $bootstrapDir = EVO_STORAGE_PATH . 'bootstrap/';
        $siteCacheFile = $bootstrapDir . 'siteCache.idx.php';
        if (file_exists($siteCacheFile)) {
            unlink($siteCacheFile);
        }
        $sitePublishingFile = $bootstrapDir . 'sitePublishing.idx.php';
        if (file_exists($sitePublishingFile)) {
            unlink($sitePublishingFile);
        }
        file_put_contents(EVO_CORE_PATH . '.install', time());
    }

} catch (PDOException $e) {
    if (!stristr($e->getMessage(), 'database "' . $database_name . '" does not exist') &&
        !stristr($e->getMessage(), 'Unknown database \'' . $database_name . '\'')) {
        echo ($output ?? '') . '<span id="database_fail">' . $_lang['status_failed'] . ' ' . $e->getMessage() . '</span>';
        exit();
    }
}
include_once dirname(__DIR__) . '/template/actions/install.php';

function table_prefix($table_name = ''): string {
    $table_prefix = validateTablePrefix($_POST['table_prefix'] ?? \DB::getTablePrefix());
    return $table_prefix . $table_name;
}
