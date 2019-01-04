<?php
/*
menu->Build('id','parent','name','link','alt','onclick','permission','target','divider 1/0','menuindex', 'class')
*/

$sitemenu['bars'] = array(
	'bars',
	'main',
	'<i class="fa fa-bars"></i>',
	'javascript:;',
	$_lang['home'],
	'modx.resizer.toggle(); return false;',
	' return false;',
	'',
	0,
	10,
	''
);

//mainMenu
$sitemenu['site'] = array(
	'site',
	'main',
	'<i class="fa fa-tachometer"></i><span class="menu-item-text">' . $_lang['home'] . '</span>',
	'index.php?a=2',
	$_lang['home'],
	'',
	'',
	'main',
	0,
	10,
	'active'
);

if($modx->hasPermission('edit_template') || $modx->hasPermission('edit_snippet') || $modx->hasPermission('edit_chunk') || $modx->hasPermission('edit_plugin') || $modx->hasPermission('file_manager')) {
	$sitemenu['elements'] = array(
		'elements',
		'main',
		'<i class="fa fa-th"></i><span class="menu-item-text">' . $_lang['elements'] . '</span>',
		'javascript:;',
		$_lang['elements'],
		' return false;',
		'',
		'',
		0,
		20,
		''
	);
}

if($modx->hasPermission('exec_module')) {
	$sitemenu['modules'] = array(
		'modules',
		'main',
		'<i class="'.$_style['icons_modules'] .'"></i><span class="menu-item-text">' . $_lang['modules'] . '</span>',
		'javascript:;',
		$_lang['modules'],
		' return false;',
		'',
		'',
		0,
		30,
		''
	);
}

if($modx->hasPermission('edit_user') || $modx->hasPermission('edit_web_user') || $modx->hasPermission('edit_role') || $modx->hasPermission('access_permissions') || $modx->hasPermission('web_access_permissions')) {
	$sitemenu['users'] = array(
		'users',
		'main',
		'<i class="fa fa-users"></i><span class="menu-item-text">' . $_lang['users'] . '</span>',
		'javascript:;',
		$_lang['users'],
		' return false;',
		'edit_user',
		'',
		0,
		40,
		''
	);
}

if($modx->hasPermission('empty_cache') || $modx->hasPermission('bk_manager') || $modx->hasPermission('remove_locks') || $modx->hasPermission('import_static') || $modx->hasPermission('export_static')) {
	$sitemenu['tools'] = array(
		'tools',
		'main',
		'<i class="fa fa-wrench"></i><span class="menu-item-text">' . $_lang['tools'] . '</span>',
		'javascript:;',
		$_lang['tools'],
		' return false;',
		'',
		'',
		0,
		50,
		''
	);
}

$tab = 0;
if($modx->hasPermission('edit_template')) {
	$sitemenu['element_templates'] = array(
		'element_templates',
		'elements',
		'<i class="fa fa-newspaper-o"></i>' . $_lang['manage_templates'] . '<i class="fa fa-angle-right toggle"></i>',
		'index.php?a=76&tab=' . $tab++,
		$_lang['manage_templates'],
		'',
		'new_template,edit_template',
		'main',
		0,
		10,
		'dropdown-toggle'
	);
}
if($modx->hasPermission('edit_template') && $modx->hasPermission('edit_snippet') && $modx->hasPermission('edit_chunk') && $modx->hasPermission('edit_plugin')) {
	$sitemenu['element_tplvars'] = array(
		'element_tplvars',
		'elements',
		'<i class="fa fa-list-alt"></i>' . $_lang['tmplvars'] . '<i class="fa fa-angle-right toggle"></i>',
		'index.php?a=76&tab=' . $tab++,
		$_lang['tmplvars'],
		'',
		'new_template,edit_template',
		'main',
		0,
		20,
		'dropdown-toggle'
	);
}
if($modx->hasPermission('edit_chunk')) {
	$sitemenu['element_htmlsnippets'] = array(
		'element_htmlsnippets',
		'elements',
		'<i class="fa fa-th-large"></i>' . $_lang['manage_htmlsnippets'] . '<i class="fa fa-angle-right toggle"></i>',
		'index.php?a=76&tab=' . $tab++,
		$_lang['manage_htmlsnippets'],
		'',
		'new_chunk,edit_chunk',
		'main',
		0,
		30,
		'dropdown-toggle'
	);
}
if($modx->hasPermission('edit_snippet')) {
	$sitemenu['element_snippets'] = array(
		'element_snippets',
		'elements',
		'<i class="fa fa-code"></i>' . $_lang['manage_snippets'] . '<i class="fa fa-angle-right toggle"></i>',
		'index.php?a=76&tab=' . $tab++,
		$_lang['manage_snippets'],
		'',
		'new_snippet,edit_snippet',
		'main',
		0,
		40,
		'dropdown-toggle'
	);
}
if($modx->hasPermission('edit_plugin')) {
	$sitemenu['element_plugins'] = array(
		'element_plugins',
		'elements',
		'<i class="fa fa-plug"></i>' . $_lang['manage_plugins'] . '<i class="fa fa-angle-right toggle"></i>',
		'index.php?a=76&tab=' . $tab++,
		$_lang['manage_plugins'],
		'',
		'new_plugin,edit_plugin',
		'main',
		0,
		50,
		'dropdown-toggle'
	);
}
//$sitemenu['element_categories']     = array('element_categories','elements',$_lang['element_categories'],'index.php?a=76&tab=5',$_lang['element_categories'],'','new_template,edit_template,new_snippet,edit_snippet,new_chunk,edit_chunk,new_plugin,edit_plugin','main',1,60,'');

if($modx->hasPermission('file_manager')) {
	$sitemenu['manage_files'] = array(
		'manage_files',
		'elements',
		'<i class="fa fa-folder-open-o"></i>' . $_lang['manage_files'],
		'index.php?a=31',
		$_lang['manage_files'],
		'',
		'file_manager',
		'main',
		0,
		80,
		''
	);
}
if($modx->hasPermission('category_manager')) {
	$sitemenu['manage_categories'] = array(
		'manage_categories',
		'elements',
		'<i class="fa fa-object-group"></i>' . $_lang['manage_categories'],
		'index.php?a=120',
		$_lang['manage_categories'],
		'',
		'category_manager',
		'main',
		0,
		70,
		''
	);
}

// Modules Menu Items
if($modx->hasPermission('new_module') || $modx->hasPermission('edit_module') || $modx->hasPermission('save_module')) {
	$sitemenu['new_module'] = array(
		'new_module',
		'modules',
		'<i class="'.$_style['icons_modules'] .'"></i>' . $_lang['module_management'],
		'index.php?a=106',
		$_lang['module_management'],
		'',
		'new_module,edit_module',
		'main',
		1,
		0,
		''
	);
}

if($modx->hasPermission('exec_module')) {
	if($_SESSION['mgrRole'] != 1 && !empty($modx->config['use_udperms'])) {
		$rs = $modx->db->query('SELECT DISTINCT sm.id, sm.name, sm.icon, mg.member
				FROM ' . $modx->getFullTableName('site_modules') . ' AS sm
				LEFT JOIN ' . $modx->getFullTableName('site_module_access') . ' AS sma ON sma.module = sm.id
				LEFT JOIN ' . $modx->getFullTableName('member_groups') . ' AS mg ON sma.usergroup = mg.user_group
                WHERE (mg.member IS NULL OR mg.member = ' . $modx->getLoginUserID() . ') AND sm.disabled != 1 AND sm.locked != 1
                ORDER BY sm.name');
	} else {
		$rs = $modx->db->select('*', $modx->getFullTableName('site_modules'), 'disabled != 1', 'name');
	}
	if($modx->db->getRecordCount($rs)) {
	    while ($row = $modx->db->getRow($rs)) {
            $sitemenu['module' . $row['id']] = array(
                'module' . $row['id'],
                'modules',
                ($row['icon'] != '' ? '<i class="'.$row['icon'].'"></i>' : '<i class="'.$_style['icons_module'].'"></i>') . $row['name'],
                'index.php?a=112&id=' . $row['id'],
                $row['name'],
                '',
                '',
                'main',
                0,
                1,
                ''
            );
        }
    }
}

// security menu items (users)

if($modx->hasPermission('edit_user')) {
	$sitemenu['user_management_title'] = array(
		'user_management_title',
		'users',
		'<i class="fa fa-user-circle-o"></i>' . $_lang['user_management_title'] . '<i class="fa fa-angle-right toggle"></i>',
		'index.php?a=75',
		$_lang['user_management_title'],
		'',
		'edit_user',
		'main',
		0,
		10,
		'dropdown-toggle'
	);
}

if($modx->hasPermission('edit_web_user')) {
	$sitemenu['web_user_management_title'] = array(
		'web_user_management_title',
		'users',
		'<i class="fa fa-user"></i>' . $_lang['web_user_management_title'] . '<i class="fa fa-angle-right toggle"></i>',
		'index.php?a=99',
		$_lang['web_user_management_title'],
		'',
		'edit_web_user',
		'main',
		0,
		20,
		'dropdown-toggle'
	);
}

if($modx->hasPermission('edit_role')) {
	$sitemenu['role_management_title'] = array(
		'role_management_title',
		'users',
		'<i class="fa fa-legal"></i>' . $_lang['role_management_title'],
		'index.php?a=86',
		$_lang['role_management_title'],
		'',
		'new_role,edit_role,delete_role',
		'main',
		0,
		30,
		''
	);
}

if($modx->hasPermission('access_permissions')) {
	$sitemenu['manager_permissions'] = array(
		'manager_permissions',
		'users',
		'<i class="fa fa-male"></i>' . $_lang['manager_permissions'],
		'index.php?a=40',
		$_lang['manager_permissions'],
		'',
		'access_permissions',
		'main',
		0,
		40,
		''
	);
}

if($modx->hasPermission('web_access_permissions')) {
	$sitemenu['web_permissions'] = array(
		'web_permissions',
		'users',
		'<i class="fa fa-universal-access"></i>' . $_lang['web_permissions'],
		'index.php?a=91',
		$_lang['web_permissions'],
		'',
		'web_access_permissions',
		'main',
		0,
		50,
		''
	);
}

// Tools Menu

$sitemenu['refresh_site'] = array(
    'refresh_site',
    'tools',
    '<i class="fa fa-recycle"></i>' . $_lang['refresh_site'],
    'index.php?a=26',
    $_lang['refresh_site'],
    '',
    '',
    'main',
    0,
    5,
    'item-group',
    array(
        'refresh_site_in_window' => array(
            'a', // tag
            'javascript:;', // href
            'btn btn-secondary', // class or btn-success
            'modx.popup({url:\'index.php?a=26\', title:\'' . $_lang['refresh_site'] . '\', icon: \'fa-recycle\', iframe: \'ajax\', selector: \'.tab-page>.container\', position: \'right top\', width: \'auto\', maxheight: \'50%\', wrap: \'body\' })', // onclick
            $_lang['refresh_site'], // title
            '<i class="fa fa-recycle"></i>' // innerHTML
        )
    )
);

$sitemenu['search'] = array(
	'search',
	'tools',
	'<i class="fa fa-search"></i>' . $_lang['search'],
	'index.php?a=71',
	$_lang['search'],
	'',
	'',
	'main',
	1,
	9,
	''
);

if($modx->hasPermission('bk_manager')) {
	$sitemenu['bk_manager'] = array(
		'bk_manager',
		'tools',
		'<i class="fa fa-database"></i>' . $_lang['bk_manager'],
		'index.php?a=93',
		$_lang['bk_manager'],
		'',
		'bk_manager',
		'main',
		0,
		10,
		''
	);
}

if($modx->hasPermission('remove_locks')) {
	$sitemenu['remove_locks'] = array(
		'remove_locks',
		'tools',
		'<i class="fa fa-hourglass"></i>' . $_lang['remove_locks'],
		'javascript:modx.removeLocks();',
		$_lang['remove_locks'],
		'',
		'remove_locks',
		'',
		0,
		20,
		''
	);
}

if($modx->hasPermission('import_static')) {
	$sitemenu['import_site'] = array(
		'import_site',
		'tools',
		'<i class="fa fa-upload"></i>' . $_lang['import_site'],
		'index.php?a=95',
		$_lang['import_site'],
		'',
		'import_static',
		'main',
		0,
		30,
		''
	);
}

if($modx->hasPermission('export_static')) {
	$sitemenu['export_site'] = array(
		'export_site',
		'tools',
		'<i class="fa fa-download"></i>' . $_lang['export_site'],
		'index.php?a=83',
		$_lang['export_site'],
		'',
		'export_static',
		'main',
		1,
		40,
		''
	);
}

$menu = $modx->invokeEvent("OnManagerMenuPrerender", array('menu' => $sitemenu));
if(is_array($menu)) {
	$newmenu = array();
	foreach($menu as $item){
		if(is_array(unserialize($item))){
			$newmenu = array_merge($newmenu, unserialize($item));
		}
	}
	if(count($newmenu)> 0) $sitemenu = $newmenu;
}

if(file_exists(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/includes/menu.class.inc.php')) {
	include_once(MODX_MANAGER_PATH . 'media/style/' . $modx->config['manager_theme'] . '/includes/menu.class.inc.php');
} else {
	include_once(MODX_MANAGER_PATH . 'includes/menu.class.inc.php');
}
$menu = new EVOmenu();
$menu->Build($sitemenu, array(
	'outerClass' => 'nav',
	'innerClass' => 'dropdown-menu',
	'parentClass' => 'dropdown',
    'parentLinkClass' => 'dropdown-toggle',
    'parentLinkAttr' => '',
    'parentLinkIn' => ''
));
