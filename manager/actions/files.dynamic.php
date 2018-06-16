<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!$modx->hasPermission('file_manager')) {
    $modx->webAlertAndQuit($_lang["error_no_privileges"]);
}
$token_check = checkToken();
$newToken = makeToken();

// settings
$theme_image_path = $modx->config['site_manager_url'] . 'media/style/' . $modx->config['manager_theme'] . '/images/';
$excludes = array(
    '.',
    '..',
    '.svn'
);
$alias_suffix = (!empty($friendly_url_suffix)) ? ',' . ltrim($friendly_url_suffix, '.') : '';
$editablefiles = explode(',', 'txt,php,tpl,less,sass,shtml,html,htm,xml,js,css,pageCache,htaccess,json,ini' . $alias_suffix);
$inlineviewablefiles = explode(',', 'txt,php,tpl,less,sass,html,htm,xml,js,css,pageCache,htaccess,json,ini' . $alias_suffix);
$viewablefiles = explode(',', 'jpg,gif,png,ico');

$editablefiles = add_dot($editablefiles);
$inlineviewablefiles = add_dot($inlineviewablefiles);
$viewablefiles = add_dot($viewablefiles);

$protected_path = array();
/* jp only
if($_SESSION['mgrRole']!=1)
{
*/
$protected_path[] = $modx->config['site_manager_path'];
$protected_path[] = $modx->config['base_path'] . 'temp/backup';
$protected_path[] = $modx->config['base_path'] . 'assets/backup';

if (!$modx->hasPermission('save_plugin')) {
    $protected_path[] = $modx->config['base_path'] . 'assets/plugins';
}
if (!$modx->hasPermission('save_snippet')) {
    $protected_path[] = $modx->config['base_path'] . 'assets/snippets';
}
if (!$modx->hasPermission('save_template')) {
    $protected_path[] = $modx->config['base_path'] . 'assets/templates';
}
if (!$modx->hasPermission('save_module')) {
    $protected_path[] = $modx->config['base_path'] . 'assets/modules';
}
if (!$modx->hasPermission('empty_cache')) {
    $protected_path[] = $modx->config['base_path'] . 'assets/cache';
}
if (!$modx->hasPermission('import_static')) {
    $protected_path[] = $modx->config['base_path'] . 'temp/import';
    $protected_path[] = $modx->config['base_path'] . 'assets/import';
}
if (!$modx->hasPermission('export_static')) {
    $protected_path[] = $modx->config['base_path'] . 'temp/export';
    $protected_path[] = $modx->config['base_path'] . 'assets/export';
}
/*
}
*/

// Mod added by Raymond
$enablefileunzip = true;
$enablefiledownload = true;
$newfolderaccessmode = $new_folder_permissions ? octdec($new_folder_permissions) : 0777;
$new_file_permissions = $new_file_permissions ? octdec($new_file_permissions) : 0666;
// End Mod -  by Raymond
// make arrays from the file upload settings
$upload_files = explode(',', $upload_files);
$upload_images = explode(',', $upload_images);
$upload_media = explode(',', $upload_media);
$upload_flash = explode(',', $upload_flash);
// now merge them
$uploadablefiles = array_merge($upload_files, $upload_images, $upload_media, $upload_flash);
$uploadablefiles = add_dot($uploadablefiles);


// end settings

// get the current work directory
if (isset($_REQUEST['path']) && !empty($_REQUEST['path'])) {
    $_REQUEST['path'] = str_replace('..', '', $_REQUEST['path']);
    $startpath = is_dir($_REQUEST['path']) ? $_REQUEST['path'] : removeLastPath($_REQUEST['path']);
} else {
    $startpath = $filemanager_path;
}
$startpath = rtrim($startpath, '/');

if (!is_readable($startpath)) {
    $modx->webAlertAndQuit($_lang["not_readable_dir"]);
}

// Raymond: get web start path for showing pictures
$rf = realpath($filemanager_path);
$rw = realpath('../');
$webstart_path = str_replace('\\', '/', str_replace($rw, '', $rf));
if (substr($webstart_path, 0, 1) == '/') {
    $webstart_path = '..' . $webstart_path;
} else {
    $webstart_path = '../' . $webstart_path;
}

?>
    <script type="text/javascript">

        var current_path = '<?= $startpath;?>';

        function viewfile (url)
        {
            var el = document.getElementById('imageviewer');
            el.innerHTML = '<img src="' + url + '" />';
            el.style.display = 'block'
        }

        function setColor (o, state)
        {
            if (!o){return;
}
            if (state && o.style){o.style.backgroundColor = '#eeeeee';
}else if (o.style){o.style.backgroundColor = 'transparent';
}
        }

        function confirmDelete ()
        {
            return confirm("<?= $_lang['confirm_delete_file'] ?>");
        }

        function confirmDeleteFolder (status)
        {
            if (status !== 'file_exists')return confirm("<?= $_lang['confirm_delete_dir'] ?>");else return confirm("<?= $_lang['confirm_delete_dir_recursive'] ?>");
        }

        function confirmUnzip ()
        {
            return confirm("<?= $_lang['confirm_unzip_file'] ?>");
        }

        function unzipFile (file)
        {
            if (confirmUnzip()) {
                window.location.href = "index.php?a=31&mode=unzip&path=" + current_path + '/&file=' + file + "&token=<?= $newToken;?>";
                return false;
            }
        }

        function getFolderName (a)
        {
            var f = window.prompt("<?= $_lang['files_dynamic_new_file_name'] ?>", '');
            if (f) a.href += encodeURI(f);
            return !!(f);
        }

        function getFileName (a)
        {
            var f = window.prompt("<?= $_lang['files_dynamic_new_file_name'] ?>", '');
            if (f) a.href += encodeURI(f);
            return !!(f);
        }

        function deleteFolder (folder, status)
        {
            if (confirmDeleteFolder(status)) {
                window.location.href = "index.php?a=31&mode=deletefolder&path=" + current_path + "&folderpath=" + current_path + '/' + folder + "&token=<?= $newToken;?>";
                return false;
            }
        }

        function deleteFile (file)
        {
            if (confirmDelete()) {
                window.location.href = "index.php?a=31&mode=delete&path=" + current_path + '/' + file + "&token=<?= $newToken;?>";
                return false;
            }
        }

        function duplicateFile (file)
        {
            var newFilename = prompt("<?= $_lang["files_dynamic_new_file_name"] ?>", file);
            if (newFilename !== null && newFilename !== file) {
                window.location.href = "index.php?a=31&mode=duplicate&path=" + current_path + '/' + file + "&newFilename=" + newFilename + "&token=<?= $newToken;?>";
            }
        }

        function renameFolder (dir)
        {
            var newDirname = prompt("<?= $_lang["files_dynamic_new_folder_name"] ?>", dir);
            if (newDirname !== null && newDirname !== dir) {
                window.location.href = "index.php?a=31&mode=renameFolder&path=" + current_path + '&dirname=' + dir + "&newDirname=" + newDirname + "&token=<?= $newToken;?>";
            }
        }

        function renameFile (file)
        {
            var newFilename = prompt("<?= $_lang["files_dynamic_new_file_name"] ?>", file);
            if (newFilename !== null && newFilename !== file) {
                window.location.href = "index.php?a=31&mode=renameFile&path=" + current_path + '/' + file + "&newFilename=" + newFilename + "&token=<?= $newToken;?>";
            }
        }

    </script>

    <h1>
        <i class="fa fa-folder-open-o"></i><?= $_lang['manage_files'] ?>
    </h1>

    <div id="actions">
        <div class="btn-group">
            <?php if ($_POST['mode'] == 'save' || $_GET['mode'] == 'edit') : ?>
                <a class="btn btn-success" href="javascript:;" onclick="documentDirty=false;document.editFile.submit();">
                    <i class="<?= $_style["files_save"] ?>"></i><span><?= $_lang['save'] ?></span>
                </a>
            <?php endif ?>
            <?php
            if (isset($_GET['mode']) && $_GET['mode'] !== 'drill') {
                $href = 'a=31&path=' . urlencode($_REQUEST['path']);
            } else {
                $href = 'a=2';
            }
            if (is_writable($startpath)) {
                $ph = array();
                $ph['style_path'] = $theme_image_path;
                $tpl = '<a class="btn btn-secondary" href="[+href+]" onclick="return getFolderName(this);"><i class="[+image+]"></i><span>[+subject+]</span></a>';
                $ph['image'] = $_style['files_folder-open'];
                $ph['subject'] = $_lang['add_folder'];
                $ph['href'] = 'index.php?a=31&mode=newfolder&path=' . urlencode($startpath) . '&name=';
                $_ = parsePlaceholder($tpl, $ph);

                $tpl = '<a class="btn btn-secondary" href="[+href+]" onclick="return getFileName(this);"><i class="[+image+]"></i><span>' . $_lang['files.dynamic.php1'] . '</span></a>';
                $ph['image'] = $_style['files_page_html'];
                $ph['href'] = 'index.php?a=31&mode=newfile&path=' . urlencode($startpath) . '&name=';
                $_ .= parsePlaceholder($tpl, $ph);
                echo $_;
            }
            ?>
            <a id="Button5" class="btn btn-secondary" href="javascript:;" onclick="documentDirty=false;document.location.href='index.php?<?= $href ?>';">
                <i class="<?= $_style["actions_cancel"] ?>"></i><span><?= $_lang['cancel'] ?></span>
            </a>
        </div>
    </div>

    <div id="ManageFiles">
        <div class="container breadcrumbs">
            <?php
            if (!empty($_FILES['userfile'])) {
                $information = fileupload();
            } elseif ($_POST['mode'] == 'save') {
                echo textsave();
            } elseif ($_REQUEST['mode'] == 'delete') {
                echo delete_file();
            }

            if (in_array($startpath, $protected_path)) {
                $modx->webAlertAndQuit($_lang["files.dynamic.php2"]);
            }

            $tpl = '<i class="[+image+] FilesTopFolder"></i>[+subject+]';
            $ph = array();
            $ph['style_path'] = $theme_image_path;
            // To Top Level with folder icon to the left
            if ($startpath == $filemanager_path || $startpath . '/' == $filemanager_path) {
                $ph['image'] = '' . $_style['files_top'] . '';
                $ph['subject'] = '<span>Top</span>';
            } else {
                $ph['image'] = '' . $_style['files_top'] . '';
                $ph['subject'] = '<a href="index.php?a=31&mode=drill&path=' . $filemanager_path . '">Top</a>/';
            }

            echo parsePlaceholder($tpl, $ph);

            $len = strlen($filemanager_path);
            if (substr($startpath, $len, strlen($startpath)) == '') {
                $topic_path = '/';
            } else {
                $topic_path = substr($startpath, $len, strlen($startpath));
                $pieces = explode('/', rtrim($topic_path, '/'));
                $path = '';
                $count = count($pieces);
                foreach ($pieces as $i => $v) {
                    if (empty($v)) {
                        continue;
                    }
                    $path .= rtrim($v, '/') . '/';
                    if (1 < $count) {
                        $href = 'index.php?a=31&mode=drill&path=' . urlencode($filemanager_path . $path);
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
        if (substr(strtolower(str_replace('//', '/', $startpath . "/")), 0, $len) != strtolower(str_replace('//', '/', $filemanager_path . '/'))) {
            $modx->webAlertAndQuit($_lang["files_access_denied"]);
        }

        // Unzip .zip files - by Raymond
        if ($enablefileunzip && $_REQUEST['mode'] == 'unzip' && is_writable($startpath)) {
            if (!$err = unzip(realpath("{$startpath}/" . $_REQUEST['file']), realpath($startpath))) {
                echo '<span class="warning"><b>' . $_lang['file_unzip_fail'] . ($err === 0 ? 'Missing zip library (php_zip.dll / zip.so)' : '') . '</b></span><br /><br />';
            } else {
                echo '<span class="success"><b>' . $_lang['file_unzip'] . '</b></span><br /><br />';
            }
        }
        // End Unzip - Raymond


        // New Folder & Delete Folder option - Raymond
        if (is_writable($startpath)) {
            // Delete Folder
            if ($_REQUEST['mode'] == 'deletefolder') {
                $folder = $_REQUEST['folderpath'];
                if (!$token_check || !@rrmdir($folder)) {
                    echo '<span class="warning"><b>' . $_lang['file_folder_not_deleted'] . '</b></span><br /><br />';
                } else {
                    echo '<span class="success"><b>' . $_lang['file_folder_deleted'] . '</b></span><br /><br />';
                }
            }

            // Create folder here
            if ($_REQUEST['mode'] == 'newfolder') {
                $old_umask = umask(0);
                $foldername = str_replace('..\\', '', str_replace('../', '', $_REQUEST['name']));
                if (!mkdirs("{$startpath}/{$foldername}", 0777)) {
                    echo '<span class="warning"><b>', $_lang['file_folder_not_created'], '</b></span><br /><br />';
                } else {
                    if (!@chmod($startpath . '/' . $foldername, $newfolderaccessmode)) {
                        echo '<span class="warning"><b>' . $_lang['file_folder_chmod_error'] . '</b></span><br /><br />';
                    } else {
                        echo '<span class="success"><b>' . $_lang['file_folder_created'] . '</b></span><br /><br />';
                    }
                }
                umask($old_umask);
            }
            // Create file here
            if ($_REQUEST['mode'] == 'newfile') {
                $old_umask = umask(0);
                $filename = str_replace('..\\', '', str_replace('../', '', $_REQUEST['name']));
                $filename = $modx->getDatabase()->escape($filename);

                if (!checkExtension($filename)) {
                    echo '<span class="warning"><b>' . $_lang['files_filetype_notok'] . '</b></span><br /><br />';
                } elseif (preg_match('@(\\\\|\/|\:|\;|\,|\*|\?|\"|\<|\>|\||\?)@', $filename) !== 0) {
                    echo $_lang['files.dynamic.php3'];
                } else {
                    $rs = file_put_contents("{$startpath}/{$filename}", '');
                    if ($rs === false) {
                        echo '<span class="warning"><b>', $_lang['file_folder_not_created'], '</b></span><br /><br />';
                    } else {
                        echo $_lang['files.dynamic.php4'];
                    }
                    umask($old_umask);
                }
            }
            // Duplicate file here
            if ($_REQUEST['mode'] == 'duplicate') {
                $old_umask = umask(0);
                $filename = $_REQUEST['path'];
                $filename = $modx->getDatabase()->escape($filename);
                $newFilename = str_replace('..\\', '', str_replace('../', '', $_REQUEST['newFilename']));
                $newFilename = $modx->getDatabase()->escape($newFilename);

                if (!checkExtension($newFilename)) {
                    echo '<span class="warning"><b>' . $_lang['files_filetype_notok'] . '</b></span><br /><br />';
                } elseif (preg_match('@(\\\\|\/|\:|\;|\,|\*|\?|\"|\<|\>|\||\?)@', $newFilename) !== 0) {
                    echo $_lang['files.dynamic.php3'];
                } else {
                    if (!copy($filename, MODX_BASE_PATH . $newFilename)) {
                        echo $_lang['files.dynamic.php5'];
                    }
                    umask($old_umask);
                }
            }
            // Rename folder here
            if ($_REQUEST['mode'] == 'renameFolder') {
                $old_umask = umask(0);
                $dirname = $_REQUEST['path'] . '/' . $_REQUEST['dirname'];
                $dirname = $modx->getDatabase()->escape($dirname);
                $newDirname = str_replace(array(
                    '..\\',
                    '../',
                    '\\',
                    '/'
                ), '', $_REQUEST['newDirname']);
                $newDirname = $modx->getDatabase()->escape($newDirname);

                if (preg_match('@(\\\\|\/|\:|\;|\,|\*|\?|\"|\<|\>|\||\?)@', $newDirname) !== 0) {
                    echo $_lang['files.dynamic.php3'];
                } else if (!rename($dirname, $_REQUEST['path'] . '/' . $newDirname)) {
                    echo '<span class="warning"><b>', $_lang['file_folder_not_created'], '</b></span><br /><br />';
                }
                umask($old_umask);
            }
            // Rename file here
            if ($_REQUEST['mode'] == 'renameFile') {
                $old_umask = umask(0);
                $path = dirname($_REQUEST['path']);
                $filename = $_REQUEST['path'];
                $filename = $modx->getDatabase()->escape($filename);
                $newFilename = str_replace(array(
                    '..\\',
                    '../',
                    '\\',
                    '/'
                ), '', $_REQUEST['newFilename']);
                $newFilename = $modx->getDatabase()->escape($newFilename);

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
        }
        // End New Folder - Raymond

        $filesize = 0;
        $files = 0;
        $folders = 0;
        $dirs_array = array();
        $files_array = array();
        if (strlen(MODX_BASE_PATH) < strlen($filemanager_path)) {
            $len--;
        }

        ?>
        <div class="table-responsive">
            <table id="FilesTable" class="table data">
                <thead>
                <tr>
                    <th><?= $_lang['files_filename'] ?></th>
                    <th style="width: 1%;"><?= $_lang['files_modified'] ?></th>
                    <th style="width: 1%;"><?= $_lang['files_filesize'] ?></th>
                    <th style="width: 1%;" class="text-nowrap"><?= $_lang['files_fileoptions'] ?></th>
                </tr>
                </thead>
                <?php
                ls($startpath);
                echo "\n\n\n";
                if ($folders == 0 && $files == 0) {
                    echo '<tr><td colspan="4"><i class="' . $_style['files_deleted_folder'] . ' FilesDeletedFolder"></i> <span style="color:#888;cursor:default;"> ' . $_lang['files_directory_is_empty'] . ' </span></td></tr>';
                }
                ?>
            </table>
        </div>

        <div class="container">
            <p>
                <?php
                echo $_lang['files_directories'] . ': <b>' . $folders . '</b> ';
                echo $_lang['files_files'] . ': <b>' . $files . '</b> ';
                echo $_lang['files_data'] . ': <b><span dir="ltr">' . $modx->nicesize($filesizes) . '</span></b> ';
                echo $_lang['files_dirwritable'] . ' <b>' . (is_writable($startpath) == 1 ? $_lang['yes'] . '.' : $_lang['no']) . '.</b>'
                ?>
            </p>

            <?php
            if (((@ini_get("file_uploads") == true) || get_cfg_var("file_uploads") == 1) && is_writable($startpath)) {
                @ini_set("upload_max_filesize", $upload_maxsize); // modified by raymond
                ?>

                <form name="upload" enctype="multipart/form-data" action="index.php" method="post">
                    <input type="hidden" name="MAX_FILE_SIZE" value="<?= isset($upload_maxsize) ? $upload_maxsize : 3145728 ?>">
                    <input type="hidden" name="a" value="31">
                    <input type="hidden" name="path" value="<?= $startpath ?>">

                    <?php if (isset($information)) {
                        echo $information;
                    } ?>

                    <div id="uploader">
                        <input type="file" name="userfile[]" onchange="document.upload.submit();" multiple>
                        <a class="btn btn-secondary" href="javascript:;" onclick="document.upload.submit()"><?= $_lang['files_uploadfile'] ?></a>
                    </div>
                </form>
                <?php
            } else {
                echo "<p>" . $_lang['files_upload_inhibited_msg'] . "</p>";
            }
            ?>
            <div id="imageviewer"></div>
        </div>

    </div>
<?php

if ($_REQUEST['mode'] == "edit" || $_REQUEST['mode'] == "view") {
    ?>

    <div class="section" id="file_editfile">
        <div class="navbar navbar-editor"><?= $_REQUEST['mode'] == "edit" ? $_lang['files_editfile'] : $_lang['files_viewfile'] ?></div>
        <?php
        $filename = $_REQUEST['path'];
        $buffer = file_get_contents($filename);
        // Log the change
        logFileChange('view', $filename);
        if ($buffer === false) {
            $modx->webAlertAndQuit("Error opening file for reading.");
        }
        ?>
        <form action="index.php" method="post" name="editFile">
            <input type="hidden" name="a" value="31" />
            <input type="hidden" name="mode" value="save" />
            <input type="hidden" name="path" value="<?= $_REQUEST['path'] ?>" />
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td>
                        <textarea dir="ltr" name="content" id="content" class="phptextarea"><?= htmlentities($buffer, ENT_COMPAT, $modx_manager_charset) ?></textarea>
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
    $evtOut = $modx->invokeEvent('OnRichTextEditorInit', array(
        'editor' => 'Codemirror',
        'elements' => array(
            'content',
        ),
        'contentType' => $contentType,
        'readOnly' => $_REQUEST['mode'] == 'edit' ? false : true
    ));
    if (is_array($evtOut)) {
        echo implode('', $evtOut);
    }

}

