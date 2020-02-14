<?php
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
@ set_time_limit(120); // used @ to prevent warning when using safe mode?

$installMode = (int)$_POST['installmode'];
$installData = (int)!empty($_POST['installdata']);

// get db info from post
$database_server             = $_POST['databasehost'];
$database_user               = $_SESSION['databaseloginname'];
$database_password           = $_SESSION['databaseloginpassword'];
$database_collation          = $_POST['database_collation'];
$database_charset            = substr($database_collation, 0, strpos($database_collation, '_'));
$database_connection_charset = $_POST['database_connection_charset'];
$database_connection_method  = $_POST['database_connection_method'];
$dbase                       = '`' . $_POST['database_name'] . '`';
$table_prefix                = table_prefix();
$adminname                   = $_POST['cmsadmin'];
$adminemail                  = $_POST['cmsadminemail'];
$adminpass                   = $_POST['cmspassword'];
$managerlanguage             = $_POST['managerlanguage'];
$custom_placeholders         = array();

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
$base_url = $url . (substr($url, -1) !== '/' ? '/' : '');
$base_path = $pth . (substr($pth, -1) !== '/' ? '/' : '');

// connect to the database
$host = explode(':', $database_server, 2);
global $conn;
$conn = @mysqli_connect($host[0], $database_user, $database_password,'', isset($host[1]) ? $host[1] : null);
$installLevel = 0;
if ($conn) {
    $installLevel = 0;
    // select database
    $selectDatabase = mysqli_select_db($conn, str_replace('`', '', $dbase));
    if ($selectDatabase) {
        if (function_exists('mysqli_set_charset')) {
            mysqli_set_charset($conn, $database_charset);
        }
        mysqli_query($conn, sprintf('%s %s', $database_connection_method, $database_connection_charset));
        $installLevel = 1;
    } else {
        // try to create the database
        $query = sprintf(
            'CREATE DATABASE %s DEFAULT CHARACTER SET %s COLLATE %s'
            , $dbase
            , $database_charset
            , $database_collation
        );
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
            $query = sprintf(
                'SELECT COUNT(*) FROM %s.`%s`'
                , $dbase
                , table_prefix('site_content')
            );
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
        $auto_template_logic = 'sibling';
        if ($installMode !== 0) {
            $query = sprintf(
                "SELECT properties, disabled FROM %s.`%s` WHERE name='Inherit Parent Template'"
                , $dbase
                , table_prefix('site_plugins')
            );
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
            str_replace('`', '', $dbase),
            table_prefix(),
            $adminname,
            $adminemail,
            $adminpass,
            $database_connection_charset,
            $managerlanguage,
            $database_connection_method,
            $auto_template_logic
        );
        $sqlParser->database_collation = $database_collation;
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
        $confph['database_server']      = $database_server;
        $confph['user_name']            = sql_escape($database_user);
        $confph['password']             = sql_escape($database_password);
        $confph['connection_charset']   = $database_connection_charset;
        $confph['connection_collation'] = $database_collation;
        $confph['connection_method']    = $database_connection_method;
        $confph['dbase']                = str_replace('`', '', $dbase);
        $confph['table_prefix']         = table_prefix();
        $confph['lastInstallTime']      = time();
        $confph['site_sessionname']     = $site_sessionname;

        $configString = file_get_contents(dirname(__DIR__, 2) . '/stubs/files/config/database/connections/default.tpl');
        $configString = parse($configString, $confph);

        $filename = EVO_CORE_PATH . 'config/database/connections/default.php';
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
                sprintf(
                    "REPLACE INTO %s.`%s` (setting_name,setting_value) VALUES('site_id','%s'),('manager_theme','default')"
                    , $dbase
                    , table_prefix('system_settings')
                    , $siteid
                )
            );
        } else {
            // update site_id if missing
            $ds = mysqli_query(
                    $sqlParser->conn,
                sprintf(
                    "SELECT setting_name,setting_value FROM %s.`%s` WHERE setting_name='site_id'"
                    , $dbase
                    , table_prefix('system_settings')
                )
            );
            if ($ds) {
                $r = mysqli_fetch_assoc($ds);
                $siteid = $r['setting_value'];
                if ($siteid == '' || $siteid === 'MzGeQ2faT4Dw06+U49x3') {
                    $siteid = uniqid('');
                    mysqli_query(
                            $sqlParser->conn,
                        sprintf(
                            "REPLACE INTO %s.`%s` (setting_name,setting_value) VALUES('site_id','%s')"
                            , $dbase
                            , table_prefix('system_settings')
                            , $siteid
                        )
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
                    'desc'     => $moduleTemplate[1],
                    'category' => $moduleTemplate[4],
                    'locked'   => $moduleTemplate[5],
                    'file'     => $moduleTemplate[3],
                    'id'       => $moduleTemplate[7],
                ),
                'type' => '', // update, create
                /*'error' => array(
                    'type' => '' // sql, file_not_found
                    'content' => ''
                )*/
            );
            $installSample = in_array('sample', $moduleTemplate[6]) && $installData === 1;
            if ($installSample || in_array($k, $selTemplates)) {
                $name           = sql_escape($moduleTemplate[0]);
                $desc           = sql_escape($moduleTemplate[1]);
                $category       = sql_escape($moduleTemplate[4]);
                $locked         = sql_escape($moduleTemplate[5]);
                $filecontent    = $moduleTemplate[3];
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
                    $template = sql_escape($template);

                    // See if the template already exists
                    $query = sprintf(
                        "SELECT * FROM %s.`%s` WHERE templatename='%s'"
                        , $dbase
                        , table_prefix('site_templates')
                        , $name
                    );
                    $rs = mysqli_query($sqlParser->conn, $query);

                    if (mysqli_num_rows($rs)) {
                        $installDataLevel['templates'][$moduleTemplate[0]]['type'] = 'update';
                        $query = sprintf(
                            "UPDATE %s.`%s` SET content='%s', description='%s', category=%s, locked='%s'  WHERE templatename='%s' LIMIT 1"
                            , $dbase
                            , table_prefix('site_templates')
                            , $template
                            , $desc
                            , (int) $category_id
                            , (int) $locked
                            , $name
                        );
                        if (!mysqli_query($sqlParser->conn, $query)) {
                            $errors += 1;
                            $installDataLevel['templates'][$moduleTemplate[0]]['error'] = array(
                                'type' => 'sql',
                                'content' => mysqli_error($sqlParser->conn)
                            );
                            $errorData = true;
                            break;
                        }
                        if ($save_sql_id_as !== null) {
                            $sql_id = @mysqli_insert_id($sqlParser->conn);
                            if (!$sql_id) {
                                $query = sprintf(
                                    "SELECT id FROM %s.`%s` WHERE templatename='%s' LIMIT 1"
                                    , $dbase
                                    , table_prefix('site_templates')
                                    , $name
                                );
                                $idQuery = mysqli_fetch_assoc(mysqli_query($sqlParser->conn, $query));
                                $sql_id = $idQuery['id'];
                            }
                            $custom_placeholders[$save_sql_id_as] = $sql_id;
                        }
                    } else {
                        $installDataLevel['templates'][$moduleTemplate[0]]['type'] = 'create';
                        $query = sprintf(
                            "INSERT INTO %s.`%s` (templatename,description,content,category,locked) VALUES('%s','%s','%s',%s,'%s')"
                            , $dbase
                            , table_prefix('site_templates')
                            , $name
                            , $desc
                            , $template
                            , (int) $category_id
                            , (int) $locked
                        );
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
                    'desc'                 => $moduleTV[2],
                    'caption'              => $moduleTV[1],
                    'category'             => $moduleTV[10],
                    'locked'               => $moduleTV[11],
                    'file'                 => $moduleTV[8],
                    'input_type'           => $moduleTV[3],
                    'input_options'        => $moduleTV[4],
                    'input_default'        => $moduleTV[5],
                    'output_widget'        => $moduleTV[6],
                    'output_widget_params' => $moduleTV[7],
                    'assignments'          => $moduleTV[9]
                ),
                'type' => '', // update, create
                /*'error' => array(
                    'type' => '' // sql, file_not_found
                    'content' => ''
                )*/
            );

            $installSample = in_array('sample', $moduleTV[12]) && $installData == 1;
            if ($installSample || in_array($k, $selTVs)) {
                $name                 = sql_escape($moduleTV[0]);
                $caption              = sql_escape($moduleTV[1]);
                $desc                 = sql_escape($moduleTV[2]);
                $input_type           = sql_escape($moduleTV[3]);
                $input_options        = sql_escape($moduleTV[4]);
                $input_default        = sql_escape($moduleTV[5]);
                $output_widget        = sql_escape($moduleTV[6]);
                $output_widget_params = sql_escape($moduleTV[7]);
                $filecontent          = $moduleTV[8];
                $assignments          = $moduleTV[9];
                $category             = sql_escape($moduleTV[10]);
                $locked               = sql_escape($moduleTV[11]);


                // Create the category if it does not already exist
                $category = getCreateDbCategory($category, $sqlParser);

                $query = sprintf(
                    "SELECT * FROM %s.`%s` WHERE name='%s'"
                    , $dbase
                    , table_prefix('site_tmplvars')
                    , $name
                );
                $rs = mysqli_query($sqlParser->conn,$query);
                if (mysqli_num_rows($rs)) {
                    $installDataLevel['tvs'][$moduleTV[0]]['type'] = 'update';
                    while ($row = mysqli_fetch_assoc($rs)) {
                        $query = sprintf(
                            "UPDATE %s.`%s` SET type='%s', caption='%s', description='%s', category=%s, locked=%s, elements='%s', display='%s', display_params='%s', default_text='%s' WHERE id=%s"
                            , $dbase
                            , table_prefix('site_tmplvars')
                            , $input_type
                            , $caption
                            , $desc
                            , (int) $category
                            , (int) $locked
                            , $input_options
                            , $output_widget
                            , $output_widget_params
                            , $input_default
                            , $row['id']
                        );
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
                    $q = sprintf(
                        "INSERT INTO %s.`%s` (type,name,caption,description,category,locked,elements,display,display_params,default_text) VALUES('%s','%s','%s','%s',%s,%s,'%s','%s','%s','%s')"
                        , $dbase
                        , table_prefix('site_tmplvars')
                        , $input_type
                        , $name
                        , $caption
                        , $desc
                        , (int) $category
                        , (int) $locked
                        , $input_options
                        , $output_widget
                        , $output_widget_params
                        , $input_default
                    );
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
                    $query = sprintf(
                        "SELECT id FROM %s.`%s` WHERE name='%s' AND description='%s'"
                        , $dbase
                        , table_prefix('site_tmplvars')
                        , $name
                        , $desc
                    );
                    $ds = mysqli_query($sqlParser->conn, $query);
                    $row = mysqli_fetch_assoc($ds);
                    $id = $row["id"];
                    $query = sprintf(
                        "DELETE FROM %s.`%s` WHERE tmplvarid = '%s'"
                        , $dbase
                        , table_prefix('site_tmplvar_templates')
                        , $id
                    );
                    mysqli_query($sqlParser->conn, $query);

                    // add tv -> template assignments
                    foreach ($assignments as $assignment) {
                        $template = sql_escape($assignment);
                        $query = sprintf(
                            "SELECT id FROM %s.`%s` WHERE templatename='%s'"
                            , $dbase
                            , table_prefix('site_templates')
                            , $template
                        );
                        $ts = mysqli_query($sqlParser->conn, $query);
                        if ($ds && $ts) {
                            $tRow = mysqli_fetch_assoc($ts);
                            $templateId = $tRow['id'];
                            $query = sprintf(
                                "INSERT INTO %s.`%s` (tmplvarid, templateid) VALUES(%s, %s)"
                                , $dbase
                                , table_prefix('site_tmplvar_templates')
                                , (int) $id
                                , (int) $templateId
                            );
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
                    'desc'       => $moduleChunk[1],
                    'category'   => $moduleChunk[3],
                    'overwrite'  => $moduleChunk[4],
                    'file'       => $moduleChunk[2],
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
                $name        = sql_escape($moduleChunk[0]);
                $desc        = sql_escape($moduleChunk[1]);
                $category    = sql_escape($moduleChunk[3]);
                $overwrite   = sql_escape($moduleChunk[4]);
                $filecontent = $moduleChunk[2];

                if (!file_exists($filecontent)) {
                    $installDataLevel['chunks'][$moduleChunk[0]]['error'] = array(
                        'type' => 'file_not_found'
                    );
                } else {
                    // Create the category if it does not already exist
                    $category_id = getCreateDbCategory($category, $sqlParser);

                    $chunk = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', file_get_contents($filecontent), 1);
                    $chunk = sql_escape($chunk);
                    $rs = mysqli_query(
                        $sqlParser->conn,
                        sprintf(
                            "SELECT * FROM %s.`%s` WHERE name='%s'"
                            , $dbase
                            , table_prefix('site_htmlsnippets')
                            , $name
                        )
                    );
                    $count_original_name = mysqli_num_rows($rs);
                    if ($overwrite == 'false') {
                        $newname = $name . '-' . str_replace('.', '_', $modx_version);
                        $rs = mysqli_query(
                            $sqlParser->conn,
                            sprintf(
                                "SELECT * FROM %s.`%s` WHERE name='%s'"
                                , $dbase
                                , table_prefix('site_htmlsnippets')
                                , $newname
                            )
                        );
                        $count_new_name = mysqli_num_rows($rs);
                    }
                    $update = $count_original_name > 0 && $overwrite === 'true';
                    if ($update) {
                        $installDataLevel['chunks'][$moduleChunk[0]]['type'] = 'update';
                        if (!mysqli_query(
                            $sqlParser->conn
                            , sprintf(
                                "UPDATE %s.`%s` SET snippet='%s', description='%s', category=%s WHERE name='%s'"
                                , $dbase
                                , table_prefix('site_htmlsnippets')
                                , $chunk
                                , $desc
                                , (int) $category_id
                                , $name
                            )
                        )) {
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
                            $installDataLevel['chunks'][$moduleChunk[0]]['type']    = 'overwrite';
                            $installDataLevel['chunks'][$moduleChunk[0]]['newname'] = $newname;
                            $name = $newname;
                        } else {
                            $installDataLevel['chunks'][$moduleChunk[0]]['type'] = 'create';
                        }
                        $query = sprintf(
                            "INSERT INTO %s.`%s` (name,description,snippet,category) VALUES('%s','%s','%s',%s)"
                            , $dbase
                            , table_prefix('site_htmlsnippets')
                            , $name
                            , $desc
                            , $chunk
                            , (int) $category_id
                        );
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
                    'desc'     => $moduleModule[1],
                    'category' => $moduleModule[6],
                    'file'     => $moduleModule[2],
                    'guid'     => $moduleModule[4],
                    'props'    => $moduleModule[3],
                    'shared'   => $moduleModule[5],
                ),
                'type' => '', // update, create
                /*'error' => array(
                    'type' => '' // sql, file_not_found
                    'content' => ''
                )*/
            );
            $installSample = in_array('sample', $moduleModule[7]) && $installData == 1;
            if ($installSample || in_array($k, $selModules)) {
                $name        = sql_escape($moduleModule[0]);
                $desc        = sql_escape($moduleModule[1]);
                $filecontent = $moduleModule[2];
                $properties  = $moduleModule[3];
                $guid        = sql_escape($moduleModule[4]);
                $shared      = sql_escape($moduleModule[5]);
                $category    = sql_escape($moduleModule[6]);
                if (!file_exists($filecontent)) {
                    $installDataLevel['modules'][$moduleModule[0]]['error'] = array(
                        'type' => 'file_not_found'
                    );
                } else {
                    // Create the category if it does not already exist
                    $category = getCreateDbCategory($category, $sqlParser);

                    $module = end(preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent), 2));
                    // $module = removeDocblock($module, 'module'); // Modules have no fileBinding, keep docblock for info-tab
                    $module = sql_escape($module);
                    $rs = mysqli_query(
                        $sqlParser->conn
                        , sprintf(
                            "SELECT * FROM %s.`%s` WHERE name='%s'"
                            , $dbase
                            , table_prefix('site_modules')
                            , $name
                        ));
                    if (mysqli_num_rows($rs)) {
                        $installDataLevel['modules'][$moduleModule[0]]['type'] = 'update';
                        $row = mysqli_fetch_assoc($rs);
                        $props = sql_escape( propUpdate($properties, $row['properties']));
                        if (!mysqli_query(
                            $sqlParser->conn
                            , sprintf(
                                "UPDATE %s.`%s` SET modulecode='%s', description='%s', properties='%s', enable_sharedparams='%s' WHERE name='%s'"
                                , $dbase
                                , table_prefix('site_modules')
                                , $module
                                , $desc
                                , $props
                                , (int) $shared
                                , $name
                            )
                        )) {
                            $installDataLevel['modules'][$moduleModule[0]]['error'] = array(
                                'type' => 'sql',
                                'content' => mysqli_error($sqlParser->conn)
                            );
                            $errorData = true;
                            break;
                        }
                    } else {
                        $installDataLevel['modules'][$moduleModule[0]]['type'] = 'create';
                        $properties = sql_escape(parseProperties($properties, true));
                        if (!mysqli_query(
                            $sqlParser->conn
                            , sprintf(
                                "INSERT INTO %s.`%s` (name,description,modulecode,properties,guid,enable_sharedparams,category) VALUES('%s','%s','%s','%s','%s','%s', %s)"
                                , $dbase
                                , table_prefix('site_modules')
                                , $name
                                , $desc
                                , $module
                                , $properties
                                , $guid
                                , (int) $shared
                                , $category
                            )
                        )) {
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
                    'desc'     => $modulePlugin[1],
                    'file'     => $modulePlugin[2],
                    'category' => $modulePlugin[6],
                    'guid'     => $modulePlugin[5],
                    'disabled' => $modulePlugin[9],
                    'events'   => explode(',', $modulePlugin[4]),
                    'props'    => $modulePlugin[3]
                ),
                'type' => '', // update, create
                /*'error' => array(
                    'type' => '' // sql, file_not_found
                    'content' => ''
                )*/
            );

            $installSample = is_array($modulePlugin[8]) && in_array('sample', $modulePlugin[8]) && $installData == 1;

            if ($installSample || in_array($k, $selPlugs)) {
                $name = sql_escape($modulePlugin[0]);
                $desc = sql_escape($modulePlugin[1]);
                $filecontent = $modulePlugin[2];
                $properties = $modulePlugin[3];
                $events = explode(',', $modulePlugin[4]);
                $guid = sql_escape($modulePlugin[5]);
                $category = sql_escape($modulePlugin[6]);
                $leg_names = '';
                $disabled = $modulePlugin[9];
                if (array_key_exists(7, $modulePlugin)) {
                    // parse comma-separated legacy names and prepare them for sql IN clause
                    $leg_names = "'" . implode(
                            "','",
                            preg_split('/\s*,\s*/', sql_escape($modulePlugin[7]))
                        ) . "'";
                }
                if (! file_exists($filecontent)) {
                    $installDataLevel['plugins'][$modulePlugin[0]]['error'] = array(
                        'type' => 'file_not_found'
                    );
                } else {

                    // disable legacy versions based on legacy_names provided
                    if (!empty($leg_names)) {
                        $update_query = sprintf(
                            "UPDATE %s.`%s` SET disabled='1' WHERE name IN ($leg_names)"
                            , $dbase
                            , table_prefix('site_plugins')
                        );
                        $rs = mysqli_query($sqlParser->conn, $update_query);
                    }

                    // Create the category if it does not already exist
                    $category = getCreateDbCategory($category, $sqlParser);

                    $plugin = end(preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent), 2));
                    $plugin = removeDocblock($plugin, 'plugin');
                    $plugin = sql_escape($plugin);
                    $query = sprintf(
                        "SELECT * FROM %s.`%s` WHERE name='%s' ORDER BY id"
                        , $dbase
                        , table_prefix('site_plugins')
                        , $name
                    );
                    $rs = mysqli_query($sqlParser->conn, $query);
                    $prev_id = null;
                    if (mysqli_num_rows($rs)) {
                        $installDataLevel['plugins'][$modulePlugin[0]]['type'] = 'update';
                        $insert = true;
                        while ($row = mysqli_fetch_assoc($rs)) {
                            $props = sql_escape( propUpdate($properties, $row['properties']));
                            if ($row['description'] == $desc) {
                                $query = sprintf(
                                    "UPDATE %s.`%s` SET plugincode='%s', description='%s', properties='%s' WHERE id=%s"
                                    , $dbase
                                    , table_prefix('site_plugins')
                                    , $plugin
                                    , $desc
                                    , $props
                                    , (int) $row['id']
                                );
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
                                $query = sprintf(
                                    "UPDATE %s.`%s` SET disabled='1' WHERE id=%s"
                                    , $dbase
                                    , table_prefix('site_plugins')
                                    , (int) $row['id']
                                );
                                if (!mysqli_query($sqlParser->conn, $query)) {
                                    $installDataLevel['plugins'][$modulePlugin[0]]['error'] = array(
                                        'type' => 'sql',
                                        'content' => mysqli_error($sqlParser->conn)
                                    );
                                    $errorData = true;
                                    break 2;
                                }
                            }
                            $prev_id = $row['id'];
                        }
                        if ($insert === true) {
                            if(!mysqli_query(
                                $sqlParser->conn
                                , sprintf(
                                    "INSERT INTO %s.`%s` (name,description,plugincode,properties,moduleguid,disabled,category) VALUES('%s','%s','%s','%s','%s','0',%s)"
                                    , $dbase
                                    , table_prefix('site_plugins')
                                    , $name
                                    , $desc
                                    , $plugin
                                    , $props
                                    , $guid
                                    , (int) $category
                                )
                            )) {
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
                        $properties = sql_escape( parseProperties($properties, true));
                        $query = sprintf(
                            "INSERT INTO %s.`%s` (name,description,plugincode,properties,moduleguid,category,disabled) VALUES('%s','%s','%s','%s','%s',%s,%s)"
                            , $dbase
                            , table_prefix('site_plugins')
                            , $name
                            , $desc
                            , $plugin
                            , $properties
                            , $guid
                            , (int) $category
                            , (int) $disabled
                        );
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
                        $query = sprintf(
                            "SELECT id FROM %s.`%s` WHERE name='%s' AND description='%s' ORDER BY id DESC LIMIT 1"
                            , $dbase
                            , table_prefix('site_plugins')
                            , $name
                            , $desc
                        );
                        $ds = mysqli_query($sqlParser->conn, $query);
                        if ($ds) {
                            $row = mysqli_fetch_assoc($ds);
                            $id = $row["id"];
                            $_events = implode("','", $events);
                            // add new events
                            if ($prev_id) {
                                $sql = sprintf(
                                    "INSERT IGNORE INTO $dbase.`%s` (pluginid, evtid, priority)
                                        SELECT '$id' as 'pluginid', se.id as 'evtid', IF(spe.pluginid IS NULL, IF(spe2.priority IS NULL, 0, MAX(spe2.priority) + 1), spe.priority) as 'priority'
                                        FROM $dbase.`%s` se
                                        LEFT JOIN $dbase.`%s` spe ON spe.evtid = se.id AND spe.pluginid = '%s'
                                        LEFT JOIN $dbase.`%s` spe2 ON spe2.evtid = se.id
                                        WHERE name IN ('%s')
                                        GROUP BY se.id"
                                    , table_prefix('site_plugin_events')
                                    , table_prefix('system_eventnames')
                                    , table_prefix('site_plugin_events')
                                    , sql_escape($prev_id)
                                    , table_prefix('site_plugin_events')
                                    , $_events
                                );
                            } else {
                                $sql = sprintf(
                                    "INSERT IGNORE INTO %s.`%s` (pluginid, evtid, priority) SELECT '$id' as 'pluginid', se.id as 'evtid', IF(spe.priority IS NULL, 0, MAX(spe.priority) + 1) as 'priority' FROM $dbase.`%s` se LEFT JOIN $dbase.`%s` spe ON spe.evtid = se.id WHERE name IN ('%s') GROUP BY se.id"
                                    , $dbase
                                    , table_prefix('site_plugin_events')
                                    , table_prefix('system_eventnames')
                                    , table_prefix('site_plugin_events')
                                    , $_events
                                );
                            }
                            mysqli_query($sqlParser->conn, $sql);
                            // remove absent events
                            $sql = sprintf(
                                "DELETE `pe` FROM %s.`%s` `pe` LEFT JOIN %s.`%s` `se` ON `pe`.`evtid`=`se`.`id` AND `name` IN ('%s') WHERE ISNULL(`name`) AND `pluginid`=%s"
                                , $dbase
                                , table_prefix('site_plugin_events')
                                , $dbase
                                , table_prefix('system_eventnames')
                                , $_events
                                , (int) $id
                            );
                            mysqli_query($sqlParser->conn, $sql);
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
                    'desc'     => $moduleSnippet[1],
                    'category' => $moduleSnippet[4],
                    'props'    => $moduleSnippet[3],
                    'file'     => $moduleSnippet[2]
                ),
                'type' => '', // update, create, skip
                /*'error' => array(
                    'type' => '' // sql, file_not_found
                    'content' => ''
                )*/
            );
            $installSample = in_array('sample', $moduleSnippet[5]) && $installData == 1;
            if ($installSample || in_array($k, $selSnips)) {
                $name        = sql_escape($moduleSnippet[0]);
                $desc        = sql_escape($moduleSnippet[1]);
                $filecontent = $moduleSnippet[2];
                $properties  = $moduleSnippet[3];
                $category    = sql_escape($moduleSnippet[4]);
                if (!file_exists($filecontent)) {
                    $installDataLevel['snippets'][$moduleSnippet[0]]['error'] = array(
                        'type' => 'file_not_found'
                    );
                } else {
                    // Create the category if it does not already exist
                    $category = getCreateDbCategory($category, $sqlParser);

                    $snippet = end(preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent)));
                    $snippet = removeDocblock($snippet, 'snippet');
                    $snippet = sql_escape($snippet);
                    $rs = mysqli_query($sqlParser->conn,
                        sprintf(
                            "SELECT * FROM %s.`%s` WHERE name='%s'"
                            , $dbase
                            , table_prefix('site_snippets')
                            , $name
                        ));
                    if (mysqli_num_rows($rs)) {
                        $installDataLevel['snippets'][$moduleSnippet[0]]['type'] = 'update';
                        $row = mysqli_fetch_assoc($rs);
                        $props = sql_escape( propUpdate($properties, $row['properties']));
                        if (!mysqli_query($sqlParser->conn,
                            sprintf(
                                "UPDATE %s.`%s` SET snippet='%s', description='%s', properties='%s' WHERE name='%s'"
                                , $dbase
                                , table_prefix('site_snippets')
                                , $snippet
                                , $desc
                                , $props
                                , $name
                            ))) {
                            $installDataLevel['snippets'][$moduleSnippet[0]]['error'] = array(
                                'type' => 'sql',
                                'content' => mysqli_error($sqlParser->conn)
                            );
                            $errorData = true;
                            break;
                        }
                    } else {
                        $installDataLevel['snippets'][$moduleSnippet[0]]['type'] = 'create';
                        $properties = sql_escape( parseProperties($properties, true));
                        if (!mysqli_query($sqlParser->conn,
                            sprintf(
                                "INSERT INTO %s.`%s` (name,description,snippet,properties,category) VALUES('%s','%s','%s','%s',%s)"
                                , $dbase
                                , table_prefix('site_snippets')
                                , $name
                                , $desc
                                , $snippet
                                , $properties
                                , (int) $category
                            ))) {
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
                    'sql'     => $sqlParser->mysqlErrors[$i]['sql']
                );
            }
            $errorData = true;
        } else {
            $installLevel = 6;
            $sql = sprintf(
                "SELECT id FROM `%s` WHERE templatename='Evolution CMS startup - Bootstrap'"
                , table_prefix('site_templates')
            );
            $rs = mysqli_query($sqlParser->conn, $sql);
            if (mysqli_num_rows($rs)) {
                $row = mysqli_fetch_assoc($rs);
                $sql = sprintf(
                    "UPDATE `%s` SET template=%s WHERE template=4"
                    , table_prefix('site_content')
                    , (int) $row['id']
                );
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
            $query = sprintf(
                "SELECT id, guid FROM %s`%s` WHERE name='%s'"
                , $dbase
                , table_prefix('site_modules')
                , $dependency['module']
            );
            $ds = mysqli_query($sqlParser->conn, $query);
            if (!$ds) {
                $installDependencyLevel[$dependency['module']]['error'] = array(
                    'type' => 'sql',
                    'content' => mysqli_error($sqlParser->conn)
                );
                $errorInstall = true;
                break;
            }

            $row = mysqli_fetch_assoc($ds);
            $moduleId = $row["id"];
            $moduleGuid = $row["guid"];
            // get extra id
            $query = sprintf(
                "SELECT id FROM %s`%s` WHERE %s='%s'"
                , $dbase
                , table_prefix('site_'.$dependency['table'])
                , $dependency['column']
                , $dependency['name']
            );
            $ds = mysqli_query($sqlParser->conn, $query);
            if (!$ds) {
                $installDependencyLevel[$dependency['module']]['error'] = array(
                    'type' => 'sql',
                    'content' => mysqli_error($sqlParser->conn)
                );
                $errorInstall = true;
                break;
            }

            $row = mysqli_fetch_assoc($ds);
            $extraId = $row["id"];
            // setup extra as module dependency
            $query = sprintf(
                'SELECT module FROM %s`%s` WHERE module=%s AND resource=%s AND type=%s LIMIT 1'
                , $dbase
                , table_prefix('site_module_depobj')
                , (int) $moduleId
                , (int) $extraId
                , (int) $dependency['type']
            );
            $ds = mysqli_query($sqlParser->conn, $query);
            if (!$ds) {
                $installDependencyLevel[$dependency['module']]['error'] = array(
                    'type' => 'sql',
                    'content' => mysqli_error($sqlParser->conn)
                );
                $errorInstall = true;
                break;
            }

            if (mysqli_num_rows($ds) === 0) {
                $query = sprintf(
                    'INSERT INTO %s`%s` (module, resource, type) VALUES(%s,%s,%s)'
                    , $dbase
                    , table_prefix('site_module_depobj')
                    , (int) $moduleId
                    , (int) $extraId
                    , (int) $dependency['type']
                );
                mysqli_query($sqlParser->conn, $query);
                $installDependencyLevel[$dependency['module']]['type'] = 'create';
            } else {
                $query = sprintf(
                    "UPDATE %s`%s` SET module = %s, resource = %s, type = %s WHERE module=%s AND resource=%s AND type=%s"
                    , $dbase
                    , $table_prefix('site_module_depobj')
                    , (int) $moduleId
                    , (int) $extraId
                    , (int) $dependency['type']
                    , (int) $moduleId
                    , (int) $extraId
                    , (int) $dependency['type']
                );
                mysqli_query($sqlParser->conn, $query);
                $installDependencyLevel[$dependency['module']]['type'] = 'update';
            }
            if ($dependency['type'] == 30 || $dependency['type'] == 40) {
                // set extra guid for plugins and snippets
                $query = sprintf(
                    'SELECT id FROM %s`%s` WHERE id=%s LIMIT 1'
                    , $dbase
                    , table_prefix('site_'.$dependency['table'])
                    , (int) $extraId
                );
                $ds = mysqli_query($sqlParser->conn, $query);
                if (!$ds) {
                    $installDependencyLevel[$dependency['module']]['extra'] = array(
                        'type' => 'error',
                        'content' => mysqli_error($sqlParser->conn)
                    );
                    $errorInstall = true;
                    break;
                }

                if (mysqli_num_rows($ds) != 0) {
                    $query = sprintf(
                        "UPDATE %s`%s` SET moduleguid = %s WHERE id=%s"
                        , $dbase
                        , table_prefix('site_'.$dependency['table'])
                        , (int) $moduleGuid
                        , (int) $extraId
                    );
                    $ds= mysqli_query($sqlParser->conn, $query);
                    $installDependencyLevel[$dependency['module']]['extra'] = array(
                        'type' => 'done',
                        'content' => $dependency['name']
                    );
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
        if (file_exists(dirname(__DIR__, 3) . '/' . MGR_DIR . '/includes/config_mutator.php')) {
            require_once dirname(__DIR__, 3) . '/' . MGR_DIR . '/includes/config_mutator.php';
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
            define('EVO_BOOTSTRAP_FILE', EVO_CORE_PATH . 'bootstrap.php');
            require_once EVO_CORE_PATH . 'bootstrap.php';
        }

        if (! defined('MODX_CLASS')) {
            define('MODX_CLASS', '\DocumentParser');
        }

        file_put_contents(EVO_CORE_PATH . '.install', time());
        $modx = evolutionCMS();
        $modx->getDatabase()->connect();
        // always empty cache after install
        $modx->clearCache();
//        $sync = new \EvolutionCMS\Legacy\Cache();
//        $sync->setCachepath(dirname(__DIR__, 3) . '/assets/cache/');
//        $sync->setReport(false);
//        $sync->emptyCache(); // first empty the cache

        // try to chmod the cache go-rwx (for suexeced php)
        @chmod(dirname(__DIR__, 3) . '/assets/cache/siteCache.idx.php', 0600);
        @chmod(dirname(__DIR__, 3) . '/assets/cache/sitePublishing.idx.php', 0600);

        // remove any locks on the manager functions so initial manager login is not blocked
        mysqli_query($conn, sprintf('TRUNCATE TABLE `%s`', table_prefix('active_users')));

        // close db connection
//        $sqlParser->close();

        // andrazk 20070416 - release manager access
        if (file_exists(dirname(__DIR__, 3) . '/assets/cache/installProc.inc.php')) {
            @chmod(dirname(__DIR__, 3) . '/assets/cache/installProc.inc.php', 0755);
            unlink(dirname(__DIR__, 3) . '/assets/cache/installProc.inc.php');
        }
    }
}
include_once dirname(__DIR__) . '/template/actions/install.php';

function table_prefix($table_name='') {
    return $_POST['tableprefix'] . $table_name;
}

function sql_escape($string) {
    global $conn;
    return mysqli_real_escape_string($conn, $string);
}
