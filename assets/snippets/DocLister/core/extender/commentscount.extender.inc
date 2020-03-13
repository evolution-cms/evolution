<?php
/**
 * Jot extender for DocLister
 *
 * @category extender
 * @license GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @author Pathologic <maxx@np.by>
 */
class commentscount_DL_Extender extends extDocLister
{
    /**
     * @return bool
     */
    protected function run()
    {
        $context = $this->DocLister->getCFGDef('context', 'site_content');
        $context = $this->modx->db->escape($context);
        $countDeleted = $this->DocLister->getCFGDef('countDeletedComments', 0);
        $countUnpublished = $this->DocLister->getCFGDef('countUnpublishedComments', 0);
        if (!$countDeleted && !$countUnpublished) {
            $join = "LEFT JOIN {$this->DocLister->getTable('comments_stat')} `s` ON  `s`.`context`='{$context}' AND `s`.`thread` = {$this->DocLister->getPK()}";
        } else {
            $where = array();
            if (!$countDeleted) {
                $where[] = '`deleted`=0';
            }
            if (!$countUnpublished) {
                $where[] = '`published`=1';
            }
            $where = implode(' AND ', $where);
            $join = "LEFT JOIN (SELECT `thread`, COUNT(`id`) AS `comments_count` FROM {$this->DocLister->getTable('comments')} WHERE `context`='{$context}' {$where} GROUP BY `thread`) `s` ON `s`.`thread` = {$this->DocLister->getPK()}";
        }
        $this->DocLister->setFiltersJoin($join);
        $fields = $this->DocLister->getCFGDef('selectFields', 'c.*');
        $this->DocLister->config->setConfig(array(
            "selectFields" => $fields . ",IFNULL(`s`.`comments_count`,0) AS `comments_count`"
        ));

        return true;
    }
}
