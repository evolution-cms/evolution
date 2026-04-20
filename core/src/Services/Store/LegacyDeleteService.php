<?php namespace EvolutionCMS\Services\Store;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class LegacyDeleteService
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

    public function buildLegacyDeletePreview($cid, $file, $name = '', $installedVersion = '')
    {
        $cid = (int) $cid;
        $file = trim((string) $file);
        $name = trim((string) $name);
        $installedVersion = trim((string) $installedVersion);

        if ($cid <= 0) {
            return [
                'ok' => false,
                'message' => $this->langValue('legacy_delete_error', 'Unable to build delete preview.'),
            ];
        }

        $workspace = $this->prepareLegacyDeleteWorkspace();
        if (!$workspace['ok']) {
            return [
                'ok' => false,
                'message' => $workspace['message'],
            ];
        }

        $downloadUrl = $this->resolveLegacyArchiveUrl($cid, $file);
        if (!$this->downloadFile($downloadUrl, $workspace['zip'])) {
            $this->cleanupLegacyDeleteWorkspace($workspace);
            return [
                'ok' => false,
                'message' => $this->langValue('legacy_delete_download_error', 'Unable to download this package archive for delete preview.'),
            ];
        }

        $zip = new ZipArchive();
        $result = $zip->open($workspace['zip']);
        if ($result !== true) {
            $this->cleanupLegacyDeleteWorkspace($workspace);
            return [
                'ok' => false,
                'message' => $this->langValue('legacy_delete_archive_error', 'Unable to open the package archive.'),
            ];
        }

        $zip->extractTo($workspace['tmp']);
        $zip->close();

        $rootPath = $this->detectExtractRoot($workspace['tmp']);
        if ($rootPath === '') {
            $this->cleanupLegacyDeleteWorkspace($workspace);
            return [
                'ok' => false,
                'message' => $this->langValue('legacy_delete_archive_error', 'Unable to inspect the package archive.'),
            ];
        }

        self::copyFolder($rootPath, $workspace['install']);

        $packageDefinition = $this->loadLegacyPackageDefinition();
        $fileEntries = $this->buildLegacyDeleteFileEntries($rootPath);
        $dbEntries = $this->buildLegacyDeleteDbEntries($packageDefinition);

        $selection = [];
        foreach ($fileEntries as $entry) {
            $selection[] = $entry['key'];
            foreach (($entry['children'] ?? []) as $childEntry) {
                if (!empty($childEntry['key'])) {
                    $selection[] = $childEntry['key'];
                }
            }
        }
        foreach ($dbEntries as $groupEntries) {
            foreach ($groupEntries as $entry) {
                $selection[] = $entry['key'];
            }
        }

        $token = bin2hex(random_bytes(16));
        $manifest = [
            'token' => $token,
            'cid' => $cid,
            'name' => $name,
            'installed_version' => $installedVersion,
            'file' => $file,
            'download_url' => $downloadUrl,
            'files' => $fileEntries,
            'db' => $dbEntries,
            'created_at' => time(),
        ];

        $manifestDir = $this->getLegacyDeleteManifestDirectory();
        if (!is_dir($manifestDir)) {
            @mkdir($manifestDir, 0777, true);
        }
        file_put_contents($manifestDir . '/' . $token . '.json', json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->cleanupLegacyDeleteWorkspace($workspace);

        return [
            'ok' => true,
            'token' => $token,
            'name' => $name,
            'installed_version' => $installedVersion,
            'files' => $fileEntries,
            'db' => $dbEntries,
            'default_selection' => $selection,
            'summary' => [
                'file_count' => count($fileEntries),
                'db_count' => $this->countLegacyDeleteDbEntries($dbEntries),
            ],
        ];
    }

    public function runLegacyDelete($token, array $selection)
    {
        $token = preg_replace('/[^a-f0-9]/i', '', (string) $token);
        if ($token === '') {
            return [
                'ok' => false,
                'message' => $this->langValue('legacy_delete_error', 'Unable to delete this package.'),
            ];
        }

        $manifestPath = $this->getLegacyDeleteManifestDirectory() . '/' . $token . '.json';
        if (!is_file($manifestPath)) {
            return [
                'ok' => false,
                'message' => $this->langValue('legacy_delete_manifest_missing', 'Delete preview has expired. Please open it again.'),
            ];
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($manifest)) {
            @unlink($manifestPath);
            return [
                'ok' => false,
                'message' => $this->langValue('legacy_delete_manifest_missing', 'Delete preview has expired. Please open it again.'),
            ];
        }

        $allowed = $this->collectLegacyDeleteKeys($manifest);
        $selectedLookup = [];
        foreach ($selection as $key) {
            $key = (string) $key;
            if ($key !== '' && isset($allowed[$key])) {
                $selectedLookup[$key] = true;
            }
        }

        $deletedFiles = 0;
        $deletedDb = 0;
        $deletedDirs = [];

        foreach (($manifest['db']['plugins'] ?? []) as $entry) {
            if (empty($selectedLookup[$entry['key']])) {
                continue;
            }
            $id = (int) ($entry['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            \EvolutionCMS\Models\SitePluginEvent::query()->where('pluginid', $id)->delete();
            $deletedDb += (int) \EvolutionCMS\Models\SitePlugin::query()->where('id', $id)->delete();
        }

        foreach (($manifest['db']['snippets'] ?? []) as $entry) {
            if (empty($selectedLookup[$entry['key']])) {
                continue;
            }
            $id = (int) ($entry['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $deletedDb += (int) \EvolutionCMS\Models\SiteSnippet::query()->where('id', $id)->delete();
        }

        foreach (($manifest['db']['modules'] ?? []) as $entry) {
            if (empty($selectedLookup[$entry['key']])) {
                continue;
            }
            $id = (int) ($entry['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            \EvolutionCMS\Models\SiteModuleAccess::query()->where('module', $id)->delete();
            $deletedDb += (int) \EvolutionCMS\Models\SiteModule::query()->where('id', $id)->delete();
        }

        foreach (($manifest['db']['chunks'] ?? []) as $entry) {
            if (empty($selectedLookup[$entry['key']])) {
                continue;
            }
            $id = (int) ($entry['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $deletedDb += (int) \EvolutionCMS\Models\SiteHtmlsnippet::query()->where('id', $id)->delete();
        }

        foreach (($manifest['db']['templates'] ?? []) as $entry) {
            if (empty($selectedLookup[$entry['key']])) {
                continue;
            }
            $id = (int) ($entry['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $deletedDb += (int) \EvolutionCMS\Models\SiteTemplate::query()->where('id', $id)->delete();
        }

        foreach (($manifest['db']['tvs'] ?? []) as $entry) {
            if (empty($selectedLookup[$entry['key']])) {
                continue;
            }
            $id = (int) ($entry['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            \EvolutionCMS\Models\SiteTmplvarTemplate::query()->where('tmplvarid', $id)->delete();
            $deletedDb += (int) \EvolutionCMS\Models\SiteTmplvar::query()->where('id', $id)->delete();
        }

        $fileEntries = isset($manifest['files']) && is_array($manifest['files']) ? $manifest['files'] : [];
        usort($fileEntries, function ($a, $b) {
            return strlen((string) ($b['path'] ?? '')) <=> strlen((string) ($a['path'] ?? ''));
        });

        $deletedPaths = [];

        foreach ($fileEntries as $entry) {
            $path = isset($entry['path']) ? (string) $entry['path'] : '';
            if ($path === '' || (!is_file($path) && !is_dir($path))) {
                continue;
            }

            $entryKey = isset($entry['key']) ? (string) $entry['key'] : '';
            $entrySelected = $entryKey !== '' && !empty($selectedLookup[$entryKey]);
            $childEntries = isset($entry['children']) && is_array($entry['children']) ? $entry['children'] : [];

            if ($entrySelected) {
                $deleted = false;
                if (is_dir($path)) {
                    $this->removeFolder($path);
                    $deleted = !is_dir($path);
                } else {
                    $deleted = @unlink($path);
                }

                if ($deleted) {
                    $deletedFiles++;
                    $deletedDirs[] = dirname($path);
                    $deletedPaths[$path] = true;
                }
                continue;
            }

            foreach ($childEntries as $childEntry) {
                $childKey = isset($childEntry['key']) ? (string) $childEntry['key'] : '';
                if ($childKey === '' || empty($selectedLookup[$childKey])) {
                    continue;
                }

                $childPath = isset($childEntry['path']) ? (string) $childEntry['path'] : '';
                if ($childPath === '' || isset($deletedPaths[$childPath]) || !is_file($childPath)) {
                    continue;
                }

                if (@unlink($childPath)) {
                    $deletedFiles++;
                    $deletedDirs[] = dirname($childPath);
                    $deletedPaths[$childPath] = true;
                }
            }
        }

        $this->cleanupEmptyDirectories($deletedDirs);
        @unlink($manifestPath);

        return [
            'ok' => true,
            'message' => $this->langValue('legacy_delete_success', 'Legacy package was deleted.'),
            'summary' => [
                'files' => $deletedFiles,
                'db' => $deletedDb,
            ],
        ];
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

    private function prepareLegacyDeleteWorkspace()
    {
        $base = EVO_BASE_PATH . 'assets/cache/store';
        $tmp = $base . '/tmp_delete';
        $install = $base . '/install';
        $zip = $base . '/temp-delete.zip';

        if (is_dir($tmp)) {
            $this->removeFolder($tmp);
        }
        if (is_dir($install)) {
            $this->removeFolder($install);
        }
        if (is_file($zip)) {
            @unlink($zip);
        }

        @mkdir($base, 0777, true);
        @mkdir($tmp, 0777, true);
        @mkdir($install, 0777, true);

        if (!is_dir($tmp) || !is_dir($install)) {
            return [
                'ok' => false,
                'message' => $this->langValue('legacy_delete_error', 'Unable to prepare delete preview workspace.'),
            ];
        }

        return [
            'ok' => true,
            'tmp' => $tmp,
            'install' => $install,
            'zip' => $zip,
        ];
    }

    private function cleanupLegacyDeleteWorkspace(array $workspace)
    {
        if (!empty($workspace['tmp']) && is_dir($workspace['tmp'])) {
            $this->removeFolder($workspace['tmp']);
        }
        if (!empty($workspace['install']) && is_dir($workspace['install'])) {
            $this->removeFolder($workspace['install']);
        }
        if (!empty($workspace['zip']) && is_file($workspace['zip'])) {
            @unlink($workspace['zip']);
        }
    }

    private function resolveLegacyArchiveUrl($cid, $file)
    {
        $cid = (int) $cid;
        $file = trim((string) $file);
        if ($file !== '' && $file !== '%url%' && $file !== ' ') {
            return $file;
        }

        return 'https://extras.evo.im/get.php?get=file&cid=' . $cid;
    }

    private function detectExtractRoot($tmpDir)
    {
        $tmpDir = realpath($tmpDir);
        if ($tmpDir === false || !is_dir($tmpDir)) {
            return '';
        }

        $entries = array_values(array_filter(scandir($tmpDir) ?: [], function ($entry) {
            return $entry !== '.' && $entry !== '..';
        }));

        if (count($entries) === 1 && is_dir($tmpDir . '/' . $entries[0])) {
            return $tmpDir . '/' . $entries[0];
        }

        return $tmpDir;
    }

    private function loadLegacyPackageDefinition()
    {
        $moduleTemplates = [];
        $moduleTVs = [];
        $moduleChunks = [];
        $moduleSnippets = [];
        $modulePlugins = [];
        $moduleModules = [];
        $evo_branch = '';
        $evo_version = '';
        $evo_release_date = '';

        if (!defined('MGR')) {
            define('MGR', EVO_BASE_PATH . MGR_DIR);
        }

        include $this->modulePath . '/installer/setup.info.php';

        return [
            'templates' => $moduleTemplates,
            'tvs' => $moduleTVs,
            'chunks' => $moduleChunks,
            'snippets' => $moduleSnippets,
            'plugins' => $modulePlugins,
            'modules' => $moduleModules,
        ];
    }

    private function buildLegacyDeleteFileEntries($rootPath)
    {
        $entries = [];
        $rootPath = realpath($rootPath);
        if ($rootPath === false || !is_dir($rootPath)) {
            return $entries;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            if ($iterator->getDepth() === 0) {
                continue;
            }

            $absolutePath = $fileInfo->getRealPath();
            $relativePath = ltrim(str_replace($rootPath, '', $absolutePath), DIRECTORY_SEPARATOR);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
            if ($this->isLegacyDeleteIgnoredPath($relativePath)) {
                continue;
            }

            if ($relativePath === '' || strpos($relativePath, 'install/') === 0) {
                continue;
            }

            $deleteRelativePath = $this->resolveLegacyDeleteTargetPath($relativePath);
            if ($this->isLegacyDeleteIgnoredPath($deleteRelativePath)) {
                continue;
            }

            $projectPath = EVO_BASE_PATH . $deleteRelativePath;
            if (!is_file($projectPath) && !is_dir($projectPath)) {
                continue;
            }

            $key = 'file:' . md5($deleteRelativePath);
            if (!isset($entries[$key])) {
                $entries[$key] = [
                    'key' => $key,
                    'type' => is_dir($projectPath) ? 'dir' : 'file',
                    'group' => $this->detectLegacyFileGroup($deleteRelativePath),
                    'label' => $deleteRelativePath,
                    'relative_path' => $deleteRelativePath,
                    'path' => $projectPath,
                    'children' => [],
                ];
            }

            if ($deleteRelativePath !== $relativePath) {
                $childProjectPath = EVO_BASE_PATH . $relativePath;
                if (is_file($childProjectPath)) {
                    $childKey = 'file:' . md5($relativePath);
                    $entries[$key]['children'][$relativePath] = [
                        'key' => $childKey,
                        'type' => 'file',
                        'label' => $relativePath,
                        'relative_path' => $relativePath,
                        'path' => $childProjectPath,
                    ];
                }
            }
        }

        $entries = array_values($entries);
        foreach ($entries as &$entry) {
            $entry['children'] = array_values($entry['children']);
            usort($entry['children'], function ($a, $b) {
                return strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
            });
        }
        unset($entry);

        usort($entries, function ($a, $b) {
            $groupCompare = strcmp((string) $a['group'], (string) $b['group']);
            if ($groupCompare !== 0) {
                return $groupCompare;
            }
            return strcmp((string) $a['label'], (string) $b['label']);
        });

        return $entries;
    }

    private function isLegacyDeleteIgnoredPath($relativePath)
    {
        $relativePath = ltrim(str_replace('\\', '/', (string) $relativePath), '/');
        if ($relativePath === '') {
            return false;
        }

        if ($relativePath === 'assets/images' || strpos($relativePath, 'assets/images/') === 0) {
            return true;
        }

        $segments = explode('/', $relativePath);
        foreach ($segments as $segment) {
            if (strtolower((string) $segment) === '.htaccess') {
                return true;
            }
        }

        return false;
    }

    private function resolveLegacyDeleteTargetPath($relativePath)
    {
        $relativePath = ltrim(str_replace('\\', '/', (string) $relativePath), '/');
        $segments = explode('/', $relativePath);

        if (
            count($segments) >= 3
            && $segments[0] === 'assets'
            && in_array($segments[1], ['plugins', 'snippets', 'modules', 'tvs'], true)
        ) {
            return implode('/', array_slice($segments, 0, 3));
        }

        if (count($segments) >= 3 && $segments[0] === 'assets' && in_array($segments[1], ['js', 'lib'], true)) {
            return implode('/', array_slice($segments, 0, 3));
        }

        return $relativePath;
    }

    private function detectLegacyFileGroup($relativePath)
    {
        $relativePath = ltrim(str_replace('\\', '/', (string) $relativePath), '/');
        $map = [
            'assets/plugins/' => 'plugins',
            'assets/snippets/' => 'snippets',
            'assets/modules/' => 'modules',
            'assets/tvs/' => 'tvs',
        ];

        foreach ($map as $prefix => $group) {
            if (strpos($relativePath, $prefix) === 0) {
                return $group;
            }
        }

        return 'files';
    }

    private function buildLegacyDeleteDbEntries(array $definition)
    {
        $entries = [
            'snippets' => [],
            'plugins' => [],
            'modules' => [],
            'chunks' => [],
            'templates' => [],
            'tvs' => [],
        ];

        foreach (($definition['snippets'] ?? []) as $item) {
            $name = isset($item[0]) ? (string) $item[0] : '';
            if ($name === '') {
                continue;
            }
            $model = \EvolutionCMS\Models\SiteSnippet::query()->where('name', $name)->first();
            if (!$model) {
                continue;
            }
            $version = $this->extractLegacyVersion((string) $model->description);
            $entries['snippets'][] = [
                'key' => 'db:snippets:' . (int) $model->id,
                'id' => (int) $model->id,
                'name' => $name,
                'label' => $name,
                'version' => $version,
                'meta' => $this->buildLegacyDeleteDbMeta($version, (string) $model->description),
            ];
        }

        foreach (($definition['plugins'] ?? []) as $item) {
            $name = isset($item[0]) ? (string) $item[0] : '';
            if ($name === '') {
                continue;
            }
            $models = \EvolutionCMS\Models\SitePlugin::query()->where('name', $name)->get();
            foreach ($models as $model) {
                $version = $this->extractLegacyVersion((string) $model->description);
                $entries['plugins'][] = [
                    'key' => 'db:plugins:' . (int) $model->id,
                    'id' => (int) $model->id,
                    'name' => $name,
                    'label' => $name,
                    'version' => $version,
                    'meta' => $this->buildLegacyDeleteDbMeta($version, (string) $model->description),
                ];
            }
        }

        foreach (($definition['modules'] ?? []) as $item) {
            $name = isset($item[0]) ? (string) $item[0] : '';
            if ($name === '') {
                continue;
            }
            $models = \EvolutionCMS\Models\SiteModule::query()->where('name', $name)->get();
            foreach ($models as $model) {
                $version = $this->extractLegacyVersion((string) $model->description);
                $entries['modules'][] = [
                    'key' => 'db:modules:' . (int) $model->id,
                    'id' => (int) $model->id,
                    'name' => $name,
                    'label' => $name,
                    'version' => $version,
                    'meta' => $this->buildLegacyDeleteDbMeta($version, (string) $model->description),
                ];
            }
        }

        foreach (($definition['chunks'] ?? []) as $item) {
            $name = isset($item[0]) ? (string) $item[0] : '';
            if ($name === '') {
                continue;
            }
            $models = \EvolutionCMS\Models\SiteHtmlsnippet::query()->where('name', $name)->get();
            foreach ($models as $model) {
                $version = $this->extractLegacyVersion((string) $model->description);
                $entries['chunks'][] = [
                    'key' => 'db:chunks:' . (int) $model->id,
                    'id' => (int) $model->id,
                    'name' => $name,
                    'label' => $name,
                    'version' => $version,
                    'meta' => $this->buildLegacyDeleteDbMeta($version, (string) $model->description),
                ];
            }
        }

        foreach (($definition['templates'] ?? []) as $item) {
            $name = isset($item[0]) ? (string) $item[0] : '';
            if ($name === '') {
                continue;
            }
            $models = \EvolutionCMS\Models\SiteTemplate::query()->where('templatename', $name)->get();
            foreach ($models as $model) {
                $version = $this->extractLegacyVersion((string) $model->description);
                $entries['templates'][] = [
                    'key' => 'db:templates:' . (int) $model->id,
                    'id' => (int) $model->id,
                    'name' => $name,
                    'label' => $name,
                    'version' => $version,
                    'meta' => $this->buildLegacyDeleteDbMeta($version, (string) $model->description),
                ];
            }
        }

        foreach (($definition['tvs'] ?? []) as $item) {
            $name = isset($item[0]) ? (string) $item[0] : '';
            if ($name === '') {
                continue;
            }
            $models = \EvolutionCMS\Models\SiteTmplvar::query()->where('name', $name)->get();
            foreach ($models as $model) {
                $version = $this->extractLegacyVersion((string) $model->description);
                $entries['tvs'][] = [
                    'key' => 'db:tvs:' . (int) $model->id,
                    'id' => (int) $model->id,
                    'name' => $name,
                    'label' => $name,
                    'version' => $version,
                    'meta' => $this->buildLegacyDeleteDbMeta($version, (string) $model->description),
                ];
            }
        }

        return $entries;
    }

    private function buildLegacyDeleteDbMeta($version, $description)
    {
        $version = trim((string) $version);
        $description = trim(strip_tags((string) $description));

        if ($version !== '' && strpos($description, $version) === 0) {
            $description = trim(substr($description, strlen($version)));
            $description = ltrim($description, "-–— \t");
        }

        if ($version !== '' && $description !== '') {
            return $version . ' - ' . $description;
        }
        if ($description !== '') {
            return $description;
        }
        return $version;
    }

    private function countLegacyDeleteDbEntries(array $groups)
    {
        $count = 0;
        foreach ($groups as $entries) {
            $count += is_array($entries) ? count($entries) : 0;
        }
        return $count;
    }

    private function collectLegacyDeleteKeys(array $manifest)
    {
        $keys = [];
        foreach (($manifest['files'] ?? []) as $entry) {
            if (!empty($entry['key'])) {
                $keys[$entry['key']] = true;
            }
            if (!empty($entry['children']) && is_array($entry['children'])) {
                foreach ($entry['children'] as $childEntry) {
                    if (!empty($childEntry['key'])) {
                        $keys[$childEntry['key']] = true;
                    }
                }
            }
        }
        foreach (($manifest['db'] ?? []) as $entries) {
            if (!is_array($entries)) {
                continue;
            }
            foreach ($entries as $entry) {
                if (!empty($entry['key'])) {
                    $keys[$entry['key']] = true;
                }
            }
        }
        return $keys;
    }

    private function cleanupEmptyDirectories(array $dirs)
    {
        $dirs = array_unique(array_filter(array_map(function ($dir) {
            $dir = realpath($dir);
            if ($dir === false) {
                return '';
            }
            $base = realpath(EVO_BASE_PATH);
            if ($base === false || strpos($dir, $base) !== 0) {
                return '';
            }
            return $dir;
        }, $dirs)));

        usort($dirs, function ($a, $b) {
            return strlen((string) $b) <=> strlen((string) $a);
        });

        $stopDirs = array_filter([
            realpath(EVO_BASE_PATH . 'assets'),
            realpath(EVO_BASE_PATH . 'manager'),
            realpath(EVO_BASE_PATH . 'core'),
            realpath(EVO_BASE_PATH),
        ]);

        foreach ($dirs as $dir) {
            $current = $dir;
            while ($current && is_dir($current) && !in_array($current, $stopDirs, true)) {
                $items = array_diff(scandir($current) ?: [], ['.', '..']);
                if (!empty($items)) {
                    break;
                }
                @rmdir($current);
                $current = dirname($current);
            }
        }
    }

    private function getLegacyDeleteManifestDirectory()
    {
        return EVO_BASE_PATH . 'assets/cache/store/delete_manifests';
    }

    private function extractLegacyVersion($text)
    {
        preg_match('/<strong>(.*)<\/strong>/s', (string) $text, $match);
        return isset($match[1]) ? $match[1] : '';
    }

    private function langValue($key, $fallback = '')
    {
        return isset($this->lang[$key]) && $this->lang[$key] !== '' ? $this->lang[$key] : $fallback;
    }
}
