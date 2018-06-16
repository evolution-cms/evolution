<?php
if (file_exists(dirname(dirname(dirname(__DIR__))) . "/assets/cache/siteManager.php")) {
    include_once(dirname(dirname(dirname(__DIR__))) . "/assets/cache/siteManager.php");
} else {
    define('MGR_DIR', 'manager');
}
define('MODX_CLI', false);

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
@ set_time_limit(120); // used @ to prevent warning when using safe mode?

$installMode = (int)$_POST['installmode'];
$installData = (int)!empty($_POST['installdata']);

// get db info from post
$database_server = $_POST['databasehost'];
$database_user = $_SESSION['databaseloginname'];
$database_password = $_SESSION['databaseloginpassword'];
$database_collation = $_POST['database_collation'];
$database_charset = substr($database_collation, 0, strpos($database_collation, '_'));
$database_connection_charset = $_POST['database_connection_charset'];
$database_connection_method = $_POST['database_connection_method'];
$dbase = "`" . $_POST['database_name'] . "`";
$table_prefix = $_POST['tableprefix'];
$adminname = $_POST['cmsadmin'];
$adminemail = $_POST['cmsadminemail'];
$adminpass = $_POST['cmspassword'];
$managerlanguage = $_POST['managerlanguage'];
$custom_placeholders = array();

// set session name variable
if (!isset ($site_sessionname)) {
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
$base_url = $url . (substr($url, -1) != '/' ? '/' : '');
$base_path = $pth . (substr($pth, -1) != '/' ? '/' : '');

// connect to the database
$conn = @mysqli_connect($database_server, $database_user, $database_password);
$installLevel = 0;
if ($conn) {
    $installLevel = 0;
    // select database
    $selectDatabase = mysqli_select_db($conn, str_replace('`', '', $dbase));
    if ($selectDatabase) {
        if (function_exists('mysqli_set_charset')) {
            mysqli_set_charset($conn, $database_charset);
        }
        mysqli_query($conn, "{$database_connection_method} {$database_connection_charset}");
        $installLevel = 1;
    } else {
        // try to create the database
        $query = "CREATE DATABASE $dbase DEFAULT CHARACTER SET $database_charset COLLATE $database_collation";
        $createDatabase = mysqli_query($conn, $query);
        if ($createDatabase === false) {
            $errors += 1;
        } else {
            $installLevel = 1;
        }
    }

    if ($installLevel === 1) {
        // check table prefix
        if ($installMode === 0) {
            $query = "SELECT COUNT(*) FROM $dbase.`" . $table_prefix . "site_content`";
            if (@mysqli_query($conn, $query)) {
                $errors += 1;
            } else {
                $installLevel = 2;
            }
        } else {
            $installLevel = 2;
        }
    }

    if ($installLevel === 2) {
        // check status of Inherit Parent Template plugin
        $auto_template_logic = 'parent';
        if ($installMode !== 0) {
            $query = "SELECT properties, disabled FROM " . $dbase . ".`" . $table_prefix . "site_plugins` WHERE name='Inherit Parent Template'";
            $rs = mysqli_query($conn, $query);
            $row = mysqli_fetch_row($rs);
            if (!$row) {
                // not installed
                $auto_template_logic = 'system';
            } else {
                if ($row[1] == 1) {
                    // installed but disabled
                    $auto_template_logic = 'system';
                } else {
                    // installed, enabled .. see how it's configured
                    $properties = parseProperties($row[0]);
                    if (isset($properties['inheritTemplate'])) {
                        if ($properties['inheritTemplate'] === 'From First Sibling') {
                            $auto_template_logic = 'sibling';
                        }
                    }
                }
            }
        }

        // open db connection
        include dirname(__DIR__) . '/processor/result.php';
        include_once dirname(__DIR__) . '/sqlParser.class.php';
        $sqlParser = new SqlParser(
            $database_server,
            $database_user,
            $database_password,
            str_replace("`", "", $dbase),
            $table_prefix,
            $adminname,
            $adminemail,
            $adminpass,
            $database_connection_charset,
            $managerlanguage,
            $database_connection_method,
            $auto_template_logic
        );
        $sqlParser->mode = ($installMode < 1) ? 'new' : 'upd';
        $sqlParser->ignoreDuplicateErrors = true;
        $sqlParser->connect();

        // install/update database
        if ($moduleSQLBaseFile) {
            $sqlParser->process($moduleSQLBaseFile);
            // display database results
            if ($sqlParser->installFailed == true) {
                $errors += 1;
            } else {
                $installLevel = 3;
            }
        } else {
            $installLevel = 3;
        }
    }

    if ($installLevel === 3) {
        // write the config.inc.php file if new installation
        $confph = array();
        $confph['database_server'] = $database_server;
        $confph['user_name'] = mysqli_real_escape_string($conn, $database_user);
        $confph['password'] = mysqli_real_escape_string($conn, $database_password);
        $confph['connection_charset'] = $database_connection_charset;
        $confph['connection_method'] = $database_connection_method;
        $confph['dbase'] = str_replace('`', '', $dbase);
        $confph['table_prefix'] = $table_prefix;
        $confph['lastInstallTime'] = time();
        $confph['site_sessionname'] = $site_sessionname;

        $configString = file_get_contents(dirname(dirname(__DIR__)) . '/stubs/config.tpl');
        $configString = parse($configString, $confph);

        $filename = dirname(dirname(dirname(__DIR__))) . '/' . MGR_DIR . '/includes/config.inc.php';
        $configFileFailed = false;
        if (@ !$handle = fopen($filename, 'w')) {
            $configFileFailed = true;
        }

        // write $somecontent to our opened file.
        if (@ fwrite($handle, $configString) === false) {
            $configFileFailed = true;
        }
        @ fclose($handle);

        // try to chmod the config file go-rwx (for suexeced php)
        @chmod($filename, 0404);

        if ($configFileFailed === true) {
            $errors += 1;
        } else {
            $installLevel = 4;
        }
    }

    if ($installLevel === 4) {
        // generate new site_id and set manager theme to default
        if ($installMode == 0) {
            $siteid = uniqid('');
            mysqli_query(
                    $sqlParser->conn,
                "REPLACE INTO $dbase.`" . $table_prefix . "system_settings` (setting_name,setting_value) VALUES('site_id','$siteid'),('manager_theme','default')"
            );
        } else {
            // update site_id if missing
            $ds = mysqli_query(
                    $sqlParser->conn,
                "SELECT setting_name,setting_value FROM $dbase.`" . $table_prefix . "system_settings` WHERE setting_name='site_id'"
            );
            if ($ds) {
                $r = mysqli_fetch_assoc($ds);
                $siteid = $r['setting_value'];
                if ($siteid == '' || $siteid === 'MzGeQ2faT4Dw06+U49x3') {
                    $siteid = uniqid('');
                    mysqli_query(
                            $sqlParser->conn,
                        "REPLACE INTO $dbase.`" . $table_prefix . "system_settings` (setting_name,setting_value) VALUES('site_id','$siteid')"
                    );
                }
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

    $installDataLevel = array();
    $errorData = false;
    // Install Templates
    if ($installLevel === 5 && (isset ($_POST['template']) || $installData)) {
        $selTemplates = $_POST['template'];
        foreach ($moduleTemplates as $k => $moduleTemplate) {
            if (! is_array($moduleTemplate)) {
                continue;
            }
            $installDataLevel['templates'][$moduleTemplate[0]] = array(
                'data' => array(
                    'desc' =>    $moduleTemplate[1],
                    'category' => $moduleTemplate[4],
                    'locked' => $moduleTemplate[5],
                    'file' => $moduleTemplate[3],
                    'id' => $moduleTemplate[7],
                ),
                'type' => '', // update, create
                /*'error' => array(
                    'type' => '' // sql, file_not_found
                    'content' => ''
                )*/
            );
            $installSample = in_array('sample', $moduleTemplate[6]) && $installData === 1;
            if ($installSample || in_array($k, $selTemplates)) {
                $name = mysqli_real_escape_string($conn, $moduleTemplate[0]);
                $desc = mysqli_real_escape_string($conn, $moduleTemplate[1]);
                $category = mysqli_real_escape_string($conn, $moduleTemplate[4]);
                $locked = mysqli_real_escape_string($conn, $moduleTemplate[5]);
                $filecontent = $moduleTemplate[3];
                $save_sql_id_as = $moduleTemplate[7]; // Nessecary for demo-site
                if (!file_exists($filecontent)) {
                    $installDataLevel['templates'][$moduleTemplate[0]]['error'] = array(
                        'type' => 'file_not_found'
                    );
                } else {
                    // Create the category if it does not already exist
                    $category_id = getCreateDbCategory($category, $sqlParser);

                    // Strip the first comment up top
                    $template = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', file_get_contents($filecontent), 1);
                    $template = mysqli_real_escape_string($conn, $template);

                    // See if the template already exists
                    $query = "SELECT * FROM $dbase.`" . $table_prefix . "site_templates` WHERE templatename='$name'";
                    $rs = mysqli_query($sqlParser->conn, $query);

                    if (mysqli_num_rows($rs)) {
                        $installDataLevel['templates'][$moduleTemplate[0]]['type'] = 'update';
                        $query = "UPDATE $dbase.`" . $table_prefix . "site_templates` SET content='$template', description='$desc', category=$category_id, locked='$locked'  WHERE templatename='$name' LIMIT 1;";
                        if (!mysqli_query($sqlParser->conn, $query)) {
                            $errors += 1;
                            $installDataLevel['templates'][$moduleTemplate[0]]['error'] = array(
                                'type' => 'sql',
                                'content' => mysqli_error($sqlParser->conn)
                            );
                            $errorData = true;
                            break;
                        }
                        if (!is_null($save_sql_id_as)) {
                            $sql_id = @mysqli_insert_id($sqlParser->conn);
                            if (!$sql_id) {
                                $query = "SELECT id FROM $dbase.`" . $table_prefix . "site_templates` WHERE templatename='$name' LIMIT 1;";
                                $idQuery = mysqli_fetch_assoc(mysqli_query($sqlParser->conn, $query));
                                $sql_id = $idQuery['id'];
                            }
                            $custom_placeholders[$save_sql_id_as] = $sql_id;
                        }
                    } else {
                        $installDataLevel['templates'][$moduleTemplate[0]]['type'] = 'create';
                        $query = "INSERT INTO $dbase.`" . $table_prefix . "site_templates` (templatename,description,content,category,locked) VALUES('$name','$desc','$template',$category_id,'$locked');";
                        if (!@mysqli_query($sqlParser->conn, $query)) {
                            $errors += 1;
                            $installDataLevel['templates'][$moduleTemplate[0]]['error'] = array(
                                'type' => 'sql',
                                'content' => mysqli_error($sqlParser->conn)
                            );
                            $errorData = true;
                            break;
                        }
                        if ($save_sql_id_as !== null) {
                            $custom_placeholders[$save_sql_id_as] = @mysqli_insert_id($sqlParser->conn);
                        }
                    }
                }
            } else {
                $installDataLevel['templates'][$moduleTemplate[0]]['type'] = 'skip';
            }
        }
    }

    // Install Template Variables
    if ($installLevel === 5 && $errorData === false && (isset ($_POST['tv']) || $installData)) {
        $selTVs = $_POST['tv'];
        foreach ($moduleTVs as $k => $moduleTV) {
            $installDataLevel['tvs'][$moduleTV[0]] = array(
                'data' => array(
                    'desc' =>    $moduleTV[2],
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
                ),
                'type' => '', // update, create
                /*'error' => array(
                    'type' => '' // sql, file_not_found
                    'content' => ''
                )*/
            );

            $installSample = in_array('sample', $moduleTV[12]) && $installData == 1;
            if ($installSample || in_array($k, $selTVs)) {
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

                $query = "SELECT * FROM $dbase.`" . $table_prefix . "site_tmplvars` WHERE name='$name'";
                $rs = mysqli_query($sqlParser->conn,$query);
                if (mysqli_num_rows($rs)) {
                    $installDataLevel['tvs'][$moduleTV[0]]['type'] = 'update';
                    while ($row = mysqli_fetch_assoc($rs)) {
                        $query = "UPDATE $dbase.`" . $table_prefix . "site_tmplvars` SET type='$input_type', caption='$caption', description='$desc', category=$category, locked=$locked, elements='$input_options', display='$output_widget', display_params='$output_widget_params', default_text='$input_default' WHERE id={$row['id']};";
                        if (!mysqli_query($sqlParser->conn, $query)) {
                            $installDataLevel['tvs'][$moduleTV[0]]['error'] = array(
                                'type' => 'sql',
                                'content' => mysqli_error($sqlParser->conn)
                            );

                            $errorData = true;
                            break 2;
                        }
                    }
                } else {
                    $installDataLevel['tvs'][$moduleTV[0]]['type'] = 'create';
                    $q = "INSERT INTO $dbase.`" . $table_prefix . "site_tmplvars` (type,name,caption,description,category,locked,elements,display,display_params,default_text) VALUES('$input_type','$name','$caption','$desc',$category,$locked,'$input_options','$output_widget','$output_widget_params','$input_default');";
                    if (!mysqli_query($sqlParser->conn, $q)) {
                        $installDataLevel['tvs'][$moduleTV[0]]['error'] = array(
                            'type' => 'sql',
                            'content' => mysqli_error($sqlParser->conn)
                        );
                        $errorData = true;
                        break;
                    }
                }

                // add template assignments
                $assignments = explode(',', $assignments);

                if (count($assignments) > 0) {

                    // remove existing tv -> template assignments
                    $query = "SELECT id FROM $dbase.`" . $table_prefix . "site_tmplvars` WHERE name='$name' AND description='$desc';";
                    $ds = mysqli_query($sqlParser->conn, $query);
                    $row = mysqli_fetch_assoc($ds);
                    $id = $row["id"];
                    $query = 'DELETE FROM ' . $dbase . '.`' . $table_prefix . 'site_tmplvar_templates` WHERE tmplvarid = \'' . $id . '\'';
                    mysqli_query($sqlParser->conn, $query);

                    // add tv -> template assignments
                    foreach ($assignments as $assignment) {
                        $template = mysqli_real_escape_string($conn, $assignment);
                        $query = "SELECT id FROM $dbase.`" . $table_prefix . "site_templates` WHERE templatename='$template';";
                        $ts = mysqli_query($sqlParser->conn, $query);
                        if ($ds && $ts) {
                            $tRow = mysqli_fetch_assoc($ts);
                            $templateId = $tRow['id'];
                            $query = "INSERT INTO $dbase.`" . $table_prefix . "site_tmplvar_templates` (tmplvarid, templateid) VALUES($id, $templateId)";
                            mysqli_query($sqlParser->conn,$query);
                        }
                    }
                }
            }
        }
    }

    // Install Chunks
    if ($installLevel === 5 && $errorData === false && (isset ($_POST['chunk']) || $installData)) {
        $selChunks = $_POST['chunk'];
        foreach ($moduleChunks as $k => $moduleChunk) {
            if (! is_array($moduleChunk)) {
                continue;
            }
            $installDataLevel['chunks'][$moduleChunk[0]] = array(
                'data' => array(
                    'desc' =>    $moduleChunk[1],
                    'category' => $moduleChunk[3],
                    'overwrite' => $moduleChunk[4],
                    'file' => $moduleChunk[2],
                    'installset' => $moduleChunk[5]
                ),
                'type' => '', // update, create, overwrite, skip
                /*'error' => array(
                    'type' => '' // sql, file_not_found
                    'content' => ''
                )*/
            );
            $installSample = in_array('sample', $moduleChunk[5]) && $installData == 1;
            $count_new_name = 0;
            if ($installSample || in_array($k, $selChunks)) {
                $name = mysqli_real_escape_string($conn, $moduleChunk[0]);
                $desc = mysqli_real_escape_string($conn, $moduleChunk[1]);
                $category = mysqli_real_escape_string($conn, $moduleChunk[3]);
                $overwrite = mysqli_real_escape_string($conn, $moduleChunk[4]);
                $filecontent = $moduleChunk[2];

                if (!file_exists($filecontent)) {
                    $installDataLevel['chunks'][$moduleChunk[0]]['error'] = array(
                        'type' => 'file_not_found'
                    );
                } else {
                    // Create the category if it does not already exist
                    $category_id = getCreateDbCategory($category, $sqlParser);

                    $chunk = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', file_get_contents($filecontent), 1);
                    $chunk = mysqli_real_escape_string($conn, $chunk);
                    $rs = mysqli_query(
                        $sqlParser->conn,
                        "SELECT * FROM $dbase.`" . $table_prefix . "site_htmlsnippets` WHERE name='$name'"
                    );
                    $count_original_name = mysqli_num_rows($rs);
                    if ($overwrite == 'false') {
                        $newname = $name . '-' . str_replace('.', '_', $modx_version);
                        $rs = mysqli_query(
                            $sqlParser->conn,
                            "SELECT * FROM $dbase.`" . $table_prefix . "site_htmlsnippets` WHERE name='$newname'"
                        );
                        $count_new_name = mysqli_num_rows($rs);
                    }
                    $update = $count_original_name > 0 && $overwrite === 'true';
                    if ($update) {
                        $installDataLevel['chunks'][$moduleChunk[0]]['type'] = 'update';
                        if (!mysqli_query($sqlParser->conn,
                            "UPDATE $dbase.`" . $table_prefix . "site_htmlsnippets` SET snippet='$chunk', description='$desc', category=$category_id WHERE name='$name';")) {
                            $errors += 1;
                            $installDataLevel['chunks'][$moduleChunk[0]]['error'] = array(
                                'type' => 'sql',
                                'content' => mysqli_error($sqlParser->conn)
                            );
                            $errorData = true;
                            break;
                        }
                    } elseif ($count_new_name == 0) {
                        if ($count_original_name > 0 && $overwrite == 'false') {
                            $installDataLevel['chunks'][$moduleChunk[0]]['type'] = 'overwrite';
                            $installDataLevel['chunks'][$moduleChunk[0]]['newname'] = $newname;
                            $name = $newname;
                        } else {
                            $installDataLevel['chunks'][$moduleChunk[0]]['type'] = 'create';
                        }
                        $query = "INSERT INTO $dbase.`" . $table_prefix . "site_htmlsnippets` (name,description,snippet,category) VALUES('$name','$desc','$chunk',$category_id);";
                        if (!mysqli_query($sqlParser->conn, $query)) {
                            $errors += 1;
                            $installDataLevel['chunks'][$moduleChunk[0]]['error'] = array(
                                'type' => 'sql',
                                'content' => mysqli_error($sqlParser->conn)
                            );
                            $errorData = true;
                            break;
                        }
                    }
                }
            } else {
                $installDataLevel['chunks'][$moduleChunk[0]]['type'] = 'skip';
            }
        }

    }

    // Install Modules
    if ($installLevel === 5 && $errorData === false && (isset ($_POST['module']) || $installData)) {
        $selModules = $_POST['module'];
        foreach ($moduleModules as $k => $moduleModule) {
            if (! is_array($moduleModule)) {
                continue;
            }
            $installDataLevel['modules'][$moduleModule[0]] = array(
                'data' => array(
                    'desc' =>    $moduleModule[1],
                    'category' => $moduleModule[6],
                    'file' => $moduleModule[2],
                    'guid' => $moduleModule[4],
                    'props' => $moduleModule[3],
                    'shared' => $moduleModule[5],
                ),
                'type' => '', // update, create
                /*'error' => array(
                    'type' => '' // sql, file_not_found
                    'content' => ''
                )*/
            );
            $installSample = in_array('sample', $moduleModule[7]) && $installData == 1;
            if ($installSample || in_array($k, $selModules)) {
                $name = mysqli_real_escape_string($conn, $moduleModule[0]);
                $desc = mysqli_real_escape_string($conn, $moduleModule[1]);
                $filecontent = $moduleModule[2];
                $properties = $moduleModule[3];
                $guid = mysqli_real_escape_string($conn, $moduleModule[4]);
                $shared = mysqli_real_escape_string($conn, $moduleModule[5]);
                $category = mysqli_real_escape_string($conn, $moduleModule[6]);
                if (!file_exists($filecontent)) {
                    $installDataLevel['modules'][$moduleModule[0]]['error'] = array(
                        'type' => 'file_not_found'
                    );
                } else {
                    // Create the category if it does not already exist
                    $category = getCreateDbCategory($category, $sqlParser);

                    $module = end(preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent), 2));
                    // $module = removeDocblock($module, 'module'); // Modules have no fileBinding, keep docblock for info-tab
                    $module = mysqli_real_escape_string($conn, $module);
                    $rs = mysqli_query($sqlParser->conn,
                        "SELECT * FROM $dbase.`" . $table_prefix . "site_modules` WHERE name='$name'");
                    if (mysqli_num_rows($rs)) {
                        $installDataLevel['modules'][$moduleModule[0]]['type'] = 'update';
                        $row = mysqli_fetch_assoc($rs);
                        $props = mysqli_real_escape_string($conn, propUpdate($properties, $row['properties']));
                        if (!mysqli_query($sqlParser->conn,
                            "UPDATE $dbase.`" . $table_prefix . "site_modules` SET modulecode='$module', description='$desc', properties='$props', enable_sharedparams='$shared' WHERE name='$name';")) {
                            $installDataLevel['modules'][$moduleModule[0]]['error'] = array(
                                'type' => 'sql',
                                'content' => mysqli_error($sqlParser->conn)
                            );
                            $errorData = true;
                            break;
                        }
                    } else {
                        $installDataLevel['modules'][$moduleModule[0]]['type'] = 'create';
                        $properties = mysqli_real_escape_string($conn, parseProperties($properties, true));
                        if (!mysqli_query($sqlParser->conn,
                            "INSERT INTO $dbase.`" . $table_prefix . "site_modules` (name,description,modulecode,properties,guid,enable_sharedparams,category) VALUES('$name','$desc','$module','$properties','$guid','$shared', $category);")) {
                            $installDataLevel['modules'][$moduleModule[0]]['error'] = array(
                                'type' => 'sql',
                                'content' => mysqli_error($sqlParser->conn)
                            );
                            $errorData = true;
                            break;
                        }
                    }
                }
            } else {
                $installDataLevel['modules'][$moduleModule[0]]['type'] = 'skip';
            }
        }
    }

    // Install Plugins
    if ($installLevel === 5 && $errorData === false && (isset ($_POST['plugin']) || $installData)) {
        $selPlugs = $_POST['plugin'];
        foreach ($modulePlugins as $k => $modulePlugin) {
            if (! is_array($modulePlugin)) {
                continue;
            }
            $installDataLevel['plugins'][$modulePlugin[0]] = array(
                'data' => array(
                    'desc' =>    $modulePlugin[1],
                    'file' => $modulePlugin[2],
                    'category' => $modulePlugin[6],
                    'guid' => $modulePlugin[5],
                    'disabled' => $modulePlugin[9],
                    'events' => explode(',', $modulePlugin[4]),
                    'props' => $modulePlugin[3]
                ),
                'type' => '', // update, create
                /*'error' => array(
                    'type' => '' // sql, file_not_found
                    'content' => ''
                )*/
            );

            $installSample = is_array($modulePlugin[8]) && in_array('sample', $modulePlugin[8]) && $installData == 1;

            if ($installSample || in_array($k, $selPlugs)) {
                $name = mysqli_real_escape_string($conn, $modulePlugin[0]);
                $desc = mysqli_real_escape_string($conn, $modulePlugin[1]);
                $filecontent = $modulePlugin[2];
                $properties = $modulePlugin[3];
                $events = explode(",", $modulePlugin[4]);
                $guid = mysqli_real_escape_string($conn, $modulePlugin[5]);
                $category = mysqli_real_escape_string($conn, $modulePlugin[6]);
                $leg_names = '';
                $disabled = $modulePlugin[9];
                if (array_key_exists(7, $modulePlugin)) {
                    // parse comma-separated legacy names and prepare them for sql IN clause
                    $leg_names = "'" . implode(
                            "','",
                            preg_split('/\s*,\s*/', mysqli_real_escape_string($conn, $modulePlugin[7]))
                        ) . "'";
                }
                if (! file_exists($filecontent)) {
                    $installDataLevel['plugins'][$modulePlugin[0]]['error'] = array(
                        'type' => 'file_not_found'
                    );
                } else {

                    // disable legacy versions based on legacy_names provided
                    if (!empty($leg_names)) {
                        $update_query = "UPDATE $dbase.`" . $table_prefix . "site_plugins` SET disabled='1' WHERE name IN ($leg_names);";
                        $rs = mysqli_query($sqlParser->conn, $update_query);
                    }

                    // Create the category if it does not already exist
                    $category = getCreateDbCategory($category, $sqlParser);

                    $plugin = end(preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent), 2));
                    $plugin = removeDocblock($plugin, 'plugin');
                    $plugin = mysqli_real_escape_string($conn, $plugin);
                    $query = "SELECT * FROM $dbase.`" . $table_prefix . "site_plugins` WHERE name='$name'";
                    $rs = mysqli_query($sqlParser->conn, $query);
                    if (mysqli_num_rows($rs)) {
                        $installDataLevel['plugins'][$modulePlugin[0]]['type'] = 'update';
                        $insert = true;
                        while ($row = mysqli_fetch_assoc($rs)) {
                            $props = mysqli_real_escape_string($conn, propUpdate($properties, $row['properties']));
                            if ($row['description'] == $desc) {
                                $query = "UPDATE $dbase.`" . $table_prefix . "site_plugins` SET plugincode='$plugin', description='$desc', properties='$props' WHERE id={$row['id']};";
                                if (!mysqli_query($sqlParser->conn, $query)) {
                                    $installDataLevel['plugins'][$modulePlugin[0]]['error'] = array(
                                        'type' => 'sql',
                                        'content' => mysqli_error($sqlParser->conn)
                                    );
                                    $errorData = true;
                                    break 2;
                                }
                                $insert = false;
                            } else {
                                $query = "UPDATE $dbase.`" . $table_prefix . "site_plugins` SET disabled='1' WHERE id={$row['id']};";
                                if (!mysqli_query($sqlParser->conn, $query)) {
                                    $installDataLevel['plugins'][$modulePlugin[0]]['error'] = array(
                                        'type' => 'sql',
                                        'content' => mysqli_error($sqlParser->conn)
                                    );
                                    $errorData = true;
                                    break 2;
                                }
                            }
                        }
                        if ($insert === true) {
                            $properties = mysqli_real_escape_string($conn, propUpdate($properties, $row['properties']));
                            $query = "INSERT INTO $dbase.`" . $table_prefix . "site_plugins` (name,description,plugincode,properties,moduleguid,disabled,category) VALUES('$name','$desc','$plugin','$properties','$guid','0',$category);";
                            if (!mysqli_query($sqlParser->conn, $query)) {
                                $installDataLevel['plugins'][$modulePlugin[0]]['error'] = array(
                                    'type' => 'sql',
                                    'content' => mysqli_error($sqlParser->conn)
                                );
                                $errorData = true;
                                break;
                            }
                        }
                    } else {
                        $installDataLevel['plugins'][$modulePlugin[0]]['type'] = 'create';
                        $properties = mysqli_real_escape_string($conn, parseProperties($properties, true));
                        $query = "INSERT INTO $dbase.`" . $table_prefix . "site_plugins` (name,description,plugincode,properties,moduleguid,category,disabled) VALUES('$name','$desc','$plugin','$properties','$guid',$category,$disabled);";
                        if (!mysqli_query($sqlParser->conn, $query)) {
                            $installDataLevel['plugins'][$modulePlugin[0]]['error'] = array(
                                'type' => 'sql',
                                'content' => mysqli_error($sqlParser->conn)
                            );
                            $errorData = true;
                            break;
                        }
                    }
                    // add system events
                    if (count($events) > 0) {
                        $query = "SELECT id FROM $dbase.`" . $table_prefix . "site_plugins` WHERE name='$name' AND description='$desc';";
                        $ds = mysqli_query($sqlParser->conn, $query);
                        if ($ds) {
                            $row = mysqli_fetch_assoc($ds);
                            $id = $row["id"];
                            // remove existing events
                            $query = 'DELETE FROM ' . $dbase . '.`' . $table_prefix . 'site_plugin_events` WHERE pluginid = \'' . $id . '\'';
                            mysqli_query($sqlParser->conn, $query);
                            // add new events
                            $query = "INSERT INTO $dbase.`" . $table_prefix . "site_plugin_events` (pluginid, evtid) SELECT '$id' as 'pluginid',se.id as 'evtid' FROM $dbase.`" . $table_prefix . "system_eventnames` se WHERE name IN ('" . implode("','", $events) . "')";
                            mysqli_query($sqlParser->conn,$query);
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
        $selSnips = $_POST['snippet'];
        foreach ($moduleSnippets as $k => $moduleSnippet) {
            if (! is_array($moduleSnippet)) {
                continue;
            }
            $installDataLevel['snippets'][$moduleSnippet[0]] = array(
                'data' => array(
                    'desc' =>    $moduleSnippet[1],
                    'category' => $moduleSnippet[4],
                    'props' => $moduleSnippet[3],
                    'file' => $moduleSnippet[2]
                ),
                'type' => '', // update, create, skip
                /*'error' => array(
                    'type' => '' // sql, file_not_found
                    'content' => ''
                )*/
            );
            $installSample = in_array('sample', $moduleSnippet[5]) && $installData == 1;
            if ($installSample || in_array($k, $selSnips)) {
                $name = mysqli_real_escape_string($conn, $moduleSnippet[0]);
                $desc = mysqli_real_escape_string($conn, $moduleSnippet[1]);
                $filecontent = $moduleSnippet[2];
                $properties = $moduleSnippet[3];
                $category = mysqli_real_escape_string($conn, $moduleSnippet[4]);
                if (!file_exists($filecontent)) {
                    $installDataLevel['snippets'][$moduleSnippet[0]]['error'] = array(
                        'type' => 'file_not_found'
                    );
                } else {
                    // Create the category if it does not already exist
                    $category = getCreateDbCategory($category, $sqlParser);

                    $snippet = end(preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent)));
                    $snippet = removeDocblock($snippet, 'snippet');
                    $snippet = mysqli_real_escape_string($conn, $snippet);
                    $rs = mysqli_query($sqlParser->conn,
                        "SELECT * FROM $dbase.`" . $table_prefix . "site_snippets` WHERE name='$name'");
                    if (mysqli_num_rows($rs)) {
                        $installDataLevel['snippets'][$moduleSnippet[0]]['type'] = 'update';
                        $row = mysqli_fetch_assoc($rs);
                        $props = mysqli_real_escape_string($conn, propUpdate($properties, $row['properties']));
                        if (!mysqli_query($sqlParser->conn,
                            "UPDATE $dbase.`" . $table_prefix . "site_snippets` SET snippet='$snippet', description='$desc', properties='$props' WHERE name='$name';")) {
                            $installDataLevel['snippets'][$moduleSnippet[0]]['error'] = array(
                                'type' => 'sql',
                                'content' => mysqli_error($sqlParser->conn)
                            );
                            $errorData = true;
                            break;
                        }
                    } else {
                        $installDataLevel['snippets'][$moduleSnippet[0]]['type'] = 'create';
                        $properties = mysqli_real_escape_string($conn, parseProperties($properties, true));
                        if (!mysqli_query($sqlParser->conn,
                            "INSERT INTO $dbase.`" . $table_prefix . "site_snippets` (name,description,snippet,properties,category) VALUES('$name','$desc','$snippet','$properties',$category);")) {
                            $installDataLevel['snippets'][$moduleSnippet[0]]['error'] = array(
                                'type' => 'sql',
                                'content' => mysqli_error($sqlParser->conn)
                            );
                            $errorData = true;
                            break;
                        }
                    }
                }
            } else {
                $installDataLevel['snippets'][$moduleSnippet[0]]['type'] = 'skip';
            }
        }
    }

    // Install demo-site
    if ($installLevel === 5 && $errorData === false && ($installData && $moduleSQLDataFile)) {
        $installDataLevel['demo'] = array();
        $sqlParser->process($moduleSQLDataFile);
        // display database results
        if ($sqlParser->installFailed === true) {
            $errors += 1;
            $sqlErrors = count($sqlParser->mysqlErrors);
            $installDataLevel['demo']['error'] = array();
            for ($i = 0; $i < $sqlErrors; $i++) {
                $installDataLevel['demo']['error'][] = array(
                    'content' => $sqlParser->mysqlErrors[$i]['error'],
                    'sql' => $sqlParser->mysqlErrors[$i]['sql']
                );
            }
            $errorData = true;
        } else {
            $installLevel = 6;
            $sql = sprintf("SELECT id FROM `%ssite_templates` WHERE templatename='EVO startup - Bootstrap'",
                $sqlParser->prefix);
            $rs = mysqli_query($sqlParser->conn, $sql);
            if (mysqli_num_rows($rs)) {
                $row = mysqli_fetch_assoc($rs);
                $sql = sprintf('UPDATE `%ssite_content` SET template=%s WHERE template=4', $sqlParser->prefix,
                    $row['id']);
                mysqli_query($sqlParser->conn, $sql);
            }
        }
    }

    if ($errorData === false) {
        $installLevel = 6;
    }

    $errorInstall = false;
    if ($installLevel === 6) {
        $installDependencyLevel = array();

        // Install Dependencies
        foreach ($moduleDependencies as $dependency) {
            $installDependencyLevel[$dependency['module']] = array(
                // 'type' => '' //create, update
                /*'error' => array(
                    'type' => 'sql',
                    'content' => ''
                )*/
                /*'extra' => array(
                    'type' => '', //error, done
                    'content' => '' //dependency name or error message
                )*/
            );
            $query = 'SELECT id, guid FROM ' . $dbase . '`' . $sqlParser->prefix . 'site_modules` WHERE name="' . $dependency['module'] . '"';
            $ds = mysqli_query($sqlParser->conn, $query);
            if (!$ds) {
                $installDependencyLevel[$dependency['module']]['error'] = array(
                    'type' => 'sql',
                    'content' => mysqli_error($sqlParser->conn)
                );
                $errorInstall = true;
                break;
            } else {
                $row = mysqli_fetch_assoc($ds);
                $moduleId = $row["id"];
                $moduleGuid = $row["guid"];
            }
            // get extra id
            $query = 'SELECT id FROM ' . $dbase . '`' . $sqlParser->prefix . 'site_' . $dependency['table'] . '` WHERE ' . $dependency['column'] . '="' . $dependency['name'] . '"';
            $ds = mysqli_query($sqlParser->conn, $query);
            if (!$ds) {
                $installDependencyLevel[$dependency['module']]['error'] = array(
                    'type' => 'sql',
                    'content' => mysqli_error($sqlParser->conn)
                );
                $errorInstall = true;
                break;
            } else {
                $row = mysqli_fetch_assoc($ds);
                $extraId = $row["id"];
            }
            // setup extra as module dependency
            $query = 'SELECT module FROM ' . $dbase . '`' . $sqlParser->prefix . 'site_module_depobj` WHERE module=' . $moduleId . ' AND resource=' . $extraId . ' AND type=' . $dependency['type'] . ' LIMIT 1';
            $ds = mysqli_query($sqlParser->conn, $query);
            if (!$ds) {
                $installDependencyLevel[$dependency['module']]['error'] = array(
                    'type' => 'sql',
                    'content' => mysqli_error($sqlParser->conn)
                );
                $errorInstall = true;
                break;
            } else {
                if (mysqli_num_rows($ds) === 0) {
                    $query = 'INSERT INTO ' . $dbase . '`' . $sqlParser->prefix . 'site_module_depobj` (module, resource, type) VALUES(' . $moduleId . ',' . $extraId . ',' . $dependency['type'] . ')';
                    mysqli_query($sqlParser->conn, $query);
                    $installDependencyLevel[$dependency['module']]['type'] = 'create';
                } else {
                    $query = 'UPDATE ' . $dbase . '`' . $sqlParser->prefix . 'site_module_depobj` SET module = ' . $moduleId . ', resource = ' . $extraId . ', type = ' . $dependency['type'] . ' WHERE module=' . $moduleId . ' AND resource=' . $extraId . ' AND type=' . $dependency['type'];
                    mysqli_query($sqlParser->conn, $query);
                    $installDependencyLevel[$dependency['module']]['type'] = 'update';
                }
                if ($dependency['type'] == 30 || $dependency['type'] == 40) {
                    // set extra guid for plugins and snippets
                    $query = 'SELECT id FROM ' . $dbase . '`' . $sqlParser->prefix . 'site_' . $dependency['table'] . '` WHERE id=' . $extraId . ' LIMIT 1';
                    $ds = mysqli_query($sqlParser->conn, $query);
                    if (!$ds) {
                        $installDependencyLevel[$dependency['module']]['extra'] = array(
                            'type' => 'error',
                            'content' => mysqli_error($sqlParser->conn)
                        );
                        $errorInstall = true;
                        break;
                    } else {
                        if (mysqli_num_rows($ds) != 0) {
                            $query = 'UPDATE ' . $dbase . '`' . $sqlParser->prefix . 'site_' . $dependency['table'] . '` SET moduleguid = ' . $moduleGuid . ' WHERE id=' . $extraId;
                            $ds= mysqli_query($sqlParser->conn, $query);
                            $installDependencyLevel[$dependency['module']]['extra'] = array(
                                'type' => 'done',
                                'content' => $dependency['name']
                            );
                        }
                    }
                }
            }
        }
        if ($errorInstall === false) {
            $installLevel = 7;
        }
    }

    if ($installLevel === 7) {
        // call back function
        if ($callBackFnc != "") {
            $callBackFnc($sqlParser);
        }

        // Setup the MODX API -- needed for the cache processor
        if (file_exists(dirname(dirname(dirname(__DIR__))) . '/' . MGR_DIR . '/includes/config_mutator.php')) {
            require_once dirname(dirname(dirname(__DIR__))) . '/' . MGR_DIR . '/includes/config_mutator.php';
        }
        define('MODX_API_MODE', true);
        if (!defined('MODX_BASE_PATH')) {
            define('MODX_BASE_PATH', $base_path);
        }
        if (!defined('MODX_MANAGER_PATH')) {
            define('MODX_MANAGER_PATH', $base_path . MGR_DIR . '/');
        }
        $database_type = 'mysqli';
        // initiate a new document parser
        if (!defined('EVO_BOOTSTRAP_FILE')) {
            define('EVO_BOOTSTRAP_FILE',
                dirname(dirname(dirname(__DIR__))) . '/' . MGR_DIR . '/includes/bootstrap.php');
            require_once dirname(dirname(dirname(__DIR__))) . '/' . MGR_DIR . '/includes/bootstrap.php';
        }

        if (! defined('EVO_SERVICES_FILE')) {
            define('EVO_SERVICES_FILE', dirname(dirname(dirname(__DIR__))) . '/' . MGR_DIR . '/includes/services.php');
        }
        if (! defined('MODX_CLASS')) {
            define('MODX_CLASS', '\EvolutionCMS\Core');
        }
        
        $modx = evolutionCMS();
        $modx->getDatabase()->connect();
        // always empty cache after install
        $sync = new EvolutionCMS\Cache();
        $sync->setCachepath(dirname(dirname(dirname(__DIR__))) . '/assets/cache/');
        $sync->setReport(false);
        $sync->emptyCache(); // first empty the cache

        // try to chmod the cache go-rwx (for suexeced php)
        @chmod(dirname(dirname(dirname(__DIR__))) . '/assets/cache/siteCache.idx.php', 0600);
        @chmod(dirname(dirname(dirname(__DIR__))) . '/assets/cache/sitePublishing.idx.php', 0600);

        // remove any locks on the manager functions so initial manager login is not blocked
        mysqli_query($conn, "TRUNCATE TABLE `" . $table_prefix . "active_users`");

        // close db connection
        $sqlParser->close();

        // andrazk 20070416 - release manager access
        if (file_exists(dirname(dirname(dirname(__DIR__))) . '/assets/cache/installProc.inc.php')) {
            @chmod(dirname(dirname(dirname(__DIR__))) . '/assets/cache/installProc.inc.php', 0755);
            unlink(dirname(dirname(dirname(__DIR__))) . '/assets/cache/installProc.inc.php');
        }
    }
}
include_once dirname(__DIR__) . '/template/actions/install.php';
