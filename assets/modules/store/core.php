<?php
@ini_set('display_errors', '0');
error_reporting(0);

if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true || !$modx->hasPermission('exec_module')) {
    die('<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.');
}

$version = "0.2.0";
$Store = new Store;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$response = $Store->moduleActionService()->handle($Store, $action, $_REQUEST, $_FILES, $_GET, $_POST);
if (!empty($response['handled'])) {
    if (!empty($response['content_type'])) {
        header('Content-Type: ' . $response['content_type']);
    }
    if (array_key_exists('body', $response)) {
        echo $response['body'];
    }
    if (!empty($response['terminate'])) {
        exit();
    }
}

echo $Store->moduleViewService()->render($Store, $version);


class Store
{
    public $lang;
    public $language;

    protected $modulePath;
    protected $contextService;
    protected $catalogService;
    protected $installedStateService;
    protected $legacyDeleteService;
    protected $packageInstallFlowService;
    protected $schedulerHealthService;
    protected $workerHealthService;
    protected $systemTaskService;
    protected $moduleActionService;
    protected $moduleViewService;

    public function __construct()
    {
        $modx = EvolutionCMS();
        $this->modulePath = __DIR__;
        $lang = $modx->config['manager_language'];
        $this->lang = $this->contextService()->loadLanguage($this->modulePath, $lang);
        $this->language = $this->contextService()->getLanguageCode($lang);
    }

    protected function contextService()
    {
        if ($this->contextService === null) {
            $this->contextService = new \EvolutionCMS\Services\Store\StoreContextService();
        }

        return $this->contextService;
    }

    protected function catalogService()
    {
        if ($this->catalogService === null) {
            $this->catalogService = new \EvolutionCMS\Services\Store\CatalogService($this->installedStateService(), $this->lang);
        }

        return $this->catalogService;
    }

    protected function installedStateService()
    {
        if ($this->installedStateService === null) {
            $this->installedStateService = new \EvolutionCMS\Services\Store\InstalledStateService();
        }

        return $this->installedStateService;
    }

    protected function legacyDeleteService()
    {
        if ($this->legacyDeleteService === null) {
            $this->legacyDeleteService = new \EvolutionCMS\Services\Store\LegacyDeleteService(__DIR__, $this->lang);
        }

        return $this->legacyDeleteService;
    }

    public function packageInstallFlowService()
    {
        if ($this->packageInstallFlowService === null) {
            $this->packageInstallFlowService = new \EvolutionCMS\Services\Store\PackageInstallFlowService(__DIR__, $this->lang);
        }

        return $this->packageInstallFlowService;
    }

    public function schedulerHealthService()
    {
        if ($this->schedulerHealthService === null) {
            $this->schedulerHealthService = new \EvolutionCMS\Services\SystemTasks\SchedulerHealthService();
        }

        return $this->schedulerHealthService;
    }

    public function workerHealthService()
    {
        if ($this->workerHealthService === null) {
            $this->workerHealthService = new \EvolutionCMS\Services\SystemTasks\WorkerHealthService();
        }

        return $this->workerHealthService;
    }

    public function systemTaskService()
    {
        if ($this->systemTaskService === null) {
            $this->systemTaskService = new \EvolutionCMS\Services\SystemTasks\SystemTaskService($this->catalogService());
        }

        return $this->systemTaskService;
    }

    public function isSuperAdmin()
    {
        return $this->contextService()->isSuperAdmin();
    }

    public function getRequesterSnapshot()
    {
        return $this->contextService()->buildRequesterSnapshot();
    }

    public function getSystemTaskUiFlags()
    {
        return $this->contextService()->getSystemTaskUiFlags();
    }

    public function refreshCurrentManagerPermissions()
    {
        return $this->contextService()->refreshCurrentManagerPermissions();
    }

    public static function parse($tpl, $fields)
    {
        $modx = EvolutionCMS();
        $tpl = $modx->parseText($tpl, $fields);
        $evtOut = $modx->invokeEvent('OnManagerMainFrameHeaderHTMLBlock');
        $onManagerMainFrameHeaderHTMLBlock = is_array($evtOut) ? implode("\n", $evtOut) : '';
        $tpl = str_replace('[+onManagerMainFrameHeaderHTMLBlock+]', $onManagerMainFrameHeaderHTMLBlock, $tpl);
        return $tpl;
    }

    public function tpl($file)
    {
        $lang = $this->lang;
        ob_start();
        include($file);
        $tpl = ob_get_contents();
        ob_end_clean();
        return $tpl;
    }

    public function getModulePath()
    {
        return $this->modulePath;
    }

    public function loadTemplate($file)
    {
        return $this->tpl($file);
    }

    public function parseTemplate($tpl, $fields)
    {
        return self::parse($tpl, $fields);
    }

    public function getConsoleCatalog()
    {
        return $this->catalogService()->getConsoleCatalog();
    }

    public function getConsoleReadmePayload($repo, $branch = '', $sourceUrl = '')
    {
        return $this->catalogService()->getConsoleReadmePayload($repo, $branch, $sourceUrl);
    }

    public function getLegacyInstalledState()
    {
        return $this->installedStateService()->getLegacyInstalledState();
    }

    public function getConsoleInstalledState()
    {
        return $this->installedStateService()->getConsoleInstalledState();
    }

    public function buildLegacyDeletePreview($cid, $file, $name = '', $installedVersion = '')
    {
        return $this->legacyDeleteService()->buildLegacyDeletePreview($cid, $file, $name, $installedVersion);
    }

    public function runLegacyDelete($token, array $selection)
    {
        return $this->legacyDeleteService()->runLegacyDelete($token, $selection);
    }

    public function moduleActionService()
    {
        if ($this->moduleActionService === null) {
            $this->moduleActionService = new \EvolutionCMS\Services\Store\ModuleActionService();
        }

        return $this->moduleActionService;
    }

    public function moduleViewService()
    {
        if ($this->moduleViewService === null) {
            $this->moduleViewService = new \EvolutionCMS\Services\Store\ModuleViewService();
        }

        return $this->moduleViewService;
    }
}
