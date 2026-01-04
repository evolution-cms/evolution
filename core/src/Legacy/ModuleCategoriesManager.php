<?php namespace EvolutionCMS\Legacy;

use EvolutionCMS\Models\SiteHtmlsnippet;
use EvolutionCMS\Models\SiteModule;
use EvolutionCMS\Models\SitePlugin;
use EvolutionCMS\Models\SiteSnippet;
use EvolutionCMS\Models\SiteTemplate;
use EvolutionCMS\Models\SiteTmplvar;

class ModuleCategoriesManager extends Categories
{
    /**
     * @var array
     */
    public $params = [];
    /**
     * @var array
     */
    public $translations = [];
    /**
     * @var array
     */
    public $new_translations = [];


    /**
     * Set a paramter key and its value
     *
     * @param string $key paramter key
     * @param mixed $value parameter value - could be mixed value-types
     * @return null
     */
    public function set($key, $value)
    {
        $this->params[$key] = $value;

        return null;
    }


    /**
     * Get a parameter value
     *
     * @param string $key Paramter-key
     * @return  string           return the parameter value if exists, otherwise false
     */
    public function get($key)
    {
        $modx = evo();

        if (isset($this->params[$key])) {
            return $this->params[$key];
        } elseif (isset($modx->config[$key])) {
            return $modx->config[$key];
        } elseif (isset($modx->event->params[$key])) {
            return $modx->event->params[$key];
        }

        return false;
    }


    /**
     * @param string $message
     * @param string $namespace
     */
    public function addMessage($message, $namespace = 'default')
    {
        $this->params['messages'][$namespace][] = $message;
    }


    /**
     * @param string $namespace
     * @return bool
     */
    public function getMessages($namespace = 'default')
    {
        if (isset($this->params['messages'][$namespace])) {
            return $this->params['messages'][$namespace];
        }

        return false;
    }


    /**
     * @param string $view_name
     * @param array $data
     */
    public function renderView($view_name, $data = [])
    {
        global $_lang, $_style;

        $filename = trim($view_name) . '.tpl.phtml';
        $file = self::get('views_dir') . $filename;
        $view = &$this;

        if (is_file($file)
            && is_readable($file)) {
            include $file;
        } else {
            echo 'View "' . self::get('views_dir') . '<strong>' . $filename . '</strong>" not found.';
        }
    }

    /**
     * @param string $element
     * @param int $element_id
     * @param int $category_id
     * @return bool
     */
    public function updateElement($element, $element_id, $category_id)
    {

        $_update = [
            'category' => (int)$category_id
        ];
        switch ($element) {
            case 'templates':
                SiteTemplate::where('id', $element_id)->update($_update);
                break;
            case  'tmplvars':
                $elements = SiteTmplvar::where('id', $element_id)->update($_update);
                break;
            case 'htmlsnippets':
                $elements = SiteHtmlsnippet::where('id', $element_id)->update($_update);
                break;
            case 'snippets':
                $elements = SiteSnippet::where('id', $element_id)->update($_update);
                break;
            case 'plugins':
                $elements = SitePlugin::where('id', $element_id)->update($_update);
                break;
            case 'modules':
                $elements = SiteModule::where('id', $element_id)->update($_update);
                break;

        }


        return true;
    }


    /**
     * @param string $txt
     * @return string
     */
    public function txt($txt)
    {
        global $_lang;
        if (isset($_lang[$txt])) {
            return $_lang[$txt];
        }

        return $txt;
    }
}
