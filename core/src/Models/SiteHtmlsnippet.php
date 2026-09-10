<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;
use EvolutionCMS\Traits;

/**
 * EvolutionCMS\Models\SiteHtmlsnippet
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $editor_type
 * @property string $editor_name
 * @property int $category
 * @property bool $cache_type
 * @property string $snippet
 *      @deprecated since 3.5.8 A chunk's code belongs in views/chunks/<name>.html.
 *      Kept for chunks nobody has saved yet, and as the mirror the manager's
 *      search reads. Nothing renders from it once a file exists.
 *      @todo [remove@3.7] Remove in Evolution CMS 3.7
 * @property int $locked
 * @property int $createdon
 * @property int $editedon
 * @property int $disabled
 *
 * BelongsTo
 * @property null|Category $categories
 *
 * Virtual
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read bool $isAlreadyEdit
 * @property-read null|array $alreadyEditInfo
 * @property-read mixed $already_edit_info
 * @property-read mixed $is_already_edit
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\EvolutionCMS\Models\SiteHtmlsnippet lockedView()
 *
 * @mixin \Eloquent
 */
class SiteHtmlsnippet extends Eloquent\Model
{
    use Traits\Models\ManagerActions,
        Traits\Models\TimeMutator;

	const CREATED_AT = 'createdon';
	const UPDATED_AT = 'editedon';
    protected $dateFormat = 'U';

	protected $casts = [
		'editor_type' => 'int',
		'category' => 'int',
		'cache_type' => 'bool',
		'locked' => 'int',
		'createdon' => 'int',
		'editedon' => 'int',
		'disabled' => 'int'
	];

	protected $fillable = [
		'name',
		'description',
		'editor_type',
		'editor_name',
		'category',
		'cache_type',
		'snippet',
		'locked',
		'disabled'
	];

    protected $managerActionsMap = [
        'actions.cancel' => 76,
        'actions.new' => 77,
        'id' => [
            'actions.edit' => 78,
            'actions.save' => 79,
            'actions.enable' => 79,
            'actions.disable' => 79,
            'actions.delete' => 80,
            'actions.duplicate' => 97
        ]
    ];

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
        return evo()->getLockedElements(3);
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
