<?php namespace EvolutionCMS;

use EvolutionCMS\Interfaces\ManagerThemeInterface;
use EvolutionCMS\Interfaces\CoreInterface;
use Exception;

class ManagerTheme implements ManagerThemeInterface
{
    /**
     * @var CoreInterface
     */
    protected $core;

    /**
     * @var $theme
     */
    protected $theme;

    protected $templateNamespace = 'manager';

    public function __construct(CoreInterface $core, $theme = '')
    {
        $this->core = $core;

        if (empty($theme)) {
            $theme = $this->core->getConfig('manager_theme');
        }

        $this->theme = $theme;

        $this->loadSnippets();

        $this->loadChunks();
    }

    public function loadSnippets()
    {
        $found = $this->core->findElements(
            'chunk',
            MODX_MANAGER_PATH . 'media/style/' . $this->theme . '/snippets/',
            array('php')
        );
        foreach ($found as $name => $code) {
            $this->addSnippet($name, $code);
        }
    }

    public function loadChunks()
    {
        $found = $this->core->findElements(
            'chunk',
            MODX_MANAGER_PATH . 'media/style/' . $this->theme . '/chunks/',
            array('tpl', 'html')
        );
        foreach ($found as $name => $code) {
            $this->addChunk($name, $code);
        }
    }

    public function addSnippet($name, $code)
    {
        $this->core->addSnippet(
            $name,
            $code,
            $this->templateNamespace . '#',
            array(
                'managerTheme' => $this
            )
        );
    }

    public function addChunk($name, $code)
    {
        $this->core->addChunk($name, $code, $this->templateNamespace . '#');
    }
}
