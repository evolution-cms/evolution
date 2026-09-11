<?php

declare(strict_types=1);

namespace EvolutionCMS\Controllers;

use EvolutionCMS\Interfaces\ManagerTheme;
use EvolutionCMS\Models\Category;
use EvolutionCMS\Models\SiteModule;
use EvolutionCMS\Support\ContextMenu;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Modules extends AbstractController implements ManagerTheme\PageControllerInterface
{
    /**
     * @var string
     */
    protected $view = 'page.modules';

    /**
     * @return bool
     */
    public function canView(): bool
    {
        return $this->managerTheme->getCore()
            ->hasAnyPermissions([
                'exec_module',
                'new_module',
                'edit_module',
                'save_module',
                'delete_module'
            ]);
    }

    /**
     * @return string|null
     */
    public function checkLocked(): ?string
    {
        return null;
    }

    /**
     * @return bool
     */
    public function process(): bool
    {
        $this->parameters = [
            'contextMenu' => $this->getContextMenu(),
            'modules' => $this->getModules()
        ];

        return true;
    }

    /**
     * @return array
     */
    protected function getContextMenu(): array
    {
        // context menu
        $cm = new ContextMenu('cntxm', 150);

        $cm->addItem(__('global.run_module'), "js:menuAction(1)", $this->managerTheme->getStyle('icon_play'), (!$this->managerTheme->getCore()
            ->hasPermission('exec_module') ? 1 : 0));
        if ($this->managerTheme->getCore()
            ->hasAnyPermissions([
                'new_module',
                'edit_module',
                'delete_module'
            ])) {
            $cm->addSeparator();
        }
        $cm->addItem(__('global.edit'), 'js:menuAction(2)', $this->managerTheme->getStyle('icon_edit'), (!$this->managerTheme->getCore()
            ->hasPermission('edit_module') ? 1 : 0));
        $cm->addItem(__('global.duplicate'), 'js:menuAction(3)', $this->managerTheme->getStyle('icon_clone'), (!$this->managerTheme->getCore()
            ->hasPermission('new_module') ? 1 : 0));
        $cm->addItem(__('global.delete'), 'js:menuAction(4)', $this->managerTheme->getStyle('icon_trash'), (!$this->managerTheme->getCore()
            ->hasPermission('delete_module') ? 1 : 0));

        return [
            'menu' => $cm->render(),
            'script' => $cm->getClientScriptObject()
        ];
    }

    protected function getModules() : Collection
    {
        return SiteModule::query()
            ->orderBy('name', 'ASC')
            ->withoutProtected()
            ->lockedView()
            ->get();
    }

    /**
     * @return Collection
     */
    protected function getCategories(): Collection
    {
        // the eager load is constrained too, otherwise a category the user can
        // see would list the modules inside it that they may not run
        $roleId = (int) get_by_key($_SESSION, 'mgrRole', 0);

        return Category::with(['modules' => function ($builder) use ($roleId) {
                return $builder->allowedForRole($roleId);
            }])
            ->whereHas('modules', function (Builder $builder) {
                return $builder->withoutProtected()->lockedView();
            })
            ->orderBy('rank', 'ASC')
            ->get();
    }
}
