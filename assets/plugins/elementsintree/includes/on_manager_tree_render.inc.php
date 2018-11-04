<?php
if (!defined('MODX_BASE_PATH')) {
    die('What are you doing? Get out of here!');
}

$modx->addSnippet('hasPermission', 'return $modx->hasPermission($permission);');
$modx->addSnippet('hasAnyPermission', 'return (int)hasAnyPermission();');

$filter = $modx->getConfig('enable_filter');
$modx->config['enable_filter'] = 1;

$html = hasAnyPermission() ? '</div>' : '';
$html .= file_get_contents($eitBaseBath . 'assets/on_manager_tree_render.tpl');
$html = $modx->parseText($modx->mergeConditionalTagsContent($html), array(
    'tabLabel_template' => '<i class="fa fa-newspaper-o"></i>',
    'tabLabel_tv' => '<i class="fa fa-list-alt"></i>',
    'tabLabel_chunk' => '<i class="fa fa-th-large"></i>',
    'tabLabel_snippet' => '<i class="fa fa-code"></i>',
    'tabLabel_plugin' => '<i class="fa fa-plug"></i>',
    'tabLabel_module' => '<i class="fa fa-cubes"></i>',
    'tabLabel_create' => '<i class="fa fa-plus"></i>',
    'tabLabel_refresh' => '<i class="fa fa-refresh"></i>',
    'text_reload_title' => 'Click here to reload elements list.',
    'templates' => createElementsList('site_templates', 16, 'templatename'),
    'tmplvars' => createElementsList('site_tmplvars', 301),
    'chunk' => createElementsList('site_htmlsnippets', 78),
    'snippet' => createElementsList('site_snippets', 22),
    'plugin' => createElementsList('site_plugins', 102),
    'module' => createModulesList(112)
));
$html = $modx->parseText($html, $_lang, '[%', '%]');

$modx->config['enable_filter'] = $filter;

$modx->event->addOutput($html);
