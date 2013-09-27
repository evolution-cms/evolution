<?php
/* -----------------------------------------------------------------------------
* Snippet: AjaxSearch
* ------------------------------------------------------------------------------
* @package  AjaxSearchRequest
*
* @author       Coroico - www.evo.wangba.fr
* @version      1.9.3
* @date         26/09/2012
*
* Purpose:
*    The AjaxSearchRequest class contains all functions and data used to manage the search SQL Request
*
*/

define('GROUP_CONCAT_LENGTH', 4096); // maximum length of the group concat

class AjaxSearchRequest {

    var $cfg;
    var $withContent;

    var $scMain = array();
    var $scJoined = array();
    var $scTvs = array();
    var $scCateg = array();
    var $scTags = array();


    var $asSelect;

    var $asUtil;
    var $dbg;
    var $pgCharset;

    function AjaxSearchRequest(&$asUtil, $pgCharset) {
        $this->asUtil =& $asUtil;
        $this->dbg = $asUtil->dbg;
        $this->pgCharset = $pgCharset;
        if ($this->dbg) $this->asUtil->dbgRecord($this->pgCharset, "pgCharset");
    }
    /*
    * doSearch : Do the search
    */
    function doSearch($searchString, $advSearch, $cfg, $bsf, $fClause) {
        global $modx;
        $searchString = $modx->db->escape($searchString);
        $this->cfg = $cfg;
        $records = NULL;
        $results = array();
        $this->asSelect = '';
        if ($this->_initSearchContext($bsf)) {
            $fields = $this->_getFields();
            $from = $this->_getFrom($searchString, $advSearch);
            $where = $this->_getWhere();
            $groupBy = $this->_getGroupBy();
            $having = $this->_getHaving($searchString, $advSearch, $fClause);
            $orderBy = $this->_getOrderBy();
            $this->asSelect = "SELECT $fields FROM $from WHERE $where";
            $this->asSelect.= " GROUP BY $groupBy HAVING $having ORDER BY $orderBy";
            if (isset($this->scJoined)) {
                $modx->db->query("SET group_concat_max_len = " . GROUP_CONCAT_LENGTH . ";");
            }
            if ($this->dbg) $this->asUtil->dbgRecord($this->_printSelect($this->asSelect), "Select");
            $records = $modx->db->query($this->asSelect);
            if ($this->dbg) $this->asUtil->dbgRecord("End of select");
            $results = $this->_appendTvs($records);
        }
        $modx->db->freeResult($records);
        return $results;
    }
    /*
    * Check and initialize the description of the tables & content fields
    */
    function _initSearchContext($bsf) {
        global $modx;

        unset($this->scMain);
        unset($this->scJoined);
        unset($this->scTvs);
        unset($this->scCateg);
        unset($this->scTags);
        $mainDefined = false;
        $this->withContent = false;
        $part = explode('|', $this->cfg['whereSearch']);
        foreach ($part as $p) {
            list($ptable,$pfields) = explode(':', $p);
            switch ($ptable) {


            case 'content':
                $this->scMain = array(
                    'tb_name' => $this->_getShortTableName('site_content'),
                    'tb_alias' => 'sc',
                    'id' => 'id',
                    'searchable' => array('pagetitle', 'longtitle', 'description', 'alias', 'introtext', 'menutitle', 'content'),
                    'displayed' => array('pagetitle', 'longtitle', 'description', 'alias', 'introtext', 'template', 'menutitle', 'content'),
                    'date' => array('publishedon'),
                    'filters' => array(),
                    'jfilters' => array(),
                    'append' => array()
                );
                if ($pfields != '') {
                    unset($this->scMain['searchable']);
                    if ($pfields == 'null' or $pfields == 'NULL') $this->scMain['searchable'] = array();
                    else $this->scMain['searchable'] = explode(',', $pfields);
                }
                $this->withContent = true;
                $mainDefined = true;

                if ($this->_validListIDs($bsf['listIds'])) {
                    $this->scMain['filters'][] = array('field' => 'id', 'oper' => $bsf['oper'], 'value' => $bsf['listIds']);
                }

                $this->scMain['filters'][] = array('field' => 'published', 'oper' => '=', 'value' => '1');

                $this->scMain['filters'][] = array('field' => 'searchable', 'oper' => '=', 'value' => '1');

                $this->scMain['filters'][] = array('field' => 'deleted', 'oper' => '=', 'value' => '0');

                if (($this->cfg['hideMenu'] == 0) || ($this->cfg['hideMenu'] == 1)) $this->scMain['filters'][] = array('field' => 'hidemenu', 'oper' => '=', 'value' => $this->cfg['hideMenu']);

                if ($this->cfg['hideLink'] == 1) $this->scMain['filters'][] = array('field' => 'type', 'oper' => '=', 'value' => '\'document\'');

                if ($this->_validListIDs($this->cfg['docgrp'])) {
                    $this->scMain['jfilters'][] = array(
                        'tb_name' => $this->_getShortTableName('document_groups'),
                        'tb_alias' => 'dg',
                        'main' => 'id',
                        'join' => 'document',
                        'field' => 'document_group',
                        'oper' => 'in',
                        'value' => $this->cfg['docgrp'],
                        'or' => array('field' => 'privateweb', 'oper' => '=', 'value' => '0')
                    );
                } else {

                    $this->scMain['filters'][] = array('field' => 'privateweb', 'oper' => '=', 'value' => '0');
                }
                break;



            case 'tv':
                $this->scJoined[] = array(
                    'tb_name' => $this->_getShortTableName('site_tmplvar_contentvalues'),
                    'tb_alias' => 'tv',
                    'id' => 'id',
                    'main' => 'id',
                    'join' => 'contentid',
                    'searchable' => array('value'),
                    'displayed' => array('value'),
                    'concat_separator' => ',',
                    'filters' => array(),
                    'jfilters' => array()
                );
                $j = count($this->scJoined) - 1;
                if ($pfields != '') {
                    unset($this->scJoined[$j]['searchable']);
                    if ($pfields == 'null' or $pfields == 'NULL') $this->scJoined[$j]['searchable'] = array();
                    else $this->scJoined[$j]['searchable'] = explode(',', $pfields);
                }
                break;


            case 'jot':
                $this->scJoined[] = array(
                    'tb_name' => $this->_getShortTableName('jot_content'),
                    'tb_alias' => 'jot',
                    'id' => 'id',
                    'main' => 'id',
                    'join' => 'uparent',
                    'searchable' => array('content'),
                    'displayed' => array('content'),
                    'concat_separator' => ', ',
                    'filters' => array()
                );
                $j = count($this->scJoined) - 1;
                if ($pfields != '') {
                    unset($this->scJoined[$j]['searchable']);
                    if ($pfields == 'null' or $pfields == 'NULL') $this->scJoined[$j]['searchable'] = array();
                    else $this->scJoined[$j]['searchable'] = explode(',', $pfields);
                }

                $j = count($this->scJoined) - 1;
                $this->scJoined[$j]['filters'][] = array('field' => 'published', 'oper' => '=', 'value' => '1');
                break;


            case 'maxigallery':
                $this->scJoined[] = array(
                    'tb_name' => $this->_getShortTableName('maxigallery'),
                    'tb_alias' => 'gal',
                    'id' => 'id',
                    'main' => 'id',
                    'join' => 'gal_id',
                    'searchable' => array('title', 'descr'),
                    'displayed' => array('title', 'descr', 'filename'),
                    'concat_separator' => ', ',
                    'filters' => array()
                );
                $j = count($this->scJoined) - 1;
                if ($pfields != '') {
                    unset($this->scJoined[$j]['searchable']);
                    if ($pfields == 'null' or $pfields == 'NULL') $this->scJoined[$j]['searchable'] = array();
                    else $this->scJoined[$j]['searchable'] = explode(',', $pfields);
                }

                $j = count($this->scJoined) - 1;
                $this->scJoined[$j]['filters'][] = array('field' => 'hide', 'oper' => '=', 'value' => '0');
                break;


            default:

                if (function_exists($ptable)) {
                    $ptable($main, $joined, $bsf, $pfields);
                    if ($main) {
                        $this->scMain = $main;
                        $mainDefined = true;
                    }
                    if ($joined) {

                        if (count($joined['displayed']) > 0) $this->scJoined[] = $joined;
                    }
                }
                break;
            }
        }


        if (isset($this->cfg['withTvs']) && ($this->cfg['withTvs'])) {
            $this->scTvs = array(
                'names' => array(),
                'tvs' => array(),
            );
            $this->scTvs['names'] = $this->_getTvsArray($this->cfg['withTvs']);
            if ($this->dbg) $this->asUtil->dbgRecord($this->scTvs['names'], "initSearchContext - withTvs");
            $this->scTvs['tvs'] = $this->_getTvSubSelect($this->scTvs['names'],'','tv','simple');
        }


        $categ_array = explode(':', $this->cfg['category']);
        if (isset($categ_array[0]) && ($categ_array[0])) {
            if (!isset($categ_array[1])) {
                $ctvs_array = $this->_getTvsArray($categ_array[0]);
                if ($this->dbg) $this->asUtil->dbgRecord($ctvs_array, "initSearchContext - category");
                $stv = $this->_getTvSubSelect($ctvs_array,'category','cg','simple');
                $this->scCateg = $stv[0];
            }
            else {
                $fCateg = $categ_array[1];
                $this->scCateg = $fCateg($categ_array[0]);  // fCateg returns an array with 'tb_alias,id,value,sql fields
            }
        }


        $tags_array = explode(':', $this->cfg['tags']);
        if (isset($tags_array[0]) && ($tags_array[0])) {
            if (!isset($tags_array[1])) {
                $ttvs_array = $this->_getTvsArray($tags_array[0]);
                if ($this->dbg) $this->asUtil->dbgRecord($ttvs_array, "initSearchContext - tags");
                $stv = $this->_getTvSubSelect($ttvs_array,'tags','tg','concat');
                $this->scTags = $stv[0];
            }
            else {
                $fTags = $tags_array[1];
                $this->scTags = $fTags($tags_array[0]); // fTags returns an array with 'tb_alias,id,value,sql fields
            }
        }

        if ($this->dbgRes) {
            $this->asUtil->dbgRecord($this->scMain, "Search context main " . $this->scMain['tb_name']);
            if (isset($this->scJoined)) foreach ($this->scJoined as $joined) $this->asUtil->dbgRecord($joined, "Search context joined " . $joined['tb_name']);
        }

        return $mainDefined;
    }
    /*
    * Get the fields clause of the AS query
    */
    function _getFields() {
        $fields = array();
        $mpref = $this->scMain['tb_alias'];

        $fields[] = $mpref . '.' . $this->scMain['id'];

        if (isset($this->scMain['displayed'])) foreach ($this->scMain['displayed'] as $displayed) $fields[] = $mpref . '.' . $displayed;

        if (isset($this->scMain['date'])) foreach ($this->scMain['date'] as $date) $fields[] = $mpref . '.' . $date;

        if (isset($this->scJoined)) foreach ($this->scJoined as $joined) {
            $jpref = $joined['tb_alias'];
            $f = 'GROUP_CONCAT( DISTINCT CAST(n' . $jpref . '.' . $joined['id'] . ' AS CHAR)';
            $f.= ' SEPARATOR "," ) AS ' . $jpref . '_' . $joined['id'];
            $fields[] = $f;
        }

        if (isset($this->scJoined)) foreach ($this->scJoined as $joined) {
            $jpref = $joined['tb_alias'];
            $nbd = count($joined['displayed']);
            for ($d = 0;$d < $nbd;$d++) {
                $f = 'GROUP_CONCAT( DISTINCT n' . $jpref . '.' . $joined['displayed'][$d];
                $f.= ' SEPARATOR "' . $joined['concat_separator'] . '" ) AS ' . $jpref . '_' . $joined['displayed'][$d];
                $fields[] = $f;
            }
        }

        if (isset($this->scTvs['tvs'])) foreach($this->scTvs['tvs'] as $scTv) {
            $f = $scTv['tb_alias'] . '.' . $scTv['displayed'] . ' AS `' . $scTv['name'] . '`';
            $fields[] = $f;
        }

        if (isset($this->scCateg)) {
            $f = $this->scCateg['tb_alias'] . '.' . $this->scCateg['displayed'] . ' AS category';
            $fields[] = $f;
        }


        if (isset($this->scTags)) {
            $f = 'REPLACE( GROUP_CONCAT( DISTINCT ' . $this->scTags['tb_alias'] . '.' . $this->scTags['displayed'];
            $f.= ' SEPARATOR "," ), "||", ",") AS tags';
            $fields[] = $f;
        }

        if (count($fields) > 0) $fieldsClause = implode(', ', $fields);
        else $fieldsClause = '*';
        return $fieldsClause;
    }
    /*
    * Get the "FROM" clause of the AS query
    */
    function _getFrom($searchString, $advSearch) {

        $from[] = $this->scMain['tb_name'] . ' ' . $this->scMain['tb_alias'];
        //left join with jfilter tables
        if (isset($this->scMain['jfilters'])) foreach ($this->scMain['jfilters'] as $filter) {
            $f = 'LEFT JOIN ' . $filter['tb_name'] . ' ' . $filter['tb_alias'];
            $f.= ' ON ' . $this->scMain['tb_alias'] . '.' . $filter['main'] . ' = ' . $filter['tb_alias'] . '.' . $filter['join'];
            $from[] = $f;
        }
        //left join with joined table
        if (isset($this->scJoined)) foreach ($this->scJoined as $joined) {
            $jpref = 'n' . $joined['tb_alias'];
            $f = 'LEFT JOIN( ' . $this->_getSubSelect($joined, $searchString, $advSearch) . ' )' . ' AS ' . $jpref . ' ON ';
            $f.= $this->scMain['tb_alias'] . '.' . $joined['main'] . ' = ' . $jpref . '.' . $joined['join'];
            $from[] = $f;
        }
        //left join with Tv defined as table field
        if (isset($this->scTvs['tvs'])) foreach($this->scTvs['tvs'] as $scTv) {
            $f = 'LEFT JOIN( ' . $scTv['sql'] . ' ) AS ' . $scTv['tb_alias'];
            $f.= ' ON ' . $this->scMain['tb_alias'] . '.' . $scTv['main'] . ' = ';
            $f.= $scTv['tb_alias'] . '.' . $scTv['join'];
            $from[] = $f;
        }
        //left join with categories
        if (isset($this->scCateg)) {
            $f = 'LEFT JOIN( ' . $this->scCateg['sql'] . ' ) AS ' . $this->scCateg['tb_alias'];
            $f.= ' ON ' . $this->scMain['tb_alias'] . '.' . $this->scCateg['main'] . ' = ';
            $f.= $this->scCateg['tb_alias'] . '.' . $this->scCateg['join'];
            $from[] = $f;
        }
        //left join with tags
        if (isset($this->scTags)) {
            $f = 'LEFT JOIN( ' . $this->scTags['sql'] . ' ) AS ' . $this->scTags['tb_alias'];
            $f.= ' ON ' . $this->scMain['tb_alias'] . '.' . $this->scTags['main'] . ' = ';
            $f.= $this->scTags['tb_alias'] . '.' . $this->scTags['join'];
            $from[] = $f;
        }
        $fromClause = implode(' ', $from);
        return $fromClause;
    }
    /*
    * Get the "WHERE" clause of the AS query
    */
    function _getWhere() {

        if (isset($this->scMain['filters'])) foreach ($this->scMain['filters'] as $filter) $where[] = $this->_getFilter($this->scMain['tb_alias'], $filter);

        if (isset($this->scMain['jfilters'])) foreach ($this->scMain['jfilters'] as $filter) $where[] = $this->_getFilter($filter['tb_alias'], $filter);
        if (count($where) > 0) $whereClause = '(' . implode(' AND ', $where) . ')';
        else $whereClause = '1';
        return $whereClause;
    }
    /*
    * Get the "GROUP BY" clause of the AS query
    */
    function _getGroupBy() {
        $groupByClause = $this->scMain['tb_alias'] . '.' . $this->scMain['id'];
        return $groupByClause;
    }
    /*
    * Get the "HAVING" clause of the AS query
    */
    function _getHaving($searchString, $advSearch, $fClause) {
        $havingClause_array = array();
        if ($searchString != '') {
            $like = $this->_getWhereForm($advSearch);
            $whereOper = $this->_getWhereOper($advSearch);
            $whereStringOper = $this->_getWhereStringOper($advSearch);
            if (isset($this->scMain['searchable'])) foreach ($this->scMain['searchable'] as $searchable) $hvg[] = '(' . $this->scMain['tb_alias'] . '.' . $searchable . $like . ')';

            if ($advSearch != NOWORDS) {
                if (isset($this->scJoined)) foreach ($this->scJoined as $joined) {
                    $jpref = $joined['tb_alias'];
                    if (isset($joined['searchable'])) foreach ($joined['searchable'] as $searchable) $hvg[] = '(' . $jpref . '_' . $searchable . $like . ')';
                }
                if (isset($this->scTvs['tvs'])) foreach ($this->scTvs['tvs'] as $scTv) {
                    $jpref = $scTv['tb_alias'];
                    $hvg[] = '(`' . $scTv['name'] . '`' . $like . ')';
                }
            } else {

                if (isset($this->scJoined)) foreach ($this->scJoined as $joined) {
                    $jpref = $joined['tb_alias'];
                    if (isset($joined['searchable'])) foreach ($joined['searchable'] as $searchable) {
                        $hvg[] = '((' . $jpref . '_' . $searchable . $like . ') OR (' . $jpref . '_' . $searchable . ' IS NULL))';
                    }
                }
                if (isset($this->scTvs['tvs'])) foreach ($this->scTvs['tvs'] as $scTv) {
                    $jpref = $scTv['tb_alias'];
                    $hvg[] = '((`' . $scTv['name'] . '`' . $like . ') OR (`' . $scTv['name'] . '` IS NULL))';
                }
            }
            if (count($hvg) > 0) {
                $havingSubClause = '(' . implode($whereOper, $hvg) . ')';

                $search = $this->_getSearchTerms($searchString, $advSearch);
                foreach ($search as $searchTerm) {
                    $_having = array();
                    $_having[0] = preg_replace('/word/', $searchTerm, $havingSubClause);
                    $namedSearchTerm = $this->_getNamedSearchTerm($searchTerm);
                    if ($namedSearchTerm != $searchTerm) {
                        $_having[1] = preg_replace('/word/', $namedSearchTerm, $havingSubClause);
                        $having[] = '(' . implode(' OR ', $_having) . ')';
                    }
                    else $having[] = $_having[0];
                }
                $havingClause = '(' . implode($whereStringOper, $having) . ')';
            }
            $havingClause_array[] = $havingClause;
        }

        if ($fClause) {
            $havingClause_array[] = '(' . $fClause . ')';;
        }
        $havingClause = implode(' AND ',$havingClause_array);
        $havingClause = ($havingClause) ? $havingClause : '1';
        return $havingClause;
    }
    /*
    * Get the "GROUP BY" clause of the AS query
    */
    function _getOrderBy() {
        if (isset($this->scCateg)) $orderFields[] = 'category ASC';
        if ($this->cfg['order']) {
            $order = array_map('trim',explode(',', $this->cfg['order']));
            foreach ($order as $ord) {
                $ordElt = explode(' ',$ord);
                $ordby = '`' . $ordElt[0] . '`';
                if (isset($ordElt[1]) && ($ordElt[1] == 'ASC' || $ordElt[1] == 'DESC')) $ordby .= ' ' . $ordElt[1];
                $orderBy[] = $ordby;
            }
			$orderFields[] = implode(',', $orderBy);
        }
        if (count($orderFields) > 0) $orderByClause = implode(', ', $orderFields);
        else $orderByClause = '1';
        return $orderByClause;
    }
    /*
    * Get select statement for a joined table
    */
    function _getSubSelect($joined, $searchString, $advSearch) {
        $fields = array();
        $from = array();
        $where = array();
        $whl = array();

        $fields[] = $joined['tb_alias'] . '.' . $joined['id'];

        if (isset($joined['displayed'])) foreach ($joined['displayed'] as $displayed) $fields[] = $joined['tb_alias'] . '.' . $displayed;

        if ($joined['join'] != $joined['id']) $fields[] = $joined['tb_alias'] . '.' . $joined['join'];
        $fieldsClause = implode(', ', $fields);

        $from[] = $joined['tb_name'] . ' ' . $joined['tb_alias'];

        if (isset($joined['jfilters'])) foreach ($joined['jfilters'] as $jfilter) {
            $f = 'INNER JOIN ' . $jfilter['tb_name'] . ' ' . $jfilter['tb_alias'];
            $f.= ' ON ' . $joined['tb_alias'] . '.' . $jfilter['main'] . ' = ' . $jfilter['tb_alias'] . '.' . $jfilter['join'];
            $from[] = $f;
        }
        $fromClause = implode(' ', $from);

        if (isset($joined['filters'])) foreach ($joined['filters'] as $filter) {
            $where[] = $this->_getFilter($joined['tb_alias'], $filter);
        }
        if (isset($joined['jfilters'])) foreach ($joined['jfilters'] as $jfilter) {
            $where[] = $this->_getFilter($jfilter['tb_alias'], $jfilter);
        }
        if (count($where) > 0) {
            for ($i = 0;$i < count($where);$i++) $where[$i] = '(' . $where[$i] . ')';
            $whl[] = implode(' AND ', $where);
        }

        if (($joined['tb_alias'] != 'tv')) {
            if ($searchString) {
                $stw = $this->_getSearchTermsWhere($joined,$searchString,$advSearch);
                if (!empty($stw)) $whl[] = '(' . $stw . ')';
            }
            if (count($whl)) {
                $whereClause = '(' . implode(' AND ',$whl). ')';
                $subSelect = 'SELECT DISTINCT ' . $fieldsClause . ' FROM ' . $fromClause . ' WHERE ' . $whereClause;
            }
            else $subSelect = 'SELECT DISTINCT ' . $fieldsClause . ' FROM ' . $fromClause;
        }
        else {
            $subSelect = 'SELECT DISTINCT ' . $fieldsClause . ' FROM ' . $fromClause;
        }
        return $subSelect;
    }
    function _getFilter($alias, $filter) {
        $where = $this->_getSubFilter($alias, $filter);
        if (isset($filter['or'])) {
            $or = $filter['or'];
            if (isset($or['tb_alias'])) $alias = $or['tb_alias'];
            else $alias = $this->scMain['tb_alias'];
            $where = '(' . $where . ' OR ' . $this->_getFilter($alias, $or) . ')';
        }
        return $where;
    }
    function _getSubFilter($alias, $filter) {
        $where = '';

        if (($filter['oper'] == '=') || ($filter['oper'] == 'EQUAL')) {
            $where.= $alias . '.' . $filter['field'] . '=' . $filter['value'];
        }

        else if (($filter['oper'] == '>') || ($filter['oper'] == 'GREAT THAN')) {
            $where.= $alias . '.' . $filter['field'] . '>' . $filter['value'];
        }

        else if (($filter['oper'] == '<') || ($filter['oper'] == 'LESS THAN')) {
            $where.= $alias . '.' . $filter['field'] . '<' . $filter['value'];
        }

        else if (($filter['oper'] == 'in') || ($filter['oper'] == 'IN')) {
            $where.= $alias . '.' . $filter['field'] . ' IN (' . $filter['value'] . ')';
        }

        else if (($filter['oper'] == 'not in') || ($filter['oper'] == 'NOT IN')) {
            $where.= $alias . '.' . $filter['field'] . ' NOT IN (' . $filter['value'] . ')';
        }
        if ($where != '') $where = '(' . $where . ')';
        return $where;
    }
    function _getSearchTermsWhere($joined,$searchString,$advSearch){
		$whereClause = '';
        if (!empty($joined['searchable'])) {
			$like = $this->_getWhereForm($advSearch);
			$whereOper = $this->_getWhereOper($advSearch);
			$type = ($advSearch == 'allwords') ? 'oneword' : $advSearch;
			$whereStringOper = $this->_getWhereStringOper($type);

			foreach($joined['searchable'] as $searchable) $whsc[] = '(' . $joined['tb_alias'] . '.' . $searchable . $like .')';
			if (count($whsc)) {
				$whereSubClause = implode($whereOper,$whsc);

				$search = array();
				if ($advSearch == 'exactphrase') $search[] = $searchString;
				else $search = explode(' ',$searchString);

				foreach($search as $searchTerm) $where[]=   preg_replace('/word/', preg_quote($searchTerm), $whereSubClause);

				$whereClause = implode($whereStringOper,$where);
			}
		}
        return $whereClause;
    }
    function _getWhereForm($advSearch) {
        $whereForm = array('like' => " LIKE '%word%'", 'notlike' => " NOT LIKE '%word%'", 'regexp' => " REGEXP '[[:<:]]word[[:>:]]'");
        if ($advSearch == NOWORDS) return $whereForm['notlike'];
        else if ($advSearch == EXACTPHRASE) return $whereForm['regexp'];
        else return $whereForm['like'];
    }
    function _getWhereOper($advSearch) {
        $whereOper = array('or' => " OR ", 'and' => " AND ");
        if ($advSearch == NOWORDS) return $whereOper['and'];
        else return $whereOper['or'];
    }
    function _getWhereStringOper($advSearch) {
        $whereStringOper = array('or' => " OR ", 'and' => " AND ");
        if ($advSearch == NOWORDS || $advSearch == ALLWORDS) return $whereStringOper['and'];
        else return $whereStringOper['or'];
    }
    function _getSearchTerms($searchString, $advSearch) {
        $search = array();
        if ($advSearch == EXACTPHRASE) $search[] = $searchString;
        else $search = explode(' ', $searchString);
        return $search;
    }
    /*
    * Get named html entities version of the search term.
    */
    function _getNamedSearchTerm($searchString) {
        $named = $this->_htmlentities($searchString, ENT_COMPAT, $this->pgCharset, false);
        return $named;
    }
    /*
    * Get the subselect query related to the Tvs
    */
    function _getTvSubSelect($tvs_array,$name,$abrev,$mode) {
        global $modx;
        $scTvs = array();

        if ($mode == 'simple') {
            $i = 1;
            foreach($tvs_array as $tv) {
                $selectid = "SELECT DISTINCT id, name FROM " . $this->_getShortTableName('site_tmplvars');
                $selectid .= " WHERE name = '" . $tv . "'";
                $rs = $modx->db->query($selectid);
                $row = $modx->db->getRow($rs);

                $alias = $abrev . $i;
                $nm = ($name) ? $name : $tv;
                $subselect = "SELECT DISTINCT ".$alias.".contentid , ".$alias.".value ";
                $subselect.= "FROM " . $this->_getShortTableName('site_tmplvar_contentvalues') . " ".$alias." ";
                $subselect.= "WHERE ".$alias.".tmplvarid = '" . $row['id'] . "'";

                $scTvs[] = array(
                    'tb_alias' => 'n'.$alias,
                    'main' => 'id',
                    'join' => 'contentid',
                    'displayed' => 'value',
                    'searchable' => 'value',
                    'sql' => $subselect,
                    'name' => $nm
                );
                $i++;
            }
        }
        else { // mode = concat
            $lstTvs = "'" . implode("','",$tvs_array) . "'";
            $selectid = "SELECT GROUP_CONCAT( DISTINCT CAST(id AS CHAR) SEPARATOR \",\" ) AS " . $abrev . "_id";
            $selectid .= " FROM " . $this->_getShortTableName('site_tmplvars');
            $selectid .= " WHERE name in (" . $lstTvs. ")";
            $rs = $modx->db->query($selectid);
            $row = $modx->db->getRow($rs);

            $subselect = "SELECT DISTINCT " . $abrev . ".contentid , " . $abrev . ".value ";
            $subselect.= "FROM " . $this->_getShortTableName('site_tmplvar_contentvalues') . " " . $abrev . " ";
            $subselect.= "WHERE " . $abrev . ".tmplvarid in (" . $row[$abrev.'_id'] . ")";

            $scTvs[] = array(
                'tb_alias' => 'n'.$abrev,
                'main' => 'id',
                'join' => 'contentid',
                'displayed' => 'value',
                'searchable' => 'value',
                'sql' => $subselect,
                'name' => $name
            );
        }
        return $scTvs;
    }
    /*
    *  Return the array of tv
    */
    function _getTvsArray($ltv) {
        $tvs_array = array();

        $tvs_array = explode(':',$ltv);
        $tvSign = $tvs_array[0];
        if (($tvSign != '+') && ($tvSign != '-')) {
            $tvList = $tvSign;
            $tvSign = '+';
        }
        else {
            if (isset($tvs_array[1])) $tvList = $tvs_array[1];
            else $tvList = '';
        }

        $tvs_array = array();
        if ($tvSign == '+') {
            if ($tvList) $tvs_array = explode(',',$tvList);
            else $tvs_array = $this->_getSiteTvs();
        }
        else {
            $allTvs_array = $this->_getSiteTvs();
            $minusTvs_array = explode(',',$tvList);
            $tvs_array = array_diff($allTvs_array, $minusTvs_array);
        }
        return $tvs_array;
    }

    /*
    *  Return the list of TVs of a site
    */
    function _getSiteTvs() {
        global $modx;
        $tvs_array = array();
        $tblName = $modx->getFullTableName('site_tmplvars');
        $sql = 'SELECT GROUP_CONCAT( DISTINCT name SEPARATOR "," ) AS list FROM ' . $tblName . ' WHERE type=\'text\'';
        $rs = $modx->db->query($sql);
        if ($rs) $row = $modx->db->getRow($rs);
        if ($row) $tvs_array = explode(',',$row['list']);
        return $tvs_array;
    }
    /*
    *  Check the validity of a value separated list of Ids
    */
    function _validListIDs($ids) {
        $groups = explode(',', $ids);
        $nbg = count($groups);
        for ($i = 0;$i < $nbg;$i++) if (preg_match('/^[0-9]+$/', $groups[$i]) == 0) return false;
        return true;
    }
    /*
    *  Append the search results with the TVs fields
    */
    function _appendTvs($rs) {
        global $modx;
        $tvNames_array = array();
        $tvs_array = array();
        $records = array();

        if (!$this->cfg['tvPhx']) {
             while($row = $modx->db->getRow($rs)) $records[] = $row;
        }
        else {
            if (isset($this->cfg['withTvs']) && ($this->cfg['withTvs'])) {
                $tvNames_array = array_unique(array_diff($this->_getTvsArray($this->cfg['tvPhx']),$this->scTvs['names']));
            }
            else $tvNames_array = array_unique($this->_getTvsArray($this->cfg['tvPhx']));
            if ($this->dbg) $this->asUtil->dbgRecord($tvNames_array, "AppendTvs - tvPhx");

            $i = 0;
            while($row = $modx->db->getRow($rs)){
                $records[] = $row;
                $tv_array = $this->_getDocTvs($row['id'],$tvNames_array);
                foreach ($tv_array as $name => $output) {
                    $records[$i][$name] = $output;
                    $this->scMain['append'][] = $name;
                }
                $i++;
            }
        }
        return $records;
    }
    /*
    *  Get the user defined tvs of a document
    */
    function _getDocTvs($docid, $tvNames_array) {
        global $modx;
        $results = array();
        $idnames = array();

        if (count($tvNames_array)) $where = " AND name in ('" . implode("','",$tvNames_array) . "')";
        else $where = '';

        $sql  = "SELECT DISTINCT tv.id AS id ";
        $sql .= "FROM " . $modx->getFullTableName('site_tmplvars')." tv ";
        $sql .= "INNER JOIN " . $modx->getFullTableName('site_tmplvar_templates')." tvtpl ON tvtpl.tmplvarid = tv.id ";
        $sql .= "LEFT JOIN " . $modx->getFullTableName('site_tmplvar_contentvalues')." tvc ON tvc.tmplvarid=tv.id AND tvc.contentid = '" . $docid . "' ";
        $sql .= "WHERE tvc.contentid = '" . $docid . "'" . $where;
        $rs= $modx->db->query($sql);
        if ($modx->db->getRecordCount($rs)) {
            while($row = $modx->db->getRow($rs)){
                array_push($idnames,$row['id']);
            }
            $results = $modx->getTemplateVarOutput($idnames,$docid);
            if (!$results) $results = array();
        }
        return $results;
    }
    /*
    *  Returns a short table name based on db settings
    */
    function _getShortTableName($tbl) {
        global $modx;
        return "`" . $modx->db->config['table_prefix'] . $tbl . "`";
    }
    /*
    *  print Select
    */
    function _printSelect($query) {

        $searched = array(" SELECT", " GROUP_CONCAT", " LEFT JOIN", " INNER JOIN", " SELECT", " FROM", " WHERE", " GROUP BY", " HAVING", " ORDER BY");
        $replace = array(" \r\nSELECT", " \r\nGROUP_CONCAT", " \r\nLEFT JOIN", " \r\nINNER JOIN", " \r\nSELECT", " \r\nFROM", " \r\nWHERE", " \r\nGROUP BY", " \r\nHAVING", " \r\nORDER BY");
        $query = str_replace($searched, $replace, " " . $query);
        return $query;
    }
    /*
    *  htmlentities
    */
    function _htmlentities($string, $quote_style = ENT_COMPAT, $charset = 'UTF-8', $double_encode = false) {

        if (version_compare(PHP_VERSION, '5.2.3', '>=')) $string = htmlentities($string, $quote_style, $charset, $double_encode);
        else $string = htmlentities($string, $quote_style, $charset);
        return $string;
    }
}
?>