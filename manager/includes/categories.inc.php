<?php
//Helper functions for categories
//Kyle Jaebker - 08/07/06
use EvolutionCMS\Models\Category;
use EvolutionCMS\Models\SiteHtmlsnippet;
use EvolutionCMS\Models\SiteModule;
use EvolutionCMS\Models\SitePlugin;
use EvolutionCMS\Models\SiteSnippet;
use EvolutionCMS\Models\SiteTemplate;
use EvolutionCMS\Models\SiteTmplvar;

/**
 * Create a new category
 * @param string $newCat
 * @return int
 */
function newCategory($newCat)
{
    $categoryId = \EvolutionCMS\Models\Category::query()->insertGetId(['category'=>$newCat]);
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
    $newCatCheck = \EvolutionCMS\Models\Category::query()->where('category', $newCat)->first();
    if (!is_null($newCatCheck)) {
        return (int)$newCatCheck->id;
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
    $categories = Category::orderBy('category', 'ASC')->get()->toArray();
    foreach ($categories as $row) {
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
    if ($catId) {
        SiteTemplate::where('category', $catId)->update(['category' => 0]);

        SiteTmplvar::where('category', $catId)->update(['category' => 0]);

        SiteHtmlsnippet::where('category', $catId)->update(['category' => 0]);

        SiteSnippet::where('category', $catId)->update(['category' => 0]);

        SitePlugin::where('category', $catId)->update(['category' => 0]);

        SiteModule::where('category', $catId)->update(['category' => 0]);

        Category::where('id', $catId)->delete();
    }
}
