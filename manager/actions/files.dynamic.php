<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!evo()->hasPermission('file_manager')) {
    evo()->webAlertAndQuit($_lang["error_no_privileges"]);
}
$token_check = checkToken();
$newToken = makeToken();
// settings
$theme_image_path = EVO_MANAGER_URL . 'media/style/' . evo()->getConfig('manager_theme') . '/images/';
$excludes = [
    '.',
    '..',
    '.svn',
    '.git',
    '.idea'
];
$alias_suffix = (!empty($friendly_url_suffix)) ? ',' . ltrim($friendly_url_suffix, '.') : '';
$editablefiles = explode(',', 'txt,php,tpl,less,sass,scss,shtml,html,htm,xml,js,css,pageCache,htaccess,json,ini' . $alias_suffix);
$inlineviewablefiles = explode(',', 'log,txt,php,tpl,less,sass,scss,html,htm,xml,js,css,pageCache,htaccess,json,ini' . $alias_suffix);
$viewablefiles = explode(',', 'jpg,gif,png,ico');
$editablefiles = add_dot($editablefiles);
$inlineviewablefiles = add_dot($inlineviewablefiles);
$viewablefiles = add_dot($viewablefiles);
$protected_path = [];
/* jp only if($_SESSION['mgrRole']!=1) { */
$protected_path[] = str_replace('\\', '/', EVO_MANAGER_PATH);
$protected_path[] = str_replace('\\', '/', EVO_BASE_PATH . 'temp/backup');
$protected_path[] = str_replace('\\', '/', EVO_BASE_PATH . 'assets/backup');
if (!evo()->hasPermission('save_plugin')) {
    $protected_path[] = str_replace('\\', '/', EVO_BASE_PATH . 'assets/plugins');
}
if (!evo()->hasPermission('save_snippet')) {
    $protected_path[] = str_replace('\\', '/', EVO_BASE_PATH . 'assets/snippets');
}
if (!evo()->hasPermission('save_template')) {
    $protected_path[] = str_replace('\\', '/', EVO_BASE_PATH . 'assets/templates');
}
if (!evo()->hasPermission('save_module')) {
    $protected_path[] = str_replace('\\', '/', EVO_BASE_PATH . 'assets/modules');
}
if (!evo()->hasPermission('empty_cache')) {
    $protected_path[] = str_replace('\\', '/', EVO_BASE_PATH . 'assets/cache');
}
if (!evo()->hasPermission('import_static')) {
    $protected_path[] = str_replace('\\', '/', EVO_BASE_PATH . 'temp/import');
    $protected_path[] = str_replace('\\', '/', EVO_BASE_PATH . 'assets/import');
}
if (!evo()->hasPermission('export_static')) {
    $protected_path[] = str_replace('\\', '/', EVO_BASE_PATH . 'temp/export');
    $protected_path[] = str_replace('\\', '/', EVO_BASE_PATH . 'assets/export');
}
/* } */
// Mod added by Raymond
$enablefileunzip = true;
$enablefiledownload = true;
$newfolderaccessmode = octdec(evo()->getConfig('new_folder_permissions', '0777'));
$new_file_permissions = octdec(evo()->getConfig('new_file_permissions', '0666'));
// End Mod - by Raymond
// make arrays from the file upload settings
$upload_files = explode(',', evo()->getConfig('upload_files', ''));
$upload_images = explode(',', evo()->getConfig('upload_images', ''));
$upload_media = explode(',', evo()->getConfig('upload_media', ''));
// now merge them
$uploadablefiles = array_merge($upload_files, $upload_images, $upload_media);
$uploadablefiles = add_dot($uploadablefiles);
$upload_maxsize = evo()->getConfig('upload_maxsize');
$filemanager_path = rtrim(str_replace('\\', '/',
    realpath(evo()->getConfig('filemanager_path')) ?: realpath(EVO_BASE_PATH)), '/');
$base_path = rtrim(str_replace('\\', '/', realpath(EVO_BASE_PATH)), '/');
// end settings
// get the current work directory
$requested_path = ltrim(isset($_REQUEST['path']) ? $_REQUEST['path'] : '', '/');
$fullpath = str_replace('\\', '/', realpath($filemanager_path . '/' . $requested_path));
$selFile = '';
if (is_file($fullpath)) {
    $selFile = $requested_path;
    $startpath = rtrim(str_replace('\\', '/', realpath(dirname($fullpath))), '/');
} elseif (is_dir($fullpath)) {
    $startpath = $fullpath;
} else {
    $startpath = $filemanager_path;
}
if ($startpath === false || strpos($startpath, $filemanager_path) !== 0 || !is_readable($startpath)) {
    evo()->webAlertAndQuit($_lang["files_access_denied"]);
}
// Raymond: get web start path for showing pictures
$relative_path = ltrim(substr($startpath, strlen($filemanager_path)), '/');

// Resource Groups support for files
$fileAccessEnabled = evo()->getConfig('use_udperms');
$showFileGroups = $fileAccessEnabled && evo()->hasAnyPermissions(['manage_groups', 'manage_document_permissions']);
$allDocGroups = [];
$userGroups = fileManagerUserGroupIds();
$fileGroupsMap = [];
if ($fileAccessEnabled) {
    $scanPaths = [$relative_path];
    if ($requested_path !== '') {
        $acc = '';
        foreach (explode('/', $requested_path) as $part) {
            $acc = $acc !== '' ? $acc . '/' . $part : $part;
            $scanPaths[] = $acc;
        }
    }
    $scanItems = @scandir($startpath);
    if ($scanItems) {
        foreach ($scanItems as $scanItem) {
            if ($scanItem === '.' || $scanItem === '..') {
                continue;
            }
            $scanPaths[] = ltrim(substr(str_replace('\\', '/', $startpath . '/' . $scanItem), strlen($filemanager_path)), '/');
        }
    }
    $fileGroupsMap = fileManagerRestrictionMap(array_unique(array_filter($scanPaths, static fn ($path) => $path !== null)));
}
if ($relative_path !== '' && !fileManagerIsAccessible($relative_path, $userGroups, $fileGroupsMap)) {
    evo()->webAlertAndQuit($_lang["files_access_denied"]);
}
if ($showFileGroups) {
    $allDocGroups = \EvolutionCMS\Models\DocumentgroupName::orderBy('name')->get();
}
$currentPathWritable = is_writable($startpath) && fileManagerIsAccessible($relative_path, $userGroups, $fileGroupsMap);

if (!function_exists('fileManagerDirectoryZipPaths')) {
    function fileManagerDirectoryZipPaths(string $filemanagerPath, string $relativePath): array
    {
        $hash = sha1(rtrim(str_replace('\\', '/', EVO_BASE_PATH), '/') . '|' . rtrim($filemanagerPath, '/') . '|' . trim($relativePath, '/'));
        $base = rtrim(str_replace('\\', '/', sys_get_temp_dir()), '/') . '/evo-file-manager-directory-' . $hash;

        return [
            'lock' => $base . '.lock',
            'zip' => $base . '.zip',
        ];
    }
}

if (!function_exists('fileManagerDirectoryZipName')) {
    function fileManagerDirectoryZipName(string $filemanagerPath, string $relativePath): string
    {
        $name = basename($relativePath !== '' ? $relativePath : $filemanagerPath);
        $name = preg_replace('/[^\w.-]+/', '-', $name);
        $name = trim($name, '.-');

        return ($name !== '' ? $name : 'files') . '.zip';
    }
}

if (!function_exists('fileManagerDirectoryZipExists')) {
    function fileManagerDirectoryZipExists(array $zipPaths): bool
    {
        return is_file($zipPaths['lock']) || is_file($zipPaths['zip']);
    }
}

if (!function_exists('fileManagerPathIsProtected')) {
    function fileManagerPathIsProtected(string $path, array $protectedPaths): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        foreach ($protectedPaths as $protectedPath) {
            $protectedPath = rtrim(str_replace('\\', '/', $protectedPath), '/');
            if ($path === $protectedPath || strpos($path, $protectedPath . '/') === 0) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('fileManagerDeleteDirectoryZip')) {
    function fileManagerDeleteDirectoryZip(array $zipPaths): void
    {
        foreach ([$zipPaths['zip'], $zipPaths['lock']] as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}

if (!function_exists('fileManagerCreateDirectoryZip')) {
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
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $itemPath = str_replace('\\', '/', $item->getPathname());
            $realItemPath = str_replace('\\', '/', realpath($itemPath) ?: $itemPath);
            if (strpos($realItemPath, $sourcePath . '/') !== 0 || !$item->isReadable()) {
                continue;
            }
            if (fileManagerPathIsProtected($realItemPath, $protectedPaths)) {
                continue;
            }

            $itemRelPath = ltrim(substr($realItemPath, strlen($filemanagerPath)), '/');
            if (!fileManagerIsAccessible($itemRelPath, $userGroups, $fileGroupsMap)) {
                continue;
            }

            $archivePath = $archiveRoot . '/' . ltrim(substr($realItemPath, strlen($sourcePath)), '/');
            if ($item->isDir()) {
                $zip->addEmptyDir($archivePath);
            } elseif ($item->isFile()) {
                $zip->addFile($realItemPath, $archivePath);
            }
        }

        return $zip->close();
    }
}

$directoryZipPaths = fileManagerDirectoryZipPaths($filemanager_path, $relative_path);
$directoryZipMessage = '';
$directoryZipBlocked = evo()->getConfig('denyZipDownload')
    || fileManagerPathIsProtected($startpath, $protected_path)
    || !class_exists('ZipArchive')
    || !is_readable($startpath);
if (get_by_key($_REQUEST, 'mode') == 'deletezip') {
    if ($token_check) {
        fileManagerDeleteDirectoryZip($directoryZipPaths);
        $directoryZipMessage = '<span class="success"><b>' . $_lang['files_zip_deleted'] . '</b></span><br /><br />';
    } else {
        $directoryZipMessage = '<span class="warning"><b>Invalid token</b></span><br /><br />';
    }
} elseif (get_by_key($_REQUEST, 'mode') == 'downloadzip') {
    if (!$token_check) {
        $directoryZipMessage = '<span class="warning"><b>Invalid token</b></span><br /><br />';
    } elseif ($directoryZipBlocked) {
        $directoryZipMessage = '<span class="warning"><b>' . $_lang['files_zip_unavailable'] . '</b></span><br /><br />';
    } elseif (fileManagerDirectoryZipExists($directoryZipPaths)) {
        $directoryZipMessage = '<span class="warning"><b>' . $_lang['files_zip_in_progress'] . '</b></span><br /><br />';
    } else {
        $lockHandle = @fopen($directoryZipPaths['lock'], 'x');
        if ($lockHandle === false) {
            $directoryZipMessage = '<span class="warning"><b>' . $_lang['files_zip_in_progress'] . '</b></span><br /><br />';
        } else {
            fclose($lockHandle);
            if (fileManagerCreateDirectoryZip($startpath, $relative_path, $filemanager_path, $directoryZipPaths['zip'], $userGroups, $fileGroupsMap, $protected_path)) {
                register_shutdown_function('fileManagerDeleteDirectoryZip', $directoryZipPaths);
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . fileManagerDirectoryZipName($filemanager_path, $relative_path) . '"');
                header('Content-Length: ' . filesize($directoryZipPaths['zip']));
                header('X-Content-Type-Options: nosniff');
                readfile($directoryZipPaths['zip']);
                exit;
            }
            fileManagerDeleteDirectoryZip($directoryZipPaths);
            $directoryZipMessage = '<span class="warning"><b>' . $_lang['files_zip_failed'] . '</b></span><br /><br />';
        }
    }
}
?>
    <script type="text/javascript">
        var current_path = '<?= addslashes($relative_path) ?>';
        function viewfile(url) {
            var el = document.getElementById('imageviewer');
            el.innerHTML = '<img src="' + url + '" />';
            el.style.display = 'block'
        }
        function setColor(o, state) {
            if (!o){return;}
            if (state && o.style) {
                o.style.backgroundColor = '#eeeeee';
            } else if (o.style) {
                o.style.backgroundColor = 'transparent';
            }
        }
        function confirmDelete() {
            return confirm("<?= $_lang['confirm_delete_file'] ?>");
        }
        function confirmDeleteFolder(status) {
            if (status !== 'file_exists') {
                return confirm("<?= $_lang['confirm_delete_dir'] ?>");
            } else {
                return confirm("<?= $_lang['confirm_delete_dir_recursive'] ?>");
            }
        }
        function confirmUnzip() {
            return confirm("<?= $_lang['confirm_unzip_file'] ?>");
        }
        function unzipFile(file) {
            if (confirmUnzip()) {
                window.location.href = "index.php?a=31&mode=unzip&path=" + current_path + '&file=' + file + "&token=<?= $newToken;?>";
                return false;
            }
        }
        function getFolderName(a) {
            var f = window.prompt("<?= $_lang['files_dynamic_new_file_name'] ?>", '');
            if (f) a.href += encodeURI(f);
            return !!(f);
        }
        function getFileName(a) {
            var f = window.prompt("<?= $_lang['files_dynamic_new_file_name'] ?>", '');
            if (f) a.href += encodeURI(f);
            return !!(f);
        }
        function deleteFolder(folder, status) {
            if (confirmDeleteFolder(status)) {
                window.location.href = "index.php?a=31&mode=deletefolder&path=" + current_path + "&folderpath=" + (current_path ? current_path + '/' : '') + folder + "&token=<?= $newToken;?>";
                return false;
            }
        }
        function deleteFile(file) {
            if (confirmDelete()) {
                window.location.href = "index.php?a=31&mode=delete&path=" + (current_path ? current_path + '/' : '') + file + "&token=<?= $newToken;?>";
                return false;
            }
        }
        function duplicateFile(file) {
            var newFilename = prompt("<?= $_lang["files_dynamic_new_file_name"] ?>", file);
            if (newFilename !== null && newFilename !== file) {
                window.location.href = "index.php?a=31&mode=duplicate&path=" + (current_path ? current_path + '/' : '') + file + "&newFilename=" + newFilename + "&token=<?= $newToken;?>";
            }
        }
        function renameFolder(dir) {
            var newDirname = prompt("<?= $_lang["files_dynamic_new_folder_name"] ?>", dir);
            if (newDirname !== null && newDirname !== dir) {
                window.location.href = "index.php?a=31&mode=renameFolder&path=" + current_path + '&dirname=' + dir + "&newDirname=" + newDirname + "&token=<?= $newToken;?>";
            }
        }
        function renameFile(file) {
            var newFilename = prompt("<?= $_lang["files_dynamic_new_file_name"] ?>", file);
            if (newFilename !== null && newFilename !== file) {
                window.location.href = "index.php?a=31&mode=renameFile&path=" + (current_path ? current_path + '/' : '') + file + "&newFilename=" + newFilename + "&token=<?= $newToken;?>";
            }
        }
    </script>
    <h1>
        <i class="<?= $_style['icon_folder_open'] ?>"></i><?= $_lang['manage_files'] ?>
    </h1>
    <div id="actions">
        <div class="btn-group">
            <?php if (get_by_key($_POST, 'mode') == 'save' || get_by_key($_GET, 'mode') == 'edit') : ?>
                <a class="btn btn-success" href="javascript:;" onclick="documentDirty=false;document.editFile.submit();">
                    <i class="<?= $_style["icon_save"] ?>"></i><span><?= $_lang['save'] ?></span>
                </a>
            <?php endif ?>
            <?php if (in_array(get_by_key($_REQUEST, 'mode'), ['groups', 'savegroups'])) : ?>
                <a class="btn btn-success" href="javascript:;" onclick="documentDirty=false;document.fileGroupsForm.submit();">
                    <i class="<?= $_style["icon_save"] ?>"></i><span><?= $_lang['save'] ?></span>
                </a>
            <?php endif ?>
            <?php
            if (isset($_GET['mode']) && $_GET['mode'] !== 'drill') {
                $href = 'a=31&path=' . urlencode($_REQUEST['path']);
            } else {
                $href = 'a=2';
            }
            if ($currentPathWritable) {
                $ph = [];
                $ph['style_path'] = $theme_image_path;
                $tpl = '<a class="btn btn-secondary" href="[+href+]" onclick="return getFolderName(this);"><i class="[+image+]"></i><span>[+subject+]</span></a>';
                $ph['image'] = $_style['icon_folder_open'];
                $ph['subject'] = $_lang['add_folder'];
                $ph['href'] = 'index.php?a=31&mode=newfolder&path=' . urlencode($relative_path) . '&token=' . $newToken . '&name=';
                $_ = parsePlaceholder($tpl, $ph);

                $tpl = '<a class="btn btn-secondary" href="[+href+]" onclick="return getFileName(this);"><i class="[+image+]"></i><span>' . $_lang['files.dynamic.php1'] . '</span></a>';
                $ph['image'] = $_style['icon_document'];
                $ph['href'] = 'index.php?a=31&mode=newfile&path=' . urlencode($relative_path) . '&token=' . $newToken . '&name=';
                $_ .= parsePlaceholder($tpl, $ph);
                echo $_;
            }
            ?>
            <a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="documentDirty=false;document.location.href='index.php?<?= $href ?>';">
                <i class="<?= $_style["icon_cancel"] ?>"></i><span><?= $_lang['cancel'] ?></span>
            </a>
        </div>
    </div>
    <div id="ManageFiles">
        <div class="container breadcrumbs">
            <?php
            // Fix: Add token check for uploads
            if (!empty($_FILES['userfile'])) {
                if ($token_check) {
                    $information = fileupload();
                } else {
                    echo '<span class="warning"><b>Invalid token</b></span><br /><br />';
                }
            } elseif (get_by_key($_POST, 'mode') == 'save') {
                if ($token_check) {
                    echo textsave();
                } else {
                    echo '<span class="warning"><b>Invalid token</b></span><br /><br />';
                }
            } elseif (get_by_key($_REQUEST, 'mode') == 'delete') {
                if ($token_check) {
                    echo delete_file();
                } else {
                    echo '<span class="warning"><b>Invalid token</b></span><br /><br />';
                }
            } elseif (get_by_key($_POST, 'mode') == 'savegroups') {
                if ($token_check) {
                    if ($showFileGroups) {
                        $groupsTargetPath = ltrim($_POST['groupspath'] ?? $_POST['path'] ?? '', '/');
                        // Validate path is within filemanager_path
                        $fullGroupsPath = str_replace('\\', '/', realpath($filemanager_path . '/' . $groupsTargetPath) ?: ($filemanager_path . '/' . $groupsTargetPath));
                        if (strpos($fullGroupsPath, $filemanager_path) !== 0) {
                            echo '<span class="warning"><b>Invalid path.</b></span><br /><br />';
                        } elseif (!fileManagerIsAccessible($groupsTargetPath, $userGroups)) {
                            echo '<span class="warning"><b>' . $_lang['files_access_denied'] . '</b></span><br /><br />';
                        } else {
                            $submittedGroups = isset($_POST['docgroups']) ? (array)$_POST['docgroups'] : [];
                            $chkAllFiles = isset($_POST['chkallfiles']) && $_POST['chkallfiles'] === 'on';
                            $canManageAllGroups = evo()->hasPermission('manage_groups');
                            $existingFG = \EvolutionCMS\Models\FileGroup::query()->where('file', $groupsTargetPath)->get();
                            $existingGroupIds = $existingFG->pluck('document_group')->map(static fn ($groupId) => (int)$groupId)->all();
                            $manageableExistingGroups = $canManageAllGroups
                                ? $existingGroupIds
                                : array_values(array_intersect($existingGroupIds, $userGroups));

                            if (!$canManageAllGroups && count($manageableExistingGroups) !== count($existingGroupIds)) {
                                echo '<span class="warning"><b>' . $_lang['files_access_denied'] . '</b></span><br /><br />';
                            } elseif ($chkAllFiles && !$canManageAllGroups) {
                                echo '<span class="warning"><b>' . $_lang['files_access_denied'] . '</b></span><br /><br />';
                            } elseif ($chkAllFiles) {
                                // Make public: only full group managers may remove all restrictions
                                \EvolutionCMS\Models\FileGroup::query()->where('file', $groupsTargetPath)->delete();
                            } else {
                                // Determine which group IDs are submitted
                                $submittedGroupIds = [];
                                $insertRows = [];
                                foreach ($submittedGroups as $val) {
                                    $parts = explode(',', $val);
                                    $gid = (int)$parts[0];
                                    if (!$canManageAllGroups && !in_array($gid, $userGroups)) {
                                        continue;
                                    }
                                    $submittedGroupIds[] = $gid;
                                    if (!in_array($gid, $existingGroupIds)) {
                                        $insertRows[] = ['document_group' => $gid, 'file' => $groupsTargetPath];
                                    }
                                }

                                // Delete removed groups only from the set the current user manages
                                $toDelete = array_diff($manageableExistingGroups, $submittedGroupIds);
                                if (!empty($toDelete)) {
                                    \EvolutionCMS\Models\FileGroup::query()->where('file', $groupsTargetPath)
                                        ->whereIn('document_group', $toDelete)
                                        ->delete();
                                }

                                // Insert new entries
                                if (!empty($insertRows)) {
                                    \EvolutionCMS\Models\FileGroup::query()->insertOrIgnore($insertRows);
                                }
                            }
                            echo '<span class="success"><b>' . $_lang['file_groups_saved'] . '</b></span><br /><br />';
                            $_REQUEST['mode'] = 'groups';
                        }
                    }
                } else {
                    echo '<span class="warning"><b>Invalid token</b></span><br /><br />';
                }
            }
            echo $directoryZipMessage;
            if (in_array($startpath, $protected_path)) {
                evo()->webAlertAndQuit($_lang["files.dynamic.php2"]);
            }
            $tpl = '<i class="[+image+] FilesTopFolder"></i>[+subject+]';
            $ph = [];
            $ph['style_path'] = $theme_image_path;
            // To Top Level with folder icon to the left
            if ($startpath == $filemanager_path || $startpath . '/' == $filemanager_path) {
                $ph['image'] = '' . $_style['icon_folder_open'] . '';
                $ph['subject'] = '<span>Top</span>';
            } else {
                $ph['image'] = '' . $_style['icon_folder_open'] . '';
                $ph['subject'] = '<a href="index.php?a=31&mode=drill&path=">Top</a>/';
            }
            echo parsePlaceholder($tpl, $ph);
            $topic_path = $relative_path;
            if ($topic_path == '') {
                $topic_path = '/';
            } else {
                $pieces = explode('/', rtrim($topic_path, '/'));
                $path = '';
                $count = count($pieces);
                foreach ($pieces as $i => $v) {
                    if (empty($v)) {
                        continue;
                    }
                    $path .= $v . '/';
                    if (1 < $count) {
                        $href = 'index.php?a=31&mode=drill&path=' . urlencode(rtrim($path, '/'));
                        $pieces[$i] = '<a href="' . $href . '">' . trim($v, '/') . '</a>';
                    } else {
                        $pieces[$i] = '<span>' . trim($v, '/') . '</span>';
                    }
                    $count--;
                }
                $topic_path = implode('/', $pieces);
            }
            echo $topic_path;
            ?>
        </div>
        <?php
        // check to see user isn't trying to move below the document_root
        // Existing check replaced with realpath check above

        // Define safe unzip function
        function safe_unzip($file, $path) {
            $path = rtrim(str_replace('\\', '/', realpath($path)), '/\\');
            $zip = new ZipArchive();
            if ($zip->open($file) !== true) {
                return false;
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $filename = str_replace('\\', '/', $stat['name']);
                if (substr($filename, 0, 1) == '/' || strpos($filename, '..') !== false || strpos($filename, ':') !== false) {
                    continue; // skip malicious paths
                }
                $target = $path . '/' . $filename;
                $target_real = rtrim(str_replace('\\', '/', realpath(dirname($target)) ?: dirname($target)), '/\\');
                if (strpos($target_real, $path) !== 0) {
                    continue;
                }
                if (substr($filename, -1) == '/') {
                    if (!is_dir($target)) {
                        mkdir($target, 0777, true);
                    }
                } else {
                    $dirname = dirname($target);
                    if (!is_dir($dirname)) {
                        mkdir($dirname, 0777, true);
                    }
                    file_put_contents($target, $zip->getFromIndex($i));
                }
            }
            $zip->close();
            return true;
        }

        // Unzip .zip files - by Raymond, with safe_unzip
        if ($enablefileunzip && get_by_key($_REQUEST, 'mode') == 'unzip' && $currentPathWritable) {
            if ($token_check) {
                $zipfile = str_replace('\\', '/', realpath($startpath . '/' . $_REQUEST['file']));
                if (strpos($zipfile, $filemanager_path) !== 0) {
                    echo '<span class="warning"><b>Invalid path.</b></span><br /><br />';
                } else {
                    $success = safe_unzip($zipfile, $startpath);
                    if (!$success) {
                        echo '<span class="warning"><b>' . $_lang['file_unzip_fail'] . '</b></span><br /><br />';
                    } else {
                        echo '<span class="success"><b>' . $_lang['file_unzip'] . '</b></span><br /><br />';
                        if ($showFileGroups) {
                            $dirRelPath = $relative_path;
                            $dirGroupIds = fileManagerEffectiveGroupIds($dirRelPath, $fileGroupsMap);
                            if (!empty($dirGroupIds)) {
                                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($startpath, RecursiveDirectoryIterator::SKIP_DOTS));
                                $insertRows = [];
                                foreach ($iterator as $f) {
                                    if ($f->isFile()) {
                                        $fp = str_replace('\\', '/', $f->getPathname());
                                        $relP = ltrim(substr($fp, strlen($filemanager_path)), '/');
                                        foreach ($dirGroupIds as $gid) {
                                            $insertRows[] = ['document_group' => $gid, 'file' => $relP];
                                        }
                                    }
                                }
                                if (!empty($insertRows)) {
                                    \EvolutionCMS\Models\FileGroup::query()->insertOrIgnore($insertRows);
                                }
                            }
                        }
                    }
                }
            } else {
                echo '<span class="warning"><b>Invalid token</b></span><br /><br />';
            }
        }
        // End Unzip - Raymond
        // New Folder & Delete Folder option - Raymond
        if ($currentPathWritable) {
            // Delete Folder
            if (get_by_key($_REQUEST, 'mode') == 'deletefolder') {
                if ($token_check) {
                    $requested_folderpath = ltrim($_REQUEST['folderpath'] ?? '', '/');
                    $folder = str_replace('\\', '/', realpath($filemanager_path . '/' . $requested_folderpath));
                    if (strpos($folder, $filemanager_path) !== 0 || !is_dir($folder)) {
                        echo '<span class="warning"><b>Invalid path.</b></span><br /><br />';
                    } elseif (!fileManagerCanModifyExistingPath($requested_folderpath, $userGroups, $fileGroupsMap) || !is_writable($folder)) {
                        echo '<span class="warning"><b>' . $_lang['files_access_denied'] . '</b></span><br /><br />';
                    } elseif (!@rrmdir($folder)) {
                        echo '<span class="warning"><b>' . $_lang['file_folder_not_deleted'] . '</b></span><br /><br />';
                    } else {
                        echo '<span class="success"><b>' . $_lang['file_folder_deleted'] . '</b></span><br /><br />';
                        if ($showFileGroups) {
                            $delRelPath = ltrim(substr($folder, strlen($filemanager_path)), '/');
                            \EvolutionCMS\Models\FileGroup::query()->where('file', $delRelPath)
                                ->orWhere('file', 'like', $delRelPath . '/%')
                                ->delete();
                        }
                    }
                } else {
                    echo '<span class="warning"><b>Invalid token</b></span><br /><br />';
                }
            }
            // Create folder here
            if (get_by_key($_REQUEST, 'mode') == 'newfolder') {
                if ($token_check) {
                    $old_umask = umask(0);
                    $foldername = str_replace([ '..\\', '../', '\\', '/' ], '', $_REQUEST['name']);
                    $newdir = $startpath . '/' . $foldername;
                    if (!$currentPathWritable) {
                        echo '<span class="warning"><b>' . $_lang['files_access_denied'] . '</b></span><br /><br />';
                    } elseif (!mkdirs($newdir, 0777)) {
                        echo '<span class="warning"><b>', $_lang['file_folder_not_created'], '</b></span><br /><br />';
                    } else {
                        if (!@chmod($newdir, $newfolderaccessmode)) {
                            echo '<span class="warning"><b>' . $_lang['file_folder_chmod_error'] . '</b></span><br /><br />';
                        } else {
                            echo '<span class="success"><b>' . $_lang['file_folder_created'] . '</b></span><br /><br />';
                        }
                    }
                    umask($old_umask);
                } else {
                    echo '<span class="warning"><b>Invalid token</b></span><br /><br />';
                }
            }
            // Create file here
            if (get_by_key($_REQUEST, 'mode') == 'newfile') {
                if ($token_check) {
                    $old_umask = umask(0);
                    $filename = str_replace([ '..\\', '../', '\\', '/' ], '', $_REQUEST['name']);
                    if (!$currentPathWritable) {
                        echo '<span class="warning"><b>' . $_lang['files_access_denied'] . '</b></span><br /><br />';
                    } elseif (!checkExtension($filename)) {
                        echo '<span class="warning"><b>' . $_lang['files_filetype_notok'] . '</b></span><br /><br />';
                    } elseif (preg_match('@(\\\\|\/|\:|\;|\,|\*|\?|\"|\<|\>|\||\?)@', $filename) !== 0) {
                        echo $_lang['files.dynamic.php3'];
                    } else {
                        $rs = file_put_contents($startpath . '/' . $filename, '');
                        if ($rs === false) {
                            echo '<span class="warning"><b>', $_lang['file_folder_not_created'], '</b></span><br /><br />';
                        } else {
                            echo $_lang['files.dynamic.php4'];
                        }
                        umask($old_umask);
                    }
                } else {
                    echo '<span class="warning"><b>Invalid token</b></span><br /><br />';
                }
            }
            // Duplicate file here
            if (get_by_key($_REQUEST, 'mode') == 'duplicate') {
                if ($token_check) {
                    $old_umask = umask(0);
                    $requested_file = ltrim($_REQUEST['path'] ?? '', '/');
                    $filename = str_replace('\\', '/', realpath($filemanager_path . '/' . $requested_file));
                    if (strpos($filename, $filemanager_path) !== 0 || !is_file($filename)) {
                        echo '<span class="warning"><b>Invalid path.</b></span><br /><br />';
                    } elseif (!fileManagerCanModifyExistingPath($requested_file, $userGroups, $fileGroupsMap) || !is_writable($filename)) {
                        echo '<span class="warning"><b>' . $_lang['files_access_denied'] . '</b></span><br /><br />';
                    } else {
                        $newFilename = str_replace([ '..\\', '../', '\\', '/' ], '', $_REQUEST['newFilename']);
                        if (!checkExtension($newFilename)) {
                            echo '<span class="warning"><b>' . $_lang['files_filetype_notok'] . '</b></span><br /><br />';
                        } elseif (preg_match('@(\\\\|\/|\:|\;|\,|\*|\?|\"|\<|\>|\||\?)@', $newFilename) !== 0) {
                            echo $_lang['files.dynamic.php3'];
                        } else {
                            // Fix: Copy to same directory, not base path
                            $newpath = dirname($filename) . '/' . $newFilename;
                            if (!copy($filename, $newpath)) {
                                echo $_lang['files.dynamic.php5'];
                            }
                            umask($old_umask);
                        }
                    }
                } else {
                    echo '<span class="warning"><b>Invalid token</b></span><br /><br />';
                }
            }
            // Rename folder here
            if (get_by_key($_REQUEST, 'mode') == 'renameFolder') {
                if ($token_check) {
                    $old_umask = umask(0);
                    $requested_dir = ltrim($_REQUEST['path'] ?? '', '/');
                    $dirname = str_replace('\\', '/', realpath($filemanager_path . '/' . $requested_dir . '/' . $_REQUEST['dirname']));
                    if (strpos($dirname, $filemanager_path) !== 0 || !is_dir($dirname)) {
                        echo '<span class="warning"><b>Invalid path.</b></span><br /><br />';
                    } elseif (!fileManagerCanModifyExistingPath(trim($requested_dir . '/' . $_REQUEST['dirname'], '/'), $userGroups, $fileGroupsMap) || !is_writable($dirname)) {
                        echo '<span class="warning"><b>' . $_lang['files_access_denied'] . '</b></span><br /><br />';
                    } else {
                        $newDirname = str_replace([ '..\\', '../', '\\', '/' ], '', $_REQUEST['newDirname']);
                        if (preg_match('@(\\\\|\/|\:|\;|\,|\*|\?|\"|\<|\>|\||\?)@', $newDirname) !== 0) {
                            echo $_lang['files.dynamic.php3'];
                        } else if (!rename($dirname, dirname($dirname) . '/' . $newDirname)) {
                            echo '<span class="warning"><b>', $_lang['file_folder_not_created'], '</b></span><br /><br />';
                        } else {
                            if ($showFileGroups) {
                                $oldRelPath = ltrim(substr($dirname, strlen($filemanager_path)), '/');
                                $newRelPath = ltrim(substr(dirname($dirname) . '/' . $newDirname, strlen($filemanager_path)), '/');
                                \EvolutionCMS\Models\FileGroup::query()->where('file', $oldRelPath)
                                    ->update(['file' => $newRelPath]);
                                $oldPrefix = $oldRelPath . '/';
                                $newPrefix = $newRelPath . '/';
                                $affected = \EvolutionCMS\Models\FileGroup::query()->where('file', 'like', $oldPrefix . '%')->get();
                                foreach ($affected as $fg) {
                                    $fg->update(['file' => $newPrefix . substr($fg->file, strlen($oldPrefix))]);
                                }
                            }
                        }
                        umask($old_umask);
                    }
                } else {
                    echo '<span class="warning"><b>Invalid token</b></span><br /><br />';
                }
            }
            // Rename file here
            if (get_by_key($_REQUEST, 'mode') == 'renameFile') {
                if ($token_check) {
                    $old_umask = umask(0);
                    $requested_file = ltrim($_REQUEST['path'] ?? '', '/');
                    $filename = str_replace('\\', '/', realpath($filemanager_path . '/' . $requested_file));
                    if (strpos($filename, $filemanager_path) !== 0 || !is_file($filename)) {
                        echo '<span class="warning"><b>Invalid path.</b></span><br /><br />';
                    } elseif (!fileManagerCanModifyExistingPath($requested_file, $userGroups, $fileGroupsMap) || !is_writable($filename)) {
                        echo '<span class="warning"><b>' . $_lang['files_access_denied'] . '</b></span><br /><br />';
                    } else {
                        $path = dirname($filename);
                        $newFilename = str_replace([ '..\\', '../', '\\', '/' ], '', $_REQUEST['newFilename']);
                        if (!checkExtension($newFilename)) {
                            echo '<span class="warning"><b>' . $_lang['files_filetype_notok'] . '</b></span><br /><br />';
                        } elseif (preg_match('@(\\\\|\/|\:|\;|\,|\*|\?|\"|\<|\>|\||\?)@', $newFilename) !== 0) {
                            echo $_lang['files.dynamic.php3'];
                        } else {
                            if (!rename($filename, $path . '/' . $newFilename)) {
                                echo $_lang['files.dynamic.php5'];
                            } else {
                                if ($showFileGroups) {
                                    $oldRelPath = ltrim(substr($filename, strlen($filemanager_path)), '/');
                                    $newRelPath = ltrim(substr($path . '/' . $newFilename, strlen($filemanager_path)), '/');
                                    \EvolutionCMS\Models\FileGroup::query()->where('file', $oldRelPath)
                                        ->update(['file' => $newRelPath]);
                                }
                            }
                            umask($old_umask);
                        }
                    }
                } else {
                    echo '<span class="warning"><b>Invalid token</b></span><br /><br />';
                }
            }
        } // End New Folder - Raymond
        $len = 0;
        if (strlen(EVO_BASE_PATH) < strlen($filemanager_path)) {
            $len--;
        }
        ?>
        <script type="text/javascript" src="media/script/tablesort.js"></script>
        <div class="table-responsive">
            <table id="FilesTable" class="table data">
                <thead>
                <tr>
                    <th class="sortable"><?= $_lang['files_filename'] ?></th>
                    <th class="sortable" style="width: 1%;"><?= $_lang['files_modified'] ?></th>
                    <th class="sortable" style="width: 1%;"><?= $_lang['files_filesize'] ?></th>
                    <th class="sortable" style="width: 1%;" class="text-nowrap"><?= $_lang['files_fileoptions'] ?></th>
                </tr>
                </thead>
                <?php extract(ls($startpath, compact('len', 'editablefiles', 'enablefileunzip', 'inlineviewablefiles', 'uploadablefiles', 'enablefiledownload', 'viewablefiles', 'protected_path', 'excludes', 'filemanager_path', 'base_path', 'showFileGroups', 'fileGroupsMap', 'allDocGroups')), EXTR_OVERWRITE);
                echo "\n\n\n";
                if ($folders == 0 && $files == 0) { echo '<tr><td colspan="4"><i class="' . $_style['icon_folder'] . ' FilesDeletedFolder"></i> <span style="color:#888;cursor:default;"> ' . $_lang['files_directory_is_empty'] . ' </span></td></tr>'; }
                ?>
            </table>
        </div>
        <div class="container">
            <p>
                <?php echo $_lang['files_directories'] . ': <b>' . $folders . '</b> ';
                echo $_lang['files_files'] . ': <b>' . $files . '</b> ';
                echo $_lang['files_data'] . ': <b><span dir="ltr">' . niceSize($filesizes) . '</span></b> ';
                echo $_lang['files_dirwritable'] . ' <b>' . ($currentPathWritable ? $_lang['yes'] : $_lang['no']) . '.</b>';
                if ($showFileGroups) {
                    $effectiveGroupIds = fileManagerEffectiveGroupIds($requested_path, $fileGroupsMap);
                    if (empty($effectiveGroupIds)) {
                        $groupLabel = $_lang['all_file_groups'];
                    } else {
                        $groupNames = $allDocGroups->whereIn('id', $effectiveGroupIds)->pluck('name')->toArray();
                        $groupLabel = implode(', ', $groupNames);
                    }
                    echo '<br>' . $_lang['files_groups'] . ' <b>' . htmlspecialchars($groupLabel, ENT_QUOTES) . '</b>';
                }
                ?>
            </p>
            <?php if (((@ini_get("file_uploads") == true) || get_cfg_var("file_uploads") == 1) && $currentPathWritable) {
                @ini_set("upload_max_filesize", $upload_maxsize ?? 0); // modified by raymond ?>
                <form name="upload" enctype="multipart/form-data" action="index.php" method="post">
                    <input type="hidden" name="MAX_FILE_SIZE" value="<?= $upload_maxsize ?? 5000000 ?>">
                    <input type="hidden" name="a" value="31">
                    <input type="hidden" name="path" value="<?= $relative_path ?>">
                    <!-- Fix: Add token to upload form -->
                    <input type="hidden" name="token" value="<?= $newToken ?>">
                    <?php if (isset($information)) { echo $information; } ?>
                    <div id="uploader">
                        <input type="file" name="userfile[]" onchange="document.upload.submit();" multiple>
                        <a class="btn btn-secondary" href="javascript:;" onclick="document.upload.submit()"><?= $_lang['files_uploadfile'] ?></a>
                    </div>
                </form>
            <?php } else {
                echo "<p>" . $_lang['files_upload_inhibited_msg'] . "</p>";
            }
            $directoryZipExists = fileManagerDirectoryZipExists($directoryZipPaths);
            $directoryZipPathParam = urlencode($relative_path);
            if ($directoryZipExists) { ?>
                <p>
                    <a class="btn btn-secondary disabled" href="javascript:;" aria-disabled="true">
                        <i class="<?= $_style['icon_download'] ?>"></i><span><?= $_lang['files_download_zip'] ?></span>
                    </a>
                    <a class="btn btn-danger" href="index.php?a=31&mode=deletezip&path=<?= $directoryZipPathParam ?>&token=<?= $newToken ?>">
                        <i class="<?= $_style['icon_trash'] ?>"></i><span><?= $_lang['files_delete_zip'] ?></span>
                    </a>
                </p>
            <?php } elseif (!$directoryZipBlocked) { ?>
                <p>
                    <a class="btn btn-secondary" href="index.php?a=31&mode=downloadzip&path=<?= $directoryZipPathParam ?>&token=<?= $newToken ?>">
                        <i class="<?= $_style['icon_download'] ?>"></i><span><?= $_lang['files_download_zip'] ?></span>
                    </a>
                </p>
            <?php } ?>
            <div id="imageviewer"></div>
        </div>
    </div>
<?php if (in_array(get_by_key($_REQUEST, 'mode'), ['groups', 'savegroups'])) { ?>
    <div class="section" id="file_groups_section">
        <div class="navbar navbar-editor"><?= $_lang['access_permissions'] ?>: <?= htmlspecialchars($requested_path ?: '/', ENT_QUOTES) ?></div>
        <?php
        if ($showFileGroups) {
            // Load groups for this specific path
            $groupsTargetPath = $requested_path;
            if (!fileManagerIsAccessible($groupsTargetPath, $userGroups, $fileGroupsMap)) {
                evo()->webAlertAndQuit($_lang["files_access_denied"]);
            }
            $existingFileGroups = \EvolutionCMS\Models\FileGroup::query()->where('file', $groupsTargetPath)->get();
            $directGroupIds = $existingFileGroups->pluck('document_group')->map(static fn ($groupId) => (int)$groupId)->all();
            $effectiveGroupIds = fileManagerEffectiveGroupIds($groupsTargetPath, $fileGroupsMap);
            $effectiveGroupNames = empty($effectiveGroupIds)
                ? [$_lang['all_file_groups']]
                : $allDocGroups->whereIn('id', $effectiveGroupIds)->pluck('name')->toArray();
            $directGroupNames = empty($directGroupIds)
                ? [$_lang['all_file_groups']]
                : $allDocGroups->whereIn('id', $directGroupIds)->pluck('name')->toArray();
            $canManageAllGroups = evo()->hasPermission('manage_groups');
            $canEditPathAcl = $canManageAllGroups || empty(array_diff($directGroupIds, $userGroups));
            $groupsarray = [];
            foreach ($existingFileGroups as $efg) {
                $groupsarray[] = $efg->document_group . ',' . $efg->id;
            }
            // Retain POST state on re-display after failed save
            if (isset($_POST['docgroups'])) {
                $groupsarray = array_merge($groupsarray, $_POST['docgroups']);
            }
            $notPublic = !empty($groupsarray);

            // Build permissions list
            $permissions = [];
            $inputAttributes = [
                'type' => 'checkbox',
                'class' => 'checkbox',
                'name' => 'docgroups[]',
                'onclick' => 'makeFilesPublic(false);',
            ];
            foreach ($allDocGroups as $group) {
                $row = $group->toArray();
                $existingEntry = $existingFileGroups->where('document_group', $row['id'])->first();
                $linkId = $existingEntry ? $existingEntry->id : 'new';
                $inputValue = $row['id'] . ',' . $linkId;
                $inputId = 'fgroup-' . $row['id'];
                $checked = in_array($row['id'] . ',' . $linkId, $groupsarray)
                    || in_array($row['id'] . ',new', $groupsarray);
                $inputAttributes['id'] = $inputId;
                $inputAttributes['value'] = $inputValue;
                if ($checked) { $inputAttributes['checked'] = 'checked'; } else { unset($inputAttributes['checked']); }
                $disabled = !(in_array($row['id'], $userGroups) || evo()->hasPermission('manage_groups'));
                if ($disabled) { $inputAttributes['disabled'] = 'disabled'; } else { unset($inputAttributes['disabled']); }
                $inputString = [];
                foreach ($inputAttributes as $k => $v) $inputString[] = $k . '="' . $v . '"';
                $permissions[] = '<li><input ' . implode(' ', $inputString) . ' /><label for="' . $inputId . '">' . htmlspecialchars($row['name'], ENT_QUOTES) . '</label></li>';
            }
            if (!empty($permissions) && $canManageAllGroups) {
                array_unshift($permissions, '<li><input type="checkbox" class="checkbox" name="chkallfiles" id="fgroupall"' . (empty($notPublic) ? ' checked="checked"' : '') . ' onclick="makeFilesPublic(true);" /><label for="fgroupall" class="warning">' . $_lang['all_file_groups'] . '</label></li>');
            }
            ?>
            <form action="index.php" method="post" name="fileGroupsForm">
                <input type="hidden" name="a" value="31">
                <input type="hidden" name="mode" value="savegroups">
                <input type="hidden" name="groupspath" value="<?= htmlspecialchars($groupsTargetPath, ENT_QUOTES) ?>">
                <input type="hidden" name="path" value="<?= htmlspecialchars($requested_path, ENT_QUOTES) ?>">
                <input type="hidden" name="token" value="<?= $newToken ?>">
                <script type="text/javascript">
                /* <![CDATA[ */
                function makeFilesPublic(b) {
                    var notPublic = false;
                    var f = document.forms['fileGroupsForm'];
                    var chkpub = f['chkallfiles'];
                    var chks = f['docgroups[]'];
                    if (!chks && chkpub) { chkpub.checked = true; return false; }
                    else if (!b && chkpub) {
                        if (!chks.length) notPublic = chks.checked;
                        else for (var i = 0; i < chks.length; i++) if (chks[i].checked) notPublic = true;
                        chkpub.checked = !notPublic;
                    } else {
                        if (!chks.length) chks.checked = b ? false : chks.checked;
                        else for (var i = 0; i < chks.length; i++) if (b) chks[i].checked = false;
                        chkpub.checked = true;
                    }
                }
                /* ]]> */
                </script>
                <div class="container">
                <p><?= is_dir($fullpath) ? $_lang['access_permissions_dir_message'] : $_lang['access_permissions_file_message'] ?></p>
                <p><strong>Effective access:</strong> <?= htmlspecialchars(implode(', ', $effectiveGroupNames), ENT_QUOTES) ?></p>
                <p><strong>Direct groups:</strong> <?= htmlspecialchars(implode(', ', $directGroupNames), ENT_QUOTES) ?></p>
                <?php if (!$canEditPathAcl) { ?>
                <p><em><?= $_lang['files_access_denied'] ?></em></p>
                <?php } elseif (!empty($permissions)) { ?>
                <ul><?= implode("\n", $permissions) ?></ul>
                <?php } else { ?>
                <p><em><?= $_lang['access_permissions_off'] ?></em></p>
                <?php } ?>
                </div>
            </form>
            <?php
        }
        ?>
    </div>
<?php } ?>
<?php if (get_by_key($_REQUEST, 'mode') == "edit" || get_by_key($_REQUEST, 'mode') == "view") { ?>
    <div class="section" id="file_editfile">
        <div class="navbar navbar-editor"><?= $_REQUEST['mode'] == "edit" ? $_lang['files_editfile'] : $_lang['files_viewfile'] ?></div>
        <?php
        $requested_path = ltrim($_REQUEST['path'] ?? '', '/');
        $filename = str_replace('\\', '/', realpath($filemanager_path . '/' . $requested_path));
        if (strpos($filename, $filemanager_path) !== 0 || !is_file($filename)) {
            evo()->webAlertAndQuit("Invalid path.");
        }
        if (!fileManagerIsAccessible($requested_path, $userGroups, $fileGroupsMap)) {
            evo()->webAlertAndQuit($_lang["files_access_denied"]);
        }
        $buffer = file_get_contents($filename);
        // Log the change
        logFileChange('view', $filename);
        if ($buffer === false) {
            evo()->webAlertAndQuit("Error opening file for reading.");
        }
        ?>
        <form action="index.php" method="post" name="editFile">
            <input type="hidden" name="a" value="31" />
            <input type="hidden" name="mode" value="save" />
            <input type="hidden" name="path" value="<?= $requested_path ?>" />
            <input type="hidden" name="token" value="<?= $newToken ?>" />
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td>
                        <textarea dir="ltr" name="content" id="content" class="phptextarea"><?= htmlentities($buffer, ENT_COMPAT, ManagerTheme::getCharset()) ?></textarea>
                    </td>
                </tr>
            </table>
        </form>
    </div>
    <?php
    $pathinfo = pathinfo($filename);
    switch ($pathinfo['extension']) {
        case "css":
            $contentType = "text/css";
            break;
        case "js":
            $contentType = "text/javascript";
            break;
        case "json":
            $contentType = "application/json";
            break;
        case "php":
            $contentType = "application/x-httpd-php";
            break;
        default:
            $contentType = 'htmlmixed';
    };
    $evtOut = evo()->invokeEvent('OnRichTextEditorInit', [
        'editor' => 'Codemirror',
        'elements' => ['content'],
        'contentType' => $contentType,
        'readOnly' => $_REQUEST['mode'] !== 'edit'
    ]);
    if (is_array($evtOut)) {
        echo implode('', $evtOut);
    }
}
