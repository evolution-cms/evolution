<?php namespace EvolutionCMS\Providers;

use Illuminate\Support\ServiceProvider;
use EvolutionCMS\Tracy\Debugger;
use EvolutionCMS\Interfaces\TracyPanel;
use Tracy\IBarPanel;

if (session_status() == PHP_SESSION_NONE && (!defined('EVO_SESSION') || !EVO_SESSION)) {
    session_start();
}

class TracyServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if ($this->isTracyHandler()) {
            $this->activateTracy();
        }
    }

    protected function activateTracy(): void
    {
        Debugger::enable($this->isHiddenTracyHandler(), $this->logPath());
        Debugger::$strictMode = $this->isStrictMode();
        Debugger::$showLocation = $this->isShowLocation();
        Debugger::$maxDepth = 20;
        Debugger::$maxLength = 200;
        if($this->app['config']->get('tracy.editor')) {
            Debugger::$editor = $this->app['config']->get('tracy.editor');
        }
        if($this->app['config']->get('tracy.editorMapping')) {
            Debugger::$editorMapping = $this->app['config']->get('tracy.editorMapping');
        }

        $this->registerErrorTpl();
        $this->registerPanels($this->listPanels());
    }

    protected function logPath(): string
    {
        return evo()->storagePath() . '/logs';
    }

    protected function isTracyHandler(): bool
    {
        $this->prepareActiveTracy();
        return $this->app['config']->get('tracy.active');
    }

    /**
     * @return mixed
     */
    public function isHiddenTracyHandler()
    {
        return $this->app['config']->get('tracy.hidden');
    }

    protected function isShowLocation(): bool
    {
        return true;
    }

    protected function isStrictMode(): bool
    {
        return false;
    }

    protected function registerErrorTpl(): void
    {
        $errorTpl = $this->app['config']->get('tracy.error.500');
        if ($errorTpl !== null) {
            Debugger::$errorTemplate = EVO_BASE_PATH . $errorTpl;
        }
    }

    protected function prepareActiveTracy(): void
    {
        $flag = $this->app['config']->get('tracy.active');
        if (\is_string($flag)) {
            $newFlag = false;

            switch ($flag) {
                case 'manager':
                    if ($this->app->isLoggedIn('mgr')) {
                        $newFlag = true;
                    }
                    break;
                case 'admin':
                    if ($this->app->isLoggedIn('mgr') && isset($_SESSION['mgrRole']) && $_SESSION['mgrRole'] == 1) {
                        $newFlag = true;
                    }
                    break;
                case 'adminfrontonly':
                    if ($this->app->isLoggedIn('mgr') && isset($_SESSION['mgrRole']) && $_SESSION['mgrRole'] == 1 && ($_SERVER['SCRIPT_NAME'] != '/manager/index.php' &&  $_SERVER['SCRIPT_NAME'] != '/manager/media/browser/mcpuk/browse.php')) {
                        $newFlag = true;
                    }
                    break;
                case 'managerfrontonly':
                    if ($this->app->isLoggedIn('mgr') && ($_SERVER['SCRIPT_NAME'] != '/manager/index.php' &&  $_SERVER['SCRIPT_NAME'] != '/manager/media/browser/mcpuk/browse.php')) {
                        $newFlag = true;
                    }
                    break;
            }

            $this->app['config']->set('tracy.active', $newFlag);
        }
    }

    protected function listPanels(): array
    {
        $panels = $this->app['config']->get('tracy.panels');
        return \is_array($panels) ? $panels : [];
    }

    protected function registerPanels(array $panels): void
    {
        foreach ($panels as $panel) {
            $this->injectPanel(new $panel);
        }
    }

    protected function injectPanel(IBarPanel $panel): void
    {
        if (is_a($panel, TracyPanel::class)) {
            $panel->setEvolutionCMS($this->app);
        }
        Debugger::getBar()->addPanel($panel);
    }
}
