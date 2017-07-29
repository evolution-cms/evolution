<?php
if(IN_MANAGER_MODE != "true") {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('edit_template') && $modx->manager->action == '301') {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}
if(!$modx->hasPermission('new_template') && $modx->manager->action == '300') {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$origin = isset($_REQUEST['or']) ? intval($_REQUEST['or']) : 76;
$originId = isset($_REQUEST['oid']) ? intval($_REQUEST['oid']) : NULL;

$tbl_site_tmplvars = $modx->getFullTableName('site_tmplvars');
$tbl_site_templates = $modx->getFullTableName('site_templates');
$tbl_site_tmplvar_templates = $modx->getFullTableName('site_tmplvar_templates');
$tbl_documentgroup_names = $modx->getFullTableName('documentgroup_names');

// check to see the snippet editor isn't locked
if($lockedEl = $modx->elementIsLocked(2, $id)) {
	$modx->webAlertAndQuit(sprintf($_lang['lock_msg'], $lockedEl['username'], $_lang['tmplvar']));
}
// end check for lock

// Lock snippet for other users to edit
$modx->lockElement(2, $id);

global $content;
$content = array();
if(isset($_GET['id'])) {
	$rs = $modx->db->select('*', $tbl_site_tmplvars, "id='{$id}'");
	$content = $modx->db->getRow($rs);
	if(!$content) {
		header("Location: " . MODX_SITE_URL . "index.php?id={$site_start}");
	}

	$_SESSION['itemname'] = $content['caption'];
	if($content['locked'] == 1 && $modx->hasPermission('save_role') != 1) {
		$modx->webAlertAndQuit($_lang["error_no_privileges"]);
	}
} else if(isset($_REQUEST['itemname'])) {
	$content['name'] = $_REQUEST['itemname'];
} else {
	$_SESSION['itemname'] = $_lang["new_tmplvars"];
	$content['category'] = intval($_REQUEST['catid']);
}

if($modx->manager->hasFormValues()) {
	$modx->manager->loadFormValues();
}

$content = array_merge($content, $_POST);

// Add lock-element JS-Script
$lockElementId = $id;
$lockElementType = 2;
require_once(MODX_MANAGER_PATH . 'includes/active_user_locks.inc.php');

// get available RichText Editors
$RTEditors = '';
$evtOut = $modx->invokeEvent('OnRichTextEditorRegister', array('forfrontend' => 1));
if(is_array($evtOut)) {
	$RTEditors = implode(',', $evtOut);
}

?>
<script language="JavaScript">

	function check_toggle() {
		var el = document.getElementsByName("template[]");
		var count = el.length;
		for(i = 0; i < count; i++) el[i].checked = !el[i].checked;
	};

	function check_none() {
		var el = document.getElementsByName("template[]");
		var count = el.length;
		for(i = 0; i < count; i++) el[i].checked = false;
	};

	function check_all() {
		var el = document.getElementsByName("template[]");
		var count = el.length;
		for(i = 0; i < count; i++) el[i].checked = true;
	};

	var actions = {
		save: function() {
			documentDirty = false;
			form_save = true;
			document.mutate.save.click();
			saveWait('mutate');
		},
		duplicate: function() {
			if(confirm("<?= $_lang['confirm_duplicate_record'] ?>") === true) {
				documentDirty = false;
				document.location.href = "index.php?id=<?= $_REQUEST['id'] ?>&a=304";
			}
		},
		delete: function() {
			if(confirm("<?= $_lang['confirm_delete_tmplvars'] ?>") === true) {
				documentDirty = false;
				document.location.href = "index.php?id=" + document.mutate.id.value + "&a=303";
			}
		},
		cancel: function() {
			documentDirty = false;
			document.location.href = 'index.php?a=<?= $origin ?><?=($originId != NULL ? '&id=' . $originId : '') ?>&utype=<?php echo $lockElementType; ?>&uid=<?php echo $lockElementId; ?>';
		}
	};

	// Widget Parameters
	var widgetParams = {};          // name = description;datatype;default or list values - datatype: int, string, list : separated by comma (,)
	widgetParams['date'] = '&format=Date Format;string;%A %d, %B %Y &default=If no value, use current date;list;Yes,No;No';
	widgetParams['string'] = '&format=String Format;list;Upper Case,Lower Case,Sentence Case,Capitalize';
	widgetParams['delim'] = '&format=Delimiter;string;,';
	widgetParams['hyperlink'] = '&text=Display Text;string; &title=Title;string; &class=Class;string &style=Style;string &target=Target;string &attrib=Attributes;string';
	widgetParams['htmltag'] = '&tagname=Tag Name;string;div &tagid=Tag ID;string &class=Class;string &style=Style;string &attrib=Attributes;string';
	widgetParams['viewport'] = '&vpid=ID/Name;string &width=Width;string;100 &height=Height;string;100 &borsize=Border Size;int;1 &sbar=Scrollbars;list;,Auto,Yes,No &asize=Auto Size;list;,Yes,No &aheight=Auto Height;list;,Yes,No &awidth=Auto Width;list;,Yes,No &stretch=Stretch To Fit;list;,Yes,No &class=Class;string &style=Style;string &attrib=Attributes;string';
	widgetParams['datagrid'] = '&cols=Column Names;string &flds=Field Names;string &cwidth=Column Widths;string &calign=Column Alignments;string &ccolor=Column Colors;string &ctype=Column Types;string &cpad=Cell Padding;int;1 &cspace=Cell Spacing;int;1 &rowid=Row ID Field;string &rgf=Row Group Field;string &rgstyle = Row Group Style;string &rgclass = Row Group Class;string &rowsel=Row Select;string &rhigh=Row Hightlight;string; &psize=Page Size;int;100 &ploc=Pager Location;list;top-right,top-left,bottom-left,bottom-right,both-right,both-left; &pclass=Pager Class;string &pstyle=Pager Style;string &head=Header Text;string &foot=Footer Text;string &tblc=Grid Class;string &tbls=Grid Style;string &itmc=Item Class;string &itms=Item Style;string &aitmc=Alt Item Class;string &aitms=Alt Item Style;string &chdrc=Column Header Class;string &chdrs=Column Header Style;string;&egmsg=Empty message;string;No records found;';
	widgetParams['richtext'] = '&w=Width;string;100% &h=Height;string;300px &edt=Editor;list;<?= $RTEditors ?>';
	widgetParams['image'] = '&alttext=Alternate Text;string &hspace=H Space;int &vspace=V Space;int &borsize=Border Size;int &align=Align;list;none,baseline,top,middle,bottom,texttop,absmiddle,absbottom,left,right &name=Name;string &class=Class;string &id=ID;string &style=Style;string &attrib=Attributes;string';
	widgetParams['custom_widget'] = '&output=Output;textarea;[+value+]';

	// Current Params
	var currentParams = {};
	var lastdf, lastmod = {};

	function showParameters(ctrl) {
		var c, p, df, cp;
		var ar, desc, value, key, dt;

		currentParams = {}; // reset;

		if(ctrl && ctrl.form) {
			f = ctrl.form;
		} else {
			f = document.forms['mutate'];
			if(!f) return;
			ctrl = f.display;
		}
		cp = f.params.value.split("&"); // load current setting once

		// get display format
		df = lastdf = ctrl.options[ctrl.selectedIndex].value;

		// load last modified param values
		if(lastmod[df]) cp = lastmod[df].split("&");
		for(p = 0; p < cp.length; p++) {
			cp[p] = (cp[p] + '').replace(/^\s|\s$/, ""); // trim
			ar = cp[p].split("=");
			currentParams[ar[0]] = ar[1];
		}

		// setup parameters
		var tr = document.getElementById('displayparamrow'), t, td, dp = (widgetParams[df]) ? widgetParams[df].split("&") : "";
		if(!dp) tr.style.display = 'none';
		else {
			t = '<table class="displayparams"><thead><tr><td width="50%"><?= $_lang['parameter'] ?></td><td width="50%"><?= $_lang['value'] ?></td></tr></thead>';
			for(p = 0; p < dp.length; p++) {
				dp[p] = (dp[p] + '').replace(/^\s|\s$/, ""); // trim
				ar = dp[p].split("=");
				key = ar[0];     // param
				ar = (ar[1] + '').split(";");
				desc = ar[0];   // description
				dt = ar[1];     // data type
				value = decode((currentParams[key]) ? currentParams[key] : (dt === 'list') ? ar[3] : (ar[2]) ? ar[2] : '');
				if(value !== currentParams[key]) currentParams[key] = value;
				value = (value + '').replace(/^\s|\s$/, ""); // trim
				value = value.replace(/\"/g, "&quot;"); // replace double quotes with &quot;
				if(dt) {
					switch(dt) {
						case 'int':
						case 'float':
							c = '<input type="text" name="prop_' + key + '" value="' + value + '" size="30" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)" />';
							break;
						case 'list':
							c = '<select name="prop_' + key + '" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)">';
							var ls = (ar[2] + '').split(",");
							if(!currentParams[key] || currentParams[key] === 'undefined') {
								currentParams[key] = ls[0]; // use first list item as default
							}
							for(i = 0; i < ls.length; i++) {
								c += '<option value="' + ls[i] + '"' + ((ls[i] === value) ? ' selected="selected"' : '') + '>' + ls[i] + '</option>';
							}
							c += '</select>';
							break;
						case 'textarea':
							c = '<textarea class="inputBox phptextarea" name="prop_' + key + '" cols="25" style="width:220px;" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)" >' + value + '</textarea>';
							break;
						default:  // string
							c = '<input type="text" name="prop_' + key + '" value="' + value + '" size="30" onchange="setParameter(\'' + key + '\',\'' + dt + '\',this)" />';
							break;

					}
					t += '<tr><td bgcolor="#FFFFFF" width="50%">' + desc + '</td><td bgcolor="#FFFFFF" width="50%">' + c + '</td></tr>';
				}
				;
			}
			t += '</table>';
			td = (document.getElementById) ? document.getElementById('displayparams') : document.all['displayparams'];
			td.innerHTML = t;
			tr.style.display = '';
		}
		implodeParameters();
	}

	function setParameter(key, dt, ctrl) {
		var v;
		if(!ctrl) return null;
		switch(dt) {
			case 'int':
				ctrl.value = parseInt(ctrl.value);
				if(isNaN(ctrl.value)) ctrl.value = 0;
				v = ctrl.value;
				break;
			case 'float':
				ctrl.value = parseFloat(ctrl.value);
				if(isNaN(ctrl.value)) ctrl.value = 0;
				v = ctrl.value;
				break;
			case 'list':
				v = ctrl.options[ctrl.selectedIndex].value;
				break;
			case 'textarea':
				v = ctrl.value + '';
				break;
			default:
				v = ctrl.value + '';
				break;
		}
		currentParams[key] = v;
		implodeParameters();
	}

	function resetParameters() {
		document.mutate.params.value = "";
		lastmod[lastdf] = "";
		showParameters();
	}

	// implode parameters
	function implodeParameters() {
		var v, p, s = '';
		for(p in currentParams) {
			v = currentParams[p];
			if(v) s += '&' + p + '=' + encode(v);
		}
		document.forms['mutate'].params.value = s;
		if(lastdf) lastmod[lastdf] = s;
	}

	function encode(s) {
		s = s + '';
		s = s.replace(/\=/g, '%3D'); // =
		s = s.replace(/\&/g, '%26'); // &
		return s;
	}

	function decode(s) {
		s = s + '';
		s = s.replace(/\%3D/g, '='); // =
		s = s.replace(/\%26/g, '&'); // &
		return s;
	}

	document.addEventListener('DOMContentLoaded', function() {
		var h1help = document.querySelector('h1 > .help');
		h1help.onclick = function() {
			document.querySelector('.element-edit-message').classList.toggle('show')
		}
	});

</script>

<form name="mutate" method="post" action="index.php" enctype="multipart/form-data">
	<?php
	// invoke OnTVFormPrerender event
	$evtOut = $modx->invokeEvent('OnTVFormPrerender', array('id' => $id));
	if(is_array($evtOut)) {
		echo implode("", $evtOut);
	}
	?>
	<input type="hidden" name="id" value="<?= $content['id'] ?>">
	<input type="hidden" name="a" value="302">
	<input type="hidden" name="or" value="<?= $origin ?>">
	<input type="hidden" name="oid" value="<?= $originId ?>">
	<input type="hidden" name="mode" value="<?= $modx->manager->action ?>">
	<input type="hidden" name="params" value="<?= $modx->htmlspecialchars($content['display_params']) ?>">

	<h1>
		<i class="fa fa-list-alt"></i><?= $_lang['tmplvars_title'] ?><i class="fa fa-question-circle help"></i>
	</h1>

	<?= $_style['actionbuttons']['dynamic']['element'] ?>

	<div class="container element-edit-message">
		<div class="alert alert-info"><?= $_lang['tmplvars_msg'] ?></div>
	</div>

	<div class="tab-pane" id="tmplvarsPane">
		<script type="text/javascript">
			tpTmplvars = new WebFXTabPane(document.getElementById("tmplvarsPane"), false);
		</script>

		<div class="tab-page" id="tabGeneral">
			<h2 class="tab"><?= $_lang['settings_general'] ?></h2>
			<script type="text/javascript">tpTmplvars.addTabPage(document.getElementById("tabGeneral"));</script>
			<div class="container container-body">
				<div class="row form-row">
					<label class="col-md-3 col-lg-2"><?= $_lang['tmplvars_name'] ?></label>
					<div class="col-md-9 col-lg-10">
						<div class="form-control-name clearfix">
							<input name="name" type="text" maxlength="50" value="<?= $modx->htmlspecialchars($content['name']) ?>" class="form-control form-control-lg" onchange="documentDirty=true;" />
							<?php if($modx->hasPermission('save_role')): ?>
								<label class="custom-control" title="<?= $_lang['lock_tmplvars'] . "\n" . $_lang['lock_tmplvars_msg'] ?>" tooltip>
									<input name="locked" type="checkbox"<?= ($content['locked'] == 1 ? ' checked="checked"' : '') ?> />
									<i class="fa fa-lock"></i>
								</label>
							<?php endif; ?>
						</div>
						<script>if(!document.getElementsByName("name")[0].value) document.getElementsByName("name")[0].focus();</script>
						<small class="form-text text-danger hide" id="savingMessage"></small>
					</div>
				</div>
				<div class="row form-row">
					<label class="col-md-3 col-lg-2"><?= $_lang['tmplvars_caption'] ?></label>
					<div class="col-md-9 col-lg-10">
						<input name="caption" type="text" maxlength="80" value="<?= $modx->htmlspecialchars($content['caption']) ?>" class="form-control" onchange="documentDirty=true;" />
					</div>
				</div>
				<div class="row form-row">
					<label class="col-md-3 col-lg-2"><?= $_lang['tmplvars_description'] ?></label>
					<div class="col-md-9 col-lg-10">
						<input name="description" type="text" maxlength="255" value="<?= $modx->htmlspecialchars($content['description']) ?>" class="form-control" onChange="documentDirty=true;">
					</div>
				</div>
				<div class="row form-row">
					<label class="col-md-3 col-lg-2"><?= $_lang['existing_category'] ?></label>
					<div class="col-md-9 col-lg-10">
						<select name="categoryid" class="form-control" onChange="documentDirty=true;">
							<option>&nbsp;</option>
							<?php
							include_once(MODX_MANAGER_PATH . 'includes/categories.inc.php');
							foreach(getCategories() as $n => $v) {
								echo "<option value='" . $v['id'] . "'" . ($content["category"] == $v["id"] ? " selected='selected'" : "") . ">" . $modx->htmlspecialchars($v["category"]) . "</option>";
							}
							?>
						</select>
					</div>
				</div>
				<div class="row form-row">
					<label class="col-md-3 col-lg-2"><?= $_lang['new_category'] ?></label>
					<div class="col-md-9 col-lg-10">
						<input name="newcategory" type="text" maxlength="45" value="" class="form-control" onchange="documentDirty=true;">
					</div>
				</div>
				<div class="row form-row">
					<label class="col-md-3 col-lg-2"><?= $_lang['tmplvars_type'] ?></label>
					<div class="col-md-9 col-lg-10">
						<select name="type" size="1" class="form-control" onchange="documentDirty=true;">
							<optgroup label="Standard Type">
								<option value="text" <?= ($content['type'] == '' || $content['type'] == 'text' ? "selected='selected'" : "") ?>>Text</option>
								<option value="rawtext" <?= ($content['type'] == 'rawtext' ? "selected='selected'" : "") ?>>Raw Text (deprecated)</option>
								<option value="textarea" <?= ($content['type'] == 'textarea' ? "selected='selected'" : "") ?>>Textarea</option>
								<option value="rawtextarea" <?= ($content['type'] == 'rawtextarea' ? "selected='selected'" : "") ?>>Raw Textarea (deprecated)</option>
								<option value="textareamini" <?= ($content['type'] == 'textareamini' ? "selected='selected'" : "") ?>>Textarea (Mini)</option>
								<option value="richtext" <?= ($content['type'] == 'richtext' || $content['type'] == 'htmlarea' ? "selected='selected'" : "") ?>>RichText</option>
								<option value="dropdown" <?= ($content['type'] == 'dropdown' ? "selected='selected'" : "") ?>>DropDown List Menu</option>
								<option value="listbox" <?= ($content['type'] == 'listbox' ? "selected='selected'" : "") ?>>Listbox (Single-Select)</option>
								<option value="listbox-multiple" <?= ($content['type'] == 'listbox-multiple' ? "selected='selected'" : "") ?>>Listbox (Multi-Select)</option>
								<option value="option" <?= ($content['type'] == 'option' ? "selected='selected'" : "") ?>>Radio Options</option>
								<option value="checkbox" <?= ($content['type'] == 'checkbox' ? "selected='selected'" : "") ?>>Check Box</option>
								<option value="image" <?= ($content['type'] == 'image' ? "selected='selected'" : "") ?>>Image</option>
								<option value="file" <?= ($content['type'] == 'file' ? "selected='selected'" : "") ?>>File</option>
								<option value="url" <?= ($content['type'] == 'url' ? "selected='selected'" : "") ?>>URL</option>
								<option value="email" <?= ($content['type'] == 'email' ? "selected='selected'" : "") ?>>Email</option>
								<option value="number" <?= ($content['type'] == 'number' ? "selected='selected'" : "") ?>>Number</option>
								<option value="date" <?= ($content['type'] == 'date' ? "selected='selected'" : "") ?>>Date</option>
							</optgroup>
							<optgroup label="Custom Type">
								<option value="custom_tv" <?= ($content['type'] == 'custom_tv' ? "selected='selected'" : "") ?>>Custom Input</option>
								<?php
								$custom_tvs = scandir(MODX_BASE_PATH . 'assets/tvs');
								foreach($custom_tvs as $ctv) {
									if(strpos($ctv, '.') !== 0 && $ctv != 'index.html') {
										$selected = ($content['type'] == 'custom_tv:' . $ctv ? "selected='selected'" : "");
										echo '<option value="custom_tv:' . $ctv . '"  ' . $selected . '>' . $ctv . '</option>';
									}
								}
								?>
							</optgroup>
						</select>
					</div>
				</div>
				<div class="row form-row">
					<label class="col-md-3 col-lg-2"><?= $_lang['tmplvars_elements'] ?>
						<small class="form-text text-muted"><?= $_lang['tmplvars_binding_msg'] ?></small>
					</label>
					<div class="col-md-9 col-lg-10">
						<textarea name="elements" maxlength="65535" rows="4" class="form-control" onchange="documentDirty=true;"><?= $modx->htmlspecialchars($content['elements']) ?></textarea>
					</div>
				</div>
				<div class="row form-row">
					<label class="col-md-3 col-lg-2"><?= $_lang['tmplvars_default'] ?>
						<small class="form-text text-muted"><?= $_lang['tmplvars_binding_msg'] ?></small>
					</label>
					<div class="col-md-9 col-lg-10">
						<textarea name="default_text" class="form-control" rows="4" onchange="documentDirty=true;"><?= $modx->htmlspecialchars($content['default_text']) ?></textarea>
					</div>
				</div>
				<div class="row form-row">
					<label class="col-md-3 col-lg-2"><?= $_lang['tmplvars_widget'] ?></label>
					<div class="col-md-9 col-lg-10">
						<select name="display" size="1" class="form-control" onChange="documentDirty=true;showParameters(this);">
							<option value="" <?= ($content['display'] == '' ? "selected='selected'" : "") ?>>&nbsp;</option>
							<optgroup label="Widgets">
								<option value="datagrid" <?= ($content['display'] == 'datagrid' ? "selected='selected'" : "") ?>>Data Grid</option>
								<option value="richtext" <?= ($content['display'] == 'richtext' ? "selected='selected'" : "") ?>>RichText</option>
								<option value="viewport" <?= ($content['display'] == 'viewport' ? "selected='selected'" : "") ?>>View Port</option>
								<option value="custom_widget" <?= ($content['display'] == 'custom_widget' ? "selected='selected'" : "") ?>>Custom Widget</option>
							</optgroup>
							<optgroup label="Formats">
								<option value="htmlentities" <?= ($content['display'] == 'htmlentities' ? "selected='selected'" : "") ?>>HTML Entities</option>
								<option value="date" <?= ($content['display'] == 'date' ? "selected='selected'" : "") ?>>Date Formatter</option>
								<option value="unixtime" <?= ($content['display'] == 'unixtime' ? "selected='selected'" : "") ?>>Unixtime</option>
								<option value="delim" <?= ($content['display'] == 'delim' ? "selected='selected'" : "") ?>>Delimited List</option>
								<option value="htmltag" <?= ($content['display'] == 'htmltag' ? "selected='selected'" : "") ?>>HTML Generic Tag</option>
								<option value="hyperlink" <?= ($content['display'] == 'hyperlink' ? "selected='selected'" : "") ?>>Hyperlink</option>
								<option value="image" <?= ($content['display'] == 'image' ? "selected='selected'" : "") ?>>Image</option>
								<option value="string" <?= ($content['display'] == 'string' ? "selected='selected'" : "") ?>>String Formatter</option>
							</optgroup>
						</select>
					</div>
				</div>
				<div class="row form-row" id="displayparamrow">
					<label class="col-md-3 col-lg-2"><?= $_lang['tmplvars_widget_prop'] ?><br />
						<a href="javascript:;" onclick="resetParameters(); return false"><i class="<?= $_style['actions_refresh'] ?>" data-tooltip="<?= $_lang['tmplvars_reset_params'] ?>"></i></a></label>
					<div id="displayparams" class="col-md-9 col-lg-10"></div>
				</div>
				<div class="row form-row">
					<label class="col-md-3 col-lg-2"><?= $_lang['tmplvars_rank'] ?></label>
					<div class="col-md-9 col-lg-10">
						<input name="rank" type="text" maxlength="4" size="1" value="<?= (isset($content['rank']) ? $content['rank'] : 0) ?>" class="form-control" onchange="documentDirty=true;" />
					</div>
				</div>
				<hr>
				<!--<b><?php /*echo $_lang['tmplvar_tmpl_access'] */ ?></b>-->
				<p><?= $_lang['tmplvar_tmpl_access_msg'] ?></p>
				<div class="form-group">
					<a class="btn btn-secondary btn-sm" href="javascript:;" onClick="check_all();return false;"><?= $_lang['check_all'] ?></a>
					<a class="btn btn-secondary btn-sm" href="javascript:;" onClick="check_none();return false;"><?= $_lang['check_none'] ?></a>
					<a class="btn btn-secondary btn-sm" href="javascript:;" onClick="check_toggle(); return false;"><?= $_lang['check_toggle'] ?></a>
				</div>
				<?php
				$rs = $modx->db->select(sprintf("tpl.id AS id, templatename, tpl.description AS tpldescription, tpl.locked AS tpllocked, tpl.selectable AS selectable, tmplvarid, if(isnull(cat.category),'%s',cat.category) AS category, cat.id AS catid", $_lang['no_category']), sprintf("%s as tpl
                    LEFT JOIN %s as stt ON stt.templateid=tpl.id AND stt.tmplvarid='%s'
                    LEFT JOIN %s as cat ON tpl.category=cat.id", $modx->getFullTableName('site_templates'), $modx->getFullTableName('site_tmplvar_templates'), $id, $modx->getFullTableName('categories')), '', "category, templatename");

				$tplList = '<ul>';
				$preCat = '';
				$insideUl = 0;
				while($row = $modx->db->getRow($rs)) {
					$row['category'] = stripslashes($row['category']); //pixelchutes
					if($preCat !== $row['category']) {
						$tplList .= $insideUl ? '</ul>' : '';
						$tplList .= '<li><strong>' . $row['category'] . ($row['catid'] != '' ? ' <small>(' . $row['catid'] . ')</small>' : '') . '</strong><ul>';
						$insideUl = 1;
					}

					if($modx->manager->action == '300' && $modx->config['default_template'] == $row['id']) {
						$checked = true;
					} elseif(isset($_GET['tpl']) && $_GET['tpl'] == $row['id']) {
						$checked = true;
					} elseif($id == 0 && is_array($_POST['template'])) {
						$checked = in_array($row['id'], $_POST['template']);
					} else {
						$checked = $row['tmplvarid'];
					}
					$selectable = !$row['selectable'] ? ' class="disabled"' : '';
					$checked = $checked ? ' checked="checked"' : '';
					$tplId = '&nbsp;<small>(' . $row['id'] . ')</small>';
					$desc = !empty($row['tpldescription']) ? ' - ' . $row['tpldescription'] : '';

					$tplInfo = array();
					if($row['tpllocked']) {
						$tplInfo[] = $_lang['locked'];
					}
					if($row['id'] == $modx->config['default_template']) {
						$tplInfo[] = $_lang['defaulttemplate_title'];
					}
					$tplInfo = !empty($tplInfo) ? ' <em>(' . join(', ', $tplInfo) . ')</em>' : '';

					$tplList .= sprintf('<li><label%s><input name="template[]" value="%s" type="checkbox" %s onchange="documentDirty=true;"> %s%s%s%s</label></li>', $selectable, $row['id'], $checked, $row['templatename'], $tplId, $desc, $tplInfo);
					$tplList .= '</li>';

					$preCat = $row['category'];
				}
				$tplList .= $insideUl ? '</ul>' : '';
				$tplList .= '</ul>';
				echo $tplList;

				?>

				<!-- Access Permissions -->
				<?php
				if($use_udperms == 1) {
					// fetch permissions for the variable
					$rs = $modx->db->select('documentgroup', $modx->getFullTableName('site_tmplvar_access'), "tmplvarid='{$id}'");
					$groupsarray = $modx->db->getColumn('documentgroup', $rs);

					?>
					<?php if($modx->hasPermission('access_permissions')) { ?>
						<script type="text/javascript">
							function makePublic(b) {
								var notPublic = false;
								var f = document.forms['mutate'];
								var chkpub = f['chkalldocs'];
								var chks = f['docgroups[]'];
								if(!chks && chkpub) {
									chkpub.checked = true;
									return false;
								}
								else if(!b && chkpub) {
									if(!chks.length) notPublic = chks.checked;
									else for(i = 0; i < chks.length; i++) if(chks[i].checked) notPublic = true;
									chkpub.checked = !notPublic;
								}
								else {
									if(!chks.length) chks.checked = (b) ? false : chks.checked;
									else for(i = 0; i < chks.length; i++) if(b) chks[i].checked = false;
									chkpub.checked = true;
								}
							}
						</script>
						<hr>
						<!--<b><?php /*echo $_lang['access_permissions']; */ ?></b>-->
						<p><?= $_lang['tmplvar_access_msg'] ?></p>
						<?php
						$chk = '';
						$rs = $modx->db->select('name, id', $tbl_documentgroup_names);
						if(empty($groupsarray) && is_array($_POST['docgroups']) && empty($_POST['id'])) {
							$groupsarray = $_POST['docgroups'];
						}
						while($row = $modx->db->getRow($rs)) {
							$checked = in_array($row['id'], $groupsarray);
							if($modx->hasPermission('access_permissions')) {
								if($checked) {
									$notPublic = true;
								}
								$chks .= "<li><label><input type='checkbox' name='docgroups[]' value='" . $row['id'] . "' " . ($checked ? "checked='checked'" : '') . " onclick=\"makePublic(false)\" /> " . $row['name'] . "</label></li>";
							} else {
								if($checked) {
									echo "<input type='hidden' name='docgroups[]'  value='" . $row['id'] . "' />";
								}
							}
						}
						if($modx->hasPermission('access_permissions')) {
							$chks = "<li><label><input type='checkbox' name='chkalldocs' " . (!$notPublic ? "checked='checked'" : '') . " onclick=\"makePublic(true)\" /> <span class='warning'>" . $_lang['all_doc_groups'] . "</span></label></li>" . $chks;
						}
						echo '<ul>' . $chks . '</ul>';
						?>
					<?php } ?>
				<?php } ?>

			</div>
		</div>

		<input type="submit" name="save" style="display:none">

		<?php
		// invoke OnTVFormRender event
		$evtOut = $modx->invokeEvent('OnTVFormRender', array('id' => $id));
		if(is_array($evtOut)) {
			echo implode('', $evtOut);
		}
		?>
	</div>
</form>
<script type="text/javascript">setTimeout('showParameters()', 10);</script>
