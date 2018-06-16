<?php
use \EvolutionCMS\Support\DataGrid;

class DocManagerFrontend {
	var $dm = null;
	var $modx = null;

    function __construct(&$dm, &$modx) {
    	$this->dm = &$dm;
    	$this->modx = &$modx;
    }
    
    function getViews() {
    	$this->renderTemplates();
    	$this->renderTemplateVars();
    	$this->renderDocGroups();
    	$this->renderMisc();
    	$this->renderChangeAuthors();
    	$this->renderDocumentSelect();
    }
    
    function renderTemplates() {
		$templateRecordSet = $this->modx->db->select('id,templatename,description', $this->modx->getFullTableName('site_templates'),'','id ASC');
	
		$grd = new DataGrid('', $templateRecordSet);
		$grd->noRecordMsg = $this->dm->lang['DM_tpl_no_templates'];
		$grd->cssClass = "grid";
		$grd->columnHeaderClass = "gridHeader";
		$grd->itemClass = "gridItem";
		$grd->altItemClass = "gridAltItem";
		$grd->columns = " ," . $this->dm->lang['DM_tpl_column_id'] . "," . $this->dm->lang['DM_tpl_column_name'] . "," . $this->dm->lang['DM_tpl_column_description'];
		$grd->colTypes = "template:<input type='radio' name='id' value='[+id+]' />";
		$grd->colWidths = "5%,5%,40%,50%";
		$grd->fields = "template,id,templatename,description";
		
		$this->dm->ph['templates.grid'] = $grd->render();
		$this->dm->ph['view.templates'] = $this->dm->parseTemplate('templates.tpl', $this->dm->ph);
	}   
	
	function renderTemplateVars() {
		$templateRecordSet = $this->modx->db->select('id,templatename,description', $this->modx->getFullTableName('site_templates'),'','id ASC');

		$grd = new DataGrid('', $templateRecordSet);
		$grd->noRecordMsg = $this->dm->lang['DM_tpl_no_templates'];
		$grd->cssClass = "grid";
		$grd->columnHeaderClass = "gridHeader";
		$grd->itemClass = "gridItem";
		$grd->altItemClass = "gridAltItem";
		$grd->columns = " ," . $this->dm->lang['DM_tpl_column_id'] . "," . $this->dm->lang['DM_tpl_column_name'] . "," . $this->dm->lang['DM_tpl_column_description'];
		$grd->colTypes = 'template:<input name="tid" type="radio" value="[+id+]" onclick="loadTemplateVars(\'[+id+]\');" />';
		$grd->colWidths = "5%,5%,40%,50%";
		$grd->fields = "template,id,templatename,description";
		
		$this->dm->ph['templatevars.grid'] = $grd->render();
		$this->dm->ph['view.templatevars'] = $this->dm->parseTemplate('templatevars.tpl', $this->dm->ph);
	}
	
	function renderDocGroups() {
		$documentgroups = $this->modx->db->select('id,name', $this->modx->getFullTableName('documentgroup_names'),'','id ASC');
	
		$grd = new DataGrid('', $documentgroups);
		$grd->noRecordMsg = $this->dm->lang['DM_doc_no_docs'];
		$grd->cssClass = "grid";
		$grd->columnHeaderClass = "gridHeader";
		$grd->itemClass = "gridItem";
		$grd->altItemClass = "gridAltItem";
		$grd->columns = " ," . $this->dm->lang['DM_doc_column_id'] . "," . $this->dm->lang['DM_doc_column_name'];
		$grd->colTypes = "template:<input type='radio' name='docgroupid' value='[+id+]' />";
		$grd->colWidths = "5%,5%,40%,50%";
		$grd->fields = "template,id,name";
		
		$this->dm->ph['documentgroups.grid'] = $grd->render();
		$this->dm->ph['view.documentgroups'] = $this->dm->parseTemplate('documentgroups.tpl', $this->dm->ph);
	}
	
	function renderDocumentSelect() {
		$this->dm->ph['view.documents'] = $this->dm->parseTemplate('documents.tpl', $this->dm->ph);
	}
	
	
	function renderMisc() {
		$this->dm->ph['view.misc'] = $this->dm->parseTemplate('misc.tpl', $this->dm->ph);
        $this->dm->ph['view.tab'] = (is_numeric($_GET['tab'])) ? '<script type="text/javascript"> tpResources.setSelectedIndex( '.$_GET['tab'].' );</script>' : '';
	}
	
	function renderChangeAuthors() {
		$users = $this->modx->db->select('id,username', $this->modx->getFullTableName('manager_users'));
		$userOptions = '';

		while ($row = $this->modx->db->getRow($users)) {
			$userOptions .= '<option value="' . $row['id'] . '">' . $row['username'] . '</option>';
		}
		$this->dm->ph['changeauthors.options'] = $userOptions;
		$this->dm->ph['view.changeauthors'] = $this->dm->parseTemplate('changeauthors.tpl', $this->dm->ph);
	}
}
?>