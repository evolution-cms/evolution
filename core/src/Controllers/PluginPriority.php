<?php namespace EvolutionCMS\Controllers;

use EvolutionCMS\Models;
use EvolutionCMS\Interfaces\ManagerTheme;
use Illuminate\Database\Eloquent;


class PluginPriority extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.plugin_priority';

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
        return $this->managerTheme->getCore()->hasPermission('save_plugin');
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(array $params = []): array
    {
        return parent::getParameters([
            'events' => $this->getEventsParameter()
        ]);
    }

    protected function getEventsParameter() : Eloquent\Collection
    {
        return Models\SystemEventname::with(
            [
                'plugins' => function (Eloquent\Relations\BelongsToMany $query) {
                    $query->orderBy('priority');
                }
            ]
        )->whereHas('plugins')->orderBy('name')->get();
    }

    public function process() : bool
    {
        $updateMsg = false;

        if (isset($_POST['priority']) && is_array($_POST['priority'])) {
            $updateMsg = true;
            $db        = $this->managerTheme->getCore()->getDatabase();
            $tableName = $db->getFullTableName('site_plugin_events');

            foreach ($_POST['priority'] as $eventId => $pluginsOrder) {
                if (!is_numeric($eventId) || !is_array($pluginsOrder)) {
                    continue;
                }

                if (\count($pluginsOrder) > 0) {
                    foreach ($pluginsOrder as $priority => $pluginId) {
                        Models\SitePluginEvent::query()
                            ->where('pluginid', intval($pluginId))
                            ->where('evtid', intval($eventId))
                            ->update(['priority' => intval($priority)]);
                    }
                }
            }

            // empty cache
            $this->managerTheme->getCore()->clearCache('full');
        }

        $this->parameters['updateMsg'] = $updateMsg;

        return true;
    }
}
