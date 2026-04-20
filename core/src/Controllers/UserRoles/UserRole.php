<?php namespace EvolutionCMS\Controllers\UserRoles;

use EvolutionCMS\Controllers\AbstractController;
use EvolutionCMS\Models;
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
            Models\RolePermissions::query()->where('role_id', $id)->delete();
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

        Models\RolePermissions::query()->where('role_id', $role->getKey())->delete();
        foreach (array_keys($selectedPermissions) as $key) {
            Models\RolePermissions::create(['role_id' => $role->getKey(), 'permission' => $key]);
        }

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
