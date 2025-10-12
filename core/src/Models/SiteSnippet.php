<?php namespace EvolutionCMS\Models;

use Illuminate\Database\Eloquent;
use EvolutionCMS\Traits;

/**
 * EvolutionCMS\Models\SiteSnippet
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $editor_type
 * @property int $category
 * @property bool $cache_type
 * @property string $snippet
 * @property int $locked
 * @property string $properties
 * @property string $moduleguid
 * @property int $createdon
 * @property int $editedon
 * @property int $disabled
 *
 * BelongsTo
 * @property null|Category $categories
 * @property null|SiteModule $module
 * @property null|SiteModule $activeModule
 *
 * Virtual
 * @property string $guid
 * @property-read bool $hasModule
 * @property-read \Carbon\Carbon $created_at
 * @property-read \Carbon\Carbon $updated_at
 * @property-read bool $isAlreadyEdit
 * @property-read null|array $alreadyEditInfo
 * @property-read mixed $already_edit_info
 * @property-read mixed $has_module
 * @property-read mixed $is_already_edit
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\EvolutionCMS\Models\SiteSnippet lockedView()
 *
 * @mixin \Eloquent
 */
class SiteSnippet extends Eloquent\Model
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
		'category',
		'cache_type',
		'snippet',
		'locked',
		'properties',
		'moduleguid',
		'disabled'
	];

    protected $managerActionsMap = [
        'actions.cancel' => 76,
        'actions.new' => 23,
        'id' => [
            'actions.edit' => 22,
            'actions.save' => 24,
            'actions.enable' => 24,
            'actions.disable' => 24,
            'actions.delete' => 25,
            'actions.duplicate' => 98
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
        return evo()->getLockedElements(4);
    }

    public function getIsAlreadyEditAttribute()
    {
        return array_key_exists($this->getKey(), self::getLockedElements());
    }

    public function getAlreadyEditInfoAttribute() :? array
    {
        return $this->isAlreadyEdit ? self::getLockedElements()[$this->getKey()] : null;
    }

    public function getGuidAttribute() : string
    {
        return trim($this->moduleguid);
    }

    public function setGuidAttribute($value)
    {
        $this->moduleguid = (string)$value;
    }

    public function getHasModuleAttribute() : bool
    {
        return !empty($this->guid);
    }

    public function module() : Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SiteModule::class, 'moduleguid', 'guid');
    }

    public function activeModule() : Eloquent\Relations\BelongsTo
    {
        return $this->module()
            ->where('disabled', '=', 0);
    }

    public function getSourceCodeAttribute(){
        return '<?php' . "\n" . trim($this->snippet) . "\n";
    }
}
