<?php

if(!function_exists('getDefaultTemplate')) {
    /**
     * @return string
     */
    function getDefaultTemplate()
    {
        $modx = evolutionCMS();

        $default_template = '';
        switch ($modx->config['auto_template_logic']) {
            case 'sibling':
                if (!isset($_GET['pid']) || empty($_GET['pid'])) {
                    $site_start = $modx->config['site_start'];
                    $where = "sc.isfolder=0 AND sc.id!='{$site_start}'";
                    $sibl = $modx->getDocumentChildren($_REQUEST['pid'], 1, 0, 'template', $where, 'menuindex', 'ASC',
                        1);
                    if (isset($sibl[0]['template']) && $sibl[0]['template'] !== '') {
                        $default_template = $sibl[0]['template'];
                    }
                } else {
                    $sibl = $modx->getDocumentChildren($_REQUEST['pid'], 1, 0, 'template', 'isfolder=0', 'menuindex',
                        'ASC', 1);
                    if (isset($sibl[0]['template']) && $sibl[0]['template'] !== '') {
                        $default_template = $sibl[0]['template'];
                    } else {
                        $sibl = $modx->getDocumentChildren($_REQUEST['pid'], 0, 0, 'template', 'isfolder=0',
                            'menuindex', 'ASC', 1);
                        if (isset($sibl[0]['template']) && $sibl[0]['template'] !== '') {
                            $default_template = $sibl[0]['template'];
                        }
                    }
                }
                if (isset($default_template)) {
                    break;
                } // If $default_template could not be determined, fall back / through to "parent"-mode
            case 'parent':
                if (isset($_REQUEST['pid']) && !empty($_REQUEST['pid'])) {
                    $parent = $modx->getPageInfo($_REQUEST['pid'], 0, 'template');
                    if (isset($parent['template'])) {
                        $default_template = $parent['template'];
                    }
                }
                break;
            case 'system':
            default: // default_template is already set
                $default_template = $modx->config['default_template'];
        }

        return empty($default_template) ? $modx->config['default_template'] : $default_template;
    }
}
