<?php

/** This file is part of KCFinder project
 *
 * @desc Browser actions class
 * @package KCFinder
 * @version 2.54
 * @author Pavel Tzonkov <sunhater@sunhater.com>
 * @copyright 2010-2014 KCFinder Project
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @license http://www.opensource.org/licenses/lgpl-2.1.php LGPLv2
 * @link http://kcfinder.sunhater.com
 */

class browser extends uploader
{
    protected $action;
    protected $thumbsDir;
    protected $thumbsTypeDir;

    /**
     * browser constructor.
     * @param DocumentParser $modx
     */
    public function __construct(DocumentParser $modx)
    {
        parent::__construct($modx);

        if (isset($this->post['dir'])) {
            $dir = $this->checkInputDir($this->post['dir'], true, false);
            if ($dir === false) {
                unset($this->post['dir']);
            }
            $this->post['dir'] = $dir;
        }

        if (isset($this->get['dir'])) {
            $dir = $this->checkInputDir($this->get['dir'], true, false);
            if ($dir === false) {
                unset($this->get['dir']);
            }
            $this->get['dir'] = $dir;
        }

        $thumbsDir = $this->config['uploadDir'] . "/" . $this->config['thumbsDir'];
        if ((
                !is_dir($thumbsDir) &&
                !@mkdir($thumbsDir, $this->config['dirPerms'])
            ) ||

            !is_readable($thumbsDir) ||
            !dir::isWritable($thumbsDir) ||
            (
                !is_dir("$thumbsDir/{$this->type}") &&
                !@mkdir("$thumbsDir/{$this->type}", $this->config['dirPerms'])
            )
        ) {
            $this->errorMsg("Cannot access or create thumbnails folder.");
        }

        $this->thumbsDir = $thumbsDir;
        $this->thumbsTypeDir = "$thumbsDir/{$this->type}";

        // Remove temporary zip downloads if exists
        $files = dir::content($this->config['uploadDir'], array(
            'types'   => "file",
            'pattern' => '/^.*\.zip$/i'
        ));

        if (is_array($files) && count($files)) {
            $time = time();
            foreach ($files as $file) {
                if (is_file($file) && ($time - filemtime($file) > 3600)) {
                    unlink($file);
                }
            }
        }

        if (isset($this->get['theme']) &&
            ($this->get['theme'] == basename($this->get['theme'])) &&
            is_dir("themes/{$this->get['theme']}")
        ) {
            $this->config['theme'] = $this->get['theme'];
        }
    }

    /**
     *
     */
    public function action()
    {
        $act = isset($this->get['act']) ? $this->get['act'] : "browser";
        if (!preg_match('@^[0-9a-zA-Z_]+$@', $act)) {
            $this->errorMsg("Unknown error.");
        }
        if (!method_exists($this, "act_$act")) {
            $act = "browser";
        }
        $this->action = $act;
        $method = "act_$act";
        if ($this->config['disabled']) {
            $message = $this->label("You don't have permissions to browse server.");
            if (in_array($act, array("browser", "upload")) ||
                (substr($act, 0, 8) == "download")
            ) {
                $this->backMsg($message);
            } else {
                header("Content-Type: text/plain; charset={$this->charset}");
                die(json_encode(array('error' => $message)));
            }
        }

        if (!isset($this->session['dir'])) {
            $this->session['dir'] = $this->type;
        } else {
            $type = $this->getTypeFromPath($this->session['dir']);
            $dir = $this->config['uploadDir'] . "/" . $this->session['dir'];
            if (($type != $this->type) || !is_dir($dir) || !is_readable($dir)) {
                $this->session['dir'] = $this->type;
            }
        }
        $this->session['dir'] = path::normalize($this->session['dir']);

        if ($act == "browser") {
            header("X-UA-Compatible: chrome=1");
            header("Content-Type: text/html; charset={$this->charset}");
        } elseif (
            (substr($act, 0, 8) != "download") &&
            !in_array($act, array("thumb", "upload"))
        ) {
            header("Content-Type: text/plain; charset={$this->charset}");
        }

        $return = $this->$method();
        echo ($return === true)
            ? '{}'
            : $return;
    }

    /**
     * @return string
     */
    protected function act_browser()
    {
        if (isset($this->get['dir']) &&
            is_dir("{$this->typeDir}/{$this->get['dir']}") &&
            is_readable("{$this->typeDir}/{$this->get['dir']}")
        ) {
            $this->session['dir'] = path::normalize("{$this->type}/{$this->get['dir']}");
        }

        return $this->output();
    }

    /**
     * @return string
     */
    protected function act_init()
    {
        $tree = $this->getDirInfo($this->typeDir);
        $tree['dirs'] = $this->getTree($this->session['dir']);
        if (!is_array($tree['dirs']) || !count($tree['dirs'])) {
            unset($tree['dirs']);
        }
        $files = $this->getFiles($this->session['dir']);
        $dirWritable = dir::isWritable("{$this->config['uploadDir']}/{$this->session['dir']}");
        $data = array(
            'tree'        => &$tree,
            'files'       => &$files,
            'dirWritable' => $dirWritable
        );

        return json_encode($data);
    }

    /**
     *
     */
    protected function act_thumb()
    {
        $this->getDir($this->get['dir'], true);
        if (!isset($this->get['file']) || !isset($this->get['dir'])) {
            $this->sendDefaultThumb();
        }
        $file = $this->get['file'];
        if (basename($file) != $file) {
            $this->sendDefaultThumb();
        }
        $file = "{$this->thumbsDir}/{$this->type}/{$this->get['dir']}/$file";
        if (!is_file($file) || !is_readable($file)) {
            $file = "{$this->config['uploadDir']}/{$this->type}/{$this->get['dir']}/" . basename($file);
            if (!is_file($file) || !is_readable($file)) {
                $this->sendDefaultThumb($file);
            }
            $image = image::factory($this->imageDriver, $file);
            if ($image->initError) {
                $this->sendDefaultThumb($file);
            }
            list($tmp, $tmp, $type) = getimagesize($file);
            if (in_array($type, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)) &&
                ($image->width <= $this->config['thumbWidth']) &&
                ($image->height <= $this->config['thumbHeight'])
            ) {
                $mime =
                    ($type == IMAGETYPE_GIF) ? "gif" : (
                    ($type == IMAGETYPE_PNG) ? "png" : "jpeg");
                $mime = "image/$mime";
                httpCache::file($file, $mime);
            } else {
                $this->sendDefaultThumb($file);
            }
        }
        httpCache::file($file, "image/jpeg");
    }

    /**
     * @return string
     */
    protected function act_expand()
    {
        return json_encode(array('dirs' => $this->getDirs($this->postDir())));
    }

    /**
     * @return string
     */
    protected function act_chDir()
    {
        $this->postDir(); // Just for existing check
        $this->session['dir'] = $this->type . "/" . $this->post['dir'];
        $dirWritable = dir::isWritable("{$this->config['uploadDir']}/{$this->session['dir']}");

        return json_encode(array(
            'files'       => $this->getFiles($this->session['dir']),
            'dirWritable' => $dirWritable
        ));
    }

    /**
     * @return bool
     */
    protected function act_newDir()
    {
        if (!$this->config['access']['dirs']['create'] ||
            !isset($this->post['dir']) ||
            !isset($this->post['newDir'])
        ) {
            $this->errorMsg("Unknown error.");
        }

        $dir = $this->postDir();
        $newDir = $this->normalizeDirname(trim($this->post['newDir']));
        if (!strlen($newDir)) {
            $this->errorMsg("Please enter new folder name.");
        }
        if (preg_match('/[\/\\\\]/s', $newDir)) {
            $this->errorMsg("Unallowable characters in folder name.");
        }
        if (substr($newDir, 0, 1) == ".") {
            $this->errorMsg("Folder name shouldn't begins with '.'");
        }
        if (file_exists("$dir/$newDir")) {
            $this->errorMsg("A file or folder with that name already exists.");
        }
        if (!@mkdir("$dir/$newDir", $this->config['dirPerms'])) {
            $this->errorMsg("Cannot create {dir} folder.", array('dir' => $newDir));
        }

        return true;
    }

    /**
     * @return string
     */
    protected function act_renameDir()
    {
        if (!$this->config['access']['dirs']['rename'] ||
            !isset($this->post['dir']) ||
            !isset($this->post['newName'])
        ) {
            $this->errorMsg("Unknown error.");
        }

        $dir = $this->postDir();
        $newName = $this->normalizeDirname(trim($this->post['newName']));
        $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserRename', array(
            'element' => 'dir',
            'filepath' => realpath($dir),
            'newname' => &$newName
        ));
        if (!strlen($newName)) {
            $this->errorMsg("Please enter new folder name.");
        }
        if (preg_match('/[\/\\\\]/s', $newName)) {
            $this->errorMsg("Unallowable characters in folder name.");
        }
        if (substr($newName, 0, 1) == ".") {
            $this->errorMsg("Folder name shouldn't begins with '.'");
        }
        if (is_array($evtOut) && !empty($evtOut)) {
            $this->errorMsg(implode('\n', $evtOut));
        }
        if (!@rename($dir, dirname($dir) . "/$newName")) {
            $this->errorMsg("Cannot rename the folder.");
        }
        $thumbDir = "$this->thumbsTypeDir/{$this->post['dir']}";
        if (is_dir($thumbDir)) {
            @rename($thumbDir, dirname($thumbDir) . "/$newName");
        }
        $this->modx->invokeEvent('OnFileBrowserRename', array(
            'element' => 'dir',
            'filepath' => realpath($dir),
            'newname' => $newName
        ));

        return json_encode(array('name' => $newName));
    }

    /**
     * @return bool
     */
    protected function act_deleteDir()
    {
        if (!$this->config['access']['dirs']['delete'] ||
            !isset($this->post['dir']) ||
            !strlen(trim($this->post['dir']))
        ) {
            $this->errorMsg("Unknown error.");
        }

        $dir = $this->postDir();

        if (!dir::isWritable($dir)) {
            $this->errorMsg("Cannot delete the folder.");
        }

        $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserDelete', array(
            'element'  => 'dir',
            'filepath' => realpath($dir)
        ));
        if (is_array($evtOut) && !empty($evtOut)) {
            die(json_encode(array('error' => $evtOut)));
        }

        $result = !dir::prune($dir, false);
        if (is_array($result) && count($result)) {
            $this->errorMsg("Failed to delete {count} files/folders.",
                array('count' => count($result)));
        }
        $thumbDir = "$this->thumbsTypeDir/{$this->post['dir']}";
        if (is_dir($thumbDir)) {
            dir::prune($thumbDir);
        }
        $this->modx->invokeEvent('OnFileBrowserDelete', array(
            'element'  => 'dir',
            'filepath' => realpath($dir)
        ));

        return true;
    }

    /**
     * @return string
     */
    protected function act_upload()
    {
        $response = array('success' => false, 'message' => $this->label("Unknown error."));
        if (!$this->config['access']['files']['upload'] ||
            !isset($this->post['dir'])
        ) {
            return json_encode($response);
        }
        $dir = $this->postDir();
        if (!dir::isWritable($dir)) {
            $response['message'] = $this->label("Cannot access or write to upload folder.");

            return json_encode($response);
        }
        $response = $this->moveUploadFile($this->file, $dir);

        return json_encode($response);
    }

    /**
     *
     */
    protected function act_download()
    {
        $dir = $this->postDir();
        if (!isset($this->post['dir']) ||
            !isset($this->post['file']) ||
            strpos($this->post['file'], '../') !== false ||
            (false === ($file = "$dir/{$this->post['file']}")) ||
            !file_exists($file) || !is_readable($file)
        ) {
            $this->errorMsg("Unknown error.");
        }

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-Type: application/octet-stream");
        header('Content-Disposition: attachment; filename="' . str_replace('"', "_", $this->post['file']) . '"');
        header("Content-Transfer-Encoding:­ binary");
        header("Content-Length: " . filesize($file));
        readfile($file);
        die;
    }

    /**
     * @return bool
     */
    protected function act_rename()
    {
        $dir = $this->postDir();
        if (!$this->config['access']['files']['rename'] ||
            !isset($this->post['dir']) ||
            !isset($this->post['file']) ||
            strpos($this->post['file'], '../') !== false ||
            !isset($this->post['newName']) ||
            (false === ($file = "$dir/{$this->post['file']}")) ||
            !file_exists($file) || !is_readable($file) || !file::isWritable($file)
        ) {
            $this->errorMsg("Unknown error.");
        }

        if (isset($this->config['denyExtensionRename']) &&
            $this->config['denyExtensionRename'] &&
            (file::getExtension($this->post['file'], true) !==
                file::getExtension($this->post['newName'], true)
            )
        ) {
            $this->errorMsg("You cannot rename the extension of files!");
        }

        $newName = $this->normalizeFilename(trim($this->post['newName']));
        $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserRename', array(
            'element' => 'file',
            'filepath' => $dir,
            'filename' => $this->post['file'],
            'newname' => &$newName
        ));
        if (!strlen($newName)) {
            $this->errorMsg("Please enter new file name.");
        }
        if (preg_match('/[\/\\\\]/s', $newName)) {
            $this->errorMsg("Unallowable characters in file name.");
        }
        if (substr($newName, 0, 1) == ".") {
            $this->errorMsg("File name shouldn't begins with '.'");
        }
        $_newName = $newName;
        $newName = "$dir/$newName";
        if (file_exists($newName)) {
            $this->errorMsg("A file or folder with that name already exists.");
        }
        $ext = file::getExtension($newName);
        if (!$this->validateExtension($ext, $this->type)) {
            $this->errorMsg("Denied file extension.");
        }
        if (is_array($evtOut) && !empty($evtOut)) {
            $this->errorMsg(implode('\n', $evtOut));
        }
        if (!@rename($file, $newName)) {
            $this->errorMsg("Unknown error.");
        }
        $this->modx->invokeEvent('OnFileBrowserRename', array(
            'element' => 'file',
            'filepath' => $dir,
            'filename' => $this->post['file'],
            'newname' => $_newName
        ));
        $thumbDir = "{$this->thumbsTypeDir}/{$this->post['dir']}";
        $thumbFile = "$thumbDir/{$this->post['file']}";

        if (file_exists($thumbFile)) {
            @rename($thumbFile, "$thumbDir/" . basename($newName));
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function act_delete()
    {
        $dir = $this->postDir();

        if (!$this->config['access']['files']['delete'] ||
            !isset($this->post['dir']) ||
            !isset($this->post['file']) ||
            strpos($this->post['file'], '../') !== false ||
            (false === ($file = "$dir/{$this->post['file']}")) ||
            !file_exists($file) || !is_readable($file) || !file::isWritable($file)
        ) {
            $this->errorMsg("Cannot delete '{file}'.", array('file' => basename($file)));
        }

        $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserDelete', array(
            'element'  => 'file',
            'filename' => $this->post['file'],
            'filepath' => realpath($dir)
        ));

        if (is_array($evtOut) && !empty($evtOut)) {
            die(json_encode(array('error' => $evtOut)));
        }

        @unlink($file);

        $thumb = "{$this->thumbsTypeDir}/{$this->post['dir']}/{$this->post['file']}";
        if (file_exists($thumb)) {
            @unlink($thumb);
        }

        $this->modx->invokeEvent('OnFileBrowserDelete', array(
            'element'  => 'file',
            'filename' => $this->post['file'],
            'filepath' => realpath($dir)
        ));

        return true;
    }

    /**
     * @return bool|string
     */
    protected function act_cp_cbd()
    {
        $dir = $this->postDir();
        if (!$this->config['access']['files']['copy'] ||
            !isset($this->post['dir']) ||
            !is_dir($dir) || !is_readable($dir) || !dir::isWritable($dir) ||
            !isset($this->post['files']) || !is_array($this->post['files']) ||
            !count($this->post['files'])
        ) {
            $this->errorMsg("Unknown error.");
        }

        $error = array();
        foreach ($this->post['files'] as $file) {
            $file = path::normalize($file);
            if (substr($file, 0, 1) == ".") {
                continue;
            }
            $type = explode("/", $file);
            $type = $type[0];
            if ($type != $this->type) {
                continue;
            }
            $path = "{$this->config['uploadDir']}/$file";
            $base = basename($file);
            $replace = array('file' => $base);
            $ext = file::getExtension($base);
            $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserCopy', array(
                'oldpath'  => $path,
                'filename' => $base,
                'newpath' => realpath($dir)
            ));
            if (is_array($evtOut) && !empty($evtOut)) {
                $error[] = implode("\n", $evtOut);
            } elseif (!file_exists($path)) {
                $error[] = $this->label("The file '{file}' does not exist.", $replace);
            } elseif (substr($base, 0, 1) == ".") {
                $error[] = "$base: " . $this->label("File name shouldn't begins with '.'");
            } elseif (!$this->validateExtension($ext, $type)) {
                $error[] = "$base: " . $this->label("Denied file extension.");
            } elseif (file_exists("$dir/$base")) {
                $error[] = "$base: " . $this->label("A file or folder with that name already exists.");
            } elseif (!is_readable($path) || !is_file($path)) {
                $error[] = $this->label("Cannot read '{file}'.", $replace);
            } elseif (!@copy($path, "$dir/$base")) {
                $error[] = $this->label("Cannot copy '{file}'.", $replace);
            } else {
                if (function_exists("chmod")) {
                    @chmod("$dir/$base", $this->config['filePerms']);
                }
                $this->modx->invokeEvent('OnFileBrowserCopy', array(
                    'oldpath'  => $path,
                    'filename' => $base,
                    'newpath' => realpath($dir)
                ));
                $fromThumb = "{$this->thumbsDir}/$file";
                if (is_file($fromThumb) && is_readable($fromThumb)) {
                    $toThumb = "{$this->thumbsTypeDir}/{$this->post['dir']}";
                    if (!is_dir($toThumb)) {
                        @mkdir($toThumb, $this->config['dirPerms'], true);
                    }
                    $toThumb .= "/$base";
                    @copy($fromThumb, $toThumb);
                }
            }
        }
        if (count($error)) {
            return json_encode(array('error' => $error));
        }

        return true;
    }

    /**
     * @return bool|string
     */
    protected function act_mv_cbd()
    {
        $dir = $this->postDir();
        if (!$this->config['access']['files']['move'] ||
            !isset($this->post['dir']) ||
            !is_dir($dir) || !is_readable($dir) || !dir::isWritable($dir) ||
            !isset($this->post['files']) || !is_array($this->post['files']) ||
            !count($this->post['files'])
        ) {
            $this->errorMsg("Unknown error.");
        }

        $error = array();
        foreach ($this->post['files'] as $file) {
            $file = path::normalize($file);
            if (substr($file, 0, 1) == ".") {
                continue;
            }
            $type = explode("/", $file);
            $type = $type[0];
            if ($type != $this->type) {
                continue;
            }
            $path = "{$this->config['uploadDir']}/$file";
            $base = basename($file);
            $replace = array('file' => $base);
            $ext = file::getExtension($base);
            $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserMove', array(
                'oldpath'  => $path,
                'filename' => $base,
                'newpath' => realpath($dir)
            ));
            if (is_array($evtOut) && !empty($evtOut)) {
                $error[] = implode("\n", $evtOut);
            } elseif (!file_exists($path)) {
                $error[] = $this->label("The file '{file}' does not exist.", $replace);
            } elseif (substr($base, 0, 1) == ".") {
                $error[] = "$base: " . $this->label("File name shouldn't begins with '.'");
            } elseif (!$this->validateExtension($ext, $type)) {
                $error[] = "$base: " . $this->label("Denied file extension.");
            } elseif (file_exists("$dir/$base")) {
                $error[] = "$base: " . $this->label("A file or folder with that name already exists.");
            } elseif (!is_readable($path) || !is_file($path)) {
                $error[] = $this->label("Cannot read '{file}'.", $replace);
            } elseif (!file::isWritable($path) || !@rename($path, "$dir/$base")) {
                $error[] = $this->label("Cannot move '{file}'.", $replace);
            } else {
                if (function_exists("chmod")) {
                    @chmod("$dir/$base", $this->config['filePerms']);
                }
                $fromThumb = "{$this->thumbsDir}/$file";
                if (is_file($fromThumb) && is_readable($fromThumb)) {
                    $toThumb = "{$this->thumbsTypeDir}/{$this->post['dir']}";
                    if (!is_dir($toThumb)) {
                        @mkdir($toThumb, $this->config['dirPerms'], true);
                    }
                    $toThumb .= "/$base";
                    @rename($fromThumb, $toThumb);
                }
                $this->modx->invokeEvent('OnFileBrowserMove', array(
                    'oldpath'  => $path,
                    'filename' => $base,
                    'newpath' => realpath($dir)
                ));
            }
        }
        if (count($error)) {
            return json_encode(array('error' => $error));
        }

        return true;
    }

    /**
     * @return bool|string
     */
    protected function act_rm_cbd()
    {
        if (!$this->config['access']['files']['delete'] ||
            !isset($this->post['files']) ||
            !is_array($this->post['files']) ||
            !count($this->post['files'])
        ) {
            $this->errorMsg("Unknown error.");
        }

        $error = array();
        foreach ($this->post['files'] as $file) {
            $file = path::normalize($file);
            if (substr($file, 0, 1) == ".") {
                continue;
            }
            $type = explode("/", $file);
            $type = $type[0];
            if ($type != $this->type) {
                continue;
            }
            $path = "{$this->config['uploadDir']}/$file";
            $base = basename($file);
            $filepath = str_replace('/' . $base, '', $path);
            $replace = array('file' => $base);
            if (!is_file($path)) {
                $error[] = $this->label("The file '{file}' does not exist.", $replace);
            } else {
                $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserDelete', array(
                    'element'  => 'file',
                    'filename' => $base,
                    'filepath' => $filepath
                ));

                if (is_array($evtOut) && !empty($evtOut)) {
                    $error[] = implode("\n", $evtOut);
                } else {
                    if (!@unlink($path)) {
                        $error[] = $this->label("Cannot delete '{file}'.", $replace);
                    } else {
                        $this->modx->invokeEvent('OnFileBrowserDelete', array(
                            'element'  => 'file',
                            'filename' => $base,
                            'filepath' => $filepath
                        ));
                        $thumb = "{$this->thumbsDir}/$file";
                        if (is_file($thumb)) {
                            @unlink($thumb);
                        }
                    }
                }
            }
        }
        if (count($error)) {
            return json_encode(array('error' => $error));
        }

        return true;
    }

    /**
     * @throws Exception
     */
    protected function act_downloadDir()
    {
        $dir = $this->postDir();
        if (!isset($this->post['dir']) || $this->config['denyZipDownload']) {
            $this->errorMsg("Unknown error.");
        }
        $filename = basename($dir) . ".zip";
        do {
            $file = md5(time() . session_id());
            $file = "{$this->config['uploadDir']}/$file.zip";
        } while (file_exists($file));
        new zipFolder($file, $dir);
        header("Content-Type: application/x-zip");
        header('Content-Disposition: attachment; filename="' . str_replace('"', "_", $filename) . '"');
        header("Content-Length: " . filesize($file));
        readfile($file);
        unlink($file);
        die;
    }

    /**
     *
     */
    protected function act_downloadSelected()
    {
        $dir = $this->postDir();
        if (!isset($this->post['dir']) ||
            !isset($this->post['files']) ||
            !is_array($this->post['files']) ||
            $this->config['denyZipDownload']
        ) {
            $this->errorMsg("Unknown error.");
        }

        $zipFiles = array();
        foreach ($this->post['files'] as $file) {
            $file = path::normalize($file);
            if ((substr($file, 0, 1) == ".") || (strpos($file, '/') !== false)) {
                continue;
            }
            $file = "$dir/$file";
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }
            $zipFiles[] = $file;
        }

        do {
            $file = md5(time() . session_id());
            $file = "{$this->config['uploadDir']}/$file.zip";
        } while (file_exists($file));

        $zip = new ZipArchive();
        $res = $zip->open($file, ZipArchive::CREATE);
        if ($res === true) {
            foreach ($zipFiles as $cfile) {
                $zip->addFile($cfile, basename($cfile));
            }
            $zip->close();
        }
        header("Content-Type: application/x-zip");
        header('Content-Disposition: attachment; filename="selected_files_' . basename($file) . '"');
        header("Content-Length: " . filesize($file));
        readfile($file);
        unlink($file);
        die;
    }

    /**
     *
     */
    protected function act_downloadClipboard()
    {
        if (!isset($this->post['files']) ||
            !is_array($this->post['files']) ||
            $this->config['denyZipDownload']
        ) {
            $this->errorMsg("Unknown error.");
        }

        $zipFiles = array();
        foreach ($this->post['files'] as $file) {
            $file = path::normalize($file);
            if ((substr($file, 0, 1) == ".")) {
                continue;
            }
            $type = explode("/", $file);
            $type = $type[0];
            if ($type != $this->type) {
                continue;
            }
            $file = $this->config['uploadDir'] . "/$file";
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }
            $zipFiles[] = $file;
        }

        do {
            $file = md5(time() . session_id());
            $file = "{$this->config['uploadDir']}/$file.zip";
        } while (file_exists($file));

        $zip = new ZipArchive();
        $res = $zip->open($file, ZipArchive::CREATE);
        if ($res === true) {
            foreach ($zipFiles as $cfile) {
                $zip->addFile($cfile, basename($cfile));
            }
            $zip->close();
        }
        header("Content-Type: application/x-zip");
        header('Content-Disposition: attachment; filename="clipboard_' . basename($file) . '"');
        header("Content-Length: " . filesize($file));
        readfile($file);
        unlink($file);
        die;
    }

    /**
     * @param $file
     * @param $dir
     * @return array
     */
    protected function moveUploadFile($file, $dir)
    {
        $response = array('success' => false, 'message' => $this->label('Unknown error.'));
        $message = $this->checkUploadedFile($file);

        if ($message !== true) {
            if (isset($file['tmp_name'])) {
                @unlink($file['tmp_name']);
            }
            $response['message'] = $message;

            return $response;
        }

        $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserUpload', array(
            'file'     => &$file,
            'filepath' => realpath($dir)
        ));

        if (is_array($evtOut) && !empty($evtOut)) {
            $response['message'] = $evtOut;

            return $response;
        }
        $filename = $this->normalizeFilename($file['name']);
        $target = "$dir/" . file::getInexistantFilename($filename, $dir);

        if (!@move_uploaded_file($file['tmp_name'], $target) &&
            !@rename($file['tmp_name'], $target) &&
            !@copy($file['tmp_name'], $target)
        ) {
            @unlink($file['tmp_name']);

            $response['message'] = $this->label("Cannot move uploaded file to target folder.");

            return $response;
        } elseif (function_exists('chmod')) {
            chmod($target, $this->config['filePerms']);
        }

        $this->modx->invokeEvent('OnFileBrowserUpload', array(
            'filepath' => realpath($dir),
            'filename' => str_replace("/", "", str_replace($dir, "", realpath($target)))
        ));

        $this->makeThumb($target);
        $response['success'] = true;

        return $response;
    }

    /**
     * @param null $file
     */
    protected function sendDefaultThumb($file = null)
    {
        if ($file !== null) {
            $ext = file::getExtension($file);
            $thumb = "themes/{$this->config['theme']}/img/files/big/$ext.png";
        }
        if (!isset($thumb) || !file_exists($thumb)) {
            $thumb = "themes/{$this->config['theme']}/img/files/big/..png";
        }
        header("Content-Type: image/png");
        readfile($thumb);
        die;
    }

    /**
     * @param $dir
     * @return array
     */
    protected function getFiles($dir)
    {
        $thumbDir = "{$this->config['uploadDir']}/{$this->config['thumbsDir']}/$dir";
        $dir = "{$this->config['uploadDir']}/$dir";
        $return = array();
        $files = dir::content($dir, array('types' => "file"));
        if ($files === false) {
            return $return;
        }

        foreach ($files as $file) {
            $ext = file::getExtension($file);
            $smallThumb = false;
            $preview = false;
            if (in_array(strtolower($ext), array('png', 'jpg', 'gif', 'jpeg'))) {
                $size = @getimagesize($file);
                if (is_array($size) && count($size)) {
                    $preview = true;
                    if (!$this->config['noThumbnailsRecreation']) {
                        $thumb_file = "$thumbDir/" . basename($file);
                        if (!is_file($thumb_file) || filemtime($file) > filemtime($thumb_file)) {
                            $this->makeThumb($file);
                        }
                        $smallThumb =
                            ($size[0] <= $this->config['thumbWidth']) &&
                            ($size[1] <= $this->config['thumbHeight']) &&
                            in_array($size[2], array(IMAGETYPE_GIF, IMAGETYPE_PNG, IMAGETYPE_JPEG));
                    }
                }
            }
            $stat = stat($file);
            if ($stat === false) {
                continue;
            }
            $name = basename($file);
            $types = $this->config['types'];
            $types = explode(' ', $types['images'] . ' ' . $types['image']);
            if (substr($name, 0, 1) == '.' && !$this->config['showHiddenFiles']) {
                continue;
            }
            if ($this->type == 'images' && !in_array(strtolower($ext), $types)) {
                continue;
            }
            $bigIcon = file_exists("themes/{$this->config['theme']}/img/files/big/$ext.png");
            $smallIcon = file_exists("themes/{$this->config['theme']}/img/files/small/$ext.png");
            $thumb = file_exists("$thumbDir/$name");
            $return[] = array(
                'name'       => stripcslashes($name),
                'size'       => $stat['size'],
                'mtime'      => $stat['mtime'],
                'date'       => @strftime($this->dateTimeSmall, $stat['mtime']),
                'readable'   => is_readable($file),
                'writable'   => file::isWritable($file),
                'bigIcon'    => $bigIcon,
                'smallIcon'  => $smallIcon,
                'thumb'      => $thumb,
                'smallThumb' => $smallThumb,
                'preview'    => $preview
            );
        }

        return $return;
    }

    /**
     * @param $dir
     * @param int $index
     * @return array|bool
     */
    protected function getTree($dir, $index = 0)
    {
        $path = explode("/", $dir);

        $pdir = "";
        for ($i = 0; ($i <= $index && $i < count($path)); $i++) {
            $pdir .= "/{$path[$i]}";
        }
        if (strlen($pdir)) {
            $pdir = substr($pdir, 1);
        }

        $fdir = "{$this->config['uploadDir']}/$pdir";

        $dirs = $this->getDirs($fdir);

        if (is_array($dirs) && count($dirs) && ($index <= count($path) - 1)) {

            foreach ($dirs as $i => $cdir) {
                if ($cdir['hasDirs'] &&
                    (
                        ($index == count($path) - 1) ||
                        ($cdir['name'] == $path[$index + 1])
                    )
                ) {
                    $dirs[$i]['dirs'] = $this->getTree($dir, $index + 1);
                    if (!is_array($dirs[$i]['dirs']) || !count($dirs[$i]['dirs'])) {
                        unset($dirs[$i]['dirs']);
                        continue;
                    }
                }
            }
        } else {
            return false;
        }

        return $dirs;
    }

    /**
     * @param bool $existent
     * @return string
     */
    protected function postDir($existent = true)
    {
        $dir = $this->typeDir;
        if (isset($this->post['dir'])) {
            $dir .= "/" . $this->post['dir'];
        }
        if ($existent && (!is_dir($dir) || !is_readable($dir))) {
            $this->errorMsg("Inexistant or inaccessible folder.");
        }

        return $dir;
    }

    /**
     * @param bool $existent
     * @return string
     */
    protected function getDir($existent = true)
    {
        $dir = $this->typeDir;
        if (isset($this->get['dir'])) {
            $dir .= "/" . $this->get['dir'];
        }
        if ($existent && (!is_dir($dir) || !is_readable($dir))) {
            $this->errorMsg("Inexistant or inaccessible folder.");
        }

        return $dir;
    }

    /**
     * @param $dir
     * @return array
     */
    protected function getDirs($dir)
    {
        $dirs = dir::content($dir, array('types' => "dir"));
        $return = array();
        if (is_array($dirs)) {
            $writable = dir::isWritable($dir);
            foreach ($dirs as $cdir) {
                $info = $this->getDirInfo($cdir);
                if ($info === false) {
                    continue;
                }
                $info['removable'] = $writable && $info['writable'];
                $return[] = $info;
            }
        }

        return $return;
    }

    /**
     * @param $dir
     * @param bool $removable
     * @return array|bool
     */
    protected function getDirInfo($dir, $removable = false)
    {
        if ((substr(basename($dir), 0, 1) == ".") || !is_dir($dir) || !is_readable($dir)) {
            return false;
        }

        $dirs  = glob($dir.'/*',GLOB_ONLYDIR);
        $hasDirs = !empty($dirs);

        $writable = dir::isWritable($dir);
        $info = array(
            'name'      => stripslashes(basename($dir)),
            'readable'  => is_readable($dir),
            'writable'  => $writable,
            'removable' => $removable && $writable && dir::isWritable(dirname($dir)),
            'hasDirs'   => $hasDirs
        );

        if ($dir == "{$this->config['uploadDir']}/{$this->session['dir']}") {
            $info['current'] = true;
        }

        return $info;
    }

    /**
     * @param null $data
     * @param null $template
     * @return string
     */
    protected function output($data = null, $template = null)
    {
        if (!is_array($data)) {
            $data = array();
        }
        if ($template === null) {
            $template = $this->action;
        }

        if (file_exists("tpl/tpl_$template.php")) {
            ob_start();
            $eval = "unset(\$data);unset(\$template);unset(\$eval);";
            $_ = $data;
            foreach (array_keys($data) as $key) {
                if (preg_match('/^[a-z\d_]+$/i', $key)) {
                    $eval .= "\$$key=\$_['$key'];";
                }
            }
            $eval .= "unset(\$_);require \"tpl/tpl_$template.php\";";
            eval($eval);

            return ob_get_clean();
        }

        return "";
    }

    /**
     * @param $message
     * @param array|null $data
     */
    protected function errorMsg($message, array $data = null)
    {
        if (in_array($this->action, array("thumb", "upload", "download", "downloadDir"))) {
            die($this->label($message, $data));
        }
        if (($this->action === null) || ($this->action == "browser")) {
            $this->backMsg($message, $data);
        } else {
            $message = $this->label($message, $data);
            die(json_encode(array('error' => $message)));
        }
    }
}
