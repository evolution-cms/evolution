<?php
if(!function_exists('run')) {
    /**
     * @return string
     */
    function run()
    {
        $modx = evolutionCMS();
        global $_lang;

        $tbl_site_content = $modx->getDatabase()->getFullTableName('site_content');
        $output = '';
        $maxtime = $_POST['maxtime'];

        if (!is_numeric($maxtime)) {
            $maxtime = 30;
        }

        @set_time_limit($maxtime);

        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $importstart = $mtime;

        if ($_POST['reset'] == 'on') {
            $modx->getDatabase()->truncate($tbl_site_content);
            $modx->getDatabase()->query("ALTER TABLE {$tbl_site_content} AUTO_INCREMENT = 1");
        }

        $parent = (int)$_POST['parent'];

        if (is_dir(MODX_BASE_PATH . 'temp/import')) {
            $filedir = MODX_BASE_PATH . 'temp/import/';
        } elseif (is_dir(MODX_BASE_PATH . 'assets/import')) {
            $filedir = MODX_BASE_PATH . 'assets/import/';
        } else {
            $filedir = '';
        }

        $filesfound = 0;

        $files = getFiles($filedir);
        $files = pop_index($files);

        // no. of files to import
        $output .= sprintf('<p>' . $_lang['import_files_found'] . '</p>', $filesfound);

        // import files
        if (0 < count($files)) {
            $modx->getDatabase()->update(array('isfolder' => 1), $tbl_site_content, "id='{$parent}'");
            importFiles($parent, $filedir, $files, 'root');
        }

        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        $importend = $mtime;
        $totaltime = ($importend - $importstart);
        $output .= sprintf('<p>' . $_lang['import_site_time'] . '</p>', round($totaltime, 3));

        if ($_POST['convert_link'] == 'on') {
            convertLink();
        }

        return $output;
    }
}

if(!function_exists('importFiles')) {
    /**
     * @param int $parent
     * @param string $filedir
     * @param array $files
     * @param string $mode
     */
    function importFiles($parent, $filedir, $files, $mode)
    {
        $modx = evolutionCMS();
        global $_lang, $allowedfiles;
        global $search_default, $cache_default, $publish_default;

        $tbl_site_content = $modx->getDatabase()->getFullTableName('site_content');
        $tbl_system_settings = $modx->getDatabase()->getFullTableName('system_settings');

        $createdby = $modx->getLoginUserID();
        if (!is_array($files)) {
            return;
        }
        if ($_POST['object'] === 'all') {
            $modx->config['default_template'] = '0';
            $richtext = '0';
        } else {
            $richtext = '1';
        }

        foreach ($files as $id => $value) {
            if (is_array($value)) {
                // create folder
                $alias = $id;
                printf('<span>' . $_lang['import_site_importing_document'] . '</span>', $alias);
                $field = array();
                $field['type'] = 'document';
                $field['contentType'] = 'text/html';
                $field['published'] = $publish_default;
                $field['parent'] = $parent;
                $field['alias'] = $modx->stripAlias($alias);
                $field['richtext'] = $richtext;
                $field['template'] = $modx->config['default_template'];
                $field['searchable'] = $search_default;
                $field['cacheable'] = $cache_default;
                $field['createdby'] = $createdby;
                $field['isfolder'] = 1;
                $field['menuindex'] = 1;
                $find = false;
                foreach (array(
                             'index.html',
                             'index.htm'
                         ) as $filename) {
                    $filepath = $filedir . $alias . '/' . $filename;
                    if ($find === false && file_exists($filepath)) {
                        $file = getFileContent($filepath);
                        list($pagetitle, $content, $description) = treatContent($file, $filename, $alias);

                        $date = filemtime($filepath);
                        $field['pagetitle'] = $pagetitle;
                        $field['longtitle'] = $pagetitle;
                        $field['description'] = $description;
                        $field['content'] = $modx->getDatabase()->escape($content);
                        $field['createdon'] = $date;
                        $field['editedon'] = $date;
                        $newid = $modx->getDatabase()->insert($field, $tbl_site_content);
                        if ($newid) {
                            $find = true;
                            echo ' - <span class="success">' . $_lang['import_site_success'] . '</span><br />' . "\n";
                            importFiles($newid, $filedir . $alias . '/', $value, 'sub');
                        } else {
                            echo '<span class="fail">' . $_lang["import_site_failed"] . "</span> " . $_lang["import_site_failed_db_error"] . $modx->getDatabase()->getLastError();
                            exit;
                        }
                    }
                }
                if ($find === false) {
                    $date = time();
                    $field['pagetitle'] = '---';
                    $field['content'] = '';
                    $field['createdon'] = $date;
                    $field['editedon'] = $date;
                    $field['hidemenu'] = '1';
                    $newid = $modx->getDatabase()->insert($field, $tbl_site_content);
                    if ($newid) {
                        $find = true;
                        echo ' - <span class="success">' . $_lang['import_site_success'] . '</span><br />' . "\n";
                        importFiles($newid, $filedir . $alias . '/', $value, 'sub');
                    } else {
                        echo '<span class="fail">' . $_lang["import_site_failed"] . "</span> " . $_lang["import_site_failed_db_error"] . $modx->getDatabase()->getLastError();
                        exit;
                    }
                }
            } else {
                // create document
                if ($mode == 'sub' && $value == 'index.html') {
                    continue;
                }
                $filename = $value;
                $fparts = explode('.', $value);
                $alias = $fparts[0];
                $ext = (count($fparts) > 1) ? $fparts[count($fparts) - 1] : "";
                printf("<span>" . $_lang['import_site_importing_document'] . "</span>", $filename);

                if (!in_array($ext, $allowedfiles)) {
                    echo ' - <span class="fail">' . $_lang["import_site_skip"] . '</span><br />' . "\n";
                } else {
                    $filepath = $filedir . $filename;
                    $file = getFileContent($filepath);
                    list($pagetitle, $content, $description) = treatContent($file, $filename, $alias);

                    $date = filemtime($filepath);
                    $field = array();
                    $field['type'] = 'document';
                    $field['contentType'] = 'text/html';
                    $field['pagetitle'] = $pagetitle;
                    $field['longtitle'] = $pagetitle;
                    $field['description'] = $description;
                    $field['alias'] = $modx->stripAlias($alias);
                    $field['published'] = $publish_default;
                    $field['parent'] = $parent;
                    $field['content'] = $modx->getDatabase()->escape($content);
                    $field['richtext'] = $richtext;
                    $field['template'] = $modx->config['default_template'];
                    $field['searchable'] = $search_default;
                    $field['cacheable'] = $cache_default;
                    $field['createdby'] = $createdby;
                    $field['createdon'] = $date;
                    $field['editedon'] = $date;
                    $field['isfolder'] = 0;
                    $field['menuindex'] = ($alias == 'index') ? 0 : 2;
                    $newid = $modx->getDatabase()->insert($field, $tbl_site_content);
                    if ($newid) {
                        echo ' - <span class="success">' . $_lang['import_site_success'] . '</span><br />' . "\n";
                    } else {
                        echo '<span class="fail">' . $_lang["import_site_failed"] . "</span> " . $_lang["import_site_failed_db_error"] . $modx->getDatabase()->getLastError();
                        exit;
                    }

                    $is_site_start = false;
                    if ($filename == 'index.html') {
                        $is_site_start = true;
                    }
                    if ($is_site_start == true && $_POST['reset'] == 'on') {
                        $modx->getDatabase()->update(array('setting_value' => $newid), $tbl_system_settings,
                            "setting_name='site_start'");
                        $modx->getDatabase()->update(array('menuindex' => 0), $tbl_site_content, "id='{$newid}'");
                    }
                }
            }
        }
    }
}

if(!function_exists('getFiles')) {
    /**
     * @param string $directory
     * @param array $listing
     * @param int $count
     * @return array
     */
    function getFiles($directory, $listing = array(), $count = 0)
    {
        global $_lang;
        global $filesfound;
        $dummy = $count;
        if (!empty($directory) && $files = scandir($directory)) {
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                } elseif ($h = @opendir($directory . $file . "/")) {
                    closedir($h);
                    $count = -1;
                    $listing[$file] = getFiles($directory . $file . "/", array(), $count + 1);
                } elseif (strpos($file, '.htm') !== false) {
                    $listing[$dummy] = $file;
                    $dummy = $dummy + 1;
                    $filesfound++;
                }
            }
        } else {
            echo '<p><span class="fail">' . $_lang["import_site_failed"] . "</span> " . $_lang["import_site_failed_no_open_dir"] . $directory . ".</p>";
        }

        return ($listing);
    }
}

if(!function_exists('getFileContent')) {
    /**
     * @param string $filepath
     * @return bool|string
     */
    function getFileContent($filepath)
    {
        global $_lang;
        // get the file
        if (!$buffer = file_get_contents($filepath)) {
            echo '<p><span class="fail">' . $_lang['import_site_failed'] . "</span> " . $_lang["import_site_failed_no_retrieve_file"] . $filepath . ".</p>";
        } else {
            return $buffer;
        }
    }
}

if(!function_exists('pop_index')) {
    /**
     * @param array $array
     * @return array
     */
    function pop_index($array)
    {
        $new_array = array();
        foreach ($array as $k => $v) {
            if ($v !== 'index.html' && $v !== 'index.htm') {
                $new_array[$k] = $v;
            } else {
                array_unshift($new_array, $v);
            }
        }
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $new_array[$k] = $v;
            }
        }

        return $new_array;
    }
}

if(!function_exists('treatContent')) {
    /**
     * @param string $src
     * @param string $filename
     * @param string $alias
     * @return array
     */
    function treatContent($src, $filename, $alias)
    {
        $modx = evolutionCMS();

        $src = mb_convert_encoding($src, $modx->config['modx_charset'], 'UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP,ASCII');

        if (preg_match("@<title>(.*)</title>@i", $src, $matches)) {
            $pagetitle = ($matches[1] !== '') ? $matches[1] : $filename;
            $pagetitle = str_replace('[*pagetitle*]', '', $pagetitle);
        } else {
            $pagetitle = $alias;
        }
        if (!$pagetitle) {
            $pagetitle = $alias;
        }

        if (preg_match('@<meta[^>]+"description"[^>]+content=[\'"](.*)[\'"].+>@i', $src, $matches)) {
            $description = ($matches[1] !== '') ? $matches[1] : $filename;
            $description = str_replace('[*description*]', '', $description);
        } else {
            $description = '';
        }

        if ((preg_match("@<body[^>]*>(.*)[^<]+</body>@is", $src, $matches)) && $_POST['object'] == 'body') {
            $content = $matches[1];
        } else {
            $content = $src;
            $s = '/(<meta[^>]+charset\s*=)[^>"\'=]+(.+>)/i';
            $r = '$1' . $modx->config['modx_charset'] . '$2';
            $content = preg_replace($s, $r, $content);
            $content = preg_replace('@<title>.*</title>@i', "<title>[*pagetitle*]</title>", $content);
        }
        $content = str_replace('[*content*]', '[ *content* ]', $content);
        $content = trim($content);
        $pagetitle = $modx->getDatabase()->escape($pagetitle);

        return array(
            $pagetitle,
            $content,
            $description
        );
    }
}

if(!function_exists('convertLink')) {
    /**
     * @return void
     */
    function convertLink()
    {
        $modx = evolutionCMS();
        $tbl_site_content = $modx->getDatabase()->getFullTableName('site_content');

        $rs = $modx->getDatabase()->select('id,content', $tbl_site_content);
        $p = array();
        $target = array();
        $dir = '';
        while ($row = $modx->getDatabase()->getRow($rs)) {
            $id = $row['id'];
            $array = explode('<a href=', $row['content']);
            $c = 0;
            foreach ($array as $v) {
                if ($v[0] === '"') {
                    $v = substr($v, 1);
                    list($href, $v) = explode('"', $v, 2);
                    $_ = $href;
                    if (strpos($_, $modx->config['site_url']) !== false) {
                        $_ = $modx->config['base_url'] . str_replace($modx->config['site_url'], '', $_);
                    }
                    if ($_[0] === '/') {
                        $_ = substr($_, 1);
                    }
                    $_ = str_replace('/index.html', '.html', $_);
                    $level = substr_count($_, '../');
                    if (1 < $level) {
                        if (!isset($p[$id])) {
                            $p[$id] = $modx->getParentIds($id);
                        }
                        $k = array_keys($p[$id]);
                        while (0 < $level) {
                            $dir = array_shift($k);
                            $level--;
                        }
                        if ($dir != '') {
                            $dir .= '/';
                        }
                    } else {
                        $dir = '';
                    }

                    $_ = trim($_, './');
                    if (strpos($_, '/') !== false) {
                        $_ = substr($_, strrpos($_, '/'));
                    }
                    $_ = $dir . str_replace('.html', '', $_);
                    if (!isset($target[$_])) {
                        $target[$_] = $modx->getIdFromAlias($_);
                    }
                    $target[$_] = trim($target[$_]);
                    if (!empty($target[$_])) {
                        $href = '[~' . $target[$_] . '~]';
                    }
                    $array[$c] = '<a href="' . $href . '"' . $v;
                }
                $c++;
            }
            $content = implode('', $array);
            $f['content'] = $modx->getDatabase()->escape($content);
            $modx->getDatabase()->update($f, $tbl_site_content, "id='{$id}'");
        }
    }
}
