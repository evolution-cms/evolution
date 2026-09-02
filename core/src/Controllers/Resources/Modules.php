<?php namespace EvolutionCMS\Controllers\Resources;

use EvolutionCMS\Models;
use EvolutionCMS\Controllers\AbstractResources;
use EvolutionCMS\Interfaces\ManagerTheme\TabControllerInterface;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent;

//'actions'=>array('edit'=>array(108,'edit_module'), 'duplicate'=>array(111,'new_module'), 'remove'=>array(110,'delete_module')),
class Modules extends AbstractResources implements TabControllerInterface
{
    protected $view = 'page.resources.modules';
    /**
     * {@inheritdoc}
     */
    public function getTabName($withIndex = true): string
    {
        if ($withIndex) {
            return 'tabModules-' . $this->getIndex();
        }

        return 'tabModules';
    }

    /**
     * {@inheritdoc}
     */
    public function canView(): bool
    {
        return $this->managerTheme->getCore()->hasAnyPermissions([
            'exec_module',
            'new_module',
            'edit_module',
            'save_module',
            'delete_module'
        ]);
    }

    protected function getBaseParams()
    {
        return array_merge(
            parent::getParameters(),
            [
                'tabPageName' => $this->getTabName(false),
                'tabIndexPageName' => $this->getTabName()
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(array $params = []) : array
    {
        $params = array_merge($this->getBaseParams(), $params);

        if ($this->isNoData()) {
            return $params;
        }

        return array_merge([
            'categories' => $this->parameterCategories(),
            'outCategory' => $this->parameterOutCategory(),
            'action' => $this->parameterActionName()
        ], $params);
    }

    protected function parameterOutCategory() : Collection
    {
        return Models\SiteModule::query()
            ->where('site_modules.category', '=', 0)
            ->withoutProtected()
            ->orderBy('site_modules.name', 'ASC')
            ->lockedView()
            ->get();
    }

    protected function parameterCategories() : Collection
    {
        // the eager load is constrained too, otherwise a category the user can
        // see would list the modules inside it that they may not run
        $roleId = (int) get_by_key($_SESSION, 'mgrRole', 0);

        return Models\Category::with(['modules' => function ($builder) use ($roleId) {
                return $builder->allowedForRole($roleId);
            }])
            ->whereHas('modules', function (Eloquent\Builder $builder) use ($roleId) {
                return $builder->lockedView()->allowedForRole($roleId);
            })->orderBy('rank', 'ASC')
            ->get();
    }

    protected function parameterActionName() : string
    {
        if ($this->managerTheme->getCore()->hasPermission('edit_module')) {
            return 'actions.edit';
        }
        if ($this->managerTheme->getCore()->hasPermission('exec_module')) {
            return 'actions.run';
        }
        return '';
    }
}
