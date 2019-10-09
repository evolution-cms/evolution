<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
switch($modx->manager->action) {
	case 107:
		if(!$modx->hasPermission('new_module')) {
			$modx->webAlertAndQuit($_lang["error_no_privileges"]);
		}
		break;
	case 108:
		if(!$modx->hasPermission('edit_module')) {
			$modx->webAlertAndQuit($_lang["error_no_privileges"]);
		}
		break;
	default:
		$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
// Get table names (alphabetical)
$tbl_membergroup_names = $modx->getFullTableName('membergroup_names');
$tbl_site_content = $modx->getFullTableName('site_content');
$tbl_site_htmlsnippets = $modx->getFullTableName('site_htmlsnippets');
$tbl_site_module_access = $modx->getFullTableName('site_module_access');
$tbl_site_module_depobj = $modx->getFullTableName('site_module_depobj');
$tbl_site_modules = $modx->getFullTableName('site_modules');
$tbl_site_plugins = $modx->getFullTableName('site_plugins');
$tbl_site_snippets = $modx->getFullTableName('site_snippets');
$tbl_site_templates = $modx->getFullTableName('site_templates');
$tbl_site_tmplvars = $modx->getFullTableName('site_tmplvars');
/**
 * create globally unique identifiers (guid)
 *
 * @return string
 */
function createGUID() {
	srand((double) microtime() * 1000000);
	$r = rand();
	$u = uniqid(getmypid() . $r . (double) microtime() * 1000000, 1);
	$m = md5($u);
	return $m;
}

// check to see the module editor isn't locked
if($lockedEl = $modx->elementIsLocked(6, $id)) {
	$modx->webAlertAndQuit(sprintf($_lang['lock_msg'], $lockedEl['username'], $_lang['module']));
}
// end check for lock

// Lock snippet for other users to edit
$modx->lockElement(6, $id);

if(isset($_GET['id'])) {
	$rs = $modx->db->select('*', $tbl_site_modules, "id='{$id}'");
	$content = $modx->db->getRow($rs);
	if(!$content) {
		$modx->webAlertAndQuit("Module not found for id '{$id}'.");
	}
	$content['properties'] = str_replace("&", "&amp;", $content['properties']);
	$_SESSION['itemname'] = $content['name'];
	if($content['locked'] == 1 && $_SESSION['mgrRole'] != 1) {
		$modx->webAlertAndQuit($_lang["error_no_privileges"]);
	}
} else {
	$_SESSION['itemname'] = $_lang["new_module"];
	$content['wrap'] = '1';
}
if($modx->manager->hasFormValues()) {
	$modx->manager->loadFormValues();
}

$content = array_merge($content, $_POST);

// Add lock-element JS-Script
$lockElementId = $id;
$lockElementType = 6;
require_once(MODX_MANAGER_PATH . 'includes/active_user_locks.inc.php');
?>
<script type="text/javascript">
	function loadDependencies() {
		if(documentDirty) {
			if(!confirm("<?= $_lang['confirm_load_depends']?>")) {
				return;
			}
		}
		documentDirty = false;
		window.location.href = "index.php?id=<?= $_REQUEST['id']?>&a=113";
	}

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
				document.location.href = "index.php?id=<?= $_REQUEST['id'] ?>&a=111";
			}
		},
		delete: function() {
			if(confirm("<?= $_lang['confirm_delete_module'] ?>") === true) {
				documentDirty = false;
				document.location.href = "index.php?id=" + document.mutate.id.value + "&a=110";
			}
		},
		cancel: function() {
			documentDirty = false;
			document.location.href = 'index.php?a=106';
		},
		run: function() {
			document.location.href = "index.php?id=<?= $_REQUEST['id'] ?>&a=112";
		}
	};

	function setTextWrap(ctrl, b) {
		if(!ctrl) return;
		ctrl.wrap = (b) ? "soft" : "off";
	}

	// Current Params/Configurations
	var currentParams = {};
	var internal = <?= json_encode($internal) ?>;
	var first = true;

	function showParameters(ctrl) {
		var c, p, df, cp;
		var ar, label, value, key, dt, defaultVal;

		currentParams = {}; // reset;

		if(ctrl && ctrl.form) {
			f = ctrl.form;
		} else {
			f = document.forms['mutate'];
			if(!f) return;
		}

		tr = (document.getElementById) ? document.getElementById('displayparamrow') : document.all['displayparamrow'];

		// check if codemirror is used
		var props = typeof myCodeMirrors != "undefined" && typeof myCodeMirrors['properties'] != "undefined" ? myCodeMirrors['properties'].getValue() : f.properties.value;

		// convert old schemed setup parameters
		if(!IsJsonString(props)) {
			dp = props ? props.match(/([^&=]+)=(.*?)(?=&[^&=]+=|$)/g) : ""; // match &paramname=
			if(!dp) tr.style.display = 'none';
			else {
				for(p = 0; p < dp.length; p++) {
					dp[p] = (dp[p] + '').replace(/^\s|\s$/, ""); // trim
					ar = dp[p].match(/(?:[^\=]|==)+/g); // split by =, not by ==
					key = ar[0];        // param
					ar = (ar[1] + '').split(";");
					label = ar[0];	    // label
					dt = ar[1];	    // data type
					value = decode((ar[2]) ? ar[2] : '');

					// convert values to new json-format
					if(key && (dt === 'menu' || dt === 'list' || dt === 'list-multi' || dt === 'checkbox' || dt === 'radio')) {
						defaultVal = decode((ar[4]) ? ar[4] : ar[3]);
						desc = decode((ar[5]) ? ar[5] : "");
						currentParams[key] = [];
						currentParams[key][0] = {"label": label, "type": dt, "value": ar[3], "options": value, "default": defaultVal, "desc": desc};
					} else if(key) {
						defaultVal = decode((ar[3]) ? ar[3] : ar[2]);
						desc = decode((ar[4]) ? ar[4] : "");
						currentParams[key] = [];
						currentParams[key][0] = {"label": label, "type": dt, "value": value, "default": defaultVal, "desc": desc};
					}
				}
			}
		} else {
			currentParams = JSON.parse(props);
		}

		t = '<table width="100%" class="displayparams grid"><thead><tr><td><?= $_lang['parameter'] ?></td><td><?= $_lang['value'] ?></td><td style="text-align:right;white-space:nowrap"><?= $_lang["set_default"] ?> </td></tr></thead>';

		try {
			var type, options, found, info, sd;
			var ll, ls, sets = [];

			Object.keys(currentParams).forEach(function(key) {

				if(key === 'internal' || currentParams[key][0]['label'] == undefined) return;

				cp = currentParams[key][0];
				type = cp['type'];
				value = cp['value'];
				defaultVal = cp['default'];
				label = cp['label'] != undefined ? cp['label'] : key;
				desc = cp['desc'] + '';
				options = cp['options'] != undefined ? cp['options'] : '';

				ll = [];
				ls = [];
				if(options.indexOf('==') > -1) {
					// option-format: label==value||label==value
					sets = options.split("||");
					for(i = 0; i < sets.length; i++) {
						split = sets[i].split("==");
						ll[i] = split[0];
						ls[i] = split[1] != undefined ? split[1] : split[0];
					}
				} else {
					// option-format: value,value
					ls = options.split(",");
					ll = ls;
				}

				key   = key.replace(/\"/g, '&quot;');
				value = value.replace(/\"/g, '&quot;');

				switch(type) {
					case 'int':
						c = '<input type="text" name="prop_' + key + '" value="' + value + '" size="30" onchange="setParameter(\'' + key + '\',\'' + type + '\',this)" />';
						break;
					case 'menu':
						c = '<select name="prop_' + key + '" onchange="setParameter(\'' + key + '\',\'' + type + '\',this)">';
						if(currentParams[key] === options) currentParams[key] = ls[0]; // use first list item as default
						for(i = 0; i < ls.length; i++) {
							c += '<option value="' + ls[i] + '"' + ((ls[i] === value) ? ' selected="selected"' : '') + '>' + ll[i] + '</option>';
						}
						c += '</select>';
						break;
					case 'list':
						if(currentParams[key] === options) currentParams[key] = ls[0]; // use first list item as default
						c = '<select name="prop_' + key + '" size="' + ls.length + '" onchange="setParameter(\'' + key + '\',\'' + type + '\',this)">';
						for(i = 0; i < ls.length; i++) {
							c += '<option value="' + ls[i] + '"' + ((ls[i] === value) ? ' selected="selected"' : '') + '>' + ll[i] + '</option>';
						}
						c += '</select>';
						break;
					case 'list-multi':
						// value = typeof ar[3] !== 'undefined' ? (ar[3] + '').replace(/^\s|\s$/, "") : '';
						arrValue = value.split(",");
						if(currentParams[key] === options) currentParams[key] = ls[0]; // use first list item as default
						c = '<select name="prop_' + key + '" size="' + ls.length + '" multiple="multiple" onchange="setParameter(\'' + key + '\',\'' + type + '\',this)">';
						for(i = 0; i < ls.length; i++) {
							if(arrValue.length) {
								found = false;
								for(j = 0; j < arrValue.length; j++) {
									if(ls[i] === arrValue[j]) {
										found = true;
									}
								}
								if(found === true) {
									c += '<option value="' + ls[i] + '" selected="selected">' + ll[i] + '</option>';
								} else {
									c += '<option value="' + ls[i] + '">' + ll[i] + '</option>';
								}
							} else {
								c += '<option value="' + ls[i] + '">' + ll[i] + '</option>';
							}
						}
						c += '</select>';
						break;
					case 'checkbox':
						lv = (value + '').split(",");
						c = '';
						for(i = 0; i < ls.length; i++) {
							c += '<label><input type="checkbox" name="prop_' + key + '[]" value="' + ls[i] + '"' + ((contains(lv, ls[i]) == true) ? ' checked="checked"' : '') + ' onchange="setParameter(\'' + key + '\',\'' + type + '\',this)" />' + ll[i] + '</label>&nbsp;';
						}
						break;
					case 'radio':
						c = '';
						for(i = 0; i < ls.length; i++) {
							c += '<label><input type="radio" name="prop_' + key + '" value="' + ls[i] + '"' + ((ls[i] === value) ? ' checked="checked"' : '') + ' onchange="setParameter(\'' + key + '\',\'' + type + '\',this)" />' + ll[i] + '</label>&nbsp;';
						}
						break;
					case 'textarea':
						c = '<textarea name="prop_' + key + '" rows="4" onchange="setParameter(\'' + key + '\',\'' + type + '\',this)">' + value + '</textarea>';
						break;
					default:  // string
						c = '<input type="text" name="prop_' + key + '" value="' + value + '" onchange="setParameter(\'' + key + '\',\'' + type + '\',this)" />';
						break;
				}

				info = '';
				info += desc ? '<br/><small>' + desc + '</small>' : '';
				sd = defaultVal != undefined ? '<a title="<?= $_lang["set_default"] ?>" href="javascript:;" class="btn btn-primary" onclick="setDefaultParam(\'' + key + '\',1);return false;"><i class="fa fa-refresh"></i></a>' : '';

				t += '<tr><td class="labelCell" width="20%"><span class="paramLabel">' + label + '</span><span class="paramDesc">' + info + '</span></td><td class="inputCell relative" width="74%">' + c + '</td><td style="text-align: center">' + sd + '</td></tr>';
			});

			t += '</table>';

		} catch(e) {
			t = e + "\n\n" + props;
		}

		td = (document.getElementById) ? document.getElementById('displayparams') : document.all['displayparams'];
		td.innerHTML = t;
		tr.style.display = '';

		implodeParameters();
	}

	function setParameter(key, dt, ctrl) {
		var v;
		var arrValues, cboxes = [];
		if(!ctrl) return null;
		switch(dt) {
			case 'int':
				ctrl.value = parseInt(ctrl.value);
				if(isNaN(ctrl.value)) ctrl.value = 0;
				v = ctrl.value;
				break;
			case 'menu':
			case 'list':
				v = ctrl.options[ctrl.selectedIndex].value;
				break;
			case 'list-multi':
				arrValues = [];
				for(var i = 0; i < ctrl.options.length; i++) {
					if(ctrl.options[i].selected) {
						arrValues.push(ctrl.options[i].value);
					}
				}
				v = arrValues.toString();
				break;
			case 'checkbox':
				arrValues = [];
				cboxes = document.getElementsByName(ctrl.name);
				for(var i = 0; i < cboxes.length; i++) {
					if(cboxes[i].checked) {
						arrValues.push(cboxes[i].value);
					}
				}
				v = arrValues.toString();
				break;
			default:
				v = ctrl.value + '';
				break;
		}
		currentParams[key][0]['value'] = v;
		implodeParameters();
	}

	// implode parameters
	function implodeParameters() {
		var stringified = JSON.stringify(currentParams, null, 2);
		if(typeof myCodeMirrors != "undefined") {
			myCodeMirrors['properties'].setValue(stringified);
		} else {
			f.properties.value = stringified;
		}
		if(first) {
			documentDirty = false;
			first = false;
		}
		;
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

	/**
	 * @return {boolean}
	 */
	function IsJsonString(str) {
		try {
			JSON.parse(str);
		} catch(e) {
			return false;
		}
		return true;
	}

	function setDefaultParam(key, show) {
		if(typeof currentParams[key][0]['default'] != 'undefined') {
			currentParams[key][0]['value'] = currentParams[key][0]['default'];
			if(show) {
				implodeParameters();
				showParameters();
			}
		}
	}

	function setDefaults() {
		var keys = Object.keys(currentParams);
		var last = keys[keys.length - 1],
			show;
		Object.keys(currentParams).forEach(function(key) {
			show = key === last ? 1 : 0;
			setDefaultParam(key, show);
		});
	}

	function contains(a, obj) {
		var i = a.length;
		while(i--) {
			if(a[i] === obj) {
				return true;
			}
		}
		return false;
	}

	// Resource browser
	function OpenServerBrowser(url, width, height) {
		var iLeft = (screen.width - width) / 2;
		var iTop = (screen.height - height) / 2;
		var sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes";
		sOptions += ",width=" + width;
		sOptions += ",height=" + height;
		sOptions += ",left=" + iLeft;
		sOptions += ",top=" + iTop;
		var oWindow = window.open(url, "FCKBrowseWindow", sOptions);
	}

	function BrowseServer() {
		var w = screen.width * 0.7;
		var h = screen.height * 0.7;
		OpenServerBrowser("<?= MODX_MANAGER_URL;?>media/browser/<?= $which_browser;?>/browser.php?Type=images", w, h);
	}

	function SetUrl(url, width, height, alt) {
		document.mutate.icon.value = url;
	}

	document.addEventListener('DOMContentLoaded', function() {
		var h1help = document.querySelector('h1 > .help');
		h1help.onclick = function() {
			document.querySelector('.element-edit-message').classList.toggle('show')
		}
	});

</script>

<form name="mutate" method="post" action="index.php" id="mutate" class="module">
	<?php
	// invoke OnModFormPrerender event
	$evtOut = $modx->invokeEvent('OnModFormPrerender', array('id' => $id));
	if(is_array($evtOut)) {
		echo implode('', $evtOut);
	}

	// Prepare internal params & info-tab via parseDocBlock
	$modulecode = isset($content['modulecode']) ? $modx->db->escape($content['modulecode']) : '';
	$docBlock = $modx->parseDocBlockFromString($modulecode);
	$docBlockList = $modx->convertDocBlockIntoList($docBlock);
	$internal = array();
	?>
    <input type="hidden" name="a" value="109">
	<input type="hidden" name="id" value="<?= $content['id'] ?>">
	<input type="hidden" name="mode" value="<?= $modx->manager->action ?>">

	<h1>
		<i class="<?= ($content['icon'] != '' ? $content['icon'] : $_style['icons_module']) ?>"></i><?= ($content['name'] ? $content['name'] . '<small>(' . $content['id'] . ')</small>' : $_lang['new_module']) ?><i class="fa fa-question-circle help"></i>
	</h1>

	<?= $_style['actionbuttons']['dynamic']['element'] ?>

	<div class="container element-edit-message">
		<div class="alert alert-info"><?= $_lang['module_msg'] ?></div>
	</div>

	<div class="tab-pane" id="modulePane">
		<script type="text/javascript">
			tp = new WebFXTabPane(document.getElementById("modulePane"), <?= ($modx->config['remember_last_tab'] == 1 ? 'true' : 'false') ?> );
		</script>

		<!-- General -->
		<div class="tab-page" id="tabModule">
			<h2 class="tab"><?= $_lang['settings_general'] ?></h2>
			<script type="text/javascript">tp.addTabPage(document.getElementById("tabModule"));</script>
			<div class="container container-body">
				<div class="form-group">
					<div class="row form-row">
						<label class="col-md-3 col-lg-2"><?= $_lang['module_name'] ?></label>
						<div class="col-md-9 col-lg-10">
							<div class="form-control-name clearfix">
								<input name="name" type="text" maxlength="100" value="<?= $modx->htmlspecialchars($content['name']) ?>" class="form-control form-control-lg" onchange="documentDirty=true;" />
								<?php if($modx->hasPermission('save_role')): ?>
									<label class="custom-control" title="<?= $_lang['lock_module'] . "\n" . $_lang['lock_module_msg'] ?>" tooltip>
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
						<label class="col-md-3 col-lg-2"><?= $_lang['module_desc'] ?></label>
						<div class="col-md-9 col-lg-10">
							<input name="description" type="text" maxlength="255" value="<?= $content['description'] ?>" class="form-control" onchange="documentDirty=true;" />
						</div>
					</div>
					<div class="row form-row">
						<label class="col-md-3 col-lg-2"><?= $_lang['existing_category'] ?></label>
						<div class="col-md-9 col-lg-10">
							<select name="categoryid" class="form-control" onchange="documentDirty=true;">
								<option>&nbsp;</option>
								<?php
								include_once(MODX_MANAGER_PATH . 'includes/categories.inc.php');
								foreach(getCategories() as $n => $v) {
									echo "\t\t\t" . '<option value="' . $v['id'] . '"' . ($content['category'] == $v['id'] ? ' selected="selected"' : '') . '>' . $modx->htmlspecialchars($v['category']) . "</option>\n";
								}
								?>
							</select>
						</div>
					</div>
					<div class="row form-row">
						<label class="col-md-3 col-lg-2"><?= $_lang['new_category'] ?></label>
						<div class="col-md-9 col-lg-10">
							<input name="newcategory" type="text" maxlength="45" value="" class="form-control" onchange="documentDirty=true;" />
						</div>
					</div>
					<div class="row form-row">
						<label class="col-md-3 col-lg-2"><?= $_lang['icon'] ?>
							<small class="text-muted"><?= $_lang["icon_description"] ?></small>
						</label>
						<div class="col-md-9 col-lg-10">
							<div class="input-group">
								<input type="text" maxlength="255" name="icon" value="<?= $content['icon'] ?>" class="form-control" onchange="documentDirty=true;" />
							</div>
						</div>
					</div>
					<div class="row form-row">
						<label class="col-md-3 col-lg-2" for="enable_resource"><input name="enable_resource" id="enable_resource" title="<?= $_lang['enable_resource'] ?>" type="checkbox"<?= ($content['enable_resource'] == 1 ? ' checked="checked"' : '') ?> onclick="documentDirty=true;" /> <span title="<?= $_lang['enable_resource'] ?>"><?= $_lang["element"] ?></span></label>
						<div class="col-md-9 col-lg-10">
							<input name="resourcefile" type="text" maxlength="255" value="<?= $content['resourcefile'] ?>" class="form-control" onchange="documentDirty=true;" />
						</div>
					</div>
				</div>
				<div class="form-group">
					<div class="form-row">
						<label for="disabled"><input name="disabled" id="disabled" type="checkbox" value="on"<?= ($content['disabled'] == 1 ? ' checked="checked"' : '') ?> />
							<?= ($content['disabled'] == 1 ? '<span class="text-danger">' . $_lang['module_disabled'] . '</span>' : $_lang['module_disabled']) ?></label>
					</div>
					<div class="form-row">
						<label for="parse_docblock">
							<input name="parse_docblock" id="parse_docblock" type="checkbox" value="1"<?= ($modx->manager->action == 107 ? ' checked="checked"' : '') ?> /> <?= $_lang['parse_docblock'] ?></label>
						<small class="form-text text-muted"><?= $_lang['parse_docblock_msg'] ?></small>
					</div>
				</div>
			</div>

			<!-- PHP text editor start -->
			<div class="navbar navbar-editor">
				<span><?= $_lang['module_code'] ?></span>
			</div>
			<div class="section-editor clearfix">
				<textarea dir="ltr" class="phptextarea" name="post" rows="20" wrap="soft" onchange="documentDirty=true;"><?= (isset($content['post']) ? $modx->htmlspecialchars($content['post']) : $modx->htmlspecialchars($content['modulecode'])) ?></textarea>
			</div>
			<!-- PHP text editor end -->
		</div>

		<!-- Configuration -->
		<div class="tab-page" id="tabConfig">
			<h2 class="tab"><?= $_lang["settings_config"] ?></h2>
			<script type="text/javascript">tp.addTabPage(document.getElementById("tabConfig"));</script>
			<div class="container container-body">
				<div class="form-group">
					<a href="javascript:;" class="btn btn-primary" onclick="setDefaults(this);return false;"><?= $_lang['set_default_all'] ?></a>
				</div>
				<div id="displayparamrow">
					<div id="displayparams"></div>
				</div>
			</div>
		</div>

		<!-- Properties -->
		<div class="tab-page" id="tabParams">
			<h2 class="tab"><?= $_lang['settings_properties'] ?></h2>
			<script type="text/javascript">tp.addTabPage(document.getElementById("tabParams"));</script>
			<div class="container container-body">
				<div class="form-group">
					<div class="row form-row">
						<label class="col-md-3 col-lg-2"><?= $_lang['guid'] ?></label>
						<div class="col-md-9 col-lg-10">
							<input name="guid" type="text" maxlength="32" value="<?= ($modx->manager->action == 107 ? createGUID() : $content['guid']) ?>" class="form-control" onchange="documentDirty=true;" />
							<small class="form-text text-muted"><?= $_lang['import_params_msg'] ?></small>
						</div>
					</div>
					<div class="row form-row">
						<label class="col-md-3 col-lg-2" for="enable_sharedparams">
							<input name="enable_sharedparams" id="enable_sharedparams" type="checkbox"<?= ($content['enable_sharedparams'] == 1 ? ' checked="checked"' : '') ?> onclick="documentDirty=true;" /> <?= $_lang['enable_sharedparams'] ?></label>
						<div class="col-md-9 col-lg-10">
							<small class="form-text text-muted"><?= $_lang['enable_sharedparams_msg'] ?></small>
						</div>
					</div>
				</div>
				<div class="form-group">
					<a href="javascript:;" class="btn btn-primary" onclick="tp.pages[1].select();showParameters(this);return false;"><?= $_lang['update_params'] ?></a>
				</div>
			</div>
			<!-- HTML text editor start -->
			<div class="section-editor clearfix">
				<textarea dir="ltr" name="properties" class="phptextarea" rows="20" wrap="soft" onChange="showParameters(this);documentDirty=true;"><?= $content['properties'] ?></textarea>
			</div>
			<!-- HTML text editor end -->
		</div>
		<?php if($modx->manager->action == '108'): ?>
			<!-- Dependencies -->
			<div class="tab-page" id="tabDepend">
				<h2 class="tab"><?= $_lang['settings_dependencies'] ?></h2>
				<script type="text/javascript">tp.addTabPage(document.getElementById("tabDepend"));</script>
				<div class="container container-body">
					<p><?= $_lang['module_viewdepend_msg'] ?></p>
					<div class="form-group clearfix">
						<a class="btn btn-primary" href="javascript:;" onclick="loadDependencies();return false;">
							<i class="<?= $_style["actions_save"] ?>"></i> <?= $_lang['manage_depends'] ?></a>
					</div>
					<?php
					$ds = $modx->db->select("smd.id, COALESCE(ss.name,st.templatename,sv.name,sc.name,sp.name,sd.pagetitle) AS name, 
					CASE smd.type
						WHEN 10 THEN 'Chunk'
						WHEN 20 THEN 'Document'
						WHEN 30 THEN 'Plugin'
						WHEN 40 THEN 'Snippet'
						WHEN 50 THEN 'Template'
						WHEN 60 THEN 'TV'
					END AS type", "{$tbl_site_module_depobj} AS smd 
						LEFT JOIN {$tbl_site_htmlsnippets} AS sc ON sc.id = smd.resource AND smd.type = 10 
						LEFT JOIN {$tbl_site_content} AS sd ON sd.id = smd.resource AND smd.type = 20
						LEFT JOIN {$tbl_site_plugins} AS sp ON sp.id = smd.resource AND smd.type = 30
						LEFT JOIN {$tbl_site_snippets} AS ss ON ss.id = smd.resource AND smd.type = 40
						LEFT JOIN {$tbl_site_templates} AS st ON st.id = smd.resource AND smd.type = 50
						LEFT JOIN {$tbl_site_tmplvars} AS sv ON sv.id = smd.resource AND smd.type = 60", "smd.module='{$id}'", 'smd.type,name');

					include_once MODX_MANAGER_PATH . "includes/controls/datagrid.class.php";
					$grd = new DataGrid('', $ds, 0); // set page size to 0 t show all items
					$grd->noRecordMsg = $_lang['no_records_found'];
					$grd->cssClass = 'grid';
					$grd->columnHeaderClass = 'gridHeader';
					$grd->itemClass = 'gridItem';
					$grd->altItemClass = 'gridAltItem';
					$grd->columns = $_lang['element_name'] . " ," . $_lang['type'];
					$grd->fields = "name,type";
					echo $grd->render();
					?>
				</div>
			</div>
		<?php endif; ?>

		<!-- access permission -->
		<div class="tab-page" id="tabPermissions">
			<h2 class="tab"><?= $_lang['access_permissions'] ?></h2>
			<script type="text/javascript">tp.addTabPage(document.getElementById("tabPermissions"));</script>
			<div class="container container-body">
				<?php if($use_udperms == 1) : ?>
					<?php
					// fetch user access permissions for the module
					$rs = $modx->db->select('usergroup', $tbl_site_module_access, "module='{$id}'");
					$groupsarray = $modx->db->getColumn('usergroup', $rs);

					if($modx->hasPermission('access_permissions')) {
						?>
						<!-- User Group Access Permissions -->
						<script type="text/javascript">
							function makePublic(b) {
								var notPublic = false;
								var f = document.forms['mutate'];
								var chkpub = f['chkallgroups'];
								var chks = f['usrgroups[]'];
								if(!chks && chkpub) {
									chkpub.checked = true;
									return false;
								} else if(!b && chkpub) {
									if(!chks.length) notPublic = chks.checked;
									else for(i = 0; i < chks.length; i++) if(chks[i].checked) notPublic = true;
									chkpub.checked = !notPublic;
								} else {
									if(!chks.length) chks.checked = (b) ? false : chks.checked;
									else for(i = 0; i < chks.length; i++) if(b) chks[i].checked = false;
									chkpub.checked = true;
								}
							}
						</script>
						<p><?= $_lang['module_group_access_msg'] ?></p>
						<?php
					}
					$chk = '';
					$rs = $modx->db->select('name, id', $tbl_membergroup_names, '', 'name');
					while($row = $modx->db->getRow($rs)) {
						$groupsarray = is_numeric($id) && $id > 0 ? $groupsarray : array();
						$checked = in_array($row['id'], $groupsarray);
						if($modx->hasPermission('access_permissions')) {
							if($checked) {
								$notPublic = true;
							}
							$chks .= '<label><input type="checkbox" name="usrgroups[]" value="' . $row['id'] . '"' . ($checked ? ' checked="checked"' : '') . ' onclick="makePublic(false)" /> ' . $row['name'] . "</label><br />\n";
						} else {
							if($checked) {
								$chks = '<input type="hidden" name="usrgroups[]"  value="' . $row['id'] . '" />' . "\n" . $chks;
							}
						}
					}
					if($modx->hasPermission('access_permissions')) {
						$chks = '<label><input type="checkbox" name="chkallgroups"' . (!$notPublic ? ' checked="checked"' : '') . ' onclick="makePublic(true)" /><span class="warning"> ' . $_lang['all_usr_groups'] . '</span></label><br />' . "\n" . $chks;
					}
					echo $chks;
					?>
				<?php endif; ?>
			</div>
		</div>

		<!-- docBlock Info -->
		<div class="tab-page" id="tabDocBlock">
			<h2 class="tab"><?= $_lang['information'] ?></h2>
			<script type="text/javascript">tp.addTabPage(document.getElementById("tabDocBlock"));</script>
			<div class="container container-body">
				<?= $docBlockList ?>
			</div>
		</div>

		<input type="submit" name="save" style="display:none;">
		<?php
		// invoke OnModFormRender event
		$evtOut = $modx->invokeEvent('OnModFormRender', array('id' => $id));
		if(is_array($evtOut)) {
			echo implode('', $evtOut);
		}
		?>
</form>
<script type="text/javascript">setTimeout('showParameters();', 10);</script>
