<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;
use EvolutionCMS\Traits;

/**
 * EvolutionCMS\Models\SiteTemplate
 *
 * @property int $id
 * @property string $templatename
 * @property string $description
 * @property int $editor_type
 * @property int $category
 * @property string $icon
 * @property int $template_type
 * @property string $content
 * @property int $locked
 * @property int $selectable
 * @property int $createdon
 * @property int $editedon
 *
 * Virtual
 * @property string $name Alias for templatename
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read bool $isAlreadyEdit
 * @property-read null|array $alreadyEditInfo
 *
 * BelongsTo
 * @property null|Category $categories
 *
 * BelongsToMany
 * @property Eloquent\Collection $tvs
 * @property-read mixed $already_edit_info
 * @property-read mixed $is_already_edit
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\EvolutionCMS\Models\SiteTemplate lockedView()
 *
 * @mixin \Eloquent
 */
class SiteTemplate extends Eloquent\Model
{
    use Traits\Models\ManagerActions,
        Traits\Models\TimeMutator;

	const CREATED_AT = 'createdon';
	const UPDATED_AT = 'editedon';
    protected $dateFormat = 'U';

	protected $casts = [
		'editor_type' => 'int',
		'category' => 'int',
		'template_type' => 'int',
		'locked' => 'int',
		'selectable' => 'int',
		'createdon' => 'int',
		'editedon' => 'int'
	];

	protected $fillable = [
		'templatename',
        'templatealias',
		'description',
		'editor_type',
		'category',
		'icon',
		'template_type',
		'content',
		'locked',
		'selectable'
	];

    protected $managerActionsMap = [
        'actions.cancel' => 76,
        'actions.new' => 19,
        'id' => [
            'actions.edit' => 16,
            'actions.save' => 20,
            'actions.delete' => 21,
            'actions.duplicate' => 96
        ]
    ];

	public function getNameAttribute()
    {
        return $this->templatename;
    }

    public function setNameAttribute($val)
    {
        $this->templatename = $val;
    }

    public function categories() : Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'category', 'id');
    }

    public function categoryName($default = '')
    {
        return $this->categories === null ? $default : $this->categories->category;
    }

    public function categoryId()
    {
        return $this->categories === null ? null : $this->categories->getKey();
    }

    /**
     * @return Eloquent\Relations\BelongsToMany
     */
    public function tvs() : Eloquent\Relations\BelongsToMany
    {

        return $this->belongsToMany(
            SiteTmplvar::class,
            (new SiteTmplvarTemplate())->getTable(),
            'templateid',
            'tmplvarid'
        )->withPivot('rank')
            ->orderBy('pivot_rank', 'ASC');
    }

    public function getCreatedAtAttribute()
    {
        return $this->convertTimestamp($this->createdon);
    }

    public function getUpdatedAtAttribute()
    {
        return $this->convertTimestamp($this->editedon);
    }

    public function scopeLockedView(Eloquent\Builder $builder)
    {
        return evo()->getLoginUserID('mgr') !== 1 ?
            $builder->where('locked', '=', 0) : $builder;
    }

    public static function getLockedElements()
    {
        return evo()->getLockedElements(1);
    }

    public function getIsAlreadyEditAttribute()
    {
        return array_key_exists($this->getKey(), self::getLockedElements());
    }

    public function getAlreadyEditInfoAttribute() :? array
    {
        return $this->isAlreadyEdit ? self::getLockedElements()[$this->getKey()] : null;
    }
}
