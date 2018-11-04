<?php

require_once(MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');

/**
 * Class AssetsHelper
 */
class AssetsHelper
{
    /**
     * Объект DocumentParser - основной класс MODX
     * @var \DocumentParser
     * @access protected
     */
    protected $modx = null;
    protected $fs = null;

    /**
     * @var AssetsHelper cached reference to singleton instance
     */
    protected static $instance;

    /**
     * gets the instance via lazy initialization (created on first usage)
     *
     * @return self
     */
    public static function getInstance(DocumentParser $modx)
    {

        if (null === self::$instance) {
            self::$instance = new self($modx);
        }

        return self::$instance;
    }

    /**
     * is not allowed to call from outside: private!
     *
     */
    private function __construct(DocumentParser $modx)
    {
        $this->modx = $modx;
        $this->fs = \Helpers\FS::getInstance();
    }

    /**
     * prevent the instance from being cloned
     *
     * @return void
     */
    private function __clone()
    {

    }

    /**
     * prevent from being unserialized
     *
     * @return void
     */
    private function __wakeup()
    {

    }

    /**
     * @return string
     */
    public function registerJQuery()
    {
        $output = '';
        $plugins = $this->modx->pluginEvent;
        //файл проверяется чтобы определить новую админку
        if (file_exists(MODX_MANAGER_PATH . 'media/script/jquery/jquery.min.js') || array_search('ManagerManager', $plugins['OnDocFormRender']) !== false) {
            return $output;
        }

        $output .= $this->registerScript('jQuery', array(
                'src'     => 'assets/js/jquery/jquery-1.9.1.min.js',
                'version' => '1.9.1'
            ));
        $output .= '<script type="text/javascript">var jQuery = jQuery.noConflict(true);</script>';

        return $output;
    }

    /**
     * @param $name
     * @param $params
     * @return string
     */
    public function registerScript($name, $params)
    {
        $out = '';
        if (!isset($this->modx->loadedjscripts[$name])) {
            $src = $params['src'];
            $remote = strpos($src, 'http') === 0 || strpos($src, '//') === 0;
            if (!$remote) {
                $src = $this->modx->config['site_url'] . $src;
                if (!$this->fs->checkFile($params['src'])) {
                    $this->modx->logEvent(0, 3, 'Cannot load ' . $src, 'Assets helper');

                    return $out;
                }
            }

            $tmp = explode('.', $src);
            $type = isset($params['type']) ? $params['type'] : end($tmp);
            if ($type == 'js') {
                $out = '<script type="text/javascript" src="' . $src . '"></script>';
            } else {
                $media = isset($params['media']) ? " media=\"{$params['media']}\"" : '';
                $out = "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$src}\"{$media}>";
            }

            $this->modx->loadedjscripts[$name] = $params;

        }

        return $out;
    }

    /**
     * @param array $list
     * @return string
     */
    public function registerScriptsList($list = array())
    {
        $out = '';
        if (!is_array($list)) return $out;

        foreach ($list as $script => $params) {
            $out .= $this->registerScript($script, $params);
        }

        return $out;
    }
}
