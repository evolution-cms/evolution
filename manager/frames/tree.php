<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

// invoke OnManagerTreeInit event
$evtOut = $modx->invokeEvent('OnManagerTreeInit', $_REQUEST);
if(is_array($evtOut)) {
	echo implode("\n", $evtOut);
}
?>

<div class="treeframebody">
	<div id="treeMenu">

		<a class="treeButton" id="treeMenu_expandtree" onclick="modx.tree.expandTree();" title="<?php echo $_lang['expand_tree']; ?>"><?php echo $_style['expand_tree']; ?></a>

		<a class="treeButton" id="treeMenu_collapsetree" onclick="modx.tree.collapseTree();" title="<?php echo $_lang['collapse_tree']; ?>"><?php echo $_style['collapse_tree']; ?></a>

		<?php if($modx->hasPermission('new_document')) { ?>
			<a class="treeButton" id="treeMenu_addresource" onclick="modx.tabs({url:'<?= MODX_MANAGER_URL ?>?a=4', title: '<?php echo $_lang['add_resource']; ?>'});" title="<?php echo $_lang['add_resource']; ?>"><?php echo $_style['add_doc_tree']; ?></a>
			<a class="treeButton" id="treeMenu_addweblink" onclick="modx.tabs({url:'<?= MODX_MANAGER_URL ?>?a=72', title: '<?php echo $_lang['add_weblink']; ?>'});" title="<?php echo $_lang['add_weblink']; ?>"><?php echo $_style['add_weblink_tree']; ?></a>
		<?php } ?>

		<a class="treeButton" id="treeMenu_refreshtree" onclick="modx.tree.restoreTree();" title="<?php echo $_lang['refresh_tree']; ?>"><?php echo $_style['refresh_tree']; ?></a>

		<a class="treeButton" id="treeMenu_sortingtree" onclick="modx.tree.showSorter(event);" title="<?php echo $_lang['sort_tree']; ?>"><?php echo $_style['sort_tree']; ?></a>

		<?php if($modx->hasPermission('edit_document') && $modx->hasPermission('save_document')) { ?>
			<a class="treeButton" id="treeMenu_sortingindex" onclick="modx.tabs({url: '<?= MODX_MANAGER_URL ?>?a=56&id=0', title: '<?php echo $_lang['sort_menuindex']; ?>'});" title="<?php echo $_lang['sort_menuindex']; ?>"><?php echo $_style['sort_menuindex']; ?></a>
		<?php } ?>

		<?php if($use_browser && $modx->hasPermission('assets_images')) { ?>
			<a class="treeButton" id="treeMenu_openimages" title="<?php echo $_lang["images_management"] . "\n" . $_lang['em_button_shift'] ?>"><?php echo $_style['images_management']; ?></a>
		<?php } ?>

		<?php if($use_browser && $modx->hasPermission('assets_files')) { ?>
			<a class="treeButton" id="treeMenu_openfiles" title="<?php echo $_lang["files_management"] . "\n" . $_lang['em_button_shift'] ?>"><?php echo $_style['files_management']; ?></a>
		<?php } ?>

		<?php if($modx->hasPermission('edit_template') || $modx->hasPermission('edit_snippet') || $modx->hasPermission('edit_chunk') || $modx->hasPermission('edit_plugin')) { ?>
			<a class="treeButton" id="treeMenu_openelements" title="<?php echo $_lang["element_management"] . "\n" . $_lang['em_button_shift'] ?>"><?php echo $_style['element_management']; ?></a>
		<?php } ?>

		<?php if($modx->hasPermission('empty_trash')) { ?>
			<a class="treeButton treeButtonDisabled" id="treeMenu_emptytrash" title="<?php echo $_lang['empty_recycle_bin_empty']; ?>"><?php echo $_style['empty_recycle_bin_empty']; ?></a>
		<?php } ?>

		<a class="treeButton" id="treeMenu_theme_dark" onclick="modx.tree.toggleTheme(event)"><i class="fa fa-adjust"></i></a>

	</div>

	<div id="treeHolder">
		<?php
		// invoke OnManagerTreePrerender event
		$evtOut = $modx->invokeEvent('OnManagerTreePrerender', $modx->getDatabase()->escape($_REQUEST));
		if(is_array($evtOut)) {
			echo implode("\n", $evtOut);
		}
		?>
		<div id="node0" class="rootNode"><a class="node" onclick="modx.tree.treeAction(event, 0)" data-id="0" data-title-esc="<?php $site_name = $modx->getPhpCompat()->entities($site_name);
			echo $site_name; ?>"><span class="icon"><?php echo $_style['tree_showtree']; ?></span><span class="title"><?php echo $site_name; ?></span></a>
			<div id="treeloader"><i class="fa fa-cog fa-spin fa-3x fa-fw"></i></div>
		</div>
		<div id="treeRoot"></div>
		<?php
		// invoke OnManagerTreeRender event
		$evtOut = $modx->invokeEvent('OnManagerTreeRender', $modx->getDatabase()->escape($_REQUEST));
		if(is_array($evtOut)) {
			echo implode("\n", $evtOut);
		}
		?>
	</div>
</div>
