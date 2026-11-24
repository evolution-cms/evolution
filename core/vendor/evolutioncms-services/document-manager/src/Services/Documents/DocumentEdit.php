<?php namespace EvolutionCMS\DocumentManager\Services\Documents;

use Closure;
use EvolutionCMS\Exceptions\ServiceActionException;
use EvolutionCMS\Exceptions\ServiceValidationException;
use EvolutionCMS\Interfaces\ServiceInterface;
use EvolutionCMS\Models\SiteContent;
use EvolutionCMS\Models\SiteTmplvarTemplate;
use EvolutionCMS\Models\SiteTmplvarContentvalue;
use \EvolutionCMS\Models\User;
use Illuminate\Support\Facades\Lang;

/** Apply manager document edits and atomic partial updates with TV values. */
class DocumentEdit extends DocumentCreate
{
    /**
     * @var \string[][]
     */
    public $validate;

    /**
     * @var array
     */
    public $messages;

    /**
     * @var array
     */
    public $documentData;

    /**
     * @var bool
     */
    public $events;

    /**
     * @var bool
     */
    public $cache;

    /**
     * @var array $validateErrors
     */
    public $validateErrors;

    /**
     * @var int
     */
    public $currentDate;

    /**
     * @var array
     */
    public $tvs = [];

    /**
     * UserRegistration constructor.
     * @param array $documentData
     * @param bool $events
     * @param bool $cache
     */
    public function __construct(array $documentData, bool $events = true, bool $cache = true)
    {
        $this->validate = $this->getValidationRules();
        $this->messages = $this->getValidationMessages();
        $this->documentData = $documentData;
        $this->events = $events;
        $this->cache = $cache;
        $this->currentDate = EvolutionCMS()->timestamp((int)get_by_key($_SERVER, 'REQUEST_TIME', 0));

    }

    /**
     * @return \string[][]
     */
    public function getValidationRules(): array
    {
        return [
            'id' => ['required'],
        ];
    }

    /**
     * @return array
     */
    public function getValidationMessages(): array
    {
        return [
            'id.required' => Lang::get("global.required_field", ['field' => 'id']),
        ];

    }

    /**
     * @return \Illuminate\Database\Eloquent\Model
     * @throws ServiceActionException
     * @throws ServiceValidationException
     */
    public function process(): \Illuminate\Database\Eloquent\Model
    {
        return $this->saveDocument()['document'];
    }

    /**
     * Edit the locked resource and return the requested keys that actually changed.
     *
     * @param array<string,string> $tvs
     * @param Closure(SiteContent): void $authorize
     * @return array{document: SiteContent, updated_fields: list<string>, updated_tvs: list<string>}
     * @since 1.1.0
     */
    public function processPatch(array $tvs, Closure $authorize): array
    {
        return $this->saveDocument($tvs, $authorize);
    }

    /**
     * Persist the resource, TV values and parent status in one database transaction.
     *
     * Manager form edits use the existing flat payload. Patch calls use a separate
     * TV map and reject unauthorized publication changes before the first write.
     * @param array<string,string>|null $tvValues
     * @param (Closure(SiteContent): void)|null $authorize
     * @return array{document: SiteContent, updated_fields: list<string>, updated_tvs: list<string>}
     * @since 1.1.0
     */
    private function saveDocument(?array $tvValues = null, ?Closure $authorize = null): array
    {
        if (!$this->checkRules()) {
            throw new ServiceActionException(Lang::get('global.error_no_privileges'));
        }
        if (!$this->validate()) {
            $exception = new ServiceValidationException();
            $exception->setValidationErrors($this->validateErrors);
            throw $exception;
        }

        $connection = (new SiteContent())->getConnection();
        $result = $connection->transaction(function () use ($tvValues, $authorize) {
            $id = (int)$this->documentData['id'];
            $document = SiteContent::query()->withTrashed()->lockForUpdate()->findOrFail($id);
            $requestedFields = array_keys($this->documentData);
            if ($authorize !== null) {
                $authorize($document);
                $this->preparePatchFields($document);
            }

            if (isset($this->documentData['pagetitle'])) {
                $this->prepareAliasDocument();
            }
            if ($authorize === null) {
                $this->prepareEditDocument();
            } else {
                $this->prepareEditDocumentFrom($document);
            }
            $patchTvIds = $tvValues === null ? [] : $this->resolvePatchTvs($tvValues);

            if ($this->events) {
                EvolutionCMS()->invokeEvent('OnBeforeDocFormSave', ['mode' => 'upd', 'user' => $id]);

                if ($authorize === null) {
                    // Compare submitted values against the row as left by manager plugins.
                    $document = SiteContent::query()->withTrashed()->lockForUpdate()->findOrFail($id);
                }
            }

            if (!$document->update($this->documentData)) {
                throw new ServiceActionException('The document update was cancelled.');
            }
            $updatedFields = array_values(array_intersect($requestedFields, array_keys($document->getChanges())));
            $updatedTvs = [];
            if ($tvValues === null) {
                $this->prepareTV();
                $this->saveTVs();
            } else {
                foreach ($patchTvIds as $name => $tvId) {
                    $value = SiteTmplvarContentvalue::query()->firstOrNew(['contentid' => $id, 'tmplvarid' => $tvId]);
                    $value->value = $tvValues[$name];
                    if ($value->exists && !$value->isDirty('value')) {
                        continue;
                    }
                    if (!$value->save()) {
                        throw new ServiceActionException("The update of TV [{$name}] was cancelled.");
                    }
                    if ($value->wasRecentlyCreated || $value->wasChanged('value')) {
                        $updatedTvs[] = $name;
                    }
                }
            }
            $this->updateParent();

            return ['document' => $document, 'updated_fields' => $updatedFields, 'updated_tvs' => $updatedTvs];
        });

        $afterCommit = function () use ($result) {
            if ($this->events) {
                EvolutionCMS()->invokeEvent('OnDocFormSave', ['mode' => 'upd', 'id' => $result['document']->getKey()]);
            }
            $_SESSION['itemname'] = $result['document']->pagetitle;
            if ($this->cache) {
                EvolutionCMS()->clearCache('full');
            }
        };
        if ($connection->transactionLevel() > 0) {
            $connection->afterCommit($afterCommit);
        } else {
            $afterCommit();
        }

        return $result;
    }

    /**
     * Check requested publication changes and fill fields the manager service reads on each edit.
     *
     * @since 1.1.0
     */
    private function preparePatchFields(SiteContent $document): void
    {
        foreach (['published', 'pub_date', 'unpub_date'] as $field) {
            if (array_key_exists($field, $this->documentData)
                && (int)$this->documentData[$field] !== (int)$document->getAttribute($field)
                && !EvolutionCMS()->hasPermission('publish_document', 'mgr')
            ) {
                throw new ServiceActionException('Changing publication state or dates requires the publish_document permission.');
            }
        }

        foreach (['pagetitle', 'alias', 'template', 'parent', 'published', 'pub_date', 'unpub_date', 'isfolder', 'hidemenu', 'menuindex'] as $field) {
            $this->documentData += [$field => $document->getAttribute($field)];
        }
    }

    /**
     * Resolve every supplied TV against the chosen template before any writes.
     *
     * @param array<string,string> $values
     * @return array<string,int>
     * @since 1.1.0
     */
    private function resolvePatchTvs(array $values): array
    {
        if ($values === []) {
            return [];
        }

        $ids = SiteTmplvarTemplate::query()
            ->where('templateid', $this->documentData['template'])
            ->join('site_tmplvars', 'site_tmplvars.id', '=', 'site_tmplvar_templates.tmplvarid')
            ->whereIn('site_tmplvars.name', array_keys($values))
            ->pluck('site_tmplvars.id', 'site_tmplvars.name')
            ->all();
        if (array_diff_key($values, $ids) !== []) {
            throw new ServiceActionException('TVs not assigned to the target template: ' . implode(', ', array_keys(array_diff_key($values, $ids))));
        }

        $ordered = [];
        foreach (array_keys($values) as $name) {
            $ordered[$name] = (int)$ids[$name];
        }

        return $ordered;
    }

    /**
     * @return bool
     */
    public function checkRules(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function validate(): bool
    {
        $validator = \Validator::make($this->documentData, $this->validate, $this->messages);
        $this->validateErrors = $validator->errors()->toArray();
        return !$validator->fails();
    }


    /**
     * Prepare manager fields and retain the stored publication values when the role lacks permission.
     *
     * The locked model is supplied by the transactional editor.
     */
    public function prepareEditDocument()
    {
        $document = SiteContent::query()->withTrashed()->findOrFail($this->documentData['id']);
        $this->prepareEditDocumentFrom($document);
    }

    /**
     * Apply the editor's normalizations using a document already read inside the transaction.
     *
     * @since 1.1.0
     */
    private function prepareEditDocumentFrom(SiteContent $document): void
    {
        $existingDocument = $document->toArray();
        $this->documentData['editedby'] = EvolutionCMS()->getLoginUserID();
        $this->documentData['editedon'] = $this->currentDate;
        $this->documentData['oldparent'] = $existingDocument['parent'];
        if (!isset($this->documentData['parent'])) {
            $this->documentData['parent'] = $this->documentData['oldparent'];
        }
        if (!isset($this->documentData['template'])) {
            $this->documentData['template'] = $existingDocument['template'];
        }

        if ($this->documentData['id'] == EvolutionCMS()->getConfig('site_start') && $this->documentData['published'] == 0) {
            throw new ServiceActionException("Document is linked to site_start variable and cannot be unpublished!");
        }
        $today = EvolutionCMS()->timestamp((int)get_by_key($_SERVER, 'REQUEST_TIME', 0));
        if ($this->documentData['id'] == EvolutionCMS()->getConfig('site_start') && ($this->documentData['pub_date'] > $today || $this->documentData['unpub_date'] != "0")) {
            throw new ServiceActionException("Document is linked to site_start variable and cannot have publish or unpublish dates set!");
        }
        if ($this->documentData['parent'] == $this->documentData['id']) {
            throw new ServiceActionException("Document can not be it's own parent!");
        }

        $parents = EvolutionCMS()->getParentIds($this->documentData['parent']);
        if (in_array($this->documentData['id'], $parents)) {
            throw new ServiceActionException("Document descendant can not be it's parent!");
        }

        // check to see document is a folder
        $child = \EvolutionCMS\Models\SiteContent::withTrashed()->select('id')->where('parent', $this->documentData['id'])->first();
        if (!is_null($child)) {
            $this->documentData['isfolder'] = 1;
        }

        // set publishedon and publishedby
        $was_published = $existingDocument['published'];

        // keep original publish state, if change is not permitted
        if (!EvolutionCMS()->hasPermission('publish_document')) {
            $this->documentData['published'] = $was_published;
            $this->documentData['pub_date'] = $existingDocument['pub_date'];
            $this->documentData['unpub_date'] = $existingDocument['unpub_date'];
        }

        // if it was changed from unpublished to published
        if (!$was_published && $this->documentData['published']) {
            $this->documentData['publishedon'] = $this->currentDate;
            $this->documentData['publishedby'] = EvolutionCMS()->getLoginUserID();
        } elseif ((!empty($this->documentData['pub_date']) && $this->documentData['pub_date'] <= $this->currentDate && $this->documentData['published'])) {
            $this->documentData['publishedon'] = $this->documentData['pub_date'];
            $this->documentData['publishedby'] = EvolutionCMS()->getLoginUserID();
        } elseif ($was_published && !$this->documentData['published']) {
            $this->documentData['publishedon'] = 0;
            $this->documentData['publishedby'] = 0;
        } else {
            $this->documentData['publishedon'] = $existingDocument['publishedon'];
            $this->documentData['publishedby'] = $existingDocument['publishedby'];
        }


    }

}
