<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

/**
 * Don't delete this file
 * Don't rename and move functions
 */

$tpl = array(
    'viewForm' => file_get_contents(MODX_MANAGER_PATH . 'actions/resources/tpl_viewForm.tpl'),
    'panelGroup' => file_get_contents(MODX_MANAGER_PATH . 'actions/resources/tpl_panelGroup.tpl'),
    'panelHeading' => file_get_contents(MODX_MANAGER_PATH . 'actions/resources/tpl_panelHeading.tpl'),
    'panelCollapse' => file_get_contents(MODX_MANAGER_PATH . 'actions/resources/tpl_panelCollapse.tpl'),
    'elementsRow' => file_get_contents(MODX_MANAGER_PATH . 'actions/resources/tpl_elementsRow.tpl')
);

/**
 * @param string $tpl
 * @param array $ph
 * @return string
 */
function parsePh($tpl, $ph) {
    $modx = evolutionCMS(); global $_lang;
    $tpl = $modx->parseText($tpl, $_lang, '[%', '%]');
    return $modx->parseText($tpl, $ph);
}

/**
 * @param string|int $cssId
 * @return string
 */
function renderViewSwitchButtons($cssId) {
    $modx = evolutionCMS(); global $_lang, $tpl;

    return parsePh($tpl['viewForm'], array(
        'cssId' => $cssId
    ));
}

/**
 * @param string $resourceTable
 * @param mgrResources $resources
 * @return string
 */
function createResourceList($resourceTable, $resources) {
    $modx = evolutionCMS(); global $_lang, $_style, $modx_textdir, $tpl;

    $items = isset($resources->items[$resourceTable]) ? $resources->items[$resourceTable] : false;

    if( ! is_array($items) || empty($items)) {
        return $_lang['no_results'];
    }

    // Prepare elements- and categories-list
    $elements = array();
    $categories = array();
    foreach($items as $row) {
        $catid = $row['catid'] ? $row['catid'] : 0;
        $categories[$catid] = array('name' => stripslashes($row['category']));
        $elements[$catid][] = prepareElementRowPh($row, $resourceTable, $resources);
    }

    // Now render categories / panel-collapse
    $panelGroup = '';
    foreach($elements as $catid => $elList) {
        // Add panel-heading / category-collapse to output
        $panelGroup .= parsePh($tpl['panelHeading'], array(
            'tab' => $resourceTable,
            'category' => $categories[$catid]['name'],
            'categoryid' => $catid != '' ? ' <small>(' . $catid . ')</small>' : '',
            'catid' => $catid,
        ));

        // Prepare content for panel-collapse
        $panelCollapse = '';
        foreach($elList as $el) {
            $panelCollapse .= parsePh($tpl['elementsRow'], $el);
        }

        // Add panel-collapse with elements to output
        $panelGroup .= parsePh($tpl['panelCollapse'], array(
            'tab' => $resourceTable,
            'catid' => $catid,
            'wrapper' => $panelCollapse,
        ));
    }

    return parsePh($tpl['panelGroup'], array(
        'resourceTable' => $resourceTable,
        'wrapper' => $panelGroup
    ));
}

/**
 * @param mgrResources $resources
 * @return string
 */
function createCombinedView($resources) {
    $modx = evolutionCMS(); global $_lang, $_style, $modx_textdir;

    $itemsPerCategory = isset($resources->itemsPerCategory) ? $resources->itemsPerCategory : false;
    $types = isset($resources->types) ? $resources->types : false;
    $categories = isset($resources->categories) ? $resources->categories : false;

    if(!$itemsPerCategory) {
        return $_lang['no_results'];
    }

    $tpl = array(
        'panelGroup' => file_get_contents(MODX_MANAGER_PATH . 'actions/resources/tpl_panelGroup.tpl'),
        'panelHeading' => file_get_contents(MODX_MANAGER_PATH . 'actions/resources/tpl_panelHeading.tpl'),
        'panelCollapse' => file_get_contents(MODX_MANAGER_PATH . 'actions/resources/tpl_panelCollapse.tpl'),
        'elementsRow' => file_get_contents(MODX_MANAGER_PATH . 'actions/resources/tpl_elementsRow.tpl'),
    );

    // Easily loop through $itemsPerCategory-Array
    $panelGroup = '';
    foreach($categories as $catid => $category) {
        // Prepare collapse content / elements-list
        $panelCollapse = '';
        foreach($itemsPerCategory[$catid] as $el) {
            $resourceTable = $el['type'];
            $ph = prepareElementRowPh($el, $resourceTable, $resources);
            $panelCollapse .= parsePh($tpl['elementsRow'], $ph);
        }

        // Add panel-heading / button
        $panelGroup .= parsePh($tpl['panelHeading'], array(
            'tab' => 'categories_list',
            'category' => $categories[$catid],
            'categoryid' => $catid != '' ? ' <small>(' . $catid . ')</small>' : '',
            'catid' => $catid,
        ));

        // Add panel
        $panelGroup .= parsePh($tpl['panelCollapse'], array(
            'tab' => 'categories_list',
            'catid' => $catid,
            'wrapper' => $panelCollapse,
        ));
    }

    return parsePh($tpl['panelGroup'], array(
        'resourceTable' => 'categories_list',
        'wrapper' => $panelGroup
    ));
}

/**
 * @param array $row
 * @param string $resourceTable
 * @param mgrResources $resources
 * @return array
 */
function prepareElementRowPh($row, $resourceTable, $resources) {
    $modx = evolutionCMS(); global $modx_textdir, $_style, $_lang;

    $types = isset($resources->types[$resourceTable]) ? $resources->types[$resourceTable] : false;

    $_lang["confirm_delete"] = $_lang["delete"];

    switch($resourceTable){
        case 'site_templates':
            $class = $row['selectable'] ? '' : 'disabledPlugin';
            $lockElementType = 1;
            $_lang["confirm_delete"] = $_lang["confirm_delete_template"];
            break;
        case 'site_tmplvars':
            $class = $row['reltpl'] ? '' : 'disabledPlugin';
            $lockElementType = 2;
            $_lang["confirm_delete"] = $_lang["confirm_delete_tmplvars"];
            break;
        case 'site_htmlsnippets':
            $class = $row['disabled'] ? 'disabledPlugin' : '';
            $lockElementType = 3;
            $_lang["confirm_delete"] = $_lang["confirm_delete_htmlsnippet"];
            break;
        case 'site_snippets':
            $class = $row['disabled'] ? 'disabledPlugin' : '';
            $lockElementType = 4;
            $_lang["confirm_delete"] = $_lang["confirm_delete_snippet"];
            break;
        case 'site_plugins':
            $class = $row['disabled'] ? 'disabledPlugin' : '';
            $lockElementType = 5;
            $_lang["confirm_delete"] = $_lang["confirm_delete_plugin"];
            break;
        case 'site_modules':
            $class = $row['disabled'] ? '' : 'disabledPlugin';
            $_lang["confirm_delete"] = $_lang["confirm_delete_module"];
            break;
        default:
            return array();
    }

    // Prepare displaying user-locks
    $lockedByUser = '';
    $rowLock = $modx->elementIsLocked($lockElementType, $row['id'], true);
    if($rowLock && $modx->hasPermission('display_locks')) {
        if($rowLock['sid'] == $modx->sid) {
            $title = $modx->parseText($_lang["lock_element_editing"], array(
                'element_type' => $_lang["lock_element_type_" . $lockElementType],
                'lasthit_df' => $rowLock['lasthit_df']
            ));
            $lockedByUser = '<span title="' . $title . '" class="editResource" style="cursor:context-menu;">' . $_style['tree_preview_resource'] . '</span>&nbsp;';
        } else {
            $title = $modx->parseText($_lang["lock_element_locked_by"], array(
                'element_type' => $_lang["lock_element_type_" . $lockElementType],
                'username' => $rowLock['username'],
                'lasthit_df' => $rowLock['lasthit_df']
            ));
            if($modx->hasPermission('remove_locks')) {
                $lockedByUser = '<a href="javascript:;" onclick="unlockElement(' . $lockElementType . ', ' . $row['id'] . ', this);return false;" title="' . $title . '" class="lockedResource"><i class="' . $_style['icons_secured'] . '"></i></a>';
            } else {
                $lockedByUser = '<span title="' . $title . '" class="lockedResource" style="cursor:context-menu;"><i class="' . $_style['icons_secured'] . '"></i></span>';
            }
        }
    }
    if($lockedByUser) {
        $lockedByUser = '<div class="lockCell">' . $lockedByUser . '</div>';
    }

    // Caption
    if($resourceTable == 'site_tmplvars') {
        $caption = !empty($row['description']) ? ' ' . $row['caption'] . ' &nbsp; <small>(' . $row['description'] . ')</small>' : ' ' . $row['caption'];
    } else {
        $caption = !empty($row['description']) ? ' ' . $row['description'] : '';
    }

    // Special marks
    $tplInfo = array();
    if($row['locked']) {
        $tplInfo[] = $_lang['locked'];
    }
    if($row['id'] == $modx->config['default_template'] && $resourceTable == 'site_templates') {
        $tplInfo[] = $_lang['defaulttemplate_title'];
    }
    $marks = !empty($tplInfo) ? ' <em>(' . implode(', ', $tplInfo) . ')</em>' : '';

    /* row buttons */
    $buttons = '';
    if($modx->hasPermission($types['actions']['edit'][1])) {
        $buttons .= '<li><a title="' . $_lang["edit_resource"] . '" href="index.php?a=' . $types['actions']['edit'][0] . '&amp;id=' . $row['id'] . '"><i class="fa fa-edit fa-fw"></i></a></li>';
    }
    if($modx->hasPermission($types['actions']['duplicate'][1])) {
        $buttons .= '<li><a onclick="return confirm(\'' . $_lang["confirm_duplicate_record"] . '\')" title="' . $_lang["resource_duplicate"] . '" href="index.php?a=' . $types['actions']['duplicate'][0] . '&amp;id=' . $row['id'] . '"><i class="fa fa-clone fa-fw"></i></a></li>';
    }
    if($modx->hasPermission($types['actions']['remove'][1])) {
        $buttons .= '<li><a onclick="return confirm(\'' . $_lang["confirm_delete"] . '\')" title="' . $_lang["delete"] . '" href="index.php?a=' . $types['actions']['remove'][0] . '&amp;id=' . $row['id'] . '"><i class="fa fa-trash fa-fw"></i></a></li>';
    }
    $buttons = $buttons ? '<div class="btnCell"><ul class="elements_buttonbar">' . $buttons . '</ul></div>' : '';

    $catid = $row['catid'] ? $row['catid'] : 0;

    // Placeholders for elements-row
    return array(
        'class' => $class ? ' class="' . $class . '"' : '',
        'lockedByUser' => $lockedByUser,
        'name' => $row['name'],
        'caption' => $caption,
        'buttons' => $buttons,
        'marks' => $marks,
        'id' => $row['id'],
        'resourceTable' => $resourceTable,
        'actionEdit' => $types['actions']['edit'][0],
        'catid' => $catid,
        'textdir' => $modx_textdir ? '&rlm;' : '',
    );
}
