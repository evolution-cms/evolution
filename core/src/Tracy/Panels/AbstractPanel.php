<?php namespace EvolutionCMS\Tracy\Panels;

use EvolutionCMS\Interfaces\TracyPanel;
use Tracy\Helpers;
use Tracy\IBarPanel;
use EvolutionCMS\Tracy\Template;
use EvolutionCMS\Interfaces\CoreInterface;

/**
 * @see: https://github.com/recca0120/laravel-tracy
 */
abstract class AbstractPanel implements IBarPanel, TracyPanel
{
    /**
     * $attributes.
     *
     * @var mixed
     */
    protected $attributes;
    /**
     * $viewPath.
     *
     * @var string
     */
    protected $viewPath = null;
    /**
     * $template.
     *
     * @var Template
     */
    protected $template;
    /**
     * $evolution description.
     *
     * @var CoreInterface
     */
    protected $evolution;
    /**
     * __construct.
     *
     * @param Template $template
     */
    public function __construct(?Template $template = null)
    {
        $this->template = $template ?: new Template;
    }

    /**
     * setEvolutionCMS.
     *
     * @param CoreInterface $evolution
     * @return $this
     */
    public function setEvolutionCMS(?CoreInterface $evolution = null)
    {
        if ($evolution !== null) {
            $this->evolution = $evolution;
        }

        if ($this->hasEvolutionCMS() && method_exists($this, 'subscribe')) {
            $this->subscribe();
        }

        return $this;
    }
    /**
     * Renders HTML code for custom tab.
     *
     * @return string
     */
    public function getTab()
    {
        return $this->render('tab');
    }
    /**
     * Renders HTML code for custom panel.
     *
     * @return string
     */
    public function getPanel()
    {
        return $this->render('panel');
    }

    /**
     * @return bool
     *@deprecated
     * @TODO: remove in 3.7
     * has subscribe method
     *
     */
    protected function hasSubscribeMethod() : bool
    {
        return method_exists($this, 'subscribe');
    }

    /**
     * has evolutionCms.
     *
     * @return bool
     */
    protected function hasEvolutionCMS()
    {
        return is_a($this->evolution, CoreInterface::class);
    }
    /**
     * render.
     *
     * @param string $view
     * @return string
     */
    protected function render($view)
    {
        $view = $this->getViewPath().$view.'.php';
        if (empty($this->attributes) === true) {
            $this->template->setAttributes(
                $this->attributes = $this->getAttributes()
            );
        }
        return $this->template->render($view);
    }
    /**
     * getViewPath.
     *
     * @return string
     */
    public function getViewPath()
    {
        if ($this->viewPath !== null) {
            return $this->viewPath;
        }

        $class_info = new \ReflectionClass($this);
        return $this->viewPath = dirname($class_info->getFileName()) . '/assets/';
    }
    /**
     * getAttributes.
     *
     * @return array
     */
    abstract protected function getAttributes();
    /**
     * Use a backtrace to search for the origin of the query.
     *
     * @return string|array
     */
    protected static function findSource()
    {
        $source = '';
        $trace = debug_backtrace(PHP_VERSION_ID >= 50306 ? DEBUG_BACKTRACE_IGNORE_ARGS : false);
        foreach ($trace as $row) {
            if (isset($row['file']) === false) {
                continue;
            }
            if (isset($row['function']) === true && strpos($row['function'], 'call_user_func') === 0) {
                continue;
            }
            if (isset($row['class']) === true && (
                    is_subclass_of($row['class'], '\Tracy\IBarPanel') === true ||
                    strpos(str_replace('/', '\\', $row['file']), 'Illuminate\\') !== false
                )) {
                continue;
            }
            $source = [$row['file'], (int) $row['line']];
        }
        return $source;
    }
    /**
     * editor link.
     *
     * @param string|array $source
     * @return string
     */
    protected static function editorLink($source)
    {
        if (is_string($source) === true) {
            $file = $source;
            $line = null;
        } else {
            $file = $source[0];
            $line = $source[1];
        }
        return Helpers::editorLink($file, $line);
    }
}
