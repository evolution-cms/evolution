<?php namespace EvolutionCMS\Controllers;

use EvolutionCMS\Interfaces\ManagerTheme;
use EvolutionCMS\Interfaces\ManagerThemeInterface;
use EvolutionCMS\Models;

class RefreshSite extends AbstractController implements ManagerTheme\PageControllerInterface
{
    protected $view = 'page.refresh_site';

    /**
     * @var \EvolutionCMS\Interfaces\DatabaseInterface
     */
    protected $database;

    public function __construct(ManagerThemeInterface $managerTheme, array $data = [])
    {
        parent::__construct($managerTheme, $data);
        $this->database = $this->managerTheme->getCore()->getDatabase();
    }

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
        return true;
    }

    /**
     * Updates the site: (de)publishes documents and clears the full CMS cache.
     * The full cache clear also invalidates the shared environment/configuration cache.
     */
    public function process(): bool
    {
        $core = $this->managerTheme->getCore();

        // Running the due (un)publish changes document state, so it needs
        // publish_document; empty_cache alone only clears the cache. Documents
        // past their schedule are (un)published on the front end regardless
        // (Core::runPubStatusSync()), so the cache clear below still runs.
        $time = $core->timestamp();
        if ($core->hasPermission('publish_document') === 1) {
            $this->parameters = [
                'num_rows_pub' => $this->publishDocuments($time),
                'num_rows_unpub' => $this->unPublishDocuments($time),
            ];
        } else {
            $this->parameters = [
                'num_rows_pub' => 0,
                'num_rows_unpub' => 0,
            ];
        }

        ob_start();
        $core->clearCache('full', true);
        $this->parameters['cache_log'] = ob_get_contents();
        ob_end_clean();

        // invoke OnSiteRefresh event
        $core->invokeEvent("OnSiteRefresh");

        return true;
    }

    protected function publishDocuments(int $time) : int
    {
        $query = Models\SiteContent::publishDocuments($time);

        $count = $query->count();

        $query->update(['published' => 1]);

        return $count;
    }

    protected function unPublishDocuments(int $time) : int
    {
        $query = Models\SiteContent::unPublishDocuments($time);

        $count = $query->count();

        $query->update(['published' => 0]);

        return $count;
    }
}
