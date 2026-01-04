<?php namespace EvolutionCMS\Console;

use Composer\Console\Application;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @see: https://github.com/laravel-zero/foundation/blob/9.x/src/Illuminate/Foundation/Console/ClearCompiledCommand.php
 */
class SiteUpdateCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:site {command_site=update} {version=null}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update site';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->argument('command_site')) {
            case 'pizdato':
                echo 'Remove MODX REVO and install Evolution CMS' . "\n";
                $this->startUpdate();
                break;
            case 'update':
                $this->startUpdate();
                break;
        }
    }

    public function startUpdate()
    {
        $evo = evo();
        $updateRepository = $evo->getConfig('UpgradeRepository');
        if ($updateRepository == '') {
            $updateRepository = 'evolution-cms/evolution';
        }
        $ch = curl_init();
        $url = 'https://api.github.com/repos/' . $updateRepository . '/tags';
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: updateNotify widget']);
        $info = curl_exec($ch);
        curl_close($ch);
        if (substr($info, 0, 1) != '[') {
            return;
        }
        $currentVersion = $evo->getVersionData();
        $arrayVersion = explode('.', $currentVersion['version']);
        $currentMajorVersion = array_shift($arrayVersion);

        $info = json_decode($info, true);
        foreach ($info as $key => $val) {
            $arrayVersion = explode('.', $val['name']);
            if ($currentMajorVersion == array_shift($arrayVersion)) {
                $git['version'] = $val['name'];
                if (strpos($val['name'], 'alpha')) {
                    $git['alpha'] = $val['name'];
                    continue;
                } elseif (strpos($val['name'], 'beta')) {
                    $git['beta'] = $val['name'];
                    continue;
                } else {
                    $git['stable'] = $val['name'];
                    break;
                }
            }
        }
        $git['version'] = $this->argument('version');

        if ($git['version'] == 'null') {
            if (isset($git['stable'])) {
                if (version_compare($currentVersion['version'], $git['stable'], '!=')) {
                    $git['version'] = $git['stable'];
                }
            }
        }
        if ($git['version'] != '') {
            $url = 'https://github.com/evolution-cms/evolution/archive/' . $git['version'] . '.zip';
            $this->line('<fg=green>Start download Evolution CMS</>');
            $url = file_get_contents($url);
            $file = MODX_BASE_PATH . 'new_version.zip';

            file_put_contents($file, $url);
            $this->line('<fg=green>Start unpacking Evolution CMS</>');

            $temp_dir = MODX_BASE_PATH . '_temp' . md5(time());
            //run unzip and install

            $zip = new \ZipArchive;
            $res = $zip->open($file);
            $zip->extractTo($temp_dir);
            $zip->close();
            unlink($file);

            if ($handle = opendir($temp_dir)) {
                while (false !== ($name = readdir($handle))) {
                    if ($name != '.' && $name != '..') $dir = $name;
                }
                closedir($handle);
            }

            self::moveFiles($temp_dir . '/' . $dir, MODX_BASE_PATH);
            self::rmdirs($temp_dir);

            $ch = curl_init();
            $url = 'https://api.github.com/repos/' . $updateRepository . '/releases';
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_REFERER, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["User-Agent: updateNotify widget"]);
            $releases = curl_exec($ch);
            curl_close($ch);

            $factoryName = $currentVersion['full_appname'];
            if (substr($releases, 0, 1) == "[") {
                $releases = json_decode($releases, true);
                foreach ($releases as $release) {
                    if ($git['version'] == $release["tag_name"]) {
                        $factoryDate = date("M j, Y", strtotime($release["published_at"]));
                        $factoryName = $release["name"] . ' (' . $factoryDate . ')';
                        $factoryVersion = '<?php return [' . "\n";
                        $factoryVersion .= "\t" . '"version" => "' . $release["tag_name"] . '", // Current version number' . "\n";
                        $factoryVersion .= "\t" . '"release_date" => "' . $factoryDate . '", // Date of release' . "\n";
                        $factoryVersion .= "\t" . '"branch" => "Evolution CMS", // Codebase name' . "\n";
                        $factoryVersion .= "\t" . '"full_appname" => "' . $factoryName . '", // Date of release' . "\n";
                        $factoryVersion .= '];';
                        file_put_contents(EVO_CORE_PATH . "factory/version.php", $factoryVersion);
                        break;
                    }
                }
            }

            $delete_file = MODX_BASE_PATH . 'install/stubs/file_for_delete.txt';
            if (file_exists($delete_file)) {
                $files = explode("\n", file_get_contents($delete_file));
                foreach ($files as $file) {
                    $file = str_replace('{core}', EVO_CORE_PATH, $file);
                    if (file_exists($file)) {
                        if (is_dir($file)) {
                            self::rmdirs($file);
                        } else {
                            unlink($file);
                        }
                    }
                }
            }
            putenv('COMPOSER_HOME=' . EVO_CORE_PATH . 'composer');
            $input = new ArrayInput(['command' => 'update']);
            $application = new Application();
            $application->setAutoExit(false);
            $application->run($input);
            $this->line('<fg=green>Run Migrations</>');
            exec('php  ../install/cli-install.php --typeInstall=2 --removeInstall=y');

            $this->line('<fg=green>Remove Install Directory</>');
            self::rmdirs(MODX_BASE_PATH . 'install');

            $this->line('<fg=green>Run Composer update</>');
            exec('composer update');

            $this->line('<fg=yellow;bg=blue>Now You use ' . $factoryName . '</>');
        } else {
            $this->line('<fg=yellow;bg=blue>You use almost current version</>');
        }
    }

    static public function moveFiles($src, $dest)
    {
        $path = realpath($src);
        $dest = realpath($dest);
        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($objects as $name => $object) {
            $startsAt = substr(dirname($name), strlen($path));
            self::mmkDir($dest . $startsAt);
            if ($object->isDir()) {
                self::mmkDir($dest . substr($name, strlen($path)));
            }

            if (is_writable($dest . $startsAt) && $object->isFile()) {
                rename((string)$name, $dest . $startsAt . '/' . basename($name));
            }
        }
    }

    static public function rmdirs($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object) && !is_link($dir . "/" . $object))
                        self::rmdirs($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            rmdir($dir);
        }
    }

    static public function mmkDir($folder, $perm = 0777)
    {
        if (!is_dir($folder)) {
            mkdir($folder, $perm);
        }
    }
}
