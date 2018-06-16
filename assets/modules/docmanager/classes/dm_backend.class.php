<?php

class DocManagerBackend {
	var $dm = null;
	var $modx = null;

    function __construct(&$dm, &$modx) {
    	$this->dm = &$dm;
    	$this->modx = &$modx;
    }

    function handlePostback() {
    	switch($_POST['tabAction']) {
    		case 'changeTemplate':
    			echo $this->changeTemplate($_POST['pids'], $_POST['newvalue']);
    			break;
    		case 'changeTV':
    			echo $this->changeTemplateVariables($_POST['pids']);
    			break;
    		case 'pushDocGroup':
    		case 'pullDocGroup':
    			echo $this->changeDocGroups($_POST['pids'], $_POST['newvalue'], $_POST['tabAction']);
    			break;
    		case 'changeOther':
    			echo $this->changeOther($_POST['pids']);
    			break;
    	}
    }

    function changeTemplate($pids, $template) {
		$results = $this->processRange($pids, 'id', 1);
		$pids = $results[0];
		$error = $results[1];

		if ($pids !== '' && $template !== '') {
			$values = rtrim($pids, ' OR ');
			$fields = array ('template' => (int)$template);
			$this->modx->db->update($fields, $this->modx->getFullTableName('site_content'), $values);
		} else {
			$error .= '<br />' . $this->dm->lang['DM_process_noselection'] . '<br />';
		}

		if ($error == '') {
			$this->dm->ph['update.message'] = $this->dm->lang['DM_process_update_success'];
		} else {
			$this->dm->ph['update.message'] = $this->dm->lang['DM_process_update_error'] . '<br />' . $error;
		}
		$this->dm->ph['update.message'] .= '<br />' . $this->dm->lang['DM_tpl_results_message'];

		$this->modx->clearCache('full');
		$this->logDocumentChange('template');
		return $this->dm->parseTemplate('update.tpl', $this->dm->ph);
	}

	function changeTemplateVariables($pids) {
		$updateError = '';

		/*
        $ignoreList = array();
		if (trim($_POST['ignoreTV']) <> '') {
			$ignoreList = explode(',', $_POST['ignoreTV']);
			foreach ($ignoreList as $key => $value) {
				$ignoreList[$key] = trim($value);
			}
		}
		 */

		$results = $this->processRange($pids, 'id', 0);
		$pids = $results[0];
		$error = $results[1];

		if (count($pids) > 0) {
			$tmplVars = array ();
			foreach ($_POST as $key => $value) {
				if (substr($key, 0, 10) == 'update_tv_' && $value == 'yes') {
					//echo $key;
					$tvKeyName = substr($key, 10);
					//if (strpos($key,'_prefix') !== false)
					//	continue;

					$typeSQL = $this->modx->db->select('*', $this->modx->getFullTableName('site_tmplvars'), "id='{$tvKeyName}'");
					$row = $this->modx->db->getRow($typeSQL);
					if ($row['type'] == 'url') {
						$tmplvar = $_POST["tv" . $row['id']];
						if ($_POST["tv" . $row['id'] . '_prefix'] != '--') {
							$tmplvar = str_replace(array (
								"ftp://",
								"http://"
							), "", $tmplvar);
							$tmplvar = $_POST["tv" . $row['id'] . '_prefix'] . $tmplvar;
						}
					} elseif ($row['type'] == 'file') {
							$tmplvar = $_POST["tv" . $row['id']];
					} else {
						if (is_array($_POST["tv" . $tvKeyName])) {
							$feature_insert = array();
							$lst = $_POST["tv".$row['id']];
							while (list($featureValue, $feature_item) = each ($lst)) {
								$feature_insert[count($feature_insert)] = $feature_item;
							}
							$tmplvar = implode("||",$feature_insert);
         				} else {
  	  	    				$tmplvar = $_POST["tv".$row['id']];
         				}
					}
					$tmplVars["{$tvKeyName}"] = $tmplvar;
				}
			}

			foreach ($pids as $docID) {
				$tempSQL = $this->modx->db->select('template', $this->modx->getFullTableName('site_content'), "id='{$docID}'");
				if ($row = $this->modx->db->getRow($tempSQL)) {
					if ($row['template'] == $_POST['template_id']) {
						$tvID = $this->getTemplateVarIds($tmplVars,$docID);
						if (count($tvID) > 0) {
							foreach ($tvID as $tvIndex => $tvValue) {
                                if($_POST['update_tv_' . $tvIndex] == 'yes') {
                                    $checkSQL = $this->modx->db->select('value', $this->modx->getFullTableName('site_tmplvar_contentvalues'), "contentid='{$docID}' AND tmplvarid='{$tvValue}'");
                                    $checkCount = $this->modx->db->getRecordCount($checkSQL);
                                    if ($checkCount) {
                                        $checkRow = $this->modx->db->getRow($checkSQL);
                                        if ($checkRow['value'] == $tmplVars["$tvIndex"]) {
                                            $noUpdate = true;
                                        }
                                        elseif (trim($tmplVars["$tvIndex"]) == '') {
                                            $this->modx->db->delete($this->modx->getFullTableName('site_tmplvar_contentvalues'), "contentid='{$docID}' AND tmplvarid='{$tvValue}'");
                                            $noUpdate = true;
                                        }
                                    }
                                    //+++++++
                                    $newTvVal = $tmplVars["$tvIndex"];
                                    if (substr($newTvVal, 0, 5) == '@EVAL') {
                                        $sign = trim(substr($newTvVal, 6, 1));
                                        $op = trim(substr($newTvVal, 7));
                                        switch ($sign) {
                                            case '+':
                                                $newTvVal = $checkRow['value'] + $op;
                                                break;
                                            case '-':
                                                $newTvVal = $checkRow['value'] - $op;
                                                break;
                                            case '*':
                                                $newTvVal = $checkRow['value'] * $op;
                                                break;
                                            case '/':
                                                $newTvVal = $checkRow['value'] / $op;
                                                break;

                                            default:
                                                $newTvVal = $checkRow['value'].'~'.$op;
                                                break;
                                        }
                                    }
                                    //++++++
                                    if ($checkCount > 0 && !isset ($noUpdate)) {
                                        $fields = array (
                                            'value' => $this->modx->db->escape($tmplVars["$tvIndex"])
                                        );

                                        $this->modx->db->update($fields, $this->modx->getFullTableName('site_tmplvar_contentvalues'), "contentid='{$docID}' AND tmplvarid='{$tvValue}'");
                                        $updated = true;
                                    } elseif (!isset ($noUpdate) && ltrim($tmplVars["$tvIndex"]) !== '') {

                                        $fields = array (
                                            'value' => $this->modx->db->escape($tmplVars["$tvIndex"]),
                                            'contentid' => $this->modx->db->escape($docID),
                                            'tmplvarid' => $this->modx->db->escape($tvValue)
                                        );

                                        $this->modx->db->insert($fields, $this->modx->getFullTableName('site_tmplvar_contentvalues'));
                                        $updated = true;
                                    }
                                }
                                unset($noUpdate);
							}
						}
					} else {
						$updateError .= 'ID: ' . $docID . ' ' . $this->dm->lang['DM_tv_template_mismatch'] . '<br />';
					}
				} else {
					if ($docID !== '0') {
						$updateError .= 'ID: ' . $docID . ' ' . $this->dm->lang['DM_tv_doc_not_found'] . '<br />';
					}
				}
			}
		} else {
			$updateError .= $this->dm->lang['DM_tv_no_docs'] . '<br />';
		}

		if ($updated) {
			$this->logDocumentChange('templatevariables');
		}

		if ($error == '' && $updateError == '') {
			$this->dm->ph['update.message'] = $this->dm->lang['DM_process_update_success'];
		} else {
			$this->dm->ph['update.message'] = $this->dm->lang['DM_process_update_error'] . '<br />' . $error;
		}

		if ($updateError <> '') {
			$this->dm->ph['update.message'] .= '<br />' . $updateError;
		}
		$this->dm->ph['update.message'] .= '<br />'. $this->dm->lang['DM_tpl_results_message'];

		$this->modx->clearCache();
		return $this->dm->parseTemplate('update.tpl', $this->dm->ph);
	}

	function changeDocGroups($pids, $docgroup, $action) {
		$doc_id = array ();
		$this->dm->ph['update.message'] = '';
		$doc_vals = $this->processRange($pids, '', 0);
		$doc_id = $doc_vals[0];
		$error = $doc_vals[1];

		if (!empty($docgroup)) {
			switch ($action) {
				case 'pushDocGroup' :
					if (count($doc_id) > 0) {
						foreach ($doc_id as $value) {
							$docsAdded = 0;
							$sqlResult = $this->modx->db->select('count(*)', $this->modx->getFullTableName('document_groups'), "document_group = '{$docgroup}' AND document = '{$value}'");
							$NotAMember = ($this->modx->db->getValue($sqlResult) == 0);
							if ($NotAMember) {
								$this->modx->db->insert(
									array(
										'document_group' => $docgroup,
										'document'       => $value,
									), $this->modx->getFullTableName('document_groups'));
								$this->secureWebDocument($value);
								$this->secureMgrDocument($value);
								$docsAdded += 1;
							} else {
								$this->dm->ph['update.message'] .= $this->dm->lang['DM_doc_skip_message1'] . ' ' . $value . ' ' . $this->dm->lang['DM_doc_skip_message2'] . "<br />";
							}
						}
					}

					break;
				case 'pullDocGroup' :
					if (count($doc_id) > 0) {
						foreach ($doc_id as $value) {
							$docsRemoved = 0;
							$sqlResult = $this->modx->db->select('count(*)', $this->modx->getFullTableName('document_groups'), "document_group = '{$docgroup}' AND document = '{$value}'");
							$AMember = ($this->modx->db->getValue($sqlResult) <> 0);
							if ($AMember) {
								$this->modx->db->delete($this->modx->getFullTableName('document_groups'), "document_group = '{$docgroup}' AND document = '{$value}'");
								$this->secureWebDocument($value);
								$this->secureMgrDocument($value);
								$docsRemoved += 1;
							} else {
								$this->dm->ph['update.message'] .= $this->dm->lang['DM_doc_skip_message1'] . $value . $this->dm->lang['DM_doc_skip_message2'] . "<br />";
							}
						}
					}
					break;
			}
		} else {
			$error = $this->dm->lang['DM_doc_no_docs'];
		}

		if ($error == '') {
			$this->dm->ph['update.message'] .= '<br />' . $this->dm->lang['DM_process_update_success'];
		} else {
			$this->dm->ph['update.message'] .= '<br />' . $this->dm->lang['DM_process_update_error'] . '<br />' . $error;
		}

		$this->logDocumentChange('docpermissions');
		return $this->dm->parseTemplate('update.tpl', $this->dm->ph);
	}

	function changeOther($pids) {
		session_start();

		/* misc document settings */
		switch ($_POST['setoption']) {
			case 1:
				$fieldval = 'published';
				$secondaryFields = array (
					'publishedon' => (($_POST['newvalue'] == '1') ? time() : 0),
					'publishedby' => (($_POST['newvalue'] == '1') ? $_SESSION['mgrInternalKey'] : 0)
				);
				$this->logDocumentChange('publish');
				break;
			case 2:
				$fieldval = 'hidemenu';
				$this->logDocumentChange('hidemenu');
				break;
			case 3:
				$fieldval = 'searchable';
				$this->logDocumentChange('search');
				break;
			case 4:
				$fieldval = 'cacheable';
				$this->logDocumentChange('cache');
				break;
			case 5:
				$fieldval = 'richtext';
				$this->logDocumentChange('richtext');
				break;
			case 6:
				$fieldval = 'deleted';
				$secondaryFields = array (
					'deletedon' => (($_POST['newvalue'] == '1') ? time() : '0'),
					'deletedby' => (($_POST['newvalue'] == '1') ? $_SESSION['mgrInternalKey'] : '0')
				);
				$this->logDocumentChange('delete');
				break;
			default:
				break;
		}

		/* document date settings */
		$dateval = array();

		if ($_POST['pubdate'] <> '')
			$dateval['pub_date'] = $this->modx->toTimeStamp($_POST['pubdate']);
		if ($_POST['unpubdate'] <> '')
			$dateval['unpub_date'] = $this->modx->toTimeStamp($_POST['unpubdate']);
		if ($_POST['createdon'] <> '')
			$dateval['createdon'] = $this->modx->toTimeStamp($_POST['createdon']);
		if ($_POST['editedon'] <> '')
			$dateval['editedon'] = $this->modx->toTimeStamp($_POST['editedon']);

		/* document author settings */
		$authorval = array ();
		if ($_POST['author_createdby'] <> 0)
			$authorval['createdby'] = (int)$_POST['author_createdby'];
		if ($_POST['author_editedby'] <> 0)
			$authorval['editedby'] = (int)$_POST['author_editedby'];

		$new = false;
		$results = $this->processRange($pids, 'id', 1);
		$pids = $results[0];
		$error = $results[1];
		$values = rtrim($pids, ' OR ');

		if ($pids !== '' && $_POST['newvalue'] !== '') {
			$fields = array ($fieldval => (int)$_POST['newvalue']);
			if (isset ($secondaryFields) && is_array($secondaryFields)) {
				$fields = array_merge($fields, $secondaryFields);
			}

			$this->modx->db->update($fields, $this->modx->getFullTableName('site_content'), $values);
			$new = true;
		}

		if ($pids !== '' && count($dateval) > 0) {
			$this->modx->db->update($dateval, $this->modx->getFullTableName('site_content'), $values);
			$new = true;
			$this->logDocumentChange('dates');
		}

		if ($pids <> '' && count($authorval) > 0) {
			$this->modx->db->update($authorval, $this->modx->getFullTableName('site_content'), $values);
			$new = true;
			$this->logDocumentChange('authors');
		}

		if (!$new) {
			$error .= '<br />' . $this->dm->lang['DM_process_noselection'] . '<br />';
		}

		if ($error == '') {
			$this->dm->ph['update.message'] = '<br />' . $this->dm->lang['DM_process_update_success'];
		} else {
			$this->dm->ph['update.message'] = '<br />' . $this->dm->lang['DM_process_update_error'] . '<br />' . $error;
		}

		return $this->dm->parseTemplate('update.tpl', $this->dm->ph);
	}

    function processRange($pids, $column, $returnval = 1) {
		$values = array();
		$error = '';

		if (trim($pids) <> '') {
			$values = explode(',', $pids);
		} else {
			$error .= $this->dm->lang['DM_process_novalues'];
		}
		$pids = '';

		/* parse values, and check for invalid entries */
		foreach ($values as $key => $value) {
			/* value is a range */
			if (preg_match('/^[\d]+\-[\d]+$/', trim($value))) {
				$match = explode('-', $value);

				if (($match[1] - $match[0]) < 0) {
					$error = $this->dm->lang['DM_process_limits_error'] . $value . '<br />';
				}

				$loop = $match[1] - $match[0];
				for ($i = 0; $i <= $loop; $i++) {
					if ($returnval == 0) {
						$idarray[] = ($i + $match[0]);
					} else {
						$pids .= '' . $column . '=\'' . ($i + $match[0]) . '\' OR ';
					}
				}
			}

			/* value is a group for immediate children */
			elseif (preg_match('/^[\d]+\*$/', trim($value), $match)) {
				$match = rtrim($match[0], '*');

				$group = $this->modx->db->select('id', $this->modx->getFullTablename('site_content'), "parent='{$match}'");

				if ($returnval == 0) {
					$idarray[] = $match;
				} else {
					$pids .= '' . $column . '=\'' . $match . '\' OR ';
				}
				while ($row = $this->modx->db->getRow($group)) {
					if ($returnval == 0) {
						$idarray[] = ($row['id']);
					} else {
						$pids .= '' . $column . '=\'' . $row['id'] . '\' OR ';
					}
				}
			}
			/* value is a group for ALL children */
			elseif (preg_match('/^[\d]+\*\*$/', trim($value), $match)) {
				$match = rtrim($match[0], '**');
				$idarray[] = $match;

				for ($i = 0; $i < count($idarray); $i++) {
					$where = 'parent=' . $idarray[$i];
					$rs = $this->modx->db->select('id', $this->modx->getFullTableName('site_content'), $where);
						while ($row = $this->modx->db->getRow($rs)) {
							$idarray[] = $row['id'];
						}
				}

				for ($i = 0; $i < count($idarray); $i++) {
					$pids .= '' . $column . '=\'' . $idarray[$i] . '\' OR ';
				}
			}
			/* value is a single document */
			elseif (preg_match('/^[\d]+$/', trim($value), $match)) {
				if ($returnval == 0) {
					$idarray[] = ($i + $match[0]);
				} else {
					$pids .= '' . $column . '=\'' . trim($value) . '\' OR ';
				}
			} else {
				$error .= $this->dm->lang['DM_process_invalid_error'] . $value . '<br />';
			}
		}

		if ($returnval == 0) {
			$results[] = $idarray;
			$results[] = $error;
		} else {
			$results[] = $pids;
			$results[] = $error;
		}

		return $results;
	}

    function getTemplateVarIds($tvNames = array (), $documentId, $ignoreList=array()) {
		$output = array ();
		if (count($tvNames) > 0) {
			foreach ($tvNames as $name => $value) {
				if (in_array($name,$ignoreList)) {
					continue;
				}
				$sql = $this->modx->db->select('id,default_text', $this->modx->getFullTableName('site_tmplvars'), "id='{$name}'");
				if ($row = $this->modx->db->getRow($sql)) {
					if ($value !== $row['default_text'] || trim($value) == '') {
						$output["$name"] = $row['id'];
					} elseif ($value == $row["default_text"]) {
						$newSql = $this->modx->db->select("count(value)", $this->modx->getFullTableName("site_tmplvar_contentvalues"), "tmplvarid='{$row['id']}' AND contentid='{$documentId}'");
						if ($this->modx->db->getValue($newSql) == 1) {
							$this->modx->db->delete($this->modx->getFullTableName("site_tmplvar_contentvalues"), "tmplvarid='{$row['id']}' AND contentid='{$documentId}'");
						}
					}
				}
			}
		}
		return $output;
	}

    function secureWebDocument($docId = '') {
		$rs = $this->modx->db->select(
			'DISTINCT sc.id',
			$this->modx->getFullTableName("site_content") . " sc
				LEFT JOIN " . $this->modx->getFullTableName("document_groups") . " dg ON dg.document = sc.id
				LEFT JOIN " . $this->modx->getFullTableName("webgroup_access") . " wga ON wga.documentgroup = dg.document_group",
			($docId > 0 ? " sc.id={$docId} AND " : "") . "wga.id>0"
			);
		$ids = $this->modx->db->getColumn("id", $rs);
		if (count($ids) > 0) {
			$this->modx->db->update(array('privateweb'=>1), $this->modx->getFullTableName("site_content"), "id IN (" . implode(",", $ids) . ")");
		} else {
			$this->modx->db->update(array('privateweb'=>0), $this->modx->getFullTableName("site_content"), ($docId > 0 ? "id='{$docId}'" : "privateweb = 1"));
		}
	}

	function secureMgrDocument($docId = '') {
		$rs = $this->modx->db->select(
			'DISTINCT sc.id',
			$this->modx->getFullTableName("site_content") . " sc
				LEFT JOIN " . $this->modx->getFullTableName("document_groups") . " dg ON dg.document = sc.id
				LEFT JOIN " . $this->modx->getFullTableName("membergroup_access") . " mga ON mga.documentgroup = dg.document_group",
			($docId > 0 ? " sc.id={$docId} AND " : "") . "mga.id>0"
			);
		$ids = $this->modx->db->getColumn("id", $rs);
		if (count($ids) > 0) {
			$this->modx->db->update(array('privatemgr'=>1), $this->modx->getFullTableName("site_content"), "id IN (" . implode(",", $ids) . ")");
		} else {
			$this->modx->db->update(array('privatemgr'=>0), $this->modx->getFullTableName("site_content"), ($docId > 0 ? "id='{$docId}'" : "privatemgr = 1"));
		}
	}

	function logDocumentChange($action) {
		$log = new EvolutionCMS\Legacy\LogHandler;

		switch ($action) {
			case 'template' :
				$log->initAndWriteLog($this->dm->lang['DM_log_template']);
				break;
			case 'templatevariables' :
				$log->initAndWriteLog($this->dm->lang['DM_log_templatevariables']);
				break;
			case 'docpermissions' :
				$log->initAndWriteLog($this->dm->lang['DM_log_docpermissions']);
				break;
			case 'sortmenu' :
				$log->initAndWriteLog($this->dm->lang['DM_log_sortmenu']);
				break;
			case 'publish' :
				$log->initAndWriteLog($this->dm->lang['DM_log_publish']);
				break;
			case 'hidemenu' :
				$log->initAndWriteLog($this->dm->lang['DM_log_hidemenu']);
				break;
			case 'search' :
				$log->initAndWriteLog($this->dm->lang['DM_log_search']);
				break;
			case 'cache' :
				$log->initAndWriteLog($this->dm->lang['DM_log_cache']);
				break;
			case 'richtext' :
				$log->initAndWriteLog($this->dm->lang['DM_log_richtext']);
				break;
			case 'delete' :
				$log->initAndWriteLog($this->dm->lang['DM_log_delete']);
				break;
			case 'dates' :
				$log->initAndWriteLog($this->dm->lang['DM_log_richtext']);
				break;
			case 'authors' :
				$log->initAndWriteLog($this->dm->lang['DM_log_authors']);
				break;
		}
	}
}
?>
