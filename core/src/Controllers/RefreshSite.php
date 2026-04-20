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
     * Updates the site: (de)publishes documents, clears the cache, and invalidates the env cache.
     *
     * After clearing the cache, deletes `core/storage/cache/env.php` so that the `.env` cache is rebuilt on the next request.
     */
    public function process(): bool
    {
        // (un)publishing of documents, version 2!
        // first, publish document waiting to be published
        $time = $this->managerTheme->getCore()->timestamp();

        $this->parameters = [
            'num_rows_pub' => $this->publishDocuments($time),
            'num_rows_unpub' => $this->unPublishDocuments($time),
        ];

        ob_start();
        $this->managerTheme->getCore()->clearCache('full', true);
        $this->parameters['cache_log'] = ob_get_contents();
        ob_end_clean();

        $envCache = EVO_BASE_PATH . 'core/storage/cache/env.php';
        if (is_file($envCache)) {
            @unlink($envCache);
        }

        // invoke OnSiteRefresh event
        $this->managerTheme->getCore()->invokeEvent("OnSiteRefresh");

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
