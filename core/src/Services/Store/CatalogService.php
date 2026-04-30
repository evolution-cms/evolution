<?php namespace EvolutionCMS\Services\Store;

class CatalogService
{
    private const CONSOLE_CATALOG_URL = 'https://evo.im/extras.json';
    private const CONSOLE_CATALOG_CACHE_TTL = 900;

    protected InstalledStateService $installedStateService;
    protected RemoteTransportService $remoteTransportService;
    protected array $lang;

    public function __construct(?InstalledStateService $installedStateService = null, array $lang = [], ?RemoteTransportService $remoteTransportService = null)
    {
        $this->installedStateService = $installedStateService ?: new InstalledStateService();
        $this->lang = $lang;
        $this->remoteTransportService = $remoteTransportService ?: new RemoteTransportService();
    }

    public function getConsoleCatalog()
    {
        $packages = $this->loadConsoleCatalogPackages();
        if ($packages === []) {
            return [];
        }

        $items = [];
        $consoleInstalled = $this->installedStateService->getConsoleInstalledState();
        foreach ($packages as $package) {
            if (!is_array($package)) {
                continue;
            }

            $name = isset($package['name']) ? trim((string) $package['name']) : '';
            $composerName = isset($package['composer_name']) ? trim((string) $package['composer_name']) : '';
            if ($name === '' || $composerName === '') {
                continue;
            }

            $latestRelease = isset($package['latest_release']) ? trim((string) $package['latest_release']) : '';
            $defaultBranch = isset($package['default_branch']) ? trim((string) $package['default_branch']) : '';
            $tags = isset($package['tags']) && is_array($package['tags']) ? $package['tags'] : [];
            $installVersion = $latestRelease !== '' ? $latestRelease : $defaultBranch;
            $displayVersion = $installVersion !== '' ? $installVersion : 'main';
            $fullName = isset($package['full_name']) ? trim((string) $package['full_name']) : '';
            $downloads = $this->resolvePackageDownloads($package);
            $author = '';
            $composerKey = strtolower($composerName);
            if ($fullName !== '' && strpos($fullName, '/') !== false) {
                $author = explode('/', $fullName)[0];
            }
            $installedVersion = '';
            $rawInstalledVersion = '';
            $isInstalled = false;
            if (isset($consoleInstalled[$composerKey])) {
                $isInstalled = !empty($consoleInstalled[$composerKey]['is_installed']);
                $installedVersion = isset($consoleInstalled[$composerKey]['version']) ? (string) $consoleInstalled[$composerKey]['version'] : '';
                $rawInstalledVersion = isset($consoleInstalled[$composerKey]['raw_version']) ? (string) $consoleInstalled[$composerKey]['raw_version'] : '';
            }
            $statusClass = $this->installedStateService->resolveConsoleStatusClass($installedVersion, $displayVersion, $defaultBranch);

            $versionOptions = [];
            $stableTags = [];
            foreach ($tags as $tag) {
                if (!is_string($tag) || trim($tag) === '') {
                    continue;
                }
                $tag = trim($tag);
                if (!$this->isStableReleaseVersion($tag)) {
                    continue;
                }
                $stableTags[] = $tag;
                $versionOptions[] = [
                    'file' => $tag,
                    'version' => $tag,
                    'date' => '',
                ];
            }
            $isDevOnly = count($stableTags) === 0;
            if ($defaultBranch !== '') {
                $hasDefaultBranchOption = false;
                foreach ($versionOptions as $option) {
                    if (isset($option['file']) && (string) $option['file'] === $defaultBranch) {
                        $hasDefaultBranchOption = true;
                        break;
                    }
                }
                if (!$hasDefaultBranchOption) {
                    $versionOptions[] = [
                        'file' => $defaultBranch,
                        'version' => $defaultBranch,
                        'date' => '',
                    ];
                }
            }
            if ($versionOptions === [] && $defaultBranch !== '') {
                $versionOptions[] = [
                    'file' => $defaultBranch,
                    'version' => $defaultBranch,
                    'date' => '',
                ];
            }

            $items[] = [
                'id' => 'console-' . md5($composerName),
                'title' => $name,
                'name' => $name,
                'name_in_modx' => $name,
                'composer_name' => $composerName,
                'description' => isset($package['description']) ? trim((string) $package['description']) : '',
                'type' => 'package',
                'install_method' => 'console-extra',
                'install_target' => $name,
                'install_command' => '',
                'version' => $displayVersion,
                'current_version' => $installedVersion,
                'raw_current_version' => $rawInstalledVersion,
                'is_installed' => $isInstalled ? 1 : 0,
                'cls' => $statusClass,
                'downloads' => $downloads,
                'author' => $author,
                'date' => '',
                'source_url' => isset($package['html_url']) ? trim((string) $package['html_url']) : '',
                'repo_full_name' => $fullName,
                'readme_branch' => $defaultBranch !== '' ? $defaultBranch : 'main',
                'url' => [
                    'fieldValue' => $versionOptions,
                ],
                'dependencies' => '',
                'deprecated' => 0,
                'is_dev_package' => $isDevOnly ? 1 : 0,
            ];
        }

        usort($items, function ($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });

        return $items;
    }

    public function getConsoleReadmePayload($repo, $branch = '', $sourceUrl = '')
    {
        $repo = $this->sanitizeRepoFullName($repo);
        $branch = $this->sanitizeRefName($branch);
        $repoUrl = trim((string) $sourceUrl);

        if ($repo === '') {
            return [
                'ok' => false,
                'html' => '',
                'message' => $this->langValue('popup_readme_missing', 'README was not found for this package.'),
                'repo_url' => $repoUrl,
            ];
        }

        if ($repoUrl === '') {
            $repoUrl = 'https://github.com/' . $repo;
        }

        if ($branch === '') {
            $branch = 'main';
        }

        foreach (['README.md', 'readme.md', 'Readme.md'] as $fileName) {
            $raw = $this->fetchRemoteBody($this->buildGithubRawUrl($repo, $branch, $fileName));
            if (!is_string($raw)) {
                continue;
            }

            $trimmed = trim($raw);
            if ($trimmed === '' || $trimmed === '404: Not Found') {
                continue;
            }

            return [
                'ok' => true,
                'html' => $this->renderMarkdownHtml($raw, $repoUrl, $branch),
                'message' => '',
                'repo_url' => $repoUrl,
            ];
        }

        return [
            'ok' => false,
            'html' => '',
            'message' => $this->langValue('popup_readme_missing', 'README was not found for this package.'),
            'repo_url' => $repoUrl,
        ];
    }

    public function fetchRemoteBody($url)
    {
        return $this->remoteTransportService->fetchBody($url);
    }

    private function loadConsoleCatalogPackages(): array
    {
        $cachedFresh = $this->readConsoleCatalogCache(false);
        $packages = $this->decodeConsoleCatalogPackages($cachedFresh);
        if ($packages !== []) {
            return $packages;
        }

        $remote = $this->fetchRemoteBody(self::CONSOLE_CATALOG_URL);
        $packages = $this->decodeConsoleCatalogPackages($remote);
        if ($packages !== []) {
            $this->writeConsoleCatalogCache((string) $remote);
            return $packages;
        }

        return $this->decodeConsoleCatalogPackages($this->readConsoleCatalogCache(true));
    }

    private function decodeConsoleCatalogPackages($raw): array
    {
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['packages']) || !is_array($data['packages'])) {
            return [];
        }

        return $data['packages'];
    }

    private function readConsoleCatalogCache(bool $allowStale): ?string
    {
        $path = $this->getConsoleCatalogCachePath();
        if (!is_file($path)) {
            return null;
        }

        $modifiedAt = @filemtime($path);
        if (!$allowStale && (!$modifiedAt || ($modifiedAt + self::CONSOLE_CATALOG_CACHE_TTL) < time())) {
            return null;
        }

        $raw = @file_get_contents($path);
        return is_string($raw) ? $raw : null;
    }

    private function writeConsoleCatalogCache(string $raw): void
    {
        $directory = $this->getConsoleCatalogCacheDirectory();
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        if (!is_dir($directory)) {
            return;
        }

        @file_put_contents($this->getConsoleCatalogCachePath(), $raw, LOCK_EX);
    }

    private function getConsoleCatalogCacheDirectory(): string
    {
        return rtrim(EVO_CORE_PATH, '/\\') . DIRECTORY_SEPARATOR . 'custom' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'store';
    }

    private function resolvePackageDownloads(array $package): string
    {
        foreach (['downloads', 'downloads_monthly', 'downloads_daily'] as $field) {
            if (!array_key_exists($field, $package) || $package[$field] === null || $package[$field] === '') {
                continue;
            }

            if (is_numeric($package[$field])) {
                $value = (int) $package[$field];
                if ($value > 0) {
                    return (string) $value;
                }
            }
        }

        return '';
    }

    private function getConsoleCatalogCachePath(): string
    {
        return $this->getConsoleCatalogCacheDirectory() . DIRECTORY_SEPARATOR . 'console-catalog.json';
    }

    private function isStableReleaseVersion($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }

        return (bool) preg_match('/^v?\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.\-]+)?$/', $value);
    }

    private function sanitizeRepoFullName($repo)
    {
        $repo = trim((string) $repo);
        if ($repo === '') {
            return '';
        }

        if (!preg_match('~^[A-Za-z0-9._-]+/[A-Za-z0-9._-]+$~', $repo)) {
            return '';
        }

        return $repo;
    }

    private function sanitizeRefName($ref)
    {
        $ref = trim((string) $ref);
        if ($ref === '') {
            return '';
        }

        return preg_replace('~[^A-Za-z0-9._/-]~', '', $ref);
    }

    private function buildGithubRawUrl($repo, $branch, $fileName)
    {
        $parts = explode('/', $repo, 2);
        if (count($parts) !== 2) {
            return '';
        }

        return sprintf(
            'https://raw.githubusercontent.com/%s/%s/%s/%s',
            rawurlencode($parts[0]),
            rawurlencode($parts[1]),
            rawurlencode($branch),
            rawurlencode($fileName)
        );
    }

    private function renderMarkdownHtml($markdown, $repoUrl = '', $branch = 'main')
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", (string) $markdown);
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown);

        if (class_exists('\\Illuminate\\Support\\Str')) {
            try {
                $html = (string) \Illuminate\Support\Str::markdown($markdown, [
                    'html_input' => 'allow',
                    'allow_unsafe_links' => false,
                    'max_nesting_level' => 20,
                ]);

                if (trim($html) !== '') {
                    return $this->postProcessRenderedMarkdownHtml($html, $repoUrl, $branch);
                }
            } catch (\Throwable $exception) {
            }
        }

        return $this->renderMarkdownHtmlFallback($markdown, $repoUrl, $branch);
    }

    private function renderMarkdownHtmlFallback($markdown, $repoUrl = '', $branch = 'main')
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", (string) $markdown);
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown);

        $codeBlocks = [];
        $markdown = preg_replace_callback('/```([a-zA-Z0-9_-]*)\n(.*?)```/s', function ($matches) use (&$codeBlocks) {
            $language = trim($matches[1]);
            $code = htmlspecialchars(rtrim($matches[2], "\n"), ENT_QUOTES, 'UTF-8');
            $placeholder = '__CODE_BLOCK_' . count($codeBlocks) . '__';
            $codeBlocks[$placeholder] = '<pre><code' . ($language !== '' ? ' class="language-' . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . '"' : '') . '>' . $code . '</code></pre>';
            return "\n" . $placeholder . "\n";
        }, $markdown);

        $lines = explode("\n", $markdown);
        $html = [];
        $paragraph = [];
        $listType = '';
        $listItems = [];
        $indentedCode = [];
        $rawHtmlBlock = [];

        $flushParagraph = function () use (&$paragraph, &$html, $repoUrl, $branch) {
            if ($paragraph === []) {
                return;
            }

            $text = trim(implode(' ', $paragraph));
            if ($text !== '') {
                $html[] = '<p>' . $this->renderMarkdownInline($text, $repoUrl, $branch) . '</p>';
            }
            $paragraph = [];
        };

        $flushList = function () use (&$listType, &$listItems, &$html, $repoUrl, $branch) {
            if ($listType === '' || $listItems === []) {
                return;
            }

            $itemsHtml = [];
            foreach ($listItems as $item) {
                $itemsHtml[] = '<li>' . $this->renderMarkdownInline($item, $repoUrl, $branch) . '</li>';
            }

            $html[] = '<' . $listType . '>' . implode('', $itemsHtml) . '</' . $listType . '>';
            $listType = '';
            $listItems = [];
        };

        $flushIndentedCode = function () use (&$indentedCode, &$html) {
            if ($indentedCode === []) {
                return;
            }

            $code = htmlspecialchars(rtrim(implode("\n", $indentedCode), "\n"), ENT_QUOTES, 'UTF-8');
            $html[] = '<pre><code>' . $code . '</code></pre>';
            $indentedCode = [];
        };

        $flushRawHtmlBlock = function () use (&$rawHtmlBlock, &$html) {
            if ($rawHtmlBlock === []) {
                return;
            }

            $html[] = implode("\n", $rawHtmlBlock);
            $rawHtmlBlock = [];
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($rawHtmlBlock !== []) {
                if ($trimmed === '') {
                    $flushRawHtmlBlock();
                } elseif (preg_match('/^\s*<\/?[a-zA-Z][^>]*>\s*$/', $line) || preg_match('/^\s*<[^>]+>.*$/', $line)) {
                    $rawHtmlBlock[] = ltrim($line);
                    continue;
                } else {
                    $flushRawHtmlBlock();
                }
            }

            if ($indentedCode !== []) {
                if ($trimmed === '') {
                    $indentedCode[] = '';
                    continue;
                }

                if (preg_match('/^(?: {4}|\t)(.*)$/', $line, $matches)) {
                    $indentedCode[] = $matches[1];
                    continue;
                }

                $flushIndentedCode();
            }

            if ($trimmed === '') {
                $flushParagraph();
                $flushList();
                $flushRawHtmlBlock();
                continue;
            }

            if (isset($codeBlocks[$trimmed])) {
                $flushParagraph();
                $flushList();
                $flushIndentedCode();
                $flushRawHtmlBlock();
                $html[] = $codeBlocks[$trimmed];
                continue;
            }

            if (preg_match('/^\s*<\/?[a-zA-Z][^>]*>\s*$/', $line) || preg_match('/^\s*<[^>]+>.*$/', $line)) {
                $flushParagraph();
                $flushList();
                $flushIndentedCode();
                $rawHtmlBlock[] = ltrim($line);
                continue;
            }

            if (preg_match('/^(?: {4}|\t)(.*)$/', $line, $matches)) {
                $flushParagraph();
                $flushList();
                $indentedCode[] = $matches[1];
                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.*)$/', $trimmed, $matches)) {
                $flushParagraph();
                $flushList();
                $flushIndentedCode();
                $flushRawHtmlBlock();
                $level = strlen($matches[1]);
                $html[] = '<h' . $level . '>' . $this->renderMarkdownInline($matches[2], $repoUrl, $branch) . '</h' . $level . '>';
                continue;
            }

            if (preg_match('/^>\s?(.*)$/', $trimmed, $matches)) {
                $flushParagraph();
                $flushList();
                $flushIndentedCode();
                $flushRawHtmlBlock();
                $html[] = '<blockquote><p>' . $this->renderMarkdownInline($matches[1], $repoUrl, $branch) . '</p></blockquote>';
                continue;
            }

            if (preg_match('/^[-*+]\s+(.*)$/', $trimmed, $matches)) {
                $flushParagraph();
                $flushIndentedCode();
                $flushRawHtmlBlock();
                if ($listType !== 'ul') {
                    $flushList();
                    $listType = 'ul';
                }
                $listItems[] = $matches[1];
                continue;
            }

            if (preg_match('/^\d+\.\s+(.*)$/', $trimmed, $matches)) {
                $flushParagraph();
                $flushIndentedCode();
                $flushRawHtmlBlock();
                if ($listType !== 'ol') {
                    $flushList();
                    $listType = 'ol';
                }
                $listItems[] = $matches[1];
                continue;
            }

            $paragraph[] = $trimmed;
        }

        $flushParagraph();
        $flushList();
        $flushIndentedCode();
        $flushRawHtmlBlock();

        return $this->postProcessRenderedMarkdownHtml(implode("\n", $html), $repoUrl, $branch);
    }

    private function postProcessRenderedMarkdownHtml($html, $repoUrl = '', $branch = 'main')
    {
        $html = trim((string) $html);
        if ($html === '' || !class_exists('\\DOMDocument')) {
            return $html;
        }

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        if (!$loaded) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return $html;
        }

        $images = $document->getElementsByTagName('img');
        for ($index = $images->length - 1; $index >= 0; $index--) {
            $image = $images->item($index);
            if ($image instanceof \DOMElement && $image->hasAttribute('src')) {
                $image->setAttribute('src', $this->resolveMarkdownUrl($image->getAttribute('src'), $repoUrl, $branch, true));
            }
        }

        $links = $document->getElementsByTagName('a');
        for ($index = $links->length - 1; $index >= 0; $index--) {
            $link = $links->item($index);
            if (!($link instanceof \DOMElement) || !$link->hasAttribute('href')) {
                continue;
            }

            $resolvedHref = $this->resolveMarkdownUrl($link->getAttribute('href'), $repoUrl, $branch, false);
            $link->setAttribute('href', $resolvedHref);

            if (!preg_match('~^#~', $resolvedHref)) {
                $link->setAttribute('target', '_blank');
                $link->setAttribute('rel', 'noopener');
            }
        }

        $result = $document->saveHTML();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return preg_replace('/^<\\?xml.+?\\?>/i', '', (string) $result);
    }

    private function renderMarkdownInline($text, $repoUrl = '', $branch = 'main')
    {
        $text = htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');

        $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function ($matches) use ($repoUrl, $branch) {
            $alt = htmlspecialchars_decode($matches[1], ENT_QUOTES);
            $url = htmlspecialchars_decode($matches[2], ENT_QUOTES);
            $resolved = $this->resolveMarkdownUrl($url, $repoUrl, $branch, true);
            return '<img src="' . htmlspecialchars($resolved, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '">';
        }, $text);

        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($matches) use ($repoUrl, $branch) {
            $label = htmlspecialchars_decode($matches[1], ENT_QUOTES);
            $url = htmlspecialchars_decode($matches[2], ENT_QUOTES);
            $resolved = $this->resolveMarkdownUrl($url, $repoUrl, $branch, false);
            return '<a href="' . htmlspecialchars($resolved, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
        }, $text);

        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);

        return $text;
    }

    private function resolveMarkdownUrl($url, $repoUrl = '', $branch = 'main', $isImage = false)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '#';
        }

        if (preg_match('~^(https?:)?//|^mailto:|^#~i', $url)) {
            return $url;
        }

        if ($repoUrl === '') {
            return $url;
        }

        $path = ltrim(preg_replace('~^\./~', '', $url), '/');
        if ($path === '') {
            return $repoUrl;
        }

        $base = rtrim($repoUrl, '/');
        if ($isImage) {
            return $base . '/raw/' . rawurlencode($branch) . '/' . str_replace('%2F', '/', rawurlencode($path));
        }

        return $base . '/blob/' . rawurlencode($branch) . '/' . str_replace('%2F', '/', rawurlencode($path));
    }

    private function langValue($key, $fallback = '')
    {
        return isset($this->lang[$key]) && $this->lang[$key] !== '' ? $this->lang[$key] : $fallback;
    }
}
