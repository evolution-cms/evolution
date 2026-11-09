<?php namespace EvolutionCMS\Controllers\UserRoles;

use EvolutionCMS\Controllers\AbstractController;
use EvolutionCMS\Models;
use EvolutionCMS\Support\ModuleAccess;
use EvolutionCMS\Support\RoleGuard;
use EvolutionCMS\Interfaces\ManagerTheme;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent;

class UserRole extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.user_roles.user_role';

    /**
     * {@inheritdoc}
     */
    public function checkLocked(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function canView(): bool
    {
        if (!$this->managerTheme->getCore()->hasPermission('edit_role')) {
            return false;
        }
        if (!$this->managerTheme->getCore()->hasPermission('new_role')) {
            return false;
        }
        return true;
    }

    public function process(): bool
    {
        if(isset($_GET['action']) && $_GET['action'] == 'delete' ){
            $id = $this->getElementId();
            if (!$this->managerTheme->getCore()->hasPermission('delete_role')) {
                $this->managerTheme->alertAndQuit('error_no_privileges');
            }
            $this->guardRoleReach($id);
            // Refuse to delete a role still assigned to users: the delete does not reassign
            // them, so they would be left pointing at a role that no longer exists.
            if (Models\UserAttribute::where('role', $id)->exists()) {
                $this->managerTheme->alertAndQuit('role_in_use_message');
            }
            Models\RolePermissions::query()->where('role_id', $id)->delete();
            Models\SiteModuleRole::query()->where('role', $id)->delete();
            Models\UserRoleVar::where('roleid', $id)->delete();
            Models\UserRole::query()->where('id', $id)->delete();
            header('Location: index.php?a=86&tab=0');
        }
        if (isset($_POST['a'])) {
            $this->updateOrCreate();
            return true;
        }

        return true;
    }

    public function updateOrCreate()
    {
        $id = $this->getElementId();
        $mode = $this->getIndex();
        if (!$this->managerTheme->getCore()->hasPermission('save_role')) {
            $this->managerTheme->alertAndQuit('error_no_privileges');
        }
        $actorRole = (int) ($_SESSION['mgrRole'] ?? 0);
        $actorPermissions = $this->actorPermissions();
        $this->guardRoleReach($id, $actorRole, $actorPermissions);
        if (!isset($_POST['name']) || $_POST['name'] == '') {
            $this->managerTheme->getCore()->getManagerApi()->saveFormValues();
            $this->managerTheme->getCore()->webAlertAndQuit("Please enter a name for this role!", "index.php?a={$mode}" . ($mode = 35 ? "&id={$id}" : ""));
        }

        $role = Models\UserRole::findOrNew($id);
        $role->name = $_POST['name'];
        $role->description = $_POST['description'];
        $role->save();

        $requiredPermissions = Models\Permissions::query()
            ->where('disabled', 1)
            ->pluck('key')
            ->all();
        $selectedPermissions = self::normalizePermissionsPayload(
            is_array($_POST['permissions'] ?? null) ? $_POST['permissions'] : [],
            $requiredPermissions
        );

        // Store only permissions the actor itself holds, so a role cannot be lifted past its author.
        $grantable = RoleGuard::grantablePermissions(
            $actorRole,
            $actorPermissions,
            array_keys($selectedPermissions),
            $requiredPermissions
        );

        Models\RolePermissions::query()->where('role_id', $role->getKey())->delete();
        foreach ($grantable as $key) {
            Models\RolePermissions::create(['role_id' => $role->getKey(), 'permission' => $key]);
        }

        $this->saveModuleAccess(
            (int) $role->getKey(),
            is_array($_POST['modules'] ?? null) ? array_keys($_POST['modules']) : []
        );

        if ($_POST['tvsDirty'] == 1) {
            // Preserve rankings of already assigned TVs
            $exists = Models\UserRoleVar::where('roleid', $role->id)->get()->toArray();
            $ranks = [];
            $highest = 0;
            foreach ($exists as $row) {
                $ranks[$row['tmplvarid']] = $row['rank'];
                $highest = max($highest, $row['rank']);
            };
            Models\UserRoleVar::where('roleid', $role->id)->delete();

            $newAssignedTvs = isset($_POST['assignedTv']) ? $_POST['assignedTv'] : '';

            if (empty($newAssignedTvs)) {
                return;
            }

            foreach ($newAssignedTvs as $tvid) {
                if (empty($tvid)) {
                    continue;
                }

                Models\UserRoleVar::create([
                    'roleid' => $role->id,
                    'tmplvarid' => $tvid,
                    'rank' => isset($ranks[$tvid]) ? $ranks[$tvid] : $highest++,
                ]);
            }
        }

        $this->managerTheme->getCore()->getManagerApi()->clearSavedFormValues();
        header('Location: index.php?a=35&id=' . $role->getKey() . '&r=9');
    }

    /**
     * Store which modules this role may run.
     *
     * A module with no rows in site_module_roles is unrestricted, so allowing
     * such a module means writing nothing at all. Denying one has to make it
     * restricted first, by listing every other role. A module always keeps at
     * least the admin role, otherwise dropping the last row would silently
     * turn it back into an unrestricted module.
     *
     * @param int $roleId
     * @param array $allowedModuleIds Module ids ticked on the role form.
     * @return void
     */
    protected function saveModuleAccess(int $roleId, array $allowedModuleIds): void
    {
        // the admin role bypasses the ACL, and the form renders read-only for it
        if ($roleId <= 0 || $roleId === ModuleAccess::ADMIN_ROLE) {
            return;
        }

        if (!$this->managerTheme->getCore()->hasPermission('manage_module_permissions')) {
            return;
        }

        $allowed = ModuleAccess::normalizeRoleIds($allowedModuleIds);
        $restricted = Models\SiteModuleRole::query()->get()->groupBy('module');
        $allRoleIds = Models\UserRole::query()->pluck('id')->all();

        foreach (Models\SiteModule::query()->pluck('id') as $moduleId) {
            $moduleId = (int) $moduleId;
            $isRestricted = isset($restricted[$moduleId]);

            if (in_array($moduleId, $allowed, true)) {
                if ($isRestricted) {
                    Models\SiteModuleRole::query()->firstOrCreate([
                        'module' => $moduleId,
                        'role' => $roleId,
                    ]);
                }

                continue;
            }

            if (!$isRestricted) {
                foreach (ModuleAccess::normalizeRoleIds($allRoleIds) as $otherRoleId) {
                    if ($otherRoleId === $roleId) {
                        continue;
                    }

                    Models\SiteModuleRole::query()->firstOrCreate([
                        'module' => $moduleId,
                        'role' => $otherRoleId,
                    ]);
                }

                continue;
            }

            Models\SiteModuleRole::query()
                ->where('module', $moduleId)
                ->where('role', $roleId)
                ->delete();

            $remaining = Models\SiteModuleRole::query()->where('module', $moduleId)->count();
            if ($remaining === 0) {
                Models\SiteModuleRole::query()->firstOrCreate([
                    'module' => $moduleId,
                    'role' => ModuleAccess::ADMIN_ROLE,
                ]);
            }
        }
    }

    /**
     * Modules the role may run, keyed by module id.
     *
     * Unrestricted modules read as allowed: unticking one is what restricts it.
     *
     * @param int $roleId
     * @return array<int, bool>
     */
    protected function parameterModuleAccess(int $roleId): array
    {
        $restricted = Models\SiteModuleRole::query()->get()->groupBy('module');
        $selected = [];

        foreach (Models\SiteModule::query()->pluck('id') as $moduleId) {
            $moduleId = (int) $moduleId;
            $selected[$moduleId] = !isset($restricted[$moduleId])
                || $restricted[$moduleId]->contains('role', $roleId);
        }

        return $selected;
    }

    /**
     * Refuse to create, edit or delete a role the actor may not reach: the administrator role, or
     * a role already holding a permission the actor itself lacks.
     *
     * @param int|null $actorRole
     * @param string[]|null $actorPermissions
     */
    protected function guardRoleReach(int $id, ?int $actorRole = null, ?array $actorPermissions = null): void
    {
        $actorRole = $actorRole ?? (int) ($_SESSION['mgrRole'] ?? 0);
        $actorPermissions = $actorPermissions ?? $this->actorPermissions();
        $targetPermissions = RoleGuard::reachOf($id);

        if (!RoleGuard::mayManageRole($actorRole, $actorPermissions, $id, $targetPermissions)) {
            $this->managerTheme->alertAndQuit('error_no_privileges');
        }
    }

    /**
     * The permission keys the acting manager holds.
     *
     * @return string[]
     */
    protected function actorPermissions(): array
    {
        $core = $this->managerTheme->getCore();

        return RoleGuard::heldBy(static fn ($key) => $core->hasPermission($key));
    }

    public static function normalizePermissionsPayload(array $permissions, array $requiredPermissions = []): array
    {
        foreach ($requiredPermissions as $permission) {
            $permissions[$permission] = 1;
        }

        return $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(array $params = []): array
    {
        $this->managerTheme->getCore()->getManagerApi()->loadFormValues();

        $id = $this->getElementId();
        $permissionsRole = [];
        if ($id != 0) {
            $permissionsRoleTemp = Models\RolePermissions::query()->where('role_id', $id)->get()->pluck('permission')->toArray();
            foreach ($permissionsRoleTemp as $role) {
                $permissionsRole[$role] = 1;
            }
        }
        if(isset($_POST['a'])){
            foreach ($_POST['permissions'] as $role => $key) {
                $permissionsRole[$role] = 1;
            }
        }

        $role = Models\UserRole::with('tvs')->findOrNew($id);

        return [
            'role'             => $role,
            'groups'           => Models\PermissionsGroups::query()->get(),
            'permissionsRole'  => $permissionsRole,
            'modules'          => Models\SiteModule::query()->orderBy('name')->get(),
            'moduleAccess'     => $this->parameterModuleAccess($id),
            'canManageModuleAccess' => $this->managerTheme->getCore()
                ->hasPermission('manage_module_permissions'),
            'categories'       => $this->parameterCategories(),
            'tvSelected'       => $this->parameterTvSelected(),
            'categoriesWithTv' => $this->parameterCategoriesWithTv(
                $role->tvs->reject(
                    function (Models\SiteTmplvar $item) {
                        return $item->category === 0;
                    })->pluck('id')->toArray()
            ),
            'tvOutCategory'    => $this->parameterTvOutCategory(
                $role->tvs->reject(
                    function (Models\SiteTmplvar $item) {
                        return $item->category !== 0;
                    })->pluck('id')->toArray()
            ),
        ];
    }

    protected function parameterCategories(): Collection
    {
        return Models\Category::orderBy('rank', 'ASC')
            ->orderBy('category', 'ASC')
            ->get();
    }

    protected function parameterTvSelected()
    {
        return array_unique(array_map('intval', get_by_key($_POST, 'assignedTv', [], 'is_array')));
    }

    protected function parameterTvOutCategory(array $ignore = []): Collection
    {
        $query = Models\SiteTmplvar::with('userRoles')
            ->where('category', '=', 0)
            ->orderBy('name', 'ASC');

        if (!empty($ignore)) {
            $query = $query->whereNotIn('id', $ignore);
        }

        return $query->get();
    }

    protected function parameterCategoriesWithTv(array $ignore = []): Collection
    {
        $query = Models\Category::with('tvs.userRoles')
            ->whereHas('tvs', function(Eloquent\Builder $builder) use
            (
                $ignore
            ) {
                if (!empty($ignore)) {
                    $builder = $builder->whereNotIn(
                        (new Models\SiteTmplvar)->getTable() . '.id'
                        , $ignore
                    );
                }
                return $builder;
            })
            ->orderBy('rank', 'ASC');

        return $query->get();
    }
}
