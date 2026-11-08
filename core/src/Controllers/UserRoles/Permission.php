<?php namespace EvolutionCMS\Controllers\UserRoles;

use EvolutionCMS\Controllers\AbstractController;
use EvolutionCMS\Models;
use EvolutionCMS\Interfaces\ManagerTheme;

class Permission extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.user_roles.permission';

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
        return $this->managerTheme->getCore()->hasPermission('edit_role');
    }

    public function process() : bool
    {
        $core = $this->managerTheme->getCore();
        if(isset($_GET['action']) && $_GET['action'] == 'delete' ){
            if (!$core->hasPermission('delete_role')) {
                $core->webAlertAndQuit(\Lang::get('global.error_no_privileges'));
            }
            Models\Permissions::query()->where('id', $this->getElementId())->delete();
            header('Location: index.php?a=86&tab=2');
        }
        if (isset($_POST['a'])) {
            if (!$core->hasPermission('save_role')) {
                $core->webAlertAndQuit(\Lang::get('global.error_no_privileges'));
            }
            $this->updateOrCreate();
            return true;
        }

        return true;
    }

    public function updateOrCreate()
    {
        $group_id = $_POST['group_id'];
        if(isset($_POST['newcategory']) && $_POST['newcategory'] != ''){
            $group_id = PermissionsGroups::findCategoryOrNew($_POST['newcategory']);
        }
        if(!isset($_POST['disabled'])){
            $_POST['disabled'] = 0;
        }
        $id = $this->getElementId();
        $group = Models\Permissions::findOrNew($id);
        $group->name = $_POST['name'];
        $group->lang_key = $_POST['lang_key'];
        $group->key = $_POST['key'];
        $group->group_id = $group_id;
        $group->disabled = $_POST['disabled'];
        $group->save();
        header('Location: index.php?a=135&id=' . $group->getKey() . '&r=9');

    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(array $params = []): array
    {
        $id = $this->getElementId();
        return [
            'permission' => Models\Permissions::findOrNew($id),
            'categories' => Models\PermissionsGroups::query()->select('id', 'name as category')
        ];
    }
}
