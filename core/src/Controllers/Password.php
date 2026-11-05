<?php namespace EvolutionCMS\Controllers;

use EvolutionCMS\Interfaces\ManagerTheme;

class Password extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.password';

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
        return $this->managerTheme->getCore()->hasPermission('change_password');
    }

    public function getParameters(array $params = []): array
    {
        return [
            'actionButtons' => $this->parameterActionButtons()
        ];
    }

    protected function parameterActionButtons()
    {
        return [
            'save' => 1,
            'cancel' => 1
        ];
    }
}
