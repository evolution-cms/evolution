<?php namespace EvolutionCMS\Controllers\Users;

use EvolutionCMS\Controllers\AbstractController;
use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Exceptions\ServiceValidationException;
use EvolutionCMS\Models;
use EvolutionCMS\Interfaces\ManagerTheme;

class ChangePassword extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = '';

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
        return $this->managerTheme->getCore()->hasPermission('save_password');
    }

    public function process() : bool
    {
        try {
            \UserManager::changeManagerPassword($_POST);
        }catch (ServiceActionException $exception){
            EvolutionCMS()->webAlertAndQuit($exception->getMessage(), 'index.php?a=28');
        }catch (ServiceValidationException $exception){
            foreach ($exception->getValidationErrors() as $errors){
                if(is_array($errors)){
                    foreach ($errors as $error){
                        EvolutionCMS()->webAlertAndQuit($error, 'index.php?a=28');
                    }
                }
            }
        }

        // not the user list (a=99): it needs edit_user, which this action does not require
        header("Location: index.php?a=2");
        exit();
    }




}
