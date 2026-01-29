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
$theme_image_path = MODX_MANAGER_URL . 'media/style/' . evo()->getConfig('manager_theme') . '/images/';
$excludes = [
    '.',
    '..',
    '.svn',
    '.git',
    '.idea'
];
$alias_suffix = (!empty($friendly_url_suffix)) ? ',' . ltrim($friendly_url_suffix, '.') : '';
$editablefiles = explode(',', 'txt,php,tpl,less,sass,scss,shtml,html,htm,xml,js,css,pageCache,htaccess,json,ini' . $alias_suffix);
$inlineviewablefiles = explode(',', 'txt,php,tpl,less,sass,scss,html,htm,xml,js,css,pageCache,htaccess,json,ini' . $alias_suffix);
$viewablefiles = explode(',', 'jpg,gif,png,ico');
$editablefiles = add_dot($editablefiles);
$inlineviewablefiles = add_dot($inlineviewablefiles);
$viewablefiles = add_dot($viewablefiles);
$protected_path = [];
/* jp only if($_SESSION['mgrRole']!=1) { */
$protected_path[] = str_replace('\\', '/', MODX_MANAGER_PATH);
$protected_path[] = str_replace('\\', '/', MODX_BASE_PATH . 'temp/backup');
$protected_path[] = str_replace('\\', '/', MODX_BASE_PATH . 'assets/backup');
if (!evo()->hasPermission('save_plugin')) {
    $protected_path[] = str_replace('\\', '/', MODX_BASE_PATH . 'assets/plugins');
}
if (!evo()->hasPermission('save_snippet')) {
    $protected_path[] = str_replace('\\', '/', MODX_BASE_PATH . 'assets/snippets');
}
if (!evo()->hasPermission('save_template')) {
    $protected_path[] = str_replace('\\', '/', MODX_BASE_PATH . 'assets/templates');
}
if (!evo()->hasPermission('save_module')) {
    $protected_path[] = str_replace('\\', '/', MODX_BASE_PATH . 'assets/modules');
}
if (!evo()->hasPermission('empty_cache')) {
    $protected_path[] = str_replace('\\', '/', MODX_BASE_PATH . 'assets/cache');
}
if (!evo()->hasPermission('import_static')) {
    $protected_path[] = str_replace('\\', '/', MODX_BASE_PATH . 'temp/import');
    $protected_path[] = str_replace('\\', '/', MODX_BASE_PATH . 'assets/import');
}
if (!evo()->hasPermission('export_static')) {
    $protected_path[] = str_replace('\\', '/', MODX_BASE_PATH . 'temp/export');
    $protected_path[] = str_replace('\\', '/', MODX_BASE_PATH . 'assets/export');
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
$filemanager_path = rtrim(str_replace('\\', '/', realpath(evo()->getConfig('filemanager_path', MODX_BASE_PATH))), '/');
$base_path = rtrim(str_replace('\\', '/', realpath(MODX_BASE_PATH)), '/');
$len = strlen($filemanager_path);
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
            <?php
            if (isset($_GET['mode']) && $_GET['mode'] !== 'drill') {
                $href = 'a=31&path=' . urlencode($_REQUEST['path']);
            } else {
                $href = 'a=2';
            }
            if (is_writable($startpath)) {
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
            }
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
        if ($enablefileunzip && get_by_key($_REQUEST, 'mode') == 'unzip' && is_writable($startpath)) {
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
                    }
                }
            } else {
                echo '<span class="warning"><b>Invalid token</b></span><br /><br />';
            }
        }
        // End Unzip - Raymond
        // New Folder & Delete Folder option - Raymond
        if (is_writable($startpath)) {
            // Delete Folder
            if (get_by_key($_REQUEST, 'mode') == 'deletefolder') {
                if ($token_check) {
                    $requested_folderpath = ltrim($_REQUEST['folderpath'] ?? '', '/');
                    $folder = str_replace('\\', '/', realpath($filemanager_path . '/' . $requested_folderpath));
                    if (strpos($folder, $filemanager_path) !== 0 || !is_dir($folder)) {
                        echo '<span class="warning"><b>Invalid path.</b></span><br /><br />';
                    } elseif (!@rrmdir($folder)) {
                        echo '<span class="warning"><b>' . $_lang['file_folder_not_deleted'] . '</b></span><br /><br />';
                    } else {
                        echo '<span class="success"><b>' . $_lang['file_folder_deleted'] . '</b></span><br /><br />';
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
                    if (!mkdirs($newdir, 0777)) {
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
                    if (!checkExtension($filename)) {
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
                    } else {
                        $newDirname = str_replace([ '..\\', '../', '\\', '/' ], '', $_REQUEST['newDirname']);
                        if (preg_match('@(\\\\|\/|\:|\;|\,|\*|\?|\"|\<|\>|\||\?)@', $newDirname) !== 0) {
                            echo $_lang['files.dynamic.php3'];
                        } else if (!rename($dirname, dirname($dirname) . '/' . $newDirname)) {
                            echo '<span class="warning"><b>', $_lang['file_folder_not_created'], '</b></span><br /><br />';
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
                            }
                            umask($old_umask);
                        }
                    }
                } else {
                    echo '<span class="warning"><b>Invalid token</b></span><br /><br />';
                }
            }
        } // End New Folder - Raymond
        if (strlen(MODX_BASE_PATH) < strlen($filemanager_path)) {
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
                <?php extract(ls($startpath, compact('len', 'editablefiles', 'enablefileunzip', 'inlineviewablefiles', 'uploadablefiles', 'enablefiledownload', 'viewablefiles', 'protected_path', 'excludes', 'filemanager_path', 'base_path')), EXTR_OVERWRITE);
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
                echo $_lang['files_dirwritable'] . ' <b>' . (is_writable($startpath) == 1 ? $_lang['yes'] : $_lang['no']) . '.</b>'
                ?>
            </p>
            <?php if (((@ini_get("file_uploads") == true) || get_cfg_var("file_uploads") == 1) && is_writable($startpath)) {
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
            } ?>
            <div id="imageviewer"></div>
        </div>
    </div>
<?php if (get_by_key($_REQUEST, 'mode') == "edit" || get_by_key($_REQUEST, 'mode') == "view") { ?>
    <div class="section" id="file_editfile">
        <div class="navbar navbar-editor"><?= $_REQUEST['mode'] == "edit" ? $_lang['files_editfile'] : $_lang['files_viewfile'] ?></div>
        <?php
        $requested_path = ltrim($_REQUEST['path'] ?? '', '/');
        $filename = str_replace('\\', '/', realpath($filemanager_path . '/' . $requested_path));
        if (strpos($filename, $filemanager_path) !== 0 || !is_file($filename)) {
            evo()->webAlertAndQuit("Invalid path.");
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
