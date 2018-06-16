<?php
/**
 * Summary extender for DocLister
 *
 * @category extender
 * @license GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @author Agel_Nash <Agel_Nash@xaker.ru>
 */
class summary_DL_Extender extends extDocLister
{

    /**
     * @return mixed
     */
    protected function run()
    {
        if (!empty($this->_cfg['action']) && !empty($this->_cfg['content'])) {
            include_once(MODX_BASE_PATH . 'assets/lib/class.summary.php');
            $summary = new SummaryText($this->_cfg['content'], $this->_cfg['action'],
                $this->getCFGDef('breakSummary', null));
            $summary->setCut($this->getCFGDef('cutSummary', '<cut/>'));
            $this->_cfg['content'] = $summary->run($this->getCFGDef('dotSummary', 0));
        }

        return $this->_cfg['content'];
    }
}