<?php
include_once('site_content.php');

/**
 * site_content controller
 * @see http://modx.im/blog/addons/374.html
 *
 * @category controller
 * @license GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @author Agel_Nash <Agel_Nash@xaker.ru>, kabachello <kabachnik@hotmail.com>
 */
class site_content_menuDocLister extends site_contentDocLister
{
    public $levels = array();
    protected $currentLevel = 1;
    protected $docTvs = array();
    protected $IDs = array();
    protected $activeBranch = array();
    protected $countChildren = array();
    protected $display = array();

    /**
     * Очистка массива $IDs по которому потом будет производиться выборка документов
     *
     * @param mixed $IDs список id документов по которым необходима выборка
     * @return array очищенный массив
     */
    public function setIDs($IDs)
    {
        $this->debug->debug('set ID list ' . $this->debug->dumpData($IDs), 'setIDs', 2);
        $IDs = $this->cleanIDs($IDs);
        $this->debug->debugEnd("setIDs");

        return ($this->IDs = $IDs);
    }

    /**
     * @param string $tvlist
     * @return array
     */
    public function getDocs($tvlist = '')
    {
        $this->extTV->getAllTV_Name();
        if ($ids = $this->getCFGDef('documents')) {
            $this->levels = $this->extCache->load('menudata');
            if ($this->levels === false) {
                $this->levels = array();
                $this->setIDs($ids);
                $docs = $this->getDocList();
                $display = count($docs);
                $iteration = 1;
                foreach ($docs as $id => &$item) {
                    $item['iteration'] = $iteration++;
                    $item['_display'] = $display;
                    $item['_parent'] = $item['parent'];
                    $item['parent'] = 0;
                }
                $this->levels[1] = $docs;
                $this->extCache->save($this->levels, 'menudata');
            }
            $this->setActiveBranch($this->getHereId(), 1);
        } else {
            $this->_getChildren();
        }

        $this->addTvs($tvlist)->countChildren();

        return $this->levels;
    }

    /**
     *
     */
    public function _getChildren()
    {
        $maxDepth = $this->getCFGDef('maxDepth', 10);
        if ($this->getCFGDef('hideSubMenus', 0) && empty($this->getCFGDef('openIds'))) {
            $maxDepth = min($maxDepth, $this->setActiveBranch($this->getHereId()));
            if (empty(array_intersect($this->IDs, $this->activeBranch))) {
                $maxDepth = 1;
                $this->config->setConfig(array('hideSubMenus' => 0));
            };
        } else {
            $this->setActiveBranch($this->getHereId());
        }
        if ($oIds = $this->getCFGDef('openIds')) {
            $maxDepth = 1;
            $oIds = $this->cleanIDs($oIds);
            $oIds[] = $this->getHereId();
            foreach ($oIds as $id) {
                if (($c = $this->setActiveBranch($id)) > $maxDepth) {
                    $maxDepth = $c;
                }
            }
            $this->config->setConfig(array('hideSubMenus' => 1));
        }
        $key = 'menudata' . $maxDepth;
        $displayKey = 'menudisplay' . $maxDepth;
        $this->levels = $this->extCache->load($key);
        if ($this->levels === false) {
            $this->levels = array();
            $currentLevel = &$this->currentLevel;
            $currentLevel = 1;
            if ($this->getCFGDef('showParent', 0) && in_array(0, $this->IDs)) {
                $this->config->setConfig(array('showParent' => 0));
            }
            $joinMenus = $this->getCFGDef('joinMenus', 0) && !$this->getCFGDef('showParent', 0);
            while ($currentLevel <= $maxDepth) {
                $orderBy = $this->getCFGDef('orderBy');
                if ($this->getCFGDef('showParent', 0) && $currentLevel == 1) {
                    $docs = $this->getDocList();
                    $this->config->setConfig(array('showParent' => 0));
                } else {
                    $docs = $this->getChildrenList();
                }
                if ($currentLevel == 1 && $joinMenus) {
                    $tmp = array();
                    $display = 0;
                    $iteration = 1;
                    foreach ($docs as $id => $item) {
                        $tmp[$item['parent']][] = $item['id'];
                        $display++;
                    }
                    foreach ($tmp as $id => $item) {
                        foreach ($item as $_id) {
                            $docs[$_id]['_display'] = $display;
                            $docs[$_id]['iteration'] = $iteration++;
                        }
                    }
                    unset($tmp);
                } else {
                    foreach ($docs as $id => &$item) {
                        $parent = $item['parent'];
                        if (!isset($this->display[$parent])) {
                            $this->display[$parent] = 1;
                        }
                        $item['iteration'] = $this->display[$parent]++;
                    }
                }
                $this->config->setConfig(array('orderBy' => $orderBy));
                if (empty($docs)) {
                    break;
                }
                $this->levels[$currentLevel++] = $docs;
                $this->IDs = array_keys($docs);
                $this->AddTable = array();
            }
            $this->extCache->save($this->levels, $key);
            $this->extCache->save($this->display, $displayKey);
        } else {
            $this->display = $this->extCache->load($displayKey);
        }
    }

    /**
     * @param string $tvlist
     * @return site_content_menuDocLister
     */
    protected function addTvs($tvlist = '')
    {
        if ($tvlist == '') {
            $tvlist = $this->getCFGDef('tvList', '');
        }

        if ($tvlist != '') {
            $this->docTvs = $this->extCache->load('tvs');
            if ($this->docTvs === false) {
                $this->docTvs = array();
                $ids = array();
                foreach ($this->levels as $level => $docs) {
                    $ids = array_merge($ids, array_keys($docs));
                }
                if (!empty($ids)) {
                    $tv = $this->extTV->getTVList($ids, $tvlist);
                    if (!is_array($tv)) {
                        $tv = array();
                    }
                    $this->docTvs = $tv;
                }
                $this->extCache->save('tvs');
            }
        }

        return $this;
    }

    /**
     * Список активных документов
     * @param $id
     * @param int $maxDepth
     * @return int
     */
    public function setActiveBranch($id, $maxDepth = 10)
    {
        $ids = array_values($this->modx->getParentIds($id, $maxDepth));
        $ids[] = $id;
        $ids[] = 0;
        $this->activeBranch = array_merge($this->activeBranch, $ids);
        $this->activeBranch = array_keys(array_flip($this->activeBranch));

        return count($ids);
    }

    /**
     * Подсчет количества непосредственных дочерних документов
     */
    public function countChildren()
    {
        if (!$this->getCFGDef('countChildren', 0)) {
            return;
        }
        $this->countChildren = $this->extCache->load('countChildren');
        if ($this->countChildren === false) {
            $this->countChildren = array();
            $ids = array();
            $out = &$this->countChildren;
            foreach ($this->levels as $level => $docs) {
                $ids = array_merge($ids, array_keys($docs));
            }
            $maxDepth = count($this->levels);
            $currentDepth = 1;
            while ($currentDepth <= $maxDepth) {
                $_ids = implode(',', $ids);
                if (empty($_ids)) {
                    break;
                }
                $q = $this->dbQuery("SELECT `parent`,COUNT(*) as `count` FROM {$this->getTable('site_content')} WHERE `parent` IN ({$_ids}) AND `published`=1 AND `deleted`=0 GROUP BY `parent`");
                $_ids = array();
                while ($row = $this->modx->db->getRow($q)) {
                    $_ids[] = $row['parent'];
                    $out[$row['parent']] = $row['count'];
                }
                if (!empty($_ids)) {
                    $ids = $this->diff($ids, $_ids);
                } else {
                    break;
                }
                $currentDepth++;
            }
            $this->extCache->save($this->countChildren, 'countChildren');
        }
    }

    /**
     * Возвращает элементы массива $a, которых нет в массиве $b
     * @param $b
     * @param $a
     * @return array
     */
    private function diff($b, $a)
    {
        $at = array_flip($a);
        $d = array();
        foreach ($b as $i) {
            if (!isset($at[$i])) {
                $d[] = $i;
            }
        }

        return $d;
    }

    /**
     * @return string
     */
    protected function getBranchCacheKey()
    {
        $depth = count($this->levels);
        $out = array();
        while ($depth > 0) {
            $ids = array_keys($this->levels[$depth]);
            foreach ($this->activeBranch as $id) {
                if (in_array($id, $ids)) {
                    $out[] = $id;
                    break;
                }
            }
            $depth--;
        }
        $key = $this->getCFGDef('renderCacheKey', '');
        $out = 'branch' . $key . implode('-', $out);

        return $out;
    }


    /**
     * Подготовка результатов к отображению в соответствии с настройками
     *
     * @param string $tpl шаблон
     * @return string
     */
    public function render($tpl = '')
    {
        $this->debug->debug(array('Render data with template ' => $tpl), 'render', 2, array('html'));
        if (empty($this->levels)) {
            $noneTpl = $this->getCFGDef('noneTpl');
            $out = $noneTpl ? $this->parseChunk($noneTpl, array()) : '';
        } else {
            $out = $this->_render($tpl);
        }
        if ($out) {
            $this->outData = DLTemplate::getInstance($this->modx)->parseDocumentSource($out);
        }
        $this->debug->debugEnd('render');

        return $this->outData;
    }

    /**
     * @param string $tpl
     * @return string
     */
    public function _render($tpl = '')
    {
        $key = $this->getBranchCacheKey();
        $out = $this->extCache->load($key);
        if ($out === false) {
            $currentLevel = &$this->currentLevel;
            $currentLevel = count($this->levels);
            $docs = $this->levels;
            /** @var prepare_DL_Extender_ $extPrepare */
            $extPrepare = $this->getExtender('prepare');
            while ($currentLevel > 0) {
                foreach ($docs[$currentLevel] as $id => &$data) {
                    if ($out = $this->prepareData($data)) {
                        if (is_array($out)) {
                            $data = $out;
                        }
                    };
                    if (!isset($data['maxLevel'])) {
                        $data['maxLevel'] = 1;
                        $docs[$currentLevel - 1][$data['parent']]['maxLevel'] = 0;
                    }

                    if ($extPrepare) {
                        $data = $extPrepare->init($this, array(
                            'data'      => $data,
                            'nameParam' => 'prepare'
                        ));
                        if ($data === false) {
                            continue;
                        }
                    }

                    if (isset($data['wrap'])) {
                        if (is_array($data['wrap'])) {
                            $data['wrap'] = $this->parseRow($data['wrap']);
                        }
                        $data['wrap'] = $this->parseOuter($data);
                    }
                    $hideSubMenus = $this->getCFGDef('hideSubMenus', 0);
                    $hideSubMenus = !$hideSubMenus || ($hideSubMenus && in_array((int)$data['parent'],
                                $this->activeBranch));
                    if ($hideSubMenus) {
                        $docs[$currentLevel - 1][$data['parent']]['wrap'][] = $data;
                    }
                }
                unset($docs[$currentLevel]);
                $currentLevel--;
            }
            unset($data);
            $out = '';
            $joinMenus = $this->getCFGDef('joinMenus', 0) && !$this->getCFGDef('showParent', 0);
            foreach ($docs[0] as $id => $data) {
                if (isset($data['wrap'])) {
                    if ($joinMenus) {
                        $out .= $this->parseRow($data['wrap']);
                    } else {
                        $data['wrap'] = $this->parseRow($data['wrap']);
                        $out .= $this->parseOuter($data);
                    }
                }
            }
            if ($joinMenus) {
                $out = $this->parseOuter(array('wrap' => $out));
            }
            $this->extCache->save($out, $key);
        }

        return $out;
    }

    /**
     * Добавление виртуальных плейсхолдеров
     * @param $data
     * @return array
     */
    public function prepareData($data)
    {
        /**
         * @var e_DL_Extender $extE
         */
        $extE = $this->getExtender('e', true, true);
        $id = empty($data['id']) ? 0 : $data['id'];
        if (isset($this->docTvs[$id])) {
            $data = array_merge($data, $this->docTvs[$id]);
        }
        if ($id == $this->getHereId()) {
            $data['here'] = 1;
        }
        if (!isset($data['here']) && in_array($id, $this->activeBranch)) {
            $data['active'] = 1;
        }
        if ($this->getCFGDef('hideSubMenus') && isset($data['isfolder']) && $data['isfolder']) {
            $data['state'] = in_array($data['id'], $this->activeBranch) ? 'open' : 'closed';
        }

        if (!isset($data['_display'])) {
            $data['_display'] = $this->display[$data['parent']] - 1;
        }
        if ($data['iteration'] == 1) {
            $data['first'] = 1;
        }
        if ($data['iteration'] == $data['_display']) {
            $data['last'] = 1;
        }
        $titleField = $this->getCFGDef('titleField', 'title');
        $data[$titleField] = isset($data['menutitle']) && !empty($data['menutitle']) ? $data['menutitle'] : $data['pagetitle'];
        $data['level'] = $this->currentLevel;
        $data['url'] = $this->makeUrl($data);
        if ($this->getCFGDef('countChildren', 0)) {
            $data['count'] = isset($this->countChildren[$data['id']]) ? $this->countChildren[$data['id']] : 0;
        }

        if ($out = $extE->init($this, compact('data'))) {
            if (is_array($out)) {
                $data = $out;
            }
        }

        return $data;
    }

    /**
     * Вывод обертки блока меню
     * @param array $data
     * @return string
     */
    public function parseOuter($data = array())
    {
        $tpl = $this->getCFGDef('outerTpl', '@CODE:<ul[+classes+]>[+wrap+]</ul>');
        $classes = '';
        $classNames = $this->getCFGDef('outerClass');
        if ($this->currentLevel >= 1) {
            $tpl = $this->getCFGDef('innerTpl', $tpl);
            $classNames = $this->getCFGDef('innerClass');
        }
        if ($classNames) {
            $classes = " class=\"{$classNames}\"";
        }
        $tpl = isset($data['_renderOuterTpl']) ? $data['_renderOuterTpl'] : $tpl;
        $out = $this->parseChunk(
            $tpl,
            array_merge($data, array('classes' => $classes, 'classNames' => $classNames))
        );

        return $out;
    }

    /**
     * Вывод пункта меню
     * @param array $data
     * @return string
     */
    public function parseRow($data = array())
    {
        $out = '';
        foreach ($data as $iteration => $item) {
            $tpl = isset($item['_renderRowTpl']) ? $item['_renderRowTpl'] : $this->getRowTemplate($item);
            $item = array_merge($item, $this->getClasses($item));
            $out .= $this->parseChunk($tpl, $item);
        }

        return $out;
    }

    /**
     * Формирование ссылки на документ
     * @param array $data
     * @return string
     */
    protected function makeUrl($data = array())
    {
        $out = '';
        if ($this->getCFGDef('makeUrl', 1)) {
            if (isset($data['type']) && $data['type'] == 'reference' && isset($data['content'])) {
                $out = is_numeric($data['content']) ? $this->modx->makeUrl($data['content'], '', '',
                    $this->getCFGDef('urlScheme', '')) : $data['content'];
            } else {
                $out = isset($data['id']) && is_numeric($data['id']) ? $this->modx->makeUrl($data['id'], '', '',
                    $this->getCFGDef('urlScheme', '')) : '';
            }
        }

        return $out;
    }

    /**
     * Вовзращает id текущего документа
     * @return int
     */
    public function getHereId()
    {
        if (!$hereId = (int)$this->getCFGDef('hereId')) {
            $hereId = isset($this->modx->documentIdentifier) ? (int)$this->modx->documentIdentifier : 0;
        }

        return $hereId;
    }

    /**
     * Задание классов пункту меню
     * @param array $data
     * @return array
     */
    protected function getClasses($data = array())
    {
        $classes = isset($data['classes']) ? $data['classes'] : array(
            'rowClass'     => '',
            'firstClass'   => '',
            'lastClass'    => '',
            'levelClass'   => '',
            'webLinkClass' => '',
            'parentClass'  => '',
            'hereClass'    => '',
            'activeClass'  => '',
            'oddClass'     => '',
            'evenClass'    => ''
        );
        if (isset($data['state'])) {
            $classes['stateClass'] = $this->getCFGDef($data['state'] . 'Class', $data['state']);
        }
        if (isset($data['here'])) {
            $classes['hereClass'] = $this->getCFGDef('hereClass', 'current');
        }
        if (isset($data['active'])) {
            $classes['activeClass'] = $this->getCFGDef('activeClass', 'active');
        }
        $classes['rowClass'] = $this->getCFGDef('rowClass');
        if ($data['iteration'] % 2 == 1) {
            $classes['oddClass'] = $this->getCFGDef('oddClass', 'odd');
        } else {
            $classes['evenClass'] = $this->getCFGDef('evenClass', 'even');
        }
        if (isset($data['first'])) {
            $classes['firstClass'] = $this->getCFGDef('firstClass', 'first');
        }
        if (isset($data['last'])) {
            $classes['lastClass'] = $this->getCFGDef('lastClass', 'last');
        }
        if ($levelClass = $this->getCFGDef('levelClass', 'level')) {
            $classes['levelClass'] = $levelClass . $data['level'];
        }
        if (isset($data['type']) && $data['type'] == 'reference') {
            $classes['webLinkClass'] = $this->getCFGDef('webLinkClass');
        }
        if (!empty($data['wrap'])) {
            $classes['parentClass'] = $this->getCFGDef('parentClass');
        }
        $classNames = implode(' ', array_filter(array_values($classes)));
        $classes['classNames'] = $classNames;
        $classes['classes'] = $classNames ? " class=\"{$classNames}\"" : "";

        return $classes;
    }

    /**
     * Вывод пункта меню
     * @param array $data
     * @return string
     */
    protected function getRowTemplate($data = array())
    {
        $tpl = $this->getCFGDef('rowTpl', '@CODE:<li[+classes+]><a href="[+url+]">[+title+]</a></li>');
        if (!empty($data['wrap'])) {
            $tpl = $this->getCFGDef('parentRowTpl',
                '@CODE:<li[+classes+]><a href="[+url+]">[+title+]</a>[+wrap+]</li>');
            if ((isset($data['template']) && !$data['template']) || (isset($data['link_attributes']) && strpos($data['link_attributes'],
                        'category') !== false)
            ) {
                $tpl = $this->getCFGDef('categoryFolderTpl', $tpl);
            } elseif (isset($data['here'])) {
                $tpl = $this->getCFGDef('parentRowHereTpl', $tpl);
            } elseif (isset($data['active'])) {
                $tpl = $this->getCFGDef('parentRowActiveTpl', $tpl);
            }
        } elseif ($data['level'] > 1) {
            $tpl = $this->getCFGDef('innerRowTpl', $tpl);
            if (isset($data['here'])) {
                $tpl = $this->getCFGDef('innerRowHereTpl', $tpl);
            }
        } else {
            if (isset($data['here'])) {
                $tpl = $this->getCFGDef('rowHereTpl', $tpl);
            }
        }

        return $tpl;
    }

    /**
     * @return array
     */
    protected function getMenuData()
    {
        $key = 'raw' . $this->getBranchCacheKey();
        $out = $this->extCache->load($key);
        if ($out === false) {
            $currentLevel = &$this->currentLevel;
            $currentLevel = count($this->levels);
            $docs = $this->levels;

            if (empty($docs)) {
                $out = '[]';
            } else {
                /** @var prepare_DL_Extender_ $extPrepare */
                $extPrepare = $this->getExtender('prepare');

                while ($currentLevel > 0) {
                    foreach ($docs[$currentLevel] as $id => &$data) {
                        if ($out = $this->prepareData($data)) {
                            if (is_array($out)) {
                                $data = $out;
                            }
                        };

                        if (isset($data['here']) || isset($data['active'])) {
                            $docs[$currentLevel - 1][$data['parent']]['active'] = 1;
                        }

                        if ($extPrepare) {
                            $data = $extPrepare->init($this, array(
                                'data'      => $data,
                                'nameParam' => 'prepare'
                            ));
                            if ($data === false) {
                                continue;
                            }
                        }

                        $hideSubMenus = $this->getCFGDef('hideSubMenus', 0);
                        $hideSubMenus = !$hideSubMenus || ($hideSubMenus && in_array((int)$data['parent'],
                                    $this->activeBranch));
                        if ($hideSubMenus) {
                            $docs[$currentLevel - 1][$data['parent']]['children'][] = $data;
                        }
                    }
                    unset($docs[$currentLevel]);
                    $currentLevel--;
                }
                unset($data);
                $out = array();
                $joinMenus = $this->getCFGDef('joinMenus', 0) && !$this->getCFGDef('showParent', 0);
                foreach ($docs[0] as $id => $data) {
                    if (isset($data['children'])) {
                        if ($joinMenus) {
                            $out = array_merge($out, $data['children']);
                        } else {
                            $out[] = $data['children'];
                        }
                    }
                }
                unset($docs);
            }
            $this->extCache->save($out, $key);
        }

        return $out;
    }

    /**
     * @return array
     */
    public function getMenu() {
        $this->getDocs();

        return $this->getMenuData();
    }

    /**
     * @param array $data
     * @param mixed $fields
     * @param array $array
     * @return string
     */
    public function getJSON($data = array(), $fields = array(), $array = array()) {
        return json_encode($this->getMenuData(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Получение информации из конфига с учетом уровня меню
     *
     * @param string $name имя параметра в конфиге
     * @param mixed $def значение по умолчанию, если в конфиге нет искомого параметра
     * @return mixed значение из конфига
     */
    public function getCFGDef($name, $def = null)
    {
        return parent::getCFGDef($name . $this->currentLevel, parent::getCFGDef($name, $def));
    }
}
