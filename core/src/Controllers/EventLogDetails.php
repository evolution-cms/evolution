<?php namespace EvolutionCMS\Controllers;

use EvolutionCMS\Interfaces\ManagerTheme;
use EvolutionCMS\Models;

class EventLogDetails extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.eventlog_details';

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
        return $this->managerTheme->getCore()->hasPermission('view_eventlog');
    }

    public function process() : bool
    {
        $this->parameters['log'] = Models\EventLog::findOrNew($this->getElementId());

        return true;
    }
}
