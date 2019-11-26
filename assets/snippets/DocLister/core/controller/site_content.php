<?php

/**
 * site_content controller
 * @see http://modx.im/blog/addons/374.html
 *
 * @category controller
 * @license GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @author Agel_Nash <Agel_Nash@xaker.ru>, kabachello <kabachnik@hotmail.com>
 */
class site_contentDocLister extends DocLister
{
    /**
     * Экземпляр экстендера TV
     *
     * @var null|xNop|tv_DL_Extender
     */
    protected $extTV = null;

    /**
     * Экземпляр экстендера cache
     *
     * @var null|xNop|cache_DL_Extender
     */
    protected $extCache = null;

    /**
     * Экземпляр экстендера пагинации
     * @var null|paginate_DL_Extender
     */
    protected $extPaginate = null;

    /**
     * site_contentDocLister constructor.
     * @param DocumentParser $modx
     * @param array $cfg
     * @param null $startTime
     */
    public function __construct($modx, $cfg = array(), $startTime = null)
    {
        parent::__construct($modx, $cfg, $startTime);
        $this->extTV = $this->getExtender('tv', true, true);
    }

    /**
     * @abstract
     */
    public function getDocs($tvlist = '')
    {
        if ($tvlist == '') {
            $tvlist = $this->getCFGDef('tvList', '');
        }

        $this->extTV->getAllTV_Name();

        /**
         * @var $multiCategories multicategories_DL_Extender
         */
        $multiCategories = $this->getCFGDef('multiCategories', 0) ? $this->getExtender('multicategories', true) : null;
        if ($multiCategories) {
            $multiCategories->init($this);
        }

        /**
         * @var $extJotCount jotcount_DL_Extender
         */
        $extJotCount = $this->getCFGdef('jotcount', 0) ? $this->getExtender('jotcount', true) : null;

        if ($extJotCount) {
            $extJotCount->init($this);
        }

        /**
         * @var $extCommentsCount commentscount_DL_Extender
         */
        $extCommentsCount = $this->getCFGdef('commentsCount', 0) ? $this->getExtender('commentscount', true) : null;

        if ($extCommentsCount) {
            $extCommentsCount->init($this);
        }

        if ($this->extPaginate = $this->getExtender('paginate')) {
            $this->extPaginate->init($this);
        }

        $type = $this->getCFGDef('idType', 'parents');
        $this->_docs = ($type == 'parents') ? $this->getChildrenList() : $this->getDocList();
        if ($tvlist != '' && count($this->_docs) > 0) {
            $tv = $this->extTV->getTVList(array_keys($this->_docs), $tvlist);
            if (!is_array($tv)) {
                $tv = array();
            }
            foreach ($tv as $docID => $TVitem) {
                if (isset($this->_docs[$docID]) && is_array($this->_docs[$docID])) {
                    $this->_docs[$docID] = array_merge($this->_docs[$docID], $TVitem);
                } else {
                    unset($this->_docs[$docID]);
                }
            }
        }
        if (1 == $this->getCFGDef('tree', '0')) {
            $this->treeBuild('id', 'parent');
        }

        return $this->_docs;
    }

    /**
     * @param string $tpl
     * @return string
     */
    public function _render($tpl = '')
    {
        $out = '';
        $separator = $this->getCFGDef('outputSeparator', '');
        if ($tpl == '') {
            $tpl = $this->getCFGDef('tpl', '@CODE:<a href="[+url+]">[+pagetitle+]</a><br />');
        }
        if ($tpl != '') {
            $this->toPlaceholders(count($this->_docs), 1, "display"); // [+display+] - сколько показано на странице.

            $i = 1;
            $sysPlh = $this->renameKeyArr($this->_plh, $this->getCFGDef("sysKey", "dl"));
            if (count($this->_docs) > 0) {
                /**
                 * @var $extUser user_DL_Extender
                 */
                if ($extUser = $this->getExtender('user')) {
                    $extUser->init($this, array('fields' => $this->getCFGDef("userFields", "")));
                }

                /**
                 * @var $extSummary summary_DL_Extender
                 */
                $extSummary = $this->getExtender('summary');

                /**
                 * @var $extPrepare prepare_DL_Extender
                 */
                $extPrepare = $this->getExtender('prepare');

                $this->skippedDocs = 0;
                foreach ($this->_docs as $item) {
                    $this->renderTPL = $tpl;
                    if ($extUser) {
                        $item = $extUser->setUserData($item); //[+user.id.createdby+], [+user.fullname.publishedby+], [+dl.user.publishedby+]....
                    }

                    $item['summary'] = $extSummary ? $this->getSummary($item, $extSummary, 'introtext', 'content') : '';

                    $item = array_merge(
                        $item,
                        $sysPlh
                    ); //inside the chunks available all placeholders set via $modx->toPlaceholders with prefix id, and with prefix sysKey
                    $item['iteration'] = $i; //[+iteration+] - Number element. Starting from zero

                    if (isset($item['menutitle']) && $item['menutitle'] != '') {
                        $item['title'] = $item['menutitle'];
                    } elseif (isset($item['pagetitle'])) {
                        $item['title'] = $item['pagetitle'];
                    }

                    if ($this->getCFGDef('makeUrl', 1)) {
                        if ($item['type'] == 'reference') {
                            $item['url'] = is_numeric($item['content']) ? $this->modx->makeUrl(
                                $item['content'],
                                '',
                                '',
                                $this->getCFGDef('urlScheme', '')
                            ) : $item['content'];
                        } else {
                            $item['url'] = $this->modx->makeUrl($item['id'], '', '', $this->getCFGDef('urlScheme', ''));
                        }
                    }
                    $date = $this->getCFGDef('dateSource', 'pub_date');
                    if (isset($item[$date])) {
                        if (!$item[$date] && $date == 'pub_date' && isset($item['createdon'])) {
                            $date = 'createdon';
                        }
                        $_date = is_numeric($item[$date]) && $item[$date] == (int)$item[$date] ? $item[$date] : strtotime($item[$date]);
                        if ($_date !== false) {
                            $_date = $_date + $this->modx->config['server_offset_time'];
                            $dateFormat = $this->getCFGDef('dateFormat', '%d.%b.%y %H:%M');
                            if ($dateFormat) {
                                $item['date'] = strftime($dateFormat, $_date);
                            }
                        }
                    }

                    $findTpl = $this->renderTPL;
                    $tmp = $this->uniformPrepare($item, $i);
                    extract($tmp, EXTR_SKIP);
                    if ($this->renderTPL == '') {
                        $this->renderTPL = $findTpl;
                    }

                    if ($extPrepare) {
                        $item = $extPrepare->init($this, array(
                            'data'      => $item,
                            'nameParam' => 'prepare'
                        ));
                        if ($item === false) {
                            $this->skippedDocs++;
                            continue;
                        }
                    }
                    $tmp = $this->parseChunk($this->renderTPL, $item);

                    if ($this->getCFGDef('contentPlaceholder', 0) !== 0) {
                        $this->toPlaceholders(
                            $tmp,
                            1,
                            "item[" . $i . "]"
                        ); // [+item[x]+] – individual placeholder for each iteration documents on this page
                    }
                    $out .= $tmp;
                    if (next($this->_docs) !== false) {
                        $out .= $separator;
                    }
                    $i++;
                }
            } else {
                $noneTPL = $this->getCFGDef('noneTPL', '');
                $out = ($noneTPL != '') ? $this->parseChunk($noneTPL, $sysPlh) : '';
            }
            $out = $this->renderWrap($out);
        }

        return $this->toPlaceholders($out);
    }

    /**
     * @param array $data
     * @param mixed $fields
     * @param array $array
     * @return string
     */
    public function getJSON($data, $fields, $array = array())
    {
        $out = array();
        $fields = is_array($fields) ? $fields : explode(",", $fields);
        $date = $this->getCFGDef('dateSource', 'pub_date');
        /**
         * @var $extSummary summary_DL_Extender
         */
        $extSummary = $this->getExtender('summary');

        /**
         * @var $extPrepare prepare_DL_Extender
         */
        $extPrepare = $this->getExtender('prepare');

        /**
         * @var $extE e_DL_Extender
         */
        $extE = $this->getExtender('e', true, true);

        foreach ($data as $num => $row) {
            if ((array('1') == $fields || in_array('summary', $fields)) && $extSummary) {
                $row['summary'] = $this->getSummary($row, $extSummary, 'introtext', 'content');
            }

            if (array('1') == $fields || in_array('date', $fields)) {
                if (isset($row[$date])) {
                    if (!$row[$date] && $date == 'pub_date' && isset($row['createdon'])) {
                        $date = 'createdon';
                    }
                    $_date = is_numeric($row[$date]) && $row[$date] == (int)$row[$date] ? $row[$date] : strtotime($row[$date]);
                    if ($_date !== false) {
                        $_date = $_date + $this->modx->config['server_offset_time'];
                        $dateFormat = $this->getCFGDef('dateFormat', '%d.%b.%y %H:%M');
                        if ($dateFormat) {
                            $row['date'] = strftime($dateFormat, $_date);
                        }
                    }
                }
            }

            if (array('1') == $fields || in_array('title', $fields)) {
                if (isset($row['pagetitle'])) {
                    $row['title'] = empty($row['menutitle']) ? $row['pagetitle'] : $row['menutitle'];
                }
            }
            if ((bool)$this->getCFGDef('makeUrl', 1) && (array('1') == $fields || in_array('url', $fields))
            ) {
                if (isset($row['type']) && $row['type'] == 'reference' && isset($row['content'])) {
                    $row['url'] = is_numeric($row['content']) ? $this->modx->makeUrl(
                        $row['content'],
                        '',
                        '',
                        $this->getCFGDef('urlScheme', '')
                    ) : $row['content'];
                } elseif (isset($row['id'])) {
                    $row['url'] = $this->modx->makeUrl($row['id'], '', '', $this->getCFGDef('urlScheme', ''));
                }
            }
            if ($extE && $tmp = $extE->init($this, array('data' => $row))) {
                if (is_array($tmp)) {
                    $row = $tmp;
                }
            }

            if ($extPrepare) {
                $row = $extPrepare->init($this, array('data' => $row));
                if ($row === false) {
                    continue;
                }
            }
            $out[$num] = $row;
        }

        return parent::getJSON($out, $fields, $out);
    }

    /**
     * @abstract
     */
    public function getChildrenCount()
    {
        $out = $this->extCache->load('childrenCount');
        if ($out === false) {
            $out = 0;
            $sanitarInIDs = $this->sanitarIn($this->IDs);
            if ($sanitarInIDs != "''" || $this->getCFGDef('ignoreEmpty', '0')) {
                $q_true = $this->getCFGDef('ignoreEmpty', '0');
                $q_true = $q_true ? $q_true : $this->getCFGDef('idType', 'parents') == 'parents';
                $where = $this->getCFGDef('addWhereList', '');
                $where = sqlHelper::trimLogicalOp($where);
                $where = ($where ? $where . ' AND ' : '') . $this->_filters['where'];
                if ($where != '' && $this->_filters['where'] != '') {
                    $where .= " AND ";
                }
                $where = sqlHelper::trimLogicalOp($where);

                $where = "WHERE {$where}";
                $whereArr = array();
                if (!$this->getCFGDef('showNoPublish', 0)) {
                    $whereArr[] = "c.deleted=0 AND c.published=1";
                } else {
                    $q_true = 1;
                }

                $tbl_site_content = $this->getTable('site_content', 'c');

                if ($sanitarInIDs != "''") {
                    switch ($this->getCFGDef('idType', 'parents')) {
                        case 'parents':
                            switch ($this->getCFGDef('showParent', '0')) {
                                case '-1':
                                    $tmpWhere = "c.parent IN (" . $sanitarInIDs . ")";
                                    break;
                                case 0:
                                    $tmpWhere = "c.parent IN ({$sanitarInIDs}) AND c.id NOT IN({$sanitarInIDs})";
                                    break;
                                case 1:
                                default:
                                    $tmpWhere = "(c.parent IN ({$sanitarInIDs}) OR c.id IN({$sanitarInIDs}))";
                                    break;
                            }
                            if (($addDocs = $this->getCFGDef('documents', '')) != '') {
                                $addDocs = $this->sanitarIn($this->cleanIDs($addDocs));
                                $whereArr[] = "((" . $tmpWhere . ") OR c.id IN({$addDocs}))";
                            } else {
                                $whereArr[] = $tmpWhere;
                            }

                            break;
                        case 'documents':
                            $whereArr[] = "c.id IN({$sanitarInIDs})";
                            break;
                    }
                }
                $from = $tbl_site_content . " " . $this->_filters['join'];
                $where = sqlHelper::trimLogicalOp($where);

                $q_true = $q_true ? $q_true : trim($where) != 'WHERE';

                if (trim($where) != 'WHERE') {
                    $where .= " AND ";
                }

                $where .= implode(" AND ", $whereArr);
                $where = sqlHelper::trimLogicalOp($where);

                if (trim($where) == 'WHERE') {
                    $where = '';
                }
                $group = $this->getGroupSQL($this->getCFGDef('groupBy', $this->getPK()));

                $q_true = $q_true ? $q_true : $group != '';
                if ($q_true) {
                    $maxDocs = $this->getCFGDef('maxDocs', 0);
                    $limit = $maxDocs > 0 ? $this->LimitSQL($this->getCFGDef('maxDocs', 0)) : '';
                    $rs = $this->dbQuery("SELECT count(*) FROM (SELECT count(*) FROM {$from} {$where} {$group} {$limit}) as `tmp`");
                    $out = $this->modx->db->getValue($rs);
                } else {
                    $out = count($this->IDs);
                }
            }
            $this->extCache->save($out, 'childrenCount');
        }

        return $out;
    }

    /**
     * @return array
     */
    protected function getDocList()
    {
        $out = array();
        $sanitarInIDs = $this->sanitarIn($this->IDs);
        if ($sanitarInIDs != "''" || $this->getCFGDef('ignoreEmpty', '0')) {
            $where = $this->getCFGDef('addWhereList', '');
            $where = sqlHelper::trimLogicalOp($where);

            $where = ($where ? $where . ' AND ' : '') . $this->_filters['where'];
            $where = sqlHelper::trimLogicalOp($where);

            $tbl_site_content = $this->getTable('site_content', 'c');
            if ($sanitarInIDs != "''") {
                $where .= ($where ? " AND " : "") . "c.id IN ({$sanitarInIDs}) AND";
            }
            $where = sqlHelper::trimLogicalOp($where);

            if ($this->getCFGDef('showNoPublish', 0)) {
                if ($where != '') {
                    $where = "WHERE {$where}";
                } else {
                    $where = '';
                }
            } else {
                if ($where != '') {
                    $where = "WHERE {$where} AND ";
                } else {
                    $where = "WHERE {$where} ";
                }
                $where .= "c.deleted=0 AND c.published=1";
            }


            $fields = $this->getCFGDef('selectFields', 'c.*');
            $group = $this->getGroupSQL($this->getCFGDef('groupBy', $this->getPK()));
            $sort = $this->SortOrderSQL("if(c.pub_date=0,c.createdon,c.pub_date)");
            list($tbl_site_content, $sort) = $this->injectSortByTV(
                $tbl_site_content . ' ' . $this->_filters['join'],
                $sort
            );

            $limit = $this->LimitSQL($this->getCFGDef('queryLimit', 0));

            $rs = $this->dbQuery("SELECT {$fields} FROM {$tbl_site_content} {$where} {$group} {$sort} {$limit}");

            while ($item = $this->modx->db->getRow($rs)) {
                $out[$item['id']] = $item;
            };
        }

        return $out;
    }

    /**
     * @param string|array $id
     * @return array
     */
    public function getChildrenFolder($id)
    {
        $out = array();
        $where = $this->getCFGDef('addWhereFolder', '');
        $where = sqlHelper::trimLogicalOp($where);
        if ($where != '') {
            $where .= " AND ";
        }

        $tbl_site_content = $this->getTable('site_content', 'c');
        $sanitarInIDs = $this->sanitarIn($id);
        if ($this->getCFGDef('showNoPublish', 0)) {
            $where = "WHERE {$where} c.parent IN ({$sanitarInIDs}) AND c.isfolder=1";
        } else {
            $where = "WHERE {$where} c.parent IN ({$sanitarInIDs}) AND c.deleted=0 AND c.published=1 AND c.isfolder=1";
        }

        $rs = $this->dbQuery("SELECT id FROM {$tbl_site_content} {$where}");

        while ($item = $this->modx->db->getRow($rs)) {
            $out[] = $item['id'];
        }

        return $out;
    }

    /**
     * @param $table
     * @param $sort
     * @return array
     */
    protected function injectSortByTV($table, $sort)
    {
        $out = $this->getExtender('tv', true, true)->injectSortByTV($table, $sort);
        if (!is_array($out) || empty($out)) {
            $out = array($table, $sort);
        }

        return $out;
    }

    /**
     * @return array
     */
    protected function getChildrenList()
    {
        $where = array();
        $out = array();

        $tmpWhere = $this->getCFGDef('addWhereList', '');
        $tmpWhere = sqlHelper::trimLogicalOp($tmpWhere);
        if (!empty($tmpWhere)) {
            $where[] = $tmpWhere;
        }

        $tmpWhere = sqlHelper::trimLogicalOp($this->_filters['where']);
        if (!empty($tmpWhere)) {
            $where[] = $tmpWhere;
        }

        $tbl_site_content = $this->getTable('site_content', 'c');

        $sort = $this->SortOrderSQL("if(c.pub_date=0,c.createdon,c.pub_date)");
        list($from, $sort) = $this->injectSortByTV($tbl_site_content . ' ' . $this->_filters['join'], $sort);
        $sanitarInIDs = $this->sanitarIn($this->IDs);

        $tmpWhere = null;
        if ($sanitarInIDs != "''") {
            switch ($this->getCFGDef('showParent', '0')) {
                case '-1':
                    $tmpWhere = "c.parent IN (" . $sanitarInIDs . ")";
                    break;
                case 0:
                    $tmpWhere = "c.parent IN (" . $sanitarInIDs . ") AND c.id NOT IN(" . $sanitarInIDs . ")";
                    break;
                case 1:
                default:
                    $tmpWhere = "(c.parent IN (" . $sanitarInIDs . ") OR c.id IN({$sanitarInIDs}))";
                    break;
            }
        }
        if (($addDocs = $this->getCFGDef('documents', '')) != '') {
            $addDocs = $this->sanitarIn($this->cleanIDs($addDocs));
            if (empty($tmpWhere)) {
                $tmpWhere = "c.id IN({$addDocs})";
            } else {
                $tmpWhere = "((" . $tmpWhere . ") OR c.id IN({$addDocs}))";
            }
        }
        if (!empty($tmpWhere)) {
            $where[] = $tmpWhere;
        }
        if (!$this->getCFGDef('showNoPublish', 0)) {
            $where[] = "c.deleted=0 AND c.published=1";
        }
        if (!empty($where)) {
            $where = "WHERE " . implode(" AND ", $where);
        } else {
            $where = '';
        }
        $fields = $this->getCFGDef('selectFields', 'c.*');
        $group = $this->getGroupSQL($this->getCFGDef('groupBy', $this->getPK()));

        if ($sanitarInIDs != "''" || $this->getCFGDef('ignoreEmpty', '0')) {
            $rs = $this->dbQuery("SELECT {$fields} FROM " . $from . " " . $where . " " .
                $group . " " .
                $sort . " " .
                $this->LimitSQL($this->getCFGDef('queryLimit', 0)));

            while ($item = $this->modx->db->getRow($rs)) {
                $out[$item['id']] = $item;
            }
        }

        return $out;
    }

    /**
     * @param string $field
     * @param string $type
     * @return string
     */
    public function changeSortType($field, $type)
    {
        $type = trim($type);
        switch (strtoupper($type)) {
            case 'TVDATETIME':
                $field = "STR_TO_DATE(" . $field . ",'%d-%m-%Y %H:%i:%s')";
                break;
            default:
                $field = parent::changeSortType($field, $type);
        }

        return $field;
    }
}
