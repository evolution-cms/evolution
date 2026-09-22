<?php

namespace EvolutionCMS\Support;

use EvolutionCMS\Models\DocumentGroup;
use EvolutionCMS\Models\SiteContent;

/**
 * Keeps privateweb / privatemgr in step with the user groups behind the groups of a document.
 * @since 3.5.9
 */
final class DocumentPrivacy
{
    public const WEB = 1;
    public const MANAGER = 0;

    /**
     * Both flags of one document in a select and an update; one flag when a context is given.
     */
    public static function refresh(int $documentId, ?int $context = null): void
    {
        $contexts = DocumentGroup::query()
            ->join('membergroup_access', 'document_groups.document_group', '=', 'membergroup_access.documentgroup')
            ->where('document_groups.document', $documentId)
            ->distinct()
            ->pluck('membergroup_access.context')
            ->map(fn ($context) => (int) $context)
            ->all();

        $flags = [];
        if ($context !== self::MANAGER) {
            $flags['privateweb'] = in_array(self::WEB, $contexts, true) ? 1 : 0;
        }
        if ($context !== self::WEB) {
            $flags['privatemgr'] = in_array(self::MANAGER, $contexts, true) ? 1 : 0;
        }

        SiteContent::withTrashed()->where('id', $documentId)->update($flags);
    }

    /**
     * Recomputes one flag for every document, after the access groups screen changed the links.
     */
    public static function refreshAll(int $context): void
    {
        $field = $context === self::WEB ? 'privateweb' : 'privatemgr';

        $ids = DocumentGroup::query()
            ->join('membergroup_access', function ($join) use ($context) {
                $join->on('document_groups.document_group', '=', 'membergroup_access.documentgroup')
                    ->where('membergroup_access.context', '=', $context);
            })
            ->distinct()
            ->pluck('document_groups.document')
            ->map(fn ($id) => (int) $id)
            ->all();

        SiteContent::withTrashed()->where($field, 1)
            ->when($ids, fn ($q) => $q->whereNotIn('id', $ids))
            ->update([$field => 0]);
        if ($ids) {
            SiteContent::withTrashed()->whereIn('id', $ids)->where($field, 0)->update([$field => 1]);
        }
    }
}
