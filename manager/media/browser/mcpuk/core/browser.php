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
    /** Name prefix of the temporary download archives; the cleanup removes nothing else. */
    const TEMP_ZIP_PREFIX = 'kcf-download-';

    protected $action;
    /** @var string[]|null fileManagerProtectedPaths(), read once per request */
    protected $protectedPaths;
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
        // checked before anything is probed or created: through a link, that would touch its target
        if (fileManagerPathContainsLink($this->config['uploadDir'], "$thumbsDir/{$this->type}")) {
            $this->errorMsg("Cannot access or create thumbnails folder.");
        }
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

        // Remove temporary zip downloads left behind. Only archives named by newTempZipPath() go:
        // any other .zip in the upload folder is somebody's file, possibly a restricted one.
        $files = dir::content($this->config['uploadDir'], [
            'types'   => "file",
            'pattern' => '/^.*\.zip$/i'
        ]);

        if (is_array($files) && count($files)) {
            $time = time();
            foreach ($files as $file) {
                if (preg_match('/^' . self::TEMP_ZIP_PREFIX . '[0-9a-f]{32}\.zip$/', basename($file))
                    && is_file($file) && ($time - filemtime($file) > 3600)) {
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
        $this->refuseReservedRequestNames();
        if ($this->config['disabled']) {
            $message = $this->label("You don't have permissions to browse server.");
            if (in_array($act, ["browser", "upload"]) ||
                (substr($act, 0, 8) == "download")
            ) {
                $this->backMsg($message);
            } else {
                header("Content-Type: text/plain; charset={$this->charset}");
                die(json_encode(['error' => $message]));
            }
        }

        if (!isset($this->session['dir'])) {
            $this->session['dir'] = $this->type;
        } else {
            $type = $this->getTypeFromPath($this->session['dir']);
            $dir = $this->config['uploadDir'] . "/" . $this->session['dir'];
            if (($type != $this->type) || !is_dir($dir) || !is_readable($dir) || !$this->isInsideTypeDir($dir)) {
                $this->session['dir'] = $this->type;
            }
        }
        $this->session['dir'] = path::normalize($this->session['dir']);

        if ($act == "browser") {
            header("X-UA-Compatible: chrome=1");
            header("Content-Type: text/html; charset={$this->charset}");
        } elseif (
            (substr($act, 0, 8) != "download") &&
            !in_array($act, ["thumb", "upload"])
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
            is_readable("{$this->typeDir}/{$this->get['dir']}") &&
            $this->isInsideTypeDir("{$this->typeDir}/{$this->get['dir']}")
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
        $files = $this->getEntries($this->session['dir']);
        $dirWritable = dir::isWritable("{$this->config['uploadDir']}/{$this->session['dir']}") &&
            $this->isWriteAllowed($this->removeTypeFromPath($this->session['dir']));
        $data = [
            'tree'        => &$tree,
            'files'       => &$files,
            'dirWritable' => $dirWritable
        ];

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
        // a cached thumbnail is still a picture of the original
        if (!$this->isPathAccessible("{$this->typeDir}/{$this->get['dir']}/$file")) {
            $this->sendDefaultThumb();
        }
        $original = "{$this->typeDir}/{$this->get['dir']}/$file";
        $file = "{$this->thumbsDir}/{$this->type}/{$this->get['dir']}/$file";
        // a cached path that is, or runs through, a link could lead to any file PHP can read
        if (!$this->isSafeThumbPath($file) || fileManagerPathContainsLink($this->typeDir, $original)) {
            $this->sendDefaultThumb();
        }
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
            if (in_array($type, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG]) &&
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
        return json_encode(['dirs' => $this->getDirs($this->postDir())]);
    }

    /**
     * @return string
     */
    protected function act_dirSize()
    {
        $dir = $this->postDir();
        if (!$this->isPathAccessible($dir)) {
            $this->errorMsg("Inexistant or inaccessible folder.");
        }

        return json_encode(['size' => $this->calculateDirectorySize($dir)]);
    }

    /**
     * @return string
     */
    protected function act_chDir()
    {
        $this->postDir(); // Just for existing check
        $this->session['dir'] = $this->type . "/" . $this->post['dir'];
        $dirWritable = dir::isWritable("{$this->config['uploadDir']}/{$this->session['dir']}") &&
            $this->isWriteAllowed($this->post['dir']);

        return json_encode([
            'files'       => $this->getEntries($this->session['dir']),
            'dirWritable' => $dirWritable
        ]);
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

        if (!$this->isWriteAllowed($this->post['dir'])) {
            $this->logDenied('folder permissions');
            $this->errorMsg("You don't have permissions to write to this folder.");
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
            $this->errorMsg("Cannot create {dir} folder.", ['dir' => $newDir]);
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

        if (!$this->isStrictWriteAllowed($this->post['dir'])) {
            $this->logDenied('folder permissions');
            $this->errorMsg("You don't have permissions to write to this folder.");
        }

        $dir = $this->postDir();
        $newName = $this->normalizeDirname(trim($this->post['newName']));
        $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserRename', [
            'element' => 'dir',
            'filepath' => realpath($dir),
            'newname' => &$newName
        ]);
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
        if (!$this->canModifyExisting($dir)) {
            $this->logDenied('folder permissions');
            $this->errorMsg("You don't have permissions to write to this folder.");
        }
        $oldKey = $this->getFileGroupsRelPath($dir);
        $newPath = dirname($dir) . "/$newName";
        // on Linux rename() silently replaces an empty folder: whatever sits at the destination,
        // restricted or not, is not ours to replace. Only a change of letter case is the same folder.
        if ($this->isTaken($newPath) && !(strcasecmp($dir, $newPath) === 0 && realpath($dir) === realpath($newPath))) {
            $this->errorMsg("A file or folder with that name already exists.");
        }
        if (!@rename($dir, $newPath)) {
            $this->errorMsg("Cannot rename the folder.");
        }
        $this->moveFileGroups($oldKey, dirname($dir) . "/$newName");
        $thumbDir = "$this->thumbsTypeDir/{$this->post['dir']}";
        if (is_dir($thumbDir) && $this->isSafeThumbPath($thumbDir)) {
            @rename($thumbDir, dirname($thumbDir) . "/$newName");
        }

        $this->modx->invokeEvent('OnFileBrowserRename', [
            'element' => 'dir',
            'filepath' => realpath($dir),
            'newname' => $newName
        ]);
        return json_encode(['name' => $newName]);
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

        if (!$this->isStrictWriteAllowed($this->post['dir'])) {
            $this->logDenied('folder permissions');
            $this->errorMsg("You don't have permissions to write to this folder.");
        }

        $dir = $this->postDir();

        if (!dir::isWritable($dir)) {
            $this->errorMsg("Cannot delete the folder.");
        }
        if (!$this->canModifyExisting($dir)) {
            $this->logDenied('folder permissions');
            $this->errorMsg("You don't have permissions to write to this folder.");
        }
        // prune() takes everything below with it, including entries the listing hid
        if ($this->hasInaccessibleDescendants($dir)) {
            $this->logDenied('folder permissions');
            $this->errorMsg("You don't have permissions to write to this folder.");
        }

        $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserDelete', [
            'element'  => 'dir',
            'filepath' => realpath($dir)
        ]);
        if (is_array($evtOut) && !empty($evtOut)) {
            die(json_encode(['error' => $evtOut]));
        }

        $oldKey = $this->getFileGroupsRelPath($dir);
        $wasLink = fileManagerIsLink($dir);
        $result = !dir::prune($dir, false);
        if (!is_dir($dir) && !$wasLink) {
            $this->forgetFileGroups($oldKey);
        }
        if (is_array($result) && count($result)) {
            $this->errorMsg("Failed to delete {count} files/folders.",
                ['count' => count($result)]);
        }
        $thumbDir = "$this->thumbsTypeDir/{$this->post['dir']}";
        if (is_dir($thumbDir) && $this->isSafeThumbPath($thumbDir)) {
            dir::prune($thumbDir);
        }
        $this->modx->invokeEvent('OnFileBrowserDelete', [
            'element'  => 'dir',
            'filepath' => realpath($dir)
        ]);

        return true;
    }

    /**
     * @return string
     */
    protected function act_upload()
    {
        $response = ['success' => false, 'message' => $this->label("Unknown error.")];
        if (!$this->config['access']['files']['upload'] ||
            !isset($this->post['dir'])
        ) {
            return json_encode($response);
        }
        if (!$this->isWriteAllowed($this->post['dir'])) {
            $this->logDenied('folder permissions');
            $response['message'] = $this->label("You don't have permissions to write to this folder.");
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
            !file_exists($file) || !is_readable($file) ||
            !$this->isPathAccessible($file)
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
        if (isset($this->post['dir']) && !$this->isWriteAllowed($this->post['dir'])) {
            $this->logDenied('folder permissions');
            $this->errorMsg("You don't have permissions to write to this folder.");
        }
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
        if (!$this->isPathAccessible($file) || !$this->canModifyExisting($file)) {
            $this->logDenied('folder permissions');
            $this->errorMsg("You don't have permissions to write to this folder.");
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
        $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserRename', [
            'element' => 'file',
            'filepath' => $dir,
            'filename' => $this->post['file'],
            'newname' => &$newName
        ]);
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
        if ($this->isTaken($newName)) {
            $this->errorMsg("A file or folder with that name already exists.");
        }
        $ext = file::getExtension($newName);
        if (!$this->validateFilename($newName, $this->type)) {
            $this->errorMsg("Denied file extension.");
        }
        if (is_array($evtOut) && !empty($evtOut)) {
            $this->errorMsg(implode('\n', $evtOut));
        }
        $oldKey = $this->getFileGroupsRelPath($file);
        if (!@rename($file, $newName)) {
            $this->errorMsg("Unknown error.");
        }
        $this->moveFileGroups($oldKey, $newName);
        $this->modx->invokeEvent('OnFileBrowserRename', [
            'element' => 'file',
            'filepath' => $dir,
            'filename' => $this->post['file'],
            'newname' => $_newName
        ]);
        $thumbDir = "{$this->thumbsTypeDir}/{$this->post['dir']}";
        $thumbFile = "$thumbDir/{$this->post['file']}";

        if (file_exists($thumbFile) && $this->isSafeThumbPath($thumbFile)) {
            @rename($thumbFile, "$thumbDir/" . basename($newName));
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function act_delete()
    {
        if (isset($this->post['dir']) && !$this->isWriteAllowed($this->post['dir'])) {
            $this->logDenied('folder permissions');
            $this->errorMsg("You don't have permissions to write to this folder.");
        }
        $dir = $this->postDir();

        if (!$this->config['access']['files']['delete'] ||
            !isset($this->post['dir']) ||
            !isset($this->post['file']) ||
            strpos($this->post['file'], '../') !== false ||
            (false === ($file = "$dir/{$this->post['file']}")) ||
            !file_exists($file) || !is_readable($file) || !file::isWritable($file) ||
            !$this->isPathAccessible($file) || !$this->canModifyExisting($file)
        ) {
            $this->logDenied('file group or top level', is_string($file) ? $file : null);
            $this->errorMsg("Cannot delete '{file}'.", ['file' => basename($file)]);
        }

        $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserDelete', [
            'element'  => 'file',
            'filename' => $this->post['file'],
            'filepath' => realpath($dir)
        ]);

        if (is_array($evtOut) && !empty($evtOut)) {
            die(json_encode(['error' => $evtOut]));
        }

        $oldKey = $this->getFileGroupsRelPath($file);
        // the key of a link is its target's: removing the link leaves the target and its groups
        $wasLink = fileManagerIsLink($file);
        if (@unlink($file) && !$wasLink) {
            $this->forgetFileGroups($oldKey);
        }

        $thumb = "{$this->thumbsTypeDir}/{$this->post['dir']}/{$this->post['file']}";
        if (file_exists($thumb) && $this->isSafeThumbPath($thumb)) {
            @unlink($thumb);
        }

        $this->modx->invokeEvent('OnFileBrowserDelete', [
            'element'  => 'file',
            'filename' => $this->post['file'],
            'filepath' => realpath($dir)
        ]);

        return true;
    }

    /**
     * @return bool|string
     */
    protected function act_cp_cbd()
    {
        if (isset($this->post['dir']) && !$this->isWriteAllowed($this->post['dir'])) {
            $this->logDenied('folder permissions');
            $this->errorMsg("You don't have permissions to write to this folder.");
        }
        $dir = $this->postDir();
        if (!$this->config['access']['files']['copy'] ||
            !isset($this->post['dir']) ||
            !is_dir($dir) || !is_readable($dir) || !dir::isWritable($dir) ||
            !isset($this->post['files']) || !is_array($this->post['files']) ||
            !count($this->post['files'])
        ) {
            $this->errorMsg("Unknown error.");
        }

        $error = [];
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
            $replace = ['file' => $base];
            if (!$this->isPathAccessible($path)) {
                // a copy into an open folder would hand out a file the listing hides
                $this->logDenied('file group', $file);
                $error[] = $this->label("Cannot read '{file}'.", $replace);
                continue;
            }
            $ext = file::getExtension($base);
            $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserCopy', [
                'oldpath'  => $path,
                'filename' => $base,
                'newpath' => realpath($dir)
            ]);
            if (is_array($evtOut) && !empty($evtOut)) {
                $error[] = implode("\n", $evtOut);
            } elseif (!file_exists($path)) {
                $error[] = $this->label("The file '{file}' does not exist.", $replace);
            } elseif (substr($base, 0, 1) == ".") {
                $error[] = "$base: " . $this->label("File name shouldn't begins with '.'");
            } elseif (!$this->validateFilename($base, $type)) {
                $error[] = "$base: " . $this->label("Denied file extension.");
            } elseif ($this->isTaken("$dir/$base")) {
                $error[] = "$base: " . $this->label("A file or folder with that name already exists.");
            } elseif (!is_readable($path) || !is_file($path)) {
                $error[] = $this->label("Cannot read '{file}'.", $replace);
            } elseif (null === ($carried = \EvolutionCMS\Support\FileManagerAccess::carriedRestrictions(
                $this->getFileGroupsRelPath($path),
                $this->getFileGroupsRelPath("$dir/$base")
            ))) {
                // no single set of groups holds the copy to what the original is held to
                $this->logDenied('copy would widen the file groups', $file);
                $error[] = $this->label("Cannot copy '{file}'.", $replace);
            } elseif (!$this->copyExclusive($path, "$dir/$base")) {
                $error[] = $this->label("Cannot copy '{file}'.", $replace);
            } else {
                if (function_exists("chmod")) {
                    @chmod("$dir/$base", $this->config['filePerms']);
                }
                // the copy is as restricted as the original, not as open as the folder it landed in
                \EvolutionCMS\Support\FileManagerAccess::replaceRestrictions(
                    $this->getFileGroupsRelPath("$dir/$base"),
                    $carried
                );
                $this->modx->invokeEvent('OnFileBrowserCopy', [
                    'oldpath'  => $path,
                    'filename' => $base,
                    'newpath' => realpath($dir)
                ]);
                $fromThumb = "{$this->thumbsDir}/$file";
                $toThumb = "{$this->thumbsTypeDir}/{$this->post['dir']}";
                if (is_file($fromThumb) && is_readable($fromThumb)
                    && $this->isSafeThumbPath($fromThumb) && $this->isSafeThumbPath("$toThumb/$base")
                ) {
                    if (!is_dir($toThumb)) {
                        @mkdir($toThumb, $this->config['dirPerms'], true);
                    }
                    $toThumb .= "/$base";
                    $this->copyExclusive($fromThumb, $toThumb);
                }
            }
        }
        if (count($error)) {
            return json_encode(['error' => $error]);
        }

        return true;
    }

    /**
     * @return bool|string
     */
    protected function act_mv_cbd()
    {
        if (isset($this->post['dir']) && !$this->isWriteAllowed($this->post['dir'])) {
            $this->logDenied('folder permissions');
            $this->errorMsg("You don't have permissions to write to this folder.");
        }
        $dir = $this->postDir();
        if (!$this->config['access']['files']['move'] ||
            !isset($this->post['dir']) ||
            !is_dir($dir) || !is_readable($dir) || !dir::isWritable($dir) ||
            !isset($this->post['files']) || !is_array($this->post['files']) ||
            !count($this->post['files'])
        ) {
            $this->errorMsg("Unknown error.");
        }

        $error = [];
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
            $srcRelDir = $this->removeTypeFromPath(dirname($file));
            if (!$this->isWriteAllowed($srcRelDir)) {
                $this->logDenied('folder permissions');
                $error[] = basename($file) . ": " . $this->label("You don't have permissions to write to this folder.");
                continue;
            }
            $path = "{$this->config['uploadDir']}/$file";
            $base = basename($file);
            $replace = ['file' => $base];
            if (!$this->isPathAccessible($path)) {
                $this->logDenied('file group', $file);
                $error[] = $this->label("Cannot move '{file}'.", $replace);
                continue;
            }
            $oldKey = $this->getFileGroupsRelPath($path);
            // read before the move: the folder it leaves may be the only thing restricting it
            $carriedGroups = \EvolutionCMS\Support\FileManagerAccess::carriedRestrictions(
                $oldKey,
                $this->getFileGroupsRelPath("$dir/$base")
            );
            $ext = file::getExtension($base);
            $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserMove', [
                'oldpath'  => $path,
                'filename' => $base,
                'newpath' => realpath($dir)
            ]);
            if (is_array($evtOut) && !empty($evtOut)) {
                $error[] = implode("\n", $evtOut);
            } elseif (!file_exists($path)) {
                $error[] = $this->label("The file '{file}' does not exist.", $replace);
            } elseif (substr($base, 0, 1) == ".") {
                $error[] = "$base: " . $this->label("File name shouldn't begins with '.'");
            } elseif (!$this->validateFilename($base, $type)) {
                $error[] = "$base: " . $this->label("Denied file extension.");
            } elseif ($this->isTaken("$dir/$base")) {
                $error[] = "$base: " . $this->label("A file or folder with that name already exists.");
            } elseif (!is_readable($path) || !is_file($path)) {
                $error[] = $this->label("Cannot read '{file}'.", $replace);
            } elseif ($carriedGroups === null || !$this->canModifyExisting($path)) {
                $this->logDenied($carriedGroups === null ? 'move would widen the file groups' : 'top level', $file);
                $error[] = $this->label("Cannot move '{file}'.", $replace);
            } elseif (!file::isWritable($path) || !@rename($path, "$dir/$base")) {
                $error[] = $this->label("Cannot move '{file}'.", $replace);
            } else {
                $this->moveFileGroups($oldKey, "$dir/$base");
                // and it keeps the restrictions of the folder it left, not only its own: all of them
                // at once, which is narrower than its own groups when the folder had others
                if ($carriedGroups !== []) {
                    \EvolutionCMS\Support\FileManagerAccess::replaceRestrictions(
                        $this->getFileGroupsRelPath("$dir/$base"),
                        $carriedGroups
                    );
                }
                if (function_exists("chmod")) {
                    @chmod("$dir/$base", $this->config['filePerms']);
                }
                $fromThumb = "{$this->thumbsDir}/$file";
                $toThumb = "{$this->thumbsTypeDir}/{$this->post['dir']}";
                if (is_file($fromThumb) && is_readable($fromThumb)
                    && $this->isSafeThumbPath($fromThumb) && $this->isSafeThumbPath("$toThumb/$base")
                ) {
                    if (!is_dir($toThumb)) {
                        @mkdir($toThumb, $this->config['dirPerms'], true);
                    }
                    $toThumb .= "/$base";
                    @rename($fromThumb, $toThumb);
                }
                $this->modx->invokeEvent('OnFileBrowserMove', [
                    'oldpath'  => $path,
                    'filename' => $base,
                    'newpath' => realpath($dir)
                ]);
            }
        }
        if (count($error)) {
            return json_encode(['error' => $error]);
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

        $error = [];
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
            $srcRelDir = $this->removeTypeFromPath(dirname($file));
            if (!$this->isWriteAllowed($srcRelDir)) {
                $this->logDenied('folder permissions');
                $error[] = basename($file) . ": " . $this->label("You don't have permissions to write to this folder.");
                continue;
            }
            $path = "{$this->config['uploadDir']}/$file";
            $base = basename($file);
            $filepath = str_replace('/' . $base, '', $path);
            $replace = ['file' => $base];
            if (!$this->isPathAccessible($path) || !$this->canModifyExisting($path)) {
                $this->logDenied('file group or top level', $file);
                $error[] = $this->label("Cannot delete '{file}'.", $replace);
            } elseif (!is_file($path)) {
                $error[] = $this->label("The file '{file}' does not exist.", $replace);
            } else {
                $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserDelete', [
                    'element'  => 'file',
                    'filename' => $base,
                    'filepath' => $filepath
                ]);

                if (is_array($evtOut) && !empty($evtOut)) {
                    $error[] = implode("\n", $evtOut);
                } else {
                    $oldKey = $this->getFileGroupsRelPath($path);
                    $wasLink = fileManagerIsLink($path);
                    if (!@unlink($path)) {
                        $error[] = $this->label("Cannot delete '{file}'.", $replace);
                    } else {
                        if (!$wasLink) {
                            $this->forgetFileGroups($oldKey);
                        }
                        $this->modx->invokeEvent('OnFileBrowserDelete', [
                            'element'  => 'file',
                            'filename' => $base,
                            'filepath' => $filepath
                        ]);
                        $thumb = "{$this->thumbsDir}/$file";
                        if (is_file($thumb) && $this->isSafeThumbPath($thumb)) {
                            @unlink($thumb);
                        }
                    }
                }
            }
        }
        if (count($error)) {
            return json_encode(['error' => $error]);
        }

        return true;
    }

    /**
     * Path for a temporary download archive; the name is what lets the cleanup in the
     * constructor tell it from a user's own .zip.
     */
    protected function newTempZipPath()
    {
        do {
            $file = "{$this->config['uploadDir']}/" . self::TEMP_ZIP_PREFIX . md5(uniqid(session_id(), true)) . ".zip";
        } while (file_exists($file));

        return $file;
    }

    /**
     * @throws Exception
     */
    protected function act_downloadDir()
    {
        $dir = $this->postDir();
        if (!isset($this->post['dir']) || $this->config['denyZipDownload'] || !$this->isPathAccessible($dir)) {
            $this->errorMsg("Unknown error.");
        }
        $filename = basename($dir) . ".zip";
        $file = $this->newTempZipPath();
        new zipFolder($file, $dir, null, $this->subtreeAccessFilter($dir));
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

        $zipFiles = [];
        foreach ($this->post['files'] as $file) {
            $file = path::normalize($file);
            if ((substr($file, 0, 1) == ".") || (strpos($file, '/') !== false)) {
                continue;
            }
            $file = "$dir/$file";
            if (!is_file($file) || !is_readable($file) || !$this->isPathAccessible($file)) {
                continue;
            }
            $zipFiles[] = $file;
        }

        $file = $this->newTempZipPath();

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

        $zipFiles = [];
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
            if (!is_file($file) || !is_readable($file) || !$this->isPathAccessible($file)) {
                continue;
            }
            $zipFiles[] = $file;
        }

        $file = $this->newTempZipPath();

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
        $response = ['success' => false, 'message' => $this->label('Unknown error.')];
        $message = $this->checkUploadedFile($file);

        if ($message !== true) {
            if (isset($file['tmp_name'])) {
                @unlink($file['tmp_name']);
            }
            $response['message'] = $message;

            return $response;
        }

        $evtOut = $this->modx->invokeEvent('OnBeforeFileBrowserUpload', [
            'file'     => &$file,
            'filepath' => realpath($dir)
        ]);

        if (is_array($evtOut) && !empty($evtOut)) {
            $response['message'] = $evtOut;

            return $response;
        }
        $filename = $this->normalizeFilename($file['name']);
        $target = "$dir/" . file::getInexistantFilename($filename, $dir);

        // checkUploadedFile() saw the name as sent; transliteration and numbering may have changed it
        if (!$this->validateFilename(basename($target), $this->type)) {
            @unlink($file['tmp_name']);
            $response['message'] = $this->label("Denied file extension.");

            return $response;
        }

        // every attempt below writes through a symlink at $target, so check before each one
        if (!$this->isNewUploadTarget($target) ||
            (!@move_uploaded_file($file['tmp_name'], $target) &&
                (!$this->isNewUploadTarget($target) || !@rename($file['tmp_name'], $target)) &&
                (!$this->isNewUploadTarget($target) || !$this->copyExclusive($file['tmp_name'], $target)))
        ) {
            @unlink($file['tmp_name']);

            $response['message'] = $this->label("Cannot move uploaded file to target folder.");

            return $response;
        } elseif (function_exists('chmod')) {
            chmod($target, $this->config['filePerms']);
        }

        $this->modx->invokeEvent('OnFileBrowserUpload', [
            'filepath' => realpath($dir),
            'filename' => str_replace("/", "", str_replace($dir, "", realpath($target)))
        ]);

        $this->makeThumb($target);
        $response['success'] = true;

        return $response;
    }

    /**
     * Whether an upload may be written to $target: nothing is there yet, not even a dangling
     * symlink, and it lies inside the type folder.
     *
     * @param string $target
     * @return bool
     */
    protected function isTaken($target)
    {
        return fileManagerPathIsTaken($target);
    }

    /**
     * Copies $from to a destination nobody has taken: 'x' mode fails on anything already there,
     * a dangling symlink included, instead of writing through it.
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    protected function copyExclusive($from, $to)
    {
        if ($this->isTaken($to)) {
            return false;
        }
        $in = @fopen($from, 'rb');
        $out = $in ? @fopen($to, 'xb') : false;
        if (!$out) {
            if ($in) {
                fclose($in);
            }
            return false;
        }
        $ok = stream_copy_to_stream($in, $out) !== false;
        fclose($in);
        fclose($out);
        if (!$ok) {
            @unlink($to);
        }
        return $ok;
    }

    protected function isNewUploadTarget($target)
    {
        return !$this->isTaken($target) && $this->isInsideTypeDir($target);
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
        $return = [];
        $files = dir::content($dir, ['types' => "file"]);
        if ($files === false) {
            return $return;
        }
        $files = $this->filterAccessiblePaths($files);

        foreach ($files as $file) {
            $ext = file::getExtension($file);
            $smallThumb = false;
            $preview = false;
            if (in_array(strtolower($ext), ['png', 'jpg', 'gif', 'jpeg', 'webp'])) {
                $size = @getimagesize($file);
                if (is_array($size) && count($size)) {
                    $preview = true;
                    if (!$this->config['noThumbnailsRecreation']) {
                        $thumb_file = "$thumbDir/" . basename($file);
                        if (!is_file($thumb_file) || filemtime($file) > filemtime($thumb_file)) {
                            $this->makeThumb($file);
                        }
                        $smallThumb =
                            ($size[0] <= (int)$this->config['thumbWidth']) &&
                            ($size[1] <= (int)$this->config['thumbHeight']) &&
                            in_array($size[2], [IMAGETYPE_GIF, IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP, IMAGETYPE_AVIF]);
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
            $return[] = [
                'name'       => stripcslashes($name),
                'size'       => $stat['size'],
                'mtime'      => $stat['mtime'],
                'date'       => date($this->dateTimeSmall, $stat['mtime']),
                'readable'   => is_readable($file),
                'writable'   => file::isWritable($file),
                'bigIcon'    => $bigIcon,
                'smallIcon'  => $smallIcon,
                'thumb'      => $thumb,
                'smallThumb' => $smallThumb,
                'preview'    => $preview
            ];
        }

        return $return;
    }

    /**
     * @param $dir
     * @return array
     */
    protected function getEntries($dir)
    {
        $entries = [];

        foreach ($this->getDirs("{$this->config['uploadDir']}/$dir") as $folder) {
            $entries[] = [
                'name'      => $folder['name'],
                'size'      => 0,
                'mtime'     => $folder['mtime'],
                'date'      => $folder['date'],
                'readable'  => $folder['readable'],
                'writable'  => $folder['writable'],
                'removable' => $folder['removable'],
                'hasDirs'   => $folder['hasDirs'],
                'isDir'     => true
            ];
        }

        foreach ($this->getFiles($dir) as $file) {
            $file['isDir'] = false;
            $entries[] = $file;
        }

        return $entries;
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
                    if($index + 1 <=(count($path) - 1)) {
                        $dirs[$i]['dirs'] = $this->getTree($dir, $index + 1);
                    }
                    if (!isset($dirs[$i]['dirs']) || !is_array($dirs[$i]['dirs']) || !count($dirs[$i]['dirs'])) {
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
        if (($existent && (!is_dir($dir) || !is_readable($dir))) || !$this->isInsideTypeDir($dir)) {
            $this->errorMsg("Inexistant or inaccessible folder.");
        }

        return $dir;
    }

    /**
     * Stops a request that names a Windows device name, before any of its names is probed.
     *
     * @return void
     */
    protected function refuseReservedRequestNames()
    {
        // every name the request carries comes before the first probe of the file system
        foreach ([$this->get, $this->post] as $request) {
            foreach (['dir', 'file', 'newDir', 'newName', 'name', 'files', 'dirs'] as $key) {
                $values = $request[$key] ?? [];
                foreach (is_array($values) ? $values : [$values] as $value) {
                    if (is_string($value) && fileManagerRefuseReservedName($value)) {
                        $this->errorMsg("Denied file extension.");
                    }
                }
            }
        }
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
        if (($existent && (!is_dir($dir) || !is_readable($dir))) || !$this->isInsideTypeDir($dir)) {
            $this->errorMsg("Inexistant or inaccessible folder.");
        }

        return $dir;
    }

    /**
     * Whether $absPath, once symlinks are resolved, is still inside the current type folder.
     * The dir and file parameters are only checked by name; a symlink below the upload folder
     * would otherwise lead every action (and the file groups check) outside it.
     *
     * @param string $absPath
     * @return bool
     */
    protected function isInsideTypeDir($absPath)
    {
        return !fileManagerPathContainsLink($this->typeDir, $absPath)
            && $this->isInsideTypeRoot($absPath)
            && !$this->isInProtectedFolder($absPath);
    }

    /**
     * Whether $absPath lies in a folder the current manager user's permissions keep closed (the
     * same list as in the classic file manager: backups, element folders, core, manager).
     *
     * @param string $absPath
     * @return bool
     */
    protected function isInProtectedFolder($absPath)
    {
        if ($this->protectedPaths === null) {
            $this->protectedPaths = fileManagerProtectedPaths();
        }

        return fileManagerPathIsProtected(fileManagerCanonicalCase($absPath), $this->protectedPaths);
    }

    /**
     * @param string $absPath
     * @return bool
     */
    protected function isInsideTypeRoot($absPath)
    {
        $root = realpath($this->typeDir);
        if ($root === false) {
            return false;
        }
        $path = realpath($absPath);
        if ($path === false) {
            // nothing there yet is fine, a dangling link is not: writing to it creates its target
            if (dir::isLink($absPath)) {
                return false;
            }
            $parent = realpath(dirname($absPath));

            return $parent !== false && \EvolutionCMS\Support\FileManagerAccess::isWithin(
                str_replace('\\', '/', $root),
                str_replace('\\', '/', $parent)
            );
        }

        return \EvolutionCMS\Support\FileManagerAccess::isWithin(
            str_replace('\\', '/', $root),
            str_replace('\\', '/', $path)
        );
    }

    /**
     * @param $dir
     * @return array
     */
    protected function getDirs($dir)
    {
        $dirs = dir::content($dir, ['types' => "dir"]);
        $return = [];
        if (is_array($dirs)) {
            $dirs = $this->filterAccessiblePaths($dirs);
            foreach ($dirs as $cdir) {
                $info = $this->getDirInfo($cdir);
                if ($info === false) {
                    continue;
                }
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
        $stat = @stat($dir);
        $mtime = $stat === false ? false : $stat['mtime'];

        // isWriteAllowed() takes a path below the type folder, not below the site root
        $typeRelativePath = \EvolutionCMS\Support\FileManagerAccess::getRelativePath($this->typeDir, $dir);
        $writable = is_writable($dir) && $this->isWriteAllowed($typeRelativePath);
        $removable = $writable
            && is_writable(dirname($dir))
            && $this->isStrictWriteAllowed($typeRelativePath);
        $info = [
            'name'      => stripslashes(basename($dir)),
            'mtime'     => $mtime === false ? 0 : $mtime,
            'date'      => $mtime === false ? '' : date($this->dateTimeSmall, $mtime),
            'readable'  => is_readable($dir),
            'writable'  => $writable,
            'removable' => $removable,
            'hasDirs'   => $hasDirs
        ];

        if ($dir == "{$this->config['uploadDir']}/{$this->session['dir']}") {
            $info['current'] = true;
        }

        return $info;
    }

    /**
     * Calculate the total size of readable files the current user may access below a folder.
     * This is called only after the user asks for a folder size, since traversing a large tree
     * would make the initial browser listing slow.
     *
     * @param string $dir
     * @return int
     */
    protected function calculateDirectorySize($dir)
    {
        $isAccessible = $this->subtreeAccessFilter($dir);
        $pending = [$dir];
        $size = 0;

        while (count($pending)) {
            $current = array_pop($pending);
            if (!$isAccessible($current) || (dir::isLink($current) && $current !== $this->typeDir)) {
                continue;
            }

            $entries = dir::content($current, ['followLinks' => false]);
            if (!is_array($entries)) {
                continue;
            }

            foreach ($entries as $entry) {
                if (!$isAccessible($entry) || dir::isLink($entry)) {
                    continue;
                }
                if (is_dir($entry)) {
                    if (is_readable($entry)) {
                        $pending[] = $entry;
                    }
                    continue;
                }
                if (!is_file($entry) || !is_readable($entry)) {
                    continue;
                }

                $fileSize = @filesize($entry);
                if ($fileSize !== false) {
                    $size += $fileSize;
                }
            }
        }

        return $size;
    }

    /**
     * @param null $data
     * @param null $template
     * @return string
     */
    protected function output($data = null, $template = null)
    {
        if (!is_array($data)) {
            $data = [];
        }
        if ($template === null) {
            $template = $this->action;
        }

        if (file_exists("tpl/tpl_$template.php")) {
            ob_start();
            // EXTR_SKIP keeps $data/$template/$this from being clobbered by a data key.
            extract($data, EXTR_SKIP);
            unset($data);
            require "tpl/tpl_$template.php";

            return ob_get_clean();
        }

        return "";
    }

    /**
     * Convert an absolute filesystem path to a path relative to filemanager_path,
     * as stored in the file_groups table. Returns '' if the path is outside the root.
     *
     * @param string $absPath
     * @return string
     */
    protected function getFileGroupsRelPath(string $absPath): string
    {
        // the site-wide root, not the manager's own filemanager_path: groups are keyed by it
        return \EvolutionCMS\Support\FileManagerAccess::getRelativePath(
            \EvolutionCMS\Support\FileManagerAccess::aclRoot(),
            $absPath
        );
    }

    /**
     * Filter a list of absolute paths to only those accessible to the current manager user.
     * Paths with no file_groups rows are public (accessible to all).
     * Paths restricted to specific groups are only accessible if the user belongs to one.
     * Admins (mgrRole == 1) always see everything.
     *
     * @param string[] $absPaths
     * @return string[]
     */
    protected function filterAccessiblePaths(array $absPaths): array
    {
        // a symlink out of the upload folder is neither listed nor followed
        $absPaths = array_values(array_filter($absPaths, [$this, 'isInsideTypeDir']));
        if (!$this->modx->getConfig('use_udperms')) {
            return $absPaths;
        }
        if (isset($_SESSION['mgrRole']) && (int)$_SESSION['mgrRole'] === 1) {
            return $absPaths;
        }

        $userGroups = array_map('intval', (array)($_SESSION['mgrDocgroups'] ?? []));
        $itemRelativePaths = [];
        foreach ($absPaths as $ap) {
            $itemRelativePaths[$ap] = $this->getFileGroupsRelPath($ap);
        }
        $restricted = \EvolutionCMS\Support\FileManagerAccess::loadRestrictions(array_values($itemRelativePaths));

        $result = [];
        foreach ($absPaths as $ap) {
            $relativePath = $itemRelativePaths[$ap] ?? '';
            if ($relativePath === '') {
                $result[] = $ap; // rel path unknown, let through
                continue;
            }
            if (\EvolutionCMS\Support\FileManagerAccess::isAccessible($relativePath, $userGroups, $restricted)) {
                $result[] = $ap;
            }
        }
        return $result;
    }

    /**
     * Check if the current manager user has write access to the given directory.
     *
     * Admins (mgrRole == 1) may write anywhere.
     * For all other managers, access is granted when use_udperms is disabled or
     * the path is effectively accessible through its direct/inherited file groups.
     *
     * @param string $relDir  Path relative to typeDir, without leading slash
     * @return bool
     */
    protected function isWriteAllowed($relDir)
    {
        $relDir = trim($relDir, '/');
        $absPath = $this->typeDir . ($relDir !== '' ? '/' . $relDir : '');
        if (!$this->isInsideTypeDir($absPath)) {
            return false;
        }
        if (isset($_SESSION['mgrRole']) && (int)$_SESSION['mgrRole'] === 1) {
            return true;
        }
        if (!$this->modx->getConfig('use_udperms')) {
            return true;
        }

        $relPath = $this->getFileGroupsRelPath($absPath);
        if ($relPath === '') {
            return true;
        }
        $userGroups = array_map('intval', (array)($_SESSION['mgrDocgroups'] ?? []));
        $rows = \EvolutionCMS\Support\FileManagerAccess::loadRestrictions([$relPath]);

        return \EvolutionCMS\Support\FileManagerAccess::isAccessible($relPath, $userGroups, $rows);
    }

    /**
     * Whether the current manager user may reach this file or folder. The listing hides what
     * this refuses; every act that reads or changes a named entry has to ask as well, or a
     * hidden entry is one crafted request away.
     *
     * @param string $absPath
     * @return bool
     */
    protected function isPathAccessible($absPath)
    {
        if (!$this->isInsideTypeDir($absPath)) {
            return false;
        }
        if (isset($_SESSION['mgrRole']) && (int)$_SESSION['mgrRole'] === 1) {
            return true;
        }
        if (!$this->modx->getConfig('use_udperms')) {
            return true;
        }

        $relPath = $this->getFileGroupsRelPath($absPath);
        if ($relPath === '') {
            return true;
        }
        $userGroups = array_map('intval', (array)($_SESSION['mgrDocgroups'] ?? []));

        return \EvolutionCMS\Support\FileManagerAccess::isAccessible(
            $relPath,
            $userGroups,
            \EvolutionCMS\Support\FileManagerAccess::loadRestrictions([$relPath])
        );
    }

    /**
     * A filter for everything below $absDir that the current manager user may reach, built
     * from one query instead of one per entry.
     *
     * @param string $absDir
     * @return callable(string): bool
     */
    protected function subtreeAccessFilter($absDir)
    {
        if ((isset($_SESSION['mgrRole']) && (int)$_SESSION['mgrRole'] === 1) || !$this->modx->getConfig('use_udperms')) {
            return fn ($path) => $this->isInsideTypeDir($path);
        }

        $restrictions = \EvolutionCMS\Support\FileManagerAccess::loadSubtreeRestrictions($this->getFileGroupsRelPath($absDir));
        $userGroups = array_map('intval', (array)($_SESSION['mgrDocgroups'] ?? []));

        return function ($path) use ($restrictions, $userGroups) {
            if (!$this->isInsideTypeDir($path)) {
                return false;
            }
            $relPath = $this->getFileGroupsRelPath($path);

            return $relPath === ''
                || \EvolutionCMS\Support\FileManagerAccess::isAccessible($relPath, $userGroups, $restrictions);
        };
    }

    /**
     * Carry the file groups of a renamed or moved entry over to its new path. The rows have to
     * follow whoever moved it, or everything below turns public at the new path.
     *
     * @param string $oldKey getFileGroupsRelPath() of the old path, taken before the move
     * @param string $newAbsPath
     */
    protected function moveFileGroups($oldKey, $newAbsPath)
    {
        \EvolutionCMS\Support\FileManagerAccess::moveRestrictions($oldKey, $this->getFileGroupsRelPath($newAbsPath));
    }

    /**
     * @param string $oldKey getFileGroupsRelPath() of the removed path, taken before removal
     */
    protected function forgetFileGroups($oldKey)
    {
        \EvolutionCMS\Support\FileManagerAccess::forgetRestrictions($oldKey);
    }

    /**
     * The classic file manager's rule for an existing entry: what lies at the top of the manager's
     * own root cannot be renamed, moved or deleted by a restricted manager. Same key, same answer
     * in both tools.
     *
     * @param string $absPath
     * @return bool
     */
    protected function canModifyExisting($absPath)
    {
        if (!fileManagerAclApplies()) {
            return true;
        }
        $root = realpath($this->modx->getConfig('filemanager_path')) ?: realpath(EVO_BASE_PATH);
        $relative = \EvolutionCMS\Support\FileManagerAccess::getRelativePath($root, $absPath);
        if ($relative === '') {
            // not under the file manager's root (or the root itself): nothing to compare it with
            return !\EvolutionCMS\Support\FileManagerAccess::isWithin(
                str_replace('\\', '/', $root),
                str_replace('\\', '/', (string) (realpath($absPath) ?: $absPath))
            );
        }

        return fileManagerCanModifyExistingPath($relative);
    }

    /**
     * Whether pruning $absDir would take something with it the current manager user may not reach.
     *
     * @param string $absDir
     * @return bool
     */
    protected function hasInaccessibleDescendants($absDir)
    {
        if ((isset($_SESSION['mgrRole']) && (int)$_SESSION['mgrRole'] === 1) || !$this->modx->getConfig('use_udperms')) {
            return false;
        }

        $relPath = $this->getFileGroupsRelPath($absDir);
        if ($relPath === '') {
            return false;
        }
        $userGroups = array_map('intval', (array)($_SESSION['mgrDocgroups'] ?? []));

        return \EvolutionCMS\Support\FileManagerAccess::inaccessibleDescendants(
            $relPath,
            $userGroups,
            \EvolutionCMS\Support\FileManagerAccess::loadSubtreeRestrictions($relPath)
        ) !== [];
    }

    /**
     * Like isWriteAllowed(), but also requires the path to be strictly inside
     * the type directory (i.e. at least one segment deep), preventing structural
     * changes to the type root itself.
     *
     * @param string $relDir  Path relative to typeDir
     * @return bool
     */
    protected function isStrictWriteAllowed($relDir)
    {
        $relDir = trim($relDir, '/');
        return $relDir !== ''
            && !\EvolutionCMS\Support\FileManagerAccess::isTopLevelPath($this->getFileGroupsRelPath($this->typeDir . '/' . $relDir))
            && $this->isWriteAllowed($relDir);
    }

    /**
     * @param $message
     * @param array|null $data
     */
    protected function errorMsg($message, array $data = null)
    {
        if (in_array($this->action, ["thumb", "upload", "download", "downloadDir"])) {
            die($this->label($message, $data));
        }
        if (($this->action === null) || ($this->action == "browser")) {
            $this->backMsg($message, $data);
        } else {
            $message = $this->label($message, $data);
            die(json_encode(['error' => $message]));
        }
    }
}
