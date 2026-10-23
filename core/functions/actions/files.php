<?php
use EvolutionCMS\Support\FileManagerAccess;

if(!function_exists('add_dot')) {
    /**
     * @param array $array
     * @return array
     */
    function add_dot($array)
    {
        $count = count($array);
        for ($i = 0; $i < $count; $i++) {
            $array[$i] = '.' . strtolower(trim($array[$i])); // add a dot :)
        }

        return $array;
    }
}

if(!function_exists('fileManagerUserGroupIds')) {
    /**
     * @return int[]
     */
    function fileManagerUserGroupIds()
    {
        if (!evolutionCMS()->getConfig('use_udperms')) {
            return [];
        }

        if (isset($_SESSION['mgrRole']) && (int)$_SESSION['mgrRole'] === 1) {
            return [];
        }

        return array_values(array_unique(array_map('intval', (array)($_SESSION['mgrDocgroups'] ?? []))));
    }
}

if(!function_exists('fileManagerLogDenied')) {
    /**
     * Writes a refused file manager request to the event log, where administrators read it. A
     * warning that names the action, the path asked for and where it came from; the manager who
     * did it is the event's user. Never throws: logging must not change what the request does.
     *
     * @param string|null $what what was attempted; the request's mode when omitted
     * @param string|null $path what it was attempted on; taken from the request when omitted
     * @param string $reason which rule refused it, when the caller knows
     */
    function fileManagerLogDenied($what = null, $path = null, $reason = '')
    {
        static $logged = [];

        try {
            if ($what === null) {
                $mode = $_REQUEST['mode'] ?? '';
                $what = is_string($mode) && $mode !== '' ? $mode : 'access';
            }
            if ($path === null) {
                $parts = [];
                foreach (['folderpath', 'path', 'file', 'dirname'] as $field) {
                    if (isset($_REQUEST[$field]) && is_string($_REQUEST[$field]) && $_REQUEST[$field] !== '') {
                        $parts[] = trim($_REQUEST[$field], '/');
                    }
                }
                $path = implode('/', $parts);
            }

            $key = $what . '|' . $path . '|' . $reason;
            if (isset($logged[$key]) || count($logged) >= 20) {
                return; // one line per refusal, and a request cannot flood the log
            }
            $logged[$key] = true;

            $clean = static function ($value) {
                $value = preg_replace('/[\x00-\x1F\x7F]/', ' ', (string) $value);

                return htmlspecialchars(substr($value, 0, 200), ENT_QUOTES, 'UTF-8');
            };
            $message = 'File manager access denied: ' . $clean($what) . ' ' . $clean($path)
                . ($reason !== '' ? ' (' . $clean($reason) . ')' : '')
                . ' - from ' . $clean($_SERVER['REMOTE_ADDR'] ?? '');

            evolutionCMS()->logEvent(0, 2, $message, 'File manager');
        } catch (\Throwable $exception) {
            // the request is refused either way
        }
    }
}

if(!function_exists('fileManagerDenied')) {
    /**
     * Logs the refusal and returns the message shown for it.
     *
     * @param string $reason which rule refused it, when the caller knows
     * @return string
     */
    function fileManagerDenied($reason = '', $path = null)
    {
        global $_lang;

        fileManagerLogDenied(null, $path, $reason);

        return '<span class="warning"><b>' . $_lang['files_access_denied'] . '</b></span><br /><br />';
    }
}

if(!function_exists('fileManagerAclKey')) {
    /**
     * The file_groups key of a path given relative to the current manager's file manager root.
     * Groups are stored against the site-wide root, so a manager with their own
     * filemanager_path still hits the rows everyone else's paths hit.
     *
     * @param string $relativePath
     * @return string|null null when the path lies outside the ACL root
     */
    function fileManagerAclKey($relativePath)
    {
        static $roots = null;
        if ($roots === null) {
            $userRoot = realpath(evolutionCMS()->getConfig('filemanager_path')) ?: realpath(EVO_BASE_PATH);
            $roots = [FileManagerAccess::aclRoot(), rtrim(str_replace('\\', '/', $userRoot), '/')];
        }

        return FileManagerAccess::aclKey($roots[0], $roots[1], $relativePath);
    }
}

if(!function_exists('fileManagerAclApplies')) {
    /**
     * @return bool false for administrators and when document permissions are off
     */
    function fileManagerAclApplies()
    {
        return evolutionCMS()->getConfig('use_udperms')
            && !(isset($_SESSION['mgrRole']) && (int)$_SESSION['mgrRole'] === 1);
    }
}

if(!function_exists('fileManagerRestrictionMap')) {
    /**
     * @param string[] $relativePaths relative to the manager's root
     * @return array<string, int[]> keyed by ACL key
     */
    function fileManagerRestrictionMap(array $relativePaths)
    {
        if (!fileManagerAclApplies()) {
            return [];
        }

        $keys = array_filter(array_map('fileManagerAclKey', $relativePaths), static fn ($key) => $key !== null);

        return FileManagerAccess::loadRestrictions(array_values($keys));
    }
}

if(!function_exists('fileManagerIsAccessible')) {
    /**
     * @param string $relativePath relative to the manager's root
     * @param int[]|null $userGroups
     * @param array<string, int[]>|null $restrictionMap from fileManagerRestrictionMap()
     * @return bool
     */
    function fileManagerIsAccessible($relativePath, ?array $userGroups = null, ?array $restrictionMap = null)
    {
        if (!fileManagerAclApplies()) {
            return true;
        }

        $key = fileManagerAclKey($relativePath);
        if ($key === null) {
            return true;
        }

        return FileManagerAccess::isAccessible(
            $key,
            $userGroups ?? fileManagerUserGroupIds(),
            $restrictionMap ?? fileManagerRestrictionMap([$relativePath])
        );
    }
}

if(!function_exists('fileManagerCanModifyExistingPath')) {
    /**
     * @param string $relativePath relative to the manager's root
     * @param int[]|null $userGroups
     * @param array<string, int[]>|null $restrictionMap from fileManagerRestrictionMap()
     * @return bool
     */
    function fileManagerCanModifyExistingPath($relativePath, ?array $userGroups = null, ?array $restrictionMap = null)
    {
        if (!fileManagerAclApplies()) {
            return true;
        }

        // top level is what this manager sees at the top of their own file manager
        $relativePath = FileManagerAccess::normalizeRelativePath($relativePath);

        return $relativePath !== ''
            && !FileManagerAccess::isTopLevelPath($relativePath)
            && fileManagerIsAccessible($relativePath, $userGroups, $restrictionMap);
    }
}

if(!function_exists('fileManagerResolvePath')) {
    /**
     * Resolve a requested path under the file manager root. Links below the root are rejected;
     * for the remaining paths, ACLs use the resolved path, so callers must check the returned
     * 'relative', never the raw request: own/../private names private, not something below own.
     *
     * @param string $filemanagerPath canonical root, no trailing slash
     * @param string $requestedPath path relative to the root, as requested
     * @return array{path: string, relative: string}|null null when it does not exist or leaves the root
     */
    function fileManagerResolvePath($filemanagerPath, $requestedPath)
    {
        if (fileManagerRefuseReservedName($requestedPath)) {
            return null;
        }
        $requested = $filemanagerPath . '/' . ltrim(str_replace('\\', '/', (string) $requestedPath), '/');
        if (fileManagerPathContainsLink($filemanagerPath, $requested)) {
            return null;
        }

        $path = realpath($requested);
        if ($path === false) {
            return null;
        }
        $path = rtrim(str_replace('\\', '/', $path), '/');
        if (!FileManagerAccess::isWithin($filemanagerPath, $path)) {
            return null;
        }

        return [
            'path' => $path,
            'relative' => FileManagerAccess::normalizeRelativePath(substr($path, strlen(rtrim($filemanagerPath, '/')))),
        ];
    }
}

if(!function_exists('fileManagerProtectedPaths')) {
    /**
     * Folders the file manager must not enter or change: each element folder (and the backups)
     * unless the user may edit that element type (or manage backups) anyway. The manager and the core (code
     * and configuration secrets) are open only to users who can already run their own PHP.
     *
     * @return string[] canonical absolute paths, no trailing slash
     */
    function fileManagerProtectedPaths()
    {
        $evo = evolutionCMS();
        $paths = [];
        $byPermission = [
            'bk_manager' => ['temp/backup', 'assets/backup'],
            'save_plugin' => ['assets/plugins'],
            'save_snippet' => ['assets/snippets'],
            'save_template' => ['assets/templates'],
            'save_module' => ['assets/modules'],
            'empty_cache' => ['assets/cache'],
            'import_static' => ['temp/import', 'assets/import'],
            'export_static' => ['temp/export', 'assets/export'],
        ];
        foreach ($byPermission as $permission => $folders) {
            if (!$evo->hasPermission($permission)) {
                foreach ($folders as $folder) {
                    $paths[] = EVO_BASE_PATH . $folder;
                }
            }
        }

        if (!fileManagerMayRunCode()) {
            $paths[] = EVO_MANAGER_PATH;
            $paths[] = EVO_CORE_PATH;
            // A file under a view path is executed by its engine, whatever the extension.
            foreach (fileManagerTemplateViewPaths() as $viewPath) {
                $paths[] = $viewPath;
            }
        }

        return array_map(
            static fn ($path) => rtrim(str_replace('\\', '/', realpath($path) ?: $path), '/'),
            $paths
        );
    }
}

if(!function_exists('fileManagerTemplateViewPaths')) {
    /**
     * The folders template files are rendered from (`view.paths`). The `php` engine includes a
     * file, `blade.php` compiles to PHP, and plugins register latte, twig, tpl and others, so
     * writing anything there authors server-side code (see TemplateFileGuard). Falls back to the
     * shipped `views/` folder when the configuration cannot be read.
     *
     * @param array|null $configured view paths to use instead of `config('view.paths')`
     * @return string[] absolute paths
     */
    function fileManagerTemplateViewPaths(?array $configured = null)
    {
        if ($configured === null) {
            try {
                $configured = function_exists('config') ? (array) config('view.paths', []) : [];
            } catch (\Throwable $e) {
                $configured = [];
            }
        }
        $paths = array_values(array_filter(array_map('strval', $configured), static fn ($path) => trim($path) !== ''));

        return $paths !== [] ? $paths : [(defined('EVO_BASE_PATH') ? EVO_BASE_PATH : dirname(__DIR__, 3) . '/') . 'views'];
    }
}

if(!function_exists('fileManagerMayRunCode')) {
    /**
     * save_snippet, save_plugin and save_module store PHP that runs on the server: whoever has
     * one can already change anything under core/ and manager/, and may put executable files
     * in place. Everyone else may not.
     */
    function fileManagerMayRunCode()
    {
        return \EvolutionCMS\Support\BackendCodePermissions::granted();
    }
}

if(!function_exists('fileManagerIsExecutableName')) {
    /**
     * Whether a file name would be run by the web server or changes how it serves a folder:
     * a PHP-like extension anywhere in the name ("shell.php.jpg" runs under AddHandler), or
     * one of the per-directory configuration files.
     *
     * @param string $name
     * @return bool
     */
    function fileManagerIsExecutableName($name)
    {
        $base = strtolower(basename(str_replace(chr(92), '/', (string) $name)));
        // Windows drops trailing dots and spaces (".htaccess." is .htaccess), and ".htaccess:x" is a
        // stream of the file .htaccess, which it creates
        $base = rtrim(explode(':', $base, 2)[0], ' .');
        if (in_array($base, ['.htaccess', '.htpasswd', '.user.ini', '.env', 'web.config'], true)) {
            return true;
        }
        $parts = explode('.', $base);
        array_shift($parts);

        foreach ($parts as $part) {
            if (preg_match('/^(?:php\d*|phps|phtml|pht|phar|inc|cgi|pl|py|sh|asp|aspx|jsp)$/', trim($part))) {
                return true;
            }
        }

        return false;
    }
}

if(!function_exists('fileManagerCodeEntry')) {
    /**
     * The first entry at or below $path whose name is executable (fileManagerIsExecutableName()),
     * relative to $path: '' when $path itself is one, null when there is none. A link counts by its
     * own name and is not followed. A folder that cannot be read counts as holding code: what it
     * holds cannot be told.
     *
     * @param string $path absolute
     * @return string|null
     */
    function fileManagerCodeEntry($path)
    {
        $path = rtrim(str_replace('\\', '/', (string) $path), '/');
        if (fileManagerIsExecutableName($path)) {
            return '';
        }
        if (fileManagerIsLink($path) || !is_dir($path)) {
            return null;
        }
        $stack = [''];
        while ($stack) {
            $rel = array_pop($stack);
            $entries = @scandir($rel === '' ? $path : "$path/$rel");
            if ($entries === false) {
                return $rel;
            }
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $entryRel = ltrim("$rel/$entry", '/');
                if (fileManagerIsExecutableName($entry)) {
                    return $entryRel;
                }
                // never probed: a device name can hang the worker (and holds no code by its name)
                if (fileManagerIsReservedDeviceName($entry)) {
                    continue;
                }
                if (!fileManagerIsLink("$path/$entryRel") && is_dir("$path/$entryRel")) {
                    $stack[] = $entryRel;
                }
            }
        }

        return null;
    }
}

if(!function_exists('fileManagerCodeRemovalDenied')) {
    /**
     * The refusal (logged, as fileManagerDenied() prints it) when deleting, renaming or moving $path
     * would take code away from where it is (a file or link of an executable name, or a folder holding
     * one) from a manager who may not create code; null when the operation may go on. Whoever may not
     * write code may not remove or displace it either.
     *
     * @param string $path absolute
     * @param string $relative $path below the root, as logged
     * @return string|null
     */
    function fileManagerCodeRemovalDenied($path, $relative)
    {
        if (fileManagerMayRunCode()) {
            return null;
        }
        $entry = fileManagerCodeEntry($path);

        return $entry === null ? null : fileManagerDenied('executable name', trim("$relative/$entry", '/'));
    }
}


if(!function_exists('fileManagerIsReservedDeviceName')) {
    /**
     * Whether the name is a Windows device name (CON, NUL, COM1.jpg ...). Windows resolves it to the
     * device whatever the extension, and on PHP releases before 8.3.35, 8.4.26 and 8.5.11
     * (CVE-2026-17545) a filesystem call on one can hang the worker. Refused on every platform:
     * a site can be moved to Windows with its files.
     *
     * @param string $name
     * @return bool
     */
    function fileManagerIsReservedDeviceName($name)
    {
        $base = basename(str_replace(chr(92), '/', (string) $name));
        $stem = rtrim(explode('.', $base, 2)[0], ' ');

        return (bool) preg_match('/^(?:con|prn|aux|nul|conin\$|conout\$|(?:com|lpt)[0-9¹²³])$/iu', $stem);
    }
}

if(!function_exists('fileManagerRefuseReservedName')) {
    /**
     * Whether any segment of $path is a Windows device name; logged, because such a name is only
     * ever sent to cause trouble. Ask before the file system is: even a probe (file_exists, is_dir,
     * realpath) of one can hang the worker on an unpatched PHP.
     *
     * @param string $path a name or a path, relative or absolute
     * @return bool true when the path has to be refused
     */
    function fileManagerRefuseReservedName($path)
    {
        foreach (explode('/', str_replace(chr(92), '/', (string) $path)) as $segment) {
            if ($segment !== '' && fileManagerIsReservedDeviceName($segment)) {
                fileManagerLogDenied(null, $segment, 'reserved device name');

                return true;
            }
        }

        return false;
    }
}

if(!function_exists('fileManagerPathIsProtected')) {
    /**
     * Whether $path is a protected folder or lies inside one.
     *
     * @param string $path canonical absolute path
     * @param string[] $protectedPaths
     */
    function fileManagerPathIsProtected($path, array $protectedPaths)
    {
        foreach ($protectedPaths as $protectedPath) {
            if (FileManagerAccess::isWithin($protectedPath, $path)) {
                return true;
            }
        }

        return false;
    }
}

if(!function_exists('fileManagerPathTouchesProtected')) {
    /**
     * Whether removing or renaming $path would affect a protected folder: it lies inside one,
     * or one lies inside it.
     *
     * @param string $path canonical absolute path
     * @param string[] $protectedPaths
     */
    function fileManagerPathTouchesProtected($path, array $protectedPaths)
    {
        foreach ($protectedPaths as $protectedPath) {
            if (FileManagerAccess::isWithin($protectedPath, $path) || FileManagerAccess::isWithin($path, $protectedPath)) {
                return true;
            }
        }

        return false;
    }
}

if(!function_exists('fileManagerIsLink')) {
    /**
     * Whether $path is a link of any kind: a symlink, or on Windows a junction or other
     * reparse point, which is_link() does not report (PHP sees a junction as a plain folder).
     * So an existing entry counts as a link when it does not resolve to itself. readlink()
     * cannot decide it: on Windows it returns a path for ordinary files and folders too.
     *
     * @param string $path
     */
    function fileManagerIsLink($path)
    {
        clearstatcache(true, $path);
        if (is_link($path)) {
            return true;
        }
        if (!file_exists($path)) {
            // something is there that leads nowhere: a dangling junction, which only lstat()
            // sees (is_link() above already caught a dangling symlink)
            return PHP_OS_FAMILY === 'Windows' && @lstat($path) !== false;
        }
        $real = realpath($path);
        $parent = realpath(dirname($path));
        if ($real === false || $parent === false) {
            return true;
        }
        $expected = rtrim(str_replace('\\', '/', $parent), '/') . '/' . basename($path);
        $real = str_replace('\\', '/', $real);

        return PHP_OS_FAMILY === 'Windows' ? strcasecmp($real, $expected) !== 0 : $real !== $expected;
    }
}

if(!function_exists('fileManagerPathContainsLink')) {
    /**
     * Whether a path below $root passes through a symlink or junction. The root itself is a
     * configured boundary and may be a symlink (for example, public_html); links below it are
     * never followed. Accept either the configured root spelling or its canonical spelling.
     *
     * @param string $root absolute root
     * @param string $path absolute path, possibly not created yet
     *
     * A Windows device name below the root counts as a link here: callers refuse both.
     */
    function fileManagerPathContainsLink($root, $path)
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $canonicalRoot = realpath($root);
        $canonicalRoot = $canonicalRoot === false ? '' : rtrim(str_replace('\\', '/', $canonicalRoot), '/');

        if (FileManagerAccess::isWithin($root, $path)) {
            $base = $root;
        } elseif ($canonicalRoot !== '' && FileManagerAccess::isWithin($canonicalRoot, $path)) {
            $base = $canonicalRoot;
        } else {
            return true;
        }

        // a name the file system must not be asked about is as unusable as a link
        if (fileManagerRefuseReservedName(substr($path, strlen($base)))) {
            return true;
        }

        $current = $base;
        $relative = substr($path, strlen($base));
        foreach (explode('/', trim($relative, '/')) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($current === $base) {
                    return true;
                }
                $current = dirname($current);
                continue;
            }

            $current = rtrim($current, '/') . '/' . $segment;
            if (fileManagerIsLink($current)) {
                return true;
            }
        }

        return false;
    }
}

if(!function_exists('fileManagerIsSafeWriteTarget')) {
    /**
     * Whether writing to $target, which need not exist yet, stays under $root. Every part of
     * the path that already exists is checked, so a symlink anywhere on the way (a folder
     * whose children do not exist yet, or the file itself) cannot redirect the write.
     *
     * @param string $root canonical absolute path
     * @param string $target absolute path below $root
     */
    function fileManagerIsSafeWriteTarget($root, $target)
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $target = rtrim(str_replace('\\', '/', $target), '/');
        if ($target === $root || !FileManagerAccess::isWithin($root, $target)) {
            return false;
        }

        if (fileManagerRefuseReservedName(substr($target, strlen($root)))) {
            return false;
        }

        $current = $root;
        foreach (explode('/', substr($target, strlen($root) + 1)) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
            $current .= '/' . $segment;
            if (fileManagerIsLink($current)) {
                return false;
            }
            if (!file_exists($current)) {
                // everything below is created by this write, so none of it can be a link
                break;
            }
        }

        return true;
    }
}

if(!function_exists('fileManagerPathIsTaken')) {
    /**
     * Whether anything is at $path. file_exists() is false for a dangling symlink, which a write
     * would follow, so a link counts as taken.
     *
     * @param string $path
     */
    function fileManagerPathIsTaken($path)
    {
        return file_exists($path) || fileManagerIsLink($path);
    }
}

if(!function_exists('fileManagerIsNewWriteTarget')) {
    /**
     * Whether $target is a safe place for a new file or folder: it stays under $root and
     * nothing is there yet, not even a dangling symlink that a write would follow.
     *
     * @param string $root canonical absolute path
     * @param string $target absolute path below $root
     */
    function fileManagerIsNewWriteTarget($root, $target)
    {
        return fileManagerIsSafeWriteTarget($root, $target) && !fileManagerPathIsTaken($target);
    }
}

if(!function_exists('fileManagerCreateFile')) {
    /**
     * Creates $target with $content, but only as a new file: an existing file or symlink of
     * that name is left alone, so the write can never truncate or follow it.
     *
     * @param string $root canonical absolute path
     * @param string $target absolute path below $root
     * @param string $content
     */
    function fileManagerCreateFile($root, $target, $content = '')
    {
        if (!fileManagerIsNewWriteTarget($root, $target)) {
            return false;
        }
        // "x" fails when the name appeared in the meantime, including as a symlink
        $handle = @fopen($target, 'xb');
        if ($handle === false) {
            return false;
        }
        $written = fwrite($handle, $content) === strlen($content);
        fclose($handle);

        return $written;
    }
}

if(!function_exists('fileManagerCopyToNewFile')) {
    /**
     * Copies $source to $target, which must not exist yet (see fileManagerCreateFile()).
     *
     * @param string $root canonical absolute path
     * @param string $source existing file
     * @param string $target absolute path below $root
     */
    function fileManagerCopyToNewFile($root, $source, $target)
    {
        if (!is_file($source) || !fileManagerIsNewWriteTarget($root, $target)) {
            return false;
        }
        $in = @fopen($source, 'rb');
        if ($in === false) {
            return false;
        }
        $out = @fopen($target, 'xb');
        if ($out === false) {
            fclose($in);

            return false;
        }
        $copied = stream_copy_to_stream($in, $out) !== false;
        fclose($in);
        fclose($out);
        if (!$copied) {
            @unlink($target);
        }

        return $copied;
    }
}

if(!function_exists('fileManagerCopyFolder')) {
    /**
     * Copies the folder $source with what it holds to $target, which must not exist yet. What the
     * walk may not take is left out, a folder with everything below it: links (never followed),
     * protected folders, Windows device names, unreadable entries, and whatever $mayCopy refuses
     * (file groups the user is not in, names the upload rules refuse). Every write is exclusive, so
     * nothing that appears in the meantime is replaced or written through.
     *
     * @param string $root canonical absolute path
     * @param string $source existing folder below $root
     * @param string $target absolute path below $root
     * @param string[] $protectedPaths
     * @param callable|null $mayCopy fn(string $sourcePath, bool $isDir): bool; false leaves the entry out
     * @param int $dirMode
     * @param int|null $fileMode null keeps what the umask gives
     * @param string[]|null $copied filled with the paths, relative to $source, of what was copied
     * @param array<string, string>|null $skipped filled with relative path => reason of what was left out
     * @return bool false when the copy could not be made; a partial copy is removed again
     */
    function fileManagerCopyFolder(
        $root,
        $source,
        $target,
        array $protectedPaths = [],
        ?callable $mayCopy = null,
        $dirMode = 0777,
        $fileMode = null,
        ?array &$copied = null,
        ?array &$skipped = null
    ) {
        $copied = [];
        $skipped = [];
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $source = rtrim(str_replace('\\', '/', $source), '/');
        $target = rtrim(str_replace('\\', '/', $target), '/');
        if (!FileManagerAccess::isWithin($root, $source) || $source === $root
            || fileManagerPathContainsLink($root, $source) || !is_dir($source) || !is_readable($source)
            || fileManagerPathIsProtected(fileManagerCanonicalCase($source), $protectedPaths)
            // a copy into itself would walk what it has just written
            || FileManagerAccess::isWithin($source, $target)
            || !fileManagerIsNewWriteTarget($root, $target)
            || fileManagerPathIsProtected(fileManagerCanonicalCase($target), $protectedPaths)) {
            return false;
        }

        // first decide what goes, then write: a refusal below must not leave half a copy behind
        $dirs = [];
        $files = [];
        $pending = [''];
        while ($pending !== []) {
            $relDir = array_pop($pending);
            $entries = @scandir($source . ($relDir !== '' ? '/' . $relDir : ''));
            if ($entries === false) {
                $skipped[$relDir] = 'unreadable';
                continue;
            }
            foreach ($entries as $name) {
                if ($name === '.' || $name === '..') {
                    continue;
                }
                $rel = $relDir !== '' ? $relDir . '/' . $name : $name;
                // before the entry is probed: on an unpatched PHP that alone can hang the worker
                if (fileManagerIsReservedDeviceName($name)) {
                    $skipped[$rel] = 'reserved device name';
                    continue;
                }
                $path = $source . '/' . $rel;
                if (fileManagerIsLink($path)) {
                    $skipped[$rel] = 'link';
                    continue;
                }
                if (fileManagerPathIsProtected(fileManagerCanonicalCase($path), $protectedPaths)) {
                    $skipped[$rel] = 'protected folder';
                    continue;
                }
                $isDir = is_dir($path);
                if ((!$isDir && !is_file($path)) || !is_readable($path)) {
                    $skipped[$rel] = 'unreadable';
                    continue;
                }
                if ($mayCopy !== null && !$mayCopy($path, $isDir)) {
                    $skipped[$rel] = 'refused';
                    continue;
                }
                if ($isDir) {
                    $dirs[] = $rel;
                    $pending[] = $rel;
                } else {
                    $files[] = $rel;
                }
            }
        }

        // mkdir() fails on anything already there, a link included
        if (!@mkdir($target, $dirMode)) {
            return false;
        }
        @chmod($target, $dirMode);
        // parents come before their children: a folder is listed before its own entries are read
        foreach ($dirs as $rel) {
            if (!@mkdir($target . '/' . $rel, $dirMode)) {
                rrmdir($target);
                $copied = [];

                return false;
            }
            @chmod($target . '/' . $rel, $dirMode);
        }
        foreach ($files as $rel) {
            if (!fileManagerCopyToNewFile($root, $source . '/' . $rel, $target . '/' . $rel)) {
                rrmdir($target);
                $copied = [];

                return false;
            }
            if ($fileMode !== null) {
                @chmod($target . '/' . $rel, $fileMode);
            }
        }
        $copied = array_merge($dirs, $files);

        return true;
    }
}

if(!function_exists('fileManagerCopyFolderAccessFilter')) {
    /**
     * The entries below $source the current manager may reach, from one query for the whole
     * subtree. A folder copy (like a download archive) must not hand out what the listing hides.
     *
     * @param string $filemanagerPath canonical root, no trailing slash
     * @param string $source canonical folder below it
     * @return callable(string): bool asked with an absolute path below $source
     */
    function fileManagerCopyFolderAccessFilter($filemanagerPath, $source): callable
    {
        $filemanagerPath = rtrim(str_replace('\\', '/', $filemanagerPath), '/');
        if (!fileManagerAclApplies()) {
            return static fn ($path) => true;
        }
        $restrictions = fileManagerSubtreeRestrictionMap(FileManagerAccess::getRelativePath($filemanagerPath, $source));
        $groups = fileManagerUserGroupIds();

        return static function ($path) use ($filemanagerPath, $restrictions, $groups) {
            $path = str_replace('\\', '/', $path);
            if (!FileManagerAccess::isWithin($filemanagerPath, $path)) {
                return false;
            }

            return fileManagerIsAccessible(substr($path, strlen($filemanagerPath) + 1), $groups, $restrictions);
        };
    }
}

if(!function_exists('fileManagerCreateDirectoryZip')) {
    /**
     * Packs $sourcePath into $zipPath, leaving out what the current manager may not reach: protected
     * folders and file groups they are not in, a folder with everything below it. A link is stored
     * as a link entry with a relative target, never followed (fileManagerRelativeLinkTarget()).
     *
     * @param array<string, int[]> $fileGroupsMap restrictions of the whole subtree (fileManagerSubtreeRestrictionMap())
     */
    function fileManagerCreateDirectoryZip(
        string $sourcePath,
        string $relativePath,
        string $filemanagerPath,
        string $zipPath,
        array $userGroups,
        array $fileGroupsMap,
        array $protectedPaths
    ): bool {
        $sourcePath = rtrim(str_replace('\\', '/', realpath($sourcePath)), '/');
        $filemanagerPath = rtrim(str_replace('\\', '/', realpath($filemanagerPath)), '/');
        $archiveRoot = basename($relativePath !== '' ? $relativePath : $sourcePath);
        $archiveRoot = trim(preg_replace('/[^\w.-]+/', '-', $archiveRoot), '.-') ?: 'files';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $zip->addEmptyDir($archiveRoot);
        $mayReveal = static function ($target) use ($filemanagerPath, $userGroups) {
            // the target's own groups, not only those this folder's subtree map holds
            return fileManagerIsAccessible(substr($target, strlen($filemanagerPath) + 1), $userGroups);
        };

        // a walk of our own: an iterator would enter a junction, which PHP takes for a folder
        $pending = [''];
        while ($pending !== []) {
            $relDir = array_pop($pending);
            $entries = @scandir($sourcePath . ($relDir !== '' ? '/' . $relDir : ''));
            if ($entries === false) {
                continue;
            }
            foreach ($entries as $name) {
                if ($name === '.' || $name === '..' || fileManagerIsReservedDeviceName($name)) {
                    continue;
                }
                $rel = $relDir !== '' ? $relDir . '/' . $name : $name;
                $itemPath = $sourcePath . '/' . $rel;
                $itemRelPath = ltrim(substr($itemPath, strlen($filemanagerPath)), '/');
                if (!fileManagerIsAccessible($itemRelPath, $userGroups, $fileGroupsMap)) {
                    continue;
                }
                $archivePath = $archiveRoot . '/' . $rel;
                if (fileManagerIsLink($itemPath)) {
                    // kept as a link, never followed; its target only as a relative path the
                    // manager may know of, so a blue-green "current" survives the round trip
                    $target = fileManagerRelativeLinkTarget($filemanagerPath, $itemPath, null, $mayReveal);
                    if ($target !== null) {
                        fileManagerZipAddLink($zip, $archivePath, $target);
                    }
                    continue;
                }
                if (fileManagerPathIsProtected(fileManagerCanonicalCase($itemPath), $protectedPaths) || !is_readable($itemPath)) {
                    continue;
                }
                if (is_dir($itemPath)) {
                    $zip->addEmptyDir($archivePath);
                    $pending[] = $rel;
                } elseif (is_file($itemPath)) {
                    $zip->addFile($itemPath, $archivePath);
                }
            }
        }

        return $zip->close();
    }
}

if(!function_exists('fileManagerNormalizePathLexically')) {
    /**
     * Resolves . and .. of an absolute path by its text alone, without asking the file system.
     *
     * @param string $path absolute path
     * @return string|null null when .. climbs above the file system root
     */
    function fileManagerNormalizePathLexically($path)
    {
        $path = str_replace('\\', '/', (string) $path);
        $prefix = '';
        if (preg_match('~^([A-Za-z]:)/~', $path, $match)) {
            $prefix = $match[1];
            $path = substr($path, 2);
        }
        $parts = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($parts === []) {
                    return null;
                }
                array_pop($parts);
                continue;
            }
            $parts[] = $segment;
        }

        return $prefix . '/' . implode('/', $parts);
    }
}

if(!function_exists('fileManagerRelativeLinkTarget')) {
    /**
     * Where the link $linkPath leads, as a path relative to the folder it lies in, and only when
     * that place is inside $root and $mayReveal lets the viewer know of it. A target outside the
     * root (../../ or absolute alike) or one the viewer may not reach gives null: neither the
     * server's layout nor the name of a restricted entry is shown to the manager.
     *
     * @param string $root canonical absolute path
     * @param string $linkPath absolute path of the link, its folder canonical and inside $root
     * @param string|null $target the link's own text (a ZIP entry, never trusted: an absolute one
     *                            gives null); null reads the link on disk
     * @param callable|null $mayReveal fn(string $absoluteTarget): bool
     * @return string|null
     */
    function fileManagerRelativeLinkTarget($root, $linkPath, $target = null, ?callable $mayReveal = null)
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $from = rtrim(str_replace('\\', '/', dirname(str_replace('\\', '/', $linkPath))), '/');
        $resolved = false;
        // a target handed in is text from an archive, written by whoever made it: compared with the
        // root as an absolute path, guesses would tell where the site lives on the server
        $supplied = $target !== null;
        if ($target === null) {
            // an existing target: where the file system says it is, whatever the link's text
            $resolved = realpath($linkPath);
            if ($resolved === false) {
                $target = @readlink($linkPath);
                if (!is_string($target) || $target === '') {
                    return null;
                }
            }
        }
        if ($resolved === false) {
            // a long-path prefix as Windows reports a junction's target
            $target = preg_replace('~^//\?/~', '', str_replace('\\', '/', (string) $target));
            $absolute = preg_match('~^(?:/|[A-Za-z]:/)~', $target) === 1;
            if ($absolute && $supplied) {
                return null;
            }
            $resolved = fileManagerNormalizePathLexically($absolute ? $target : $from . '/' . $target);
            if ($resolved === null) {
                return null;
            }
        }
        $resolved = rtrim(str_replace('\\', '/', $resolved), '/');
        if (!FileManagerAccess::isWithin($root, $resolved)
            || ($mayReveal !== null && !$mayReveal($resolved))) {
            return null;
        }

        $fromParts = array_values(array_filter(explode('/', substr($from, strlen($root))), 'strlen'));
        $toParts = array_values(array_filter(explode('/', substr($resolved, strlen($root))), 'strlen'));
        $common = 0;
        while (isset($fromParts[$common], $toParts[$common])
            && (PHP_OS_FAMILY === 'Windows'
                ? strcasecmp($fromParts[$common], $toParts[$common]) === 0
                : $fromParts[$common] === $toParts[$common])) {
            $common++;
        }
        $relative = str_repeat('../', count($fromParts) - $common) . implode('/', array_slice($toParts, $common));

        return rtrim($relative, '/') !== '' ? rtrim($relative, '/') : '.';
    }
}

if(!function_exists('fileManagerLinkTargetText')) {
    /**
     * What the classic manager shows for where a link leads: its relative target, marked when it
     * leads nowhere, or one note for anything the manager may not know of. "Broken" is said only of
     * a target that is shown: of one outside, it would tell whether a path exists there.
     * Plain text, to be escaped.
     *
     * @param string $root canonical file manager root
     * @param string $linkPath absolute path of the link
     * @param string|null $target the link's own text (a ZIP entry); null reads the link on disk
     */
    function fileManagerLinkTargetText($root, $linkPath, $target = null)
    {
        $_lang = ManagerTheme::getLexicon();
        $root = rtrim(str_replace('\\', '/', $root), '/');
        // the file groups are asked about links on disk only: for text from an archive, the answer
        // would tell which guessed paths are restricted, and the text is the uploader's own anyway
        $mayReveal = $target !== null ? null : static function ($resolved) use ($root) {
            return fileManagerIsAccessible(substr($resolved, strlen($root) + 1));
        };
        $relative = fileManagerRelativeLinkTarget($root, $linkPath, $target, $mayReveal);
        if ($relative === null) {
            return $_lang['files_link_hidden'];
        }

        return $target === null && !file_exists($linkPath) ? $relative . ' (' . $_lang['files_link_broken'] . ')' : $relative;
    }
}

if(!function_exists('fileManagerLinkList')) {
    /**
     * Links the classic manager did not create, as escaped "name → target" lines: those of an
     * archive (entry name => target text) or of a folder copy (path => null, read on disk).
     *
     * @param string $root canonical file manager root
     * @param string $base folder the names are relative to
     * @param array<string, string|null> $links
     */
    function fileManagerLinkList($root, $base, array $links)
    {
        $base = rtrim(str_replace('\\', '/', $base), '/');
        $lines = [];
        foreach (array_slice($links, 0, 20, true) as $name => $target) {
            $lines[] = e((string) $name) . ' &rarr; ' . e(fileManagerLinkTargetText($root, $base . '/' . $name, $target));
        }
        if (count($links) > 20) {
            $lines[] = '&hellip; +' . (count($links) - 20);
        }

        return implode('<br />', $lines);
    }
}

if(!function_exists('fileManagerZipEntryIsLink')) {
    /**
     * Whether entry $index of $zip is a symbolic link: a Unix entry whose file type is S_IFLNK.
     * Its content is the link's target, not data.
     */
    function fileManagerZipEntryIsLink(ZipArchive $zip, $index)
    {
        $opsys = null;
        $attributes = null;
        if (!$zip->getExternalAttributesIndex($index, $opsys, $attributes)) {
            return false;
        }

        return $opsys === ZipArchive::OPSYS_UNIX && ((($attributes >> 16) & 0170000) === 0120000);
    }
}

if(!function_exists('fileManagerZipAddLink')) {
    /**
     * Adds a symbolic link entry to $zip: what an unpacker that keeps links (unzip, 7-Zip, tar)
     * recreates as a link, and others as a small file holding the target.
     *
     * @param string $target relative target (fileManagerRelativeLinkTarget())
     */
    function fileManagerZipAddLink(ZipArchive $zip, $name, $target)
    {
        return $zip->addFromString($name, $target)
            && $zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, (0120777 << 16));
    }
}

if(!function_exists('fileManagerCanRenameTo')) {
    /**
     * Whether $source may be renamed to $target: nothing is at the new name yet, so a rename
     * cannot replace another file (or one the user may not modify). A case-only rename is
     * allowed too, as on Windows the new name finds the file itself.
     *
     * @param string $root canonical absolute path
     * @param string $source existing entry
     * @param string $target absolute path below $root
     */
    function fileManagerCanRenameTo($root, $source, $target)
    {
        if (fileManagerIsNewWriteTarget($root, $target)) {
            return true;
        }
        if (!fileManagerIsSafeWriteTarget($root, $target)
            || strcasecmp(str_replace('\\', '/', $source), str_replace('\\', '/', $target)) !== 0) {
            return false;
        }
        $sourceReal = realpath($source);
        $targetReal = realpath($target);
        if ($sourceReal === false || $targetReal === false) {
            return false;
        }
        if ($sourceReal === $targetReal) {
            return true;
        }

        // On case-insensitive macOS volumes realpath can preserve the requested
        // spelling. Verify the entry identity without allowing replacement of a
        // distinct, case-sensitive hard link that happens to share its inode.
        $sourceStat = @lstat($sourceReal);
        $targetStat = @lstat($targetReal);
        $entries = @scandir(dirname($sourceReal));

        return $sourceStat !== false && $targetStat !== false && $entries !== false
            && $sourceStat['ino'] !== 0
            && $sourceStat['dev'] === $targetStat['dev']
            && $sourceStat['ino'] === $targetStat['ino']
            && !in_array(basename($target), $entries, true);
    }
}

if(!function_exists('fileManagerRemoveLink')) {
    /**
     * Removes the symlink $path itself, never what it points to. A directory symlink or
     * junction on Windows is removed with rmdir.
     *
     * @param string $path
     */
    function fileManagerRemoveLink($path)
    {
        return @unlink($path) || @rmdir($path);
    }
}

if(!function_exists('fileManagerCanonicalCase')) {
    /**
     * $path with the letter case the file system stores for its existing part; the rest, which
     * does not exist yet, is kept as given.
     *
     * @param string $path absolute path
     */
    function fileManagerCanonicalCase($path)
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $rest = [];
        $existing = $path;
        while ($existing !== '' && ($real = realpath($existing)) === false) {
            $rest[] = basename($existing);
            $parent = dirname($existing);
            if ($parent === $existing) {
                return $path;
            }
            $existing = $parent;
        }
        if ($existing === '') {
            return $path;
        }

        return rtrim(str_replace('\\', '/', $real), '/') . ($rest ? '/' . implode('/', array_reverse($rest)) : '');
    }
}

if(!function_exists('fileManagerZipWriteGuard')) {
    /**
     * Per-entry check for fileManagerExtractZip(): the ZIP and the destination folder were
     * checked, but its entries may carry names the upload form refuses, or lead into restricted
     * subfolders or over restricted files.
     *
     * @param string $filemanagerPath canonical root, no trailing slash
     * @param string $destination canonical folder the archive is extracted into
     */
    function fileManagerZipWriteGuard($filemanagerPath, $destination): callable
    {
        $filemanagerPath = rtrim(str_replace('\\', '/', $filemanagerPath), '/');
        $aclApplies = fileManagerAclApplies();
        $restrictions = [];
        if ($aclApplies) {
            $destRel = FileManagerAccess::getRelativePath($filemanagerPath, $destination);
            $restrictions = fileManagerSubtreeRestrictionMap($destRel);
        }
        $groups = fileManagerUserGroupIds();

        return static function ($target, $isDir) use ($filemanagerPath, $aclApplies, $restrictions, $groups) {
            // unpacking must not be the way around the upload form's allowed extensions
            if (!$isDir && !checkExtension($target)) {
                return false;
            }
            if (!$aclApplies) {
                return true;
            }
            $target = str_replace('\\', '/', $target);
            if (!FileManagerAccess::isWithin($filemanagerPath, $target)) {
                return false;
            }
            $rel = FileManagerAccess::normalizeRelativePath(substr($target, strlen($filemanagerPath)));

            $allowed = file_exists($target)
                ? fileManagerCanModifyExistingPath($rel, $groups, $restrictions)
                : fileManagerIsAccessible($rel, $groups, $restrictions);
            if (!$allowed) {
                fileManagerLogDenied('unzip entry', $rel, 'file group');
            }

            return $allowed;
        };
    }
}

if(!function_exists('fileManagerExtractZip')) {
    /**
     * Extracts $file into $path, skipping any entry that would land outside it, go through a
     * symlink, reach a protected folder, or be refused by $mayWrite.
     *
     * @param string $file
     * @param string $path
     * @param string[] $protectedPaths
     * @param int $dirMode
     * @param callable|null $mayWrite fn(string $target, bool $isDir): bool, asked with the canonical
     *                                target of each entry; false skips it (upload rules, file groups)
     * @param string[]|null $created filled with the files this call created; files it replaced are
     *                               not listed, so they keep the restrictions they already have
     * @param array<string, string>|null $links filled with entry name => target of the symbolic links
     *                                          in the archive: never created, nor written as files
     * @return bool false when the archive cannot be opened
     */
    function fileManagerExtractZip($file, $path, array $protectedPaths = [], $dirMode = 0777, ?callable $mayWrite = null, ?array &$created = null, ?array &$links = null)
    {
        $links = [];
        $root = realpath($path);
        if ($root === false) {
            return false;
        }
        $root = rtrim(str_replace('\\', '/', $root), '/');

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return false;
        }
        $refused = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $filename = str_replace('\\', '/', $stat['name']);
            if (substr($filename, 0, 1) == '/' || strpos($filename, ':') !== false) {
                $refused[] = $filename;
                continue; // skip absolute paths
            }
            // only a whole ".." segment climbs up: "just-stop..png" is an ordinary file name
            // (Windows hides the extension, so "just-stop." easily becomes one)
            $segments = array_values(array_filter(
                explode('/', $filename),
                static fn ($segment) => $segment !== '' && $segment !== '.'
            ));
            if ($segments === [] || in_array('..', $segments, true)) {
                if ($segments !== []) {
                    $refused[] = $filename;
                }
                continue;
            }
            if (fileManagerZipEntryIsLink($zip, $i)) {
                // the file manager creates no links; written out, it would be a file holding a path
                $links[implode('/', $segments)] = (string) $zip->getFromIndex($i);
                continue;
            }
            $isDir = substr($filename, -1) == '/';
            $target = $root . '/' . implode('/', $segments);
            if (!fileManagerIsSafeWriteTarget($root, $target)) {
                $refused[] = $filename;
                continue;
            }
            // the entry's own letter case is not trusted: on Windows ASSETS/PLUGINS is assets/plugins
            $canonical = fileManagerCanonicalCase($target);
            if (fileManagerPathIsProtected($canonical, $protectedPaths)) {
                $refused[] = $filename;
                continue;
            }
            if ($mayWrite !== null && !$mayWrite($canonical, $isDir)) {
                continue;
            }
            if ($isDir) {
                if (!is_dir($target)) {
                    mkdir($target, $dirMode, true);
                }
                continue;
            }
            $dirname = dirname($target);
            if (!is_dir($dirname)) {
                mkdir($dirname, $dirMode, true);
            }
            $existed = file_exists($target);
            if (file_put_contents($target, $zip->getFromIndex($i)) !== false && !$existed && $created !== null) {
                $created[] = $target;
            }
        }
        $zip->close();

        if ($refused !== []) {
            fileManagerLogDenied(
                'unzip',
                basename((string) $file),
                count($refused) . ' entries refused: ' . implode(', ', array_slice($refused, 0, 5))
            );
        }

        return true;
    }
}

if(!function_exists('fileManagerSubtreeRestrictionMap')) {
    /**
     * @param string $relativePath relative to the manager's root
     * @return array<string, int[]> keyed by ACL key
     */
    function fileManagerSubtreeRestrictionMap($relativePath)
    {
        if (!fileManagerAclApplies()) {
            return [];
        }

        $key = fileManagerAclKey($relativePath);
        if ($key === null) {
            return [];
        }

        return FileManagerAccess::loadSubtreeRestrictions($key);
    }
}

if(!function_exists('fileManagerHasInaccessibleDescendants')) {
    /**
     * Whether a recursive operation on $relativePath would reach something the user may not.
     *
     * @param string $relativePath relative to the manager's root
     * @return bool
     */
    function fileManagerHasInaccessibleDescendants($relativePath)
    {
        $restrictions = fileManagerSubtreeRestrictionMap($relativePath);
        $key = fileManagerAclKey($relativePath);

        return $restrictions !== [] && $key !== null
            && FileManagerAccess::inaccessibleDescendants($key, fileManagerUserGroupIds(), $restrictions) !== [];
    }
}

if(!function_exists('fileManagerEffectiveGroupIds')) {
    /**
     * @param string $relativePath relative to the manager's root
     * @param array<string, int[]>|null $restrictionMap from fileManagerRestrictionMap()
     * @return int[]
     */
    function fileManagerEffectiveGroupIds($relativePath, ?array $restrictionMap = null)
    {
        $key = fileManagerAclKey($relativePath);
        if ($key === null) {
            return [];
        }

        return FileManagerAccess::effectiveGroupIds(
            $key,
            $restrictionMap ?? fileManagerRestrictionMap([$relativePath])
        );
    }
}

if(!function_exists('determineIcon')) {
    /**
     * @param string $file
     * @param string $selFile
     * @param string $mode
     * @return string
     */
    function determineIcon($file, $selFile, $mode)
    {
        $_style = ManagerTheme::getStyle();

        $icons = [
            'default' => $_style['icon_file'],
            'edit'    => $_style['icon_edit'],
            'view'    => $_style['icon_eye']
        ];
        $icon = $icons['default'];
        if ($file == $selFile) {
            $icon = isset($icons[$mode]) ? $icons[$mode] : $icons['default'];
        }

        return '<i class="' . $icon . ' FilesPage"></i>';
    }
}

if(!function_exists('markRow')) {
    /**
     * @param string $file
     * @param string $selFile
     * @param string $mode
     * @return string
     */
    function markRow($file, $selFile, $mode)
    {
        $classNames = [
            'default' => '',
            'edit'    => 'editRow',
            'view'    => 'viewRow'
        ];
        if ($file == $selFile) {
            $class = isset($classNames[$mode]) ? $classNames[$mode] : $classNames['default'];

            return ' class="' . $class . '"';
        }

        return '';
    }
}

if(!function_exists('ls')) {
    /**
     * @param string $curpath
     * @param array $options
     */
    function ls($curpath, array $options = [])
    {
        extract($options, EXTR_OVERWRITE);

        $curpath = rtrim(str_replace('\\', '/', $curpath), '/') . '/';
        $filemanager_path = rtrim(str_replace('\\', '/', $filemanager_path), '/');
        $base_path = rtrim(str_replace('\\', '/', $base_path), '/');

        $_lang = ManagerTheme::getLexicon();
        $_style = ManagerTheme::getStyle();
        $dircounter = 0;
        $filecounter = 0;
        $filesizes = 0;
        $dirs_array = [];
        $files_array = [];
        $links_array = [];
        $currentRelPath = ltrim(substr(rtrim($curpath, '/'), strlen($filemanager_path)), '/');
        $currentDirAccessible = fileManagerIsAccessible($currentRelPath, $userGroups ?? [], $fileGroupsMap ?? []);
        $currentDirWritable = $currentDirAccessible && is_writable($curpath);
        $docGroupNamesById = [];
        if (!empty($allDocGroups)) {
            foreach ($allDocGroups as $group) {
                $docGroupNamesById[(int)$group->id] = $group->name;
            }
        }

        if (!is_dir($curpath)) {
            echo 'Invalid path "', htmlspecialchars($curpath, ENT_QUOTES, 'UTF-8'), '"<br />';

            return;
        }
        $dir = scandir($curpath);

        // first, get info
        foreach ($dir as $file) {
            $newpath = $curpath . $file;
            if ($file === '..' || $file === '.') {
                continue;
            }
            $rel_newpath = ltrim(substr($newpath, strlen($filemanager_path)), '/');
            $rel_web = ltrim(substr($newpath, strlen($base_path)), '/');
            if (fileManagerIsLink($newpath)) {
                // shown, never followed: a link may lead anywhere, and the web server would serve its
                // target without any check here, so it gets no URL, no drill-down and no action
                if (fileManagerIsAccessible($rel_newpath, $userGroups ?? [], $fileGroupsMap ?? [])) {
                    $linkStats = @lstat($newpath) ?: [7 => 0, 9 => 0];
                    // removing the link itself is the one action; judged by its own path, not its target's
                    $canDeleteLink = $currentDirWritable
                        && fileManagerCanModifyExistingPath($rel_newpath, $userGroups ?? [], $fileGroupsMap ?? [])
                        && !fileManagerPathIsProtected($newpath, $protected_path ?? []);
                    $links_array[] = [
                        'name' => $file,
                        'stats' => $linkStats,
                        'delete' => $canDeleteLink
                            ? '<a href="javascript:deleteLink(\'' . urlencode($file) . '\');"><i class="' . $_style['icon_trash']
                                . '" title="' . e($_lang['files_link_delete']) . '"></i></a>'
                            : '',
                        'text' => '<span class="FilesLink"><i class="' . ($_style['icon_link'] ?? 'evo-icon evo-icon-link') . '"></i> '
                            . htmlspecialchars($file, ENT_QUOTES, 'UTF-8')
                            . ' <small class="text-muted">&rarr; '
                            . htmlspecialchars(fileManagerLinkTargetText($filemanager_path, $newpath), ENT_QUOTES, 'UTF-8')
                            . '</small></span>',
                    ];
                }
                continue;
            }
            if (!fileManagerIsAccessible($rel_newpath, $userGroups ?? [], $fileGroupsMap ?? [])) {
                continue;
            }
            $effectiveGroupNames = array_map(
                static fn ($groupId) => $docGroupNamesById[$groupId] ?? (string)$groupId,
                fileManagerEffectiveGroupIds($rel_newpath, $fileGroupsMap ?? [])
            );
            $groupSuffix = empty($effectiveGroupNames)
                ? ''
                : ' <small class="text-muted">- ' . htmlspecialchars(implode(', ', $effectiveGroupNames), ENT_QUOTES, 'UTF-8') . '</small>';
            if (is_dir($newpath)) {
                $dirs_array[$dircounter]['dir'] = $newpath;
                $dirs_array[$dircounter]['stats'] = lstat($newpath);
                if ($file === '..' || $file === '.') {
                    continue;
                } elseif (!in_array($file, $excludes) && !in_array($newpath, $protected_path)) {
                    $dirs_array[$dircounter]['text'] = '<i class="' . $_style['icon_folder'] . ' FilesFolder"></i> '
                        . '<a href="index.php?a=31&mode=drill&path=' . urlencode($rel_newpath) . '"><b>'
                        . htmlspecialchars($file, ENT_QUOTES, 'UTF-8') . '</b></a>' . $groupSuffix;

                    $dfiles = scandir($newpath);
                    foreach ($dfiles as $i => $infile) {
                        switch ($infile) {
                            case '..':
                            case '.':
                                unset($dfiles[$i]);
                                break;
                        }
                    }
                    $file_exists = (0 < count($dfiles)) ? 'file_exists' : '';
                    $canModifyDir = $currentDirWritable
                        && fileManagerCanModifyExistingPath($rel_newpath, $userGroups ?? [], $fileGroupsMap ?? [])
                        && is_writable($newpath);

                    $dirs_array[$dircounter]['delete'] = $canModifyDir ? '<a href="javascript: deleteFolder(\''
                        . urlencode($file) . '\',\'' . $file_exists . '\');"><i class="' . $_style['icon_trash']
                        . '" title="' . $_lang['file_delete_folder'] . '"></i></a>' : '';
                    // a copy lands beside the original: what counts is that this folder takes new entries
                    $dirs_array[$dircounter]['copy'] = $currentDirWritable
                        ? '<a href="javascript:copyFolder(\'' . urlencode($file) . '\');"><i class="'
                        . $_style['icon_clone'] . '" title="' . $_lang['files_copy_folder'] . '"></i></a>'
                        : '';
                } else {
                    $dirs_array[$dircounter]['text'] = '<span><i class="' . $_style['icon_folder']
                        . ' FilesDeletedFolder"></i> ' . htmlspecialchars($file, ENT_QUOTES, 'UTF-8')
                        . $groupSuffix
                        . '</span>';
                    $dirs_array[$dircounter]['delete'] = $currentDirWritable ? '<span class="disabled"><i class="'
                        . $_style['icon_trash'] . '" title="' . $_lang['file_delete_folder'] . '"></i></span>' : '';
                }

                $dirs_array[$dircounter]['rename'] = ($currentDirWritable
                    && fileManagerCanModifyExistingPath($rel_newpath, $userGroups ?? [], $fileGroupsMap ?? [])
                    && is_writable($newpath)) ? '<a href="javascript:renameFolder(\''
                    . urlencode($file) . '\');"><i class="' . $_style['icon_i_cursor'] . '" title="' . $_lang['rename']
                    . '"></i></a>' : '';

                $dirs_array[$dircounter]['groups'] = ($showFileGroups ?? false)
                    ? '<a href="index.php?a=31&mode=groups&path=' . urlencode($rel_newpath) . '"><i class="'
                    . (!empty($fileGroupsMap[fileManagerAclKey($rel_newpath) ?? '']) ? $_style['icon_lock'] : $_style['icon_unlock'])
                    . '" title="' . $_lang['file_groups_edit'] . '"></i></a>'
                    : '';

                // increment the counter
                $dircounter++;
            } else {
                $type = getExtension($newpath);
                $files_array[$filecounter]['file'] = $rel_newpath;
                $files_array[$filecounter]['stats'] = lstat($newpath);
                $files_array[$filecounter]['text'] = determineIcon($rel_newpath, get_by_key($_REQUEST, 'path', ''),
                        get_by_key($_REQUEST, 'mode', '')) . ' ' . htmlspecialchars($file, ENT_QUOTES,
                        'UTF-8') . $groupSuffix;
                $canModifyFile = $currentDirWritable
                    && fileManagerCanModifyExistingPath($rel_newpath, $userGroups ?? [], $fileGroupsMap ?? [])
                    && is_writable($newpath);
                $files_array[$filecounter]['view'] = in_array($type, $viewablefiles)
                    ? '<a href="javascript:;" onclick="viewfile(\'../' . implode('/', array_map('rawurlencode', explode('/', $rel_web))) . '\');"><i class="' . $_style['icon_eye'] . '" title="'
                    . $_lang['files_viewfile'] . '"></i></a>'
                    : (($enablefiledownload && in_array($type, $uploadablefiles))
                        ? '<a href="../' . implode('/', array_map('rawurlencode',
                            explode('/', $rel_web)))
                        . '" style="cursor:pointer;" download><i class="' . $_style['icon_download'] . '" title="'
                        . $_lang['file_download_file'] . '"></i></a>' : '<span class="disabled"><i class="'
                        . $_style['icon_eye'] . '" title="' . $_lang['files_viewfile'] . '"></i></span>');
                $files_array[$filecounter]['view'] = (in_array($type, $inlineviewablefiles))
                    ? '<a href="index.php?a=31&mode=view&path=' . urlencode($rel_newpath) . '"><i class="'
                    . $_style['icon_eye'] . '" title="' . $_lang['files_viewfile'] . '"></i></a>'
                    : $files_array[$filecounter]['view'];
                $files_array[$filecounter]['unzip'] = ($enablefileunzip && $type == '.zip')
                    ? '<a href="javascript:unzipFile(\'' . urlencode($file) . '\');"><i class="'
                    . $_style['icon_archive'] . '" title="' . $_lang['file_download_unzip'] . '"></i></a>'
                    : '';
                $files_array[$filecounter]['edit'] = (in_array($type,
                        $editablefiles) && $canModifyFile)
                    ? '<a href="index.php?a=31&mode=edit&path=' . urlencode($rel_newpath) . '#file_editfile"><i class="'
                    . $_style['icon_edit'] . '" title="' . $_lang['files_editfile'] . '"></i></a>'
                    : '<span class="disabled"><i class="' . $_style['icon_edit'] . '" title="' . $_lang['files_editfile']
                    . '"></i></span>';
                $files_array[$filecounter]['duplicate'] = (in_array($type, $editablefiles) && $canModifyFile)
                    ? '<a href="javascript:duplicateFile(\'' . urlencode($file) . '\');"><i class="' . $_style['icon_clone']
                    . '" title="' . $_lang['duplicate'] . '"></i></a>'
                    : '<span class="disabled"><i class="' . $_style['icon_clone'] . '" align="absmiddle" title="'
                    . $_lang['duplicate'] . '"></i></span>';
                $files_array[$filecounter]['rename'] = (in_array($type, $editablefiles) && $canModifyFile)
                    ? '<a href="javascript:renameFile(\'' . urlencode($file) . '\');"><i class="'
                    . $_style['icon_i_cursor'] . '" align="absmiddle" title="' . $_lang['rename'] . '"></i></a>'
                    : '<span class="disabled"><i class="' . $_style['icon_i_cursor'] . '" align="absmiddle" title="'
                    . $_lang['rename'] . '"></i></span>';
                $files_array[$filecounter]['delete'] = $canModifyFile
                    ? '<a href="javascript:deleteFile(\'' . urlencode($file) . '\');"><i class="'
                    . $_style['icon_trash'] . '" title="' . $_lang['file_delete_file'] . '"></i></a>'
                    : '<span class="disabled"><i class="' . $_style['icon_trash'] . '" title="'
                    . $_lang['file_delete_file'] . '"></i></span>';

                $files_array[$filecounter]['groups'] = ($showFileGroups ?? false)
                    ? '<a href="index.php?a=31&mode=groups&path=' . urlencode($rel_newpath) . '"><i class="'
                    . (!empty($fileGroupsMap[fileManagerAclKey($rel_newpath) ?? '']) ? $_style['icon_lock'] : $_style['icon_unlock'])
                    . '" title="' . $_lang['file_groups_edit'] . '"></i></a>'
                    : '';

                // increment the counter
                $filecounter++;
            }
        }

        // dump array entries for directories
        $folders = count($dirs_array);
        sort($dirs_array); // sorting the array alphabetically (Thanks pxl8r!)
        for ($i = 0; $i < $folders; $i++) {
            $filesizes += $dirs_array[$i]['stats']['7'];
            echo '<tr>';
            echo '<td>' . $dirs_array[$i]['text'] . '</td>';
            echo '<td class="text-nowrap">' . evolutionCMS()->toDateFormat($dirs_array[$i]['stats']['9']) . '</td>';
            echo '<td class="text-right">' . niceSize($dirs_array[$i]['stats']['7']) . '</td>';
            echo '<td class="actions text-right">';
            echo '<span class="disabled"><i class="' . $_style['icon_eye'] . '"></i></span>';
            echo '<span class="disabled"><i class="' . $_style['icon_edit'] . '"></i></span>';
            echo ($dirs_array[$i]['copy'] ?? '') !== ''
                ? $dirs_array[$i]['copy']
                : '<span class="disabled"><i class="' . $_style['icon_clone'] . '"></i></span>';
            echo $dirs_array[$i]['rename'];
            echo $dirs_array[$i]['groups'] ?? '';
            echo $dirs_array[$i]['delete'];
            echo '</td>';
            echo '</tr>';
        }

        // dump array entries for files
        $files = count($files_array);
        sort($files_array); // sorting the array alphabetically (Thanks pxl8r!)
        for ($i = 0; $i < $files; $i++) {
            $filesizes += $files_array[$i]['stats']['7'];
            echo '<tr ' . markRow($files_array[$i]['file'], get_by_key($_REQUEST, 'path'), get_by_key($_REQUEST, 'mode')) . '>';
            echo '<td>' . $files_array[$i]['text'] . '</td>';
            echo '<td class="text-nowrap">' . evo()->toDateFormat($files_array[$i]['stats']['9']) . '</td>';
            echo '<td class="text-right">' . niceSize($files_array[$i]['stats']['7']) . '</td>';
            echo '<td class="actions text-right">';
            echo $files_array[$i]['unzip'];
            echo $files_array[$i]['view'];
            echo $files_array[$i]['edit'];
            echo $files_array[$i]['duplicate'];
            echo $files_array[$i]['rename'];
            echo $files_array[$i]['groups'] ?? '';
            echo $files_array[$i]['delete'];
            echo '</td>';
            echo '</tr>';
        }

        // links last, listed but inert
        $links = count($links_array);
        usort($links_array, static fn ($a, $b) => strcmp($a['name'], $b['name']));
        foreach ($links_array as $link) {
            echo '<tr>';
            echo '<td>' . $link['text'] . '</td>';
            echo '<td class="text-nowrap">' . evo()->toDateFormat($link['stats'][9]) . '</td>';
            echo '<td class="text-right" title="' . e($_lang['files_link']) . '">' . e($_lang['files_link']) . '</td>';
            echo '<td class="actions text-right">' . $link['delete'] . '</td>';
            echo '</tr>';
        }

        return compact('filesizes', 'files', 'folders', 'links');
    }
}

if(!function_exists('removeLastPath')) {
    /**
     * @param string $string
     * @return bool|string
     */
    function removeLastPath($string)
    {
        $pos = strrpos($string, '/');
        if ($pos !== false) {
            $path = substr($string, 0, $pos);
        } else {
            $path = false;
        }

        return $path;
    }
}

if(!function_exists('getExtension')) {
    /**
     * @param string $string
     * @return bool|string
     *
     * @TODO: not work if $string contains folder name with dot
     */
    function getExtension($string)
    {
        $pos = strrpos($string, '.');
        if ($pos !== false) {
            $ext = substr($string, $pos);
            $ext = strtolower($ext);
        } else {
            $ext = false;
        }

        return $ext;
    }
}

if(!function_exists('checkExtension')) {
    /**
     * @param string $path
     * @return bool
     */
    function checkExtension($path = '')
    {
        if (fileManagerIsReservedDeviceName($path)) {
            fileManagerLogDenied(null, basename(str_replace('\\', '/', (string) $path)), 'reserved device name');

            return false;
        }

        // not a matter of the lists below: only whoever can store PHP anyway may add executable files
        if (fileManagerIsExecutableName($path) && !fileManagerMayRunCode()) {
            fileManagerLogDenied(null, basename(str_replace('\\', '/', (string) $path)), 'executable name');

            return false;
        }

        $upload_files = explode(',', evolutionCMS()->getConfig('upload_files', ''));
        $upload_images = explode(',', evolutionCMS()->getConfig('upload_images', ''));
        $upload_media = explode(',', evolutionCMS()->getConfig('upload_media', ''));
        // now merge them
        $uploadablefiles = array_merge($upload_files, $upload_images, $upload_media);
        $uploadablefiles = add_dot($uploadablefiles);

        if (in_array(getExtension($path), $uploadablefiles)) {
            return true;
        } else {
            return false;
        }
    }
}

if(!function_exists('mkdirs')) {
    /**
     * recursive mkdir function
     *
     * @param string $strPath
     * @param int $mode
     * @return bool
     */
    function mkdirs($strPath, $mode)
    {
        if (is_dir($strPath)) {
            return true;
        }
        $pStrPath = dirname($strPath);
        if (!mkdirs($pStrPath, $mode)) {
            return false;
        }

        return @mkdir($strPath);
    }
}

if(!function_exists('logFileChange')) {
    /**
     * @param string $type
     * @param string $filename
     */
    function logFileChange($type, $filename)
    {
        //global $_lang;

        $log = new EvolutionCMS\Legacy\LogHandler();

        switch ($type) {
            case 'upload':
                $string = 'Uploaded File';
                break;
            case 'delete':
                $string = 'Deleted File';
                break;
            case 'modify':
                $string = 'Modified File';
                break;
            default:
                $string = 'Viewing File';
                break;
        }

        $string = sprintf($string, $filename);
        $log->initAndWriteLog($string, '', '', '', $type, $filename);

        // HACK: change the global action to prevent double logging
        // @see index.php @ 915
        global $action;
        $action = 1;
    }
}

if(!function_exists('unzip')) {
    /**
     * by patrick_allaert - php user notes
     *
     * @param string $file
     * @param string $path
     * @return bool|int
     */
    function unzip($file, $path)
    {
        global $newfolderaccessmode, $token_check;

        if (!$token_check) {
            return false;
        }

        // added by Raymond
        if (!extension_loaded('zip')) {
            return 0;
        }
        // end mod

        $old_umask = umask(0);
        $result = fileManagerExtractZip(
            $file,
            $path,
            fileManagerProtectedPaths(),
            $newfolderaccessmode ?: 0777,
            fileManagerZipWriteGuard(rtrim(str_replace('\\', '/',
                (string) realpath(evolutionCMS()->getConfig('filemanager_path', EVO_BASE_PATH))), '/'),
                rtrim(str_replace('\\', '/', (string) realpath($path)), '/'))
        );
        umask($old_umask);

        return $result;
    }
}

if(!function_exists('rrmdir')) {
    /**
     * Deletes $dir and everything in it. Symlinks are removed as links: the walk never enters
     * them, so a link to a folder elsewhere cannot take that folder's content with it.
     *
     * @param string $dir
     * @return bool
     */
    function rrmdir($dir)
    {
        $dir = rtrim(str_replace('\\', '/', $dir), '/');
        if (fileManagerIsLink($dir)) {
            return fileManagerRemoveLink($dir);
        }
        $items = @scandir($dir);
        if ($items === false) {
            return false;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $file = $dir . '/' . $item;
            if (fileManagerIsLink($file)) {
                fileManagerRemoveLink($file);
            } elseif (is_dir($file)) {
                rrmdir($file);
            } else {
                @unlink($file);
            }
        }

        return @rmdir($dir);
    }
}

if(!function_exists('fileupload')) {
    /**
     * @return string
     */
    function fileupload()
    {
        global $_lang, $uploadablefiles;

        $modx = evolutionCMS();
        $filemanager_path = rtrim(str_replace('\\', '/', realpath(evolutionCMS()
            ->getConfig('filemanager_path', EVO_BASE_PATH))), '/'); // Canonicalize base path
        $requested_path = ltrim($_REQUEST['path'] ?? '', '/');
        $resolvedStartPath = fileManagerResolvePath($filemanager_path, $requested_path);
        if ($resolvedStartPath === null) {
            return '<p><span class="warning">Invalid path.</span></p>';
        }
        $startpath = $resolvedStartPath['path'];
        $startpath = rtrim($startpath, '/');
        // Ensure startpath is within filemanager_path
        if (!FileManagerAccess::isWithin($filemanager_path, $startpath) || !is_dir($startpath)) {
            return '<p><span class="warning">Invalid path.</span></p>';
        }
        $dirRel = ltrim(substr($startpath, strlen($filemanager_path)), '/');
        if (!fileManagerIsAccessible($dirRel)
            || fileManagerPathIsProtected($startpath, fileManagerProtectedPaths())
            || !is_writable($startpath)) {
            fileManagerLogDenied('upload', $dirRel);

            return '<p><span class="warning">' . $_lang['files_access_denied'] . '</span></p>';
        }
        $new_file_permissions = octdec(evolutionCMS()->getConfig('new_file_permissions', '0666'));
        $msg = '';
        $dirGroupIds = [];
        if (evolutionCMS()->getConfig('use_udperms')) {
            $dirGroupIds = fileManagerEffectiveGroupIds($dirRel);
        }
        $inheritInserts = [];
        foreach ($_FILES['userfile']['name'] as $i => $name) {
            if (empty($_FILES['userfile']['tmp_name'][$i])) {
                continue;
            }
            $userfile = [];

            $userfile['tmp_name'] = $_FILES['userfile']['tmp_name'][$i];
            $userfile['error'] = $_FILES['userfile']['error'][$i];
            $name = $_FILES['userfile']['name'][$i];
            if ($modx->getConfig('clean_uploaded_filename') == 1) {
                $nameparts = explode('.', $name);
                $nameparts = array_map([
                    $modx,
                    'stripAlias'
                ], $nameparts, ['file_manager']);
                $name = implode('.', $nameparts);
            }
            // Sanitize name to prevent traversal or invalid chars
            $name = preg_replace('/[^\w\.-]/', '', $name);
            $name = ltrim($name, '.');
            $userfile['name'] = $name;
            $userfile['type'] = $_FILES['userfile']['type'][$i];

            // this seems to be an upload action.
            $rel_path = ltrim(substr($startpath, strlen($filemanager_path)), '/');
            $path = EVO_SITE_URL . ($rel_path ? $rel_path . '/' : '') . $userfile['name'];
            $msg .= htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
            if ($userfile['error'] == 0) {
                $img = (strpos($userfile['type'],'image') !== false) ? '<br /><img src="'
                    . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '" height="75" />' : '';
                $msg .= "<p>" . $_lang['files_file_type'] . htmlspecialchars($userfile['type'], ENT_QUOTES,
                        'UTF-8') . ", " . niceSize(filesize($userfile['tmp_name'])) . $img . '</p>';
            }

            $userfilename = $userfile['tmp_name'];

            if (is_uploaded_file($userfilename)) {
                // file is uploaded file, process it!
                if (!checkExtension($userfile['name'])) {
                    $msg .= '<p><span class="warning">' . $_lang['files_filetype_notok'] . '</span></p>';
                } else {
                    $targetFile = $startpath . '/' . $userfile['name'];
                    // a replaced file is a modified file: its own restrictions apply, not just the folder's.
                    // The restrictions are keyed by the stored name: on Windows secret.txt is Secret.txt
                    $existingRel = ltrim(substr(fileManagerCanonicalCase($targetFile), strlen($filemanager_path)), '/');
                    $replacing = file_exists($targetFile);
                    if ($replacing && !fileManagerCanModifyExistingPath($existingRel)) {
                        fileManagerLogDenied('upload over existing file', $existingRel, 'file group');
                        $msg .= '<p><span class="warning">' . $_lang['files_access_denied'] . '</span></p>';
                    } elseif (@move_uploaded_file($userfile['tmp_name'], $targetFile)) {
                        // Ryan: Repair broken permissions issue with file manager
                        if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
                            @chmod($targetFile, $new_file_permissions);
                        }
                        // Ryan: End
                        $msg .= '<p><span class="success">' . $_lang['files_upload_ok'] . '</span></p><hr/>';

                        // invoke OnFileManagerUpload event
                        $modx->invokeEvent('OnFileManagerUpload', [
                            'filepath' => $startpath,
                            'filename' => $userfile['name']
                        ]);
                        // Log the change
                        logFileChange('upload', $targetFile);
                        // Inherit groups from parent directory. A replaced file keeps the restrictions it
                        // had: giving it the folder's groups would let that wider group read it
                        if (!$replacing && !empty($dirGroupIds)) {
                            $fileRel = fileManagerAclKey(ltrim(substr($targetFile, strlen($filemanager_path)), '/'));
                            foreach ($fileRel === null ? [] : $dirGroupIds as $gid) {
                                $inheritInserts[] = ['document_group' => $gid, 'file' => $fileRel];
                            }
                        }
                    } else {
                        $msg .= '<p><span class="warning">' . $_lang['files_upload_copyfailed'] . '</span> '
                            . $_lang["files_upload_permissions_error"] . '</p>';
                    }
                }
            } else {
                $msg .= '<br /><span class="warning"><b>' . $_lang['files_upload_error'] . ':</b>';
                switch ($userfile['error']) {
                    case 0: //no error; possible file attack!
                        $msg .= $_lang['files_upload_error0'];
                        break;
                    case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
                        $msg .= $_lang['files_upload_error1'];
                        break;
                    case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
                        $msg .= $_lang['files_upload_error2'];
                        break;
                    case 3: //uploaded file was only partially uploaded
                        $msg .= $_lang['files_upload_error3'];
                        break;
                    case 4: //no file was uploaded
                        $msg .= $_lang['files_upload_error4'];
                        break;
                    default: //a default error, just in case!  :)
                        $msg .= $_lang['files_upload_error5'];
                        break;
                }
                $msg .= '</span><br />';
            }
        }

        if (!empty($inheritInserts)) {
            \EvolutionCMS\Models\FileGroup::query()->insertOrIgnore($inheritInserts);
        }

        return $msg . '<br/>';
    }
}

if(!function_exists('textsave')) {
    /**
     * @return string
     */
    function textsave()
    {
        global $_lang;

        $filemanager_path = rtrim(str_replace('\\', '/', realpath(evolutionCMS()
            ->getConfig('filemanager_path', EVO_BASE_PATH))), '/');
        $requested_path = ltrim($_POST['path'] ?? '', '/');
        $requested = $filemanager_path . '/' . $requested_path;
        if (fileManagerPathContainsLink($filemanager_path, $requested)) {
            return '<span class="warning"><b>Invalid path.</b></span><br /><br />';
        }
        $filename = str_replace('\\', '/', realpath($requested));
        if (!FileManagerAccess::isWithin($filemanager_path, $filename) || !is_file($filename)) {
            return '<span class="warning"><b>Invalid path.</b></span><br /><br />';
        }
        $fileRel = ltrim(substr($filename, strlen($filemanager_path)), '/');
        if (!fileManagerCanModifyExistingPath($fileRel)
            || fileManagerPathIsProtected($filename, fileManagerProtectedPaths())
            || (fileManagerIsExecutableName($filename) && !fileManagerMayRunCode())
            || !is_writable($filename)) {
            return fileManagerDenied();
        }
        $content = $_POST['content'];

        // Write $content to our opened file.
        if (file_put_contents($filename, $content) === false) {
            $msg = '<span class="warning"><b>' . $_lang['file_not_saved'] . '</b></span><br /><br />';
        } else {
            $msg = '<span class="success"><b>' . $_lang['file_saved'] . '</b></span><br /><br />';
            $_REQUEST['mode'] = 'edit';
        }
        // Log the change
        logFileChange('modify', $filename);

        return $msg;
    }
}

if(!function_exists('delete_file')) {
    /**
     * @return string
     */
    function delete_file()
    {
        global $_lang;

        $filemanager_path = rtrim(str_replace('\\', '/', realpath(evolutionCMS()
            ->getConfig('filemanager_path', EVO_BASE_PATH))), '/');
        $requested_path = ltrim($_REQUEST['path'] ?? '', '/');
        $requested = $filemanager_path . '/' . $requested_path;
        if (fileManagerPathContainsLink($filemanager_path, $requested)) {
            return '<span class="warning"><b>Invalid path.</b></span><br /><br />';
        }
        $file = str_replace('\\', '/', realpath($requested));
        if (!FileManagerAccess::isWithin($filemanager_path, $file) || !is_file($file)) {
            return '<span class="warning"><b>Invalid path.</b></span><br /><br />';
        }
        $fileRel = ltrim(substr($file, strlen($filemanager_path)), '/');
        if (!fileManagerCanModifyExistingPath($fileRel)
            || fileManagerPathIsProtected($file, fileManagerProtectedPaths())
            || !is_writable($file)) {
            return fileManagerDenied();
        }
        if (($denied = fileManagerCodeRemovalDenied($file, $fileRel)) !== null) {
            return $denied;
        }
        // the path below the root, never the server's absolute one
        $msg = sprintf($_lang['deleting_file'], htmlspecialchars($fileRel, ENT_QUOTES, 'UTF-8'));

        if (!evolutionCMS()->hasPermission('file_manager') || !@unlink($file)) {
            $msg .= '<span class="warning"><b>' . $_lang['file_not_deleted'] . '</b></span><br /><br />';
        } else {
            $msg .= '<span class="success"><b>' . $_lang['file_deleted'] . '</b></span><br /><br />';
            \EvolutionCMS\Support\FileManagerAccess::forgetRestrictions(fileManagerAclKey($fileRel));
        }

        // Log the change
        logFileChange('delete', $file);

        return $msg;
    }
}

if(!function_exists('parsePlaceholder')) {
    /**
     * @param string $tpl
     * @param array $ph
     * @return string
     */
    function parsePlaceholder($tpl, $ph)
    {
        foreach ($ph as $k => $v) {
            $k = "[+{$k}+]";
            $tpl = str_replace($k, $v, $tpl);
        }

        return $tpl;
    }
}

if(!function_exists('checkToken')) {
    /**
     * @return bool
     */
    function checkToken()
    {
        if (isset($_POST['token']) && !empty($_POST['token'])) {
            $token = $_POST['token'];
        } elseif (isset($_GET['token']) && !empty($_GET['token'])) {
            $token = $_GET['token'];
        } else {
            $token = false;
        }

        if (is_string($token) && isset($_SESSION['token']) && !empty($_SESSION['token']) && hash_equals($_SESSION['token'], $token)) {
            $rs = true;
        } else {
            $rs = false;
        }
        $_SESSION['token'] = '';

        return $rs;
    }
}

if(!function_exists('makeToken')) {
    /**
     * @return string
     */
    function makeToken()
    {
        $newToken = bin2hex(random_bytes(16)); // uniqid() is clock-derived, not random
        $_SESSION['token'] = $newToken;

        return $newToken;
    }
}
