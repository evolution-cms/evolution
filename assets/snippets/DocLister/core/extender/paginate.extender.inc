<?php
/**
 * Paginate extender for DocLister
 *
 * @category extender
 * @license GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @author Agel_Nash <Agel_Nash@xaker.ru>
 * @see http://mis-algoritmos.com/2007/05/27/digg-style-pagination-class
 */
include_once(MODX_BASE_PATH . "assets/snippets/DocLister/lib/DLpaginate.class.php");
include_once(MODX_BASE_PATH . "assets/snippets/DocLister/lib/DLpaginateReversed.class.php");

/**
 * Class paginate_DL_Extender
 */
class paginate_DL_Extender extends extDocLister
{
    /**
     * @var bool
     */
    protected $lang = true;
    protected $reverse = false;
    protected $count = 0;
    /**
     * @var array
     */
    private $_pages = array();

    /**
     * Call method
     *
     * @return int total pages
     */
    protected function run()
    {
        if (($paginate = $this->DocLister->getCFGDef('paginate', '')) == '') {
            $this->DocLister->config->setConfig(array('start' => 0));
        }
        $this->reverse = $this->DocLister->getCFGDef('reversePagination', 0);
        $pages = $this->getListPages($paginate, 'total');
        $display = $this->DocLister->getCFGDef('display', 0);
        switch ($paginate) {
            case 'offset':
                $requestName = $this->getRequestName('start');
                $start = (isset($_GET[$requestName]) && intval($_GET[$requestName]) > 1) ? intval($_GET[$requestName]) : 0;
                $this->_pages['current'] = ceil($start / $display) + 1;
                if ($this->reverse && $this->_pages['current'] > 1) {
                    $offset = $pages * $display - $this->count;
                    $start -= $offset;
                }
                $this->DocLister->config->setConfig(array('start' => $start));
                break;
            case 'pages':
            default:
                $paginate = 'pages';
                $requestName = $this->getRequestName('page');
                if (
                    isset($_GET[$requestName])
                    && !$this->DocLister->getCFGDef('noRedirect', 0)
                ) {
                    if ((!$this->reverse && (intval($_GET[$requestName]) <= 1 || intval($_GET[$requestName]) > $pages)) || ($this->reverse && (intval($_GET[$requestName]) >= $pages || intval($_GET[$requestName]) < 1))) {
                        $this->modx->sendRedirect($this->getUrl());
                    }
                }
                if ($this->reverse) {
                    $start = (isset($_GET[$requestName]) && intval($_GET[$requestName]) <= $pages) ? ($pages - intval($_GET[$requestName])) : 0;
                    $this->_pages['current'] = $start + 1;
                    $offset = $pages * $display - $this->count;
                    if ($this->_pages['current'] > 1) {
                        $this->DocLister->config->setConfig(array('start' => $start * $display - $offset));
                    } else {
                        $display = $display - $offset;
                        $this->DocLister->config->setConfig(array('display' => $display));
                    }
                } else {
                    $start = (isset($_GET[$requestName]) && intval($_GET[$requestName]) > 1) ? (intval($_GET[$requestName]) - 1) : 0;
                    $this->_pages['current'] = $start + 1;
                    $this->DocLister->config->setConfig(array('start' => $start * $display));
                }


                break;
        }

        if ($this->reverse) {
            $currentPage = $pages + 1 - $this->currentPage();
            $diff = $currentPage == $this->totalPage() ? 0 : $display * $pages - $this->count;
            $from = ($this->currentPage() - 1) * $display + 1 - $diff;
            $to = $currentPage == $this->totalPage() ? $display : $from - 1 + $display;
        } else {
            $currentPage = $this->currentPage();
            $from = ($this->currentPage() - 1) * $display + 1;
            $to = $currentPage == $this->totalPage() ? $this->count : $from - 1 + $display;
        }
        $this->DocLister->toPlaceholders($currentPage, 1, "current");
        $this->DocLister->toPlaceholders($from, 1, "from");
        $this->DocLister->toPlaceholders($to, 1, "to");
        $this->DocLister->toPlaceholders($this->renderPages($paginate), 1, "pages");
        $this->DocLister->toPlaceholders(($this->currentPage() == 1 ? 1 : 0), 1,
            "isstart"); //[+isstart+] – is start page
        $this->DocLister->toPlaceholders(($this->currentPage() == $pages ? 1 : 0), 1,
            "isstop"); //[+isstop+] – is end page
        $this->DocLister->toPlaceholders($pages, 1, "totalPages"); // [+totalPages+] - total page.
        $limit = $this->DocLister->getCFGDef('maxDocs', 0);
        if ($limit > 0 && !$this->reverse) {
            switch ($paginate) {
                case 'offset':
                    $offset = $start;
                    break;
                case 'pages':
                default:
                    $offset = $start * $display;
                    break;
            }
            if (($offset + $display) > $limit) {
                $this->DocLister->config->setConfig(array('display' => $limit - $offset));
            }
        }

        return $pages;
    }

    /**
     * Get information about pagination (current, total or all in array)
     *
     * @param string $paginate Mode paginate. Empty string if no paginate
     * @param string $name What information is interested
     * @return string|array information about pagination
     *
     * @todo Set always placeholder count document. Not only pagination
     */
    protected function getListPages($paginate, $name = '')
    {
        if ($paginate == '') {
            $this->_pages['total'] = 1;
        } else {
            $addTables = $this->DocLister->AddTable;
            $tmp = $this->count = (int)$this->DocLister->getChildrenCount();
            $this->DocLister->AddTable = $addTables;

            $display = $this->DocLister->getCFGDef('display', 0);
            $this->_pages['total'] = ceil($tmp / $display);
            if ($this->currentPage() <= $this->totalPage() || $this->totalPage() == 0 || $this->DocLister->getCFGDef('noRedirect',
                    0)
            ) {
                $this->DocLister->toPlaceholders($tmp, 1, "count");
            } else {
                $this->modx->sendRedirect($this->getUrl());
            }
        }

        return APIHelpers::getkey($this->_pages, $name, $this->_pages);
    }

    /**
     * Номер текущей страницы
     * @return int
     */
    public function currentPage()
    {
        return (int)APIHelpers::getkey($this->_pages, 'current', 1);
    }

    /**
     * Общее число страниц
     * @return int
     */
    public function totalPage()
    {
        return (int)APIHelpers::getkey($this->_pages, 'total', 1);
    }

    /**
     * Общее число документов
     * @return int
     */
    public function totalDocs()
    {
        return $this->count;
    }

    /**
     * @param string $mode
     * @return string
     */
    private function renderPages($mode = '')
    {
        $currentPage = $this->currentPage();
        $p = $this->reverse ? new DLpaginateReversed : new DLpaginate;
        $p->nextT = $this->DocLister->getChunkByParam('TplNextP', '@CODE:<a href="[+link+]">[%paginate.next%] ></a>');
        $p->prevT = $this->DocLister->getChunkByParam('TplPrevP', '@CODE:<a href="[+link+]">< [%paginate.prev%]</a>');
        $p->firstT = $this->DocLister->getChunkByParam('TplFirstP');
        $p->lastT = $this->DocLister->getChunkByParam('TplLastP');
        $p->numberT = $this->DocLister->getChunkByParam('TplPage',
            '@CODE:<a href="[+link+]" class="page">[+num+]</a>');
        $p->currentT = $this->DocLister->getChunkByParam('TplCurrentPage', '@CODE:<b class="current">[+num+]</b>');
        $p->dotsT = $this->DocLister->getChunkByParam('TplDotsPage', '@CODE:...');
        $p->prevI = '';
        $p->nextI = '';
        $p->firstI = '';
        $p->lastI = '';
        if ($this->DocLister->getCFGDef("PrevNextAlwaysShow", 0) == '1') {
            $p->nextI = $this->DocLister->getChunkByParam('TplNextI', '@CODE:[%paginate.next%] >');
            $p->prevI = $this->DocLister->getChunkByParam('TplPrevI', '@CODE:< [%paginate.prev%]');
            $p->firstI = $this->DocLister->getChunkByParam('TplFirstI');
            $p->lastI = $this->DocLister->getChunkByParam('TplLastI');
        }
        $p->changeClass($this->DocLister->getCFGDef('PaginateClass', $p->className));
        $p->mainTpl = $this->DocLister->getChunkByParam('TplWrapPaginate',
            '@CODE:<div class="[+class+]">[+wrap+]</div>');
        $p->Items($this->totalPage());
        $p->limit($this->DocLister->getCFGDef("pageLimit", 1)); //show page count
        $p->adjacents($this->DocLister->getCFGDef("pageAdjacents", 4));
        $p->target($this->getUrl());
        $p->currentPage($currentPage);

        switch (true) {
            case ($mode == 'offset' && $this->totalPage() > 1):
                $display = $this->DocLister->getCFGDef('display', 0);
                $offset = $this->reverse ? $this->totalPage() * $display - $this->count : 0;
                if ($currentPage == 1) {
                    $this->DocLister->config->setConfig(array('display'=>$display - $offset));
                }
                $p->setMode('offset', array(
                    'display' => $display
                ))->parameterName($this->getRequestName('start'));
                break;
            case ($mode == 'pages' && $this->totalPage() > 1):
                $p->parameterName($this->getRequestName('page'));
                break;
        }
        if ($this->DocLister->getCFGDef('paginationMeta', 0)) {
            $nextPage = $prevPage = 0;
            if ($this->reverse) {
                $currentPage = $this->totalPage() + 1 - $currentPage;
            } 
            if ($currentPage > 1) {
                $prevPage = $currentPage - 1;
            }
            if ($currentPage < $this->totalPage()) {
                $nextPage = $currentPage + 1;
            }
            if ($nextPage) {
                $this->modx->regClientStartupHTMLBlock('<link rel="next" href="' . $p->get_pagenum_link($nextPage) . '">');
            }
            if ($prevPage) {
                $this->modx->regClientStartupHTMLBlock('<link rel="prev" href="' . $p->get_pagenum_link($prevPage) . '">');
            }
        }

        return $p->getOutput();
    }

    /**
     * @param string $requestName
     * @return array|string
     */
    public function getUrl($requestName = '')
    {
        $url = $this->DocLister->getUrl();
        $params = parse_url($url, PHP_URL_QUERY);
        parse_str(html_entity_decode($params), $params);
        if ($requestName == '') {
            switch ($this->DocLister->getCFGDef('paginate', '')) {
                case 'offset':
                    $requestName = 'start';
                    $url = $this->buildUrl($url, $requestName, $params);
                    break;
                case 'pages':
                default:
                    $requestName = 'page';
                    $call = $this->DocLister->getCFGDef('makePaginateUrl', '');
                    if ((is_object($call) && ($call instanceof Closure)) || is_callable($call)) {
                        $url = call_user_func($call, compact('url', 'params'), $this->modx, $this->DocLister, $this);
                    } else {
                        $url = $this->buildUrl($url, $requestName, $params);
                    }
                    break;
            }
        }


        return $url;
    }

    /**
     * @param $url
     * @param $requestName
     * @param $params
     * @return array|string
     */
    public function buildUrl($url, $requestName, $params)
    {
        if ($requestName != '' && is_array($params)) {
            $q = http_build_query(array_merge($params, array($this->getRequestName($requestName) => null)));
            $url = explode("?", $url, 2);
            $url = $url[0];
            if (!empty($q)) {
                $url .= "?" . $q;
            }
        }

        return $url;
    }

    /**
     * Имя REQUEST переменной в пагинаторе
     *
     * @param string $name основное имя REQUEST переменной в пагинаторе
     * @return string
     */
    public function getRequestName($name)
    {
        $id = $this->DocLister->getCFGDef('id', '');

        return (($id != '') ? ($id . "_") : "") . $name;
    }
}
