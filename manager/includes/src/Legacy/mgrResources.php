<?php namespace EvolutionCMS\Legacy;

class mgrResources {
    /**
     * @var array
     */
    public $types = array();
    /**
     * @var array
     */
    public $items = array();
    /**
     * @var array
     */
    public $categories = array();
    /**
     * @var array
     */
    public $itemsPerCategory = array();

    /**
     * mgrResources constructor.
     */
    public function __construct() {
        $this->setTypes();
        $this->queryItemsFromDB();
        $this->prepareCategoryArrays();
    }

    /**
     * @return void
     */
    public function setTypes() {
        global $_lang;
        $this->types['site_templates']    = array(
            'title'=>$_lang["manage_templates"],
            'actions'=>array( 'edit'=>array(16,'edit_template'), 'duplicate'=>array(96,'new_template'), 'remove'=>array(21,'delete_template') ),
            'permissions'=>array('new_template','edit_template'),
            'name'=>'templatename'
        );
        $this->types['site_tmplvars']     = array(
            'title'=>$_lang["tmplvars"],
            'actions'=>array('edit'=>array(301,'edit_template'), 'duplicate'=>array(304,'edit_template'), 'remove'=>array(303,'edit_template')),
            'permissions'=>array('new_template','edit_template'),
        );
        $this->types['site_htmlsnippets'] = array(
            'title'=>$_lang["manage_htmlsnippets"],
            'actions'=>array('edit'=>array(78,'edit_chunk'), 'duplicate'=>array(97,'new_chunk'), 'remove'=>array(80,'delete_chunk')),
            'permissions'=>array('new_chunk','edit_chunk'),
        );
        $this->types['site_snippets']     = array(
            'title'=>$_lang["manage_snippets"],
            'actions'=>array('edit'=>array(22,'edit_snippet'), 'duplicate'=>array(98,'new_snippet'), 'remove'=>array(25,'delete_snippet')),
            'permissions'=>array('new_snippet','edit_snippet'),
        );
        $this->types['site_plugins']      = array(
            'title'=>$_lang["manage_plugins"],
            'actions'=>array('edit'=>array(102,'edit_plugin'), 'duplicate'=>array(105,'new_plugin'), 'remove'=>array(104,'delete_plugin')),
            'permissions'=>array('new_plugin','edit_plugin'),
        );
        $this->types['site_modules']      = array(
            'title'=>$_lang["manage_modules"],
            'actions'=>array('edit'=>array(108,'edit_module'), 'duplicate'=>array(111,'new_module'), 'remove'=>array(110,'delete_module')),
            'permissions'=>array('new_module','edit_module'),
        );
    }

    /**
     * @return void
     */
    public function queryItemsFromDB() {
        foreach($this->types as $resourceTable=>$type) {
            if($this->hasAnyPermissions($type['permissions'])) {
                $nameField = isset($type['name']) ? $type['name'] : 'name';
                $this->items[$resourceTable] = $this->queryResources($resourceTable, $nameField);
            }
        }
    }

    /**
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermissions($permissions) {
        $modx = evolutionCMS();

        foreach($permissions as $p)
            if($modx->hasPermission($p)) return true;

        return false;
    }

    /**
     * @param string $resourceTable
     * @param string $nameField
     * @return array|bool
     */
    public function queryResources($resourceTable, $nameField = 'name') {
        $modx = evolutionCMS(); global $_lang;

        $allowed = array(
            'site_htmlsnippets',
            'site_snippets',
            'site_plugins',
            'site_modules'
        );
        $pluginsql = !empty($resourceTable) && in_array($resourceTable, $allowed) ? $resourceTable . '.disabled, ' : '';

        $tvsql  = '';
        $tvjoin = '';
        if ($resourceTable === 'site_tmplvars') {
            $tvsql    = 'site_tmplvars.caption, ';
            $tvjoin   = sprintf('LEFT JOIN %s AS stt ON site_tmplvars.id=stt.tmplvarid GROUP BY site_tmplvars.id,reltpl', $modx->getDatabase()->getFullTableName('site_tmplvar_templates'));
            $sttfield = 'IF(stt.templateid,1,0) AS reltpl,';
        }
        else $sttfield = '';

        $selectableTemplates = $resourceTable === 'site_templates' ? "{$resourceTable}.selectable, " : "";

        $rs = $modx->getDatabase()->select(
            "{$sttfield} {$pluginsql} {$tvsql} {$resourceTable}.{$nameField} as name, {$resourceTable}.id, {$resourceTable}.description, {$resourceTable}.locked, {$selectableTemplates}IF(isnull(categories.category),'{$_lang['no_category']}',categories.category) as category, categories.id as catid",
            $modx->getDatabase()->getFullTableName($resourceTable) . " AS {$resourceTable}
	            LEFT JOIN " . $modx->getDatabase()->getFullTableName('categories') . " AS categories ON {$resourceTable}.category = categories.id {$tvjoin}",
            "",
            "category,name"
        );
        $limit = $modx->getDatabase()->getRecordCount($rs);

        if($limit < 1) return false;

        $result = array();
        while ($row = $modx->getDatabase()->getRow($rs)) {
            $result[] = $row;
        }
        return $result;
    }

    /**
     * @return void
     */
    public function prepareCategoryArrays() {
        foreach($this->items as $type=>$items) {
            foreach((array)$items as $item) {
                $catid = $item['catid'] ? $item['catid'] : 0;
                $this->categories[$catid] = $item['category'];

                $item['type'] = $type;
                $this->itemsPerCategory[$catid][] = $item;
            }
        }

        // Sort categories by name
        natcasesort($this->categories);

        // Now sort by name
        foreach($this->itemsPerCategory as $catid=>$items) {
            usort($this->itemsPerCategory[$catid], function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });
        }
    }
}
