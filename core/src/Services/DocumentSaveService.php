<?php

namespace EvolutionCMS\Services;

use EvolutionCMS\Models\DocumentGroup;
use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Services\DocumentSave\DocumentSaveContext;
use EvolutionCMS\Services\DocumentSave\DocumentSaveDenied;
use EvolutionCMS\Services\DocumentSave\DocumentSaveResult;
use EvolutionCMS\Support\DocumentPrivacy;
use EvolutionCMS\Support\DocumentSave\DocumentGroupSync;
use EvolutionCMS\Support\DocumentSave\PublishState;
use EvolutionCMS\Support\DocumentSave\TemplateVariableInput;
use EvolutionCMS\Support\DocumentSave\TemplateVariableValues;
use Illuminate\Support\Facades\DB;

/**
 * Saves the resource editor form: checks, one transaction of writes, then the events.
 *
 * OnBeforeDocFormSave fires before the transaction and OnDocFormSave after it has been
 * committed, so a plugin never sees uncommitted rows and cannot undo the save by exiting.
 * @since 3.5.9
 */
final class DocumentSaveService
{
    public function __construct(private readonly DocumentSaveContext $ctx)
    {
    }

    public static function forManager(): self
    {
        return new self(DocumentSaveContext::fromManager());
    }

    /**
     * @param array $input the posted editor form
     * @throws DocumentSaveDenied
     */
    public function save(array $input): DocumentSaveResult
    {
        $ctx = $this->ctx;

        $id = is_numeric($input['id'] ?? null) ? (int) $input['id'] : 0;
        $mode = ($id > 0 || in_array((string) ($input['mode'] ?? ''), ['73', '27'], true)) ? 'edit' : 'new';

        $type = (string) ($input['type'] ?? 'document');
        $pagetitle = (string) ($input['pagetitle'] ?? '');
        if (trim($pagetitle) === '') {
            $pagetitle = $ctx->lang($type === 'reference' ? 'untitled_weblink' : 'untitled_resource');
        }
        $parent = (int) get_by_key($input, 'parent', 0, 'is_scalar');
        $template = (int) ($input['template'] ?? 0);
        $makePublic = ($input['chkalldocs'] ?? '') === 'on';
        $groupPairs = $makePublic ? [] : get_by_key($input, 'docgroups', [], 'is_array');
        $usePermissions = (int) $ctx->config('use_udperms') === 1;

        $existing = null;
        if ($mode === 'edit') {
            $existing = SiteContent::withTrashed()->find($id);
            if ($existing === null) {
                throw new DocumentSaveDenied($ctx->lang('error_no_results'), false);
            }
            // the editor page checks this before showing the form; a posted save has to as well
            if ($usePermissions && !$ctx->canEdit($id)) {
                throw new DocumentSaveDenied($ctx->lang('access_permission_denied'), false);
            }
        }

        $alias = $this->resolveAlias((string) ($input['alias'] ?? ''), $pagetitle, $id, $parent);

        // a non administrator may not take their own groups off the document
        if (!$ctx->isAdministrator()
            && DocumentGroupSync::locksOut(DocumentGroupSync::postedGroups($groupPairs), $ctx->userGroups())) {
            throw new DocumentSaveDenied($ctx->lang('resource_permissions_error'));
        }

        if ($usePermissions && ($existing === null || (int) $existing->parent !== $parent) && !$ctx->canCreateIn($parent)) {
            throw new DocumentSaveDenied($ctx->lang('access_permission_parent_denied'));
        }

        $tvs = TemplateVariableValues::forTemplate($template, $id, !$ctx->isAdministrator(), $ctx->managerDocgroups);
        $tvValues = TemplateVariableInput::values($tvs, $input);

        $now = $ctx->now;
        $pubDate = empty($input['pub_date']) ? 0 : $ctx->toTimestamp((string) $input['pub_date']);
        $unpubDate = empty($input['unpub_date']) ? 0 : $ctx->toTimestamp((string) $input['unpub_date']);
        $published = PublishState::fromDates((int) ($input['published'] ?? 0), $pubDate, $unpubDate, $now);
        $mayPublish = $ctx->can('publish_document');

        $fields = [
            'introtext' => $input['introtext'] ?? '',
            'content' => $input['ta'] ?? '',
            'pagetitle' => $pagetitle,
            'longtitle' => $input['longtitle'] ?? '',
            'type' => $type,
            'description' => $input['description'] ?? '',
            'alias' => $alias,
            'link_attributes' => $input['link_attributes'] ?? '',
            'isfolder' => (int) ($input['isfolder'] ?? 0),
            'richtext' => (int) ($input['richtext'] ?? 0),
            'parent' => $parent,
            'template' => $template,
            'menuindex' => (int) ($input['menuindex'] ?? 0),
            'searchable' => (int) ($input['searchable'] ?? 0),
            'cacheable' => (int) ($input['cacheable'] ?? 0),
            'editedby' => $ctx->userId,
            'editedon' => $now,
            'contentType' => $input['contentType'] ?? 'text/html',
            'content_dispo' => (int) ($input['content_dispo'] ?? 0),
            'hide_from_tree' => (int) ($input['hide_from_tree'] ?? 0),
            'menutitle' => $input['menutitle'] ?? '',
            'hidemenu' => (int) ($input['hidemenu'] ?? 0),
            'alias_visible' => (int) ($input['alias_visible'] ?? 0),
        ];

        // find() hides trashed rows, so a missing parent is a trashed one
        $parentRow = $parent > 0 ? SiteContent::withTrashed()->select('id', 'isfolder', 'deleted')->find($parent) : null;
        if ($parent > 0 && ($parentRow === null || (int) $parentRow->deleted === 1)) {
            $fields['deleted'] = 1;
        }

        if ($mode === 'new') {
            $fields['createdby'] = $ctx->userId;
            $fields += PublishState::forNew($published, $pubDate, $unpubDate, $now, $ctx->userId, $mayPublish);

            // only the event sees this id: the row gets auto increment, as it always did (id is not fillable)
            $ctx->fire('OnBeforeDocFormSave', ['mode' => 'new', 'id' => $this->announcedId()]);

            $editedon = 0;
            $id = $this->transaction(function () use (&$editedon, $fields, $tvs, $tvValues, $parent, $parentRow, $groupPairs, $usePermissions) {
                $document = SiteContent::withTrashed()->create($fields);
                $id = (int) $document->getKey();
                $editedon = (int) $document->editedon;
                TemplateVariableValues::sync($id, $tvs, $tvValues);
                if ($usePermissions) {
                    $this->attachGroupsToNew($id, $parent, $groupPairs);
                }
                $this->markAsFolder($parentRow);

                return $id;
            });

            $ctx->fire('OnDocFormSave', ['mode' => 'new', 'id' => $id]);
        } else {
            $this->guardEdit($existing, $parent, $published, $pubDate, $unpubDate);

            $oldParent = (int) $existing->parent;
            if (SiteContent::withTrashed()->where('parent', $id)->exists()) {
                $fields['isfolder'] = 1;
            }
            $fields += PublishState::forEdit($published, $pubDate, $unpubDate, $now, $ctx->userId, $mayPublish, $existing->getAttributes());

            $ctx->fire('OnBeforeDocFormSave', ['mode' => 'upd', 'id' => $id]);

            $editedon = 0;
            $this->transaction(function () use (&$editedon, $existing, $id, $fields, $tvs, $tvValues, $parent, $oldParent, $parentRow, $groupPairs, $makePublic, $usePermissions) {
                foreach ($fields as $field => $value) {
                    $existing->{$field} = $value;
                }
                $existing->save();
                $editedon = (int) $existing->editedon;
                TemplateVariableValues::sync($id, $tvs, $tvValues);
                if ($usePermissions && ($this->ctx->can('manage_groups') || $this->ctx->can('manage_document_permissions'))) {
                    $kept = DocumentGroupSync::forExistingDocument($id, $groupPairs, $this->ctx->userGroups(), $this->ctx->can('manage_groups'), $makePublic);
                    if (!$kept) {
                        throw new DocumentSaveDenied($this->ctx->lang('resource_permissions_error'));
                    }
                }
                $this->markAsFolder($parentRow);
                if ($oldParent !== $parent && $oldParent > 0
                    && !SiteContent::withTrashed()->where('parent', $oldParent)->exists()) {
                    SiteContent::withTrashed()->where('id', $oldParent)->update(['isfolder' => 0]);
                }
            });

            $ctx->fire('OnDocFormSave', ['mode' => 'upd', 'id' => $id]);
        }

        // after the event, a plugin may have changed the groups
        DocumentPrivacy::refresh($id);

        return new DocumentSaveResult($id, $mode, $type, $parent, $pagetitle, $alias, $editedon);
    }

    /**
     * Rules that only apply to an existing document.
     */
    private function guardEdit(SiteContent $existing, int $parent, int $published, int $pubDate, int $unpubDate): void
    {
        $ctx = $this->ctx;
        $id = (int) $existing->getKey();

        if ($id === (int) $ctx->config('site_start')) {
            if ($published === 0) {
                throw new DocumentSaveDenied('Document is linked to site_start variable and cannot be unpublished!', false);
            }
            if ($pubDate > $ctx->now || $unpubDate !== 0) {
                throw new DocumentSaveDenied('Document is linked to site_start variable and cannot have publish or unpublish dates set!', false);
            }
        }
        if ($parent === $id) {
            throw new DocumentSaveDenied("Document can not be it's own parent!", false);
        }
        if (in_array($id, array_map('intval', $ctx->parentIds($parent)), true)) {
            throw new DocumentSaveDenied("Document descendant can not be it's parent!", false);
        }
    }

    /**
     * The alias to store: generated, stripped and checked for duplicates as the settings ask.
     */
    private function resolveAlias(string $alias, string $pagetitle, int $id, int $parent): string
    {
        $ctx = $this->ctx;

        if (!$ctx->config('friendly_urls')) {
            return $alias === '' ? '' : $ctx->stripAlias($alias);
        }

        $allowDuplicates = (bool) $ctx->config('allow_duplicate_alias');

        if ($alias === '') {
            if (!$ctx->config('automatic_alias')) {
                return '';
            }
            $alias = strtolower($ctx->stripAlias(trim($pagetitle)));

            // a duplicate gets a counter; without allow_duplicate_alias the whole site counts
            $base = $alias;
            $count = 1;
            while ($this->aliasQuery($alias, $id, $allowDuplicates ? $parent : null)->exists()) {
                $alias = $base . $count++;
            }

            return $alias;
        }

        $alias = $ctx->stripAlias($alias);
        // with duplicates allowed, or alias paths on, only the same level has to be unique
        $sameLevelOnly = $allowDuplicates || $ctx->config('use_alias_path');
        $duplicate = $this->aliasQuery($alias, $id, $sameLevelOnly ? $parent : null)->first();
        if ($duplicate !== null) {
            throw new DocumentSaveDenied(sprintf($ctx->lang('duplicate_alias_found'), $duplicate->id, $alias));
        }

        return $alias;
    }

    private function aliasQuery(string $alias, int $id, ?int $parent)
    {
        return SiteContent::withTrashed()->select('id')
            ->where('id', '<>', $id)
            ->where('alias', $alias)
            ->when($parent !== null, fn ($q) => $q->where('parent', $parent));
    }

    /**
     * The id docid_incrmnt_method promises to OnBeforeDocFormSave: first gap, max + 1, or '' for auto increment.
     */
    private function announcedId(): int|string
    {
        switch ((string) $this->ctx->config('docid_incrmnt_method')) {
            case '1':
                $table = SiteContent::query()->getQuery()->getGrammar()->wrapTable('site_content');
                $id = SiteContent::withTrashed()
                    ->leftJoin('site_content as t1', function ($join) use ($table) {
                        $join->on(DB::raw($table . '.id + 1'), '=', 't1.id');
                    })
                    ->whereNull('t1.id')->min('site_content.id');

                return (int) $id + 1;
            case '2':
                return (int) SiteContent::withTrashed()->max('id') + 1;
            default:
                return '';
        }
    }

    private function attachGroupsToNew(int $id, int $parent, array $groupPairs): void
    {
        $ctx = $this->ctx;
        $groupsOfParent = $parent > 0
            ? array_map('intval', DocumentGroup::query()->where('document', $parent)->pluck('document_group')->all())
            : [];
        $manageGroups = $ctx->can('manage_groups');
        $manageDocPerms = $ctx->can('manage_document_permissions');
        $userGroups = ($manageGroups || $manageDocPerms) ? $ctx->userGroups() : [];

        DocumentGroupSync::forNewDocument($id, $groupPairs, $groupsOfParent, $userGroups, $manageGroups, $manageDocPerms);
    }

    private function markAsFolder(?SiteContent $parentRow): void
    {
        if ($parentRow !== null && (int) $parentRow->isfolder !== 1) {
            SiteContent::withTrashed()->where('id', $parentRow->getKey())->update(['isfolder' => 1]);
        }
    }

    private function transaction(callable $callback): mixed
    {
        return SiteContent::resolveConnection()->transaction($callback);
    }
}
