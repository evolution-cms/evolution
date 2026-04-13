<?php namespace EvolutionCMS\Services\Store;

class InstalledStateService
{
    public function getLegacyInstalledState()
    {
        $byType = [
            'snippets' => [],
            'plugins' => [],
            'modules' => [],
        ];
        $items = [];

        $snippets = \EvolutionCMS\Models\SiteSnippet::query()->get();
        foreach ($snippets as $snippet) {
            $version = $this->extractLegacyVersion($snippet->description);
            $byType['snippets'][$snippet->name] = $version;
            $items[] = [
                'type' => 'snippets',
                'name' => $snippet->name,
                'version' => $version,
                'is_installed' => 1,
            ];
        }
        $byType['snippets_writable'] = is_writable(EVO_BASE_PATH . 'assets/snippets');

        $plugins = \EvolutionCMS\Models\SitePlugin::query()->get();
        foreach ($plugins as $plugin) {
            $version = $this->extractLegacyVersion($plugin->description);
            $byType['plugins'][$plugin->name] = $version;
            $items[] = [
                'type' => 'plugins',
                'name' => $plugin->name,
                'version' => $version,
                'is_installed' => 1,
            ];
        }
        $byType['plugins_writable'] = is_writable(EVO_BASE_PATH . 'assets/plugins');

        $modules = \EvolutionCMS\Models\SiteModule::query()->get();
        foreach ($modules as $module) {
            $version = $this->extractLegacyVersion($module->description);
            $byType['modules'][$module->name] = $version;
            $items[] = [
                'type' => 'modules',
                'name' => $module->name,
                'version' => $version,
                'is_installed' => 1,
            ];
        }
        $byType['modules_writable'] = is_writable(EVO_BASE_PATH . 'assets/modules');

        return [
            'by_type' => $byType,
            'items' => $items,
        ];
    }

    public function getConsoleInstalledState()
    {
        static $state = null;
        if ($state !== null) {
            return $state;
        }

        $state = [];
        if (!class_exists('\\Composer\\InstalledVersions')) {
            return $state;
        }

        try {
            foreach (\Composer\InstalledVersions::getInstalledPackages() as $packageName) {
                if (!is_string($packageName) || $packageName === '') {
                    continue;
                }

                $key = strtolower($packageName);
                $prettyVersion = '';
                try {
                    $prettyVersion = (string) \Composer\InstalledVersions::getPrettyVersion($packageName);
                } catch (\Throwable $exception) {
                    $prettyVersion = '';
                }

                $state[$key] = [
                    'is_installed' => true,
                    'raw_version' => $prettyVersion,
                    'version' => $this->normalizeInstalledComposerVersion($prettyVersion),
                ];
            }
        } catch (\Throwable $exception) {
            return [];
        }

        return $state;
    }

    public function resolveConsoleStatusClass($installedVersion, $catalogVersion, $defaultBranch = '')
    {
        $installedVersion = trim((string) $installedVersion);
        $catalogVersion = trim((string) $catalogVersion);
        $defaultBranch = trim((string) $defaultBranch);

        if ($installedVersion === '') {
            return 'pack_install';
        }

        $normalizedInstalled = $this->normalizeComparableVersion($installedVersion, $defaultBranch);
        $normalizedCatalog = $this->normalizeComparableVersion($catalogVersion, $defaultBranch);

        if ($normalizedInstalled !== '' && $normalizedCatalog !== '' && $normalizedInstalled === $normalizedCatalog) {
            return 'pack_reinstall';
        }

        if ($this->isComparableSemver($normalizedInstalled) && $this->isComparableSemver($normalizedCatalog)) {
            return version_compare($normalizedInstalled, $normalizedCatalog, '<') ? 'pack_update' : 'pack_reinstall';
        }

        return 'pack_reinstall';
    }

    public function normalizeInstalledComposerVersion($version)
    {
        $version = trim((string) $version);
        if ($version === '') {
            return '';
        }

        if (strpos($version, 'dev-') === 0) {
            return substr($version, 4);
        }

        return $version;
    }

    private function normalizeComparableVersion($version, $defaultBranch = '')
    {
        $version = trim((string) $version);
        $defaultBranch = trim((string) $defaultBranch);
        if ($version === '') {
            return '';
        }

        if (strpos($version, 'dev-') === 0) {
            $version = substr($version, 4);
        }

        if ($defaultBranch !== '' && $version === $defaultBranch) {
            return $defaultBranch;
        }

        if (preg_match('/^v(\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.\-]+)?)$/', $version, $matches)) {
            return $matches[1];
        }

        return $version;
    }

    private function isComparableSemver($version)
    {
        return (bool) preg_match('/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.\-]+)?$/', (string) $version);
    }

    private function extractLegacyVersion($text)
    {
        preg_match('/<strong>(.*)<\/strong>/s', (string) $text, $match);
        return isset($match[1]) ? $match[1] : '';
    }
}
