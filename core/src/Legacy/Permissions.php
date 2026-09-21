<?php namespace EvolutionCMS\Legacy;

use EvolutionCMS\Models\DocumentGroup;
use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Support\ResourceParentGuard;

/**
 * @class: udperms
 */
class Permissions
{
    /**
     * @var int
     */
    public $user;
    /**
     * @var int
     */
    public $document;
    /**
     * @var int
     */
    public $role;
    /**
     * @var bool
     */
    public $duplicateDoc = false;

    /**
     * @return bool
     */
    public function checkPermissions()
    {
        $modx = evo();

        $document = (int) $this->document;

        if (ResourceParentGuard::grantsWithoutLookup($this->role, $modx->getConfig('use_udperms'))) {
            return true; // administrator, or permissions aren't in use
        }

        if ($document === 0) {
            return static::rootIsAllowed(); // placing a resource in the site root
        }

        if ($this->duplicateDoc) {
            $source = SiteContent::withTrashed()->find($document);
            if (ResourceParentGuard::duplicateBlockedAtRoot(static::allowRootSetting(), $source->parent ?? 0)) {
                return false; // the duplicate would end up in the root
            }
        }

        /* Note:
            A document is flagged as private whenever the document group that it
            belongs to is assigned or links to a user group. In other words if
            the document is assigned to a document group that is not yet linked
            to a user group then that document will be made public. Documents that
            are private to the manager users will not be private to web users if the
            document group is not assigned to a web user group and visa versa.
         */
        return static::documentIsAccessible($document);
    }

    /**
     * The manager no longer exports settings as globals, so the setting is the authoritative
     * source here; the legacy global is still honoured when something did define it.
     *
     * @return mixed
     */
    public static function allowRootSetting()
    {
        global $udperms_allowroot;

        return isset($udperms_allowroot) ? $udperms_allowroot : evo()->getConfig('udperms_allowroot');
    }

    /**
     * @return bool
     */
    public static function rootIsAllowed()
    {
        return ResourceParentGuard::allowsRoot(static::allowRootSetting());
    }

    /**
     * Document groups of the manager user of the current session.
     *
     * @return array
     */
    public static function getManagerDocumentGroups()
    {
        return (isset($_SESSION['mgrDocgroups']) && is_array($_SESSION['mgrDocgroups']))
            ? $_SESSION['mgrDocgroups']
            : [];
    }

    /**
     * Ids of the private documents the manager user of the current session belongs to.
     * Trashed documents are kept, so restoring one from the recycle bin stays possible.
     *
     * @return array
     */
    public static function getAccessibleDocumentIds()
    {
        static $documents = null;

        if ($documents === null) {
            $docgrp = static::getManagerDocumentGroups();
            $documents = empty($docgrp)
                ? []
                : DocumentGroup::query()->whereIn('document_group', $docgrp)
                    ->pluck('document')
                    ->map(static function ($document) {
                        return (int) $document;
                    })
                    ->all();
        }

        return $documents;
    }

    /**
     * Whether the manager user of the current session may work with the given document.
     *
     * @param int $document
     * @return bool
     */
    public static function documentIsAccessible($document)
    {
        $docgrp = static::getManagerDocumentGroups();

        // withTrashed(), so publishing, restoring and moving a trashed document keep working
        $query = SiteContent::withTrashed()->where('site_content.id', (int) $document);

        if (empty($docgrp)) {
            $query->where('site_content.privatemgr', 0);
        } else {
            $query->leftJoin('document_groups', 'site_content.id', '=', 'document_groups.document')
                ->where(function ($q) use ($docgrp) {
                    $q->where('site_content.privatemgr', 0)
                        ->orWhereIn('document_groups.document_group', $docgrp);
                });
        }

        return $query->exists();
    }

    /**
     * Whether the manager user of the current session may place a resource inside $parent.
     *
     * @param int $parent
     * @return bool
     */
    public static function canCreateIn($parent)
    {
        $udperms = new static();
        $udperms->user = evo()->getLoginUserID('mgr');
        $udperms->document = (int) $parent;
        $udperms->role = $_SESSION['mgrRole'] ?? 0;

        return $udperms->checkPermissions();
    }

    /**
     * First location the manager user of the current session may create resources in.
     *
     * @return int
     */
    public static function getFirstAllowedParent()
    {
        if (static::canCreateIn(0)) {
            return 0;
        }

        return ResourceParentGuard::pickDefaultParent(false, static::findFirstAccessibleDocument());
    }

    /**
     * Topmost document of the tree the manager user of the current session can reach.
     *
     * @return int|null
     */
    protected static function findFirstAccessibleDocument()
    {
        $docgrp = static::getManagerDocumentGroups();

        $query = SiteContent::query()->select('site_content.id')
            ->where('site_content.deleted', 0);

        if (empty($docgrp)) {
            $query->where('site_content.privatemgr', 0);
        } else {
            $query->leftJoin('document_groups', 'site_content.id', '=', 'document_groups.document')
                ->where(function ($q) use ($docgrp) {
                    $q->where('site_content.privatemgr', 0)
                        ->orWhereIn('document_groups.document_group', $docgrp);
                });
        }

        $first = $query->orderBy('site_content.parent')
            ->orderBy('site_content.menuindex')
            ->orderBy('site_content.id')
            ->first();

        return $first === null ? null : (int) $first->id;
    }
}
