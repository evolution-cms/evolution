<?php
//Helper functions for categories
//Kyle Jaebker - 08/07/06

/**
 * Create a new category
 * @param string $newCat
 * @return int
 */
function newCategory($newCat)
{
    $modx = evolutionCMS();
    $useTable = $modx->getDatabase()->getFullTableName('categories');
    $categoryId = $modx->getDatabase()->insert(
        array(
            'category' => $modx->getDatabase()->escape($newCat),
        ), $useTable);
    if (!$categoryId) {
        $categoryId = 0;
    }

    return $categoryId;
}

/**
 * check if new category already exists
 *
 * @param string $newCat
 * @return int
 */
function checkCategory($newCat = '')
{
    $modx = evolutionCMS();
    $newCat = $modx->getDatabase()->escape($newCat);
    $cats = $modx->getDatabase()->select('id', $modx->getDatabase()->getFullTableName('categories'), "category='{$newCat}'");
    if ($cat = $modx->getDatabase()->getValue($cats)) {
        return (int)$cat;
    }

    return 0;
}

/**
 * Check for category, create new if not exists
 *
 * @param string $category
 * @return int
 */
function getCategory($category = '')
{
    $categoryId = checkCategory($category);
    if (!$categoryId) {
        $categoryId = newCategory($category);
    }

    return $categoryId;
}

/**
 * Get all categories
 *
 * @return array
 */
function getCategories()
{
    $modx = evolutionCMS();
    $useTable = $modx->getDatabase()->getFullTableName('categories');
    $cats = $modx->getDatabase()->select('id, category', $modx->getDatabase()->getFullTableName('categories'), '', 'category');
    $resourceArray = array();
    while ($row = $modx->getDatabase()->getRow($cats)) {
        $row['category'] = stripslashes($row['category']);
        $resourceArray[] = $row;
    }

    return $resourceArray;
}

/**
 * Delete category & associations
 *
 * @param int $catId
 */
function deleteCategory($catId = 0)
{
    $modx = evolutionCMS();
    if ($catId) {
        $resetTables = array(
            'site_plugins',
            'site_snippets',
            'site_htmlsnippets',
            'site_templates',
            'site_tmplvars',
            'site_modules'
        );
        foreach ($resetTables as $n => $v) {
            $useTable = $modx->getDatabase()->getFullTableName($v);
            $modx->getDatabase()->update(array('category' => 0), $useTable, "category='{$catId}'");
        }
        $catTable = $modx->getDatabase()->getFullTableName('categories');
        $modx->getDatabase()->delete($catTable, "id='{$catId}'");
    }
}
