<?php namespace EvolutionCMS\Controllers;

use EvolutionCMS\Interfaces\ManagerThemeInterface;
use EvolutionCMS\Interfaces\ManagerTheme;
use Illuminate\Support\Collection;

class Phpinfo extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.phpinfo';

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
        return $this->managerTheme->getCore()->hasPermission('settings');
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(array $params = []): array
    {
        return [];
    }
}
