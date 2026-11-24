<?php namespace EvolutionCMS\DocumentManager\Services;

use Closure;
use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\DocumentManager\Services\Documents\DocumentClearCart;
use EvolutionCMS\DocumentManager\Services\Documents\DocumentCreate;
use EvolutionCMS\DocumentManager\Services\Documents\DocumentDelete;
use EvolutionCMS\DocumentManager\Services\Documents\DocumentDuplicate;
use EvolutionCMS\DocumentManager\Services\Documents\DocumentEdit;
use EvolutionCMS\DocumentManager\Services\Documents\DocumentPublish;
use EvolutionCMS\DocumentManager\Services\Documents\DocumentSetGroups;
use EvolutionCMS\DocumentManager\Services\Documents\DocumentUndelete;
use EvolutionCMS\DocumentManager\Services\Documents\DocumentUnpublish;

/** Dispatch manager document operations and partial updates to their services. */
class DocumentManager
{

    public function get($id)
    {
        return SiteContent::withTrashed()->find($id);
    }

    public function create(array $userData, bool $events = true, bool $cache = true)
    {
        $document = new DocumentCreate($userData, $events, $cache);
        return $document->process();
    }

    public function edit(array $userData, bool $events = true, bool $cache = true)
    {
        $document = new DocumentEdit($userData, $events, $cache);
        return $document->process();
    }

    /**
     * Save a partial document update and separately supplied template variable values.
     *
     * The caller authorizes the locked resource and destination before any write.
     * @param array<string,mixed> $fields
     * @param array<string,string> $tvs
     * @param Closure(SiteContent): void $authorize
     * @return array{document: SiteContent, updated_fields: list<string>, updated_tvs: list<string>}
     * @since 1.1.0
     */
    public function editPatch(int $id, array $fields, array $tvs, Closure $authorize): array
    {
        return (new DocumentEdit(['id' => $id] + $fields))->processPatch($tvs, $authorize);
    }

    public function duplicate(array $userData, bool $events = true, bool $cache = true)
    {
        $document = new DocumentDuplicate($userData, $events, $cache);
        return $document->process();
    }

    public function delete(array $userData, bool $events = true, bool $cache = true)
    {
        $username = new DocumentDelete($userData, $events, $cache);
        return $username->process();
    }

    public function undelete(array $userData, bool $events = true, bool $cache = true)
    {
        $username = new DocumentUndelete($userData, $events, $cache);
        return $username->process();
    }

    public function setGroups(array $userData, bool $events = true, bool $cache = true)
    {
        $user = new DocumentSetGroups($userData, $events, $cache);
        return $user->process();
    }


    public function publish(array $userData, bool $events = true, bool $cache = true)
    {
        $user = new DocumentPublish($userData, $events, $cache);
        return $user->process();
    }

    public function unpublish(array $userData, bool $events = true, bool $cache = true)
    {
        $user = new DocumentUnpublish($userData, $events, $cache);
        return $user->process();
    }

    public function clearCart(array $userData = [], bool $events = true, bool $cache = true)
    {
        $user = new DocumentClearCart($userData, $events, $cache);
        return $user->process();
    }

}
