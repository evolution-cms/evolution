<?php
namespace SimpleTab;

include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLTemplate.class.php');
include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');
require_once(MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');
require_once(MODX_BASE_PATH . 'assets/lib/Helpers/Assets.php');

/**
 * Class Plugin
 * @package SimpleTab
 */
abstract class Plugin
{
    /**
     * Объект DocumentParser - основной класс MODX
     * @var \DocumentParser
     * @access public
     */
    public $modx = null;
    /**
     * @var string
     */
    public $pluginName = '';
    /**
     * @var array
     */
    public $params = array();
    /**
     * @var string
     */
    public $table = '';
    public $tpl = '';
    public $jsListDefault = '';
    public $jsListCustom = '';
    public $cssListDefault = '';
    public $cssListCustom = '';
    public $pluginEvents = array();
    public $_table = '';
    protected $fs = null;
    protected $assets = null;
    protected $emptyTpl = null;
    protected $jsListEmpty = '';

    public $DLTemplate = null;
    public $lang_attribute = '';

    protected $checkTemplate = true;
    protected $renderEvent = 'OnDocFormRender';

    protected $checkId = true;

    /**
     * @param $modx
     * @param string $lang_attribute
     */
    public function __construct($modx, $lang_attribute = 'en')
    {
        $this->modx = $modx;
        $this->_table = $modx->getFullTableName($this->table);
        $this->lang_attribute = $lang_attribute;
        $this->params = $modx->event->params;
        if ($this->checkTemplate && !isset($this->params['template']) && $modx->event->name != 'OnEmptyTrash') {
            $doc = $modx->getDocument($this->params['id'], 'template', 'all', 'all');
            $this->params['template'] = is_array($doc) ? end($doc) : null;
        }
        //overload plugin and class properties
        $_params = $modx->parseProperties(
            '&template=;;' . $this->params['template'] . ' &id=;;' . $this->params['id'],
            $modx->event->activePlugin,
            'plugin'
        );
        foreach ($_params as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        $this->params = array_merge($this->params, $_params);
        $modx->event->_output = "";
        $this->DLTemplate = \DLTemplate::getInstance($this->modx);
        $this->fs = \Helpers\FS::getInstance();
        $this->assets = \AssetsHelper::getInstance($modx);
    }

    /**
     * @param array $ids
     * @param $folder
     */
    public function clearFolders($ids = array(), $folder)
    {
        foreach ($ids as $id) {
            $this->fs->rmDir($folder . $id . '/');
        }
    }

    /**
     * @return bool
     * false если можно выводить
     */
    public function checkPermissions()
    {
        $templates = isset($this->params['templates']) ? explode(',', $this->params['templates']) : false;
        $documents = isset($this->params['documents']) ? explode(',', $this->params['documents']) : false;
        $ignoreDocs = isset($this->params['ignoreDoc']) ? explode(',', $this->params['ignoreDoc']) : false;
        $roles = isset($this->params['roles']) ? explode(',', $this->params['roles']) : false;
        if ($this->checkTemplate && $templates !== false && in_array($this->params['template'], $templates)) {
            $out = false;
        } elseif ($this->checkId && $documents !== false && in_array($this->params['id'], $documents)) {
            $out = false;
        } else {
            $out = true;
        }

        if (!$out && $this->checkId && $ignoreDocs !== false && in_array($this->params['id'], $ignoreDocs)) {
            $out = true;
        }
        if (!$out && $roles !== false && in_array($_SESSION['mgrRole'], $roles)) {
            $out = false;
        }

        return $out;
    }

    /**
     * @return string
     */
    public function prerender()
    {
        if (! $this->checkTable()) {
            $result = $this->createTable();
            if (! $result) {
                $this->modx->logEvent(0, 3, "Cannot create {$this->table} table.", $this->pluginName);

                return;
            }
            $this->registerEvents($this->pluginEvents);
        }
        $output = $this->assets->registerJQuery();
        $tpl = MODX_BASE_PATH . $this->tpl;
        if ($this->fs->checkFile($tpl)) {
            $output .= '[+js+][+styles+]' . file_get_contents($tpl);
        } else {
            $this->modx->logEvent(0, 3, "Cannot load {$this->tpl} .", $this->pluginName);

            return false;
        }

        return $output;
    }

    /**
     * @param $list
     * @param array $ph
     * @return string
     */
    public function renderJS($list, $ph = array())
    {
        $js = '';
        $scripts = MODX_BASE_PATH . $list;
        if ($this->fs->checkFile($scripts)) {
            $scripts = @file_get_contents($scripts);
            $scripts = $this->DLTemplate->parseChunk('@CODE:' . $scripts, $ph);
            $scripts = json_decode($scripts, true);
            $scripts = isset($scripts['scripts']) ? $scripts['scripts'] : $scripts['styles'];
            $js = $this->assets->registerScriptsList($scripts);
        } else {
            if ($list == $this->jsListDefault) {
                $this->modx->logEvent(0, 3, "Cannot load {$this->jsListDefault} .", $this->pluginName);
            } elseif ($list == $this->cssListDefault) {
                $this->modx->logEvent(0, 3, "Cannot load {$this->cssListDefault} .", $this->pluginName);
            }
        }

        return $js;
    }

    /**
     * @return array
     */
    public function getTplPlaceholders()
    {
        $ph = array();

        return $ph;
    }

    /**
     * @return string
     */
    public function render()
    {
        if (! $this->checkPermissions()) {
            $output = $this->prerender();
            if ($output !== false) {
                $ph = $this->getTplPlaceholders();
                $ph['js'] = $this->renderJS($this->jsListDefault, $ph) . $this->renderJS($this->jsListCustom, $ph);
                $ph['styles'] = $this->renderJS($this->cssListDefault, $ph) . $this->renderJS(
                    $this->cssListCustom,
                    $ph
                );
                $output = $this->DLTemplate->parseChunk('@CODE:' . $output, $ph);
            }

            return $output;
        }
    }

    /**
     * @return string
     */
    public function renderEmpty()
    {
        if (! $this->checkPermissions()) {
            $tpl = MODX_BASE_PATH . $this->emptyTpl;
            if ($this->fs->checkFile($tpl)) {
                $output = '[+js+]' . file_get_contents($tpl);
                $ph = $this->getTplPlaceholders();
                $ph['js'] = $this->renderJS($this->jsListEmpty, $ph);
                $output = $this->DLTemplate->parseChunk('@CODE:' . $output, $ph);

                return $output;
            } else {
                $this->modx->logEvent(0, 3, "Cannot load {$this->emptyTpl} .", $this->pluginName);
            }
        }
    }

    /**
     * @return bool
     */
    public function checkTable()
    {
        $table = $this->modx->db->config['table_prefix'] . $this->table;
        $sql = "SHOW TABLES LIKE '{$table}'";

        return $this->modx->db->getRecordCount($this->modx->db->query($sql));
    }

    /**
     * @return mixed
     */
    public function createTable()
    {
        $sql = '';

        return $this->modx->db->query($sql);
    }

    /**
     * @param array $events
     * @param string $eventsType
     */
    public function registerEvents($events = array(), $eventsType = '6')
    {
        $eventsTable = $this->modx->getFullTableName('system_eventnames');
        foreach ($events as $event) {
            $result = $this->modx->db->select('`id`', $eventsTable, "`name` = '{$event}'");
            if (! $this->modx->db->getRecordCount($result)) {
                $sql = "INSERT INTO {$eventsTable} VALUES (NULL, '{$event}', '{$eventsType}', '{$this->pluginName} Events')";
                if (! $this->modx->db->query($sql)) {
                    $this->modx->logEvent(0, 3, "Cannot register {$event} event.", $this->pluginName);
                }
            }
        }
    }

}
