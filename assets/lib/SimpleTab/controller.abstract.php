<?php namespace SimpleTab;

require_once(MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');

use EvolutionCMS\Core as DocumentParser;

/**
 * Class AbstractController
 * @package SimpleTab
 */
abstract class AbstractController
{
    /**
     * @var string
     */
    public $rfName = '';
    public $rid = 0;
    public $data = null;
    public $FS = null;
    public $isExit = false;
    public $output = null;
    public $params = null;
    public $fireEvents = true;

    public $dlParams = array(
        "controller"  => "onetable",
        "table"       => "",
        'idField'     => "",
        "api"         => 1,
        "idType"      => "documents",
        'ignoreEmpty' => 1,
        'JSONformat'  => "new",
        'display'     => 10,
        'offset'      => 0,
        'sortBy'      => "",
        'sortDir'     => "desc",


    );

    /**
     * Объект DocumentParser - основной класс MODX
     * @var DocumentParser
     * @access protected
     */
    protected $modx = null;

    /**
     * AbstractController constructor.
     * @param DocumentParser $modx
     */
    public function __construct(DocumentParser $modx)
    {
        $this->FS = \Helpers\FS::getInstance();
        $this->modx = $modx;
        $this->params = $modx->event->params;
        $this->rid = isset($_REQUEST[$this->rfName]) ? (int)$_REQUEST[$this->rfName] : 0;
    }

    public function callExit()
    {
        if ($this->isExit) {
            echo $this->output;
            exit;
        }
    }

    /**
     * @return array
     */
    public function remove()
    {
        $out = array();
        $ids = isset($_POST['ids']) ? (string)$_POST['ids'] : '';
        $ids = isset($_POST['id']) ? (string)$_POST['id'] : $ids;
        $out['success'] = false;
        if (!empty($ids)) {
            if ($this->data->deleteAll($ids, $this->rid)) {
                $out['success'] = true;
            }
        }

        return $out;
    }

    /**
     * @return array
     */
    public function place()
    {
        $out = array();
        $ids = isset($_POST['ids']) ? (string)$_POST['ids'] : '';
        $dir = isset($_POST['dir']) ? $_POST['dir'] : 'top';
        $out['success'] = false;
        if (!empty($ids)) {
            if ($this->data->place($ids, $dir, $this->rid)) {
                $out['success'] = true;
            }
        }

        return $out;
    }

    /**
     * @return array
     */
    public function reorder()
    {
        $out = array();
        $source = $_POST['source'];
        $target = $_POST['target'];
        $point = $_POST['point'];
        $orderDir = $_POST['orderDir'];
        $rows = $this->data->reorder($source, $target, $point, $this->rid, $orderDir);

        if ($rows) {
            $out['success'] = true;
        } else {
            $out['success'] = false;
        }

        return $out;
    }

    /**
     * @return string|void
     */
    public function listing()
    {
        if (!$this->rid) {
            $this->isExit = true;

            return;
        }

        return $this->modx->runSnippet("DocLister", $this->dlParams);
    }

    public function dlInit()
    {
        $this->dlParams['table'] = $this->data->tableName();
        $this->dlParams['idField'] = $this->data->fieldPKName();
        $this->dlParams['addWhereList'] = "`{$this->rfName}`={$this->rid}";
        if (isset($_POST['rows'])) {
            $this->dlParams['display'] = (int)$_POST['rows'];
        }
        $offset = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $offset = $offset ? $offset : 1;
        $offset = $this->dlParams['display'] * abs($offset - 1);
        $this->dlParams['offset'] = $offset;
        if (isset($_POST['sort'])) {
            $this->dlParams['sortBy'] = preg_replace('/[^A-Za-z0-9_\-]/', '', $_POST['sort']);
        }
        if (isset($_POST['order']) && in_array(strtoupper($_POST['order']), array("ASC", "DESC"))) {
            $this->dlParams['sortDir'] = $_POST['order'];
        }
        foreach ($this->dlParams as &$param) {
            if (empty($param)) {
                unset($param);
            }
        }
    }

    /**
     * @return null
     */
    public function getLanguageCode()
    {
        $manager_language = $this->modx->config['manager_language'];
        if (file_exists(MODX_MANAGER_PATH . "includes/lang/" . $manager_language . ".inc.php")) {
            include_once MODX_MANAGER_PATH . "includes/lang/" . $manager_language . ".inc.php";
        }

        return isset($modx_lang_attribute) ? $modx_lang_attribute : null;
    }

}
