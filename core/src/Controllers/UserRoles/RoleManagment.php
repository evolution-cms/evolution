<?php namespace EvolutionCMS\Controllers\UserRoles;

use EvolutionCMS\Interfaces\ManagerTheme;
use EvolutionCMS\Controllers\AbstractResources;

class RoleManagment extends AbstractResources implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.role_management';

    /**
     * Don't change tab index !!!
     */
    protected $tabs = [
        0 => RoleList::class,
        1 => PermissionsGroupsList::class,
        2 => PermissionsList::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function canView(): bool
    {
        return true;
    }

    public function getParameters(array $params = []) : array
    {
        $tabs = [];
        $tab = $this->needTab();
        if ($tab === 0) {
            $tab = key($this->tabs);
        }
        foreach ($this->tabs as $tabN => $tabClass) {
            if (($tabController = $this->makeTab($tabClass, $tabN)) !== null) {

                if ((string)$tab !== (string)$tabN) {
                    $tabController->setNoData();
                } else {
                    $activeTab = $tabController->getTabName();
                }
                $tabs[$tabController->getTabName()] = $tabController;
            }
        }

        return array_merge(compact('tabs'), parent::getParameters($params), compact('activeTab'));
    }

    protected function makeTab($tabClass, ?int $index = null) :? ManagerTheme\TabControllerInterface
    {

        $tabController = null;
        if (class_exists($tabClass) &&
            is_a($tabClass, ManagerTheme\TabControllerInterface::class, true)
        ) {
            /** @var ManagerTheme\TabControllerInterface $tabController */
            $tabController = new $tabClass($this->managerTheme);
            $tabController->setIndex($index);
            if (! $tabController->canView()) {
                $tabController = null;
            }
        }

        return $tabController;
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $params = []) : string
    {
        if (!is_ajax() || ($tab = $this->needTab()) === null) {
            return parent::render($params);
        }

        $index = array_search($tab, $this->tabs, true);
        $tabController = $this->makeTab($this->tabs[$tab], $index);
        if (($index !== false && $tabController !== null)) {
            return $tabController->render(
                $tabController->getParameters()
            );
        }
        return '';
    }

    protected function needTab()
    {
        return get_by_key(
            $_GET
            , 'tab'
            , 0
            , function ($val) {
            return is_numeric($val) && array_key_exists($val, $this->tabs);
        });
    }
}
