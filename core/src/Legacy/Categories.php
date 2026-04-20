<?php namespace EvolutionCMS\Legacy;

use EvolutionCMS\Models\Category;
use EvolutionCMS\Models\SiteHtmlsnippet;
use EvolutionCMS\Models\SiteModule;
use EvolutionCMS\Models\SitePlugin;
use EvolutionCMS\Models\SiteSnippet;
use EvolutionCMS\Models\SiteTemplate;
use EvolutionCMS\Models\SiteTmplvar;

/**
 * Class to handle the modx-categories
 */
class Categories
{
    public $db;
    public $db_tbl = [];
    public $elements = ['templates', 'tmplvars', 'htmlsnippets', 'snippets', 'plugins', 'modules'];

    public function __construct()
    {

    }


    /**
     * Get all categories
     * @return  array   $categories / array contains all categories
     */
    public function getCategories()
    {
        $categories = Category::query()->orderBy('rank', 'ASC')->orderBy('category', 'ASC')->get()->toArray();

        return empty($categories) ? [] : $categories;
    }

    /**
     * @param string $search
     * @param string $where
     * @return array|bool|object|stdClass
     */
    public function getCategory($search, $where = 'category')
    {
        $category = Category::where($where, $search)->first();
        if(!is_null($category))
            return $category->toArray();
        else
            return false;
    }

    /**
     * @param string $value
     * @param string $search
     * @param string $where
     * @return bool|int|string
     */
    public function getCategoryValue($value, $search, $where = 'category')
    {

        $category = Category::where($where, $search)->first();
        if(!is_null($category))
            return $category->{$value};
        else
            return false;
    }

    /**
     * @param int $category_id
     * @param string $element
     * @return array|bool
     */
    public function getAssignedElements($category_id, $element)
    {
        if (in_array($element, $this->elements, true)) {
            switch ($element) {
                case 'templates':
                    $elements = SiteTemplate::where('category', $category_id)->get()->toArray();
                    $_elements_count = count($elements);
                    for ($i = 0; $i < $_elements_count; $i++) {
                        $elements[$i]['name'] = $elements[$i]['templatename'];
                    }
                    break;
                case  'tmplvars':
                    $elements = SiteTmplvar::where('category', $category_id)->get()->toArray();
                    break;
                case 'htmlsnippets':
                    $elements = SiteHtmlsnippet::where('category', $category_id)->get()->toArray();
                    break;
                case 'snippets':
                    $elements = SiteSnippet::where('category', $category_id)->get()->toArray();
                    break;
                case 'plugins':
                    $elements = SitePlugin::where('category', $category_id)->get()->toArray();
                    break;
                case 'modules':
                    $elements = SiteModule::where('category', $category_id)->get()->toArray();
                    break;

            }


            return $elements;
        }

        return false;
    }

    /**
     * @param int $category_id
     * @return array
     */
    public function getAllAssignedElements($category_id)
    {
        $elements = [];
        foreach ($this->elements as $element) {
            $elements[$element] = $this->getAssignedElements($category_id, $element);
        }

        return $elements;
    }

    /**
     * @param int $category_id
     * @return bool
     */
    public function deleteCategory($category_id)
    {
        $_update = ['category' => 0];
        SiteTemplate::where('category', $category_id)->update($_update);

        SiteTmplvar::where('category', $category_id)->update($_update);

        SiteHtmlsnippet::where('category', $category_id)->update($_update);

        SiteSnippet::where('category', $category_id)->update($_update);

        SitePlugin::where('category', $category_id)->update($_update);

        SiteModule::where('category', $category_id)->update($_update);

        Category::where('id', $category_id)->delete();


        return true;
    }

    /**
     * @param int $category_id
     * @param array $data
     * @return bool
     */
    public function updateCategory($category_id, $data = [])
    {
        if (empty($data) || empty($category_id)) {
            return false;
        }

        $_update = [
            'category' => $data['category'],
            'rank' => (int)$data['rank']
        ];
        $category = Category::query()->find($category_id);

        if (!is_null($category)) {
            $category->update($_update);
            return true;
        }

        return false;
    }

    /**
     * @param string $category_name
     * @param int $category_rank
     * @return bool|int|mixed
     */
    public function addCategory($category_name, $category_rank)
    {
        if ($this->isCategoryExists($category_name)) {
            return false;
        }
        $catId = Category::query()->insertGetId(['category' => $category_name, 'rank' => $category_rank]);

        if (is_numeric($catId)) {
            return $catId;
        }

        return false;
    }

    /**
     * @param string $category_name
     * @return bool|int|string
     */
    public function isCategoryExists($category_name)
    {
        $category = Category::where('category', $category_name)->first();

        if (!is_null($category)) {
            return $category->id;
        }

        return false;
    }
}
