<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;
use EvolutionCMS\Traits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use EvolutionCMS\Extensions\Collection;

/**
 * EvolutionCMS\Models\SiteContent
 *
 * @property int $id
 * @property string $type
 * @property string $contentType
 * @property string $pagetitle
 * @property string $longtitle
 * @property string $description
 * @property string $alias
 * @property string $link_attributes
 * @property int $published
 * @property int $pub_date
 * @property int $unpub_date
 * @property int $parent
 * @property int $isfolder
 * @property string $introtext
 * @property string $content
 * @property bool $richtext
 * @property int $template
 * @property int $menuindex
 * @property int $searchable
 * @property int $cacheable
 * @property int $createdby
 * @property int $createdon
 * @property int $editedby
 * @property int $editedon
 * @property int $deleted
 * @property string $deletedon
 * @property int $deletedby
 * @property int $publishedon
 * @property int $publishedby
 * @property string $menutitle
 * @property bool $hide_from_tree
 * @property bool $privateweb
 * @property bool $privatemgr
 * @property bool $content_dispo
 * @property bool $hidemenu
 * @property int $alias_visible
 *
 * BelongsTo
 * @property SiteContent|null $ancestor
 * @property SiteTemplate|null $tpl
 *
 * HasMany
 * @property Eloquent\Collection $children
 * @property Eloquent\Collection $templateValues
 *
 * BelongsToMany
 * @property Eloquent\Collection $documentGroups
 *
 * Virtual
 * @property-read \Carbon\Carbon $pub_at
 * @property-read \Carbon\Carbon $unPub_at
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read \Carbon\Carbon $deleted_at
 * @property-read bool $isAlreadyEdit
 * @property-read null|array $alreadyEditInfo
 * @property-read mixed $already_edit_info
 * @property-read mixed $is_already_edit
 * @property-read mixed $node_name
 * @property-read mixed $un_pub_at
 * @property-read bool $wasNull
 *
 * Scope
 * @method static Eloquent\Builder publishDocuments($time)
 * @method static Eloquent\Builder unPublishDocuments($time)
 *
 * @mixin \Eloquent
 */
class SiteContent extends Eloquent\Model
{
    use Traits\Models\SoftDeletes,
        Traits\Models\ManagerActions,
        Traits\Models\TimeMutator;

    protected $table = 'site_content';

    const CREATED_AT = 'createdon';
    const UPDATED_AT = 'editedon';
    const DELETED_AT = 'deletedon';
    const DELETED = 'deleted';

    protected $dateFormat = 'U';

    const CHILDREN_RELATION_NAME = 'children';

    /**
     * Upper bound for the filters of one tvFilter() call and for the sort terms of one tvOrderBy()
     * call: every one of them costs the database a join or a sort key, so an unbounded list is a DoS.
     */
    const MAX_TV_QUERY_TERMS = 20;

    /**
     * Operators a tvFilter() term may use. The operator is spliced into raw SQL for numeric casts,
     * so it has to be an exact match, not a pattern.
     */
    const TV_FILTER_OPERATORS = [
        '=', '!=', '<>', '<', '>', '<=', '>=',
        'like', 'like-l', 'like-r', 'in', 'not_in', 'isnull', 'null', 'isnotnull', '!null',
    ];

    /**
     * ClosureTable model instance.
     *
     * @var ClosureTable
     */
    protected $closure = ClosureTable::class;

    /**
     * Cached "previous" (i.e. before the model is moved) direct ancestor id of this model.
     *
     * @var int
     */
    private $previousParentId;

    /**
     * Cached "previous" (i.e. before the model is moved) model position.
     *
     * @var int
     */
    private $previousPosition;

    /**
     * Whether this node is being moved to another parent node.
     *
     * @var bool
     */
    private $isMoved = false;

    /**
     * Indicates if the model should soft delete.
     *
     * @var bool
     */
    protected $softDelete = true;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Entity constructor.
     *
     * @param array $attributes
     */

    protected $casts = [
        'published' => 'int',
        'pub_date' => 'int',
        'unpub_date' => 'int',
        'parent' => 'int',
        'isfolder' => 'int',
        'richtext' => 'bool',
        'template' => 'int',
        'menuindex' => 'int',
        'searchable' => 'int',
        'cacheable' => 'int',
        'createdby' => 'int',
        'createdon' => 'int',
        'editedby' => 'int',
        'editedon' => 'int',
        'deleted' => 'int',
        'deletedby' => 'int',
        'publishedon' => 'int',
        'publishedby' => 'int',
        'hide_from_tree' => 'bool',
        'privateweb' => 'bool',
        'privatemgr' => 'bool',
        'content_dispo' => 'bool',
        'hidemenu' => 'bool',
        'alias_visible' => 'int',
    ];

    /*
     * @phpstan-consistent-constructor
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $position = $this->getPositionColumn();

        $this->fillable(array_merge($this->getFillable(), [$position]));

        if (isset($attributes[$position]) && $attributes[$position] < 0) {
            $attributes[$position] = 0;
        }

        parent::__construct($attributes);
    }

    /**
     * The closure table model, built on first use: reading documents never
     * needs it, and building one for every hydrated row doubled the models.
     *
     * @return ClosureTable
     */
    protected function closureTable(): ClosureTable
    {
        if (!$this->closure instanceof ClosureTable) {
            $this->closure = new $this->closure;

            // The default class name of the closure table was not changed
            // so we define and set default closure table name automagically.
            // This can prevent useless copy paste of closure table models.
            if (get_class($this->closure) === ClosureTable::class) {
                $this->closure->setTable($this->getTable() . '_closure');
            }
        }

        return $this->closure;
    }

    // adjust boot function
    public static function boot()
    {
        // run parent
        parent::boot();

        static::saving(static function (SiteContent $entity) {
            $entity->editedon = time();
            if ($entity->isDirty($entity->getPositionColumn())) {
                $latest = static::getLatestPosition($entity);
                $entity->menuindex = max(0, min($entity->menuindex, $latest));
            } elseif (!$entity->exists) {
                $entity->menuindex = static::getLatestPosition($entity);
            }
        });

        static::creating(static function (SiteContent $entity) {
            $entity->createdon = time();
        });
        // When entity is created, the appropriate
        // data will be put into the closure table.
        static::created(static function (SiteContent $entity) {
            $entity->previousParentId = null;
            $entity->previousPosition = null;

            $descendant = $entity->getKey();
            $ancestor = isset($entity->parent) ? $entity->parent : $descendant;

            $entity->closureTable()->insertNode($ancestor, $descendant);
        });

        static::saved(static function (SiteContent $entity) {
            $parentIdChanged = $entity->isDirty($entity->getParentIdColumn());

            if ($parentIdChanged || $entity->isDirty($entity->getPositionColumn())) {
                $entity->reorderSiblings();
            }

            if ($entity->closureTable()->ancestor === null) {
                $primaryKey = $entity->getKey();
                $entity->closureTable()->ancestor = $primaryKey;
                $entity->closureTable()->descendant = $primaryKey;
                $entity->closureTable()->depth = 0;
            }

            if ($parentIdChanged) {
                $entity->closureTable()->moveNodeTo($entity->parent);
            }

        });

        // add in custom deleting
        static::deleting(function ($model) {
            // save custom delete value
            $model->deleted = 1;
            $model->save();
        });

        // add in custom restoring
        static::restoring(function ($model) {
            // save custom delete value
            $model->deleted = 0;
            $model->save();
        });

    }

    protected $fillable = [
        'type',
        'contentType',
        'pagetitle',
        'longtitle',
        'description',
        'alias',
        'link_attributes',
        'published',
        'pub_date',
        'unpub_date',
        'parent',
        'isfolder',
        'introtext',
        'content',
        'richtext',
        'template',
        'menuindex',
        'searchable',
        'cacheable',
        'createdby',
        'editedby',
        'deleted',
        'deletedby',
        'publishedon',
        'publishedby',
        'menutitle',
        'hide_from_tree',
        'privateweb',
        'privatemgr',
        'content_dispo',
        'hidemenu',
        'alias_visible'
    ];

    protected $managerActionsMap = [
        'id' => [
            'actions.info' => 3
        ]
    ];

    /**
     * @return mixed
     */
    public function getNodeNameAttribute()
    {
        $key = evo()->getConfig('resource_tree_node_name', 'pagetitle');
        if (mb_strtolower($key) === 'nodename') {
            $key = 'pagetitle';
        }

        return $this->getAttributeValue($key);
    }

    /**
     * @return \Illuminate\Support\Carbon|null
     */
    public function getCreatedAtAttribute()
    {
        return $this->convertTimestamp($this->createdon);
    }

    /**
     * @return \Illuminate\Support\Carbon|null
     */
    public function getUpdatedAtAttribute()
    {
        return $this->convertTimestamp($this->editedon);
    }

    /**
     * @return \Illuminate\Support\Carbon|null
     */
    public function getDeletedAtAttribute()
    {
        return $this->convertTimestamp($this->deletedon);
    }

    /**
     * @return \Illuminate\Support\Carbon|null
     */
    public function getPubAtAttribute()
    {
        return $this->convertTimestamp($this->pub_date);
    }

    /**
     * @return \Illuminate\Support\Carbon|null
     */
    public function getUnPubAtAttribute()
    {
        return $this->convertTimestamp($this->unpub_date);
    }

    /**
     * @return bool
     */
    public function getWasNullAttribute(): bool
    {
        return trim($this->content) === '' && $this->template === 0;
    }

    public static function getLockedElements()
    {
        return evo()->getLockedElements(7);
    }

    /**
     * @return bool
     */
    public function getIsAlreadyEditAttribute(): bool
    {
        return array_key_exists($this->getKey(), self::getLockedElements());
    }

    /**
     * @return array|null
     */
    public function getAlreadyEditInfoAttribute(): ?array
    {
        return $this->isAlreadyEdit ? self::getLockedElements()[$this->getKey()] : null;
    }

    /**
     * @return array
     */
    public function getAllChildren($parent): array
    {
        $ids = [];
        foreach ($parent->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllChildren($child));
        }
        return $ids;
    }

    /**
     * @return Collection
     */
    public function getTvAttribute()
    {
        /** @var Collection $docTv */
        if ($this->tpl->tvs === null) {
            return collect();
        }
        $docTv = $this->templateValues->pluck('value', 'tmplvarid');
        return $this->tpl->tvs->map(function (SiteTmplvar $value) use ($docTv) {
            $out = $value->default_text;
            if ($docTv->has($value->getKey())) {
                $out = $docTv->get($value->getKey());
            }

            return ['name' => $value->name, 'value' => $out];
        });
    }

    /**
     * @param Eloquent\Builder $builder
     * @param $time
     * @return Eloquent\Builder
     */
    public function scopePublishDocuments(Eloquent\Builder $builder, $time): Eloquent\Builder
    {
        return $builder->where('pub_date', '<=', $time)
            ->where('pub_date', '>', 0)
            ->where(function (Eloquent\Builder $query) use ($time) {
                $query->where('unpub_date', '>', $time)
                    ->orWhere('unpub_date', '=', 0);
            })->where('published', '=', 0);
    }

    /**
     * @param Eloquent\Builder $builder
     * @param $time
     * @return Eloquent\Builder
     */
    public function scopeUnPublishDocuments(Eloquent\Builder $builder, $time): Eloquent\Builder
    {
        return $builder->where('unpub_date', '<=', $time)
            ->where('unpub_date', '>', 0)
            ->where('published', '=', 1);
    }

    /**
     * @return Eloquent\Relations\HasMany
     */
    public function templateValues(): Eloquent\Relations\HasMany
    {
        return $this->hasMany(SiteTmplvarContentvalue::class, 'contentid', 'id');
    }

    /**
     * @return Eloquent\Relations\BelongsTo
     */
    public function ancestor(): Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent')
            ->withTrashed();
    }

    /**
     * @return Eloquent\Relations\HasMany
     */
    public function children(): Eloquent\Relations\HasMany
    {
        return $this->hasMany(get_class($this), $this->getParentIdColumn())->withTrashed();
    }

    /**
     * @return Eloquent\Relations\BelongsToMany
     */
    public function documentGroups(): Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(DocumentgroupName::class, 'document_groups', 'document', 'document_group');
    }

    /**
     * @return Eloquent\Relations\BelongsTo
     */
    public function tpl(): Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SiteTemplate::class, 'template', 'id')->withDefault();
    }


    public function newFromBuilder($attributes = [], $connection = null)
    {
        $instance = parent::newFromBuilder($attributes);
        $instance->previousParentId = $instance->parent;
        $instance->previousPosition = $instance->menuindex;
        return $instance;
    }

    /**
     * Gets value of the "parent id" attribute.
     *
     * @return int
     */
    public function getParentIdAttribute()
    {
        return $this->getAttributeFromArray($this->getParentIdColumn());
    }

    /**
     * Sets new parent id and caches the old one.
     *
     * @param int $value
     */
    public function setParentIdAttribute($value)
    {
        if ($this->parent === $value) {
            return;
        }

        $parentId = $this->getParentIdColumn();
        $this->previousParentId = isset($this->original[$parentId]) ? $this->original[$parentId] : null;
        $this->attributes[$parentId] = $value;
    }

    /**
     * Gets the fully qualified "parent id" column.
     *
     * @return string
     */
    public function getQualifiedParentIdColumn()
    {
        return $this->getTable() . '.' . $this->getParentIdColumn();
    }

    /**
     * Gets the short name of the "parent id" column.
     *
     * @return string
     */
    public function getParentIdColumn()
    {
        return 'parent';
    }

    /**
     * Get the name of the "deleted" column.
     *
     * @return string
     */
    public function getDeletedColumn()
    {
        return defined('static::DELETED') ? static::DELETED : 'deleted';
    }

    /**
     * Get the fully qualified "deleted" column.
     *
     * @return string
     */
    public function getQualifiedDeletedColumn()
    {
        return $this->qualifyColumn($this->getDeletedColumn());
    }

    /**
     * Gets value of the "position" attribute.
     *
     * @return int
     */
    public function getPositionAttribute()
    {
        return $this->getAttributeFromArray($this->getPositionColumn());
    }

    /**
     * Sets new position and caches the old one.
     *
     * @param int $value
     */
    public function setPositionAttribute($value)
    {
        if ($this->menuindex === $value) {
            return;
        }

        $position = $this->getPositionColumn();
        $this->previousPosition = isset($this->original[$position]) ? $this->original[$position] : null;
        $this->attributes[$position] = max(0, (int)$value);
    }

    /**
     * Gets the fully qualified "position" column.
     *
     * @return string
     */
    public function getQualifiedPositionColumn()
    {
        return $this->getTable() . '.' . $this->getPositionColumn();
    }

    /**
     * Gets the short name of the "position" column.
     *
     * @return string
     */
    public function getPositionColumn()
    {
        return 'menuindex';
    }

    /**
     * Gets the fully qualified "real depth" column.
     *
     * @return string
     */
    public function getQualifiedRealDepthColumn()
    {
        return $this->getTable() . '.' . $this->getRealDepthColumn();
    }


    /**
     * Indicates whether the model is a parent.
     *
     * @return bool
     */
    public function isParent()
    {
        return $this->exists && $this->hasChildren();
    }

    /**
     * Indicates whether the model has no ancestors.
     *
     * @return bool
     */
    public function isRoot()
    {
        return $this->exists && $this->parent === null;
    }

    /**
     * Retrieves direct ancestor of a model.
     *
     * @param array $columns
     * @return Entity|null
     */
    public function getParent(array $columns = ['*'])
    {
        return $this->exists ? $this->find($this->parent, $columns) : null;
    }

    /**
     * Returns query builder for ancestors.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeAncestors(Builder $builder)
    {
        return $this->buildAncestorsQuery($builder, $this->getKey(), false);
    }

    /**
     * Returns query builder for ancestors of the node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeAncestorsOf(Builder $builder, $id)
    {
        return $this->buildAncestorsQuery($builder, $id, false);
    }

    /**
     * Returns query builder for ancestors including the current node.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeAncestorsWithSelf(Builder $builder)
    {
        return $this->buildAncestorsQuery($builder, $this->getKey(), true);
    }

    /**
     * Returns query builder for ancestors of the node with given ID including that node also.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeAncestorsWithSelfOf(Builder $builder, $id)
    {
        return $this->buildAncestorsQuery($builder, $id, true);
    }

    /**
     * Builds base ancestors query.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param bool $withSelf
     *
     * @return Builder
     */
    private function buildAncestorsQuery(Builder $builder, $id, $withSelf)
    {
        $depthOperator = $withSelf ? '>=' : '>';

        return $builder
            ->join(
                $this->closureTable()->getTable(),
                $this->closureTable()->getAncestorColumn(),
                '=',
                $this->getQualifiedKeyName()
            )
            ->where($this->closureTable()->getDescendantColumn(), '=', $id)
            ->where($this->closureTable()->getDepthColumn(), $depthOperator, 0);
    }

    /**
     * Retrieves all ancestors of a model.
     *
     * @param array $columns
     * @return \Franzose\ClosureTable\Extensions\Collection
     */
    public function getAncestors(array $columns = ['*'])
    {
        return $this->ancestors()->get($columns);
    }

    /**
     * Returns a number of model's ancestors.
     *
     * @return int
     */
    public function countAncestors()
    {
        return $this->ancestors()->count();
    }

    /**
     * Indicates whether a model has ancestors.
     *
     * @return bool
     */
    public function hasAncestors()
    {
        return (bool)$this->countAncestors();
    }

    /**
     * Returns query builder for descendants.
     *
     * @param Builder $builder
     * @param bool $withSelf
     *
     * @return Builder
     */
    public function scopeDescendants(Builder $builder)
    {
        return $this->buildDescendantsQuery($builder, $this->getKey(), false);
    }

    /**
     * Returns query builder for descendants of the node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeDescendantsOf(Builder $builder, $id)
    {
        return $this->buildDescendantsQuery($builder, $id, false);
    }

    /**
     * Returns query builder for descendants including the current node.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeDescendantsWithSelf(Builder $builder)
    {
        return $this->buildDescendantsQuery($builder, $this->getKey(), true);
    }

    /**
     * Returns query builder for descendants including the current node of the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeDescendantsWithSelfOf(Builder $builder, $id)
    {
        return $this->buildDescendantsQuery($builder, $id, true);
    }

    /**
     * Builds base descendants query.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param bool $withSelf
     *
     * @return Builder
     */
    private function buildDescendantsQuery(Builder $builder, $id, $withSelf)
    {
        $depthOperator = $withSelf ? '>=' : '>';

        return $builder
            ->join(
                $this->closureTable()->getTable(),
                $this->closureTable()->getDescendantColumn(),
                '=',
                $this->getQualifiedKeyName()
            )
            ->where($this->closureTable()->getAncestorColumn(), '=', $id)
            ->where($this->closureTable()->getDepthColumn(), $depthOperator, 0);
    }

    /**
     * Retrieves all descendants of a model.
     *
     * @param array $columns
     * @return Collection
     */
    public function getDescendants(array $columns = ['*'])
    {
        return $this->descendants()->get($columns);
    }

    /**
     * Returns a number of model's descendants.
     *
     * @return int
     */
    public function countDescendants()
    {
        return $this->descendants()->count();
    }

    /**
     * Indicates whether a model has descendants.
     *
     * @return bool
     */
    public function hasDescendants()
    {
        return (bool)$this->countDescendants();
    }

    /**
     * Retrieves all children of a model.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function getChildren(array $columns = ['*'])
    {
        return $this->children()->get($columns);
    }

    /**
     * Returns a number of model's children.
     *
     * @return int
     */
    public function countChildren()
    {
        return $this->children()->count();
    }

    /**
     *  Indicates whether a model has children.
     *
     * @return bool
     */
    public function hasChildren()
    {
        return (bool)$this->countChildren();
    }

    /**
     * Returns query builder for child nodes.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeChildNode(Builder $builder)
    {
        return $this->scopeChildNodeOf($builder, $this->getKey());
    }

    /**
     * Returns query builder for child nodes of the node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeChildNodeOf(Builder $builder, $id)
    {
        $parentId = $this->getParentIdColumn();

        return $builder
            ->whereNotNull($parentId)
            ->where($parentId, '=', $id);
    }

    /**
     * Returns query builder for a child at the given position.
     *
     * @param Builder $builder
     * @param int $position
     *
     * @return Builder
     */
    public function scopeChildAt(Builder $builder, $position)
    {
        return $this
            ->scopeChildNode($builder)
            ->where($this->getPositionColumn(), '=', $position);
    }

    /**
     * Returns query builder for a child at the given position of the node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param int $position
     *
     * @return Builder
     */
    public function scopeChildOf(Builder $builder, $id, $position)
    {
        return $this
            ->scopeChildNodeOf($builder, $id)
            ->where($this->getPositionColumn(), '=', $position);
    }

    /**
     * Retrieves a child with given position.
     *
     * @param int $position
     * @param array $columns
     * @return Entity
     */
    public function getChildAt($position, array $columns = ['*'])
    {
        return $this->childAt($position)->first($columns);
    }

    /**
     * Returns query builder for the first child node.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeFirstChild(Builder $builder)
    {
        return $this->scopeChildAt($builder, 0);
    }

    /**
     * Returns query builder for the first child node of the node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeFirstChildOf(Builder $builder, $id)
    {
        return $this->scopeChildOf($builder, $id, 0);
    }

    /**
     * Retrieves the first child.
     *
     * @param array $columns
     * @return Entity
     */
    public function getFirstChild(array $columns = ['*'])
    {
        return $this->getChildAt(0, $columns);
    }

    /**
     * Returns query builder for the last child node.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeLastChild(Builder $builder)
    {
        return $this->scopeChildNode($builder)->orderByDesc($this->getPositionColumn());
    }

    /**
     * Returns query builder for the last child node of the node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeLastChildOf(Builder $builder, $id)
    {
        return $this->scopeChildNodeOf($builder, $id)->orderByDesc($this->getPositionColumn());
    }

    /**
     * Retrieves the last child.
     *
     * @param array $columns
     * @return Entity
     */
    public function getLastChild(array $columns = ['*'])
    {
        return $this->lastChild()->first($columns);
    }

    /**
     * Returns query builder to child nodes in the range of the given positions.
     *
     * @param Builder $builder
     * @param int $from
     * @param int|null $to
     *
     * @return Builder
     */
    public function scopeChildrenRange(Builder $builder, $from, $to = null)
    {
        $position = $this->getPositionColumn();
        $query = $this->scopeChildNode($builder)->where($position, '>=', $from);

        if ($to !== null) {
            $query->where($position, '<=', $to);
        }

        return $query;
    }

    /**
     * Returns query builder to child nodes in the range of the given positions for the node of the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param int $from
     * @param int|null $to
     *
     * @return Builder
     */
    public function scopeChildrenRangeOf(Builder $builder, $id, $from, $to = null)
    {
        $position = $this->getPositionColumn();
        $query = $this->scopeChildNodeOf($builder, $id)->where($position, '>=', $from);

        if ($to !== null) {
            $query->where($position, '<=', $to);
        }

        return $query;
    }

    /**
     * Retrieves children within given positions range.
     *
     * @param int $from
     * @param int $to
     * @param array $columns
     * @return Collection
     */
    public function getChildrenRange($from, $to = null, array $columns = ['*'])
    {
        return $this->childrenRange($from, $to)->get($columns);
    }

    /**
     * Appends a child to the model.
     *
     * @param SiteContent $child
     * @param int $position
     * @param bool $returnChild
     * @return SiteContent
     */
    public function addChild(SiteContent $child, $position = null, $returnChild = false)
    {
        if ($this->exists) {
            $position = $position !== null ? $position : $this->getLatestChildPosition();

            $child->moveTo($position, $this);
        }

        return $returnChild === true ? $child : $this;
    }

    /**
     * Returns the latest child position.
     *
     * @return int
     */
    private function getLatestChildPosition()
    {
        $lastChild = $this->lastChild()->first([$this->getPositionColumn()]);

        return $lastChild !== null ? $lastChild->menuindex + 1 : 0;
    }

    /**
     * Appends a collection of children to the model.
     *
     * @param Entity[] $children
     * @param int $from
     *
     * @return Entity
     * @throws InvalidArgumentException
     * @throws \Throwable
     */
    public function addChildren(array $children, $from = null)
    {
        if (!$this->exists) {
            return $this;
        }

        $this->transactional(function () use (&$from, $children) {
            foreach ($children as $child) {
                $this->addChild($child, $from);
                $from++;
            }
        });

        return $this;
    }

    /**
     * Appends the given entity to the children relation.
     *
     * @param Entity $entity
     * @internal
     */
    public function appendChild(SiteContent $entity)
    {
        $this->getChildrenRelation()->add($entity);
    }

    /**
     * @return Collection
     */
    private function getChildrenRelation()
    {
        if (!$this->relationLoaded(static::CHILDREN_RELATION_NAME)) {
            $this->setRelation(static::CHILDREN_RELATION_NAME, new Collection());
        }

        return $this->getRelation(static::CHILDREN_RELATION_NAME);
    }

    /**
     * Removes a model's child with given position.
     *
     * @param int $position
     * @param bool $forceDelete
     *
     * @return $this
     * @throws \Throwable
     */
    public function removeChild($position = null, $forceDelete = false)
    {
        if (!$this->exists) {
            return $this;
        }

        $child = $this->getChildAt($position, [
            $this->getKeyName(),
            $this->getParentIdColumn(),
            $this->getPositionColumn()
        ]);

        if ($child === null) {
            return $this;
        }

        $this->transactional(function () use ($child, $forceDelete) {
            $action = ($forceDelete === true ? 'forceDelete' : 'delete');

            $child->{$action}();

            $child->nextSiblings()->decrement($this->getPositionColumn());
        });

        return $this;
    }

    /**
     * Removes model's children within a range of positions.
     *
     * @param int $from
     * @param int $to
     * @param bool $forceDelete
     *
     * @return $this
     * @throws InvalidArgumentException
     * @throws \Throwable
     */
    public function removeChildren($from, $to = null, $forceDelete = false)
    {
        if (!is_numeric($from) || ($to !== null && !is_numeric($to))) {
            throw new \InvalidArgumentException('`from` and `to` are the position boundaries. They must be of type int.');
        }

        if (!$this->exists) {
            return $this;
        }

        $this->transactional(function () use ($from, $to, $forceDelete) {
            $action = ($forceDelete === true ? 'forceDelete' : 'delete');

            $this->childrenRange($from, $to)->{$action}();

            if ($to !== null) {
                $this
                    ->childrenRange($to)
                    ->decrement($this->getPositionColumn(), $to - $from + 1);
            }
        });

        return $this;
    }

    /**
     * Returns sibling query builder.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeSibling(Builder $builder)
    {
        return $builder->where($this->getParentIdColumn(), '=', $this->parent);
    }

    /**
     * Returns query builder for siblings of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeSiblingOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id);
    }

    /**
     * Returns siblings query builder.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeSiblings(Builder $builder)
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '<>', $this->menuindex);
    }

    /**
     * Return query builder for siblings of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeSiblingsOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id, function ($position) {
            return function (Builder $builder) use ($position) {
                $builder->where($this->getPositionColumn(), '<>', $position);
            };
        });
    }

    /**
     * Retrives all siblings of a model.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function getSiblings(array $columns = ['*'])
    {
        return $this->siblings()->get($columns);
    }

    /**
     * Returns number of model's siblings.
     *
     * @return int
     */
    public function countSiblings()
    {
        return $this->siblings()->count();
    }

    /**
     * Indicates whether a model has siblings.
     *
     * @return bool
     */
    public function hasSiblings()
    {
        return (bool)$this->countSiblings();
    }

    /**
     * Returns neighbors query builder.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeNeighbors(Builder $builder)
    {
        $position = $this->menuindex;

        return $this
            ->scopeSiblings($builder)
            ->whereIn($this->getPositionColumn(), [$position - 1, $position + 1]);
    }

    /**
     * Returns query builder for the neighbors of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeNeighborsOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id, function ($position) {
            return function (Builder $builder) use ($position) {
                return $builder->whereIn($this->getPositionColumn(), [$position - 1, $position + 1]);
            };
        });
    }

    /**
     * Retrieves neighbors (immediate previous and immediate next models) of a model.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function getNeighbors(array $columns = ['*'])
    {
        return $this->neighbors()->get($columns);
    }

    /**
     * Returns query builder for a sibling at the given position.
     *
     * @param Builder $builder
     * @param int $position
     *
     * @return Builder
     */
    public function scopeSiblingAt(Builder $builder, $position)
    {
        return $this
            ->scopeSiblings($builder)
            ->where($this->getPositionColumn(), '=', $position);
    }

    /**
     * Returns query builder for a sibling at the given position of a node of the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param int $position
     *
     * @return Builder
     */
    public function scopeSiblingOfAt(Builder $builder, $id, $position)
    {
        return $this
            ->scopeSiblingOf($builder, $id)
            ->where($this->getPositionColumn(), '=', $position);
    }

    /**
     * Retrieves a model's sibling with given position.
     *
     * @param int $position
     * @param array $columns
     * @return Entity
     */
    public function getSiblingAt($position, array $columns = ['*'])
    {
        return $this->siblingAt($position)->first($columns);
    }

    /**
     * Returns query builder for the first sibling.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeFirstSibling(Builder $builder)
    {
        return $this->scopeSiblingAt($builder, 0);
    }

    /**
     * Returns query builder for the first sibling of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeFirstSiblingOf(Builder $builder, $id)
    {
        return $this->scopeSiblingOfAt($builder, $id, 0);
    }

    /**
     * Retrieves the first model's sibling.
     *
     * @param array $columns
     * @return Entity
     */
    public function getFirstSibling(array $columns = ['*'])
    {
        return $this->getSiblingAt(0, $columns);
    }

    /**
     * Returns query builder for the last sibling.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeLastSibling(Builder $builder)
    {
        return $this->scopeSiblings($builder)->orderByDesc($this->getPositionColumn());
    }

    /**
     * Returns query builder for the last sibling of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeLastSiblingOf(Builder $builder, $id)
    {
        return $this
            ->scopeSiblingOf($builder, $id)
            ->orderByDesc($this->getPositionColumn())
            ->limit(1);
    }

    /**
     * Retrieves the last model's sibling.
     *
     * @param array $columns
     * @return Entity
     */
    public function getLastSibling(array $columns = ['*'])
    {
        return $this->lastSibling()->first($columns);
    }

    /**
     * Returns query builder for the previous sibling.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopePrevSibling(Builder $builder)
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '=', $this->menuindex - 1);
    }

    /**
     * Returns query builder for the previous sibling of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopePrevSiblingOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id, function ($position) {
            return function (Builder $builder) use ($position) {
                return $builder->where($this->getPositionColumn(), '=', $position - 1);
            };
        });
    }

    /**
     * Retrieves immediate previous sibling of a model.
     *
     * @param array $columns
     * @return Entity
     */
    public function getPrevSibling(array $columns = ['*'])
    {
        return $this->prevSibling()->first($columns);
    }

    /**
     * Returns query builder for the previous siblings.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopePrevSiblings(Builder $builder)
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '<', $this->menuindex);
    }

    /**
     * Returns query builder for the previous siblings of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopePrevSiblingsOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id, function ($position) {
            return function (Builder $builder) use ($position) {
                return $builder->where($this->getPositionColumn(), '<', $position);
            };
        });
    }

    /**
     * Retrieves all previous siblings of a model.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function getPrevSiblings(array $columns = ['*'])
    {
        return $this->prevSiblings()->get($columns);
    }

    /**
     * Returns number of previous siblings of a model.
     *
     * @return int
     */
    public function countPrevSiblings()
    {
        return $this->prevSiblings()->count();
    }

    /**
     * Indicates whether a model has previous siblings.
     *
     * @return bool
     */
    public function hasPrevSiblings()
    {
        return (bool)$this->countPrevSiblings();
    }

    /**
     * Returns query builder for the next sibling.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeNextSibling(Builder $builder)
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '=', $this->menuindex + 1);
    }

    /**
     * Returns query builder for the next sibling of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeNextSiblingOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id, function ($position) {
            return function (Builder $builder) use ($position) {
                return $builder->where($this->getPositionColumn(), '=', $position + 1);
            };
        });
    }

    /**
     * Retrieves immediate next sibling of a model.
     *
     * @param array $columns
     * @return Entity
     */
    public function getNextSibling(array $columns = ['*'])
    {
        return $this->nextSibling()->first($columns);
    }

    /**
     * Returns query builder for the next siblings.
     *
     * @param Builder $builder
     *
     * @return Builder
     */
    public function scopeNextSiblings(Builder $builder)
    {
        return $this
            ->scopeSibling($builder)
            ->where($this->getPositionColumn(), '>', $this->menuindex);
    }

    /**
     * Returns query builder for the next siblings of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     *
     * @return Builder
     */
    public function scopeNextSiblingsOf(Builder $builder, $id)
    {
        return $this->buildSiblingQuery($builder, $id, function ($position) {
            return function (Builder $builder) use ($position) {
                return $builder->where($this->getPositionColumn(), '>', $position);
            };
        });
    }

    /**
     * Retrieves all next siblings of a model.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public function getNextSiblings(array $columns = ['*'])
    {
        return $this->nextSiblings()->get($columns);
    }

    /**
     * Returns number of next siblings of a model.
     *
     * @return int
     */
    public function countNextSiblings()
    {
        return $this->nextSiblings()->count();
    }

    /**
     * Indicates whether a model has next siblings.
     *
     * @return bool
     */
    public function hasNextSiblings()
    {
        return (bool)$this->countNextSiblings();
    }

    /**
     * Returns query builder for a range of siblings.
     *
     * @param Builder $builder
     * @param int $from
     * @param int|null $to
     *
     * @return Builder
     */
    public function scopeSiblingsRange(Builder $builder, $from, $to = null)
    {
        $position = $this->getPositionColumn();

        $query = $this
            ->scopeSiblings($builder)
            ->where($position, '>=', $from);

        if ($to !== null) {
            $query->where($position, '<=', $to);
        }

        return $query;
    }

    /**
     * Returns query builder for a range of siblings of a node with the given ID.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param int $from
     * @param int|null $to
     *
     * @return Builder
     */
    public function scopeSiblingsRangeOf(Builder $builder, $id, $from, $to = null)
    {
        $position = $this->getPositionColumn();

        $query = $this
            ->buildSiblingQuery($builder, $id)
            ->where($position, '>=', $from);

        if ($to !== null) {
            $query->where($position, '<=', $to);
        }

        return $query;
    }

    /**
     * Retrieves siblings within given positions range.
     *
     * @param int $from
     * @param int $to
     * @param array $columns
     * @return Collection
     */
    public function getSiblingsRange($from, $to = null, array $columns = ['*'])
    {
        return $this->siblingsRange($from, $to)->get($columns);
    }

    /**
     * Builds query for siblings.
     *
     * @param Builder $builder
     * @param mixed $id
     * @param callable|null $positionCallback
     *
     * @return Builder
     */
    private function buildSiblingQuery(Builder $builder, $id, ?callable $positionCallback = null)
    {
        $parentIdColumn = $this->getParentIdColumn();
        $positionColumn = $this->getPositionColumn();

        $entity = $this
            ->select([$this->getKeyName(), $parentIdColumn, $positionColumn])
            ->from($this->getTable())
            ->where($this->getKeyName(), '=', $id)
            ->limit(1)
            ->first();

        if ($entity === null) {
            return $builder;
        }

        if ($entity->parent === null) {
            $builder->whereNull($parentIdColumn);
        } else {
            $builder->where($parentIdColumn, '=', $entity->parent);
        }

        if (is_callable($positionCallback)) {
            $builder->where($positionCallback($entity->menuindex));
        }

        return $builder;
    }

    /**
     * Appends a sibling within the current depth.
     *
     * @param SiteContent $sibling
     * @param int|null $position
     * @param bool $returnSibling
     * @return SiteContent
     */
    public function addSibling(SiteContent $sibling, $position = null, $returnSibling = false)
    {
        if ($this->exists) {
            $position = $position === null ? static::getLatestPosition($this) : $position;

            $sibling->moveTo($position, $this->parent);

            if ($position < $this->menuindex) {
                $this->menuindex++;
            }
        }

        return ($returnSibling === true ? $sibling : $this);
    }

    /**
     * Appends multiple siblings within the current depth.
     *
     * @param Entity[] $siblings
     * @param int|null $from
     *
     * @return Entity
     * @throws Throwable
     */
    public function addSiblings(array $siblings, $from = null)
    {
        if (!$this->exists) {
            return $this;
        }

        $from = $from === null ? static::getLatestPosition($this) : $from;

        $this->transactional(function () use ($siblings, &$from) {
            foreach ($siblings as $sibling) {
                $this->addSibling($sibling, $from);
                $from++;
            }
        });

        return $this;
    }

    /**
     * Retrieves root (with no ancestors) models.
     *
     * @param array $columns
     *
     * @return Collection
     */
    public static function getRoots(array $columns = ['*'])
    {
        /**
         * @var \Entity $instance
         */
        $instance = new self();

        return $instance->whereNull($instance->getParentIdColumn())->get($columns);
    }

    /**
     * Makes model a root with given position.
     *
     * @param int $position
     * @return $this
     */
    public function makeRoot($position)
    {
        return $this->moveTo($position, null);
    }

    /**
     * Adds "parent id" column to columns list for proper tree querying.
     *
     * @param array $columns
     * @return array
     */
    protected function prepareTreeQueryColumns(array $columns)
    {
        return ($columns === ['*'] ? $columns : array_merge($columns, [$this->getParentIdColumn()]));
    }

    /**
     * Saves models from the given attributes array.
     *
     * @param array $tree
     * @param SiteContent|null $parent
     *
     * @return Collection
     * @throws Throwable
     */
    public static function createFromArray(array $tree, ?SiteContent $parent = null)
    {
        $entities = [];

        foreach ($tree as $item) {
            $children = Arr::pull($item, static::CHILDREN_RELATION_NAME);

            /**
             * @var Entity $entity
             */
            $entity = new self($item);
            $entity->parent = $parent ? $parent->getKey() : null;
            $entity->save();

            if ($children !== null) {
                $entity->addChildren(static::createFromArray($children, $entity)->all());
            }

            $entities[] = $entity;
        }

        return new Collection($entities);
    }

    /**
     * Makes the model a child or a root with given position. Do not use moveTo to move a node within the same ancestor (call position = value and save instead).
     *
     * @param int $position
     * @param SiteContent|int $ancestor
     * @return Entity
     * @throws InvalidArgumentException
     */
    public function moveTo($position, $ancestor = null)
    {
        $parentId = $ancestor instanceof self ? $ancestor->getKey() : $ancestor;

        if ($this->parent === $parentId && $this->parent !== null) {
            return $this;
        }

        if ($this->getKey() === $parentId) {
            throw new \InvalidArgumentException('Target entity is equal to the sender.');
        }

        $this->parent = $parentId;
        $this->menuindex = $position;

        $this->isMoved = true;
        $this->save();
        $this->isMoved = false;

        return $this;
    }

    /**
     * Gets the next sibling position after the last one.
     *
     * @param Entity $entity
     *
     * @return int
     */
    public static function getLatestPosition(SiteContent $entity)
    {
        $positionColumn = $entity->getPositionColumn();
        $parentIdColumn = $entity->getParentIdColumn();

        $latest = $entity->select($positionColumn)
            ->where($parentIdColumn, '=', $entity->parent)
            ->latest($positionColumn)
            ->first();

        $position = $latest !== null ? $latest->menuindex : -1;

        return $position + 1;
    }

    /**
     * Reorders node's siblings when it is moved to another position or ancestor.
     *
     * @return void
     */
    private function reorderSiblings()
    {
        $position = $this->getPositionColumn();

        if ($this->previousPosition !== null) {
            $this
                ->where($this->getKeyName(), '<>', $this->getKey())
                ->where($this->getParentIdColumn(), '=', $this->previousParentId)
                ->where($position, '>', $this->previousPosition)
                ->decrement($position);
        }

        $this
            ->sibling()
            ->where($this->getKeyName(), '<>', $this->getKey())
            ->where($position, '>=', $this->menuindex)
            ->increment($position);
    }

    /**
     * Deletes a subtree from database.
     *
     * @param bool $withSelf
     * @param bool $forceDelete
     *
     * @return void
     * @throws \Exception
     */
    public function deleteSubtree($withSelf = false, $forceDelete = false)
    {
        $action = ($forceDelete === true ? 'forceDelete' : 'delete');

        $query = $withSelf ? $this->descendantsWithSelf() : $this->descendants();
        $ids = $query->pluck($this->getKeyName());

        if ($forceDelete) {
            $this->closureTable()->whereIn($this->closureTable()->getDescendantColumn(), $ids)->delete();
        }

        $this->whereIn($this->getKeyName(), $ids)->$action();
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param array $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Executes queries within a transaction.
     *
     * @param callable $callable
     *
     * @return mixed
     * @throws Throwable
     */
    private function transactional(callable $callable)
    {
        return $this->getConnection()->transaction($callable);
    }

    public function scopePublished($query)
    {
        return $query->where('published', '1');
    }

    public function scopeUnpublished($query)
    {
        return $query->where('published', '0');
    }

    public function scopeActive($query)
    {
        return $query->where('published', '1')->where('deleted', '0');
    }

    /**
     * The rows of a document query as arrays, the same as `$query->get()->toArray()`.
     *
     * No model is hydrated per row only to be turned back into an array: rows
     * whose columns have no accessor and only scalar casts (the usual
     * id/pagetitle/alias listing) are cast directly, any other row goes through
     * one shared model instance, so dates and accessors stay Eloquent's own.
     * The "retrieved" model event does not fire for these rows.
     *
     * @param Builder $query
     * @return array<int, array<string, mixed>>
     */
    public static function toArrays(Builder $query): array
    {
        $model = $query->getModel()->newInstance([], true);
        $rows = [];
        $scalarCasts = null;
        foreach ($query->toBase()->get() as $row) {
            $row = (array) $row;
            // Every row of one query has the same columns: decide once.
            $scalarCasts ??= $model->scalarCastsFor(array_keys($row));
            if ($scalarCasts === false) {
                // toArray() without its recursion guard, which hashes a backtrace
                // per call: a row model has no relations to recurse into.
                $model->setRawAttributes($row);
                $rows[] = array_merge($model->attributesToArray(), $model->relationsToArray());
                continue;
            }
            foreach ($scalarCasts as $key => $type) {
                if ($row[$key] !== null) {
                    $row[$key] = match ($type) {
                        'int', 'integer' => (int) $row[$key],
                        'bool', 'boolean' => (bool) $row[$key],
                        'string' => (string) $row[$key],
                        // The deleted-at column, as SoftDeletes::addCastAttributesToArray() keeps it.
                        'timestamp' => is_numeric($row[$key]) ? (int) $row[$key] : $row[$key],
                    };
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * The casts of the given columns when toArray() would do nothing else to them
     * than an int, bool or string cast (or the deleted-at timestamp SoftDeletes keeps);
     * false when one of them needs the model (an accessor, a float, date or object
     * cast, hidden/visible/appended attributes).
     *
     * @param list<string> $columns
     * @return array<string, string>|false
     */
    protected function scalarCastsFor(array $columns): array|false
    {
        // A subclass may change how a model becomes an array; it keeps doing so.
        if (static::class !== self::class
            || $this->getHidden() || $this->getVisible() || $this->getAppends()
            || array_intersect($columns, $this->getMutatedAttributes())) {
            return false;
        }
        $deletedAt = $this->getDeletedAtColumn();
        $casts = [];
        foreach (array_intersect_key($this->getCasts(), array_flip($columns)) as $key => $type) {
            $type = strtolower(trim((string) $type));
            if ($key === $deletedAt && $type === 'datetime') {
                // Arrayed as its stored timestamp, not as a date (see SoftDeletes).
                $casts[$key] = 'timestamp';
                continue;
            }
            if (!in_array($type, ['int', 'integer', 'bool', 'boolean', 'string'], true)) {
                return false;
            }
            $casts[$key] = $type;
        }
        foreach ($columns as $column) {
            if ($column !== $deletedAt && $this->isDateAttribute($column)) {
                return false;
            }
        }

        return $casts;
    }

    public function scopeWithoutProtected($query)
    {
        $docgrp = evo()->getUserDocGroups();
        // The join only serves the group condition below: without groups it would
        // cost a lookup per row (and repeat a document once per group it is in).
        if ($docgrp) {
            $query->leftJoin('document_groups', 'document_groups.document', '=', 'site_content.id');
        }
        $query->where(function($query) use ($docgrp) {
            if (evo()->isFrontend()) {
                $query->where('privateweb', 0);
            } else {
                $query->whereRaw("1 = {$_SESSION['mgrRole']}");
                $query->orWhere('site_content.privatemgr', 0);
            }
            if ($docgrp) {
                $query->orWhereIn('document_groups.document_group', $docgrp);
            }
        });

        return $query;
    }

    public function scopeWithTVs($query, $tvList = [], $sep = ':', $tree = false)
    {
        $main_table = 'site_content';
        if($tree){
            $main_table = 't2';
        }
        if (!empty($tvList)) {
            $query->addSelect($main_table.'.*');
            $tvList = array_unique($tvList);
            $tvListWithDefaults = [];
            foreach ($tvList as $v) {
                $tmp = explode($sep, $v, 2);
                $tvListWithDefaults[$tmp[0]] = !empty($tmp[1]) ? trim($tmp[1]) : '';
            }
            $tvs = SiteTmplvar::whereIn('name', array_keys($tvListWithDefaults))->get()->pluck('id', 'name')->toArray();
            foreach ($tvs as $tvname => $tvid) {
                $query = $query->leftJoin('site_tmplvar_contentvalues as tv_' . $tvname, function ($join) use ($main_table, $tvid, $tvname) {
                    $join->on($main_table.'.id', '=', 'tv_' . $tvname . '.contentid')->where('tv_' . $tvname . '.tmplvarid', '=', $tvid);
                });
                $query = $query->addSelect('tv_' . $tvname . '.value as ' . $tvname);
                $query = $query->groupBy('tv_' . $tvname . '.value');
                if (!empty($tvListWithDefaults[$tvname]) && $tvListWithDefaults[$tvname] == 'd') {
                    $query = $query->leftJoin('site_tmplvars as tvd_' . $tvname, function ($join) use ($tvid, $tvname) {
                        $join->where('tvd_' . $tvname . '.id', '=', $tvid);
                    });

                }
            }
            $query->groupBy($main_table.'.id');
        }
        return $query;
    }

    public function scopeTvFilter($query, $filters = '', $outerSep = ';', $innerSep = ':')
    {
        $prefix = evo()->getDatabase()->getConfig('prefix');
        $filters = explode($outerSep, trim($filters));
        if (count($filters) > self::MAX_TV_QUERY_TERMS) {
            // Fail closed, like a malformed filter: dropping the surplus would widen the result set
            return $query->whereRaw('1 = 0');
        }
        foreach ($filters as $filter) {
            if (empty($filter)) break;
            $parts = explode($innerSep, $filter, 5);
            $type = $parts[0];
            $tvname = $parts[1];
            $op = $parts[2];
            $value = !empty($parts[3]) ? $parts[3] : '';
            $cast = !empty($parts[4]) ? $parts[4] : '';
            // The name, operator and cast end up in raw SQL below, so refuse anything that is not plain
            if (!preg_match('/^[\w\-]+$/D', (string)$tvname)
                || !in_array(strtolower((string)$op), self::TV_FILTER_OPERATORS, true)
                || !preg_match('/^([A-Za-z]+(\(\d+(,\d+)?\))?)?$/D', (string)$cast)) {
                // Fail closed: dropping a malformed filter would widen the result set
                $query = $query->whereRaw('1 = 0');
                continue;
            }
            $field = 'tv_' . $tvname . '.value';
            if ($type == 'tvd') {
                $field = \DB::Raw("IFNULL(`" . $prefix . "tv_" . $tvname . "`.`value`, `" . $prefix . "tvd_" . $tvname . "`.`default_text`)");
            }
            switch (true) {
                case ($op == 'in'):
                    $query = $query->whereIn($field, explode(',', $value));
                    break;
                case ($op == 'not_in'):
                    $query = $query->whereNotIn($field, explode(',', $value));
                    break;
                case ($op == 'like'):
                    $query = $query->where($field, $op, '%' . $value . '%');
                    break;
                case ($op == 'like-r'):
                    $query = $query->where($field, $op, $value . '%');
                    break;
                case ($op == 'like-l'):
                    $query = $query->where($field, $op, '%' . $value);
                    break;
                case ($op == 'isnull'):
                case ($op == 'null'):
                    $query = $query->whereNull($field);
                    break;
                case ($op == 'isnotnull'):
                case ($op == '!null'):
                    $query = $query->whereNotNull($field);
                    break;
                case ($cast == 'UNSIGNED'):
                case ($cast == 'SIGNED'):
                case (strpos($cast, 'DECIMAL') !== false):
                    if (!is_numeric($value)) {
                        $value = 0;
                    }
                    $numericCast = (in_array(evo()->getDatabase()->getConfig('driver'), ['sqlite', 'sqlite3'], true))
                        ? 'INTEGER'
                        : $cast;
                    if ($type == 'tvd') {
                        $query = $query->whereRaw("CAST(IFNULL(`" . $prefix . "tv_" . $tvname . "`.`value`, `" . $prefix . "tvd_" . $tvname . "`.`default_text`) AS " . $numericCast . " ) " . $op . " " . $value);
                    } else {
                        $query = $query->whereRaw("CAST(`" . $prefix . 'tv_' . $tvname . "`.`value` AS " . $numericCast . " ) " . $op . " " . $value);
                    }
                    break;
                default:
                    $query = $query->where($field, $op, $value);
                    break;
            }
        }
        return $query;
    }

    public function scopeTvOrderBy($query, $orderBy = '', $sep = ':')
    {
        $prefix = evo()->getDatabase()->getConfig('prefix');
        $orderBy = array_slice(explode(',', trim($orderBy)), 0, self::MAX_TV_QUERY_TERMS);
        foreach ($orderBy as $parts) {
            if (empty(trim($parts))) return;
            $part = array_map('trim', explode(' ', trim($parts), 3));
            $tvname = $part[0];
            $sortDir = !empty($part[1]) ? $part[1] : 'desc';
            $cast = !empty($part[2]) ? $part[2] : '';
            // The name, direction and cast end up in raw SQL below, so refuse anything that is not plain
            $nameOnly = explode($sep, $tvname, 2)[0];
            if (!preg_match('/^[\w\-]+$/D', $nameOnly)
                || !preg_match('/^(asc|desc)$/iD', $sortDir)
                || !preg_match('/^([A-Za-z]+(\(\d+(,\d+)?\))?)?$/D', $cast)) {
                continue;
            }
            $driver = evo()->getDatabase()->getConfig('driver');
            $castType = $cast;
            if (in_array($driver, ['sqlite', 'sqlite3'], true) && $castType !== '') {
                $castType = 'INTEGER';
            }
            $withDefaults = false;
            if (strpos($tvname, $sep) !== false) {
                list($tvname, $withDefaults) = explode($sep, $tvname, 2);
                $withDefaults = !empty($withDefaults) && $withDefaults == 'd';
            }
            $field = 'tv_' . $tvname . ".value";
            if ($withDefaults === true) {
                $field = \DB::Raw("IFNULL(`" . $prefix . "tv_" . $tvname . "`.`value`, `" . $prefix . "tvd_" . $tvname . "`.`default_text`)");
            }
            switch (true) {
                case ($castType == 'UNSIGNED'):
                case ($castType == 'SIGNED'):
                case (strpos($castType, 'DECIMAL') !== false):
                    if ($withDefaults === false) {
                        $query = $query->orderByRaw("CAST(`" . $prefix . 'tv_' . $tvname . "`.`value` AS " . $castType . ") " . $sortDir);
                    } else {
                        $query = $query->orderByRaw("CAST(IFNULL(`" . $prefix . "tv_" . $tvname . "`.`value`, `" . $prefix . "tvd_" . $tvname . "`.`default_text`) AS " . $castType . ") " . $sortDir);
                    }
                    break;
                default:
                    $query = $query->orderBy($field, $sortDir);
                    break;
            }
        }
        return $query;
    }

    /**
     * @param $query
     * @param int $depth
     */
    public function scopeGetRootTree($query, $depth = 0)
    {
        return $query->select('t2.*')
            ->leftJoin('site_content_closure', function($join) use ($depth) {
                $join->on('site_content.id', '=', 'site_content_closure.ancestor');
                $join->on('site_content_closure.depth', '<', \DB::raw($depth));
            })
            ->join('site_content as t2', 't2.id', '=', 'site_content_closure.descendant' )
            ->where('site_content.parent', 0);
    }

    //return tvs array [$docid => tvs array()]
    public static function getTvList($docs, $tvList = [])
    {
        if (empty($docs) || empty($tvList)) {
            return [];
        }

        return static::getTvValues($docs->pluck('id')->toArray(), $tvList);
    }

    /**
     * Reads the given TVs of many documents in one query: [docid => [tvname => value]].
     *
     * Only the named TVs are read, through the (tmplvarid, contentid) unique index, so a
     * listing of cards, prices or any other per-document fields costs one query however
     * many documents it shows. Every requested document gets every TV that exists; a TV
     * without a stored (or with an empty) value falls back to its default_text unless
     * $withDefaults is false, then it is ''. Values are raw: no widget rendering and no
     * template assignment check, as with getTvList(). Returns [] when no TV matches.
     *
     * @param array $docIds document ids
     * @param array $tvNames TV names, or TV ids when every element is numeric
     */
    public static function getTvValues(array $docIds, array $tvNames, bool $withDefaults = true): array
    {
        $docIds = array_values(array_unique(array_map('intval', $docIds)));
        $tvNames = array_values(array_unique(array_filter(array_map('trim', array_map('strval', $tvNames)), 'strlen')));
        if ($docIds === [] || $tvNames === []) {
            return [];
        }
        $byId = count(array_filter($tvNames, 'is_numeric')) === count($tvNames);

        $rows = (new SiteTmplvar())->getConnection()
            ->table('site_tmplvars as tv')
            ->leftJoin('site_tmplvar_contentvalues as tvc', function ($join) use ($docIds) {
                $join->on('tvc.tmplvarid', '=', 'tv.id')->whereIntegerInRaw('tvc.contentid', $docIds);
            })
            ->whereIn($byId ? 'tv.id' : 'tv.name', $byId ? array_map('intval', $tvNames) : $tvNames)
            ->orderBy('tv.id')
            ->get(['tv.name', 'tv.default_text', 'tvc.contentid', 'tvc.value']);
        if ($rows->isEmpty()) {
            return [];
        }

        $defaults = [];
        $values = [];
        foreach ($rows as $row) {
            $defaults[$row->name] = $withDefaults ? (string) $row->default_text : '';
            if ($row->contentid !== null && (string) $row->value !== '') {
                $values[(int) $row->contentid][$row->name] = (string) $row->value;
            }
        }

        $result = [];
        foreach ($docIds as $docId) {
            $result[$docId] = array_replace($defaults, $values[$docId] ?? []);
        }

        return $result;
    }

    //return docs array with tvs
    public static function tvList($docs, $tvList = [])
    {
        if (empty($docs)) {
            return [];
        } else {
            $docsTV = static::getTvList($docs, $tvList);
            $docs = $docs->toArray();
            $tmp = $docs;
            foreach ($docs as $key => $doc) {
                $tmp[$key]['tvs'] = !empty($docsTV[$doc['id']]) ? $docsTV[$doc['id']] : [];
            }
            $docs = $tmp;
            unset($tmp);
            return $docs;
        }
    }

    public function scopeOrderByDate($query, $sortDir = 'desc')
    {
        $sortDir = strtolower(trim((string)$sortDir)) === 'asc' ? 'ASC' : 'DESC';

        return $query->orderByRaw('CASE WHEN pub_date != 0 THEN pub_date ELSE createdon END ' . $sortDir);
    }

    public function scopeTagsData($query, $tagsData, $sep = ':', $tagSeparator = ',')
    {
        $tmp = explode($sep, $tagsData, 2);
        if (is_numeric($tmp[0])) {
            $tv_id = $tmp[0];
            $tags = explode($tagSeparator, $tmp[1]);
            $query->select('site_content.*');
            $query->whereIn('tags.name', $tags)->where('site_content_tags.tv_id', $tv_id);
            $query->rightJoin('site_content_tags', 'site_content_tags.doc_id', '=', 'site_content.id');
            $query->rightJoin('tags', 'tags.id', '=', 'site_content_tags.tag_id');
        }
        return $query;
    }
}
