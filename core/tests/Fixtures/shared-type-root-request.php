<?php

/**
 * The media browser and the classic file manager on a site with links an operator made, built the
 * way a web request builds them: $_SERVER, the working directory of browse.php, the real browser
 * constructor and config.php. The hosting chain public_html -> evolution -> evo-3.6.2 sits above
 * the site; assets/images links to ../../shared/assets/images; assets/plugins/myplugin links (by
 * an absolute path) to a checkout elsewhere, whose images are linked into the shared image folder.
 *
 * argv: base64 JSON {base: canonical|linked, upload: site|shared}
 * Prints JSON with both managers' answers.
 */

$options = json_decode(base64_decode($argv[1] ?? ''), true) ?: [];
$home = sys_get_temp_dir() . '/evo-shared-root-' . bin2hex(random_bytes(6));
$site = $home . '/evo-3.6.2';
$shared = $home . '/shared/assets/images';
$repo = $home . '/repos/myplugin';
foreach ([$site . '/assets/files/team', $site . '/assets/plugins', $shared . '/private', $shared . '/public',
             $repo . '/images', $home . '/outside'] as $dir) {
    mkdir($dir, 0777, true);
}
file_put_contents($shared . '/private/secret.png', 'secret');
file_put_contents($shared . '/public/open.png', 'open');
file_put_contents($repo . '/images/logo.png', 'logo');
file_put_contents($repo . '/plugin.php', '<?php');
file_put_contents($home . '/outside/loot.txt', 'loot');

if (!@symlink('../../shared/assets/images', $site . '/assets/images')
    || !@symlink('evo-3.6.2', $home . '/evolution')
    || !@symlink('evolution', $home . '/public_html')) {
    echo json_encode(['skip' => 'symlinks are not available']);
    return;
}
symlink($repo, $site . '/assets/plugins/myplugin');                  // absolute: a plugin checked out elsewhere
symlink('../../../repos/myplugin/images', $shared . '/plugin-images'); // relative: its images, for the browser
symlink($home . '/outside', $shared . '/outside');                   // somewhere new: passed
symlink($shared . '/private', $shared . '/internal');                // a second name for private: not passed
symlink($site . '/assets/files/team', $shared . '/team');            // a second name for a site folder: not passed
symlink('..', $shared . '/public/loop');                             // a loop: not passed
symlink($home . '/missing', $shared . '/dangling');                  // leads nowhere: not passed

$basePath = ($options['base'] ?? 'canonical') === 'linked' ? $home . '/public_html/' : $site . '/';
$uploadDir = ($options['upload'] ?? 'site') === 'shared' ? $home . '/shared/assets/' : '[(base_path)]assets/';
$mcpuk = dirname(__DIR__, 3) . '/manager/media/browser/mcpuk';

define('EVO_BASE_PATH', $basePath);
define('EVO_BASE_URL', '/');
define('EVO_SITE_URL', 'https://example.test/');
define('EVO_MANAGER_PATH', $basePath . 'manager/');
define('EVO_CORE_PATH', $basePath . 'core/');
define('IN_MANAGER_MODE', true);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', false);

/**
 * The CMS object the browser is constructed with: settings of the site above, nothing else.
 */
class DocumentParser
{
    public array $configGlobal = [];
    public array $config;
    public array $log = [];
    public array $denied = [];

    public function __construct(string $uploadDir)
    {
        $this->config = [
            'filemanager_path' => '[(base_path)]',
            'rb_base_dir' => $uploadDir,
            'rb_base_url' => 'assets/',
            'use_udperms' => 1,
            'upload_images' => 'png,jpg',
            'upload_files' => 'txt,pdf',
            'upload_media' => 'mp4',
            'upload_maxsize' => 1048576,
            'new_folder_permissions' => '0777',
            'new_file_permissions' => '0666',
            'thumbsDir' => '.thumbs',
            'thumbWidth' => 100,
            'thumbHeight' => 100,
            'jpegQuality' => 90,
        ];
    }

    public function getConfig($key, $default = null)
    {
        $value = $this->config[$key] ?? $default;

        return is_string($value) ? str_replace('[(base_path)]', EVO_BASE_PATH, $value) : $value;
    }

    public function hasPermission($permission)
    {
        return !in_array($permission, $this->denied, true);
    }

    public function invokeEvent(...$args)
    {
        return [];
    }

    public function logEvent($id, $type, $message, $source)
    {
        $this->log[] = strip_tags($message);
    }

    public function toDateFormat($timestamp, $mode = '')
    {
        return date('Y-m-d H:i', (int) $timestamp);
    }
}

/** Lexicon and style entries ls() prints; any key reads as its own name. */
final class FixtureLexicon implements ArrayAccess
{
    public function offsetExists($offset): bool
    {
        return true;
    }

    public function offsetGet($offset): mixed
    {
        return (string) $offset;
    }

    public function offsetSet($offset, $value): void
    {
    }

    public function offsetUnset($offset): void
    {
    }
}

final class ManagerTheme
{
    public static function getLexicon()
    {
        return new FixtureLexicon();
    }

    public static function getStyle()
    {
        return new FixtureLexicon();
    }
}

$GLOBALS['evo'] = new DocumentParser($uploadDir);
function evo()
{
    return $GLOBALS['evo'];
}
function evolutionCMS()
{
    return evo();
}

require dirname(__DIR__, 2) . '/vendor/autoload.php';
spl_autoload_register(static function ($class) use ($mcpuk) {
    $map = [
        'browser' => 'core/browser.php', 'uploader' => 'core/uploader.php', 'dir' => 'lib/helper_dir.php',
        'file' => 'lib/helper_file.php', 'path' => 'lib/helper_path.php', 'text' => 'lib/helper_text.php',
        'input' => 'lib/class_input.php', 'image' => 'lib/class_image.php', 'image_gd' => 'lib/class_image_gd.php',
        'image_imagick' => 'lib/class_image_imagick.php', 'image_gmagick' => 'lib/class_image_gmagick.php',
        'gd' => 'lib/class_gd.php', 'type_img' => 'core/types/type_img.php', 'type_mime' => 'core/types/type_mime.php',
    ];
    if (isset($map[$class])) {
        require $mcpuk . '/' . $map[$class];
    }
});

$capsule = new Illuminate\Database\Capsule\Manager();
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
$capsule->setAsGlobal();
$capsule->bootEloquent();
$capsule->getConnection()->statement('CREATE TABLE file_groups (id INTEGER PRIMARY KEY, document_group INTEGER, file TEXT)');
$capsule->getConnection()->table('file_groups')->insert([
    ['id' => 1, 'document_group' => 7, 'file' => 'assets/images/private'],
    ['id' => 2, 'document_group' => 7, 'file' => 'assets/files/team'],
]);

try {
    // what Apache hands browse.php on such hosting: the document root by its public spelling,
    // the working directory by its real one
    $_SERVER = array_merge($_SERVER, [
        'DOCUMENT_ROOT' => $home . '/public_html',
        'SCRIPT_NAME' => '/manager/media/browser/mcpuk/browse.php',
        'PHP_SELF' => '/manager/media/browser/mcpuk/browse.php',
        'SCRIPT_FILENAME' => $mcpuk . '/browse.php',
        'HTTP_HOST' => 'example.test',
        'REQUEST_METHOD' => 'GET',
    ]);
    $_GET = ['type' => 'images'];
    $_POST = [];
    $_COOKIE = [];
    $_SESSION = ['mgrRole' => 2, 'mgrDocgroups' => []];
    chdir($mcpuk);
    $browser = new browser(evo());
    $invoke = static fn ($name, ...$args) => (new ReflectionMethod(browser::class, $name))->invoke($browser, ...$args);
    $typeDir = $browser->typeDir;
    $secret = $typeDir . '/private/secret.png';
    $open = $typeDir . '/public/open.png';
    $pluginImage = $typeDir . '/plugin-images/logo.png';

    $output = [
        'typeURL' => $browser->typeURL,
        'browserKey' => $invoke('getFileGroupsRelPath', $secret),
        'browserOutsiderReadsSecret' => $invoke('isPathAccessible', $secret),
        'browserListing' => array_map('basename', $invoke('filterAccessiblePaths', array_map(
            static fn ($name) => $typeDir . '/' . $name,
            ['private', 'public', 'plugin-images', 'outside', 'internal', 'team', 'dangling']
        ))),
        'browserPluginImage' => $invoke('isPathAccessible', $pluginImage),
        'browserPluginImageKey' => $invoke('getFileGroupsRelPath', $pluginImage),
        'browserPluginUpload' => $invoke('isWriteAllowed', 'plugin-images'),
        'browserSecondName' => $invoke('isPathAccessible', $typeDir . '/internal/secret.png'),
        'browserSiteName' => $invoke('isPathAccessible', $typeDir . '/team'),
        'browserLoop' => $invoke('isPathAccessible', $typeDir . '/public/loop/private/secret.png'),
        'browserDangling' => $invoke('isPathAccessible', $typeDir . '/dangling'),
        'browserClimb' => $invoke('isPathAccessible', $typeDir . '/outside/../private/secret.png'),
        'browserInertLinks' => array_column($invoke('getLinks', 'images'), 'name'),
    ];

    // the classic file manager, as files.dynamic.php sets it up
    $root = rtrim(str_replace('\\', '/', fileManagerCanonicalPath(evo()->getConfig('filemanager_path')) ?: realpath(EVO_BASE_PATH)), '/');
    $relative = static fn ($request) => fileManagerResolvePath($root, $request)['relative'] ?? null;
    $resolved = fileManagerResolvePath($root, 'assets/images/private/secret.png');
    $output += [
        'sharedRoots' => array_map(
            static fn ($link) => substr($link, strlen($root) + 1),
            array_keys(fileManagerSharedTypeRoots())
        ),
        'classicKey' => $resolved === null ? null : fileManagerAclKey($resolved['relative']),
        'classicOutsiderReadsSecret' => $resolved !== null && fileManagerIsAccessible($resolved['relative'], []),
        'classicMemberReadsSecret' => $resolved !== null && fileManagerIsAccessible($resolved['relative'], [7]),
        'classicPluginImage' => $relative('assets/images/plugin-images/logo.png'),
        'classicPluginFolder' => $relative('assets/plugins/myplugin/images/logo.png'),
        'classicOutside' => $relative('assets/images/outside/loot.txt'),
        'classicSecondName' => $relative('assets/images/internal/secret.png'),
        'classicSiteName' => $relative('assets/images/team'),
        'classicLoop' => $relative('assets/images/public/loop/private'),
        'classicDangling' => $relative('assets/images/dangling'),
        'classicClimb' => $relative('assets/images/../files'),
        'classicClimbOutOfPlugin' => $relative('assets/plugins/myplugin/../../files'),
        'writeInside' => fileManagerIsNewWriteTarget($root, $root . '/assets/images/public/new.png'),
        'writeIntoPlugin' => fileManagerIsNewWriteTarget($root, $root . '/assets/plugins/myplugin/images/new.png'),
        'writeOverLink' => fileManagerIsSafeWriteTarget($root, $root . '/assets/images'),
        'writeThroughSecondName' => fileManagerIsSafeWriteTarget($root, $root . '/assets/images/internal/new.png'),
        'isSharedRoot' => fileManagerSharedTypeRootAt($root . '/assets/images') !== null,
        'isSharedRootPlugin' => fileManagerSharedTypeRootAt($root . '/assets/plugins/myplugin') !== null,
        'pluginCode' => fileManagerCodeEntry($root . '/assets/plugins/myplugin'),
        'pluginCodeFromAssets' => fileManagerCodeEntry($root . '/assets/plugins'),
    ];
    evo()->denied = ['save_plugin', 'save_snippet', 'save_module'];
    $output['pluginProtected'] = fileManagerPathIsProtected($root . '/assets/plugins/myplugin/images', fileManagerProtectedPaths());
    evo()->denied = [];

    // the listing of assets/images: linked folders are folders, the rest stay inert links
    $listingOf = static function ($path) use ($root) {
        ob_start();
        ls($root . '/' . $path, [
            'len' => 0, 'editablefiles' => [], 'enablefileunzip' => false, 'inlineviewablefiles' => [],
            'uploadablefiles' => [], 'enablefiledownload' => true, 'viewablefiles' => [], 'protected_path' => [],
            'excludes' => [], 'filemanager_path' => $root, 'base_path' => rtrim(str_replace('\\', '/', realpath(EVO_BASE_PATH)), '/'),
            'showFileGroups' => true, 'fileGroupsMap' => [], 'allDocGroups' => [], 'userGroups' => [],
        ]);

        return ob_get_clean();
    };
    $assets = $listingOf('assets');
    $images = $listingOf('assets/images');
    $output += [
        'imagesListedAsFolder' => strpos($assets, 'mode=drill&path=assets%2Fimages"') !== false,
        'imagesOffersDelete' => strpos($assets, 'deleteFolder(\'images\'') !== false,
        'imagesOffersRename' => strpos($assets, 'renameFolder(\'images\')') !== false,
        'pluginImagesListedAsFolder' => strpos($images, 'mode=drill&path=assets%2Fimages%2Fplugin-images"') !== false,
        'pluginImagesOffersDelete' => strpos($images, 'deleteFolder(\'plugin-images\'') !== false,
        'inertListedAsLinks' => array_values(array_filter(
            ['internal', 'team', 'dangling', 'outside'],
            static fn ($name) => strpos($images, 'deleteLink(\'' . $name . '\')') !== false
        )),
    ];

    // deleting a linked folder removes the link, never what it leads to
    $output['removedLink'] = rrmdir($root . '/assets/plugins/myplugin');
    $output['linkGone'] = !file_exists($site . '/assets/plugins/myplugin') && !is_link($site . '/assets/plugins/myplugin');
    $output['checkoutKept'] = is_file($repo . '/plugin.php') && is_file($repo . '/images/logo.png');
    $output['log'] = evo()->log;
    echo json_encode($output, JSON_UNESCAPED_SLASHES);
} finally {
    chdir(__DIR__);
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($home, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) {
        if ($item->isLink() || !$item->isDir()) {
            unlink($item->getPathname());
        } else {
            rmdir($item->getPathname());
        }
    }
    rmdir($home);
}
