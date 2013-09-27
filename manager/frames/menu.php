<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if (!array_key_exists('mail_check_timeperiod', $modx->config) || !is_numeric($modx->config['mail_check_timeperiod'])) {
	$modx->config['mail_check_timeperiod'] = 5;
}
$modx_textdir = isset($modx_textdir) ? $modx_textdir : null;
if ($manager_theme) $manager_theme .= '/';
$mxla = $modx_lang_attribute ? $modx_lang_attribute : 'en';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html <?php echo ($modx_textdir ? 'dir="rtl" lang="' : 'lang="').$mxla.'" xml:lang="'.$mxla.'"'; ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $modx_manager_charset?>" />
	<title>nav</title>
	<link rel="stylesheet" type="text/css" href="media/style/<?php echo $manager_theme?>style.css" />
	<script src="media/script/mootools/mootools.js" type="text/javascript"></script>
	<script src="media/script/mootools/moodx.js" type="text/javascript"></script>
    <script type="text/javascript" src="media/script/session.js"></script>
	<script type="text/javascript">
	// TREE FUNCTIONS - FRAME
	// These functions affect the tree frame and any items that may be pointing to the tree.
	var currentFrameState = 'open';
	var defaultFrameWidth = '<?php echo !$modx_textdir ? '260,*' : '*,260'?>';
	var userDefinedFrameWidth = '<?php echo !$modx_textdir ? '260,*' : '*,260'?>';

	var workText;
	var buildText;

	// Create the AJAX mail update object before requesting it
	var updateMailerAjx = new Ajax('index.php', {method:'post', postBody:'updateMsgCount=true', onComplete:showResponse});
	function updateMail(now) {
		try {
			// if 'now' is set, runs immediate ajax request (avoids problem on initial loading where periodical waits for time period before making first request)
			if (now)
				updateMailerAjx.request();
			return false;
		} catch(oException) {
			// Delay first run until we're ready...
			xx=updateMail.delay(1000 * 60,'',true);
		}
	};

	function showResponse(request) {
		var counts = request.split(',');
		var elm = $('msgCounter');
		if (elm) elm.innerHTML ='(' + counts[0] + ' / ' + counts[1] + ')';
		var elm = $('newMail');
		if (elm) elm.style.display = counts[0] >0 ? 'inline' :  'none';
	}

	window.addEvent('load', function() {
		updateMail(true); // First run update
		updateMail.periodical(<?php echo $modx->config['mail_check_timeperiod'] * 1000 ?>, '', true); // Periodical Updater
		if(top.__hideTree) {
			// display toc icon
			var elm = $('tocText');
			if(elm) elm.innerHTML = "<a href='#' onclick='defaultTreeFrame();'><img src='<?php echo $_style['show_tree']?>' alt='<?php echo $_lang['show_tree']?>' width='16' height='16' /></a>";
		}
	});

	function hideTreeFrame() {
		userDefinedFrameWidth = parent.document.getElementsByTagName("FRAMESET").item(1).cols;
		currentFrameState = 'closed';
		try {
			var elm = $('tocText');
			if(elm) elm.innerHTML = "<a href='#' onclick='defaultTreeFrame();'><img src='<?php echo $_style['show_tree']?>' alt='<?php echo $_lang['show_tree']?>' width='16' height='16' /></a>";
			parent.document.getElementsByTagName("FRAMESET").item(1).cols = '<?php echo (!$modx_textdir ? '0,*' : '*,0')?>';
			top.__hideTree = true;
		} catch(oException) {
			x=window.setTimeout('hideTreeFrame()', 1000);
		}
	}

	function defaultTreeFrame() {
		userDefinedFrameWidth = defaultFrameWidth;
		currentFrameState = 'open';
		try {
			var elm = $('tocText');
			if(elm) elm.innerHTML = "";
			parent.document.getElementsByTagName("FRAMESET").item(1).cols = defaultFrameWidth;
			top.__hideTree = false;
		} catch(oException) {
			z=window.setTimeout('defaultTreeFrame()', 1000);
		}
	}

	// TREE FUNCTIONS - Expand/ Collapse
	// These functions affect the expanded/collapsed state of the tree and any items that may be pointing to it
	function expandTree() {
		try {
			parent.tree.d.openAll();  // dtree
		} catch(oException) {
			zz=window.setTimeout('expandTree()', 1000);
		}
	}

	function collapseTree() {
		try {
			parent.tree.d.closeAll();  // dtree
		} catch(oException) {
			yy=window.setTimeout('collapseTree()', 1000);
		}
	}

	// GENERAL FUNCTIONS - Refresh
	// These functions are used for refreshing the tree or menu
	function reloadtree() {
		var elm = $('buildText');
		if (elm) {
			elm.innerHTML = "&nbsp;&nbsp;<img src='<?php echo $_style['icons_loading_doc_tree']?>' width='16' height='16' />&nbsp;<?php echo $_lang['loading_doc_tree']?>";
			elm.style.display = 'block';
		}
		top.tree.saveFolderState(); // save folder state
		setTimeout('top.tree.restoreTree()',200);
	}

	function reloadmenu() {
<?php if($manager_layout==0) { ?>
		var elm = $('buildText');
		if (elm) {
			elm.innerHTML = "&nbsp;&nbsp;<img src='<?php echo $_style['icons_working']?>' width='16' height='16' />&nbsp;<?php echo $_lang['loading_menu']?>";
			elm.style.display = 'block';
		}
		parent.mainMenu.location.reload();
<?php } ?>
	}

	function startrefresh(rFrame){
		if(rFrame==1){
			x=window.setTimeout('reloadtree()',500);
		}
		if(rFrame==2) {
			x=window.setTimeout('reloadmenu()',500);
		}
		if(rFrame==9) {
			x=window.setTimeout('reloadmenu()',500);
			y=window.setTimeout('reloadtree()',500);
		}
		if(rFrame==10) {
			window.top.location.href = "../<?php echo MGR_DIR;?>";
		}
	}

	// GENERAL FUNCTIONS - Work
	// These functions are used for showing the user the system is working
	function work() {
		var elm = $('workText');
		if (elm) elm.innerHTML = "&nbsp;<img src='<?php echo $_style['icons_working']?>' width='16' height='16' />&nbsp;<?php echo $_lang['working']?>";
		else w=window.setTimeout('work()', 50);
	}

	function stopWork() {
		var elm = $('workText');
		if (elm) elm.innerHTML = "";
		else  ww=window.setTimeout('stopWork()', 50);
	}

	// GENERAL FUNCTIONS - Remove locks
	// This function removes locks on documents, templates, parsers, and snippets
	function removeLocks() {
		if(confirm("<?php echo $_lang['confirm_remove_locks']?>")==true) {
			top.main.document.location.href="index.php?a=67";
		}
	}

	function showWin() {
		window.open('../');
	}

	function stopIt() {
		top.mainMenu.stopWork();
	}

	function openCredits() {
		parent.main.document.location.href = "index.php?a=18";
		xwwd = window.setTimeout('stopIt()', 2000);
	}

	function NavToggle(element) {
		// This gives the active tab its look
		var navid = document.getElementById('nav');
		var navs = navid.getElementsByTagName('li');
		var navsCount = navs.length;
		for(j = 0; j < navsCount; j++) {
			active = (navs[j].id == element.parentNode.id) ? "active" : "";
			navs[j].className = active;
		}

		// remove focus from top nav
		if(element) element.blur();
	}
	</script>
	<!--[if lt IE 7]>
	<style type="text/css">
	body { behavior: url(media/script/forIE/htcmime.php?file=csshover.htc) }
	img { behavior: url(media/script/forIE/htcmime.php?file=pngbehavior.htc); }
	</style>
	<![endif]-->
</head>

<body id="topMenu" class="<?php echo $modx_textdir ? 'rtl':'ltr'?>">

<div id="tocText"<?php echo $modx_textdir ? ' class="tocTextRTL"' : '' ?>></div>
<div id="topbar">
<div id="topbar-container">
	<div id="statusbar">
		<span id="buildText"></span>
		<span id="workText"></span>
	</div>

	<div id="supplementalNav">
	<?php echo $modx->getLoginUserName(). ($modx->hasPermission('change_password') ? ': <a onclick="this.blur();" href="index.php?a=28" target="main">'.$_lang['change_password'].'</a>'."\n" : "\n") ?>
<?php if($modx->hasPermission('messages')) { ?>
	| <span id="newMail"><a href="index.php?a=10" title="<?php echo $_lang['you_got_mail']?>" target="main"> <img src="<?php echo $_style['icons_mail']?>" width="16" height="16" /></a></span>
	<a onclick="this.blur();" href="index.php?a=10" target="main"><?php echo $_lang['messages']?> <span id="msgCounter">( ? / ? )</span></a>
<?php }
if($modx->hasPermission('help')) { ?>
	| <a href="index.php?a=9" target="main"><?php echo $_lang['help']?></a>
<?php } ?>
	| <a href="index.php?a=8" target="_top"><?php echo $_lang['logout']?></a>
	| <span title="<?php echo $site_name ?> &ndash; <?php echo $modx->getVersionData('full_appname') ?>"><?php echo $modx->getVersionData('version') ?></span>&nbsp;
	<!-- close #supplementalNav --></div>
</div>
</div>

<form name="menuForm" action="l4mnu.php" class="clear">
    <input type="hidden" name="sessToken" id="sessTokenInput" value="<?php echo md5(session_id());?>" />
<div id="Navcontainer">
<div id="divNav">
	<ul id="nav">
<?php
// Concatenate menu items based on permissions

// Site Menu
$sitemenu = array();
// home
$sitemenu[] = '<li><a onclick="this.blur();" href="index.php?a=2" target="main">'.$_lang['home'].'</a></li>';
// preview
$sitemenu[] = '<li><a onclick="this.blur();" href="../" target="_blank">'.$_lang['preview'].'</a></li>';
// clear-cache
$sitemenu[] = '<li><a onclick="this.blur();" href="index.php?a=26" target="main">'.$_lang['refresh_site'].'</a></li>';
// search
$sitemenu[] = '<li><a onclick="this.blur();" href="index.php?a=71" target="main">'.$_lang['search'].'</a></li>';
if ($modx->hasPermission('new_document')) {
	// new-document
	$sitemenu[] = '<li><a onclick="this.blur();" href="index.php?a=4" target="main">'.$_lang['add_resource'].'</a></li>';
	// new-weblink
	$sitemenu[] = '<li><a onclick="this.blur();" href="index.php?a=72" target="main">'.$_lang['add_weblink'].'</a></li>';
}

// Elements Menu
$resourcemenu = array();
if($modx->hasPermission('new_template') || $modx->hasPermission('edit_template') || $modx->hasPermission('new_snippet') || $modx->hasPermission('edit_snippet') || $modx->hasPermission('new_chunk') || $modx->hasPermission('edit_chunk') || $modx->hasPermission('new_plugin') || $modx->hasPermission('edit_plugin')) {
	// Elements
	$resourcemenu[] = '<li><a onclick="this.blur();" href="index.php?a=76" target="main">'.$_lang['element_management'].'</a></li>';
}
if($modx->hasPermission('file_manager')) {
	// Manage-Files
	$resourcemenu[] = '<li><a onclick="this.blur();" href="index.php?a=31" target="main">'.$_lang['manage_files'].'</a></li>';
}
if($modx->hasPermission('manage_metatags') && $modx->config['show_meta'] == 1) {
	// Manage-Metatags
	$resourcemenu[] = '<li><a onclick="this.blur();" href="index.php?a=81" target="main">'.$_lang['manage_metatags'].'</a></li>';
}

// Modules Menu Items
$modulemenu = array();
if($modx->hasPermission('new_module') || $modx->hasPermission('edit_module')) {
	// manage-modules
	$modulemenu[] = '<li><a onclick="this.blur();" href="index.php?a=106" target="main">'.$_lang['module_management'].'</a></li>';
}
if($modx->hasPermission('exec_module')) {
	// Each module
	if ($_SESSION['mgrRole'] != 1) {
		// Display only those modules the user can execute
		$rs = $modx->db->query('SELECT DISTINCT sm.id, sm.name, mg.member
				FROM '.$modx->getFullTableName('site_modules').' AS sm
				LEFT JOIN '.$modx->getFullTableName('site_module_access').' AS sma ON sma.module = sm.id
				LEFT JOIN '.$modx->getFullTableName('member_groups').' AS mg ON sma.usergroup = mg.user_group
				WHERE (mg.member IS NULL OR mg.member = '.$modx->getLoginUserID().') AND sm.disabled != 1');
	} else {
		// Admins get the entire list
		$rs = $modx->db->select('*', $modx->getFullTableName('site_modules'), 'disabled != 1');
	}
	while ($content = $modx->db->getRow($rs)) {
		$modulemenu[] = '<li><a onclick="this.blur();" href="index.php?a=112&amp;id='.$content['id'].'" target="main">'.$content['name'].'</a></li>';
	}
}

// Security menu items (users)
$securitymenu = array();
if($modx->hasPermission('edit_user')) {
	// manager-users
	$securitymenu[] = '<li><a onclick="this.blur();" href="index.php?a=75" target="main">'.$_lang['user_management_title'].'</a></li>';
}
if($modx->hasPermission('edit_web_user')) {
	// web-users
	$securitymenu[] = '<li><a onclick="this.blur();" href="index.php?a=99" target="main">'.$_lang['web_user_management_title'].'</a></li>';
}
if($modx->hasPermission('new_role') || $modx->hasPermission('edit_role') || $modx->hasPermission('delete_role')) {
	// roles
	$securitymenu[] = '<li><a onclick="this.blur();" href="index.php?a=86" target="main">'.$_lang['role_management_title'].'</a></li>';
}
if($modx->hasPermission('access_permissions')) {
	// manager-perms
	$securitymenu[] = '<li><a onclick="this.blur();" href="index.php?a=40" target="main">'.$_lang['manager_permissions'].'</a></li>';
}
if($modx->hasPermission('web_access_permissions')) {
	// web-user-perms
	$securitymenu[] = '<li><a onclick="this.blur();" href="index.php?a=91" target="main">'.$_lang['web_permissions'].'</a></li>';
}

// Tools Menu
$toolsmenu = array();
if($modx->hasPermission('bk_manager')) {
	// backup-mgr
	$toolsmenu[] = '<li><a onclick="this.blur();" href="index.php?a=93" target="main">'.$_lang['bk_manager'].'</a></li>';
}
if($modx->hasPermission('remove_locks')) {
	// unlock-pages
	$toolsmenu[] = '<li><a onclick="this.blur();" href="javascript:removeLocks();">'.$_lang['remove_locks'].'</a></li>';
}
if($modx->hasPermission('import_static')) {
	// import-html
	$toolsmenu[] = '<li><a onclick="this.blur();" href="index.php?a=95" target="main">'.$_lang['import_site'].'</a></li>';
}
if($modx->hasPermission('export_static')) {
	// export-static-site
	$toolsmenu[] = '<li><a onclick="this.blur();" href="index.php?a=83" target="main">'.$_lang['export_site'].'</a></li>';
}
if($modx->hasPermission('settings')) {
	// configuration
	$toolsmenu[] = '<li><a onclick="this.blur();" href="index.php?a=17" target="main">'.$_lang['edit_settings'].'</a></li>';
}

// Reports Menu
$reportsmenu = array();
// site-sched
$reportsmenu[] = '<li><a onclick="this.blur();" href="index.php?a=70" target="main">'.$_lang['site_schedule'].'</a></li>';
if($modx->hasPermission('view_eventlog')) {
	// eventlog
	$reportsmenu[] = '<li><a onclick="this.blur();" href="index.php?a=114" target="main">'.$_lang['eventlog_viewer'].'</a></li>';
}
if($modx->hasPermission('logs')) {
	// manager-audit-trail
	$reportsmenu[] = '<li><a onclick="this.blur();" href="index.php?a=13" target="main">'.$_lang['view_logging'].'</a></li>';
	// system-info
	$reportsmenu[] = '<li><a onclick="this.blur();" href="index.php?a=53" target="main">'.$_lang['view_sysinfo'].'</a></li>';
}

// Output Menus where there are items to show
if (!empty($sitemenu)) {
	echo "\t",'<li id="limenu3" class="active"><a href="#menu3" onclick="new NavToggle(this); return false;">',$_lang['site'],'</a><ul class="subnav" id="menu3">',"\n\t\t",
	     implode("\n\t\t", $sitemenu),
	     "\n\t</ul></li>\n";
}
if (!empty($resourcemenu)) {
	echo "\t",'<li id="limenu5"><a href="#menu5" onclick="new NavToggle(this); return false;">',$_lang['elements'],'</a><ul class="subnav" id="menu5">',"\n\t\t",
	     implode("\n\t\t", $resourcemenu),
	     "\n\t</ul></li>\n";
}
if (!empty($modulemenu)) {
	echo "\t",'<li id="limenu9"><a href="#menu9" onclick="new NavToggle(this); return false;">',$_lang['modules'],'</a><ul class="subnav" id="menu9">',"\n\t\t",
	     implode("\n\t\t", $modulemenu),
	     "\n\t</ul></li>\n";
}
if (!empty($securitymenu)) {
	echo "\t",'<li id="limenu2"><a href="#menu2" onclick="new NavToggle(this); return false;">',$_lang['users'],'</a><ul class="subnav" id="menu2">',"\n\t\t",
	     implode("\n\t\t", $securitymenu),
	     "\n\t</ul></li>\n";
}
if (!empty($toolsmenu)) {
	echo "\t",'<li id="limenu1-1"><a href="#menu1-1" onclick="new NavToggle(this); return false;">',$_lang['tools'],'</a><ul class="subnav" id="menu1-1">',"\n\t\t",
	     implode("\n\t\t", $toolsmenu),
	     "\n\t</ul></li>\n";
}
if (!empty($reportsmenu)) {
	echo "\t",'<li id="limenu1-2"><a href="#menu1-2" onclick="new NavToggle(this); return false;">',$_lang['reports'],'</a><ul class="subnav" id="menu1-2">',"\n\t\t",
	     implode("\n\t\t", $reportsmenu),
	     "\n\t</ul></li>\n";
}
?>
	</ul>
</div>
</div>
</form>

<div id="menuSplitter"></div>
</body>
</html>
