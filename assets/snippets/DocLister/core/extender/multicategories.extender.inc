<?php
/**
 * Multicategories  extender for DocLister
 *
 * @category extender
 * @license GNU General Public License (GPL), http://www.gnu.org/copyleft/gpl.html
 * @author Pathologic <m@xim.name>
 */
class multicategories_DL_Extender extends extDocLister
{

    /**
     * @return bool
     */
    protected function run()
    {
        if ($this->DocLister->getCFGDef('idType', 'parents') == 'parents') {
            $ids = $this->DocLister->getIDs();
            if ($ids) {
                $ids = implode(',', $ids);
                $q = $this->DocLister->dbQuery("SELECT `doc` FROM {$this->modx->getFullTableName('site_content_categories')} WHERE `category` IN ({$ids})");
                $docs = $this->modx->getDatabase()->getColumn('doc', $q);
                if ($docs) {
                    $docs = implode(',', $docs);
                    $documents = $this->DocLister->getCFGDef('documents', '');
                    $this->DocLister->config->setConfig(array(
                        "documents" => $documents . ',' . $docs
                    ));
                }
            }
        };

        return true;
    }
}
