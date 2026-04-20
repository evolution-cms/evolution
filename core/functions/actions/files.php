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

if(!function_exists('fileManagerRestrictionMap')) {
    /**
     * @param string[] $relativePaths
     * @return array<string, int[]>
     */
    function fileManagerRestrictionMap(array $relativePaths)
    {
        if (!evolutionCMS()->getConfig('use_udperms')) {
            return [];
        }

        if (isset($_SESSION['mgrRole']) && (int)$_SESSION['mgrRole'] === 1) {
            return [];
        }

        return FileManagerAccess::loadRestrictions($relativePaths);
    }
}

if(!function_exists('fileManagerIsAccessible')) {
    /**
     * @param string $relativePath
     * @param int[]|null $userGroups
     * @param array<string, int[]>|null $restrictionMap
     * @return bool
     */
    function fileManagerIsAccessible($relativePath, ?array $userGroups = null, ?array $restrictionMap = null)
    {
        if (!evolutionCMS()->getConfig('use_udperms')) {
            return true;
        }

        if (isset($_SESSION['mgrRole']) && (int)$_SESSION['mgrRole'] === 1) {
            return true;
        }

        return FileManagerAccess::isAccessible(
            $relativePath,
            $userGroups ?? fileManagerUserGroupIds(),
            $restrictionMap ?? fileManagerRestrictionMap([$relativePath])
        );
    }
}

if(!function_exists('fileManagerCanModifyExistingPath')) {
    /**
     * @param string $relativePath
     * @param int[]|null $userGroups
     * @param array<string, int[]>|null $restrictionMap
     * @return bool
     */
    function fileManagerCanModifyExistingPath($relativePath, ?array $userGroups = null, ?array $restrictionMap = null)
    {
        if (!evolutionCMS()->getConfig('use_udperms')) {
            return true;
        }

        if (isset($_SESSION['mgrRole']) && (int)$_SESSION['mgrRole'] === 1) {
            return true;
        }

        return FileManagerAccess::canModifyExistingPath(
            $relativePath,
            $userGroups ?? fileManagerUserGroupIds(),
            $restrictionMap ?? fileManagerRestrictionMap([$relativePath])
        );
    }
}

if(!function_exists('fileManagerEffectiveGroupIds')) {
    /**
     * @param string $relativePath
     * @param array<string, int[]>|null $restrictionMap
     * @return int[]
     */
    function fileManagerEffectiveGroupIds($relativePath, ?array $restrictionMap = null)
    {
        return FileManagerAccess::effectiveGroupIds(
            $relativePath,
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
                    . (!empty($fileGroupsMap[$rel_newpath]) ? $_style['icon_lock'] : $_style['icon_unlock'])
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
                    . (!empty($fileGroupsMap[$rel_newpath]) ? $_style['icon_lock'] : $_style['icon_unlock'])
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
        $path = rtrim(str_replace('\\', '/', realpath($path)), '/\\'); // No trailing slash

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            umask($old_umask);
            return false;
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $filename = str_replace('\\', '/', $stat['name']);
            if (substr($filename, 0, 1) == '/' || strpos($filename, '..') !== false ||
                strpos($filename, ':') !== false) {
                continue; // skip malicious paths
            }
            $target = $path . '/' . $filename;
            // Additional check to ensure target is within path
            $target_dir = rtrim(str_replace('\\', '/', realpath(dirname($target)) ?: dirname($target)), '/\\');
            if (strpos($target_dir, $path) !== 0) {
                continue;
            }
            if (substr($filename, -1) == '/') {
                if (!is_dir($target)) {
                    mkdir($target, $newfolderaccessmode ?: 0777, true);
                }
            } else {
                $dirname = dirname($target);
                if (!is_dir($dirname)) {
                    mkdir($dirname, $newfolderaccessmode ?: 0777, true);
                }
                file_put_contents($target, $zip->getFromIndex($i));
            }
        }
        $zip->close();
        umask($old_umask);
        return true;
    }
}

if(!function_exists('rrmdir')) {
    /**
     * @param string $dir
     * @return bool
     */
    function rrmdir($dir)
    {
        $dir = str_replace('\\', '/', realpath($dir)); // Canonicalize path
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                rrmdir($file);
            } else {
                unlink($file);
            }
        }

        return rmdir($dir);
    }
}

if(!function_exists('fileupload')) {
    /**
     * @return string
     */
    function fileupload()
    {
        $modx = evolutionCMS();
        $filemanager_path = rtrim(str_replace('\\', '/', realpath(evolutionCMS()
            ->getConfig('filemanager_path', EVO_BASE_PATH))), '/'); // Canonicalize base path
        $requested_path = ltrim($_REQUEST['path'] ?? '', '/');
        $startpath = str_replace('\\', '/', realpath($filemanager_path . '/' . $requested_path));
        $startpath = rtrim($startpath, '/');
        // Ensure startpath is within filemanager_path
        if (strpos($startpath, $filemanager_path) !== 0 || !is_dir($startpath)) {
            return '<p><span class="warning">Invalid path.</span></p>';
        }
        $dirRel = ltrim(substr($startpath, strlen($filemanager_path)), '/');
        if (!fileManagerIsAccessible($dirRel) || !is_writable($startpath)) {
            return '<p><span class="warning">' . $_lang['files_access_denied'] . '</span></p>';
        }
        $new_file_permissions = octdec(evolutionCMS()->getConfig('new_file_permissions', '0666'));
        global $_lang, $uploadablefiles;
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
                            $fileRel = ltrim(substr($targetFile, strlen($filemanager_path)), '/');
                            foreach ($dirGroupIds as $gid) {
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
        if (strpos($filename, $filemanager_path) !== 0 || !is_file($filename)) {
            return '<span class="warning"><b>Invalid path.</b></span><br /><br />';
        }
        $fileRel = ltrim(substr($filename, strlen($filemanager_path)), '/');
        if (!fileManagerCanModifyExistingPath($fileRel) || !is_writable($filename)) {
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
        if (strpos($file, $filemanager_path) !== 0 || !is_file($file)) {
            return '<span class="warning"><b>Invalid path.</b></span><br /><br />';
        }
        $fileRel = ltrim(substr($file, strlen($filemanager_path)), '/');
        if (!fileManagerCanModifyExistingPath($fileRel) || !is_writable($file)) {
            return '<span class="warning"><b>' . $_lang['files_access_denied'] . '</b></span><br /><br />';
        }
        $msg = sprintf($_lang['deleting_file'], str_replace('\\', '/', $file));

        if (!evolutionCMS()->hasPermission('file_manager') || !@unlink($file)) {
            $msg .= '<span class="warning"><b>' . $_lang['file_not_deleted'] . '</b></span><br /><br />';
        } else {
            $msg .= '<span class="success"><b>' . $_lang['file_deleted'] . '</b></span><br /><br />';
            if (evolutionCMS()->getConfig('use_udperms')) {
                \EvolutionCMS\Models\FileGroup::query()->where('file', $fileRel)->delete();
            }
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

        if (isset($_SESSION['token']) && !empty($_SESSION['token']) && $_SESSION['token'] === $token) {
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
        $newToken = uniqid('', true);
        $_SESSION['token'] = $newToken;

        return $newToken;
    }
}
