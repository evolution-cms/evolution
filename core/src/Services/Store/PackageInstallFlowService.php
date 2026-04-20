<?php namespace EvolutionCMS\Services\Store;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class PackageInstallFlowService
{
    protected array $lang;
    protected string $modulePath;
    protected RemoteTransportService $remoteTransportService;

    public function __construct(string $modulePath, array $lang = [], ?RemoteTransportService $remoteTransportService = null)
    {
        $this->modulePath = rtrim($modulePath, '/\\');
        $this->lang = $lang;
        $this->remoteTransportService = $remoteTransportService ?: new RemoteTransportService();
    }

    public function handleLegacyInstall(string $action, array $request, array $files, array $get = [], array $post = []): array
    {
        if (is_dir(EVO_BASE_PATH . 'assets/cache/store/')) {
            $this->removeFolder(EVO_BASE_PATH . 'assets/cache/store/');
        }

        $id = isset($request['cid']) ? (int) $request['cid'] : 0;
        @mkdir(EVO_BASE_PATH . 'assets/cache/store', 0777, true);
        @mkdir(EVO_BASE_PATH . 'assets/cache/store/tmp_install', 0777, true);
        @mkdir(EVO_BASE_PATH . 'assets/cache/store/install', 0777, true);

        $message = '';
        if ($action === 'install') {
            $file = isset($post['file']) && $post['file'] !== '' ? $post['file'] : ($get['file'] ?? '');
            if ($file !== '%url%' && $file !== '' && $file !== ' ') {
                $url = $file;
            } else {
                $url = "https://extras.evo.im/get.php?get=file&cid=" . $id;
            }

            if (!$this->downloadFile($url, EVO_BASE_PATH . "assets/cache/store/temp.zip")) {
                return [
                    'body' => '[{"result":"false","error":"Download failed"}]',
                    'content_type' => 'application/json; charset=UTF-8',
                ];
            }
        } else {
            $installFile = $files['install_file'] ?? null;
            $extension = pathinfo((string) ($installFile['name'] ?? ''), PATHINFO_EXTENSION);
            if (!in_array($extension, ['zip'], true)) {
                return ['body' => 'Only ZIP-Files allowed'];
            }
            if (!move_uploaded_file((string) ($installFile['tmp_name'] ?? ''), EVO_BASE_PATH . "assets/cache/store/temp.zip")) {
                return ['body' => 'Uploaded File could not be moved to assets/cache/store/'];
            }
            $message = $this->lang['install_file_success'] ?? 'Install file uploaded.';
        }

        $zip = new ZipArchive();
        $res = $zip->open(EVO_BASE_PATH . "assets/cache/store/temp.zip");
        if ($res === true) {
            $zip->extractTo(EVO_BASE_PATH . "assets/cache/store/tmp_install");
            $zip->close();

            $dir = $this->detectExtractedPackageDir(EVO_BASE_PATH . 'assets/cache/store/tmp_install');
            if ($dir !== '') {
                self::copyFolder(EVO_BASE_PATH . 'assets/cache/store/tmp_install/' . $dir, EVO_BASE_PATH . 'assets/cache/store/install');
                if (is_dir(EVO_BASE_PATH . 'assets/cache/store/tmp_install/install/')) {
                    $this->removeFolder(EVO_BASE_PATH . 'assets/cache/store/tmp_install/install/');
                }

                self::copyFolder(EVO_BASE_PATH . 'assets/cache/store/tmp_install/' . $dir, EVO_BASE_PATH);
                if (is_dir(EVO_BASE_PATH . 'install/')) {
                    $this->removeFolder(EVO_BASE_PATH . 'install/');
                }
            }
            if (is_dir(EVO_BASE_PATH . 'assets/cache/store/tmp_install/')) {
                $this->removeFolder(EVO_BASE_PATH . 'assets/cache/store/tmp_install/');
            }
        }

        $dependencyErrors = $this->buildDependencyErrorHtml($get);
        $method = isset($get['method']) ? (string) $get['method'] : '';

        if ($method !== 'fast') {
            if ($dependencyErrors !== '') {
                return ['body' => $dependencyErrors];
            }

            $_GET['action'] = 'options';
            $modx = \EvolutionCMS();
            $GLOBALS['modx'] = $modx;
            ob_start();
            require $this->modulePath . '/installer/index.php';
            $content = ob_get_clean();

            return ['body' => $content];
        }

        if ($dependencyErrors !== '') {
            return [
                'body' => json_encode(['result' => 'error', 'data' => $dependencyErrors]),
                'content_type' => 'application/json; charset=UTF-8',
            ];
        }

        $cwd = getcwd();
        chdir($this->modulePath . '/installer/');
        ob_start();
        require "instprocessor-fast.php";
        ob_end_clean();
        chdir($cwd);

        if (is_dir(EVO_BASE_PATH . 'assets/cache/store/')) {
            $this->removeFolder(EVO_BASE_PATH . 'assets/cache/store/');
        }

        if ($action === 'install') {
            return [
                'body' => '[{"result":"true"}]',
                'content_type' => 'application/json; charset=UTF-8',
            ];
        }

        return ['body' => $message];
    }

    public function downloadFile($url, $path)
    {
        return $this->remoteTransportService->downloadFile($url, $path);
    }

    public function removeFolder($path)
    {
        $dir = realpath($path);
        if (!is_dir($dir)) {
            return;
        }
        $it = new RecursiveDirectoryIterator($dir);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->getFilename() === '.' || $file->getFilename() === '..') {
                continue;
            }
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

    public static function copyFolder($src, $dest)
    {
        $path = realpath($src);
        $dest = realpath($dest);
        $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($objects as $name => $object) {
            if (!$objects->getDepth() && $object->isFile()) {
                continue;
            }
            $startsAt = substr(dirname($name), strlen($path));
            self::mkDir($dest . $startsAt);
            if ($object->isDir()) {
                @self::mkDir($dest . substr($name, strlen($path)));
            }

            if (is_writable($dest . $startsAt) and $object->isFile()) {
                copy((string) $name, $dest . $startsAt . DIRECTORY_SEPARATOR . basename($name));
            }
        }
    }

    private static function mkDir($folder, $perm = 0777)
    {
        if (!is_dir($folder)) {
            mkdir($folder, $perm);
        }
    }

    private function detectExtractedPackageDir(string $tmpInstallPath): string
    {
        $dir = '';
        $handle = @opendir($tmpInstallPath);
        if ($handle) {
            while (false !== ($name = readdir($handle))) {
                if ($name !== "." && $name !== "..") {
                    $dir = $name;
                }
            }
            closedir($handle);
        }

        return $dir;
    }

    private function buildDependencyErrorHtml(array $get): string
    {
        $arrDependencies = [];
        if (isset($get['dependencies']) && $get['dependencies'] !== '') {
            $arrDependencies = explode(',', $get['dependencies']);
            $result = \EvolutionCMS\Models\SiteSnippet::query()->whereIn('name', $arrDependencies)->pluck('name');
            foreach ($result as $value) {
                $key = array_search($value, $arrDependencies, true);
                if ($key !== false) {
                    unset($arrDependencies[$key]);
                }
            }
            if (count($arrDependencies) > 0) {
                $result = \EvolutionCMS\Models\SitePlugin::query()->whereIn('name', $arrDependencies)->pluck('name');
                foreach ($result as $value) {
                    $key = array_search($value, $arrDependencies, true);
                    if ($key !== false) {
                        unset($arrDependencies[$key]);
                    }
                }
            }
            if (count($arrDependencies) > 0) {
                $result = \EvolutionCMS\Models\SiteModule::query()->whereIn('name', $arrDependencies)->pluck('name');
                foreach ($result as $value) {
                    $key = array_search($value, $arrDependencies, true);
                    if ($key !== false) {
                        unset($arrDependencies[$key]);
                    }
                }
            }
        }

        if (count($arrDependencies) <= 0) {
            return '';
        }

        $bodyClass = ((int) evo()->config['manager_theme_mode'] === 4) ? ' class="darkness"' : '';
        $html = '<!DOCTYPE html>
<html><head><title>Install</title>
<meta http-equiv="Content-Type" content="text/html; charset="utf-8" />
<link rel="stylesheet" href="' . EVO_SITE_URL . 'assets/modules/store/installer/style.css" type="text/css" media="screen" /></head>
<body' . $bodyClass . '><div id="contentarea"><div class="container_12"><br>';
        $html .= '<h2>Error installation</h2><br><br><p>Before install ' . htmlspecialchars((string) ($get['name'] ?? '')) . '<br> Please install this packages: <br>' . implode('<br>', $arrDependencies) . '</p>';
        $html .= "</div><!-- // content --></div><!-- // contentarea --><br /></body></html>";

        return $html;
    }
}
