<?php
if ( ! defined('MODX_BASE_PATH')) {
    die('HACK???');
}

include_once(MODX_BASE_PATH . 'assets/lib/APIHelpers.class.php');
include_once(MODX_BASE_PATH . 'assets/snippets/DocLister/lib/DLFixedPrepare.class.php');

$p = &$modx->event->params;
if ( ! is_array($p)) {
    $p = array();
}

if ( isset( $p['config'] ) ) {
    require_once MODX_BASE_PATH . 'assets/lib/Helpers/Config.php';

    $helper = new \Helpers\Config( $p );
    $helper->setPath( '/assets/snippets/DocLister/' );
    $helper->loadConfig( $p['config'] );
    
    $p = array_merge( $helper->getConfig(), $p );
}

/** Текущий уровень вложенности */
$p['currentDepth'] = $currentDepth = \APIhelpers::getkey($p, 'currentDepth', 1);


/** Основной шаблон обертка */
$p['TplMainOwner'] = \APIhelpers::getkey($p, 'TplMainOwner',
    '@CODE:<ul id="nav" class="menu level-1">[+dl.wrap+]</ul>'
);

/** Шаблон обертка для вложенных уровней */
$p['TplSubOwner'] = \APIhelpers::getkey($p, 'TplSubOwner',
    '@CODE:<ul class="sub-menu level-[+dl.currentDepth+]">[+dl.wrap+]</ul>'
);

/**
 * TplOwnerN    Шаблон обертка для N уровня вложенности
 * TplMainOwner Основной шабон обертка
 * TplSubOwner  Шаблон обертка для вложенных уровней
 */
$currentOwnerTpl = \APIhelpers::getkey($p, 'TplOwner' . $currentDepth);
if (empty($currentOwnerTpl)) {
    $currentOwnerTpl = \APIhelpers::getkey($p, (($currentDepth == 1) ? 'TplMainOwner' : 'TplSubOwner'));
    $currentOwnerTpl = str_replace('[+dl.currentDepth+]', $currentDepth, $currentOwnerTpl);
}


/** Основной шаблон для каждого пункта меню всех уровней */
$p['TplOneItem'] = $currentTpl = \APIhelpers::getkey($p, 'TplOneItem',
    '@CODE:<li id="menu-item-[+id+]" class="menu-item [+dl.class+]">
        <a href="[+url+]" title="[+e.title+]">[+title+]</a>
        [+dl.submenu+]
    </li>'
);

/**
 *   TplDepthN               Шаблон пункта меню вложенности N
 *   TplNoChildrenDepthN     Шаблон пункта меню вложенности N без дочерних элементов
 *   noChildrenRowTPL        Общий шаблон пункта меню без дочерних элементов
 */
$currentTpl = \APIhelpers::getkey($p, 'TplDepth' . $currentDepth, $currentTpl);
$currentNoChildrenTpl = \APIhelpers::getkey($p, 'TplNoChildrenDepth' . $currentDepth);
if (empty($currentNoChildrenTpl)) {
    $currentNoChildrenTpl = \APIhelpers::getkey($p, 'noChildrenRowTPL', $currentTpl);
}


/** Условия выборки документов для всех уровней */
$p['addWhereList'] = $currentWhere = \APIhelpers::getkey($p, 'addWhereList', 'c.hidemenu = 0');
/** addWhereListN   Условия выборки документов N уровня */
$currentWhere = \APIhelpers::getkey($p, 'addWhereList' . $currentDepth, $currentWhere);

$p['orderBy'] = $currentOrderBy = \APIhelpers::getkey($p, 'orderBy', 'menuindex ASC, id ASC');
/** orderByN   Условия сортировки документов N уровня */
$currentOrderBy = \APIhelpers::getkey($p, 'orderBy' . $currentDepth, $currentOrderBy);


$p['tvList'] = $currentTvList = \APIhelpers::getkey($p, 'tvList');
$currentTvList  = \APIhelpers::getkey($p, 'tvList' . $currentDepth, $currentTvList);
/**
 * Получение prepare сниппетов из параметров BeforePrepare и AfterPrepare
 * для совмещения с обязательным вызовом DLFixedPrepare::buildMenu метода
 */
$prepare = \APIhelpers::getkey($p, 'BeforePrepare', '');
$prepare = explode(",", $prepare);
$prepare[] = 'DLFixedPrepare::buildMenu';
$prepare[] = \APIhelpers::getkey($p, 'AfterPrepare', '');
$p['prepare'] = trim(implode(",", $prepare), ',');

return $modx->runSnippet('DocLister', array_merge(array(
        'idType'  => 'parents',
        'parents' => \APIhelpers::getkey($p, 'parents', 0),
    ), $p, array(
        'params'           => $p,
        'tpl'              => $currentTpl,
        'ownerTPL'         => $currentOwnerTpl,
        'mainRowTpl'       => $currentTpl,
        'noChildrenRowTPL' => $currentNoChildrenTpl,
        'noneWrapOuter'    => '0',
        'addWhereList'     => $currentWhere,
        'orderBy'          => $currentOrderBy
    ))
);
