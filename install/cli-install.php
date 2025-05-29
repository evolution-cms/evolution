<?php

use EvolutionCMS\Facades\Console;

$base_path = dirname(__DIR__) . '/';
define('MODX_API_MODE', true);
define('EVO_BASE_PATH', $base_path);
define('EVO_SITE_URL', '/');
define('EVO_CORE_PATH', $base_path . 'core/');
define('IN_INSTALL_MODE', true);
define('MODX_CLI', true);
require_once 'src/functions.php';
/**
 * EVO Cli Installer
 * php cli-install.php --typeInstall=1 --databaseType=pgsql --databaseServer=localhost --database=db_name --databaseUser=serious --databasePassword=serious  --tablePrefix=evo_ --cmsAdmin=admin --cmsAdminEmail=serious2008@gmail.com --cmsPassword=123456 --language=uk --removeInstall=y
 **/

$install = new InstallEvo($argv);
$install->start();

class InstallEvo
{
    public $typeInstall = '';
    public $databaseType = '';
    public $databaseServer = '';
    public $database = '';
    public $databaseUser = '';
    public $databasePassword = '';
    public $tablePrefix = '';
    public $cmsAdmin = '';
    public $cmsAdminEmail = '';
    public $cmsPassword = '';
    public $language = '';
    public $removeInstall = '';
    public $database_charset = 'utf8mb4';
    public $database_collation = 'utf8mb4_unicode_520_ci';
    public $dbh;
    public $evo;
    public $version;

    function __construct($argv)
    {
        $args = array_slice($argv, 1);
        file_put_contents('/tmp/argv.log', var_export($argv, true));
        foreach ($args as $arg) {
            $tmp = array_map('trim', explode('=', $arg));
            if (count($tmp) === 2) {
                $k = ltrim($tmp[0], '-');

                $cli_variables[$k] = $tmp[1];
                if (isset($this->{$k})) {
                    $this->{$k} = $tmp[1];
                }
            }
        }
    }

    public function start(): void
    {
        if (!in_array($this->typeInstall, [1, 2], true)) {
            $answer = $this->choice(
                'Please choose your variant of install:',
                ['Install', 'Update'],
                0
            );
            $this->typeInstall = $answer === 'Install' ? 1 : 2;
        }

        match ($this->typeInstall) {
            1 => $this->install(),
            2 => $this->update(),
            default => $this->start(),
        };
    }

    public function read_line($message, $message2 = "")
    {
        info($message);
        return readline($message2);
    }

    /**
     * Stylized console selection (no kernel dependency).
     *
     * @param string $question  Question text
     * @param array  $options   ['Install', 'Update', …]
     * @param int    $default   Default option index
     * @return string           Selected element of the $options array
     */
    public function choice(string $question, array $options, int $default = 0): string
    {
        // ── ANSI colors, if the terminal supports them
        $useColor = function_exists('stream_isatty') && stream_isatty(STDOUT);
        $c = $useColor
            ? [
                'yellowBold' => "\e[1;33m",
                'yellow' => "\e[0;33m",
                'cyan' => "\e[0;36m",
                'green' => "\e[0;32m",
                'blue' => "\e[1;34m",
                'reset' => "\e[0m",
            ]
            : array_fill_keys(['yellowBold', 'yellow', 'cyan', 'green', 'blue', 'reset'], '');

        /* 1. Question */
        echo PHP_EOL . $c['yellowBold'] . $question . $c['reset'] . PHP_EOL;

        /* 2. List of options */
        foreach ($options as $i => $label) {
            $idx = $i + 1;
            $def = $i === $default ? $c['green'] . ' *' . $c['reset'] : '';
            printf("  [%s%2d%s] %s%s\n",
                $c['yellow'], $idx, $c['reset'], $label, $def
            );
        }

        /* 3. User input */
        $max = count($options);
        do {
            $answer = readline("Choose [1-{$max}]: ");
            if ($answer === '' && isset($options[$default])) {
                return $options[$default];
            }
            $idx = (int)$answer - 1;
        } while (!isset($options[$idx]));

        return $options[$idx];
    }

    /**
     * Prompts the user for a string.
     *
     * @param string      $question  Question text («Please enter database server»)
     * @param string|null $default   Default value if Enter is pressed
     * @return string
     */
    public function ask(string $question, ?string $default = null): string
    {
        $useColor = function_exists('stream_isatty') && stream_isatty(STDOUT);
        $yellow = $useColor ? "\e[1;33m" : '';
        $reset = $useColor ? "\e[0m"    : '';

        $suffix = $default !== null ? " [{$default}]" : '';
        echo PHP_EOL . $yellow . $question . $suffix . $reset . PHP_EOL;

        $answer = readline('➤ ');
        return $answer !== '' ? trim($answer) : (string) $default;
    }

    public function initEvo()
    {
        include EVO_BASE_PATH . 'index.php';
        $this->evo = evo();
        $this->version = evo()->getVersionData()['full_appname'] ?? 'almost current version';
    }

    public function update()
    {
        $this->initEvo();
        Console::call('migrate', ['--path' => EVO_BASE_PATH . 'install/stubs/migrations', '--force' => true]);
        seed('update');
        echo 'Evolution CMS updated!' . "\n";
        $this->checkRemoveInstall();
        $this->removeInstall();
    }

    public function install()
    {
        $this->checkDatabaseType();
        $this->checkDatabaseServer();
        $this->checkDatabaseUser();
        $this->checkDatabasePassword();
        $this->checkConnectToDatabase();
        $this->checkDatabase();
        $this->checkConnectToDatabaseWithBase();
        $this->checkTablePrefix();
        $this->checkIssetTablePrefix();
        $this->checkCmsAdmin();
        $this->checkCmsAdminEmail();
        $this->checkCmsPassword();
        $this->checkLanguage();
        $this->composerUpdate();
        $this->realInstall();
        $this->checkRemoveInstall();
        $this->removeInstall();
        echo "\033[1;33;44mNow you use {$this->version}\033[0m" . PHP_EOL;
    }

    public function checkDatabaseType()
    {
        if ($this->databaseType != 'pgsql' && $this->databaseType != 'mysql') {
            $this->databaseType = $this->choice(
                'Please choose your database type:',
                ['mysql', 'pgsql'],
                0
            );
        }
        if ($this->databaseType != 'pgsql' && $this->databaseType != 'mysql') {
            $this->checkDatabaseType();
        }
    }

    public function checkDatabaseServer()
    {
        if ($this->databaseServer == '') {
            $this->databaseServer = $this->ask('Please enter database server:', 'localhost');
        }
        if ($this->databaseServer == '') {
            $this->checkDatabaseServer();
        }
    }

    public function checkDatabase()
    {
        if ($this->database == '') {
            $this->database = $this->ask('Please enter database:', '');
        }
        if ($this->database == '') {
            $this->checkDatabase();
        }
    }

    public function checkDatabaseUser()
    {
        if ($this->databaseUser == '') {
            $this->databaseUser = $this->ask('Please enter database user:', '');
        }
        if ($this->databaseUser == '') {
            $this->checkDatabaseUser();
        }
    }

    public function checkDatabasePassword()
    {
        if ($this->databasePassword == '') {
            $this->databasePassword = $this->ask('Please enter database password:', '');
        }
        if ($this->databasePassword == '') {
            $this->checkDatabasePassword();
        }
    }

    public function checkTablePrefix()
    {
        if ($this->tablePrefix == '') {
            $this->tablePrefix = $this->ask('Please enter table_prefix:', 'evo_');
        }
        if ($this->tablePrefix == '') {
            $this->tablePrefix = 'evo_';
        }
    }

    public function checkCmsAdmin()
    {
        if ($this->cmsAdmin == '') {
            $this->cmsAdmin = $this->ask('Please enter you login for access to manager:', '');
        }
        if ($this->cmsAdmin == '') {
            $this->checkCmsAdmin();
        }
    }

    public function checkCmsAdminEmail()
    {
        if ($this->cmsAdminEmail == '') {
            $this->cmsAdminEmail = $this->ask('Please enter you email:', '');
        }
        if ($this->cmsAdminEmail == '') {
            $this->checkCmsAdminEmail();
        }
    }

    public function checkCmsPassword()
    {
        if ($this->cmsPassword == '') {
            $this->cmsPassword = $this->ask('Please enter you password for access to manager:', '');
        }
        if ($this->database == '') {
            $this->checkCmsPassword();
        }
    }

    public function checkLanguage()
    {
        $langs = [];
        if ($handle = opendir(EVO_BASE_PATH . 'core/lang')) {
            while (false !== ($file = readdir($handle))) {
                if (is_dir(EVO_BASE_PATH . 'core/lang/' . $file) && $file != '.' && $file != '..') {
                    $langs[] = $file;
                }
            }
            closedir($handle);
        }

        if (!in_array($this->language, $langs)) {
            sort($langs);
            unset($langs[array_search('en', $langs)]);
            $this->language = $this->choice(
                'Please choose your language:',
                array_merge(['en'], $langs),
                0
            );
        }
        if (!in_array($this->language, $langs)) {
            $this->checkLanguage();
        }
    }

    public function checkRemoveInstall()
    {
        ob_end_clean();

        if (!in_array($this->removeInstall, ['y', 'n'], true)) {
            $this->removeInstall = $this->choice(
                'Do you want remove install directory:',
                ['y', 'n'],
                0
            );
        }

        if (!in_array($this->removeInstall, ['y', 'n'], true)) {
            $this->checkRemoveInstall();
        }
    }

    public function checkConnectToDatabase()
    {
        try {
            $this->dbh = new PDO($this->databaseType . ':host=' . $this->databaseServer, $this->databaseUser, $this->databasePassword);
        } catch (PDOException $e) {
            error('✖ ' . $e->getMessage());
            $this->dbh = false;
        }
        if ($this->dbh === false) {
            $this->databaseType = '';
            $this->databaseServer = '';
            $this->databaseUser = '';
            $this->databasePassword = '';
            $this->database = '';
            $this->install();
        }
    }

    public function checkConnectToDatabaseWithBase()
    {
        $error = 0;
        try {
            $dbh_alt = new PDO($this->databaseType . ':host=' . $this->databaseServer . ';dbname=' . $this->database, $this->databaseUser, $this->databasePassword);
        } catch (PDOException $e) {
            $error = $e->getCode();
            if ($error != 7 && $error != 1049) {
                error('✖ ' . $e->getMessage());
                $dbh_alt = false;
            }
        }
        if ($error == 7 && $this->databaseType == 'pgsql') {
            $this->database_charset = 'utf8';
            $this->database_collation = 'utf8';
            try {
                $this->dbh->query('CREATE DATABASE "' . $this->database . '" ENCODING \'' . $this->database_charset . '\';');
                if ($this->dbh->errorCode() > 0) {
                    echo '<span id="database_fail" style="color:#FF0000;">' . print_r($this->dbh->errorInfo(), true) . '</span>';
                }
                $error = -1;
            } catch (Exception $exception) {
                echo $exception->getMessage();
            }
        }
        if ($error == 1049 && $this->databaseType == 'mysql') {

            try {
                $query = 'CREATE DATABASE `' . $this->database . '` CHARACTER SET ' . $this->database_charset . ' COLLATE ' . $this->database_collation . ";";
                if ($this->dbh->query($query)) {
                    $error = -1;
                }

            } catch (Exception $exception) {
                echo $exception->getMessage();
            }
        }
        if ($error == -1) {
            try {
                $dbh_alt = new PDO($this->databaseType . ':host=' . $this->databaseServer . ';dbname=' . $this->database, $this->databaseUser, $this->databasePassword);
            } catch (PDOException $e) {
                echo $e->getMessage() . "\n";
                $error = $e->getCode();
                $dbh_alt = false;
            }
        }
        if ($this->dbh === false) {
            $this->database = '';
            $this->checkDatabase();
            $this->checkConnectToDatabaseWithBase();
        } else {
            $this->dbh = $dbh_alt;
        }
    }

    public function checkIssetTablePrefix()
    {
        try {
            $result = $this->dbh->query("SELECT COUNT(*) FROM {$this->tablePrefix}site_content");
            if ($result !== false) {
                error('✖ Table prefix already exists');
                $this->tablePrefix = '';
                $this->checkTablePrefix();
                $this->checkIssetTablePrefix();
            }
        } catch (\PDOException $exception) {
        }
    }

    public function composerUpdate()
    {
        $projectRoot = dirname(__DIR__);
        $composerBin = $projectRoot . '/core/vendor/bin/composer';
        $workingDir  = $projectRoot . '/core';
        $cmd = sprintf(
            'php %s update --no-interaction --prefer-dist --working-dir=%s',
            escapeshellarg($composerBin),
            escapeshellarg($workingDir)
        );

        if (!is_file($composerBin)) {
            warning("⚠ Local Composer not found: {$composerBin} Please perform 'composer install' or 'composer update' manually.");
            return;
        }

        success("✔ Usage local Composer");
        info("   Running: {$cmd}");

        $disabled = array_map('trim', explode(',', ini_get('disable_functions') ?: ''));

        $exitCode = null;
        if (!in_array('passthru', $disabled, true)) {
            passthru($cmd, $exitCode);
        } elseif (!in_array('exec', $disabled, true)) {
            exec($cmd . ' 2>&1', $out, $exitCode);
            echo implode(PHP_EOL, $out), PHP_EOL;
        } elseif (!in_array('shell_exec', $disabled, true)) {
            $output   = shell_exec($cmd . ' 2>&1');
            $exitCode = (is_string($output) && $output !== '') ? 0 : 1;
            echo $output;
        } else {
            warning('⚠ The passthru/exec/shell_exec functions are disabled in php.ini.');
            warning('   Run "composer update" manually.');
            return;
        }

        if ($exitCode === 0) {
            success('✔ Dependencies updated successfully.');
        } else {
            error("✖ Composer finished with the code {$exitCode}.");
            warning('   Make sure you have execute permissions and try "composer update" manually.');
        }
    }

    public function realInstall()
    {
        $this->writeConfig();
        $this->initEvo();
        $this->migrationAndSeed();
        $this->installModulesAndPlugins();
        $this->clearCacheAfterInstall();
    }

    public function writeConfig()
    {
        $confph = array();
        $confph['database_server'] = $this->databaseServer;
        $confph['database_type'] = $this->databaseType;
        $confph['user_name'] = $this->databaseUser;
        $confph['password'] = $this->databasePassword;
        $confph['connection_charset'] = $this->database_charset;
        $confph['connection_collation'] = $this->database_collation;
        $confph['connection_method'] = 'SET CHARACTER SET';
        $confph['dbase'] = str_replace('`', '', $this->database);
        $confph['table_prefix'] = $this->tablePrefix;
        $confph['lastInstallTime'] = time();
        $confph['database_engine'] = '';
        switch ($this->databaseType) {
            case 'pgsql':
                $confph['database_port'] = '5432';
                $confph['connection_charset'] = 'utf8';
                break;
            case 'mysql':
                $confph['database_port'] = '3306';
                $serverVersion = $this->dbh->getAttribute(PDO::ATTR_SERVER_VERSION);
                if (version_compare($serverVersion, '5.7.6', '<')) {
                    $confph['database_engine'] = ", 'myisam'";
                } else {
                    $confph['database_engine'] = ", 'innodb'";
                }
                break;
        }
        $configString = file_get_contents(EVO_BASE_PATH . 'stubs/files/config/database/connections/default.tpl');
        $configString = parse($configString, $confph);

        $filename = EVO_CORE_PATH . 'config/database/connections/default.php';
        $configFileFailed = false;

        @chmod($filename, 0777);

        if (!$handle = fopen($filename, 'w')) {
            $configFileFailed = true;
        }
        // write $somecontent to our opened file.
        if (@ fwrite($handle, $configString) === false) {
            $configFileFailed = true;
        }
        @ fclose($handle);

        // try to chmod the config file go-rwx (for suexeced php)
        @chmod($filename, 0404);

    }

    public function migrationAndSeed()
    {
        $delete_file = 'stubs/file_for_delete.txt';
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
        $_POST['database_type'] = $this->databaseType; // костиль для адекватної міграції
        Console::call('migrate', ['--path' => '../install/stubs/migrations', '--force' => true]);
        seed('install');
        $field = array();
        $field['password'] = $this->evo->getPasswordHash()->HashPassword($this->cmsPassword);
        $field['username'] = $this->cmsAdmin;
        $managerUser = EvolutionCMS\Models\User::create($field);
        $internalKey = $managerUser->getKey();
        $role = \EvolutionCMS\Models\UserRole::where('name', 'Administrator')->first()->getKey();
        $field = ['internalKey' => $internalKey, 'email' => $this->cmsAdminEmail, 'role' => $role, 'verified' => 1];
        $managerUser->attributes()->create($field);
        $managerUser->attributes->role = $role;
        $managerUser->attributes->save();
        $systemSettings[] = ['setting_name' => 'manager_language', 'setting_value' => $this->language];
        $systemSettings[] = ['setting_name' => 'auto_template_logic', 'setting_value' => 1];
        $systemSettings[] = ['setting_name' => 'emailsender', 'setting_value' => $this->cmsAdminEmail];
        $systemSettings[] = ['setting_name' => 'fe_editor_lang', 'setting_value' => $this->language];
        \EvolutionCMS\Models\SystemSetting::insert($systemSettings);
        success('✔ All migrations is done!');
    }

    public function installModulesAndPlugins()
    {
        $pluginPath = 'assets/plugins';
        $modulePath = 'assets/modules';
        $modulePlugins = [];
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
                        $params['guid'] ?? "",
                        $params['modx_category'],
                        $params['legacy_names'] ?? "",
                        array_key_exists('installset', $params) ? preg_split("/\s*,\s*/", $params['installset']) : false,
                        $params['disabled'] ?? 0
                    );
                }
            }
            $d->close();
        }

        if (count($modulePlugins) > 0) {
            foreach ($modulePlugins as $k => $modulePlugin) {
                $name = $modulePlugin[0];
                $desc = $modulePlugin[1];
                $filecontent = $modulePlugin[2];
                $properties = $modulePlugin[3];
                $events = explode(",", $modulePlugin[4]);
                $guid = $modulePlugin[5];
                $category = $modulePlugin[6];
                $leg_names = [];
                $disabled = $modulePlugin[9];
                if (array_key_exists(7, $modulePlugin)) {
                    // parse comma-separated legacy names and prepare them for sql IN clause
                    $leg_names = preg_split('/\s*,\s*/', $modulePlugin[7]);
                }
                if (!file_exists($filecontent)) {
                    echo $name . " " . $filecontent . " not found ";
                } else {
                    // disable legacy versions based on legacy_names provided
                    if (count($leg_names)) {
                        \EvolutionCMS\Models\SitePlugin::query()->whereIn('name', $leg_names)->update(['disabled' => 1]);
                    }
                    // Create the category if it does not already exist
                    $category = getCreateDbCategory($category);
                    $array1 = preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent), 2);
                    $plugin = end($array1);
                    // remove installer docblock
                    $plugin = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', $plugin, 1);
                    $pluginDbRecord = \EvolutionCMS\Models\SitePlugin::where('name', $name)->orderBy('id');
                    $prev_id = null;
                    if ($pluginDbRecord->count() > 0) {
                        $insert = true;
                        foreach ($pluginDbRecord->get()->toArray() as $row) {
                            $props = propUpdate($properties, $row['properties']);
                            if ($row['description'] == $desc) {
                                \EvolutionCMS\Models\SitePlugin::query()->where('id', $row['id'])->update(['plugincode' => $plugin, 'description' => $desc, 'properties' => $props]);

                                $insert = false;
                            } else {
                                \EvolutionCMS\Models\SitePlugin::query()->where('id', $row['id'])->update(['disabled' => 1]);
                            }
                            $prev_id = $row['id'];
                        }
                        if ($insert === true) {
                            $props = propUpdate($properties, $row['properties']);
                            \EvolutionCMS\Models\SitePlugin::query()->create(['name' => $name, 'plugincode' => $plugin, 'description' => $desc, 'properties' => $props, 'moduleguid' => $guid, 'disabled' => 0, 'category' => $category]);
                        }
                    } else {
                        $properties = parseProperties($properties, true);
                        \EvolutionCMS\Models\SitePlugin::query()->create(['name' => $name, 'plugincode' => $plugin, 'description' => $desc, 'properties' => $properties, 'moduleguid' => $guid, 'disabled' => $disabled, 'category' => $category]);
                    }
                    // add system events
                    if (count($events) > 0) {
                        $sitePlugin = \EvolutionCMS\Models\SitePlugin::where('name', $name)->where('description', $desc)->first();
                        if (!is_null($sitePlugin)) {
                            $id = $sitePlugin->id;

                            // add new events
                            foreach ($events as $event) {
                                $eventName = \EvolutionCMS\Models\SystemEventname::where('name', $event)->first();
                                if (!is_null($eventName)) {
                                    $prev_priority = null;
                                    if ($prev_id) {
                                        $pluginEvent = \EvolutionCMS\Models\SitePluginEvent::query()
                                            ->where('pluginid', $prev_id)
                                            ->where('evtid', $eventName->getKey())->first();
                                        if (!is_null($pluginEvent)) {
                                            $prev_priority = $pluginEvent->priority;
                                        }
                                    }
                                    if (is_null($prev_priority)) {
                                        $pluginEvent = \EvolutionCMS\Models\SitePluginEvent::query()
                                            ->where('evtid', $eventName->getKey())
                                            ->orderBy('priority', 'DESC')->first();
                                        if (!is_null($pluginEvent)) {
                                            $prev_priority = $pluginEvent->priority;
                                            $prev_priority++;
                                        }
                                    }
                                    if (is_null($prev_priority)) {
                                        $prev_priority = 0;
                                    }
                                    $arrInsert = ['pluginid' => $id, 'evtid' => $eventName->getKey(), 'priority' => $prev_priority];
                                    \EvolutionCMS\Models\SitePluginEvent::query()
                                        ->firstOrCreate($arrInsert);
                                }
                            }

                            // remove absent events
                            \EvolutionCMS\Models\SitePluginEvent::query()->join('system_eventnames', function ($join) use ($events) {
                                $join->on('site_plugin_events.evtid', '=', 'system_eventnames.id')
                                    ->whereIn('name', $events);
                            })
                                ->whereNull('name')
                                ->where('pluginid', $id)->delete();
                        }
                    }
                }
            }
        }
        $moduleModules = [];
        $moduleDependencies = [];
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
                        $params['properties'] ?? "",
                        $params['guid'] ?? "",
                        $params['shareparams'] ?? 0,
                        $params['modx_category'] ?? "",
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
                                    'table' => 'templates',
                                    'column' => 'templatename',
                                    'type' => 50,
                                    'name' => trim($dependency[1])
                                );
                                break;
                            case 'tv':
                            case 'tmplvar':
                                $mdp[] = array(
                                    'module' => $params['name'],
                                    'table' => 'tmplvars',
                                    'column' => 'name',
                                    'type' => 60,
                                    'name' => trim($dependency[1])
                                );
                                break;
                            case 'chunk':
                            case 'htmlsnippet':
                                $mdp[] = array(
                                    'module' => $params['name'],
                                    'table' => 'htmlsnippets',
                                    'column' => 'name',
                                    'type' => 10,
                                    'name' => trim($dependency[1])
                                );
                                break;
                            case 'snippet':
                                $mdp[] = array(
                                    'module' => $params['name'],
                                    'table' => 'snippets',
                                    'column' => 'name',
                                    'type' => 40,
                                    'name' => trim($dependency[1])
                                );
                                break;
                            case 'plugin':
                                $mdp[] = array(
                                    'module' => $params['name'],
                                    'table' => 'plugins',
                                    'column' => 'name',
                                    'type' => 30,
                                    'name' => trim($dependency[1])
                                );
                                break;
                            case 'resource':
                                $mdp[] = array(
                                    'module' => $params['name'],
                                    'table' => 'content',
                                    'column' => 'pagetitle',
                                    'type' => 20,
                                    'name' => trim($dependency[1])
                                );
                                break;
                        }
                    }
                }
            }
            $d->close();
            success('✔ All plugins is installed!');
        }
        // Install Modules
        if (count($moduleModules) > 0) {
            foreach ($moduleModules as $k => $moduleModule) {
                $name = $moduleModule[0];
                $desc = $moduleModule[1];
                $filecontent = $moduleModule[2];
                $properties = $moduleModule[3];
                $guid = $moduleModule[4];
                $shared = $moduleModule[5];
                $category = $moduleModule[6];
                if (!file_exists($filecontent)) {
                    echo $name . " " . $filecontent . " not found ";
                } else {
                    // Create the category if it does not already exist
                    $category = getCreateDbCategory($category);

                    $array = preg_split("/(\/\/)?\s*\<\?php/", file_get_contents($filecontent), 2);
                    $module = end($array);
                    // remove installer docblock
                    $module = preg_replace("/^.*?\/\*\*.*?\*\/\s+/s", '', $module, 1);
                    $moduleDb = \EvolutionCMS\Models\SiteModule::query()->where('name', $name)->first();
                    if (!is_null($moduleDb)) {
                        $props = propUpdate($properties, $moduleDb->properties);
                        \EvolutionCMS\Models\SiteModule::query()->where('name', $name)->update(['modulecode' => $module, 'description' => $desc, 'properties' => $props, 'enable_sharedparams' => $shared]);
                    } else {
                        $props = parseProperties($properties, true);
                        \EvolutionCMS\Models\SiteModule::query()->create(['name' => $name, 'guid' => $guid, 'category' => $category, 'modulecode' => $module, 'description' => $desc, 'properties' => $props, 'enable_sharedparams' => $shared]);
                    }
                }
            }
            success('✔ All modules is installed!');
        }
    }

    public function clearCacheAfterInstall()
    {
        if (file_exists(EVO_BASE_PATH . 'assets/cache/installProc.inc.php')) {
            @chmod(EVO_BASE_PATH . 'assets/cache/installProc.inc.php', 0755);
            unlink(EVO_BASE_PATH . 'assets/cache/installProc.inc.php');
        }
        file_put_contents(EVO_CORE_PATH . '.install', time());
        $this->evo->clearCache('full');
        success('✔ Cache after Install cleared!');
    }

    public function removeInstall()
    {
        if ($this->removeInstall == 'y') {
            $path = __DIR__ . '/';
            removeFolder($path);
            if (file_exists(EVO_BASE_PATH . '.tx')) {
                removeFolder(EVO_BASE_PATH . '.tx');
            }
            if (file_exists(EVO_BASE_PATH . 'vendor')) {
                removeFolder(EVO_BASE_PATH . 'vendor');
            }
            if (file_exists(EVO_BASE_PATH . 'README.md')) {
                unlink(EVO_BASE_PATH . 'README.md');
            }
            if (file_exists(EVO_BASE_PATH . 'composer.json')) {
                unlink(EVO_BASE_PATH . 'composer.json');
            }
            if (file_exists(EVO_BASE_PATH . 'composer.lock')) {
                unlink(EVO_BASE_PATH . 'composer.lock');
            }
            success('✔ Install folder deleted!');
        }
    }
}

exit();
