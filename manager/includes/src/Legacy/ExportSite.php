<?php namespace EvolutionCMS\Legacy;

use EvolutionCMS\Interfaces\ExportSiteInerface;

class ExportSite implements ExportSiteInerface
{
    /**
     * @var string
     */
    public $targetDir;
    /**
     * @var string
     */
    public $generate_mode;
    /**
     * @var
     */
    public $total;
    /**
     * @var int
     */
    public $count;
    /**
     * @var
     */
    public $ignore_ids;
    /**
     * @var array|mixed
     */
    public $exportstart;
    /**
     * @var
     */
    public $repl_before;
    /**
     * @var
     */
    public $repl_after;
    /**
     * @var array
     */
    public $output = array();
    /**
     * @var int
     */
    public $dirCheckCount = 0;

    /**
     * EXPORT_SITE constructor.
     */
    public function __construct()
    {
        $modx = evolutionCMS();

        if (!defined('MODX_BASE_PATH')) {
            return false;
        }
        $this->exportstart = $this->get_mtime();
        $this->count = 0;
        $this->setUrlMode();
        $this->generate_mode = 'crawl';
        $this->targetDir = $modx->config['base_path'] . 'temp/export';
        if (!isset($this->total)) {
            $this->getTotal();
        }
    }

    /**
     * @param string $dir
     */
    public function setExportDir($dir)
    {
        $dir = str_replace('\\', '/', $dir);
        $dir = rtrim($dir, '/');
        $this->targetDir = $dir;
    }

    /**
     * @return int
     */
    public function get_mtime()
    {
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];

        return $mtime;
    }

    /**
     * @return void
     */
    public function setUrlMode()
    {
        $modx = evolutionCMS();

        if ($modx->config['friendly_urls'] == 0) {
            $modx->config['friendly_urls'] = 1;
            $modx->config['use_alias_path'] = 1;
            $modx->clearCache('full');
        }
        $modx->config['make_folders'] = '1';
    }

    /**
     * @param string|array $ignore_ids
     * @param string|int|bool $noncache
     * @return int
     */
    public function getTotal($ignore_ids = '', $noncache = '0')
    {
        $modx = evolutionCMS();
        $tbl_site_content = $modx->getDatabase()->getFullTableName('site_content');

        $ignore_ids = array_filter(array_map('intval', explode(',', $ignore_ids)));
        if (count($ignore_ids) > 0) {
            $ignore_ids = "AND NOT id IN ('" . implode("','", $ignore_ids) . "')";
        } else {
            $ignore_ids = '';
        }

        $this->ignore_ids = $ignore_ids;

        $noncache = ($noncache == 1) ? '' : 'AND cacheable=1';
        $where = "deleted=0 AND ((published=1 AND type='document') OR (isfolder=1)) {$noncache} {$ignore_ids}";
        $rs = $modx->getDatabase()->select('count(id)', $tbl_site_content, $where);
        $this->total = (int)$modx->getDatabase()->getValue($rs);

        return $this->total;
    }

    /**
     * @param string $directory
     * @return bool
     */
    public function removeDirectoryAll($directory = '')
    {
        $rs = false;
        if (empty($directory)) {
            $directory = $this->targetDir;
        }
        $directory = rtrim($directory, '/');
        // if the path is not valid or is not a directory ...
        if (empty($directory)) {
            return false;
        }
        if (strpos($directory, MODX_BASE_PATH) === false) {
            return $rs;
        }

        if (!is_dir($directory)) {
            return $rs;
        } elseif (!is_readable($directory)) {
            return $rs;
        } else {
            $files = glob($directory . '/*');
            if (!empty($files)) {
                foreach ($files as $path) {
                    $rs = is_dir($path) ? $this->removeDirectoryAll($path) : unlink($path);
                }
            }
        }
        if ($directory !== $this->targetDir) {
            $rs = rmdir($directory);
        }

        return $rs;
    }

    /**
     * @param int $docid
     * @param string $filepath
     * @return string
     */
    public function makeFile($docid, $filepath)
    {
        $modx = evolutionCMS(); global $_lang;
        $file_permission = octdec($modx->config['new_file_permissions']);
        if ($this->generate_mode === 'direct') {
            $back_lang = $_lang;
            $src = $modx->executeParser($docid);

            $_lang = $back_lang;
        } else {
            $src = $this->curl_get_contents(MODX_SITE_URL . "index.php?id={$docid}");
        }


        if ($src !== false) {
            if ($this->repl_before !== $this->repl_after) {
                $src = str_replace($this->repl_before, $this->repl_after, $src);
            }
            $result = file_put_contents($filepath, $src);
            if ($result !== false) {
                @chmod($filepath, $file_permission);
            }

            if ($result !== false) {
                return 'success';
            } else {
                return 'failed_no_write';
            }
        } else {
            return 'failed_no_retrieve';
        }
    }

    /**
     * @param int $docid
     * @param string $alias
     * @param string $prefix
     * @param string $suffix
     * @return string
     */
    public function getFileName($docid, $alias = '', $prefix, $suffix)
    {
        $modx = evolutionCMS();

        if ($alias === '') {
            $filename = $prefix . $docid . $suffix;
        } else {
            if ($modx->config['suffix_mode'] === '1' && strpos($alias, '.') !== false) {
                $suffix = '';
            }
            $filename = $prefix . $alias . $suffix;
        }

        return $filename;
    }

    /**
     * @param int $parent
     * @return string
     */
    public function run($parent = 0)
    {
        global $_lang;
        $modx = evolutionCMS();

        $tbl_site_content = $modx->getDatabase()->getFullTableName('site_content');

        $ignore_ids = $this->ignore_ids;
        $dirpath = $this->targetDir . '/';

        $prefix = $modx->config['friendly_url_prefix'];
        $suffix = $modx->config['friendly_url_suffix'];

        $tpl = ' <span class="[+status+]">[+msg1+]</span> [+msg2+]</span>';
        $ph = array();

        $ph['status'] = 'fail';
        $ph['msg1'] = $_lang['export_site_failed'];
        $ph['msg2'] = $_lang["export_site_failed_no_write"] . ' - ' . $dirpath;
        $msg_failed_no_write = $this->parsePlaceholder($tpl, $ph);

        $ph['msg2'] = $_lang["export_site_failed_no_retrieve"];
        $msg_failed_no_retrieve = $this->parsePlaceholder($tpl, $ph);

        $ph['status'] = 'success';
        $ph['msg1'] = $_lang['export_site_success'];
        $ph['msg2'] = '';
        $msg_success = $this->parsePlaceholder($tpl, $ph);

        $ph['msg2'] = $_lang['export_site_success_skip_doc'];
        $msg_success_skip_doc = $this->parsePlaceholder($tpl, $ph);

        $ph['msg2'] = $_lang['export_site_success_skip_dir'];
        $msg_success_skip_dir = $this->parsePlaceholder($tpl, $ph);

        $fields = "id, alias, pagetitle, isfolder, (content = '' AND template = 0) AS wasNull, published";
        $noncache = $_POST['includenoncache'] == 1 ? '' : 'AND cacheable=1';
        $where = "parent = '{$parent}' AND deleted=0 AND ((published=1 AND type='document') OR (isfolder=1)) {$noncache} {$ignore_ids}";
        $rs = $modx->getDatabase()->select($fields, $tbl_site_content, $where);

        $ph = array();
        $ph['total'] = $this->total;
        $folder_permission = octdec($modx->config['new_folder_permissions']);
        while ($row = $modx->getDatabase()->getRow($rs)) {
            $this->count++;
            $filename = '';
            $row['count'] = $this->count;
            $row['url'] = $modx->makeUrl($row['id']);

            if (!$row['wasNull']) { // needs writing a document
                $docname = $this->getFileName($row['id'], $row['alias'], $prefix, $suffix);
                $filename = $dirpath . $docname;
                if (!is_file($filename)) {
                    if ($row['published'] === '1') {
                        $status = $this->makeFile($row['id'], $filename);
                        switch ($status) {
                            case 'failed_no_write'   :
                                $row['status'] = $msg_failed_no_write;
                                break;
                            case 'failed_no_retrieve':
                                $row['status'] = $msg_failed_no_retrieve;
                                break;
                            default:
                                $row['status'] = $msg_success;
                        }
                    } else {
                        $row['status'] = $msg_failed_no_retrieve;
                    }
                } else {
                    $row['status'] = $msg_success_skip_doc;
                }
                $this->output[] = $this->parsePlaceholder($_lang['export_site_exporting_document'], $row);
            } else {
                $row['status'] = $msg_success_skip_dir;
                $this->output[] = $this->parsePlaceholder($_lang['export_site_exporting_document'], $row);
            }
            if ($row['isfolder'] === '1' && ($modx->config['suffix_mode'] !== '1' || strpos($row['alias'],
                        '.') === false)) { // needs making a folder
                $end_dir = ($row['alias'] !== '') ? $row['alias'] : $row['id'];
                $dir_path = $dirpath . $end_dir;
                if (strpos($dir_path, MODX_BASE_PATH) === false) {
                    return false;
                }
                if (!is_dir($dir_path)) {
                    if (is_file($dir_path)) {
                        @unlink($dir_path);
                    }
                    mkdir($dir_path);
                    @chmod($dir_path, $folder_permission);

                }


                if ($modx->config['make_folders'] === '1' && $row['published'] === '1') {
                    if (!empty($filename) && is_file($filename)) {
                        rename($filename, $dir_path . '/index.html');
                    }
                }
                $this->targetDir = $dir_path;
                $this->run($row['id']);
            }
        }

        return implode("\n", $this->output);
    }

    /**
     * @param string $url
     * @param int $timeout
     * @return string
     */
    public function curl_get_contents($url, $timeout = 30)
    {
        if (!function_exists('curl_init')) {
            return @file_get_contents($url);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);    // 0 = DO NOT VERIFY AUTHENTICITY OF SSL-CERT
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);    // 2 = CERT MUST INDICATE BEING CONNECTED TO RIGHT SERVER
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public function parsePlaceholder($tpl, $ph = array())
    {
        foreach ($ph as $k => $v) {
            $k = "[+{$k}+]";
            $tpl = str_replace($k, $v, $tpl);
        }

        return $tpl;
    }

}
