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
     * Resolve a requested path under the file manager root. The ACL is keyed by the resolved
     * path, so callers must check the returned 'relative', never the raw request: own/../private
     * names private, not something below own.
     *
     * @param string $filemanagerPath canonical root, no trailing slash
     * @param string $requestedPath path relative to the root, as requested
     * @return array{path: string, relative: string}|null null when it does not exist or leaves the root
     */
    function fileManagerResolvePath($filemanagerPath, $requestedPath)
    {
        $path = realpath($filemanagerPath . '/' . ltrim((string) $requestedPath, '/'));
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
     * Folders the file manager must not enter or change: always the manager and the backups,
     * and each element folder unless the user may edit that element type anyway.
     *
     * @return string[] canonical absolute paths, no trailing slash
     */
    function fileManagerProtectedPaths()
    {
        $evo = evolutionCMS();
        $paths = [
            EVO_MANAGER_PATH,
            EVO_BASE_PATH . 'temp/backup',
            EVO_BASE_PATH . 'assets/backup',
        ];
        $byPermission = [
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

        return array_map(
            static fn ($path) => rtrim(str_replace('\\', '/', realpath($path) ?: $path), '/'),
            $paths
        );
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
        return fileManagerIsSafeWriteTarget($root, $target) && !file_exists($target) && !fileManagerIsLink($target);
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

        return $sourceReal !== false && $sourceReal === realpath($target);
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

if(!function_exists('fileManagerExtractZip')) {
    /**
     * Extracts $file into $path, skipping any entry that would land outside it, go through a
     * symlink, reach a protected folder, or be a server-executable file.
     *
     * @param string $file
     * @param string $path
     * @param string[] $protectedPaths
     * @param int $dirMode
     * @return bool false when the archive cannot be opened
     */
    function fileManagerExtractZip($file, $path, array $protectedPaths = [], $dirMode = 0777)
    {
        $root = realpath($path);
        if ($root === false) {
            return false;
        }
        $root = rtrim(str_replace('\\', '/', $root), '/');

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            return false;
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $filename = str_replace('\\', '/', $stat['name']);
            if (substr($filename, 0, 1) == '/' || strpos($filename, ':') !== false) {
                continue; // skip absolute paths
            }
            // only a whole ".." segment climbs up: "just-stop..png" is an ordinary file name
            // (Windows hides the extension, so "just-stop." easily becomes one)
            $segments = array_values(array_filter(
                explode('/', $filename),
                static fn ($segment) => $segment !== '' && $segment !== '.'
            ));
            if ($segments === [] || in_array('..', $segments, true)) {
                continue;
            }
            $isDir = substr($filename, -1) == '/';
            $target = $root . '/' . implode('/', $segments);
            if (!fileManagerIsSafeWriteTarget($root, $target) || fileManagerPathIsProtected($target, $protectedPaths)) {
                continue;
            }
            // the upload form refuses these; unpacking must not be the way around it
            if (!$isDir && fileManagerIsExecutableName(end($segments))) {
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
            file_put_contents($target, $zip->getFromIndex($i));
        }
        $zip->close();

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
                    ? '<a href="javascript:;" onclick="viewfile(\'../' . addslashes($rel_web) . '\');"><i class="' . $_style['icon_eye'] . '" title="'
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
            echo '<span class="disabled"><i class="' . $_style['icon_clone'] . '"></i></span>';
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

        return compact('filesizes', 'files', 'folders');
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
        $result = fileManagerExtractZip($file, $path, fileManagerProtectedPaths(), $newfolderaccessmode ?: 0777);
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
        $startpath = str_replace('\\', '/', realpath($filemanager_path . '/' . $requested_path));
        $startpath = rtrim($startpath, '/');
        // Ensure startpath is within filemanager_path
        if (!FileManagerAccess::isWithin($filemanager_path, $startpath) || !is_dir($startpath)) {
            return '<p><span class="warning">Invalid path.</span></p>';
        }
        $dirRel = ltrim(substr($startpath, strlen($filemanager_path)), '/');
        if (!fileManagerIsAccessible($dirRel)
            || fileManagerPathIsProtected($startpath, fileManagerProtectedPaths())
            || !is_writable($startpath)) {
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
                    if (@move_uploaded_file($userfile['tmp_name'], $targetFile)) {
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
                        // Inherit groups from parent directory
                        if (!empty($dirGroupIds)) {
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
        $filename = str_replace('\\', '/', realpath($filemanager_path . '/' . $requested_path));
        if (!FileManagerAccess::isWithin($filemanager_path, $filename) || !is_file($filename)) {
            return '<span class="warning"><b>Invalid path.</b></span><br /><br />';
        }
        $fileRel = ltrim(substr($filename, strlen($filemanager_path)), '/');
        if (!fileManagerCanModifyExistingPath($fileRel)
            || fileManagerPathIsProtected($filename, fileManagerProtectedPaths())
            || !is_writable($filename)) {
            return '<span class="warning"><b>' . $_lang['files_access_denied'] . '</b></span><br /><br />';
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
        $file = str_replace('\\', '/', realpath($filemanager_path . '/' . $requested_path));
        if (!FileManagerAccess::isWithin($filemanager_path, $file) || !is_file($file)) {
            return '<span class="warning"><b>Invalid path.</b></span><br /><br />';
        }
        $fileRel = ltrim(substr($file, strlen($filemanager_path)), '/');
        if (!fileManagerCanModifyExistingPath($fileRel)
            || fileManagerPathIsProtected($file, fileManagerProtectedPaths())
            || !is_writable($file)) {
            return '<span class="warning"><b>' . $_lang['files_access_denied'] . '</b></span><br /><br />';
        }
        $msg = sprintf($_lang['deleting_file'], str_replace('\\', '/', $file));

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
