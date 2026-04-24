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
     * Default GitHub repository used for core updates when no custom repository is configured.
     */
    protected const DEFAULT_UPGRADE_REPOSITORY = 'evolution-cms/evolution';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'make:site
                            {command_site=update : Update action to run. Keep "update" for normal core updates}
                            {version=null : Optional tag, branch or commit hash. Examples: 3.5.4, 3.5.x, 922ece660}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update site';

    /**
     * Additional help shown by the Artisan help command.
     *
     * @var string
     */
    protected $help = <<<'HELP'
Downloads and applies an Evolution CMS core update package from GitHub.

If no version is provided, the command installs the latest stable tag
available for the current major version.

You can also request a specific ref manually:

  php artisan make:site
    Update to the latest stable tag for the current major version.

  php artisan make:site update 3.5.4
    Update to a specific release tag.

  php artisan make:site update 3.5.x
    Update to the current HEAD of the 3.5.x branch.

  php artisan make:site update 922ece66071acecaea9afb8486791738acc6de5e
    Update to a specific commit.
HELP;

    /**
     * Create a new site update command instance.
     */
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

    /**
     * Download, unpack and apply the requested Evolution CMS update package.
     *
     * When no explicit version/ref is provided, the command falls back to the latest
     * stable tag for the current major version reported by GitHub.
     *
     * @since 3.5.5 Added support for branch and commit refs in manual update requests.
     * @return void
     */
    public function startUpdate()
    {
        $evo = evo();
        $updateRepository = $this->resolveUpdateRepository();
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
        $git['version'] = $this->normalizeRequestedVersion($this->argument('version'));

        if ($git['version'] == 'null') {
            if (isset($git['stable'])) {
                if (version_compare($currentVersion['version'], $git['stable'], '!=')) {
                    $git['version'] = $git['stable'];
                }
            }
        }
        if ($git['version'] != '') {
            $url = $this->buildArchiveUrl($updateRepository, $git['version']);
            $this->line('<fg=green>Start download Evolution CMS</>');
            $url = file_get_contents($url);
            $file = EVO_BASE_PATH . 'new_version.zip';

            file_put_contents($file, $url);
            $this->line('<fg=green>Start unpacking Evolution CMS</>');

            $temp_dir = EVO_BASE_PATH . '_temp' . md5(time());
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

            self::moveFiles($temp_dir . '/' . $dir, EVO_BASE_PATH);
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

            $delete_file = EVO_BASE_PATH . 'install/stubs/file_for_delete.txt';
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
            $this->line('<fg=green>Rebuild optimized autoload</>');
            $this->runCoreShellCommand('composer dump-autoload -o --no-dev --classmap-authoritative');
            $this->line('<fg=green>Run Core Migrations</>');
            $this->runCoreShellCommand('php artisan migrate --force');

            $this->line('<fg=green>Remove Install Directory</>');
            self::rmdirs(EVO_BASE_PATH . 'install');

            $this->line('<fg=green>Run Composer update</>');
            $this->runCoreShellCommand('composer update');
            $this->line('<fg=green>Rebuild optimized autoload</>');
            $this->runCoreShellCommand('composer dump-autoload -o --no-dev --classmap-authoritative');

            $this->line('<fg=yellow;bg=blue>Now You use ' . $factoryName . '</>');
        } else {
            $this->line('<fg=yellow;bg=blue>You use almost current version</>');
        }
    }

    /**
     * Resolve the GitHub repository used as the update source.
     *
     * @since 3.5.5
     * @return string Repository slug in the "vendor/repository" format.
     */
    protected function resolveUpdateRepository(): string
    {
        $updateRepository = (string) evo()->getConfig('UpgradeRepository');

        return $updateRepository !== '' ? trim($updateRepository, '/') : self::DEFAULT_UPGRADE_REPOSITORY;
    }

    /**
     * Run a shell command from the core directory.
     *
     * Core updates may be started from the manager, cron, or a shell with a different
     * current working directory, so Composer and installer calls must not rely on cwd.
     *
     * @since 3.5.7
     * @param string $command Command to execute from EVO_CORE_PATH.
     * @return void
     */
    protected function runCoreShellCommand(string $command): void
    {
        $fullCommand = 'cd ' . escapeshellarg(EVO_CORE_PATH) . ' && ' . $command;
        exec($fullCommand, $output, $exitCode);

        if ((int) $exitCode !== 0) {
            $message = 'Command failed: ' . $command;
            if (!empty($output)) {
                $message .= '. ' . implode("\n", array_slice($output, -8));
            }

            throw new \RuntimeException($message);
        }
    }

    /**
     * Normalize the optional version/ref argument passed to the command.
     *
     * Empty values are converted to the legacy "null" marker expected by the existing logic.
     *
     * @since 3.5.5
     * @param mixed $version Raw version argument from Artisan input.
     * @return string Normalized ref name or the "null" placeholder.
     */
    protected function normalizeRequestedVersion($version): string
    {
        if ($version === null) {
            return 'null';
        }

        $version = trim((string) $version);

        return $version === '' ? 'null' : $version;
    }

    /**
     * Build a codeload archive URL for a Git tag, branch or commit hash.
     *
     * Semantic versions are treated as tags, hex hashes as commits, and all other refs
     * are resolved as branch names.
     *
     * @since 3.5.5
     * @param string $repository Repository slug in the "vendor/repository" format.
     * @param string $ref Tag, branch or commit hash to download.
     * @return string Download URL for the requested archive.
     */
    protected function buildArchiveUrl(string $repository, string $ref): string
    {
        $repository = trim($repository, '/');
        $ref = trim($ref);

        if ($this->isSemanticVersionTag($ref)) {
            return sprintf('https://codeload.github.com/%s/zip/refs/tags/%s', $repository, rawurlencode($ref));
        }

        if ($this->isCommitHash($ref)) {
            return sprintf('https://codeload.github.com/%s/zip/%s', $repository, rawurlencode($ref));
        }

        return sprintf(
            'https://codeload.github.com/%s/zip/refs/heads/%s',
            $repository,
            str_replace('%2F', '/', rawurlencode($ref))
        );
    }

    /**
     * Determine whether the given ref looks like a semantic-version tag.
     *
     * @since 3.5.5
     * @param string $ref Candidate ref name.
     * @return bool True when the ref matches the expected release-tag pattern.
     */
    protected function isSemanticVersionTag(string $ref): bool
    {
        return preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9\.-]+)?$/', $ref) === 1;
    }

    /**
     * Determine whether the given ref looks like a Git commit hash.
     *
     * @since 3.5.5
     * @param string $ref Candidate ref name.
     * @return bool True when the ref is a 7-40 character hexadecimal hash.
     */
    protected function isCommitHash(string $ref): bool
    {
        return preg_match('/^[0-9a-f]{7,40}$/i', $ref) === 1;
    }

    /**
     * Recursively move files from the extracted update archive into the site root.
     *
     * Destination directories are created on demand. Existing writable files are replaced.
     *
     * @param string $src Absolute path to the extracted source directory.
     * @param string $dest Absolute path to the destination root.
     * @return void
     */
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

    /**
     * Recursively delete a directory and all of its contents.
     *
     * @param string $dir Absolute path to the directory being removed.
     * @return void
     */
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

    /**
     * Create a directory when it does not already exist.
     *
     * @param string $folder Absolute path to the directory.
     * @param int $perm Octal permissions passed to mkdir().
     * @return void
     */
    static public function mmkDir($folder, $perm = 0777)
    {
        if (!is_dir($folder)) {
            mkdir($folder, $perm);
        }
    }
}
