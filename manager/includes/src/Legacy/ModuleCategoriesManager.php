<?php namespace EvolutionCMS\Legacy;

class ModuleCategoriesManager extends Categories
{
    /**
     * @var array
     */
    public $params = array();
    /**
     * @var array
     */
    public $translations = array();
    /**
     * @var array
     */
    public $new_translations = array();


    /**
     * Set a paramter key and its value
     *
     * @return null
     * @param string $key paramter key
     * @param mixed $value parameter value - could be mixed value-types
     */
    public function set($key, $value)
    {
        $this->params[$key] = $value;

        return null;
    }


    /**
     * Get a parameter value
     *
     * @return  string           return the parameter value if exists, otherwise false
     * @param   string $key Paramter-key
     */
    public function get($key)
    {
        $modx = evolutionCMS();

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
    public function renderView($view_name, $data = array())
    {
        global $_lang, $_style;

        $filename = trim($view_name) . '.tpl.phtml';
        $file = self::get('views_dir') . $filename;
        $view = &$this;

        if (is_file($file)
            && is_readable($file)) {
            include $file;
        } else {
            echo sprintf(
                'View "%s<strong>%s</strong>" not found.',
                self::get('views_dir'),
                $filename
            );
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

        $_update = array(
            'id'       => (int)$element_id,
            'category' => (int)$category_id
        );

        $this->getDatabase()->update(
            $_update,
            $this->db_tbl[$element],
            "`id` = '" . (int)$element_id . "'"
        );

        return $this->getDatabase()->getAffectedRows() === 1;
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
