<?php
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

if(!function_exists('determineIcon')) {
    /**
     * @param string $file
     * @param string $selFile
     * @param string $mode
     * @return string
     */
    function determineIcon($file, $selFile, $mode)
    {
        $icons = array(
            'default' => 'fa fa-file-o',
            'edit'    => 'fa fa-pencil-square-o',
            'view'    => 'fa fa-eye'
        );
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
        $classNames = array(
            'default' => '',
            'edit'    => 'editRow',
            'view'    => 'viewRow'
        );
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
     */
    function ls($curpath)
    {
        global $_lang, $theme_image_path, $_style;
        global $excludes, $protected_path, $editablefiles, $inlineviewablefiles, $viewablefiles, $enablefileunzip, $enablefiledownload, $uploadablefiles, $folders, $files, $filesizes, $len, $dirs_array, $files_array, $webstart_path, $modx;
        $dircounter = 0;
        $filecounter = 0;
        $curpath = str_replace('//', '/', $curpath . '/');

        if (!is_dir($curpath)) {
            echo 'Invalid path "', $curpath, '"<br />';

            return;
        }
        $dir = scandir($curpath);

        // first, get info
        foreach ($dir as $file) {
            $newpath = $curpath . $file;
            if ($file === '..' || $file === '.') {
                continue;
            }
            if (is_dir($newpath)) {
                $dirs_array[$dircounter]['dir'] = $newpath;
                $dirs_array[$dircounter]['stats'] = lstat($newpath);
                if ($file === '..' || $file === '.') {
                    continue;
                } elseif (!in_array($file, $excludes) && !in_array($newpath, $protected_path)) {
                    $dirs_array[$dircounter]['text'] = '<i class="' . $_style['files_folder'] . ' FilesFolder"></i> <a href="index.php?a=31&mode=drill&path=' . urlencode($newpath) . '"><b>' . $file . '</b></a>';

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

                    $dirs_array[$dircounter]['delete'] = is_writable($curpath) ? '<a href="javascript: deleteFolder(\'' . urlencode($file) . '\',\'' . $file_exists . '\');"><i class="' . $_style['files_delete'] . '" title="' . $_lang['file_delete_folder'] . '"></i></a>' : '';
                } else {
                    $dirs_array[$dircounter]['text'] = '<span><i class="' . $_style['files_deleted_folder'] . ' FilesDeletedFolder"></i> ' . $file . '</span>';
                    $dirs_array[$dircounter]['delete'] = is_writable($curpath) ? '<span class="disabled"><i class="' . $_style['files_delete'] . '" title="' . $_lang['file_delete_folder'] . '"></i></span>' : '';
                }

                $dirs_array[$dircounter]['rename'] = is_writable($curpath) ? '<a href="javascript:renameFolder(\'' . urlencode($file) . '\');"><i class="' . $_style['files_rename'] . '" title="' . $_lang['rename'] . '"></i></a> ' : '';

                // increment the counter
                $dircounter++;
            } else {
                $type = getExtension($newpath);
                $files_array[$filecounter]['file'] = $newpath;
                $files_array[$filecounter]['stats'] = lstat($newpath);
                $files_array[$filecounter]['text'] = determineIcon($newpath, $_REQUEST['path'],
                        $_REQUEST['mode']) . ' ' . $file;
                $files_array[$filecounter]['view'] = (in_array($type,
                    $viewablefiles)) ? '<a href="javascript:;" onclick="viewfile(\'' . $webstart_path . substr($newpath,
                        $len,
                        strlen($newpath)) . '\');"><i class="' . $_style['files_view'] . '" title="' . $_lang['files_viewfile'] . '"></i></a>' : (($enablefiledownload && in_array($type,
                        $uploadablefiles)) ? '<a href="' . $webstart_path . implode('/', array_map('rawurlencode',
                        explode('/', substr($newpath, $len,
                            strlen($newpath))))) . '" style="cursor:pointer;"><i class="' . $_style['files_download'] . '" title="' . $_lang['file_download_file'] . '"></i></a>' : '<span class="disabled"><i class="' . $_style['files_view'] . '" title="' . $_lang['files_viewfile'] . '"></i></span>');
                $files_array[$filecounter]['view'] = (in_array($type,
                    $inlineviewablefiles)) ? '<a href="index.php?a=31&mode=view&path=' . urlencode($newpath) . '"><i class="' . $_style['files_view'] . '" title="' . $_lang['files_viewfile'] . '"></i></a>' : $files_array[$filecounter]['view'];
                $files_array[$filecounter]['unzip'] = ($enablefileunzip && $type == '.zip') ? '<a href="javascript:unzipFile(\'' . urlencode($file) . '\');"><i class="' . $_style['files_unzip'] . '" title="' . $_lang['file_download_unzip'] . '"></i></a>' : '';
                $files_array[$filecounter]['edit'] = (in_array($type,
                        $editablefiles) && is_writable($curpath) && is_writable($newpath)) ? '<a href="index.php?a=31&mode=edit&path=' . urlencode($newpath) . '#file_editfile"><i class="' . $_style['files_edit'] . '" title="' . $_lang['files_editfile'] . '"></i></a>' : '<span class="disabled"><i class="' . $_style['files_edit'] . '" title="' . $_lang['files_editfile'] . '"></i></span>';
                $files_array[$filecounter]['duplicate'] = (in_array($type,
                        $editablefiles) && is_writable($curpath) && is_writable($newpath)) ? '<a href="javascript:duplicateFile(\'' . urlencode($file) . '\');"><i class="' . $_style['files_duplicate'] . '" title="' . $_lang['duplicate'] . '"></i></a>' : '<span class="disabled"><i class="' . $_style['files_duplicate'] . '" align="absmiddle" title="' . $_lang['duplicate'] . '"></i></span>';
                $files_array[$filecounter]['rename'] = (in_array($type,
                        $editablefiles) && is_writable($curpath) && is_writable($newpath)) ? '<a href="javascript:renameFile(\'' . urlencode($file) . '\');"><i class="' . $_style['files_rename'] . '" align="absmiddle" title="' . $_lang['rename'] . '"></i></a>' : '<span class="disabled"><i class="' . $_style['files_rename'] . '" align="absmiddle" title="' . $_lang['rename'] . '"></i></span>';
                $files_array[$filecounter]['delete'] = is_writable($curpath) && is_writable($newpath) ? '<a href="javascript:deleteFile(\'' . urlencode($file) . '\');"><i class="' . $_style['files_delete'] . '" title="' . $_lang['file_delete_file'] . '"></i></a>' : '<span class="disabled"><i class="' . $_style['files_delete'] . '" title="' . $_lang['file_delete_file'] . '"></i></span>';

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
            echo '<td class="text-nowrap">' . $modx->toDateFormat($dirs_array[$i]['stats']['9']) . '</td>';
            echo '<td class="text-right">' . $modx->nicesize($dirs_array[$i]['stats']['7']) . '</td>';
            echo '<td class="actions text-right">';
            echo $dirs_array[$i]['rename'];
            echo $dirs_array[$i]['delete'];
            echo '</td>';
            echo '</tr>';
        }

        // dump array entries for files
        $files = count($files_array);
        sort($files_array); // sorting the array alphabetically (Thanks pxl8r!)
        for ($i = 0; $i < $files; $i++) {
            $filesizes += $files_array[$i]['stats']['7'];
            echo '<tr ' . markRow($files_array[$i]['file'], $_REQUEST['path'], $_REQUEST['mode']) . '>';
            echo '<td>' . $files_array[$i]['text'] . '</td>';
            echo '<td class="text-nowrap">' . $modx->toDateFormat($files_array[$i]['stats']['9']) . '</td>';
            echo '<td class="text-right">' . $modx->nicesize($files_array[$i]['stats']['7']) . '</td>';
            echo '<td class="actions text-right">';
            echo $files_array[$i]['unzip'];
            echo $files_array[$i]['view'];
            echo $files_array[$i]['edit'];
            echo $files_array[$i]['duplicate'];
            echo $files_array[$i]['rename'];
            echo $files_array[$i]['delete'];
            echo '</td>';
            echo '</tr>';
        }

        return;
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
        global $uploadablefiles;

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
        $zip = zip_open($file);
        if ($zip) {
            $old_umask = umask(0);
            $path = rtrim($path, '/') . '/';
            while ($zip_entry = zip_read($zip)) {
                if (zip_entry_filesize($zip_entry) > 0) {
                    // str_replace must be used under windows to convert "/" into "\"
                    $zip_entry_name = zip_entry_name($zip_entry);
                    $complete_path = $path . str_replace('\\', '/', dirname($zip_entry_name));
                    $complete_name = $path . str_replace('\\', '/', $zip_entry_name);
                    if (!file_exists($complete_path)) {
                        $tmp = '';
                        foreach (explode('/', $complete_path) AS $k) {
                            $tmp .= $k . '/';
                            if (!is_dir($tmp)) {
                                mkdir($tmp, 0777);
                            }
                        }
                    }
                    if (zip_entry_open($zip, $zip_entry, 'r')) {
                        file_put_contents($complete_name, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));
                        zip_entry_close($zip_entry);
                    }
                }
            }
            umask($old_umask);
            zip_close($zip);

            return true;
        }
        zip_close($zip);
    }
}

if(!function_exists('rrmdir')) {
    /**
     * @param string $dir
     * @return bool
     */
    function rrmdir($dir)
    {
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
        global $_lang, $startpath, $filemanager_path, $uploadablefiles, $new_file_permissions;
        $msg = '';
        foreach ($_FILES['userfile']['name'] as $i => $name) {
            if (empty($_FILES['userfile']['tmp_name'][$i])) {
                continue;
            }
            $userfile = array();

            $userfile['tmp_name'] = $_FILES['userfile']['tmp_name'][$i];
            $userfile['error'] = $_FILES['userfile']['error'][$i];
            $name = $_FILES['userfile']['name'][$i];
            if ($modx->config['clean_uploaded_filename'] == 1) {
                $nameparts = explode('.', $name);
                $nameparts = array_map(array(
                    $modx,
                    'stripAlias'
                ), $nameparts, array('file_manager'));
                $name = implode('.', $nameparts);
            }
            $userfile['name'] = $name;
            $userfile['type'] = $_FILES['userfile']['type'][$i];

            // this seems to be an upload action.
            $path = $modx->config['site_url'] . substr($startpath, strlen($filemanager_path), strlen($startpath));
            $path = rtrim($path, '/') . '/' . $userfile['name'];
            $msg .= $path;
            if ($userfile['error'] == 0) {
                $img = (strpos($userfile['type'],
                        'image') !== false) ? '<br /><img src="' . $path . '" height="75" />' : '';
                $msg .= "<p>" . $_lang['files_file_type'] . $userfile['type'] . ", " . $modx->nicesize(filesize($userfile['tmp_name'])) . $img . '</p>';
            }

            $userfilename = $userfile['tmp_name'];

            if (is_uploaded_file($userfilename)) {
                // file is uploaded file, process it!
                if (!checkExtension($userfile['name'])) {
                    $msg .= '<p><span class="warning">' . $_lang['files_filetype_notok'] . '</span></p>';
                } else {
                    if (@move_uploaded_file($userfile['tmp_name'], $_POST['path'] . '/' . $userfile['name'])) {
                        // Ryan: Repair broken permissions issue with file manager
                        if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
                            @chmod($_POST['path'] . "/" . $userfile['name'], $new_file_permissions);
                        }
                        // Ryan: End
                        $msg .= '<p><span class="success">' . $_lang['files_upload_ok'] . '</span></p><hr/>';

                        // invoke OnFileManagerUpload event
                        $modx->invokeEvent('OnFileManagerUpload', array(
                            'filepath' => $_POST['path'],
                            'filename' => $userfile['name']
                        ));
                        // Log the change
                        logFileChange('upload', $_POST['path'] . '/' . $userfile['name']);
                    } else {
                        $msg .= '<p><span class="warning">' . $_lang['files_upload_copyfailed'] . '</span> ' . $_lang["files_upload_permissions_error"] . '</p>';
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

        $msg = $_lang['editing_file'];
        $filename = $_POST['path'];
        $content = $_POST['content'];

        // Write $content to our opened file.
        if (file_put_contents($filename, $content) === false) {
            $msg .= '<span class="warning"><b>' . $_lang['file_not_saved'] . '</b></span><br /><br />';
        } else {
            $msg .= '<span class="success"><b>' . $_lang['file_saved'] . '</b></span><br /><br />';
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
        global $_lang, $token_check;

        $msg = sprintf($_lang['deleting_file'], str_replace('\\', '/', $_REQUEST['path']));

        $file = $_REQUEST['path'];
        if (!$token_check || !@unlink($file)) {
            $msg .= '<span class="warning"><b>' . $_lang['file_not_deleted'] . '</b></span><br /><br />';
        } else {
            $msg .= '<span class="success"><b>' . $_lang['file_deleted'] . '</b></span><br /><br />';
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
        $newToken = uniqid('');
        $_SESSION['token'] = $newToken;

        return $newToken;
    }
}
