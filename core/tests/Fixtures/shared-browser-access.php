<?php

/** Exercise the real browser guards with release symlinks, SQLite ACLs and an editor session. */
$tmp = sys_get_temp_dir() . '/evo-shared-browser-' . bin2hex(random_bytes(6));
foreach (['release/assets/local', 'release/assets/cache', 'release/assets/pictures', 'shared/assets/images/private', 'shared/assets/images/public', 'shared/assets/files', 'outside'] as $dir) {
    mkdir($tmp . '/' . $dir, 0777, true);
}
define('EVO_BASE_PATH', $tmp . '/release/');
define('EVO_BASE_URL', '/');
define('EVO_SITE_URL', 'https://example.test/');
define('EVO_MANAGER_PATH', EVO_BASE_PATH . 'manager/');
define('EVO_CORE_PATH', EVO_BASE_PATH . 'core/');
define('IN_MANAGER_MODE', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', false);

/**
 * Minimal CMS host for the browser; file-group decisions use the real implementation.
 *
 * @since 3.6.0
 */
final class SharedBrowserStub
{
    public array $configGlobal = [];
    public array $config = [];
    public array $log = [];

    /**
     * Return only the settings needed by the isolated ACL checks.
     *
     * @since 3.6.0
     */
    public function getConfig($key, $default = null)
    {
        return $this->config[$key] ?? ['filemanager_path' => EVO_BASE_PATH, 'use_udperms' => 1][$key] ?? $default;
    }

    /**
     * Keep the dashboard checksum check outside this filesystem regression fixture.
     *
     * @since 3.6.0
     */
    public function getManagerApi(): self
    {
        return $this;
    }

    /**
     * The isolated site has no tracked CMS checksum changes.
     *
     * @since 3.6.0
     */
    public function checkSystemChecksum(): string
    {
        return '0';
    }

    /**
     * Capture refusals for assertions without writing a CMS event log.
     *
     * @since 3.6.0
     */
    public function logEvent($id, $type, $message, $source): void
    {
        $this->log[] = $message;
    }
}

$GLOBALS['evo'] = new SharedBrowserStub();
/**
 * Supply the fixture CMS host to the unmodified browser helpers.
 *
 * @since 3.6.0
 */
function evo() { return $GLOBALS['evo']; }
/**
 * Supply the legacy spelling used by the refusal logger.
 *
 * @since 3.6.0
 */
function evolutionCMS() { return evo(); }

require dirname(__DIR__, 2) . '/vendor/autoload.php';
$browserDir = dirname(__DIR__, 3) . '/manager/media/browser/mcpuk/';
require $browserDir . 'lib/helper_path.php';
require $browserDir . 'lib/helper_dir.php';
require $browserDir . 'core/uploader.php';
require $browserDir . 'core/browser.php';

try {
    if (!@symlink($tmp . '/shared/assets/images', EVO_BASE_PATH . 'assets/images')
        || !@symlink($tmp . '/shared/assets/files', EVO_BASE_PATH . 'assets/files')) {
        echo json_encode(['skip' => 'symlinks are not available']);
        return;
    }
    file_put_contents($tmp . '/shared/assets/images/private/secret.txt', 'secret');
    file_put_contents($tmp . '/shared/assets/images/public/open.txt', 'open');
    symlink($tmp . '/outside', $tmp . '/shared/assets/images/escape');
    symlink($tmp . '/shared/assets/images/private', $tmp . '/shared/assets/images/internal');
    symlink($tmp . '/outside/missing', $tmp . '/shared/assets/images/dangling');
    mkdir($tmp . '/shared/assets/.thumbs/images', 0777, true);
    symlink($tmp . '/outside', $tmp . '/shared/assets/.thumbs-link');

    $capsule = new Illuminate\Database\Capsule\Manager();
    $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    $capsule->getConnection()->statement('CREATE TABLE file_groups (id INTEGER PRIMARY KEY, document_group INTEGER, file TEXT)');
    $capsule->getConnection()->table('file_groups')->insert([
        'id' => 1, 'document_group' => 7, 'file' => 'assets/images/private',
    ]);

    $browser = (new ReflectionClass(browser::class))->newInstanceWithoutConstructor();
    $typeDir = $tmp . '/shared/assets/images';
    // typeURL as KCFinder derives a relative rb_base_url (against the browser script): never the folder
    foreach (['modx' => evo(), 'typeDir' => $typeDir, 'typeURL' => '/manager/media/browser/mcpuk/assets/images',
        'type' => 'images', 'config' => ['uploadDir' => $tmp . '/shared/assets', '_siteUploadURL' => 'assets']] as $name => $value) {
        (new ReflectionProperty(uploader::class, $name))->setValue($browser, $value);
    }
    (new ReflectionProperty(browser::class, 'protectedPaths'))->setValue($browser, []);
    $invoke = static fn ($name, ...$args) => (new ReflectionMethod(browser::class, $name))->invoke($browser, ...$args);
    $_SESSION = ['mgrRole' => 2, 'mgrDocgroups' => []];
    $secret = $typeDir . '/private/secret.txt';
    $open = $typeDir . '/public/open.txt';
    $output = [
        'key' => $invoke('getFileGroupsRelPath', $secret),
        'newKey' => $invoke('getFileGroupsRelPath', $typeDir . '/private/new.txt'),
        'dottedKey' => $invoke('getFileGroupsRelPath', $typeDir . '/./private/secret.txt'),
        'deniedRead' => $invoke('isPathAccessible', $secret),
        'deniedWrite' => $invoke('isWriteAllowed', 'private'),
        'publicRead' => $invoke('isPathAccessible', $open),
        'publicWrite' => $invoke('isWriteAllowed', 'public'),
        'listing' => array_map('basename', $invoke('filterAccessiblePaths', [$secret, $open])),
        'subtreeSecret' => $invoke('subtreeAccessFilter', $typeDir)($secret),
        'protectedDescendants' => $invoke('hasInaccessibleDescendants', $typeDir),
        'escape' => $invoke('isPathAccessible', $typeDir . '/escape'),
        'internal' => $invoke('isPathAccessible', $typeDir . '/internal/secret.txt'),
        'visibleLink' => $invoke('isLinkVisible', $typeDir . '/escape'),
        'dangling' => $invoke('isWriteAllowed', 'dangling'),
        'traversal' => $invoke('isPathAccessible', $typeDir . '/../files'),
        'safeThumbs' => !fileManagerPathContainsLink($tmp . '/shared/assets', $tmp . '/shared/assets/.thumbs/images'),
        'linkedThumbs' => !fileManagerPathContainsLink($tmp . '/shared/assets', $tmp . '/shared/assets/.thumbs-link/images'),
        'diagnostic' => EvolutionCMS\Support\FileBrowserAccess::isUsableRoot(
            $tmp . '/shared/assets', 'assets/', EVO_BASE_PATH, EVO_BASE_PATH, '/', EVO_SITE_URL
        ),
        'missingDiagnostic' => EvolutionCMS\Support\FileBrowserAccess::isUsableRoot(
            $tmp . '/missing', 'assets/', EVO_BASE_PATH, EVO_BASE_PATH, '/', EVO_SITE_URL
        ),
        'unmappedDiagnostic' => EvolutionCMS\Support\FileBrowserAccess::isUsableRoot(
            $tmp . '/shared/assets', 'unmapped/', EVO_BASE_PATH, EVO_BASE_PATH, '/', EVO_SITE_URL
        ),
        'standardDiagnostic' => EvolutionCMS\Support\FileBrowserAccess::isUsableRoot(
            '[(base_path)]assets/', 'assets/', '[(base_path)]', EVO_BASE_PATH, '/', EVO_SITE_URL
        ),
        'standardKey' => EvolutionCMS\Support\FileBrowserAccess::resolveAclPath(
            EVO_BASE_PATH, EVO_BASE_PATH . 'assets/local', EVO_BASE_PATH . 'assets/local/new.txt'
        ),
        'wrongTargetKey' => EvolutionCMS\Support\FileBrowserAccess::resolveAclPath(
            EVO_BASE_PATH, $typeDir, $open, EVO_BASE_PATH . 'assets/local'
        ),
        'outsideSpellingKey' => EvolutionCMS\Support\FileBrowserAccess::resolveAclPath(
            EVO_BASE_PATH, $typeDir, $open, $typeDir
        ),
        'personalKey' => EvolutionCMS\Support\FileBrowserAccess::resolveAclPath(
            EVO_BASE_PATH, $typeDir . '/private', $secret, EVO_BASE_PATH . 'assets/images/private'
        ),
    ];
    $_SESSION['mgrDocgroups'] = [7];
    $output['memberRead'] = $invoke('isPathAccessible', $secret);
    $output['memberWrite'] = $invoke('isWriteAllowed', 'private');
    $output['memberSubtree'] = $invoke('subtreeAccessFilter', $typeDir)($secret);
    $output['memberDescendants'] = $invoke('hasInaccessibleDescendants', $typeDir);

    $setConfig = static fn (array $config) => (new ReflectionProperty(uploader::class, 'config'))->setValue($browser, $config);
    $setConfig(['uploadDir' => $tmp . '/shared/assets', '_siteUploadURL' => 'unmapped']);
    $output['unmappedKey'] = $invoke('getFileGroupsRelPath', $open);
    $output['unmappedRead'] = $invoke('isPathAccessible', $open);
    $output['unmappedWrite'] = $invoke('isWriteAllowed', 'public');
    $output['unmappedListing'] = $invoke('filterAccessiblePaths', [$open]);
    $output['unmappedSubtree'] = $invoke('subtreeAccessFilter', $typeDir)($open);
    $output['unmappedDescendants'] = $invoke('hasInaccessibleDescendants', $typeDir);
    $output['unmappedLink'] = $invoke('isLinkVisible', $typeDir . '/escape');
    $output['log'] = evo()->log;

    // A narrowed site-wide filemanager_path: rows can only name what lies below it, so a type root
    // the site serves from outside it carries no groups, linked to shared storage or not.
    evo()->configGlobal['filemanager_path'] = EVO_BASE_PATH . 'assets/local';
    $setConfig(['uploadDir' => $tmp . '/shared/assets', '_siteUploadURL' => 'assets']);
    $output['narrowedSharedKey'] = $invoke('getFileGroupsRelPath', $secret);
    $output['narrowedSharedRead'] = $invoke('isPathAccessible', $secret);
    $pictures = EVO_BASE_PATH . 'assets/pictures';
    file_put_contents($pictures . '/cat.jpg', 'cat');
    foreach (['typeDir' => $pictures, 'type' => 'pictures'] as $name => $value) {
        (new ReflectionProperty(uploader::class, $name))->setValue($browser, $value);
    }
    $setConfig(['uploadDir' => EVO_BASE_PATH . 'assets', '_siteUploadURL' => 'assets']);
    $output['narrowedPlainKey'] = $invoke('getFileGroupsRelPath', $pictures . '/cat.jpg');
    $output['narrowedPlainRead'] = $invoke('isPathAccessible', $pictures . '/cat.jpg');
    $output['narrowedPlainWrite'] = $invoke('isWriteAllowed', '');
    $output['narrowedPlainListing'] = array_map('basename', $invoke('filterAccessiblePaths', [$pictures . '/cat.jpg']));
    $setConfig(['uploadDir' => EVO_BASE_PATH . 'assets', '_siteUploadURL' => 'elsewhere']);
    $output['narrowedUnprovenKey'] = $invoke('getFileGroupsRelPath', $pictures . '/cat.jpg');
    $output['narrowedDiagnostic'] = EvolutionCMS\Support\FileBrowserAccess::isUsableRoot(
        EVO_BASE_PATH . 'assets', 'assets/', EVO_BASE_PATH . 'assets/local', EVO_BASE_PATH, '/', EVO_SITE_URL
    );
    unset(evo()->configGlobal['filemanager_path']);
    foreach (['typeDir' => $typeDir, 'type' => 'images'] as $name => $value) {
        (new ReflectionProperty(uploader::class, $name))->setValue($browser, $value);
    }
    $_SESSION['mgrRole'] = 1;
    $output['adminRead'] = $invoke('isPathAccessible', $open);
    $output['adminEscape'] = $invoke('isPathAccessible', $typeDir . '/escape');
    // Another release must retain the same stored key, without moving file_groups rows.
    mkdir($tmp . '/next-release/assets', 0777, true);
    symlink($typeDir, $tmp . '/next-release/assets/images');
    $output['nextReleaseKey'] = EvolutionCMS\Support\FileBrowserAccess::resolveAclPath(
        $tmp . '/next-release', $typeDir, $secret, $tmp . '/next-release/assets/images'
    );

    // Execute the actual dashboard include, including its independent file-manager check.
    $capsule->getConnection()->statement('CREATE TABLE site_content (id INTEGER, published INTEGER, privateweb INTEGER, deleted INTEGER)');
    require dirname(__DIR__, 2) . '/lang/en/global.php';
    foreach ([
        'shared' => [$tmp . '/shared/assets/', 'assets/', '[(base_path)]'],
        'portable' => ['[(base_path)]assets/', 'assets/', '[(base_path)]'],
        'missing' => [$tmp . '/missing/', 'assets/', '[(base_path)]'],
        'unmapped' => [$tmp . '/shared/assets/', 'unmapped/', '[(base_path)]'],
        'missingManager' => ['[(base_path)]assets/', 'assets/', '[(base_path)]missing/'],
        'outsideManager' => [$tmp . '/shared/assets/', 'assets/', $tmp . '/shared/'],
        'traversalManager' => ['[(base_path)]assets/', 'assets/', '[(base_path)]../shared/'],
    ] as $case => [$upload, $url, $managerRoot]) {
        evo()->config = ['rb_base_dir' => $upload, 'rb_base_url' => $url, 'filemanager_path' => $managerRoot];
        include dirname(__DIR__, 3) . '/manager/includes/config_check.inc.php';
        $messages = array_column($warnings, 0);
        $output['dashboard'][$case] = [
            'browserWarning' => in_array($_lang['configcheck_rb_base_dir'], $messages, true),
            'managerWarning' => in_array($_lang['configcheck_filemanager_path'], $messages, true),
        ];
    }
    echo json_encode($output);
} finally {
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) {
        if ($item->isLink() || !$item->isDir()) {
            unlink($item->getPathname());
        } else {
            rmdir($item->getPathname());
        }
    }
    rmdir($tmp);
}
