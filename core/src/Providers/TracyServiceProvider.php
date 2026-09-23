<?php namespace EvolutionCMS\Providers;

use Illuminate\Support\ServiceProvider;
use EvolutionCMS\Tracy\Debugger;
use EvolutionCMS\Interfaces\TracyPanel;
use Tracy\IBarPanel;

if (session_status() == PHP_SESSION_NONE && (!defined('EVO_SESSION') || !EVO_SESSION) && !(class_exists('EvoSessionProxy', false) && \EvoSessionProxy::disabled())) {
    session_start();
}

/**
 * Configure Tracy error handling and debug panels for the current request.
 *
 * Activation is controlled by tracy.active; visibility by tracy.hidden.
 */
class TracyServiceProvider extends ServiceProvider
{
    /**
     * Activate Tracy when the current request matches tracy.active.
     *
     * @return void
     */
    public function register()
    {
        if ($this->isTracyHandler()) {
            $this->activateTracy();
        }
    }

    /**
     * Initialize the debugger, fatal-error logging, templates and panels.
     */
    protected function activateTracy(): void
    {
        Debugger::enable($this->isHiddenTracyHandler(), $this->logPath());
        Debugger::$onFatalError[] = function (\Throwable $exception): void {
            $this->app['log']->error($exception->getMessage(), ['exception' => $exception]);
        };
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

    /**
     * Return the directory used for Tracy error logs.
     */
    protected function logPath(): string
    {
        return evo()->storagePath() . '/logs';
    }

    /**
     * Resolve the activation setting and return its request-specific result.
     */
    protected function isTracyHandler(): bool
    {
        $this->prepareActiveTracy();
        return $this->app['config']->get('tracy.active');
    }

    /**
     * Return the visibility mode passed to Debugger::enable().
     *
     * false enables debugging; true enables production error handling only.
     * A string or array restricts debugging to the configured IP addresses,
     * optionally using Tracy's SECRET@IP syntax.
     *
     * @return bool|string|string[]
     */
    public function isHiddenTracyHandler()
    {
        return $this->app['config']->get('tracy.hidden');
    }

    /**
     * Whether debug output includes source locations.
     */
    protected function isShowLocation(): bool
    {
        return true;
    }

    /**
     * Whether Tracy treats notices and warnings as fatal errors.
     */
    protected function isStrictMode(): bool
    {
        return false;
    }

    /**
     * Apply the optional tracy.error.500 template relative to EVO_BASE_PATH.
     */
    protected function registerErrorTpl(): void
    {
        $errorTpl = $this->app['config']->get('tracy.error.500');
        if ($errorTpl !== null) {
            Debugger::$errorTemplate = EVO_BASE_PATH . $errorTpl;
        }
    }

    /**
     * Resolve tracy.active against the current manager session.
     *
     * Supported values:
     * - bool: enable or disable Tracy directly.
     * - int[]: allow authenticated manager user IDs, e.g. [1, 5, 12].
     *   IDs are matched strictly against integers; an empty array allows nobody.
     * - 'manager': require isLoggedIn('mgr').
     * - 'admin': additionally require manager role ID 1, not user ID 1.
     * - 'managerfrontonly' / 'adminfrontonly': apply the corresponding rule
     *   and exclude /manager/index.php and /manager/media/browser/mcpuk/browse.php.
     *
     * Unknown strings resolve to false. Array and string settings are replaced
     * with their boolean result in the configuration for this request.
     * Session-dependent checks belong here, after session restoration, rather
     * than in the configuration file loaded during application bootstrap.
     */
    protected function prepareActiveTracy(): void
    {
        $flag = $this->app['config']->get('tracy.active');
        if (\is_array($flag)) {
            $userId = (int) ($_SESSION['mgrInternalKey'] ?? 0);
            $this->app['config']->set(
                'tracy.active',
                $this->app->isLoggedIn('mgr')
                && $userId > 0
                && \in_array($userId, $flag, true)
            );

            return;
        }

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

    /**
     * Return configured panel classes, or an empty array for a non-array setting.
     *
     * @return array<array-key, class-string<IBarPanel>>
     */
    protected function listPanels(): array
    {
        $panels = $this->app['config']->get('tracy.panels');
        return \is_array($panels) ? $panels : [];
    }

    /**
     * Instantiate and register each configured panel.
     *
     * @param array<array-key, class-string<IBarPanel>> $panels
     */
    protected function registerPanels(array $panels): void
    {
        foreach ($panels as $panel) {
            $this->injectPanel(new $panel);
        }
    }

    /**
     * Supply the CMS instance to compatible panels and add them to Tracy's bar.
     *
     * @param IBarPanel $panel Panel instance to register.
     */
    protected function injectPanel(IBarPanel $panel): void
    {
        if (is_a($panel, TracyPanel::class)) {
            $panel->setEvolutionCMS($this->app);
        }
        Debugger::getBar()->addPanel($panel);
    }
}
