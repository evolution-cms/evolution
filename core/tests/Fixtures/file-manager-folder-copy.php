<?php

/**
 * Runs a folder copy or a directory archive of the classic file manager in a clean process, as a
 * manager in the given document groups (role 1 for an administrator), against a throwaway site
 * tree and a SQLite file_groups table.
 *
 * argv: site root, base64 JSON {db, groups, role, permissions, uploadFiles, action, source, target, zip}
 * Prints JSON: {result, copied, skipped, rows, log, zipEntries}
 */

[, $site, $payload] = $argv;
$options = json_decode(base64_decode($payload), true);

define('EVO_BASE_PATH', $site . '/');
define('EVO_MANAGER_PATH', $site . '/manager/');
define('EVO_CORE_PATH', $site . '/core/');
define('EVO_SITE_URL', 'http://example.test/');
define('IN_MANAGER_MODE', false);
define('IN_INSTALL_MODE', false);
define('EVO_API_MODE', false);

final class FolderCopyStubEvo
{
    public array $configGlobal = [];
    public array $log = [];

    public function __construct(private array $options)
    {
    }

    public function getConfig($key, $default = null)
    {
        return [
            'filemanager_path' => EVO_BASE_PATH,
            'use_udperms' => 1,
            'upload_files' => $this->options['uploadFiles'] ?? 'txt,zip',
            'upload_images' => 'png',
            'upload_media' => '',
        ][$key] ?? $default;
    }

    public function hasPermission($permission)
    {
        return in_array($permission, array_merge(['file_manager'], $this->options['permissions'] ?? []), true);
    }

    public function invokeEvent(...$args)
    {
        return [];
    }

    public function logEvent($id, $type, $message, $source)
    {
        $this->log[] = $message;
    }
}

$GLOBALS['evo'] = new FolderCopyStubEvo($options);
function evo()
{
    return $GLOBALS['evo'];
}
function evolutionCMS()
{
    return $GLOBALS['evo'];
}
function logFileChange($type, $filename)
{
}

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$_SESSION = ['mgrRole' => $options['role'] ?? 2, 'mgrDocgroups' => $options['groups'] ?? []];
$_REQUEST = ['mode' => $options['action']];

$capsule = new Illuminate\Database\Capsule\Manager();
$capsule->addConnection(['driver' => 'sqlite', 'database' => $options['db'], 'prefix' => '']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$root = rtrim(str_replace('\\', '/', realpath($site)), '/');
$protected = fileManagerProtectedPaths();
$output = ['copied' => [], 'skipped' => [], 'zipEntries' => [], 'zipLinks' => []];

if ($options['action'] === 'copyFolder') {
    $source = $root . '/' . $options['source'];
    $target = $root . '/' . $options['target'];
    $sourceKey = fileManagerAclKey($options['source']);
    $targetKey = fileManagerAclKey($options['target']);
    $carried = EvolutionCMS\Support\FileManagerAccess::carriedRestrictions($sourceKey, $targetKey);
    // the same guard files.dynamic.php hands over
    $mayRead = fileManagerCopyFolderAccessFilter($root, $source);
    $mayCopy = static fn ($path, $isDir) => $mayRead($path) && ($isDir || checkExtension($path));
    $copied = [];
    $skipped = [];
    $output['result'] = $carried !== null
        && fileManagerCopyFolder($root, $source, $target, $protected, $mayCopy, 0777, null, $copied, $skipped);
    if ($output['result']) {
        EvolutionCMS\Support\FileManagerAccess::copyFolderRestrictions($sourceKey, $targetKey, $carried, $copied);
    }
    sort($copied);
    ksort($skipped);
    $output['copied'] = $copied;
    $output['skipped'] = $skipped;
} else {
    $relative = $options['source'];
    $zip = $options['zip'];
    $output['result'] = fileManagerCreateDirectoryZip(
        $root . '/' . $relative,
        $relative,
        $root,
        $zip,
        fileManagerUserGroupIds(),
        fileManagerSubtreeRestrictionMap($relative),
        $protected
    );
    $archive = new ZipArchive();
    if ($output['result'] && $archive->open($zip) === true) {
        for ($i = 0; $i < $archive->numFiles; $i++) {
            $output['zipEntries'][] = rtrim($archive->getNameIndex($i), '/');
            if (fileManagerZipEntryIsLink($archive, $i)) {
                $output['zipLinks'][$archive->getNameIndex($i)] = $archive->getFromIndex($i);
            }
        }
        $archive->close();
        sort($output['zipEntries']);
    }
}

$output['rows'] = Illuminate\Database\Capsule\Manager::table('file_groups')
    ->orderBy('file')->orderBy('document_group')->get()
    ->map(static fn ($row) => $row->file . ':' . $row->document_group)->sort()->values()->all();
$output['log'] = $GLOBALS['evo']->log;
$capsule->getConnection()->disconnect();

echo json_encode($output);
