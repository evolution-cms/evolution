<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;
use EvolutionCMS\Traits;
use Illuminate\Support\Collection;

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
 * @property bool $donthit
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
 * @property Eloquent\Collection $childrens
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
    protected $dateFormat = 'U';

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
        'donthit' => 'bool',
        'privateweb' => 'bool',
        'privatemgr' => 'bool',
        'content_dispo' => 'bool',
        'hidemenu' => 'bool',
        'alias_visible' => 'int'
    ];

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
        'donthit',
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
        $key = evolutionCMS()->getConfig('resource_tree_node_name', 'pagetitle');
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
        return evolutionCMS()->getLockedElements(7);
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
    public function getAllChildrens($parent): array
    {
        $ids = [];
        foreach ($parent->childrens as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllChildrens($child));
        }
        return $ids;
    }
    
    /**
     * @return Collection
     */
    public function getDocument(): Collection
    {
        $out = $this->getAttributes();
        $tvs = $this->getTvAttribute()->pluck('value', 'name')->toArray();

        return collect(array_merge($out, $tvs));
    }

    /**
     * @return Collection
     */
    public function getTvAttribute(): Collection
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
    public function childrens(): Eloquent\Relations\HasMany
    {
        return $this->hasMany(__CLASS__, 'parent')
            ->withTrashed();
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
}
